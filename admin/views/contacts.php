<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fwa-contacts-page">
	<!-- Page Header -->
	<div class="fwa-page-header">
		<div class="fwa-page-header-content">
			<h2><?php esc_html_e( 'Contacts', 'flexi-whatsapp-automation' ); ?></h2>
			<p class="fwa-page-subtitle"><?php esc_html_e( 'Manage your WhatsApp contacts', 'flexi-whatsapp-automation' ); ?></p>
		</div>
		<div class="fwa-page-header-actions">
			<button type="button" class="button button-primary" id="fwa-add-contact-btn">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add Contact', 'flexi-whatsapp-automation' ); ?>
			</button>
			<button type="button" class="button" id="fwa-import-contacts-btn">
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Import CSV', 'flexi-whatsapp-automation' ); ?>
			</button>
			<button type="button" class="button" id="fwa-export-contacts-btn">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export CSV', 'flexi-whatsapp-automation' ); ?>
			</button>
			<button type="button" class="button" id="fwa-sync-woo-btn">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Sync WooCommerce', 'flexi-whatsapp-automation' ); ?>
			</button>
		</div>
	</div>

	<!-- Add / Edit Contact Modal -->
	<div id="fwa-contact-modal" class="fwa-modal-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Contact Form', 'flexi-whatsapp-automation' ); ?>">
		<div class="fwa-modal">
			<div class="fwa-modal-header">
				<h3 id="fwa-contact-form-title"><?php esc_html_e( 'Add Contact', 'flexi-whatsapp-automation' ); ?></h3>
				<button type="button" class="fwa-modal-close" id="fwa-cancel-contact" aria-label="<?php esc_attr_e( 'Close', 'flexi-whatsapp-automation' ); ?>">&times;</button>
			</div>
			<div class="fwa-modal-body">
				<input type="hidden" id="fwa-contact-id" value="">
				<div class="fwa-form-group">
					<label for="fwa-contact-phone"><?php esc_html_e( 'Phone Number', 'flexi-whatsapp-automation' ); ?> <span class="fwa-required">*</span></label>
					<input type="text" id="fwa-contact-phone" class="regular-text" placeholder="+1234567890" required>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-contact-name"><?php esc_html_e( 'Name', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-contact-name" class="regular-text">
				</div>
				<div class="fwa-form-group">
					<label for="fwa-contact-email"><?php esc_html_e( 'Email', 'flexi-whatsapp-automation' ); ?></label>
					<input type="email" id="fwa-contact-email" class="regular-text">
				</div>
				<div class="fwa-form-group">
					<label for="fwa-contact-tags"><?php esc_html_e( 'Tags', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-contact-tags" class="regular-text" placeholder="<?php esc_attr_e( 'tag1, tag2', 'flexi-whatsapp-automation' ); ?>">
					<p class="description"><?php esc_html_e( 'Separate tags with commas.', 'flexi-whatsapp-automation' ); ?></p>
				</div>
				<div class="fwa-form-group">
					<label for="fwa-contact-notes"><?php esc_html_e( 'Notes', 'flexi-whatsapp-automation' ); ?></label>
					<textarea id="fwa-contact-notes" class="large-text" rows="3"></textarea>
				</div>
			</div>
			<div class="fwa-modal-footer">
				<button type="button" class="button" id="fwa-cancel-contact-footer"><?php esc_html_e( 'Cancel', 'flexi-whatsapp-automation' ); ?></button>
				<button type="button" class="button button-primary" id="fwa-save-contact"><?php esc_html_e( 'Save Contact', 'flexi-whatsapp-automation' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Import CSV Modal -->
	<div id="fwa-import-modal" class="fwa-modal-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Import Contacts', 'flexi-whatsapp-automation' ); ?>">
		<div class="fwa-modal fwa-modal-sm">
			<div class="fwa-modal-header">
				<h3><?php esc_html_e( 'Import Contacts from CSV', 'flexi-whatsapp-automation' ); ?></h3>
				<button type="button" class="fwa-modal-close" id="fwa-import-close" aria-label="<?php esc_attr_e( 'Close', 'flexi-whatsapp-automation' ); ?>">&times;</button>
			</div>
			<div class="fwa-modal-body">
				<p class="description"><?php esc_html_e( 'CSV should have columns: phone, name, email, tags', 'flexi-whatsapp-automation' ); ?></p>
				<div class="fwa-form-group">
					<input type="file" id="fwa-import-file" accept=".csv" class="regular-text">
				</div>
			</div>
			<div class="fwa-modal-footer">
				<button type="button" class="button" id="fwa-import-cancel"><?php esc_html_e( 'Cancel', 'flexi-whatsapp-automation' ); ?></button>
				<button type="button" class="button button-primary" id="fwa-do-import"><?php esc_html_e( 'Upload &amp; Import', 'flexi-whatsapp-automation' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Search & Filter Bar -->
	<div class="fwa-form-card">
		<div class="fwa-filters">
			<span class="dashicons dashicons-search fwa-search-icon"></span>
			<input type="text" id="fwa-contact-search" placeholder="<?php esc_attr_e( 'Search by name or phone...', 'flexi-whatsapp-automation' ); ?>" class="regular-text">
			<span id="fwa-contacts-count" class="fwa-badge fwa-badge-info"></span>
		</div>
	</div>

	<!-- Contacts Table -->
	<div class="fwa-form-card">
		<table class="widefat striped" id="fwa-contacts-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Phone', 'flexi-whatsapp-automation' ); ?></th>
					<th><?php esc_html_e( 'Name', 'flexi-whatsapp-automation' ); ?></th>
					<th><?php esc_html_e( 'Email', 'flexi-whatsapp-automation' ); ?></th>
					<th><?php esc_html_e( 'Tags', 'flexi-whatsapp-automation' ); ?></th>
					<th><?php esc_html_e( 'Subscribed', 'flexi-whatsapp-automation' ); ?></th>
					<th><?php esc_html_e( 'Created', 'flexi-whatsapp-automation' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'flexi-whatsapp-automation' ); ?></th>
				</tr>
			</thead>
			<tbody id="fwa-contacts-body">
				<tr><td colspan="7"><?php esc_html_e( 'Loading...', 'flexi-whatsapp-automation' ); ?></td></tr>
			</tbody>
		</table>
		<div class="fwa-pagination" id="fwa-contacts-pagination"></div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

	var page    = 1;
	var perPage = 25;

	function esc(str) { return $('<span>').text(str || '').html(); }

	function loadContacts() {
		$.post(fwa_admin.ajax_url, {
			action:   'fwa_get_contacts',
			nonce:    fwa_admin.nonce,
			page:     page,
			per_page: perPage,
			search:   $('#fwa-contact-search').val(),
		}, function(r) {
			var $body = $('#fwa-contacts-body');
			$body.empty();
			if (!r.success || !r.data || !r.data.contacts || !r.data.contacts.length) {
				$body.html('<tr><td colspan="7"><?php echo esc_js( __( 'No contacts found.', 'flexi-whatsapp-automation' ) ); ?></td></tr>');
				return;
			}
			$.each(r.data.contacts, function(i, c) {
				var subLabel = c.is_subscribed ? '<?php echo esc_js( __( 'Subscribed', 'flexi-whatsapp-automation' ) ); ?>' : '<?php echo esc_js( __( 'Opted Out', 'flexi-whatsapp-automation' ) ); ?>';
				var subColor = c.is_subscribed ? '#46b450' : '#dc3232';
				$body.append(
					'<tr data-id="' + esc(c.id) + '">' +
					'<td>' + esc(c.phone) + '</td>' +
					'<td>' + esc(c.name) + '</td>' +
					'<td>' + esc(c.email) + '</td>' +
					'<td>' + esc(c.tags) + '</td>' +
					'<td><span class="fwa-sub-badge" style="background:' + subColor + ';color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">' + subLabel + '</span></td>' +
					'<td>' + esc(c.created_at) + '</td>' +
					'<td>' +
					  '<button class="button button-small fwa-toggle-sub" data-id="' + esc(c.id) + '" data-sub="' + esc(c.is_subscribed) + '">' +
					  (c.is_subscribed ? '<?php echo esc_js( __( 'Opt Out', 'flexi-whatsapp-automation' ) ); ?>' : '<?php echo esc_js( __( 'Opt In', 'flexi-whatsapp-automation' ) ); ?>') +
					  '</button> ' +
					  '<button class="button button-small fwa-check-wa" data-id="' + esc(c.id) + '" data-phone="' + esc(c.phone) + '" title="<?php echo esc_js( __( 'Check if on WhatsApp', 'flexi-whatsapp-automation' ) ); ?>">WA?</button> ' +
					  '<button class="button button-small button-link-delete fwa-delete-contact" data-id="' + esc(c.id) + '"><?php echo esc_js( __( 'Delete', 'flexi-whatsapp-automation' ) ); ?></button>' +
					'</td></tr>'
				);
			});

			if (r.data.total) {
				$('#fwa-contacts-count').text(r.data.total + ' <?php echo esc_js( __( 'contacts', 'flexi-whatsapp-automation' ) ); ?>');
			}
		});
	}

	loadContacts();

	// Search
	var searchTimer;
	$('#fwa-contact-search').on('input', function() {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function(){ page = 1; loadContacts(); }, 400);
	});

	// Toggle subscription
	$(document).on('click', '.fwa-toggle-sub', function() {
		var id  = $(this).data('id');
		var $el = $(this);
		$.post(fwa_admin.ajax_url, { action: 'fwa_toggle_contact_subscription', nonce: fwa_admin.nonce, id: id }, function(r) {
			if (r.success) { loadContacts(); }
			else { alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Error.', 'flexi-whatsapp-automation' ) ); ?>'); }
		});
	});

	// Check WhatsApp
	$(document).on('click', '.fwa-check-wa', function() {
		var phone = $(this).data('phone');
		var $btn  = $(this).prop('disabled', true).text('...');
		$.post(fwa_admin.ajax_url, { action: 'fwa_check_wa_number', nonce: fwa_admin.nonce, phone: phone }, function(r) {
			$btn.prop('disabled', false).text('WA?');
			if (r.success) {
				var msg = r.data.exists
					? '<?php echo esc_js( __( '✓ This number is on WhatsApp.', 'flexi-whatsapp-automation' ) ); ?>'
					: '<?php echo esc_js( __( '✗ This number is NOT on WhatsApp.', 'flexi-whatsapp-automation' ) ); ?>';
				alert(msg);
			} else {
				alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Check failed.', 'flexi-whatsapp-automation' ) ); ?>');
			}
		});
	});

	// Delete contact
	$(document).on('click', '.fwa-delete-contact', function() {
		if (!confirm('<?php echo esc_js( __( 'Delete this contact?', 'flexi-whatsapp-automation' ) ); ?>')) return;
		var id = $(this).data('id');
		$.post(fwa_admin.ajax_url, { action: 'fwa_delete_contact', nonce: fwa_admin.nonce, id: id }, function(r) {
			if (r.success) { loadContacts(); }
		});
	});

	// Add contact modal
	$('#fwa-add-contact-btn').on('click', function() {
		$('#fwa-contact-id').val('');
		$('#fwa-contact-phone, #fwa-contact-name, #fwa-contact-email, #fwa-contact-tags, #fwa-contact-notes').val('');
		$('#fwa-contact-modal').fadeIn(200);
	});

	$('#fwa-cancel-contact, #fwa-cancel-contact-footer').on('click', function() {
		$('#fwa-contact-modal').fadeOut(200);
	});

	$('#fwa-save-contact').on('click', function() {
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_create_contact',
			nonce:  fwa_admin.nonce,
			phone:  $('#fwa-contact-phone').val(),
			name:   $('#fwa-contact-name').val(),
			email:  $('#fwa-contact-email').val(),
			tags:   $('#fwa-contact-tags').val(),
		}, function(r) {
			if (r.success) { $('#fwa-contact-modal').fadeOut(200); loadContacts(); }
			else { alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Error saving contact.', 'flexi-whatsapp-automation' ) ); ?>'); }
		});
	});

	// Import CSV
	$('#fwa-import-contacts-btn').on('click', function() { $('#fwa-import-modal').fadeIn(200); });
	$('#fwa-import-close, #fwa-import-cancel').on('click', function() { $('#fwa-import-modal').fadeOut(200); });

	$('#fwa-do-import').on('click', function() {
		var file = $('#fwa-import-file')[0].files[0];
		if (!file) { alert('<?php echo esc_js( __( 'Please select a CSV file.', 'flexi-whatsapp-automation' ) ); ?>'); return; }
		var fd = new FormData();
		fd.append('action', 'fwa_import_contacts');
		fd.append('nonce', fwa_admin.nonce);
		fd.append('file', file);
		$.ajax({ url: fwa_admin.ajax_url, type: 'POST', data: fd, processData: false, contentType: false, success: function(r) {
			if (r.success) { $('#fwa-import-modal').fadeOut(200); loadContacts(); alert(r.data.message || '<?php echo esc_js( __( 'Import complete.', 'flexi-whatsapp-automation' ) ); ?>'); }
			else { alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Import failed.', 'flexi-whatsapp-automation' ) ); ?>'); }
		}});
	});

	// Export CSV
	$('#fwa-export-contacts-btn').on('click', function() {
		$.post(fwa_admin.ajax_url, { action: 'fwa_export_contacts', nonce: fwa_admin.nonce }, function(r) {
			if (r.success && r.data && r.data.csv) {
				var blob = new Blob([r.data.csv], {type: 'text/csv'});
				var a    = document.createElement('a');
				a.href   = URL.createObjectURL(blob);
				a.download = 'fwa-contacts.csv';
				a.click();
			}
		});
	});

	// WooCommerce sync
	$('#fwa-sync-woo-btn').on('click', function() {
		var $btn = $(this).prop('disabled', true).text('<?php echo esc_js( __( 'Syncing…', 'flexi-whatsapp-automation' ) ); ?>');
		$.post(fwa_admin.ajax_url, { action: 'fwa_sync_woo_contacts', nonce: fwa_admin.nonce }, function(r) {
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Sync WooCommerce', 'flexi-whatsapp-automation' ) ); ?>');
			if (r.success) { loadContacts(); alert(r.data.message || '<?php echo esc_js( __( 'Sync complete.', 'flexi-whatsapp-automation' ) ); ?>'); }
			else { alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Sync failed.', 'flexi-whatsapp-automation' ) ); ?>'); }
		});
	});

	// Close modals on overlay click
	$('.fwa-modal-overlay').on('click', function(e) {
		if ($(e.target).is('.fwa-modal-overlay')) { $(this).fadeOut(200); }
	});
});
</script>

