<?php
/**
 * Main plugin orchestrator.
 *
 * Loads all dependencies and initializes components.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Loader
 *
 * Bootstraps the plugin by including required files and
 * creating instances of each module class.
 *
 * @since 1.0.0
 */
class FWA_Loader {

	/**
	 * Load all dependency files.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {

		// 1. Helpers.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-helpers.php';

		// 2. Database installer.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-db.php';

		// 3. Uninstaller (needed by deactivator for full-cleanup path).
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-uninstaller.php';

		// 3b. Licensing — loaded early so guard is available to all modules.
		require_once FWA_PLUGIN_DIR . 'includes/licensing/class-fwa-license-client.php';
		require_once FWA_PLUGIN_DIR . 'includes/licensing/class-fwa-license-manager.php';
		require_once FWA_PLUGIN_DIR . 'includes/licensing/class-fwa-license-guard.php';

		// 4. API Client.
		require_once FWA_PLUGIN_DIR . 'includes/api/class-fwa-api-client.php';

		// 5. Instance Manager.
		require_once FWA_PLUGIN_DIR . 'includes/instance/class-fwa-instance-manager.php';

		// 6. Message Sender.
		require_once FWA_PLUGIN_DIR . 'includes/messaging/class-fwa-message-sender.php';

		// 7. Contact Manager.
		require_once FWA_PLUGIN_DIR . 'includes/messaging/class-fwa-contact-manager.php';

		// 8. Campaign Manager.
		require_once FWA_PLUGIN_DIR . 'includes/campaign/class-fwa-campaign-manager.php';

		// 9. Scheduler / Queue.
		require_once FWA_PLUGIN_DIR . 'includes/scheduler/class-fwa-scheduler.php';

		// 10. Automation Engine.
		require_once FWA_PLUGIN_DIR . 'includes/automation/class-fwa-automation-engine.php';

		// 11. Webhook Controller.
		require_once FWA_PLUGIN_DIR . 'includes/webhook/class-fwa-webhook-controller.php';

		// 12. Logger.
		require_once FWA_PLUGIN_DIR . 'includes/logging/class-fwa-logger.php';

		// 12b. OTP Manager.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-otp-manager.php';

		// 12c. Template Engine.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-template-engine.php';

		// 12d. OTP Login (public shortcodes).
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-otp-login.php';

		// 12e. Chat Widget.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-chat-widget.php';

		// 12f. Advanced Phone Field.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-phone-field.php';

		// 13. Dashboard Widget.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-dashboard-widget.php';

		// 14. Admin (conditional).
		if ( is_admin() ) {
			require_once FWA_PLUGIN_DIR . 'admin/class-fwa-admin.php';
			require_once FWA_PLUGIN_DIR . 'admin/class-fwa-admin-settings.php';
			require_once FWA_PLUGIN_DIR . 'admin/class-fwa-admin-ajax.php';
		}

		// 15. Public.
		require_once FWA_PLUGIN_DIR . 'includes/class-fwa-public.php';
	}

	/**
	 * Register custom cron schedule intervals.
	 *
	 * Must run on every request so WordPress can resolve
	 * the custom intervals stored by the activator.
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

	/**
	 * Initialize all components and fire the ready hook.
	 *
	 * @since 1.0.0
	 */
	public function run() {

		$this->load_dependencies();

		// Ensure custom cron intervals are always available.
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );

		// Initialize the license manager (singleton) and schedule revalidation.
		$license_mgr = FWA_License_Manager::get_instance();
		$license_mgr->schedule_revalidation();

		// Show admin notice when license is missing/invalid.
		add_action( 'admin_notices', array( 'FWA_License_Guard', 'maybe_show_admin_notice' ) );

		// Core modules.
		new FWA_API_Client();
		new FWA_Instance_Manager();
		new FWA_Message_Sender();
		new FWA_Contact_Manager();
		new FWA_Campaign_Manager();
		new FWA_Scheduler();
		new FWA_Automation_Engine();
		new FWA_Webhook_Controller();
		FWA_Logger::get_instance();

		// Dashboard widget.
		new FWA_Dashboard_Widget();

		// Chat widget (frontend).
		new FWA_Chat_Widget();

		// Advanced Phone Field.
		new FWA_Phone_Field();

		// OTP Login (public shortcodes).
		new FWA_OTP_Login();

		// Admin.
		if ( is_admin() ) {
			new FWA_Admin();
			new FWA_Admin_Settings();
			new FWA_Admin_AJAX();
		}

		// Public-facing.
		new FWA_Public();

		/**
		 * Fires after all FWA modules have been instantiated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'fwa_modules_loaded' );
	}
}
