<?php
/**
 * Test Script for Twilio WhatsApp Integration
 * Run via: php test-twilio-manual.php
 */

// 1. Simular entorno WP
define('ABSPATH', __DIR__ . '/');
define('RT_VERSION', '1.0.0');

// Mock WP Constants & Functions
if (!function_exists('add_action')) {
    function add_action($tag, $callback)
    {
    }
}
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback)
    {
    }
}
if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback)
    {
    }
}
if (!function_exists('get_option')) {
    function get_option($key)
    {
        // Return dummy credentials for testing
        if ($key === 'rt_twilio_account_sid')
            return 'AC_DUMMY_SID';
        if ($key === 'rt_twilio_auth_token')
            return 'AUTH_DUMMY_TOKEN';
        if ($key === 'rt_twilio_whatsapp_number')
            return 'whatsapp:+14155238886';
        return false;
    }
}
if (!function_exists('current_time')) {
    function current_time($type)
    {
        return date('Y-m-d H:i:s');
    }
}
if (!function_exists('register_rest_route')) {
    function register_rest_route()
    {
    }
}

// Mock WPDB
class MockWPDB
{
    public $prefix = 'wp_';
    public function get_results($query)
    {
        echo "SQL Execute: $query\n";
        return [];
    }
    public function prepare($query, ...$args)
    {
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
    public function insert($table, $data)
    {
        echo "Insert into $table: " . json_encode($data) . "\n";
        return true;
    }
    public function update($table, $data, $where)
    {
        echo "Update $table: " . json_encode($data) . " Where: " . json_encode($where) . "\n";
        return true;
    }
}
$wpdb = new MockWPDB();

// Mock Twilio Lib (since we might not have vendor/autoload here)
// But we used composer/autoload in the plugin. 
// We'll try to include local autoloader if exists, or warn.
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    echo "Warning: vendor/autoload.php not found. Twilio SDK strictly required.\n";
    // Define Mock Client if not found just to pass syntax check
    // namespace Twilio\Rest;
    // class Client { public $messages; function __construct() { $this->messages = new class { function create() { return (object)['sid' => 'SM_MOCK']; } }; } }
}

// 2. Include Classes
require_once 'includes/class-twilio-whatsapp.php';

use ReservasTerapia\Twilio_Whatsapp;

// 3. Test Initialization
echo "1. Initializing Twilio Class...\n";
$twilio = Twilio_Whatsapp::get_instance();

// 4. Test Sending (Mocked)
echo "\n2. Testing Send Message...\n";
$sid = $twilio->send_message('+1234567890', 'Hello World from Test Script');
if ($sid) {
    echo "Success! SID: $sid\n";
} else {
    echo "Failed (Expected if no real credentials/SDK)\n";
}

// 5. Test Scheduling
echo "\n3. Testing Scheduling...\n";
$twilio->schedule_message(123, 'remider', '2025-01-01 10:00:00', '+1234567890', 'Scheduled Message');

echo "\nTest Complete.\n";
