<?php

/**
 * Plugin Name: Nexura Database Cleaner & Optimizer
 * Description: Safely clean and optimize your WordPress database.
 * Version: 1.0.0
 * Author: Nexura Security
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nexura-database-cleaner
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 *
 * @package NexuraDatabaseCleaner
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( function_exists( 'nexura_database_cleaner_fs' ) ) {
    nexura_database_cleaner_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'nexura_database_cleaner_fs' ) ) {
        // Create a helper function for easy SDK access.
        function nexura_database_cleaner_fs() {
            global $nexura_database_cleaner_fs;
            if ( !isset( $nexura_database_cleaner_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $nexura_database_cleaner_fs = fs_dynamic_init( array(
                    'id'               => '39911',
                    'slug'             => 'nexura-database-cleaner',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_4957eb676b83bb43862768f48e8d2',
                    'is_premium'       => false,
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => true,
                    'menu'             => array(
                        'slug'    => 'nexura-database-cleaner',
                        'support' => false,
                    ),
                    'is_live'          => true,
                ) );
            }
            return $nexura_database_cleaner_fs;
        }

        // Init Freemius.
        nexura_database_cleaner_fs();
        // Signal that SDK was initiated.
        do_action( 'nexura_database_cleaner_fs_loaded' );
    }
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
// Register Freemius after_uninstall hook for clean uninstallation.
nexura_database_cleaner_fs()->add_action( 'after_uninstall', 'nexura_database_cleaner_fs_uninstall_cleanup' );
/**
 * Cleanup plugin data upon uninstallation (via Freemius after_uninstall hook).
 */
function nexura_database_cleaner_fs_uninstall_cleanup() {
    delete_option( 'nexura_database_cleaner_settings' );
    delete_option( 'nexura_database_cleaner_last_scan_results' );
    delete_option( 'nexura_database_cleaner_last_scan_timestamp' );
    delete_option( 'nexura_database_cleaner_cleanup_history' );
    delete_option( 'nexura_database_cleaner_schedule_settings' );
    delete_option( 'nexura_database_cleaner_pro_tools' );
    wp_clear_scheduled_hook( 'nexura_database_cleaner_pro_maintenance' );
    delete_transient( 'nexura_database_cleaner_db_overview' );
    delete_site_transient( 'nexura_database_cleaner_db_overview' );
    delete_transient( 'nexura_database_cleaner_lock' );
}

/**
 * Check if the current installation has active Pro access.
 *
 * @return bool
 */
function nexura_database_cleaner_is_premium() {
    return function_exists( 'nexura_database_cleaner_fs' ) && nexura_database_cleaner_fs()->can_use_premium_code();
}

/**
 * Contextual Pro upgrade link. Hidden once a Pro license is active.
 * Shown only inside this plugin's screens, next to the feature being sold.
 *
 * @param string $reason Why this screen offers Pro.
 */
function nexura_database_cleaner_upgrade_prompt(  $reason  ) {
    if ( nexura_database_cleaner_is_premium() || !function_exists( 'nexura_database_cleaner_fs' ) ) {
        return;
    }
    $url = nexura_database_cleaner_fs()->get_upgrade_url();
    if ( !$url ) {
        return;
    }
    echo '<p class="nexdbc-upgrade-prompt">';
    echo esc_html( $reason );
    echo ' <a class="button button-secondary" href="' . esc_url( $url ) . '">';
    esc_html_e( 'Upgrade to Pro', 'nexura-database-cleaner' );
    echo '</a></p>';
}

// Plugin constants
define( 'NEXURA_DATABASE_CLEANER_VERSION', '1.0.0' );
define( 'NEXURA_DATABASE_CLEANER_FILE', __FILE__ );
define( 'NEXURA_DATABASE_CLEANER_PATH', plugin_dir_path( __FILE__ ) );
define( 'NEXURA_DATABASE_CLEANER_URL', plugin_dir_url( __FILE__ ) );
define( 'NEXURA_DATABASE_CLEANER_BASENAME', plugin_basename( __FILE__ ) );
/**
 * Autoload core classes
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'NexuraDatabaseCleaner\\';
    $base_dir = NEXURA_DATABASE_CLEANER_PATH;
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative_class = substr( $class, $len );
    // Map namespaces to directories
    $parts = explode( '\\', $relative_class );
    $file_name = 'class-' . strtolower( str_replace( '_', '-', end( $parts ) ) ) . '.php';
    if ( count( $parts ) === 1 ) {
        $file = $base_dir . 'includes/' . $file_name;
    } elseif ( strtolower( $parts[0] ) === 'admin' ) {
        $file = $base_dir . 'admin/' . $file_name;
    } elseif ( strtolower( $parts[0] ) === 'includes' ) {
        $file = $base_dir . 'includes/' . $file_name;
    } elseif ( strtolower( $parts[0] ) === 'modules' ) {
        $module_dir = strtolower( str_replace( '_', '-', $parts[1] ) );
        $file = $base_dir . 'modules/' . $module_dir . '/' . $file_name;
    } else {
        $file = $base_dir . 'includes/' . $file_name;
    }
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );
// Include manual bootstrap if not autoloaded
require_once NEXURA_DATABASE_CLEANER_PATH . 'includes/class-plugin.php';
/**
 * Bootstrap the plugin
 */
function nexura_database_cleaner_init() {
    return \NexuraDatabaseCleaner\Includes\Plugin::instance();
}

add_action( 'plugins_loaded', 'nexura_database_cleaner_init' );