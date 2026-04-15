<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fwa-logs-page">
	<h2><?php esc_html_e( 'Logs', 'flexi-whatsapp-automation' ); ?>
		<button type="button" class="button" id="fwa-export-logs-btn"><?php esc_html_e( 'Export CSV', 'flexi-whatsapp-automation' ); ?></button>
		<button type="button" class="button" id="fwa-clear-logs-btn" style="color:#a00;"><?php esc_html_e( 'Clear Logs', 'flexi-whatsapp-automation' ); ?></button>
		<label style="margin-left:15px;"><input type="checkbox" id="fwa-auto-refresh"> <?php esc_html_e( 'Auto-refresh (10s)', 'flexi-whatsapp-automation' ); ?></label>
	</h2>

	<div class="fwa-filters" style="margin-bottom:15px;">
		<select id="fwa-log-level">
			<option value=""><?php esc_html_e( 'All Levels', 'flexi-whatsapp-automation' ); ?></option>
			<option value="debug"><?php esc_html_e( 'Debug', 'flexi-whatsapp-automation' ); ?></option>
			<option value="info"><?php esc_html_e( 'Info', 'flexi-whatsapp-automation' ); ?></option>
			<option value="warning"><?php esc_html_e( 'Warning', 'flexi-whatsapp-automation' ); ?></option>
			<option value="error"><?php esc_html_e( 'Error', 'flexi-whatsapp-automation' ); ?></option>
			<option value="critical"><?php esc_html_e( 'Critical', 'flexi-whatsapp-automation' ); ?></option>
		</select>
		<select id="fwa-log-source">
			<option value=""><?php esc_html_e( 'All Sources', 'flexi-whatsapp-automation' ); ?></option>
		</select>
		<input type="date" id="fwa-log-date-from" placeholder="<?php esc_attr_e( 'From', 'flexi-whatsapp-automation' ); ?>">
		<input type="date" id="fwa-log-date-to" placeholder="<?php esc_attr_e( 'To', 'flexi-whatsapp-automation' ); ?>">
		<input type="text" id="fwa-log-search" placeholder="<?php esc_attr_e( 'Search messages...', 'flexi-whatsapp-automation' ); ?>" class="regular-text">
		<button type="button" class="button" id="fwa-filter-logs-btn"><?php esc_html_e( 'Filter', 'flexi-whatsapp-automation' ); ?></button>
	</div>

	<table class="widefat striped" id="fwa-logs-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date/Time', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Level', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Source', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Message', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'Instance', 'flexi-whatsapp-automation' ); ?></th>
				<th><?php esc_html_e( 'IP', 'flexi-whatsapp-automation' ); ?></th>
			</tr>
		</thead>
		<tbody id="fwa-logs-body">
			<tr><td colspan="6"><?php esc_html_e( 'Loading...', 'flexi-whatsapp-automation' ); ?></td></tr>
		</tbody>
	</table>
	<div class="fwa-pagination" id="fwa-logs-pagination"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($){
	var page = 1, refreshTimer = null;

	function loadLogs(){
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_get_logs', nonce: fwa_admin.nonce, page: page,
			level: $('#fwa-log-level').val(), source: $('#fwa-log-source').val(),
			date_from: $('#fwa-log-date-from').val(), date_to: $('#fwa-log-date-to').val(),
			search: $('#fwa-log-search').val()
		}, function(r){
			if (r.success && r.data.logs) {
				var html = '';
				var levelColors = {debug:'#999',info:'#0073aa',warning:'#ffb900',error:'#dc3232',critical:'#8b0000'};
				$.each(r.data.logs, function(i, l){
					var msg = $('<span>').text(l.message).html();
					if (msg.length > 120) msg = msg.substring(0, 120) + '&hellip;';
					html += '<tr>' +
						'<td>' + $('<span>').text(l.created_at).html() + '</td>' +
						'<td><span class="fwa-badge" style="background:' + (levelColors[l.level]||'#999') + ';color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">' + $('<span>').text(l.level).html() + '</span></td>' +
						'<td>' + $('<span>').text(l.source||'-').html() + '</td>' +
						'<td title="' + $('<span>').text(l.message).html() + '">' + msg + '</td>' +
						'<td>' + $('<span>').text(l.instance_id||'-').html() + '</td>' +
						'<td>' + $('<span>').text(l.ip_address||'-').html() + '</td></tr>';
				});
				$('#fwa-logs-body').html(html || '<tr><td colspan="6"><?php echo esc_js( __( 'No logs found.', 'flexi-whatsapp-automation' ) ); ?></td></tr>');

				if (r.data.sources) {
					var cur = $('#fwa-log-source').val();
					$('#fwa-log-source').find('option:gt(0)').remove();
					$.each(r.data.sources, function(i, s){
						$('#fwa-log-source').append('<option value="' + $('<span>').text(s).html() + '">' + $('<span>').text(s).html() + '</option>');
					});
					if (cur) $('#fwa-log-source').val(cur);
				}
			}
		});
	}
	loadLogs();

	$('#fwa-filter-logs-btn').on('click', function(){ page = 1; loadLogs(); });

	$('#fwa-auto-refresh').on('change', function(){
		if ($(this).is(':checked')) { refreshTimer = setInterval(loadLogs, 10000); }
		else { clearInterval(refreshTimer); }
	});

	$('#fwa-clear-logs-btn').on('click', function(){
		if (confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'flexi-whatsapp-automation' ) ); ?>')) {
			$.post(fwa_admin.ajax_url, { action: 'fwa_clear_logs', nonce: fwa_admin.nonce }, function(){ loadLogs(); });
		}
	});

	$('#fwa-export-logs-btn').on('click', function(){
		$.post(fwa_admin.ajax_url, {
			action: 'fwa_export_logs', nonce: fwa_admin.nonce,
			level: $('#fwa-log-level').val(), source: $('#fwa-log-source').val(),
			date_from: $('#fwa-log-date-from').val(), date_to: $('#fwa-log-date-to').val()
		}, function(r){
			if (r.success && r.data.csv) {
				var blob = new Blob([r.data.csv], {type:'text/csv'});
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = 'fwa-logs.csv';
				a.click();
			}
		});
	});
});
</script>
