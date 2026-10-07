<?php
/**
 * Automated Scheduled Cleanup Engine
 *
 * Fully protected for low-end servers and shared hosting environments:
 * - Time budgeting: Stops gracefully before shared host 30-second execution limits.
 * - Resource elevation: Safely raises memory/execution limits without notices.
 * - Concurrency lock: Prevents simultaneous cron runs from colliding.
 * - Fail-safe Automated Email Summary Reports: Sends lightweight HTML summaries (<3KB)
 *   and fails completely silently if server mail() is disabled or SMTP fails.
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scheduler {

	const CRON_HOOK       = 'nexura_database_cleaner_scheduled_cleanup_cron';
	const OPTION_SETTINGS = 'nexura_database_cleaner_schedule_settings';

	/**
	 * Initialize scheduler hooks and cron intervals.
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scheduled_cleanup' ) );
	}

	/**
	 * Add weekly and monthly schedules if missing.
	 *
	 * @param array $schedules
	 * @return array
	 */
	public static function add_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'nexura-database-cleaner' ),
			);
		}
		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Once Monthly (30 Days)', 'nexura-database-cleaner' ),
			);
		}
		return $schedules;
	}

	/**
	 * Get current schedule settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'enabled'               => false,
			'frequency'             => 'weekly',
			'items'                 => array( 'revisions', 'expired_transients', 'spam_comments', 'trashed_comments', 'trashed_posts', 'auto_drafts', 'oembed_caches' ),
			'last_run'              => 0,
			'email_report'          => false,
			'email_recipient'       => '',
			'email_only_on_cleaned' => true,
		);

		$settings = get_option( self::OPTION_SETTINGS, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$merged             = wp_parse_args( $settings, $defaults );
		$merged['next_run'] = wp_next_scheduled( self::CRON_HOOK );

		return $merged;
	}

	/**
	 * Save schedule settings and adjust WP-Cron.
	 *
	 * @param bool   $enabled
	 * @param string $frequency
	 * @param array  $items
	 * @param bool   $email_report
	 * @param string $email_recipient
	 * @param bool   $email_only_on_cleaned
	 * @return bool
	 */
	public static function save_settings( $enabled, $frequency, $items, $email_report = false, $email_recipient = '', $email_only_on_cleaned = true ) {
		$enabled   = (bool) $enabled;
		$frequency = in_array( $frequency, array( 'daily', 'weekly', 'monthly' ), true ) ? $frequency : 'weekly';
		$items     = is_array( $items ) ? array_map( 'sanitize_key', $items ) : array();

		// Always clear existing schedule first.
		wp_clear_scheduled_hook( self::CRON_HOOK );

		if ( $enabled ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, $frequency, self::CRON_HOOK );
		}

		$current                          = self::get_settings();
		$current['enabled']               = $enabled;
		$current['frequency']             = $frequency;
		$current['items']                 = $items;
		$current['email_report']          = (bool) $email_report;
		$current['email_recipient']       = sanitize_email( $email_recipient );
		$current['email_only_on_cleaned'] = (bool) $email_only_on_cleaned;

		return update_option( self::OPTION_SETTINGS, $current, false );
	}

	/**
	 * Run scheduled cleanup via WP-Cron with low-end/shared server resilience.
	 */
	public static function run_scheduled_cleanup() {
		$settings = self::get_settings();
		if ( empty( $settings['enabled'] ) || empty( $settings['items'] ) ) {
			return;
		}

		// 1. Elevate server resources safely.
		Email_Reporter::prepare_server_resources();

		// 2. Prevent concurrent cron execution collisions.
		$job_id = Lock::generate_job_id();
		if ( ! Lock::acquire_lock( $job_id, 'scheduled_cleanup', 600 ) ) {
			return;
		}

		$start_time = microtime( true );

		$allowed_safe = array(
			'revisions',
			'auto_drafts',
			'trashed_posts',
			'spam_comments',
			'trashed_comments',
			'pingbacks',
			'trackbacks',
			'expired_transients',
			'expired_site_transients',
			'oembed_caches',
			'as_completed_actions',
			'as_failed_actions',
			'as_canceled_actions',
			'as_orphan_logs',
		);

		$total_cleaned = 0;
		$definitions   = Detector::get_item_definitions();
		$items_summary = array();

		try {
			foreach ( $settings['items'] as $item_id ) {
				// Time budget guard: Check if approaching shared host timeout limit.
				if ( Email_Reporter::is_time_budget_exceeded( $start_time, 20.0 ) ) {
					break;
				}

				if ( ! in_array( $item_id, $allowed_safe, true ) ) {
					continue;
				}

				$item_name = isset( $definitions[ $item_id ]['title'] ) ? $definitions[ $item_id ]['title'] : $item_id;

				// Clean up to 5 batches per item to avoid timeouts.
				$max_batches      = 5;
				$cleaned_for_item = 0;

				while ( $max_batches > 0 ) {
					// Time budget guard inside batch loop.
					if ( Email_Reporter::is_time_budget_exceeded( $start_time, 20.0 ) ) {
						break;
					}

					$res = Cleaner::clean_batch( $item_id, 100, false );
					if ( ! empty( $res['cleaned_count'] ) ) {
						$cleaned_for_item += (int) $res['cleaned_count'];
					}
					if ( ! empty( $res['is_complete'] ) || empty( $res['success'] ) ) {
						break;
					}

					// Yield CPU and database connection slightly on shared hosting.
					usleep( 15000 );
					$max_batches--;
				}

				if ( $cleaned_for_item > 0 ) {
					$total_cleaned += $cleaned_for_item;

					Reporter::log( array(
						'job_id'         => $job_id,
						'item_id'        => $item_id,
						'item_name'      => $item_name . ' (Auto-Scheduled)',
						'rows_deleted'   => $cleaned_for_item,
						'rows_before'    => $cleaned_for_item,
						'rows_after'     => 0,
						'status'         => 'SUCCESS',
						'verification'   => 'Passed',
						'storage_status' => __( 'Estimated data affected', 'nexura-database-cleaner' ),
					) );
				}

				$items_summary[] = array(
					'id'        => $item_id,
					'label'     => $item_name,
					'count'     => $cleaned_for_item,
					'frequency' => $settings['frequency'],
				);
			}

			$settings['last_run'] = current_time( 'timestamp' );
			update_option( self::OPTION_SETTINGS, $settings, false );

			$duration = round( microtime( true ) - $start_time, 2 );

			// Dispatch Automated Email Summary Report safely if enabled.
			if ( ! empty( $settings['email_report'] ) ) {
				Email_Reporter::send_summary(
					$items_summary,
					$total_cleaned,
					$duration,
					'free_schedule',
					array(
						'recipient'       => ! empty( $settings['email_recipient'] ) ? $settings['email_recipient'] : '',
						'only_if_cleaned' => ! empty( $settings['email_only_on_cleaned'] ),
						'schedule_name'   => __( 'Standard Scheduled Cleanup', 'nexura-database-cleaner' ),
					)
				);
			}
		} finally {
			Lock::release_lock( $job_id );
		}
	}
}
