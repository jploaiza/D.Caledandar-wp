<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Ajustes Generales</h1>
    <hr class="wp-header-end">

    <div class="rt-card">
        <form method="post" action="options.php">
            <?php
            // This would normally use settings_fields() and do_settings_sections()
            // creating a manual form for now to match the style request consistency
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rt_currency">Moneda</label></th>
                    <td>
                        <select name="rt_currency" id="rt_currency">
                            <option value="EUR">Euro (€)</option>
                            <option value="USD">Dólar ($)</option>
                            <option value="GBP">Libra (£)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rt_date_format">Formato de Fecha</label></th>
                    <td>
                        <select name="rt_date_format" id="rt_date_format">
                            <option value="d/m/Y">DD/MM/YYYY</option>
                            <option value="Y-m-d">YYYY-MM-DD</option>
                            <option value="m/d/Y">MM/DD/YYYY</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rt_slot_step">Intervalo de Slots (Minutos)</label></th>
                    <td>
                        <input type="number" name="rt_default_slot_step" value="30" class="small-text">
                        <p class="description">Intervalo base para visualizar el calendario.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Limpiar Datos</th>
                    <td>
                        <label>
                            <input type="checkbox" name="rt_delete_on_uninstall" value="1">
                            Borrar todos los datos al desinstalar el plugin
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Guardar Cambios</button>
            </p>
        </form>
    </div>
</div>