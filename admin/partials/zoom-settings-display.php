<?php
/**
 * Zoom Settings Display
 *
 * @package ReservasTerapia
 * @subpackage ReservasTerapia/admin/partials
 */
?>
<div class="wrap">
    <h2>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h2>

    <form method="post" action="options.php">
        <?php
        settings_fields($this->plugin_name . '_zoom'); // Use explicit option group
        do_settings_sections($this->plugin_name . '_zoom');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Zoom Client ID</th>
                <td>
                    <input type="text" name="rt_zoom_client_id"
                        value="<?php echo esc_attr(get_option('rt_zoom_client_id')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Zoom Client Secret</th>
                <td>
                    <input type="password" name="rt_zoom_client_secret"
                        value="<?php echo esc_attr(get_option('rt_zoom_client_secret')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Webhook Secret Token</th>
                <td>
                    <input type="password" name="rt_zoom_webhook_secret"
                        value="<?php echo esc_attr(get_option('rt_zoom_webhook_secret')); ?>"
                        class="regular-text" />
                    <p class="description">From Zoom App > Feature > Event Subscriptions</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Record Meetings</th>
                <td>
                    <input type="checkbox" name="rt_zoom_record_meetings" value="1" <?php checked(1, get_option('rt_zoom_record_meetings'), true); ?> />
                    <label>Automatically record meetings (cloud)</label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <hr>

    <h3>Zoom Connection Status</h3>
    <?php
    $access_token = get_option('rt_zoom_access_token');

    // Check callback if present
    if (isset($_GET['code']) && isset($_GET['state']) && wp_verify_nonce($_GET['state'], 'rt_zoom_auth_nonce')) {
        // Need to ensure class is loaded. It is manually required in main file.
        $zoom = \ReservasTerapia\Zoom::get_instance();
        $result = $zoom->handle_callback(sanitize_text_field($_GET['code']));

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Zoom connected successfully!</p></div>';
            // Refresh token var
            $access_token = get_option('rt_zoom_access_token');
        }
    }

    if ($access_token) {
        echo '<p style="color: green;"><strong>✅ Connected to Zoom</strong></p>';
        // Optional: Show token expiry or a Disconnect button
    } else {
        if (class_exists('ReservasTerapia\Zoom')) {
            $zoom = \ReservasTerapia\Zoom::get_instance();
            $login_url = $zoom->get_login_url();
            echo '<a href="' . esc_url($login_url) . '" class="button button-primary">Connect with Zoom</a>';
        } else {
            echo '<p class="error">Zoom class not loaded.</p>';
        }
    }
    ?>
</div>