<?php
/**
 * Plugin activation handler.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Activator
 *
 * Runs on plugin activation to create database tables,
 * set default options, and schedule cron events.
 *
 * @since 1.0.0
 */
class FWA_Activator {

	/**
	 * Execute all activation tasks.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {

		self::create_tables();
		self::set_default_options();
		self::schedule_cron_events();

		update_option( 'fwa_db_version', FWA_DB_VERSION );

		flush_rewrite_rules();
	}

	/**
	 * Create all plugin database tables.
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {

		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-db.php';
		FWA_DB::install();
	}

	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {

		add_option( 'fwa_api_base_url', '' );
		add_option( 'fwa_debug_mode', 'no' );
		add_option( 'fwa_rate_limit', 20 );
		add_option( 'fwa_rate_limit_interval', 60 );
		add_option( 'fwa_default_country_code', '+1' );
		add_option( 'fwa_enable_automation', 'yes' );
		add_option( 'fwa_enable_logging', 'yes' );
		add_option( 'fwa_enable_campaigns', 'yes' );
		add_option( 'fwa_log_level', 'info' );
		add_option( 'fwa_log_retention_days', 30 );
		add_option( 'fwa_campaign_message_delay', 3 );
		add_option( 'fwa_auto_reconnect', 'yes' );
		add_option( 'fwa_health_check_interval', 5 );

		// Trigger onboarding redirect ONLY if setup has not been completed before.
		// On reactivation after a completed setup, skip the wizard entirely.
		if ( 'yes' !== get_option( 'fwa_setup_complete' ) ) {
			set_transient( 'fwa_activation_redirect', true, 30 );
		}
	}

	/**
	 * Register recurring cron events.
	 *
	 * Uses FWA_Loader::add_cron_schedules() to avoid duplicating custom
	 * interval definitions.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_cron_events() {

		// Temporarily register custom intervals so wp_schedule_event can resolve them.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-loader.php';
		add_filter( 'cron_schedules', array( 'FWA_Loader', 'add_cron_schedules' ) );

		if ( ! wp_next_scheduled( 'fwa_process_queue' ) ) {
			wp_schedule_event( time(), 'every_minute', 'fwa_process_queue' );
		}

		if ( ! wp_next_scheduled( 'fwa_health_check' ) ) {
			wp_schedule_event( time(), 'every_five_minutes', 'fwa_health_check' );
		}

		if ( ! wp_next_scheduled( 'fwa_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'fwa_cleanup_logs' );
		}
	}
}
