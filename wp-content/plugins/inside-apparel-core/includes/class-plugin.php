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
        // Modul dimuat di sini satu per satu seiring development.
    }

    public static function missing_woocommerce_notice(): void {
        echo '<div class="notice notice-error"><p>'
            . esc_html__( 'Inside Apparel Core membutuhkan WooCommerce aktif.', 'inside-apparel-core' )
            . '</p></div>';
    }
}
