<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fwa-schedules-page">
	<h2><?php esc_html_e( 'Scheduled Messages', 'flexi-whatsapp-automation' ); ?>
		<button type="button" class="button button-primary" id="fwa-add-schedule-btn"><?php esc_html_e( 'New Schedule', 'flexi-whatsapp-automation' ); ?></button>
	</h2>

	<div id="fwa-schedule-form" style="display:none;" class="fwa-form-card">
		<h3><?php esc_html_e( 'Create Schedule', 'flexi-whatsapp-automation' ); ?></h3>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Instance', 'flexi-whatsapp-automation' ); ?></th><td><select id="fwa-sched-instance" class="fwa-instance-select"></select></td></tr>
			<tr><th><?php esc_html_e( 'Name', 'flexi-whatsapp-automation' ); ?></th><td><input type="text" id="fwa-sched-name" class="regular-text"></td></tr>
			<tr><th><?php esc_html_e( 'Recipient', 'flexi-whatsapp-automation' ); ?></th><td><input type="text" id="fwa-sched-recipient" class="regular-text" placeholder="+1234567890"></td></tr>
			<tr><th><?php esc_html_e( 'Type', 'flexi-whatsapp-automation' ); ?></th><td><select id="fwa-sched-type">
				<option value="one_time"><?php esc_html_e( 'One Time', 'flexi-whatsapp-automation' ); ?></option>
				<option value="recurring"><?php esc_html_e( 'Recurring', 'flexi-whatsapp-automation' ); ?></option>
			</select></td></tr>
			<tr><th><?php esc_html_e( 'Message Type', 'flexi-whatsapp-automation' ); ?></th><td><select id="fwa-sched-msg-type">
				<option value="text"><?php esc_html_e( 'Text', 'flexi-whatsapp-automation' ); ?></option>
				<option value="image"><?php esc_html_e( 'Image', 'flexi-whatsapp-automation' ); ?></option>
				<option value="video"><?php esc_html_e( 'Video', 'flexi-whatsapp-automation' ); ?></option>
				<option value="audio"><?php esc_html_e( 'Audio', 'flexi-whatsapp-automation' ); ?></option>
				<option value="document"><?php esc_html_e( 'Document', 'flexi-whatsapp-automation' ); ?></option>
			</select></td></tr>
			<tr><th><?php esc_html_e( 'Content', 'flexi-whatsapp-automation' ); ?></th><td><textarea id="fwa-sched-content" class="large-text" rows="4"></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Media URL', 'flexi-whatsapp-automation' ); ?></th><td><input type="url" id="fwa-sched-media-url" class="regular-text"></td></tr>
			<tr class="fwa-recurring-field"><th><?php esc_html_e( 'Recurrence', 'flexi-whatsapp-automation' ); ?></th><td><select id="fwa-sched-recurrence">
				<option value="hourly"><?php esc_html_e( 'Hourly', 'flexi-whatsapp-automation' ); ?></option>
				<option value="daily"><?php esc_html_e( 'Daily', 'flexi-whatsapp-automation' ); ?></option>
				<option value="weekly"><?php esc_html_e( 'Weekly', 'flexi-whatsapp-automation' ); ?></option>
				<option value="monthly"><?php esc_html_e( 'Monthly', 'flexi-whatsapp-automation' ); ?></option>
			</select></td></tr>
			<tr><th><?php esc_html_e( 'Next Run At', 'flexi-whatsapp-automation' ); ?></th><td><input type="datetime-local" id="fwa-sched-next-run"></td></tr>
			<tr class="fwa-recurring-field"><th><?php esc_html_e( 'Max Runs', 'flexi-whatsapp-automation' ); ?></th><td><input type="number" id="fwa-sched-max-runs" value="0" min="0"> <span class="description"><?php esc_html_e( '0 = unlimited', 'flexi-whatsapp-automation' ); ?></span></td></tr>
		</table>
		<button type="button" class="button button-primary" id="fwa-save-schedule"><?php esc_html_e( 'Create Schedule', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button" id="fwa-cancel-schedule"><?php esc_html_e( 'Cancel', 'flexi-whatsapp-automation' ); ?></button>
	</div>

	<table class="widefat striped" id="fwa-schedules-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Recipient', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Type', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Status', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Next Run', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Last Run', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Runs', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'flexi-whatsapp-automation' ); ?></th>
			</tr>
		</thead>
		<tbody id="fwa-schedules-body">
			<tr><td colspan="8"><?php esc_html_e( 'Loading...', 'flexi-whatsapp-automation' ); ?></td></tr>
		</tbody>
	</table>
	<div class="fwa-pagination" id="fwa-schedules-pagination"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($){
	$('#fwa-sched-type').on('change', function(){
		$('.fwa-recurring-field').toggle($(this).val() === 'recurring');
	}).trigger('change');

	$('#fwa-add-schedule-btn').on('click', function(){ $('#fwa-schedule-form').slideDown(); });
	$('#fwa-cancel-schedule').on('click', function(){ $('#fwa-schedule-form').slideUp(); });

	function loadSchedules() {
		$.post(fwa_admin.ajax_url, { action: 'fwa_get_schedules', nonce: fwa_admin.nonce }, function(r){
			if (r.success && r.data.schedules) {
				var html = '';
				$.each(r.data.schedules, function(i, s) {
					html += '<tr>' +
						'<td>' + $('<span>').text(s.name || '#' + s.id).html() + '</td>' +
						'<td>' + $('<span>').text(s.recipient).html() + '</td>' +
						'<td>' + $('<span>').text(s.type).html() + '</td>' +
						'<td><span class="fwa-badge fwa-badge-' + s.status + '">' + $('<span>').text(s.status).html() + '</span></td>' +
						'<td>' + $('<span>').text(s.next_run_at || '-').html() + '</td>' +
						'<td>' + $('<span>').text(s.last_run_at || '-').html() + '</td>' +
						'<td>' + parseInt(s.run_count) + (parseInt(s.max_runs) ? '/' + parseInt(s.max_runs) : '') + '</td>' +
						'<td>';
					if (s.status === 'active') html += '<button class="button button-small fwa-pause-sched" data-id="' + parseInt(s.id) + '"><?php echo esc_js( __( 'Pause', 'flexi-whatsapp-automation' ) ); ?></button> ';
					if (s.status === 'paused') html += '<button class="button button-small fwa-resume-sched" data-id="' + parseInt(s.id) + '"><?php echo esc_js( __( 'Resume', 'flexi-whatsapp-automation' ) ); ?></button> ';
					html += '<button class="button button-small fwa-delete-sched" data-id="' + parseInt(s.id) + '"><?php echo esc_js( __( 'Delete', 'flexi-whatsapp-automation' ) ); ?></button></td></tr>';
				});
				$('#fwa-schedules-body').html(html || '<tr><td colspan="8"><?php echo esc_js( __( 'No schedules found.', 'flexi-whatsapp-automation' ) ); ?></td></tr>');
			}
		});
	}
	loadSchedules();

	$('#fwa-save-schedule').on('click', function(){
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_create_schedule', nonce: fwa_admin.nonce,
			instance_id: $('#fwa-sched-instance').val(),
			name: $('#fwa-sched-name').val(),
			recipient: $('#fwa-sched-recipient').val(),
			type: $('#fwa-sched-type').val(),
			message_type: $('#fwa-sched-msg-type').val(),
			content: $('#fwa-sched-content').val(),
			media_url: $('#fwa-sched-media-url').val(),
			recurrence_rule: $('#fwa-sched-recurrence').val(),
			next_run_at: $('#fwa-sched-next-run').val(),
			max_runs: $('#fwa-sched-max-runs').val()
		}, function(r){
			if (r.success) { $('#fwa-schedule-form').slideUp(); loadSchedules(); }
			else { alert(r.data ? r.data.message : 'Error'); }
		});
	});

	$(document).on('click', '.fwa-pause-sched', function(){
		$.post(fwa_admin.ajax_url, { action: 'fwa_pause_schedule', nonce: fwa_admin.nonce, id: $(this).data('id') }, function(){ loadSchedules(); });
	});
	$(document).on('click', '.fwa-resume-sched', function(){
		$.post(fwa_admin.ajax_url, { action: 'fwa_resume_schedule', nonce: fwa_admin.nonce, id: $(this).data('id') }, function(){ loadSchedules(); });
	});
	$(document).on('click', '.fwa-delete-sched', function(){
		if (confirm(fwa_admin.strings.confirm_delete)) {
			$.post(fwa_admin.ajax_url, { action: 'fwa_delete_schedule', nonce: fwa_admin.nonce, id: $(this).data('id') }, function(){ loadSchedules(); });
		}
	});
});
</script>
