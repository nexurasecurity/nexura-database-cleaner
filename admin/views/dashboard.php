<?php
/**
 * Dashboard View
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

$health_score  = isset( $scan_data['health_score'] ) ? $scan_data['health_score'] : 100;
$db_stats      = isset( $scan_data['db_stats'] ) ? $scan_data['db_stats'] : array();
$total_size    = isset( $db_stats['total_size_human'] ) ? $db_stats['total_size_human'] : '0 B';
$total_tables  = isset( $db_stats['total_tables'] ) ? $db_stats['total_tables'] : 0;
$cleanable_str = isset( $scan_data['total_cleanable_human'] ) ? $scan_data['total_cleanable_human'] : '0 B';
$cleanable_cnt = isset( $scan_data['total_cleanable_items'] ) ? $scan_data['total_cleanable_items'] : 0;
$overhead_str  = isset( $scan_data['total_overhead_human'] ) ? $scan_data['total_overhead_human'] : '0 B';
$groups        = isset( $scan_data['groups'] ) ? $scan_data['groups'] : array();

$score_class = 'nexdbc-score-good';
if ( $health_score < 60 ) {
	$score_class = 'nexdbc-score-bad';
} elseif ( $health_score < 80 ) {
	$score_class = 'nexdbc-score-warning';
}
?>

<div class="nexdbc-content">
	<!-- Summary Metric Cards -->
	<div class="nexdbc-grid-cards">
		<div class="nexdbc-card nexdbc-card-metric">
			<div class="nexdbc-metric-title"><?php esc_html_e( 'Database Health', 'nexura-database-cleaner' ); ?></div>
			<div class="nexdbc-metric-value <?php echo esc_attr( $score_class ); ?>">
				<?php echo esc_html( $health_score ); ?> <span class="nexdbc-metric-denom">/ 100</span>
			</div>
			<div class="nexdbc-metric-hint">
				<a href="#nexdbc-score-calc-dialog" class="nexdbc-link-calc"><?php esc_html_e( 'How is this calculated?', 'nexura-database-cleaner' ); ?></a>
			</div>
		</div>

		<div class="nexdbc-card nexdbc-card-metric">
			<div class="nexdbc-metric-title"><?php esc_html_e( 'Database Size', 'nexura-database-cleaner' ); ?></div>
			<div class="nexdbc-metric-value"><?php echo esc_html( $total_size ); ?></div>
			<div class="nexdbc-metric-hint"><?php echo esc_html( number_format_i18n( isset( $db_stats['total_rows'] ) ? $db_stats['total_rows'] : 0 ) ); ?> <?php esc_html_e( 'total records', 'nexura-database-cleaner' ); ?></div>
		</div>

		<div class="nexdbc-card nexdbc-card-metric">
			<div class="nexdbc-metric-title"><?php esc_html_e( 'Database Tables', 'nexura-database-cleaner' ); ?></div>
			<div class="nexdbc-metric-value"><?php echo esc_html( $total_tables ); ?></div>
			<div class="nexdbc-metric-hint">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-optimization' ) ); ?>"><?php esc_html_e( 'View table details &rarr;', 'nexura-database-cleaner' ); ?></a>
			</div>
		</div>

		<div class="nexdbc-card nexdbc-card-metric">
			<div class="nexdbc-metric-title"><?php esc_html_e( 'Potential Cleanup', 'nexura-database-cleaner' ); ?></div>
			<div class="nexdbc-metric-value nexdbc-metric-highlight"><?php echo esc_html( $cleanable_str ); ?></div>
			<div class="nexdbc-metric-hint"><?php echo esc_html( number_format_i18n( $cleanable_cnt ) ); ?> <?php esc_html_e( 'items identified', 'nexura-database-cleaner' ); ?></div>
		</div>
	</div>

	<!-- Health Score Calculation Transparency Box -->
	<div id="nexdbc-score-calc-info" class="nexdbc-info-banner" style="display:none;">
		<h4><?php esc_html_e( 'Database Health Calculation Transparency', 'nexura-database-cleaner' ); ?></h4>
		<p><?php esc_html_e( 'We never use arbitrary or misleading health scores. The score begins at 100 and applies transparent deductions:', 'nexura-database-cleaner' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Clutter Deduction:', 'nexura-database-cleaner' ); ?></strong> <?php esc_html_e( '1 point per 200 orphaned/trash records (up to -40 pts).', 'nexura-database-cleaner' ); ?></li>
			<li><strong><?php esc_html_e( 'Fragmentation Overhead:', 'nexura-database-cleaner' ); ?></strong> <?php esc_html_e( '1 point per 2MB of table fragmentation (up to -20 pts).', 'nexura-database-cleaner' ); ?></li>
			<li><strong><?php esc_html_e( 'Autoload Size:', 'nexura-database-cleaner' ); ?></strong> <?php esc_html_e( '1 point per 100KB of autoloaded options exceeding 800KB (up to -20 pts).', 'nexura-database-cleaner' ); ?></li>
		</ul>
	</div>

	<!-- Recommendations Grouped by Priority -->
	<div class="nexdbc-section-title">
		<h2><?php esc_html_e( 'Cleanup Recommendations', 'nexura-database-cleaner' ); ?></h2>
		<p><?php esc_html_e( 'Every item is classified by risk level. Review details or run a safe dry-run before cleaning.', 'nexura-database-cleaner' ); ?></p>
	</div>

	<div class="nexdbc-recommendations">
		<!-- High Priority -->
		<div class="nexdbc-priority-block">
			<div class="nexdbc-priority-header nexdbc-header-high">
				<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'HIGH PRIORITY', 'nexura-database-cleaner' ); ?>
			</div>
			<div class="nexdbc-priority-body">
				<?php if ( empty( $groups['high_priority'] ) ) : ?>
					<p class="nexdbc-empty-notice"><?php esc_html_e( 'No high priority clutter detected. Your database metadata is clean!', 'nexura-database-cleaner' ); ?></p>
				<?php else : ?>
					<div class="nexdbc-rec-list">
						<?php foreach ( $groups['high_priority'] as $item ) : ?>
							<div class="nexdbc-rec-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" data-count="<?php echo esc_attr( $item['count'] ); ?>" data-title="<?php echo esc_attr( $item['title'] ); ?>">
								<div class="nexdbc-rec-info">
									<div class="nexdbc-rec-title">
										<strong><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?> <?php echo esc_html( $item['title'] ); ?></strong>
										<?php echo wp_kses_post( Safety::get_risk_badge( $item['risk'] ) ); ?>
									</div>
									<div class="nexdbc-rec-meta">
										<span><?php esc_html_e( 'Estimated size:', 'nexura-database-cleaner' ); ?> <?php echo esc_html( $item['estimated_human'] ); ?></span> &bull;
										<span><?php echo esc_html( $item['description'] ); ?></span>
									</div>
								</div>
								<div class="nexdbc-rec-actions">
									<button type="button" class="button nexdbc-btn-review" data-id="<?php echo esc_attr( $item['id'] ); ?>">
										<?php esc_html_e( 'Review & Preview', 'nexura-database-cleaner' ); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Safe Items -->
		<div class="nexdbc-priority-block">
			<div class="nexdbc-priority-header nexdbc-header-safe">
				<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'SAFE CLEANUP', 'nexura-database-cleaner' ); ?>
			</div>
			<div class="nexdbc-priority-body">
				<?php if ( empty( $groups['safe'] ) ) : ?>
					<p class="nexdbc-empty-notice"><?php esc_html_e( 'No safe transient or revision clutter detected.', 'nexura-database-cleaner' ); ?></p>
				<?php else : ?>
					<div class="nexdbc-rec-list">
						<?php foreach ( $groups['safe'] as $item ) : ?>
							<div class="nexdbc-rec-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" data-count="<?php echo esc_attr( $item['count'] ); ?>" data-title="<?php echo esc_attr( $item['title'] ); ?>">
								<div class="nexdbc-rec-info">
									<div class="nexdbc-rec-title">
										<strong><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?> <?php echo esc_html( $item['title'] ); ?></strong>
										<?php echo wp_kses_post( Safety::get_risk_badge( $item['risk'] ) ); ?>
									</div>
									<div class="nexdbc-rec-meta">
										<span><?php esc_html_e( 'Estimated size:', 'nexura-database-cleaner' ); ?> <?php echo esc_html( $item['estimated_human'] ); ?></span> &bull;
										<span><?php echo esc_html( $item['description'] ); ?></span>
									</div>
								</div>
								<div class="nexdbc-rec-actions">
									<button type="button" class="button nexdbc-btn-review" data-id="<?php echo esc_attr( $item['id'] ); ?>">
										<?php esc_html_e( 'Review & Preview', 'nexura-database-cleaner' ); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Review / Low Risk Items -->
		<div class="nexdbc-priority-block">
			<div class="nexdbc-priority-header nexdbc-header-review">
				<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'NEEDS REVIEW', 'nexura-database-cleaner' ); ?>
			</div>
			<div class="nexdbc-priority-body">
				<?php if ( empty( $groups['review'] ) ) : ?>
					<p class="nexdbc-empty-notice"><?php esc_html_e( 'No items pending review.', 'nexura-database-cleaner' ); ?></p>
				<?php else : ?>
					<div class="nexdbc-rec-list">
						<?php foreach ( $groups['review'] as $item ) : ?>
							<div class="nexdbc-rec-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" data-count="<?php echo esc_attr( $item['count'] ); ?>" data-title="<?php echo esc_attr( $item['title'] ); ?>">
								<div class="nexdbc-rec-info">
									<div class="nexdbc-rec-title">
										<strong><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?> <?php echo esc_html( $item['title'] ); ?></strong>
										<?php echo wp_kses_post( Safety::get_risk_badge( $item['risk'] ) ); ?>
									</div>
									<div class="nexdbc-rec-meta">
										<span><?php esc_html_e( 'Estimated size:', 'nexura-database-cleaner' ); ?> <?php echo esc_html( $item['estimated_human'] ); ?></span> &bull;
										<span><?php echo esc_html( $item['description'] ); ?></span>
									</div>
								</div>
								<div class="nexdbc-rec-actions">
									<button type="button" class="button nexdbc-btn-review" data-id="<?php echo esc_attr( $item['id'] ); ?>">
										<?php esc_html_e( 'Review & Preview', 'nexura-database-cleaner' ); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	nexura_database_cleaner_upgrade_prompt(
		__( 'Free already scans and cleans revisions, trash, spam, and transients. Pro adds abandoned-table cleanup, SQL backup, and a separate schedule for each cleaner.', 'nexura-database-cleaner' )
	);
	?>
</div>

<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
