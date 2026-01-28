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
        $this->client->setApplicationName('Reservas Terapia Plugin');
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
    public function sync_availability($date, $timezone)
    {
        $start_time = $date . ' 00:00:00';
        $end_time = $date . ' 23:59:59';

        // Get Google Calendar busy slots
        $google_busy = $this->check_availability($start_time, $end_time, $timezone);

        // Placeholder for configured schedules (e.g., 9:00 - 17:00)
        // In production, fetch this from plugin settings.
        $business_hours = [
            'start' => '09:00',
            'end' => '17:00',
        ];

        // Placeholder for local reservations
        // In production, fetch from WP database.
        $local_reservations = [];

        $available_slots = [];

        // Logic to calculate availability:
        // 1. Generate all potential slots within business hours.
        // 2. Remove slots colliding with $google_busy.
        // 3. Remove slots colliding with $local_reservations.
        // 4. Return remaining slots.

        // Simplified example return for the integration:
        return [
            'date' => $date,
            'google_busy' => $google_busy,
            'business_hours' => $business_hours,
            'message' => 'Logic to merge slots to be implemented with Reservation/Settings classes.'
        ];
    }
}

