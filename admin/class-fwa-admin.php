<?php
/**
 * Main admin class.
 *
 * Registers menus, enqueues scripts, and routes admin pages.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Admin
 *
 * @since 1.0.0
 */
class FWA_Admin {

	/**
	 * Page slug → view file mapping.
	 *
	 * @var array
	 */
	private $pages = array(
		'fwa-dashboard'  => 'admin/views/dashboard.php',
		'fwa-instances'  => 'admin/views/instances.php',
		'fwa-messages'   => 'admin/views/messages.php',
		'fwa-campaigns'  => 'admin/views/campaigns.php',
		'fwa-contacts'   => 'admin/views/contacts.php',
		'fwa-automation' => 'admin/views/automation.php',
		'fwa-schedules'  => 'admin/views/schedules.php',
		'fwa-logs'       => 'admin/views/logs.php',
		'fwa-settings'   => 'admin/views/settings.php',
		'fwa-onboarding' => 'admin/views/onboarding.php',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * Fires on admin_init.
	 *
	 * Redirects to onboarding wizard on first activation.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		// Redirect to onboarding on first activation.
		if ( get_transient( 'fwa_activation_redirect' ) ) {
			delete_transient( 'fwa_activation_redirect' );
			if ( 'yes' !== get_option( 'fwa_onboarding_complete' ) && current_user_can( 'manage_options' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=fwa-onboarding' ) );
				exit;
			}
		}

		/**
		 * Fires when the FWA admin initializes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'fwa_admin_init' );
	}

	/**
	 * Register the admin menu and submenus.
	 *
	 * @since 1.0.0
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WhatsApp Automation', 'flexi-whatsapp-automation' ),
			__( 'WhatsApp Auto', 'flexi-whatsapp-automation' ),
			'manage_options',
			'fwa-dashboard',
			array( $this, 'render_page' ),
			'dashicons-format-chat',
			58
		);

		$submenus = array(
			'fwa-dashboard'  => __( 'Dashboard', 'flexi-whatsapp-automation' ),
			'fwa-instances'  => __( 'Instances', 'flexi-whatsapp-automation' ),
			'fwa-messages'   => __( 'Messages', 'flexi-whatsapp-automation' ),
			'fwa-campaigns'  => __( 'Campaigns', 'flexi-whatsapp-automation' ),
			'fwa-contacts'   => __( 'Contacts', 'flexi-whatsapp-automation' ),
			'fwa-automation' => __( 'Automation', 'flexi-whatsapp-automation' ),
			'fwa-schedules'  => __( 'Schedules', 'flexi-whatsapp-automation' ),
			'fwa-logs'       => __( 'Logs', 'flexi-whatsapp-automation' ),
			'fwa-settings'   => __( 'Settings', 'flexi-whatsapp-automation' ),
		);

		foreach ( $submenus as $slug => $label ) {
			add_submenu_page(
				'fwa-dashboard',
				$label . ' &mdash; ' . __( 'WhatsApp Automation', 'flexi-whatsapp-automation' ),
				$label,
				'manage_options',
				$slug,
				array( $this, 'render_page' )
			);
		}

		// Hidden onboarding page (no menu item).
		add_submenu_page(
			null,
			__( 'Setup Wizard', 'flexi-whatsapp-automation' ),
			'',
			'manage_options',
			'fwa-onboarding',
			array( $this, 'render_page' )
		);

		/**
		 * Fires after FWA admin menus are registered.
		 *
		 * Extensions can use this to add additional submenus.
		 *
		 * @since 1.0.0
		 *
		 * @param string $parent_slug The parent menu slug.
		 */
		do_action( 'fwa_admin_menu', 'fwa-dashboard' );
	}

	/**
	 * Render the current admin page.
	 *
	 * Views provide their own page headers, so no <h1> is emitted here
	 * to avoid duplication.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'fwa-dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $this->pages[ $page ] ) ) {
			$page = 'fwa-dashboard';
		}

		$view_file = FWA_PLUGIN_DIR . $this->pages[ $page ];

		echo '<div class="wrap fwa-wrap">';

		/**
		 * Fires before the FWA admin page content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $page Current page slug.
		 */
		do_action( 'fwa_admin_page_before', $page );

		if ( file_exists( $view_file ) ) {
			include $view_file;
		} else {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html(
				sprintf(
					/* translators: %s: view file path */
					__( 'View file not found: %s', 'flexi-whatsapp-automation' ),
					$this->pages[ $page ]
				)
			);
			echo '</p></div>';
		}

		/**
		 * Fires after the FWA admin page content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $page Current page slug.
		 */
		do_action( 'fwa_admin_page_after', $page );

		echo '</div>';
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook ) {
		if ( false === strpos( $hook, 'fwa-' ) ) {
			return;
		}

		wp_enqueue_style(
			'fwa-admin',
			FWA_PLUGIN_URL . 'admin/css/fwa-admin.css',
			array(),
			FWA_VERSION
		);

		wp_enqueue_script(
			'fwa-admin',
			FWA_PLUGIN_URL . 'admin/js/fwa-admin.js',
			array( 'jquery' ),
			FWA_VERSION,
			true
		);

		wp_localize_script(
			'fwa-admin',
			'fwa_admin',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'fwa_admin_nonce' ),
				'api_base'      => rest_url( 'fwa/v1/' ),
				'dashboard_url' => admin_url( 'admin.php?page=fwa-dashboard' ),
				'strings'       => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item? This action cannot be undone.', 'flexi-whatsapp-automation' ),
					'sending'        => __( 'Sending…', 'flexi-whatsapp-automation' ),
					'sent'           => __( 'Message sent successfully.', 'flexi-whatsapp-automation' ),
					'error'          => __( 'An error occurred. Please try again.', 'flexi-whatsapp-automation' ),
					'saved'          => __( 'Settings saved successfully.', 'flexi-whatsapp-automation' ),
					'loading'        => __( 'Loading…', 'flexi-whatsapp-automation' ),
					'no_results'     => __( 'No results found.', 'flexi-whatsapp-automation' ),
					'connected'      => __( 'Connected', 'flexi-whatsapp-automation' ),
					'disconnected'   => __( 'Disconnected', 'flexi-whatsapp-automation' ),
					'connecting'     => __( 'Connecting…', 'flexi-whatsapp-automation' ),
					'import_success' => __( 'Contacts imported successfully.', 'flexi-whatsapp-automation' ),
					'export_success' => __( 'Export completed successfully.', 'flexi-whatsapp-automation' ),
					'syncing'        => __( 'Syncing…', 'flexi-whatsapp-automation' ),
					'rules_saved'    => __( 'Rules saved!', 'flexi-whatsapp-automation' ),
					'edit'           => __( 'Edit', 'flexi-whatsapp-automation' ),
					'delete'         => __( 'Delete', 'flexi-whatsapp-automation' ),
					'sync_woo'       => __( 'Sync WooCommerce', 'flexi-whatsapp-automation' ),
					'active_instance'=> __( 'Active Instance', 'flexi-whatsapp-automation' ),
				),
			)
		);

		// Enqueue onboarding script on the onboarding page.
		if ( false !== strpos( $hook, 'fwa-onboarding' ) ) {
			wp_enqueue_script(
				'fwa-onboarding',
				FWA_PLUGIN_URL . 'admin/js/fwa-onboarding.js',
				array( 'jquery', 'fwa-admin' ),
				FWA_VERSION,
				true
			);
		}

		/**
		 * Fires after FWA admin scripts are enqueued.
		 *
		 * @since 1.0.0
		 *
		 * @param string $hook Current admin page hook.
		 */
		do_action( 'fwa_admin_enqueue_scripts', $hook );
	}

	/**
	 * Get an array of admin page hook suffixes for script loading.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_admin_page_hooks() {
		$hooks = array_keys( $this->pages );

		/**
		 * Filters the list of FWA admin page hooks.
		 *
		 * @since 1.0.0
		 *
		 * @param array $hooks Page hook suffixes.
		 */
		return apply_filters( 'fwa_admin_page_hooks', $hooks );
	}

	/**
	 * Get the display title for a page slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page Page slug.
	 * @return string
	 */
	private function get_page_title( $page ) {
		$titles = array(
			'fwa-dashboard'  => __( 'Dashboard', 'flexi-whatsapp-automation' ),
			'fwa-instances'  => __( 'Instances', 'flexi-whatsapp-automation' ),
			'fwa-messages'   => __( 'Messages', 'flexi-whatsapp-automation' ),
			'fwa-campaigns'  => __( 'Campaigns', 'flexi-whatsapp-automation' ),
			'fwa-contacts'   => __( 'Contacts', 'flexi-whatsapp-automation' ),
			'fwa-automation' => __( 'Automation', 'flexi-whatsapp-automation' ),
			'fwa-schedules'  => __( 'Schedules', 'flexi-whatsapp-automation' ),
			'fwa-logs'       => __( 'Logs', 'flexi-whatsapp-automation' ),
			'fwa-settings'   => __( 'Settings', 'flexi-whatsapp-automation' ),
			'fwa-onboarding' => __( 'Setup Wizard', 'flexi-whatsapp-automation' ),
		);

		return isset( $titles[ $page ] ) ? $titles[ $page ] : __( 'WhatsApp Automation', 'flexi-whatsapp-automation' );
	}
}
