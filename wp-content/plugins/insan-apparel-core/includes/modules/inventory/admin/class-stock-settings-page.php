<?php
/**
 * Pemeriksaan dan penerapan pengaturan WooCommerce yang diwajibkan desain stok
 * (docs/INVENTORY_DESIGN.md bagian 11). Tidak ada yang diubah sebelum tombol ditekan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Stock_Settings_Page {

	/** Pengaturan global WooCommerce: opsi => label dan nilai rekomendasi. */
	public static function recommended(): array {
		return array(
			'woocommerce_manage_stock'            => array(
				'label' => 'Aktifkan manajemen stok',
				'value' => 'yes',
			),
			'woocommerce_hold_stock_minutes'      => array(
				'label' => 'Tahan stok untuk order belum dibayar (menit)',
				'value' => '30',
			),
			'woocommerce_notify_low_stock'        => array(
				'label' => 'Notifikasi email stok rendah',
				'value' => 'yes',
			),
			'woocommerce_notify_no_stock'         => array(
				'label' => 'Notifikasi email stok habis',
				'value' => 'yes',
			),
			'woocommerce_notify_low_stock_amount' => array(
				'label' => 'Ambang stok rendah',
				'value' => '2',
			),
			'woocommerce_notify_no_stock_amount'  => array(
				'label' => 'Ambang stok habis',
				'value' => '0',
			),
		);
	}

	/** ID produk (sederhana + variasi) yang mengelola stok dan mengizinkan backorder. */
	private static function backorder_product_ids(): array {
		$ids = wc_get_products(
			array(
				'type'   => array( 'simple', 'variation' ),
				'status' => array( 'publish', 'private', 'draft' ),
				'limit'  => 2000,
				'return' => 'ids',
			)
		);
		$out = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p && true === $p->managing_stock() && 'no' !== $p->get_backorders() ) {
				$out[] = (int) $id;
			}
		}
		return $out;
	}

	private static function can_apply(): bool {
		return current_user_can( 'ia_manage_stock' ) && current_user_can( 'manage_woocommerce' );
	}

	public static function render(): void {
		if ( ! current_user_can( 'ia_manage_stock' ) ) {
			IA_Inventory_Module::deny();
		}

		echo '<div class="wrap"><h1>Pengaturan Stok</h1>';
		IA_Inventory_Module::render_flash();
		echo '<p>Pengaturan WooCommerce yang diperlukan agar stok akurat. Tidak ada yang berubah sebelum Anda menekan tombol.</p>';

		$need_change = 0;
		echo '<table class="wp-list-table widefat striped" style="max-width:820px;"><thead><tr><th>Pengaturan</th><th style="width:120px;">Saat ini</th><th style="width:120px;">Rekomendasi</th><th style="width:120px;">Status</th></tr></thead><tbody>';
		foreach ( self::recommended() as $option => $info ) {
			$current = (string) get_option( $option, '' );
			$ok      = $current === $info['value'];
			if ( ! $ok ) {
				++$need_change;
			}
			echo '<tr>';
			echo '<td>' . esc_html( $info['label'] ) . '</td>';
			echo '<td>' . ( '' === $current ? '<em>kosong</em>' : esc_html( $current ) ) . '</td>';
			echo '<td>' . esc_html( $info['value'] ) . '</td>';
			echo '<td>' . ( $ok ? '<span style="color:#1a7f37;">Sesuai</span>' : '<strong style="color:#b32d2e;">Perlu diubah</strong>' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px;">';
		echo '<input type="hidden" name="action" value="ia_stock_settings_apply">';
		wp_nonce_field( 'ia_stock_settings_apply' );
		if ( self::can_apply() ) {
			submit_button( 'Terapkan pengaturan rekomendasi', 'primary', 'submit', false, $need_change > 0 ? array() : array( 'disabled' => 'disabled' ) );
		} else {
			echo '<p><em>Menerapkan pengaturan memerlukan hak akses pengelola WooCommerce.</em></p>';
		}
		echo '</form>';

		$recipient = (string) get_option( 'woocommerce_stock_email_recipient', '' );
		printf(
			'<p class="description">Email notifikasi stok dikirim ke: <strong>%s</strong>. Ubah di WooCommerce &rarr; Pengaturan &rarr; Produk &rarr; Inventaris.</p>',
			esc_html( '' !== $recipient ? $recipient : '(belum diisi)' )
		);

		$backorders = self::backorder_product_ids();
		echo '<hr><h2>Backorder</h2>';
		if ( ! $backorders ) {
			echo '<p><span style="color:#1a7f37;">Sesuai.</span> Tidak ada produk yang mengizinkan backorder.</p>';
		} else {
			printf( '<p><strong style="color:#b32d2e;">%d produk/variasi</strong> mengizinkan backorder, sehingga stok bisa terjual melebihi persediaan. Desain stok mewajibkan backorder mati.</p>', count( $backorders ) );
			if ( self::can_apply() ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Matikan backorder pada semua produk yang mengizinkannya?\');">';
				echo '<input type="hidden" name="action" value="ia_stock_backorders_off">';
				wp_nonce_field( 'ia_stock_backorders_off' );
				submit_button( 'Matikan backorder pada semua produk', 'secondary', 'submit', false );
				echo '</form>';
			}
		}
		echo '</div>';
	}

	public static function handle_apply(): void {
		if ( ! self::can_apply() ) {
			IA_Inventory_Module::deny();
		}
		check_admin_referer( 'ia_stock_settings_apply' );

		$changed = 0;
		foreach ( self::recommended() as $option => $info ) {
			if ( (string) get_option( $option, '' ) !== $info['value'] ) {
				update_option( $option, $info['value'] );
				++$changed;
			}
		}
		IA_Inventory_Module::flash( 'success', sprintf( '%d pengaturan WooCommerce diperbarui.', $changed ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ia-stock-settings' ) );
		exit;
	}

	public static function handle_backorders_off(): void {
		if ( ! self::can_apply() ) {
			IA_Inventory_Module::deny();
		}
		check_admin_referer( 'ia_stock_backorders_off' );

		$count = 0;
		foreach ( self::backorder_product_ids() as $id ) {
			$p = wc_get_product( $id );
			if ( $p ) {
				$p->set_backorders( 'no' );
				$p->save();
				++$count;
			}
		}
		IA_Inventory_Module::flash( 'success', sprintf( 'Backorder dimatikan pada %d produk/variasi.', $count ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ia-stock-settings' ) );
		exit;
	}
}
