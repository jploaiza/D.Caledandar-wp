<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Servicios</h1>
    <button id="rt-btn-add-service" class="page-title-action">Añadir Nuevo</button>
    <hr class="wp-header-end">

    <div id="rt-services-list-container">
        <!-- Service List Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Duración</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="rt-services-table-body">
                <!-- Populated via AJAX -->
                <tr>
                    <td colspan="5">Cargando servicios...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Service Modal/Form Container (Hidden by default) -->
    <div id="rt-service-modal" class="rt-modal" style="display:none;">
        <div class="rt-modal-content">
            <span class="rt-close-modal">&times;</span>
            <h2 id="rt-modal-title">Editar Servicio</h2>
            <form id="rt-service-form">
                <input type="hidden" id="service_id" name="service_id" value="">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="service_name">Nombre <span class="required">*</span></label></th>
                        <td><input name="service_name" type="text" id="service_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_description">Descripción</label></th>
                        <td><textarea name="service_description" id="service_description" class="large-text"
                                rows="3"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_duration">Duración</label></th>
                        <td>
                            <select name="service_duration" id="service_duration">
                                <option value="15">15 minutos</option>
                                <option value="30">30 minutos</option>
                                <option value="45">45 minutos</option>
                                <option value="60">1 hora</option>
                                <option value="90">1 hora 30 min</option>
                                <option value="120">2 horas</option>
                                <option value="180">3 horas</option>
                                <option value="240">4 horas</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_price">Precio</label></th>
                        <td><input name="service_price" type="number" step="0.01" id="service_price" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_color">Color Calendario</label></th>
                        <td><input name="service_color" type="text" id="service_color" class="rt-color-field"
                                value="#3b82f6"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_buffer_before">Buffer Antes (min)</label></th>
                        <td><input name="service_buffer_before" type="number" id="service_buffer_before"
                                class="tiny-text" value="0"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_buffer_after">Buffer Después (min)</label></th>
                        <td><input name="service_buffer_after" type="number" id="service_buffer_after" class="tiny-text"
                                value="0"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_active">Estado</label></th>
                        <td>
                            <select name="service_active" id="service_active">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" id="rt-save-service" class="button button-primary">Guardar Servicio</button>
                    <span class="spinner"></span>
                </p>
            </form>
        </div>
    </div>
</div>