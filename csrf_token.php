<?php
// csrf_token.php - Generate and provide CSRF token for forms

// Include input validation functions which contains the CSRF functions
require_once 'input_validation.php';

// Set content type to JSON
header('Content-Type: application/json; charset=UTF-8');

// Generate and return CSRF token
$token = generate_csrf_token();
echo json_encode(['csrf_token' => $token]);
?>