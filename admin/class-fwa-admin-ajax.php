<?php
/**
 * Centralized admin AJAX handler.
 *
 * Registers and processes all admin AJAX actions.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Admin_AJAX
 *
 * @since 1.0.0
 */
class FWA_Admin_AJAX {

	/**
	 * Constructor — registers all AJAX actions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$actions = array(
			// Instances.
			'fwa_get_instances',
			'fwa_create_instance',
			'fwa_delete_instance',
			'fwa_connect_instance',
			'fwa_disconnect_instance',
			'fwa_set_active_instance',
			'fwa_get_qr_code',
			'fwa_get_instance_status',
			// Messages.
			'fwa_send_message',
			'fwa_get_messages',
			// Campaigns.
			'fwa_create_campaign',
			'fwa_start_campaign',
			'fwa_pause_campaign',
			'fwa_delete_campaign',
			// Contacts.
			'fwa_get_contacts',
			'fwa_create_contact',
			'fwa_delete_contact',
			'fwa_import_contacts',
			'fwa_export_contacts',
			// Automation.
			'fwa_save_automation_rules',
			// Schedules.
			'fwa_create_schedule',
			'fwa_delete_schedule',
			// Logs.
			'fwa_get_logs',
			'fwa_export_logs',
			'fwa_clear_logs',
			// Dashboard.
			'fwa_get_dashboard_stats',
			// Settings.
			'fwa_save_settings',
		);

		foreach ( $actions as $action ) {
			$method = 'handle_' . substr( $action, 4 ); // strip "fwa_" prefix.
			add_action( 'wp_ajax_' . $action, array( $this, $method ) );
		}
	}

	// =========================================================================
	// Security helpers
	// =========================================================================

	/**
	 * Verify nonce and capability.
	 *
	 * Sends a JSON error and dies on failure.
	 *
	 * @since 1.0.0
	 */
	private function verify_request() {
		check_ajax_referer( 'fwa_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to perform this action.', 'flexi-whatsapp-automation' ),
			) );
		}
	}

	// =========================================================================
	// Instance handlers
	// =========================================================================

	/**
	 * Handle retrieving all instances.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_instances() {
		$this->verify_request();

		$manager   = new FWA_Instance_Manager();
		$args      = array();

		if ( ! empty( $_POST['status'] ) ) {
			$args['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
		}

		$instances = $manager->get_all( $args );
		wp_send_json_success( array( 'instances' => $instances ) );
	}

	/**
	 * Handle creating an instance.
	 *
	 * @since 1.0.0
	 */
	public function handle_create_instance() {
		$this->verify_request();

		$args = array(
			'instance_id' => isset( $_POST['instance_id'] ) ? sanitize_text_field( wp_unslash( $_POST['instance_id'] ) ) : '',
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'token'       => isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '',
		);

		$manager = new FWA_Instance_Manager();
		$result  = $manager->create( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'instance' => $result ) );
	}

	/**
	 * Handle deleting an instance.
	 *
	 * @since 1.0.0
	 */
	public function handle_delete_instance() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Instance_Manager();
		$result  = $manager->delete( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Instance deleted.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle connecting an instance.
	 *
	 * @since 1.0.0
	 */
	public function handle_connect_instance() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Instance_Manager();
		$result  = $manager->connect( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Instance connected.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle disconnecting an instance.
	 *
	 * @since 1.0.0
	 */
	public function handle_disconnect_instance() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Instance_Manager();
		$result  = $manager->disconnect( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Instance disconnected.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle setting an instance as active.
	 *
	 * @since 1.0.0
	 */
	public function handle_set_active_instance() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Instance_Manager();
		$result  = $manager->set_active( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Active instance updated.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle retrieving QR code for an instance.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_qr_code() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager  = new FWA_Instance_Manager();
		$instance = $manager->get( $id );

		if ( ! $instance ) {
			wp_send_json_error( array( 'message' => __( 'Instance not found.', 'flexi-whatsapp-automation' ) ) );
		}

		$api    = new FWA_API_Client();
		$result = $api->get_qr_code( $instance->instance_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'qr' => $result ) );
	}

	/**
	 * Handle retrieving instance status.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_instance_status() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager  = new FWA_Instance_Manager();
		$instance = $manager->get( $id );

		if ( ! $instance ) {
			wp_send_json_error( array( 'message' => __( 'Instance not found.', 'flexi-whatsapp-automation' ) ) );
		}

		wp_send_json_success( array(
			'status' => $instance->status,
			'name'   => $instance->name,
		) );
	}

	// =========================================================================
	// Message handlers
	// =========================================================================

	/**
	 * Handle sending a message.
	 *
	 * @since 1.0.0
	 */
	public function handle_send_message() {
		$this->verify_request();

		$phone         = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$message       = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$message_type  = isset( $_POST['message_type'] ) ? sanitize_text_field( wp_unslash( $_POST['message_type'] ) ) : 'text';
		$instance_id   = isset( $_POST['instance_id'] ) ? absint( $_POST['instance_id'] ) : null;
		$media_url     = isset( $_POST['media_url'] ) ? esc_url_raw( wp_unslash( $_POST['media_url'] ) ) : '';
		$media_caption = isset( $_POST['media_caption'] ) ? sanitize_textarea_field( wp_unslash( $_POST['media_caption'] ) ) : '';

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'flexi-whatsapp-automation' ) ) );
		}

		$sender = new FWA_Message_Sender();

		if ( 'text' === $message_type ) {
			$result = $sender->send_text( $phone, $message, $instance_id );
		} else {
			$result = $sender->send_media( $phone, $message_type, $media_url, $media_caption, $instance_id );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'    => __( 'Message sent successfully.', 'flexi-whatsapp-automation' ),
			'message_id' => $result,
		) );
	}

	/**
	 * Handle retrieving messages.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_messages() {
		$this->verify_request();

		$args = array();

		if ( ! empty( $_POST['instance_id'] ) ) {
			$args['instance_id'] = absint( $_POST['instance_id'] );
		}
		if ( ! empty( $_POST['direction'] ) ) {
			$args['direction'] = sanitize_text_field( wp_unslash( $_POST['direction'] ) );
		}
		if ( ! empty( $_POST['per_page'] ) ) {
			$args['per_page'] = absint( $_POST['per_page'] );
		}
		if ( ! empty( $_POST['page'] ) ) {
			$args['page'] = absint( $_POST['page'] );
		}

		$sender   = new FWA_Message_Sender();
		$messages = $sender->get_messages( $args );

		wp_send_json_success( array( 'messages' => $messages ) );
	}

	// =========================================================================
	// Campaign handlers
	// =========================================================================

	/**
	 * Handle creating a campaign.
	 *
	 * @since 1.0.0
	 */
	public function handle_create_campaign() {
		$this->verify_request();

		$args = array(
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'message'     => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
			'instance_id' => isset( $_POST['instance_id'] ) ? absint( $_POST['instance_id'] ) : 0,
			'filter'      => isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '',
		);

		$manager = new FWA_Campaign_Manager();
		$result  = $manager->create( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'campaign' => $result ) );
	}

	/**
	 * Handle starting a campaign.
	 *
	 * @since 1.0.0
	 */
	public function handle_start_campaign() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Campaign_Manager();
		$result  = $manager->start( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Campaign started.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle pausing a campaign.
	 *
	 * @since 1.0.0
	 */
	public function handle_pause_campaign() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Campaign_Manager();
		$result  = $manager->pause( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Campaign paused.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle deleting a campaign.
	 *
	 * @since 1.0.0
	 */
	public function handle_delete_campaign() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Campaign_Manager();
		$result  = $manager->delete( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Campaign deleted.', 'flexi-whatsapp-automation' ) ) );
	}

	// =========================================================================
	// Contact handlers
	// =========================================================================

	/**
	 * Handle retrieving contacts.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_contacts() {
		$this->verify_request();

		$args = array();

		if ( ! empty( $_POST['per_page'] ) ) {
			$args['per_page'] = absint( $_POST['per_page'] );
		}
		if ( ! empty( $_POST['page'] ) ) {
			$args['page'] = absint( $_POST['page'] );
		}
		if ( ! empty( $_POST['search'] ) ) {
			$args['search'] = sanitize_text_field( wp_unslash( $_POST['search'] ) );
		}
		if ( ! empty( $_POST['tags'] ) ) {
			$args['tags'] = sanitize_text_field( wp_unslash( $_POST['tags'] ) );
		}

		$manager  = new FWA_Contact_Manager();
		$contacts = $manager->get_all( $args );

		wp_send_json_success( array( 'contacts' => $contacts ) );
	}

	/**
	 * Handle creating a contact.
	 *
	 * @since 1.0.0
	 */
	public function handle_create_contact() {
		$this->verify_request();

		$args = array(
			'name'  => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'phone' => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'email' => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'tags'  => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
		);

		$manager = new FWA_Contact_Manager();
		$result  = $manager->create( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'contact' => $result ) );
	}

	/**
	 * Handle deleting a contact.
	 *
	 * @since 1.0.0
	 */
	public function handle_delete_contact() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid contact ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Contact_Manager();
		$result  = $manager->delete( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Contact deleted.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle importing contacts from CSV.
	 *
	 * @since 1.0.0
	 */
	public function handle_import_contacts() {
		$this->verify_request();

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'flexi-whatsapp-automation' ) ) );
		}

		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$allowed_types = array( 'text/csv', 'application/vnd.ms-excel', 'text/plain' );
		if ( ! in_array( $file['type'], $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a CSV file.', 'flexi-whatsapp-automation' ) ) );
		}

		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file extension. Only .csv files are allowed.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Contact_Manager();
		$result  = $manager->import_from_csv( $file['tmp_name'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Contacts imported successfully.', 'flexi-whatsapp-automation' ),
			'stats'   => $result,
		) );
	}

	/**
	 * Handle exporting contacts.
	 *
	 * @since 1.0.0
	 */
	public function handle_export_contacts() {
		$this->verify_request();

		$args = array();

		if ( ! empty( $_POST['tags'] ) ) {
			$args['tags'] = sanitize_text_field( wp_unslash( $_POST['tags'] ) );
		}
		if ( ! empty( $_POST['subscribed'] ) ) {
			$args['subscribed'] = sanitize_text_field( wp_unslash( $_POST['subscribed'] ) );
		}

		$manager = new FWA_Contact_Manager();
		$result  = $manager->export_to_csv( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Export ready.', 'flexi-whatsapp-automation' ),
			'data'    => $result,
		) );
	}

	// =========================================================================
	// Automation handlers
	// =========================================================================

	/**
	 * Handle saving automation rules.
	 *
	 * @since 1.0.0
	 */
	public function handle_save_automation_rules() {
		$this->verify_request();

		$rules_raw = isset( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( is_string( $rules_raw ) ) {
			$rules = json_decode( $rules_raw, true );
		} else {
			$rules = $rules_raw;
		}

		if ( ! is_array( $rules ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid rules data.', 'flexi-whatsapp-automation' ) ) );
		}

		// Sanitize each rule.
		$sanitized = array();
		foreach ( $rules as $rule ) {
			$sanitized[] = array(
				'event'      => isset( $rule['event'] ) ? sanitize_text_field( $rule['event'] ) : '',
				'conditions' => isset( $rule['conditions'] ) ? array_map( 'sanitize_text_field', (array) $rule['conditions'] ) : array(),
				'template'   => isset( $rule['template'] ) ? sanitize_textarea_field( $rule['template'] ) : '',
				'enabled'    => ! empty( $rule['enabled'] ),
			);
		}

		$engine = new FWA_Automation_Engine();
		$result = $engine->save_rules( $sanitized );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Automation rules saved.', 'flexi-whatsapp-automation' ) ) );
	}

	// =========================================================================
	// Schedule handlers
	// =========================================================================

	/**
	 * Handle creating a schedule.
	 *
	 * @since 1.0.0
	 */
	public function handle_create_schedule() {
		$this->verify_request();

		$args = array(
			'name'            => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'message'         => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
			'instance_id'     => isset( $_POST['instance_id'] ) ? absint( $_POST['instance_id'] ) : 0,
			'recurrence_rule' => isset( $_POST['recurrence_rule'] ) ? sanitize_text_field( wp_unslash( $_POST['recurrence_rule'] ) ) : '',
			'scheduled_at'    => isset( $_POST['scheduled_at'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_at'] ) ) : '',
		);

		$scheduler = new FWA_Scheduler();
		$result    = $scheduler->create( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'schedule' => $result ) );
	}

	/**
	 * Handle deleting a schedule.
	 *
	 * @since 1.0.0
	 */
	public function handle_delete_schedule() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid schedule ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$scheduler = new FWA_Scheduler();
		$result    = $scheduler->delete( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Schedule deleted.', 'flexi-whatsapp-automation' ) ) );
	}

	// =========================================================================
	// Log handlers
	// =========================================================================

	/**
	 * Handle retrieving logs.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_logs() {
		$this->verify_request();

		$args = array();

		if ( ! empty( $_POST['level'] ) ) {
			$args['level'] = sanitize_text_field( wp_unslash( $_POST['level'] ) );
		}
		if ( ! empty( $_POST['source'] ) ) {
			$args['source'] = sanitize_text_field( wp_unslash( $_POST['source'] ) );
		}
		if ( ! empty( $_POST['per_page'] ) ) {
			$args['per_page'] = absint( $_POST['per_page'] );
		}
		if ( ! empty( $_POST['page'] ) ) {
			$args['page'] = absint( $_POST['page'] );
		}
		if ( ! empty( $_POST['date_from'] ) ) {
			$args['date_from'] = sanitize_text_field( wp_unslash( $_POST['date_from'] ) );
		}
		if ( ! empty( $_POST['date_to'] ) ) {
			$args['date_to'] = sanitize_text_field( wp_unslash( $_POST['date_to'] ) );
		}

		$logger = FWA_Logger::get_instance();
		$logs   = $logger->get_logs( $args );

		wp_send_json_success( array( 'logs' => $logs ) );
	}

	/**
	 * Handle exporting logs.
	 *
	 * @since 1.0.0
	 */
	public function handle_export_logs() {
		$this->verify_request();

		$args   = array();
		$format = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : 'csv';

		if ( ! empty( $_POST['level'] ) ) {
			$args['level'] = sanitize_text_field( wp_unslash( $_POST['level'] ) );
		}
		if ( ! empty( $_POST['date_from'] ) ) {
			$args['date_from'] = sanitize_text_field( wp_unslash( $_POST['date_from'] ) );
		}
		if ( ! empty( $_POST['date_to'] ) ) {
			$args['date_to'] = sanitize_text_field( wp_unslash( $_POST['date_to'] ) );
		}

		$logger = FWA_Logger::get_instance();
		$result = $logger->export_logs( $args, $format );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Logs exported.', 'flexi-whatsapp-automation' ),
			'data'    => $result,
		) );
	}

	/**
	 * Handle clearing logs.
	 *
	 * @since 1.0.0
	 */
	public function handle_clear_logs() {
		$this->verify_request();

		$args = array();

		if ( ! empty( $_POST['before_date'] ) ) {
			$args['before_date'] = sanitize_text_field( wp_unslash( $_POST['before_date'] ) );
		}

		$logger = FWA_Logger::get_instance();
		$result = $logger->delete_logs( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Logs cleared.', 'flexi-whatsapp-automation' ) ) );
	}

	// =========================================================================
	// Dashboard handler
	// =========================================================================

	/**
	 * Handle retrieving dashboard statistics.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_dashboard_stats() {
		$this->verify_request();

		$instance_mgr = new FWA_Instance_Manager();
		$msg_sender   = new FWA_Message_Sender();
		$campaign_mgr = new FWA_Campaign_Manager();
		$contact_mgr  = new FWA_Contact_Manager();
		$scheduler    = new FWA_Scheduler();
		$logger       = FWA_Logger::get_instance();

		$all_instances  = $instance_mgr->get_all();
		$status_counts  = $instance_mgr->get_count_by_status();
		$connected      = $instance_mgr->get_connected_count();

		$all_campaigns  = $campaign_mgr->get_all();
		$active_camps   = 0;
		$completed_camps = 0;
		foreach ( $all_campaigns as $c ) {
			if ( isset( $c->status ) ) {
				if ( 'running' === $c->status ) {
					$active_camps++;
				} elseif ( 'completed' === $c->status ) {
					$completed_camps++;
				}
			}
		}

		$contact_count = $contact_mgr->get_count();
		$subscribed    = 0;
		$all_contacts  = $contact_mgr->get_all( array( 'subscribed' => 'yes' ) );
		if ( is_array( $all_contacts ) ) {
			$subscribed = count( $all_contacts );
		}

		$sched_stats   = $scheduler->get_stats();
		$log_stats     = $logger->get_stats();

		$msg_today_sent     = $msg_sender->get_messages_count_today();
		$msg_today_received = 0;
		$received_today     = $msg_sender->get_messages( array(
			'direction' => 'inbound',
			'date_from' => gmdate( 'Y-m-d 00:00:00' ),
		) );
		if ( is_array( $received_today ) ) {
			$msg_today_received = count( $received_today );
		}

		$all_messages  = $msg_sender->get_messages( array( 'per_page' => 1 ) );
		$total_msgs    = is_array( $all_messages ) && isset( $all_messages['total'] ) ? $all_messages['total'] : 0;

		$stats = array(
			'instances'  => array(
				'total'     => is_array( $all_instances ) ? count( $all_instances ) : 0,
				'connected' => $connected,
				'by_status' => $status_counts,
			),
			'messages'   => array(
				'today_sent'     => $msg_today_sent,
				'today_received' => $msg_today_received,
				'total'          => $total_msgs,
			),
			'campaigns'  => array(
				'active'    => $active_camps,
				'completed' => $completed_camps,
			),
			'contacts'   => array(
				'total'      => $contact_count,
				'subscribed' => $subscribed,
			),
			'schedules'  => array(
				'active'  => isset( $sched_stats['active'] ) ? $sched_stats['active'] : 0,
				'pending' => $scheduler->get_pending_count(),
			),
			'logs'       => array(
				'today'        => isset( $log_stats['today'] ) ? $log_stats['today'] : 0,
				'errors_today' => isset( $log_stats['errors_today'] ) ? $log_stats['errors_today'] : 0,
			),
		);

		wp_send_json_success( array( 'stats' => $stats ) );
	}

	// =========================================================================
	// Settings handler
	// =========================================================================

	/**
	 * Handle saving plugin settings via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function handle_save_settings() {
		$this->verify_request();

		$settings_handler = new FWA_Admin_Settings();

		$fields = array(
			'fwa_api_base_url',
			'fwa_api_global_token',
			'fwa_webhook_secret',
			'fwa_rate_limit',
			'fwa_rate_limit_interval',
			'fwa_debug_mode',
			'fwa_file_logging',
			'fwa_log_level',
			'fwa_log_retention_days',
			'fwa_default_country_code',
			'fwa_campaign_message_delay',
			'fwa_auto_reconnect',
			'fwa_health_check_interval',
		);

		$input = array();
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$input[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		$sanitized = $settings_handler->sanitize_settings( $input );

		foreach ( $sanitized as $key => $value ) {
			update_option( $key, $value );
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'flexi-whatsapp-automation' ) ) );
	}
}
