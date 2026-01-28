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
     * Beautified: /gestionar-reserva/ABC123 (optional, but sticking to query var for now as per req)
     * Requirement: https://sitio.com/gestionar-reserva/?codigo=ABC123
     * 
     * Actually, if we want a dedicated path that maps to a query var without a physical page,
     * we can use a rewrite rule.
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
            'callback' => array($this, 'handle_cancel_booking'),
            'permission_callback' => '__return_true', // Validation inside
        ));

        register_rest_route('rt/v1', '/bookings/(?P<code>[a-zA-Z0-9]+)/reschedule', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_reschedule_booking'),
            'permission_callback' => '__return_true', // Validation inside
        ));
    }

    /**
     * Handle booking cancellation via REST API.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_cancel_booking($request)
    {
        global $wpdb;
        $code = $request->get_param('code');
        $reason = sanitize_textarea_field($request->get_param('motivo'));

        $table_reservas = $wpdb->prefix . 'rt_reservas';
        $table_servicios = $wpdb->prefix . 'rt_servicios';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.nombre as service_name FROM $table_reservas r JOIN $table_servicios s ON r.servicio_id = s.id WHERE r.codigo_unico = %s",
            $code
        ));

        if (!$booking) {
            return new \WP_REST_Response(array('success' => false, 'message' => 'Reserva no encontrada.'), 404);
        }

        if ($booking->estado === 'cancelada') {
            return new \WP_REST_Response(array('success' => false, 'message' => 'La reserva ya está cancelada.'), 400);
        }

        if (new DateTime($booking->fecha_hora_inicio) < new DateTime()) {
            return new \WP_REST_Response(array('success' => false, 'message' => 'No se puede cancelar una reserva pasada.'), 400);
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

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Reserva cancelada correctamente.',
            'code' => $code
        ), 200);
    }

    /**
     * Handle booking rescheduling via REST API.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_reschedule_booking($request)
    {
        global $wpdb;
        $code = $request->get_param('code');
        $new_date_str = $request->get_param('nueva_fecha_hora'); // ISO 8601 or Y-m-d H:i:s
        $timezone = $request->get_param('nueva_timezone');

        if (!$new_date_str) {
            return new \WP_REST_Response(array('success' => false, 'message' => 'Fecha inválida.'), 400);
        }

        $table_reservas = $wpdb->prefix . 'rt_reservas';
        $table_servicios = $wpdb->prefix . 'rt_servicios';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.duracion_minutos, s.nombre as service_name FROM $table_reservas r JOIN $table_servicios s ON r.servicio_id = s.id WHERE r.codigo_unico = %s",
            $code
        ));

        if (!$booking) {
            return new \WP_REST_Response(array('success' => false, 'message' => 'Reserva no encontrada.'), 404);
        }

        if ($booking->estado !== 'confirmada') {
            return new \WP_REST_Response(array('success' => false, 'message' => 'Solo se pueden reagendar reservas confirmadas.'), 400);
        }

        // Calculate new end time
        try {
            $start_dt = new DateTime($new_date_str, new DateTimeZone($timezone));
            $end_dt = clone $start_dt;
            $end_dt->modify('+' . $booking->duracion_minutos . ' minutes');

            if ($start_dt < new DateTime()) {
                return new \WP_REST_Response(array('success' => false, 'message' => 'La fecha debe ser futura.'), 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response(array('success' => false, 'message' => 'Formato de fecha inválido.'), 400);
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

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Reserva reagendada correctamente.',
            'code' => $code
        ), 200);
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
