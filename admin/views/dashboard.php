<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// WP-Cron health check: warn if the every_minute event is overdue (> 3 minutes late).
$cron_ok       = true;
$next_cron     = wp_next_scheduled( 'fwa_process_queue' );
if ( false === $next_cron || ( $next_cron + 180 ) < time() ) {
    $cron_ok = false;
}
?>
<div class="wrap fwa-dashboard">
    <h1><?php echo esc_html__( 'Dashboard', 'flexi-whatsapp-automation' ); ?></h1>

    <?php if ( ! $cron_ok ) : ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php echo esc_html__( 'WP-Cron Warning:', 'flexi-whatsapp-automation' ); ?></strong>
            <?php echo esc_html__( 'The queue-processor cron job is not firing on schedule. Scheduled messages and campaigns may be stuck. Please verify that WP-Cron is running every minute, or add a real system cron job:', 'flexi-whatsapp-automation' ); ?>
            <code>* * * * * wget -q -O- <?php echo esc_url( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?> &gt;/dev/null 2&gt;&amp;1</code>
        </p>
    </div>
    <?php endif; ?>

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

    <!-- Quick Send -->
    <div class="postbox" style="margin-bottom:20px;">
        <h2 class="hndle"><span><?php echo esc_html__( 'Quick Send', 'flexi-whatsapp-automation' ); ?></span></h2>
        <div class="inside">
            <p class="description" style="margin-bottom:10px;">
                <?php echo esc_html__( 'Send a test message to verify your WhatsApp instance is working. Enter the recipient\'s phone number in E.164 format (e.g. +911234567890).', 'flexi-whatsapp-automation' ); ?>
            </p>
            <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <label for="fwa-qs-phone" style="display:block;margin-bottom:4px;font-weight:600;"><?php echo esc_html__( 'Phone Number', 'flexi-whatsapp-automation' ); ?></label>
                    <input type="tel" id="fwa-qs-phone" class="regular-text" placeholder="+911234567890" style="min-width:220px;">
                </div>
                <div style="flex:1;min-width:280px;">
                    <label for="fwa-qs-message" style="display:block;margin-bottom:4px;font-weight:600;"><?php echo esc_html__( 'Message', 'flexi-whatsapp-automation' ); ?></label>
                    <textarea id="fwa-qs-message" class="large-text" rows="2" style="width:100%;"><?php echo esc_textarea( __( 'Hello! This is a test message from Flexi WhatsApp Automation.', 'flexi-whatsapp-automation' ) ); ?></textarea>
                </div>
                <div style="padding-top:24px;">
                    <button type="button" class="button button-primary" id="fwa-qs-send"><?php echo esc_html__( 'Send Test', 'flexi-whatsapp-automation' ); ?></button>
                </div>
            </div>
            <div id="fwa-qs-result" style="margin-top:10px;"></div>
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
        if (response.success && response.data && response.data.stats) {
            var s = response.data.stats;
            $('#fwa-stat-instances').text((s.instances && s.instances.connected) || 0);
            $('#fwa-stat-messages').text((s.messages && s.messages.today_sent) || 0);
            $('#fwa-stat-campaigns').text((s.campaigns && s.campaigns.active) || 0);
            $('#fwa-stat-contacts').text((s.contacts && s.contacts.total) || 0);
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
        var instances = (response.success && response.data && response.data.stats && response.data.stats.instances_list)
            ? response.data.stats.instances_list : [];
        if (instances.length) {
            $.each(instances, function(i, inst) {
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

    // Quick Send
    $('#fwa-qs-send').on('click', function() {
        var phone   = $('#fwa-qs-phone').val().trim();
        var message = $('#fwa-qs-message').val().trim();
        var $btn    = $(this);
        var $result = $('#fwa-qs-result');

        if (!phone) {
            $result.html('<span style="color:#dc3232;">' + <?php echo wp_json_encode( esc_html__( 'Please enter a phone number.', 'flexi-whatsapp-automation' ) ); ?> + '</span>');
            return;
        }
        if (!message) {
            $result.html('<span style="color:#dc3232;">' + <?php echo wp_json_encode( esc_html__( 'Please enter a message.', 'flexi-whatsapp-automation' ) ); ?> + '</span>');
            return;
        }

        $btn.prop('disabled', true).text(<?php echo wp_json_encode( esc_html__( 'Sending…', 'flexi-whatsapp-automation' ) ); ?>);
        $result.html('<span style="color:#555;">' + <?php echo wp_json_encode( esc_html__( 'Sending…', 'flexi-whatsapp-automation' ) ); ?> + '</span>');

        $.post(fwa_admin.ajax_url, {
            action:       'fwa_send_message',
            nonce:        fwa_admin.nonce,
            phone:        phone,
            message:      message,
            message_type: 'text',
            instance_id:  ''
        }, function(r) {
            if (r.success) {
                $result.html('<span style="color:#46b450;">&#10003; ' + <?php echo wp_json_encode( esc_html__( 'Message sent successfully.', 'flexi-whatsapp-automation' ) ); ?> + '</span>');
            } else {
                $result.html('<span style="color:#dc3232;">&#10007; ' + $('<span>').text(r.data && r.data.message ? r.data.message : <?php echo wp_json_encode( esc_html__( 'Failed to send message.', 'flexi-whatsapp-automation' ) ); ?>).html() + '</span>');
            }
        }).fail(function() {
            $result.html('<span style="color:#dc3232;">&#10007; ' + <?php echo wp_json_encode( esc_html__( 'Network error. Please try again.', 'flexi-whatsapp-automation' ) ); ?> + '</span>');
        }).always(function() {
            $btn.prop('disabled', false).text(<?php echo wp_json_encode( esc_html__( 'Send Test', 'flexi-whatsapp-automation' ) ); ?>);
        });
    });
});
</script>
