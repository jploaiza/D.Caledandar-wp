<?php
/**
 * Schedules page partial
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$days = [
    'mon' => __('Lunes', 'reservas-terapia'),
    'tue' => __('Martes', 'reservas-terapia'),
    'wed' => __('Miércoles', 'reservas-terapia'),
    'thu' => __('Jueves', 'reservas-terapia'),
    'fri' => __('Viernes', 'reservas-terapia'),
    'sat' => __('Sábado', 'reservas-terapia'),
    'sun' => __('Domingo', 'reservas-terapia'),
];
?>
<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline"><?php esc_html_e('Configuración de Horarios', 'reservas-terapia'); ?></h1>
    <hr class="wp-header-end">

    <p class="description">
        <?php esc_html_e('Configura los días y horarios en los que estás disponible para recibir reservas.', 'reservas-terapia'); ?>
    </p>

    <div class="rt-card rt-schedule-container">
        <form id="rt-schedule-form">
            <?php wp_nonce_field('rt_admin_nonce', 'rt_schedules_nonce'); ?>

            <table class="form-table rt-schedule-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Día', 'reservas-terapia'); ?></th>
                        <th><?php esc_html_e('Habilitado', 'reservas-terapia'); ?></th>
                        <th><?php esc_html_e('Hora Inicio', 'reservas-terapia'); ?></th>
                        <th><?php esc_html_e('Hora Fin', 'reservas-terapia'); ?></th>
                        <th><?php esc_html_e('Duración Slot', 'reservas-terapia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $key => $day_name): ?>
                        <tr class="rt-schedule-row" data-day="<?php echo esc_attr($key); ?>">
                            <td>
                                <strong><?php echo esc_html($day_name); ?></strong>
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="schedule[<?php echo esc_attr($key); ?>][active]" value="1"
                                        class="rt-day-toggle">
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td>
                                <input type="time" name="schedule[<?php echo esc_attr($key); ?>][start]"
                                    class="rt-time-start" value="09:00" disabled>
                            </td>
                            <td>
                                <input type="time" name="schedule[<?php echo esc_attr($key); ?>][end]" class="rt-time-end"
                                    value="18:00" disabled>
                            </td>
                            <td>
                                <select name="schedule[<?php echo esc_attr($key); ?>][slot_duration]"
                                    class="rt-slot-duration" disabled>
                                    <option value="15">15 min</option>
                                    <option value="30" selected>30 min</option>
                                    <option value="45">45 min</option>
                                    <option value="60">60 min</option>
                                    <option value="90">90 min</option>
                                    <option value="120">120 min</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="rt-schedule-preview">
                <h3><?php esc_html_e('Vista Previa de Slots', 'reservas-terapia'); ?></h3>
                <p class="description">
                    <?php esc_html_e('Modifica un día habilitado para ver cómo se generarán los espacios disponibles.', 'reservas-terapia'); ?>
                </p>
                <div id="rt-slots-preview-container">
                    <span
                        class="description"><?php esc_html_e('Habilita un día para ver la vista previa', 'reservas-terapia'); ?></span>
                </div>
            </div>

            <p class="submit">
                <button type="submit" id="rt-save-schedule" class="button button-primary">
                    <?php esc_html_e('Guardar Horarios', 'reservas-terapia'); ?>
                </button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>

    <div class="rt-card">
        <h3><?php esc_html_e('Días Especiales / Excepciones', 'reservas-terapia'); ?></h3>
        <p class="description">
            <?php esc_html_e('Para bloquear días específicos (vacaciones, feriados, etc.), utiliza la integración con Google Calendar.', 'reservas-terapia'); ?>
        </p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=rt-integrations')); ?>" class="button button-secondary">
            <?php esc_html_e('Ir a Integraciones', 'reservas-terapia'); ?>
        </a>
    </div>
</div>