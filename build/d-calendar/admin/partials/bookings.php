<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Reservas</h1>
    <button id="rt-btn-add-booking" class="page-title-action">Nueva Reserva Manual</button>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="rt-filter-service">
                <option value="">Todos los servicios</option>
                <!-- Populated via JS -->
            </select>
            <select id="rt-filter-status">
                <option value="">Todos los estados</option>
                <option value="confirmed">Confirmada</option>
                <option value="pending">Pendiente</option>
                <option value="cancelled">Cancelada</option>
                <option value="completed">Completada</option>
            </select>
            <input type="date" id="rt-filter-date">
            <button type="button" id="rt-apply-filters" class="button">Filtrar</button>
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num" id="rt-total-items">0 elementos</span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column"><input type="checkbox"></th>
                <th scope="col">Código</th>
                <th scope="col">Cliente</th>
                <th scope="col">Email / Teléfono</th>
                <th scope="col">Servicio</th>
                <th scope="col">Fecha y Hora</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="rt-bookings-table-body">
            <tr>
                <td colspan="8">Cargando reservas...</td>
            </tr>
        </tbody>
    </table>

    <!-- Booking Detail/Edit Modal -->
    <div id="rt-booking-modal" class="rt-modal" style="display:none;">
        <div class="rt-modal-content">
            <span class="rt-close-modal">&times;</span>
            <h2 id="rt-booking-modal-title">Detalles de Reserva</h2>
            <form id="rt-booking-form">
                <input type="hidden" id="booking_id" name="booking_id">

                <div class="rt-grid-2">
                    <div class="rt-form-group">
                        <label>Cliente</label>
                        <input type="text" name="client_name" id="booking_client_name" class="regular-text" required>
                    </div>
                    <div class="rt-form-group">
                        <label>Email</label>
                        <input type="email" name="client_email" id="booking_client_email" class="regular-text" required>
                    </div>
                </div>

                <div class="rt-grid-2">
                    <div class="rt-form-group">
                        <label>Teléfono</label>
                        <input type="text" name="client_phone" id="booking_client_phone" class="regular-text">
                    </div>
                    <div class="rt-form-group">
                        <label>Servicio</label>
                        <select name="service_id" id="booking_service_id" required>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </div>

                <div class="rt-grid-2">
                    <div class="rt-form-group">
                        <label>Fecha</label>
                        <input type="date" name="booking_date" id="booking_date" required>
                    </div>
                    <div class="rt-form-group">
                        <label>Hora</label>
                        <input type="time" name="booking_time" id="booking_time" required>
                    </div>
                </div>

                <div class="rt-form-group">
                    <label>Estado</label>
                    <select name="booking_status" id="booking_status">
                        <option value="pending">Pendiente</option>
                        <option value="confirmed">Confirmada</option>
                        <option value="cancelled">Cancelada</option>
                        <option value="completed">Completada</option>
                    </select>
                </div>

                <div class="rt-form-group">
                    <label>Notas Internas</label>
                    <textarea name="booking_notes" id="booking_notes" class="large-text" rows="3"></textarea>
                </div>

                <div class="rt-modal-actions">
                    <button type="submit" class="button button-primary">Guardar Cambios</button>
                    <button type="button" class="button button-secondary rt-close-modal-btn">Cerrar</button>
                </div>
            </form>
        </div>
    </div>
</div>