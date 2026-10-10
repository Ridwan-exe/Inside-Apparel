<?php
/**
 * Mencatat perubahan stok yang dilakukan WooCommerce ke log Insan Apparel.
 *
 * Tiga sumber perubahan:
 * 1. Order website: stok dikurangi saat dibayar, dikembalikan saat dibatalkan.
 * 2. Edit langsung di layar produk WooCommerce (atau REST/impor WooCommerce).
 * 3. Produk baru yang langsung diberi stok.
 *
 * Perubahan oleh IA_Inventory_Service::adjust() diabaikan (sudah dicatat sendiri; lihat is_busy()).
 * Catatan: pengembalian stok lewat fitur refund WooCommerce ("Restock refunded items") tidak
 * dicatat di sini. Retur dicatat lewat Posisi Stok > Sesuaikan > "Retur dikembalikan ke stok".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Inventory_Woo_Hooks {

	/** Stok lama produk yang sedang disimpan: id produk => stok lama (null jika sebelumnya tidak dikelola). */
	private static array $stash = array();

	public static function init(): void {
		// Order website. Dipicu WooCommerce setelah stok order dikurangi / dikembalikan.
		add_action( 'woocommerce_reduce_order_stock', array( __CLASS__, 'sync_order' ) );
		add_action( 'woocommerce_restore_order_stock', array( __CLASS__, 'sync_order' ) );

		// Edit langsung. Variasi bisa memakai nama tipe objek berbeda antar versi WooCommerce, jadi keduanya didaftarkan.
		foreach ( array( 'product', 'product_variation' ) as $type ) {
			add_action( "woocommerce_before_{$type}_object_save", array( __CLASS__, 'stash_stock' ), 10, 1 );
			add_action( "woocommerce_after_{$type}_object_save", array( __CLASS__, 'log_direct_edit' ), 10, 1 );
		}

		// Produk baru dengan stok awal.
		add_action( 'woocommerce_new_product', array( __CLASS__, 'on_new_product' ), 10, 1 );
		add_action( 'woocommerce_new_product_variation', array( __CLASS__, 'on_new_product' ), 10, 1 );
	}

	private static function is_stock_product( $product ): bool {
		return $product instanceof WC_Product && in_array( $product->get_type(), array( 'simple', 'variation' ), true );
	}

	/**
	 * Menyamakan log dengan kondisi stok order. Berbasis status: membandingkan jumlah yang sudah
	 * dikurangi WooCommerce (_reduced_stock) dengan jumlah yang sudah kita catat (_ia_stock_logged),
	 * sehingga aman dipanggil berulang tanpa mencatat ganda.
	 *
	 * @param WC_Order|int $order
	 */
	public static function sync_order( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order ) {
			return;
		}

		// Order dari channel lain (mis. Shopee) ditangani modulnya sendiri.
		$channel = (string) $order->get_meta( '_ia_channel', true );
		if ( '' !== $channel && 'website' !== $channel ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			if ( ! $product_id ) {
				continue;
			}

			$reduced = max( 0, (int) $item->get_meta( '_reduced_stock', true ) );
			$logged  = max( 0, (int) $item->get_meta( '_ia_stock_logged', true ) );
			$diff    = $reduced - $logged;
			if ( 0 === $diff ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$after = (int) $product->get_stock_quantity();

			if ( $diff > 0 ) {
				$reason = 'order_website';
				$delta  = -$diff;
			} else {
				$reason = 0 === $reduced ? 'order_cancel' : 'koreksi';
				$delta  = -$diff;
			}

			$result = IA_Inventory_Service::record(
				(int) $product_id,
				$delta,
				$after,
				$reason,
				'website',
				array(
					'ref_type' => 'order',
					'ref_id'   => (string) $order->get_id(),
				)
			);
			if ( is_wp_error( $result ) ) {
				continue; // dicoba lagi pada pemicu berikutnya
			}

			if ( $reduced > 0 ) {
				$item->update_meta_data( '_ia_stock_logged', $reduced );
			} else {
				$item->delete_meta_data( '_ia_stock_logged' );
			}
			$item->save_meta_data();
		}
	}

	/** Sebelum produk disimpan: simpan stok lama bila stok akan berubah. */
	public static function stash_stock( $product ): void {
		if ( IA_Inventory_Service::is_busy() || ! self::is_stock_product( $product ) ) {
			return;
		}
		$id = $product->get_id();
		if ( ! $id ) {
			return; // produk baru ditangani on_new_product()
		}
		if ( ! array_key_exists( 'stock_quantity', $product->get_changes() ) ) {
			return;
		}
		$old = get_post_meta( $id, '_stock', true );

		self::$stash[ $id ] = ( '' === $old || null === $old || false === $old ) ? null : (int) $old;
	}

	/** Sesudah produk disimpan: catat selisih bila stok benar-benar berubah. */
	public static function log_direct_edit( $product ): void {
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$id = $product->get_id();
		if ( ! array_key_exists( $id, self::$stash ) ) {
			return;
		}
		$old = self::$stash[ $id ];
		unset( self::$stash[ $id ] );

		if ( true !== $product->managing_stock() ) {
			return;
		}
		$new   = (int) $product->get_stock_quantity();
		$delta = $new - (int) $old;
		if ( 0 === $delta ) {
			return;
		}

		$user_id = get_current_user_id();
		IA_Inventory_Service::record(
			$id,
			$delta,
			$new,
			null === $old ? 'stok_awal' : 'edit_langsung',
			$user_id ? 'admin' : 'system',
			array(
				'user_id'  => $user_id ? $user_id : null,
				'ref_type' => 'wc_edit',
			)
		);
	}

	public static function on_new_product( $product_id ): void {
		if ( IA_Inventory_Service::is_busy() ) {
			return;
		}
		$product = wc_get_product( $product_id );
		if ( ! self::is_stock_product( $product ) || true !== $product->managing_stock() ) {
			return;
		}
		$qty = (int) $product->get_stock_quantity();
		if ( $qty <= 0 ) {
			return;
		}

		$user_id = get_current_user_id();
		IA_Inventory_Service::record(
			(int) $product_id,
			$qty,
			$qty,
			'stok_awal',
			$user_id ? 'admin' : 'system',
			array(
				'user_id'  => $user_id ? $user_id : null,
				'ref_type' => 'wc_new',
			)
		);
	}
}
