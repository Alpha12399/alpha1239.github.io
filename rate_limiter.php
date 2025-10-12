<?php
// Rate limiting system to prevent DDoS attacks

class RateLimiter {
    private $limit;
    private $window;
    private $storage_file;
    
    /**
     * Constructor
     * @param int $limit Number of requests allowed
     * @param int $window Time window in seconds
     * @param string $storage_file File to store request data
     */
    public function __construct($limit = 100, $window = 3600, $storage_file = 'rate_limit.json') {
        $this->limit = $limit;
        $this->window = $window;
        $this->storage_file = $storage_file;
    }
    
    /**
     * Check if a request is allowed
     * @param string $identifier Unique identifier (IP address, user ID, etc.)
     * @return bool True if allowed, false if rate limited
     */
    public function isAllowed($identifier) {
        $this->cleanup();
        $data = $this->loadData();
        
        $now = time();
        $key = $this->getKey($identifier);
        
        if (!isset($data[$key])) {
            $data[$key] = [
                'count' => 1,
                'first_request' => $now
            ];
            $this->saveData($data);
            return true;
        }
        
        // Reset counter if window has passed
        if (($now - $data[$key]['first_request']) > $this->window) {
            $data[$key] = [
                'count' => 1,
                'first_request' => $now
            ];
            $this->saveData($data);
            return true;
        }
        
        // Increment counter
        $data[$key]['count']++;
        
        // Check if limit is exceeded
        if ($data[$key]['count'] > $this->limit) {
            $this->saveData($data);
            return false;
        }
        
        $this->saveData($data);
        return true;
    }
    
    /**
     * Get the key for an identifier
     * @param string $identifier Identifier
     * @return string Key
     */
    private function getKey($identifier) {
        return hash('sha256', $identifier . floor(time() / $this->window));
    }
    
    /**
     * Load data from storage file
     * @return array Data
     */
    private function loadData() {
        if (!file_exists($this->storage_file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($this->storage_file), true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * Save data to storage file
     * @param array $data Data to save
     */
    private function saveData($data) {
        file_put_contents($this->storage_file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Clean up old entries
     */
    private function cleanup() {
        if (!file_exists($this->storage_file)) {
            return;
        }
        
        $data = $this->loadData();
        $now = time();
        $threshold = $now - ($this->window * 2); // Keep data for 2 windows
        
        foreach ($data as $key => $entry) {
            if ($entry['first_request'] < $threshold) {
                unset($data[$key]);
            }
        }
        
        $this->saveData($data);
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
    
    /**
     * Block an IP address
     * @param string $ip IP address to block
     * @param int $duration Duration in seconds
     */
    public function blockIP($ip, $duration = 3600) {
        $block_file = 'blocked_ips.json';
        $data = [];
        
        if (file_exists($block_file)) {
            $data = json_decode(file_get_contents($block_file), true);
            if (!is_array($data)) {
                $data = [];
            }
        }
        
        $data[$ip] = time() + $duration;
        file_put_contents($block_file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Check if an IP is blocked
     * @param string $ip IP address to check
     * @return bool True if blocked, false otherwise
     */
    public function isBlocked($ip) {
        $block_file = 'blocked_ips.json';
        
        if (!file_exists($block_file)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($block_file), true);
        if (!is_array($data) || !isset($data[$ip])) {
            return false;
        }
        
        // Check if block has expired
        if (time() > $data[$ip]) {
            unset($data[$ip]);
            file_put_contents($block_file, json_encode($data), LOCK_EX);
            return false;
        }
        
        return true;
    }
}
?>