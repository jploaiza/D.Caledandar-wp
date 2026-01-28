<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    ReservasTerapia
 * @subpackage ReservasTerapia/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h2>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h2>

    <form method="post" action="options.php">
        <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Google Client ID</th>
                <td><input type="text" name="rt_google_client_id"
                        value="<?php echo esc_attr(get_option('rt_google_client_id')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Google Client Secret</th>
                <td><input type="password" name="rt_google_client_secret"
                        value="<?php echo esc_attr(get_option('rt_google_client_secret')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Redirect URI</th>
                <td>
                    <input type="text" name="rt_google_redirect_uri"
                        value="<?php echo esc_attr(get_option('rt_google_redirect_uri')); ?>" class="regular-text" />
                    <p class="description">Must match the URI authorized in Google Cloud Console.</p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <hr>

    <h3>Google Calendar Authorization</h3>
    <?php
    $access_token = get_option('rt_google_access_token');

    if ($access_token) {
        echo '<p style="color: green;"><strong>Connected to Google Calendar</strong></p>';
        // In a real implementation, add a disconnect button/link here processing a custom action.
    } else {
        // Instantiate Google_Calendar to get auth URL.
        // Assuming autoloader is working and class is available.
        if (class_exists('ReservasTerapia\Google_Calendar')) {
            try {
                $gc = new \ReservasTerapia\Google_Calendar();
                $auth_url = $gc->get_auth_url();
                echo '<a href="' . esc_url($auth_url) . '" class="button button-primary">Connect Google Calendar</a>';
            } catch (Exception $e) {
                echo '<p class="error">Error initializing Google Calendar: ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p>Google Calendar class not loaded. Please check composer dependencies.</p>';
        }
    }
    ?>
    }
    ?>

    <hr>
    <?php include_once 'zoom-settings-display.php'; ?>
</div>