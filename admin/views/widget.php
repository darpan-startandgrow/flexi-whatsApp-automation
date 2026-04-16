<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'fwa_chat_widget', array() );

$defaults = array(
	'enabled'          => 'yes',
	'position'         => 'bottom-right',
	'button_text'      => __( 'Chat with us', 'flexi-whatsapp-automation' ),
	'button_color'     => '#25D366',
	'text_color'       => '#ffffff',
	'header_title'     => get_bloginfo( 'name' ),
	'header_subtitle'  => __( 'Typically replies within minutes', 'flexi-whatsapp-automation' ),
	'welcome_message'  => __( 'Hello! How can we help you?', 'flexi-whatsapp-automation' ),
	'display_delay'    => 3,
	'show_on_mobile'   => 'yes',
	'show_on_desktop'  => 'yes',
	'display_pages'    => '',
	'exclude_pages'    => '',
	'analytics'        => 'yes',
	'show_desktop_qr'  => 'yes',
	'social_facebook'  => '',
	'social_instagram' => '',
	'social_twitter'   => '',
	'social_linkedin'  => '',
	'social_youtube'   => '',
	'cta_message'      => '',
	'agents'           => array(),
);

$s = wp_parse_args( $settings, $defaults );
?>
<div class="wrap fwa-widget-settings">
	<h1><?php esc_html_e( 'Chat Widget', 'flexi-whatsapp-automation' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Configure the floating WhatsApp chat widget shown on the front-end of your site.', 'flexi-whatsapp-automation' ); ?></p>

	<div id="fwa-widget-saved" class="notice notice-success" style="display:none;"><p><?php esc_html_e( 'Settings saved.', 'flexi-whatsapp-automation' ); ?></p></div>

	<!-- ----------------------------------------------------------------
	     General Settings
	-----------------------------------------------------------------  -->
	<div class="postbox" style="padding:20px;margin-top:20px;">
		<h2><?php esc_html_e( 'General', 'flexi-whatsapp-automation' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Widget', 'flexi-whatsapp-automation' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="fwa-widget-enabled" value="yes" <?php checked( 'yes', $s['enabled'] ); ?>>
						<?php esc_html_e( 'Show floating WhatsApp button on the site', 'flexi-whatsapp-automation' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="fwa-widget-position"><?php esc_html_e( 'Position', 'flexi-whatsapp-automation' ); ?></label></th>
				<td>
					<select id="fwa-widget-position">
						<option value="bottom-right" <?php selected( 'bottom-right', $s['position'] ); ?>><?php esc_html_e( 'Bottom Right', 'flexi-whatsapp-automation' ); ?></option>
						<option value="bottom-left"  <?php selected( 'bottom-left',  $s['position'] ); ?>><?php esc_html_e( 'Bottom Left',  'flexi-whatsapp-automation' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="fwa-widget-button-text"><?php esc_html_e( 'Button Text', 'flexi-whatsapp-automation' ); ?></label></th>
				<td><input type="text" id="fwa-widget-button-text" class="regular-text" value="<?php echo esc_attr( $s['button_text'] ); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Button Colour', 'flexi-whatsapp-automation' ); ?></th>
				<td><input type="color" id="fwa-widget-button-color" value="<?php echo esc_attr( $s['button_color'] ); ?>">
				    <input type="color" id="fwa-widget-text-color" value="<?php echo esc_attr( $s['text_color'] ); ?>" title="<?php esc_attr_e( 'Text/icon colour', 'flexi-whatsapp-automation' ); ?>">
				    <span class="description">&nbsp;<?php esc_html_e( 'Background &amp; text/icon colour', 'flexi-whatsapp-automation' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="fwa-widget-header-title"><?php esc_html_e( 'Header Title', 'flexi-whatsapp-automation' ); ?></label></th>
				<td><input type="text" id="fwa-widget-header-title" class="regular-text" value="<?php echo esc_attr( $s['header_title'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="fwa-widget-header-sub"><?php esc_html_e( 'Header Subtitle', 'flexi-whatsapp-automation' ); ?></label></th>
				<td><input type="text" id="fwa-widget-header-sub" class="regular-text" value="<?php echo esc_attr( $s['header_subtitle'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="fwa-widget-welcome"><?php esc_html_e( 'Welcome Message', 'flexi-whatsapp-automation' ); ?></label></th>
				<td><textarea id="fwa-widget-welcome" class="large-text" rows="2"><?php echo esc_textarea( $s['welcome_message'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="fwa-widget-cta"><?php esc_html_e( 'Pre-filled WhatsApp Message', 'flexi-whatsapp-automation' ); ?></label></th>
				<td>
					<input type="text" id="fwa-widget-cta" class="large-text" value="<?php echo esc_attr( $s['cta_message'] ); ?>">
					<p class="description"><?php esc_html_e( 'Text auto-filled in the visitor\'s WhatsApp compose box when they tap an agent.', 'flexi-whatsapp-automation' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="fwa-widget-delay"><?php esc_html_e( 'Display Delay (s)', 'flexi-whatsapp-automation' ); ?></label></th>
				<td><input type="number" id="fwa-widget-delay" min="0" max="120" value="<?php echo esc_attr( absint( $s['display_delay'] ) ); ?>" class="small-text"></td>
			</tr>
		</table>
	</div>

	<!-- ----------------------------------------------------------------
	     Display Rules
	-----------------------------------------------------------------  -->
	<div class="postbox" style="padding:20px;margin-top:20px;">
		<h2><?php esc_html_e( 'Display Rules', 'flexi-whatsapp-automation' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Show On', 'flexi-whatsapp-automation' ); ?></th>
				<td>
					<label><input type="checkbox" id="fwa-widget-mobile"  value="yes" <?php checked( 'yes', $s['show_on_mobile'] ); ?>> <?php esc_html_e( 'Mobile', 'flexi-whatsapp-automation' ); ?></label>&nbsp;&nbsp;
					<label><input type="checkbox" id="fwa-widget-desktop" value="yes" <?php checked( 'yes', $s['show_on_desktop'] ); ?>> <?php esc_html_e( 'Desktop', 'flexi-whatsapp-automation' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="fwa-widget-pages-include"><?php esc_html_e( 'Show Only On Page IDs', 'flexi-whatsapp-automation' ); ?></label></th>
				<td>
					<input type="text" id="fwa-widget-pages-include" class="regular-text" value="<?php echo esc_attr( $s['display_pages'] ); ?>" placeholder="1,2,3">
					<p class="description"><?php esc_html_e( 'Comma-separated post/page IDs. Leave blank for all pages.', 'flexi-whatsapp-automation' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="fwa-widget-pages-exclude"><?php esc_html_e( 'Exclude Page IDs', 'flexi-whatsapp-automation' ); ?></label></th>
				<td>
					<input type="text" id="fwa-widget-pages-exclude" class="regular-text" value="<?php echo esc_attr( $s['exclude_pages'] ); ?>" placeholder="4,5">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'QR Code for Desktop', 'flexi-whatsapp-automation' ); ?></th>
				<td>
					<label><input type="checkbox" id="fwa-widget-desktop-qr" value="yes" <?php checked( 'yes', $s['show_desktop_qr'] ); ?>>
					<?php esc_html_e( 'Show a WhatsApp QR code inside the widget on desktop browsers', 'flexi-whatsapp-automation' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Track Analytics', 'flexi-whatsapp-automation' ); ?></th>
				<td><label><input type="checkbox" id="fwa-widget-analytics" value="yes" <?php checked( 'yes', $s['analytics'] ); ?>> <?php esc_html_e( 'Fire Google Analytics / GTM events on widget open and click', 'flexi-whatsapp-automation' ); ?></label></td>
			</tr>
		</table>
	</div>

	<!-- ----------------------------------------------------------------
	     Social Links
	-----------------------------------------------------------------  -->
	<div class="postbox" style="padding:20px;margin-top:20px;">
		<h2><?php esc_html_e( 'Social Links', 'flexi-whatsapp-automation' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Optional social links shown inside the widget panel.', 'flexi-whatsapp-automation' ); ?></p>
		<table class="form-table">
			<?php
			$socials = array(
				'social_facebook'  => 'Facebook',
				'social_instagram' => 'Instagram',
				'social_twitter'   => 'Twitter / X',
				'social_linkedin'  => 'LinkedIn',
				'social_youtube'   => 'YouTube',
			);
			foreach ( $socials as $key => $label ) :
				$field_id = 'fwa-widget-' . str_replace( 'social_', '', $key );
			?>
			<tr>
				<th><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td><input type="url" id="<?php echo esc_attr( $field_id ); ?>" data-social="<?php echo esc_attr( $key ); ?>" class="fwa-widget-social regular-text" value="<?php echo esc_attr( $s[ $key ] ); ?>" placeholder="https://"></td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<!-- ----------------------------------------------------------------
	     Agents
	-----------------------------------------------------------------  -->
	<div class="postbox" style="padding:20px;margin-top:20px;">
		<h2><?php esc_html_e( 'Support Agents', 'flexi-whatsapp-automation' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Add agents shown inside the widget. Each agent has their own WhatsApp number.', 'flexi-whatsapp-automation' ); ?></p>

		<div id="fwa-widget-agents"></div>

		<p>
			<button type="button" class="button" id="fwa-add-agent">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add Agent', 'flexi-whatsapp-automation' ); ?>
			</button>
		</p>
	</div>

	<p>
		<button type="button" class="button button-primary button-hero" id="fwa-save-widget-btn">
			<?php esc_html_e( 'Save Widget Settings', 'flexi-whatsapp-automation' ); ?>
		</button>
	</p>
</div>

<!-- Agent row template (hidden) -->
<script type="text/template" id="fwa-agent-template">
	<div class="fwa-agent-row postbox" style="padding:15px;margin-bottom:10px;">
		<div style="display:flex;gap:10px;flex-wrap:wrap;">
			<div><label><?php esc_html_e( 'Name', 'flexi-whatsapp-automation' ); ?></label><br><input type="text" class="fwa-agent-name regular-text" placeholder="<?php esc_attr_e( 'Support Agent', 'flexi-whatsapp-automation' ); ?>"></div>
			<div><label><?php esc_html_e( 'Phone (E.164)', 'flexi-whatsapp-automation' ); ?></label><br><input type="text" class="fwa-agent-phone regular-text" placeholder="+1234567890"></div>
			<div><label><?php esc_html_e( 'Role', 'flexi-whatsapp-automation' ); ?></label><br><input type="text" class="fwa-agent-role regular-text" placeholder="<?php esc_attr_e( 'Sales', 'flexi-whatsapp-automation' ); ?>"></div>
			<div><label><?php esc_html_e( 'Availability', 'flexi-whatsapp-automation' ); ?></label><br><input type="text" class="fwa-agent-availability regular-text" placeholder="<?php esc_attr_e( 'Mon-Fri 9-5', 'flexi-whatsapp-automation' ); ?>"></div>
			<div><label><?php esc_html_e( 'Avatar URL', 'flexi-whatsapp-automation' ); ?></label><br><input type="url" class="fwa-agent-avatar regular-text" placeholder="https://"></div>
			<div><label><?php esc_html_e( 'Default Message', 'flexi-whatsapp-automation' ); ?></label><br><input type="text" class="fwa-agent-message regular-text" placeholder="<?php esc_attr_e( 'Hello, I need help', 'flexi-whatsapp-automation' ); ?>"></div>
		</div>
		<p><button type="button" class="button button-small fwa-remove-agent" style="color:#a00;"><?php esc_html_e( 'Remove Agent', 'flexi-whatsapp-automation' ); ?></button></p>
	</div>
</script>

<script type="text/javascript">
jQuery(document).ready(function($) {

	var agents = <?php echo wp_json_encode( ! empty( $s['agents'] ) ? $s['agents'] : array() ); ?>;

	function renderAgents() {
		var $wrap = $('#fwa-widget-agents');
		$wrap.empty();
		$.each(agents, function(i, a) {
			var $tpl = $($('#fwa-agent-template').html());
			$tpl.find('.fwa-agent-name').val(a.name || '');
			$tpl.find('.fwa-agent-phone').val(a.phone || '');
			$tpl.find('.fwa-agent-role').val(a.role || '');
			$tpl.find('.fwa-agent-availability').val(a.availability || '');
			$tpl.find('.fwa-agent-avatar').val(a.avatar_url || '');
			$tpl.find('.fwa-agent-message').val(a.default_message || '');
			$tpl.data('index', i);
			$wrap.append($tpl);
		});
	}

	renderAgents();

	$('#fwa-add-agent').on('click', function() {
		agents.push({ name:'', phone:'', role:'', availability:'', avatar_url:'', default_message:'' });
		renderAgents();
	});

	$(document).on('click', '.fwa-remove-agent', function() {
		var idx = $(this).closest('.fwa-agent-row').data('index');
		agents.splice(idx, 1);
		renderAgents();
	});

	function collectAgents() {
		var list = [];
		$('.fwa-agent-row').each(function() {
			list.push({
				name:            $(this).find('.fwa-agent-name').val(),
				phone:           $(this).find('.fwa-agent-phone').val(),
				role:            $(this).find('.fwa-agent-role').val(),
				availability:    $(this).find('.fwa-agent-availability').val(),
				avatar_url:      $(this).find('.fwa-agent-avatar').val(),
				default_message: $(this).find('.fwa-agent-message').val(),
			});
		});
		return list;
	}

	$('#fwa-save-widget-btn').on('click', function() {
		var social = {};
		$('.fwa-widget-social').each(function() {
			social[$(this).data('social')] = $(this).val();
		});

		var widget = $.extend({
			enabled:          $('#fwa-widget-enabled').is(':checked')  ? 'yes' : 'no',
			position:         $('#fwa-widget-position').val(),
			button_text:      $('#fwa-widget-button-text').val(),
			button_color:     $('#fwa-widget-button-color').val(),
			text_color:       $('#fwa-widget-text-color').val(),
			header_title:     $('#fwa-widget-header-title').val(),
			header_subtitle:  $('#fwa-widget-header-sub').val(),
			welcome_message:  $('#fwa-widget-welcome').val(),
			cta_message:      $('#fwa-widget-cta').val(),
			display_delay:    $('#fwa-widget-delay').val(),
			show_on_mobile:   $('#fwa-widget-mobile').is(':checked')   ? 'yes' : 'no',
			show_on_desktop:  $('#fwa-widget-desktop').is(':checked')  ? 'yes' : 'no',
			display_pages:    $('#fwa-widget-pages-include').val(),
			exclude_pages:    $('#fwa-widget-pages-exclude').val(),
			show_desktop_qr:  $('#fwa-widget-desktop-qr').is(':checked') ? 'yes' : 'no',
			analytics:        $('#fwa-widget-analytics').is(':checked') ? 'yes' : 'no',
			agents:           collectAgents(),
		}, social);

		$.post(fwa_admin.ajax_url, {
			action:      'fwa_save_widget_settings',
			nonce:       fwa_admin.nonce,
			widget:      JSON.stringify(widget),
		}, function(r) {
			if (r.success) {
				$('#fwa-widget-saved').slideDown().delay(3000).slideUp();
			} else {
				alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Error saving settings.', 'flexi-whatsapp-automation' ) ); ?>');
			}
		});
	});
});
</script>
