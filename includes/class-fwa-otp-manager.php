<?php
/**
 * OTP Manager for phone number verification.
 *
 * Generates, stores, and validates OTPs sent via WhatsApp messages
 * using the system bot session.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_OTP_Manager
 *
 * Handles OTP generation, storage (transients), validation,
 * rate-limiting, and logging for phone number verification.
 *
 * @since 1.1.0
 */
class FWA_OTP_Manager {

	/**
	 * OTP expiry in seconds (5 minutes).
	 *
	 * @since 1.1.0
	 * @var int
	 */
	const OTP_EXPIRY = 300;

	/**
	 * Maximum verification attempts per OTP.
	 *
	 * @since 1.1.0
	 * @var int
	 */
	const MAX_ATTEMPTS = 5;

	/**
	 * Rate limit: max OTP requests per phone per hour.
	 *
	 * @since 1.1.0
	 * @var int
	 */
	const RATE_LIMIT = 5;

	/**
	 * Rate limit window in seconds (1 hour).
	 *
	 * @since 1.1.0
	 * @var int
	 */
	const RATE_LIMIT_WINDOW = 3600;

	/**
	 * Generate and send an OTP to the given phone number.
	 *
	 * Uses the bot (active) WhatsApp session to deliver the OTP
	 * as a WhatsApp message.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Recipient phone number (E.164 format preferred).
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function send_otp( $phone ) {
		$phone = FWA_Helpers::sanitize_phone( $phone );

		if ( empty( $phone ) ) {
			return new WP_Error(
				'fwa_otp_invalid_phone',
				__( 'A valid phone number is required.', 'flexi-whatsapp-automation' )
			);
		}

		// Rate limiting.
		$rate_check = $this->check_rate_limit( $phone );
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		// Generate a 6-digit OTP.
		$otp = $this->generate_otp();

		// Store OTP with metadata.
		$this->store_otp( $phone, $otp );

		// Increment rate limit counter.
		$this->increment_rate_limit( $phone );

		// Send OTP via WhatsApp using the bot session.
		$send_result = $this->deliver_otp( $phone, $otp );

		if ( is_wp_error( $send_result ) ) {
			$this->log( 'error', sprintf( 'Failed to send OTP to %s: %s', $phone, $send_result->get_error_message() ) );
			return $send_result;
		}

		$this->log( 'info', sprintf( 'OTP sent to %s', $phone ) );

		return true;
	}

	/**
	 * Verify an OTP for the given phone number.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Recipient phone number.
	 * @param string $otp   The OTP code entered by the user.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function verify_otp( $phone, $otp ) {
		$phone = FWA_Helpers::sanitize_phone( $phone );
		$otp   = sanitize_text_field( $otp );

		if ( empty( $phone ) || empty( $otp ) ) {
			return new WP_Error(
				'fwa_otp_missing_fields',
				__( 'Phone number and OTP are required.', 'flexi-whatsapp-automation' )
			);
		}

		$stored = $this->get_stored_otp( $phone );

		if ( ! $stored ) {
			$this->log( 'warning', sprintf( 'OTP verification failed for %s: no OTP found or expired', $phone ) );
			return new WP_Error(
				'fwa_otp_expired',
				__( 'OTP has expired or was not requested. Please request a new one.', 'flexi-whatsapp-automation' )
			);
		}

		// Check attempt limit.
		if ( $stored['attempts'] >= self::MAX_ATTEMPTS ) {
			$this->clear_otp( $phone );
			$this->log( 'warning', sprintf( 'OTP verification for %s exceeded max attempts', $phone ) );
			return new WP_Error(
				'fwa_otp_max_attempts',
				__( 'Too many incorrect attempts. Please request a new OTP.', 'flexi-whatsapp-automation' )
			);
		}

		// Increment attempts before checking.
		$stored['attempts']++;
		$this->update_stored_otp( $phone, $stored );

		if ( ! hash_equals( $stored['otp'], $otp ) ) {
			$remaining = self::MAX_ATTEMPTS - $stored['attempts'];
			$this->log( 'warning', sprintf( 'Incorrect OTP for %s (attempt %d)', $phone, $stored['attempts'] ) );
			return new WP_Error(
				'fwa_otp_invalid',
				sprintf(
					/* translators: %d: remaining attempts */
					__( 'Invalid OTP. You have %d attempt(s) remaining.', 'flexi-whatsapp-automation' ),
					$remaining
				)
			);
		}

		// OTP verified — clear it.
		$this->clear_otp( $phone );
		$this->log( 'info', sprintf( 'OTP verified successfully for %s', $phone ) );

		return true;
	}

	/**
	 * Check if the bot session is available for sending messages.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if a connected instance exists.
	 */
	public function is_bot_session_available() {
		$instance = FWA_Helpers::get_active_instance();
		return ( $instance && 'connected' === $instance->status );
	}

	/*--------------------------------------------------------------
	 * Internal helpers
	 *------------------------------------------------------------*/

	/**
	 * Generate a cryptographically secure 6-digit OTP.
	 *
	 * @since 1.1.0
	 *
	 * @return string 6-digit OTP code.
	 */
	private function generate_otp() {
		try {
			$otp = random_int( 100000, 999999 );
		} catch ( \Exception $e ) {
			$otp = wp_rand( 100000, 999999 );
		}
		return (string) $otp;
	}

	/**
	 * Store an OTP in a transient with metadata.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number (key).
	 * @param string $otp   OTP code.
	 */
	private function store_otp( $phone, $otp ) {
		$key  = $this->get_transient_key( $phone );
		$data = array(
			'otp'        => $otp,
			'attempts'   => 0,
			'created_at' => time(),
		);
		set_transient( $key, $data, self::OTP_EXPIRY );
	}

	/**
	 * Retrieve stored OTP data.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @return array|false OTP data array or false if not found/expired.
	 */
	private function get_stored_otp( $phone ) {
		$key  = $this->get_transient_key( $phone );
		$data = get_transient( $key );
		return is_array( $data ) ? $data : false;
	}

	/**
	 * Update stored OTP data (e.g. increment attempts).
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @param array  $data  Updated OTP data.
	 */
	private function update_stored_otp( $phone, $data ) {
		$key       = $this->get_transient_key( $phone );
		$remaining = self::OTP_EXPIRY - ( time() - $data['created_at'] );
		if ( $remaining > 0 ) {
			set_transient( $key, $data, $remaining );
		}
	}

	/**
	 * Clear stored OTP data.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 */
	private function clear_otp( $phone ) {
		delete_transient( $this->get_transient_key( $phone ) );
	}

	/**
	 * Build the transient key for a phone number.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @return string Transient key.
	 */
	private function get_transient_key( $phone ) {
		return 'fwa_otp_' . md5( $phone );
	}

	/**
	 * Check the per-phone rate limit.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @return true|WP_Error
	 */
	private function check_rate_limit( $phone ) {
		$key     = 'fwa_otp_rate_' . md5( $phone );
		$current = (int) get_transient( $key );

		if ( $current >= self::RATE_LIMIT ) {
			$this->log( 'warning', sprintf( 'OTP rate limit exceeded for %s', $phone ) );
			return new WP_Error(
				'fwa_otp_rate_limited',
				__( 'Too many OTP requests. Please try again later.', 'flexi-whatsapp-automation' )
			);
		}

		return true;
	}

	/**
	 * Increment the rate limit counter.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 */
	private function increment_rate_limit( $phone ) {
		$key     = 'fwa_otp_rate_' . md5( $phone );
		$current = (int) get_transient( $key );

		if ( 0 === $current ) {
			set_transient( $key, 1, self::RATE_LIMIT_WINDOW );
		} else {
			set_transient( $key, $current + 1, self::RATE_LIMIT_WINDOW );
		}
	}

	/**
	 * Deliver an OTP via WhatsApp using the active bot session.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @param string $otp   OTP code.
	 * @return int|WP_Error Message ID on success, WP_Error on failure.
	 */
	private function deliver_otp( $phone, $otp ) {
		$instance = FWA_Helpers::get_active_instance();

		if ( ! $instance ) {
			return new WP_Error(
				'fwa_otp_no_bot_session',
				__( 'No active WhatsApp bot session available. Please connect a WhatsApp instance first, or skip phone verification.', 'flexi-whatsapp-automation' )
			);
		}

		if ( 'connected' !== $instance->status ) {
			return new WP_Error(
				'fwa_otp_bot_disconnected',
				__( 'The WhatsApp bot session is not connected. Please reconnect, or skip phone verification.', 'flexi-whatsapp-automation' )
			);
		}

		$site_name = get_bloginfo( 'name' );
		$message   = sprintf(
			/* translators: 1: OTP code, 2: site name */
			__( 'Your verification code for %2$s is: *%1$s*. This code expires in 5 minutes. Do not share it with anyone.', 'flexi-whatsapp-automation' ),
			$otp,
			$site_name
		);

		$sender = new FWA_Message_Sender();
		return $sender->send_text( $phone, $message, $instance->id );
	}

	/**
	 * Log a message via FWA_Logger when available.
	 *
	 * @since 1.1.0
	 *
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 */
	private function log( $level, $message ) {
		if ( class_exists( 'FWA_Logger' ) ) {
			$logger = FWA_Logger::get_instance();
			if ( method_exists( $logger, 'log' ) ) {
				$logger->log( $level, $message, 'otp-manager' );
			}
		}
	}
}
