<?php
/**
 * Plugin deactivation handler.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Deactivator
 *
 * Clears scheduled cron events on deactivation and optionally
 * performs a full data cleanup via FWA_Uninstaller.
 *
 * @since 1.0.0
 */
class FWA_Deactivator {

	/**
	 * Execute deactivation tasks.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $full_cleanup When true, drop tables, delete options, and delete transients.
	 */
	public static function deactivate( $full_cleanup = false ) {

		self::clear_cron_events();

		if ( $full_cleanup ) {
			self::run_full_cleanup();
		}
	}

	/**
	 * Remove all plugin cron events.
	 *
	 * @since 1.0.0
	 */
	private static function clear_cron_events() {

		$cron_hooks = array(
			'fwa_process_queue',
			'fwa_health_check',
			'fwa_cleanup_logs',
		);

		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Delegate full data removal to the uninstaller.
	 *
	 * @since 1.0.0
	 */
	private static function run_full_cleanup() {

		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-uninstaller.php';

		FWA_Uninstaller::drop_tables();
		FWA_Uninstaller::delete_options();
		FWA_Uninstaller::delete_transients();
	}
}
