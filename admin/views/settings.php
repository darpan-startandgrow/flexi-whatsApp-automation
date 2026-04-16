<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tabs = array(
	'general'    => __( 'General', 'flexi-whatsapp-automation' ),
	'api'        => __( 'API Configuration', 'flexi-whatsapp-automation' ),
	'messaging'  => __( 'Messaging', 'flexi-whatsapp-automation' ),
	'woocommerce'=> __( 'WooCommerce', 'flexi-whatsapp-automation' ),
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
						<input type="text" name="fwa_default_country_code" value="<?php echo esc_attr( get_option( 'fwa_default_country_code', '+1' ) ); ?>" class="regular-text" placeholder="+1">
						<p class="description"><?php esc_html_e( 'Prepended to phone numbers that do not include a country code. Use the E.164 format with a leading "+" sign.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Examples:', 'flexi-whatsapp-automation' ); ?></strong> <?php esc_html_e( '+1 (USA/Canada), +44 (UK), +91 (India), +49 (Germany)', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Debug Mode', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<label><input type="checkbox" name="fwa_debug_mode" value="yes" <?php checked( get_option( 'fwa_debug_mode', 'no' ), 'yes' ); ?>>
						<?php esc_html_e( 'Enable debug logging', 'flexi-whatsapp-automation' ); ?></label>
						<p class="description"><?php esc_html_e( 'When enabled, detailed debug information is logged for every API request, webhook event, and message send. Useful for troubleshooting but may slow performance — disable in production.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Admin Alert Phone', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="tel" name="fwa_admin_alert_phone" value="<?php echo esc_attr( get_option( 'fwa_admin_alert_phone', '' ) ); ?>" class="regular-text" placeholder="+1234567890">
						<p class="description"><?php esc_html_e( 'Receive admin alerts (new orders, registrations, automation errors) on this WhatsApp number. Must be in E.164 format.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Example:', 'flexi-whatsapp-automation' ); ?></strong> <code>+14155552671</code>
						<?php esc_html_e( 'Leave blank to disable admin alerts.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
			</table>

		<?php elseif ( 'api' === $active_tab ) : ?>
			<?php
			$api_configured = 'yes' === get_option( 'fwa_api_configured', 'no' );
			$api_url        = get_option( 'fwa_api_base_url', '' );
			?>
			<?php if ( ! $api_configured || empty( $api_url ) ) : ?>
			<div class="fwa-not-configured" style="margin-bottom:20px;">
				<span class="dashicons dashicons-warning"></span>
				<div class="fwa-not-configured-text">
					<strong><?php esc_html_e( 'API Not Configured', 'flexi-whatsapp-automation' ); ?></strong>
					<p><?php esc_html_e( 'Enter your API credentials below and click "Test Connection" to verify. Messages will not be sent until the API is properly configured.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
			</div>
			<?php else : ?>
			<div class="fwa-info-box" style="margin-bottom:20px;border-left-color:#00a32a;">
				<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
				<div>
					<p><strong><?php esc_html_e( 'API Connected', 'flexi-whatsapp-automation' ); ?></strong> — <?php esc_html_e( 'Your API credentials are configured and were verified during setup.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
			</div>
			<?php endif; ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'API Base URL', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="url" name="fwa_api_base_url" id="fwa-api-base-url" value="<?php echo esc_attr( $api_url ); ?>" class="regular-text" placeholder="https://api.example.com">
						<p class="description"><?php esc_html_e( 'The full base URL of your WhatsApp API server. Do not include a trailing slash.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Examples:', 'flexi-whatsapp-automation' ); ?></strong> <code>https://waha.myserver.com</code>, <code>https://api.waengine.io</code>, <code>https://wa.mycompany.com:3000</code></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Global API Token', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="password" name="fwa_api_global_token" id="fwa-api-global-token" value="<?php echo esc_attr( get_option( 'fwa_api_global_token', '' ) ); ?>" class="regular-text">
						<button type="button" class="button" id="fwa-test-connection-btn" style="margin-left:8px;">
							<span class="dashicons dashicons-cloud" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;"></span>
							<?php esc_html_e( 'Test Connection', 'flexi-whatsapp-automation' ); ?>
						</button>
						<p class="description"><?php esc_html_e( 'Global access token for API authentication. This is used as a fallback when an instance does not have its own token.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Where to find it:', 'flexi-whatsapp-automation' ); ?></strong> <?php esc_html_e( 'Check your session engine dashboard or configuration file for the API key / bearer token.', 'flexi-whatsapp-automation' ); ?></p>
						<div id="fwa-connection-test-result" class="fwa-wizard-status" style="margin-top:8px;"></div>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Webhook Secret', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="text" name="fwa_webhook_secret" value="<?php echo esc_attr( get_option( 'fwa_webhook_secret', '' ) ); ?>" class="regular-text">
						<p class="description">
							<?php esc_html_e( 'Secret key for webhook verification. Set the same value in your session engine.', 'flexi-whatsapp-automation' ); ?>
							<br><strong><?php esc_html_e( 'Webhook URL:', 'flexi-whatsapp-automation' ); ?></strong>
							<code><?php echo esc_html( rest_url( 'fwa/v1/webhook' ) ); ?></code>
							<br><em><?php esc_html_e( 'Paste this URL into the webhook configuration of your session engine (WAHA, WA-Multi-Device, etc.).', 'flexi-whatsapp-automation' ); ?></em>
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
						<p class="description"><?php esc_html_e( 'Plain Secret: your engine sends the secret in the X-Webhook-Secret header. HMAC-SHA256: your engine signs the request body and sends the signature in X-Hub-Signature-256.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
			</table>

		<?php elseif ( 'woocommerce' === $active_tab ) : ?>
			<?php
			$wc_statuses = array(
				'processing' => __( 'Processing', 'flexi-whatsapp-automation' ),
				'completed'  => __( 'Completed', 'flexi-whatsapp-automation' ),
				'on-hold'    => __( 'On Hold', 'flexi-whatsapp-automation' ),
				'cancelled'  => __( 'Cancelled', 'flexi-whatsapp-automation' ),
				'refunded'   => __( 'Refunded', 'flexi-whatsapp-automation' ),
				'failed'     => __( 'Failed', 'flexi-whatsapp-automation' ),
			);
			$wc_notifications = get_option( 'fwa_wc_notifications', array() );
			if ( ! is_array( $wc_notifications ) ) {
				$wc_notifications = array();
			}
			?>
			<p class="description" style="margin-bottom:15px;">
				<?php esc_html_e( 'Configure automatic WhatsApp notifications to customers when their WooCommerce order status changes. Use placeholders: {customer_name}, {order_id}, {order_total}, {order_status}, {store_name}, {product_list}.', 'flexi-whatsapp-automation' ); ?>
			</p>
			<table class="form-table">
				<?php foreach ( $wc_statuses as $status_key => $status_label ) :
					$notif   = isset( $wc_notifications[ $status_key ] ) ? $wc_notifications[ $status_key ] : array();
					$enabled = ! empty( $notif['enabled'] );
					$message = isset( $notif['message'] ) ? $notif['message'] : '';
				?>
				<tr>
					<th><?php echo esc_html( $status_label ); ?></th>
					<td>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="fwa_wc_notifications[<?php echo esc_attr( $status_key ); ?>][enabled]" value="1" <?php checked( $enabled ); ?>>
							<?php
							/* translators: %s: order status label */
							printf( esc_html__( 'Send notification when order is %s', 'flexi-whatsapp-automation' ), esc_html( strtolower( $status_label ) ) );
							?>
						</label>
						<textarea name="fwa_wc_notifications[<?php echo esc_attr( $status_key ); ?>][message]" class="large-text" rows="2" placeholder="<?php
							/* translators: %s: order status */
							echo esc_attr( sprintf( __( 'Hi {customer_name}, your order #{order_id} is now %s. Thank you!', 'flexi-whatsapp-automation' ), strtolower( $status_label ) ) );
						?>"><?php echo esc_textarea( $message ); ?></textarea>
					</td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th><?php esc_html_e( 'Admin Order Alerts', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="fwa_wc_admin_order_alert" value="yes" <?php checked( get_option( 'fwa_wc_admin_order_alert', 'no' ), 'yes' ); ?>>
							<?php esc_html_e( 'Notify admin on new orders via WhatsApp', 'flexi-whatsapp-automation' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Uses the Admin Alert Phone number from the General tab.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
			</table>

		<?php elseif ( 'otp' === $active_tab ) : ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable OTP Login', 'flexi-whatsapp-automation' ); ?></th>
					<td><label><input type="checkbox" name="fwa_otp_enabled" value="yes" <?php checked( get_option( 'fwa_otp_enabled', 'yes' ), 'yes' ); ?>> <?php esc_html_e( 'Allow WhatsApp OTP-based login / signup', 'flexi-whatsapp-automation' ); ?></label>
					<p class="description"><?php esc_html_e( 'When enabled, users can log in or register using a one-time password sent to their WhatsApp number instead of a traditional email/password.', 'flexi-whatsapp-automation' ); ?></p></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Role-Based Redirects (JSON)', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<textarea name="fwa_otp_role_redirects" class="large-text" rows="4" placeholder='{"subscriber":"/my-account","customer":"/shop"}'><?php echo esc_textarea( get_option( 'fwa_otp_role_redirects', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'JSON object mapping WordPress user roles to redirect URLs after OTP login. Each key is a role slug and the value is the URL path.', 'flexi-whatsapp-automation' ); ?></p>
						<div class="fwa-info-box" style="margin-top:8px;">
							<span class="dashicons dashicons-info-outline"></span>
							<div>
								<p><strong><?php esc_html_e( 'Sample JSON:', 'flexi-whatsapp-automation' ); ?></strong></p>
								<code style="display:block;margin-top:4px;white-space:pre-wrap;background:#f6f7f7;padding:8px;border-radius:4px;">{
  "subscriber": "/my-account",
  "customer": "/shop",
  "editor": "/wp-admin",
  "administrator": "/wp-admin"
}</code>
								<p style="margin-top:8px;"><strong><?php esc_html_e( 'Available roles:', 'flexi-whatsapp-automation' ); ?></strong> subscriber, contributor, author, editor, administrator<?php if ( function_exists( 'wc_get_page_id' ) ) { ?>, customer, shop_manager<?php } ?></p>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Default Redirect URL', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="text" name="fwa_otp_default_redirect" class="regular-text" value="<?php echo esc_attr( get_option( 'fwa_otp_default_redirect', '' ) ); ?>" placeholder="/my-account">
						<p class="description"><?php esc_html_e( 'Fallback redirect URL after OTP login when no role-based redirect matches. Use a relative path (e.g., /my-account) or leave blank for homepage.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Welcome Message (New Users)', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<textarea name="fwa_otp_welcome_message" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Welcome to {site_name}, {name}! Your account has been created.', 'flexi-whatsapp-automation' ); ?>"><?php echo esc_textarea( get_option( 'fwa_otp_welcome_message', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Sent via WhatsApp to new users after their first OTP login. Leave blank to disable.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Available placeholders:', 'flexi-whatsapp-automation' ); ?></strong> <code>{name}</code>, <code>{phone}</code>, <code>{site_name}</code></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Admin Alert Phone', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="tel" name="fwa_otp_admin_alert_phone" class="regular-text" value="<?php echo esc_attr( get_option( 'fwa_otp_admin_alert_phone', '' ) ); ?>" placeholder="+1234567890">
						<p class="description"><?php esc_html_e( 'Receive a WhatsApp notification when users log in via OTP. Must be in E.164 format (e.g., +14155552671). Leave blank to disable.', 'flexi-whatsapp-automation' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Choose when to send admin alerts: on every OTP login, only when a new user registers, or never.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Phone Blacklist', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<textarea name="fwa_otp_phone_blacklist" class="large-text" rows="4" placeholder="<?php esc_attr_e( "+1234567890\n+0987654321", 'flexi-whatsapp-automation' ); ?>"><?php echo esc_textarea( get_option( 'fwa_otp_phone_blacklist', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One phone number per line in E.164 format. These numbers will be blocked from OTP login. Use this to prevent spam or abuse.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'WooCommerce Checkout OTP', 'flexi-whatsapp-automation' ); ?></th>
					<td><label><input type="checkbox" name="fwa_otp_wc_checkout" value="yes" <?php checked( get_option( 'fwa_otp_wc_checkout', 'no' ), 'yes' ); ?>> <?php esc_html_e( 'Require phone OTP verification on checkout', 'flexi-whatsapp-automation' ); ?></label>
					<p class="description"><?php esc_html_e( 'When enabled, customers must verify their phone number via WhatsApp OTP before completing checkout. Helps reduce fake orders.', 'flexi-whatsapp-automation' ); ?></p></td>
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
						<p class="description"><?php esc_html_e( 'Limits how many messages can be sent within the time window to avoid being rate-limited or banned by WhatsApp.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Recommended:', 'flexi-whatsapp-automation' ); ?></strong> <?php esc_html_e( '20 messages per 60 seconds for safe operation. Reduce if you experience delivery issues.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Campaign Message Delay', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_campaign_message_delay" value="<?php echo esc_attr( get_option( 'fwa_campaign_message_delay', 3 ) ); ?>" min="1" max="60" class="small-text">
						<?php esc_html_e( 'seconds between messages', 'flexi-whatsapp-automation' ); ?>
						<p class="description"><?php esc_html_e( 'Delay between sending each individual message in a campaign. Higher values reduce spam risk but make campaigns slower.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Recommended:', 'flexi-whatsapp-automation' ); ?></strong> <?php esc_html_e( '3–5 seconds for typical use. Increase to 10+ seconds for large campaigns (500+ recipients).', 'flexi-whatsapp-automation' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'When enabled, the plugin will attempt to reconnect WhatsApp instances that lose connection (e.g., due to phone reboot or network issues). This runs via WP-Cron.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Health Check Interval', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_health_check_interval" value="<?php echo esc_attr( get_option( 'fwa_health_check_interval', 5 ) ); ?>" min="1" max="60" class="small-text">
						<?php esc_html_e( 'minutes', 'flexi-whatsapp-automation' ); ?>
						<p class="description"><?php esc_html_e( 'How often to check instance connection status and trigger auto-reconnect. Lower values detect disconnections faster but increase server load.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Recommended:', 'flexi-whatsapp-automation' ); ?></strong> <?php esc_html_e( '5 minutes for most setups. Use 1–2 minutes for critical business use.', 'flexi-whatsapp-automation' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'In addition to the database logs (viewable in the Logs page), also write to log files in wp-content/fwa-logs/. Useful for server-level debugging with tools like tail or grep.', 'flexi-whatsapp-automation' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Only messages at this level or higher will be recorded. Debug captures everything (verbose). Error only captures failures.', 'flexi-whatsapp-automation' ); ?>
						<br><strong><?php esc_html_e( 'Recommended:', 'flexi-whatsapp-automation' ); ?></strong> <?php esc_html_e( '"Info" for normal use, "Debug" for troubleshooting, "Error" for production with minimal logging.', 'flexi-whatsapp-automation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Log Retention', 'flexi-whatsapp-automation' ); ?></th>
					<td>
						<input type="number" name="fwa_log_retention_days" value="<?php echo esc_attr( get_option( 'fwa_log_retention_days', 30 ) ); ?>" min="1" max="365" class="small-text">
						<?php esc_html_e( 'days', 'flexi-whatsapp-automation' ); ?>
						<p class="description"><?php esc_html_e( 'Logs older than this number of days are automatically deleted to keep the database clean. Set higher for compliance requirements.', 'flexi-whatsapp-automation' ); ?></p>
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
				window.fwaToast ? window.fwaToast(r.data ? r.data.message : '<?php echo esc_js( __( 'Error saving settings.', 'flexi-whatsapp-automation' ) ); ?>', 'error') : alert(r.data ? r.data.message : 'Error saving settings.');
			}
		});
	});

	/* API Connection Test on Settings page */
	$('#fwa-test-connection-btn').on('click', function(){
		var $btn = $(this);
		var url  = $('#fwa-api-base-url').val().trim();
		var key  = $('#fwa-api-global-token').val().trim();
		var $res = $('#fwa-connection-test-result');

		if (!url || !key) {
			$res.html('<div class="fwa-wizard-status-error"><span class="dashicons dashicons-dismiss"></span> <span><?php echo esc_js( __( 'Please enter both the API Base URL and API Token.', 'flexi-whatsapp-automation' ) ); ?></span></div>');
			return;
		}

		$btn.prop('disabled', true);
		$res.html('<div class="fwa-wizard-status-loading"><span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> <span><?php echo esc_js( __( 'Testing connection…', 'flexi-whatsapp-automation' ) ); ?></span></div>');

		$.post(fwa_admin.ajax_url, {
			action: 'fwa_test_api_connection',
			nonce: fwa_admin.nonce,
			api_url: url,
			api_key: key
		}, function(r){
			if (r.success) {
				$res.html('<div class="fwa-wizard-status-success"><span class="dashicons dashicons-yes-alt"></span> <span>' + (r.data ? r.data.message : '<?php echo esc_js( __( 'Connection successful!', 'flexi-whatsapp-automation' ) ); ?>') + '</span></div>');
			} else {
				$res.html('<div class="fwa-wizard-status-error"><span class="dashicons dashicons-dismiss"></span> <span>' + (r.data ? r.data.message : '<?php echo esc_js( __( 'Connection failed.', 'flexi-whatsapp-automation' ) ); ?>') + '</span></div>');
			}
		}).fail(function(){
			$res.html('<div class="fwa-wizard-status-error"><span class="dashicons dashicons-dismiss"></span> <span><?php echo esc_js( __( 'Network error. Please try again.', 'flexi-whatsapp-automation' ) ); ?></span></div>');
		}).always(function(){
			$btn.prop('disabled', false);
		});
	});
});
</script>
