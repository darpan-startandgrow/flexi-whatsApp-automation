/**
 * Flexi WhatsApp Automation – Onboarding Wizard JavaScript
 *
 * New 5-step flow:
 *  Step 1 – API Configuration  (URL + token → real connection test)
 *  Step 2 – Connect WhatsApp   (create instance + QR scan)
 *  Step 3 – Webhook & Active   (copy webhook URL, set secret, pick active instance)
 *  Step 4 – Configure          (feature toggles + quick-start rules)
 *  Step 5 – Test & Finish      (send test message + summary)
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

/* global jQuery, fwa_admin */

(function ($) {
	'use strict';

	var Wizard = {
		currentStep: 1,
		totalSteps: 5,
		apiVerified: false,
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

			// Step 1: Save & Check Connection.
			$('#fwa-ob-save-api').on('click', function () {
				self.testAPIConnection();
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
					self.testAPIConnection();
				}
			});

			// Password visibility toggle.
			$('.fwa-toggle-password').on('click', function () {
				var targetId = $(this).data('target');
				var $input = $('#' + targetId);
				var $icon = $(this).find('.dashicons');
				if ($input.attr('type') === 'password') {
					$input.attr('type', 'text');
					$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
				} else {
					$input.attr('type', 'password');
					$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
				}
			});
		},

		nextStep: function () {
			// Gate: Step 1 requires API to be verified first.
			if (this.currentStep === 1 && !this.apiVerified) {
				this.showStatus('#fwa-ob-api-status', 'error', 'Please verify your API connection before continuing. Click "Save & Check Connection".');
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

			// Hide Next on Step 2 until instance is connected or user decides to skip.
			if (step === 2 && !this.instanceCreated) {
				$('#fwa-wizard-next').toggle(false);
			}

			// Stop QR polling when leaving step 2.
			if (step !== 2 && this.qrTimer) {
				clearInterval(this.qrTimer);
				this.qrTimer = null;
			}

			// Build summary on step 5.
			if (step === 5) {
				this.buildSummary();
			}

			// Load instance picker on step 3.
			if (step === 3) {
				this.loadInstancePicker();
			}
		},

		/* =================================================================
		   Step 1: API Configuration – Real Connection Test
		   ================================================================= */

		testAPIConnection: function () {
			var self = this;
			var url = $('#fwa-ob-api-url').val().trim().replace(/\/+$/, '');
			var key = $('#fwa-ob-api-key').val().trim();

			if (!url) {
				this.showStatus('#fwa-ob-api-status', 'error', 'Please enter the API Base URL.');
				$('#fwa-ob-api-url').focus();
				return;
			}

			if (!key) {
				this.showStatus('#fwa-ob-api-status', 'error', 'Please enter the API Key.');
				$('#fwa-ob-api-key').focus();
				return;
			}

			// Basic URL format validation.
			try {
				new URL(url);
			} catch (e) {
				this.showStatus('#fwa-ob-api-status', 'error', 'The API Base URL is not a valid URL. Please check the format (e.g., https://api.example.com).');
				$('#fwa-ob-api-url').focus();
				return;
			}

			var $btn = $('#fwa-ob-save-api');
			$btn.prop('disabled', true);
			$btn.find('.dashicons').removeClass('dashicons-cloud').addClass('dashicons-update fwa-spin');
			$btn.contents().last()[0].textContent = ' Testing Connection…';
			this.showStatus('#fwa-ob-api-status', 'loading', 'Connecting to API server… This may take a few seconds.');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_test_api_connection',
				nonce: fwa_admin.nonce,
				api_url: url,
				api_key: key
			}, function (r) {
				if (r.success) {
					self.apiVerified = true;
					self.showStatus('#fwa-ob-api-status', 'success', r.data.message);
					$('#fwa-wizard-next').show();
				} else {
					self.apiVerified = false;
					self.showStatus('#fwa-ob-api-status', 'error', r.data ? r.data.message : 'Connection test failed. Please check your credentials.');
				}
			}).fail(function () {
				self.apiVerified = false;
				self.showStatus('#fwa-ob-api-status', 'error', 'Network error. Could not reach WordPress. Please check your internet connection and try again.');
			}).always(function () {
				$btn.prop('disabled', false);
				$btn.find('.dashicons').removeClass('dashicons-update fwa-spin').addClass('dashicons-cloud');
				$btn.contents().last()[0].textContent = ' Save & Check Connection';
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

			$btn.prop('disabled', true);
			$btn.find('.dashicons').removeClass('dashicons-admin-links').addClass('dashicons-update fwa-spin');
			$btn.contents().last()[0].textContent = ' Connecting…';
			this.showStatus('#fwa-ob-connection-status', 'loading', 'Creating instance and requesting QR code…');

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
					self.showStatus('#fwa-ob-connection-status', 'error', r.data ? r.data.message : 'Failed to create instance. Please check your API configuration.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-connection-status', 'error', 'Request failed. Please check your network connection.');
			}).always(function () {
				$btn.prop('disabled', false);
				$btn.find('.dashicons').removeClass('dashicons-update fwa-spin').addClass('dashicons-admin-links');
				$btn.contents().last()[0].textContent = ' Connect WhatsApp';
			});
		},

		loadQR: function () {
			var self = this;
			if (!self.instanceId) {
				return;
			}

			$('#fwa-ob-qr-area').html('<div class="fwa-loading-state"><span class="spinner is-active"></span><p>Loading QR code…</p></div>');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_get_qr_code',
				nonce: fwa_admin.nonce,
				id: self.instanceId
			}, function (r) {
				if (r.success && r.data.qr) {
					var src = r.data.qr;
					if (src.indexOf('data:') !== 0 && src.indexOf('http') !== 0) {
						src = 'data:image/png;base64,' + src;
					}
					$('#fwa-ob-qr-area').html(
						'<div class="fwa-qr-container">' +
						'<img src="' + src + '" alt="QR Code" class="fwa-qr-image" />' +
						'<p class="description">Scan this QR code with WhatsApp to link your device.</p>' +
						'<button type="button" class="button" id="fwa-ob-refresh-qr"><span class="dashicons dashicons-image-rotate"></span> Refresh QR</button>' +
						'</div>'
					);
					$('#fwa-ob-refresh-qr').on('click', function () {
						self.loadQR();
					});
				} else {
					$('#fwa-ob-qr-area').html(
						'<div class="fwa-empty-state">' +
						'<span class="dashicons dashicons-warning"></span>' +
						'<p>' + (r.data ? r.data.message : 'QR code not available yet.') + '</p>' +
						'<button type="button" class="button" id="fwa-ob-refresh-qr"><span class="dashicons dashicons-image-rotate"></span> Try Again</button>' +
						'</div>'
					);
					$('#fwa-ob-refresh-qr').on('click', function () {
						self.loadQR();
					});
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
					self.showStatus('#fwa-ob-connection-status', 'success', 'WhatsApp connected successfully! Your device is linked.');
					$('#fwa-ob-qr-area').html(
						'<div class="fwa-success-state">' +
						'<span class="dashicons dashicons-yes-alt"></span>' +
						'<p>Connected and ready!</p>' +
						'</div>'
					);
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
			var text = $field.val();
			var $btn = $('#fwa-ob-copy-webhook');

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
					setTimeout(function () {
						$btn.html('<span class="dashicons dashicons-clipboard"></span> Copy');
					}, 2000);
				});
			} else {
				$field[0].select();
				try {
					document.execCommand('copy');
					$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
					setTimeout(function () {
						$btn.html('<span class="dashicons dashicons-clipboard"></span> Copy');
					}, 2000);
				} catch (e) {
					// Clipboard API not available — the user can copy manually.
				}
			}
		},

		saveWebhookSettings: function () {
			var self = this;
			var secret = $('#fwa-ob-webhook-secret').val().trim();

			var $btn = $('#fwa-ob-save-webhook');
			$btn.prop('disabled', true);
			$btn.find('.dashicons').removeClass('dashicons-saved').addClass('dashicons-update fwa-spin');
			this.showStatus('#fwa-ob-webhook-status', 'loading', 'Saving webhook settings…');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_settings',
				nonce: fwa_admin.nonce,
				tab: 'api',
				fwa_webhook_secret: secret
			}, function (r) {
				if (r.success) {
					var msg = 'Webhook settings saved successfully.';
					if (!secret) {
						msg += ' ⚠️ No webhook secret set — any request to the webhook endpoint will be accepted. Consider adding one for production use.';
					}
					self.showStatus('#fwa-ob-webhook-status', r.success && secret ? 'success' : 'warning', msg);
				} else {
					self.showStatus('#fwa-ob-webhook-status', 'error', r.data ? r.data.message : 'Failed to save webhook settings.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-webhook-status', 'error', 'Network error. Please try again.');
			}).always(function () {
				$btn.prop('disabled', false);
				$btn.find('.dashicons').removeClass('dashicons-update fwa-spin').addClass('dashicons-saved');
			});
		},

		loadInstancePicker: function () {
			var self = this;
			var $picker = $('#fwa-ob-instance-picker');
			$picker.html('<div class="fwa-loading-state"><span class="spinner is-active"></span><p>Loading instances…</p></div>');

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_get_instances',
				nonce: fwa_admin.nonce
			}, function (r) {
				var instances = (r.success && r.data && r.data.instances) ? r.data.instances : [];
				if (!instances.length) {
					$picker.html(
						'<div class="fwa-empty-state">' +
						'<span class="dashicons dashicons-info-outline"></span>' +
						'<p>No instances found. Complete Step 2 to create one, or skip if you plan to set up later.</p>' +
						'</div>'
					);
					return;
				}

				var html = '<table class="widefat striped fwa-compact-table"><thead><tr><th style="width:40px;">Active</th><th>Name</th><th>Status</th></tr></thead><tbody>';
				$.each(instances, function (i, inst) {
					var isActive = parseInt(inst.is_active, 10) === 1;
					var statusClass = 'fwa-badge fwa-badge-' + (inst.status || 'disconnected');
					html += '<tr>' +
						'<td><input type="radio" name="fwa-ob-active-instance" value="' + inst.id + '"' + (isActive ? ' checked' : '') + '></td>' +
						'<td>' + $('<span>').text(inst.name || '').html() + '</td>' +
						'<td><span class="' + statusClass + '">' + $('<span>').text(inst.status || '').html() + '</span></td>' +
						'</tr>';
				});
				html += '</tbody></table>';
				html += '<div class="fwa-form-actions" style="margin-top:12px;"><button type="button" class="button button-primary" id="fwa-ob-set-active"><span class="dashicons dashicons-star-filled"></span> Set as Active</button></div>';
				$picker.html(html);

				$('#fwa-ob-set-active').on('click', function () {
					var selectedId = $('input[name="fwa-ob-active-instance"]:checked').val();
					if (!selectedId) {
						self.showStatus('#fwa-ob-active-status', 'error', 'Please select an instance from the list.');
						return;
					}
					self.setActiveInstance(selectedId);
				});
			}).fail(function () {
				$picker.html(
					'<div class="fwa-empty-state fwa-empty-state-error">' +
					'<span class="dashicons dashicons-warning"></span>' +
					'<p>Failed to load instances. Please check your connection and try again.</p>' +
					'</div>'
				);
			});
		},

		setActiveInstance: function (instanceId) {
			var self = this;
			var $btn = $('#fwa-ob-set-active');
			$btn.prop('disabled', true);
			$btn.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-update fwa-spin');
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
				$btn.prop('disabled', false);
				$btn.find('.dashicons').removeClass('dashicons-update fwa-spin').addClass('dashicons-star-filled');
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
			if (this.apiVerified) {
				items.push({ icon: '✅', text: 'API connection verified and credentials saved', type: 'success' });
			} else {
				items.push({ icon: '⚠️', text: 'API not configured — set up from Settings → API Configuration', type: 'warning' });
			}
			if (this.instanceCreated) {
				items.push({ icon: '✅', text: 'WhatsApp instance created', type: 'success' });
			} else {
				items.push({ icon: '⚠️', text: 'No WhatsApp instance — create one from Instances page', type: 'warning' });
			}
			var secret = $('#fwa-ob-webhook-secret').val().trim();
			if (secret) {
				items.push({ icon: '✅', text: 'Webhook secret configured', type: 'success' });
			} else {
				items.push({ icon: '⚠️', text: 'Webhook secret not set (recommended for security)', type: 'warning' });
			}
			if ($('#fwa-ob-enable-automation').is(':checked')) {
				items.push({ icon: '✅', text: 'Automation enabled', type: 'success' });
			}
			if ($('#fwa-ob-enable-logging').is(':checked')) {
				items.push({ icon: '✅', text: 'Logging enabled', type: 'success' });
			}
			if ($('#fwa-ob-enable-campaigns').is(':checked')) {
				items.push({ icon: '✅', text: 'Campaigns enabled', type: 'success' });
			}
			if ($('#fwa-ob-auto-woo').is(':checked')) {
				items.push({ icon: '✅', text: 'WooCommerce order notification rule added', type: 'success' });
			}
			if ($('#fwa-ob-auto-welcome').is(':checked')) {
				items.push({ icon: '✅', text: 'Welcome message rule added', type: 'success' });
			}

			var html = '<div class="fwa-summary-list">';
			$.each(items, function (i, item) {
				html += '<div class="fwa-summary-item fwa-summary-' + item.type + '">' +
					'<span class="fwa-summary-icon">' + item.icon + '</span>' +
					'<span>' + item.text + '</span>' +
					'</div>';
			});
			if (items.length === 0) {
				html += '<div class="fwa-summary-item fwa-summary-warning"><span class="fwa-summary-icon">ℹ️</span><span>No items configured. You can set up everything from the dashboard.</span></div>';
			}
			html += '</div>';

			$('#fwa-ob-summary').html(html);
		},

		sendTest: function () {
			var self = this;
			var phone = $('#fwa-ob-test-phone').val().trim();

			if (!phone) {
				this.showStatus('#fwa-ob-test-result', 'error', 'Enter a phone number in E.164 format (e.g., +1234567890).');
				$('#fwa-ob-test-phone').focus();
				return;
			}

			var $btn = $('#fwa-ob-send-test');
			$btn.prop('disabled', true);
			$btn.find('.dashicons').removeClass('dashicons-email').addClass('dashicons-update fwa-spin');
			$btn.contents().last()[0].textContent = ' Sending…';

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_send_message',
				nonce: fwa_admin.nonce,
				phone: phone,
				message: 'Hello! This is a test message from Flexi WhatsApp Automation. ✅',
				message_type: 'text',
				instance_id: ''
			}, function (r) {
				if (r.success) {
					self.showStatus('#fwa-ob-test-result', 'success', 'Test message sent successfully! Check your WhatsApp.');
				} else {
					self.showStatus('#fwa-ob-test-result', 'error', r.data ? r.data.message : 'Failed to send test message. Please check your instance connection.');
				}
			}).fail(function () {
				self.showStatus('#fwa-ob-test-result', 'error', 'Request failed. Please check your connection.');
			}).always(function () {
				$btn.prop('disabled', false);
				$btn.find('.dashicons').removeClass('dashicons-update fwa-spin').addClass('dashicons-email');
				$btn.contents().last()[0].textContent = ' Send Test';
			});
		},

		/* =================================================================
		   Helpers
		   ================================================================= */

		skipWizard: function () {
			if (!confirm('Skip the setup wizard?\n\nYou can configure everything manually from Settings. The system will be marked as "Not Configured" until you complete the API setup.')) {
				return;
			}
			this.completeOnboarding(fwa_admin.dashboard_url, true);
		},

		completeOnboarding: function (redirectUrl, skipped) {
			var data = {
				action: 'fwa_complete_onboarding',
				_ajax_nonce: fwa_admin.nonce
			};
			if (skipped) {
				data.skipped = 'yes';
			}
			$.post(fwa_admin.ajax_url, data, function () {
				window.location.href = redirectUrl || fwa_admin.dashboard_url;
			}).fail(function () {
				window.location.href = redirectUrl || fwa_admin.dashboard_url;
			});
		},

		showStatus: function (selector, type, message) {
			var iconMap = {
				success: '<span class="dashicons dashicons-yes-alt"></span> ',
				error: '<span class="dashicons dashicons-dismiss"></span> ',
				warning: '<span class="dashicons dashicons-warning"></span> ',
				loading: '<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> '
			};
			var icon = iconMap[type] || '';
			var cls = 'fwa-wizard-status-' + type;

			$(selector).html('<div class="' + cls + '">' + icon + '<span>' + message + '</span></div>');
		}
	};

	$(document).ready(function () {
		if ($('#fwa-wizard').length) {
			Wizard.init();
		}
	});
})(jQuery);
