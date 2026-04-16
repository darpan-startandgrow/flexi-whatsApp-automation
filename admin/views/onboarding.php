<?php
/**
 * Onboarding wizard view.
 *
 * Correct flow:
 *  Step 1 – API Configuration (URL + token → real-time connection test)
 *  Step 2 – Connect WhatsApp   (create instance + QR scan)
 *  Step 3 – Webhook & Active   (copy webhook URL, set secret, pick active instance)
 *  Step 4 – Configure          (feature toggles + quick-start automation rules)
 *  Step 5 – Test & Finish      (send test message + summary)
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$webhook_url = rest_url( 'fwa/v1/webhook' );
?>
<div class="fwa-wizard" id="fwa-wizard">
	<!-- Header -->
	<div class="fwa-wizard-header">
		<div class="fwa-wizard-brand">
			<span class="dashicons dashicons-format-chat"></span>
			<h1><?php esc_html_e( 'Flexi WhatsApp Automation', 'flexi-whatsapp-automation' ); ?></h1>
		</div>
		<button type="button" class="button fwa-btn-skip" id="fwa-skip-wizard">
			<span class="dashicons dashicons-controls-forward"></span>
			<?php esc_html_e( 'Skip Setup', 'flexi-whatsapp-automation' ); ?>
		</button>
	</div>

	<!-- Step Progress Indicator -->
	<div class="fwa-wizard-steps">
		<div class="fwa-wizard-step active" data-step="1">
			<span class="fwa-wizard-step-num">1</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'API Setup', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="2">
			<span class="fwa-wizard-step-num">2</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Connect', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="3">
			<span class="fwa-wizard-step-num">3</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Webhook', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="4">
			<span class="fwa-wizard-step-num">4</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Configure', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="5">
			<span class="fwa-wizard-step-num">5</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Finish', 'flexi-whatsapp-automation' ); ?></span>
		</div>
	</div>

	<!-- Step Content -->
	<div class="fwa-wizard-content">

		<!-- =====================================================================
		     Step 1: API Configuration
		     ===================================================================== -->
		<div class="fwa-wizard-pane active" data-step="1">
			<div class="fwa-wizard-hero">
				<span class="dashicons dashicons-admin-network fwa-wizard-hero-icon"></span>
				<h2><?php esc_html_e( 'API Configuration', 'flexi-whatsapp-automation' ); ?></h2>
				<p><?php esc_html_e( 'Enter the URL and API key of your WhatsApp session engine. A real-time connection test will verify your credentials before proceeding.', 'flexi-whatsapp-automation' ); ?></p>
			</div>
			<div class="fwa-form-card">
				<div class="fwa-info-box">
					<span class="dashicons dashicons-info-outline"></span>
					<div>
						<p><?php esc_html_e( 'If you are using a self-hosted session engine (e.g. WAHA, WA-Multi-Device), paste the base URL here. For a hosted service, enter the URL provided by your provider.', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-api-url"><?php esc_html_e( 'API Base URL', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="url" id="fwa-ob-api-url" class="regular-text" placeholder="https://api.example.com" value="<?php echo esc_attr( get_option( 'fwa_api_base_url', '' ) ); ?>">
					<p class="description"><?php esc_html_e( 'The full base URL of your WhatsApp API server without a trailing slash. Example: https://waha.myserver.com or https://api.waengine.io', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-api-key"><?php esc_html_e( 'API Key', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<div class="fwa-input-with-toggle">
						<input type="password" id="fwa-ob-api-key" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. sk_live_abc123def456...', 'flexi-whatsapp-automation' ); ?>" value="<?php echo esc_attr( get_option( 'fwa_api_global_token', '' ) ); ?>">
						<button type="button" class="button fwa-toggle-password" data-target="fwa-ob-api-key" title="<?php esc_attr_e( 'Toggle visibility', 'flexi-whatsapp-automation' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<p class="description"><?php esc_html_e( 'Your global API authentication key or bearer token. This is provided by your WhatsApp API service.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-form-actions">
					<button type="button" class="button button-primary button-large" id="fwa-ob-save-api">
						<span class="dashicons dashicons-cloud"></span>
						<?php esc_html_e( 'Save & Check Connection', 'flexi-whatsapp-automation' ); ?>
					</button>
				</div>
				<div id="fwa-ob-api-status" class="fwa-wizard-status"></div>
			</div>
		</div>

		<!-- =====================================================================
		     Step 2: Connect WhatsApp (Create Instance + QR)
		     ===================================================================== -->
		<div class="fwa-wizard-pane" data-step="2">
			<div class="fwa-form-card">
				<div class="fwa-card-header">
					<span class="dashicons dashicons-smartphone fwa-card-icon"></span>
					<div>
						<h3><?php esc_html_e( 'Connect Your WhatsApp Account', 'flexi-whatsapp-automation' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Create a WhatsApp instance and scan the QR code with the WhatsApp app on your phone.', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>
				<div class="fwa-info-box">
					<span class="dashicons dashicons-info-outline"></span>
					<div>
						<p><strong><?php esc_html_e( 'How to connect:', 'flexi-whatsapp-automation' ); ?></strong>
						<?php esc_html_e( 'Open WhatsApp on your phone → Tap ⋮ Menu (three dots) → Linked Devices → Link a Device → Scan the QR code below.', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-instance-name"><?php esc_html_e( 'Instance Name', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-ob-instance-name" class="regular-text" value="My WhatsApp" placeholder="<?php esc_attr_e( 'e.g. Support Line, Sales Team', 'flexi-whatsapp-automation' ); ?>">
					<p class="description"><?php esc_html_e( 'A friendly label to identify this WhatsApp number (e.g., "Support Line" or "Sales Team").', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-form-actions">
					<button type="button" class="button button-primary button-large" id="fwa-ob-create-instance">
						<span class="dashicons dashicons-admin-links"></span>
						<?php esc_html_e( 'Connect WhatsApp', 'flexi-whatsapp-automation' ); ?>
					</button>
				</div>
				<div id="fwa-ob-connection-status" class="fwa-wizard-status"></div>
				<div id="fwa-ob-qr-area" class="fwa-qr-area"></div>
			</div>
		</div>

		<!-- =====================================================================
		     Step 3: Webhook URL & Set Active Instance
		     ===================================================================== -->
		<div class="fwa-wizard-pane" data-step="3">
			<div class="fwa-form-card">
				<div class="fwa-card-header">
					<span class="dashicons dashicons-rest-api fwa-card-icon"></span>
					<div>
						<h3><?php esc_html_e( 'Webhook & Active Instance', 'flexi-whatsapp-automation' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Configure the webhook so your session engine can push incoming messages, delivery receipts, and session status updates to WordPress.', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>

				<!-- Webhook URL -->
				<div class="fwa-form-group">
					<label><?php esc_html_e( 'Your Webhook URL', 'flexi-whatsapp-automation' ); ?></label>
					<div class="fwa-input-with-action">
						<input type="text" id="fwa-ob-webhook-url" class="regular-text" value="<?php echo esc_attr( $webhook_url ); ?>" readonly>
						<button type="button" class="button" id="fwa-ob-copy-webhook">
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e( 'Copy', 'flexi-whatsapp-automation' ); ?>
						</button>
					</div>
					<p class="description"><?php esc_html_e( 'Copy this URL and paste it into the "Webhook URL" field of your session engine (WAHA, WA-Multi-Device, etc.). This allows the engine to send events to your WordPress site.', 'flexi-whatsapp-automation' ); ?></p>
				</div>

				<!-- Webhook Secret -->
				<div class="fwa-form-group">
					<label for="fwa-ob-webhook-secret"><?php esc_html_e( 'Webhook Secret', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-ob-webhook-secret" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. my-secret-key-123', 'flexi-whatsapp-automation' ); ?>" value="<?php echo esc_attr( get_option( 'fwa_webhook_secret', '' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Set the same secret in your session engine as the X-Webhook-Secret header value. This ensures only legitimate requests are accepted. Leave blank to accept all requests (not recommended for production).', 'flexi-whatsapp-automation' ); ?></p>
				</div>

				<div class="fwa-form-actions">
					<button type="button" class="button button-primary" id="fwa-ob-save-webhook">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Webhook Settings', 'flexi-whatsapp-automation' ); ?>
					</button>
				</div>
				<div id="fwa-ob-webhook-status" class="fwa-wizard-status"></div>

				<!-- Set Active Instance -->
				<div class="fwa-section-divider"></div>
				<div class="fwa-card-header">
					<span class="dashicons dashicons-star-filled fwa-card-icon"></span>
					<div>
						<h4><?php esc_html_e( 'Set Active Instance', 'flexi-whatsapp-automation' ); ?></h4>
						<p class="description"><?php esc_html_e( 'The active instance is used by default for all outgoing messages and automation rules. Select the instance you want to use.', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>
				<div id="fwa-ob-instance-picker">
					<div class="fwa-loading-state">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Loading instances…', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>
				<div id="fwa-ob-active-status" class="fwa-wizard-status"></div>
			</div>
		</div>

		<!-- =====================================================================
		     Step 4: Configuration
		     ===================================================================== -->
		<div class="fwa-wizard-pane" data-step="4">
			<div class="fwa-form-card">
				<div class="fwa-card-header">
					<span class="dashicons dashicons-admin-generic fwa-card-icon"></span>
					<div>
						<h3><?php esc_html_e( 'Configure Your Settings', 'flexi-whatsapp-automation' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Choose which features to enable. You can change these anytime from Settings.', 'flexi-whatsapp-automation' ); ?></p>
					</div>
				</div>

				<h4><?php esc_html_e( 'Enable Features', 'flexi-whatsapp-automation' ); ?></h4>
				<div class="fwa-wizard-toggles">
					<label class="fwa-toggle-label">
						<input type="checkbox" id="fwa-ob-enable-automation" checked>
						<span class="fwa-toggle-slider"></span>
						<span class="fwa-toggle-text">
							<strong><?php esc_html_e( 'Automation', 'flexi-whatsapp-automation' ); ?></strong>
							<small><?php esc_html_e( 'Trigger WhatsApp messages on WordPress events (orders, registrations, etc.)', 'flexi-whatsapp-automation' ); ?></small>
						</span>
					</label>
					<label class="fwa-toggle-label">
						<input type="checkbox" id="fwa-ob-enable-logging" checked>
						<span class="fwa-toggle-slider"></span>
						<span class="fwa-toggle-text">
							<strong><?php esc_html_e( 'Logging', 'flexi-whatsapp-automation' ); ?></strong>
							<small><?php esc_html_e( 'Track all activity, errors, and message delivery for debugging', 'flexi-whatsapp-automation' ); ?></small>
						</span>
					</label>
					<label class="fwa-toggle-label">
						<input type="checkbox" id="fwa-ob-enable-campaigns" checked>
						<span class="fwa-toggle-slider"></span>
						<span class="fwa-toggle-text">
							<strong><?php esc_html_e( 'Campaigns', 'flexi-whatsapp-automation' ); ?></strong>
							<small><?php esc_html_e( 'Send bulk messages to groups of contacts with delivery tracking', 'flexi-whatsapp-automation' ); ?></small>
						</span>
					</label>
				</div>

				<div class="fwa-section-divider"></div>

				<h4><?php esc_html_e( 'Quick Start Automation Rules', 'flexi-whatsapp-automation' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Enable common automation rules to get started quickly. You can customize templates and add more rules from the Automation page later.', 'flexi-whatsapp-automation' ); ?></p>
				<div class="fwa-wizard-auto-cards">
					<div class="fwa-wizard-auto-card">
						<div class="fwa-wizard-auto-card-header">
							<label class="fwa-toggle-label">
								<input type="checkbox" id="fwa-ob-auto-woo">
								<span class="fwa-toggle-slider"></span>
								<strong><?php esc_html_e( 'WooCommerce Order Notification', 'flexi-whatsapp-automation' ); ?></strong>
							</label>
						</div>
						<div class="fwa-wizard-auto-card-body">
							<p class="description"><?php esc_html_e( 'Automatically notify customers via WhatsApp when a new order is placed.', 'flexi-whatsapp-automation' ); ?></p>
							<code>Hi {customer_name}, your order #{order_id} for {order_total} has been placed!</code>
						</div>
					</div>
					<div class="fwa-wizard-auto-card">
						<div class="fwa-wizard-auto-card-header">
							<label class="fwa-toggle-label">
								<input type="checkbox" id="fwa-ob-auto-welcome">
								<span class="fwa-toggle-slider"></span>
								<strong><?php esc_html_e( 'Welcome Message for New Users', 'flexi-whatsapp-automation' ); ?></strong>
							</label>
						</div>
						<div class="fwa-wizard-auto-card-body">
							<p class="description"><?php esc_html_e( 'Send a welcome WhatsApp message when a new user registers on your site.', 'flexi-whatsapp-automation' ); ?></p>
							<code>Welcome to {store_name}, {customer_name}! We are glad to have you.</code>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- =====================================================================
		     Step 5: Finish
		     ===================================================================== -->
		<div class="fwa-wizard-pane" data-step="5">
			<div class="fwa-wizard-hero">
				<span class="dashicons dashicons-yes-alt fwa-wizard-hero-icon fwa-wizard-hero-success"></span>
				<h2><?php esc_html_e( "You're All Set!", 'flexi-whatsapp-automation' ); ?></h2>
				<p><?php esc_html_e( 'Your WhatsApp automation is configured and ready to use.', 'flexi-whatsapp-automation' ); ?></p>
			</div>
			<div id="fwa-ob-summary" class="fwa-wizard-summary"></div>
			<div class="fwa-wizard-finish-actions">
				<div class="fwa-form-card">
					<div class="fwa-card-header">
						<span class="dashicons dashicons-email-alt fwa-card-icon"></span>
						<div>
							<h4><?php esc_html_e( 'Send a Test Message', 'flexi-whatsapp-automation' ); ?></h4>
							<p class="description"><?php esc_html_e( 'Send a quick test to confirm end-to-end messaging is working correctly.', 'flexi-whatsapp-automation' ); ?></p>
						</div>
					</div>
					<div class="fwa-wizard-test-form">
						<input type="tel" id="fwa-ob-test-phone" class="regular-text" placeholder="<?php esc_attr_e( '+1234567890', 'flexi-whatsapp-automation' ); ?>">
						<button type="button" class="button button-primary" id="fwa-ob-send-test">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Send Test', 'flexi-whatsapp-automation' ); ?>
						</button>
					</div>
					<div id="fwa-ob-test-result" class="fwa-wizard-status"></div>
				</div>
				<div class="fwa-wizard-cta-buttons">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-dashboard' ) ); ?>" class="button button-primary button-hero" id="fwa-ob-go-dashboard">
						<?php esc_html_e( 'Go to Dashboard →', 'flexi-whatsapp-automation' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<!-- Footer Navigation -->
	<div class="fwa-wizard-footer">
		<button type="button" class="button" id="fwa-wizard-prev"><?php esc_html_e( '← Back', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button button-primary" id="fwa-wizard-next"><?php esc_html_e( 'Continue →', 'flexi-whatsapp-automation' ); ?></button>
	</div>
</div>
