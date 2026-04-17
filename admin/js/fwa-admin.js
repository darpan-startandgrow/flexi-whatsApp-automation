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
	S.syncing        = S.syncing        || 'Syncing…';
	S.rules_saved    = S.rules_saved    || 'Rules saved!';
	S.sync_woo       = S.sync_woo       || 'Sync WooCommerce';
	S.active_instance = S.active_instance || 'Active Instance';
	window.fwa_admin.strings = S;

	/**
	 * ----------------------------------------------------------------
	 *  Toast notifications
	 * ----------------------------------------------------------------
	 */
	window.fwaToast = function (message, type) {
		type = type || 'success';
		var colors = { success: '#00a32a', error: '#d63638', warning: '#dba617', info: '#2271b1' };
		var $toast = $('<div class="fwa-toast" style="' +
			'position:fixed;top:40px;right:20px;z-index:200000;' +
			'padding:12px 20px;border-radius:6px;background:#fff;' +
			'box-shadow:0 2px 12px rgba(0,0,0,0.15);font-size:13px;' +
			'border-left:4px solid ' + (colors[type] || colors.info) + ';' +
			'max-width:400px;color:#1d2327;">' + message + '</div>');
		$('body').append($toast);
		$toast.delay(4000).fadeOut(400, function () { $(this).remove(); });
	};

	/**
	 * ----------------------------------------------------------------
	 *  Modal helpers
	 * ----------------------------------------------------------------
	 */
	function openModal(id) {
		$('#' + id).addClass('active');
	}

	function closeModal(id) {
		$('#' + id).removeClass('active');
	}

	// Close modal on overlay click.
	$(document).on('click', '.fwa-modal-overlay', function (e) {
		if (e.target === this) {
			$(this).removeClass('active');
		}
	});

	// Close modal on close button.
	$(document).on('click', '.fwa-modal-close', function () {
		$(this).closest('.fwa-modal-overlay').removeClass('active');
	});

	/**
	 * ----------------------------------------------------------------
	 *  Contacts Page
	 * ----------------------------------------------------------------
	 */
	$(document).ready(function () {
		if (!$('.fwa-contacts-page').length) {
			return;
		}

		var contactPage = 1;
		var debounce;

		function loadContacts() {
			$('#fwa-contacts-body').html('<tr><td colspan="7">' + S.loading + '</td></tr>');
			$.post(fwa_admin.ajax_url, {
				action: 'fwa_get_contacts',
				nonce: fwa_admin.nonce,
				page: contactPage,
				search: $('#fwa-contact-search').val()
			}, function (r) {
				if (r.success && r.data.contacts) {
					var html = '';
					$.each(r.data.contacts, function (i, c) {
						html += '<tr>' +
							'<td>' + $('<span>').text(c.phone).html() + '</td>' +
							'<td>' + $('<span>').text(c.name || '').html() + '</td>' +
							'<td>' + $('<span>').text(c.email || '').html() + '</td>' +
							'<td>' + $('<span>').text(c.tags || '').html() + '</td>' +
							'<td>' + (parseInt(c.is_subscribed, 10) ? '<span class="fwa-badge fwa-badge-connected">Yes</span>' : '<span class="fwa-badge fwa-badge-disconnected">No</span>') + '</td>' +
							'<td>' + $('<span>').text(c.created_at || '').html() + '</td>' +
							'<td>' +
								'<button class="button button-small fwa-edit-contact" data-id="' + parseInt(c.id, 10) + '">' + S.edit + '</button> ' +
								'<button class="button button-small fwa-delete-contact" data-id="' + parseInt(c.id, 10) + '">' + S.delete + '</button>' +
							'</td></tr>';
					});
					$('#fwa-contacts-body').html(html || '<tr><td colspan="7">' + S.no_results + '</td></tr>');
					var count = r.data.total || r.data.contacts.length;
					$('#fwa-contacts-count').text(count + ' contacts');
				}
			});
		}

		loadContacts();

		$('#fwa-contact-search').on('keyup', function () {
			clearTimeout(debounce);
			debounce = setTimeout(function () {
				contactPage = 1;
				loadContacts();
			}, 300);
		});

		$('#fwa-add-contact-btn').on('click', function () {
			$('#fwa-contact-id').val('');
			$('#fwa-contact-phone, #fwa-contact-name, #fwa-contact-email, #fwa-contact-tags, #fwa-contact-notes').val('');
			$('#fwa-contact-form-title').text('Add Contact');
			openModal('fwa-contact-modal');
		});

		$('#fwa-cancel-contact, #fwa-cancel-contact-footer').on('click', function () {
			closeModal('fwa-contact-modal');
		});

		$('#fwa-save-contact').on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);
			$.post(fwa_admin.ajax_url, {
				action: 'fwa_create_contact',
				nonce: fwa_admin.nonce,
				id: $('#fwa-contact-id').val(),
				phone: $('#fwa-contact-phone').val(),
				name: $('#fwa-contact-name').val(),
				email: $('#fwa-contact-email').val(),
				tags: $('#fwa-contact-tags').val(),
				notes: $('#fwa-contact-notes').val()
			}, function (r) {
				$btn.prop('disabled', false);
				if (r.success) {
					closeModal('fwa-contact-modal');
					window.fwaToast(S.saved, 'success');
					loadContacts();
				} else {
					window.fwaToast(r.data ? r.data.message : S.error, 'error');
				}
			});
		});

		$(document).on('click', '.fwa-edit-contact', function () {
			var id = $(this).data('id');
			$('#fwa-contact-form-title').text(S.edit);
			$('#fwa-contact-id').val(id);
			openModal('fwa-contact-modal');
		});

		$(document).on('click', '.fwa-delete-contact', function () {
			if (confirm(S.confirm_delete)) {
				$.post(fwa_admin.ajax_url, {
					action: 'fwa_delete_contact',
					nonce: fwa_admin.nonce,
					id: $(this).data('id')
				}, function () {
					loadContacts();
					window.fwaToast('Contact deleted.', 'success');
				});
			}
		});

		$('#fwa-import-contacts-btn').on('click', function () {
			openModal('fwa-import-modal');
		});

		$('#fwa-import-close, #fwa-import-cancel').on('click', function () {
			closeModal('fwa-import-modal');
		});

		$('#fwa-do-import').on('click', function () {
			var file = $('#fwa-import-file')[0].files[0];
			if (!file) {
				return;
			}
			var fd = new FormData();
			fd.append('action', 'fwa_import_contacts');
			fd.append('nonce', fwa_admin.nonce);
			fd.append('file', file);
			$.ajax({
				url: fwa_admin.ajax_url,
				type: 'POST',
				data: fd,
				processData: false,
				contentType: false,
				success: function (r) {
					closeModal('fwa-import-modal');
					window.fwaToast(r.success ? 'Imported: ' + (r.data.imported || 0) : S.error, r.success ? 'success' : 'error');
					loadContacts();
				}
			});
		});

		$('#fwa-export-contacts-btn').on('click', function () {
			$.post(fwa_admin.ajax_url, { action: 'fwa_export_contacts', nonce: fwa_admin.nonce }, function (r) {
				if (r.success && r.data.csv) {
					var blob = new Blob([r.data.csv], { type: 'text/csv' });
					var a = document.createElement('a');
					a.href = URL.createObjectURL(blob);
					a.download = 'fwa-contacts.csv';
					a.click();
					window.fwaToast('Export complete.', 'success');
				}
			});
		});

		$('#fwa-sync-woo-btn').on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true).find('.dashicons').addClass('fwa-spin');
			$.post(fwa_admin.ajax_url, { action: 'fwa_sync_woo_contacts', nonce: fwa_admin.nonce }, function (r) {
				$btn.prop('disabled', false).find('.dashicons').removeClass('fwa-spin');
				window.fwaToast(r.success ? 'Synced: ' + (r.data.count || 0) : S.error, r.success ? 'success' : 'error');
				loadContacts();
			}).fail(function () {
				$btn.prop('disabled', false).find('.dashicons').removeClass('fwa-spin');
				window.fwaToast(S.error, 'error');
			});
		});
	});

	/**
	 * ----------------------------------------------------------------
	 *  Automation Page
	 * ----------------------------------------------------------------
	 */
	$(document).ready(function () {
		if (!$('.fwa-automation-page').length) {
			return;
		}

		var ruleIdx = 0;

		function loadRules() {
			$.post(fwa_admin.ajax_url, { action: 'fwa_get_automation_rules', nonce: fwa_admin.nonce }, function (r) {
				if (r.success && r.data.rules && r.data.rules.length > 0) {
					$('#fwa-rules-empty').hide();
					$.each(r.data.rules, function (i, rule) { addRuleCard(rule); });
				}
			});
			// Load instances for dropdowns.
			$.post(fwa_admin.ajax_url, { action: 'fwa_get_instances', nonce: fwa_admin.nonce }, function (r) {
				if (r.success && r.data.instances) {
					window.fwaInstances = r.data.instances;
					$('.fwa-instance-select').each(function () { populateInstances($(this)); });
				}
			});
		}

		function populateInstances($sel) {
			$sel.empty().append('<option value="">' + S.active_instance + '</option>');
			if (window.fwaInstances) {
				$.each(window.fwaInstances, function (i, inst) {
					$sel.append('<option value="' + parseInt(inst.id, 10) + '">' + $('<span>').text(inst.name).html() + '</option>');
				});
			}
		}

		function addRuleCard(rule) {
			rule = rule || {};
			var tpl = $('#fwa-rule-template').html();
			tpl = tpl.replace(/\{\{INDEX\}\}/g, ruleIdx).replace(/\{\{NUM\}\}/g, ruleIdx + 1);
			$('#fwa-rules-empty').before(tpl);
			$('#fwa-rules-empty').hide();

			var $card = $('.fwa-rule-card[data-index="' + ruleIdx + '"]');
			if (rule.event) { $card.find('.fwa-rule-event').val(rule.event); }
			if (rule.enabled === false) { $card.find('.fwa-rule-enabled').prop('checked', false); }
			if (rule.phone_field) { $card.find('.fwa-rule-phone-field').val(rule.phone_field); }
			if (rule.message_type) { $card.find('.fwa-rule-msg-type').val(rule.message_type); }
			if (rule.template) { $card.find('.fwa-rule-template').val(rule.template); }
			if (rule.media_url) { $card.find('.fwa-rule-media-url').val(rule.media_url); }

			populateInstances($card.find('.fwa-instance-select'));
			if (rule.instance_id) { $card.find('.fwa-rule-instance').val(rule.instance_id); }

			ruleIdx++;
		}

		$('#fwa-add-rule-btn').on('click', function () {
			addRuleCard();
		});

		$(document).on('click', '.fwa-remove-rule', function () {
			$(this).closest('.fwa-rule-card').remove();
			if (!$('.fwa-rule-card').length) {
				$('#fwa-rules-empty').show();
			}
		});

		$('#fwa-save-rules').on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);
			var data = [];
			$('.fwa-rule-card').each(function () {
				var $c = $(this);
				data.push({
					event: $c.find('.fwa-rule-event').val(),
					enabled: $c.find('.fwa-rule-enabled').is(':checked'),
					instance_id: $c.find('.fwa-rule-instance').val(),
					phone_field: $c.find('.fwa-rule-phone-field').val(),
					message_type: $c.find('.fwa-rule-msg-type').val(),
					template: $c.find('.fwa-rule-template').val(),
					media_url: $c.find('.fwa-rule-media-url').val()
				});
			});
			$.post(fwa_admin.ajax_url, {
				action: 'fwa_save_automation_rules',
				nonce: fwa_admin.nonce,
				rules: JSON.stringify(data)
			}, function (r) {
				$btn.prop('disabled', false);
				window.fwaToast(r.success ? S.rules_saved : S.error, r.success ? 'success' : 'error');
			}).fail(function () {
				$btn.prop('disabled', false);
				window.fwaToast(S.error, 'error');
			});
		});

		loadRules();
	});

	/**
	 * ----------------------------------------------------------------
	 *  License Page
	 * ----------------------------------------------------------------
	 */
	$(document).ready(function () {
		if (!$('.fwa-license-page').length) {
			return;
		}

		var $notices = $('#fwa-license-notices');

		function showLicenseNotice(message, type) {
			type = type || 'error';
			var cssClass = type === 'success' ? 'notice-success' : 'notice-error';
			$notices.html(
				'<div class="notice ' + cssClass + ' is-dismissible"><p>' +
				$('<span>').text(message).html() +
				'</p></div>'
			);
		}

		/* Activation form. */
		$('#fwa-license-activate-form').on('submit', function (e) {
			e.preventDefault();

			var $btn = $('#fwa-license-activate-btn');
			var key  = $('#fwa-license-key').val();

			if (!key || !key.trim()) {
				showLicenseNotice(S.error || 'Please enter a license key.');
				return;
			}

			$btn.prop('disabled', true).text(S.loading || 'Activating…');
			$notices.empty();

			$.post(fwa_admin.ajax_url, {
				action:      'fwa_activate_license',
				nonce:       fwa_admin.nonce,
				license_key: key
			}, function (r) {
				if (r.success) {
					showLicenseNotice(r.data.message || 'License activated successfully.', 'success');
					/* Reload after short delay so the page shows the new state. */
					setTimeout(function () { location.reload(); }, 1200);
				} else {
					showLicenseNotice(r.data && r.data.message ? r.data.message : 'Invalid license key. Please check and try again.');
					$btn.prop('disabled', false).text('Activate License');
				}
			}).fail(function () {
				showLicenseNotice(S.error || 'An error occurred. Please try again.');
				$btn.prop('disabled', false).text('Activate License');
			});
		});

		/* Deactivation form. */
		$('#fwa-license-deactivate-form').on('submit', function (e) {
			e.preventDefault();

			var $btn = $('#fwa-license-deactivate-btn');
			$btn.prop('disabled', true).text(S.loading || 'Deactivating…');
			$notices.empty();

			$.post(fwa_admin.ajax_url, {
				action: 'fwa_deactivate_license',
				nonce:  fwa_admin.nonce
			}, function (r) {
				if (r.success) {
					showLicenseNotice(r.data.message || 'License deactivated.', 'success');
					setTimeout(function () { location.reload(); }, 1200);
				} else {
					showLicenseNotice(r.data && r.data.message ? r.data.message : S.error);
					$btn.prop('disabled', false).text('Deactivate License');
				}
			}).fail(function () {
				showLicenseNotice(S.error || 'An error occurred. Please try again.');
				$btn.prop('disabled', false).text('Deactivate License');
			});
		});
	});

})(jQuery);
