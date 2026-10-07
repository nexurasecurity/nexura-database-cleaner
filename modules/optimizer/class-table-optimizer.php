<?php
/**
 * Table Optimizer Module
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Optimizer;

use NexuraDatabaseCleaner\Includes\Database;
use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Table_Optimizer {

	/**
	 * Get list of tables with optimization stats.
	 *
	 * @return array
	 */
	public static function get_optimizable_tables() {
		$tables = Database::get_tables( true );

		$optimizable = array();
		foreach ( $tables as $table ) {
			$engine      = isset( $table['engine'] ) ? $table['engine'] : '';
			$is_innodb   = ( 0 === strcasecmp( $engine, 'InnoDB' ) );
			// In MySQL/MariaDB with InnoDB, tables allocate disk in 2 MB extent blocks.
			// This extent reserve is required by MySQL architecture and cannot be reduced to 0 B.
			$min_reserve = $is_innodb ? ( 2 * 1024 * 1024 ) : 0;
			$free_size   = (float) $table['free_size'];
			$reclaimable = max( 0, $free_size - $min_reserve );
			$is_optimal  = $is_innodb ? ( $free_size <= $min_reserve ) : ( $free_size <= 0 );

			$optimizable[] = array(
				'name'              => $table['name'],
				'engine'            => $engine,
				'rows'              => $table['rows'],
				'data_size'         => $table['data_size'],
				'data_size_human'   => Database::format_size( $table['data_size'] ),
				'index_size'        => $table['index_size'],
				'index_size_human'  => Database::format_size( $table['index_size'] ),
				'free_size'         => $free_size,
				'free_size_human'   => Database::format_size( $free_size ),
				'reclaimable'       => $reclaimable,
				'reclaimable_human' => Database::format_size( $reclaimable ),
				'total_size'        => $table['total_size'],
				'total_size_human'  => Database::format_size( $table['total_size'] ),
				'has_overhead'      => ( $reclaimable > 0 ),
				'is_optimal'        => $is_optimal,
				'is_core'           => $table['is_core'],
			);
		}

		return $optimizable;
	}

	/**
	 * Calculate total reclaimable overhead.
	 *
	 * @return float
	 */
	public static function get_total_overhead() {
		$tables = Database::get_tables( true );
		$overhead = 0;
		foreach ( $tables as $t ) {
			$engine      = isset( $t['engine'] ) ? $t['engine'] : '';
			$is_innodb   = ( 0 === strcasecmp( $engine, 'InnoDB' ) );
			$min_reserve = $is_innodb ? ( 2 * 1024 * 1024 ) : 0;
			$reclaimable = max( 0, (float) $t['free_size'] - $min_reserve );
			$overhead   += $reclaimable;
		}
		return (float) $overhead;
	}

	/**
	 * Optimize a single table.
	 *
	 * @param string $table_name
	 * @return array Result status and message.
	 */
	public static function optimize_table( $table_name ) {
		global $wpdb;

		// Large tables (2GB - 4GB+) take time to rebuild InnoDB extent pages
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 600 );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		// Security: table name must only contain alphanumeric characters and underscores
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table_name ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid table name format.', 'nexura-database-cleaner' ),
			);
		}

		// Ensure table exists in our active database
		$found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		if ( $found !== $table_name ) {
			return array(
				'success' => false,
				'message' => __( 'Table does not exist.', 'nexura-database-cleaner' ),
			);
		}

		// Run OPTIMIZE TABLE
		$escaped_table = esc_sql( $table_name );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$res = $wpdb->get_row( "OPTIMIZE TABLE `{$escaped_table}`", ARRAY_A );

		$msg_text = isset( $res['Msg_text'] ) ? $res['Msg_text'] : 'OK';
		$status   = isset( $res['Msg_type'] ) ? $res['Msg_type'] : 'status';

		// Invalidate cached scan so dashboard reflects newly optimized tables.
		delete_option( 'nexura_database_cleaner_last_scan_results' );

		return array(
			'success' => true,
			'table'   => $table_name,
			'status'  => $status,
			'message' => $msg_text,
		);
	}

	/**
	 * Analyze a single table.
	 *
	 * @param string $table_name
	 * @return array
	 */
	public static function analyze_table( $table_name ) {
		global $wpdb;

		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 300 );
		}

		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table_name ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid table name format.', 'nexura-database-cleaner' ),
			);
		}

		$found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		if ( $found !== $table_name ) {
			return array(
				'success' => false,
				'message' => __( 'Table does not exist.', 'nexura-database-cleaner' ),
			);
		}

		$escaped_table = esc_sql( $table_name );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$res = $wpdb->get_row( "ANALYZE TABLE `{$escaped_table}`", ARRAY_A );

		$msg_text = isset( $res['Msg_text'] ) ? $res['Msg_text'] : 'OK';
		$status   = isset( $res['Msg_type'] ) ? $res['Msg_type'] : 'status';

		return array(
			'success' => true,
			'table'   => $table_name,
			'status'  => $status,
			'message' => $msg_text,
		);
	}
}
