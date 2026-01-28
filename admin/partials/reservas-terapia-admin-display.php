<?php
/**
 * Settings page partial
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin_name = isset($this) && isset($this->plugin_name) ? $this->plugin_name : 'd-calendar';
?>
<div class="wrap rt-admin-wrapper">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="rt-card">
        <h2 class="title"><?php esc_html_e('Credenciales de Google Calendar', 'reservas-terapia'); ?></h2>

        <form method="post" action="options.php">
            <?php
            settings_fields($plugin_name);
            do_settings_sections($plugin_name);
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Google Client ID', 'reservas-terapia'); ?></th>
                    <td>
                        <input type="text" name="rt_google_client_id"
                            value="<?php echo esc_attr(get_option('rt_google_client_id')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Google Client Secret', 'reservas-terapia'); ?></th>
                    <td>
                        <input type="password" name="rt_google_client_secret"
                            value="<?php echo esc_attr(get_option('rt_google_client_secret')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Redirect URI', 'reservas-terapia'); ?></th>
                    <td>
                        <input type="text" name="rt_google_redirect_uri"
                            value="<?php echo esc_attr(get_option('rt_google_redirect_uri')); ?>" class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Debe coincidir con la URI autorizada en Google Cloud Console.', 'reservas-terapia'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Guardar Configuración', 'reservas-terapia')); ?>
        </form>

        <hr>

        <h3><?php esc_html_e('Autorización de Google Calendar', 'reservas-terapia'); ?></h3>
        <?php
        $access_token = get_option('rt_google_access_token');

        if ($access_token) {
            echo '<p style="color: #10b981;"><strong>' . esc_html__('Conectado a Google Calendar', 'reservas-terapia') . '</strong></p>';
            echo '<p class="description">' . esc_html__('La sincronización con Google Calendar está activa.', 'reservas-terapia') . '</p>';
        } else {
            if (class_exists('ReservasTerapia\\Google_Calendar')) {
                try {
                    $gc = new \ReservasTerapia\Google_Calendar();
                    $auth_url = $gc->get_auth_url();
                    ?>
                    <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                        <?php esc_html_e('Conectar Google Calendar', 'reservas-terapia'); ?>
                    </a>
                    <?php
                } catch (Exception $e) {
                    ?>
                    <p class="description" style="color: #ef4444;">
                        <?php esc_html_e('Error:', 'reservas-terapia'); ?>             <?php echo esc_html($e->getMessage()); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Asegúrate de haber configurado correctamente las credenciales de Google Cloud Console.', 'reservas-terapia'); ?>
                    </p>
                    <?php
                }
            } else {
                ?>
                <p class="description">
                    <?php esc_html_e('La clase Google Calendar no está cargada. Verifica las dependencias de Composer.', 'reservas-terapia'); ?>
                </p>
                <?php
            }
        }
        ?>
    </div>

    <div class="rt-card">
        <h2 class="title"><?php esc_html_e('Configuración de Zoom', 'reservas-terapia'); ?></h2>
        <?php include_once 'zoom-settings-display.php'; ?>
    </div>

    <div class="rt-card">
        <h2 class="title"><?php esc_html_e('Configuración General', 'reservas-terapia'); ?></h2>

        <form method="post" action="options.php">
            <?php settings_fields($plugin_name); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Nombre del Negocio', 'reservas-terapia'); ?></th>
                    <td>
                        <input type="text" name="rt_business_name"
                            value="<?php echo esc_attr(get_option('rt_business_name')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Email de Contacto', 'reservas-terapia'); ?></th>
                    <td>
                        <input type="email" name="rt_business_email"
                            value="<?php echo esc_attr(get_option('rt_business_email', get_option('admin_email'))); ?>"
                            class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Teléfono de Contacto', 'reservas-terapia'); ?></th>
                    <td>
                        <input type="text" name="rt_business_phone"
                            value="<?php echo esc_attr(get_option('rt_business_phone')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Zona Horaria', 'reservas-terapia'); ?></th>
                    <td>
                        <select name="rt_timezone">
                            <?php
                            $current_tz = get_option('rt_timezone', wp_timezone_string());
                            $timezones = timezone_identifiers_list();
                            foreach ($timezones as $tz) {
                                $selected = ($tz === $current_tz) ? 'selected' : '';
                                echo '<option value="' . esc_attr($tz) . '" ' . $selected . '>' . esc_html($tz) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: current WordPress timezone */
                                esc_html__('Por defecto usa la zona horaria de WordPress: %s', 'reservas-terapia'),
                                '<code>' . esc_html(wp_timezone_string()) . '</code>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Guardar Configuración', 'reservas-terapia')); ?>
        </form>
    </div>
</div>