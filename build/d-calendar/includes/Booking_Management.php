<?php
/**
 * Booking Management Class
 *
 * Handles the logic for managing bookings: cancellation, rescheduling,
 * unique URLs, and REST API endpoints.
 *
 * @package ReservasTerapia
 */

namespace ReservasTerapia;

use ReservasTerapia\Google_Calendar;
use ReservasTerapia\Zoom;
use ReservasTerapia\Twilio_Whatsapp;
use DateTime;
use DateTimeZone;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Booking_Management
 */
class Booking_Management
{
    /**
     * Initialize the class and register hooks.
     */
    // Init handled by Plugin Loader


    /**
     * Register rewrite rules for the booking management page.
     * URL: /gestionar-reserva/?codigo=ABC123
     */
    public function register_rewrite_rules()
    {
        add_rewrite_rule(
            '^gestionar-reserva/?$',
            'index.php?rt_manage_booking=1',
            'top'
        );
    }

    /**
     * Register custom query variables.
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function register_query_vars($vars)
    {
        $vars[] = 'rt_manage_booking';
        $vars[] = 'codigo';
        return $vars;
    }

    /**
     * Handle template redirection for the management page.
     */
    public function handle_template_redirect()
    {
        if (get_query_var('rt_manage_booking')) {
            $code = get_query_var('codigo'); // can be empty if user visits /gestionar-reserva/ directly

            // basic security headers
            nocache_headers();

            // Load the template
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/partials/manage-booking.php';

            if (file_exists($template_path)) {
                include $template_path;
                exit;
            }
        }
    }

    /**
     * Enqueue assets for the booking management page.
     */
    public function enqueue_assets()
    {
        if (get_query_var('rt_manage_booking')) {
            wp_enqueue_style(
                'rt-manage-booking',
                plugin_dir_url(dirname(__FILE__)) . 'public/css/manage-booking.css',
                array(),
                RT_VERSION
            );

            wp_enqueue_script(
                'rt-manage-booking',
                plugin_dir_url(dirname(__FILE__)) . 'public/js/manage-booking.js',
                array(),
                RT_VERSION,
                true // In footer
            );
        }
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes()
    {
        register_rest_route('rt/v1', '/bookings/(?P<code>[a-zA-Z0-9]+)/cancel', array(
            'methods' => 'POST',
            'callback' => array($this, 'cancel_booking'),
            'permission_callback' => array($this, 'validate_public_request'),
        ));

        register_rest_route('rt/v1', '/bookings/(?P<code>[a-zA-Z0-9]+)/reschedule', array(
            'methods' => 'POST',
            'callback' => array($this, 'reschedule_booking'),
            'permission_callback' => array($this, 'validate_public_request'),
        ));
    }

    /**
     * Valida que las peticiones públicas tengan autorización válida
     * Permite:
     * 1. Usuarios logueados con permisos de admin
     * 2. Tokens de acción válidos en header
     * 
     * @param WP_REST_Request $request
     * @return bool|WP_Error True si autorizado, WP_Error si no
     */
    public function validate_public_request(WP_REST_Request $request)
    {

        // Opción 1: Usuario admin logueado
        if (current_user_can('manage_options')) {
            error_log(json_encode([
                'msg' => 'Request autorizado por usuario admin',
                'context' => 'api',
                'data' => ['user_id' => get_current_user_id()]
            ]));
            return true;
        }

        // Opción 2: Token de acción válido
        $token = $request->get_header('X-Action-Token');

        if (empty($token)) {
            error_log(json_encode([
                'msg' => 'Request sin token de autorización',
                'context' => 'security',
                'data' => [
                    'endpoint' => $request->get_route(),
                    'ip' => $this->get_client_ip()
                ]
            ]));

            return new WP_Error(
                'missing_authorization',
                'Se requiere un token de autorización. Usa el link que recibiste por email.',
                ['status' => 401]
            );
        }

        // Verificar rate limiting ANTES de validar token
        // Esto previene ataques de fuerza bruta
        if (!$this->check_rate_limit($this->get_client_ip(), 'api_action')) {
            error_log(json_encode([
                'msg' => 'Rate limit excedido en endpoint público',
                'context' => 'security',
                'data' => ['ip' => $this->get_client_ip()]
            ]));

            return new WP_Error(
                'rate_limit_exceeded',
                'Demasiados intentos. Por favor espera 1 minuto antes de reintentar.',
                ['status' => 429]
            );
        }

        // Determinar acción según endpoint
        $route = $request->get_route();
        $action = '';

        if (strpos($route, '/cancel') !== false) {
            $action = 'cancel';
        } elseif (strpos($route, '/reschedule') !== false) {
            $action = 'reschedule';
        } else {
            error_log(json_encode([
                'msg' => 'Endpoint no reconocido para validación',
                'context' => 'api',
                'data' => ['route' => $route]
            ]));

            return new WP_Error(
                'invalid_endpoint',
                'Endpoint no válido',
                ['status' => 400]
            );
        }

        // Validar token con la base de datos
        $database = new Database();
        $booking_id = $database->validate_action_token($token, $action);

        if (!$booking_id) {
            // El método validate_action_token ya logeó el motivo específico

            $this->log_failed_attempt($this->get_client_ip(), 'token_validation');

            return new WP_Error(
                'invalid_token',
                'Token inválido, expirado o ya utilizado. Solicita un nuevo link de gestión.',
                ['status' => 403]
            );
        }

        // Token válido - guardar booking_id en el request para uso posterior
        $request->set_param('validated_booking_id', $booking_id);

        error_log(json_encode([
            'msg' => 'Token validado correctamente',
            'context' => 'security',
            'data' => [
                'booking_id' => $booking_id,
                'action' => $action
            ]
        ]));

        return true;
    }

    /**
     * Rate limiting por IP y tipo de acción
     * 
     * @param string $ip Dirección IP
     * @param string $type Tipo de acción
     * @return bool True si dentro del límite
     */
    private function check_rate_limit($ip, $type)
    {
        $transient_key = "rt_ratelimit_{$type}_" . md5($ip);
        $attempts = get_transient($transient_key);

        $max_attempts = 10; // 10 intentos por minuto
        $window = MINUTE_IN_SECONDS;

        if ($attempts === false) {
            set_transient($transient_key, 1, $window);
            return true;
        }

        if ($attempts >= $max_attempts) {
            return false;
        }

        set_transient($transient_key, $attempts + 1, $window);
        return true;
    }

    /**
     * Registra intentos fallidos para detectar patrones de ataque
     * 
     * @param string $ip
     * @param string $type
     */
    private function log_failed_attempt($ip, $type)
    {
        $transient_key = "rt_failed_{$type}_" . md5($ip);
        $failures = get_transient($transient_key);

        if ($failures === false) {
            set_transient($transient_key, 1, HOUR_IN_SECONDS);
            return;
        }

        $new_failures = $failures + 1;
        set_transient($transient_key, $new_failures, HOUR_IN_SECONDS);

        // Si hay muchos fallos, alertar al admin
        if ($new_failures >= 5) {
            $this->send_security_alert($ip, $type, $new_failures);
        }
    }

    /**
     * Envía alerta de seguridad al administrador
     * 
     * @param string $ip
     * @param string $type
     * @param int $attempts
     */
    private function send_security_alert($ip, $type, $attempts)
    {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = "[ALERTA SEGURIDAD] {$site_name} - Múltiples intentos fallidos";

        $message = "Se han detectado múltiples intentos fallidos de acceso a la API del plugin de Reservas.\n\n";
        $message .= "Detalles:\n";
        $message .= "- Tipo: {$type}\n";
        $message .= "- IP: {$ip}\n";
        $message .= "- Intentos fallidos: {$attempts}\n";
        $message .= "- Fecha/Hora: " . current_time('mysql') . "\n\n";
        $message .= "Acciones recomendadas:\n";
        $message .= "- Revisar logs del plugin\n";
        $message .= "- Considerar bloquear esta IP con firewall si continúan los intentos\n";
        $message .= "- Verificar que no sea un usuario legítimo con problemas\n\n";
        $message .= "Este es un mensaje automático del sistema de seguridad.\n";

        wp_mail($admin_email, $subject, $message);

        error_log(json_encode([
            'msg' => 'Alerta de seguridad enviada al administrador',
            'context' => 'security',
            'data' => ['ip' => $ip, 'attempts' => $attempts]
        ]));
    }

    /**
     * Obtiene IP del cliente
     * 
     * @return string
     */
    private function get_client_ip()
    {
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
     * Handle booking cancellation via REST API.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function cancel_booking(WP_REST_Request $request)
    {
        global $wpdb;
        $code = $request->get_param('code');
        $reason = sanitize_textarea_field($request->get_param('motivo'));

        // Obtener booking_id del token validado (no del código)
        $booking_id = $request->get_param('validated_booking_id');

        $table_reservas = $wpdb->prefix . 'rt_reservas';
        $table_servicios = $wpdb->prefix . 'rt_servicios';

        // Load booking by validated ID if set, otherwise fallback for admin (but code is required param)
        // Actually, if admin is using this, validated_booking_id won't be set by validate_public_request unless we add logic there.
        // validate_public_request returns true for admin immediately without setting param.
        // So we need to fetch booking by code regardless, then check permission.

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.nombre as service_name FROM $table_reservas r JOIN $table_servicios s ON r.servicio_id = s.id WHERE r.codigo_unico = %s",
            $code
        ));

        if (!$booking) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Reserva no encontrada.'), 404);
        }

        // If validated_booking_id is set (token auth), it MUST match the booking id
        if ($booking_id && $booking_id != $booking->id) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Token no válido para esta reserva.'), 403);
        }

        // If not admin and not token validated -> 403 (should be caught by permission_callback but double check)
        if (!$booking_id && !current_user_can('manage_options')) {
            return new WP_REST_Response(array('success' => false, 'message' => 'No autorizado.'), 403);
        }

        if ($booking->estado === 'cancelada') {
            return new WP_REST_Response(array('success' => false, 'message' => 'La reserva ya está cancelada.'), 400);
        }

        if (new DateTime($booking->fecha_hora_inicio) < new DateTime()) {
            return new WP_REST_Response(array('success' => false, 'message' => 'No se puede cancelar una reserva pasada.'), 400);
        }

        // 1. Update DB
        $wpdb->update(
            $table_reservas,
            array('estado' => 'cancelada', 'comentarios' => $booking->comentarios . "\n[Cancelada] Motivo: " . $reason),
            array('id' => $booking->id)
        );

        // 2. Google Calendar
        if (!empty($booking->google_event_id)) {
            $gc = new Google_Calendar();
            $gc->delete_event($booking->google_event_id);
        }

        // 3. Zoom
        if (!empty($booking->zoom_meeting_id)) {
            $zoom = Zoom::get_instance();
            $zoom->delete_meeting($booking->zoom_meeting_id);
        }

        // 4. Notifications
        $this->send_cancellation_notifications($booking, $reason);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Reserva cancelada correctamente.',
            'code' => $code
        ), 200);
    }

    /**
     * Handle booking rescheduling via REST API.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function reschedule_booking(WP_REST_Request $request)
    {
        global $wpdb;
        $code = $request->get_param('code');
        $new_date_str = $request->get_param('nueva_fecha_hora'); // ISO 8601 or Y-m-d H:i:s
        $timezone = $request->get_param('nueva_timezone');

        // Obtener booking_id del token validado
        $booking_id = $request->get_param('validated_booking_id');

        if (!$new_date_str) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Fecha inválida.'), 400);
        }

        $table_reservas = $wpdb->prefix . 'rt_reservas';
        $table_servicios = $wpdb->prefix . 'rt_servicios';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.duracion_minutos, s.nombre as service_name FROM $table_reservas r JOIN $table_servicios s ON r.servicio_id = s.id WHERE r.codigo_unico = %s",
            $code
        ));

        if (!$booking) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Reserva no encontrada.'), 404);
        }

        // If validated_booking_id is set (token auth), it MUST match the booking id
        if ($booking_id && $booking_id != $booking->id) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Token no válido para esta reserva.'), 403);
        }

        // If not admin and not token validated -> 403
        if (!$booking_id && !current_user_can('manage_options')) {
            return new WP_REST_Response(array('success' => false, 'message' => 'No autorizado.'), 403);
        }

        if ($booking->estado !== 'confirmada') {
            return new WP_REST_Response(array('success' => false, 'message' => 'Solo se pueden reagendar reservas confirmadas.'), 400);
        }

        // Calculate new end time
        try {
            $start_dt = new DateTime($new_date_str, new DateTimeZone($timezone));
            $end_dt = clone $start_dt;
            $end_dt->modify('+' . $booking->duracion_minutos . ' minutes');

            if ($start_dt < new DateTime()) {
                return new WP_REST_Response(array('success' => false, 'message' => 'La fecha debe ser futura.'), 400);
            }
        } catch (\Exception $e) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Formato de fecha inválido.'), 400);
        }

        // 1. Update DB
        $wpdb->update(
            $table_reservas,
            array(
                'fecha_hora_inicio' => $start_dt->format('Y-m-d H:i:s'),
                'fecha_hora_fin' => $end_dt->format('Y-m-d H:i:s'),
                'zona_horaria' => $timezone
            ),
            array('id' => $booking->id)
        );

        // 2. Zoom Update
        if (!empty($booking->zoom_meeting_id)) {
            $zoom = Zoom::get_instance();
            $zoom->update_meeting($booking->zoom_meeting_id, array(
                'start_time' => $start_dt->format('Y-m-d\TH:i:s'),
                'timezone' => $timezone
            ));
        }

        // 3. Google Calendar Update
        if (!empty($booking->google_event_id)) {
            $gc = new Google_Calendar();
            $gc->update_event($booking->google_event_id, array(
                'start' => $start_dt->format(DateTime::ATOM),
                'end' => $end_dt->format(DateTime::ATOM),
                'timezone' => $timezone
            ));
        }

        // 4. Notifications
        $this->send_reschedule_notifications($booking, $start_dt, $end_dt);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Reserva reagendada correctamente.',
            'code' => $code
        ), 200);
    }

    /**
     * Create a new booking and generate security tokens.
     *
     * @param array $data Booking data.
     * @return array|WP_Error Booking ID and Tokens or Error.
     */
    public function create_booking($data)
    {
        global $wpdb;

        // Basic validation
        if (empty($data['servicio_id']) || empty($data['fecha_hora_inicio'])) {
            return new WP_Error('missing_fields', 'Faltan campos obligatorios');
        }

        $database = new Database();
        $code = $database->generate_unique_code();

        $table = $wpdb->prefix . 'rt_reservas';

        $inserted = $wpdb->insert($table, array(
            'codigo_unico' => $code,
            'servicio_id' => $data['servicio_id'],
            'cliente_nombre' => $data['cliente_nombre'],
            'cliente_email' => $data['cliente_email'],
            'cliente_telefono' => isset($data['cliente_telefono']) ? $data['cliente_telefono'] : '',
            'fecha_hora_inicio' => $data['fecha_hora_inicio'],
            'fecha_hora_fin' => $data['fecha_hora_fin'],
            'zona_horaria' => isset($data['zona_horaria']) ? $data['zona_horaria'] : 'UTC',
            'estado' => 'pending', // Default to pending
            'created_at' => current_time('mysql')
        ));

        if (!$inserted) {
            return new WP_Error('db_error', 'Error al crear reserva: ' . $wpdb->last_error);
        }

        $booking_id = $wpdb->insert_id;

        // --- SECURITY TOKEN GENERATION (User Request Part 3) ---
        $cancel_token = $database->generate_action_token(
            $booking_id,
            'cancel',
            72 // Expira en 72 horas
        );

        $reschedule_token = $database->generate_action_token(
            $booking_id,
            'reschedule',
            72 // Expira en 72 horas
        );

        if (!$cancel_token || !$reschedule_token) {
            error_log(json_encode([
                'msg' => 'Error generando tokens de acción para reserva',
                'context' => 'seguridad',
                'data' => ['booking_id' => $booking_id]
            ]));
            // No fallar la reserva por esto, pero logear
        }

        // Send Notification (Preserved functionality)
        $this->send_confirmation_notifications($booking_id, $data, $code, $cancel_token, $reschedule_token);

        // Guardar tokens en array de respuesta para usar en notificaciones
        error_log(json_encode([
            'msg' => 'Tokens de acción generados para reserva',
            'context' => 'seguridad',
            'data' => [
                'booking_id' => $booking_id,
                'cancel_token_preview' => substr($cancel_token, 0, 10) . '...',
                'reschedule_token_preview' => substr($reschedule_token, 0, 10) . '...'
            ]
        ]));

        return array(
            'id' => $booking_id,
            'codigo' => $code,
            'cancel_token' => $cancel_token,
            'reschedule_token' => $reschedule_token
        );
    }

    /**
     * Send confirmation notifications with secure tokens.
     */
    private function send_confirmation_notifications($booking_id, $data, $code, $cancel_token, $reschedule_token)
    {
        $cancel_url = site_url("/manage-booking/?action=cancel&code={$code}&token={$cancel_token}");
        $reschedule_url = site_url("/manage-booking/?action=reschedule&code={$code}&token={$reschedule_token}");

        // Email
        $to = $data['cliente_email'];
        $subject = "Confirmación de Reserva";
        $message = "Hola {$data['cliente_nombre']},\n\nTu reserva está confirmada para el {$data['fecha_hora_inicio']}.\n\n" .
            "Para cancelar haz click aquí: $cancel_url\n" .
            "Para reagendar haz click aquí: $reschedule_url\n\nGracias.";
        wp_mail($to, $subject, $message);

        // WhatsApp (Optional if Twilio configured)
        if (!empty($data['cliente_telefono'])) {
            $twilio = Twilio_Whatsapp::get_instance();
            $msg = "Hola {$data['cliente_nombre']}, reserva confirmada {$data['fecha_hora_inicio']}. Cancelar: $cancel_url Reagendar: $reschedule_url";
            $twilio->send_message($data['cliente_telefono'], $msg);
        }
    }

    /**
     * Send cancellation notifications via Email and WhatsApp.
     */
    private function send_cancellation_notifications($booking, $reason)
    {
        $twilio = Twilio_Whatsapp::get_instance();

        // WhatsApp
        if (!empty($booking->cliente_telefono)) {
            $msg = "Hola {$booking->cliente_nombre}, tu reserva de {$booking->service_name} ha sido cancelada. Motivo: {$reason}.";
            $twilio->send_message($booking->cliente_telefono, $msg);
        }

        // Email
        $to = $booking->cliente_email;
        $subject = "Reserva Cancelada - {$booking->service_name}";
        $message = "Hola {$booking->cliente_nombre},\n\nTu reserva ha sido cancelada.\n\nServicio: {$booking->service_name}\nFecha original: {$booking->fecha_hora_inicio}\nMotivo: {$reason}\n\nSi crees que es un error, contáctanos.";
        wp_mail($to, $subject, $message);
    }

    /**
     * Send reschedule notifications.
     */
    private function send_reschedule_notifications($booking, $new_start, $new_end)
    {
        $twilio = Twilio_Whatsapp::get_instance();
        $date_str = $new_start->format('d/m/Y H:i');

        // WhatsApp
        if (!empty($booking->cliente_telefono)) {
            $msg = "Hola {$booking->cliente_nombre}, tu reserva de {$booking->service_name} ha sido reagendada para el {$date_str}. Nuevo link Zoom enviada a tu correo.";
            $twilio->send_message($booking->cliente_telefono, $msg);
        }

        // Email
        $to = $booking->cliente_email;
        $subject = "Reserva Reagendada - {$booking->service_name}";
        $message = "Hola {$booking->cliente_nombre},\n\nTu reserva se ha reagendado con éxito.\n\nServicio: {$booking->service_name}\nNueva Fecha: {$date_str}\n\nGracias.";
        wp_mail($to, $subject, $message);
    }
}
