<?php
/**
 * Email Manager Class
 *
 * Handles email notifications and template processing with secure tokens.
 *
 * @package ReservasTerapia
 */

namespace ReservasTerapia;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Email_Manager
 */
class Email_Manager
{

    /**
     * Instance of this class.
     *
     * @var \ReservasTerapia\Email_Manager
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return \ReservasTerapia\Email_Manager
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        // Initialization if needed
    }

    /**
     * Send an email using a template.
     *
     * @param string $to Recipient email.
     * @param string $subject Email subject.
     * @param string $template_html HTML template content.
     * @param array $booking_data Data to populate variables.
     * @return bool True on success, false on failure.
     */
    public function send_email($to, $subject, $template_html, $booking_data)
    {
        $message = $this->process_template_variables($template_html, $booking_data);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Procesa variables en plantilla de email
     * 
     * @param string $template Plantilla HTML con variables
     * @param array $booking_data Datos de la reserva
     * @return string HTML procesado
     */
    private function process_template_variables($template, $booking_data)
    {

        $variables = [
            '{nombre}' => $booking_data['cliente_nombre'] ?? '',
            '{email}' => $booking_data['cliente_email'] ?? '',
            '{telefono}' => $booking_data['cliente_telefono'] ?? '',
            '{servicio}' => $booking_data['servicio_nombre'] ?? '',
            '{fecha}' => $this->format_date($booking_data['fecha_hora_inicio'] ?? ''),
            '{hora}' => $this->format_time($booking_data['fecha_hora_inicio'] ?? ''),
            '{duracion}' => $booking_data['duracion_minutos'] ?? '',
            '{codigo}' => $booking_data['codigo'] ?? '',
            '{zoom_url}' => $booking_data['zoom_join_url'] ?? '',

            // URLs CON TOKENS DE SEGURIDAD
            '{link_cancelar}' => $this->generate_cancel_url(
                $booking_data['codigo'] ?? '',
                $booking_data['cancel_token'] ?? ''
            ),
            '{link_reagendar}' => $this->generate_reschedule_url(
                $booking_data['codigo'] ?? '',
                $booking_data['reschedule_token'] ?? ''
            ),

            // Link genérico de gestión (muestra ambas opciones)
            '{link_gestionar}' => $this->generate_manage_url(
                $booking_data['codigo'] ?? '',
                $booking_data['cancel_token'] ?? '',
                $booking_data['reschedule_token'] ?? ''
            )
        ];

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $template
        );
    }

    /**
     * Genera URL de cancelación con token
     * 
     * @param string $codigo Código único de reserva
     * @param string $token Token de cancelación
     * @return string URL completa
     */
    public function generate_cancel_url($codigo, $token)
    {
        return add_query_arg(
            [
                'codigo' => $codigo,
                'token' => $token
            ],
            home_url('/cancelar-reserva/')
        );
    }

    /**
     * Genera URL de reagendamiento con token
     * 
     * @param string $codigo Código único de reserva
     * @param string $token Token de reagendamiento
     * @return string URL completa
     */
    public function generate_reschedule_url($codigo, $token)
    {
        return add_query_arg(
            [
                'codigo' => $codigo,
                'token' => $token
            ],
            home_url('/reagendar-reserva/')
        );
    }

    /**
     * Genera URL genérica de gestión
     * Pasa ambos tokens para que el frontend decida
     * 
     * @param string $codigo
     * @param string $cancel_token
     * @param string $reschedule_token
     * @return string URL completa
     */
    public function generate_manage_url($codigo, $cancel_token, $reschedule_token)
    {
        return add_query_arg(
            [
                'codigo' => $codigo,
                'ct' => $cancel_token, // ct = cancel token
                'rt' => $reschedule_token // rt = reschedule token
            ],
            home_url('/gestionar-reserva/')
        );
    }

    /**
     * Format date for display.
     *
     * @param string $date_string Date string.
     * @return string Formatted date (d/m/Y).
     */
    private function format_date($date_string)
    {
        if (empty($date_string)) {
            return '';
        }
        $date = date_create($date_string);
        return date_format($date, 'd/m/Y');
    }

    /**
     * Format time for display.
     *
     * @param string $date_string Date string.
     * @return string Formatted time (H:i).
     */
    private function format_time($date_string)
    {
        if (empty($date_string)) {
            return '';
        }
        $date = date_create($date_string);
        return date_format($date, 'H:i');
    }
}
