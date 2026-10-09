<?php
/**
 * Log perubahan stok (append-only).
 * Tidak ada fungsi ubah atau hapus. Lihat docs/INVENTORY_DESIGN.md bagian 4.3.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Stock_Log {

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'ia_stock_log';
	}

	/** Semua kode alasan: kode => label. */
	public static function reasons(): array {
		return array(
			'stok_awal'       => 'Stok awal / impor',
			'order_website'   => 'Order website',
			'order_cancel'    => 'Order website dibatalkan',
			'return_restock'  => 'Retur dikembalikan ke stok',
			'order_shopee'    => 'Order Shopee',
			'shopee_cancel'   => 'Order Shopee dibatalkan',
			'opname'          => 'Stok opname',
			'rusak_hilang'    => 'Barang rusak / hilang',
			'koreksi'         => 'Koreksi',
			'sync_correction' => 'Koreksi sinkronisasi',
			'edit_langsung'   => 'Edit langsung WooCommerce',
		);
	}

	/** Alasan yang boleh dipilih admin pada penyesuaian manual, beserta mode yang diizinkan. */
	public static function manual_reasons(): array {
		return array(
			'opname'         => array(
				'label' => 'Stok opname',
				'modes' => array( 'add', 'subtract', 'set' ),
			),
			'stok_awal'      => array(
				'label' => 'Stok awal',
				'modes' => array( 'add', 'set' ),
			),
			'rusak_hilang'   => array(
				'label' => 'Barang rusak / hilang',
				'modes' => array( 'subtract' ),
			),
			'koreksi'        => array(
				'label' => 'Koreksi salah input',
				'modes' => array( 'add', 'subtract', 'set' ),
			),
			'return_restock' => array(
				'label' => 'Retur dikembalikan ke stok',
				'modes' => array( 'add' ),
			),
		);
	}

	public static function sources(): array {
		return array(
			'website' => 'Website',
			'shopee'  => 'Shopee',
			'admin'   => 'Admin',
			'system'  => 'Sistem',
			'pos'     => 'POS (disiapkan)',
		);
	}

	/**
	 * Menulis satu baris log.
	 *
	 * @return int|false ID log, atau false jika gagal.
	 */
	public static function insert( array $row ) {
		global $wpdb;

		$ok = $wpdb->insert(
			self::table(),
			array(
				'product_id' => (int) $row['product_id'],
				'sku'        => (string) $row['sku'],
				'delta'      => (int) $row['delta'],
				'qty_before' => (int) $row['qty_before'],
				'qty_after'  => (int) $row['qty_after'],
				'reason'     => (string) $row['reason'],
				'source'     => (string) $row['source'],
				'ref_type'   => isset( $row['ref_type'] ) ? (string) $row['ref_type'] : null,
				'ref_id'     => isset( $row['ref_id'] ) ? (string) $row['ref_id'] : null,
				'note'       => isset( $row['note'] ) && '' !== $row['note'] ? (string) $row['note'] : null,
				'user_id'    => isset( $row['user_id'] ) && $row['user_id'] ? (int) $row['user_id'] : null,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $ok ? (int) $wpdb->insert_id : false;
	}

	/** Membersihkan filter dari input mentah (GET). */
	public static function sanitize_filters( array $raw ): array {
		$f = array(
			'sku'       => '',
			'reason'    => '',
			'source'    => '',
			'user_id'   => 0,
			'date_from' => '',
			'date_to'   => '',
		);

		if ( isset( $raw['sku'] ) ) {
			$f['sku'] = sanitize_text_field( wp_unslash( $raw['sku'] ) );
		}
		if ( isset( $raw['reason'] ) ) {
			$reason = sanitize_key( wp_unslash( $raw['reason'] ) );
			if ( array_key_exists( $reason, self::reasons() ) ) {
				$f['reason'] = $reason;
			}
		}
		if ( isset( $raw['source'] ) ) {
			$source = sanitize_key( wp_unslash( $raw['source'] ) );
			if ( array_key_exists( $source, self::sources() ) ) {
				$f['source'] = $source;
			}
		}
		if ( isset( $raw['user_id'] ) ) {
			$f['user_id'] = absint( $raw['user_id'] );
		}
		foreach ( array( 'date_from', 'date_to' ) as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$date = sanitize_text_field( wp_unslash( $raw[ $key ] ) );
				if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
					$f[ $key ] = $date;
				}
			}
		}

		return $f;
	}

	private static function build_where( array $f ): array {
		global $wpdb;
		$where = array();
		$args  = array();

		if ( '' !== $f['sku'] ) {
			$where[] = 'sku LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $f['sku'] ) . '%';
		}
		if ( '' !== $f['reason'] ) {
			$where[] = 'reason = %s';
			$args[]  = $f['reason'];
		}
		if ( '' !== $f['source'] ) {
			$where[] = 'source = %s';
			$args[]  = $f['source'];
		}
		if ( $f['user_id'] > 0 ) {
			$where[] = 'user_id = %d';
			$args[]  = $f['user_id'];
		}
		// Tanggal yang dimasukkan admin adalah tanggal zona waktu situs; kolom tersimpan UTC.
		if ( '' !== $f['date_from'] ) {
			$where[] = 'created_at >= %s';
			$args[]  = get_gmt_from_date( $f['date_from'] . ' 00:00:00' );
		}
		if ( '' !== $f['date_to'] ) {
			$where[] = 'created_at <= %s';
			$args[]  = get_gmt_from_date( $f['date_to'] . ' 23:59:59' );
		}

		return array( $where ? 'WHERE ' . implode( ' AND ', $where ) : '', $args );
	}

	private static function prepared( string $sql, array $args ): string {
		global $wpdb;
		// prepare() tanpa placeholder menimbulkan peringatan; query tanpa argumen tidak memuat input pengguna.
		return $args ? $wpdb->prepare( $sql, $args ) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * @return array{rows: array, total: int}
	 */
	public static function query( array $filters, int $per_page, int $page ): array {
		global $wpdb;
		$table = self::table();
		list( $where, $args ) = self::build_where( $filters );

		$total = (int) $wpdb->get_var( self::prepared( "SELECT COUNT(*) FROM {$table} {$where}", $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$offset = max( 0, $page - 1 ) * $per_page;
		$rows   = $wpdb->get_results(
			self::prepared(
				"SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( $args, array( $per_page, $offset ) )
			)
		);

		return array(
			'rows'  => $rows ? $rows : array(),
			'total' => $total,
		);
	}

	/** Pengguna yang pernah mengubah stok: user_id => nama. */
	public static function logged_users(): array {
		global $wpdb;
		$table = self::table();
		$ids   = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$table} WHERE user_id IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$out   = array();
		foreach ( (array) $ids as $id ) {
			$user = get_userdata( (int) $id );
			if ( $user ) {
				$out[ (int) $id ] = $user->display_name;
			}
		}
		return $out;
	}
}
