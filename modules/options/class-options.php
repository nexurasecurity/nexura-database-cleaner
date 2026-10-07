<?php
/**
 * Options Analyzer Module
 *
 * Implements strict option protection, autoload classification (PROTECTED, REVIEW, SAFE),
 * and safe autoload toggling (repot.text Sections 11 & 12).
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Options;

use NexuraDatabaseCleaner\Includes\Database;
use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Options {

	/**
	 * Default size threshold for large options in bytes (100 KB).
	 */
	const LARGE_OPTION_THRESHOLD = 102400;

	/**
	 * Minimum list of protected WordPress options that must NEVER have their
	 * autoload or existence modified by automated cleanup (repot.text Section 11).
	 */
	const PROTECTED_OPTIONS = array(
		'siteurl',
		'home',
		'blogname',
		'blogdescription',
		'admin_email',
		'users_can_register',
		'default_role',
		'active_plugins',
		'template',
		'stylesheet',
		'cron',
		'rewrite_rules',
		'db_version',
		'db_upgraded',
		'initial_db_version',
		'permalink_structure',
		'category_base',
		'tag_base',
		'upload_path',
		'upload_url_path',
		'wp_user_roles',
		'WPLANG',
		'nexura_database_cleaner_settings',
		'nexura_database_cleaner_schedule_settings',
		'nexura_database_cleaner_cleanup_history',
	);

	/**
	 * Check if an option is strictly protected.
	 *
	 * @param string $option_name
	 * @return bool
	 */
	public static function is_protected_option( $option_name ) {
		$option_name = sanitize_key( $option_name );
		return in_array( $option_name, self::PROTECTED_OPTIONS, true );
	}

	/**
	 * Classify an option: PROTECTED, SAFE, REVIEW, or UNKNOWN (repot.text Section 12).
	 *
	 * @param string $option_name
	 * @return string
	 */
	public static function classify_option( $option_name ) {
		if ( self::is_protected_option( $option_name ) ) {
			return 'PROTECTED';
		}

		// Known temporary caches or transients in options table
		if ( 0 === strpos( $option_name, '_transient_' ) || 0 === strpos( $option_name, '_site_transient_' ) ) {
			return 'SAFE';
		}

		// Known core options that shouldn't be altered lightly
		if ( 0 === strpos( $option_name, 'wp_' ) || 0 === strpos( $option_name, 'widget_' ) || 0 === strpos( $option_name, 'theme_mods_' ) ) {
			return 'REVIEW';
		}

		return 'REVIEW';
	}

	/**
	 * Get autoload stats (total count and total size in bytes).
	 *
	 * @return array
	 */
	public static function get_autoload_stats() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) as count, SUM(LENGTH(option_value)) as total_bytes FROM {$wpdb->options} WHERE autoload IN (%s, %s, %s, %s)",
				'yes',
				'on',
				'auto',
				'1'
			)
		);

		$total_bytes = (float) ( isset( $row->total_bytes ) ? $row->total_bytes : 0 );
		$count       = (int) ( isset( $row->count ) ? $row->count : 0 );

		return array(
			'count'            => $count,
			'total_bytes'      => $total_bytes,
			'total_human'      => Database::format_size( $total_bytes ),
			'is_warning_level' => $total_bytes > 800000, // WP recommendation is usually < 800KB
		);
	}

	/**
	 * Get largest autoloaded options with protection classification.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function get_largest_autoloaded_options( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, LENGTH(option_value) AS size_bytes, autoload
				 FROM {$wpdb->options}
				 WHERE autoload IN (%s, %s, %s, %s)
				 ORDER BY size_bytes DESC
				 LIMIT %d",
				'yes',
				'on',
				'auto',
				'1',
				$limit
			),
			ARRAY_A
		);
		if ( empty( $results ) ) {
			return array();
		}

		foreach ( $results as &$opt ) {
			$opt['size_human']     = Database::format_size( $opt['size_bytes'] );
			$opt['is_protected']   = self::is_protected_option( $opt['option_name'] );
			$opt['classification'] = self::classify_option( $opt['option_name'] );
		}

		return $results;
	}

	/**
	 * Get large options above threshold with classification.
	 *
	 * @param int $threshold Bytes
	 * @param int $limit
	 * @return array
	 */
	public static function get_large_options( $threshold = self::LARGE_OPTION_THRESHOLD, $limit = 50 ) {
		global $wpdb;
		$threshold = absint( $threshold );
		$limit     = absint( $limit );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, LENGTH(option_value) AS size_bytes, autoload
				 FROM {$wpdb->options}
				 WHERE LENGTH(option_value) >= %d
				 ORDER BY size_bytes DESC
				 LIMIT %d",
				$threshold,
				$limit
			),
			ARRAY_A
		);
		if ( empty( $results ) ) {
			return array();
		}

		foreach ( $results as &$opt ) {
			$opt['size_human']     = Database::format_size( $opt['size_bytes'] );
			$opt['is_protected']   = self::is_protected_option( $opt['option_name'] );
			$opt['classification'] = self::classify_option( $opt['option_name'] );
		}

		return $results;
	}

	/**
	 * Toggle autoload status for an option (e.g. from 'yes' to 'no').
	 * Rejects any request targeting a protected option (repot.text Section 11).
	 *
	 * @param int $option_id
	 * @param string $autoload 'yes' or 'no'
	 * @return array Result array with success boolean and message.
	 */
	public static function set_autoload( $option_id, $autoload = 'no' ) {
		global $wpdb;
		$option_id = absint( $option_id );
		$autoload  = ( 'yes' === $autoload ) ? 'yes' : 'no';

		// Verify option name
		$option_name = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_id = %d", $option_id )
		);

		if ( empty( $option_name ) ) {
			return array(
				'success' => false,
				'message' => __( 'Option not found.', 'nexura-database-cleaner' ),
			);
		}

		// Security: Prevent modifying protected options
		if ( self::is_protected_option( $option_name ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: option name */
					__( 'Option "%s" is a critical WordPress core setting and is protected from modification.', 'nexura-database-cleaner' ),
					esc_html( $option_name )
				),
			);
		}

		$updated = $wpdb->update(
			$wpdb->options,
			array( 'autoload' => $autoload ),
			array( 'option_id' => $option_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return array(
				'success' => false,
				'message' => __( 'Database update failed.', 'nexura-database-cleaner' ),
			);
		}

		return array(
			'success'  => true,
			'message'  => sprintf(
				/* translators: 1: option name, 2: autoload status */
				__( 'Autoload for "%1$s" updated to "%2$s".', 'nexura-database-cleaner' ),
				esc_html( $option_name ),
				esc_html( $autoload )
			),
			'autoload' => $autoload,
		);
	}
}
