<?php
/**
 * Central message dispatch system.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Message_Sender
 *
 * Dispatches messages through the WhatsApp API, records every attempt in the
 * database, and exposes convenience methods for each message type.
 *
 * @since 1.0.0
 */
class FWA_Message_Sender {

	/**
	 * API client instance.
	 *
	 * @since 1.0.0
	 * @var FWA_API_Client|null
	 */
	private $api_client;

	/**
	 * Instance manager.
	 *
	 * @since 1.0.0
	 * @var FWA_Instance_Manager|null
	 */
	private $instance_manager;

	/**
	 * Constructor.
	 *
	 * This is a service class called by other modules — no hooks are registered.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->instance_manager = new FWA_Instance_Manager();
	}

	/*--------------------------------------------------------------
	 * Primary dispatch
	 *------------------------------------------------------------*/

	/**
	 * Send a message through the WhatsApp API.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Message parameters.
	 *
	 *     @type string $to            Required. Recipient phone number.
	 *     @type string $type          Required. text|image|video|audio|document|contact|poll|link_preview.
	 *     @type string $content       Optional. Message body (text types).
	 *     @type string $media_url     Optional. Public URL for media messages.
	 *     @type string $media_caption Optional. Caption for media messages.
	 *     @type int    $instance_id   Optional. Instance DB ID. Falls back to active instance.
	 *     @type int    $campaign_id   Optional. Associated campaign ID.
	 *     @type int    $schedule_id   Optional. Associated schedule ID.
	 * }
	 * @return int|WP_Error Message DB ID on success, WP_Error on failure.
	 */
	public function send( $args ) {
		// License guard — block message sending without a valid license (fail-closed).
		$license_check = FWA_License_Guard::can_send_message();
		if ( is_wp_error( $license_check ) ) {
			return $license_check;
		}

		$defaults = array(
			'to'            => '',
			'type'          => 'text',
			'content'       => '',
			'media_url'     => '',
			'media_caption' => '',
			'instance_id'   => null,
			'campaign_id'   => null,
			'schedule_id'   => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields.
		if ( empty( $args['to'] ) ) {
			return new WP_Error( 'fwa_missing_recipient', __( 'Recipient phone number is required.', 'flexi-whatsapp-automation' ) );
		}

		// Auto-apply default country code for numbers without one.
		$default_cc = get_option( 'fwa_default_country_code', '' );
		$args['to'] = FWA_Helpers::format_phone_e164( $args['to'], ltrim( $default_cc, '+' ) );

		$allowed_types = array( 'text', 'image', 'video', 'audio', 'document', 'contact', 'poll', 'link_preview' );
		if ( ! in_array( $args['type'], $allowed_types, true ) ) {
			return new WP_Error( 'fwa_invalid_type', __( 'Invalid message type.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Filter message arguments before sending.
		 *
		 * Return false to cancel the send entirely.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Message arguments.
		 */
		$args = apply_filters( 'fwa_before_send_message', $args );

		if ( false === $args ) {
			return new WP_Error( 'fwa_send_cancelled', __( 'Message send was cancelled by a filter.', 'flexi-whatsapp-automation' ) );
		}

		// Resolve instance.
		$instance = $this->resolve_instance( $args['instance_id'] );
		if ( is_wp_error( $instance ) ) {
			return $instance;
		}

		$client = $this->create_api_client( $instance );

		// Dispatch to the appropriate sender.
		$response = $this->dispatch( $client, $args );

		// Record the message.
		$status     = is_wp_error( $response ) ? 'failed' : 'sent';
		$message_id = $this->record_message( $args, $instance, $status, $response );

		if ( is_wp_error( $message_id ) ) {
			return $message_id;
		}

		if ( 'failed' === $status ) {
			$error_message = $response->get_error_message();

			/**
			 * Fires when a message fails to send.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $message_id DB message ID.
			 * @param array  $args       Original arguments.
			 * @param string $error      Error message.
			 */
			do_action( 'fwa_message_failed', $message_id, $args, $error_message );

			return new WP_Error( 'fwa_send_failed', $error_message, array( 'message_id' => $message_id ) );
		}

		/**
		 * Fires after a message is sent successfully.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $message_id DB message ID.
		 * @param array $args       Original arguments.
		 */
		do_action( 'fwa_message_sent', $message_id, $args );

		return $message_id;
	}

	/*--------------------------------------------------------------
	 * Shorthand helpers
	 *------------------------------------------------------------*/

	/**
	 * Send a text message.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $to          Recipient phone number.
	 * @param string   $text        Message body.
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return int|WP_Error
	 */
	public function send_text( $to, $text, $instance_id = null ) {
		return $this->send( array(
			'to'          => $to,
			'type'        => 'text',
			'content'     => $text,
			'instance_id' => $instance_id,
		) );
	}

	/**
	 * Send a media message (image, video, audio, document).
	 *
	 * @since 1.0.0
	 *
	 * @param string   $to          Recipient phone number.
	 * @param string   $type        Media type: image|video|audio|document.
	 * @param string   $url         Public URL of the media file.
	 * @param string   $caption     Optional caption.
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return int|WP_Error
	 */
	public function send_media( $to, $type, $url, $caption = '', $instance_id = null ) {
		return $this->send( array(
			'to'            => $to,
			'type'          => $type,
			'media_url'     => $url,
			'media_caption' => $caption,
			'instance_id'   => $instance_id,
		) );
	}

	/**
	 * Send a poll message.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $to           Recipient phone number.
	 * @param string   $question     Poll question.
	 * @param array    $options      Answer options.
	 * @param bool     $multi_select Whether multiple selections are allowed.
	 * @param int|null $instance_id  Optional instance DB ID.
	 * @return int|WP_Error
	 */
	public function send_poll( $to, $question, $options, $multi_select = false, $instance_id = null ) {
		return $this->send( array(
			'to'          => $to,
			'type'        => 'poll',
			'content'     => wp_json_encode( array(
				'question'     => $question,
				'options'      => $options,
				'multi_select' => $multi_select,
			) ),
			'instance_id' => $instance_id,
		) );
	}

	/**
	 * Send a contact card.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $to           Recipient phone number.
	 * @param array    $contact_data Contact vCard data.
	 * @param int|null $instance_id  Optional instance DB ID.
	 * @return int|WP_Error
	 */
	public function send_contact( $to, $contact_data, $instance_id = null ) {
		return $this->send( array(
			'to'          => $to,
			'type'        => 'contact',
			'content'     => wp_json_encode( $contact_data ),
			'instance_id' => $instance_id,
		) );
	}

	/**
	 * Send a link preview message.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $to          Recipient phone number.
	 * @param string   $url         URL to preview.
	 * @param string   $text        Optional accompanying text.
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return int|WP_Error
	 */
	public function send_link_preview( $to, $url, $text = '', $instance_id = null ) {
		return $this->send( array(
			'to'          => $to,
			'type'        => 'link_preview',
			'content'     => $text,
			'media_url'   => $url,
			'instance_id' => $instance_id,
		) );
	}

	/*--------------------------------------------------------------
	 * Read receipts
	 *------------------------------------------------------------*/

	/**
	 * Mark messages as read via the API.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $message_ids Array of external message IDs.
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return array|WP_Error API response or error.
	 */
	public function mark_as_read( $message_ids, $instance_id = null ) {
		$instance = $this->resolve_instance( $instance_id );
		if ( is_wp_error( $instance ) ) {
			return $instance;
		}

		$client = $this->create_api_client( $instance );

		return $client->markAsRead( (array) $message_ids );
	}

	/*--------------------------------------------------------------
	 * Status management
	 *------------------------------------------------------------*/

	/**
	 * Update a message record with a new status.
	 *
	 * Sets the appropriate timestamp column (sent_at, delivered_at, read_at)
	 * based on the new status value.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $message_id Message DB ID.
	 * @param string $status     New status value.
	 * @param array  $extra      Optional extra columns to update.
	 * @return true|WP_Error
	 */
	public function update_message_status( $message_id, $status, $extra = array() ) {
		global $wpdb;

		$message_id = absint( $message_id );
		if ( ! $message_id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid message ID.', 'flexi-whatsapp-automation' ) );
		}

		$table = FWA_Helpers::get_messages_table();
		$now   = current_time( 'mysql' );

		$data = array_merge( $extra, array(
			'status' => sanitize_text_field( $status ),
		) );

		switch ( $status ) {
			case 'sent':
				$data['sent_at'] = $now;
				break;
			case 'delivered':
				$data['delivered_at'] = $now;
				break;
			case 'read':
				$data['read_at'] = $now;
				break;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update( $table, $data, array( 'id' => $message_id ) );

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_update_failed', __( 'Failed to update message status.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a message status is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $message_id Message DB ID.
		 * @param string $status     New status.
		 */
		do_action( 'fwa_message_status_updated', $message_id, $status );

		return true;
	}

	/*--------------------------------------------------------------
	 * Retrieval
	 *------------------------------------------------------------*/

	/**
	 * Get a single message record.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Message DB ID.
	 * @return object|null Database row or null.
	 */
	public function get_message( $id ) {
		global $wpdb;

		$table = FWA_Helpers::get_messages_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) )
		);
	}

	/**
	 * Retrieve messages with filtering and pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $instance_id Filter by instance.
	 *     @type string $direction   'outgoing' or 'incoming'.
	 *     @type string $status      Message status.
	 *     @type string $recipient   Recipient phone.
	 *     @type int    $campaign_id Filter by campaign.
	 *     @type string $date_from   Start date (Y-m-d H:i:s).
	 *     @type string $date_to     End date (Y-m-d H:i:s).
	 *     @type string $orderby     Column to order by. Default 'created_at'.
	 *     @type string $order       ASC or DESC. Default 'DESC'.
	 *     @type int    $limit       Rows to return. Default 50.
	 *     @type int    $offset      Offset for pagination. Default 0.
	 * }
	 * @return array {
	 *     @type array $messages Array of message row objects.
	 *     @type int   $total    Total matching rows (for pagination).
	 * }
	 */
	public function get_messages( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'instance_id' => null,
			'direction'   => '',
			'status'      => '',
			'recipient'   => '',
			'campaign_id' => null,
			'date_from'   => '',
			'date_to'     => '',
			'orderby'     => 'created_at',
			'order'       => 'DESC',
			'limit'       => 50,
			'offset'      => 0,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = FWA_Helpers::get_messages_table();

		$where  = array();
		$values = array();

		if ( null !== $args['instance_id'] ) {
			$where[]  = 'instance_id = %d';
			$values[] = absint( $args['instance_id'] );
		}

		if ( '' !== $args['direction'] ) {
			$where[]  = 'direction = %s';
			$values[] = sanitize_text_field( $args['direction'] );
		}

		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		if ( '' !== $args['recipient'] ) {
			$where[]  = 'recipient = %s';
			$values[] = sanitize_text_field( $args['recipient'] );
		}

		if ( null !== $args['campaign_id'] ) {
			$where[]  = 'campaign_id = %d';
			$values[] = absint( $args['campaign_id'] );
		}

		if ( '' !== $args['date_from'] ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( $args['date_from'] );
		}

		if ( '' !== $args['date_to'] ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( $args['date_to'] );
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		// Total count for pagination.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		$allowed_orderby = array( 'id', 'instance_id', 'status', 'recipient', 'created_at', 'sent_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$messages = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		return array(
			'messages' => $messages,
			'total'    => $total,
		);
	}

	/**
	 * Get the number of messages sent today.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return int
	 */
	public function get_messages_count_today( $instance_id = null ) {
		global $wpdb;

		$table     = FWA_Helpers::get_messages_table();
		$today     = current_time( 'Y-m-d' ) . ' 00:00:00';
		$tomorrow  = current_time( 'Y-m-d' ) . ' 23:59:59';

		if ( null !== $instance_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE direction = 'outgoing' AND created_at BETWEEN %s AND %s AND instance_id = %d",
				$today,
				$tomorrow,
				absint( $instance_id )
			) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE direction = 'outgoing' AND created_at BETWEEN %s AND %s",
			$today,
			$tomorrow
		) );
	}

	/**
	 * Get an API client configured for a given or active instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return FWA_API_Client|WP_Error
	 */
	public function get_api_client( $instance_id = null ) {
		$instance = $this->resolve_instance( $instance_id );
		if ( is_wp_error( $instance ) ) {
			return $instance;
		}

		return $this->create_api_client( $instance );
	}

	/*--------------------------------------------------------------
	 * Internals
	 *------------------------------------------------------------*/

	/**
	 * Resolve an instance DB record.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $instance_id Instance DB ID or null for active.
	 * @return object|WP_Error Instance row or error.
	 */
	private function resolve_instance( $instance_id ) {
		if ( null !== $instance_id ) {
			$instance = $this->instance_manager->get( absint( $instance_id ) );
		} else {
			$instance = $this->instance_manager->get_active();
		}

		if ( ! $instance ) {
			return new WP_Error( 'fwa_no_instance', __( 'No active WhatsApp instance found.', 'flexi-whatsapp-automation' ) );
		}

		return $instance;
	}

	/**
	 * Create an API client from an instance record.
	 *
	 * @since 1.0.0
	 *
	 * @param object $instance Instance database row.
	 * @return FWA_API_Client
	 */
	private function create_api_client( $instance ) {
		return new FWA_API_Client( $instance->instance_id, $instance->access_token );
	}

	/**
	 * Dispatch the API call based on message type.
	 *
	 * @since 1.0.0
	 *
	 * @param FWA_API_Client $client API client.
	 * @param array          $args   Message arguments.
	 * @return array|WP_Error API response.
	 */
	private function dispatch( $client, $args ) {
		$to = sanitize_text_field( $args['to'] );

		switch ( $args['type'] ) {
			case 'text':
				return $client->sendText( $to, $args['content'] );

			case 'image':
			case 'video':
			case 'audio':
			case 'document':
				return $client->sendMedia( $to, $args['type'], $args['media_url'], $args['media_caption'] );

			case 'contact':
				$contact_data = FWA_Helpers::is_json( $args['content'] ) ? json_decode( $args['content'], true ) : $args['content'];
				return $client->sendContact( $to, $contact_data );

			case 'poll':
				$poll_data = FWA_Helpers::is_json( $args['content'] ) ? json_decode( $args['content'], true ) : array();
				return $client->sendPoll(
					$to,
					isset( $poll_data['question'] ) ? $poll_data['question'] : '',
					isset( $poll_data['options'] ) ? $poll_data['options'] : array(),
					! empty( $poll_data['multi_select'] )
				);

			case 'link_preview':
				return $client->sendLinkPreview( $to, $args['media_url'], $args['content'] );

			default:
				return new WP_Error( 'fwa_unknown_type', __( 'Unknown message type.', 'flexi-whatsapp-automation' ) );
		}
	}

	/**
	 * Record a message in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param array          $args     Message arguments.
	 * @param object         $instance Instance record.
	 * @param string         $status   'sent' or 'failed'.
	 * @param array|WP_Error $response API response.
	 * @return int|WP_Error Inserted message ID or error.
	 */
	private function record_message( $args, $instance, $status, $response ) {
		global $wpdb;

		$table = FWA_Helpers::get_messages_table();
		$now   = current_time( 'mysql' );

		$data = array(
			'instance_id'   => absint( $instance->id ),
			'direction'     => 'outgoing',
			'message_type'  => sanitize_text_field( $args['type'] ),
			'recipient'     => sanitize_text_field( $args['to'] ),
			'content'       => $args['content'],
			'media_url'     => esc_url_raw( $args['media_url'] ),
			'media_caption' => sanitize_text_field( $args['media_caption'] ),
			'status'        => $status,
			'campaign_id'   => $args['campaign_id'] ? absint( $args['campaign_id'] ) : null,
			'schedule_id'   => $args['schedule_id'] ? absint( $args['schedule_id'] ) : null,
			'created_at'    => $now,
		);

		if ( 'sent' === $status ) {
			$data['sent_at'] = $now;
			if ( is_array( $response ) && isset( $response['messageId'] ) ) {
				$data['message_id'] = sanitize_text_field( $response['messageId'] );
			}
		}

		if ( 'failed' === $status && is_wp_error( $response ) ) {
			$data['error_message'] = $response->get_error_message();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_insert_failed', __( 'Failed to record message.', 'flexi-whatsapp-automation' ) );
		}

		return (int) $wpdb->insert_id;
	}
}
