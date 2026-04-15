<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Dashboard widget for WhatsApp Automation quick actions and status.
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
			'<span class="dashicons dashicons-format-chat" style="margin-right:4px;"></span> ' . esc_html__( 'WhatsApp Automation', 'flexi-whatsapp-automation' ),
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
		<style>
			#fwa_dashboard_widget .fwa-section {
				background: #f9f9f9;
				border: 1px solid #e2e4e7;
				border-radius: 6px;
				padding: 12px 14px;
				margin-bottom: 12px;
			}
			#fwa_dashboard_widget .fwa-section:last-child {
				margin-bottom: 0;
			}
			#fwa_dashboard_widget .fwa-section h4 {
				margin: 0 0 8px;
				font-size: 13px;
				font-weight: 600;
				color: #1d2327;
			}
			#fwa_dashboard_widget .fwa-stat {
				display: flex;
				justify-content: space-between;
				padding: 4px 0;
				font-size: 13px;
			}
			#fwa_dashboard_widget .fwa-dot {
				display: inline-block;
				width: 8px;
				height: 8px;
				border-radius: 50%;
				margin-right: 6px;
				vertical-align: middle;
			}
			#fwa_dashboard_widget .fwa-dot-green { background: #00a32a; }
			#fwa_dashboard_widget .fwa-dot-red   { background: #d63638; }
			#fwa_dashboard_widget .fwa-quick-send label {
				display: block;
				font-size: 12px;
				font-weight: 600;
				margin-bottom: 4px;
				color: #50575e;
			}
			#fwa_dashboard_widget .fwa-quick-send input[type="text"],
			#fwa_dashboard_widget .fwa-quick-send textarea,
			#fwa_dashboard_widget .fwa-quick-send select {
				width: 100%;
				margin-bottom: 8px;
			}
			#fwa_dashboard_widget .fwa-quick-send textarea {
				min-height: 60px;
			}
			#fwa_dashboard_widget .fwa-actions-list {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}
			#fwa_dashboard_widget .fwa-actions-list a {
				display: inline-block;
				padding: 6px 12px;
				background: #f0f0f1;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				text-decoration: none;
				font-size: 12px;
				color: #2271b1;
			}
			#fwa_dashboard_widget .fwa-actions-list a:hover {
				background: #e0e0e1;
			}
			#fwa_dashboard_widget .fwa-log-entry {
				display: flex;
				align-items: flex-start;
				gap: 6px;
				padding: 4px 0;
				font-size: 12px;
				border-bottom: 1px solid #eee;
			}
			#fwa_dashboard_widget .fwa-log-entry:last-child {
				border-bottom: none;
			}
			#fwa_dashboard_widget .fwa-badge {
				display: inline-block;
				padding: 1px 6px;
				border-radius: 3px;
				font-size: 10px;
				font-weight: 600;
				text-transform: uppercase;
				flex-shrink: 0;
			}
			#fwa_dashboard_widget .fwa-badge-debug    { background: #e0e0e0; color: #50575e; }
			#fwa_dashboard_widget .fwa-badge-info     { background: #d1ecf1; color: #0c5460; }
			#fwa_dashboard_widget .fwa-badge-warning  { background: #fff3cd; color: #856404; }
			#fwa_dashboard_widget .fwa-badge-error    { background: #f8d7da; color: #721c24; }
			#fwa_dashboard_widget .fwa-badge-critical { background: #d63638; color: #fff; }
			#fwa_dashboard_widget .fwa-send-result {
				margin-top: 6px;
				font-size: 12px;
			}
		</style>

		<!-- Section 1 – Status Overview -->
		<div class="fwa-section">
			<h4><?php esc_html_e( 'Status Overview', 'flexi-whatsapp-automation' ); ?></h4>
			<div class="fwa-stat">
				<span>
					<span class="fwa-dot <?php echo $connected > 0 ? 'fwa-dot-green' : 'fwa-dot-red'; ?>"></span>
					<?php esc_html_e( 'Connected Accounts', 'flexi-whatsapp-automation' ); ?>
				</span>
				<strong><?php echo esc_html( $connected ); ?></strong>
			</div>
			<div class="fwa-stat">
				<span><?php esc_html_e( 'Active Sessions', 'flexi-whatsapp-automation' ); ?></span>
				<strong><?php echo esc_html( $active . ' / ' . $total_sess ); ?></strong>
			</div>
			<div class="fwa-stat">
				<span><?php esc_html_e( 'Messages Today', 'flexi-whatsapp-automation' ); ?></span>
				<strong>
					<?php
					printf(
						/* translators: 1: sent count, 2: received count */
						esc_html__( '%1$d sent, %2$d received', 'flexi-whatsapp-automation' ),
						$sent,
						$received
					);
					?>
				</strong>
			</div>
			<div class="fwa-stat">
				<span><?php esc_html_e( 'Pending Queue', 'flexi-whatsapp-automation' ); ?></span>
				<strong><?php echo esc_html( $pending ); ?></strong>
			</div>
		</div>

		<!-- Section 2 – Quick Send Form -->
		<div class="fwa-section fwa-quick-send">
			<h4><?php esc_html_e( 'Quick Send', 'flexi-whatsapp-automation' ); ?></h4>
			<form id="fwa-quick-send-form">
				<?php wp_nonce_field( 'fwa_quick_send', 'fwa_quick_send_nonce' ); ?>

				<label for="fwa-qs-phone"><?php esc_html_e( 'Phone Number', 'flexi-whatsapp-automation' ); ?></label>
				<input type="text" id="fwa-qs-phone" name="phone" placeholder="+1234567890" />

				<label for="fwa-qs-message"><?php esc_html_e( 'Message', 'flexi-whatsapp-automation' ); ?></label>
				<textarea id="fwa-qs-message" name="message"></textarea>

				<label for="fwa-qs-instance"><?php esc_html_e( 'Instance', 'flexi-whatsapp-automation' ); ?></label>
				<select id="fwa-qs-instance" name="instance_id">
					<?php if ( empty( $instances ) ) : ?>
						<option value=""><?php esc_html_e( 'No instances available', 'flexi-whatsapp-automation' ); ?></option>
					<?php else : ?>
						<?php foreach ( $instances as $inst ) : ?>
							<option value="<?php echo esc_attr( $inst['id'] ); ?>">
								<?php echo esc_html( $inst['name'] ); ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>

				<button type="submit" class="button button-primary"><?php esc_html_e( 'Send', 'flexi-whatsapp-automation' ); ?></button>
				<div id="fwa-quick-send-result" class="fwa-send-result"></div>
			</form>
		</div>

		<!-- Section 3 – Quick Actions -->
		<div class="fwa-section">
			<h4><?php esc_html_e( 'Quick Actions', 'flexi-whatsapp-automation' ); ?></h4>
			<div class="fwa-actions-list">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-instances' ) ); ?>">
					<?php esc_html_e( 'Manage Instances', 'flexi-whatsapp-automation' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-messages' ) ); ?>">
					<?php esc_html_e( 'View Messages', 'flexi-whatsapp-automation' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-campaigns' ) ); ?>">
					<?php esc_html_e( 'New Campaign', 'flexi-whatsapp-automation' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-logs' ) ); ?>">
					<?php esc_html_e( 'View Logs', 'flexi-whatsapp-automation' ); ?>
				</a>
			</div>
		</div>

		<!-- Section 4 – Recent Activity -->
		<div class="fwa-section">
			<h4><?php esc_html_e( 'Recent Activity', 'flexi-whatsapp-automation' ); ?></h4>
			<?php if ( empty( $recent ) ) : ?>
				<p style="margin:0;font-size:12px;color:#757575;">
					<?php esc_html_e( 'No recent activity.', 'flexi-whatsapp-automation' ); ?>
				</p>
			<?php else : ?>
				<?php foreach ( $recent as $entry ) : ?>
					<div class="fwa-log-entry">
						<span class="fwa-badge fwa-badge-<?php echo esc_attr( $entry->level ); ?>">
							<?php echo esc_html( $entry->level ); ?>
						</span>
						<span><?php echo esc_html( $entry->message ); ?></span>
					</div>
				<?php endforeach; ?>
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
			$manager = FWA_Instance_Manager::get_instance();

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
			$sender = FWA_Message_Sender::get_instance();

			if ( method_exists( $sender, 'get_sent_today_count' ) ) {
				$data['messages_sent_today'] = $sender->get_sent_today_count();
			}
			if ( method_exists( $sender, 'get_received_today_count' ) ) {
				$data['messages_received_today'] = $sender->get_received_today_count();
			}
		}

		// Scheduler data.
		if ( class_exists( 'FWA_Scheduler' ) ) {
			$scheduler = FWA_Scheduler::get_instance();

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
	 * Enqueue inline JavaScript for the quick-send form.
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

		$js = <<<'JS'
(function($){
	$(document).on('submit', '#fwa-quick-send-form', function(e){
		e.preventDefault();
		var $form   = $(this),
			$btn    = $form.find('button[type="submit"]'),
			$result = $('#fwa-quick-send-result');

		$btn.prop('disabled', true);
		$result.html('').css('color', '');

		$.post(ajaxurl, {
			action:              'fwa_quick_send',
			fwa_quick_send_nonce: $form.find('#fwa_quick_send_nonce').val(),
			phone:               $form.find('#fwa-qs-phone').val(),
			message:             $form.find('#fwa-qs-message').val(),
			instance_id:         $form.find('#fwa-qs-instance').val()
		}, function(res){
			if (res.success) {
				$result.css('color', '#00a32a').html(res.data.message || 'Sent!');
				$form.find('#fwa-qs-message').val('');
			} else {
				$result.css('color', '#d63638').html(res.data.message || 'Error.');
			}
		}).fail(function(){
			$result.css('color', '#d63638').html('Request failed.');
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

		$sender = FWA_Message_Sender::get_instance();
		$result = $sender->send( $phone, $message, $instance_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Message sent successfully.', 'flexi-whatsapp-automation' ) ) );
	}
}
