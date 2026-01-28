<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Registro de Mensajes de WhatsApp</h1>
    <hr class="wp-header-end">

    <div class="rt-card">
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'rt_mensajes_programados';

        // Handle Filtering? (Optional for now)
        
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50");
        ?>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Para</th>
                    <th>Mensaje</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>SID / Error</th>
                    <th>Fecha Envío</th>
                    <th>Intentos</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results): ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <?php echo esc_html($row->id); ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->to_number); ?>
                            </td>
                            <td>
                                <?php echo esc_html(mb_strimwidth($row->message_body, 0, 50, '...')); ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->tipo_mensaje); ?>
                            </td>
                            <td>
                                <span class="rt-status-badge status-<?php echo esc_attr($row->estado); ?>">
                                    <?php echo esc_html(ucfirst($row->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($row->message_sid) {
                                    echo '<span class="code">' . esc_html($row->message_sid) . '</span>';
                                } elseif ($row->error_message) {
                                    echo '<span class="error-text">' . esc_html($row->error_message) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->fecha_hora_envio); ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->intentos); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No hay mensajes registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .rt-status-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .status-pendiente {
        background: #ffeeba;
        color: #856404;
    }

    .status-enviado {
        background: #d4edda;
        color: #155724;
    }

    .status-fallido {
        background: #f8d7da;
        color: #721c24;
    }

    .code {
        font-family: monospace;
        background: #eee;
        padding: 2px 4px;
        border-radius: 3px;
    }

    .error-text {
        color: #dc3545;
        font-size: 0.9em;
    }
</style>