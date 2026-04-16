/**
 * Flexi WhatsApp Automation – Onboarding Wizard JavaScript
 *
 * New 5-step flow:
 *  Step 1 – API Configuration  (URL + token → save)
 *  Step 2 – Connect WhatsApp   (create instance + QR scan)
 *  Step 3 – Webhook & Active   (copy webhook URL, set secret, pick active instance)
 *  Step 4 – Configure          (feature toggles + quick-start rules)
 *  Step 5 – Test & Finish      (send test message + summary)
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
		apiSaved: false,
		instanceCreated: false,
		instanceId: null,
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

			// Step 1: Save API settings.
			$('#fwa-ob-save-api').on('click', function () {
				self.saveAPISettings();
			});

			// Step 2: Create instance + QR.
			$('#fwa-ob-create-instance').on('click', function () {
				self.createInstance();
			});

			// Step 3: Save webhook settings + copy URL + set active.
			$('#fwa-ob-save-webhook').on('click', function () {
				self.saveWebhookSettings();
			});
			$('#fwa-ob-copy-webhook').on('click', function () {
				self.copyWebhookURL();
			});

			// Step 5: Test message and go to dashboard.
			$('#fwa-ob-send-test').on('click', function () {
				self.sendTest();
			});
			$('#fwa-ob-go-dashboard').on('click', function (e) {
				e.preventDefault();
				self.completeOnboarding($(this).attr('href'));
			});

			// Allow Enter on API URL / Key fields.
			$('#fwa-ob-api-url, #fwa-ob-api-key').on('keypress', function (e) {
				if (e.which === 13) {
					self.saveAPISettings();
				}
			});
		},

		nextStep: function () {
			// Gate: Step 1 requires API to be saved first.
			if (this.currentStep === 1 && !this.apiSaved) {
				this.showStatus('#fwa-ob-api-status', 'error', 'Please save your API settings before continuing.');
				return;
			}

			// Save settings when leaving step 4.
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

			// Navigation visibility.
			$('#fwa-wizard-prev').toggle(step > 1 && step < this.totalSteps);
			$('#fwa-wizard-next').toggle(step < this.totalSteps);

			// Hide Next on Step 1 until API is saved (enforced in nextStep too).
			// On Step 2, hide Next until instance is connected or user skips.
			if (step === 2 && !this.instanceCreated) {
				$('#fwa-wizard-next').toggle(false);
			}

			// Stop QR polling when leaving step 2.
			if (step !== 2 && this.qrTimer) {
				clearInterval(this.qrTimer);
				this.qrTimer = null;
			}

			// Pre-fill test phone on step 5.
			if (step === 5) {
				this.buildSummary();
			}

			// Load instance picker on step 3.
			if (step === 3) {
				this.loadInstancePicker();
			}
		},

		/* =================================================================
		   Step 1: API Configuration
		   ================================================================= */

		saveAPISettings: function () {
			var self = this;
			var url = $('#fwa-ob-api-url').val().trim();
			var key = $('#fwa-ob-api-key').val().trim();

			if (!url || !key) {
				this.showStatus('#fwa-ob-api-status', 'error', 'Please enter both the API Base URL and the API Key.');
				return;
			}

			var $btn = $('#fwa-ob-save-api');
			$btn.prop('disabled', true).text('Saving…');
			this.showStatus('#fwa-ob-api-status', 'loading', 'Saving API settings…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				nonce: fwa_admin.nonce,
				tab: 'api',
				fwa_api_base_url: url,
				fwa_api_global_token: key
			}, function (r) {
				if (r.success) {
					self.apiSaved = true;
					self.showStatus('#fwa-ob-api-status', 'success', 'API settings saved. You can now create your WhatsApp instance.');
					$('#fwa-wizard-next').show();
				} else {
					self.showStatus('#fwa-ob-api-status', 'error', r.data ? r.data.message : 'Failed to save API settings.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-api-status', 'error', 'Network error. Please try again.');
			}).always(function () {
				$btn.prop('disabled', false).text('Save & Continue');
			});
		},

		/* =================================================================
		   Step 2: WhatsApp Connection
		   ================================================================= */

		createInstance: function () {
			var self = this;
			var $btn = $('#fwa-ob-create-instance');
			var name = $('#fwa-ob-instance-name').val().trim();

			if (!name) {
				name = 'My WhatsApp';
				$('#fwa-ob-instance-name').val(name);
			}

			$btn.prop('disabled', true).text('Connecting…');
			this.showStatus('#fwa-ob-connection-status', 'loading', 'Creating instance…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_create_instance',
				nonce: fwa_admin.nonce,
				name: name,
				instance_id: 'auto_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8),
				access_token: '',
				phone_number: ''
			}, function (r) {
				if (r.success) {
					self.instanceCreated = true;
					self.instanceId = r.data && r.data.id ? r.data.id : null;
					self.showStatus('#fwa-ob-connection-status', 'success', 'Instance created! Scan the QR code below with WhatsApp on your phone.');
					$('#fwa-wizard-next').show();
					self.loadQR();
				} else {
					self.showStatus('#fwa-ob-connection-status', 'error', r.data ? r.data.message : 'Failed to create instance.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-connection-status', 'error', 'Request failed. Check your network.');
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
					$('#fwa-ob-qr-area').html('<p>' + (r.data ? r.data.message : 'QR code not available yet. Try refreshing.') + '</p>');
				}
			});

			// Poll for connection status every 20 seconds.
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
		   Step 3: Webhook & Active Instance
		   ================================================================= */

		copyWebhookURL: function () {
			var $field = $('#fwa-ob-webhook-url');
			$field[0].select();
			try {
				document.execCommand('copy');
				$('#fwa-ob-copy-webhook').text('Copied!');
				setTimeout(function () {
					$('#fwa-ob-copy-webhook').text('Copy');
				}, 2000);
			} catch (e) {
				// Clipboard API not available — the user can copy manually.
			}
		},

		saveWebhookSettings: function () {
			var self = this;
			var secret = $('#fwa-ob-webhook-secret').val().trim();

			var $btn = $('#fwa-ob-save-webhook');
			$btn.prop('disabled', true).text('Saving…');
			this.showStatus('#fwa-ob-webhook-status', 'loading', 'Saving webhook settings…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				nonce: fwa_admin.nonce,
				tab: 'api',
				fwa_webhook_secret: secret
			}, function (r) {
				if (r.success) {
					var msg = 'Webhook settings saved.';
					if (!secret) {
						msg += ' Warning: No webhook secret set — any request to the webhook endpoint will be accepted.';
					}
					self.showStatus('#fwa-ob-webhook-status', 'success', msg);
				} else {
					self.showStatus('#fwa-ob-webhook-status', 'error', r.data ? r.data.message : 'Failed to save.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-webhook-status', 'error', 'Network error. Please try again.');
			}).always(function () {
				$btn.prop('disabled', false).text('Save Webhook Settings');
			});
		},

		loadInstancePicker: function () {
			var self = this;
			var $picker = $('#fwa-ob-instance-picker');
			$picker.html('<p style="color:#999;">Loading instances…</p>');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_get_instances',
				nonce: fwa_admin.nonce
			}, function (r) {
				var instances = (r.success && r.data && r.data.instances) ? r.data.instances : [];
				if (!instances.length) {
					$picker.html('<p style="color:#999;">No instances found. Complete Step 2 to create one.</p>');
					return;
				}

				var html = '<table class="widefat striped" style="margin-top:8px;"><thead><tr><th>Active</th><th>Name</th><th>Status</th></tr></thead><tbody>';
				$.each(instances, function (i, inst) {
					var isActive = parseInt(inst.is_active, 10) === 1;
					html += '<tr>' +
						'<td><input type="radio" name="fwa-ob-active-instance" value="' + inst.id + '"' + (isActive ? ' checked' : '') + '></td>' +
						'<td>' + $('<span>').text(inst.name || '').html() + '</td>' +
						'<td>' + $('<span>').text(inst.status || '').html() + '</td>' +
						'</tr>';
				});
				html += '</tbody></table>';
				html += '<button type="button" class="button button-primary" id="fwa-ob-set-active" style="margin-top:10px;">Set as Active</button>';
				$picker.html(html);

				$('#fwa-ob-set-active').on('click', function () {
					var selectedId = $('input[name="fwa-ob-active-instance"]:checked').val();
					if (!selectedId) {
						self.showStatus('#fwa-ob-active-status', 'error', 'Please select an instance.');
						return;
					}
					self.setActiveInstance(selectedId);
				});
			}).fail(function () {
				$picker.html('<p style="color:#dc3232;">Failed to load instances. Please try again.</p>');
			});
		},

		setActiveInstance: function (instanceId) {
			var self = this;
			var $btn = $('#fwa-ob-set-active');
			$btn.prop('disabled', true).text('Saving…');
			this.showStatus('#fwa-ob-active-status', 'loading', 'Setting active instance…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_set_active_instance',
				nonce: fwa_admin.nonce,
				id: instanceId
			}, function (r) {
				if (r.success) {
					self.showStatus('#fwa-ob-active-status', 'success', 'Active instance updated. All automation and messages will use this instance by default.');
				} else {
					self.showStatus('#fwa-ob-active-status', 'error', r.data ? r.data.message : 'Failed to set active instance.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-active-status', 'error', 'Network error. Please try again.');
			}).always(function () {
				$btn.prop('disabled', false).text('Set as Active');
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
				nonce: fwa_admin.nonce,
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
			if (this.apiSaved) {
				items.push('✅ API credentials saved');
			}
			if (this.instanceCreated) {
				items.push('✅ WhatsApp instance created & connected');
			}
			var secret = $('#fwa-ob-webhook-secret').val().trim();
			if (secret) {
				items.push('✅ Webhook secret configured');
			} else {
				items.push('⚠️ Webhook secret not set (recommended for security)');
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
				message: 'Hello! This is a test message from Flexi WhatsApp Automation.',
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
