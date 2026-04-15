/**
 * Flexi WhatsApp Automation – Admin JavaScript
 *
 * Handles deactivation modal, admin notices, and common UI helpers.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

/* global jQuery, fwa_admin, fwa_deactivation */

(function ($) {
	'use strict';

	/**
	 * ----------------------------------------------------------------
	 *  Deactivation Data-Cleanup Modal
	 *
	 *  Intercepts the "Deactivate" link on the Plugins page, shows a
	 *  prompt asking the user whether to Delete or Keep data, then
	 *  stores the choice via AJAX before actually deactivating.
	 * ----------------------------------------------------------------
	 */
	$(document).ready(function () {

		/* Only run on the main Plugins list page. */
		if (typeof fwa_deactivation === 'undefined') {
			return;
		}

		var $deactivateLink = $('tr[data-plugin="' + fwa_deactivation.plugin_basename + '"] .deactivate a');
		if (!$deactivateLink.length) {
			return;
		}

		var deactivateHref = $deactivateLink.attr('href');

		/* Build the modal HTML once. */
		var modalHTML =
			'<div id="fwa-deactivation-overlay" class="fwa-deactivation-overlay" style="display:none;">' +
				'<div class="fwa-deactivation-modal">' +
					'<h3>' + fwa_deactivation.strings.title + '</h3>' +
					'<p>' + fwa_deactivation.strings.message + '</p>' +
					'<div class="fwa-modal-actions">' +
						'<button type="button" class="button button-delete-data" id="fwa-deactivate-delete">' +
							fwa_deactivation.strings.delete_data +
						'</button>' +
						'<button type="button" class="button button-primary" id="fwa-deactivate-keep">' +
							fwa_deactivation.strings.keep_data +
						'</button>' +
						'<button type="button" class="button" id="fwa-deactivate-cancel">' +
							fwa_deactivation.strings.cancel +
						'</button>' +
					'</div>' +
				'</div>' +
			'</div>';

		$('body').append(modalHTML);

		var $overlay = $('#fwa-deactivation-overlay');

		/* Intercept the deactivation click. */
		$deactivateLink.on('click', function (e) {
			e.preventDefault();
			$overlay.fadeIn(200);
		});

		/* Cancel – close modal, do nothing. */
		$('#fwa-deactivate-cancel').on('click', function () {
			$overlay.fadeOut(200);
		});

		/* Close on overlay background click. */
		$overlay.on('click', function (e) {
			if (e.target === this) {
				$overlay.fadeOut(200);
			}
		});

		/* Keep Data – deactivate without cleanup. */
		$('#fwa-deactivate-keep').on('click', function () {
			doDeactivate('no');
		});

		/* Delete Data – tell server to clean up, then deactivate. */
		$('#fwa-deactivate-delete').on('click', function () {
			doDeactivate('yes');
		});

		/**
		 * Send AJAX cleanup preference and redirect to WP deactivation URL.
		 *
		 * @param {string} cleanup 'yes' or 'no'.
		 */
		function doDeactivate(cleanup) {
			/* Disable buttons to prevent double-clicks. */
			$overlay.find('button').prop('disabled', true);

			$.post(fwa_deactivation.ajax_url, {
				action:  'fwa_deactivation_cleanup',
				nonce:   fwa_deactivation.nonce,
				cleanup: cleanup
			}).always(function () {
				/* Redirect to the actual WordPress deactivation URL. */
				window.location.href = deactivateHref;
			});
		}
	});

	/**
	 * ----------------------------------------------------------------
	 *  Admin Notices – Auto-dismiss after 5 seconds.
	 * ----------------------------------------------------------------
	 */
	$(document).ready(function () {
		$('.fwa-notice.is-dismissible').delay(5000).fadeOut(400);
	});

	/**
	 * ----------------------------------------------------------------
	 *  Localization helper strings (fallback defaults).
	 * ----------------------------------------------------------------
	 */
	if (typeof window.fwa_admin === 'undefined') {
		window.fwa_admin = {
			ajax_url: '',
			nonce:    '',
			strings:  {}
		};
	}

	var S = window.fwa_admin.strings || {};
	S.confirm_delete = S.confirm_delete || 'Are you sure you want to delete this item? This action cannot be undone.';
	S.sending        = S.sending        || 'Sending…';
	S.sent           = S.sent           || 'Message sent successfully.';
	S.error          = S.error          || 'An error occurred. Please try again.';
	S.saved          = S.saved          || 'Settings saved successfully.';
	S.loading        = S.loading        || 'Loading…';
	S.no_results     = S.no_results     || 'No results found.';
	S.edit           = S.edit           || 'Edit';
	S.delete         = S.delete         || 'Delete';
	window.fwa_admin.strings = S;

})(jQuery);
