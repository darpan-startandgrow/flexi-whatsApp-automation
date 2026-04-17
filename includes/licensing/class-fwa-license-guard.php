<?php
/**
 * License enforcement guard.
 *
 * Distributed gate-keeper that prevents critical operations when the
 * license is invalid. This class is intentionally loaded early and
 * checked in multiple places to avoid single-point bypass.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_License_Guard
 *
 * Provides static helper methods that each critical operation calls
 * before proceeding. When the license is invalid the guard returns a
 * WP_Error so that calling code can handle it cleanly.
 *
 * @since 1.2.0
 */
class FWA_License_Guard {

	/**
	 * Cached validity result for the current request to avoid
	 * repeated option lookups.
	 *
	 * @since 1.2.0
	 * @var bool|null
	 */
	private static $cached_valid = null;

	/*--------------------------------------------------------------
	 * Primary guard methods
	 *------------------------------------------------------------*/

	/**
	 * Check whether the license allows the requested operation.
	 *
	 * Returns true when licensed, WP_Error when not.
	 * Every critical code path should call this before executing.
	 *
	 * @since 1.2.0
	 *
	 * @param string $operation Human-readable operation name for error messages.
	 * @return true|WP_Error
	 */
	public static function authorize( $operation = '' ) {
		if ( self::is_licensed() ) {
			return true;
		}

		$message = empty( $operation )
			? __( 'A valid license is required to use this feature.', 'flexi-whatsapp-automation' )
			: sprintf(
				/* translators: %s: operation name */
				__( 'A valid license is required for: %s.', 'flexi-whatsapp-automation' ),
				$operation
			);

		return new WP_Error( 'fwa_license_required', $message );
	}

	/**
	 * Whether the plugin is currently licensed.
	 *
	 * Caches the result per-request to minimise overhead.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public static function is_licensed() {
		if ( null !== self::$cached_valid ) {
			return self::$cached_valid;
		}

		if ( ! class_exists( 'FWA_License_Manager' ) ) {
			self::$cached_valid = false;
			return false;
		}

		self::$cached_valid = FWA_License_Manager::get_instance()->is_valid();
		return self::$cached_valid;
	}

	/**
	 * Reset the cached validity (e.g. after activation/deactivation).
	 *
	 * @since 1.2.0
	 */
	public static function reset_cache() {
		self::$cached_valid = null;
	}

	/*--------------------------------------------------------------
	 * Operation-specific guards
	 *------------------------------------------------------------*/

	/**
	 * Guard: WhatsApp API requests.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error
	 */
	public static function can_make_api_request() {
		return self::authorize( __( 'WhatsApp API communication', 'flexi-whatsapp-automation' ) );
	}

	/**
	 * Guard: Sending messages.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error
	 */
	public static function can_send_message() {
		return self::authorize( __( 'Sending WhatsApp messages', 'flexi-whatsapp-automation' ) );
	}

	/**
	 * Guard: Campaign execution.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error
	 */
	public static function can_execute_campaign() {
		return self::authorize( __( 'Campaign execution', 'flexi-whatsapp-automation' ) );
	}

	/**
	 * Guard: Automation triggers.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error
	 */
	public static function can_run_automation() {
		return self::authorize( __( 'Automation triggers', 'flexi-whatsapp-automation' ) );
	}

	/**
	 * Guard: Instance connection.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error
	 */
	public static function can_connect_instance() {
		return self::authorize( __( 'WhatsApp instance connection', 'flexi-whatsapp-automation' ) );
	}

	/**
	 * Guard: Scheduled message processing.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error
	 */
	public static function can_process_schedule() {
		return self::authorize( __( 'Scheduled message processing', 'flexi-whatsapp-automation' ) );
	}

	/*--------------------------------------------------------------
	 * Admin helpers
	 *------------------------------------------------------------*/

	/**
	 * Render an admin notice when the license is inactive or invalid.
	 *
	 * Called via the admin_notices hook.
	 *
	 * @since 1.2.0
	 */
	public static function maybe_show_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show on FWA pages.
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'fwa-' ) ) {
			return;
		}

		if ( self::is_licensed() ) {
			return;
		}

		$license_url = admin_url( 'admin.php?page=fwa-license' );
		$status      = class_exists( 'FWA_License_Manager' ) ? FWA_License_Manager::get_instance()->get_status() : 'inactive';

		switch ( $status ) {
			case 'expired':
				$message = sprintf(
					/* translators: %s: license page URL */
					__( '<strong>Flexi WhatsApp Automation:</strong> Your license has expired. Core features (messaging, campaigns, automation) are disabled. <a href="%s">Renew your license</a> to restore full functionality.', 'flexi-whatsapp-automation' ),
					esc_url( $license_url )
				);
				break;

			case 'invalid':
				$message = sprintf(
					/* translators: %s: license page URL */
					__( '<strong>Flexi WhatsApp Automation:</strong> Your license is invalid. Core features are disabled. <a href="%s">Check your license</a>.', 'flexi-whatsapp-automation' ),
					esc_url( $license_url )
				);
				break;

			default:
				$message = sprintf(
					/* translators: %s: license page URL */
					__( '<strong>Flexi WhatsApp Automation:</strong> No license key found. Core features (messaging, campaigns, automation) are disabled. <a href="%s">Activate your license</a> to get started.', 'flexi-whatsapp-automation' ),
					esc_url( $license_url )
				);
				break;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			wp_kses(
				$message,
				array(
					'strong' => array(),
					'a'      => array( 'href' => array() ),
				)
			)
		);
	}
}
