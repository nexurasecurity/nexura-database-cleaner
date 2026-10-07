<?php
/**
 * Safety rules and risk level definitions
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Safety {

	const RISK_SAFE    = 'safe';
	const RISK_LOW     = 'low';
	const RISK_MEDIUM  = 'medium';
	const RISK_HIGH    = 'high';
	const RISK_UNKNOWN = 'unknown';

	/**
	 * Default batch size for cleanup queries.
	 */
	const DEFAULT_BATCH_SIZE = 100;

	/**
	 * Max allowed batch size to safeguard database resources.
	 */
	const MAX_BATCH_SIZE = 500;

	/**
	 * Standard WordPress core table suffixes.
	 *
	 * @return array
	 */
	public static function get_core_table_suffixes() {
		return array(
			'commentmeta',
			'comments',
			'links',
			'options',
			'postmeta',
			'posts',
			'term_relationships',
			'term_taxonomy',
			'termmeta',
			'terms',
			'usermeta',
			'users',
			// Multisite tables
			'blogs',
			'blogmeta',
			'registration_log',
			'signups',
			'site',
			'sitemeta',
		);
	}

	/**
	 * Check if a given table is a core WordPress table.
	 *
	 * @param string $table_name Full table name.
	 * @return bool
	 */
	public static function is_core_table( $table_name ) {
		global $wpdb;

		$prefix = $wpdb->prefix;
		$base_prefix = $wpdb->base_prefix;

		$suffixes = self::get_core_table_suffixes();
		foreach ( $suffixes as $suffix ) {
			if ( $table_name === $prefix . $suffix || $table_name === $base_prefix . $suffix ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Verify if an operation is permitted under safety guidelines.
	 *
	 * @param string $risk_level Risk level.
	 * @return bool
	 */
	public static function is_operation_permitted( $risk_level ) {
		if ( $risk_level === self::RISK_UNKNOWN ) {
			return false;
		}
		return true;
	}

	/**
	 * Get formatted risk badge HTML.
	 *
	 * @param string $risk_level
	 * @return string
	 */
	public static function get_risk_badge( $risk_level ) {
		$labels = array(
			self::RISK_SAFE    => array( 'Safe', 'nexdbc-badge-safe' ),
			self::RISK_LOW     => array( 'Low Risk', 'nexdbc-badge-low' ),
			self::RISK_MEDIUM  => array( 'Medium Risk', 'nexdbc-badge-medium' ),
			self::RISK_HIGH    => array( 'High Risk', 'nexdbc-badge-high' ),
			self::RISK_UNKNOWN => array( 'Unknown', 'nexdbc-badge-unknown' ),
		);

		$info = isset( $labels[ $risk_level ] ) ? $labels[ $risk_level ] : $labels[ self::RISK_UNKNOWN ];

		return sprintf(
			'<span class="nexdbc-risk-badge %s">%s</span>',
			esc_attr( $info[1] ),
			esc_html( $info[0] )
		);
	}

	/**
	 * Sanitize and validate batch size.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function sanitize_batch_size( $batch_size ) {
		$size = absint( $batch_size );
		if ( $size <= 0 ) {
			$size = self::DEFAULT_BATCH_SIZE;
		}
		return min( $size, self::MAX_BATCH_SIZE );
	}
}
