<?php
/**
 * Database Management Class
 *
 * Handles the creation, update, and management of custom database tables
 * for the D.Calendar plugin.
 *
 * @package DCalendar
 */

namespace ReservasTerapia;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Database
 * 
 * Manages custom tables creation, versioning, and cleanup.
 */
class Database
{

    /**
     * Database version.
     *
     * @var string
     */
    const DB_VERSION = '1.0.0';

    /**
     * Option name for storing the database version.
     *
     * @var string
     */
    const DB_VERSION_OPTION = 'rt_db_version';

    /**
     * Initialize the database management.
     * 
     * Hooks into plugins_loaded to check for database updates.
     */
    public function init()
    {
        add_action('plugins_loaded', array($this, 'update_db_check'));
    }

    /**
     * Check if the database needs an update.
     * 
     * Compares the stored database version with the current class constant.
     */
    public function update_db_check()
    {
        $installed_ver = get_site_option(self::DB_VERSION_OPTION);
        if ($installed_ver !== self::DB_VERSION) {
            $this->create_tables();
        }
    }

    /**
     * Create or update the custom tables.
     * 
     * Uses dbDelta to create or modify tables based on the schema.
     */
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Table: {prefix}_rt_servicios
        // Stores service definitions.
        $table_servicios = $wpdb->prefix . 'rt_servicios';
        $sql_servicios = "CREATE TABLE $table_servicios (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			nombre varchar(255) NOT NULL,
			descripcion text,
			duracion_minutos int(11) NOT NULL,
			precio decimal(10,2) NOT NULL DEFAULT '0.00',
			buffer_antes_minutos int(11) DEFAULT 0,
			buffer_despues_minutos int(11) DEFAULT 0,
			color_calendario varchar(20),
			activo tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

        // 2. Table: {prefix}_rt_reservas
        // Stores appointment/reservation data.
        $table_reservas = $wpdb->prefix . 'rt_reservas';
        $sql_reservas = "CREATE TABLE $table_reservas (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			codigo_unico varchar(50) NOT NULL,
			servicio_id bigint(20) unsigned NOT NULL,
			cliente_nombre varchar(255) NOT NULL,
			cliente_email varchar(255) NOT NULL,
			cliente_telefono varchar(50),
			cliente_pais_codigo varchar(10),
			fecha_hora_inicio datetime NOT NULL,
			fecha_hora_fin datetime NOT NULL,
			zona_horaria varchar(100),
			estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
			zoom_meeting_id varchar(100),
			zoom_join_url text,
			google_event_id varchar(255),
			comentarios text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY codigo_unico (codigo_unico),
			KEY idx_email (cliente_email),
			KEY idx_fecha (fecha_hora_inicio),
			KEY idx_codigo (codigo_unico)
		) $charset_collate;";

        // 3. Table: {prefix}_rt_horarios
        // Stores available time slots/schedule.
        $table_horarios = $wpdb->prefix . 'rt_horarios';
        $sql_horarios = "CREATE TABLE $table_horarios (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			dia_semana tinyint(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
			hora_inicio time NOT NULL,
			hora_fin time NOT NULL,
			duracion_slot_minutos int(11) NOT NULL,
			activo tinyint(1) DEFAULT 1,
			PRIMARY KEY  (id)
		) $charset_collate;";

        // 4. Table: {prefix}_rt_bloqueos
        // Stores blocked time ranges.
        $table_bloqueos = $wpdb->prefix . 'rt_bloqueos';
        $sql_bloqueos = "CREATE TABLE $table_bloqueos (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			fecha_hora_inicio datetime NOT NULL,
			fecha_hora_fin datetime NOT NULL,
			motivo text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

        // 5. Table: {prefix}_rt_alertas_config
        // Stores configuration for notifications/alerts.
        $table_alertas = $wpdb->prefix . 'rt_alertas_config';
        $sql_alertas = "CREATE TABLE $table_alertas (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tipo varchar(50) NOT NULL,
			canal varchar(50) NOT NULL,
			tiempo_offset_horas int(11) DEFAULT 0,
			plantilla_asunto varchar(255),
			plantilla_mensaje text,
			formato varchar(20) DEFAULT 'text',
			activo tinyint(1) DEFAULT 1,
			PRIMARY KEY  (id)
		) $charset_collate;";

        // Execute dbDelta for each table.
        dbDelta($sql_servicios);
        dbDelta($sql_reservas);
        dbDelta($sql_horarios);
        dbDelta($sql_bloqueos);
        dbDelta($sql_alertas);

        // 6. Table: {prefix}_rt_mensajes_programados
        // Stores scheduled messages queue.
        $table_mensajes = $wpdb->prefix . 'rt_mensajes_programados';
        $sql_mensajes = "CREATE TABLE $table_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reserva_id bigint(20) unsigned NOT NULL,
            tipo_mensaje varchar(50) NOT NULL,
            to_number varchar(20) NOT NULL,
            message_body text NOT NULL,
            media_url text,
            fecha_hora_envio datetime NOT NULL,
            estado ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente',
            intentos int(11) DEFAULT 0,
            message_sid varchar(100),
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_fecha_envio (fecha_hora_envio),
            KEY idx_estado (estado),
            KEY idx_reserva (reserva_id)
        ) $charset_collate;";

        dbDelta($sql_mensajes);

        // 7. Table: {prefix}_rt_action_tokens
        // Stores security tokens for actions.
        $table_action_tokens = $wpdb->prefix . 'rt_action_tokens';
        $sql_action_tokens = "CREATE TABLE $table_action_tokens (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(20) NOT NULL COMMENT 'cancel, reschedule',
            token VARCHAR(64) NOT NULL COMMENT 'Token único criptográficamente seguro',
            expires_at DATETIME NOT NULL COMMENT 'Fecha de expiración',
            used TINYINT(1) DEFAULT 0 COMMENT '0=no usado, 1=usado',
            used_at DATETIME NULL COMMENT 'Cuándo se usó el token',
            created_at DATETIME NOT NULL,
            ip_address VARCHAR(45) NULL COMMENT 'IP que creó el token',
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY booking_action (booking_id, action),
            KEY expires_at (expires_at),
            KEY used (used)
        ) $charset_collate;";

        dbDelta($sql_action_tokens);

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    /**
     * Generates a secure unique code for a reservation.
     * 
     * Ensures the code is unique within the reserves table.
     * format: 12 alphanumeric characters, excluding 0, O, 1, I
     *
     * @return string Unique alphanumeric code.
     */
    public function generate_unique_code()
    {
        global $wpdb;
        $table_reservas = $wpdb->prefix . 'rt_reservas';

        // Characters allowed: A-Z, 2-9 (excluding 0, 1, O, I)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = 12;

        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }

            // check if exists
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_reservas WHERE codigo_unico = %s", $code));
        } while ($exists);

        return $code;
    }

    /**
     * Drop all plugin tables.
     * 
     * WARNING: This will delete all data. Should be used during uninstall.
     */
    public function drop_tables()
    {
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'rt_servicios',
            $wpdb->prefix . 'rt_reservas',
            $wpdb->prefix . 'rt_horarios',
            $wpdb->prefix . 'rt_bloqueos',
            $wpdb->prefix . 'rt_alertas_config',
            $wpdb->prefix . 'rt_mensajes_programados',
            $wpdb->prefix . 'rt_action_tokens'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option(self::DB_VERSION_OPTION);
    }

    /**
     * Genera un token único y temporal para una acción específica
     * 
     * @param int $booking_id ID de la reserva
     * @param string $action Tipo de acción: 'cancel' o 'reschedule'
     * @param int $expiry_hours Horas hasta expiración (default: 72 horas)
     * @return string|false Token generado o false si falla
     */
    public function generate_action_token($booking_id, $action, $expiry_hours = 72)
    {
        global $wpdb;

        // Validar booking_id
        if (!$this->booking_exists($booking_id)) {
            error_log(json_encode([
                'msg' => 'Intento de generar token para reserva inexistente',
                'context' => 'security',
                'data' => ['booking_id' => $booking_id]
            ]));
            return false;
        }

        // Validar acción
        $valid_actions = ['cancel', 'reschedule'];
        if (!in_array($action, $valid_actions, true)) {
            error_log(json_encode([
                'msg' => 'Acción inválida para token',
                'context' => 'security',
                'data' => ['action' => $action]
            ]));
            return false;
        }

        // Generar token criptográficamente seguro
        // 32 bytes = 64 caracteres en hexadecimal
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            error_log(json_encode([
                'msg' => 'Error generando token aleatorio',
                'context' => 'security',
                'data' => ['error' => $e->getMessage()]
            ]));
            return false;
        }

        // Calcular fecha de expiración
        $expires_at = date(
            'Y-m-d H:i:s',
            strtotime("+{$expiry_hours} hours")
        );

        // Obtener IP del usuario
        $ip_address = $this->get_client_ip();

        // Insertar en base de datos
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'rt_action_tokens',
            [
                'booking_id' => $booking_id,
                'action' => $action,
                'token' => $token,
                'expires_at' => $expires_at,
                'used' => 0,
                'created_at' => current_time('mysql'),
                'ip_address' => $ip_address
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if (!$inserted) {
            error_log(json_encode([
                'msg' => 'Error insertando token en base de datos',
                'context' => 'security',
                'data' => [
                    'booking_id' => $booking_id,
                    'error' => $wpdb->last_error
                ]
            ]));
            return false;
        }

        error_log(json_encode([
            'msg' => 'Token de acción generado exitosamente',
            'context' => 'security',
            'data' => [
                'booking_id' => $booking_id,
                'action' => $action,
                'expires_hours' => $expiry_hours,
                'token_preview' => substr($token, 0, 10) . '...'
            ]
        ]));

        return $token;
    }

    /**
     * Valida y consume un token de acción
     * El token solo puede usarse UNA VEZ (one-time use)
     * 
     * @param string $token Token a validar
     * @param string $action Acción que se intenta realizar
     * @return int|false ID de reserva si token válido, false si inválido
     */
    public function validate_action_token($token, $action)
    {
        global $wpdb;

        if (empty($token) || empty($action)) {
            return false;
        }

        $table = $wpdb->prefix . 'rt_action_tokens';

        // Buscar token que cumpla todas las condiciones
        $token_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, booking_id, action, used, expires_at 
                 FROM {$table} 
                 WHERE token = %s 
                 AND action = %s 
                 AND used = 0 
                 AND expires_at > NOW()
                 LIMIT 1",
                $token,
                $action
            )
        );

        if (!$token_data) {
            // Intentar determinar por qué falló
            $any_token = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT used, expires_at, action FROM {$table} WHERE token = %s",
                    $token
                )
            );

            if ($any_token) {
                if ($any_token->used == 1) {
                    error_log(json_encode([
                        'msg' => 'Intento de reusar token ya consumido',
                        'context' => 'security',
                        'data' => [
                            'token_preview' => substr($token, 0, 10) . '...',
                            'ip' => $this->get_client_ip()
                        ]
                    ]));
                } elseif (strtotime($any_token->expires_at) < time()) {
                    error_log(json_encode([
                        'msg' => 'Intento de usar token expirado',
                        'context' => 'security',
                        'data' => [
                            'token_preview' => substr($token, 0, 10) . '...',
                            'expired_at' => $any_token->expires_at
                        ]
                    ]));
                } elseif ($any_token->action !== $action) {
                    error_log(json_encode([
                        'msg' => 'Intento de usar token con acción incorrecta',
                        'context' => 'security',
                        'data' => [
                            'expected' => $action,
                            'got' => $any_token->action
                        ]
                    ]));
                }
            } else {
                error_log(json_encode([
                    'msg' => 'Token no encontrado en base de datos',
                    'context' => 'security',
                    'data' => ['token_preview' => substr($token, 0, 10) . '...']
                ]));
            }

            return false;
        }

        // Token válido - marcarlo como usado (one-time use)
        $updated = $wpdb->update(
            $table,
            [
                'used' => 1,
                'used_at' => current_time('mysql')
            ],
            ['id' => $token_data->id],
            ['%d', '%s'],
            ['%d']
        );

        if (!$updated) {
            error_log(json_encode([
                'msg' => 'Error marcando token como usado',
                'context' => 'security',
                'data' => ['token_id' => $token_data->id]
            ]));
            return false;
        }

        error_log(json_encode([
            'msg' => 'Token validado y consumido exitosamente',
            'context' => 'security',
            'data' => [
                'booking_id' => $token_data->booking_id,
                'action' => $action
            ]
        ]));

        return (int) $token_data->booking_id;
    }

    /**
     * Verifica si una reserva existe
     * 
     * @param int $booking_id
     * @return bool
     */
    private function booking_exists($booking_id)
    {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rt_reservas WHERE id = %d",
                $booking_id
            )
        );

        return $exists > 0;
    }

    /**
     * Obtiene IP del cliente considerando proxies
     * 
     * @return string
     */
    private function get_client_ip()
    {
        // X-Forwarded-For puede contener múltiples IPs
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ip_list[0]);
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Limpia tokens expirados de la base de datos
     * Debe ejecutarse periódicamente con WP Cron
     * 
     * @return int Número de tokens eliminados
     */
    public function cleanup_expired_tokens()
    {
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}rt_action_tokens 
             WHERE expires_at < NOW()"
        );

        if ($deleted > 0) {
            error_log(json_encode([
                'msg' => "Tokens expirados limpiados: {$deleted}",
                'context' => 'sistema'
            ]));
        }

        return $deleted;
    }

    /**
     * Get the full table name with prefix.
     *
     * @param string $suffix Table suffix without prefix (e.g., 'rt_servicios').
     * @return string Full table name.
     */
    public static function get_table_name($suffix)
    {
        global $wpdb;
        return $wpdb->prefix . $suffix;
    }
}
