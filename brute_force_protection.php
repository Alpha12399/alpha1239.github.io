<?php
// Brute force protection system

class BruteForceProtection {
    private $max_attempts = 5; // Maximum attempts before blocking
    private $block_duration = 900; // Block duration in seconds (15 minutes)
    private $log_file = 'security_attempts.log';
    
    /**
     * Check if an IP is blocked
     * @param string $ip IP address to check
     * @return bool True if blocked, false otherwise
     */
    public function isBlocked($ip) {
        $this->cleanupOldEntries();
        
        $attempts = $this->getAttempts($ip);
        return $attempts >= $this->max_attempts;
    }
    
    /**
     * Log a failed attempt
     * @param string $ip IP address
     */
    public function logFailedAttempt($ip) {
        $entry = [
            'ip' => $ip,
            'time' => time(),
            'attempts' => $this->getAttempts($ip) + 1
        ];
        
        $this->saveEntry($entry);
    }
    
    /**
     * Log a successful attempt (resets counter)
     * @param string $ip IP address
     */
    public function logSuccessfulAttempt($ip) {
        $this->removeEntry($ip);
    }
    
    /**
     * Get number of attempts for an IP
     * @param string $ip IP address
     * @return int Number of attempts
     */
    private function getAttempts($ip) {
        if (!file_exists($this->log_file)) {
            return 0;
        }
        
        $entries = json_decode(file_get_contents($this->log_file), true);
        if (!is_array($entries)) {
            return 0;
        }
        
        foreach ($entries as $entry) {
            if ($entry['ip'] === $ip && (time() - $entry['time']) < $this->block_duration) {
                return $entry['attempts'];
            }
        }
        
        return 0;
    }
    
    /**
     * Save an entry to the log file
     * @param array $entry Entry to save
     */
    private function saveEntry($entry) {
        $entries = [];
        if (file_exists($this->log_file)) {
            $entries = json_decode(file_get_contents($this->log_file), true);
            if (!is_array($entries)) {
                $entries = [];
            }
        }
        
        // Remove existing entry for this IP
        $entries = array_filter($entries, function($e) use ($entry) {
            return $e['ip'] !== $entry['ip'];
        });
        
        // Add new entry
        $entries[] = $entry;
        
        file_put_contents($this->log_file, json_encode(array_values($entries)), LOCK_EX);
    }
    
    /**
     * Remove an entry for an IP
     * @param string $ip IP address
     */
    private function removeEntry($ip) {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $entries = json_decode(file_get_contents($this->log_file), true);
        if (!is_array($entries)) {
            return;
        }
        
        $entries = array_filter($entries, function($e) use ($ip) {
            return $e['ip'] !== $ip;
        });
        
        file_put_contents($this->log_file, json_encode(array_values($entries)), LOCK_EX);
    }
    
    /**
     * Clean up old entries
     */
    private function cleanupOldEntries() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $entries = json_decode(file_get_contents($this->log_file), true);
        if (!is_array($entries)) {
            return;
        }
        
        $entries = array_filter($entries, function($entry) {
            return (time() - $entry['time']) < $this->block_duration;
        });
        
        file_put_contents($this->log_file, json_encode(array_values($entries)), LOCK_EX);
    }
    
    /**
     * Get client IP address
     * @return string IP address
     */
    public function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
?>