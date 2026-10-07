<?php
/**
 * Admin Shared Footer
 *
 * @package NexuraDatabaseCleaner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<div class="nexdbc-footer">
		<p>
			<strong><?php esc_html_e( 'Nexura Database Cleaner & Optimizer', 'nexura-database-cleaner' ); ?></strong> &bull;
			<?php esc_html_e( 'Always scan, explain, preview, and verify before cleaning.', 'nexura-database-cleaner' ); ?>
		</p>
	</div>
</div><!-- /.nexdbc-wrap -->

<!-- Preview & Batch Modal -->
<div id="nexdbc-modal-overlay" class="nexdbc-modal-overlay" style="display:none;">
	<div class="nexdbc-modal-box">
		<div class="nexdbc-modal-header">
			<h2 id="nexdbc-modal-title"><?php esc_html_e( 'Cleanup Preview', 'nexura-database-cleaner' ); ?></h2>
			<button type="button" class="nexdbc-modal-close">&times;</button>
		</div>
		<div class="nexdbc-modal-body" id="nexdbc-modal-body">
			<div class="nexdbc-modal-loader">
				<span class="spinner is-active"></span> <?php esc_html_e( 'Loading preview details...', 'nexura-database-cleaner' ); ?>
			</div>
		</div>
		<div class="nexdbc-modal-footer" id="nexdbc-modal-footer">
			<button type="button" class="button nexdbc-modal-cancel"><?php esc_html_e( 'Cancel', 'nexura-database-cleaner' ); ?></button>
			<button type="button" id="nexdbc-modal-btn-dryrun" class="button button-secondary"><?php esc_html_e( 'Run Dry Run', 'nexura-database-cleaner' ); ?></button>
			<button type="button" id="nexdbc-modal-btn-confirm" class="button button-primary nexdbc-btn-danger"><?php esc_html_e( 'Start Batch Cleanup', 'nexura-database-cleaner' ); ?></button>
		</div>
	</div>
</div>
