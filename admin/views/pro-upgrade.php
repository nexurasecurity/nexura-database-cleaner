<?php
/**
 * Pro upgrade screen shown when no license is active.
 *
 * @package NexuraDatabaseCleaner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/header.php';

$nexura_database_cleaner_fs           = function_exists( 'nexura_database_cleaner_fs' ) ? nexura_database_cleaner_fs() : null;
$nexura_database_cleaner_upgrade_url  = $nexura_database_cleaner_fs ? $nexura_database_cleaner_fs->get_upgrade_url() : '';
$nexura_database_cleaner_account_url  = $nexura_database_cleaner_fs ? $nexura_database_cleaner_fs->get_account_url() : '';
$nexura_database_cleaner_registered   = $nexura_database_cleaner_fs && $nexura_database_cleaner_fs->is_registered();
$nexura_database_cleaner_free_plan    = $nexura_database_cleaner_fs && $nexura_database_cleaner_fs->is_free_plan();
?>
<div class="nexdbc-content">
	<div class="nexdbc-card">
		<h2 class="nexdbc-card-header"><?php esc_html_e( 'Free stays free. Pro is only this tab.', 'nexura-database-cleaner' ); ?></h2>
		<?php if ( $nexura_database_cleaner_registered && $nexura_database_cleaner_free_plan ) : ?>
			<p><?php esc_html_e( 'Freemius is connected, and this site is still on the Free plan. Connecting an account, or activating a free license, does not turn Pro tools on.', 'nexura-database-cleaner' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'No Pro license is active on this site. The other menus keep working. Nothing there is removed when you upgrade.', 'nexura-database-cleaner' ); ?></p>
		<?php endif; ?>
		<p><?php esc_html_e( 'Upgrade when you need to delete leftover plugin tables and options, back up a table, or give each cleaner its own schedule.', 'nexura-database-cleaner' ); ?></p>

		<div class="nexdbc-compare">
			<div class="nexdbc-compare-free">
				<h3><?php esc_html_e( 'Already included', 'nexura-database-cleaner' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Health score, scan, dry run, and batch cleanup', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Revisions, auto-drafts, trash, spam, pingbacks, and expired transients', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Orphan and duplicate meta, plus oEmbed cache', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Table optimize, autoload toggle, and cron analyzer', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Reports and one daily, weekly, or monthly schedule', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Settings lists inactive-plugin tables. It does not delete them.', 'nexura-database-cleaner' ); ?></li>
				</ul>
			</div>
			<div class="nexdbc-compare-pro">
				<h3><?php esc_html_e( 'Unlocked on this Pro tab', 'nexura-database-cleaner' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Drop abandoned plugin tables', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Delete leftover options from inactive plugins', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'WooCommerce sessions, orphan variations, order items & meta, webhook logs, and orphan lookup rows in Scan', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Hourly, daily, weekly, or monthly schedule per cleaner, plus an email summary', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'SQL backup, MyISAM to InnoDB, column and index browser', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Search inside options and post meta', 'nexura-database-cleaner' ); ?></li>
					<li><?php esc_html_e( 'Multisite network cleanup', 'nexura-database-cleaner' ); ?></li>
				</ul>
			</div>
		</div>

		<p>
			<?php if ( $nexura_database_cleaner_upgrade_url ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( $nexura_database_cleaner_upgrade_url ); ?>">
					<?php esc_html_e( 'Upgrade to Pro', 'nexura-database-cleaner' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $nexura_database_cleaner_account_url ) : ?>
				<a class="button" href="<?php echo esc_url( $nexura_database_cleaner_account_url ); ?>">
					<?php esc_html_e( 'View Freemius account', 'nexura-database-cleaner' ); ?>
				</a>
			<?php endif; ?>
		</p>
	</div>
</div>
<?php
include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/footer.php';
