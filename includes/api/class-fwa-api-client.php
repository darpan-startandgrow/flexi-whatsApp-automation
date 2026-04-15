<?php
/**
 * Centralized HTTP client for the external WhatsApp API.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_API_Client
 *
 * Reusable API client for communicating with the external WhatsApp service.
 * Handles authentication, rate limiting, retry logic, and logging.
 *
 * @since 1.0.0
 */
class FWA_API_Client {

	/**
	 * Base URL for the WhatsApp API.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $base_url;

	/**
	 * External instance identifier.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $instance_id;

	/**
	 * Bearer access token.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $access_token;

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var FWA_Logger|null
	 */
	private $logger;

	/**
	 * Maximum number of retries on server errors or timeouts.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id  Optional. External instance ID.
	 * @param string $access_token Optional. Bearer token for authentication.
	 */
	public function __construct( $instance_id = '', $access_token = '' ) {
		$this->base_url     = untrailingslashit( get_option( 'fwa_api_base_url', '' ) );
		$this->instance_id  = $instance_id;
		$this->access_token = $access_token;

		if ( class_exists( 'FWA_Logger' ) ) {
			$this->logger = new FWA_Logger();
		}
	}

	/*--------------------------------------------------------------
	 * Core request
	 *------------------------------------------------------------*/

	/**
	 * Send an HTTP request to the WhatsApp API.
	 *
	 * Implements rate limiting, retry with exponential back-off, and
	 * fires hooks for extensibility.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Relative API endpoint (e.g. /message/text).
	 * @param string $method   HTTP method. Default 'POST'.
	 * @param array  $body     Request body data.
	 * @param array  $headers  Additional headers.
	 * @return array|WP_Error  Decoded response body or WP_Error on failure.
	 */
	public function request( $endpoint, $method = 'POST', $body = array(), $headers = array() ) {

		/**
		 * Fires before an API request is dispatched.
		 *
		 * @since 1.0.0
		 *
		 * @param string $endpoint API endpoint.
		 * @param string $method   HTTP method.
		 * @param array  $body     Request body.
		 */
		do_action( 'fwa_before_api_request', $endpoint, $method, $body );

		// --- Rate limiting ---------------------------------------------------
		$rate_error = $this->check_rate_limit();
		if ( is_wp_error( $rate_error ) ) {
			do_action( 'fwa_api_error', $endpoint, $rate_error );
			return $rate_error;
		}

		// --- Build request ---------------------------------------------------
		$url = $this->base_url . '/' . ltrim( $endpoint, '/' );

		$default_headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->access_token,
		);

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => 30,
			'headers' => array_merge( $default_headers, $headers ),
		);

		if ( ! empty( $body ) && in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		/**
		 * Filter the request arguments before sending.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args     wp_remote_request arguments.
		 * @param string $endpoint API endpoint.
		 * @param string $method   HTTP method.
		 * @param array  $body     Original body data.
		 */
		$args = apply_filters( 'fwa_api_request_args', $args, $endpoint, $method, $body );

		// --- Execute with retry logic ----------------------------------------
		$attempt  = 0;
		$response = null;

		while ( $attempt < $this->max_retries ) {
			$attempt++;

			$this->log( 'debug', sprintf( 'API request attempt %d: %s %s', $attempt, $args['method'], $url ) );

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				$this->log( 'error', sprintf( 'API request error: %s', $response->get_error_message() ) );

				if ( $attempt < $this->max_retries ) {
					$this->backoff( $attempt );
					continue;
				}

				do_action( 'fwa_api_error', $endpoint, $response );
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );

			// Retry on 5xx server errors.
			if ( $code >= 500 && $attempt < $this->max_retries ) {
				$this->log( 'warning', sprintf( 'API returned %d, retrying…', $code ) );
				$this->backoff( $attempt );
				continue;
			}

			// Successful or non-retryable response — break out.
			break;
		}

		// --- Process response ------------------------------------------------
		$response_body = wp_remote_retrieve_body( $response );
		$decoded       = json_decode( $response_body, true );

		if ( null === $decoded && '' !== $response_body ) {
			$decoded = $response_body;
		}

		$code = wp_remote_retrieve_response_code( $response );

		$this->log( 'debug', sprintf( 'API response %d for %s %s', $code, $args['method'], $url ) );

		if ( $code >= 400 ) {
			$message = is_array( $decoded ) && isset( $decoded['message'] )
				? $decoded['message']
				: sprintf( 'API request failed with status %d', $code );

			$error = new WP_Error( 'fwa_api_error', $message, array(
				'status'   => $code,
				'response' => $decoded,
			) );

			do_action( 'fwa_api_error', $endpoint, $error );

			return $error;
		}

		/**
		 * Filter the decoded API response.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $decoded  Decoded response body.
		 * @param string $endpoint API endpoint.
		 * @param array  $body     Original request body.
		 */
		$decoded = apply_filters( 'fwa_api_response', $decoded, $endpoint, $body );

		/**
		 * Fires after a successful API request.
		 *
		 * @since 1.0.0
		 *
		 * @param string $endpoint API endpoint.
		 * @param mixed  $decoded  Decoded response body.
		 * @param array  $body     Original request body.
		 */
		do_action( 'fwa_after_api_request', $endpoint, $decoded, $body );

		return $decoded;
	}

	/*--------------------------------------------------------------
	 * Instance management
	 *------------------------------------------------------------*/

	/**
	 * Create a new WhatsApp instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name   Instance name.
	 * @param array  $params Additional parameters.
	 * @return array|WP_Error Instance data or error.
	 */
	public function createInstance( $name, $params = array() ) {
		$body = array_merge( array( 'name' => $name ), $params );
		return $this->request( 'instance/create', 'POST', $body );
	}

	/**
	 * Get QR code for an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Optional. Falls back to $this->instance_id.
	 * @return array|WP_Error QR code data (base64 or URL) or error.
	 */
	public function getQRCode( $instance_id = '' ) {
		$instance_id = $this->resolve_instance_id( $instance_id );
		return $this->request( "instance/{$instance_id}/qr", 'GET' );
	}

	/**
	 * Get a pairing code for the instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Optional. Falls back to $this->instance_id.
	 * @param string $phone       Phone number for pairing.
	 * @return string|WP_Error Pairing code or error.
	 */
	public function getPairingCode( $instance_id = '', $phone = '' ) {
		$instance_id = $this->resolve_instance_id( $instance_id );
		$phone       = sanitize_text_field( $phone );
		return $this->request( "instance/{$instance_id}/pairing?phone={$phone}", 'GET' );
	}

	/**
	 * Get the connection status of an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Optional.
	 * @return array|WP_Error Status object or error.
	 */
	public function getInstanceStatus( $instance_id = '' ) {
		$instance_id = $this->resolve_instance_id( $instance_id );
		return $this->request( "instance/{$instance_id}/status", 'GET' );
	}

	/**
	 * Reconnect a disconnected instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Optional.
	 * @return array|WP_Error
	 */
	public function reconnectInstance( $instance_id = '' ) {
		$instance_id = $this->resolve_instance_id( $instance_id );
		return $this->request( "instance/{$instance_id}/reconnect", 'POST' );
	}

	/**
	 * Delete an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Optional.
	 * @return array|WP_Error
	 */
	public function deleteInstance( $instance_id = '' ) {
		$instance_id = $this->resolve_instance_id( $instance_id );
		return $this->request( "instance/{$instance_id}", 'DELETE' );
	}

	/**
	 * Logout an instance (clears session on the service).
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Optional.
	 * @return array|WP_Error
	 */
	public function logoutInstance( $instance_id = '' ) {
		$instance_id = $this->resolve_instance_id( $instance_id );
		return $this->request( "instance/{$instance_id}/logout", 'POST' );
	}

	/*--------------------------------------------------------------
	 * Messaging
	 *------------------------------------------------------------*/

	/**
	 * Send a text message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to      Recipient phone number.
	 * @param string $message Message text.
	 * @return array|WP_Error Response containing message ID or error.
	 */
	public function sendText( $to, $message ) {
		return $this->request( 'message/text', 'POST', array(
			'to'   => $to,
			'body' => array( 'text' => $message ),
		) );
	}

	/**
	 * Send a media message (image, video, audio, document).
	 *
	 * @since 1.0.0
	 *
	 * @param string $to       Recipient phone number.
	 * @param string $type     Media type: image|video|audio|document.
	 * @param string $url      Public URL of the media file.
	 * @param string $caption  Optional caption.
	 * @param string $filename Optional filename for documents.
	 * @return array|WP_Error
	 */
	public function sendMedia( $to, $type, $url, $caption = '', $filename = '' ) {
		$body = array(
			'to'   => $to,
			'type' => $type,
			'url'  => $url,
		);

		if ( '' !== $caption ) {
			$body['caption'] = $caption;
		}
		if ( '' !== $filename ) {
			$body['filename'] = $filename;
		}

		return $this->request( 'message/media', 'POST', $body );
	}

	/**
	 * Send a contact card.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to           Recipient phone number.
	 * @param array  $contact_data vCard-formatted contact data.
	 * @return array|WP_Error
	 */
	public function sendContact( $to, $contact_data ) {
		return $this->request( 'message/contact', 'POST', array(
			'to'      => $to,
			'contact' => $contact_data,
		) );
	}

	/**
	 * Send a poll message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to           Recipient phone number.
	 * @param string $question     Poll question.
	 * @param array  $options      Poll answer options.
	 * @param bool   $multi_select Whether multiple options can be selected.
	 * @return array|WP_Error
	 */
	public function sendPoll( $to, $question, $options, $multi_select = false ) {
		return $this->request( 'message/poll', 'POST', array(
			'to'   => $to,
			'poll' => array(
				'name'            => $question,
				'values'          => $options,
				'selectableCount' => $multi_select ? 0 : 1,
			),
		) );
	}

	/**
	 * Send a link preview message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to   Recipient phone number.
	 * @param string $url  URL to preview.
	 * @param string $text Optional accompanying text.
	 * @return array|WP_Error
	 */
	public function sendLinkPreview( $to, $url, $text = '' ) {
		return $this->request( 'message/link-preview', 'POST', array(
			'to'   => $to,
			'url'  => $url,
			'text' => $text,
		) );
	}

	/**
	 * Mark messages as read.
	 *
	 * @since 1.0.0
	 *
	 * @param array $message_ids Array of message IDs to mark as read.
	 * @return array|WP_Error
	 */
	public function markAsRead( $message_ids ) {
		return $this->request( 'message/mark-read', 'POST', array(
			'messageIds' => (array) $message_ids,
		) );
	}

	/**
	 * Set the instance's presence status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Presence status. Default 'available'.
	 * @return array|WP_Error
	 */
	public function setPresence( $status = 'available' ) {
		return $this->request( 'instance/presence', 'POST', array(
			'status' => $status,
		) );
	}

	/*--------------------------------------------------------------
	 * Internals
	 *------------------------------------------------------------*/

	/**
	 * Resolve the instance ID, falling back to the property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id Passed instance ID.
	 * @return string
	 */
	private function resolve_instance_id( $instance_id ) {
		return ! empty( $instance_id ) ? $instance_id : $this->instance_id;
	}

	/**
	 * Check and enforce the per-minute rate limit.
	 *
	 * @since 1.0.0
	 *
	 * @return true|WP_Error True when within limits, WP_Error when exceeded.
	 */
	private function check_rate_limit() {
		$limit         = (int) get_option( 'fwa_rate_limit', 20 );
		$transient_key = 'fwa_rate_limit_' . $this->instance_id;
		$current       = (int) get_transient( $transient_key );

		if ( $current >= $limit ) {
			return new WP_Error(
				'fwa_rate_limit_exceeded',
				__( 'API rate limit exceeded. Please wait before making more requests.', 'flexi-whatsapp-automation' )
			);
		}

		if ( 0 === $current ) {
			set_transient( $transient_key, 1, MINUTE_IN_SECONDS );
		} else {
			set_transient( $transient_key, $current + 1, MINUTE_IN_SECONDS );
		}

		return true;
	}

	/**
	 * Sleep with exponential back-off (1 s, 2 s, 4 s …).
	 *
	 * @since 1.0.0
	 *
	 * @param int $attempt Current attempt number (1-based).
	 */
	private function backoff( $attempt ) {
		$seconds = pow( 2, $attempt - 1 );
		sleep( $seconds );
	}

	/**
	 * Log a message via FWA_Logger when available.
	 *
	 * @since 1.0.0
	 *
	 * @param string $level   Log level (debug, info, warning, error, critical).
	 * @param string $message Log message.
	 */
	private function log( $level, $message ) {
		if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
			$this->logger->log( $level, $message, 'api-client' );
		}
	}
}
