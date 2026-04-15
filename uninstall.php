<?php
/**
 * Plugin uninstaller.
 *
 * Fired when the plugin is deleted through the WordPress admin.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

// If not called by WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load helpers for table names.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-fwa-helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-fwa-uninstaller.php';

// Always clean up on uninstall (plugin deletion).
FWA_Uninstaller::drop_tables();
FWA_Uninstaller::delete_options();
FWA_Uninstaller::delete_transients();
FWA_Uninstaller::delete_user_meta();
FWA_Uninstaller::delete_log_files();
FWA_Uninstaller::clear_cron_events();
