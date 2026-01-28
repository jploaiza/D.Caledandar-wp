<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Integraciones</h1>
    <hr class="wp-header-end">

    <div class="rt-integrations-grid">
        <!-- Google Calendar -->
        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-calendar-alt integration-icon"></span>
                <h2>Google Calendar</h2>
            </div>
            <p>Sincroniza tus reservas con Google Calendar automáticamente.</p>
            <form id="rt-integration-google">
                <div class="rt-form-group">
                    <label>Estado: <span class="status-badge status-disconnected">Desconectado</span></label>
                </div>
                <!-- Logic for OAuth would go here, simplified inputs for now -->
                <div class="rt-form-group">
                    <label>Calendar ID (Opcional)</label>
                    <input type="text" name="google_calendar_id" class="regular-text" placeholder="primary">
                </div>
                <button type="button" class="button button-primary section-btn">Conectar cuenta de Google</button>
            </form>
        </div>

        <!-- Zoom -->
        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-video-alt3 integration-icon"></span>
                <h2>Zoom</h2>
            </div>
            <p>Genera enlaces de reuniones automáticamente para servicios online.</p>
            <form id="rt-integration-zoom">
                <div class="rt-form-group">
                    <label>API Key (Client ID)</label>
                    <input type="text" name="zoom_api_key" class="regular-text">
                </div>
                <div class="rt-form-group">
                    <label>API Secret (Client Secret)</label>
                    <input type="password" name="zoom_api_secret" class="regular-text">
                </div>
                <button type="button" class="button button-primary section-btn" id="rt-save-zoom">Guardar y
                    Conectar</button>
            </form>
        </div>

        <div class="rt-card integration-card">
            <div class="integration-header">
                <span class="dashicons dashicons-smartphone integration-icon"></span>
                <h2>Twilio (WhatsApp/SMS)</h2>
            </div>
            <p>Envía notificaciones por WhatsApp o SMS.</p>
            <form method="post" action="options.php">
                <?php settings_fields($this->plugin_name); // Assuming 'reservas-terapia' is the option group used in register_setting ?>
                <div class="rt-form-group">
                    <label>Account SID</label>
                    <input type="text" name="rt_twilio_account_sid" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_twilio_account_sid')); ?>">
                </div>
                <div class="rt-form-group">
                    <label>Auth Token</label>
                    <input type="password" name="rt_twilio_auth_token" class="regular-text"
                        value="<?php echo esc_attr(get_option('rt_twilio_auth_token')); ?>">
                </div>
                <div class="rt-form-group">
                    <label>WhatsApp Number (From)</label>
                    <input type="text" name="rt_twilio_whatsapp_number" class="regular-text" placeholder="+14155238886"
                        value="<?php echo esc_attr(get_option('rt_twilio_whatsapp_number')); ?>">
                </div>
                <div class="rt-action-buttons">
                    <?php submit_button('Guardar Credenciales', 'primary', 'submit', false); ?>
                    <button type="button" class="button button-secondary" id="rt-test-twilio">Enviar Mensaje de
                        Prueba</button>
                </div>
            </form>
        </div>
    </div>
</div>