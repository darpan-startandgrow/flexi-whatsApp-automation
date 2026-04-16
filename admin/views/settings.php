<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tabs = array(
	'general'    => __( 'General', 'flexi-whatsapp-automation' ),
	'api'        => __( 'API Configuration', 'flexi-whatsapp-automation' ),
	'messaging'  => __( 'Messaging', 'flexi-whatsapp-automation' ),
	'otp'        => __( 'OTP Login', 'flexi-whatsapp-automation' ),
	'automation' => __( 'Automation', 'flexi-whatsapp-automation' ),
	'advanced'   => __( 'Advanced', 'flexi-whatsapp-automation' ),
);
$tabs = apply_filters( 'fwa_settings_tabs', $tabs );

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! array_key_exists( $active_tab, $tabs ) ) {
	$active_tab = 'general';
}
?>
<div class="fwa-settings-page">
	<h2><?php esc_html_e( 'Settings', 'flexi-whatsapp-automation' ); ?></h2>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="" id="fwa-settings-form" class="fwa-form-card" style="margin-top:15px;">
		<?php wp_nonce_field( 'fwa_save_settings', 'fwa_settings_nonce' ); ?>
		<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">

		<?php if ( 'general' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Default Country Code', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="text" name="fwa_default_country_code" value="<?php echo esc_attr( get_option( 'fwa_default_country_code', '' ) ); ?>" class="regular-text" placeholder="+1">
						<p class="description"><?php esc_html_e( 'Default country code for phone numbers without one.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Debug Mode', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<label><input type="checkbox" name="fwa_debug_mode" value="yes" <?php checked( get_option( 'fwa_debug_mode', 'no' ), 'yes' ); ?>>
						<?php esc_html_e( 'Enable debug logging', 'flexi-whatsapp-automation' ); ?></label>
					</td>
				</tr>
			</table>

		<?php elseif ( 'api' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'API Base URL', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="url" name="fwa_api_base_url" value="<?php echo esc_attr( get_option( 'fwa_api_base_url', '' ) ); ?>" class="regular-text" placeholder="https://api.example.com">
						<p class="description"><?php esc_html_e( 'Base URL for the WhatsApp API service.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Global API Token', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="password" name="fwa_api_global_token" value="<?php echo esc_attr( get_option( 'fwa_api_global_token', '' ) ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Global access token (used when instance has no token).', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Webhook Secret', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="text" name="fwa_webhook_secret" value="<?php echo esc_attr( get_option( 'fwa_webhook_secret', '' ) ); ?>" class="regular-text">
						<p class="description">
							<?php esc_html_e( 'Secret key for webhook verification.', 'flexi-whatsapp-automation' ); ?>
							<?php esc_html_e( 'Webhook URL:', 'flexi-whatsapp-automation' ); ?>
							<code><?php echo esc_html( rest_url( 'fwa/v1/webhook' ) ); ?></code>
						</p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Webhook Signature Mode', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<select name="fwa_webhook_signature_mode">
							<option value="secret" <?php selected( get_option( 'fwa_webhook_signature_mode', 'secret' ), 'secret' ); ?>><?php esc_html_e( 'Plain Secret (X-Webhook-Secret header)', 'flexi-whatsapp-automation' ); ?></option>
							<option value="hmac"   <?php selected( get_option( 'fwa_webhook_signature_mode', 'secret' ), 'hmac' ); ?>><?php esc_html_e( 'HMAC-SHA256 (X-Hub-Signature-256)', 'flexi-whatsapp-automation' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'HMAC mode verifies a SHA-256 HMAC of the raw request body using the secret above.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
			</table>

		<?php elseif ( 'otp' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable OTP Login', 'flexi-whatsapp-automation' ); ?></th>
					<td><label><input type="checkbox" name="fwa_otp_enabled" value="yes" <?php checked( get_option( 'fwa_otp_enabled', 'yes' ), 'yes' ); ?>> <?php esc_html_e( 'Allow WhatsApp OTP-based login / signup', 'flexi-whatsapp-automation' ); ?></label></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Role-Based Redirects (JSON)', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<textarea name="fwa_otp_role_redirects" class="large-text" rows="4" placeholder='{"subscriber":"/my-account","customer":"/shop"}'><?php echo esc_textarea( get_option( 'fwa_otp_role_redirects', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'JSON mapping role to URL. Overrides default redirect.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Default Redirect URL', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="text" name="fwa_otp_default_redirect" class="regular-text" value="<?php echo esc_attr( get_option( 'fwa_otp_default_redirect', '' ) ); ?>" placeholder="/my-account">
						<p class="description"><?php esc_html_e( 'Leave blank for homepage.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Welcome Message (New Users)', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<textarea name="fwa_otp_welcome_message" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'fwa_otp_welcome_message', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Placeholders: {name}, {phone}, {site_name}. Leave blank to disable.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Admin Alert Phone', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="tel" name="fwa_otp_admin_alert_phone" class="regular-text" value="<?php echo esc_attr( get_option( 'fwa_otp_admin_alert_phone', '' ) ); ?>" placeholder="+1234567890">
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Admin Alert Events', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<select name="fwa_otp_admin_alert_events">
							<option value="all"      <?php selected( get_option( 'fwa_otp_admin_alert_events', 'all' ), 'all' ); ?>><?php esc_html_e( 'All Logins', 'flexi-whatsapp-automation' ); ?></option>
							<option value="new_only" <?php selected( get_option( 'fwa_otp_admin_alert_events', 'all' ), 'new_only' ); ?>><?php esc_html_e( 'New Registrations Only', 'flexi-whatsapp-automation' ); ?></option>
							<option value="none"     <?php selected( get_option( 'fwa_otp_admin_alert_events', 'all' ), 'none' ); ?>><?php esc_html_e( 'Disable', 'flexi-whatsapp-automation' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Phone Blacklist', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<textarea name="fwa_otp_phone_blacklist" class="large-text" rows="4"><?php echo esc_textarea( get_option( 'fwa_otp_phone_blacklist', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One phone number per line. These numbers are blocked from OTP login.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'WooCommerce Checkout OTP', 'flexi-whatsapp-automation' ); ?></th>
					<td><label><input type="checkbox" name="fwa_otp_wc_checkout" value="yes" <?php checked( get_option( 'fwa_otp_wc_checkout', 'no' ), 'yes' ); ?>> <?php esc_html_e( 'Require phone OTP verification on checkout', 'flexi-whatsapp-automation' ); ?></label></td>
				</tr>
			</table>

		<?php elseif ( 'messaging' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Rate Limit', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_rate_limit" value="<?php echo esc_attr( get_option( 'fwa_rate_limit', 20 ) ); ?>" min="1" max="100" class="small-text">
						<?php esc_html_e( 'messages per', 'flexi-whatsapp-automation' ); ?>
						<input type="number" name="fwa_rate_limit_interval" value="<?php echo esc_attr( get_option( 'fwa_rate_limit_interval', 60 ) ); ?>" min="10" max="3600" class="small-text">
						<?php esc_html_e( 'seconds', 'flexi-whatsapp-automation' ); ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Campaign Message Delay', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_campaign_message_delay" value="<?php echo esc_attr( get_option( 'fwa_campaign_message_delay', 3 ) ); ?>" min="1" max="60" class="small-text">
						<?php esc_html_e( 'seconds between messages', 'flexi-whatsapp-automation' ); ?>
					</td>
				</tr>
			</table>

		<?php elseif ( 'automation' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Auto Reconnect', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<label><input type="checkbox" name="fwa_auto_reconnect" value="yes" <?php checked( get_option( 'fwa_auto_reconnect', 'yes' ), 'yes' ); ?>>
						<?php esc_html_e( 'Automatically reconnect disconnected instances', 'flexi-whatsapp-automation' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Health Check Interval', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_health_check_interval" value="<?php echo esc_attr( get_option( 'fwa_health_check_interval', 5 ) ); ?>" min="1" max="60" class="small-text">
						<?php esc_html_e( 'minutes', 'flexi-whatsapp-automation' ); ?>
					</td>
				</tr>
			</table>

		<?php elseif ( 'advanced' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'File Logging', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<label><input type="checkbox" name="fwa_file_logging" value="yes" <?php checked( get_option( 'fwa_file_logging', 'no' ), 'yes' ); ?>>
						<?php esc_html_e( 'Also write logs to files', 'flexi-whatsapp-automation' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Log Level', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<select name="fwa_log_level">
							<?php foreach ( array( 'debug', 'info', 'warning', 'error', 'critical' ) as $lvl ) : ?>
								<option value="<?php echo esc_attr( $lvl ); ?>" <?php selected( get_option( 'fwa_log_level', 'info' ), $lvl ); ?>><?php echo esc_html( ucfirst( $lvl ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Log Retention', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_log_retention_days" value="<?php echo esc_attr( get_option( 'fwa_log_retention_days', 30 ) ); ?>" min="1" max="365" class="small-text">
						<?php esc_html_e( 'days', 'flexi-whatsapp-automation' ); ?>
					</td>
				</tr>
			</table>

		<?php endif; ?>

		<?php
		/**
		 * Fires after settings tab content is rendered.
		 *
		 * @since 1.0.0
		 * @param string $active_tab Current tab ID.
		 */
		do_action( 'fwa_render_settings_tab_' . $active_tab );
		?>

		<p class="submit">
			<button type="button" class="button button-primary" id="fwa-save-settings-btn"><?php esc_html_e( 'Save Settings', 'flexi-whatsapp-automation' ); ?></button>
		</p>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($){
	$('#fwa-save-settings-btn').on('click', function(){
		var $btn = $(this).prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'flexi-whatsapp-automation' ) ); ?>');
		var data = $('#fwa-settings-form').serialize();
		data += '&action=fwa_save_settings&nonce=' + fwa_admin.nonce;
		$.post(fwa_admin.ajax_url, data, function(r){
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save Settings', 'flexi-whatsapp-automation' ) ); ?>');
			if (r.success) {
				$('<div class="notice notice-success is-dismissible"><p><?php echo esc_js( __( 'Settings saved.', 'flexi-whatsapp-automation' ) ); ?></p></div>')
					.insertBefore('#fwa-settings-form').delay(3000).fadeOut();
			} else {
				alert(r.data ? r.data.message : 'Error saving settings.');
			}
		});
	});
});
</script>
