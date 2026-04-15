<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fwa-dashboard">
    <h1><?php echo esc_html__( 'Dashboard', 'flexi-whatsapp-automation' ); ?></h1>

    <!-- Stats Cards -->
    <div class="fwa-stats-row" style="display:flex;gap:20px;margin:20px 0;flex-wrap:wrap;">
        <div class="fwa-stat-card" style="flex:1;min-width:200px;background:#fff;border-left:4px solid #46b450;padding:20px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <span class="dashicons dashicons-networking" style="color:#46b450;font-size:36px;float:right;"></span>
            <div class="fwa-stat-number" id="fwa-stat-instances" style="font-size:28px;font-weight:700;">—</div>
            <div class="fwa-stat-label" style="color:#999;"><?php echo esc_html__( 'Connected Instances', 'flexi-whatsapp-automation' ); ?></div>
        </div>
        <div class="fwa-stat-card" style="flex:1;min-width:200px;background:#fff;border-left:4px solid #0073aa;padding:20px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <span class="dashicons dashicons-email" style="color:#0073aa;font-size:36px;float:right;"></span>
            <div class="fwa-stat-number" id="fwa-stat-messages" style="font-size:28px;font-weight:700;">—</div>
            <div class="fwa-stat-label" style="color:#999;"><?php echo esc_html__( 'Messages Today', 'flexi-whatsapp-automation' ); ?></div>
        </div>
        <div class="fwa-stat-card" style="flex:1;min-width:200px;background:#fff;border-left:4px solid #ffb900;padding:20px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <span class="dashicons dashicons-megaphone" style="color:#ffb900;font-size:36px;float:right;"></span>
            <div class="fwa-stat-number" id="fwa-stat-campaigns" style="font-size:28px;font-weight:700;">—</div>
            <div class="fwa-stat-label" style="color:#999;"><?php echo esc_html__( 'Active Campaigns', 'flexi-whatsapp-automation' ); ?></div>
        </div>
        <div class="fwa-stat-card" style="flex:1;min-width:200px;background:#fff;border-left:4px solid #826eb4;padding:20px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <span class="dashicons dashicons-groups" style="color:#826eb4;font-size:36px;float:right;"></span>
            <div class="fwa-stat-number" id="fwa-stat-contacts" style="font-size:28px;font-weight:700;">—</div>
            <div class="fwa-stat-label" style="color:#999;"><?php echo esc_html__( 'Total Contacts', 'flexi-whatsapp-automation' ); ?></div>
        </div>
    </div>

    <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <!-- Recent Messages -->
        <div style="flex:2;min-width:400px;">
            <div class="postbox">
                <h2 class="hndle"><span><?php echo esc_html__( 'Recent Messages', 'flexi-whatsapp-automation' ); ?></span></h2>
                <div class="inside">
                    <table class="widefat striped" id="fwa-recent-messages">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__( 'Date', 'flexi-whatsapp-automation' ); ?></th>
                                <th><?php echo esc_html__( 'Direction', 'flexi-whatsapp-automation' ); ?></th>
                                <th><?php echo esc_html__( 'Phone', 'flexi-whatsapp-automation' ); ?></th>
                                <th><?php echo esc_html__( 'Type', 'flexi-whatsapp-automation' ); ?></th>
                                <th><?php echo esc_html__( 'Content', 'flexi-whatsapp-automation' ); ?></th>
                                <th><?php echo esc_html__( 'Status', 'flexi-whatsapp-automation' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6"><?php echo esc_html__( 'Loading…', 'flexi-whatsapp-automation' ); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Instance Status -->
        <div style="flex:1;min-width:280px;">
            <div class="postbox">
                <h2 class="hndle"><span><?php echo esc_html__( 'Instance Status', 'flexi-whatsapp-automation' ); ?></span></h2>
                <div class="inside">
                    <ul id="fwa-instance-status-list" style="margin:0;">
                        <li><?php echo esc_html__( 'Loading…', 'flexi-whatsapp-automation' ); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Load dashboard stats
    $.post(fwa_admin.ajax_url, {
        action: 'fwa_get_dashboard_stats',
        _ajax_nonce: fwa_admin.nonce
    }, function(response) {
        if (response.success && response.data) {
            $('#fwa-stat-instances').text(response.data.connected_instances || 0);
            $('#fwa-stat-messages').text(response.data.messages_today || 0);
            $('#fwa-stat-campaigns').text(response.data.active_campaigns || 0);
            $('#fwa-stat-contacts').text(response.data.total_contacts || 0);
        }
    });

    // Load recent messages
    $.post(fwa_admin.ajax_url, {
        action: 'fwa_get_messages',
        _ajax_nonce: fwa_admin.nonce,
        per_page: 10,
        page: 1
    }, function(response) {
        var tbody = $('#fwa-recent-messages tbody');
        tbody.empty();
        if (response.success && response.data && response.data.messages && response.data.messages.length) {
            $.each(response.data.messages, function(i, msg) {
                var dirIcon = msg.direction === 'outgoing' ? '&#x2191;' : '&#x2193;';
                var content = msg.content ? msg.content.substring(0, 50) : '';
                tbody.append(
                    '<tr>' +
                    '<td>' + $('<span>').text(msg.created_at || '').html() + '</td>' +
                    '<td>' + dirIcon + ' ' + $('<span>').text(msg.direction || '').html() + '</td>' +
                    '<td>' + $('<span>').text(msg.phone || '').html() + '</td>' +
                    '<td>' + $('<span>').text(msg.message_type || '').html() + '</td>' +
                    '<td>' + $('<span>').text(content).html() + '</td>' +
                    '<td><span class="fwa-badge fwa-badge-' + $('<span>').text(msg.status || '').html() + '">' + $('<span>').text(msg.status || '').html() + '</span></td>' +
                    '</tr>'
                );
            });
        } else {
            tbody.append('<tr><td colspan="6">' + <?php echo wp_json_encode( esc_html__( 'No recent messages.', 'flexi-whatsapp-automation' ) ); ?> + '</td></tr>');
        }
    });

    // Load instance status
    $.post(fwa_admin.ajax_url, {
        action: 'fwa_get_dashboard_stats',
        _ajax_nonce: fwa_admin.nonce
    }, function(response) {
        var list = $('#fwa-instance-status-list');
        list.empty();
        if (response.success && response.data && response.data.instances && response.data.instances.length) {
            $.each(response.data.instances, function(i, inst) {
                var dotColor = inst.status === 'connected' ? '#46b450' : (inst.status === 'disconnected' ? '#dc3232' : '#ffb900');
                list.append(
                    '<li style="padding:8px 0;border-bottom:1px solid #eee;">' +
                    '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + dotColor + ';margin-right:8px;"></span>' +
                    '<strong>' + $('<span>').text(inst.name || '').html() + '</strong> — ' +
                    '<span>' + $('<span>').text(inst.status || '').html() + '</span>' +
                    '</li>'
                );
            });
        } else {
            list.append('<li>' + <?php echo wp_json_encode( esc_html__( 'No instances configured.', 'flexi-whatsapp-automation' ) ); ?> + '</li>');
        }
    });
});
</script>
