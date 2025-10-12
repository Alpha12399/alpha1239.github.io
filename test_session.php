<?php
// test_session.php - Test file for secure session management

// Include security headers
require_once 'security_headers.php';

// Include secure session management
require_once 'secure_session.php';

// Set content type to JSON
header('Content-Type: application/json; charset=UTF-8');

// Initialize secure session
init_secure_session();

// Validate session
if (!validate_session()) {
    echo json_encode(['status' => 'error', 'message' => 'Session validation failed']);
    exit;
}

// Test setting a session value
set_secure_session_value('test_key', 'test_value');

// Test getting a session value
$test_value = get_secure_session_value('test_key', 'default_value');

// Test session anomalies
$anomalies = check_session_anomalies();

// Return test results
echo json_encode([
    'status' => 'success',
    'message' => 'Secure session test completed',
    'test_value' => $test_value,
    'anomalies_detected' => $anomalies,
    'session_id' => session_id()
]);
?>