<?php
/**
 * Detailed Scan Results View
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
$timestamp  = isset( $scan_data['timestamp'] ) ? $scan_data['timestamp'] : time();

// Group by category
$categories = array();
foreach ( $detections as $id => $item ) {
	$cat = $item['category'];
	if ( ! isset( $categories[ $cat ] ) ) {
		$categories[ $cat ] = array();
	}
	$categories[ $cat ][ $id ] = $item;
}
?>

<div class="nexdbc-content">
	<div class="nexdbc-section-title nexdbc-flex-between">
		<div>
			<h2><?php esc_html_e( 'Database Scan Details', 'nexura-database-cleaner' ); ?></h2>
			<p>
				<?php esc_html_e( 'Last scanned:', 'nexura-database-cleaner' ); ?>
				<strong><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'F j, Y g:i a' ) ); ?></strong>
			</p>
			<?php
			nexura_database_cleaner_upgrade_prompt(
				__( 'WooCommerce expired sessions, orphan variations, orphaned order items & meta, webhook delivery logs, and orphan lookup rows are added to this scan with Pro.', 'nexura-database-cleaner' )
			);
			?>
		</div>
		<div>
			<button id="nexdbc-btn-rescan" type="button" class="button button-primary nexdbc-action-btn">
				<span class="dashicons dashicons-update"></span>
				<span class="nexdbc-btn-text"><?php esc_html_e( 'Scan Again', 'nexura-database-cleaner' ); ?></span>
			</button>
		</div>
	</div>

	<?php foreach ( $categories as $cat_name => $items ) : ?>
		<div class="nexdbc-card nexdbc-category-card">
			<h3 class="nexdbc-card-header"><?php echo esc_html( $cat_name ); ?></h3>
			<table class="wp-list-table widefat fixed striped nexdbc-table">
				<thead>
					<tr>
						<th style="width: 25%;"><?php esc_html_e( 'Item Type', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Risk Level', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 12%;"><?php esc_html_e( 'Found Count', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Est. Size', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Detection Reason', 'nexura-database-cleaner' ); ?></th>
						<th style="width: 13%; text-align: right;"><?php esc_html_e( 'Actions', 'nexura-database-cleaner' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $id => $item ) : ?>
						<tr data-item-id="<?php echo esc_attr( $id ); ?>" data-count="<?php echo esc_attr( $item['count'] ); ?>" data-title="<?php echo esc_attr( $item['title'] ); ?>">
							<td>
								<strong><?php echo esc_html( $item['title'] ); ?></strong>
								<div class="nexdbc-item-desc"><?php echo esc_html( $item['description'] ); ?></div>
							</td>
							<td>
								<?php echo wp_kses_post( Safety::get_risk_badge( $item['risk'] ) ); ?>
								<div class="nexdbc-item-conf"><?php esc_html_e( 'Confidence:', 'nexura-database-cleaner' ); ?> <strong><?php echo esc_html( $item['confidence'] ); ?></strong></div>
							</td>
							<td>
								<span class="nexdbc-row-count <?php echo $item['count'] > 0 ? 'nexdbc-has-items' : ''; ?>">
									<?php echo esc_html( number_format_i18n( $item['count'] ) ); ?>
								</span>
							</td>
							<td>
								<?php echo esc_html( $item['estimated_human'] ); ?>
							</td>
							<td>
								<small class="nexdbc-why-text"><?php echo esc_html( $item['why'] ); ?></small>
							</td>
							<td style="text-align: right;">
								<?php if ( $item['count'] > 0 ) : ?>
									<button type="button" class="button button-small nexdbc-btn-review" data-id="<?php echo esc_attr( $id ); ?>">
										<?php esc_html_e( 'Preview', 'nexura-database-cleaner' ); ?>
									</button>
								<?php else : ?>
									<span class="nexdbc-clean-tag"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Clean', 'nexura-database-cleaner' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
</div>

<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
