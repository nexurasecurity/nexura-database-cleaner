<?php
/**
 * Admin Shared Header and Navigation
 *
 * @package NexuraDatabaseCleaner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'nexura-database-cleaner';
$nexura_database_cleaner_pro_active = function_exists( 'nexura_database_cleaner_is_premium' ) && nexura_database_cleaner_is_premium();
?>
<div class="wrap nexdbc-wrap">
	<div class="nexdbc-header">
		<div class="nexdbc-header-brand">
			<div class="nexdbc-logo-icon">
				<span class="dashicons dashicons-database-view"></span>
			</div>
			<div>
				<h1 class="nexdbc-title">
					<?php esc_html_e( 'Nexura Database Cleaner & Optimizer', 'nexura-database-cleaner' ); ?>
					<span class="nexdbc-version">v<?php echo esc_html( NEXURA_DATABASE_CLEANER_VERSION ); ?></span>
					<?php if ( $nexura_database_cleaner_pro_active ) : ?>
						<span class="nexdbc-plan nexdbc-plan-pro"><?php esc_html_e( 'Pro active', 'nexura-database-cleaner' ); ?></span>
					<?php else : ?>
						<span class="nexdbc-plan nexdbc-plan-free"><?php esc_html_e( 'Free plan', 'nexura-database-cleaner' ); ?></span>
					<?php endif; ?>
				</h1>
				<p class="nexdbc-subtitle">
					<?php if ( $nexura_database_cleaner_pro_active ) : ?>
						<?php esc_html_e( 'Dashboard, Scan, Cleanup, Optimization, Reports, and Settings stay the free tools. The Pro tab is the only place the license unlocks.', 'nexura-database-cleaner' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Dashboard, Scan, Cleanup, Optimization, Reports, and Settings are free. A Freemius connection does not unlock Pro. Open the Pro tab to see what an upgrade adds.', 'nexura-database-cleaner' ); ?>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<div class="nexdbc-header-actions">
			<button id="nexdbc-btn-quick-scan" type="button" class="button button-primary nexdbc-action-btn">
				<span class="dashicons dashicons-update"></span>
				<span class="nexdbc-btn-text"><?php esc_html_e( 'Run Database Scan', 'nexura-database-cleaner' ); ?></span>
			</button>
		</div>
	</div>

	<nav class="nav-tab-wrapper nexdbc-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'nexura-database-cleaner' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-scan' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner-scan' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Scan Database', 'nexura-database-cleaner' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-cleanup' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner-cleanup' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Cleanup & Preview', 'nexura-database-cleaner' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-optimization' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner-optimization' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Optimization', 'nexura-database-cleaner' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-reports' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner-reports' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Reports & History', 'nexura-database-cleaner' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-settings' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner-settings' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'nexura-database-cleaner' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura-database-cleaner-pro' ) ); ?>" class="nav-tab <?php echo 'nexura-database-cleaner-pro' === $current_page ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Pro', 'nexura-database-cleaner' ); ?>
			<?php if ( $nexura_database_cleaner_pro_active ) : ?>
				<span class="nexdbc-tab-plan nexdbc-tab-plan-on"><?php esc_html_e( 'On', 'nexura-database-cleaner' ); ?></span>
			<?php else : ?>
				<span class="nexdbc-tab-plan"><?php esc_html_e( 'Locked', 'nexura-database-cleaner' ); ?></span>
			<?php endif; ?>
		</a>
	</nav>

	<?php
	// WordPress moves .notice elements after the first h1 unless this marker exists.
	// Keep admin notices below the branded header instead of between the title and subtitle.
	?>
	<hr class="wp-header-end" />

	<div id="nexdbc-global-notice" class="nexdbc-notice" style="display:none;"></div>

	<!-- Active Progress Container (Globally available for all pages & modal triggers) -->
	<div id="nexdbc-batch-runner" class="nexdbc-card nexdbc-batch-card" style="display:none;">
		<div class="nexdbc-batch-header">
			<h3 id="nexdbc-batch-title"><?php esc_html_e( 'Processing Cleanup...', 'nexura-database-cleaner' ); ?></h3>
			<span id="nexdbc-batch-status-badge" class="nexdbc-badge nexdbc-badge-info"><?php esc_html_e( 'Running', 'nexura-database-cleaner' ); ?></span>
		</div>
		<div class="nexdbc-progress-wrapper">
			<div class="nexdbc-progress-bar">
				<div id="nexdbc-progress-fill" class="nexdbc-progress-fill" style="width: 0%;"></div>
			</div>
			<div class="nexdbc-progress-text">
				<span id="nexdbc-progress-stats">0 / 0</span>
				<span id="nexdbc-progress-percent">0%</span>
			</div>
		</div>
		<div id="nexdbc-batch-log" class="nexdbc-batch-log"></div>
	</div>
