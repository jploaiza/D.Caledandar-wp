<?php
/**
 * Services page partial
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Servicios', 'reservas-terapia'); ?>
    </h1>
    <button id="rt-btn-add-service" class="page-title-action">
        <?php esc_html_e('Añadir Nuevo', 'reservas-terapia'); ?>
    </button>
    <hr class="wp-header-end">

    <?php wp_nonce_field('rt_admin_nonce', 'rt_services_nonce'); ?>

    <div id="rt-services-list-container">
        <!-- Service List Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>
                        <?php esc_html_e('Nombre', 'reservas-terapia'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Duración', 'reservas-terapia'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Precio', 'reservas-terapia'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Estado', 'reservas-terapia'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Acciones', 'reservas-terapia'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="rt-services-table-body">
                <!-- Populated via AJAX -->
                <tr>
                    <td colspan="5" class="rt-loading">
                        <?php esc_html_e('Cargando servicios...', 'reservas-terapia'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="rt-service-modal" class="rt-modal" style="display:none;">
        <div class="rt-modal-content">
            <span class="rt-close-modal">&times;</span>
            <h2 id="rt-modal-title">
                <?php esc_html_e('Añadir Servicio', 'reservas-terapia'); ?>
            </h2>
            <form id="rt-service-form">
                <input type="hidden" id="service_id" name="service_id" value="">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="service_name">
                                <?php esc_html_e('Nombre', 'reservas-terapia'); ?> <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <input name="service_name" type="text" id="service_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_description">
                                <?php esc_html_e('Descripción', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <textarea name="service_description" id="service_description" class="large-text"
                                rows="3"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_duration">
                                <?php esc_html_e('Duración', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="service_duration" id="service_duration">
                                <option value="15">15
                                    <?php esc_html_e('minutos', 'reservas-terapia'); ?>
                                </option>
                                <option value="30">30
                                    <?php esc_html_e('minutos', 'reservas-terapia'); ?>
                                </option>
                                <option value="45">45
                                    <?php esc_html_e('minutos', 'reservas-terapia'); ?>
                                </option>
                                <option value="60" selected>1
                                    <?php esc_html_e('hora', 'reservas-terapia'); ?>
                                </option>
                                <option value="90">1
                                    <?php esc_html_e('hora', 'reservas-terapia'); ?> 30 min
                                </option>
                                <option value="120">2
                                    <?php esc_html_e('horas', 'reservas-terapia'); ?>
                                </option>
                                <option value="180">3
                                    <?php esc_html_e('horas', 'reservas-terapia'); ?>
                                </option>
                                <option value="240">4
                                    <?php esc_html_e('horas', 'reservas-terapia'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_price">
                                <?php esc_html_e('Precio', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <input name="service_price" type="number" step="0.01" min="0" id="service_price"
                                class="small-text" value="0">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_color">
                                <?php esc_html_e('Color Calendario', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <input name="service_color" type="text" id="service_color" class="rt-color-field"
                                value="#3b82f6">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_buffer_before">
                                <?php esc_html_e('Buffer Antes (min)', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <input name="service_buffer_before" type="number" min="0" id="service_buffer_before"
                                class="tiny-text" value="0">
                            <p class="description">
                                <?php esc_html_e('Tiempo de preparación antes de la cita', 'reservas-terapia'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_buffer_after">
                                <?php esc_html_e('Buffer Después (min)', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <input name="service_buffer_after" type="number" min="0" id="service_buffer_after"
                                class="tiny-text" value="0">
                            <p class="description">
                                <?php esc_html_e('Tiempo de descanso después de la cita', 'reservas-terapia'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_active">
                                <?php esc_html_e('Estado', 'reservas-terapia'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="service_active" id="service_active">
                                <option value="1">
                                    <?php esc_html_e('Activo', 'reservas-terapia'); ?>
                                </option>
                                <option value="0">
                                    <?php esc_html_e('Inactivo', 'reservas-terapia'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" id="rt-save-service" class="button button-primary">
                        <?php esc_html_e('Guardar Servicio', 'reservas-terapia'); ?>
                    </button>
                    <span class="spinner"></span>
                </p>
            </form>
        </div>
    </div>
</div>