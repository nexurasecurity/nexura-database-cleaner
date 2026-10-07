<?php
/**
 * Cleanup & Preview View
 *
 * @package NexuraDatabaseCleaner
 */

use NexuraDatabaseCleaner\Includes\Safety;
use NexuraDatabaseCleaner\Includes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/header.php';

$detections = isset( $scan_data['detections'] ) ? $scan_data['detections'] : array();
?>

<div class="nexdbc-content">
	<div class="nexdbc-section-title">
		<h2><?php esc_html_e( 'Batch Database Cleanup', 'nexura-database-cleaner' ); ?></h2>
		<p><?php esc_html_e( 'Select the clutter items you wish to clean. Deletions run asynchronously in safe batches to prevent server timeouts.', 'nexura-database-cleaner' ); ?></p>
		<?php
		nexura_database_cleaner_upgrade_prompt(
			__( 'Dry run and batch delete stay free. A SQL backup of the selected tables before cleanup is Pro.', 'nexura-database-cleaner' )
		);
		?>
	</div>

	<div class="nexdbc-card">
		<div class="nexdbc-card-toolbar nexdbc-flex-between">
			<div>
				<label class="nexdbc-checkbox-label">
					<input type="checkbox" id="nexdbc-select-all-clean" checked> <strong><?php esc_html_e( 'Select All Eligible Items', 'nexura-database-cleaner' ); ?></strong>
				</label>
			</div>
			<div class="nexdbc-toolbar-buttons">
				<button type="button" id="nexdbc-btn-dry-run-selected" class="button button-secondary">
					<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Dry Run Selected', 'nexura-database-cleaner' ); ?>
				</button>
				<button type="button" id="nexdbc-btn-clean-selected" class="button button-primary nexdbc-btn-danger">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clean Selected Items', 'nexura-database-cleaner' ); ?>
				</button>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped nexdbc-table">
			<thead>
				<tr>
					<th style="width: 5%; text-align: center;"><input type="checkbox" disabled></th>
					<th style="width: 25%;"><?php esc_html_e( 'Item Type', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Category', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Risk Level', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Items Count', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Estimated Space', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 10%; text-align: right;"><?php esc_html_e( 'Preview', 'nexura-database-cleaner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$has_any = false;
				foreach ( $detections as $id => $item ) :
					if ( $item['count'] > 0 ) :
						$has_any = true;
				?>
					<tr data-item-id="<?php echo esc_attr( $id ); ?>" data-count="<?php echo esc_attr( $item['count'] ); ?>" data-title="<?php echo esc_attr( $item['title'] ); ?>" data-size="<?php echo esc_attr( $item['estimated_human'] ); ?>">
						<td style="text-align: center;">
							<input type="checkbox" class="nexdbc-item-select" value="<?php echo esc_attr( $id ); ?>" checked>
						</td>
						<td>
							<strong><?php echo esc_html( $item['title'] ); ?></strong>
						</td>
						<td>
							<?php echo esc_html( $item['category'] ); ?>
						</td>
						<td>
							<?php echo wp_kses_post( Safety::get_risk_badge( $item['risk'] ) ); ?>
						</td>
						<td>
							<strong class="nexdbc-item-count-label"><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></strong>
						</td>
						<td>
							<?php echo esc_html( $item['estimated_human'] ); ?>
						</td>
						<td style="text-align: right;">
							<button type="button" class="button button-small nexdbc-btn-review" data-id="<?php echo esc_attr( $id ); ?>">
								<?php esc_html_e( 'Preview', 'nexura-database-cleaner' ); ?>
							</button>
						</td>
					</tr>
				<?php
					endif;
				endforeach;

				if ( ! $has_any ) :
				?>
					<tr>
						<td colspan="7" class="nexdbc-empty-notice-table">
							<span class="dashicons dashicons-yes-alt nexdbc-icon-green"></span>
							<p><?php esc_html_e( 'No cleanable clutter found in your database. Everything is currently optimal!', 'nexura-database-cleaner' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- Pre-Cleanup Backup Safety Modal -->
<div id="nexdbc-backup-modal" class="nexdbc-modal-backdrop" style="display:none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.55); z-index: 100000; align-items: center; justify-content: center;">
	<div class="nexdbc-modal-dialog" style="max-width: 520px; width: 90%; background: #fff; border-radius: 8px; padding: 24px 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
		<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
			<span class="dashicons dashicons-shield-alt" style="font-size: 32px; width: 32px; height: 32px; color: #2271b1;"></span>
			<h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1d2327;"><?php esc_html_e( 'Database Backup Recommendation', 'nexura-database-cleaner' ); ?></h3>
		</div>
		<p style="color: #3c434a; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
			<?php esc_html_e( 'A database backup is strongly recommended before running bulk cleanup operations. While all selected items are vetted for safety and integrity, having a recent backup ensures you can easily restore your site if needed.', 'nexura-database-cleaner' ); ?>
		</p>
		<div style="display: flex; justify-content: flex-end; gap: 10px;">
			<button type="button" id="nexdbc-btn-modal-cancel" class="button button-secondary button-large"><?php esc_html_e( 'Cancel', 'nexura-database-cleaner' ); ?></button>
			<button type="button" id="nexdbc-btn-modal-proceed" class="button button-primary nexdbc-btn-danger button-large"><?php esc_html_e( 'I Have a Backup, Proceed to Clean', 'nexura-database-cleaner' ); ?></button>
		</div>
	</div>
</div>

<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
