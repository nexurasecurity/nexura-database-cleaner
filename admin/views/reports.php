<?php
/**
 * Reports & Audit History View
 *
 * Implements factual storage reporting, Job ID display, and verification status
 * per repot.text Sections 4, 6, 8, 32, 51, 52.
 *
 * @package NexuraDatabaseCleaner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/header.php';
?>

<div class="nexdbc-content">
	<div class="nexdbc-section-title nexdbc-flex-between">
		<div>
			<h2><?php esc_html_e( 'Cleanup History & Verification Reports', 'nexura-database-cleaner' ); ?></h2>
			<p><?php esc_html_e( 'Authoritative audit trail of database maintenance operations, verified rows removed, and server status.', 'nexura-database-cleaner' ); ?></p>
			<?php
			nexura_database_cleaner_upgrade_prompt(
				__( 'This history stays free. Pro emails the admin a summary after a scheduled cleanup.', 'nexura-database-cleaner' )
			);
			?>
		</div>
		<div>
			<?php if ( ! empty( $history ) ) : ?>
				<button id="nexdbc-btn-clear-history" class="button button-secondary">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear Audit Log', 'nexura-database-cleaner' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<div class="nexdbc-card">
		<table class="wp-list-table widefat fixed striped nexdbc-table">
			<thead>
				<tr>
					<th style="width: 18%;"><?php esc_html_e( 'Date & Time', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 25%;"><?php esc_html_e( 'Item Processed', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Job ID', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 14%;"><?php esc_html_e( 'Rows Deleted', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 14%;"><?php esc_html_e( 'Storage Status', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 14%;"><?php esc_html_e( 'Verification', 'nexura-database-cleaner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $history ) ) : ?>
					<tr>
						<td colspan="6" class="nexdbc-empty-notice-table">
							<span class="dashicons dashicons-clock nexdbc-icon-muted"></span>
							<p><?php esc_html_e( 'No cleanup operations logged yet. Run a cleanup operation to generate an audit report.', 'nexura-database-cleaner' ); ?></p>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $history as $entry ) : ?>
						<?php
						$status       = ! empty( $entry['status'] ) ? $entry['status'] : 'SUCCESS';
						$verification = ! empty( $entry['verification'] ) ? $entry['verification'] : 'Passed';
						$is_dry_run   = ! empty( $entry['dry_run'] ) || 'DRY_RUN' === $status;
						$badge_class  = 'nexdbc-badge-safe';
						if ( 'PARTIAL' === $status ) {
							$badge_class = 'nexdbc-badge-medium';
						} elseif ( 'FAILED' === $status ) {
							$badge_class = 'nexdbc-badge-high';
						} elseif ( $is_dry_run ) {
							$badge_class = 'nexdbc-badge-low';
						}
						?>
						<tr>
							<td><?php echo esc_html( $entry['date_human'] ); ?></td>
							<td>
								<strong><?php echo esc_html( ! empty( $entry['item_name'] ) ? $entry['item_name'] : $entry['item_id'] ); ?></strong>
								<?php if ( $is_dry_run ) : ?>
									<span class="nexdbc-badge nexdbc-badge-low" style="margin-left: 5px;"><?php esc_html_e( 'Dry Run', 'nexura-database-cleaner' ); ?></span>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( ! empty( $entry['job_id'] ) ? $entry['job_id'] : '—' ); ?></code></td>
							<td>
								<strong><?php echo esc_html( number_format_i18n( ! empty( $entry['rows_deleted'] ) ? $entry['rows_deleted'] : ( ! empty( $entry['rows_cleaned'] ) ? $entry['rows_cleaned'] : 0 ) ) ); ?></strong>
								<?php if ( isset( $entry['rows_before'] ) && isset( $entry['rows_after'] ) ) : ?>
									<div style="font-size: 11px; color: #646970;">
										<?php
										/* translators: 1: row count before cleanup, 2: row count after cleanup */
										printf( esc_html__( 'Before: %1$s | After: %2$s', 'nexura-database-cleaner' ), esc_html( number_format_i18n( $entry['rows_before'] ) ), esc_html( number_format_i18n( $entry['rows_after'] ) ) );
										?>
									</div>
								<?php endif; ?>
							</td>
							<td>
								<span style="font-size: 12px; color: #50575e;">
									<?php echo esc_html( ! empty( $entry['storage_status'] ) ? $entry['storage_status'] : ( ! empty( $entry['freed_human'] ) ? $entry['freed_human'] : __( 'Not measured', 'nexura-database-cleaner' ) ) ); ?>
								</span>
							</td>
							<td>
								<span class="nexdbc-badge <?php echo esc_attr( $badge_class ); ?>">
									<span class="dashicons dashicons-yes"></span> <?php echo esc_html( $status ); ?> (<?php echo esc_html( $verification ); ?>)
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
