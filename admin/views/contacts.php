<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fwa-contacts-page">
	<h2><?php esc_html_e( 'Contacts', 'flexi-whatsapp-automation' ); ?>
		<button type="button" class="button button-primary" id="fwa-add-contact-btn"><?php esc_html_e( 'Add Contact', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button" id="fwa-import-contacts-btn"><?php esc_html_e( 'Import CSV', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button" id="fwa-export-contacts-btn"><?php esc_html_e( 'Export CSV', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button" id="fwa-sync-woo-btn"><?php esc_html_e( 'Sync from WooCommerce', 'flexi-whatsapp-automation' ); ?></button>
	</h2>

	<div id="fwa-contact-form" style="display:none;" class="fwa-form-card">
		<h3 id="fwa-contact-form-title"><?php esc_html_e( 'Add Contact', 'flexi-whatsapp-automation' ); ?></h3>
		<input type="hidden" id="fwa-contact-id" value="">
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Phone', 'flexi-whatsapp-automation' ); ?></th><td><input type="text" id="fwa-contact-phone" class="regular-text" placeholder="+1234567890" required></td></tr>
			<tr><th><?php esc_html_e( 'Name', 'flexi-whatsapp-automation' ); ?></th><td><input type="text" id="fwa-contact-name" class="regular-text"></td></tr>
			<tr><th><?php esc_html_e( 'Email', 'flexi-whatsapp-automation' ); ?></th><td><input type="email" id="fwa-contact-email" class="regular-text"></td></tr>
			<tr><th><?php esc_html_e( 'Tags', 'flexi-whatsapp-automation' ); ?></th><td><input type="text" id="fwa-contact-tags" class="regular-text" placeholder="<?php esc_attr_e( 'tag1, tag2', 'flexi-whatsapp-automation' ); ?>"></td></tr>
			<tr><th><?php esc_html_e( 'Notes', 'flexi-whatsapp-automation' ); ?></th><td><textarea id="fwa-contact-notes" class="large-text" rows="3"></textarea></td></tr>
		</table>
		<button type="button" class="button button-primary" id="fwa-save-contact"><?php esc_html_e( 'Save Contact', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button" id="fwa-cancel-contact"><?php esc_html_e( 'Cancel', 'flexi-whatsapp-automation' ); ?></button>
	</div>

	<div id="fwa-import-form" style="display:none;" class="fwa-form-card">
		<h3><?php esc_html_e( 'Import Contacts from CSV', 'flexi-whatsapp-automation' ); ?></h3>
		<p class="description"><?php esc_html_e( 'CSV should have columns: phone, name, email, tags', 'flexi-whatsapp-automation' ); ?></p>
		<input type="file" id="fwa-import-file" accept=".csv">
		<button type="button" class="button button-primary" id="fwa-do-import"><?php esc_html_e( 'Upload & Import', 'flexi-whatsapp-automation' ); ?></button>
	</div>

	<div class="fwa-filters">
		<input type="text" id="fwa-contact-search" placeholder="<?php esc_attr_e( 'Search by name or phone...', 'flexi-whatsapp-automation' ); ?>" class="regular-text">
	</div>

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

<script type="text/javascript">
jQuery(document).ready(function($){
	var page = 1;

	function loadContacts() {
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_get_contacts',
			nonce: fwa_admin.nonce,
			page: page,
			search: $('#fwa-contact-search').val()
		}, function(r) {
			if (r.success && r.data.contacts) {
				var html = '';
				$.each(r.data.contacts, function(i, c) {
					html += '<tr>' +
						'<td>' + $('<span>').text(c.phone).html() + '</td>' +
						'<td>' + $('<span>').text(c.name || '').html() + '</td>' +
						'<td>' + $('<span>').text(c.email || '').html() + '</td>' +
						'<td>' + $('<span>').text(c.tags || '').html() + '</td>' +
						'<td>' + (parseInt(c.is_subscribed) ? '&#9989;' : '&#10060;') + '</td>' +
						'<td>' + $('<span>').text(c.created_at || '').html() + '</td>' +
						'<td><button class="button button-small fwa-edit-contact" data-id="' + parseInt(c.id) + '">' + fwa_admin.strings.edit + '</button> ' +
						'<button class="button button-small fwa-delete-contact" data-id="' + parseInt(c.id) + '">' + fwa_admin.strings.delete + '</button></td></tr>';
				});
				$('#fwa-contacts-body').html(html || '<tr><td colspan="7"><?php echo esc_js( __( 'No contacts found.', 'flexi-whatsapp-automation' ) ); ?></td></tr>');
			}
		});
	}

	loadContacts();

	$('#fwa-contact-search').on('keyup', function(){ page = 1; loadContacts(); });
	$('#fwa-add-contact-btn').on('click', function(){ $('#fwa-contact-form').slideDown(); $('#fwa-contact-id').val(''); });
	$('#fwa-cancel-contact').on('click', function(){ $('#fwa-contact-form').slideUp(); });

	$('#fwa-save-contact').on('click', function(){
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_create_contact',
			nonce: fwa_admin.nonce,
			id: $('#fwa-contact-id').val(),
			phone: $('#fwa-contact-phone').val(),
			name: $('#fwa-contact-name').val(),
			email: $('#fwa-contact-email').val(),
			tags: $('#fwa-contact-tags').val(),
			notes: $('#fwa-contact-notes').val()
		}, function(r) {
			if (r.success) { $('#fwa-contact-form').slideUp(); loadContacts(); }
			else { alert(r.data ? r.data.message : 'Error'); }
		});
	});

	$(document).on('click', '.fwa-delete-contact', function(){
		if (confirm(fwa_admin.strings.confirm_delete)) {
			$.post(fwa_admin.ajax_url, { action: 'fwa_delete_contact', nonce: fwa_admin.nonce, id: $(this).data('id') }, function(){ loadContacts(); });
		}
	});

	$('#fwa-import-contacts-btn').on('click', function(){ $('#fwa-import-form').slideToggle(); });
	$('#fwa-do-import').on('click', function(){
		var file = $('#fwa-import-file')[0].files[0];
		if (!file) return;
		var fd = new FormData();
		fd.append('action', 'fwa_import_contacts');
		fd.append('nonce', fwa_admin.nonce);
		fd.append('file', file);
		$.ajax({ url: fwa_admin.ajax_url, type: 'POST', data: fd, processData: false, contentType: false,
			success: function(r) { alert(r.success ? 'Imported: ' + (r.data.imported || 0) : 'Error'); loadContacts(); }
		});
	});

	$('#fwa-export-contacts-btn').on('click', function(){
		$.post(fwa_admin.ajax_url, { action: 'fwa_export_contacts', nonce: fwa_admin.nonce }, function(r){
			if (r.success && r.data.csv) {
				var blob = new Blob([r.data.csv], {type: 'text/csv'});
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = 'fwa-contacts.csv';
				a.click();
			}
		});
	});

	$('#fwa-sync-woo-btn').on('click', function(){
		$(this).prop('disabled', true).text('<?php echo esc_js( __( 'Syncing...', 'flexi-whatsapp-automation' ) ); ?>');
		$.post(fwa_admin.ajax_url, { action: 'fwa_sync_woo_contacts', nonce: fwa_admin.nonce }, function(r){
			$('#fwa-sync-woo-btn').prop('disabled', false).text('<?php echo esc_js( __( 'Sync from WooCommerce', 'flexi-whatsapp-automation' ) ); ?>');
			alert(r.success ? 'Synced: ' + (r.data.count || 0) : 'Error');
			loadContacts();
		});
	});
});
</script>
