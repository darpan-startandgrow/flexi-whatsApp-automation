<?php
/**
 * Onboarding wizard view.
 *
 * Phone number OTP verification is the primary onboarding method.
 * QR code connection is secondary but required for WhatsApp session.
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
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Phone Verify', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="2">
			<span class="fwa-wizard-step-num">2</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Enter OTP', 'flexi-whatsapp-automation' ); ?></span>
		</div>
		<div class="fwa-wizard-step-connector"></div>
		<div class="fwa-wizard-step" data-step="3">
			<span class="fwa-wizard-step-num">3</span>
			<span class="fwa-wizard-step-label"><?php esc_html_e( 'Connect WhatsApp', 'flexi-whatsapp-automation' ); ?></span>
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

		<!-- Step 1: Phone Number Entry -->
		<div class="fwa-wizard-pane active" data-step="1">
			<div class="fwa-wizard-hero">
				<span class="dashicons dashicons-smartphone fwa-wizard-hero-icon"></span>
				<h2><?php esc_html_e( 'Verify Your Phone Number', 'flexi-whatsapp-automation' ); ?></h2>
				<p><?php esc_html_e( 'Enter your WhatsApp phone number to get started. We will send a verification code to your WhatsApp (not SMS).', 'flexi-whatsapp-automation' ); ?></p>
			</div>
			<div class="fwa-form-card">
				<div class="fwa-otp-notice">
					<span class="dashicons dashicons-info-outline"></span>
					<p><?php esc_html_e( 'You will receive a WhatsApp message with a 6-digit code. Make sure WhatsApp is active on this phone number.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-phone"><?php esc_html_e( 'WhatsApp Phone Number', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="tel" id="fwa-ob-phone" class="regular-text" placeholder="+1234567890">
					<p class="description"><?php esc_html_e( 'Include country code (e.g. +1 for US, +91 for India).', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<button type="button" class="button button-primary" id="fwa-ob-send-otp">
					<?php esc_html_e( 'Send Verification Code', 'flexi-whatsapp-automation' ); ?>
				</button>
				<div id="fwa-ob-otp-send-status" class="fwa-wizard-status"></div>
				<div class="fwa-otp-skip-link">
					<a href="#" id="fwa-ob-skip-phone"><?php esc_html_e( 'Skip phone verification → Connect via QR code directly', 'flexi-whatsapp-automation' ); ?></a>
				</div>
			</div>
		</div>

		<!-- Step 2: OTP Verification -->
		<div class="fwa-wizard-pane" data-step="2">
			<div class="fwa-wizard-hero">
				<span class="dashicons dashicons-lock fwa-wizard-hero-icon"></span>
				<h2><?php esc_html_e( 'Enter Verification Code', 'flexi-whatsapp-automation' ); ?></h2>
				<p><?php esc_html_e( 'Check your WhatsApp messages and enter the 6-digit code below.', 'flexi-whatsapp-automation' ); ?></p>
			</div>
			<div class="fwa-form-card">
				<div class="fwa-form-group">
					<label for="fwa-ob-otp-code"><?php esc_html_e( 'Verification Code', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="text" id="fwa-ob-otp-code" class="regular-text fwa-otp-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}">
				</div>
				<div class="fwa-otp-actions">
					<button type="button" class="button button-primary" id="fwa-ob-verify-otp">
						<?php esc_html_e( 'Verify Code', 'flexi-whatsapp-automation' ); ?>
					</button>
					<button type="button" class="button" id="fwa-ob-resend-otp" disabled>
						<?php esc_html_e( 'Resend Code', 'flexi-whatsapp-automation' ); ?>
					</button>
				</div>
				<div id="fwa-ob-otp-verify-status" class="fwa-wizard-status"></div>
				<p class="description" id="fwa-ob-otp-timer"><?php esc_html_e( 'Code expires in 5:00', 'flexi-whatsapp-automation' ); ?></p>
			</div>
		</div>

		<!-- Step 3: WhatsApp Connection (QR Code) -->
		<div class="fwa-wizard-pane" data-step="3">
			<div class="fwa-form-card">
				<h3><?php esc_html_e( 'Connect Your WhatsApp Account', 'flexi-whatsapp-automation' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Now connect your WhatsApp account by scanning the QR code. This creates your messaging session.', 'flexi-whatsapp-automation' ); ?></p>
				<div class="fwa-otp-notice">
					<span class="dashicons dashicons-info-outline"></span>
					<p><?php esc_html_e( 'Phone verification only confirms ownership. You must still connect WhatsApp via QR code to enable messaging.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-instance-name"><?php esc_html_e( 'Instance Name', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-ob-instance-name" class="regular-text" placeholder="<?php esc_attr_e( 'My WhatsApp', 'flexi-whatsapp-automation' ); ?>">
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-api-url"><?php esc_html_e( 'API Base URL', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="url" id="fwa-ob-api-url" class="regular-text" placeholder="https://api.example.com" value="<?php echo esc_attr( get_option( 'fwa_api_base_url', '' ) ); ?>">
				</div>
				<div class="fwa-form-group">
					<label for="fwa-ob-api-key"><?php esc_html_e( 'API Key', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="password" id="fwa-ob-api-key" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your API key', 'flexi-whatsapp-automation' ); ?>" value="<?php echo esc_attr( get_option( 'fwa_global_api_token', '' ) ); ?>">
				</div>
				<div class="fwa-ob-qr-actions">
					<button type="button" class="button button-primary" id="fwa-ob-create-instance">
						<?php esc_html_e( 'Connect WhatsApp', 'flexi-whatsapp-automation' ); ?>
					</button>
				</div>
				<div id="fwa-ob-qr-area" class="fwa-qr-area"></div>
				<div id="fwa-ob-connection-status" class="fwa-wizard-status"></div>
			</div>
		</div>

		<!-- Step 4: Configuration -->
		<div class="fwa-wizard-pane" data-step="4">
			<div class="fwa-form-card">
				<h3><?php esc_html_e( 'Configure Your Settings', 'flexi-whatsapp-automation' ); ?></h3>
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

				<h4><?php esc_html_e( 'Quick Start Automation', 'flexi-whatsapp-automation' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Enable common automation rules. You can customize these later.', 'flexi-whatsapp-automation' ); ?></p>
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

		<!-- Step 5: Finish -->
		<div class="fwa-wizard-pane" data-step="5">
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
