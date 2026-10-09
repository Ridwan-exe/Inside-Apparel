<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Core_Plugin {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_woocommerce_notice' ) );
			return;
		}

		require_once IA_CORE_PATH . 'includes/modules/inventory/class-inventory-module.php';
		IA_Inventory_Module::init();
	}

	public static function missing_woocommerce_notice(): void {
		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Inside Apparel Core membutuhkan WooCommerce aktif.', 'inside-apparel-core' )
			. '</p></div>';
	}
}
