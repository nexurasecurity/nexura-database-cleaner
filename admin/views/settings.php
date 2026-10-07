<?php
/**
 * Settings and Advanced Tools View
 *
 * @package NexuraDatabaseCleaner
 */

use NexuraDatabaseCleaner\Modules\Options\Options;
use NexuraDatabaseCleaner\Modules\Cron\Cron;
use NexuraDatabaseCleaner\Modules\Plugin_Tables\Plugin_Tables;
use NexuraDatabaseCleaner\Modules\WooCommerce\WooCommerce;
use NexuraDatabaseCleaner\Includes\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/header.php';

$batch_size         = isset( $settings['batch_size'] ) ? absint( $settings['batch_size'] ) : 100;
$schedule_settings  = Scheduler::get_settings();
$autoload_stats     = Options::get_autoload_stats();
$large_options      = Options::get_large_options( 51200, 10 ); // > 50KB top 10
$cron_events        = Cron::get_cron_events();
$plugin_tables      = Plugin_Tables::analyze_tables();
$wc_analysis        = WooCommerce::analyze();
?>

<div class="nexdbc-content">
	<div class="nexdbc-section-title">
		<h2><?php esc_html_e( 'Plugin Settings & Advanced Analyzers', 'nexura-database-cleaner' ); ?></h2>
		<p><?php esc_html_e( 'Configure cleanup batch parameters, automated background schedules, and diagnostic analyzers.', 'nexura-database-cleaner' ); ?></p>
	</div>

	<!-- Core Settings Form -->
	<div class="nexdbc-card">
		<h3 class="nexdbc-card-header"><?php esc_html_e( 'General & Automated Cleanup Settings', 'nexura-database-cleaner' ); ?></h3>
		<form id="nexdbc-form-settings" class="nexdbc-form">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="nexdbc-batch-size"><?php esc_html_e( 'Batch Size (rows per request)', 'nexura-database-cleaner' ); ?></label>
						</th>
						<td>
							<input type="number" id="nexdbc-batch-size" name="batch_size" min="10" max="500" step="10" value="<?php echo esc_attr( $batch_size ); ?>" class="small-text">
							<p class="description">
								<?php esc_html_e( 'Number of records removed per asynchronous batch request (10 to 500). Smaller sizes avoid timeouts on shared servers.', 'nexura-database-cleaner' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Automated Scheduled Cleanup', 'nexura-database-cleaner' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="nexdbc-schedule-enabled">
									<input type="checkbox" id="nexdbc-schedule-enabled" name="schedule_enabled" value="1" <?php checked( ! empty( $schedule_settings['enabled'] ) ); ?>>
									<strong><?php esc_html_e( 'Enable Automated Background Database Cleanup', 'nexura-database-cleaner' ); ?></strong>
								</label>
								<p class="description">
									<?php esc_html_e( 'Automatically purges selected safe clutter in the background using WordPress Cron without requiring manual interaction.', 'nexura-database-cleaner' ); ?>
								</p>
								<?php
								nexura_database_cleaner_upgrade_prompt(
									__( 'This free schedule uses one interval for every selected item. Pro gives each cleaner its own hourly, daily, weekly, or monthly interval.', 'nexura-database-cleaner' )
								);
								?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nexdbc-schedule-frequency"><?php esc_html_e( 'Schedule Frequency', 'nexura-database-cleaner' ); ?></label>
						</th>
						<td>
							<select id="nexdbc-schedule-frequency" name="schedule_frequency">
								<option value="daily" <?php selected( $schedule_settings['frequency'], 'daily' ); ?>><?php esc_html_e( 'Daily (Once Every 24 Hours)', 'nexura-database-cleaner' ); ?></option>
								<option value="weekly" <?php selected( $schedule_settings['frequency'], 'weekly' ); ?>><?php esc_html_e( 'Weekly (Once Every 7 Days)', 'nexura-database-cleaner' ); ?></option>
								<option value="monthly" <?php selected( $schedule_settings['frequency'], 'monthly' ); ?>><?php esc_html_e( 'Monthly (Once Every 30 Days)', 'nexura-database-cleaner' ); ?></option>
							</select>
							<?php if ( ! empty( $schedule_settings['enabled'] ) && ! empty( $schedule_settings['next_run'] ) ) : ?>
								<p class="description" style="color: #008a20; margin-top: 6px;">
									<span class="dashicons dashicons-clock" style="font-size: 16px; vertical-align: text-bottom;"></span>
									<?php
									printf(
										/* translators: %s: formatted next run date */
										esc_html__( 'Next automated run: %s', 'nexura-database-cleaner' ),
										esc_html( wp_date( 'Y-m-d H:i:s', $schedule_settings['next_run'] ) )
									);
									?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Email Summary Report', 'nexura-database-cleaner' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="nexdbc-schedule-email-report" style="font-weight: 600; cursor: pointer;">
									<input type="checkbox" id="nexdbc-schedule-email-report" name="schedule_email_report" value="1" <?php checked( ! empty( $schedule_settings['email_report'] ) ); ?>>
									<?php esc_html_e( 'Send Automated Email Summary Report after cleanup', 'nexura-database-cleaner' ); ?>
								</label>
								<div id="nexdbc-email-report-options" style="<?php echo empty( $schedule_settings['email_report'] ) ? 'display: none;' : ''; ?> margin-top: 10px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; max-width: 520px;">
									<p style="margin: 0 0 8px 0;">
										<label for="nexdbc-schedule-email-recipient" style="display: block; font-size: 13px; font-weight: 500; color: #475569; margin-bottom: 4px;">
											<?php esc_html_e( 'Recipient Email (leave blank to use Admin Email):', 'nexura-database-cleaner' ); ?>
										</label>
										<input type="email" id="nexdbc-schedule-email-recipient" name="schedule_email_recipient" value="<?php echo esc_attr( ! empty( $schedule_settings['email_recipient'] ) ? $schedule_settings['email_recipient'] : '' ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text" style="width: 100%; max-width: 360px;">
									</p>
									<label style="display: block; margin-top: 8px; font-size: 13px; color: #475569; cursor: pointer;">
										<input type="checkbox" name="schedule_email_only_cleaned" value="1" <?php checked( ! isset( $schedule_settings['email_only_on_cleaned'] ) || ! empty( $schedule_settings['email_only_on_cleaned'] ) ); ?>>
										<?php esc_html_e( 'Only send email when clutter was cleaned (Recommended for shared hosting)', 'nexura-database-cleaner' ); ?>
									</label>
									<p class="description" style="margin: 6px 0 0 0; font-size: 12px; color: #64748b;">
										<span class="dashicons dashicons-shield" style="font-size: 15px; vertical-align: text-bottom; color: #0d9488;"></span>
										<?php esc_html_e( 'Low-end & Shared Hosting Protected: Timeouts are automatically prevented and disabled server mail will fail silently without errors.', 'nexura-database-cleaner' ); ?>
									</p>
								</div>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Items to Clean Automatically', 'nexura-database-cleaner' ); ?>
						</th>
						<td>
							<?php
							$sched_items = isset( $schedule_settings['items'] ) ? (array) $schedule_settings['items'] : array();
							$safe_options = array(
								'revisions'            => __( 'Post Revisions', 'nexura-database-cleaner' ),
								'auto_drafts'          => __( 'Auto Drafts', 'nexura-database-cleaner' ),
								'trashed_posts'        => __( 'Trashed Posts', 'nexura-database-cleaner' ),
								'spam_comments'        => __( 'Spam Comments', 'nexura-database-cleaner' ),
								'trashed_comments'     => __( 'Trashed Comments', 'nexura-database-cleaner' ),
								'expired_transients'   => __( 'Expired Transients', 'nexura-database-cleaner' ),
								'oembed_caches'        => __( 'oEmbed Caches', 'nexura-database-cleaner' ),
								'as_completed_actions' => __( 'Action Scheduler: Completed Actions (> 30 days)', 'nexura-database-cleaner' ),
							);
							?>
							<fieldset style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px;">
								<?php foreach ( $safe_options as $key => $label ) : ?>
									<label>
										<input type="checkbox" name="schedule_items[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $sched_items, true ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'nexura-database-cleaner' ); ?></button>
			</p>
		</form>
	</div>

	<!-- Options & Autoload Diagnostics -->
	<div class="nexdbc-card">
		<h3 class="nexdbc-card-header"><?php esc_html_e( 'wp_options & Autoload Analyzer', 'nexura-database-cleaner' ); ?></h3>
		<p>
			<?php esc_html_e( 'Total Autoloaded Options Size:', 'nexura-database-cleaner' ); ?>
			<strong class="<?php echo esc_attr( $autoload_stats['is_warning_level'] ? 'nexdbc-text-danger' : 'nexdbc-text-success' ); ?>">
				<?php echo esc_html( $autoload_stats['total_human'] ); ?>
			</strong>
			(<?php echo esc_html( number_format_i18n( $autoload_stats['count'] ) ); ?> <?php esc_html_e( 'autoloaded options', 'nexura-database-cleaner' ); ?>)
			<?php if ( $autoload_stats['is_warning_level'] ) : ?>
				<span class="nexdbc-badge nexdbc-badge-warning"><?php esc_html_e( 'Exceeds recommended 800 KB', 'nexura-database-cleaner' ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( ! empty( $large_options ) ) : ?>
			<h4><?php esc_html_e( 'Largest Options in Database (> 50 KB)', 'nexura-database-cleaner' ); ?></h4>
			<table class="wp-list-table widefat fixed striped nexdbc-table-small">
				<thead>
					<tr>
						<th style="width: 40%;"><?php esc_html_e( 'Option Name', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Size', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Autoload', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 20%; text-align: right;"><?php esc_html_e( 'Action', 'nexura-database-cleaner' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $large_options as $opt ) : ?>
						<tr data-option-id="<?php echo esc_attr( $opt['option_id'] ); ?>">
							<td><code><?php echo esc_html( $opt['option_name'] ); ?></code></td>
							<td><?php echo esc_html( $opt['size_human'] ); ?></td>
							<td>
								<span class="nexdbc-badge <?php echo esc_attr( 'yes' === $opt['autoload'] ? 'nexdbc-badge-warning' : 'nexdbc-badge-safe' ); ?>">
									<?php echo esc_html( $opt['autoload'] ); ?>
								</span>
							</td>
							<td style="text-align: right;">
								<?php if ( 'yes' === $opt['autoload'] ) : ?>
									<button type="button" class="button button-small nexdbc-btn-toggle-autoload" data-id="<?php echo esc_attr( $opt['option_id'] ); ?>" data-autoload="no">
										<?php esc_html_e( 'Disable Autoload', 'nexura-database-cleaner' ); ?>
									</button>
								<?php else : ?>
									<button type="button" class="button button-small nexdbc-btn-toggle-autoload" data-id="<?php echo esc_attr( $opt['option_id'] ); ?>" data-autoload="yes">
										<?php esc_html_e( 'Enable Autoload', 'nexura-database-cleaner' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- Cron Diagnostics -->
	<div class="nexdbc-card">
		<h3 class="nexdbc-card-header"><?php esc_html_e( 'Cron Analyzer', 'nexura-database-cleaner' ); ?></h3>
		<p><?php esc_html_e( 'Scheduled background tasks in WordPress. Events without active callbacks may be orphaned from deleted plugins.', 'nexura-database-cleaner' ); ?></p>
		<table class="wp-list-table widefat fixed striped nexdbc-table-small">
			<thead>
				<tr>
					<th style="width: 35%;"><?php esc_html_e( 'Hook Name', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 25%;"><?php esc_html_e( 'Next Scheduled Run', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 20%;"><?php esc_html_e( 'Callback Status', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 20%; text-align: right;"><?php esc_html_e( 'Actions', 'nexura-database-cleaner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$orphan_count = 0;
				foreach ( $cron_events as $ev ) :
					if ( $ev['is_orphan'] ) :
						$orphan_count++;
				?>
					<tr>
						<td><code><?php echo esc_html( $ev['hook'] ); ?></code></td>
						<td><?php echo esc_html( $ev['next_run'] ); ?></td>
						<td>
							<span class="nexdbc-badge nexdbc-badge-warning"><?php esc_html_e( 'No Callback Registered', 'nexura-database-cleaner' ); ?></span>
						</td>
						<td style="text-align: right;">
							<button type="button" class="button button-small nexdbc-btn-delete-cron" data-hook="<?php echo esc_attr( $ev['hook'] ); ?>" data-timestamp="<?php echo esc_attr( $ev['timestamp'] ); ?>">
								<?php esc_html_e( 'Unschedule', 'nexura-database-cleaner' ); ?>
							</button>
						</td>
					</tr>
				<?php
					endif;
				endforeach;

				if ( 0 === $orphan_count ) :
				?>
					<tr>
						<td colspan="4" class="nexdbc-empty-notice-table">
							<span class="dashicons dashicons-yes-alt nexdbc-icon-green"></span>
							<?php esc_html_e( 'All scheduled cron events have active registered callbacks.', 'nexura-database-cleaner' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Plugin Tables & WooCommerce Diagnostics -->
	<div class="nexdbc-card">
		<h3 class="nexdbc-card-header"><?php esc_html_e( 'Plugin Tables & WooCommerce Diagnostics', 'nexura-database-cleaner' ); ?> <span class="nexdbc-plan nexdbc-plan-free"><?php esc_html_e( 'Free, view only', 'nexura-database-cleaner' ); ?></span></h3>
		<?php
		nexura_database_cleaner_upgrade_prompt(
			__( 'This list is view only. Pro can drop these tables and delete the leftover options.', 'nexura-database-cleaner' )
		);
		if ( function_exists( 'nexura_database_cleaner_is_premium' ) && nexura_database_cleaner_is_premium() ) :
			?>
			<p><?php esc_html_e( 'Drop these tables and delete leftover options on the Pro tab.', 'nexura-database-cleaner' ); ?></p>
		<?php endif; ?>
		<p><?php echo esc_html( $wc_analysis['status_note'] ); ?></p>

		<?php if ( ! empty( $plugin_tables ) ) : ?>
			<table class="wp-list-table widefat fixed striped nexdbc-table-small">
				<thead>
					<tr>
						<th style="width: 30%;"><?php esc_html_e( 'Custom Table Name', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Associated Plugin', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Size', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 30%;"><?php esc_html_e( 'Detection Reason', 'nexura-database-cleaner' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $plugin_tables as $pt ) : ?>
						<tr>
							<td><code><?php echo esc_html( $pt['table_name'] ); ?></code></td>
							<td>
								<strong><?php echo esc_html( $pt['plugin_name'] ); ?></strong>
								<?php if ( 'abandoned' === $pt['status'] ) : ?>
									<span class="nexdbc-badge nexdbc-badge-warning"><?php esc_html_e( 'Inactive Plugin', 'nexura-database-cleaner' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $pt['size_human'] ); ?></td>
							<td><small><?php echo esc_html( implode( ' ', $pt['reasons'] ) ); ?></small></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
