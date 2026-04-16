/**
 * Flexi WhatsApp Automation – OTP Login Script
 *
 * Handles OTP send, verify, countdown timer, and login flow
 * for public-facing [fwa_otp_login] and [fwa_otp_verify] shortcodes.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.1.0
 */

/* global jQuery, fwa_otp */

(function ($) {
	'use strict';

	var OTPForm = {
		phone: '',
		countdown: 300,
		timer: null,

		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			var self = this;

			// Send OTP buttons.
			$(document).on('click', '.fwa-otp-send-btn', function () {
				var $form = $(this).closest('.fwa-otp-form, .fwa-verify-bar');
				self.sendOTP($form);
			});

			// Verify OTP buttons.
			$(document).on('click', '.fwa-otp-verify-btn', function () {
				var $form = $(this).closest('.fwa-otp-form, .fwa-verify-bar');
				self.verifyOTP($form);
			});

			// Resend.
			$(document).on('click', '.fwa-otp-resend-btn', function () {
				var $form = $(this).closest('.fwa-otp-form');
				self.sendOTP($form);
			});

			// Back to phone step.
			$(document).on('click', '.fwa-otp-back-btn', function () {
				var $form = $(this).closest('.fwa-otp-form');
				self.showStep($form, 'phone');
			});

			// Enter key on inputs.
			$(document).on('keypress', '.fwa-otp-code-input', function (e) {
				if (e.which === 13) {
					var $form = $(this).closest('.fwa-otp-form, .fwa-verify-bar');
					self.verifyOTP($form);
				}
			});

			$(document).on('keypress', '.fwa-otp-input[name="phone"], .fwa-verify-bar-phone', function (e) {
				if (e.which === 13) {
					var $form = $(this).closest('.fwa-otp-form, .fwa-verify-bar');
					self.sendOTP($form);
				}
			});
		},

		sendOTP: function ($form) {
			var self = this;
			var $phoneInput = $form.find('input[name="phone"], .fwa-verify-bar-phone');
			var phone = $phoneInput.val().trim();

			if (!phone) {
				this.showStatus($form, 'error', fwa_otp.strings.enter_phone);
				return;
			}

			self.phone = phone;

			var $btn = $form.find('.fwa-otp-send-btn');
			$btn.prop('disabled', true).text(fwa_otp.strings.sending);
			this.showStatus($form, 'loading', fwa_otp.strings.sending);

			$.post(fwa_otp.ajax_url, {
				action: 'fwa_public_send_otp',
				nonce: fwa_otp.nonce,
				phone: phone
			}, function (r) {
				if (r.success) {
					self.showStatus($form, 'success', r.data.message);

					// Show code step.
					if ($form.hasClass('fwa-otp-form')) {
						self.showStep($form, 'code');
						$form.find('.fwa-otp-code-input').focus();
					} else {
						// Verify bar — show OTP inputs.
						$form.find('.fwa-verify-bar-form').hide();
						$form.find('.fwa-verify-bar-otp').show();
						$form.find('.fwa-otp-code-input').focus();
					}

					self.startCountdown($form);
				} else {
					self.showStatus($form, 'error', r.data ? r.data.message : 'Failed to send code.');
				}
			}).fail(function () {
				self.showStatus($form, 'error', fwa_otp.strings.network_error);
			}).always(function () {
				$btn.prop('disabled', false).text(fwa_otp.strings.code_sent ? 'Send Code' : 'Send Code');
			});
		},

		verifyOTP: function ($form) {
			var self = this;
			var otp = $form.find('.fwa-otp-code-input').val().trim();

			if (!otp || otp.length !== 6 || !/^\d{6}$/.test(otp)) {
				this.showStatus($form, 'error', fwa_otp.strings.enter_code);
				return;
			}

			var mode = $form.data('mode') || 'verify';
			var $btn = $form.find('.fwa-otp-verify-btn');
			$btn.prop('disabled', true).text(fwa_otp.strings.verifying);
			this.showStatus($form, 'loading', fwa_otp.strings.verifying);

			$.post(fwa_otp.ajax_url, {
				action: 'fwa_public_verify_otp',
				nonce: fwa_otp.nonce,
				phone: self.phone,
				otp: otp,
				mode: mode
			}, function (r) {
				if (r.success) {
					self.showStatus($form, 'success', r.data.message);

					// Clear timer.
					if (self.timer) {
						clearInterval(self.timer);
						self.timer = null;
					}

					if (r.data.redirect) {
						window.location.href = r.data.redirect;
						return;
					}

					// Show success state.
					if ($form.hasClass('fwa-otp-form')) {
						self.showStep($form, 'success');
					} else {
						// Verify bar — hide the bar.
						$form.slideUp();
					}

					// Fire custom event.
					if (typeof window.CustomEvent === 'function') {
						window.dispatchEvent(new CustomEvent('fwa_phone_verified', {
							detail: { phone: self.phone }
						}));
					}
				} else {
					self.showStatus($form, 'error', r.data ? r.data.message : 'Verification failed.');
				}
			}).fail(function () {
				self.showStatus($form, 'error', fwa_otp.strings.network_error);
			}).always(function () {
				$btn.prop('disabled', false).text('Verify');
			});
		},

		showStep: function ($form, step) {
			$form.find('.fwa-otp-step').hide();
			$form.find('.fwa-otp-step[data-step="' + step + '"]').show();
		},

		startCountdown: function ($form) {
			var self = this;
			this.countdown = 300;

			var $timer = $form.find('.fwa-otp-timer');
			var $resend = $form.find('.fwa-otp-resend-btn');
			$resend.prop('disabled', true);

			if (this.timer) {
				clearInterval(this.timer);
			}

			this.timer = setInterval(function () {
				self.countdown--;

				if (self.countdown <= 0) {
					clearInterval(self.timer);
					self.timer = null;
					$timer.text('Code expired. Request a new one.');
					$resend.prop('disabled', false);
					return;
				}

				var m = Math.floor(self.countdown / 60);
				var s = self.countdown % 60;
				$timer.text('Code expires in ' + m + ':' + (s < 10 ? '0' : '') + s);

				// Enable resend after 30 seconds.
				if (self.countdown <= 270) {
					$resend.prop('disabled', false);
				}
			}, 1000);
		},

		showStatus: function ($form, type, message) {
			var $status = $form.find('.fwa-otp-status');
			$status.html('<span class="fwa-otp-status-' + type + '">' + message + '</span>');
		}
	};

	$(document).ready(function () {
		if ($('.fwa-otp-form, .fwa-verify-bar').length) {
			OTPForm.init();
		}
	});
})(jQuery);
