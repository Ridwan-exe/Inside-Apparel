<?php
/**
 * Pembuat tabel dan capability modul inventory.
 * Detail tabel: docs/DATABASE.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Inventory_Installer {

	const DB_VERSION = '1';
	const DB_OPTION  = 'ia_core_db_version';

	public static function table_stock_log(): string {
		global $wpdb;
		return $wpdb->prefix . 'ia_stock_log';
	}

	public static function table_sku_map(): string {
		global $wpdb;
		return $wpdb->prefix . 'ia_channel_sku_map';
	}

	/** Dipanggil saat plugin diaktifkan. */
	public static function activate(): void {
		self::create_tables();
		self::add_capabilities();
		update_option( self::DB_OPTION, self::DB_VERSION );
	}

	/** Dipanggil tiap request; hanya bekerja bila versi database berubah. */
	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_OPTION ) === self::DB_VERSION ) {
			return;
		}
		self::create_tables();
		self::add_capabilities();
		update_option( self::DB_OPTION, self::DB_VERSION );
	}

	private static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$log     = self::table_stock_log();
		$map     = self::table_sku_map();

		dbDelta(
			"CREATE TABLE {$log} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id BIGINT(20) UNSIGNED NOT NULL,
  sku VARCHAR(100) NOT NULL DEFAULT '',
  delta INT(11) NOT NULL,
  qty_before INT(11) NOT NULL,
  qty_after INT(11) NOT NULL,
  reason VARCHAR(40) NOT NULL,
  source VARCHAR(20) NOT NULL,
  ref_type VARCHAR(20) NULL,
  ref_id VARCHAR(64) NULL,
  note TEXT NULL,
  user_id BIGINT(20) UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY sku_created (sku,created_at),
  KEY product_id (product_id),
  KEY ref (ref_type,ref_id)
) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$map} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  sku VARCHAR(100) NOT NULL,
  product_id BIGINT(20) UNSIGNED NOT NULL,
  channel VARCHAR(20) NOT NULL,
  channel_item_id VARCHAR(64) NOT NULL DEFAULT '',
  channel_model_id VARCHAR(64) NULL,
  last_pushed_qty INT(11) NULL,
  last_pushed_at DATETIME NULL,
  sync_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  retry_count SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
  last_error TEXT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY channel_sku (channel,sku),
  KEY sync_status (sync_status)
) {$charset};"
		);
	}

	private static function add_capabilities(): void {
		$caps = array( 'ia_view_stock', 'ia_manage_stock', 'ia_manage_channels' );
		foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
}
