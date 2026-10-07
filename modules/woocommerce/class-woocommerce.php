<?php
/**
 * WooCommerce Analyzer Module
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\WooCommerce;

use NexuraDatabaseCleaner\Includes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class WooCommerce {

	/**
	 * List of standard WooCommerce tables without prefix.
	 *
	 * @return array
	 */
	public static function get_known_wc_tables() {
		return array(
			'woocommerce_order_items',
			'woocommerce_order_itemmeta',
			'woocommerce_payment_tokens',
			'woocommerce_payment_tokenmeta',
			'woocommerce_shipping_zones',
			'woocommerce_shipping_zone_locations',
			'woocommerce_shipping_zone_methods',
			'woocommerce_tax_rates',
			'woocommerce_tax_rate_locations',
			'woocommerce_attribute_taxonomies',
			'woocommerce_downloadable_product_permissions',
			'woocommerce_sessions',
			'wc_orders',
			'wc_order_addresses',
			'wc_order_operational_data',
			'wc_orders_meta',
			'wc_order_stats',
			'wc_order_product_lookup',
			'wc_customer_lookup',
			'wc_category_lookup',
			'wc_order_tax_lookup',
			'wc_order_coupon_lookup',
			'wc_admin_notes',
			'wc_admin_note_actions',
			'wc_reserved_stock',
			'actionscheduler_actions',
			'actionscheduler_claims',
			'actionscheduler_groups',
			'actionscheduler_logs',
		);
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_wc_active() {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( 'woocommerce/woocommerce.php' );
		}

		if ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( function_exists( 'is_plugin_active' ) ) {
				return is_plugin_active( 'woocommerce/woocommerce.php' );
			}
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );
		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true );
	}

	/**
	 * Analyze WooCommerce tables status.
	 *
	 * @return array
	 */
	public static function analyze() {
		global $wpdb;

		$is_active = self::is_wc_active();
		$wc_tables = self::get_known_wc_tables();
		$prefix    = $wpdb->prefix;

		$existing_wc_tables = array();
		$total_size = 0;
		$total_rows = 0;

		foreach ( $wc_tables as $suffix ) {
			$table_name = $prefix . $suffix;
			$exists     = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );

			if ( $exists === $table_name ) {
				$status = $wpdb->get_row( $wpdb->prepare( "SHOW TABLE STATUS LIKE %s", $table_name ) );
				$rows   = isset( $status->Rows ) ? (int) $status->Rows : 0;
				$size   = (float) ( ( isset( $status->Data_length ) ? $status->Data_length : 0 ) + ( isset( $status->Index_length ) ? $status->Index_length : 0 ) );

				$total_size += $size;
				$total_rows += $rows;

				$existing_wc_tables[] = array(
					'name'        => $table_name,
					'rows'        => $rows,
					'size_human'  => Database::format_size( $size ),
					'size_bytes'  => $size,
				);
			}
		}

		return array(
			'is_active'          => $is_active,
			'tables_found_count' => count( $existing_wc_tables ),
			'total_rows'         => $total_rows,
			'total_size'         => $total_size,
			'total_size_human'   => Database::format_size( $total_size ),
			'tables'             => $existing_wc_tables,
			'status_note'        => $is_active
				? __( 'WooCommerce is currently active. All WooCommerce tables are protected and active.', 'nexura-database-cleaner' )
				: __( 'WooCommerce is inactive. Review these tables before deciding if they are still needed.', 'nexura-database-cleaner' ),
		);
	}
}
