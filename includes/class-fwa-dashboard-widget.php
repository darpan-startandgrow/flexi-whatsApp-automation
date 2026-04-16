<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Dashboard widget for WhatsApp Automation quick actions and status.
 *
 * @since 1.0.0
 */
class FWA_Dashboard_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_widget_scripts' ) );
		add_action( 'wp_ajax_fwa_quick_send', array( $this, 'handle_quick_send' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'fwa_dashboard_widget',
			'<span class="dashicons dashicons-format-chat"></span> ' . esc_html__( 'WhatsApp Automation', 'flexi-whatsapp-automation' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render the dashboard widget.
	 */
	public function render_widget() {
		$data = $this->get_widget_data();

		$connected  = isset( $data['connected_accounts'] ) ? (int) $data['connected_accounts'] : 0;
		$active     = isset( $data['active_sessions'] ) ? (int) $data['active_sessions'] : 0;
		$total_sess = isset( $data['total_sessions'] ) ? (int) $data['total_sessions'] : 0;
		$sent       = isset( $data['messages_sent_today'] ) ? (int) $data['messages_sent_today'] : 0;
		$received   = isset( $data['messages_received_today'] ) ? (int) $data['messages_received_today'] : 0;
		$pending    = isset( $data['pending_queue'] ) ? (int) $data['pending_queue'] : 0;
		$instances  = isset( $data['instances'] ) ? $data['instances'] : array();
		$recent     = isset( $data['recent_logs'] ) ? $data['recent_logs'] : array();
		?>

		<!-- Section 1 – Stats Grid -->
		<div class="fwa-widget-stats">
			<div class="fwa-widget-stat-item">
				<span class="fwa-widget-stat-icon fwa-widget-stat-green">
					<span class="dashicons dashicons-networking"></span>
				</span>
				<div class="fwa-widget-stat-data">
					<span class="fwa-widget-stat-value"><?php echo esc_html( $connected ); ?></span>
					<span class="fwa-widget-stat-label"><?php esc_html_e( 'Accounts', 'flexi-whatsapp-automation' ); ?></span>
				</div>
			</div>
			<div class="fwa-widget-stat-item">
				<span class="fwa-widget-stat-icon fwa-widget-stat-blue">
					<span class="dashicons dashicons-email"></span>
				</span>
				<div class="fwa-widget-stat-data">
					<span class="fwa-widget-stat-value"><?php echo esc_html( $sent ); ?></span>
					<span class="fwa-widget-stat-label"><?php esc_html_e( 'Sent Today', 'flexi-whatsapp-automation' ); ?></span>
				</div>
			</div>
			<div class="fwa-widget-stat-item">
				<span class="fwa-widget-stat-icon fwa-widget-stat-purple">
					<span class="dashicons dashicons-admin-users"></span>
				</span>
				<div class="fwa-widget-stat-data">
					<span class="fwa-widget-stat-value"><?php echo esc_html( $active . '/' . $total_sess ); ?></span>
					<span class="fwa-widget-stat-label"><?php esc_html_e( 'Sessions', 'flexi-whatsapp-automation' ); ?></span>
				</div>
			</div>
			<div class="fwa-widget-stat-item">
				<span class="fwa-widget-stat-icon fwa-widget-stat-orange">
					<span class="dashicons dashicons-clock"></span>
				</span>
				<div class="fwa-widget-stat-data">
					<span class="fwa-widget-stat-value"><?php echo esc_html( $pending ); ?></span>
					<span class="fwa-widget-stat-label"><?php esc_html_e( 'Pending', 'flexi-whatsapp-automation' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Section 2 – Quick Send Form -->
		<div class="fwa-widget-section">
			<h4 class="fwa-widget-section-title"><?php esc_html_e( 'Quick Send', 'flexi-whatsapp-automation' ); ?></h4>
			<form id="fwa-quick-send-form">
				<?php wp_nonce_field( 'fwa_quick_send', 'fwa_quick_send_nonce' ); ?>

				<div class="fwa-widget-form-group">
					<label for="fwa-qs-phone"><?php esc_html_e( 'Phone', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-qs-phone" name="phone" placeholder="+1234567890" class="fwa-widget-input" />
				</div>

				<div class="fwa-widget-form-group">
					<label for="fwa-qs-message"><?php esc_html_e( 'Message', 'flexi-whatsapp-automation' ); ?></label>
					<textarea id="fwa-qs-message" name="message" class="fwa-widget-textarea" rows="3"></textarea>
				</div>

				<div class="fwa-widget-form-row">
					<div class="fwa-widget-form-group fwa-widget-form-flex">
						<select id="fwa-qs-instance" name="instance_id" class="fwa-widget-select">
							<?php if ( empty( $instances ) ) : ?>
								<option value=""><?php esc_html_e( 'No instances', 'flexi-whatsapp-automation' ); ?></option>
							<?php else : ?>
								<?php foreach ( $instances as $inst ) : ?>
									<option value="<?php echo esc_attr( $inst['id'] ); ?>">
										<?php echo esc_html( $inst['name'] ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>
					<button type="submit" class="button button-primary fwa-widget-send-btn">
						<span class="dashicons dashicons-email-alt"></span>
						<?php esc_html_e( 'Send', 'flexi-whatsapp-automation' ); ?>
					</button>
				</div>
				<div id="fwa-quick-send-result" class="fwa-widget-result"></div>
			</form>
		</div>

		<!-- Section 3 – Quick Actions -->
		<div class="fwa-widget-section">
			<h4 class="fwa-widget-section-title"><?php esc_html_e( 'Quick Actions', 'flexi-whatsapp-automation' ); ?></h4>
			<div class="fwa-widget-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-instances' ) ); ?>" class="fwa-widget-action-link">
					<span class="dashicons dashicons-networking"></span>
					<?php esc_html_e( 'Instances', 'flexi-whatsapp-automation' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-messages' ) ); ?>" class="fwa-widget-action-link">
					<span class="dashicons dashicons-email"></span>
					<?php esc_html_e( 'Messages', 'flexi-whatsapp-automation' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-campaigns' ) ); ?>" class="fwa-widget-action-link">
					<span class="dashicons dashicons-megaphone"></span>
					<?php esc_html_e( 'Campaigns', 'flexi-whatsapp-automation' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-logs' ) ); ?>" class="fwa-widget-action-link">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Logs', 'flexi-whatsapp-automation' ); ?>
				</a>
			</div>
		</div>

		<!-- Section 4 – Recent Activity -->
		<div class="fwa-widget-section">
			<h4 class="fwa-widget-section-title"><?php esc_html_e( 'Recent Activity', 'flexi-whatsapp-automation' ); ?></h4>
			<?php if ( empty( $recent ) ) : ?>
				<p class="fwa-widget-empty"><?php esc_html_e( 'No recent activity.', 'flexi-whatsapp-automation' ); ?></p>
			<?php else : ?>
				<div class="fwa-widget-activity">
					<?php foreach ( $recent as $entry ) : ?>
						<div class="fwa-widget-activity-item">
							<span class="fwa-badge fwa-badge-<?php echo esc_attr( $entry->level ); ?>">
								<?php echo esc_html( $entry->level ); ?>
							</span>
							<span class="fwa-widget-activity-msg"><?php echo esc_html( $entry->message ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Collect all data required by the widget.
	 *
	 * @return array
	 */
	public function get_widget_data() {
		$data = array(
			'connected_accounts'     => 0,
			'active_sessions'        => 0,
			'total_sessions'         => 0,
			'messages_sent_today'    => 0,
			'messages_received_today'=> 0,
			'pending_queue'          => 0,
			'instances'              => array(),
			'recent_logs'            => array(),
		);

		// Instance data.
		if ( class_exists( 'FWA_Instance_Manager' ) ) {
			$manager = new FWA_Instance_Manager();

			if ( method_exists( $manager, 'get_connected_count' ) ) {
				$data['connected_accounts'] = $manager->get_connected_count();
			}
			if ( method_exists( $manager, 'get_active_session_count' ) ) {
				$data['active_sessions'] = $manager->get_active_session_count();
			}
			if ( method_exists( $manager, 'get_total_session_count' ) ) {
				$data['total_sessions'] = $manager->get_total_session_count();
			}
			if ( method_exists( $manager, 'get_instances_list' ) ) {
				$data['instances'] = $manager->get_instances_list();
			}
		}

		// Message data.
		if ( class_exists( 'FWA_Message_Sender' ) ) {
			$sender = new FWA_Message_Sender();

			if ( method_exists( $sender, 'get_sent_today_count' ) ) {
				$data['messages_sent_today'] = $sender->get_sent_today_count();
			}
			if ( method_exists( $sender, 'get_received_today_count' ) ) {
				$data['messages_received_today'] = $sender->get_received_today_count();
			}
		}

		// Scheduler data.
		if ( class_exists( 'FWA_Scheduler' ) ) {
			$scheduler = new FWA_Scheduler();

			if ( method_exists( $scheduler, 'get_pending_count' ) ) {
				$data['pending_queue'] = $scheduler->get_pending_count();
			}
		}

		// Recent logs.
		if ( class_exists( 'FWA_Logger' ) ) {
			$logger = FWA_Logger::get_instance();
			$result = $logger->get_logs( array(
				'limit'   => 5,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			) );
			$data['recent_logs'] = $result['logs'];
		}

		/**
		 * Filters the dashboard widget data.
		 *
		 * @param array $data Widget data.
		 */
		return apply_filters( 'fwa_dashboard_widget_data', $data );
	}

	/**
	 * Enqueue widget styles and inline JavaScript for the quick-send form.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_widget_scripts( $hook_suffix ) {
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Widget-specific styles.
		wp_enqueue_style(
			'fwa-dashboard-widget',
			FWA_PLUGIN_URL . 'admin/css/fwa-admin.css',
			array(),
			FWA_VERSION
		);

		$js = <<<'JS'
(function($){
	$(document).on('submit', '#fwa-quick-send-form', function(e){
		e.preventDefault();
		var $form   = $(this),
			$btn    = $form.find('.fwa-widget-send-btn'),
			$result = $('#fwa-quick-send-result');

		$btn.prop('disabled', true);
		$result.html('').removeClass('fwa-widget-result-success fwa-widget-result-error');

		$.post(ajaxurl, {
			action:              'fwa_quick_send',
			fwa_quick_send_nonce: $form.find('#fwa_quick_send_nonce').val(),
			phone:               $form.find('#fwa-qs-phone').val(),
			message:             $form.find('#fwa-qs-message').val(),
			instance_id:         $form.find('#fwa-qs-instance').val()
		}, function(res){
			if (res.success) {
				$result.addClass('fwa-widget-result-success').html(res.data.message || 'Sent!');
				$form.find('#fwa-qs-message').val('');
			} else {
				$result.addClass('fwa-widget-result-error').html(res.data.message || 'Error.');
			}
		}).fail(function(){
			$result.addClass('fwa-widget-result-error').html('Request failed.');
		}).always(function(){
			$btn.prop('disabled', false);
		});
	});
})(jQuery);
JS;

		wp_add_inline_script( 'jquery-core', $js );
	}

	/**
	 * AJAX handler for the quick-send form.
	 */
	public function handle_quick_send() {
		check_ajax_referer( 'fwa_quick_send', 'fwa_quick_send_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'flexi-whatsapp-automation' ) ) );
		}

		$phone       = isset( $_POST['phone'] )       ? sanitize_text_field( wp_unslash( $_POST['phone'] ) )       : '';
		$message     = isset( $_POST['message'] )      ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$instance_id = isset( $_POST['instance_id'] )  ? absint( $_POST['instance_id'] )                            : 0;

		if ( empty( $phone ) || empty( $message ) || empty( $instance_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone, message, and instance are required.', 'flexi-whatsapp-automation' ) ) );
		}

		if ( ! class_exists( 'FWA_Message_Sender' ) ) {
			wp_send_json_error( array( 'message' => __( 'Message sender is not available.', 'flexi-whatsapp-automation' ) ) );
		}

		$sender = new FWA_Message_Sender();
		$result = $sender->send( $phone, $message, $instance_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Message sent successfully.', 'flexi-whatsapp-automation' ) ) );
	}
}
