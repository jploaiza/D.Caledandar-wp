<?php
/**
 * Test script for Security Token Logic.
 * Run with: php tests/test-security.php
 */

// Mock WordPress Environment
define('MINUTE_IN_SECONDS', 60);
function current_time($type)
{
    return date('Y-m-d H:i:s');
}

// Mock WPDB
class MockWPDB
{
    public $prefix = 'wp_';
    public $last_error = '';
    public $insert_id = 123;

    public $mock_tokens = [];

    public function prepare($query, ...$args)
    {
        foreach ($args as $arg) {
            $query = preg_replace('/%[sd]/', "'$arg'", $query, 1);
        }
        return $query;
    }

    public function insert($table, $data, $format)
    {
        if (strpos($table, 'rt_action_tokens') !== false) {
            $this->mock_tokens[$data['token']] = (object) [
                'id' => 1,
                'booking_id' => $data['booking_id'],
                'action' => $data['action'],
                'token' => $data['token'],
                'expires_at' => $data['expires_at'],
                'used' => 0
            ];
            echo "[DB] Inserted token for action {$data['action']}\n";
            return 1;
        }
        return 1;
    }

    public function get_row($query)
    {
        // Simple regex to parse the mock query
        // SELECT * FROM ... WHERE token = %s AND action = %s AND used = 0 ...
        preg_match("/token = '([^']+)'/", $query, $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
            if (isset($this->mock_tokens[$token])) {
                $t = $this->mock_tokens[$token];
                // Check action
                preg_match("/action = '([^']+)'/", $query, $matchesAction);
                if ($matchesAction[1] !== $t->action)
                    return null;

                // Check used
                if ($t->used == 1)
                    return null;

                // Check expiry (very simple string comparison mock)
                if ($t->expires_at < date('Y-m-d H:i:s'))
                    return null;

                return $t;
            }
        }
        return null;
    }

    public function update($table, $data, $where)
    {
        if (isset($data['used']) && $data['used'] == 1) {
            // Find token in mock by ID? We assume single token test flows
            foreach ($this->mock_tokens as &$t) {
                if ($t->id == $where['id']) {
                    $t->used = 1;
                    echo "[DB] Marked token as used.\n";
                }
            }
        }
    }
}
$wpdb = new MockWPDB();

// Include Database Class
require_once __DIR__ . '/../includes/Database.php';

echo "--- Testing Security Token Logic ---\n";

$db = new Database();
$booking_id = 99;

// 1. Generate Token
echo "\n1. Generating Cancel Token...\n";
$token = $db->generate_action_token($booking_id, 'cancel', 24);
echo "Token generated: " . substr($token, 0, 10) . "...\n";

if (strlen($token) === 64) {
    echo "PASS: Token length is 64 hex chars.\n";
} else {
    echo "FAIL: Token length is " . strlen($token) . "\n";
}

// 2. Validate Token Correctly
echo "\n2. Validating Token (Correct)...\n";
$valid_id = $db->validate_action_token($token, 'cancel');
if ($valid_id === $booking_id) {
    echo "PASS: Token validated successfully.\n";
} else {
    echo "FAIL: Token validation failed.\n";
}

// 3. Try Reuse
echo "\n3. Testing Reuse Protection...\n";
$reuse = $db->validate_action_token($token, 'cancel');
if ($reuse === false) {
    echo "PASS: Reuse prevented (Token validation returned false).\n";
} else {
    echo "FAIL: Reuse allowed!\n";
}

// 4. Test Invalid Action
echo "\n4. Testing Invalid Action...\n";
$token2 = $db->generate_action_token($booking_id, 'reschedule', 24);
$invalid_action = $db->validate_action_token($token2, 'cancel'); // Wrong action
if ($invalid_action === false) {
    echo "PASS: Wrong action prevented.\n";
} else {
    echo "FAIL: Wrong action accepted!\n";
}

// 5. Test Expiration (Mock)
echo "\n5. Testing Expiration...\n";
$expired_token = $db->generate_action_token($booking_id, 'cancel', -1); // Expired 1 hour ago
// Manually update mock to ensure it's seen as expired? 
// generate_action_token sets 'expires_at' using date(). 
// If I pass -1 hour, date() will be in past.
// get_row mock checks date.
$expired_result = $db->validate_action_token($expired_token, 'cancel');
if ($expired_result === false) {
    echo "PASS: Expired token rejected.\n";
} else {
    echo "FAIL: Expired token accepted! (Ensure mock environment handles dates properly)\n";
}


