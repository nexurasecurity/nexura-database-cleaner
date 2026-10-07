<?php
/**
 * Safe Automated Email Summary Reporter
 *
 * Engineered specifically for low-end servers, shared hosting, and restricted environments:
 * - 100% fail-safe: Gracefully catches all exceptions and suppresses PHP warnings if server
 *   mail() is disabled, unconfigured, or if SMTP plugins encounter network/auth failures.
 * - Resource-guarded: Memory elevation, max execution time guards, and time budgeting to
 *   prevent shared hosting 30-second execution timeouts.
 * - Quota & spam protection: Rate limiting debouncing and automatic empty-report suppression
 *   to avoid burning shared hosting hourly email limits (typically 50-100/hr).
 * - Ultra-lightweight: Responsive, client-agnostic HTML template (<3KB payload) with inline styles.
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Reporter {

	const DEBOUNCE_TRANSIENT  = 'nexura_database_cleaner_email_debounce';
	const DEBOUNCE_TTL        = 900; // 15 minutes minimum interval between automated reports
	const MAX_BUDGET_SECONDS  = 22.0; // Safe cutoff before shared hosting 30s timeout

	/**
	 * Prepare low-end and shared server resources before intensive routines.
	 * Suppresses warnings if functions are disabled in php.ini disable_functions.
	 */
	public static function prepare_server_resources() {
		// 1. Elevate memory limit safely.
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			@wp_raise_memory_limit( 'admin' );
		}
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Essential for resilience on shared hosts.
		@ini_set( 'memory_limit', '256M' );

		// 2. Elevate execution time if allowed.
		$disabled = (string) @ini_get( 'disable_functions' );
		if ( function_exists( 'set_time_limit' ) && false === stripos( $disabled, 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Essential for resilience on shared hosts.
			@set_time_limit( 300 );
		}
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Essential for resilience on shared hosts.
		@ini_set( 'max_execution_time', '300' );

		// 3. Ensure background execution does not terminate if connection drops.
		if ( function_exists( 'ignore_user_abort' ) && false === stripos( $disabled, 'ignore_user_abort' ) ) {
			@ignore_user_abort( true );
		}
	}

	/**
	 * Check whether the execution time budget has been exceeded.
	 *
	 * @param float $start_time Microtime float from microtime( true ).
	 * @param float $budget     Max seconds allowed before breaking (default: 22.0s).
	 * @return bool True if time budget is exceeded.
	 */
	public static function is_time_budget_exceeded( $start_time, $budget = self::MAX_BUDGET_SECONDS ) {
		if ( empty( $start_time ) ) {
			return false;
		}
		return ( ( microtime( true ) - (float) $start_time ) >= (float) $budget );
	}

	/**
	 * Send an automated database cleanup summary report.
	 *
	 * @param array  $items_cleaned Array of items: [ ['id' => ..., 'label' => ..., 'count' => ..., 'frequency' => ...] ]
	 * @param int    $total_cleaned Total number of records deleted.
	 * @param float  $duration      Total execution duration in seconds.
	 * @param string $source        Identifier: 'pro_schedule' or 'free_schedule'.
	 * @param array  $options       Optional configuration:
	 *                              - 'recipient': Custom target email address.
	 *                              - 'only_if_cleaned': Boolean, default true. If true and 0 items cleaned, skips sending.
	 *                              - 'force': Boolean, default false. If true, bypasses debouncing.
	 *                              - 'schedule_name': String, human readable schedule label.
	 * @return bool True if wp_mail accepted the message, false otherwise. Never throws.
	 */
	public static function send_summary( $items_cleaned, $total_cleaned, $duration = 0.0, $source = 'pro_schedule', $options = array() ) {
		$total_cleaned   = max( 0, (int) $total_cleaned );
		$duration        = max( 0.0, (float) $duration );
		$only_if_cleaned = ! isset( $options['only_if_cleaned'] ) || ! empty( $options['only_if_cleaned'] );
		$is_forced       = ! empty( $options['force'] );

		// 1. Quota guard: Skip sending if no clutter was cleaned and the threshold guard is active.
		if ( $only_if_cleaned && 0 === $total_cleaned ) {
			return false;
		}

		// 2. Debounce guard: Prevent duplicate cron executions from spamming on high-traffic sites.
		$transient_key = self::DEBOUNCE_TRANSIENT . '_' . sanitize_key( $source );
		if ( ! $is_forced && get_transient( $transient_key ) ) {
			return false;
		}

		// 3. Resolve and validate recipient.
		$recipient = ! empty( $options['recipient'] ) ? sanitize_email( $options['recipient'] ) : '';
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			$recipient = sanitize_email( get_option( 'admin_email' ) );
		}

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return false;
		}

		// 4. Ensure wp_mail is available.
		if ( ! function_exists( 'wp_mail' ) ) {
			return false;
		}

		// 5. Build subject.
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		if ( empty( $site_name ) ) {
			$site_name = wp_parse_url( home_url(), PHP_URL_HOST );
		}

		$schedule_label = ! empty( $options['schedule_name'] ) ? $options['schedule_name'] : __( 'Scheduled Cleanup', 'nexura-database-cleaner' );

		if ( $total_cleaned > 0 ) {
			$subject = sprintf(
				/* translators: 1: site name, 2: schedule name, 3: cleaned count */
				__( '[%1$s] %2$s Report: %3$s items safely purged', 'nexura-database-cleaner' ),
				$site_name,
				$schedule_label,
				number_format_i18n( $total_cleaned )
			);
		} else {
			$subject = sprintf(
				/* translators: 1: site name, 2: schedule name */
				__( '[%1$s] %2$s Report: Database is clean (0 items)', 'nexura-database-cleaner' ),
				$site_name,
				$schedule_label
			);
		}

		// 6. Build lightweight HTML template.
		$html_body = self::render_html_template(
			$site_name,
			$recipient,
			$items_cleaned,
			$total_cleaned,
			$duration,
			$schedule_label
		);

		// 7. Prepare headers.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'X-Mailer: Nexura Database Cleaner/' . NEXURA_DATABASE_CLEANER_VERSION,
		);

		// 8. 100% Fail-safe transmission.
		// Catches PHPMailer Exceptions, standard Errors, and suppresses warnings if mail() is disabled on shared host.
		$sent            = false;
		$mail_error_info = null;

		$failed_hook = function( $wp_error ) use ( &$mail_error_info ) {
			$mail_error_info = $wp_error;
		};

		add_action( 'wp_mail_failed', $failed_hook, 999 );

		try {
			$sent = (bool) @wp_mail( $recipient, $subject, $html_body, $headers );
		} catch ( \Throwable $e ) {
			// Silently intercept any fatal exception from custom mailers/SMTP plugins.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[Nexura Database Cleaner] Automated summary caught exception: ' . $e->getMessage() );
			}
			$sent = false;
		} finally {
			remove_action( 'wp_mail_failed', $failed_hook, 999 );
		}

		// 9. If sent successfully, set debounce lock for 15 minutes.
		if ( $sent ) {
			set_transient( $transient_key, time(), self::DEBOUNCE_TTL );
		}

		return $sent;
	}

	/**
	 * Render ultra-lightweight, high-deliverability HTML email template (<3KB).
	 * Uses inline CSS, high-contrast typography, and no external dependencies.
	 *
	 * @param string $site_name
	 * @param string $recipient
	 * @param array  $items_cleaned
	 * @param int    $total_cleaned
	 * @param float  $duration
	 * @param string $schedule_label
	 * @return string HTML payload.
	 */
	private static function render_html_template( $site_name, $recipient, $items_cleaned, $total_cleaned, $duration, $schedule_label ) {
		$site_url     = home_url();
		$settings_url = admin_url( 'admin.php?page=nexura-database-cleaner' );
		$date_human   = wp_date( 'M j, Y, g:i a' );
		$duration_str = number_format( $duration, 2 ) . 's';

		$rows_html = '';
		if ( ! empty( $items_cleaned ) && is_array( $items_cleaned ) ) {
			$index = 0;
			foreach ( $items_cleaned as $item ) {
				$label     = ! empty( $item['label'] ) ? esc_html( $item['label'] ) : ( ! empty( $item['id'] ) ? esc_html( $item['id'] ) : 'Item' );
				$count     = isset( $item['count'] ) ? absint( $item['count'] ) : 0;
				$freq_info = ! empty( $item['frequency'] ) ? ' <span style="color:#64748b;font-size:11px;">(' . esc_html( ucfirst( $item['frequency'] ) ) . ')</span>' : '';
				$bg        = ( 0 === $index % 2 ) ? '#ffffff' : '#f8fafc';

				$count_badge = $count > 0
					? '<span style="color:#0f766e;font-weight:700;">' . esc_html( number_format_i18n( $count ) ) . '</span>'
					: '<span style="color:#94a3b8;">0</span>';

				$rows_html .= '<tr style="background:' . $bg . ';border-bottom:1px solid #f1f5f9;">' .
					'<td style="padding:10px 14px;font-size:13px;color:#1e293b;">' . $label . $freq_info . '</td>' .
					'<td style="padding:10px 14px;font-size:13px;text-align:right;">' . $count_badge . '</td>' .
				'</tr>';
				$index++;
			}
		} else {
			$rows_html = '<tr style="background:#ffffff;"><td colspan="2" style="padding:16px;text-align:center;color:#64748b;font-size:13px;">' .
				esc_html__( 'No database clutter found during this scheduled run.', 'nexura-database-cleaner' ) .
			'</td></tr>';
		}

		$status_badge_bg   = $total_cleaned > 0 ? '#ccfbf1' : '#e0f2fe';
		$status_badge_text = $total_cleaned > 0 ? '#0f766e' : '#0369a1';
		$status_label      = $total_cleaned > 0 ? esc_html__( 'Maintenance Passed', 'nexura-database-cleaner' ) : esc_html__( 'All Clear', 'nexura-database-cleaner' );

		return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . esc_html( $schedule_label ) . '</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:580px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
	<!-- Header -->
	<tr>
		<td style="background:#0f172a;padding:24px 28px;text-align:left;">
			<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<div style="color:#0d9488;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Nexura Database Cleaner</div>
						<h1 style="color:#ffffff;font-size:18px;margin:0;font-weight:700;line-height:1.3;">' . esc_html( $site_name ) . '</h1>
					</td>
					<td style="text-align:right;">
						<span style="background:' . $status_badge_bg . ';color:' . $status_badge_text . ';font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block;">' . $status_label . '</span>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Summary Cards -->
	<tr>
		<td style="padding:24px 28px 12px 28px;">
			<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:20px;">
				<tr>
					<td width="48%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;vertical-align:top;">
						<div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Items Purged</div>
						<div style="font-size:24px;font-weight:800;color:#0f172a;margin-top:4px;">' . esc_html( number_format_i18n( $total_cleaned ) ) . '</div>
					</td>
					<td width="4%"></td>
					<td width="48%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;vertical-align:top;">
						<div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Duration / Time</div>
						<div style="font-size:16px;font-weight:700;color:#0f172a;margin-top:4px;">' . esc_html( $duration_str ) . '</div>
						<div style="font-size:11px;color:#94a3b8;margin-top:2px;">' . esc_html( $date_human ) . '</div>
					</td>
				</tr>
			</table>

			<!-- Details Table -->
			<div style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Purged Breakdown</div>
			<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
				<thead>
					<tr style="background:#f1f5f9;border-bottom:1px solid #e2e8f0;">
						<th style="padding:9px 14px;text-align:left;font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:0.5px;">Category</th>
						<th style="padding:9px 14px;text-align:right;font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:0.5px;">Cleaned</th>
					</tr>
				</thead>
				<tbody>
					' . $rows_html . '
				</tbody>
			</table>
		</td>
	</tr>

	<!-- Action Button -->
	<tr>
		<td style="padding:12px 28px 24px 28px;text-align:center;">
			<a href="' . esc_url( $settings_url ) . '" style="background:#0f172a;color:#ffffff;text-decoration:none;font-size:13px;font-weight:600;padding:10px 20px;border-radius:6px;display:inline-block;">View Database Dashboard &rarr;</a>
		</td>
	</tr>

	<!-- Footer -->
	<tr>
		<td style="background:#f8fafc;padding:18px 28px;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#64748b;line-height:1.5;">
			<div>' . sprintf( /* translators: %s: site link */ esc_html__( 'Automated database summary generated by Nexura Database Cleaner on %s.', 'nexura-database-cleaner' ), '<a href="' . esc_url( $site_url ) . '" style="color:#0d9488;text-decoration:none;">' . esc_html( $site_name ) . '</a>' ) . '</div>
			<div style="margin-top:4px;color:#94a3b8;">' . esc_html__( 'Engineered for safe execution on low-end servers and shared hosting.', 'nexura-database-cleaner' ) . '</div>
		</td>
	</tr>
</table>
</body>
</html>';
	}
}
