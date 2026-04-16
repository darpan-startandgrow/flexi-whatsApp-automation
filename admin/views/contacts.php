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
