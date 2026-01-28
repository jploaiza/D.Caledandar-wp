<?php
/**
 * Database Management Class
 *
 * Handles the creation, update, and management of custom database tables
 * for the Reservas Terapia plugin.
 *
 * @package ReservasTerapia
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

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    /**
     * Generates a secure unique code for a reservation.
     * 
     * Ensures the code is unique within the reserves table.
     *
     * @return string Unique alphanumeric code.
     */
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
            $wpdb->prefix . 'rt_mensajes_programados'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option(self::DB_VERSION_OPTION);
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
