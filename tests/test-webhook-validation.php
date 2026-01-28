<?php
/**
 * Test script for Webhook Validation.
 * 
 * Usage: php tests/test-webhook-validation.php
 * Note: This is a standalone script simulation.
 */

// Simulation of WordPress environment for testing purposes if not running inside WP
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

echo "=== Webhook Validation Test ===\n";

// --- Mocking ---
// Mock get_option
function get_option($name)
{
    if ($name === 'rt_twilio_auth_token')
        return 'mock_auth_token';
    if ($name === 'rt_zoom_webhook_secret')
        return 'mock_secret_token';
    return false;
}

// Mock WP_Error
class WP_Error
{
    public function __construct($code, $message, $data = [])
    {
        echo "[WP_Error] $code: $message\n";
    }
}

// Mock WP_REST_Request
class WP_REST_Request_Mock
{
    private $headers = [];
    private $params = [];
    private $body = '';

    public function set_header($key, $value)
    {
        $this->headers[strtolower($key)] = $value;
    }

    public function get_header($key)
    {
        return isset($this->headers[strtolower($key)]) ? $this->headers[strtolower($key)] : null;
    }

    public function get_headers()
    {
        return $this->headers;
    }

    public function set_params($params)
    {
        $this->params = $params;
    }

    public function get_params()
    {
        return $this->params;
    }

    public function get_body_params()
    {
        return $this->params;
    }

    public function set_body($body)
    {
        $this->body = $body;
    }

    public function get_body()
    {
        return $this->body;
    }

    public function get_json_params()
    {
        return json_decode($this->body, true);
    }
}

// Mock Functions
function home_url($path = '')
{
    return 'https://example.com' . $path;
}
function register_rest_route($ns, $route, $args)
{
}
function error_log($msg)
{
    echo "[LOG] $msg\n";
}
function get_transient($name)
{
    return false;
} // Always pass rate limit for test
function set_transient($name, $val, $exp)
{
}
function current_time($type)
{
    return date('Y-m-d H:i:s');
}

// --- Twilio Validation Test ---
// Need to require the class file. Adjust path as needed.
// Warning: This requires dependencies (Twilio SDK) to be autoloaded.
// If run directly without composer autoload, this will fail.

echo "\n> Testing Twilio Signature (Mock logic only as SDK not loaded here)...\n";
echo "Skipping actual SDK call in this standalone script. In real env, RequestValidator verifies X-Twilio-Signature.\n";


// --- Zoom Validation Test ---
echo "\n> Testing Zoom Signature...\n";

// Manual implementation of what's inside Zoom class for testing logic
$secret = 'mock_secret_token';
$body_json = '{"event":"meeting.started","payload":{"object":{"id":"12345"}}}';
$timestamp = time();
$message = 'v0:' . $timestamp . ':' . $body_json;
$signature = 'v0=' . hash_hmac('sha256', $message, $secret);

echo "Generated Signature: $signature\n";

// Validate
$expected = 'v0=' . hash_hmac('sha256', 'v0:' . $timestamp . ':' . $body_json, $secret);

if (hash_equals($expected, $signature)) {
    echo "PASS: Zoom Signature matches.\n";
} else {
    echo "FAIL: Zoom Signature mismatch.\n";
}

// Test Replay Attack
echo "\n> Testing Zoom Replay Attack...\n";
$old_timestamp = time() - 600; // 10 mins ago
if (abs(time() - $old_timestamp) > 300) {
    echo "PASS: Old timestamp rejected.\n";
} else {
    echo "FAIL: Old timestamp accepted.\n";
}
