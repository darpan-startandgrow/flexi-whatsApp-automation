<?php
/**
 * Multi-account WhatsApp instance manager.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Instance_Manager
 *
 * Manages WhatsApp instances — CRUD, status tracking, connectivity,
 * and periodic health checks.
 *
 * @since 1.0.0
 */
class FWA_Instance_Manager {

	/**
	 * Constructor.
	 *
	 * Registers the recurring health-check schedule on admin_init.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'schedule_health_check' ) );
		add_action( 'fwa_instance_health_check_event', array( $this, 'health_check' ) );
	}

	/**
	 * Ensure the health-check cron event is scheduled.
	 *
	 * @since 1.0.0
	 */
	public function schedule_health_check() {
		if ( ! wp_next_scheduled( 'fwa_instance_health_check_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'fwa_instance_health_check_event' );
		}
	}

	/*--------------------------------------------------------------
	 * CRUD
	 *------------------------------------------------------------*/

	/**
	 * Create a new instance record.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Instance data.
	 *
	 *     @type string $name         Required. Display name.
	 *     @type string $instance_id  External instance identifier.
	 *     @type string $access_token API access token.
	 *     @type string $phone_number Associated phone number.
	 *     @type string $webhook_url  Webhook callback URL.
	 *     @type string $status       Initial status. Default 'disconnected'.
	 *     @type int    $is_active    Whether this is the active instance.
	 * }
	 * @return int|WP_Error Insert ID on success, WP_Error on failure.
	 */
	public function create( $args ) {
		global $wpdb;

		if ( empty( $args['name'] ) ) {
			return new WP_Error(
				'fwa_missing_name',
				__( 'Instance name is required.', 'flexi-whatsapp-automation' )
			);
		}

		$defaults = array(
			'name'         => '',
			'instance_id'  => '',
			'access_token' => '',
			'phone_number' => '',
			'webhook_url'  => '',
			'status'       => 'disconnected',
			'is_active'    => 0,
			'meta'         => '',
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $args, $defaults );
		$data = array_intersect_key( $data, $defaults );

		$data['name']         = sanitize_text_field( $data['name'] );
		$data['instance_id']  = sanitize_text_field( $data['instance_id'] );
		$data['access_token'] = sanitize_text_field( $data['access_token'] );
		$data['phone_number'] = sanitize_text_field( $data['phone_number'] );
		$data['webhook_url']  = esc_url_raw( $data['webhook_url'] );
		$data['is_active']    = absint( $data['is_active'] );

		$table  = FWA_Helpers::get_instance_table();
		$result = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error(
				'fwa_db_insert_failed',
				__( 'Failed to create instance.', 'flexi-whatsapp-automation' )
			);
		}

		$id = (int) $wpdb->insert_id;

		/**
		 * Fires after an instance is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Newly created instance ID.
		 * @param array $args Original arguments.
		 */
		do_action( 'fwa_instance_created', $id, $args );

		return $id;
	}

	/**
	 * Update an existing instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Instance ID.
	 * @param array $args Columns to update.
	 * @return true|WP_Error
	 */
	public function update( $id, $args ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) );
		}

		$allowed = array(
			'name', 'instance_id', 'access_token', 'phone_number',
			'webhook_url', 'status', 'is_active', 'meta',
			'last_seen_at', 'updated_at',
		);

		$data = array_intersect_key( $args, array_flip( $allowed ) );

		if ( empty( $data ) ) {
			return new WP_Error( 'fwa_nothing_to_update', __( 'No valid fields to update.', 'flexi-whatsapp-automation' ) );
		}

		if ( isset( $data['name'] ) ) {
			$data['name'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['instance_id'] ) ) {
			$data['instance_id'] = sanitize_text_field( $data['instance_id'] );
		}
		if ( isset( $data['access_token'] ) ) {
			$data['access_token'] = sanitize_text_field( $data['access_token'] );
		}
		if ( isset( $data['phone_number'] ) ) {
			$data['phone_number'] = sanitize_text_field( $data['phone_number'] );
		}
		if ( isset( $data['webhook_url'] ) ) {
			$data['webhook_url'] = esc_url_raw( $data['webhook_url'] );
		}
		if ( isset( $data['is_active'] ) ) {
			$data['is_active'] = absint( $data['is_active'] );
		}

		$data['updated_at'] = current_time( 'mysql' );

		$table  = FWA_Helpers::get_instance_table();
		$result = $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_update_failed', __( 'Failed to update instance.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after an instance is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Instance ID.
		 * @param array $args Updated fields.
		 */
		do_action( 'fwa_instance_updated', $id, $args );

		return true;
	}

	/**
	 * Delete an instance.
	 *
	 * Optionally removes the remote instance via the API before
	 * deleting the local database record.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Instance ID.
	 * @return true|WP_Error
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) );
		}

		$instance = $this->get( $id );
		if ( ! $instance ) {
			return new WP_Error( 'fwa_not_found', __( 'Instance not found.', 'flexi-whatsapp-automation' ) );
		}

		// Attempt remote deletion when credentials are available.
		if ( ! empty( $instance->instance_id ) && ! empty( $instance->access_token ) ) {
			$client = new FWA_API_Client( $instance->instance_id, $instance->access_token );
			$client->deleteInstance( $instance->instance_id );
		}

		$table  = FWA_Helpers::get_instance_table();
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_delete_failed', __( 'Failed to delete instance.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after an instance is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Deleted instance ID.
		 */
		do_action( 'fwa_instance_deleted', $id );

		return true;
	}

	/**
	 * Retrieve a single instance by primary key.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Instance ID.
	 * @return object|null Database row or null.
	 */
	public function get( $id ) {
		global $wpdb;

		$table = FWA_Helpers::get_instance_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) )
		);
	}

	/**
	 * Retrieve an instance by its external instance_id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $instance_id External instance identifier.
	 * @return object|null
	 */
	public function get_by_instance_id( $instance_id ) {
		global $wpdb;

		$table = FWA_Helpers::get_instance_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE instance_id = %s LIMIT 1", sanitize_text_field( $instance_id ) )
		);
	}

	/**
	 * Retrieve instances with optional filtering and ordering.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type string $status    Filter by status.
	 *     @type int    $is_active Filter by active flag (0 or 1).
	 *     @type string $orderby   Column to order by. Default 'created_at'.
	 *     @type string $order     ASC or DESC. Default 'DESC'.
	 *     @type int    $limit     Number of rows. Default 50.
	 *     @type int    $offset    Offset for pagination. Default 0.
	 * }
	 * @return array Array of instance row objects.
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'    => '',
			'is_active' => null,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'limit'     => 50,
			'offset'    => 0,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = FWA_Helpers::get_instance_table();

		$where  = array();
		$values = array();

		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		if ( null !== $args['is_active'] ) {
			$where[]  = 'is_active = %d';
			$values[] = absint( $args['is_active'] );
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		$allowed_orderby = array( 'id', 'name', 'status', 'created_at', 'updated_at', 'last_seen_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * Get the currently active instance.
	 *
	 * Falls back to the first connected instance when none is explicitly active.
	 *
	 * @since 1.0.0
	 *
	 * @return object|null Instance row or null.
	 */
	public function get_active() {
		global $wpdb;

		$table = FWA_Helpers::get_instance_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$active = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE is_active = %d LIMIT 1", 1 )
		);

		if ( $active ) {
			return $active;
		}

		// Fallback: first connected instance.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY created_at ASC LIMIT 1", 'connected' )
		);
	}

	/**
	 * Set a specific instance as the active one (deactivates all others).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Instance ID to activate.
	 * @return true|WP_Error
	 */
	public function set_active( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) );
		}

		$table = FWA_Helpers::get_instance_table();

		// Deactivate all.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$table} SET is_active = 0" );

		// Activate the chosen instance.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $table, array( 'is_active' => 1, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );

		/**
		 * Fires after an instance is set as the active instance.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Activated instance ID.
		 */
		do_action( 'fwa_instance_activated', $id );

		return true;
	}

	/*--------------------------------------------------------------
	 * Status & connectivity
	 *------------------------------------------------------------*/

	/**
	 * Update the status of an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $id     Instance ID.
	 * @param string $status New status value.
	 * @return true|WP_Error
	 */
	public function update_status( $id, $status ) {
		$instance = $this->get( absint( $id ) );
		if ( ! $instance ) {
			return new WP_Error( 'fwa_not_found', __( 'Instance not found.', 'flexi-whatsapp-automation' ) );
		}

		$old_status = $instance->status;

		$data = array( 'status' => sanitize_text_field( $status ) );

		if ( 'connected' === $status ) {
			$data['last_seen_at'] = current_time( 'mysql' );
		}

		$result = $this->update( $id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires when an instance's status changes.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $id         Instance ID.
		 * @param string $status     New status.
		 * @param string $old_status Previous status.
		 */
		do_action( 'fwa_instance_status_changed', $id, $status, $old_status );

		return true;
	}

	/**
	 * Initiate a connection (QR code flow) for an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Instance ID.
	 * @return array|WP_Error QR data / status or error.
	 */
	public function connect( $id ) {
		$instance = $this->get( absint( $id ) );
		if ( ! $instance ) {
			return new WP_Error( 'fwa_not_found', __( 'Instance not found.', 'flexi-whatsapp-automation' ) );
		}

		$client = new FWA_API_Client( $instance->instance_id, $instance->access_token );

		// Attempt reconnect first; fall back to QR code.
		$status = $client->getInstanceStatus( $instance->instance_id );

		if ( ! is_wp_error( $status ) && isset( $status['status'] ) && 'connected' === $status['status'] ) {
			$this->update_status( $id, 'connected' );
			return $status;
		}

		$reconnect = $client->reconnectInstance( $instance->instance_id );

		if ( ! is_wp_error( $reconnect ) && isset( $reconnect['status'] ) && 'connected' === $reconnect['status'] ) {
			$this->update_status( $id, 'connected' );
			return $reconnect;
		}

		// Fall back to QR code.
		$qr = $client->getQRCode( $instance->instance_id );

		if ( is_wp_error( $qr ) ) {
			return $qr;
		}

		$this->update_status( $id, 'qr_required' );

		return $qr;
	}

	/**
	 * Disconnect (logout) an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Instance ID.
	 * @return true|WP_Error
	 */
	public function disconnect( $id ) {
		$instance = $this->get( absint( $id ) );
		if ( ! $instance ) {
			return new WP_Error( 'fwa_not_found', __( 'Instance not found.', 'flexi-whatsapp-automation' ) );
		}

		$client = new FWA_API_Client( $instance->instance_id, $instance->access_token );
		$client->logoutInstance( $instance->instance_id );

		$this->update_status( $id, 'disconnected' );

		return true;
	}

	/*--------------------------------------------------------------
	 * Health check
	 *------------------------------------------------------------*/

	/**
	 * Check the health of all non-disconnected instances.
	 *
	 * Queries the remote API for status, updates local records, and
	 * attempts to reconnect instances that should be online.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function health_check() {
		$instances = $this->get_all( array(
			'limit' => 100,
		) );

		$results = array();

		foreach ( $instances as $instance ) {
			// Skip instances that are intentionally disconnected.
			if ( 'disconnected' === $instance->status ) {
				continue;
			}

			if ( empty( $instance->instance_id ) || empty( $instance->access_token ) ) {
				continue;
			}

			$client = new FWA_API_Client( $instance->instance_id, $instance->access_token );
			$status = $client->getInstanceStatus( $instance->instance_id );

			if ( is_wp_error( $status ) ) {
				$results[ $instance->id ] = 'error';
				continue;
			}

			$remote_status = isset( $status['status'] ) ? sanitize_text_field( $status['status'] ) : 'disconnected';

			// Normalize remote status to the allowed ENUM values.
			$allowed = array( 'connected', 'disconnected', 'qr_required', 'initializing', 'banned' );
			if ( ! in_array( $remote_status, $allowed, true ) ) {
				$remote_status = 'disconnected';
			}

			if ( $remote_status !== $instance->status ) {
				$this->update_status( $instance->id, $remote_status );
			}

			// Attempt to reconnect instances that dropped unexpectedly.
			if ( 'disconnected' === $remote_status && in_array( $instance->status, array( 'connected', 'initializing' ), true ) ) {
				$client->reconnectInstance( $instance->instance_id );
			}

			$results[ $instance->id ] = $remote_status;
		}

		/**
		 * Fires after all instance health checks are complete.
		 *
		 * @since 1.0.0
		 *
		 * @param array $results Associative array of instance_id => status.
		 */
		do_action( 'fwa_instance_health_checked', $results );
	}

	/*--------------------------------------------------------------
	 * Counts / stats
	 *------------------------------------------------------------*/

	/**
	 * Get a count of instances grouped by status.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of status => count.
	 */
	public function get_count_by_status() {
		global $wpdb;

		$table = FWA_Helpers::get_instance_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status" );

		$counts = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$counts[ $row->status ] = (int) $row->total;
			}
		}

		return $counts;
	}

	/**
	 * Get the number of currently connected instances.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_connected_count() {
		global $wpdb;

		$table = FWA_Helpers::get_instance_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'connected' )
		);
	}
}
