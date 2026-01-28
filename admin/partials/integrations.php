<?php
/**
 * Integrations page partial
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// $plugin_name is passed from the display method in Admin.php
$plugin_name = isset($plugin_name) ? $plugin_name : 'd-calendar';

// Check connection statuses
$google_connected = !empty(get_option('rt_google_access_token'));
$zoom_connected = !empty(get_option('rt_zoom_access_token'));
$twilio_configured = !empty(get_option('rt_twilio_account_sid')) && !empty(get_option('rt_twilio_auth_token'));
?>
<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline"><?php esc_html_e('Integraciones', 'reservas-terapia'); ?></h1>
    <hr class="wp-header-end">

    <?php wp_nonce_field('rt_admin_nonce', 'rt_integrations_nonce'); ?>

    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Conecta D.Calendar con servicios externos para automatizar tu flujo de trabajo.', 'reservas-terapia'); ?>
    </p>

    <div class="rt-integrations-grid">
        <!-- Google Calendar -->
        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-calendar-alt integration-icon"></span>
                <h2>Google Calendar</h2>
            </div>
            <p><?php esc_html_e('Sincroniza tus reservas con Google Calendar automáticamente.', 'reservas-terapia'); ?>
            </p>

            <div class="rt-form-group">
                <label><?php esc_html_e('Estado:', 'reservas-terapia'); ?>
                    <?php if ($google_connected): ?>
                        <span
                            class="status-badge status-connected"><?php esc_html_e('Conectado', 'reservas-terapia'); ?></span>
                    <?php else: ?>
                        <span
                            class="status-badge status-disconnected"><?php esc_html_e('Desconectado', 'reservas-terapia'); ?></span>
                    <?php endif; ?>
                </label>
            </div>

            <?php if ($google_connected): ?>
                <div class="rt-form-group">
                    <label><?php esc_html_e('Calendar ID', 'reservas-terapia'); ?></label>
                    <input type="text" name="google_calendar_id" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_google_calendar_id', 'primary')); ?>"
                        placeholder="primary">
                    <p class="description">
                        <?php esc_html_e('Deja en blanco para usar el calendario principal', 'reservas-terapia'); ?></p>
                </div>
                <button type="button"
                    class="button button-secondary"><?php esc_html_e('Desconectar', 'reservas-terapia'); ?></button>
            <?php else: ?>
                <?php
                if (class_exists('ReservasTerapia\\Google_Calendar')) {
                    try {
                        $gc = new \ReservasTerapia\Google_Calendar();
                        $auth_url = $gc->get_auth_url();
                        ?>
                        <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                            <?php esc_html_e('Conectar con Google', 'reservas-terapia'); ?>
                        </a>
                        <?php
                    } catch (Exception $e) {
                        ?>
                        <p class="description" style="color: #ef4444;">
                            <?php echo esc_html($e->getMessage()); ?>
                        </p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=rt-settings')); ?>" class="button button-secondary">
                            <?php esc_html_e('Configurar credenciales', 'reservas-terapia'); ?>
                        </a>
                        <?php
                    }
                } else {
                    ?>
                    <p class="description">
                        <?php esc_html_e('Clase Google Calendar no disponible. Verifica las dependencias.', 'reservas-terapia'); ?>
                    </p>
                    <?php
                }
                ?>
            <?php endif; ?>
        </div>

        <!-- Zoom -->
        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-video-alt3 integration-icon"></span>
                <h2>Zoom</h2>
            </div>
            <p><?php esc_html_e('Genera enlaces de reuniones automáticamente para servicios online.', 'reservas-terapia'); ?>
            </p>

            <div class="rt-form-group">
                <label><?php esc_html_e('Estado:', 'reservas-terapia'); ?>
                    <?php if ($zoom_connected): ?>
                        <span
                            class="status-badge status-connected"><?php esc_html_e('Conectado', 'reservas-terapia'); ?></span>
                    <?php else: ?>
                        <span
                            class="status-badge status-disconnected"><?php esc_html_e('Desconectado', 'reservas-terapia'); ?></span>
                    <?php endif; ?>
                </label>
            </div>

            <form id="rt-integration-zoom">
                <div class="rt-form-group">
                    <label><?php esc_html_e('Client ID', 'reservas-terapia'); ?></label>
                    <input type="text" name="zoom_api_key" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_zoom_client_id')); ?>">
                </div>
                <div class="rt-form-group">
                    <label><?php esc_html_e('Client Secret', 'reservas-terapia'); ?></label>
                    <input type="password" name="zoom_api_secret" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_zoom_client_secret')); ?>">
                </div>
                <div class="rt-form-group">
                    <label><?php esc_html_e('Webhook Secret', 'reservas-terapia'); ?></label>
                    <input type="text" name="zoom_webhook_secret" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_zoom_webhook_secret')); ?>">
                    <p class="description"><?php esc_html_e('Para validar webhooks de Zoom', 'reservas-terapia'); ?></p>
                </div>
                <div class="rt-action-buttons">
                    <button type="button" class="button button-primary" id="rt-save-zoom">
                        <?php esc_html_e('Guardar y Conectar', 'reservas-terapia'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Twilio WhatsApp -->
        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-smartphone integration-icon"></span>
                <h2>Twilio (WhatsApp/SMS)</h2>
            </div>
            <p><?php esc_html_e('Envía notificaciones por WhatsApp o SMS.', 'reservas-terapia'); ?></p>

            <div class="rt-form-group">
                <label><?php esc_html_e('Estado:', 'reservas-terapia'); ?>
                    <?php if ($twilio_configured): ?>
                        <span
                            class="status-badge status-connected"><?php esc_html_e('Configurado', 'reservas-terapia'); ?></span>
                    <?php else: ?>
                        <span
                            class="status-badge status-disconnected"><?php esc_html_e('No configurado', 'reservas-terapia'); ?></span>
                    <?php endif; ?>
                </label>
            </div>

            <form method="post" action="options.php" id="rt-integration-twilio">
                <?php settings_fields($plugin_name); ?>
                <div class="rt-form-group">
                    <label><?php esc_html_e('Account SID', 'reservas-terapia'); ?></label>
                    <input type="text" name="rt_twilio_account_sid" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_twilio_account_sid')); ?>">
                </div>
                <div class="rt-form-group">
                    <label><?php esc_html_e('Auth Token', 'reservas-terapia'); ?></label>
                    <input type="password" name="rt_twilio_auth_token" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_twilio_auth_token')); ?>">
                </div>
                <div class="rt-form-group">
                    <label><?php esc_html_e('WhatsApp Number (From)', 'reservas-terapia'); ?></label>
                    <input type="text" name="rt_twilio_whatsapp_number" class="regular-text" placeholder="+14155238886"
                        value="<?php echo esc_attr(get_option('rt_twilio_whatsapp_number')); ?>">
                    <p class="description">
                        <?php esc_html_e('Número de Twilio Sandbox o número aprobado', 'reservas-terapia'); ?></p>
                </div>
                <div class="rt-action-buttons">
                    <?php submit_button(__('Guardar Credenciales', 'reservas-terapia'), 'primary', 'submit', false); ?>
                    <button type="button" class="button button-secondary" id="rt-test-twilio">
                        <?php esc_html_e('Enviar Mensaje de Prueba', 'reservas-terapia'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Email / SMTP (informational) -->
        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-email-alt integration-icon"></span>
                <h2><?php esc_html_e('Email', 'reservas-terapia'); ?></h2>
            </div>
            <p><?php esc_html_e('Los emails se envían usando la configuración de WordPress.', 'reservas-terapia'); ?>
            </p>

            <div class="rt-form-group">
                <label><?php esc_html_e('Email del sitio:', 'reservas-terapia'); ?></label>
                <p><strong><?php echo esc_html(get_option('admin_email')); ?></strong></p>
            </div>

            <p class="description">
                <?php
                printf(
                    /* translators: %s: link to plugins page */
                    esc_html__('Para mejorar la entrega de emails, considera usar un plugin SMTP como %s.', 'reservas-terapia'),
                    '<a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP Mail SMTP</a>'
                );
                ?>
            </p>
        </div>
    </div>

    <div class="rt-card" style="margin-top: 20px;">
        <h3><?php esc_html_e('Webhooks Endpoint', 'reservas-terapia'); ?></h3>
        <p class="description">
            <?php esc_html_e('Estas URLs son necesarias para configurar los webhooks en los servicios externos:', 'reservas-terapia'); ?>
        </p>

        <table class="form-table" style="margin: 0;">
            <tr>
                <th><?php esc_html_e('Twilio WhatsApp', 'reservas-terapia'); ?></th>
                <td>
                    <code><?php echo esc_url(rest_url('reservas-terapia/v1/twilio/webhook')); ?></code>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Zoom Webhooks', 'reservas-terapia'); ?></th>
                <td>
                    <code><?php echo esc_url(rest_url('reservas-terapia/v1/zoom/webhook')); ?></code>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Google Calendar', 'reservas-terapia'); ?></th>
                <td>
                    <code><?php echo esc_url(admin_url('admin.php?page=rt-settings')); ?></code>
                    <span class="description">(<?php esc_html_e('Redirect URI', 'reservas-terapia'); ?>)</span>
                </td>
            </tr>
        </table>
    </div>
</div>