<?php
/**
 * Penghubung modul inventory: memuat file, mendaftarkan menu admin dan handler form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Inventory_Module {

	public static function init(): void {
		$dir = IA_CORE_PATH . 'includes/modules/inventory/';

		require_once $dir . 'class-installer.php';
		require_once $dir . 'class-stock-log.php';
		require_once $dir . 'class-inventory-service.php';
		require_once $dir . 'class-csv.php';

		IA_Inventory_Installer::maybe_upgrade();

		if ( ! is_admin() ) {
			return;
		}

		require_once $dir . 'admin/class-stock-position-page.php';
		require_once $dir . 'admin/class-stock-log-page.php';
		require_once $dir . 'admin/class-stock-import-page.php';

		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );

		add_action( 'admin_post_ia_stock_adjust', array( 'IA_Stock_Position_Page', 'handle_adjust' ) );
		add_action( 'admin_post_ia_stock_export', array( 'IA_Stock_Position_Page', 'handle_export' ) );
		add_action( 'admin_post_ia_stock_log_export', array( 'IA_Stock_Log_Page', 'handle_export' ) );
		add_action( 'admin_post_ia_stock_import_preview', array( 'IA_Stock_Import_Page', 'handle_preview' ) );
		add_action( 'admin_post_ia_stock_import_apply', array( 'IA_Stock_Import_Page', 'handle_apply' ) );
	}

	public static function register_menu(): void {
		add_menu_page(
			'Inside Apparel',
			'Inside Apparel',
			'ia_view_stock',
			'ia-stock',
			array( 'IA_Stock_Position_Page', 'render' ),
			'dashicons-archive',
			56
		);
		add_submenu_page( 'ia-stock', 'Posisi Stok', 'Posisi Stok', 'ia_view_stock', 'ia-stock', array( 'IA_Stock_Position_Page', 'render' ) );
		add_submenu_page( 'ia-stock', 'Log Stok', 'Log Stok', 'ia_view_stock', 'ia-stock-log', array( 'IA_Stock_Log_Page', 'render' ) );
		add_submenu_page( 'ia-stock', 'Impor Stok', 'Impor Stok', 'ia_manage_stock', 'ia-stock-import', array( 'IA_Stock_Import_Page', 'render' ) );
	}

	/** Pesan satu kali tampil (disimpan per pengguna selama 60 detik). */
	public static function flash( string $type, string $message ): void {
		if ( ! in_array( $type, array( 'success', 'error', 'warning' ), true ) ) {
			$type = 'error';
		}
		set_transient(
			'ia_flash_' . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			60
		);
	}

	public static function render_flash(): void {
		$key  = 'ia_flash_' . get_current_user_id();
		$data = get_transient( $key );
		if ( ! is_array( $data ) || empty( $data['message'] ) ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $data['type'] ),
			esc_html( $data['message'] )
		);
	}

	public static function deny(): void {
		wp_die( esc_html( 'Anda tidak memiliki izin untuk melakukan ini.' ), '', array( 'response' => 403 ) );
	}
}
