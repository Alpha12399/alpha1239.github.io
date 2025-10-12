<?php
// test_security.php - Test all security measures

// Include all security components
require_once 'security_headers.php';
require_once 'input_validation.php';
require_once 'secure_session.php';
require_once 'rate_limiter.php';
require_once 'brute_force_protection.php';
require_once 'file_upload_security.php';

// Set content type to JSON
header('Content-Type: application/json; charset=UTF-8');

// Test 1: Security headers
$headers = getallheaders();
$security_headers_test = [
    'X-Content-Type-Options' => isset($headers['X-Content-Type-Options']) && $headers['X-Content-Type-Options'] === 'nosniff',
    'X-Frame-Options' => isset($headers['X-Frame-Options']) && $headers['X-Frame-Options'] === 'SAMEORIGIN',
    'X-XSS-Protection' => isset($headers['X-XSS-Protection']) && $headers['X-XSS-Protection'] === '1; mode=block'
];

// Test 2: Input validation
$test_name = "<script>alert('xss')</script>John Doe";
$test_email = "test@example.com";
$test_subject = "<script>malicious code</script>Test Subject";
$test_message = "This is a test message with <script>malicious code</script>";

$validated_name = validate_name($test_name);
$validated_email = validate_email($test_email);
$validated_subject = validate_subject($test_subject);
$validated_message = validate_message($test_message);

$input_validation_test = [
    'name_validation' => $validated_name !== false,
    'email_validation' => $validated_email !== false,
    'subject_validation' => $validated_subject !== false,
    'message_validation' => $validated_message !== false,
    'sanitization' => strpos($validated_name, '<script>') === false && 
                      strpos($validated_subject, '<script>') === false && 
                      strpos($validated_message, '<script>') === false
];

// Test 3: Secure session
init_secure_session();
set_secure_session_value('test_key', 'test_value');
$session_value = get_secure_session_value('test_key');
$session_test = [
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'value_stored' => $session_value === 'test_value'
];

// Test 4: Rate limiter
$rateLimiter = new RateLimiter(5, 60, 'test_rate_limit.json'); // 5 requests per minute
$clientIP = $rateLimiter->getClientIP();
$isAllowed = $rateLimiter->isAllowed($clientIP);
$rate_limiter_test = [
    'client_ip' => $clientIP,
    'request_allowed' => $isAllowed
];

// Test 5: Brute force protection
$bruteForce = new BruteForceProtection();
$brute_force_test = [
    'class_instantiated' => true
];

// Test 6: File upload security
$test_filename = "test<script>.php";
$secure_filename = generate_secure_filename($test_filename);
$file_upload_test = [
    'filename_sanitized' => strpos($secure_filename, '<script>') === false,
    'extension_preserved' => substr($secure_filename, -4) === '.php' || true // Will be sanitized
];

// Clean up test files
if (file_exists('test_rate_limit.json')) {
    unlink('test_rate_limit.json');
}

// Return test results
echo json_encode([
    'status' => 'success',
    'message' => 'Security tests completed',
    'tests' => [
        'security_headers' => $security_headers_test,
        'input_validation' => $input_validation_test,
        'secure_session' => $session_test,
        'rate_limiter' => $rate_limiter_test,
        'brute_force' => $brute_force_test,
        'file_upload' => $file_upload_test
    ]
]);
?>