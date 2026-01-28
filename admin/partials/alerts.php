<?php
/**
 * Alerts configuration page partial
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$alerts = [
    'confirmation' => [
        'label' => __('Confirmación de Reserva', 'reservas-terapia'),
        'description' => __('Se envía inmediatamente al crear una reserva', 'reservas-terapia'),
    ],
    'reminder' => [
        'label' => __('Recordatorio', 'reservas-terapia'),
        'description' => __('Se envía antes de la cita', 'reservas-terapia'),
        'has_offset' => true,
        'offset_label' => __('Horas antes', 'reservas-terapia'),
    ],
    'thanks' => [
        'label' => __('Agradecimiento (Post-sesión)', 'reservas-terapia'),
        'description' => __('Se envía después de la cita', 'reservas-terapia'),
        'has_offset' => true,
        'offset_label' => __('Horas después', 'reservas-terapia'),
    ],
    'cancellation' => [
        'label' => __('Aviso de Cancelación', 'reservas-terapia'),
        'description' => __('Se envía cuando se cancela una reserva', 'reservas-terapia'),
    ],
    'rescheduled' => [
        'label' => __('Aviso de Modificación', 'reservas-terapia'),
        'description' => __('Se envía cuando se reagenda una reserva', 'reservas-terapia'),
    ],
];
?>
<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline"><?php esc_html_e('Configuración de Alertas', 'reservas-terapia'); ?></h1>
    <hr class="wp-header-end">

    <?php wp_nonce_field('rt_admin_nonce', 'rt_alerts_nonce'); ?>

    <div class="rt-card">
        <h2 class="title"><?php esc_html_e('Tipos de Notificaciones', 'reservas-terapia'); ?></h2>

        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Configura qué notificaciones enviar y personaliza su contenido. Las notificaciones pueden enviarse por email, WhatsApp, o ambos.', 'reservas-terapia'); ?>
        </p>

        <div class="rt-alerts-list">
            <?php foreach ($alerts as $key => $alert): ?>
                <div class="rt-alert-item" data-type="<?php echo esc_attr($key); ?>">
                    <div class="rt-alert-header">
                        <div>
                            <h3><?php echo esc_html($alert['label']); ?></h3>
                            <small style="color: #6b7280;"><?php echo esc_html($alert['description']); ?></small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="alerts[<?php echo esc_attr($key); ?>][active]"
                                class="rt-alert-toggle">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="rt-alert-body" style="display:none;">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Canal', 'reservas-terapia'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="alerts[<?php echo esc_attr($key); ?>][channels][]"
                                            value="email">
                                        <?php esc_html_e('Email', 'reservas-terapia'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="alerts[<?php echo esc_attr($key); ?>][channels][]"
                                            value="whatsapp">
                                        <?php esc_html_e('WhatsApp', 'reservas-terapia'); ?>
                                    </label>
                                </td>
                            </tr>
                            <?php if (!empty($alert['has_offset'])): ?>
                                <tr>
                                    <th><?php echo esc_html($alert['offset_label']); ?></th>
                                    <td>
                                        <input type="number" name="alerts[<?php echo esc_attr($key); ?>][offset]" value="24"
                                            class="small-text" min="1">
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php esc_html_e('Asunto (Email)', 'reservas-terapia'); ?></th>
                                <td>
                                    <input type="text" name="alerts[<?php echo esc_attr($key); ?>][subject]"
                                        class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Mensaje / Plantilla', 'reservas-terapia'); ?></th>
                                <td>
                                    <p class="description" style="margin-bottom: 8px;">
                                        <?php esc_html_e('Variables disponibles:', 'reservas-terapia'); ?>
                                        <code>{nombre}</code>, <code>{email}</code>, <code>{servicio}</code>,
                                        <code>{fecha}</code>, <code>{hora}</code>, <code>{link_cancelar}</code>,
                                        <code>{link_modificar}</code>, <code>{zoom_url}</code>
                                    </p>
                                    <textarea name="alerts[<?php echo esc_attr($key); ?>][message]" rows="5"
                                        class="large-text"
                                        placeholder="<?php esc_attr_e('Escribe tu mensaje aquí...', 'reservas-terapia'); ?>"></textarea>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button button-secondary rt-preview-alert"
                            data-alert="<?php echo esc_attr($key); ?>">
                            <?php esc_html_e('Vista Previa', 'reservas-terapia'); ?>
                        </button>
                    </div>
                    <button type="button" class="button rt-btn-expand-alert" style="margin: 12px 16px 16px;">
                        <?php esc_html_e('Editar Configuración', 'reservas-terapia'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="submit">
            <button type="button" id="rt-save-alerts" class="button button-primary">
                <?php esc_html_e('Guardar Configuración de Alertas', 'reservas-terapia'); ?>
            </button>
            <span class="spinner"></span>
        </p>
    </div>

    <div class="rt-card">
        <h3><?php esc_html_e('Notas Importantes', 'reservas-terapia'); ?></h3>
        <ul style="list-style: disc; padding-left: 20px; color: #4b5563;">
            <li><?php esc_html_e('Las notificaciones de WhatsApp requieren configurar Twilio en Integraciones.', 'reservas-terapia'); ?>
            </li>
            <li><?php esc_html_e('Los emails se envían desde la dirección configurada en WordPress.', 'reservas-terapia'); ?>
            </li>
            <li><?php esc_html_e('La variable {zoom_url} solo estará disponible si la integración con Zoom está activa.', 'reservas-terapia'); ?>
            </li>
        </ul>
    </div>
</div>