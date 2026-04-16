<?php
/**
 * Plugin Name: Flexi WhatsApp Automation
 * Plugin URI:  https://github.com/darpan-startandgrow/flexi-whatsApp-automation
 * Description: WhatsApp Web-based messaging automation for WordPress & WooCommerce. Multi-account management, campaigns, scheduling, and logging. Works standalone or enhances Flexi Revive Cart when present.
 * Version:     1.1.0
 * Author:      Darpan Sarmah
 * Author URI:  https://github.com/darpan-startandgrow
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flexi-whatsapp-automation
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 *
 * @package Flexi_WhatsApp_Automation
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'FWA_VERSION', '1.1.0' );
define( 'FWA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FWA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FWA_DB_VERSION', '1.0.0' );

/**
 * Check if Flexi Revive Cart (free) is active.
 *
 * @return bool
 */
function fwa_is_flexi_revive_cart_active() {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	foreach ( $active_plugins as $plugin ) {
		if ( false !== strpos( $plugin, 'flexi-revive-cart.php' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check if Flexi Revive Cart Pro is installed, active, AND has a valid license.
 *
 * This is the authoritative check that MUST be used before accessing any
 * FRC Pro data, hooks, or exposing FRC Pro-related options in the UI.
 *
 * The check verifies three independent conditions:
 *   1. FRC_PRO_VERSION constant is defined (Pro plugin is loaded).
 *   2. The frc_pro_license_valid filter returns true (Pro plugin confirms
 *      its own license validity).
 *   3. A convenience fwa_frc_pro_licensed filter allows extensions to
 *      override the result if needed.
 *
 * Simply defining FRC_PRO_VERSION in wp-config.php will NOT return true
 * because the frc_pro_license_valid filter will still return false without
 * the actual Pro plugin loaded and licensed.
 *
 * @since 1.2.0
 *
 * @return bool True only if FRC Pro is installed, active, and licensed.
 */
function fwa_is_frc_pro_licensed() {
	// The FRC free plugin exposes frc_is_pro_licensed() which checks both
	// the FRC_PRO_VERSION constant and the frc_pro_license_valid filter.
	if ( function_exists( 'frc_is_pro_licensed' ) ) {
		$licensed = frc_is_pro_licensed();
	} else {
		// Fallback: replicate the same two-part check so FWA works even
		// if an older version of FRC free is present.
		$licensed = defined( 'FRC_PRO_VERSION' ) && apply_filters( 'frc_pro_license_valid', false );
	}

	/**
	 * Filters whether FRC Pro is considered licensed for FWA purposes.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $licensed True if FRC Pro is installed, active, and licensed.
	 */
	return (bool) apply_filters( 'fwa_frc_pro_licensed', $licensed );
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function fwa_is_woocommerce_active() {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	foreach ( $active_plugins as $plugin ) {
		if ( false !== strpos( $plugin, 'woocommerce.php' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Activation hook.
 */
function fwa_activate_plugin() {
	require_once FWA_PLUGIN_DIR . 'includes/class-fwa-activator.php';
	FWA_Activator::activate();
}
register_activation_hook( __FILE__, 'fwa_activate_plugin' );

/**
 * Deactivation hook.
 */
function fwa_deactivate_plugin() {
	require_once FWA_PLUGIN_DIR . 'includes/class-fwa-deactivator.php';

	$cleanup = get_transient( 'fwa_cleanup_on_deactivate' );
	FWA_Deactivator::deactivate( ! empty( $cleanup ) );

	if ( ! empty( $cleanup ) ) {
		delete_transient( 'fwa_cleanup_on_deactivate' );
	}
}
register_deactivation_hook( __FILE__, 'fwa_deactivate_plugin' );

/**
 * Declare HPOS compatibility for WooCommerce.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Initialize the plugin.
 */
function fwa_init_plugin() {
	load_plugin_textdomain( 'flexi-whatsapp-automation', false, dirname( FWA_PLUGIN_BASENAME ) . '/languages' );

	require_once FWA_PLUGIN_DIR . 'includes/class-fwa-loader.php';
	$loader = new FWA_Loader();
	$loader->run();

	/**
	 * Fires after the Flexi WhatsApp Automation plugin is fully loaded.
	 *
	 * Third-party plugins and add-ons should hook here to extend functionality.
	 *
	 * @since 1.0.0
	 */
	do_action( 'fwa_loaded' );
}
add_action( 'plugins_loaded', 'fwa_init_plugin', 15 );

/**
 * AJAX handler for deactivation data cleanup consent.
 */
add_action( 'wp_ajax_fwa_deactivation_cleanup', function () {
	check_ajax_referer( 'fwa_deactivation_nonce', 'nonce' );

	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'flexi-whatsapp-automation' ) ) );
	}

	$cleanup = isset( $_POST['cleanup'] ) ? sanitize_text_field( wp_unslash( $_POST['cleanup'] ) ) : 'no';
	if ( 'yes' === $cleanup ) {
		set_transient( 'fwa_cleanup_on_deactivate', true, 300 );
	}

	wp_send_json_success();
});

/**
 * Enqueue the deactivation modal script on the Plugins page.
 *
 * @since 1.0.0
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'plugins.php' !== $hook ) {
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
		'fwa_deactivation',
		array(
			'plugin_basename' => FWA_PLUGIN_BASENAME,
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'fwa_deactivation_nonce' ),
			'strings'         => array(
				'title'       => __( 'Flexi WhatsApp Automation – Deactivation', 'flexi-whatsapp-automation' ),
				'message'     => __( 'Would you like to delete all plugin data (database tables, settings, and logs) or keep it for future use?', 'flexi-whatsapp-automation' ),
				'delete_data' => __( 'Delete Data & Deactivate', 'flexi-whatsapp-automation' ),
				'keep_data'   => __( 'Keep Data & Deactivate', 'flexi-whatsapp-automation' ),
				'cancel'      => __( 'Cancel', 'flexi-whatsapp-automation' ),
			),
		)
	);
});
