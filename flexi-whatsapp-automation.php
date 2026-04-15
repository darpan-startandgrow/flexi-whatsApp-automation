<?php
/**
 * Plugin Name: Flexi WhatsApp Automation
 * Plugin URI:  https://github.com/darpan-startandgrow/flexi-whatsApp-automation
 * Description: WhatsApp Web-based messaging automation for WordPress & WooCommerce. Multi-account management, campaigns, scheduling, and logging. Works as an add-on for Flexi Revive Cart.
 * Version:     1.0.0
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
define( 'FWA_VERSION', '1.0.0' );
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
