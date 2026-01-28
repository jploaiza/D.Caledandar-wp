<?php
/**
 * Twilio WhatsApp Integration Class
 *
 * Handles sending messages, managing templates, scheduling, and processing webhooks
 * for the D.Calendar plugin.
 *
 * @package DCalendar
 */

namespace ReservasTerapia;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Twilio\Security\RequestValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Twilio_Whatsapp
 * 
 * Main class for Twilio WhatsApp integration.
 */
class Twilio_Whatsapp
{
    /**
     * Instance of this class.
     *
     * @var \ReservasTerapia\Twilio_Whatsapp
     */
    private static $instance = null;

    /**
     * Twilio Client instance.
     *
     * @var \Twilio\Rest\Client
     */
    private $twilio;

    /**
     * Twilio Sender Number (WhatsApp).
     *
     * @var string
     */
    private $from_number;

    /**
     * Constructor.
     * 
     * Initializes the Twilio client if credentials are present.
     */
    private function __construct()
    {
        $sid = get_option('rt_twilio_account_sid');
        $token = get_option('rt_twilio_auth_token');
        $this->from_number = get_option('rt_twilio_whatsapp_number');

        if ($sid && $token) {
            try {
                $this->twilio = new Client($sid, $token);
            } catch (\Exception $e) {
                error_log('Twilio Client Initialization Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the singleton instance.
     *
     * @return \ReservasTerapia\Twilio_Whatsapp
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send a WhatsApp message.
     *
     * @param string $to_number Recipient number in E.164 format (e.g., +1415...).
     * @param string $message_body Message text.
     * @param string|null $media_url Optional media URL.
     * @return string|false Message SID on success, false on failure.
     */
    public function send_message($to_number, $message_body, $media_url = null)
    {
        if (!$this->twilio || !$this->from_number) {
            error_log('Twilio not configured.');
            return false;
        }

        // Ensure "whatsapp:" prefix
        $from = (strpos($this->from_number, 'whatsapp:') === 0) ? $this->from_number : 'whatsapp:' . $this->from_number;
        $to = (strpos($to_number, 'whatsapp:') === 0) ? $to_number : 'whatsapp:' . $to_number;

        $params = [
            'from' => $from,
            'body' => $message_body
        ];

        if ($media_url) {
            $params['mediaUrl'] = [$media_url];
        }

        try {
            $message = $this->twilio->messages->create($to, $params);
            error_log("Twilio Message Sent: SID {$message->sid} to {$to}");
            return $message->sid;
        } catch (TwilioException $e) {
            error_log("Twilio Send Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process a message template.
     *
     * Replaces placeholders with actual data.
     *
     * @param string $template Template string.
     * @param array $data Data for replacement.
     * @return string Processed message.
     */
    public function process_template($template, $data)
    {
        // Generate Token URLs if tokens are present
        $extra_data = [];
        if (isset($data['codigo'])) {
            $cancel_token = $data['cancel_token'] ?? '';
            $reschedule_token = $data['reschedule_token'] ?? '';

            $extra_data['link_cancelar'] = $this->generate_cancel_url($data['codigo'], $cancel_token);
            $extra_data['link_reagendar'] = $this->generate_reschedule_url($data['codigo'], $reschedule_token);
            $extra_data['link_gestionar'] = $this->generate_manage_url($data['codigo'], $cancel_token, $reschedule_token);
        }

        $full_data = array_merge($data, $extra_data);

        foreach ($full_data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
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
                'ct' => $cancel_token,
                'rt' => $reschedule_token
            ],
            home_url('/gestionar-reserva/')
        );
    }

    /**
     * Schedule a message for later sending.
     *
     * @param int $reserva_id Reservation ID.
     * @param string $type Message type (reminder, thank_you, etc.).
     * @param int $offset_minutes Minutes offset from now or relative to booking (logic handled by caller usually, but here we expect execution time).
     * @param string $to_number Recipient number.
     * @param string $message_body Message content.
     * @return bool True on success.
     */
    public function schedule_message($reserva_id, $type, $fecha_hora_envio, $to_number, $message_body)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rt_mensajes_programados';

        $result = $wpdb->insert(
            $table,
            [
                'reserva_id' => $reserva_id,
                'tipo_mensaje' => $type,
                'to_number' => $to_number,
                'message_body' => $message_body,
                'fecha_hora_envio' => $fecha_hora_envio,
                'estado' => 'pendiente',
                'intentos' => 0
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d']
        );

        return $result !== false;
    }

    /**
     * Process the queue of scheduled messages.
     * 
     * Registered as a WP Cron hook.
     */
    public function process_scheduled_queue()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rt_mensajes_programados';

        // Get pending messages due now or in the past
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE estado = 'pendiente' AND fecha_hora_envio <= %s LIMIT 10",
                current_time('mysql')
            )
        );

        if (empty($messages)) {
            return;
        }

        foreach ($messages as $msg) {
            $sid = $this->send_message($msg->to_number, $msg->message_body, $msg->media_url);

            if ($sid) {
                $wpdb->update(
                    $table,
                    ['estado' => 'enviado', 'message_sid' => $sid, 'updated_at' => current_time('mysql')],
                    ['id' => $msg->id]
                );
            } else {
                $new_intentos = $msg->intentos + 1;
                $estado = ($new_intentos >= 3) ? 'fallido' : 'pendiente';
                // Simple backoff: retry in 5 mins * attempt
                $retry_time = date('Y-m-d H:i:s', strtotime("+" . (5 * $new_intentos) . " minutes"));

                $wpdb->update(
                    $table,
                    [
                        'estado' => $estado,
                        'intentos' => $new_intentos,
                        'fecha_hora_envio' => ($estado === 'pendiente') ? $retry_time : $msg->fecha_hora_envio,
                        'updated_at' => current_time('mysql')
                    ],
                    ['id' => $msg->id]
                );
            }
        }
    }

    /**
     * Register the REST API route for Twilio Webhooks.
     */
    public function register_routes()
    {
        register_rest_route('d-calendar/v1', '/twilio-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Validation happens via Twilio Signature if implemented, or checking params
        ));
    }

    /**
     * Handle incoming Twilio webhooks.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     * @return WP_REST_Response|WP_Error
     */
    public function handle_webhook($request)
    {
        // Rate Limiting
        if (!$this->check_rate_limit($_SERVER['REMOTE_ADDR'])) {
            return new WP_Error('rate_limit', 'Too many requests', ['status' => 429]);
        }

        // VALIDACIÓN DE FIRMA TWILIO
        $auth_token = get_option('rt_twilio_auth_token');

        if (empty($auth_token)) {
            return new WP_Error(
                'missing_config',
                'Auth token no configurado',
                ['status' => 500]
            );
        }

        // Obtener firma del header
        $twilio_signature = $request->get_header('X-Twilio-Signature');

        if (empty($twilio_signature)) {
            error_log('[Twilio-Warning] Webhook Twilio sin firma recibido IP: ' . $_SERVER['REMOTE_ADDR']);
            return new WP_Error(
                'invalid_signature',
                'Firma requerida',
                ['status' => 403]
            );
        }

        // Reconstruir URL completa del webhook
        // Nota: home_url() + ruta debe coincidir exactamente con lo configurado en Twilio
        // Podría necesitar ajustes si hay proxy o HTTPS forzado diferentemente
        $url = home_url('/wp-json/d-calendar/v1/twilio-webhook');

        // Obtener parámetros POST
        $params = $request->get_params();

        // Validar firma usando Twilio SDK
        $validator = new RequestValidator($auth_token);
        $is_valid = $validator->validate(
            $twilio_signature,
            $url,
            $params
        );

        if (!$is_valid) {
            error_log('[Twilio-Error] Firma de Twilio inválida. URL: ' . $url . ' IP: ' . $_SERVER['REMOTE_ADDR'] . ' Sig: ' . substr($twilio_signature, 0, 20) . '...');
            return new WP_Error(
                'invalid_signature',
                'Firma inválida',
                ['status' => 403]
            );
        }

        error_log('[Twilio-Info] Webhook Twilio validado correctamente');

        $params = $request->get_body_params();
        $from = isset($params['From']) ? $params['From'] : '';
        $body = isset($params['Body']) ? trim($params['Body']) : '';

        error_log("Twilio Webhook Received from $from: $body");

        if (empty($from) || empty($body)) {
            return new WP_REST_Response(['status' => 'ignored'], 200);
        }

        // Basic command processing
        $command = strtoupper(explode(' ', $body)[0]);

        switch ($command) {
            case 'CANCELAR':
                // Logic to find reservation and cancel it
                // This would require scanning reservations for this phone number
                $this->handle_cancellation_request($from);
                break;
            case 'REAGENDAR':
                $this->send_message($from, 'Para reagendar, por favor visita: ' . site_url('/reagendar'));
                break;
            case 'AYUDA':
            default:
                $this->send_message($from, "Comandos disponibles:\nCANCELAR - Cancelar tu cita\nREAGENDAR - Cambiar fecha\nAYUDA - Ver este mensaje");
                break;
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }

    /**
     * Check rate limit for a given identifier.
     *
     * @param string $identifier Unique identifier (e.g., IP address).
     * @return bool True if allowed, false if limit exceeded.
     */
    private function check_rate_limit($identifier)
    {
        $transient_key = 'rt_webhook_limit_' . md5($identifier);
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return true;
        }

        if ($attempts >= 60) { // máx 60 requests por minuto
            error_log('[Security] Rate limit excedido en webhook Twilio IP: ' . $identifier);
            return false;
        }

        set_transient($transient_key, $attempts + 1, MINUTE_IN_SECONDS);
        return true;
    }

    private function handle_cancellation_request($from_number)
    {
        // Placeholder for cancellation logic
        // TODO: Implement finding the active reservation by phone and cancelling it
        $this->send_message($from_number, 'Hemos recibido tu solicitud de cancelación. Un agente te contactará si hay problemas.');
    }
}
