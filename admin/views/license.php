<?php
/**
 * License activation / deactivation admin page.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_manager = FWA_License_Manager::get_instance();
$license_data    = $license_manager->get_license_data();
$status          = $license_manager->get_status();
$is_active       = ( 'active' === $status );
$masked_key      = $license_manager->get_masked_key();
?>
<div class="fwa-license-page">
	<h2><?php esc_html_e( 'License', 'flexi-whatsapp-automation' ); ?></h2>

	<div class="fwa-form-card" style="max-width:600px; margin-top:15px;">
		<h3 style="margin-top:0;">
			<?php esc_html_e( 'License Activation', 'flexi-whatsapp-automation' ); ?>
		</h3>

		<!-- Status badge -->
		<p>
			<strong><?php esc_html_e( 'Status:', 'flexi-whatsapp-automation' ); ?></strong>
			<?php if ( $is_active ) : ?>
				<span class="fwa-badge fwa-badge-success"><?php esc_html_e( 'Active', 'flexi-whatsapp-automation' ); ?></span>
			<?php elseif ( 'expired' === $status ) : ?>
				<span class="fwa-badge fwa-badge-warning"><?php esc_html_e( 'Expired', 'flexi-whatsapp-automation' ); ?></span>
			<?php elseif ( 'invalid' === $status ) : ?>
				<span class="fwa-badge fwa-badge-error"><?php esc_html_e( 'Invalid', 'flexi-whatsapp-automation' ); ?></span>
			<?php else : ?>
				<span class="fwa-badge fwa-badge-inactive"><?php esc_html_e( 'Inactive', 'flexi-whatsapp-automation' ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( $is_active && ! empty( $license_data['expires_at'] ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Expires:', 'flexi-whatsapp-automation' ); ?></strong>
				<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_data['expires_at'] ) ) ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $is_active && ! empty( $license_data['plan'] ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Plan:', 'flexi-whatsapp-automation' ); ?></strong>
				<?php echo esc_html( ucfirst( $license_data['plan'] ) ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $is_active && ! empty( $masked_key ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'License Key:', 'flexi-whatsapp-automation' ); ?></strong>
				<code><?php echo esc_html( $masked_key ); ?></code>
			</p>
		<?php endif; ?>

		<hr>

		<div id="fwa-license-notices"></div>

		<?php if ( ! $is_active ) : ?>
			<!-- Activation form -->
			<form id="fwa-license-activate-form">
				<?php wp_nonce_field( 'fwa_admin_nonce', 'fwa_license_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th>
							<label for="fwa-license-key"><?php esc_html_e( 'License Key', 'flexi-whatsapp-automation' ); ?></label>
						</th>
						<td>
							<input type="text" id="fwa-license-key" name="license_key"
								class="regular-text" placeholder="<?php esc_attr_e( 'Enter your license key', 'flexi-whatsapp-automation' ); ?>"
								autocomplete="off" spellcheck="false" required>
							<p class="description">
								<?php esc_html_e( 'Enter the license key you received after purchase.', 'flexi-whatsapp-automation' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary" id="fwa-license-activate-btn">
						<?php esc_html_e( 'Activate License', 'flexi-whatsapp-automation' ); ?>
					</button>
				</p>
			</form>
		<?php else : ?>
			<!-- Deactivation form -->
			<form id="fwa-license-deactivate-form">
				<?php wp_nonce_field( 'fwa_admin_nonce', 'fwa_license_nonce' ); ?>
				<p class="description" style="margin-bottom:15px;">
					<?php esc_html_e( 'Deactivating your license will disable core features on this site. You can re-activate or transfer the license to another domain.', 'flexi-whatsapp-automation' ); ?>
				</p>
				<p class="submit">
					<button type="submit" class="button button-secondary" id="fwa-license-deactivate-btn">
						<?php esc_html_e( 'Deactivate License', 'flexi-whatsapp-automation' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( ! $is_active ) : ?>
		<div class="fwa-form-card" style="max-width:600px; margin-top:15px; background:#fff3cd; border-left:4px solid #ffc107;">
			<h4 style="margin-top:0;"><?php esc_html_e( 'Features Disabled Without License', 'flexi-whatsapp-automation' ); ?></h4>
			<ul style="list-style:disc; padding-left:20px;">
				<li><?php esc_html_e( 'WhatsApp instance connection', 'flexi-whatsapp-automation' ); ?></li>
				<li><?php esc_html_e( 'Sending messages', 'flexi-whatsapp-automation' ); ?></li>
				<li><?php esc_html_e( 'Campaign execution', 'flexi-whatsapp-automation' ); ?></li>
				<li><?php esc_html_e( 'Automation triggers', 'flexi-whatsapp-automation' ); ?></li>
				<li><?php esc_html_e( 'Scheduled message processing', 'flexi-whatsapp-automation' ); ?></li>
			</ul>
			<p>
				<?php esc_html_e( 'The admin dashboard, contacts, and settings remain accessible so you can configure everything before activating.', 'flexi-whatsapp-automation' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>
