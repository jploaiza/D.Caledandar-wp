<?php
/**
 * Manual Test Script for Google Calendar Integration
 * 
 * Usage: php test-google-calendar-manual.php
 * 
 * Note: This script mocks WordPress functions if they are not available.
 * Ensure you have the 'vendor' directory with Google API Client installed.
 */

// Mock WordPress functions if running outside WP
if (!function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        // Replace with actual credentials for local testing if needed
        $options = [
            'rt_google_client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
            'rt_google_client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
            'rt_google_redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: '',
            'rt_google_access_token' => json_decode(file_get_contents('token_store.json'), true) ?? null,
        ];
        return isset($options[$name]) ? $options[$name] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($name, $value)
    {
        echo "[WP Mock] Updating option '$name'\n";
        if ($name === 'rt_google_access_token') {
            file_put_contents('token_store.json', json_encode($value));
        }
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($name)
    {
        echo "[WP Mock] Deleting option '$name'\n";
        return true;
    }
}

if (!function_exists('error_log')) {
    function error_log($message)
    {
        echo "[Error Log] $message\n";
    }
}

// Load Composer Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load Class
require_once __DIR__ . '/includes/class-google-calendar.php';

use ReservasTerapia\Google_Calendar;

echo "Use 'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET' env vars for config.\n";
echo "Initializing Google Calendar Integration...\n";

$gc = new Google_Calendar();

// 1. Get Auth URL
echo "\n1. Auth URL:\n";
echo $gc->get_auth_url() . "\n";
echo "\nIf you have a code, paste it here (or press Enter to skip auth step): ";
$handle = fopen("php://stdin", "r");
$code = trim(fgets($handle));

if (!empty($code)) {
    echo "Authenticating...\n";
    $token = $gc->authenticate($code);
    if ($token) {
        echo "Authentication Successful!\n";
        print_r($token);
    } else {
        echo "Authentication Failed.\n";
    }
}

// 2. Check Availability
echo "\n2. Checking Availability for tomorrow...\n";
$tomorrow = new DateTime('tomorrow');
$availability = $gc->check_availability(
    $tomorrow->format('Y-m-d') . ' 00:00:00',
    $tomorrow->format('Y-m-d') . ' 23:59:59',
    'America/Santiago' // Adjust as needed
);
print_r($availability);

// 3. Create Test Event
echo "\n3. Create Test Event? (y/n): ";
$create = trim(fgets($handle));
if (strtolower($create) === 'y') {
    $event_id = $gc->create_event([
        'summary' => 'Test Event from Plugin',
        'description' => 'Created via manual test script',
        'start' => $tomorrow->modify('+10 hours')->format(DateTime::ATOM),
        'end' => $tomorrow->modify('+1 hour')->format(DateTime::ATOM),
        'timezone' => 'America/Santiago',
    ]);

    if ($event_id) {
        echo "Event Created! ID: $event_id\n";

        // 4. Update Event
        echo "Updating Event...\n";
        $gc->update_event($event_id, ['summary' => 'Updated Test Event']);
        echo "Event Updated.\n";

        // 5. Delete Event
        echo "Delete Event? (y/n): ";
        $delete = trim(fgets($handle));
        if (strtolower($delete) === 'y') {
            $gc->delete_event($event_id);
            echo "Event Deleted.\n";
        }
    } else {
        echo "Failed to create event.\n";
    }
}

echo "\nTest Complete.\n";
