<?php
/**
 * Angka stok turunan (lihat docs/INVENTORY_DESIGN.md bagian 3).
 *
 *  Stok WooCommerce = stok setelah dikurangi order yang sudah dibayar
 *  Ditahan          = jumlah pada order belum dibayar yang masih ditahan WooCommerce (hold stock)
 *  Tersedia         = Stok WooCommerce - Ditahan   (yang boleh dijual sekarang)
 *  Dalam proses     = jumlah pada order dibayar yang belum dikirim
 *  Fisik            = Stok WooCommerce + Dalam proses   (barang yang masih ada di rak)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Stock_Metrics {

	/** Batas jumlah order yang dihitung untuk "Dalam proses". */
	const MAX_ORDERS = 2000;

	/**
	 * Jumlah stok yang sedang ditahan untuk order belum dibayar.
	 *
	 * @param WC_Product $product
	 * @return int|null null bila tidak dapat dihitung (mis. versi WooCommerce tanpa fitur ini).
	 */
	public static function held_quantity( $product ): ?int {
		$class = '\Automattic\WooCommerce\Checkout\Helpers\ReserveStock';
		if ( ! class_exists( $class ) ) {
			return null;
		}
		try {
			$reserve = new $class();
			return (int) $reserve->get_reserved_stock( $product, 0 );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Peta product_id => jumlah yang sudah dikurangi dari stok tetapi belum dikirim.
	 * Hanya item yang stoknya benar-benar sudah dikurangi WooCommerce (_reduced_stock) yang dihitung.
	 */
	public static function in_process_map(): array {
		/** Tambahkan status kustom (mis. "packed") lewat filter ini. */
		$statuses = apply_filters( 'ia_in_process_order_statuses', array( 'wc-processing' ) );
		$ids      = wc_get_orders(
			array(
				'status' => $statuses,
				'limit'  => self::MAX_ORDERS,
				'return' => 'ids',
			)
		);

		$map = array();
		foreach ( $ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$reduced = (int) $item->get_meta( '_reduced_stock', true );
				if ( $reduced <= 0 ) {
					continue;
				}
				$pid         = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
				$map[ $pid ] = ( $map[ $pid ] ?? 0 ) + $reduced;
			}
		}
		return $map;
	}

	/**
	 * Menghitung semua angka untuk satu produk.
	 *
	 * @param array $in_process_map Hasil in_process_map().
	 * @return array{wc:int, held:?int, available:int, in_process:int, physical:int}
	 */
	public static function for_product( $product, array $in_process_map ): array {
		$wc   = (int) $product->get_stock_quantity();
		$held = self::held_quantity( $product );
		$proc = (int) ( $in_process_map[ $product->get_id() ] ?? 0 );

		return self::compute( $wc, $held, $proc );
	}

	/** Perhitungan murni (mudah diuji). */
	public static function compute( int $wc, ?int $held, int $in_process ): array {
		return array(
			'wc'         => $wc,
			'held'       => $held,
			'available'  => $wc - ( $held ?? 0 ),
			'in_process' => $in_process,
			'physical'   => $wc + $in_process,
		);
	}

	/**
	 * Mengubah hasil hitung fisik di rak menjadi target Stok WooCommerce.
	 *
	 * @return int|null null bila fisik lebih kecil dari barang dalam proses (tidak masuk akal).
	 */
	public static function wc_target_from_physical( int $physical, int $in_process ): ?int {
		$target = $physical - $in_process;
		return $target < 0 ? null : $target;
	}
}
