<?php
// secure_session.php - Enhanced session security management

/**
 * Initialize secure session with enhanced security settings
 * @return void
 */
function init_secure_session() {
    // Set session cookie parameters for enhanced security
    session_set_cookie_params([
        'lifetime' => 0,                    // Session cookie expires when browser closes
        'path' => '/',                      // Cookie path
        'domain' => '',                     // Cookie domain (empty for current domain only)
        'secure' => isset($_SERVER['HTTPS']), // Only send cookie over HTTPS if available
        'httponly' => true,                 // Prevent client-side JavaScript access
        'samesite' => 'Strict'              // CSRF protection
    ]);
    
    // Start the session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    // Set session name to a more secure name
    session_name('NEXORA_SECURE_SESSION');
    
    // Additional security headers for sessions
    if (isset($_SERVER['HTTPS'])) {
        ini_set('session.cookie_secure', 1);
    }
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.entropy_length', 32);
    ini_set('session.entropy_file', '/dev/urandom');
    
    // Set session hash function
    ini_set('session.hash_function', 'sha256');
    ini_set('session.hash_bits_per_character', 5);
}

/**
 * Validate session integrity and security
 * @return bool True if session is valid, false otherwise
 */
function validate_session() {
    // Check if session exists
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    $timeout_duration = 1800; // 30 minutes in seconds
    
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    } elseif (time() - $_SESSION['last_activity'] > $timeout_duration) {
        // Session timeout
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Check user agent consistency
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        // User agent changed, potential session hijacking
        session_destroy();
        return false;
    }
    
    // Check IP address consistency (first 3 octets)
    if (!isset($_SESSION['ip_address'])) {
        $_SESSION['ip_address'] = get_client_ip();
    } elseif (get_ip_prefix($_SESSION['ip_address']) !== get_ip_prefix(get_client_ip())) {
        // IP address changed significantly, potential session hijacking
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * Get client IP address
 * @return string Client IP address
 */
function get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle multiple IPs (comma separated)
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            
            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get IP prefix (first 3 octets)
 * @param string $ip IP address
 * @return string IP prefix
 */
function get_ip_prefix($ip) {
    $parts = explode('.', $ip);
    return isset($parts[0]) && isset($parts[1]) && isset($parts[2]) ? 
        $parts[0] . '.' . $parts[1] . '.' . $parts[2] : $ip;
}

/**
 * Set a secure session value
 * @param string $key Session key
 * @param mixed $value Session value
 * @return void
 */
function set_secure_session_value($key, $value) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$key] = $value;
    }
}

/**
 * Get a secure session value
 * @param string $key Session key
 * @param mixed $default Default value if key doesn't exist
 * @return mixed Session value or default
 */
function get_secure_session_value($key, $default = null) {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$key])) {
        return $_SESSION[$key];
    }
    return $default;
}

/**
 * Unset a session value
 * @param string $key Session key
 * @return void
 */
function unset_secure_session_value($key) {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Destroy session securely
 * @return void
 */
function destroy_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
}

/**
 * Regenerate session ID securely
 * @param bool $delete_old_session Whether to delete the old session file
 * @return void
 */
function regenerate_secure_session_id($delete_old_session = true) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id($delete_old_session);
    }
}

/**
 * Log session security events
 * @param string $event Event description
 * @return void
 */
function log_session_event($event) {
    $log_entry = date('Y-m-d H:i:s') . " - SESSION EVENT - " . $event . " - IP: " . get_client_ip() . " - User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
    error_log($log_entry, 3, 'session_security.log');
}

/**
 * Check for session anomalies
 * @return bool True if anomalies detected
 */
function check_session_anomalies() {
    $anomalies = [];
    
    // Check for session duration
    if (isset($_SESSION['created_at'])) {
        $session_duration = time() - $_SESSION['created_at'];
        if ($session_duration > 3600) { // 1 hour
            $anomalies[] = 'Long session duration';
        }
    } else {
        $_SESSION['created_at'] = time();
    }
    
    // Check for rapid session regeneration
    if (isset($_SESSION['regen_count'])) {
        $_SESSION['regen_count']++;
        if ($_SESSION['regen_count'] > 10) {
            $anomalies[] = 'Excessive session regeneration';
        }
    } else {
        $_SESSION['regen_count'] = 1;
    }
    
    // Log anomalies if detected
    if (!empty($anomalies)) {
        log_session_event('ANOMALY DETECTED: ' . implode(', ', $anomalies));
        return true;
    }
    
    return false;
}
?>