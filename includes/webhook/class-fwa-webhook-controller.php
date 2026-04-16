<?php
/**
 * REST API webhook handler.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Webhook_Controller
 *
 * Registers REST API endpoints for receiving incoming webhook events
 * (messages, delivery statuses, session updates) and a verification
 * endpoint for initial webhook setup.
 *
 * @since 1.0.0
 */
class FWA_Webhook_Controller {

	/**
	 * REST namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const REST_NAMESPACE = 'fwa/v1';

	/**
	 * Constructor.
	 *
	 * Registers the REST routes on rest_api_init.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/*--------------------------------------------------------------
	 * Route registration
	 *------------------------------------------------------------*/

	/**
	 * Register all webhook REST routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		// Main webhook endpoint.
		register_rest_route( self::REST_NAMESPACE, '/webhook', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_webhook' ),
			'permission_callback' => '__return_true',
		) );

		// Incoming message.
		register_rest_route( self::REST_NAMESPACE, '/webhook/message', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_incoming_message' ),
			'permission_callback' => '__return_true',
		) );

		// Delivery status.
		register_rest_route( self::REST_NAMESPACE, '/webhook/status', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_status_update' ),
			'permission_callback' => '__return_true',
		) );

		// Session status.
		register_rest_route( self::REST_NAMESPACE, '/webhook/session', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_session_update' ),
			'permission_callback' => '__return_true',
		) );

		// Verification (GET).
		register_rest_route( self::REST_NAMESPACE, '/webhook/verify', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_verification' ),
			'permission_callback' => '__return_true',
		) );
	}

	/*--------------------------------------------------------------
	 * Handlers
	 *------------------------------------------------------------*/

	/**
	 * Main webhook entry point.
	 *
	 * Validates the secret, determines the event type, and routes to the
	 * appropriate handler.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		if ( ! $this->verify_webhook_secret( $request ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid webhook secret.', 'flexi-whatsapp-automation' ) ),
				401
			);
		}

		$body       = $request->get_json_params();
		$event_type = isset( $body['event'] ) ? sanitize_text_field( $body['event'] ) : 'unknown';

		/**
		 * Fires when a webhook event is received.
		 *
		 * @since 1.0.0
		 *
		 * @param string $event_type Event type identifier.
		 * @param array  $body       Full request body.
		 */
		do_action( 'fwa_webhook_received', $event_type, $body );

		// Route by event type.
		switch ( $event_type ) {
			case 'message':
			case 'incoming_message':
				return $this->handle_incoming_message( $request );

			case 'status':
			case 'message_status':
				return $this->handle_status_update( $request );

			case 'session':
			case 'session_status':
				return $this->handle_session_update( $request );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'event'   => $event_type,
		), 200 );
	}

	/**
	 * Handle an incoming message webhook.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_incoming_message( WP_REST_Request $request ) {
		if ( ! $this->verify_webhook_secret( $request ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid webhook secret.', 'flexi-whatsapp-automation' ) ),
				401
			);
		}

		global $wpdb;

		$body = $request->get_json_params();

		$from         = isset( $body['from'] ) ? sanitize_text_field( $body['from'] ) : '';
		$content      = isset( $body['content'] ) ? sanitize_textarea_field( $body['content'] ) : '';
		$message_type = isset( $body['message_type'] ) ? sanitize_text_field( $body['message_type'] ) : 'text';
		$media_url    = isset( $body['media_url'] ) ? esc_url_raw( $body['media_url'] ) : '';
		$instance_id  = isset( $body['instance_id'] ) ? absint( $body['instance_id'] ) : 0;
		$message_id   = isset( $body['message_id'] ) ? sanitize_text_field( $body['message_id'] ) : '';

		if ( empty( $from ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Sender phone number is required.', 'flexi-whatsapp-automation' ) ),
				400
			);
		}

		$table = FWA_Helpers::get_messages_table();

		$wpdb->insert( $table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'instance_id'  => $instance_id,
			'direction'    => 'incoming',
			'message_type' => $message_type,
			'sender'       => $from,
			'recipient'    => '',
			'content'      => $content,
			'media_url'    => $media_url,
			'message_id'   => $message_id,
			'status'       => 'delivered',
			'created_at'   => current_time( 'mysql' ),
		) );

		$db_id = (int) $wpdb->insert_id;

		$message_data = array(
			'id'           => $db_id,
			'from'         => $from,
			'content'      => $content,
			'message_type' => $message_type,
			'media_url'    => $media_url,
			'instance_id'  => $instance_id,
			'message_id'   => $message_id,
		);

		/**
		 * Fires after an incoming message is recorded.
		 *
		 * @since 1.0.0
		 *
		 * @param array $message_data Normalised message data.
		 */
		do_action( 'fwa_message_received', $message_data );

		if ( 'text' === $message_type ) {
			/**
			 * Fires for incoming text messages.
			 *
			 * @since 1.0.0
			 *
			 * @param string $from        Sender phone number.
			 * @param string $content     Message text.
			 * @param int    $instance_id Instance DB ID.
			 */
			do_action( 'fwa_incoming_text', $from, $content, $instance_id );
		} else {
			$media_data = array(
				'type' => $message_type,
				'url'  => $media_url,
			);

			/**
			 * Fires for incoming media messages.
			 *
			 * @since 1.0.0
			 *
			 * @param string $from        Sender phone number.
			 * @param array  $media_data  Media metadata.
			 * @param int    $instance_id Instance DB ID.
			 */
			do_action( 'fwa_incoming_media', $from, $media_data, $instance_id );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'id'      => $db_id,
		), 200 );
	}

	/**
	 * Handle a message delivery status update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_status_update( WP_REST_Request $request ) {
		if ( ! $this->verify_webhook_secret( $request ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid webhook secret.', 'flexi-whatsapp-automation' ) ),
				401
			);
		}

		global $wpdb;

		$body       = $request->get_json_params();
		$message_id = isset( $body['message_id'] ) ? sanitize_text_field( $body['message_id'] ) : '';
		$status     = isset( $body['status'] ) ? sanitize_text_field( $body['status'] ) : '';

		if ( empty( $message_id ) || empty( $status ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Message ID and status are required.', 'flexi-whatsapp-automation' ) ),
				400
			);
		}

		$allowed_statuses = array( 'sent', 'delivered', 'read', 'failed' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid status value.', 'flexi-whatsapp-automation' ) ),
				400
			);
		}

		$table = FWA_Helpers::get_messages_table();

		$update_data = array( 'status' => $status );

		if ( 'delivered' === $status ) {
			$update_data['delivered_at'] = current_time( 'mysql' );
		} elseif ( 'read' === $status ) {
			$update_data['read_at'] = current_time( 'mysql' );
		} elseif ( 'failed' === $status && isset( $body['error_message'] ) ) {
			$update_data['error_message'] = sanitize_text_field( $body['error_message'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $table, $update_data, array( 'message_id' => $message_id ) );

		// Also update campaign_logs if applicable.
		$logs_table = FWA_Helpers::get_campaign_logs_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$logs_table} WHERE message_id = %s LIMIT 1",
			$message_id
		) );

		if ( $log ) {
			$log_update = array( 'status' => $status );
			if ( 'failed' === $status && isset( $body['error_message'] ) ) {
				$log_update['error_message'] = sanitize_text_field( $body['error_message'] );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $logs_table, $log_update, array( 'id' => $log->id ) );
		}

		/**
		 * Fires after a message status is updated via webhook.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message_id External message ID.
		 * @param string $status     New status.
		 */
		do_action( 'fwa_message_status_webhook', $message_id, $status );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Handle a session/instance status update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_session_update( WP_REST_Request $request ) {
		if ( ! $this->verify_webhook_secret( $request ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid webhook secret.', 'flexi-whatsapp-automation' ) ),
				401
			);
		}

		$body        = $request->get_json_params();
		$instance_id = isset( $body['instance_id'] ) ? sanitize_text_field( $body['instance_id'] ) : '';
		$status      = isset( $body['status'] ) ? sanitize_text_field( $body['status'] ) : '';

		if ( empty( $instance_id ) || empty( $status ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Instance ID and status are required.', 'flexi-whatsapp-automation' ) ),
				400
			);
		}

		$allowed_statuses = array( 'connected', 'disconnected', 'qr_required' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid session status.', 'flexi-whatsapp-automation' ) ),
				400
			);
		}

		// Update instance via the manager.
		$manager = new FWA_Instance_Manager();
		$instance = $manager->get_by_instance_id( $instance_id );

		if ( $instance ) {
			$manager->update_status( $instance->id, $status );
		}

		/**
		 * Fires after a session status is updated via webhook.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance_id External instance ID.
		 * @param string $status      New session status.
		 */
		do_action( 'fwa_session_status_webhook', $instance_id, $status );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Handle webhook verification (GET request).
	 *
	 * Returns the challenge parameter for initial webhook setup.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_verification( WP_REST_Request $request ) {
		$challenge = $request->get_param( 'challenge' );

		if ( empty( $challenge ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Challenge parameter is required.', 'flexi-whatsapp-automation' ) ),
				400
			);
		}

		return new WP_REST_Response( array(
			'challenge' => sanitize_text_field( $challenge ),
		), 200 );
	}

	/*--------------------------------------------------------------
	 * Security
	 *------------------------------------------------------------*/

	/**
	 * Verify the webhook secret header.
	 *
	 * Two verification modes are supported, controlled by the
	 * fwa_webhook_signature_mode option:
	 *
	 *  - 'secret' (default): Compare raw X-Webhook-Secret header value.
	 *  - 'hmac':             Verify X-Hub-Signature-256 (HMAC-SHA256 of raw
	 *                        body, prefixed with "sha256=") as used by
	 *                        WhatsApp Cloud API, GitHub, etc.
	 *
	 * If no secret is configured, the request is allowed through.
	 *
	 * @since 1.0.0 (HMAC mode added 1.2.0)
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return bool
	 */
	public function verify_webhook_secret( WP_REST_Request $request ) {
		$stored_secret = get_option( 'fwa_webhook_secret', '' );

		// No secret configured — allow but warn.
		if ( empty( $stored_secret ) ) {
			return true;
		}

		$mode = get_option( 'fwa_webhook_signature_mode', 'secret' );

		if ( 'hmac' === $mode ) {
			// HMAC-SHA256 verification (WhatsApp Cloud API / GitHub style).
			$signature_header = $request->get_header( 'X-Hub-Signature-256' );

			if ( empty( $signature_header ) ) {
				return false;
			}

			// Raw body needed for HMAC.
			$raw_body = $request->get_body();

			$expected = 'sha256=' . hash_hmac( 'sha256', $raw_body, $stored_secret );

			return hash_equals( $expected, $signature_header );
		}

		// Default: plain secret header comparison.
		$header_secret = $request->get_header( 'X-Webhook-Secret' );

		return hash_equals( $stored_secret, (string) $header_secret );
	}

	/*--------------------------------------------------------------
	 * Utilities
	 *------------------------------------------------------------*/

	/**
	 * Get the full webhook URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Absolute URL of the main webhook endpoint.
	 */
	public static function get_webhook_url() {
		return rest_url( self::REST_NAMESPACE . '/webhook' );
	}
}
