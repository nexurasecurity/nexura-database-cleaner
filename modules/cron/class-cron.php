<?php
/**
 * Cron Analyzer Module
 *
 * Implements strict cron protection for WordPress core and active plugins,
 * preventing accidental deletion of vital scheduled tasks (repot.text Section 13).
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cron {

	/**
	 * Core WordPress scheduled tasks that must NEVER be deleted.
	 */
	const CORE_CRON_HOOKS = array(
		'wp_version_check',
		'wp_update_plugins',
		'wp_update_themes',
		'wp_scheduled_delete',
		'wp_scheduled_auto_draft_delete',
		'recovery_mode_clean_expired_keys',
		'wp_privacy_delete_old_export_files',
		'wp_https_detection',
		'wp_site_health_scheduled_check',
		'delete_expired_transients',
		'wp_update_user_counts',
		'nexura_database_cleaner_scheduled_cleanup_cron',
		'action_scheduler_run_queue',
		'woocommerce_cleanup_sessions',
		'woocommerce_cleanup_personal_data',
		'woocommerce_cleanup_logs',
		'woocommerce_geoip_updater',
		'woocommerce_tracker_send_event',
	);

	/**
	 * Check if a cron hook is a protected WordPress core or internal job.
	 *
	 * @param string $hook
	 * @return bool
	 */
	public static function is_protected_cron( $hook ) {
		$hook = sanitize_text_field( $hook );
		if ( in_array( $hook, self::CORE_CRON_HOOKS, true ) ) {
			return true;
		}

		// Any hook starting with wp_ is standard core convention
		if ( 0 === strpos( $hook, 'wp_' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all scheduled cron jobs and classify orphans safely.
	 *
	 * @return array
	 */
	public static function get_cron_events() {
		$crons = _get_cron_array();
		if ( empty( $crons ) || ! is_array( $crons ) ) {
			return array();
		}

		$events = array();

		foreach ( $crons as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $keys ) {
				$has_callback = ( has_action( $hook ) !== false );
				$is_core      = self::is_protected_cron( $hook );

				// A cron is only a true potential orphan if it is NOT core AND has no active callback
				$is_orphan = ( ! $has_callback && ! $is_core );

				$classification = 'UNKNOWN';
				if ( $is_core ) {
					$classification = 'PROTECTED_CORE';
				} elseif ( $has_callback ) {
					$classification = 'ACTIVE_PLUGIN';
				} elseif ( $is_orphan ) {
					$classification = 'POTENTIAL_ORPHAN';
				}

				foreach ( $keys as $key => $data ) {
					$schedule = isset( $data['schedule'] ) && $data['schedule'] ? $data['schedule'] : 'Single Event';
					$args     = isset( $data['args'] ) ? $data['args'] : array();

					$events[] = array(
						'hook'           => $hook,
						'timestamp'      => $timestamp,
						'next_run'       => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' ),
						'schedule'       => $schedule,
						'args'           => $args,
						'has_callback'   => $has_callback,
						'is_core'        => $is_core,
						'is_orphan'      => $is_orphan,
						'classification' => $classification,
						'key'            => $key,
					);
				}
			}
		}

		return $events;
	}

	/**
	 * Count confirmed orphaned cron jobs.
	 *
	 * @return int
	 */
	public static function count_orphaned_crons() {
		$events  = self::get_cron_events();
		$orphans = 0;
		foreach ( $events as $ev ) {
			if ( ! empty( $ev['is_orphan'] ) ) {
				$orphans++;
			}
		}
		return $orphans;
	}

	/**
	 * Remove a specific scheduled hook safely.
	 * Rejects any attempt to delete protected core tasks (repot.text Section 13).
	 *
	 * @param string $hook
	 * @param int $timestamp
	 * @param array $args
	 * @return array Result array with success status and message.
	 */
	public static function remove_cron_event( $hook, $timestamp, $args = array() ) {
		$hook      = sanitize_text_field( $hook );
		$timestamp = absint( $timestamp );

		// Security: Prevent deleting core protected cron events
		if ( self::is_protected_cron( $hook ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: cron hook name */
					__( 'Scheduled event "%s" is a protected WordPress task and cannot be deleted.', 'nexura-database-cleaner' ),
					esc_html( $hook )
				),
			);
		}

		$removed = wp_unschedule_event( $timestamp, $hook, $args );

		if ( ! $removed ) {
			return array(
				'success' => false,
				'message' => __( 'Could not unschedule event. It may have already run or been removed.', 'nexura-database-cleaner' ),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: cron hook name */
				__( 'Event "%s" successfully unscheduled.', 'nexura-database-cleaner' ),
				esc_html( $hook )
			),
		);
	}
}
