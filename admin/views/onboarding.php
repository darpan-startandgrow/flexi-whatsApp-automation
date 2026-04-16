<?php
/**
 * Onboarding wizard view.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fwa-wizard" id="fwa-wizard">
	<!-- Header -->
	<div class="fwa-wizard-header">
		<div class="fwa-wizard-brand">
			<span class="dashicons dashicons-format-chat"></span>
			<h1><?php esc_html_e( 'Flexi WhatsApp Automation', 'flexi-whatsapp-automation' ); ?></h1>
		</div>
		<button type="button" class="button" id="fwa-skip-wizard"><?php esc_html_e( 'Skip Setup', 'flexi-whatsapp-automation' ); ?></button>
	</div>

	<!-- Step Progress Indicator -->
	<div class="fwa-wizard-steps">
		<div class="fwa-wizard-step active" data-step="1">
			<span class="fwa-wizard-step-num">1</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Welcome', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="2">
			<span class="fwa-wizard-step-num">2</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'API Setup', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="3">
			<span class="fwa-wizard-step-num">3</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'WhatsApp', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="4">
			<span class="fwa-wizard-step-num">4</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Configure', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="5">
			<span class="fwa-wizard-step-num">5</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Automation', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="6">
			<span class="fwa-wizard-step-num">6</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Finish', 'flexi-whatsapp-automation' ); ?></span>
		</div>
	</div>

	<!-- Step Content -->
	<div class="fwa-wizard-content">

		<!-- Step 1: Welcome -->
		<div class="fwa-wizard-pane active" data-step="1">
			<div class="fwa-wizard-hero">
				<span class="dashicons dashicons-format-chat fwa-wizard-hero-icon"></span>
				<h2><?php esc_html_e( 'Welcome to WhatsApp Automation', 'flexi-whatsapp-automation' ); ?></h2>
				<p><?php esc_html_e( 'Automate your WhatsApp messaging, manage multiple accounts, and run bulk campaigns — all from your WordPress dashboard.', 'flexi-whatsapp-automation' ); ?></p>
			</div>
			<div class="fwa-wizard-benefits">
				<div class="fwa-wizard-benefit-card">
					<span class="dashicons dashicons-networking"></span>
					<h3><?php esc_html_e( 'Multi-Account', 'flexi-whatsapp-automation' ); ?></h3>
					<p><?php esc_html_e( 'Connect and manage multiple WhatsApp accounts from one dashboard.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-wizard-benefit-card">
					<span class="dashicons dashicons-controls-repeat"></span>
					<h3><?php esc_html_e( 'Smart Automation', 'flexi-whatsapp-automation' ); ?></h3>
					<p><?php esc_html_e( 'Trigger messages on WooCommerce events, user registrations, and more.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-wizard-benefit-card">
					<span class="dashicons dashicons-megaphone"></span>
					<h3><?php esc_html_e( 'Campaigns', 'flexi-whatsapp-automation' ); ?></h3>
					<p><?php esc_html_e( 'Send bulk messages and schedule campaigns with ease.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Step 2: API Connection -->
		<div class="fwa-wizard-pane" data-step="2">
			<div class="fwa-form-card">
				<h3><?php esc_html_e( 'Connect to WhatsApp API', 'flexi-whatsapp-automation' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Enter your API credentials to connect to the WhatsApp service.', 'flexi-whatsapp-automation' ); ?></p>
				<div class="fwa-form-group">
					<label for="fwa-ob-api-url"><?php esc_html_e( 'API Base URL', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="url" id="fwa-ob-api-url" class="regular-text" placeholder="https://api.example.com" value="<?php echo esc_attr( get_option( 'fwa_api_base_url', '' ) ); ?>">
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-api-key"><?php esc_html_e( 'API Key', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="password" id="fwa-ob-api-key" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your API key', 'flexi-whatsapp-automation' ); ?>" value="<?php echo esc_attr( get_option( 'fwa_global_api_token', '' ) ); ?>">
				</div>
				<button type="button" class="button button-primary" id="fwa-ob-validate-api">
					<?php esc_html_e( 'Validate Connection', 'flexi-whatsapp-automation' ); ?>
				</button>
				<div id="fwa-ob-api-status" class="fwa-wizard-status"></div>
			</div>
		</div>

		<!-- Step 3: WhatsApp Account -->
		<div class="fwa-wizard-pane" data-step="3">
			<div class="fwa-form-card">
				<h3><?php esc_html_e( 'Connect Your WhatsApp Account', 'flexi-whatsapp-automation' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Create an instance and scan the QR code with WhatsApp on your phone.', 'flexi-whatsapp-automation' ); ?></p>
				<div class="fwa-form-group">
					<label for="fwa-ob-instance-name"><?php esc_html_e( 'Instance Name', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-ob-instance-name" class="regular-text" placeholder="<?php esc_attr_e( 'My WhatsApp', 'flexi-whatsapp-automation' ); ?>">
				</div>
				<button type="button" class="button button-primary" id="fwa-ob-create-instance">
					<?php esc_html_e( 'Create Instance', 'flexi-whatsapp-automation' ); ?>
				</button>
				<div id="fwa-ob-qr-area" class="fwa-qr-area"></div>
				<div id="fwa-ob-connection-status" class="fwa-wizard-status"></div>
			</div>
		</div>

		<!-- Step 4: Default Configuration -->
		<div class="fwa-wizard-pane" data-step="4">
			<div class="fwa-form-card">
				<h3><?php esc_html_e( 'Configure Your Settings', 'flexi-whatsapp-automation' ); ?></h3>
				<div class="fwa-form-group">
					<label for="fwa-ob-default-instance"><?php esc_html_e( 'Default Instance', 'flexi-whatsapp-automation' ); ?></label>
					<select id="fwa-ob-default-instance" class="regular-text">
						<option value=""><?php esc_html_e( 'Select default instance', 'flexi-whatsapp-automation' ); ?></option>
					</select>
				</div>
				<h4><?php esc_html_e( 'Enable Features', 'flexi-whatsapp-automation' ); ?></h4>
				<div class="fwa-wizard-toggles">
					<label class="fwa-toggle-label">
						<input type="checkbox" id="fwa-ob-enable-automation" checked>
						<span><?php esc_html_e( 'Automation (trigger messages on events)', 'flexi-whatsapp-automation' ); ?></span>
					</label>
					<label class="fwa-toggle-label">
						<input type="checkbox" id="fwa-ob-enable-logging" checked>
						<span><?php esc_html_e( 'Logging (track activity and errors)', 'flexi-whatsapp-automation' ); ?></span>
					</label>
					<label class="fwa-toggle-label">
						<input type="checkbox" id="fwa-ob-enable-campaigns" checked>
						<span><?php esc_html_e( 'Campaigns (bulk messaging)', 'flexi-whatsapp-automation' ); ?></span>
					</label>
				</div>
			</div>
		</div>

		<!-- Step 5: Quick Automation -->
		<div class="fwa-wizard-pane" data-step="5">
			<div class="fwa-form-card">
				<h3><?php esc_html_e( 'Quick Start Automation', 'flexi-whatsapp-automation' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Enable common automation rules with one click. You can customize these later.', 'flexi-whatsapp-automation' ); ?></p>
				<div class="fwa-wizard-auto-cards">
					<div class="fwa-wizard-auto-card">
						<div class="fwa-wizard-auto-card-header">
							<label class="fwa-toggle-label">
								<input type="checkbox" id="fwa-ob-auto-woo">
								<strong><?php esc_html_e( 'WooCommerce Order Notification', 'flexi-whatsapp-automation' ); ?></strong>
							</label>
						</div>
						<div class="fwa-wizard-auto-card-body">
							<code><?php esc_html_e( 'Hi {customer_name}, your order #{order_id} for {order_total} has been placed!', 'flexi-whatsapp-automation' ); ?></code>
						</div>
					</div>
					<div class="fwa-wizard-auto-card">
						<div class="fwa-wizard-auto-card-header">
							<label class="fwa-toggle-label">
								<input type="checkbox" id="fwa-ob-auto-welcome">
								<strong><?php esc_html_e( 'Welcome Message for New Users', 'flexi-whatsapp-automation' ); ?></strong>
							</label>
						</div>
						<div class="fwa-wizard-auto-card-body">
							<code><?php esc_html_e( 'Welcome to {store_name}, {customer_name}! We are glad to have you.', 'flexi-whatsapp-automation' ); ?></code>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Step 6: Finish -->
		<div class="fwa-wizard-pane" data-step="6">
			<div class="fwa-wizard-hero">
				<span class="dashicons dashicons-yes-alt fwa-wizard-hero-icon fwa-wizard-hero-success"></span>
				<h2><?php esc_html_e( "You're All Set!", 'flexi-whatsapp-automation' ); ?></h2>
				<p><?php esc_html_e( 'Your WhatsApp automation is configured and ready to use.', 'flexi-whatsapp-automation' ); ?></p>
			</div>
			<div id="fwa-ob-summary" class="fwa-wizard-summary"></div>
			<div class="fwa-wizard-finish-actions">
				<div class="fwa-wizard-test-send">
					<h4><?php esc_html_e( 'Send a Test Message', 'flexi-whatsapp-automation' ); ?></h4>
					<div class="fwa-wizard-test-form">
						<input type="text" id="fwa-ob-test-phone" class="regular-text" placeholder="+1234567890">
						<button type="button" class="button" id="fwa-ob-send-test"><?php esc_html_e( 'Send Test', 'flexi-whatsapp-automation' ); ?></button>
					</div>
					<div id="fwa-ob-test-result" class="fwa-wizard-status"></div>
				</div>
				<div class="fwa-wizard-cta-buttons">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fwa-dashboard' ) ); ?>" class="button button-primary button-hero" id="fwa-ob-go-dashboard">
						<?php esc_html_e( 'Go to Dashboard', 'flexi-whatsapp-automation' ); ?>
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
