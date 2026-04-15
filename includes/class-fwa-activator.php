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
	}

	/**
	 * Register recurring cron events.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_cron_events() {

		// Register custom cron intervals.
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );

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

	/**
	 * Add custom cron schedule intervals.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public static function add_cron_schedules( $schedules ) {

		if ( ! isset( $schedules['every_minute'] ) ) {
			$schedules['every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every Minute', 'flexi-whatsapp-automation' ),
			);
		}

		if ( ! isset( $schedules['every_five_minutes'] ) ) {
			$schedules['every_five_minutes'] = array(
				'interval' => 300,
				'display'  => __( 'Every 5 Minutes', 'flexi-whatsapp-automation' ),
			);
		}

		return $schedules;
	}
}
