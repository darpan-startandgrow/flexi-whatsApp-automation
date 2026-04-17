<?php
/**
 * License manager — activation, deactivation, storage, and revalidation.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_License_Manager
 *
 * Singleton that manages the full license lifecycle:
 *   - Activation / deactivation via the remote server
 *   - Local storage of license data (encrypted)
 *   - Periodic revalidation using WP-Cron
 *   - Domain-bound enforcement
 *
 * @since 1.2.0
 */
class FWA_License_Manager {

	/**
	 * Option key for stored license data.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const OPTION_KEY = 'fwa_license_data';

	/**
	 * Cron hook for periodic revalidation.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const CRON_HOOK = 'fwa_license_revalidate';

	/**
	 * Default revalidation interval in seconds (12 hours).
	 *
	 * @since 1.2.0
	 * @var int
	 */
	const REVALIDATION_INTERVAL = 43200;

	/**
	 * Grace period in seconds after failed revalidation (72 hours).
	 *
	 * During the grace period the license is still considered valid
	 * to avoid disruption caused by temporary server outages.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	const GRACE_PERIOD = 259200;

	/**
	 * Singleton instance.
	 *
	 * @since 1.2.0
	 * @var FWA_License_Manager|null
	 */
	private static $instance = null;

	/**
	 * License client.
	 *
	 * @since 1.2.0
	 * @var FWA_License_Client
	 */
	private $client;

	/**
	 * Cached license data (decoded from option).
	 *
	 * @since 1.2.0
	 * @var array|null
	 */
	private $license_data = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.2.0
	 *
	 * @return FWA_License_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->client = new FWA_License_Client();
		add_action( self::CRON_HOOK, array( $this, 'revalidate' ) );
	}

	/*--------------------------------------------------------------
	 * Activation / Deactivation
	 *------------------------------------------------------------*/

	/**
	 * Activate a license key.
	 *
	 * Sends the key + domain to the remote server, stores the response
	 * locally, and schedules periodic revalidation.
	 *
	 * @since 1.2.0
	 *
	 * @param string $license_key The license key to activate.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function activate( $license_key ) {
		$license_key = sanitize_text_field( trim( $license_key ) );

		if ( empty( $license_key ) ) {
			return new WP_Error(
				'fwa_license_empty_key',
				__( 'Please enter a license key.', 'flexi-whatsapp-automation' )
			);
		}

		$response = $this->client->activate( $license_key );

		if ( is_wp_error( $response ) ) {
			$this->log( 'error', 'License activation failed: ' . $response->get_error_message() );
			return $response;
		}

		if ( empty( $response['success'] ) ) {
			$message = isset( $response['message'] ) ? $response['message'] : __( 'Activation failed.', 'flexi-whatsapp-automation' );
			$this->log( 'warning', 'License activation rejected: ' . $message );
			return new WP_Error( 'fwa_license_activation_failed', $message );
		}

		// Store license data.
		$data = array(
			'key'           => $license_key,
			'status'        => isset( $response['status'] ) ? sanitize_text_field( $response['status'] ) : 'active',
			'domain'        => $this->get_site_domain(),
			'expires_at'    => isset( $response['expires_at'] ) ? sanitize_text_field( $response['expires_at'] ) : '',
			'plan'          => isset( $response['plan'] ) ? sanitize_text_field( $response['plan'] ) : '',
			'activated_at'  => current_time( 'mysql' ),
			'last_check'    => time(),
			'check_hash'    => $this->compute_integrity_hash( $license_key, 'active' ),
		);

		$this->store_license_data( $data );
		$this->schedule_revalidation();

		$this->log( 'info', 'License activated successfully.' );

		/**
		 * Fires after a license has been successfully activated.
		 *
		 * @since 1.2.0
		 *
		 * @param array $data Stored license data.
		 */
		do_action( 'fwa_license_activated', $data );

		return true;
	}

	/**
	 * Deactivate the current license.
	 *
	 * Notifies the remote server and removes local license data.
	 *
	 * @since 1.2.0
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function deactivate() {
		$data = $this->get_license_data();

		if ( empty( $data['key'] ) ) {
			return new WP_Error(
				'fwa_license_not_active',
				__( 'No active license to deactivate.', 'flexi-whatsapp-automation' )
			);
		}

		$response = $this->client->deactivate( $data['key'] );

		// Even if the remote call fails, clear local data to allow re-activation.
		if ( is_wp_error( $response ) ) {
			$this->log( 'warning', 'Remote deactivation failed: ' . $response->get_error_message() . '. Clearing local data.' );
		}

		$this->clear_license_data();
		$this->unschedule_revalidation();

		$this->log( 'info', 'License deactivated.' );

		/**
		 * Fires after a license has been deactivated.
		 *
		 * @since 1.2.0
		 */
		do_action( 'fwa_license_deactivated' );

		return true;
	}

	/*--------------------------------------------------------------
	 * Revalidation
	 *------------------------------------------------------------*/

	/**
	 * Revalidate the license with the remote server.
	 *
	 * Called by WP-Cron at the configured interval.
	 *
	 * @since 1.2.0
	 */
	public function revalidate() {
		$data = $this->get_license_data();

		if ( empty( $data['key'] ) ) {
			return;
		}

		$response = $this->client->validate( $data['key'] );

		if ( is_wp_error( $response ) ) {
			// Network error — enter grace period instead of immediate invalidation.
			$data['last_check_error'] = $response->get_error_message();
			$data['last_check']       = time();
			$this->store_license_data( $data );
			$this->log( 'warning', 'License revalidation failed (network): ' . $response->get_error_message() );
			return;
		}

		if ( ! empty( $response['success'] ) && isset( $response['status'] ) ) {
			$data['status']           = sanitize_text_field( $response['status'] );
			$data['expires_at']       = isset( $response['expires_at'] ) ? sanitize_text_field( $response['expires_at'] ) : $data['expires_at'];
			$data['plan']             = isset( $response['plan'] ) ? sanitize_text_field( $response['plan'] ) : $data['plan'];
			$data['last_check']       = time();
			$data['last_check_error'] = '';
			$data['check_hash']       = $this->compute_integrity_hash( $data['key'], $data['status'] );

			$this->store_license_data( $data );

			$this->log( 'info', 'License revalidated: status=' . $data['status'] );

			/**
			 * Fires after license revalidation succeeds.
			 *
			 * @since 1.2.0
			 *
			 * @param array $data Updated license data.
			 */
			do_action( 'fwa_license_revalidated', $data );
		} else {
			// Server explicitly rejected the license.
			$message        = isset( $response['message'] ) ? $response['message'] : '';
			$data['status'] = 'invalid';
			$data['last_check']       = time();
			$data['last_check_error'] = $message;
			$data['check_hash']       = $this->compute_integrity_hash( $data['key'], 'invalid' );

			$this->store_license_data( $data );

			$this->log( 'error', 'License revalidation rejected: ' . $message );

			/**
			 * Fires when license revalidation is rejected by the server.
			 *
			 * @since 1.2.0
			 *
			 * @param array  $data    License data.
			 * @param string $message Server rejection message.
			 */
			do_action( 'fwa_license_invalidated', $data, $message );
		}
	}

	/*--------------------------------------------------------------
	 * Status checks
	 *------------------------------------------------------------*/

	/**
	 * Check whether the current license is valid and active.
	 *
	 * This is the primary check used throughout the plugin.
	 * It verifies:
	 *   1. License data exists
	 *   2. Status is 'active'
	 *   3. Domain matches
	 *   4. Not expired
	 *   5. Integrity hash is intact
	 *   6. Within grace period if last check failed
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if the license is valid.
	 */
	public function is_valid() {
		$data = $this->get_license_data();

		if ( empty( $data ) || empty( $data['key'] ) ) {
			return $this->apply_valid_filter( false );
		}

		// Domain check.
		if ( ! empty( $data['domain'] ) && $data['domain'] !== $this->get_site_domain() ) {
			return $this->apply_valid_filter( false );
		}

		// Integrity hash check (detect tampering).
		if ( ! empty( $data['check_hash'] ) ) {
			$expected_hash = $this->compute_integrity_hash( $data['key'], $data['status'] );
			if ( ! hash_equals( $expected_hash, $data['check_hash'] ) ) {
				return $this->apply_valid_filter( false );
			}
		}

		// Explicit invalid status.
		if ( isset( $data['status'] ) && 'active' !== $data['status'] ) {
			return $this->apply_valid_filter( false );
		}

		// Expiration check.
		if ( ! empty( $data['expires_at'] ) ) {
			$expires_timestamp = strtotime( $data['expires_at'] );
			if ( false !== $expires_timestamp && $expires_timestamp < time() ) {
				return $this->apply_valid_filter( false );
			}
		}

		// Grace period check: if last validation had an error but we are still
		// within the grace window, continue operating normally.
		if ( ! empty( $data['last_check_error'] ) && ! empty( $data['last_check'] ) ) {
			if ( ( time() - (int) $data['last_check'] ) > self::GRACE_PERIOD ) {
				return $this->apply_valid_filter( false );
			}
		}

		return $this->apply_valid_filter( true );
	}

	/**
	 * Get the current license status string.
	 *
	 * @since 1.2.0
	 *
	 * @return string Status: 'active', 'expired', 'invalid', 'inactive'.
	 */
	public function get_status() {
		$data = $this->get_license_data();

		if ( empty( $data ) || empty( $data['key'] ) ) {
			return 'inactive';
		}

		// Check expiration.
		if ( ! empty( $data['expires_at'] ) ) {
			$expires = strtotime( $data['expires_at'] );
			if ( false !== $expires && $expires < time() ) {
				return 'expired';
			}
		}

		/**
		 * Return the license status directly without filtering to prevent
		 * third-party code from spoofing the status.
		 *
		 * @since 1.2.1 Filter removed for security hardening.
		 */
		return isset( $data['status'] ) ? $data['status'] : 'inactive';
	}

	/**
	 * Get the stored license key (masked for display).
	 *
	 * @since 1.2.0
	 *
	 * @return string Masked license key.
	 */
	public function get_masked_key() {
		$data = $this->get_license_data();
		if ( empty( $data['key'] ) ) {
			return '';
		}
		return FWA_Helpers::mask_token( $data['key'] );
	}

	/**
	 * Get the full license data array.
	 *
	 * @since 1.2.0
	 *
	 * @return array License data.
	 */
	public function get_license_data() {
		if ( null !== $this->license_data ) {
			return $this->license_data;
		}

		$raw = get_option( self::OPTION_KEY, '' );

		if ( empty( $raw ) ) {
			$this->license_data = array();
			return $this->license_data;
		}

		// Decrypt stored data.
		$decrypted = $this->decrypt( $raw );
		if ( false === $decrypted ) {
			$this->license_data = array();
			return $this->license_data;
		}

		$data = json_decode( $decrypted, true );
		$this->license_data = is_array( $data ) ? $data : array();

		return $this->license_data;
	}

	/*--------------------------------------------------------------
	 * Storage (encrypted)
	 *------------------------------------------------------------*/

	/**
	 * Store license data (encrypted).
	 *
	 * @since 1.2.0
	 *
	 * @param array $data License data array.
	 */
	private function store_license_data( $data ) {
		$json      = wp_json_encode( $data );
		$encrypted = $this->encrypt( $json );

		update_option( self::OPTION_KEY, $encrypted, false );

		// Reset cache.
		$this->license_data = $data;
	}

	/**
	 * Clear all stored license data.
	 *
	 * @since 1.2.0
	 */
	private function clear_license_data() {
		delete_option( self::OPTION_KEY );
		$this->license_data = array();
	}

	/*--------------------------------------------------------------
	 * Encryption helpers
	 *------------------------------------------------------------*/

	/**
	 * Encrypt data using AES-256-CBC.
	 *
	 * Falls back to base64 encoding if OpenSSL is unavailable.
	 *
	 * @since 1.2.0
	 *
	 * @param string $data Plain text to encrypt.
	 * @return string Encrypted string.
	 */
	private function encrypt( $data ) {
		$key = $this->get_encryption_key();

		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv     = openssl_random_pseudo_bytes( 16 );
			$cipher = openssl_encrypt( $data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
			if ( false === $cipher ) {
				return base64_encode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}
			return base64_encode( $iv . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return base64_encode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt data encrypted with encrypt().
	 *
	 * @since 1.2.0
	 *
	 * @param string $data Encrypted string.
	 * @return string|false Decrypted string or false on failure.
	 */
	private function decrypt( $data ) {
		$key = $this->get_encryption_key();
		$raw = base64_decode( $data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $raw ) {
			return false;
		}

		if ( function_exists( 'openssl_decrypt' ) && strlen( $raw ) > 16 ) {
			$iv     = substr( $raw, 0, 16 );
			$cipher = substr( $raw, 16 );
			$result = openssl_decrypt( $cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
			if ( false !== $result ) {
				return $result;
			}
		}

		// Fallback: the data might have been stored without encryption.
		return $raw;
	}

	/**
	 * Derive the encryption key from WordPress salts.
	 *
	 * @since 1.2.0
	 *
	 * @return string 32-byte key.
	 */
	private function get_encryption_key() {
		return hash( 'sha256', 'fwa_license_' . wp_salt( 'secure_auth' ), true );
	}

	/*--------------------------------------------------------------
	 * Integrity hash
	 *------------------------------------------------------------*/

	/**
	 * Compute a tamper-detection hash for stored license data.
	 *
	 * If someone manually edits the option to change status to 'active',
	 * the hash will not match and is_valid() returns false.
	 *
	 * @since 1.2.0
	 *
	 * @param string $key    License key.
	 * @param string $status License status.
	 * @return string HMAC hash.
	 */
	private function compute_integrity_hash( $key, $status ) {
		// Use length-prefixed components to prevent collision from values
		// containing the '|' separator character.
		$payload = strlen( $key ) . ':' . $key . '|' . strlen( $status ) . ':' . $status . '|' . $this->get_site_domain();
		return hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) );
	}

	/*--------------------------------------------------------------
	 * Cron scheduling
	 *------------------------------------------------------------*/

	/**
	 * Schedule the license revalidation cron event.
	 *
	 * @since 1.2.0
	 */
	public function schedule_revalidation() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + self::REVALIDATION_INTERVAL, 'twicedaily', self::CRON_HOOK );
		}
	}

	/**
	 * Remove the license revalidation cron event.
	 *
	 * @since 1.2.0
	 */
	public function unschedule_revalidation() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/*--------------------------------------------------------------
	 * Helpers
	 *------------------------------------------------------------*/

	/**
	 * Get the normalized site domain.
	 *
	 * @since 1.2.0
	 *
	 * @return string Domain string without protocol or www.
	 */
	private function get_site_domain() {
		$url    = home_url();
		$parsed = wp_parse_url( $url );
		$domain = isset( $parsed['host'] ) ? $parsed['host'] : $url;
		$domain = preg_replace( '/^www\./i', '', $domain );
		return strtolower( $domain );
	}

	/**
	 * Apply internal license validity check.
	 *
	 * The filter has been removed to prevent third-party code from
	 * overriding the license status, which would be a security bypass.
	 *
	 * @since 1.2.0
	 * @since 1.2.1 Filter removed for security hardening.
	 *
	 * @param bool $valid Current validity.
	 * @return bool Validity (unfiltered).
	 */
	private function apply_valid_filter( $valid ) {
		return (bool) $valid;
	}

	/**
	 * Log a license-related event.
	 *
	 * @since 1.2.0
	 *
	 * @param string $level   Log level (info, warning, error).
	 * @param string $message Log message.
	 */
	private function log( $level, $message ) {
		if ( class_exists( 'FWA_Logger' ) ) {
			$logger = FWA_Logger::get_instance();
			if ( method_exists( $logger, $level ) ) {
				$logger->$level( $message, 'license' );
			} elseif ( method_exists( $logger, 'log' ) ) {
				$logger->log( $level, $message, 'license' );
			}
		}
	}
}
