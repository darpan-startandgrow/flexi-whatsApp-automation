<?php
/**
 * Scheduled messages and recurring jobs.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Scheduler
 *
 * Handles one-time and recurring scheduled messages, executed via
 * WP-Cron with a transient-based lock to prevent duplicate processing.
 *
 * @since 1.0.0
 */
class FWA_Scheduler {

	/**
	 * Constructor.
	 *
	 * Hooks into the per-minute cron event to process the queue.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'fwa_process_queue', array( $this, 'process' ) );
	}

	/*--------------------------------------------------------------
	 * CRUD
	 *------------------------------------------------------------*/

	/**
	 * Create a new schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Schedule data.
	 *
	 *     @type int    $instance_id     Required. Instance DB ID.
	 *     @type string $recipient       Required. Phone number.
	 *     @type string $content         Required. Message body.
	 *     @type string $name            Optional. Schedule name.
	 *     @type string $type            Optional. 'one_time' or 'recurring'. Default 'one_time'.
	 *     @type string $message_type    Optional. Default 'text'.
	 *     @type string $media_url       Optional. Media URL.
	 *     @type string $recurrence_rule Optional. Recurrence interval keyword.
	 *     @type string $next_run_at     Optional. Future datetime for first execution.
	 *     @type int    $max_runs        Optional. Maximum executions (0 = unlimited).
	 * }
	 * @return int|WP_Error Insert ID on success, WP_Error on failure.
	 */
	public function create( $args ) {
		global $wpdb;

		if ( empty( $args['instance_id'] ) ) {
			return new WP_Error( 'fwa_missing_instance', __( 'Instance ID is required.', 'flexi-whatsapp-automation' ) );
		}
		if ( empty( $args['recipient'] ) ) {
			return new WP_Error( 'fwa_missing_recipient', __( 'Recipient is required.', 'flexi-whatsapp-automation' ) );
		}
		if ( empty( $args['content'] ) ) {
			return new WP_Error( 'fwa_missing_content', __( 'Message content is required.', 'flexi-whatsapp-automation' ) );
		}

		// Validate next_run_at is in the future.
		if ( ! empty( $args['next_run_at'] ) && strtotime( $args['next_run_at'] ) <= current_time( 'timestamp' ) ) {
			return new WP_Error( 'fwa_invalid_next_run', __( 'Scheduled time must be in the future.', 'flexi-whatsapp-automation' ) );
		}

		$defaults = array(
			'instance_id'     => 0,
			'name'            => '',
			'type'            => 'one_time',
			'message_type'    => 'text',
			'content'         => '',
			'media_url'       => '',
			'recipient'       => '',
			'recurrence_rule' => '',
			'status'          => 'active',
			'next_run_at'     => current_time( 'mysql' ),
			'last_run_at'     => null,
			'run_count'       => 0,
			'max_runs'        => 0,
			'retry_count'     => 0,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $args, $defaults );
		$data = array_intersect_key( $data, $defaults );

		// Normalise type.
		if ( ! in_array( $data['type'], array( 'one_time', 'recurring' ), true ) ) {
			$data['type'] = 'one_time';
		}

		// Default recurrence for recurring schedules.
		if ( 'recurring' === $data['type'] && empty( $data['recurrence_rule'] ) ) {
			$data['recurrence_rule'] = 'daily';
		}

		// Sanitise.
		$data['instance_id']     = absint( $data['instance_id'] );
		$data['name']            = sanitize_text_field( $data['name'] );
		$data['message_type']    = sanitize_text_field( $data['message_type'] );
		$data['content']         = sanitize_textarea_field( $data['content'] );
		$data['media_url']       = esc_url_raw( $data['media_url'] );
		$data['recipient']       = sanitize_text_field( $data['recipient'] );
		$data['recurrence_rule'] = sanitize_text_field( $data['recurrence_rule'] );
		$data['max_runs']        = absint( $data['max_runs'] );

		$table  = FWA_Helpers::get_schedules_table();
		$result = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_insert_failed', __( 'Failed to create schedule.', 'flexi-whatsapp-automation' ) );
		}

		$id = (int) $wpdb->insert_id;

		/**
		 * Fires after a schedule is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Schedule ID.
		 * @param array $args Original arguments.
		 */
		do_action( 'fwa_schedule_created', $id, $args );

		return $id;
	}

	/**
	 * Update an existing schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Schedule ID.
	 * @param array $args Columns to update.
	 * @return true|WP_Error
	 */
	public function update( $id, $args ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid schedule ID.', 'flexi-whatsapp-automation' ) );
		}

		$allowed = array(
			'name', 'instance_id', 'type', 'message_type', 'content',
			'media_url', 'recipient', 'recurrence_rule', 'status',
			'next_run_at', 'last_run_at', 'run_count', 'max_runs',
			'retry_count', 'updated_at',
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
		if ( isset( $data['recipient'] ) ) {
			$data['recipient'] = sanitize_text_field( $data['recipient'] );
		}

		$data['updated_at'] = current_time( 'mysql' );

		$table  = FWA_Helpers::get_schedules_table();
		$result = $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_update_failed', __( 'Failed to update schedule.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a schedule is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Schedule ID.
		 * @param array $args Updated fields.
		 */
		do_action( 'fwa_schedule_updated', $id, $args );

		return true;
	}

	/**
	 * Delete a schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Schedule ID.
	 * @return true|WP_Error
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid schedule ID.', 'flexi-whatsapp-automation' ) );
		}

		$schedule = $this->get( $id );
		if ( ! $schedule ) {
			return new WP_Error( 'fwa_not_found', __( 'Schedule not found.', 'flexi-whatsapp-automation' ) );
		}

		$table  = FWA_Helpers::get_schedules_table();
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_delete_failed', __( 'Failed to delete schedule.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a schedule is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Deleted schedule ID.
		 */
		do_action( 'fwa_schedule_deleted', $id );

		return true;
	}

	/**
	 * Retrieve a single schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Schedule ID.
	 * @return object|null Database row or null.
	 */
	public function get( $id ) {
		global $wpdb;

		$table = FWA_Helpers::get_schedules_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) )
		);
	}

	/**
	 * Retrieve schedules with optional filtering and pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $instance_id Filter by instance.
	 *     @type string $status      Filter by status.
	 *     @type string $type        Filter by type (one_time|recurring).
	 *     @type string $orderby     Column to order by. Default 'created_at'.
	 *     @type string $order       ASC or DESC. Default 'DESC'.
	 *     @type int    $limit       Rows to return. Default 50.
	 *     @type int    $offset      Offset for pagination. Default 0.
	 * }
	 * @return array {
	 *     @type array $schedules Array of schedule row objects.
	 *     @type int   $total     Total matching rows.
	 * }
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'instance_id' => null,
			'status'      => '',
			'type'        => '',
			'orderby'     => 'created_at',
			'order'       => 'DESC',
			'limit'       => 50,
			'offset'      => 0,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = FWA_Helpers::get_schedules_table();

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

		if ( '' !== $args['type'] ) {
			$where[]  = 'type = %s';
			$values[] = sanitize_text_field( $args['type'] );
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

		$allowed_orderby = array( 'id', 'name', 'status', 'type', 'next_run_at', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$schedules = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		return array(
			'schedules' => $schedules,
			'total'     => $total,
		);
	}

	/*--------------------------------------------------------------
	 * Status helpers
	 *------------------------------------------------------------*/

	/**
	 * Pause an active schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Schedule ID.
	 * @return true|WP_Error
	 */
	public function pause( $id ) {
		return $this->update( absint( $id ), array( 'status' => 'paused' ) );
	}

	/**
	 * Resume a paused schedule.
	 *
	 * Recalculates the next run time based on the recurrence rule so the
	 * schedule fires at the correct interval from now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Schedule ID.
	 * @return true|WP_Error
	 */
	public function resume( $id ) {
		$id       = absint( $id );
		$schedule = $this->get( $id );

		if ( ! $schedule ) {
			return new WP_Error( 'fwa_not_found', __( 'Schedule not found.', 'flexi-whatsapp-automation' ) );
		}

		$data = array( 'status' => 'active' );

		// Recalculate next run for recurring schedules.
		if ( 'recurring' === $schedule->type && ! empty( $schedule->recurrence_rule ) ) {
			$data['next_run_at'] = $this->calculate_next_run( $schedule->recurrence_rule );
		}

		return $this->update( $id, $data );
	}

	/*--------------------------------------------------------------
	 * Queue processing
	 *------------------------------------------------------------*/

	/**
	 * Process due schedules (called by WP-Cron every minute).
	 *
	 * Uses a transient lock to prevent overlapping runs. For each due
	 * schedule the message is dispatched, counters are updated, and the
	 * status/next-run time is adjusted.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function process() {
		// License guard — skip schedule processing without a valid license.
		if ( class_exists( 'FWA_License_Guard' ) && ! FWA_License_Guard::is_licensed() ) {
			return;
		}

		// Prevent concurrent processing.
		if ( get_transient( 'fwa_queue_lock' ) ) {
			return;
		}
		set_transient( 'fwa_queue_lock', true, 60 );

		global $wpdb;

		$table = FWA_Helpers::get_schedules_table();
		$now   = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$due = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status = %s AND next_run_at <= %s ORDER BY next_run_at ASC",
			'active',
			$now
		) );

		if ( empty( $due ) ) {
			delete_transient( 'fwa_queue_lock' );
			return;
		}

		$sender          = new FWA_Message_Sender();
		$processed_count = 0;

		foreach ( $due as $schedule ) {
			$send_args = array(
				'to'          => $schedule->recipient,
				'type'        => $schedule->message_type,
				'content'     => $schedule->content,
				'instance_id' => $schedule->instance_id,
				'schedule_id' => $schedule->id,
			);

			if ( ! empty( $schedule->media_url ) ) {
				$send_args['media_url'] = $schedule->media_url;
			}

			$result = $sender->send( $send_args );

			if ( is_wp_error( $result ) ) {
				$retry = (int) $schedule->retry_count + 1;
				$data  = array( 'retry_count' => $retry );

				if ( $retry >= 3 ) {
					$data['status'] = 'failed';
				}

				$this->update( $schedule->id, $data );
				continue;
			}

			// Successful send.
			$run_count = (int) $schedule->run_count + 1;
			$update    = array(
				'run_count'   => $run_count,
				'last_run_at' => current_time( 'mysql' ),
				'retry_count' => 0,
			);

			if ( 'one_time' === $schedule->type ) {
				$update['status'] = 'completed';
			} elseif ( 'recurring' === $schedule->type ) {
				$max_runs = (int) $schedule->max_runs;
				if ( $max_runs > 0 && $run_count >= $max_runs ) {
					$update['status'] = 'completed';
				} else {
					$update['next_run_at'] = $this->calculate_next_run( $schedule->recurrence_rule );
				}
			}

			$this->update( $schedule->id, $update );
			++$processed_count;
		}

		delete_transient( 'fwa_queue_lock' );

		/**
		 * Fires after the schedule queue has been processed.
		 *
		 * @since 1.0.0
		 *
		 * @param int $processed_count Number of schedules successfully processed.
		 */
		do_action( 'fwa_queue_processed', $processed_count );
	}

	/*--------------------------------------------------------------
	 * Recurrence helpers
	 *------------------------------------------------------------*/

	/**
	 * Calculate the next run datetime for a recurrence rule.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $recurrence_rule Recurrence keyword.
	 * @param string|null $from_time       Base datetime. Default current time.
	 * @return string MySQL-formatted datetime string.
	 */
	public function calculate_next_run( $recurrence_rule, $from_time = null ) {
		$from = $from_time ? new DateTime( $from_time, wp_timezone() ) : current_datetime();

		$intervals = array(
			'every_minute'     => '+1 minute',
			'every_5_minutes'  => '+5 minutes',
			'every_15_minutes' => '+15 minutes',
			'every_30_minutes' => '+30 minutes',
			'hourly'           => '+1 hour',
			'every_2_hours'    => '+2 hours',
			'every_6_hours'    => '+6 hours',
			'every_12_hours'   => '+12 hours',
			'daily'            => '+1 day',
			'weekly'           => '+1 week',
			'monthly'          => '+1 month',
		);

		if ( isset( $intervals[ $recurrence_rule ] ) ) {
			$from->modify( $intervals[ $recurrence_rule ] );
		} else {
			// Fall back to daily for unrecognised rules.
			$from->modify( '+1 day' );
		}

		$next_run = $from->format( 'Y-m-d H:i:s' );

		/**
		 * Filter the calculated next-run datetime.
		 *
		 * Allows custom recurrence rules to override the result.
		 *
		 * @since 1.0.0
		 *
		 * @param string $next_run        MySQL datetime of next execution.
		 * @param string $recurrence_rule The recurrence keyword.
		 * @param string $from_time       The base time used for calculation.
		 */
		return apply_filters( 'fwa_schedule_next_run', $next_run, $recurrence_rule, $from_time );
	}

	/*--------------------------------------------------------------
	 * Statistics
	 *------------------------------------------------------------*/

	/**
	 * Count active schedules that are overdue (next_run_at in the past).
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_pending_count() {
		global $wpdb;

		$table = FWA_Helpers::get_schedules_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE status = %s AND next_run_at <= %s",
			'active',
			current_time( 'mysql' )
		) );
	}

	/**
	 * Get schedule counts grouped by status.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     @type int $active    Active schedules.
	 *     @type int $paused    Paused schedules.
	 *     @type int $completed Completed schedules.
	 *     @type int $failed    Failed schedules.
	 * }
	 */
	public function get_stats() {
		global $wpdb;

		$table = FWA_Helpers::get_schedules_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status",
			OBJECT_K
		);

		return array(
			'active'    => isset( $rows['active'] ) ? (int) $rows['active']->cnt : 0,
			'paused'    => isset( $rows['paused'] ) ? (int) $rows['paused']->cnt : 0,
			'completed' => isset( $rows['completed'] ) ? (int) $rows['completed']->cnt : 0,
			'failed'    => isset( $rows['failed'] ) ? (int) $rows['failed']->cnt : 0,
		);
	}
}
