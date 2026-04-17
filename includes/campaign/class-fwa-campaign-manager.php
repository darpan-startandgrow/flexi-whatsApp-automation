<?php
/**
 * Campaign management system.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Campaign_Manager
 *
 * Handles creation, scheduling, execution, and tracking of bulk
 * message campaigns.
 *
 * @since 1.0.0
 */
class FWA_Campaign_Manager {

	/**
	 * Constructor.
	 *
	 * Hooks into WP-Cron to process running campaigns.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'fwa_process_campaigns', array( $this, 'process' ) );
	}

	/*--------------------------------------------------------------
	 * CRUD
	 *------------------------------------------------------------*/

	/**
	 * Create a new campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Campaign data.
	 *
	 *     @type string $name              Required. Campaign name.
	 *     @type int    $instance_id       Required. Instance DB ID.
	 *     @type string $content           Required. Message body.
	 *     @type string $message_type      Optional. Default 'text'.
	 *     @type string $media_url         Optional. Media URL.
	 *     @type string $media_caption     Optional. Media caption.
	 *     @type string $recipients_filter Optional. JSON-encoded filter.
	 *     @type string $scheduled_at      Optional. Future datetime for scheduling.
	 * }
	 * @return int|WP_Error Insert ID on success, WP_Error on failure.
	 */
	public function create( $args ) {
		global $wpdb;

		if ( empty( $args['name'] ) ) {
			return new WP_Error( 'fwa_missing_name', __( 'Campaign name is required.', 'flexi-whatsapp-automation' ) );
		}
		if ( empty( $args['instance_id'] ) ) {
			return new WP_Error( 'fwa_missing_instance', __( 'Instance ID is required.', 'flexi-whatsapp-automation' ) );
		}
		if ( empty( $args['content'] ) ) {
			return new WP_Error( 'fwa_missing_content', __( 'Campaign content is required.', 'flexi-whatsapp-automation' ) );
		}

		// Determine initial status.
		$status = 'draft';
		if ( ! empty( $args['scheduled_at'] ) && strtotime( $args['scheduled_at'] ) > current_time( 'timestamp' ) ) {
			$status = 'scheduled';
		}

		$defaults = array(
			'name'              => '',
			'instance_id'       => 0,
			'message_type'      => 'text',
			'content'           => '',
			'media_url'         => '',
			'media_caption'     => '',
			'recipients_filter' => '',
			'status'            => $status,
			'scheduled_at'      => null,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $args, $defaults );
		$data = array_intersect_key( $data, $defaults );

		$data['name']          = sanitize_text_field( $data['name'] );
		$data['instance_id']   = absint( $data['instance_id'] );
		$data['message_type']  = sanitize_text_field( $data['message_type'] );
		$data['media_url']     = esc_url_raw( $data['media_url'] );
		$data['media_caption'] = sanitize_text_field( $data['media_caption'] );

		$table  = FWA_Helpers::get_campaigns_table();
		$result = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_insert_failed', __( 'Failed to create campaign.', 'flexi-whatsapp-automation' ) );
		}

		$id = (int) $wpdb->insert_id;

		/**
		 * Fires after a campaign is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Campaign ID.
		 * @param array $args Original arguments.
		 */
		do_action( 'fwa_campaign_created', $id, $args );

		return $id;
	}

	/**
	 * Update an existing campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Campaign ID.
	 * @param array $args Columns to update.
	 * @return true|WP_Error
	 */
	public function update( $id, $args ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) );
		}

		$allowed = array(
			'name', 'instance_id', 'message_type', 'content', 'media_url',
			'media_caption', 'recipients_filter', 'total_recipients',
			'sent_count', 'delivered_count', 'read_count', 'failed_count',
			'status', 'scheduled_at', 'started_at', 'completed_at', 'updated_at',
		);

		$data = array_intersect_key( $args, array_flip( $allowed ) );

		if ( empty( $data ) ) {
			return new WP_Error( 'fwa_nothing_to_update', __( 'No valid fields to update.', 'flexi-whatsapp-automation' ) );
		}

		if ( isset( $data['name'] ) ) {
			$data['name'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['instance_id'] ) ) {
			$data['instance_id'] = absint( $data['instance_id'] );
		}
		if ( isset( $data['media_url'] ) ) {
			$data['media_url'] = esc_url_raw( $data['media_url'] );
		}
		if ( isset( $data['media_caption'] ) ) {
			$data['media_caption'] = sanitize_text_field( $data['media_caption'] );
		}

		$data['updated_at'] = current_time( 'mysql' );

		$table  = FWA_Helpers::get_campaigns_table();
		$result = $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_update_failed', __( 'Failed to update campaign.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a campaign is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Campaign ID.
		 * @param array $args Updated fields.
		 */
		do_action( 'fwa_campaign_updated', $id, $args );

		return true;
	}

	/**
	 * Delete a campaign.
	 *
	 * Running campaigns cannot be deleted — pause or complete them first.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return true|WP_Error
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) );
		}

		$campaign = $this->get( $id );
		if ( ! $campaign ) {
			return new WP_Error( 'fwa_not_found', __( 'Campaign not found.', 'flexi-whatsapp-automation' ) );
		}

		if ( 'running' === $campaign->status ) {
			return new WP_Error( 'fwa_campaign_running', __( 'Cannot delete a running campaign. Pause it first.', 'flexi-whatsapp-automation' ) );
		}

		// Remove associated logs.
		$logs_table = FWA_Helpers::get_campaign_logs_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->delete( $logs_table, array( 'campaign_id' => $id ), array( '%d' ) );

		$table  = FWA_Helpers::get_campaigns_table();
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_delete_failed', __( 'Failed to delete campaign.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a campaign is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Deleted campaign ID.
		 */
		do_action( 'fwa_campaign_deleted', $id );

		return true;
	}

	/**
	 * Retrieve a single campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return object|null Database row or null.
	 */
	public function get( $id ) {
		global $wpdb;

		$table = FWA_Helpers::get_campaigns_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) )
		);
	}

	/**
	 * Retrieve campaigns with optional filtering and pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $instance_id Filter by instance.
	 *     @type string $status      Filter by status.
	 *     @type string $orderby     Column to order by. Default 'created_at'.
	 *     @type string $order       ASC or DESC. Default 'DESC'.
	 *     @type int    $limit       Rows to return. Default 50.
	 *     @type int    $offset      Offset for pagination. Default 0.
	 * }
	 * @return array {
	 *     @type array $campaigns Array of campaign row objects.
	 *     @type int   $total     Total matching rows.
	 * }
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'instance_id' => null,
			'status'      => '',
			'orderby'     => 'created_at',
			'order'       => 'DESC',
			'limit'       => 50,
			'offset'      => 0,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = FWA_Helpers::get_campaigns_table();

		$where  = array();
		$values = array();

		if ( null !== $args['instance_id'] ) {
			$where[]  = 'instance_id = %d';
			$values[] = absint( $args['instance_id'] );
		}

		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		// Total count.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		$allowed_orderby = array( 'id', 'name', 'status', 'created_at', 'updated_at', 'scheduled_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$campaigns = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		return array(
			'campaigns' => $campaigns,
			'total'     => $total,
		);
	}

	/*--------------------------------------------------------------
	 * Execution lifecycle
	 *------------------------------------------------------------*/

	/**
	 * Start a campaign.
	 *
	 * Resolves recipients, creates log entries for each, and sets the
	 * campaign to 'running'.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return int|WP_Error Number of recipients on success, WP_Error on failure.
	 */
	public function start( $id ) {
		// License guard — block campaign execution without a valid license.
		if ( class_exists( 'FWA_License_Guard' ) ) {
			$license_check = FWA_License_Guard::can_execute_campaign();
			if ( is_wp_error( $license_check ) ) {
				return $license_check;
			}
		}

		global $wpdb;

		$id       = absint( $id );
		$campaign = $this->get( $id );

		if ( ! $campaign ) {
			return new WP_Error( 'fwa_not_found', __( 'Campaign not found.', 'flexi-whatsapp-automation' ) );
		}

		if ( ! in_array( $campaign->status, array( 'draft', 'scheduled' ), true ) ) {
			return new WP_Error( 'fwa_invalid_status', __( 'Campaign can only be started from draft or scheduled status.', 'flexi-whatsapp-automation' ) );
		}

		$recipients = $this->resolve_recipients( $campaign->recipients_filter );

		if ( empty( $recipients ) ) {
			return new WP_Error( 'fwa_no_recipients', __( 'No recipients found for this campaign.', 'flexi-whatsapp-automation' ) );
		}

		$count      = count( $recipients );
		$logs_table = FWA_Helpers::get_campaign_logs_table();
		$now        = current_time( 'mysql' );

		// Create a pending log entry for each recipient.
		foreach ( $recipients as $phone ) {
			// Lookup contact by phone.
			$contacts_table = FWA_Helpers::get_contacts_table();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$contact = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM {$contacts_table} WHERE phone = %s LIMIT 1",
				$phone
			) );

			$wpdb->insert( $logs_table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				'campaign_id' => $id,
				'contact_id'  => $contact ? (int) $contact->id : null,
				'phone'       => sanitize_text_field( $phone ),
				'status'      => 'pending',
				'created_at'  => $now,
			) );
		}

		// Update campaign record.
		$this->update( $id, array(
			'total_recipients' => $count,
			'status'           => 'running',
			'started_at'       => $now,
		) );

		/**
		 * Fires after a campaign is started.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Campaign ID.
		 */
		do_action( 'fwa_campaign_started', $id );

		return $count;
	}

	/**
	 * Pause a running campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return true|WP_Error
	 */
	public function pause( $id ) {
		$result = $this->update( absint( $id ), array( 'status' => 'paused' ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a campaign is paused.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Campaign ID.
		 */
		do_action( 'fwa_campaign_paused', absint( $id ) );

		return true;
	}

	/**
	 * Resume a paused campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return true|WP_Error
	 */
	public function resume( $id ) {
		$result = $this->update( absint( $id ), array( 'status' => 'running' ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a campaign is resumed.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Campaign ID.
		 */
		do_action( 'fwa_campaign_resumed', absint( $id ) );

		return true;
	}

	/**
	 * Process running campaigns (called by WP-Cron).
	 *
	 * Sends pending messages in batches, respects rate limiting, and
	 * marks campaigns as completed when finished.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function process() {
		// License guard — skip campaign processing without a valid license.
		if ( class_exists( 'FWA_License_Guard' ) && ! FWA_License_Guard::is_licensed() ) {
			return;
		}

		global $wpdb;

		$table     = FWA_Helpers::get_campaigns_table();
		$log_table = FWA_Helpers::get_campaign_logs_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$campaigns = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status = %s",
			'running'
		) );

		if ( empty( $campaigns ) ) {
			return;
		}

		$sender = new FWA_Message_Sender();

		/**
		 * Filter the delay (in seconds) between campaign messages.
		 *
		 * @since 1.0.0
		 *
		 * @param int $delay Default delay in seconds.
		 */
		$delay = (int) apply_filters( 'fwa_campaign_message_delay', 3 );

		foreach ( $campaigns as $campaign ) {

			// Fetch next batch of pending logs.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$pending = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$log_table} WHERE campaign_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
				$campaign->id,
				10
			) );

			if ( empty( $pending ) ) {
				// No more pending — mark campaign as completed.
				$this->update( $campaign->id, array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
				) );
				$this->update_campaign_counts( $campaign->id );
				continue;
			}

			foreach ( $pending as $log ) {
				$send_args = array(
					'to'            => $log->phone,
					'type'          => $campaign->message_type,
					'content'       => $campaign->content,
					'media_url'     => $campaign->media_url,
					'media_caption' => $campaign->media_caption,
					'instance_id'   => $campaign->instance_id,
					'campaign_id'   => $campaign->id,
				);

				$result = $sender->send( $send_args );
				$now    = current_time( 'mysql' );

				if ( is_wp_error( $result ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->update( $log_table, array(
						'status'        => 'failed',
						'error_message' => $result->get_error_message(),
						'sent_at'       => $now,
					), array( 'id' => $log->id ) );
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->update( $log_table, array(
						'status'     => 'sent',
						'message_id' => absint( $result ),
						'sent_at'    => $now,
					), array( 'id' => $log->id ) );
				}

				// Rate limiting between messages.
				if ( $delay > 0 ) {
					sleep( $delay );
				}
			}

			$this->update_campaign_counts( $campaign->id );
		}
	}

	/*--------------------------------------------------------------
	 * Recipients
	 *------------------------------------------------------------*/

	/**
	 * Resolve phone numbers from a recipients filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filter JSON-encoded filter definition.
	 * @return array Array of phone number strings.
	 */
	public function resolve_recipients( $filter ) {
		global $wpdb;

		$contacts_table = FWA_Helpers::get_contacts_table();
		$phones         = array();

		$decoded = json_decode( $filter, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = array( 'type' => 'all' );
		}

		$type = isset( $decoded['type'] ) ? $decoded['type'] : 'all';

		switch ( $type ) {
			case 'all':
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$rows = $wpdb->get_col( $wpdb->prepare(
					"SELECT phone FROM {$contacts_table} WHERE is_subscribed = %d",
					1
				) );
				$phones = $rows ? $rows : array();
				break;

			case 'tags':
				$tags = isset( $decoded['tags'] ) ? (array) $decoded['tags'] : array();
				foreach ( $tags as $tag ) {
					$tag = sanitize_text_field( $tag );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$rows = $wpdb->get_col( $wpdb->prepare(
						"SELECT phone FROM {$contacts_table} WHERE is_subscribed = %d AND tags LIKE %s",
						1,
						'%' . $wpdb->esc_like( $tag ) . '%'
					) );
					if ( $rows ) {
						$phones = array_merge( $phones, $rows );
					}
				}
				$phones = array_unique( $phones );
				break;

			case 'contacts':
				$ids = isset( $decoded['ids'] ) ? array_map( 'absint', (array) $decoded['ids'] ) : array();
				if ( ! empty( $ids ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
					$rows = $wpdb->get_col( $wpdb->prepare(
						"SELECT phone FROM {$contacts_table} WHERE id IN ({$placeholders})",
						$ids
					) );
					$phones = $rows ? $rows : array();
				}
				break;

			case 'phones':
				$phones = isset( $decoded['phones'] ) ? array_map( 'sanitize_text_field', (array) $decoded['phones'] ) : array();
				break;
		}

		/**
		 * Filter the resolved campaign recipients.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $phones Resolved phone numbers.
		 * @param string $filter Original JSON filter string.
		 */
		return apply_filters( 'fwa_campaign_recipients', $phones, $filter );
	}

	/*--------------------------------------------------------------
	 * Stats
	 *------------------------------------------------------------*/

	/**
	 * Get aggregated stats for a campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return array {
	 *     @type int $total     Total log entries.
	 *     @type int $sent      Sent count.
	 *     @type int $delivered Delivered count.
	 *     @type int $read      Read count.
	 *     @type int $failed    Failed count.
	 * }
	 */
	public function get_campaign_stats( $id ) {
		global $wpdb;

		$table = FWA_Helpers::get_campaign_logs_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT status, COUNT(*) AS total FROM {$table} WHERE campaign_id = %d GROUP BY status",
			absint( $id )
		) );

		$stats = array(
			'total'     => 0,
			'sent'      => 0,
			'delivered' => 0,
			'read'      => 0,
			'failed'    => 0,
		);

		if ( $rows ) {
			foreach ( $rows as $row ) {
				if ( isset( $stats[ $row->status ] ) ) {
					$stats[ $row->status ] = (int) $row->total;
				}
				$stats['total'] += (int) $row->total;
			}
		}

		return $stats;
	}

	/**
	 * Recalculate and update campaign counts from log data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign ID.
	 * @return true|WP_Error
	 */
	public function update_campaign_counts( $id ) {
		$stats = $this->get_campaign_stats( absint( $id ) );

		return $this->update( absint( $id ), array(
			'total_recipients' => $stats['total'],
			'sent_count'       => $stats['sent'],
			'delivered_count'  => $stats['delivered'],
			'read_count'       => $stats['read'],
			'failed_count'     => $stats['failed'],
		) );
	}
}
