<?php
/**
 * Bookings page partial
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
        <?php esc_html_e('Reservas', 'reservas-terapia'); ?>
    </h1>
    <button id="rt-btn-add-booking" class="page-title-action">
        <?php esc_html_e('Nueva Reserva Manual', 'reservas-terapia'); ?>
    </button>
    <hr class="wp-header-end">

    <?php wp_nonce_field('rt_admin_nonce', 'rt_bookings_nonce'); ?>

    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="text" id="rt-filter-search" placeholder="<?php esc_attr_e('Buscar...', 'reservas-terapia'); ?>"
                class="regular-text">
            <select id="rt-filter-service">
                <option value="">
                    <?php esc_html_e('Todos los servicios', 'reservas-terapia'); ?>
                </option>
                <!-- Populated via JS -->
            </select>
            <select id="rt-filter-status">
                <option value="">
                    <?php esc_html_e('Todos los estados', 'reservas-terapia'); ?>
                </option>
                <option value="confirmed">
                    <?php esc_html_e('Confirmada', 'reservas-terapia'); ?>
                </option>
                <option value="pending">
                    <?php esc_html_e('Pendiente', 'reservas-terapia'); ?>
                </option>
                <option value="cancelled">
                    <?php esc_html_e('Cancelada', 'reservas-terapia'); ?>
                </option>
                <option value="completed">
                    <?php esc_html_e('Completada', 'reservas-terapia'); ?>
                </option>
            </select>
            <input type="date" id="rt-filter-date">
            <button type="button" id="rt-apply-filters" class="button">
                <?php esc_html_e('Filtrar', 'reservas-terapia'); ?>
            </button>
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num" id="rt-total-items">0
                <?php esc_html_e('elementos', 'reservas-terapia'); ?>
            </span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column"><input type="checkbox"></th>
                <th scope="col">
                    <?php esc_html_e('Código', 'reservas-terapia'); ?>
                </th>
                <th scope="col">
                    <?php esc_html_e('Cliente', 'reservas-terapia'); ?>
                </th>
                <th scope="col">
                    <?php esc_html_e('Email / Teléfono', 'reservas-terapia'); ?>
                </th>
                <th scope="col">
                    <?php esc_html_e('Servicio', 'reservas-terapia'); ?>
                </th>
                <th scope="col">
                    <?php esc_html_e('Fecha y Hora', 'reservas-terapia'); ?>
                </th>
                <th scope="col">
                    <?php esc_html_e('Estado', 'reservas-terapia'); ?>
                </th>
                <th scope="col">
                    <?php esc_html_e('Acciones', 'reservas-terapia'); ?>
                </th>
            </tr>
        </thead>
        <tbody id="rt-bookings-table-body">
            <tr>
                <td colspan="8" class="rt-loading">
                    <?php esc_html_e('Cargando reservas...', 'reservas-terapia'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Pagination will be inserted here by JS -->

    <!-- Booking Detail/Edit Modal -->
    <div id="rt-booking-modal" class="rt-modal" style="display:none;">
        <div class="rt-modal-content">
            <span class="rt-close-modal">&times;</span>
            <h2 id="rt-booking-modal-title">
                <?php esc_html_e('Detalles de Reserva', 'reservas-terapia'); ?>
            </h2>
            <form id="rt-booking-form">
                <input type="hidden" id="booking_id" name="booking_id">

                <div class="rt-grid-2">
                    <div class="rt-form-group">
                        <label for="booking_client_name">
                            <?php esc_html_e('Cliente', 'reservas-terapia'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" name="client_name" id="booking_client_name" class="regular-text" required>
                    </div>
                    <div class="rt-form-group">
                        <label for="booking_client_email">
                            <?php esc_html_e('Email', 'reservas-terapia'); ?> <span class="required">*</span>
                        </label>
                        <input type="email" name="client_email" id="booking_client_email" class="regular-text" required>
                    </div>
                </div>

                <div class="rt-grid-2">
                    <div class="rt-form-group">
                        <label for="booking_client_phone">
                            <?php esc_html_e('Teléfono', 'reservas-terapia'); ?>
                        </label>
                        <input type="text" name="client_phone" id="booking_client_phone" class="regular-text"
                            placeholder="+56 9 1234 5678">
                    </div>
                    <div class="rt-form-group">
                        <label for="booking_service_id">
                            <?php esc_html_e('Servicio', 'reservas-terapia'); ?> <span class="required">*</span>
                        </label>
                        <select name="service_id" id="booking_service_id" required>
                            <option value="">
                                <?php esc_html_e('Seleccionar...', 'reservas-terapia'); ?>
                            </option>
                            <!-- Populated via JS -->
                        </select>
                    </div>
                </div>

                <div class="rt-grid-2">
                    <div class="rt-form-group">
                        <label for="booking_date">
                            <?php esc_html_e('Fecha', 'reservas-terapia'); ?> <span class="required">*</span>
                        </label>
                        <input type="date" name="booking_date" id="booking_date" required>
                    </div>
                    <div class="rt-form-group">
                        <label for="booking_time">
                            <?php esc_html_e('Hora', 'reservas-terapia'); ?> <span class="required">*</span>
                        </label>
                        <input type="time" name="booking_time" id="booking_time" required>
                    </div>
                </div>

                <div class="rt-form-group">
                    <label for="booking_status">
                        <?php esc_html_e('Estado', 'reservas-terapia'); ?>
                    </label>
                    <select name="booking_status" id="booking_status">
                        <option value="pending">
                            <?php esc_html_e('Pendiente', 'reservas-terapia'); ?>
                        </option>
                        <option value="confirmed">
                            <?php esc_html_e('Confirmada', 'reservas-terapia'); ?>
                        </option>
                        <option value="cancelled">
                            <?php esc_html_e('Cancelada', 'reservas-terapia'); ?>
                        </option>
                        <option value="completed">
                            <?php esc_html_e('Completada', 'reservas-terapia'); ?>
                        </option>
                    </select>
                </div>

                <div class="rt-form-group">
                    <label for="booking_notes">
                        <?php esc_html_e('Notas Internas', 'reservas-terapia'); ?>
                    </label>
                    <textarea name="booking_notes" id="booking_notes" class="large-text" rows="3"
                        placeholder="<?php esc_attr_e('Notas visibles solo para administradores...', 'reservas-terapia'); ?>"></textarea>
                </div>

                <div class="rt-modal-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Guardar Cambios', 'reservas-terapia'); ?>
                    </button>
                    <button type="button" class="button button-secondary rt-close-modal-btn">
                        <?php esc_html_e('Cerrar', 'reservas-terapia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>