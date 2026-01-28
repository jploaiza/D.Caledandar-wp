<?php
/**
 * Test script for sync_availability logic.
 * Run with: php test-availability-logic.php
 */

// 1. Mock WordPress Environment
define('MINUTE_IN_SECONDS', 60);
define('ARRAY_A', 'ARRAY_A');
define('OBJECT', 'OBJECT');

$transients = [];
function get_transient($key)
{
    global $transients;
    echo "Checking cache for $key... ";
    if (isset($transients[$key])) {
        echo "HIT\n";
        return $transients[$key];
    }
    echo "MISS\n";
    return false;
}
function set_transient($key, $value, $expiration)
{
    global $transients;
    echo "Setting cache for $key (expires in $expiration s)\n";
    $transients[$key] = $value;
}
function get_option($key, $default = false)
{
    return $default;
}
function update_option($key, $val)
{
}
function delete_option($key)
{
}
function wp_timezone_string()
{
    return 'America/Santiago';
}
function error_log($msg)
{
    echo "[ERROR] $msg\n";
}

// Mock WPDB
class MockWPDB
{
    public $prefix = 'wp_';

    public function prepare($query, ...$args)
    {
        // Embed args into query for simple string matching in get_row
        foreach ($args as $arg) {
            $query = preg_replace('/%[sd]/', $arg, $query, 1);
        }
        return $query;
    }

    public function get_row($query, $output_type = OBJECT)
    {
        echo "[DB] get_row: $query\n";

        if (strpos($query, 'rt_horarios') !== false) {
            // Mock Business Hours: Sunday (0) to Saturday (6)
            // Let's assume day_semana matches our test date 2026-02-15 
            // 2026-02-15 is a Sunday.
            // Let's return hours 09:00 - 12:00, 60m slots.
            return [
                'hora_inicio' => '09:00:00',
                'hora_fin' => '12:00:00',
                'duracion_slot_minutos' => 60
            ];
        }

        if (strpos($query, 'rt_servicios') !== false) {
            // Mock Service 1: 15min buffer before, 15min after.
            return [
                'buffer_antes_minutos' => 15,
                'buffer_despues_minutos' => 15
            ];
        }

        return null;
    }

    public function get_results($query)
    {
        echo "[DB] get_results: $query\n";

        if (strpos($query, 'rt_reservas') !== false) {
            // Mock Local Booking.
            // We want to test overlap.
            // Test Date: 2026-02-15.
            // Test Timezone: America/Santiago (-03:00).
            // Let's put a local booking at 11:00-12:00 Santiago.
            // In UTC, this is 14:00-15:00.

            // The query is converting start/end to UTC (done in get_local_bookings).
            // We return raw data as if from DB (UTC).

            return [
                (object) [
                    'fecha_hora_inicio' => '2026-02-15 14:00:00', // 11:00 Santiago
                    'fecha_hora_fin' => '2026-02-15 15:00:00',    // 12:00 Santiago
                ]
            ];
        }
        return [];
    }
}
$wpdb = new MockWPDB();

// 2. Define Dummy Google Classes to avoid fatal errors during include
namespace Google\Client {
    class Client
    {
        public function __construct()
        {
        }
        public function setApplicationName($n)
        {
        }
        public function setScopes($s)
        {
        }
        public function setAccessType($t)
        {
        }
        public function setPrompt($p)
        {
        }
        public function setClientId($i)
        {
        }
        public function setClientSecret($s)
        {
        }
        public function setRedirectUri($u)
        {
        }
        public function getAccessToken()
        {
            return null;
        }
    }
}
namespace Google\Service {
    class Calendar
    {
        const CALENDAR = 'calendar';
        public function __construct($client)
        {
        }
    }
}
namespace Google\Service\Calendar {
    class Event
    {
    }
    class EventDateTime
    {
    }
    class FreeBusyRequest
    {
    }
    class FreeBusyRequestItem
    {
    }
    class ConferenceDataRequest
    {
    }
}

// 3. Include the Class
require_once __DIR__ . '/includes/Google_Calendar.php';

use ReservasTerapia\Google_Calendar;

// 4. Test Subclass
class Test_Google_Calendar extends Google_Calendar
{
    public function __construct()
    {
        // Skip parent
    }

    // Override check_availability to mock Google API response
    public function check_availability($start, $end, $timezone)
    {
        echo "[GoogleAPI] check_availability ($start to $end) in $timezone\n";

        // Mock a busy slot at 09:00 - 10:00 Santiago (UTC 12:00 - 13:00)
        // check_availability returns array of ['start' => string, 'end' => string]
        // returning relative RFC strings.

        return [
            [
                'start' => '2026-02-15T09:00:00-03:00',
                'end' => '2026-02-15T10:00:00-03:00'
            ]
        ];
    }
}

// 5. Run Test
echo "--- Starting Availability Test ---\n";
$gc = new Test_Google_Calendar();

$date = '2026-02-15';
$timezone = 'America/Santiago';
$service_id = 1;

$result = $gc->sync_availability($date, $timezone, $service_id);

echo "\n--- Final Result ---\n";
print_r($result);

// Expected Logic:
// Hours: 09:00 - 12:00, 60min slots => [09-10, 10-11, 11-12].
// Google Busy: 09:00 - 10:00.
// Local Busy: 11:00 - 12:00.
// Service Buffers: 15m before, 15m after.
//
// Analysis per slot:
// Slot 09:00-10:00:
//   - Overlap with Google (09-10)? YES.
//   -> AVAILABLE: FALSE, reason: 'booked'.
//
// Slot 10:00-11:00:
//   - Overlap with Google (09-10)?
//     Google w/ buffers: 09-10 becomes 08:45-10:15.
//     Slot 10:00-11:00 overlaps 08:45-10:15? YES (10:00 < 10:15).
//     -> AVAILABLE: FALSE.
//   - Overlap with Local (11-12)?
//     Local w/ buffers: 11-12 becomes 10:45-12:15.
//     Slot 10:00-11:00 overlaps 10:45-12:15? YES (10:45 < 11:00).
//     -> AVAILABLE: FALSE. (Double blocked).
//
// Slot 11:00-12:00:
//   - Overlap with Local (11-12)? YES.
//   -> AVAILABLE: FALSE.
//
// Result should show all 3 slots as unavailable.
