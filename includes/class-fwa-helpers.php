<?php
/**
 * Static utility helpers.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Helpers
 *
 * Provides static helper methods used across the plugin.
 *
 * @since 1.0.0
 */
class FWA_Helpers {

	/*--------------------------------------------------------------
	 * Table name accessors
	 *------------------------------------------------------------*/

	/**
	 * Get the instances table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_instance_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_instances';
	}

	/**
	 * Get the messages table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_messages_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_messages';
	}

	/**
	 * Get the contacts table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_contacts_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_contacts';
	}

	/**
	 * Get the campaigns table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_campaigns_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_campaigns';
	}

	/**
	 * Get the campaign logs table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_campaign_logs_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_campaign_logs';
	}

	/**
	 * Get the schedules table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_schedules_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_schedules';
	}

	/**
	 * Get the logs table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_logs_table() {
		global $wpdb;
		return $wpdb->prefix . 'fwa_logs';
	}

	/*--------------------------------------------------------------
	 * Phone utilities
	 *------------------------------------------------------------*/

	/**
	 * Sanitize a phone number, keeping only digits and an optional leading +.
	 *
	 * @since 1.0.0
	 *
	 * @param string $phone Raw phone number.
	 * @return string Sanitized phone number.
	 */
	public static function sanitize_phone( $phone ) {
		$phone = trim( $phone );

		$has_plus = ( 0 === strpos( $phone, '+' ) );

		$phone = preg_replace( '/[^0-9]/', '', $phone );

		if ( $has_plus ) {
			$phone = '+' . $phone;
		}

		return $phone;
	}

	/**
	 * Format a phone number to E.164 standard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $phone        Raw phone number.
	 * @param string $country_code Country calling code without + (e.g. '91', '1').
	 * @return string E.164 formatted phone number.
	 */
	public static function format_phone_e164( $phone, $country_code = '' ) {
		$phone = self::sanitize_phone( $phone );

		// Already in E.164 format.
		if ( 0 === strpos( $phone, '+' ) ) {
			return $phone;
		}

		// Strip leading zero (local number convention).
		$phone = ltrim( $phone, '0' );

		if ( ! empty( $country_code ) ) {
			$country_code = ltrim( $country_code, '+' );
			$phone        = '+' . $country_code . $phone;
		} else {
			$phone = '+' . $phone;
		}

		return $phone;
	}

	/*--------------------------------------------------------------
	 * General utilities
	 *------------------------------------------------------------*/

	/**
	 * Generate a cryptographically secure random hex token.
	 *
	 * @since 1.0.0
	 *
	 * @param int $length Number of random bytes (output is twice this in hex chars).
	 * @return string Hex token.
	 */
	public static function generate_token( $length = 32 ) {
		try {
			return bin2hex( random_bytes( $length ) );
		} catch ( \Exception $e ) {
			return wp_generate_password( $length * 2, false );
		}
	}

	/**
	 * Return a human-readable "time ago" string from a datetime.
	 *
	 * @since 1.0.0
	 *
	 * @param string $datetime A date/time string parseable by strtotime().
	 * @return string Human-readable time difference (e.g. "2 hours ago").
	 */
	public static function time_ago( $datetime ) {
		$timestamp = strtotime( $datetime );

		if ( false === $timestamp ) {
			return '';
		}

		return sprintf(
			/* translators: %s: human-readable time difference */
			__( '%s ago', 'flexi-whatsapp-automation' ),
			human_time_diff( $timestamp, time() )
		);
	}

	/**
	 * Check whether a string is valid JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to validate.
	 * @return bool True if valid JSON.
	 */
	public static function is_json( $string ) {
		if ( ! is_string( $string ) || '' === $string ) {
			return false;
		}

		json_decode( $string );

		return ( json_last_error() === JSON_ERROR_NONE );
	}

	/**
	 * Retrieve the currently active WhatsApp instance.
	 *
	 * Looks for the instance flagged as active in the database.
	 *
	 * @since 1.0.0
	 *
	 * @return object|null Instance row object or null if none active.
	 */
	public static function get_active_instance() {
		global $wpdb;

		$table = self::get_instance_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$instance = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE is_active = %d LIMIT 1",
				1
			)
		);

		return $instance ? $instance : null;
	}

	/**
	 * Mask a sensitive token for safe display.
	 *
	 * Shows the first 6 and last 4 characters; everything else is replaced with asterisks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token The token to mask.
	 * @return string Masked token string.
	 */
	public static function mask_token( $token ) {
		$token = (string) $token;
		$len   = strlen( $token );

		if ( $len <= 10 ) {
			return str_repeat( '*', $len );
		}

		return substr( $token, 0, 6 ) . str_repeat( '*', $len - 10 ) . substr( $token, -4 );
	}

	/**
	 * Get the client IP address from server superglobals.
	 *
	 * Checks common proxy headers before falling back to REMOTE_ADDR.
	 *
	 * @since 1.2.0
	 *
	 * @return string IP address string.
	 */
	public static function get_client_ip() {
		$keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// X-Forwarded-For may be a comma-separated list; take the first.
				$parts = explode( ',', $ip );
				$ip    = trim( $parts[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}
}
