<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fwa-campaigns">
    <h1>
        <?php echo esc_html__( 'Campaigns', 'flexi-whatsapp-automation' ); ?>
        <button type="button" class="page-title-action" id="fwa-new-campaign-btn"><?php echo esc_html__( 'New Campaign', 'flexi-whatsapp-automation' ); ?></button>
    </h1>

    <!-- Campaign Form -->
    <div id="fwa-campaign-form-wrap" class="postbox" style="display:none;padding:20px;margin:20px 0;">
        <h2 id="fwa-campaign-form-title"><?php echo esc_html__( 'New Campaign', 'flexi-whatsapp-automation' ); ?></h2>
        <form id="fwa-campaign-form">
            <input type="hidden" name="campaign_id" id="fwa-camp-id" value="" />
            <table class="form-table">
                <tr>
                    <th><label for="fwa-camp-name"><?php echo esc_html__( 'Name', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><input type="text" id="fwa-camp-name" name="name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="fwa-camp-instance"><?php echo esc_html__( 'Instance', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><select id="fwa-camp-instance" name="instance_id" class="fwa-instance-dropdown"></select></td>
                </tr>
                <tr>
                    <th><label for="fwa-camp-type"><?php echo esc_html__( 'Message Type', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td>
                        <select id="fwa-camp-type" name="message_type">
                            <option value="text"><?php echo esc_html__( 'Text', 'flexi-whatsapp-automation' ); ?></option>
                            <option value="image"><?php echo esc_html__( 'Image', 'flexi-whatsapp-automation' ); ?></option>
                            <option value="document"><?php echo esc_html__( 'Document', 'flexi-whatsapp-automation' ); ?></option>
                            <option value="video"><?php echo esc_html__( 'Video', 'flexi-whatsapp-automation' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="fwa-camp-content"><?php echo esc_html__( 'Content', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><textarea id="fwa-camp-content" name="content" rows="5" class="large-text" required></textarea></td>
                </tr>
                <tr>
                    <th><label for="fwa-camp-media"><?php echo esc_html__( 'Media URL', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><input type="text" id="fwa-camp-media" name="media_url" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="fwa-camp-recipients"><?php echo esc_html__( 'Recipients Filter', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td>
                        <select id="fwa-camp-recipients" name="recipients_filter">
                            <option value="all"><?php echo esc_html__( 'All Contacts', 'flexi-whatsapp-automation' ); ?></option>
                            <option value="tags"><?php echo esc_html__( 'By Tags', 'flexi-whatsapp-automation' ); ?></option>
                            <option value="specific"><?php echo esc_html__( 'Specific Numbers', 'flexi-whatsapp-automation' ); ?></option>
                        </select>
                        <input type="text" name="recipients_value" class="regular-text" placeholder="<?php echo esc_attr__( 'Tags or phone numbers (comma-separated)', 'flexi-whatsapp-automation' ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="fwa-camp-schedule"><?php echo esc_html__( 'Schedule', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td>
                        <select id="fwa-camp-schedule-type" name="schedule_type">
                            <option value="now"><?php echo esc_html__( 'Send Now', 'flexi-whatsapp-automation' ); ?></option>
                            <option value="scheduled"><?php echo esc_html__( 'Schedule', 'flexi-whatsapp-automation' ); ?></option>
                        </select>
                        <input type="datetime-local" id="fwa-camp-schedule" name="scheduled_at" style="display:none;" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" name="campaign_action" value="draft" class="button"><?php echo esc_html__( 'Save Draft', 'flexi-whatsapp-automation' ); ?></button>
                <button type="submit" name="campaign_action" value="start" class="button button-primary"><?php echo esc_html__( 'Start Campaign', 'flexi-whatsapp-automation' ); ?></button>
                <button type="button" class="button" id="fwa-cancel-campaign"><?php echo esc_html__( 'Cancel', 'flexi-whatsapp-automation' ); ?></button>
            </p>
        </form>
    </div>

    <!-- Campaign Analytics Modal -->
    <div id="fwa-campaign-stats-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:100000;">
        <div style="background:#fff;max-width:580px;margin:80px auto;padding:24px;border-radius:6px;max-height:80vh;overflow:auto;">
            <h2 id="fwa-stats-title"></h2>
            <div id="fwa-stats-content"></div>
            <p><button type="button" class="button" id="fwa-stats-close"><?php echo esc_html__( 'Close', 'flexi-whatsapp-automation' ); ?></button></p>
        </div>
    </div>

    <!-- Campaigns Table -->
    <table class="widefat striped" id="fwa-campaigns-table" style="margin-top:20px;">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Name', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Instance', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Type', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Recipients', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Sent / Delivered / Read / Failed', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Status', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Scheduled At', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Actions', 'flexi-whatsapp-automation' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="8"><?php echo esc_html__( 'Loading…', 'flexi-whatsapp-automation' ); ?></td></tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    function esc(str) { return $('<span>').text(str || '').html(); }

    var statusColors = { draft: '#999', running: '#0073aa', paused: '#ffb900', completed: '#46b450', failed: '#dc3232' };

    function loadInstances() {
        $.post(fwa_admin.ajax_url, { action: 'fwa_get_instances', _ajax_nonce: fwa_admin.nonce }, function(r) {
            if (r.success && r.data) {
                var opts = '';
                $.each(r.data, function(i, inst) { opts += '<option value="' + esc(inst.id) + '">' + esc(inst.name) + '</option>'; });
                $('.fwa-instance-dropdown').html(opts);
            }
        });
    }

    function loadCampaigns() {
        $.post(fwa_admin.ajax_url, { action: 'fwa_get_campaigns', _ajax_nonce: fwa_admin.nonce }, function(r) {
            var tbody = $('#fwa-campaigns-table tbody');
            tbody.empty();
            if (r.success && r.data && r.data.length) {
                $.each(r.data, function(i, c) {
                    var color = statusColors[c.status] || '#999';
                    var stats = (c.sent || 0) + ' / ' + (c.delivered || 0) + ' / ' + (c.read || 0) + ' / ' + (c.failed || 0);
                    var actions = '';
                    if (c.status === 'draft') actions += '<button class="button button-small fwa-camp-start" data-id="' + esc(c.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Start', 'flexi-whatsapp-automation' ) ); ?> + '</button> ';
                    if (c.status === 'paused') {
                        actions += '<button class="button button-small fwa-camp-start"  data-id="' + esc(c.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Start', 'flexi-whatsapp-automation' ) ); ?> + '</button> ';
                        actions += '<button class="button button-small fwa-camp-resume" data-id="' + esc(c.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Resume', 'flexi-whatsapp-automation' ) ); ?> + '</button> ';
                    }
                    if (c.status === 'running') actions += '<button class="button button-small fwa-camp-pause" data-id="' + esc(c.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Pause', 'flexi-whatsapp-automation' ) ); ?> + '</button> ';
                    actions += '<button class="button button-small fwa-camp-stats" data-id="' + esc(c.id) + '" data-name="' + esc(c.name) + '">' + <?php echo wp_json_encode( esc_html__( 'Stats', 'flexi-whatsapp-automation' ) ); ?> + '</button> ';
                    actions += '<button class="button button-small button-link-delete fwa-camp-delete" data-id="' + esc(c.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Delete', 'flexi-whatsapp-automation' ) ); ?> + '</button>';
                    tbody.append(
                        '<tr>' +
                        '<td>' + esc(c.name) + '</td>' +
                        '<td>' + esc(c.instance_name || c.instance_id) + '</td>' +
                        '<td>' + esc(c.message_type) + '</td>' +
                        '<td>' + esc(c.recipients_count || '—') + '</td>' +
                        '<td>' + stats + '</td>' +
                        '<td><span style="background:' + color + ';color:#fff;padding:3px 10px;border-radius:3px;font-size:12px;">' + esc(c.status) + '</span></td>' +
                        '<td>' + esc(c.scheduled_at || '—') + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                tbody.append('<tr><td colspan="8">' + <?php echo wp_json_encode( esc_html__( 'No campaigns found.', 'flexi-whatsapp-automation' ) ); ?> + '</td></tr>');
            }
        });
    }

    loadInstances();
    loadCampaigns();

    $('#fwa-camp-schedule-type').on('change', function() {
        $('#fwa-camp-schedule').toggle($(this).val() === 'scheduled');
    });

    $('#fwa-new-campaign-btn').on('click', function() {
        $('#fwa-campaign-form')[0].reset();
        $('#fwa-camp-id').val('');
        $('#fwa-campaign-form-wrap').slideDown();
    });
    $('#fwa-cancel-campaign').on('click', function() { $('#fwa-campaign-form-wrap').slideUp(); });

    $('#fwa-campaign-form').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serializeArray();
        var actionBtn = $(document.activeElement).val() || 'draft';
        data.push({ name: 'action', value: 'fwa_save_campaign' });
        data.push({ name: '_ajax_nonce', value: fwa_admin.nonce });
        data.push({ name: 'campaign_action', value: actionBtn });
        $.post(fwa_admin.ajax_url, $.param(data), function(r) {
            if (r.success) { $('#fwa-campaign-form-wrap').slideUp(); loadCampaigns(); }
            else { alert(r.data || <?php echo wp_json_encode( esc_html__( 'Error saving campaign.', 'flexi-whatsapp-automation' ) ); ?>); }
        });
    });

    $(document).on('click', '.fwa-camp-start', function() {
        $.post(fwa_admin.ajax_url, { action: 'fwa_start_campaign', _ajax_nonce: fwa_admin.nonce, id: $(this).data('id') }, function() { loadCampaigns(); });
    });
    $(document).on('click', '.fwa-camp-resume', function() {
        $.post(fwa_admin.ajax_url, { action: 'fwa_resume_campaign', _ajax_nonce: fwa_admin.nonce, id: $(this).data('id') }, function() { loadCampaigns(); });
    });
    $(document).on('click', '.fwa-camp-pause', function() {
        $.post(fwa_admin.ajax_url, { action: 'fwa_pause_campaign', _ajax_nonce: fwa_admin.nonce, id: $(this).data('id') }, function() { loadCampaigns(); });
    });
    $(document).on('click', '.fwa-camp-delete', function() {
        if (!confirm(<?php echo wp_json_encode( esc_html__( 'Delete this campaign?', 'flexi-whatsapp-automation' ) ); ?>)) return;
        $.post(fwa_admin.ajax_url, { action: 'fwa_delete_campaign', _ajax_nonce: fwa_admin.nonce, id: $(this).data('id') }, function() { loadCampaigns(); });
    });

    // Campaign analytics
    $(document).on('click', '.fwa-camp-stats', function() {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        $('#fwa-stats-title').text(name + ' — <?php echo esc_js( __( 'Analytics', 'flexi-whatsapp-automation' ) ); ?>');
        $('#fwa-stats-content').html('<p><?php echo esc_js( __( 'Loading…', 'flexi-whatsapp-automation' ) ); ?></p>');
        $('#fwa-campaign-stats-modal').show();
        $.post(fwa_admin.ajax_url, { action: 'fwa_get_campaign_stats', _ajax_nonce: fwa_admin.nonce, id: id }, function(r) {
            if (!r.success) {
                $('#fwa-stats-content').html('<p style="color:#dc3232;">' + (r.data && r.data.message ? esc(r.data.message) : '<?php echo esc_js( __( 'Error loading stats.', 'flexi-whatsapp-automation' ) ); ?>') + '</p>');
                return;
            }
            var c  = r.data.campaign;
            var bs = r.data.by_status;
            var total = parseInt(c.total_recipients) || 0;
            function pct(n) { return total > 0 ? ((parseInt(n) / total) * 100).toFixed(1) + '%' : '—'; }
            var html =
                '<table class="widefat striped" style="margin-bottom:15px;">' +
                '<tr><th><?php echo esc_js( __( 'Status', 'flexi-whatsapp-automation' ) ); ?></th><td><strong>' + esc(c.status) + '</strong></td></tr>' +
                '<tr><th><?php echo esc_js( __( 'Total Recipients', 'flexi-whatsapp-automation' ) ); ?></th><td>' + esc(total) + '</td></tr>' +
                '<tr><th><?php echo esc_js( __( 'Sent', 'flexi-whatsapp-automation' ) ); ?></th><td>' + esc(bs.sent) + ' (' + pct(bs.sent) + ')</td></tr>' +
                '<tr><th><?php echo esc_js( __( 'Delivered', 'flexi-whatsapp-automation' ) ); ?></th><td>' + esc(bs.delivered) + ' (' + pct(bs.delivered) + ')</td></tr>' +
                '<tr><th><?php echo esc_js( __( 'Read', 'flexi-whatsapp-automation' ) ); ?></th><td>' + esc(bs.read) + ' (' + pct(bs.read) + ')</td></tr>' +
                '<tr><th><?php echo esc_js( __( 'Failed', 'flexi-whatsapp-automation' ) ); ?></th><td>' + esc(bs.failed) + ' (' + pct(bs.failed) + ')</td></tr>' +
                '<tr><th><?php echo esc_js( __( 'Pending', 'flexi-whatsapp-automation' ) ); ?></th><td>' + esc(bs.pending) + '</td></tr>' +
                '</table>';
            // Simple visual bar.
            if (total > 0) {
                html += '<div style="background:#eee;border-radius:4px;overflow:hidden;height:20px;margin-bottom:10px;">';
                var bars = [ {n:bs.sent,c:'#0073aa'}, {n:bs.delivered,c:'#00a32a'}, {n:bs.read,c:'#46b450'}, {n:bs.failed,c:'#dc3232'} ];
                $.each(bars, function(i,b) {
                    var w = (parseInt(b.n) / total * 100).toFixed(2);
                    if (w > 0) html += '<div style="display:inline-block;width:' + w + '%;background:' + b.c + ';height:20px;"></div>';
                });
                html += '</div>';
            }
            $('#fwa-stats-content').html(html);
        });
    });

    $('#fwa-stats-close').on('click', function() { $('#fwa-campaign-stats-modal').hide(); });
    $('#fwa-campaign-stats-modal').on('click', function(e) { if ($(e.target).is('#fwa-campaign-stats-modal')) { $(this).hide(); } });
});
</script>
