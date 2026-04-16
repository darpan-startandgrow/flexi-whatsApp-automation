<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fwa-instances">
    <h1>
        <?php echo esc_html__( 'Instances', 'flexi-whatsapp-automation' ); ?>
        <button type="button" class="page-title-action" id="fwa-add-instance-btn"><?php echo esc_html__( 'Add New Instance', 'flexi-whatsapp-automation' ); ?></button>
    </h1>

    <!-- Add / Edit Instance Form -->
    <div id="fwa-instance-form-wrap" class="postbox" style="display:none;margin-top:20px;padding:20px;">
        <h2 id="fwa-instance-form-title"><?php echo esc_html__( 'Add New Instance', 'flexi-whatsapp-automation' ); ?></h2>
        <form id="fwa-instance-form">
            <input type="hidden" name="instance_db_id" id="fwa-inst-db-id" value="" />
            <table class="form-table">
                <tr>
                    <th><label for="fwa-inst-name"><?php echo esc_html__( 'Name', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><input type="text" id="fwa-inst-name" name="name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="fwa-inst-id"><?php echo esc_html__( 'Instance ID', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><input type="text" id="fwa-inst-id" name="instance_id" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="fwa-inst-token"><?php echo esc_html__( 'Access Token', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><input type="password" id="fwa-inst-token" name="access_token" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="fwa-inst-phone"><?php echo esc_html__( 'Phone Number', 'flexi-whatsapp-automation' ); ?></label></th>
                    <td><input type="text" id="fwa-inst-phone" name="phone_number" class="regular-text" placeholder="+1234567890" /></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html__( 'Save Instance', 'flexi-whatsapp-automation' ); ?></button>
                <button type="button" class="button" id="fwa-cancel-instance"><?php echo esc_html__( 'Cancel', 'flexi-whatsapp-automation' ); ?></button>
            </p>
        </form>
    </div>

    <!-- QR Code Display -->
    <div id="fwa-qr-display" class="postbox" style="display:none;margin-top:20px;padding:20px;text-align:center;">
        <h2><?php echo esc_html__( 'Scan QR Code', 'flexi-whatsapp-automation' ); ?></h2>
        <p><?php echo esc_html__( 'Open WhatsApp on your phone and scan this QR code to connect.', 'flexi-whatsapp-automation' ); ?></p>
        <div id="fwa-qr-image" style="margin:20px auto;min-height:80px;"></div>
        <div id="fwa-qr-status" style="margin:10px 0;font-weight:600;"></div>
        <p>
            <button type="button" class="button" id="fwa-qr-refresh"><?php echo esc_html__( '↺ Refresh QR', 'flexi-whatsapp-automation' ); ?></button>
            <button type="button" class="button" id="fwa-qr-close"><?php echo esc_html__( 'Close', 'flexi-whatsapp-automation' ); ?></button>
        </p>
        <hr style="margin:15px 0;">
        <p style="font-size:13px;color:#555;"><?php echo esc_html__( 'Prefer typing on your phone? Use pairing code instead:', 'flexi-whatsapp-automation' ); ?></p>
        <div style="display:flex;justify-content:center;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="tel" id="fwa-pairing-phone" placeholder="+1234567890" class="regular-text" style="max-width:200px;" />
            <button type="button" class="button" id="fwa-get-pairing-code"><?php echo esc_html__( 'Get Pairing Code', 'flexi-whatsapp-automation' ); ?></button>
        </div>
        <div id="fwa-pairing-result" style="margin:10px 0;font-size:20px;font-weight:700;letter-spacing:6px;color:#25D366;"></div>
    </div>

    <!-- Instances Table -->
    <table class="widefat striped" id="fwa-instances-table" style="margin-top:20px;">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Name', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Instance ID', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Phone', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Status', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Active', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Last Seen', 'flexi-whatsapp-automation' ); ?></th>
                <th><?php echo esc_html__( 'Actions', 'flexi-whatsapp-automation' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="7"><?php echo esc_html__( 'Loading…', 'flexi-whatsapp-automation' ); ?></td></tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

    var statusColors = {
        connected: '#46b450',
        disconnected: '#dc3232',
        qr_required: '#ffb900',
        initializing: '#0073aa',
        banned: '#999'
    };

    var qrPollTimer    = null;
    var activeQRInstID = null;

    function esc(str) {
        return $('<span>').text(str || '').html();
    }

    function loadInstances() {
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_get_instances',
            _ajax_nonce: fwa_admin.nonce
        }, function(response) {
            var tbody = $('#fwa-instances-table tbody');
            tbody.empty();
            if (response.success && response.data && response.data.length) {
                $.each(response.data, function(i, inst) {
                    var color   = statusColors[inst.status] || '#999';
                    var checked = inst.is_active ? 'checked' : '';
                    tbody.append(
                        '<tr data-id="' + esc(inst.id) + '">' +
                        '<td>' + esc(inst.name) + '</td>' +
                        '<td><code>' + esc(inst.instance_id) + '</code></td>' +
                        '<td>' + esc(inst.phone_number || '') + '</td>' +
                        '<td><span class="fwa-status-badge" style="background:' + color + ';color:#fff;padding:3px 10px;border-radius:3px;font-size:12px;">' + esc(inst.status) + '</span></td>' +
                        '<td><input type="radio" name="fwa_active_instance" value="' + esc(inst.id) + '" ' + checked + ' class="fwa-set-active" /></td>' +
                        '<td>' + esc(inst.last_seen_at || '\u2014') + '</td>' +
                        '<td>' +
                        '<button type="button" class="button button-small fwa-connect-btn" data-id="' + esc(inst.id) + '"><?php echo esc_js( __( 'Connect / QR', 'flexi-whatsapp-automation' ) ); ?></button> ' +
                        '<button type="button" class="button button-small fwa-disconnect-btn" data-id="' + esc(inst.id) + '"><?php echo esc_js( __( 'Disconnect', 'flexi-whatsapp-automation' ) ); ?></button> ' +
                        '<button type="button" class="button button-small button-link-delete fwa-delete-btn" data-id="' + esc(inst.id) + '"><?php echo esc_js( __( 'Delete', 'flexi-whatsapp-automation' ) ); ?></button>' +
                        '</td></tr>'
                    );
                });
            } else {
                tbody.append('<tr><td colspan="7"><?php echo esc_js( __( 'No instances found.', 'flexi-whatsapp-automation' ) ); ?></td></tr>');
            }
        });
    }

    loadInstances();

    $('#fwa-add-instance-btn').on('click', function() {
        $('#fwa-instance-form')[0].reset();
        $('#fwa-inst-db-id').val('');
        $('#fwa-instance-form-title').text('<?php echo esc_js( __( 'Add New Instance', 'flexi-whatsapp-automation' ) ); ?>');
        $('#fwa-instance-form-wrap').slideDown();
    });

    $('#fwa-cancel-instance').on('click', function() {
        $('#fwa-instance-form-wrap').slideUp();
    });

    $('#fwa-instance-form').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({ name: 'action', value: 'fwa_save_instance' });
        data.push({ name: '_ajax_nonce', value: fwa_admin.nonce });
        $.post(fwa_admin.ajax_url, $.param(data), function(response) {
            if (response.success) {
                $('#fwa-instance-form-wrap').slideUp();
                loadInstances();
            } else {
                alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Error saving instance.', 'flexi-whatsapp-automation' ) ); ?>');
            }
        });
    });

    /* ----------------------------------------------------------------
     * QR Code display + live polling
     * -------------------------------------------------------------- */

    function fetchQR(dbId) {
        $('#fwa-qr-status').text('<?php echo esc_js( __( 'Fetching QR code\u2026', 'flexi-whatsapp-automation' ) ); ?>').css('color','#555');
        $('#fwa-qr-image').html('<p><?php echo esc_js( __( 'Loading\u2026', 'flexi-whatsapp-automation' ) ); ?></p>');

        $.post(fwa_admin.ajax_url, {
            action: 'fwa_get_qr_code',
            _ajax_nonce: fwa_admin.nonce,
            id: dbId
        }, function(r) {
            if (r.success && r.data && r.data.qr) {
                var src = r.data.qr;
                if (src.indexOf('data:') !== 0 && src.indexOf('http') !== 0) {
                    src = 'data:image/png;base64,' + src;
                }
                $('#fwa-qr-image').html('<img src="' + src + '" alt="QR Code" style="max-width:260px;border:4px solid #25D366;padding:4px;border-radius:4px;" />');
                $('#fwa-qr-status').text('<?php echo esc_js( __( 'Scan with WhatsApp \u2014 waiting\u2026', 'flexi-whatsapp-automation' ) ); ?>').css('color','#ffb900');
            } else {
                $('#fwa-qr-image').html('<p>' + (r.data && r.data.message ? esc(r.data.message) : '<?php echo esc_js( __( 'Could not load QR code.', 'flexi-whatsapp-automation' ) ); ?>') + '</p>');
                $('#fwa-qr-status').text('');
            }
        });
    }

    function pollInstanceStatus(dbId) {
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_get_instance_status',
            _ajax_nonce: fwa_admin.nonce,
            id: dbId
        }, function(r) {
            if (!r.success) { return; }
            var status = r.data && r.data.status ? r.data.status : '';

            if ('connected' === status) {
                stopQRPoll();
                $('#fwa-qr-status').text('<?php echo esc_js( __( '\u2713 Connected! Your WhatsApp is ready.', 'flexi-whatsapp-automation' ) ); ?>').css('color','#46b450');
                $('#fwa-qr-image').html('<span style="font-size:64px;">\u2705</span>');
                loadInstances();
                setTimeout(function(){ $('#fwa-qr-display').slideUp(); }, 3000);
            } else if ('qr_required' === status) {
                fetchQR(dbId);
            }
        });
    }

    function startQRPoll(dbId) {
        stopQRPoll();
        activeQRInstID = dbId;
        qrPollTimer = setInterval(function(){
            if (activeQRInstID) { pollInstanceStatus(activeQRInstID); }
        }, 4000);
    }

    function stopQRPoll() {
        if (qrPollTimer) { clearInterval(qrPollTimer); qrPollTimer = null; }
        activeQRInstID = null;
    }

    $(document).on('click', '.fwa-connect-btn', function() {
        var id = $(this).data('id');
        stopQRPoll();
        activeQRInstID = id;
        $('#fwa-pairing-result').text('').css('color','#25D366');
        $('#fwa-pairing-phone').val('');
        $('#fwa-qr-display').slideDown();
        fetchQR(id);
        startQRPoll(id);
    });

    $('#fwa-qr-refresh').on('click', function() {
        if (activeQRInstID) { fetchQR(activeQRInstID); }
    });

    $('#fwa-qr-close').on('click', function() {
        stopQRPoll();
        $('#fwa-qr-display').slideUp();
    });

    /* ----------------------------------------------------------------
     * Pairing code
     * -------------------------------------------------------------- */
    $('#fwa-get-pairing-code').on('click', function() {
        var phone = $('#fwa-pairing-phone').val().trim();
        if (!phone) {
            alert('<?php echo esc_js( __( 'Please enter your phone number first.', 'flexi-whatsapp-automation' ) ); ?>');
            return;
        }
        if (!activeQRInstID) {
            alert('<?php echo esc_js( __( 'No active QR session. Click Connect / QR first.', 'flexi-whatsapp-automation' ) ); ?>');
            return;
        }
        $('#fwa-pairing-result').css('color','#555').text('<?php echo esc_js( __( 'Requesting\u2026', 'flexi-whatsapp-automation' ) ); ?>');
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_get_pairing_code',
            _ajax_nonce: fwa_admin.nonce,
            id: activeQRInstID,
            phone: phone
        }, function(r) {
            if (r.success && r.data && r.data.code) {
                $('#fwa-pairing-result').css('color','#25D366').text(r.data.code);
            } else {
                $('#fwa-pairing-result').css('color','#dc3232').text(
                    r.data && r.data.message ? r.data.message : '<?php echo esc_js( __( 'Error getting pairing code.', 'flexi-whatsapp-automation' ) ); ?>'
                );
            }
        });
    });

    /* ----------------------------------------------------------------
     * Disconnect, delete, set active
     * -------------------------------------------------------------- */
    $(document).on('click', '.fwa-disconnect-btn', function() {
        var id = $(this).data('id');
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_disconnect_instance',
            _ajax_nonce: fwa_admin.nonce,
            id: id
        }, function() { loadInstances(); });
    });

    $(document).on('click', '.fwa-delete-btn', function() {
        if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this instance?', 'flexi-whatsapp-automation' ) ); ?>')) return;
        var id = $(this).data('id');
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_delete_instance',
            _ajax_nonce: fwa_admin.nonce,
            id: id
        }, function() { loadInstances(); });
    });

    $(document).on('change', '.fwa-set-active', function() {
        var id = $(this).val();
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_set_active_instance',
            _ajax_nonce: fwa_admin.nonce,
            id: id
        });
    });
});
</script>
