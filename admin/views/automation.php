<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$events = array(
	'new_order'              => __( 'New Order Created', 'flexi-whatsapp-automation' ),
	'order_status_completed' => __( 'Order Completed', 'flexi-whatsapp-automation' ),
	'order_status_processing'=> __( 'Order Processing', 'flexi-whatsapp-automation' ),
	'order_status_cancelled' => __( 'Order Cancelled', 'flexi-whatsapp-automation' ),
	'order_status_refunded'  => __( 'Order Refunded', 'flexi-whatsapp-automation' ),
	'payment_complete'       => __( 'Payment Complete', 'flexi-whatsapp-automation' ),
	'checkout_complete'      => __( 'Checkout Complete', 'flexi-whatsapp-automation' ),
	'user_register'          => __( 'User Registered', 'flexi-whatsapp-automation' ),
	'user_login'             => __( 'User Login', 'flexi-whatsapp-automation' ),
	'cart_abandoned'         => __( 'Cart Abandoned (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'cart_recovered'         => __( 'Cart Recovered (Flexi Revive Cart)', 'flexi-whatsapp-automation' ),
	'custom'                 => __( 'Custom Trigger', 'flexi-whatsapp-automation' ),
);
$events = apply_filters( 'fwa_automation_events', $events );
?>
<div class="fwa-automation-page">
	<h2><?php esc_html_e( 'Automation Rules', 'flexi-whatsapp-automation' ); ?>
		<button type="button" class="button button-primary" id="fwa-add-rule-btn"><?php esc_html_e( 'Add Rule', 'flexi-whatsapp-automation' ); ?></button>
	</h2>

	<div class="fwa-placeholder-ref fwa-form-card">
		<h4><?php esc_html_e( 'Available Placeholders', 'flexi-whatsapp-automation' ); ?></h4>
		<code>{customer_name}</code> <code>{order_id}</code> <code>{order_total}</code> <code>{order_status}</code> <code>{store_name}</code> <code>{site_url}</code> <code>{date}</code> <code>{phone}</code> <code>{email}</code> <code>{product_list}</code> <code>{cart_total}</code> <code>{recovery_link}</code>
	</div>

	<div id="fwa-rules-container"></div>

	<button type="button" class="button button-primary button-hero" id="fwa-save-rules" style="margin-top:15px;"><?php esc_html_e( 'Save All Rules', 'flexi-whatsapp-automation' ); ?></button>
</div>

<script type="text/template" id="fwa-rule-template">
	<div class="fwa-rule-card fwa-form-card" data-index="{{INDEX}}">
		<div class="fwa-rule-header">
			<strong><?php esc_html_e( 'Rule', 'flexi-whatsapp-automation' ); ?> #{{NUM}}</strong>
			<label><input type="checkbox" class="fwa-rule-enabled" checked> <?php esc_html_e( 'Enabled', 'flexi-whatsapp-automation' ); ?></label>
			<button type="button" class="button button-small fwa-remove-rule">&times;</button>
		</div>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Event', 'flexi-whatsapp-automation' ); ?></th><td><select class="fwa-rule-event">
				<?php foreach ( $events as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></td></tr>
			<tr><th><?php esc_html_e( 'Instance', 'flexi-whatsapp-automation' ); ?></th><td><select class="fwa-rule-instance fwa-instance-select"></select></td></tr>
			<tr><th><?php esc_html_e( 'Phone Field', 'flexi-whatsapp-automation' ); ?></th><td><input type="text" class="fwa-rule-phone-field regular-text" value="billing_phone"></td></tr>
			<tr><th><?php esc_html_e( 'Message Type', 'flexi-whatsapp-automation' ); ?></th><td><select class="fwa-rule-msg-type">
				<option value="text"><?php esc_html_e( 'Text', 'flexi-whatsapp-automation' ); ?></option>
				<option value="image"><?php esc_html_e( 'Image', 'flexi-whatsapp-automation' ); ?></option>
				<option value="document"><?php esc_html_e( 'Document', 'flexi-whatsapp-automation' ); ?></option>
			</select></td></tr>
			<tr><th><?php esc_html_e( 'Message Template', 'flexi-whatsapp-automation' ); ?></th><td><textarea class="fwa-rule-template large-text" rows="4" placeholder="<?php esc_attr_e( 'Hi {customer_name}, your order #{order_id} has been placed!', 'flexi-whatsapp-automation' ); ?>"></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Media URL', 'flexi-whatsapp-automation' ); ?></th><td><input type="url" class="fwa-rule-media-url regular-text"></td></tr>
		</table>
	</div>
</script>

<script type="text/javascript">
jQuery(document).ready(function($){
	var rules = [];
	var ruleIdx = 0;

	function loadRules() {
		$.post(fwa_admin.ajax_url, { action: 'fwa_get_automation_rules', nonce: fwa_admin.nonce }, function(r){
			if (r.success && r.data.rules) {
				rules = r.data.rules;
				renderRules();
			}
		});
		$.post(fwa_admin.ajax_url, { action: 'fwa_get_instances', nonce: fwa_admin.nonce }, function(r){
			if (r.success && r.data.instances) {
				window.fwaInstances = r.data.instances;
				$('.fwa-instance-select').each(function(){ populateInstances($(this)); });
			}
		});
	}

	function populateInstances($sel) {
		$sel.empty().append('<option value=""><?php echo esc_js( __( 'Active Instance', 'flexi-whatsapp-automation' ) ); ?></option>');
		if (window.fwaInstances) {
			$.each(window.fwaInstances, function(i, inst) {
				$sel.append('<option value="' + parseInt(inst.id) + '">' + $('<span>').text(inst.name).html() + '</option>');
			});
		}
	}

	function renderRules() {
		$('#fwa-rules-container').empty();
		$.each(rules, function(i, rule) { addRuleCard(rule); });
	}

	function addRuleCard(rule) {
		rule = rule || {};
		var tpl = $('#fwa-rule-template').html().replace(/{{INDEX}}/g, ruleIdx).replace(/{{NUM}}/g, ruleIdx + 1);
		$('#fwa-rules-container').append(tpl);
		var $card = $('#fwa-rules-container .fwa-rule-card').last();
		if (rule.event) $card.find('.fwa-rule-event').val(rule.event);
		if (rule.enabled === false) $card.find('.fwa-rule-enabled').prop('checked', false);
		if (rule.phone_field) $card.find('.fwa-rule-phone-field').val(rule.phone_field);
		if (rule.message_type) $card.find('.fwa-rule-msg-type').val(rule.message_type);
		if (rule.template) $card.find('.fwa-rule-template').val(rule.template);
		if (rule.media_url) $card.find('.fwa-rule-media-url').val(rule.media_url);
		populateInstances($card.find('.fwa-instance-select'));
		if (rule.instance_id) $card.find('.fwa-rule-instance').val(rule.instance_id);
		ruleIdx++;
	}

	$('#fwa-add-rule-btn').on('click', function(){ addRuleCard(); });
	$(document).on('click', '.fwa-remove-rule', function(){ $(this).closest('.fwa-rule-card').remove(); });

	$('#fwa-save-rules').on('click', function(){
		var data = [];
		$('.fwa-rule-card').each(function(){
			var $c = $(this);
			data.push({
				event: $c.find('.fwa-rule-event').val(),
				enabled: $c.find('.fwa-rule-enabled').is(':checked'),
				instance_id: $c.find('.fwa-rule-instance').val(),
				phone_field: $c.find('.fwa-rule-phone-field').val(),
				message_type: $c.find('.fwa-rule-msg-type').val(),
				template: $c.find('.fwa-rule-template').val(),
				media_url: $c.find('.fwa-rule-media-url').val()
			});
		});
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_save_automation_rules',
			nonce: fwa_admin.nonce,
			rules: JSON.stringify(data)
		}, function(r){
			alert(r.success ? '<?php echo esc_js( __( 'Rules saved!', 'flexi-whatsapp-automation' ) ); ?>' : 'Error');
		});
	});

	loadRules();
});
</script>
