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

		// 3. API Client.
		require_once FWA_PLUGIN_DIR . 'includes/api/class-fwa-api-client.php';

		// 4. Instance Manager.
		require_once FWA_PLUGIN_DIR . 'includes/instance/class-fwa-instance-manager.php';

		// 5. Messaging Engine.
		require_once FWA_PLUGIN_DIR . 'includes/messaging/class-fwa-messaging-engine.php';

		// 6. Campaign Manager.
		require_once FWA_PLUGIN_DIR . 'includes/campaign/class-fwa-campaign-manager.php';

		// 7. Scheduler / Queue.
		require_once FWA_PLUGIN_DIR . 'includes/scheduler/class-fwa-scheduler.php';

		// 8. Automation Engine.
		require_once FWA_PLUGIN_DIR . 'includes/automation/class-fwa-automation-engine.php';

		// 9. Webhook Controller.
		require_once FWA_PLUGIN_DIR . 'includes/webhook/class-fwa-webhook-controller.php';

		// 10. Logger.
		require_once FWA_PLUGIN_DIR . 'includes/logging/class-fwa-logger.php';

		// 11. Admin (conditional).
		if ( is_admin() ) {
			require_once FWA_PLUGIN_DIR . 'admin/class-fwa-admin.php';
		}

		// 12. Public.
		require_once FWA_PLUGIN_DIR . 'public/class-fwa-public.php';
	}

	/**
	 * Initialize all components and fire the ready hook.
	 *
	 * @since 1.0.0
	 */
	public function run() {

		$this->load_dependencies();

		// Core modules.
		new FWA_API_Client();
		new FWA_Instance_Manager();
		new FWA_Messaging_Engine();
		new FWA_Campaign_Manager();
		new FWA_Scheduler();
		new FWA_Automation_Engine();
		new FWA_Webhook_Controller();
		new FWA_Logger();

		// Admin.
		if ( is_admin() ) {
			new FWA_Admin();
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
