<?php
/**
 * Plugin Name: Inside Apparel Core
 * Description: Business logic untuk Inside Apparel (inventory, membership, voucher, affiliate, marketplace).
 * Version: 0.2.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: inside-apparel-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IA_CORE_VERSION', '0.2.0' );
define( 'IA_CORE_FILE', __FILE__ );
define( 'IA_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'IA_CORE_URL', plugin_dir_url( __FILE__ ) );

// Deklarasi kompatibilitas HPOS (High-Performance Order Storage).
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', IA_CORE_FILE, true );
		}
	}
);

require_once IA_CORE_PATH . 'includes/class-plugin.php';
require_once IA_CORE_PATH . 'includes/modules/inventory/class-installer.php';

register_activation_hook( __FILE__, array( 'IA_Inventory_Installer', 'activate' ) );

add_action( 'plugins_loaded', array( 'IA_Core_Plugin', 'init' ) );
