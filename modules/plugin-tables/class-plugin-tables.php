<?php
/**
 * Plugin Tables Analyzer Module
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Plugin_Tables;

use NexuraDatabaseCleaner\Includes\Database;
use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin_Tables {

	/**
	 * Known plugin signatures mapping prefix pattern to plugin main file/slug.
	 *
	 * @return array
	 */
	public static function get_known_plugin_signatures() {
		return array(
			'yoast_indexable'    => array(
				'name' => 'Yoast SEO',
				'slug' => 'wordpress-seo/wp-seo.php',
			),
			'wf'                 => array(
				'name' => 'Wordfence Security',
				'slug' => 'wordfence/wordfence.php',
			),
			'redirection_'       => array(
				'name' => 'Redirection',
				'slug' => 'redirection/redirection.php',
			),
			'revslider_'         => array(
				'name' => 'Slider Revolution',
				'slug' => 'revslider/revslider.php',
			),
			'actionscheduler_'   => array(
				'name' => 'Action Scheduler',
				'slug' => 'woocommerce/woocommerce.php', // Or standalone
			),
			'wpforms_'           => array(
				'name' => 'WPForms',
				'slug' => 'wpforms-lite/wpforms.php',
			),
			'ewwwio_'            => array(
				'name' => 'EWWW Image Optimizer',
				'slug' => 'ewww-image-optimizer/ewww-image-optimizer.php',
			),
			'rank_math_'         => array(
				'name' => 'Rank Math SEO',
				'slug' => 'seo-by-rank-math/rank-math.php',
			),
			'aioseo_'            => array(
				'name' => 'All in One SEO',
				'slug' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			),
		);
	}

	/**
	 * Analyze tables and detect potentially abandoned plugin tables.
	 *
	 * @return array
	/**
	 * Check if a plugin is active safely.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public static function check_plugin_active( $slug ) {
		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( $slug );
		}

		if ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( function_exists( 'is_plugin_active' ) ) {
				return is_plugin_active( $slug );
			}
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( in_array( $slug, $active_plugins, true ) ) {
			return true;
		}

		if ( is_multisite() && function_exists( 'get_site_option' ) ) {
			$network_active = (array) get_site_option( 'active_sitewide_plugins', array() );
			if ( isset( $network_active[ $slug ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Analyze tables and detect potentially abandoned plugin tables.
	 *
	 * @return array
	 */
	public static function analyze_tables() {
		global $wpdb;

		$tables     = Database::get_tables( true );
		$signatures = self::get_known_plugin_signatures();
		$prefix     = $wpdb->prefix;

		$results = array();

		foreach ( $tables as $table ) {
			$name = $table['name'];

			// Skip core tables completely
			if ( Safety::is_core_table( $name ) ) {
				continue;
			}

			// Strip WordPress prefix
			$relative_name = ( 0 === strpos( $name, $prefix ) ) ? substr( $name, strlen( $prefix ) ) : $name;

			$matched_plugin = null;
			foreach ( $signatures as $pattern => $info ) {
				if ( 0 === strpos( $relative_name, $pattern ) ) {
					$matched_plugin = $info;
					break;
				}
			}

			$is_plugin_active = false;
			if ( $matched_plugin ) {
				$is_plugin_active = self::check_plugin_active( $matched_plugin['slug'] );
			}

			$reasons = array();
			$confidence = 'Low';
			$status = 'unknown';

			if ( $matched_plugin ) {
				$reasons[] = __( 'Matches known signature for: ', 'nexura-database-cleaner' ) . $matched_plugin['name'];

				if ( ! $is_plugin_active ) {
					$reasons[] = __( 'Associated plugin is currently inactive or not installed.', 'nexura-database-cleaner' );
					$confidence = 'High';
					$status     = 'abandoned';
				} else {
					$reasons[] = __( 'Associated plugin is currently active.', 'nexura-database-cleaner' );
					$confidence = 'Low';
					$status     = 'active_plugin';
				}
			} else {
				$reasons[] = __( 'Custom table not recognized as WordPress core.', 'nexura-database-cleaner' );
				$status    = 'custom';
			}

			$results[] = array(
				'table_name'  => $name,
				'rows'        => $table['rows'],
				'size_human'  => Database::format_size( $table['total_size'] ),
				'size_bytes'  => $table['total_size'],
				'status'      => $status,
				'confidence'  => $confidence,
				'reasons'     => $reasons,
				'plugin_name' => $matched_plugin ? $matched_plugin['name'] : 'Unknown',
			);
		}

		return $results;
	}
}
