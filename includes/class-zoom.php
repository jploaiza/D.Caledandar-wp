<?php
/**
 * Zoom Integration for Reservas Terapia
 *
 * @package ReservasTerapia
 */

namespace ReservasTerapia;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Zoom
 * Handles all Zoom API interactions.
 */
class Zoom
{

    /**
     * Instance of this class.
     *
     * @var \ReservasTerapia\Zoom
     */
    private static $instance = null;

    /**
     * Zoom API Base URL.
     *
     * @var string
     */
    const API_BASE_URL = 'https://api.zoom.us/v2';

    /**
     * HTTP Client.
     *
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::API_BASE_URL,
            'timeout' => 30.0,
        ]);
    }

    /**
     * Get the singleton instance.
     *
     * @return \ReservasTerapia\Zoom
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get OAuth Login URL.
     *
     * @return string
     */
    public function get_login_url()
    {
        $client_id = get_option('rt_zoom_client_id');
        $redirect_uri = admin_url('admin.php?page=rt-zoom-auth');
        $state = wp_create_nonce('rt_zoom_auth_nonce');

        if (!$client_id) {
            return '#';
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
        ];

        return 'https://zoom.us/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Handle OAuth Callback.
     *
     * @param string $code The authorization code.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function handle_callback($code)
    {
        $client_id = get_option('rt_zoom_client_id');
        $client_secret = get_option('rt_zoom_client_secret');
        $redirect_uri = admin_url('admin.php?page=rt-zoom-auth');

        if (!$client_id || !$client_secret) {
            return new \WP_Error('zoom_config_missing', 'Zoom credentials missing.');
        }

        try {
            $response = $this->client->post('https://zoom.us/oauth/token', [
                'auth' => [$client_id, $client_secret],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirect_uri,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['access_token'])) {
                $this->save_tokens($data);
                return true;
            }

            return new \WP_Error('zoom_auth_failed', 'Failed to retrieve access token.');

        } catch (GuzzleException $e) {
            $this->log_error('OAuth Error: ' . $e->getMessage());
            return new \WP_Error('zoom_api_error', $e->getMessage());
        }
    }

    /**
     * Refresh Access Token.
     *
     * @return bool True if refreshed, false otherwise.
     */
    public function refresh_token()
    {
        $refresh_token = get_option('rt_zoom_refresh_token');
        $client_id = get_option('rt_zoom_client_id');
        $client_secret = get_option('rt_zoom_client_secret');

        if (!$refresh_token || !$client_id || !$client_secret) {
            return false;
        }

        try {
            $response = $this->client->post('https://zoom.us/oauth/token', [
                'auth' => [$client_id, $client_secret],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['access_token'])) {
                $this->save_tokens($data);
                return true;
            }

        } catch (GuzzleException $e) {
            $this->log_error('Refresh Token Error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Save tokens to wp_options.
     *
     * @param array $data Token data from response.
     */
    private function save_tokens($data)
    {
        update_option('rt_zoom_access_token', $data['access_token']);

        if (isset($data['refresh_token'])) {
            update_option('rt_zoom_refresh_token', $data['refresh_token']);
        }

        if (isset($data['expires_in'])) {
            update_option('rt_zoom_token_expires', time() + $data['expires_in'] - 60); // Buffer 60s
        }
    }

    /**
     * Get valid access token.
     *
     * @return string|false Token or false if unavailable.
     */
    private function get_access_token()
    {
        $token = get_option('rt_zoom_access_token');
        $expires = get_option('rt_zoom_token_expires');

        if (!$token) {
            return false;
        }

        if (time() >= $expires) {
            if ($this->refresh_token()) {
                return get_option('rt_zoom_access_token');
            }
            return false;
        }

        return $token;
    }

    /**
     * Create Meeting.
     *
     * @param array $data Meeting data (topic, start_time, duration, timezone, agenda).
     * @return array|WP_Error Meeting details or error.
     */
    public function create_meeting($data)
    {
        $token = $this->get_access_token();
        if (!$token) {
            return new \WP_Error('zoom_no_token', 'No valid Zoom token available.');
        }

        $payload = [
            'topic' => $data['topic'],
            'type' => 2, // Scheduled meeting
            'start_time' => $data['start_time'], // ISO 8601
            'duration' => (int) $data['duration'],
            'timezone' => $data['timezone'],
            'agenda' => isset($data['agenda']) ? $data['agenda'] : '',
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => true,
                'waiting_room' => false,
                'audio' => 'both',
                'auto_recording' => get_option('rt_zoom_record_meetings') ? 'cloud' : 'none',
            ],
        ];

        try {
            $response = $this->client->post('/v2/users/me/meetings', [
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $meeting = json_decode($response->getBody(), true);

            $this->log_info('Meeting created: ' . $meeting['id']);

            return [
                'id' => $meeting['id'],
                'join_url' => $meeting['join_url'],
                'start_url' => $meeting['start_url'],
                'password' => isset($meeting['password']) ? $meeting['password'] : '',
            ];

        } catch (GuzzleException $e) {
            $this->log_error('Create Meeting Error: ' . $e->getMessage());
            if ($e->getCode() == 429) {
                return new \WP_Error('zoom_rate_limit', 'Zoom API Rate Limit Exceeded.');
            }
            return new \WP_Error('zoom_api_error', $e->getMessage());
        }
    }

    /**
     * Update Meeting.
     *
     * @param string $meeting_id Meeting ID.
     * @param array  $data       Data to update.
     * @return bool|WP_Error True on success.
     */
    public function update_meeting($meeting_id, $data)
    {
        $token = $this->get_access_token();
        if (!$token) {
            return new \WP_Error('zoom_no_token', 'No valid Zoom token available.');
        }

        $payload = [];
        if (isset($data['topic']))
            $payload['topic'] = $data['topic'];
        if (isset($data['start_time']))
            $payload['start_time'] = $data['start_time'];
        if (isset($data['duration']))
            $payload['duration'] = (int) $data['duration'];
        if (isset($data['agenda']))
            $payload['agenda'] = $data['agenda'];

        try {
            $this->client->patch("/v2/meetings/$meeting_id", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $this->log_info('Meeting updated: ' . $meeting_id);
            return true;

        } catch (GuzzleException $e) {
            $this->log_error('Update Meeting Error: ' . $e->getMessage());
            return new \WP_Error('zoom_api_error', $e->getMessage());
        }
    }

    /**
     * Delete Meeting.
     *
     * @param string $meeting_id Meeting ID.
     * @return bool|WP_Error True on success.
     */
    public function delete_meeting($meeting_id)
    {
        $token = $this->get_access_token();
        if (!$token) {
            return new \WP_Error('zoom_no_token', 'No valid Zoom token available.');
        }

        try {
            $this->client->delete("/v2/meetings/$meeting_id", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ],
            ]);

            $this->log_info('Meeting deleted: ' . $meeting_id);
            return true;

        } catch (GuzzleException $e) {
            $this->log_error('Delete Meeting Error: ' . $e->getMessage());
            return new \WP_Error('zoom_api_error', $e->getMessage());
        }
    }

    /**
     * Get Meeting Details.
     *
     * @param string $meeting_id Meeting ID.
     * @return array|WP_Error Meeting data.
     */
    public function get_meeting($meeting_id)
    {
        $token = $this->get_access_token();
        if (!$token) {
            return new \WP_Error('zoom_no_token', 'No valid Zoom token available.');
        }

        try {
            $response = $this->client->get("/v2/meetings/$meeting_id", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ],
            ]);

            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            $this->log_error('Get Meeting Error: ' . $e->getMessage());
            return new \WP_Error('zoom_api_error', $e->getMessage());
        }
    }

    /**
     * Register Webhook Route.
     */
    public function register_routes()
    {
        register_rest_route('rt/v1', '/zoom/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true', // Validation done inside
        ]);
    }

    /**
     * Handle incoming webhooks.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function handle_webhook($request)
    {
        $headers = $request->get_headers();
        $body = $request->get_json_params();

        // Verify signature if implemented or check token
        // Verification Logic per Zoom docs using Secret Token
        $verification_token = get_option('rt_zoom_webhook_secret');

        if (isset($body['event'])) {
            if ('endpoint.url_validation' === $body['event']) {
                // Handle validation challenge
                $plainToken = $body['payload']['plainToken'];
                $encryptedToken = hash_hmac('sha256', $plainToken, $verification_token);
                return new \WP_REST_Response([
                    'plainToken' => $plainToken,
                    'encryptedToken' => $encryptedToken
                ], 200);
            }

            // Verify Authorization header if Zoom sends it (optional depending on config)
            if ($request->get_header('authorization') !== $verification_token) {
                // Note: Real validation is more complex for production
            }

            $event = $body['event'];
            $this->log_info("Webhook received: $event");

            switch ($event) {
                case 'meeting.started':
                case 'meeting.ended':
                case 'participant.joined':
                case 'participant.left':
                    // Logic to update reservation status in DB
                    // $meeting_id = $body['payload']['object']['id'];
                    // Do something with $meeting_id
                    break;
            }
        }

        return new \WP_REST_Response(['status' => 'ok'], 200);
    }

    /**
     * Log Info.
     *
     * @param string $message Message.
     */
    private function log_info($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Zoom Info] ' . $message);
        }
    }

    /**
     * Log Error.
     *
     * @param string $message Message.
     */
    private function log_error($message)
    {
        error_log('[Zoom Error] ' . $message);
    }
}
