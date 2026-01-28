<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Configuración de Horarios</h1>
    <hr class="wp-header-end">

    <div class="rt-card rt-schedule-container">
        <form id="rt-schedule-form">
            <table class="form-table rt-schedule-table">
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Habilitado</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Duración Slot (min)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    $day_keys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

                    foreach ($days as $index => $day):
                        $key = $day_keys[$index];
                        ?>
                        <tr class="rt-schedule-row" data-day="<?php echo esc_attr($key); ?>">
                            <td><strong>
                                    <?php echo esc_html($day); ?>
                                </strong></td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="schedule[<?php echo $key; ?>][active]" value="1"
                                        class="rt-day-toggle">
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td>
                                <input type="time" name="schedule[<?php echo $key; ?>][start]" class="rt-time-start"
                                    disabled>
                            </td>
                            <td>
                                <input type="time" name="schedule[<?php echo $key; ?>][end]" class="rt-time-end" disabled>
                            </td>
                            <td>
                                <select name="schedule[<?php echo $key; ?>][slot_duration]" class="rt-slot-duration"
                                    disabled>
                                    <option value="15">15 min</option>
                                    <option value="30" selected>30 min</option>
                                    <option value="45">45 min</option>
                                    <option value="60">60 min</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="rt-schedule-preview">
                <h3>Vista Previa de Slots</h3>
                <p class="description">Selecciona un día para ver cómo se generarán los espacios disponibles.</p>
                <div id="rt-slots-preview-container"></div>
            </div>

            <p class="submit">
                <button type="submit" id="rt-save-schedule" class="button button-primary">Guardar Horarios</button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>
</div>