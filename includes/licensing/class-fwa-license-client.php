<?php
/**
 * License server HTTP client.
 *
 * Handles all communication with the remote license validation server,
 * including HMAC-signed request authentication and response parsing.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_License_Client
 *
 * Low-level HTTP transport for license operations.
 * Every request includes: license key, domain, timestamp, and HMAC signature.
 *
 * @since 1.2.0
 */
class FWA_License_Client {

	/**
	 * Remote license server base URL.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $server_url;

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		/**
		 * Filters the license server URL.
		 *
		 * @since 1.2.0
		 *
		 * @param string $url Default license server URL.
		 */
		$this->server_url = apply_filters(
			'fwa_license_server_url',
			untrailingslashit( get_option( 'fwa_license_server_url', 'https://license.flexirevivecart.com' ) )
		);
	}

	/*--------------------------------------------------------------
	 * Public API
	 *------------------------------------------------------------*/

	/**
	 * Activate a license key on the current domain.
	 *
	 * @since 1.2.0
	 *
	 * @param string $license_key The license key to activate.
	 * @return array|WP_Error Decoded server response or WP_Error.
	 */
	public function activate( $license_key ) {
		return $this->request( '/license/activate', $license_key, array(
			'product' => 'flexi-whatsapp-automation',
			'version' => FWA_VERSION,
		) );
	}

	/**
	 * Deactivate a license key on the current domain.
	 *
	 * @since 1.2.0
	 *
	 * @param string $license_key The license key to deactivate.
	 * @return array|WP_Error Decoded server response or WP_Error.
	 */
	public function deactivate( $license_key ) {
		return $this->request( '/license/deactivate', $license_key );
	}

	/**
	 * Validate / revalidate a license key.
	 *
	 * Used for periodic cron-based revalidation.
	 *
	 * @since 1.2.0
	 *
	 * @param string $license_key The license key to validate.
	 * @return array|WP_Error Decoded server response or WP_Error.
	 */
	public function validate( $license_key ) {
		return $this->request( '/license/validate', $license_key, array(
			'product' => 'flexi-whatsapp-automation',
			'version' => FWA_VERSION,
		) );
	}

	/*--------------------------------------------------------------
	 * Signature generation
	 *------------------------------------------------------------*/

	/**
	 * Generate HMAC-SHA256 signature for a request payload.
	 *
	 * @since 1.2.0
	 *
	 * @param array  $payload     The request data to sign.
	 * @param string $license_key Used as part of the signing key.
	 * @return string Hex-encoded HMAC signature.
	 */
	private function generate_signature( $payload, $license_key ) {
		// Build deterministic string from sorted payload keys.
		ksort( $payload );

		$data_string = '';
		foreach ( $payload as $key => $value ) {
			$data_string .= $key . '=' . $value . '&';
		}
		$data_string = rtrim( $data_string, '&' );

		// Signing key: combination of license key and a site-specific salt.
		$signing_key = hash( 'sha256', $license_key . wp_salt( 'auth' ), true );

		return hash_hmac( 'sha256', $data_string, $signing_key );
	}

	/*--------------------------------------------------------------
	 * HTTP transport
	 *------------------------------------------------------------*/

	/**
	 * Send a signed request to the license server.
	 *
	 * @since 1.2.0
	 *
	 * @param string $endpoint    Relative API endpoint.
	 * @param string $license_key License key.
	 * @param array  $extra       Additional body parameters.
	 * @return array|WP_Error Decoded JSON response or WP_Error.
	 */
	private function request( $endpoint, $license_key, $extra = array() ) {
		if ( empty( $this->server_url ) ) {
			return new WP_Error(
				'fwa_license_no_server',
				__( 'License server URL is not configured.', 'flexi-whatsapp-automation' )
			);
		}

		$domain    = $this->get_site_domain();
		$timestamp = (string) time();

		// Core payload fields (always present).
		$payload = array_merge(
			array(
				'license_key' => $license_key,
				'domain'      => $domain,
				'timestamp'   => $timestamp,
			),
			$extra
		);

		// Generate HMAC signature.
		$payload['signature'] = $this->generate_signature( $payload, $license_key );

		$url = $this->server_url . $endpoint;

		$response = wp_remote_post(
			$url,
			array(
				'timeout'   => 30,
				'sslverify' => true,
				'headers'   => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
					'X-FWA-Domain' => $domain,
				),
				'body'      => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'fwa_license_request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'License server request failed: %s', 'flexi-whatsapp-automation' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown server error.', 'flexi-whatsapp-automation' );
			return new WP_Error(
				'fwa_license_server_error',
				$message,
				array( 'status' => $code )
			);
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'fwa_license_invalid_response',
				__( 'Invalid response from license server.', 'flexi-whatsapp-automation' )
			);
		}

		return $data;
	}

	/*--------------------------------------------------------------
	 * Helpers
	 *------------------------------------------------------------*/

	/**
	 * Get the site domain for license binding.
	 *
	 * Strips protocol and trailing slashes.
	 *
	 * @since 1.2.0
	 *
	 * @return string Normalized domain string.
	 */
	private function get_site_domain() {
		$url    = home_url();
		$parsed = wp_parse_url( $url );
		$domain = isset( $parsed['host'] ) ? $parsed['host'] : $url;

		// Remove www prefix for consistent domain matching.
		$domain = preg_replace( '/^www\./i', '', $domain );

		return strtolower( $domain );
	}
}
