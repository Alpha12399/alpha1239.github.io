<?php
// Input validation and sanitization functions

// Include secure session management
require_once 'secure_session.php';

/**
 * Sanitize user input to prevent XSS attacks
 * @param string $input User input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    // Remove whitespace from beginning and end
    $input = trim($input);
    
    // Remove backslashes
    $input = stripslashes($input);
    
    // Convert special characters to HTML entities
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate and sanitize name
 * @param string $name Name to validate
 * @return string|bool Sanitized name or false if invalid
 */
function validate_name($name) {
    // Remove any HTML tags
    $name = strip_tags($name);
    
    // Remove extra whitespace
    $name = trim($name);
    
    // Check if name is not empty and not too long
    if (empty($name) || strlen($name) > 100) {
        return false;
    }
    
    // Allow only letters, spaces, hyphens, and apostrophes
    if (!preg_match("/^[a-zA-Z\s\-'\p{L}]+$/u", $name)) {
        return false;
    }
    
    return $name;
}

/**
 * Validate and sanitize subject
 * @param string $subject Subject to validate
 * @return string|bool Sanitized subject or false if invalid
 */
function validate_subject($subject) {
    // Remove any HTML tags
    $subject = strip_tags($subject);
    
    // Remove extra whitespace
    $subject = trim($subject);
    
    // Check if subject is not empty and not too long
    if (empty($subject) || strlen($subject) > 200) {
        return false;
    }
    
    return $subject;
}

/**
 * Validate and sanitize message
 * @param string $message Message to validate
 * @return string|bool Sanitized message or false if invalid
 */
function validate_message($message) {
    // Remove extra whitespace
    $message = trim($message);
    
    // Check if message is not empty and not too long
    if (empty($message) || strlen($message) > 5000) {
        return false;
    }
    
    // Remove any script tags
    $message = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $message);
    
    return $message;
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token) {
    // Initialize secure session if not already started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        init_secure_session();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    // Initialize secure session if not already started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        init_secure_session();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}
?>