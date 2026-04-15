<?php
/**
 * Contact management system.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Contact_Manager
 *
 * Manages contacts — CRUD, import/export, tagging, subscription,
 * and WooCommerce synchronization.
 *
 * @since 1.0.0
 */
class FWA_Contact_Manager {

	/**
	 * Constructor.
	 *
	 * No hooks needed — this is a service class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {}

	/*--------------------------------------------------------------
	 * CRUD
	 *------------------------------------------------------------*/

	/**
	 * Create a new contact.
	 *
	 * Performs an insert-or-update when a contact with the same phone
	 * and instance already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Contact data.
	 *
	 *     @type string $phone       Required. Phone number.
	 *     @type string $name        Optional. Contact name.
	 *     @type string $email       Optional. Email address.
	 *     @type int    $instance_id Optional. Associated instance DB ID.
	 *     @type int    $wp_user_id  Optional. WordPress user ID.
	 *     @type string $tags        Optional. Comma-separated tags.
	 *     @type string $notes       Optional. Freeform notes.
	 *     @type string $meta        Optional. JSON metadata.
	 * }
	 * @return int|WP_Error Contact DB ID on success, WP_Error on failure.
	 */
	public function create( $args ) {
		global $wpdb;

		if ( empty( $args['phone'] ) ) {
			return new WP_Error( 'fwa_missing_phone', __( 'Phone number is required.', 'flexi-whatsapp-automation' ) );
		}

		$args['phone'] = FWA_Helpers::sanitize_phone( $args['phone'] );

		if ( empty( $args['phone'] ) ) {
			return new WP_Error( 'fwa_invalid_phone', __( 'Invalid phone number.', 'flexi-whatsapp-automation' ) );
		}

		// Check for existing contact (phone + instance_id is unique).
		$instance_id = isset( $args['instance_id'] ) ? absint( $args['instance_id'] ) : null;
		$existing    = $this->get_by_phone( $args['phone'], $instance_id );

		if ( $existing ) {
			// Update the existing contact instead.
			$update_args = $args;
			unset( $update_args['phone'] );
			$this->update( $existing->id, $update_args );
			return (int) $existing->id;
		}

		$defaults = array(
			'phone'         => '',
			'name'          => '',
			'email'         => '',
			'instance_id'   => null,
			'wp_user_id'    => null,
			'tags'          => '',
			'notes'         => '',
			'is_subscribed' => 1,
			'meta'          => '',
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $args, $defaults );
		$data = array_intersect_key( $data, $defaults );

		$data['name']       = sanitize_text_field( $data['name'] );
		$data['email']      = sanitize_email( $data['email'] );
		$data['tags']       = sanitize_text_field( $data['tags'] );
		$data['wp_user_id'] = $data['wp_user_id'] ? absint( $data['wp_user_id'] ) : null;

		if ( null !== $data['instance_id'] ) {
			$data['instance_id'] = absint( $data['instance_id'] );
		}

		$table  = FWA_Helpers::get_contacts_table();
		$result = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_insert_failed', __( 'Failed to create contact.', 'flexi-whatsapp-automation' ) );
		}

		$id = (int) $wpdb->insert_id;

		/**
		 * Fires after a contact is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Contact ID.
		 * @param array $args Original arguments.
		 */
		do_action( 'fwa_contact_created', $id, $args );

		return $id;
	}

	/**
	 * Update an existing contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Contact ID.
	 * @param array $args Columns to update.
	 * @return true|WP_Error
	 */
	public function update( $id, $args ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid contact ID.', 'flexi-whatsapp-automation' ) );
		}

		$allowed = array(
			'phone', 'name', 'email', 'instance_id', 'wp_user_id',
			'tags', 'notes', 'is_subscribed', 'meta', 'updated_at',
		);

		$data = array_intersect_key( $args, array_flip( $allowed ) );

		if ( empty( $data ) ) {
			return new WP_Error( 'fwa_nothing_to_update', __( 'No valid fields to update.', 'flexi-whatsapp-automation' ) );
		}

		if ( isset( $data['phone'] ) ) {
			$data['phone'] = FWA_Helpers::sanitize_phone( $data['phone'] );
		}
		if ( isset( $data['name'] ) ) {
			$data['name'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['email'] ) ) {
			$data['email'] = sanitize_email( $data['email'] );
		}
		if ( isset( $data['tags'] ) ) {
			$data['tags'] = sanitize_text_field( $data['tags'] );
		}

		$data['updated_at'] = current_time( 'mysql' );

		$table  = FWA_Helpers::get_contacts_table();
		$result = $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_update_failed', __( 'Failed to update contact.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a contact is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id   Contact ID.
		 * @param array $args Updated fields.
		 */
		do_action( 'fwa_contact_updated', $id, $args );

		return true;
	}

	/**
	 * Delete a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Contact ID.
	 * @return true|WP_Error
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'fwa_invalid_id', __( 'Invalid contact ID.', 'flexi-whatsapp-automation' ) );
		}

		$table  = FWA_Helpers::get_contacts_table();
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error( 'fwa_db_delete_failed', __( 'Failed to delete contact.', 'flexi-whatsapp-automation' ) );
		}

		/**
		 * Fires after a contact is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Deleted contact ID.
		 */
		do_action( 'fwa_contact_deleted', $id );

		return true;
	}

	/*--------------------------------------------------------------
	 * Retrieval
	 *------------------------------------------------------------*/

	/**
	 * Get a single contact by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Contact ID.
	 * @return object|null Database row or null.
	 */
	public function get( $id ) {
		global $wpdb;

		$table = FWA_Helpers::get_contacts_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) )
		);
	}

	/**
	 * Find a contact by phone number.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $phone       Phone number.
	 * @param int|null $instance_id Optional instance DB ID.
	 * @return object|null Database row or null.
	 */
	public function get_by_phone( $phone, $instance_id = null ) {
		global $wpdb;

		$table = FWA_Helpers::get_contacts_table();
		$phone = FWA_Helpers::sanitize_phone( $phone );

		if ( null !== $instance_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE phone = %s AND instance_id = %d LIMIT 1",
				$phone,
				absint( $instance_id )
			) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE phone = %s LIMIT 1",
			$phone
		) );
	}

	/**
	 * Retrieve contacts with optional filtering and pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $instance_id   Filter by instance.
	 *     @type int    $is_subscribed Filter by subscription (0 or 1).
	 *     @type string $tags          Tag to search (LIKE match).
	 *     @type string $search        Search by name or phone (LIKE match).
	 *     @type string $orderby       Column to order by. Default 'created_at'.
	 *     @type string $order         ASC or DESC. Default 'DESC'.
	 *     @type int    $limit         Rows to return. Default 50.
	 *     @type int    $offset        Offset for pagination. Default 0.
	 * }
	 * @return array {
	 *     @type array $contacts Array of contact row objects.
	 *     @type int   $total    Total matching rows.
	 * }
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'instance_id'   => null,
			'is_subscribed' => null,
			'tags'          => '',
			'search'        => '',
			'orderby'       => 'created_at',
			'order'         => 'DESC',
			'limit'         => 50,
			'offset'        => 0,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = FWA_Helpers::get_contacts_table();

		$where  = array();
		$values = array();

		if ( null !== $args['instance_id'] ) {
			$where[]  = 'instance_id = %d';
			$values[] = absint( $args['instance_id'] );
		}

		if ( null !== $args['is_subscribed'] ) {
			$where[]  = 'is_subscribed = %d';
			$values[] = absint( $args['is_subscribed'] );
		}

		if ( '' !== $args['tags'] ) {
			$where[]  = 'tags LIKE %s';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['tags'] ) ) . '%';
		}

		if ( '' !== $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(name LIKE %s OR phone LIKE %s)';
			$values[] = $like;
			$values[] = $like;
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

		$allowed_orderby = array( 'id', 'name', 'phone', 'email', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$contacts = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		return array(
			'contacts' => $contacts,
			'total'    => $total,
		);
	}

	/*--------------------------------------------------------------
	 * Import / Export
	 *------------------------------------------------------------*/

	/**
	 * Import contacts from a CSV file.
	 *
	 * Expected columns: phone, name, email, tags.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_path Absolute path to the CSV file.
	 * @return array|WP_Error {
	 *     @type int $imported Number of contacts imported.
	 *     @type int $skipped  Number of rows skipped.
	 *     @type int $errors   Number of rows with errors.
	 * }
	 */
	public function import_from_csv( $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new WP_Error( 'fwa_file_not_found', __( 'CSV file not found or not readable.', 'flexi-whatsapp-automation' ) );
		}

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return new WP_Error( 'fwa_file_open_failed', __( 'Failed to open CSV file.', 'flexi-whatsapp-automation' ) );
		}

		$header   = fgetcsv( $handle );
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		if ( ! $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'fwa_empty_csv', __( 'CSV file is empty.', 'flexi-whatsapp-automation' ) );
		}

		// Normalize header column names.
		$header = array_map( 'strtolower', array_map( 'trim', $header ) );

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$data = array_combine( $header, array_pad( $row, count( $header ), '' ) );

			if ( empty( $data['phone'] ) ) {
				$skipped++;
				continue;
			}

			$contact_args = array(
				'phone' => $data['phone'],
				'name'  => isset( $data['name'] ) ? $data['name'] : '',
				'email' => isset( $data['email'] ) ? $data['email'] : '',
				'tags'  => isset( $data['tags'] ) ? $data['tags'] : '',
			);

			$result = $this->create( $contact_args );

			if ( is_wp_error( $result ) ) {
				$errors++;
			} else {
				$imported++;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$count = array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);

		/**
		 * Fires after contacts are imported from CSV.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $count     Import statistics.
		 * @param string $file_path CSV file path.
		 */
		do_action( 'fwa_contacts_imported', $count, $file_path );

		return $count;
	}

	/**
	 * Export contacts to CSV format.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional filtering arguments (passed to get_all).
	 * @return string CSV content.
	 */
	public function export_to_csv( $args = array() ) {
		$args['limit'] = 99999;
		$result        = $this->get_all( $args );
		$contacts      = $result['contacts'];

		$output = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// Header row.
		fputcsv( $output, array( 'phone', 'name', 'email', 'tags', 'is_subscribed', 'created_at' ) );

		foreach ( $contacts as $contact ) {
			fputcsv( $output, array(
				$contact->phone,
				$contact->name,
				$contact->email,
				$contact->tags,
				$contact->is_subscribed,
				$contact->created_at,
			) );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $csv;
	}

	/*--------------------------------------------------------------
	 * Subscription
	 *------------------------------------------------------------*/

	/**
	 * Subscribe a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Contact ID.
	 * @return true|WP_Error
	 */
	public function subscribe( $id ) {
		$result = $this->update( absint( $id ), array( 'is_subscribed' => 1 ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a contact is subscribed.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Contact ID.
		 */
		do_action( 'fwa_contact_subscribed', absint( $id ) );

		return true;
	}

	/**
	 * Unsubscribe a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Contact ID.
	 * @return true|WP_Error
	 */
	public function unsubscribe( $id ) {
		$result = $this->update( absint( $id ), array( 'is_subscribed' => 0 ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a contact is unsubscribed.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id Contact ID.
		 */
		do_action( 'fwa_contact_unsubscribed', absint( $id ) );

		return true;
	}

	/*--------------------------------------------------------------
	 * Tagging
	 *------------------------------------------------------------*/

	/**
	 * Append tags to a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $id   Contact ID.
	 * @param string|array $tags Tags to add (comma-separated string or array).
	 * @return true|WP_Error
	 */
	public function add_tags( $id, $tags ) {
		$contact = $this->get( absint( $id ) );
		if ( ! $contact ) {
			return new WP_Error( 'fwa_not_found', __( 'Contact not found.', 'flexi-whatsapp-automation' ) );
		}

		$existing = array_filter( array_map( 'trim', explode( ',', $contact->tags ) ) );
		$new      = is_array( $tags ) ? $tags : array_filter( array_map( 'trim', explode( ',', $tags ) ) );
		$merged   = array_unique( array_merge( $existing, $new ) );

		return $this->update( $contact->id, array( 'tags' => implode( ',', $merged ) ) );
	}

	/**
	 * Remove tags from a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $id   Contact ID.
	 * @param string|array $tags Tags to remove (comma-separated string or array).
	 * @return true|WP_Error
	 */
	public function remove_tags( $id, $tags ) {
		$contact = $this->get( absint( $id ) );
		if ( ! $contact ) {
			return new WP_Error( 'fwa_not_found', __( 'Contact not found.', 'flexi-whatsapp-automation' ) );
		}

		$existing = array_filter( array_map( 'trim', explode( ',', $contact->tags ) ) );
		$remove   = is_array( $tags ) ? $tags : array_filter( array_map( 'trim', explode( ',', $tags ) ) );
		$filtered = array_diff( $existing, $remove );

		return $this->update( $contact->id, array( 'tags' => implode( ',', $filtered ) ) );
	}

	/*--------------------------------------------------------------
	 * Counts / lookups
	 *------------------------------------------------------------*/

	/**
	 * Get total contacts count.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_count() {
		global $wpdb;

		$table = FWA_Helpers::get_contacts_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Find a contact by WordPress user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return object|null Database row or null.
	 */
	public function get_by_wp_user( $user_id ) {
		global $wpdb;

		$table = FWA_Helpers::get_contacts_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE wp_user_id = %d LIMIT 1",
			absint( $user_id )
		) );
	}

	/*--------------------------------------------------------------
	 * WooCommerce sync
	 *------------------------------------------------------------*/

	/**
	 * Create contacts from WooCommerce customer data.
	 *
	 * Imports billing phone, email, and name from existing customers.
	 * Skips contacts that already exist.
	 *
	 * @since 1.0.0
	 *
	 * @return int|WP_Error Number of contacts synced, or WP_Error on failure.
	 */
	public function sync_from_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'fwa_woocommerce_missing', __( 'WooCommerce is not active.', 'flexi-whatsapp-automation' ) );
		}

		$customers = get_users( array(
			'role'    => 'customer',
			'number'  => -1,
			'fields'  => 'ID',
		) );

		$synced = 0;

		foreach ( $customers as $user_id ) {
			$phone = get_user_meta( $user_id, 'billing_phone', true );

			if ( empty( $phone ) ) {
				continue;
			}

			$phone = FWA_Helpers::sanitize_phone( $phone );
			if ( empty( $phone ) ) {
				continue;
			}

			// Skip if already exists.
			$existing = $this->get_by_phone( $phone );
			if ( $existing ) {
				continue;
			}

			$name  = get_user_meta( $user_id, 'billing_first_name', true ) . ' ' . get_user_meta( $user_id, 'billing_last_name', true );
			$email = get_user_meta( $user_id, 'billing_email', true );

			$result = $this->create( array(
				'phone'      => $phone,
				'name'       => trim( $name ),
				'email'      => $email,
				'wp_user_id' => (int) $user_id,
				'tags'       => 'woocommerce',
			) );

			if ( ! is_wp_error( $result ) ) {
				$synced++;
			}
		}

		/**
		 * Fires after contacts are synced from WooCommerce.
		 *
		 * @since 1.0.0
		 *
		 * @param int $synced Number of contacts synced.
		 */
		do_action( 'fwa_contacts_synced_woocommerce', $synced );

		return $synced;
	}
}
