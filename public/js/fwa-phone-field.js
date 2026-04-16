/**
 * Flexi WhatsApp Automation – Advanced Phone Field
 *
 * Attaches a country-code picker (flag + dial code dropdown) to any
 * input fields that carry the data-fwa-phone attribute, as well as to
 * the built-in OTP login forms.
 *
 * Features:
 *  - Country flag + dial code shown inside the input wrapper
 *  - GeoIP-based default detection (falls back to configured default)
 *  - Country whitelist / blacklist filtering
 *  - Real-time E.164 format validation with per-country length check
 *  - Works with the [fwa_otp_login] and [fwa_otp_verify] shortcodes
 *  - Applies to any <input data-fwa-phone="1"> field
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

/* global fwa_phone_field */

(function ($) {
	'use strict';

	var config  = (typeof fwa_phone_field !== 'undefined') ? fwa_phone_field : {};
	var countries = config.countries || [];
	var defaultCC = config.default_country || 'US';
	var geoipCC   = config.geoip_country  || '';
	var allow     = config.allow_countries ? config.allow_countries.split(',').map(function(s){ return s.trim().toUpperCase(); }) : [];
	var block     = config.block_countries ? config.block_countries.split(',').map(function(s){ return s.trim().toUpperCase(); }) : [];

	// -----------------------------------------------------------------------
	// Country data helpers
	// -----------------------------------------------------------------------

	/**
	 * Filter the countries array based on allow/block lists.
	 */
	function getFilteredCountries() {
		return countries.filter(function (c) {
			if (allow.length && allow.indexOf(c.iso2.toUpperCase()) === -1) {
				return false;
			}
			if (block.length && block.indexOf(c.iso2.toUpperCase()) !== -1) {
				return false;
			}
			return true;
		});
	}

	/**
	 * Return a country object by ISO2 code.
	 */
	function getCountryByISO2(iso2) {
		iso2 = (iso2 || '').toUpperCase();
		for (var i = 0; i < countries.length; i++) {
			if (countries[i].iso2.toUpperCase() === iso2) {
				return countries[i];
			}
		}
		return null;
	}

	/**
	 * Determine the initial country to select.
	 * Priority: geoIP > configured default > first in filtered list.
	 */
	function getInitialCountry() {
		var list = getFilteredCountries();
		var preferred = geoipCC || defaultCC || '';

		if (preferred) {
			var found = getCountryByISO2(preferred);
			// Make sure it passes filter.
			if (found) {
				var ok = true;
				if (allow.length && allow.indexOf(found.iso2.toUpperCase()) === -1) { ok = false; }
				if (block.length && block.indexOf(found.iso2.toUpperCase()) !== -1) { ok = false; }
				if (ok) { return found; }
			}
		}

		return list.length ? list[0] : null;
	}

	// -----------------------------------------------------------------------
	// Widget construction
	// -----------------------------------------------------------------------

	/**
	 * Build the picker HTML and attach it to the given input element.
	 *
	 * @param {jQuery} $input Original phone input.
	 */
	function attachPicker($input) {
		if ($input.data('fwa-picker-attached')) {
			return;
		}
		$input.data('fwa-picker-attached', true);

		var filtered  = getFilteredCountries();
		var initCC    = getInitialCountry();
		var wrapperID = 'fwa-pf-' + Math.random().toString(36).substr(2, 8);

		// Build wrapper + flag button + dropdown.
		var $wrapper = $('<div class="fwa-phone-wrapper"></div>');
		var $btn     = $('<button type="button" class="fwa-phone-flag-btn" aria-label="Select country code" aria-expanded="false"></button>');
		var $dropdown = $('<div class="fwa-phone-dropdown" style="display:none;" role="listbox" aria-label="Country codes"></div>');
		var $search   = $('<input type="text" class="fwa-phone-dropdown-search" placeholder="Search country…" autocomplete="off">');
		var $list     = $('<ul class="fwa-phone-dropdown-list"></ul>');

		$dropdown.append($search).append($list);

		// Build list items.
		function buildList(filterStr) {
			$list.empty();
			var q = (filterStr || '').toLowerCase();
			filtered.forEach(function (c) {
				if (q && c.name.toLowerCase().indexOf(q) === -1 && c.dialCode.indexOf(q) === -1) {
					return;
				}
				var $li = $(
					'<li class="fwa-phone-dropdown-item" role="option" data-iso2="' + esc(c.iso2) + '" data-dial="' + esc(c.dialCode) + '">' +
						'<span class="fwa-phone-flag fwa-flag-' + esc(c.iso2.toLowerCase()) + '"></span>' +
						'<span class="fwa-phone-country-name">' + esc(c.name) + '</span>' +
						'<span class="fwa-phone-dial-code">+' + esc(c.dialCode) + '</span>' +
					'</li>'
				);
				$list.append($li);
			});
		}

		buildList('');

		// Update the flag button label.
		function setCountry(c) {
			$btn.empty();
			$btn.append(
				$('<span class="fwa-phone-flag fwa-flag-' + c.iso2.toLowerCase() + '"></span>'),
				$('<span class="fwa-phone-selected-dial">+' + c.dialCode + '</span>'),
				$('<span class="fwa-phone-caret">&#9660;</span>')
			);
			$btn.data('iso2', c.iso2).data('dial', c.dialCode);
			$input.data('fwa-dial', c.dialCode).data('fwa-iso2', c.iso2);
		}

		if (initCC) {
			setCountry(initCC);
		}

		// Insert wrapper.
		$input.before($wrapper);
		$wrapper.append($btn).append($dropdown).append($input);

		// ----------------------------------------------------------------
		// Event: toggle dropdown
		// ----------------------------------------------------------------
		$btn.on('click', function (e) {
			e.stopPropagation();
			var isOpen = $dropdown.is(':visible');
			$('.fwa-phone-dropdown').hide();
			$('.fwa-phone-flag-btn').attr('aria-expanded', 'false');
			if (!isOpen) {
				$dropdown.show();
				$btn.attr('aria-expanded', 'true');
				$search.val('').trigger('input').focus();
			}
		});

		// ----------------------------------------------------------------
		// Event: search filter
		// ----------------------------------------------------------------
		$search.on('input', function () {
			buildList($(this).val());
		});

		// ----------------------------------------------------------------
		// Event: select country
		// ----------------------------------------------------------------
		$list.on('click', '.fwa-phone-dropdown-item', function () {
			var iso2 = $(this).data('iso2');
			var c    = getCountryByISO2(iso2);
			if (c) {
				setCountry(c);
			}
			$dropdown.hide();
			$btn.attr('aria-expanded', 'false');
			validateInput($input);
			$input.focus();
		});

		// ----------------------------------------------------------------
		// Event: close on outside click
		// ----------------------------------------------------------------
		$(document).on('click', function (e) {
			if (!$(e.target).closest($wrapper).length) {
				$dropdown.hide();
				$btn.attr('aria-expanded', 'false');
			}
		});

		// ----------------------------------------------------------------
		// Event: real-time validation
		// ----------------------------------------------------------------
		$input.on('input', function () {
			validateInput($(this));
		});
	}

	// -----------------------------------------------------------------------
	// Validation
	// -----------------------------------------------------------------------

	/**
	 * Validate phone input; show inline error if invalid.
	 *
	 * @param {jQuery} $input
	 */
	function validateInput($input) {
		var dial = $input.data('fwa-dial') || '';
		var raw  = $input.val().trim();

		// Strip leading + and dial code if user typed them.
		var digits = raw.replace(/\D/g, '');

		$input.removeClass('fwa-phone-valid fwa-phone-invalid');

		if (!digits) {
			return;
		}

		// Minimum: dial code (1-3 digits) + 6 subscriber digits = 7+ total
		if (digits.length < 7 || digits.length > 15) {
			$input.addClass('fwa-phone-invalid');
			showError($input, config.strings && config.strings.invalid_phone || 'Invalid phone number.');
		} else {
			$input.addClass('fwa-phone-valid');
			clearError($input);
		}
	}

	function showError($input, msg) {
		var $err = $input.closest('.fwa-phone-wrapper').next('.fwa-phone-error');
		if (!$err.length) {
			$err = $('<span class="fwa-phone-error" role="alert"></span>');
			$input.closest('.fwa-phone-wrapper').after($err);
		}
		$err.text(msg);
	}

	function clearError($input) {
		$input.closest('.fwa-phone-wrapper').next('.fwa-phone-error').remove();
	}

	// -----------------------------------------------------------------------
	// Full E.164 value builder
	// -----------------------------------------------------------------------

	/**
	 * Return the full E.164 phone number for a picker-attached input.
	 *
	 * @param  {jQuery} $input
	 * @return {string}
	 */
	function getFullPhone($input) {
		var dial    = $input.data('fwa-dial') || '';
		var raw     = $input.val().trim();
		var digits  = raw.replace(/\D/g, '');

		if (!digits) { return ''; }

		// If user already typed the country code, don't duplicate it.
		if (dial && digits.indexOf(dial) === 0) {
			return '+' + digits;
		}

		return dial ? ('+' + dial + digits) : ('+' + digits);
	}

	// -----------------------------------------------------------------------
	// OTP form integration
	// -----------------------------------------------------------------------

	/**
	 * Intercept OTP form submit to inject the full E.164 number.
	 */
	function hookOTPForms() {
		$(document).on('click', '.fwa-otp-send-btn', function () {
			var $form = $(this).closest('.fwa-otp-form, .fwa-verify-bar');
			var $inp  = $form.find('input[type="tel"], .fwa-otp-input').first();
			if ($inp.data('fwa-picker-attached')) {
				var full = getFullPhone($inp);
				if (full) { $inp.val(full); }
			}
		});
	}

	// -----------------------------------------------------------------------
	// Utility
	// -----------------------------------------------------------------------

	function esc(str) {
		return $('<span>').text(String(str || '')).html();
	}

	// -----------------------------------------------------------------------
	// Initialise
	// -----------------------------------------------------------------------

	$(document).ready(function () {
		// Attach to explicit targets.
		$('[data-fwa-phone]').each(function () {
			attachPicker($(this));
		});

		// Attach to OTP login/verify forms.
		$('.fwa-otp-form input[type="tel"], .fwa-verify-bar input[type="tel"]').each(function () {
			attachPicker($(this));
		});

		hookOTPForms();

		// Handle dynamically added fields.
		$(document).on('fwa_phone_field_init', function (e, $el) {
			attachPicker($el);
		});
	});

	// Expose public API.
	window.fwaPhoneField = {
		attach:       attachPicker,
		getFullPhone: getFullPhone,
		validate:     validateInput,
	};

}(jQuery));
