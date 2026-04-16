<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fwa-messages">
    <h1><?php echo esc_html__( 'Messages', 'flexi-whatsapp-automation' ); ?></h1>

    <!-- Filters -->
    <div class="postbox" style="padding:15px;margin:20px 0;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <div>
                <label for="fwa-filter-instance"><?php echo esc_html__( 'Instance', 'flexi-whatsapp-automation' ); ?></label><br />
                <select id="fwa-filter-instance" class="fwa-instance-dropdown"><option value=""><?php echo esc_html__( 'All', 'flexi-whatsapp-automation' ); ?></option></select>
            </div>
            <div>
                <label for="fwa-filter-direction"><?php echo esc_html__( 'Direction', 'flexi-whatsapp-automation' ); ?></label><br />
                <select id="fwa-filter-direction">
                    <option value=""><?php echo esc_html__( 'All', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="incoming"><?php echo esc_html__( 'Incoming', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="outgoing"><?php echo esc_html__( 'Outgoing', 'flexi-whatsapp-automation' ); ?></option>
                </select>
            </div>
            <div>
                <label for="fwa-filter-status"><?php echo esc_html__( 'Status', 'flexi-whatsapp-automation' ); ?></label><br />
                <select id="fwa-filter-status">
                    <option value=""><?php echo esc_html__( 'All', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="sent"><?php echo esc_html__( 'Sent', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="delivered"><?php echo esc_html__( 'Delivered', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="read"><?php echo esc_html__( 'Read', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="failed"><?php echo esc_html__( 'Failed', 'flexi-whatsapp-automation' ); ?></option>
                </select>
            </div>
            <div>
                <label for="fwa-filter-from"><?php echo esc_html__( 'From', 'flexi-whatsapp-automation' ); ?></label><br />
                <input type="date" id="fwa-filter-from" />
            </div>
            <div>
                <label for="fwa-filter-to"><?php echo esc_html__( 'To', 'flexi-whatsapp-automation' ); ?></label><br />
                <input type="date" id="fwa-filter-to" />
            </div>
            <div>
                <button type="button" class="button button-primary" id="fwa-filter-messages"><?php echo esc_html__( 'Filter', 'flexi-whatsapp-automation' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Send New Message -->
    <div class="postbox" style="padding:15px;margin-bottom:20px;">
        <h2><?php echo esc_html__( 'Send New Message', 'flexi-whatsapp-automation' ); ?></h2>
        <form id="fwa-send-message-form" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <div>
                <label for="fwa-msg-phone"><?php echo esc_html__( 'Phone', 'flexi-whatsapp-automation' ); ?></label><br />
                <input type="text" id="fwa-msg-phone" name="phone" class="regular-text" required placeholder="+1234567890" />
            </div>
            <div>
                <label for="fwa-msg-type"><?php echo esc_html__( 'Message Type', 'flexi-whatsapp-automation' ); ?></label><br />
                <select id="fwa-msg-type" name="message_type">
                    <option value="text"><?php echo esc_html__( 'Text', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="image"><?php echo esc_html__( 'Image', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="document"><?php echo esc_html__( 'Document', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="video"><?php echo esc_html__( 'Video', 'flexi-whatsapp-automation' ); ?></option>
                    <option value="audio"><?php echo esc_html__( 'Audio', 'flexi-whatsapp-automation' ); ?></option>
                </select>
            </div>
            <div style="flex:1;min-width:200px;">
                <label for="fwa-msg-content"><?php echo esc_html__( 'Content', 'flexi-whatsapp-automation' ); ?></label><br />
                <textarea id="fwa-msg-content" name="content" rows="2" style="width:100%;" required></textarea>
            </div>
            <div>
                <label for="fwa-msg-media"><?php echo esc_html__( 'Media URL', 'flexi-whatsapp-automation' ); ?></label><br />
                <input type="text" id="fwa-msg-media" name="media_url" class="regular-text" />
            </div>
            <div>
                <label for="fwa-msg-instance"><?php echo esc_html__( 'Instance', 'flexi-whatsapp-automation' ); ?></label><br />
                <select id="fwa-msg-instance" name="instance_id" class="fwa-instance-dropdown"></select>
            </div>
            <div>
                <button type="submit" class="button button-primary"><?php echo esc_html__( 'Send', 'flexi-whatsapp-automation' ); ?></button>
            </div>
        </form>
        <div id="fwa-send-result" style="margin-top:10px;"></div>
    </div>

    <!-- Messages Table -->
    <table class="widefat striped" id="fwa-messages-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Date', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Direction', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Recipient / Sender', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Type', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Content', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Status', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Actions', 'flexi-whatsapp-automation' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="7"><?php echo esc_html__( 'Loading…', 'flexi-whatsapp-automation' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div id="fwa-messages-pagination" class="tablenav bottom" style="margin-top:10px;">
        <div class="tablenav-pages">
            <span class="displaying-num" id="fwa-msg-total"></span>
            <span class="pagination-links">
                <button type="button" class="button" id="fwa-msg-prev" disabled>&laquo;</button>
                <span id="fwa-msg-page-info"></span>
                <button type="button" class="button" id="fwa-msg-next" disabled>&raquo;</button>
            </span>
        </div>
    </div>

    <!-- Message Detail Modal -->
    <div id="fwa-msg-detail-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:100000;">
        <div style="background:#fff;max-width:600px;margin:80px auto;padding:20px;border-radius:4px;max-height:80vh;overflow:auto;">
            <h2><?php echo esc_html__( 'Message Details', 'flexi-whatsapp-automation' ); ?></h2>
            <div id="fwa-msg-detail-content"></div>
            <p><button type="button" class="button" id="fwa-msg-detail-close"><?php echo esc_html__( 'Close', 'flexi-whatsapp-automation' ); ?></button></p>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentPage = 1;
    var perPage = 20;

    function esc(str) { return $('<span>').text(str || '').html(); }

    function loadInstances() {
        $.post(fwa_admin.ajax_url, { action: 'fwa_get_instances', _ajax_nonce: fwa_admin.nonce }, function(r) {
            if (r.success && r.data) {
                var opts = '';
                $.each(r.data, function(i, inst) {
                    opts += '<option value="' + esc(inst.id) + '">' + esc(inst.name) + '</option>';
                });
                $('.fwa-instance-dropdown').each(function() {
                    var first = $(this).find('option:first');
                    $(this).empty();
                    if (first.val() === '') $(this).append(first);
                    $(this).append(opts);
                });
            }
        });
    }

    function loadMessages() {
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_get_messages',
            _ajax_nonce: fwa_admin.nonce,
            page: currentPage,
            per_page: perPage,
            instance_id: $('#fwa-filter-instance').val(),
            direction: $('#fwa-filter-direction').val(),
            status: $('#fwa-filter-status').val(),
            date_from: $('#fwa-filter-from').val(),
            date_to: $('#fwa-filter-to').val()
        }, function(r) {
            var tbody = $('#fwa-messages-table tbody');
            tbody.empty();
            if (r.success && r.data && r.data.messages && r.data.messages.length) {
                $.each(r.data.messages, function(i, msg) {
                    var dirIcon = msg.direction === 'outgoing' ? '&#x2191;' : '&#x2193;';
                    var content = msg.content ? msg.content.substring(0, 60) : '';
                    var statusClass = 'fwa-badge-' + (msg.status || 'unknown');
                    var resendBtn = (msg.direction === 'outgoing' && msg.status === 'failed')
                        ? '<button type="button" class="button button-small fwa-resend-msg" data-id="' + esc(msg.id) + '" style="color:#a00;">' + <?php echo wp_json_encode( esc_html__( 'Resend', 'flexi-whatsapp-automation' ) ); ?> + '</button> '
                        : '';
                    tbody.append(
                        '<tr>' +
                        '<td>' + esc(msg.created_at) + '</td>' +
                        '<td>' + dirIcon + ' ' + esc(msg.direction) + '</td>' +
                        '<td>' + esc(msg.phone) + '</td>' +
                        '<td>' + esc(msg.message_type) + '</td>' +
                        '<td>' + esc(content) + '</td>' +
                        '<td><span class="fwa-badge ' + statusClass + '">' + esc(msg.status) + '</span></td>' +
                        '<td>' + resendBtn + '<button type="button" class="button button-small fwa-view-msg" data-id="' + esc(msg.id) + '">' + <?php echo wp_json_encode( esc_html__( 'View', 'flexi-whatsapp-automation' ) ); ?> + '</button></td>' +
                        '</tr>'
                    );
                });
                var total = r.data.total || 0;
                var pages = Math.ceil(total / perPage);
                $('#fwa-msg-total').text(total + ' ' + <?php echo wp_json_encode( esc_html__( 'items', 'flexi-whatsapp-automation' ) ); ?>);
                $('#fwa-msg-page-info').text(currentPage + ' / ' + pages);
                $('#fwa-msg-prev').prop('disabled', currentPage <= 1);
                $('#fwa-msg-next').prop('disabled', currentPage >= pages);
            } else {
                tbody.append('<tr><td colspan="7">' + <?php echo wp_json_encode( esc_html__( 'No messages found.', 'flexi-whatsapp-automation' ) ); ?> + '</td></tr>');
            }
        });
    }

    loadInstances();
    loadMessages();

    $('#fwa-filter-messages').on('click', function() { currentPage = 1; loadMessages(); });
    $('#fwa-msg-prev').on('click', function() { if (currentPage > 1) { currentPage--; loadMessages(); } });
    $('#fwa-msg-next').on('click', function() { currentPage++; loadMessages(); });

    $('#fwa-send-message-form').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({ name: 'action', value: 'fwa_send_message' });
        data.push({ name: '_ajax_nonce', value: fwa_admin.nonce });
        $.post(fwa_admin.ajax_url, $.param(data), function(r) {
            if (r.success) {
                $('#fwa-send-result').html('<div class="notice notice-success inline"><p>' + <?php echo wp_json_encode( esc_html__( 'Message sent successfully.', 'flexi-whatsapp-automation' ) ); ?> + '</p></div>');
                loadMessages();
            } else {
                $('#fwa-send-result').html('<div class="notice notice-error inline"><p>' + esc(r.data) + '</p></div>');
            }
        });
    });

    $(document).on('click', '.fwa-view-msg', function() {
        var id = $(this).data('id');
        $.post(fwa_admin.ajax_url, { action: 'fwa_get_message_detail', _ajax_nonce: fwa_admin.nonce, message_id: id }, function(r) {
            if (r.success && r.data) {
                var d = r.data;
                var html = '<table class="form-table">';
                $.each(['id','phone','direction','message_type','content','media_url','status','instance_id','created_at','updated_at'], function(i, k) {
                    if (d[k]) html += '<tr><th>' + esc(k) + '</th><td>' + esc(d[k]) + '</td></tr>';
                });
                html += '</table>';
                $('#fwa-msg-detail-content').html(html);
            }
            $('#fwa-msg-detail-modal').show();
        });
    });

    $('#fwa-msg-detail-close').on('click', function() { $('#fwa-msg-detail-modal').hide(); });

    $(document).on('click', '.fwa-resend-msg', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true).text(<?php echo wp_json_encode( esc_html__( 'Sending…', 'flexi-whatsapp-automation' ) ); ?>);
        $.post(fwa_admin.ajax_url, { action: 'fwa_resend_message', _ajax_nonce: fwa_admin.nonce, id: id }, function(r) {
            if (r.success) {
                $btn.closest('tr').find('.fwa-badge').text('sent').removeClass().addClass('fwa-badge fwa-badge-sent');
                $btn.remove();
            } else {
                $btn.prop('disabled', false).text(<?php echo wp_json_encode( esc_html__( 'Resend', 'flexi-whatsapp-automation' ) ); ?>);
                alert(r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Failed to resend.', 'flexi-whatsapp-automation' ) ); ?>');
            }
        });
    });
});
</script>
