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
        <div id="fwa-qr-image" style="margin:20px auto;"></div>
        <button type="button" class="button" id="fwa-qr-close"><?php echo esc_html__( 'Close', 'flexi-whatsapp-automation' ); ?></button>
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

    function loadInstances() {
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_get_instances',
            _ajax_nonce: fwa_admin.nonce
        }, function(response) {
            var tbody = $('#fwa-instances-table tbody');
            tbody.empty();
            if (response.success && response.data && response.data.length) {
                $.each(response.data, function(i, inst) {
                    var color = statusColors[inst.status] || '#999';
                    var checked = inst.is_active ? 'checked' : '';
                    tbody.append(
                        '<tr data-id="' + esc(inst.id) + '">' +
                        '<td>' + esc(inst.name) + '</td>' +
                        '<td><code>' + esc(inst.instance_id) + '</code></td>' +
                        '<td>' + esc(inst.phone_number || '') + '</td>' +
                        '<td><span class="fwa-status-badge" style="background:' + color + ';color:#fff;padding:3px 10px;border-radius:3px;font-size:12px;">' + esc(inst.status) + '</span></td>' +
                        '<td><input type="radio" name="fwa_active_instance" value="' + esc(inst.id) + '" ' + checked + ' class="fwa-set-active" /></td>' +
                        '<td>' + esc(inst.last_seen || '—') + '</td>' +
                        '<td>' +
                        '<button type="button" class="button button-small fwa-connect-btn" data-id="' + esc(inst.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Connect', 'flexi-whatsapp-automation' ) ); ?> + '</button> ' +
                        '<button type="button" class="button button-small fwa-disconnect-btn" data-id="' + esc(inst.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Disconnect', 'flexi-whatsapp-automation' ) ); ?> + '</button> ' +
                        '<button type="button" class="button button-small button-link-delete fwa-delete-btn" data-id="' + esc(inst.id) + '">' + <?php echo wp_json_encode( esc_html__( 'Delete', 'flexi-whatsapp-automation' ) ); ?> + '</button>' +
                        '</td></tr>'
                    );
                });
            } else {
                tbody.append('<tr><td colspan="7">' + <?php echo wp_json_encode( esc_html__( 'No instances found.', 'flexi-whatsapp-automation' ) ); ?> + '</td></tr>');
            }
        });
    }

    function esc(str) {
        return $('<span>').text(str || '').html();
    }

    loadInstances();

    $('#fwa-add-instance-btn').on('click', function() {
        $('#fwa-instance-form')[0].reset();
        $('#fwa-inst-db-id').val('');
        $('#fwa-instance-form-title').text(<?php echo wp_json_encode( esc_html__( 'Add New Instance', 'flexi-whatsapp-automation' ) ); ?>);
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
                alert(response.data || <?php echo wp_json_encode( esc_html__( 'Error saving instance.', 'flexi-whatsapp-automation' ) ); ?>);
            }
        });
    });

    $(document).on('click', '.fwa-connect-btn', function() {
        var id = $(this).data('id');
        $('#fwa-qr-image').html('<p>' + <?php echo wp_json_encode( esc_html__( 'Loading QR code…', 'flexi-whatsapp-automation' ) ); ?> + '</p>');
        $('#fwa-qr-display').slideDown();
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_connect_instance',
            _ajax_nonce: fwa_admin.nonce,
            instance_id: id
        }, function(response) {
            if (response.success && response.data && response.data.qr) {
                $('#fwa-qr-image').html('<img src="' + response.data.qr + '" alt="QR Code" style="max-width:300px;" />');
            } else {
                $('#fwa-qr-image').html('<p>' + (response.data || <?php echo wp_json_encode( esc_html__( 'Could not load QR code.', 'flexi-whatsapp-automation' ) ); ?>) + '</p>');
            }
        });
    });

    $('#fwa-qr-close').on('click', function() {
        $('#fwa-qr-display').slideUp();
    });

    $(document).on('click', '.fwa-disconnect-btn', function() {
        var id = $(this).data('id');
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_disconnect_instance',
            _ajax_nonce: fwa_admin.nonce,
            instance_id: id
        }, function() { loadInstances(); });
    });

    $(document).on('click', '.fwa-delete-btn', function() {
        if (!confirm(<?php echo wp_json_encode( esc_html__( 'Are you sure you want to delete this instance?', 'flexi-whatsapp-automation' ) ); ?>)) return;
        var id = $(this).data('id');
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_delete_instance',
            _ajax_nonce: fwa_admin.nonce,
            instance_id: id
        }, function() { loadInstances(); });
    });

    $(document).on('change', '.fwa-set-active', function() {
        var id = $(this).val();
        $.post(fwa_admin.ajax_url, {
            action: 'fwa_set_active_instance',
            _ajax_nonce: fwa_admin.nonce,
            instance_id: id
        });
    });
});
</script>
