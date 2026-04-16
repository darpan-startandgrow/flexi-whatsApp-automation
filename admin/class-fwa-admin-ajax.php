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
			'fwa_save_instance',
			'fwa_delete_instance',
			'fwa_connect_instance',
			'fwa_disconnect_instance',
			'fwa_set_active_instance',
			'fwa_get_qr_code',
			'fwa_get_instance_status',
			'fwa_check_instance_status',
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
			'fwa_sync_woo_contacts',
			// Automation.
			'fwa_get_automation_rules',
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
			// Onboarding.
			'fwa_complete_onboarding',
			// OTP.
			'fwa_send_otp',
			'fwa_verify_otp',
			// Instances – pairing code.
			'fwa_get_pairing_code',
			// Campaigns – resume + analytics.
			'fwa_resume_campaign',
			'fwa_get_campaign_stats',
			// Messages – resend.
			'fwa_resend_message',
			// Contacts – subscription toggle + WA check.
			'fwa_toggle_contact_subscription',
			'fwa_check_wa_number',
			// Widget settings.
			'fwa_save_widget_settings',
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
			'instance_id'  => isset( $_POST['instance_id'] ) ? sanitize_text_field( wp_unslash( $_POST['instance_id'] ) ) : '',
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			// Accept 'access_token' (new) or legacy 'token'.
			'access_token' => isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) )
				: ( isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '' ),
			'phone_number' => isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '',
		);

		$manager = new FWA_Instance_Manager();
		$result  = $manager->create( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'id' => $result,
		) );
	}

	/**
	 * Handle saving (create or update) an instance from the Instances page form.
	 *
	 * @since 1.1.0
	 */
	public function handle_save_instance() {
		$this->verify_request();

		$db_id = isset( $_POST['instance_db_id'] ) ? absint( $_POST['instance_db_id'] ) : 0;

		$args = array(
			'instance_id'  => isset( $_POST['instance_id'] ) ? sanitize_text_field( wp_unslash( $_POST['instance_id'] ) ) : '',
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'access_token' => isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '',
			'phone_number' => isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '',
		);

		$manager = new FWA_Instance_Manager();

		if ( $db_id ) {
			$result = $manager->update( $db_id, $args );
		} else {
			$result = $manager->create( $args );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'id'      => $db_id ? $db_id : $result,
			'message' => __( 'Instance saved.', 'flexi-whatsapp-automation' ),
		) );
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

		$api    = new FWA_API_Client( $instance->instance_id, $instance->access_token );
		$result = $api->getQRCode( $instance->instance_id );

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
			'instances'       => array(
				'total'     => is_array( $all_instances ) ? count( $all_instances ) : 0,
				'connected' => $connected,
				'by_status' => $status_counts,
			),
			'instances_list'  => is_array( $all_instances ) ? array_values( $all_instances ) : array(),
			'messages'        => array(
				'today_sent'     => $msg_today_sent,
				'today_received' => $msg_today_received,
				'total'          => $total_msgs,
			),
			'campaigns'       => array(
				'active'    => $active_camps,
				'completed' => $completed_camps,
			),
			'contacts'        => array(
				'total'      => $contact_count,
				'subscribed' => $subscribed,
			),
			'schedules'       => array(
				'active'  => isset( $sched_stats['active'] ) ? $sched_stats['active'] : 0,
				'pending' => $scheduler->get_pending_count(),
			),
			'logs'            => array(
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
			'fwa_webhook_signature_mode',
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
			// Feature toggles.
			'fwa_enable_automation',
			'fwa_enable_logging',
			'fwa_enable_campaigns',
			// OTP settings.
			'fwa_otp_enabled',
			'fwa_otp_role_redirects',
			'fwa_otp_default_redirect',
			'fwa_otp_welcome_message',
			'fwa_otp_admin_alert_phone',
			'fwa_otp_admin_alert_events',
			'fwa_otp_phone_blacklist',
			'fwa_otp_wc_checkout',
			// Global admin alert phone (used by automation rules).
			'fwa_admin_alert_phone',
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

		// WooCommerce per-status notification settings.
		if ( isset( $_POST['fwa_wc_notifications'] ) && is_array( $_POST['fwa_wc_notifications'] ) ) {
			$wc_raw            = wp_unslash( $_POST['fwa_wc_notifications'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$allowed_statuses  = array( 'processing', 'completed', 'on-hold', 'cancelled', 'refunded', 'failed' );
			$wc_notifications  = array();

			foreach ( $allowed_statuses as $status_key ) {
				if ( isset( $wc_raw[ $status_key ] ) && is_array( $wc_raw[ $status_key ] ) ) {
					$wc_notifications[ $status_key ] = array(
						'enabled' => ! empty( $wc_raw[ $status_key ]['enabled'] ),
						'message' => isset( $wc_raw[ $status_key ]['message'] ) ? sanitize_textarea_field( $wc_raw[ $status_key ]['message'] ) : '',
					);
				} else {
					$wc_notifications[ $status_key ] = array(
						'enabled' => false,
						'message' => '',
					);
				}
			}

			update_option( 'fwa_wc_notifications', $wc_notifications );
		}

		// WooCommerce admin order alert toggle.
		if ( isset( $_POST['fwa_wc_admin_order_alert'] ) ) {
			update_option( 'fwa_wc_admin_order_alert', 'yes' );
		} elseif ( isset( $_POST['tab'] ) && 'woocommerce' === sanitize_text_field( wp_unslash( $_POST['tab'] ) ) ) {
			update_option( 'fwa_wc_admin_order_alert', 'no' );
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'flexi-whatsapp-automation' ) ) );
	}

	// =========================================================================
	// Onboarding handler
	// =========================================================================

	/**
	 * Mark the onboarding wizard as complete.
	 *
	 * @since 1.0.0
	 */
	public function handle_complete_onboarding() {
		check_ajax_referer( 'fwa_admin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'flexi-whatsapp-automation' ) ) );
		}

		update_option( 'fwa_onboarding_complete', 'yes' );
		wp_send_json_success( array( 'message' => __( 'Onboarding complete.', 'flexi-whatsapp-automation' ) ) );
	}

	// =========================================================================
	// Additional handlers for contacts & automation
	// =========================================================================

	/**
	 * Handle syncing contacts from WooCommerce.
	 *
	 * @since 1.0.0
	 */
	public function handle_sync_woo_contacts() {
		$this->verify_request();

		if ( ! class_exists( 'FWA_Contact_Manager' ) ) {
			wp_send_json_error( array( 'message' => __( 'Contact manager not available.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Contact_Manager();

		if ( ! method_exists( $manager, 'sync_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce sync not supported.', 'flexi-whatsapp-automation' ) ) );
		}

		$count = $manager->sync_woocommerce();

		wp_send_json_success( array(
			'count'   => $count,
			'message' => sprintf(
				/* translators: %d: number of contacts synced */
				__( 'Synced %d contacts from WooCommerce.', 'flexi-whatsapp-automation' ),
				$count
			),
		) );
	}

	/**
	 * Handle retrieving automation rules.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_automation_rules() {
		$this->verify_request();

		if ( ! class_exists( 'FWA_Automation_Engine' ) ) {
			wp_send_json_error( array( 'message' => __( 'Automation engine not available.', 'flexi-whatsapp-automation' ) ) );
		}

		$engine = new FWA_Automation_Engine();

		if ( method_exists( $engine, 'get_rules' ) ) {
			$rules = $engine->get_rules();
		} else {
			$rules = get_option( 'fwa_automation_rules', array() );
		}

		wp_send_json_success( array( 'rules' => $rules ) );
	}

	/**
	 * Handle checking an instance's connection status.
	 *
	 * @since 1.0.0
	 */
	public function handle_check_instance_status() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Instance ID is required.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager  = new FWA_Instance_Manager();
		$instance = $manager->get( $id );

		if ( ! $instance ) {
			wp_send_json_error( array( 'message' => __( 'Instance not found.', 'flexi-whatsapp-automation' ) ) );
		}

		$status = isset( $instance->status ) ? $instance->status : 'disconnected';

		wp_send_json_success( array( 'status' => $status ) );
	}

	// =========================================================================
	// OTP handlers
	// =========================================================================

	/**
	 * Handle sending an OTP to a phone number via WhatsApp.
	 *
	 * @since 1.1.0
	 */
	public function handle_send_otp() {
		$this->verify_request();

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'flexi-whatsapp-automation' ) ) );
		}

		$otp_manager = new FWA_OTP_Manager();
		$result      = $otp_manager->send_otp( $phone );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Verification code sent to your WhatsApp. Please check your messages.', 'flexi-whatsapp-automation' ),
		) );
	}

	/**
	 * Handle verifying an OTP for a phone number.
	 *
	 * @since 1.1.0
	 */
	public function handle_verify_otp() {
		$this->verify_request();

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$otp   = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';

		if ( empty( $phone ) || empty( $otp ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number and OTP are required.', 'flexi-whatsapp-automation' ) ) );
		}

		$otp_manager = new FWA_OTP_Manager();
		$result      = $otp_manager->verify_otp( $phone, $otp );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Phone number verified successfully!', 'flexi-whatsapp-automation' ),
		) );
	}

	// =========================================================================
	// Pairing code handler
	// =========================================================================

	/**
	 * Get a pairing code for a WhatsApp instance.
	 *
	 * @since 1.2.0
	 */
	public function handle_get_pairing_code() {
		$this->verify_request();

		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid instance ID.', 'flexi-whatsapp-automation' ) ) );
		}

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required for pairing code.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager  = new FWA_Instance_Manager();
		$instance = $manager->get( $id );

		if ( ! $instance ) {
			wp_send_json_error( array( 'message' => __( 'Instance not found.', 'flexi-whatsapp-automation' ) ) );
		}

		$api    = new FWA_API_Client( $instance->instance_id, $instance->access_token );
		$result = $api->getPairingCode( $instance->instance_id, $phone );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$code = '';
		if ( is_array( $result ) && isset( $result['code'] ) ) {
			$code = $result['code'];
		} elseif ( is_string( $result ) ) {
			$code = $result;
		}

		wp_send_json_success( array( 'code' => sanitize_text_field( $code ) ) );
	}

	// =========================================================================
	// Campaign resume + analytics handlers
	// =========================================================================

	/**
	 * Handle resuming a paused campaign.
	 *
	 * @since 1.2.0
	 */
	public function handle_resume_campaign() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager = new FWA_Campaign_Manager();
		$result  = $manager->resume( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Campaign resumed.', 'flexi-whatsapp-automation' ) ) );
	}

	/**
	 * Handle getting campaign analytics (delivery stats).
	 *
	 * @since 1.2.0
	 */
	public function handle_get_campaign_stats() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'flexi-whatsapp-automation' ) ) );
		}

		$manager  = new FWA_Campaign_Manager();
		$campaign = $manager->get( $id );

		if ( ! $campaign ) {
			wp_send_json_error( array( 'message' => __( 'Campaign not found.', 'flexi-whatsapp-automation' ) ) );
		}

		// Compute per-status counts from campaign_logs.
		global $wpdb;
		$logs_table = FWA_Helpers::get_campaign_logs_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT status, COUNT(*) AS cnt FROM {$logs_table} WHERE campaign_id = %d GROUP BY status",
			$id
		) );

		$by_status = array(
			'pending'   => 0,
			'sent'      => 0,
			'delivered' => 0,
			'read'      => 0,
			'failed'    => 0,
		);

		foreach ( $rows as $row ) {
			if ( isset( $by_status[ $row->status ] ) ) {
				$by_status[ $row->status ] = (int) $row->cnt;
			}
		}

		wp_send_json_success( array(
			'campaign'  => $campaign,
			'by_status' => $by_status,
		) );
	}

	// =========================================================================
	// Resend message handler
	// =========================================================================

	/**
	 * Re-send a failed outgoing message.
	 *
	 * @since 1.2.0
	 */
	public function handle_resend_message() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid message ID.', 'flexi-whatsapp-automation' ) ) );
		}

		global $wpdb;
		$table = FWA_Helpers::get_messages_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$msg = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
			$id
		) );

		if ( ! $msg ) {
			wp_send_json_error( array( 'message' => __( 'Message not found.', 'flexi-whatsapp-automation' ) ) );
		}

		if ( 'outgoing' !== $msg->direction ) {
			wp_send_json_error( array( 'message' => __( 'Only outgoing messages can be resent.', 'flexi-whatsapp-automation' ) ) );
		}

		$sender = new FWA_Message_Sender();

		if ( 'text' === $msg->message_type ) {
			$result = $sender->send_text( $msg->recipient, $msg->content, $msg->instance_id );
		} else {
			$result = $sender->send_media(
				$msg->recipient,
				$msg->message_type,
				$msg->media_url,
				$msg->media_caption,
				$msg->instance_id
			);
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'    => __( 'Message resent successfully.', 'flexi-whatsapp-automation' ),
			'message_id' => $result,
		) );
	}

	// =========================================================================
	// Contact subscription toggle + WhatsApp number check
	// =========================================================================

	/**
	 * Toggle a contact's subscription status (opt-in / opt-out).
	 *
	 * @since 1.2.0
	 */
	public function handle_toggle_contact_subscription() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid contact ID.', 'flexi-whatsapp-automation' ) ) );
		}

		global $wpdb;
		$table = FWA_Helpers::get_contacts_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$contact = $wpdb->get_row( $wpdb->prepare( "SELECT id, is_subscribed FROM {$table} WHERE id = %d LIMIT 1", $id ) );

		if ( ! $contact ) {
			wp_send_json_error( array( 'message' => __( 'Contact not found.', 'flexi-whatsapp-automation' ) ) );
		}

		$new_status = $contact->is_subscribed ? 0 : 1;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $table, array( 'is_subscribed' => $new_status ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

		wp_send_json_success( array(
			'is_subscribed' => $new_status,
			'message'       => $new_status
				? __( 'Contact opted in.', 'flexi-whatsapp-automation' )
				: __( 'Contact opted out.', 'flexi-whatsapp-automation' ),
		) );
	}

	/**
	 * Check whether a phone number is registered on WhatsApp.
	 *
	 * @since 1.2.0
	 */
	public function handle_check_wa_number() {
		$this->verify_request();

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'flexi-whatsapp-automation' ) ) );
		}

		$instance = FWA_Helpers::get_active_instance();

		if ( ! $instance ) {
			wp_send_json_error( array( 'message' => __( 'No active WhatsApp instance connected.', 'flexi-whatsapp-automation' ) ) );
		}

		$api    = new FWA_API_Client( $instance->instance_id, $instance->access_token );
		$result = $api->request( 'number/check', 'POST', array( 'phone' => $phone ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$exists = isset( $result['exists'] ) ? (bool) $result['exists'] : false;

		wp_send_json_success( array(
			'phone'  => $phone,
			'exists' => $exists,
		) );
	}

	// =========================================================================
	// Widget settings handler
	// =========================================================================

	/**
	 * Save chat widget settings.
	 *
	 * @since 1.2.0
	 */
	public function handle_save_widget_settings() {
		$this->verify_request();

		if ( ! isset( $_POST['widget'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No widget data provided.', 'flexi-whatsapp-automation' ) ) );
		}

		$raw = wp_unslash( $_POST['widget'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( is_string( $raw ) ) {
			$data = json_decode( $raw, true );
		} else {
			$data = (array) $raw;
		}

		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid widget data.', 'flexi-whatsapp-automation' ) ) );
		}

		// Sanitize known scalar fields.
		$sanitized = array();

		$text_fields = array(
			'enabled', 'position', 'button_text', 'button_color', 'text_color',
			'header_title', 'header_subtitle', 'welcome_message', 'display_delay',
			'show_on_mobile', 'show_on_desktop', 'display_pages', 'exclude_pages',
			'analytics', 'social_facebook', 'social_instagram', 'social_twitter',
			'social_linkedin', 'social_youtube', 'cta_message', 'show_desktop_qr',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		// Agents (array of arrays).
		if ( isset( $data['agents'] ) && is_array( $data['agents'] ) ) {
			$sanitized['agents'] = array();
			foreach ( $data['agents'] as $agent ) {
				if ( ! is_array( $agent ) ) {
					continue;
				}
				$sanitized['agents'][] = array(
					'name'            => isset( $agent['name'] ) ? sanitize_text_field( $agent['name'] ) : '',
					'phone'           => isset( $agent['phone'] ) ? sanitize_text_field( $agent['phone'] ) : '',
					'role'            => isset( $agent['role'] ) ? sanitize_text_field( $agent['role'] ) : '',
					'default_message' => isset( $agent['default_message'] ) ? sanitize_textarea_field( $agent['default_message'] ) : '',
					'avatar_url'      => isset( $agent['avatar_url'] ) ? esc_url_raw( $agent['avatar_url'] ) : '',
					'availability'    => isset( $agent['availability'] ) ? sanitize_text_field( $agent['availability'] ) : '',
				);
			}
		}

		update_option( 'fwa_chat_widget', $sanitized );

		wp_send_json_success( array( 'message' => __( 'Widget settings saved.', 'flexi-whatsapp-automation' ) ) );
	}
}
