<?php
/**
 * Action Scheduler Cleaner Module
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\WooCommerce;

use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
class Action_Scheduler {

	/**
	 * Check if Action Scheduler tables exist.
	 *
	 * @return bool
	 */
	public static function has_tables() {
		global $wpdb;
		static $exists = null;
		if ( null !== $exists ) {
			return $exists;
		}

		$table  = $wpdb->prefix . 'actionscheduler_actions';
		$found  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		$exists = ( $found === $table );
		return $exists;
	}

	/**
	 * Count completed actions older than $days (default 30 days).
	 *
	 * @param int $days
	 * @return int
	 */
	public static function count_completed_actions( $days = 30 ) {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );
		$table  = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE `status` = 'complete' AND `last_attempt_gmt` < %s",
				$cutoff
			)
		);
	}

	/**
	 * Clean completed actions older than $days.
	 *
	 * @param int $batch_size
	 * @param int $days
	 * @return int
	 */
	public static function clean_completed_actions( $batch_size = 100, $days = 30 ) {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$cutoff     = gmdate( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );
		$actions_t  = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		$logs_t     = esc_sql( $wpdb->prefix . 'actionscheduler_logs' );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `action_id` FROM `{$actions_t}` WHERE `status` = 'complete' AND `last_attempt_gmt` < %s LIMIT %d",
				$cutoff,
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		$wpdb->query( "DELETE FROM `{$logs_t}` WHERE `action_id` IN ({$id_list})" );
		return (int) $wpdb->query( "DELETE FROM `{$actions_t}` WHERE `action_id` IN ({$id_list})" );
	}

	/**
	 * Count failed actions.
	 *
	 * @return int
	 */
	public static function count_failed_actions() {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE `status` = 'failed'" );
	}

	/**
	 * Clean failed actions.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_failed_actions( $batch_size = 100 ) {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$actions_t  = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		$logs_t     = esc_sql( $wpdb->prefix . 'actionscheduler_logs' );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `action_id` FROM `{$actions_t}` WHERE `status` = 'failed' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		$wpdb->query( "DELETE FROM `{$logs_t}` WHERE `action_id` IN ({$id_list})" );
		return (int) $wpdb->query( "DELETE FROM `{$actions_t}` WHERE `action_id` IN ({$id_list})" );
	}

	/**
	 * Count canceled actions.
	 *
	 * @return int
	 */
	public static function count_canceled_actions() {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE `status` = 'canceled'" );
	}

	/**
	 * Clean canceled actions.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_canceled_actions( $batch_size = 100 ) {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$actions_t  = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		$logs_t     = esc_sql( $wpdb->prefix . 'actionscheduler_logs' );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `action_id` FROM `{$actions_t}` WHERE `status` = 'canceled' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		$wpdb->query( "DELETE FROM `{$logs_t}` WHERE `action_id` IN ({$id_list})" );
		return (int) $wpdb->query( "DELETE FROM `{$actions_t}` WHERE `action_id` IN ({$id_list})" );
	}

	/**
	 * Count orphan Action Scheduler logs (logs where action no longer exists).
	 *
	 * @return int
	 */
	public static function count_orphan_logs() {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$actions_t = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		$logs_t    = esc_sql( $wpdb->prefix . 'actionscheduler_logs' );

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$logs_t}` l
			 LEFT JOIN `{$actions_t}` a ON l.action_id = a.action_id
			 WHERE a.action_id IS NULL"
		);
	}

	/**
	 * Clean orphan Action Scheduler logs.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_orphan_logs( $batch_size = 100 ) {
		if ( ! self::has_tables() ) {
			return 0;
		}
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$actions_t  = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		$logs_t     = esc_sql( $wpdb->prefix . 'actionscheduler_logs' );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT l.log_id FROM `{$logs_t}` l
				 LEFT JOIN `{$actions_t}` a ON l.action_id = a.action_id
				 WHERE a.action_id IS NULL
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		return (int) $wpdb->query( "DELETE FROM `{$logs_t}` WHERE `log_id` IN ({$id_list})" );
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
