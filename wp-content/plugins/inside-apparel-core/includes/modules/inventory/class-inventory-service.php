<?php
/**
 * Satu-satunya pintu perubahan stok dari kode Inside Apparel.
 * Kode lain TIDAK boleh menulis meta stok langsung. Lihat docs/INVENTORY_DESIGN.md bagian 2.
 *
 * Pemanggil bertanggung jawab memeriksa capability dan nonce sebelum memanggil service ini.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Inventory_Service {

	/** Bernilai true selama service sedang mengubah stok (hook WooCommerce Tahap 2 harus mengabaikan perubahan ini). */
	private static bool $busy = false;

	public static function is_busy(): bool {
		return self::$busy;
	}

	/**
	 * Memeriksa apakah produk dapat dikelola stoknya oleh service ini.
	 *
	 * @param mixed $product Objek produk WooCommerce atau false.
	 * @return true|WP_Error
	 */
	public static function check_product( $product ) {
		if ( ! $product ) {
			return new WP_Error( 'ia_not_found', 'Produk tidak ditemukan.' );
		}
		if ( ! in_array( $product->get_type(), array( 'simple', 'variation' ), true ) ) {
			return new WP_Error( 'ia_not_adjustable', 'Produk variabel tidak punya stok sendiri. Sesuaikan stok pada variasinya.' );
		}
		// Variasi yang memakai stok induk mengembalikan "parent", bukan true.
		if ( true !== $product->managing_stock() ) {
			return new WP_Error( 'ia_not_managed', 'Produk ini tidak mengelola stok sendiri. Aktifkan "Kelola stok" pada produk atau variasinya.' );
		}
		return true;
	}

	/**
	 * Mengubah stok sebesar $delta dan mencatatnya di log.
	 *
	 * @param int    $product_id ID produk sederhana atau variasi.
	 * @param int    $delta      Selisih; positif menambah, negatif mengurangi. Tidak boleh 0.
	 * @param string $reason     Kode alasan (IA_Stock_Log::reasons()).
	 * @param string $source     Sumber (IA_Stock_Log::sources()).
	 * @param array  $args       Opsional: ref_type, ref_id, note, user_id.
	 * @return array|WP_Error    Ringkasan perubahan, atau WP_Error.
	 */
	public static function adjust( int $product_id, int $delta, string $reason, string $source, array $args = array() ) {
		$args = array_merge(
			array(
				'ref_type' => null,
				'ref_id'   => null,
				'note'     => '',
				'user_id'  => null,
			),
			$args
		);

		if ( 0 === $delta ) {
			return new WP_Error( 'ia_zero_delta', 'Selisih stok tidak boleh 0.' );
		}
		if ( ! array_key_exists( $reason, IA_Stock_Log::reasons() ) ) {
			return new WP_Error( 'ia_bad_reason', 'Kode alasan tidak dikenal.' );
		}
		if ( ! array_key_exists( $source, IA_Stock_Log::sources() ) ) {
			return new WP_Error( 'ia_bad_source', 'Sumber tidak dikenal.' );
		}

		$product = wc_get_product( $product_id );
		$check   = self::check_product( $product );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$current = (int) $product->get_stock_quantity();
		if ( $current + $delta < 0 ) {
			return new WP_Error(
				'ia_negative_stock',
				sprintf( 'Stok tidak boleh negatif. Stok saat ini %d, perubahan %d.', $current, $delta )
			);
		}

		self::$busy = true;
		try {
			// Pembaruan atomik di level database (bukan baca lalu tulis ulang).
			$new = wc_update_product_stock( $product, abs( $delta ), $delta > 0 ? 'increase' : 'decrease' );
			if ( false === $new || null === $new ) {
				return new WP_Error( 'ia_update_failed', 'Gagal memperbarui stok.' );
			}
			$new = (int) $new;

			// Dua perubahan bersamaan bisa melewati pemeriksaan di atas. Batalkan jika hasilnya negatif.
			if ( $new < 0 ) {
				self::revert( $product, $delta );
				return new WP_Error( 'ia_negative_stock', 'Stok tidak mencukupi (berubah saat diproses). Coba lagi.' );
			}

			$log_id = IA_Stock_Log::insert(
				array(
					'product_id' => $product_id,
					'sku'        => (string) $product->get_sku(),
					'delta'      => $delta,
					'qty_before' => $new - $delta,
					'qty_after'  => $new,
					'reason'     => $reason,
					'source'     => $source,
					'ref_type'   => $args['ref_type'],
					'ref_id'     => $args['ref_id'],
					'note'       => sanitize_textarea_field( (string) $args['note'] ),
					'user_id'    => $args['user_id'],
				)
			);

			// Stok tanpa log tidak diperbolehkan: kembalikan perubahan jika log gagal ditulis.
			if ( false === $log_id ) {
				self::revert( $product, $delta );
				return new WP_Error( 'ia_log_failed', 'Gagal menulis log stok, perubahan dibatalkan.' );
			}

			// Menyimpan ulang agar status stok (tersedia/habis) mengikuti jumlah terbaru.
			$fresh = wc_get_product( $product_id );
			if ( $fresh ) {
				$fresh->save();
			}
		} finally {
			self::$busy = false;
		}

		/**
		 * Dipicu setelah stok berhasil diubah dan dicatat. Dipakai Tahap 3 untuk antrean push ke Shopee.
		 */
		do_action( 'ia_stock_adjusted', $product_id, $delta, $new, $reason, $source, $log_id );

		return array(
			'product_id' => $product_id,
			'sku'        => (string) $product->get_sku(),
			'delta'      => $delta,
			'qty_before' => $new - $delta,
			'qty_after'  => $new,
			'log_id'     => $log_id,
		);
	}

	/**
	 * Mengatur stok ke jumlah tertentu (stok opname atau impor stok awal).
	 *
	 * @return array|WP_Error WP_Error dengan kode ia_no_change bila stok sudah sama.
	 */
	public static function set_quantity( int $product_id, int $target, string $reason, string $source, array $args = array() ) {
		if ( $target < 0 ) {
			return new WP_Error( 'ia_negative_target', 'Jumlah stok tidak boleh negatif.' );
		}

		$product = wc_get_product( $product_id );
		$check   = self::check_product( $product );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$delta = $target - (int) $product->get_stock_quantity();
		if ( 0 === $delta ) {
			return new WP_Error( 'ia_no_change', 'Stok sudah sama.' );
		}

		return self::adjust( $product_id, $delta, $reason, $source, $args );
	}

	/**
	 * Mencatat perubahan stok yang SUDAH dilakukan pihak lain (mis. WooCommerce saat order dibayar).
	 * Tidak mengubah stok. Dipakai oleh hook WooCommerce; kode lain memakai adjust().
	 *
	 * @param int $qty_after Stok sesudah perubahan.
	 * @return array|WP_Error
	 */
	public static function record( int $product_id, int $delta, int $qty_after, string $reason, string $source, array $args = array() ) {
		$args = array_merge(
			array(
				'ref_type' => null,
				'ref_id'   => null,
				'note'     => '',
				'user_id'  => null,
			),
			$args
		);

		if ( 0 === $delta ) {
			return new WP_Error( 'ia_zero_delta', 'Selisih stok tidak boleh 0.' );
		}
		if ( ! array_key_exists( $reason, IA_Stock_Log::reasons() ) ) {
			return new WP_Error( 'ia_bad_reason', 'Kode alasan tidak dikenal.' );
		}
		if ( ! array_key_exists( $source, IA_Stock_Log::sources() ) ) {
			return new WP_Error( 'ia_bad_source', 'Sumber tidak dikenal.' );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'ia_not_found', 'Produk tidak ditemukan.' );
		}

		$log_id = IA_Stock_Log::insert(
			array(
				'product_id' => $product_id,
				'sku'        => (string) $product->get_sku(),
				'delta'      => $delta,
				'qty_before' => $qty_after - $delta,
				'qty_after'  => $qty_after,
				'reason'     => $reason,
				'source'     => $source,
				'ref_type'   => $args['ref_type'],
				'ref_id'     => $args['ref_id'],
				'note'       => sanitize_textarea_field( (string) $args['note'] ),
				'user_id'    => $args['user_id'],
			)
		);
		if ( false === $log_id ) {
			return new WP_Error( 'ia_log_failed', 'Gagal menulis log stok.' );
		}

		do_action( 'ia_stock_adjusted', $product_id, $delta, $qty_after, $reason, $source, $log_id );

		return array(
			'product_id' => $product_id,
			'sku'        => (string) $product->get_sku(),
			'delta'      => $delta,
			'qty_before' => $qty_after - $delta,
			'qty_after'  => $qty_after,
			'log_id'     => $log_id,
		);
	}

	private static function revert( $product, int $delta ): void {
		wc_update_product_stock( $product, abs( $delta ), $delta > 0 ? 'decrease' : 'increase' );
	}
}
