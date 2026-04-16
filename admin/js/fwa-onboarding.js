/**
 * Flexi WhatsApp Automation – Onboarding Wizard JavaScript
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

/* global jQuery, fwa_admin */

(function ($) {
	'use strict';

	var Wizard = {
		currentStep: 1,
		totalSteps: 6,
		apiValidated: false,
		instanceCreated: false,
		qrTimer: null,

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

			// Step 2: API validation.
			$('#fwa-ob-validate-api').on('click', function () {
				self.validateAPI();
			});

			// Step 3: Instance creation.
			$('#fwa-ob-create-instance').on('click', function () {
				self.createInstance();
			});

			// Step 6: Test message and go to dashboard.
			$('#fwa-ob-send-test').on('click', function () {
				self.sendTest();
			});
			$('#fwa-ob-go-dashboard').on('click', function (e) {
				e.preventDefault();
				self.completeOnboarding($(this).attr('href'));
			});
		},

		nextStep: function () {
			// Gate checks before advancing.
			if (this.currentStep === 2 && !this.apiValidated) {
				this.showStatus('#fwa-ob-api-status', 'error', fwa_admin.strings.error || 'Please validate API connection first.');
				return;
			}

			if (this.currentStep === 4) {
				this.saveConfig();
			}

			if (this.currentStep === 5) {
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

			if (step === 1) {
				$('#fwa-wizard-next').text(fwa_admin.strings.get_started || 'Get Started →');
			} else {
				$('#fwa-wizard-next').text(fwa_admin.strings.continue || 'Continue →');
			}

			// Stop QR polling when leaving step 3.
			if (step !== 3 && this.qrTimer) {
				clearInterval(this.qrTimer);
				this.qrTimer = null;
			}

			// Build summary on step 6.
			if (step === 6) {
				this.buildSummary();
			}
		},

		validateAPI: function () {
			var self = this;
			var $btn = $('#fwa-ob-validate-api');
			var url = $('#fwa-ob-api-url').val().trim();
			var key = $('#fwa-ob-api-key').val().trim();

			if (!url || !key) {
				this.showStatus('#fwa-ob-api-status', 'error', 'Please enter both API URL and API key.');
				return;
			}

			$btn.prop('disabled', true).text('Validating…');
			this.showStatus('#fwa-ob-api-status', 'loading', 'Testing connection…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				_ajax_nonce: fwa_admin.nonce,
				tab: 'api',
				fwa_api_base_url: url,
				fwa_global_api_token: key
			}, function (r) {
				if (r.success) {
					self.apiValidated = true;
					self.showStatus('#fwa-ob-api-status', 'success', 'Connection successful! API settings saved.');
				} else {
					self.showStatus('#fwa-ob-api-status', 'error', r.data ? r.data.message : 'Connection failed.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-api-status', 'error', 'Request failed. Check your network.');
			}).always(function () {
				$btn.prop('disabled', false).text('Validate Connection');
			});
		},

		createInstance: function () {
			var self = this;
			var $btn = $('#fwa-ob-create-instance');
			var name = $('#fwa-ob-instance-name').val().trim();

			if (!name) {
				name = 'My WhatsApp';
				$('#fwa-ob-instance-name').val(name);
			}

			$btn.prop('disabled', true).text('Creating…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_create_instance',
				nonce: fwa_admin.nonce,
				name: name,
				instance_id: 'auto_' + Date.now(),
				access_token: '',
				phone_number: ''
			}, function (r) {
				if (r.success) {
					self.instanceCreated = true;
					self.instanceId = r.data.id || r.data.instance_id;
					self.showStatus('#fwa-ob-connection-status', 'success', 'Instance created! Scan QR code below.');
					self.loadQR();
				} else {
					self.showStatus('#fwa-ob-connection-status', 'error', r.data ? r.data.message : 'Failed to create instance.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-connection-status', 'error', 'Request failed.');
			}).always(function () {
				$btn.prop('disabled', false).text('Create Instance');
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
					self.showStatus('#fwa-ob-connection-status', 'success', 'WhatsApp connected!');
					$('#fwa-ob-qr-area').html('<p class="fwa-text-success"><span class="dashicons dashicons-yes-alt"></span> Connected</p>');
					if (self.qrTimer) {
						clearInterval(self.qrTimer);
						self.qrTimer = null;
					}
				}
			});
		},

		saveConfig: function () {
			var instance = $('#fwa-ob-default-instance').val();
			var automation = $('#fwa-ob-enable-automation').is(':checked') ? 'yes' : 'no';
			var logging = $('#fwa-ob-enable-logging').is(':checked') ? 'yes' : 'no';
			var campaigns = $('#fwa-ob-enable-campaigns').is(':checked') ? 'yes' : 'no';

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				_ajax_nonce: fwa_admin.nonce,
				tab: 'general',
				fwa_default_instance: instance,
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

		buildSummary: function () {
			var items = [];
			if (this.apiValidated) {
				items.push('✅ API connection configured');
			}
			if (this.instanceCreated) {
				items.push('✅ WhatsApp instance created');
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
