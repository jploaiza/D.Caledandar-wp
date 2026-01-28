<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Configuración de Alertas</h1>
    <hr class="wp-header-end">

    <div class="rt-card">
        <h2 class="title">Tipos de Notificaciones</h2>
        <div class="rt-alerts-list">
            <?php
            $alerts = [
                'confirmation' => 'Confirmación de Reserva',
                'reminder' => 'Recordatorio (24h antes)',
                'thanks' => 'Agradecimiento (Post-sesión)',
                'cancellation' => 'Aviso de Cancelación',
                'rescheduled' => 'Aviso de Modificación'
            ];

            foreach ($alerts as $key => $label):
                ?>
                <div class="rt-alert-item" data-type="<?php echo esc_attr($key); ?>">
                    <div class="rt-alert-header">
                        <h3>
                            <?php echo esc_html($label); ?>
                        </h3>
                        <label class="switch">
                            <input type="checkbox" name="alerts[<?php echo $key; ?>][active]" class="rt-alert-toggle">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="rt-alert-body" style="display:none;">
                        <table class="form-table">
                            <tr>
                                <th>Canal</th>
                                <td>
                                    <label><input type="checkbox" name="alerts[<?php echo $key; ?>][channels][]"
                                            value="email"> Email</label>
                                    <br>
                                    <label><input type="checkbox" name="alerts[<?php echo $key; ?>][channels][]"
                                            value="whatsapp"> WhatsApp</label>
                                </td>
                            </tr>
                            <?php if ($key === 'reminder'): ?>
                                <tr>
                                    <th>Tiempo antes (horas)</th>
                                    <td><input type="number" name="alerts[<?php echo $key; ?>][offset]" value="24"
                                            class="small-text"></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Asunto (Email)</th>
                                <td><input type="text" name="alerts[<?php echo $key; ?>][subject]" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Mensaje / Plantilla</th>
                                <td>
                                    <p class="description">Variables disponibles: {nombre}, {email}, {servicio}, {fecha},
                                        {hora}, {link_cancelar}</p>
                                    <textarea name="alerts[<?php echo $key; ?>][message]" rows="5"
                                        class="large-text"></textarea>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button button-secondary rt-preview-alert"
                            data-alert="<?php echo $key; ?>">Vista Previa</button>
                    </div>
                    <button type="button" class="button rt-btn-expand-alert">Editar Configuración</button>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="submit">
            <button type="button" id="rt-save-alerts" class="button button-primary">Guardar Configuración de
                Alertas</button>
            <span class="spinner"></span>
        </p>
    </div>
</div>