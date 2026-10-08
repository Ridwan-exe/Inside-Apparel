<?php
/**
 * Plugin Name: Inside Apparel Core
 * Description: Business logic untuk Inside Apparel (inventory, membership, voucher, affiliate, marketplace).
 * Version: 0.1.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: inside-apparel-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IA_CORE_VERSION', '0.1.0' );
define( 'IA_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'IA_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once IA_CORE_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( 'IA_Core_Plugin', 'init' ) );
