<?php
/**
 * Manual Test Script for Zoom Integration
 *
 * Usage: php test-zoom-manual.php
 * Note: Requires WordPress environment to be loaded or manually configured.
 * Since this is a standalone script, it's best run via WP-CLI or by placing in root and visiting in browser if secured.
 * Ideally, use `wp eval-file test-zoom-manual.php`.
 */

// Load WordPress environment if running standalone (adjust path as needed)
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../wp-load.php');
}

echo "Testing Zoom Integration...\n";

if (!class_exists('ReservasTerapia\Zoom')) {
    echo "Error: Zoom class not found.\n";
    exit;
}

$zoom = ReservasTerapia\Zoom::get_instance();

// 1. Check Auth
$token = get_option('rt_zoom_access_token');
if (!$token) {
    echo "Error: No access token found. Please connect in Admin first.\n";
    exit;
}

// 2. Refresh Token Test
echo "Refreshing Token...\n";
if ($zoom->refresh_token()) {
    echo "Token refreshed successfully.\n";
} else {
    echo "Token refresh failed.\n";
}

// 3. Create Meeting
echo "Creating Meeting...\n";
$meeting_data = [
    'topic' => 'Test Meeting from Plugin',
    'start_time' => date('Y-m-d\TH:i:s', strtotime('+1 hour')),
    'duration' => 30,
    'timezone' => 'America/Santiago', // Change as needed
    'agenda' => 'Testing integration',
];

$meeting = $zoom->create_meeting($meeting_data);

if (is_wp_error($meeting)) {
    echo "Error creating meeting: " . $meeting->get_error_message() . "\n";
} else {
    echo "Meeting Created: " . $meeting['id'] . "\n";
    echo "Join URL: " . $meeting['join_url'] . "\n";

    $meeting_id = $meeting['id'];

    // 4. Update Meeting
    echo "Updating Meeting...\n";
    $update = $zoom->update_meeting($meeting_id, ['topic' => 'Updated Topic Name']);
    if (is_wp_error($update)) {
        echo "Error updating: " . $update->get_error_message() . "\n";
    } else {
        echo "Meeting Updated.\n";
    }

    // 5. Delete Meeting
    echo "Deleting Meeting...\n";
    $delete = $zoom->delete_meeting($meeting_id);
    if (is_wp_error($delete)) {
        echo "Error deleting: " . $delete->get_error_message() . "\n";
    } else {
        echo "Meeting Deleted.\n";
    }
}

echo "Done.\n";
