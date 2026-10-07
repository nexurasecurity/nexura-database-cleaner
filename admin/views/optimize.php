<?php
/**
 * Table Optimization View
 *
 * @package NexuraDatabaseCleaner
 */

use NexuraDatabaseCleaner\Includes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/header.php';

$total_reclaimable = 0;
foreach ( $tables as $tbl ) {
	$total_reclaimable += isset( $tbl['reclaimable'] ) ? $tbl['reclaimable'] : 0;
}
?>

<div class="nexdbc-content">
	<div class="nexdbc-section-title nexdbc-flex-between">
		<div>
			<h2><?php esc_html_e( 'Database Table Optimization', 'nexura-database-cleaner' ); ?></h2>
			<p><?php esc_html_e( 'Analyze and defragment MySQL/MariaDB database tables to reclaim unused disk space and optimize indexes.', 'nexura-database-cleaner' ); ?></p>
		</div>
		<div>
			<?php if ( $total_reclaimable > 0 ) : ?>
				<button id="nexdbc-btn-optimize-all-overhead" type="button" class="button button-primary nexdbc-action-btn">
					<span class="dashicons dashicons-performance"></span>
					<span class="nexdbc-btn-text">
						<?php
						printf(
							/* translators: %s: formatted total reclaimable space */
							esc_html__( 'Optimize Fragmented Tables (%s)', 'nexura-database-cleaner' ),
							esc_html( Database::format_size( $total_reclaimable ) )
						);
						?>
					</span>
				</button>
			<?php else : ?>
				<span class="nexdbc-badge nexdbc-badge-safe" style="padding: 6px 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
					<span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
					<?php esc_html_e( 'All Tables Optimal', 'nexura-database-cleaner' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<div class="nexdbc-info-banner" style="margin-bottom: 20px; padding: 12px 16px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
		<p style="margin: 0; font-size: 13px; color: #1d2327; line-height: 1.5;">
			<span class="dashicons dashicons-info" style="color: #2271b1; vertical-align: text-bottom; margin-right: 4px;"></span>
			<strong><?php esc_html_e( 'InnoDB Storage Extent Note:', 'nexura-database-cleaner' ); ?></strong>
			<?php esc_html_e( 'MySQL/MariaDB allocates InnoDB disk storage in 2 MB extent blocks. A 2 MB reserve on an InnoDB table is standard database engine behavior and indicates the table is already fully optimized and at its baseline size.', 'nexura-database-cleaner' ); ?>
		</p>
	</div>

	<div class="nexdbc-card">
		<div class="nexdbc-card-header nexdbc-flex-between">
			<span>
				<?php
				printf(
					/* translators: %d: number of database tables found */
					esc_html__( 'Found %d database tables', 'nexura-database-cleaner' ),
					(int) count( $tables )
				);
				?>
			</span>
			<span>
				<?php esc_html_e( 'Reclaimable Overhead:', 'nexura-database-cleaner' ); ?>
				<?php if ( $total_reclaimable > 0 ) : ?>
					<strong class="nexdbc-overhead-warning"><?php echo esc_html( Database::format_size( $total_reclaimable ) ); ?></strong>
				<?php else : ?>
					<strong class="nexdbc-score-good"><?php esc_html_e( '0 B (Optimal)', 'nexura-database-cleaner' ); ?></strong>
				<?php endif; ?>
			</span>
		</div>
		<table class="wp-list-table widefat fixed striped nexdbc-table nexdbc-table-optimize">
			<thead>
				<tr>
					<th style="width: 25%;"><?php esc_html_e( 'Table Name', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 10%;"><?php esc_html_e( 'Engine', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Records', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Data Size', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Index Size', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 14%;"><?php esc_html_e( 'Free Overhead', 'nexura-database-cleaner' ); ?></th>
					<th style="width: 15%; text-align: right;"><?php esc_html_e( 'Actions', 'nexura-database-cleaner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tables as $tbl ) : ?>
					<tr data-table-name="<?php echo esc_attr( $tbl['name'] ); ?>" data-free="<?php echo esc_attr( isset( $tbl['reclaimable'] ) ? $tbl['reclaimable'] : 0 ); ?>">
						<td>
							<strong><?php echo esc_html( $tbl['name'] ); ?></strong>
							<?php if ( ! empty( $tbl['is_core'] ) ) : ?>
								<span class="nexdbc-badge nexdbc-badge-core"><?php esc_html_e( 'Core', 'nexura-database-cleaner' ); ?></span>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( $tbl['engine'] ); ?></code></td>
						<td><?php echo esc_html( number_format_i18n( $tbl['rows'] ) ); ?></td>
						<td><?php echo esc_html( $tbl['data_size_human'] ); ?></td>
						<td><?php echo esc_html( $tbl['index_size_human'] ); ?></td>
						<td>
							<?php if ( ! empty( $tbl['is_optimal'] ) ) : ?>
								<span class="nexdbc-badge nexdbc-badge-safe" title="<?php esc_attr_e( 'Table is fully defragmented and optimized.', 'nexura-database-cleaner' ); ?>">
									<?php esc_html_e( 'Optimal', 'nexura-database-cleaner' ); ?>
								</span>
								<?php if ( $tbl['free_size'] > 0 ) : ?>
									<span class="nexdbc-text-muted" style="font-size: 11px; margin-left: 4px;" title="<?php esc_attr_e( 'Standard InnoDB extent reserve pre-allocated by MySQL', 'nexura-database-cleaner' ); ?>">
										(<?php echo esc_html( $tbl['free_size_human'] ); ?> reserve)
									</span>
								<?php endif; ?>
							<?php elseif ( ! empty( $tbl['has_overhead'] ) ) : ?>
								<span class="nexdbc-overhead-warning" title="<?php esc_attr_e( 'Fragmented overhead detected that can be reclaimed.', 'nexura-database-cleaner' ); ?>">
									<strong><?php echo esc_html( $tbl['free_size_human'] ); ?></strong>
								</span>
							<?php else : ?>
								<span class="nexdbc-text-muted">0 B</span>
							<?php endif; ?>
						</td>
						<td style="text-align: right;">
							<button type="button" class="button button-small nexdbc-btn-analyze-table" data-name="<?php echo esc_attr( $tbl['name'] ); ?>" title="<?php esc_attr_e( 'Analyze Table', 'nexura-database-cleaner' ); ?>">
								<?php esc_html_e( 'Analyze', 'nexura-database-cleaner' ); ?>
							</button>
							<button type="button" class="button button-small button-secondary nexdbc-btn-optimize-table" data-name="<?php echo esc_attr( $tbl['name'] ); ?>" title="<?php esc_attr_e( 'Optimize Table', 'nexura-database-cleaner' ); ?>">
								<?php esc_html_e( 'Optimize', 'nexura-database-cleaner' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
	nexura_database_cleaner_upgrade_prompt(
		__( 'Optimize and Analyze stay free. Converting MyISAM to InnoDB, and browsing columns and indexes, are Pro.', 'nexura-database-cleaner' )
	);
	?>
</div>

<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
