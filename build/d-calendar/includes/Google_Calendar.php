<?php
/**
 * Google Calendar Integration Class.
 *
 * @package ReservasTerapia
 */

namespace ReservasTerapia;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class Google_Calendar
 *
 * Handles integration with Google Calendar API v3.
 * Manages OAuth 2.0 authentication, event CRUD, and availability checks.
 */
class Google_Calendar
{

    /**
     * Google Client instance.
     *
     * @var Client
     */
    private $client;

    /**
     * Google Calendar Service instance.
     *
     * @var Calendar
     */
    private $service;

    /**
     * Option key names for storing credentials.
     */
    const OPTION_ACCESS_TOKEN = 'rt_google_access_token';
    const OPTION_REFRESH_TOKEN = 'rt_google_refresh_token';
    const OPTION_CALENDAR_ID = 'rt_google_calendar_id';
    const OPTION_TOKEN_EXPIRES = 'rt_google_token_expires';

    /**
     * Constructor.
     *
     * Initializes the Google Client with credentials and configuration.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('D.Calendar Plugin');
        $this->client->setScopes(Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        // Load client credentials from settings or constants.
        // NOTE: In a real scenario, these should be retrieved from plugin settings.
        // For now, we assume they are defined as constants or retrieved via get_option.
        $client_id = get_option('rt_google_client_id', '');
        $client_secret = get_option('rt_google_client_secret', '');
        $redirect_uri = get_option('rt_google_redirect_uri', '');

        if (!empty($client_id) && !empty($client_secret) && !empty($redirect_uri)) {
            $this->client->setClientId($client_id);
            $this->client->setClientSecret($client_secret);
            $this->client->setRedirectUri($redirect_uri);
        }

        $this->initialize_token();
    }

    /**
     * Initialize and refresh the access token if needed.
     */
    private function initialize_token()
    {
        $access_token = get_option(self::OPTION_ACCESS_TOKEN);

        if ($access_token) {
            // Check if token is expired or close to expiring (less than 5 minutes)
            $expires_at = get_option(self::OPTION_TOKEN_EXPIRES);
            if ($expires_at && (time() + 300) > $expires_at) {
                $this->refresh_access_token();
            } else {
                $this->client->setAccessToken($access_token);
            }
        }

        if ($this->client->getAccessToken()) {
            $this->service = new Calendar($this->client);
        }
    }

    /**
     * Generate authorization URL for OAuth 2.0 flow.
     *
     * @return string The authorization URL.
     */
    public function get_auth_url()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access and refresh tokens.
     *
     * @param string $code The authorization code received from Google.
     * @return array|false Token array on success, false on failure.
     */
    public function authenticate($code)
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new Exception('Error fetching access token: ' . $token['error']);
            }

            $this->save_token($token);
            return $token;
        } catch (Exception $e) {
            error_log('Google Calendar Auth Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh the access token using the stored refresh token.
     *
     * @return bool True if refreshed successfully, false otherwise.
     */
    private function refresh_access_token()
    {
        $refresh_token = get_option(self::OPTION_REFRESH_TOKEN);

        if (!$refresh_token) {
            return false;
        }

        try {
            $token = $this->client->fetchAccessTokenWithRefreshToken($refresh_token);

            if (isset($token['error'])) {
                throw new Exception('Error refreshing token: ' . $token['error']);
            }

            $this->save_token($token);
            return true;
        } catch (Exception $e) {
            error_log('Google Calendar Refresh Token Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save token details to wp_options.
     *
     * @param array $token The token array from Google API.
     */
    private function save_token($token)
    {
        update_option(self::OPTION_ACCESS_TOKEN, $token);

        if (isset($token['refresh_token'])) {
            update_option(self::OPTION_REFRESH_TOKEN, $token['refresh_token']);
        }

        if (isset($token['created']) && isset($token['expires_in'])) {
            update_option(self::OPTION_TOKEN_EXPIRES, $token['created'] + $token['expires_in']);
        }
    }

    /**
     * Disconnect and clear stored tokens.
     */
    public function disconnect()
    {
        $this->client->revokeToken();
        delete_option(self::OPTION_ACCESS_TOKEN);
        delete_option(self::OPTION_REFRESH_TOKEN);
        delete_option(self::OPTION_TOKEN_EXPIRES);
        delete_option(self::OPTION_CALENDAR_ID);
    }

    /**
     * Helper to get the Calendar ID.
     *
     * @return string
     */
    private function get_calendar_id()
    {
        return get_option(self::OPTION_CALENDAR_ID, 'primary');
    }

    /**
     * Check availability for a specific time range.
     *
     * @param string $start_time Start time (ISO 8601 or Y-m-d H:i:s).
     * @param string $end_time   End time (ISO 8601 or Y-m-d H:i:s).
     * @param string $timezone   Timezone string.
     * @return array Array of busy periods [{start, end}, ...].
     */
    public function check_availability($start_time, $end_time, $timezone)
    {
        if (!$this->service) {
            return [];
        }

        $start_dt = new DateTime($start_time, new DateTimeZone($timezone));
        $end_dt = new DateTime($end_time, new DateTimeZone($timezone));

        // Use FreeBusy query for better performance on checking availability
        $freebusy_req = new \Google\Service\Calendar\FreeBusyRequest();
        $freebusy_req->setTimeMin($start_dt->format(DateTime::ATOM));
        $freebusy_req->setTimeMax($end_dt->format(DateTime::ATOM));
        $freebusy_req->setTimeZone($timezone);

        $item = new \Google\Service\Calendar\FreeBusyRequestItem();
        $item->setId($this->get_calendar_id());
        $freebusy_req->setItems([$item]);

        try {
            $results = $this->service->freebusy->query($freebusy_req);
            $calendars = $results->getCalendars();
            $calendar_data = $calendars[$this->get_calendar_id()];
            $busy = $calendar_data->getBusy();

            $busy_slots = [];
            foreach ($busy as $slot) {
                $busy_slots[] = [
                    'start' => $slot->getStart(),
                    'end' => $slot->getEnd(),
                ];
            }
            return $busy_slots;

        } catch (Exception $e) {
            error_log('Google Calendar Check Availability Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new event in Google Calendar.
     *
     * @param array $event_data Event data: summary, description, start, end, attendees.
     * @return string|false Event ID on success, false on failure.
     */
    public function create_event($event_data)
    {
        if (!$this->service) {
            return false;
        }

        $event = new Event([
            'summary' => $event_data['summary'],
            'description' => isset($event_data['description']) ? $event_data['description'] : '',
            'start' => [
                'dateTime' => $event_data['start'],
                'timeZone' => isset($event_data['timezone']) ? $event_data['timezone'] : 'UTC',
            ],
            'end' => [
                'dateTime' => $event_data['end'],
                'timeZone' => isset($event_data['timezone']) ? $event_data['timezone'] : 'UTC',
            ],
        ]);

        if (!empty($event_data['attendees'])) {
            $attendees = array_map(function ($email) {
                return ['email' => $email];
            }, $event_data['attendees']);
            $event->setAttendees($attendees);
        }

        if (!empty($event_data['conferenceData'])) {
            // Configuration for Google Meet if needed
            $conferenceReq = new \Google\Service\Calendar\ConferenceDataRequest();
            $conferenceReq->setRequestId(uniqid());
            $event->setConferenceData($conferenceReq);
        }

        try {
            $createdEvent = $this->service->events->insert($this->get_calendar_id(), $event, ['conferenceDataVersion' => 1]);
            return $createdEvent->getId();
        } catch (Exception $e) {
            error_log('Google Calendar Create Event Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing event.
     *
     * @param string $event_id   Google Event ID.
     * @param array  $event_data New event data to update.
     * @return bool True on success, false on failure.
     */
    public function update_event($event_id, $event_data)
    {
        if (!$this->service || !$event_id) {
            return false;
        }

        try {
            $event = $this->service->events->get($this->get_calendar_id(), $event_id);

            if (isset($event_data['summary'])) {
                $event->setSummary($event_data['summary']);
            }
            if (isset($event_data['description'])) {
                $event->setDescription($event_data['description']);
            }
            if (isset($event_data['start'])) {
                $start = new EventDateTime();
                $start->setDateTime($event_data['start']);
                $start->setTimeZone(isset($event_data['timezone']) ? $event_data['timezone'] : 'UTC');
                $event->setStart($start);
            }
            if (isset($event_data['end'])) {
                $end = new EventDateTime();
                $end->setDateTime($event_data['end']);
                $end->setTimeZone(isset($event_data['timezone']) ? $event_data['timezone'] : 'UTC');
                $event->setEnd($end);
            }

            // Add logic for attendees/conferenceData update if needed.

            $this->service->events->update($this->get_calendar_id(), $event_id, $event);
            return true;
        } catch (Exception $e) {
            error_log('Google Calendar Update Event Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an event.
     *
     * @param string $event_id Google Event ID.
     * @return bool True on success, false on failure.
     */
    public function delete_event($event_id)
    {
        if (!$this->service || !$event_id) {
            return false;
        }

        try {
            $this->service->events->delete($this->get_calendar_id(), $event_id);
            return true;
        } catch (Exception $e) {
            error_log('Google Calendar Delete Event Error: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Sync availability combining Google Calendar events with configured schedules.
     *
     * Note: This method currently focuses on retrieving Google Calendar busy slots.
     * In a full implementation, it should be combined with local reservation data
     * and business hour configurations.
     *
     * @param string $date     Date to sync (Y-m-d).
     * @param string $timezone Timezone string.
     * @return array Available slots [{start, end, available}, ...].
     */
    /**
     * Sync availability combining Google Calendar events, local reservations, and business hours.
     *
     * @param string $date       Date to sync (Y-m-d).
     * @param string $timezone   Timezone string.
     * @param int    $service_id Optional. Service ID to apply specific buffers.
     * @return array Available slots structure.
     */
    public function sync_availability($date, $timezone, $service_id = null)
    {
        // 1. Caching
        $cache_key = "rt_availability_{$date}_{$timezone}_" . ($service_id ? $service_id : 'all');
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        try {
            // Validate Timezone
            try {
                $tz = new DateTimeZone($timezone);
            } catch (Exception $e) {
                $timezone = wp_timezone_string();
                $tz = new DateTimeZone($timezone);
            }

            $date_obj = new DateTime($date, $tz);

            // 2. Get Business Hours (Configuration)
            $hours = $this->get_business_hours($date_obj);

            if (empty($hours)) {
                $result = [
                    'date' => $date,
                    'timezone' => $timezone,
                    'slots' => []
                ];
                set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
                return $result;
            }

            // 3. Generate Slots
            $slots = $this->generate_time_slots($date, $hours['hora_inicio'], $hours['hora_fin'], $hours['duracion_slot_minutos'], $timezone);

            // 4. Get Google Busy Events
            $google_busy = [];
            try {
                // Determine start/end of day in the requested timezone
                $start_of_day = clone $date_obj;
                $start_of_day->setTime(0, 0, 0);
                $end_of_day = clone $date_obj;
                $end_of_day->setTime(23, 59, 59);

                $google_start = $start_of_day->format(DateTime::ATOM);
                $google_end = $end_of_day->format(DateTime::ATOM);

                $google_busy = $this->check_availability($google_start, $google_end, $timezone);
            } catch (Exception $e) {
                error_log('Google Sync Failed in sync_availability: ' . $e->getMessage());
                // Continue with local bookings only
            }

            // 5. Get Local Bookings
            // Need to pass the day range (start/end of the day)
            // Re-calculate because we need them for local DB query too
            $start_of_day = clone $date_obj;
            $start_of_day->setTime(0, 0, 0);
            $end_of_day = clone $date_obj;
            $end_of_day->setTime(23, 59, 59);

            $local_busy = $this->get_local_bookings($start_of_day, $end_of_day);

            // 6. Get Buffers
            $buffers = ['before' => 0, 'after' => 0];
            if ($service_id) {
                $buffers = $this->get_service_buffers($service_id);
            }

            // 7. Calculate Availability
            $final_slots = [];
            foreach ($slots as $slot) {
                $is_available = true;
                $reason = '';

                $slot_start = $slot['start_obj'];
                $slot_end = $slot['end_obj'];

                // Check Google
                foreach ($google_busy as $busy) {
                    // Google busy slots (from my check_availability) are arrays with 'start'/'end' as strings (RFC3339)
                    $g_start = new DateTime($busy['start'], $tz);
                    $g_end = new DateTime($busy['end'], $tz);

                    if ($this->slots_overlap($slot_start, $slot_end, $g_start, $g_end, $buffers)) {
                        $is_available = false;
                        $reason = 'booked';
                        break;
                    }
                }

                // Check Local
                if ($is_available) {
                    foreach ($local_busy as $booking) {
                        // DB is UTC
                        $l_start = new DateTime($booking->fecha_hora_inicio, new DateTimeZone('UTC'));
                        $l_start->setTimezone($tz);
                        $l_end = new DateTime($booking->fecha_hora_fin, new DateTimeZone('UTC'));
                        $l_end->setTimezone($tz);

                        if ($this->slots_overlap($slot_start, $slot_end, $l_start, $l_end, $buffers)) {
                            $is_available = false;
                            $reason = 'booked';
                            break;
                        }
                    }
                }

                $slot_output = [
                    'time' => $slot_start->format('H:i'),
                    'available' => $is_available,
                    'end_time' => $slot_end->format('H:i')
                ];
                if (!$is_available) {
                    $slot_output['reason'] = $reason;
                }
                $final_slots[] = $slot_output;
            }

            $result = [
                'date' => $date,
                'timezone' => $timezone,
                'slots' => $final_slots
            ];

            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;

        } catch (Exception $e) {
            error_log('Sync Availability Fatal Error: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Get business hours for a specific date (day of week).
     *
     * @param DateTime $date_obj
     * @return array|null Array with hora_inicio, hora_fin, duracion_slot_minutos or null if inactive.
     */
    private function get_business_hours($date_obj)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rt_horarios';

        $day_of_week = $date_obj->format('w'); // 0 (Sun) - 6 (Sat)

        $sql = $wpdb->prepare(
            "SELECT hora_inicio, hora_fin, duracion_slot_minutos 
             FROM $table_name 
             WHERE dia_semana = %d AND activo = 1",
            $day_of_week
        );

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Generate theoretical time slots for a day.
     *
     * @return array List of slots with start/end objects and ISO strings.
     */
    private function generate_time_slots($date, $start_time, $end_time, $duration_minutes, $timezone)
    {
        $slots = [];
        $tz = new DateTimeZone($timezone);
        $start = new DateTime("$date $start_time", $tz);
        $end = new DateTime("$date $end_time", $tz);

        // Safety check
        if ($duration_minutes < 5)
            $duration_minutes = 30;

        $interval = new \DateInterval("PT{$duration_minutes}M");

        $current = clone $start;
        while ($current < $end) {
            $slot_end = clone $current;
            $slot_end->add($interval);

            // if slot exceeds end time of the day's schedule
            if ($slot_end > $end) {
                break;
            }

            $slots[] = [
                'start_obj' => clone $current,
                'end_obj' => clone $slot_end
            ];

            $current->add($interval);
        }

        return $slots;
    }

    /**
     * Fetch local confirmed/pending reservations.
     *
     * @param DateTime $start_dt
     * @param DateTime $end_dt
     * @return array
     */
    private function get_local_bookings($start_dt, $end_dt)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rt_reservas';

        $start_utc = clone $start_dt;
        $start_utc->setTimezone(new DateTimeZone('UTC'));
        $end_utc = clone $end_dt;
        $end_utc->setTimezone(new DateTimeZone('UTC'));

        $sql = $wpdb->prepare(
            "SELECT fecha_hora_inicio, fecha_hora_fin 
             FROM $table_name 
             WHERE fecha_hora_inicio >= %s 
             AND fecha_hora_fin <= %s 
             AND estado IN ('confirmada', 'pendiente')",
            $start_utc->format('Y-m-d H:i:s'),
            $end_utc->format('Y-m-d H:i:s')
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get buffers for a service.
     *
     * @param int $service_id
     * @return array ['before' => int, 'after' => int]
     */
    private function get_service_buffers($service_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rt_servicios';

        $sql = $wpdb->prepare(
            "SELECT buffer_antes_minutos, buffer_despues_minutos 
             FROM $table_name 
             WHERE id = %d",
            $service_id
        );

        $result = $wpdb->get_row($sql, ARRAY_A);
        if (!$result) {
            return ['before' => 0, 'after' => 0];
        }

        return [
            'before' => intval($result['buffer_antes_minutos']),
            'after' => intval($result['buffer_despues_minutos'])
        ];
    }

    /**
     * Check overlap between a slot and a busy period (with buffers).
     *
     * @param DateTime $slot_start
     * @param DateTime $slot_end
     * @param DateTime $busy_start
     * @param DateTime $busy_end
     * @param array    $buffers
     * @return bool
     */
    private function slots_overlap($slot_start, $slot_end, $busy_start, $busy_end, $buffers)
    {
        // Expand business busy entry with the buffers required by the NEW service?
        // OR assume the buffers stored in 'rt_servicios' should apply to the busy slots?
        // The requirement E says: "Añadir estos tiempos a los intervalos ocupados".
        // This implies we treat the busy interval as if it's wider.

        $b_start = clone $busy_start;
        $b_end = clone $busy_end;

        if (!empty($buffers['before'])) {
            $b_start->modify("-{$buffers['before']} minutes");
        }
        if (!empty($buffers['after'])) {
            $b_end->modify("+{$buffers['after']} minutes");
        }

        return ($slot_start < $b_end && $slot_end > $b_start);
    }
}

