<?php
/**
 * Uninstaller helper.
 *
 * Provides static methods used by the deactivation cleanup and
 * the uninstall.php entry point.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Uninstaller
 *
 * @since 1.0.0
 */
class FWA_Uninstaller {

	/**
	 * Drop all plugin database tables.
	 *
	 * @since 1.0.0
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'fwa_instances',
			$wpdb->prefix . 'fwa_messages',
			$wpdb->prefix . 'fwa_contacts',
			$wpdb->prefix . 'fwa_campaigns',
			$wpdb->prefix . 'fwa_campaign_logs',
			$wpdb->prefix . 'fwa_schedules',
			$wpdb->prefix . 'fwa_logs',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Delete all plugin options.
	 *
	 * @since 1.0.0
	 */
	public static function delete_options() {
		global $wpdb;

		// Explicit list of known options.
		$options = array(
			'fwa_db_version',
			'fwa_api_base_url',
			'fwa_api_global_token',
			'fwa_webhook_secret',
			'fwa_debug_mode',
			'fwa_file_logging',
			'fwa_log_level',
			'fwa_log_retention_days',
			'fwa_rate_limit',
			'fwa_rate_limit_interval',
			'fwa_campaign_message_delay',
			'fwa_auto_reconnect',
			'fwa_health_check_interval',
			'fwa_default_country_code',
			'fwa_automation_rules',
			'fwa_delete_data_on_deactivate',
			'fwa_license_data',
			'fwa_license_server_url',
		);

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		// Catch any remaining fwa_ options added by extensions.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'fwa\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Delete all plugin transients.
	 *
	 * @since 1.0.0
	 */
	public static function delete_transients() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fwa\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_fwa\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Delete all plugin user meta.
	 *
	 * @since 1.0.0
	 */
	public static function delete_user_meta() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'fwa\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Remove log files created by the file logger.
	 *
	 * @since 1.0.0
	 */
	public static function delete_log_files() {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/fwa-logs';

		if ( is_dir( $log_dir ) ) {
			$files = glob( $log_dir . '/*' );
			if ( $files ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
			rmdir( $log_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
	}

	/**
	 * Clear all plugin cron events.
	 *
	 * @since 1.0.0
	 */
	public static function clear_cron_events() {
		$hooks = array(
			'fwa_process_queue',
			'fwa_health_check',
			'fwa_cleanup_logs',
			'fwa_instance_health_check_event',
			'fwa_process_campaigns',
			'fwa_license_revalidate',
		);

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}
}
