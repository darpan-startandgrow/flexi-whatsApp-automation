/**
 * Flexi WhatsApp Automation – Onboarding Wizard JavaScript
 *
 * Phone number OTP verification is the primary onboarding method.
 * QR code connection is secondary but required for WhatsApp session.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

/* global jQuery, fwa_admin */

(function ($) {
	'use strict';

	var Wizard = {
		currentStep: 1,
		totalSteps: 5,
		phoneVerified: false,
		instanceCreated: false,
		qrTimer: null,
		otpTimer: null,
		otpCountdown: 300,
		verifiedPhone: '',

		init: function () {
			this.bindEvents();
			this.updateUI();
		},

		bindEvents: function () {
			var self = this;

			$('#fwa-wizard-next').on('click', function () {
				self.nextStep();
			});
			$('#fwa-wizard-prev').on('click', function () {
				self.prevStep();
			});
			$('#fwa-skip-wizard').on('click', function () {
				self.skipWizard();
			});

			// Step 1: Phone entry + OTP send.
			$('#fwa-ob-send-otp').on('click', function () {
				self.sendOTP();
			});
			$('#fwa-ob-skip-phone').on('click', function (e) {
				e.preventDefault();
				self.skipPhoneVerification();
			});

			// Step 2: OTP verification.
			$('#fwa-ob-verify-otp').on('click', function () {
				self.verifyOTP();
			});
			$('#fwa-ob-resend-otp').on('click', function () {
				self.sendOTP();
			});

			// Step 3: Instance creation + QR.
			$('#fwa-ob-create-instance').on('click', function () {
				self.createInstance();
			});

			// Step 5: Test message and go to dashboard.
			$('#fwa-ob-send-test').on('click', function () {
				self.sendTest();
			});
			$('#fwa-ob-go-dashboard').on('click', function (e) {
				e.preventDefault();
				self.completeOnboarding($(this).attr('href'));
			});

			// Allow Enter key on OTP input.
			$('#fwa-ob-otp-code').on('keypress', function (e) {
				if (e.which === 13) {
					self.verifyOTP();
				}
			});

			// Allow Enter key on phone input.
			$('#fwa-ob-phone').on('keypress', function (e) {
				if (e.which === 13) {
					self.sendOTP();
				}
			});
		},

		nextStep: function () {
			// Gate checks.
			if (this.currentStep === 1 && !this.phoneVerified) {
				this.showStatus('#fwa-ob-otp-send-status', 'error', fwa_admin.strings.otp_required || 'Please verify your phone number first, or skip this step.');
				return;
			}

			if (this.currentStep === 4) {
				this.saveConfig();
				this.saveAutomation();
			}

			if (this.currentStep < this.totalSteps) {
				this.currentStep++;
				this.updateUI();
			}
		},

		prevStep: function () {
			if (this.currentStep > 1) {
				this.currentStep--;
				this.updateUI();
			}
		},

		updateUI: function () {
			var step = this.currentStep;

			// Update step indicators.
			$('.fwa-wizard-step').each(function () {
				var s = parseInt($(this).data('step'), 10);
				$(this).removeClass('active completed');
				if (s === step) {
					$(this).addClass('active');
				} else if (s < step) {
					$(this).addClass('completed');
				}
			});

			// Show correct pane.
			$('.fwa-wizard-pane').removeClass('active');
			$('.fwa-wizard-pane[data-step="' + step + '"]').addClass('active');

			// Update navigation buttons.
			$('#fwa-wizard-prev').toggle(step > 1 && step < this.totalSteps);
			$('#fwa-wizard-next').toggle(step < this.totalSteps);

			// Hide Next on steps that require user action.
			if (step === 1 || step === 2) {
				$('#fwa-wizard-next').toggle(false);
			}

			if (step === 1 && this.phoneVerified) {
				$('#fwa-wizard-next').toggle(true);
			}

			// Stop QR polling when leaving step 3.
			if (step !== 3 && this.qrTimer) {
				clearInterval(this.qrTimer);
				this.qrTimer = null;
			}

			// Stop OTP timer when leaving step 2.
			if (step !== 2 && this.otpTimer) {
				clearInterval(this.otpTimer);
				this.otpTimer = null;
			}

			// Pre-fill test phone on step 5.
			if (step === 5) {
				this.buildSummary();
				if (this.verifiedPhone) {
					$('#fwa-ob-test-phone').val(this.verifiedPhone);
				}
			}
		},

		/* =================================================================
		   Step 1 & 2: OTP Flow
		   ================================================================= */

		sendOTP: function () {
			var self = this;
			var phone = $('#fwa-ob-phone').val().trim();

			if (!phone) {
				this.showStatus('#fwa-ob-otp-send-status', 'error', 'Please enter your WhatsApp phone number.');
				return;
			}

			var $btn = $('#fwa-ob-send-otp');
			$btn.prop('disabled', true).text(fwa_admin.strings.sending_otp || 'Sending…');
			this.showStatus('#fwa-ob-otp-send-status', 'loading', 'Sending verification code via WhatsApp…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_send_otp',
				nonce: fwa_admin.nonce,
				phone: phone
			}, function (r) {
				if (r.success) {
					self.verifiedPhone = phone;
					self.showStatus('#fwa-ob-otp-send-status', 'success', r.data.message);
					// Move to step 2.
					self.currentStep = 2;
					self.updateUI();
					self.startOTPCountdown();
					$('#fwa-ob-otp-code').focus();
				} else {
					self.showStatus('#fwa-ob-otp-send-status', 'error', r.data ? r.data.message : 'Failed to send code.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-otp-send-status', 'error', 'Network error. Please try again.');
			}).always(function () {
				$btn.prop('disabled', false).text(fwa_admin.strings.send_otp || 'Send Verification Code');
			});
		},

		verifyOTP: function () {
			var self = this;
			var otp = $('#fwa-ob-otp-code').val().trim();

			if (!otp || otp.length !== 6) {
				this.showStatus('#fwa-ob-otp-verify-status', 'error', 'Please enter the 6-digit code.');
				return;
			}

			var $btn = $('#fwa-ob-verify-otp');
			$btn.prop('disabled', true).text('Verifying…');
			this.showStatus('#fwa-ob-otp-verify-status', 'loading', 'Verifying code…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_verify_otp',
				nonce: fwa_admin.nonce,
				phone: this.verifiedPhone,
				otp: otp
			}, function (r) {
				if (r.success) {
					self.phoneVerified = true;
					self.showStatus('#fwa-ob-otp-verify-status', 'success', r.data.message);

					// Stop countdown.
					if (self.otpTimer) {
						clearInterval(self.otpTimer);
						self.otpTimer = null;
					}

					// Auto-advance to step 3 after a brief delay.
					setTimeout(function () {
						self.currentStep = 3;
						self.updateUI();
					}, 1500);
				} else {
					self.showStatus('#fwa-ob-otp-verify-status', 'error', r.data ? r.data.message : 'Verification failed.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-otp-verify-status', 'error', 'Network error. Please try again.');
			}).always(function () {
				$btn.prop('disabled', false).text('Verify Code');
			});
		},

		startOTPCountdown: function () {
			var self = this;
			this.otpCountdown = 300; // 5 minutes.

			// Disable resend button initially.
			$('#fwa-ob-resend-otp').prop('disabled', true);

			if (this.otpTimer) {
				clearInterval(this.otpTimer);
			}

			this.otpTimer = setInterval(function () {
				self.otpCountdown--;

				var minutes = Math.floor(self.otpCountdown / 60);
				var seconds = self.otpCountdown % 60;
				var display = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

				$('#fwa-ob-otp-timer').text(
					(fwa_admin.strings.code_expires || 'Code expires in') + ' ' + display
				);

				// Enable resend after 30 seconds.
				if (self.otpCountdown <= 270) {
					$('#fwa-ob-resend-otp').prop('disabled', false);
				}

				if (self.otpCountdown <= 0) {
					clearInterval(self.otpTimer);
					self.otpTimer = null;
					$('#fwa-ob-otp-timer').text(fwa_admin.strings.code_expired || 'Code has expired. Please request a new one.');
					$('#fwa-ob-resend-otp').prop('disabled', false);
				}
			}, 1000);
		},

		skipPhoneVerification: function () {
			this.phoneVerified = true;
			this.currentStep = 3;
			this.updateUI();
		},

		/* =================================================================
		   Step 3: WhatsApp Connection
		   ================================================================= */

		createInstance: function () {
			var self = this;
			var $btn = $('#fwa-ob-create-instance');
			var name = $('#fwa-ob-instance-name').val().trim();
			var url = $('#fwa-ob-api-url').val().trim();
			var key = $('#fwa-ob-api-key').val().trim();

			if (!url || !key) {
				this.showStatus('#fwa-ob-connection-status', 'error', 'Please enter both API URL and API key.');
				return;
			}

			if (!name) {
				name = 'My WhatsApp';
				$('#fwa-ob-instance-name').val(name);
			}

			$btn.prop('disabled', true).text('Connecting…');
			this.showStatus('#fwa-ob-connection-status', 'loading', 'Saving API settings and creating instance…');

			// Save API settings first.
			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				_ajax_nonce: fwa_admin.nonce,
				tab: 'api',
				fwa_api_base_url: url,
				fwa_global_api_token: key
			}, function (r) {
				if (r.success) {
					// Now create instance.
					self.doCreateInstance(name, $btn);
				} else {
					self.showStatus('#fwa-ob-connection-status', 'error', r.data ? r.data.message : 'Failed to save API settings.');
					$btn.prop('disabled', false).text('Connect WhatsApp');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-connection-status', 'error', 'Request failed. Check your network.');
				$btn.prop('disabled', false).text('Connect WhatsApp');
			});
		},

		doCreateInstance: function (name, $btn) {
			var self = this;

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_create_instance',
				nonce: fwa_admin.nonce,
				name: name,
				instance_id: 'auto_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8),
				access_token: '',
				phone_number: self.verifiedPhone || ''
			}, function (r) {
				if (r.success) {
					self.instanceCreated = true;
					self.instanceId = r.data.id || r.data.instance_id;
					self.showStatus('#fwa-ob-connection-status', 'success', 'Instance created! Scan QR code below with WhatsApp on your phone.');
					self.loadQR();
				} else {
					self.showStatus('#fwa-ob-connection-status', 'error', r.data ? r.data.message : 'Failed to create instance.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-connection-status', 'error', 'Request failed.');
			}).always(function () {
				$btn.prop('disabled', false).text('Connect WhatsApp');
			});
		},

		loadQR: function () {
			var self = this;
			if (!self.instanceId) {
				return;
			}

			$('#fwa-ob-qr-area').html('<p>Loading QR code…</p>');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_get_qr_code',
				nonce: fwa_admin.nonce,
				id: self.instanceId
			}, function (r) {
				if (r.success && r.data.qr) {
					$('#fwa-ob-qr-area').html('<img src="' + r.data.qr + '" alt="QR Code" /><br><button type="button" class="button" id="fwa-ob-refresh-qr">Refresh QR</button>');
					$('#fwa-ob-refresh-qr').on('click', function () {
						self.loadQR();
					});
				} else {
					$('#fwa-ob-qr-area').html('<p>' + (r.data ? r.data.message : 'QR code not available.') + '</p>');
				}
			});

			// Auto-refresh QR every 20 seconds.
			if (!self.qrTimer) {
				self.qrTimer = setInterval(function () {
					self.checkStatus();
				}, 20000);
			}
		},

		checkStatus: function () {
			var self = this;
			if (!self.instanceId) {
				return;
			}

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_check_instance_status',
				nonce: fwa_admin.nonce,
				id: self.instanceId
			}, function (r) {
				if (r.success && r.data.status === 'connected') {
					self.showStatus('#fwa-ob-connection-status', 'success', 'WhatsApp connected successfully!');
					$('#fwa-ob-qr-area').html('<p class="fwa-text-success"><span class="dashicons dashicons-yes-alt"></span> Connected</p>');
					if (self.qrTimer) {
						clearInterval(self.qrTimer);
						self.qrTimer = null;
					}
				}
			});
		},

		/* =================================================================
		   Step 4: Configuration
		   ================================================================= */

		saveConfig: function () {
			var automation = $('#fwa-ob-enable-automation').is(':checked') ? 'yes' : 'no';
			var logging = $('#fwa-ob-enable-logging').is(':checked') ? 'yes' : 'no';
			var campaigns = $('#fwa-ob-enable-campaigns').is(':checked') ? 'yes' : 'no';

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				_ajax_nonce: fwa_admin.nonce,
				tab: 'general',
				fwa_enable_automation: automation,
				fwa_enable_logging: logging,
				fwa_enable_campaigns: campaigns
			});
		},

		saveAutomation: function () {
			var rules = [];

			if ($('#fwa-ob-auto-woo').is(':checked')) {
				rules.push({
					event: 'new_order',
					enabled: true,
					instance_id: '',
					phone_field: 'billing_phone',
					message_type: 'text',
					template: 'Hi {customer_name}, your order #{order_id} for {order_total} has been placed!',
					media_url: ''
				});
			}

			if ($('#fwa-ob-auto-welcome').is(':checked')) {
				rules.push({
					event: 'user_register',
					enabled: true,
					instance_id: '',
					phone_field: 'billing_phone',
					message_type: 'text',
					template: 'Welcome to {store_name}, {customer_name}! We are glad to have you.',
					media_url: ''
				});
			}

			if (rules.length > 0) {
				$.post(fwa_admin.ajax_url, {
					action: 'fwa_save_automation_rules',
					nonce: fwa_admin.nonce,
					rules: JSON.stringify(rules)
				});
			}
		},

		/* =================================================================
		   Step 5: Finish
		   ================================================================= */

		buildSummary: function () {
			var items = [];
			if (this.phoneVerified && this.verifiedPhone) {
				items.push('✅ Phone verified: ' + this.verifiedPhone);
			}
			if (this.instanceCreated) {
				items.push('✅ WhatsApp instance created & connected');
			}
			if ($('#fwa-ob-enable-automation').is(':checked')) {
				items.push('✅ Automation enabled');
			}
			if ($('#fwa-ob-enable-logging').is(':checked')) {
				items.push('✅ Logging enabled');
			}
			if ($('#fwa-ob-enable-campaigns').is(':checked')) {
				items.push('✅ Campaigns enabled');
			}
			if ($('#fwa-ob-auto-woo').is(':checked')) {
				items.push('✅ WooCommerce order notification rule');
			}
			if ($('#fwa-ob-auto-welcome').is(':checked')) {
				items.push('✅ Welcome message rule');
			}

			var html = '<ul>';
			$.each(items, function (i, item) {
				html += '<li>' + item + '</li>';
			});
			if (items.length === 0) {
				html += '<li>No items configured. You can set up everything from the dashboard.</li>';
			}
			html += '</ul>';

			$('#fwa-ob-summary').html(html);
		},

		sendTest: function () {
			var self = this;
			var phone = $('#fwa-ob-test-phone').val().trim();

			if (!phone) {
				this.showStatus('#fwa-ob-test-result', 'error', 'Enter a phone number.');
				return;
			}

			$('#fwa-ob-send-test').prop('disabled', true).text('Sending…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_send_message',
				nonce: fwa_admin.nonce,
				phone: phone,
				content: 'Hello! This is a test message from WhatsApp Automation.',
				message_type: 'text',
				instance_id: ''
			}, function (r) {
				if (r.success) {
					self.showStatus('#fwa-ob-test-result', 'success', 'Test message sent!');
				} else {
					self.showStatus('#fwa-ob-test-result', 'error', r.data ? r.data.message : 'Failed to send.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-test-result', 'error', 'Request failed.');
			}).always(function () {
				$('#fwa-ob-send-test').prop('disabled', false).text('Send Test');
			});
		},

		/* =================================================================
		   Helpers
		   ================================================================= */

		skipWizard: function () {
			if (!confirm('Skip the setup wizard? You can configure everything manually from Settings.')) {
				return;
			}
			this.completeOnboarding(fwa_admin.dashboard_url);
		},

		completeOnboarding: function (redirectUrl) {
			$.post(fwa_admin.ajax_url, {
				action: 'fwa_complete_onboarding',
				_ajax_nonce: fwa_admin.nonce
			}, function () {
				window.location.href = redirectUrl || fwa_admin.dashboard_url;
			}).fail(function () {
				window.location.href = redirectUrl || fwa_admin.dashboard_url;
			});
		},

		showStatus: function (selector, type, message) {
			var icon = '';
			var cls = 'fwa-wizard-status-' + type;

			switch (type) {
				case 'success':
					icon = '<span class="dashicons dashicons-yes-alt"></span> ';
					break;
				case 'error':
					icon = '<span class="dashicons dashicons-dismiss"></span> ';
					break;
				case 'loading':
					icon = '<span class="spinner is-active"></span> ';
					break;
			}

			$(selector).html('<span class="' + cls + '">' + icon + message + '</span>');
		}
	};

	$(document).ready(function () {
		if ($('#fwa-wizard').length) {
			Wizard.init();
		}
	});
})(jQuery);
