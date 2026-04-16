<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$events = array(
	'new_order'                => __( 'New Order Created', 'flexi-whatsapp-automation' ),
	'order_status_completed'   => __( 'Order Completed', 'flexi-whatsapp-automation' ),
	'order_status_processing'  => __( 'Order Processing', 'flexi-whatsapp-automation' ),
	'order_status_cancelled'   => __( 'Order Cancelled', 'flexi-whatsapp-automation' ),
	'order_status_refunded'    => __( 'Order Refunded', 'flexi-whatsapp-automation' ),
	'payment_complete'         => __( 'Payment Complete', 'flexi-whatsapp-automation' ),
	'checkout_complete'        => __( 'Checkout Complete', 'flexi-whatsapp-automation' ),
	'user_register'            => __( 'User Registered', 'flexi-whatsapp-automation' ),
	'user_login'               => __( 'User Login', 'flexi-whatsapp-automation' ),
	'cart_abandoned'           => __( 'Cart Abandoned (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'cart_recovered'           => __( 'Cart Recovered (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'frc_reminder_stage_1'     => __( 'FRC Reminder — Stage 1 (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'frc_reminder_stage_2'     => __( 'FRC Reminder — Stage 2 (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'frc_reminder_stage_3'     => __( 'FRC Reminder — Stage 3 (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'custom'                   => __( 'Custom Trigger', 'flexi-whatsapp-automation' ),
);
$events = apply_filters( 'fwa_automation_events', $events );
?>
<div class="fwa-automation-page">
	<!-- Page Header -->
	<div class="fwa-page-header">
		<div class="fwa-page-header-content">
			<h2><?php esc_html_e( 'Automation Rules', 'flexi-whatsapp-automation' ); ?></h2>
			<p class="fwa-page-subtitle"><?php esc_html_e( 'Set up event-driven WhatsApp messages', 'flexi-whatsapp-automation' ); ?></p>
		</div>
		<div class="fwa-page-header-actions">
			<button type="button" class="button button-primary" id="fwa-add-rule-btn">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add Rule', 'flexi-whatsapp-automation' ); ?>
			</button>
		</div>
	</div>

	<!-- Available Placeholders Reference -->
	<div class="fwa-form-card fwa-placeholder-ref">
		<div class="fwa-placeholder-header">
			<h4>
				<span class="dashicons dashicons-info-outline"></span>
				<?php esc_html_e( 'Available Placeholders', 'flexi-whatsapp-automation' ); ?>
			</h4>
		</div>
		<div class="fwa-placeholder-body">
			<code>{customer_name}</code>
			<code>{order_id}</code>
			<code>{order_total}</code>
			<code>{order_status}</code>
			<code>{store_name}</code>
			<code>{site_url}</code>
			<code>{date}</code>
			<code>{phone}</code>
			<code>{email}</code>
			<code>{product_list}</code>
			<code>{cart_total}</code>
			<code>{recovery_link}</code>
		</div>
	</div>

	<!-- Rules Container (populated by JS) -->
	<div id="fwa-rules-container">
		<!-- Empty state shown when no rules exist -->
		<div class="fwa-empty-state" id="fwa-rules-empty">
			<span class="dashicons dashicons-controls-repeat"></span>
			<h3><?php esc_html_e( 'No automation rules yet', 'flexi-whatsapp-automation' ); ?></h3>
			<p><?php esc_html_e( 'Create your first rule to automate WhatsApp messages based on events.', 'flexi-whatsapp-automation' ); ?></p>
		</div>
	</div>

	<!-- Save All Button -->
	<div class="fwa-save-bar" id="fwa-save-bar">
		<button type="button" class="button button-primary button-hero" id="fwa-save-rules">
			<span class="dashicons dashicons-saved"></span>
			<?php esc_html_e( 'Save All Rules', 'flexi-whatsapp-automation' ); ?>
		</button>
	</div>
</div>

<!-- Rule Card Template -->
<script type="text/template" id="fwa-rule-template">
	<div class="fwa-rule-card fwa-form-card" data-index="{{INDEX}}">
		<div class="fwa-rule-header">
			<strong class="fwa-rule-title">
				<span class="dashicons dashicons-controls-repeat"></span>
				<?php esc_html_e( 'Rule', 'flexi-whatsapp-automation' ); ?> #{{NUM}}
			</strong>
			<div class="fwa-rule-header-actions">
				<label class="fwa-toggle-label">
					<input type="checkbox" class="fwa-rule-enabled" checked>
					<span class="fwa-toggle-text"><?php esc_html_e( 'Enabled', 'flexi-whatsapp-automation' ); ?></span>
				</label>
				<button type="button" class="button button-small fwa-remove-rule" title="<?php esc_attr_e( 'Remove Rule', 'flexi-whatsapp-automation' ); ?>">&times;</button>
			</div>
		</div>
		<div class="fwa-rule-body">
			<div class="fwa-rule-grid">
				<div class="fwa-form-group">
					<label><?php esc_html_e( 'Event Trigger', 'flexi-whatsapp-automation' ); ?></label>
					<select class="fwa-rule-event regular-text">
						<?php foreach ( $events as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="fwa-form-group">
					<label><?php esc_html_e( 'Instance', 'flexi-whatsapp-automation' ); ?></label>
					<select class="fwa-rule-instance fwa-instance-select regular-text"></select>
				</div>
				<div class="fwa-form-group">
					<label><?php esc_html_e( 'Phone Field', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" class="fwa-rule-phone-field regular-text" value="billing_phone">
				</div>
				<div class="fwa-form-group">
					<label><?php esc_html_e( 'Message Type', 'flexi-whatsapp-automation' ); ?></label>
					<select class="fwa-rule-msg-type regular-text">
						<option value="text"><?php esc_html_e( 'Text', 'flexi-whatsapp-automation' ); ?></option>
						<option value="image"><?php esc_html_e( 'Image', 'flexi-whatsapp-automation' ); ?></option>
						<option value="document"><?php esc_html_e( 'Document', 'flexi-whatsapp-automation' ); ?></option>
					</select>
				</div>
			</div>
			<div class="fwa-form-group">
				<label><?php esc_html_e( 'Message Template', 'flexi-whatsapp-automation' ); ?></label>
				<textarea class="fwa-rule-template large-text" rows="4" placeholder="<?php esc_attr_e( 'Hi {customer_name}, your order #{order_id} has been placed!', 'flexi-whatsapp-automation' ); ?>"></textarea>
			</div>
			<div class="fwa-form-group">
				<label><?php esc_html_e( 'Media URL', 'flexi-whatsapp-automation' ); ?></label>
				<input type="url" class="fwa-rule-media-url regular-text" placeholder="https://">
			</div>
		</div>
	</div>
</script>
