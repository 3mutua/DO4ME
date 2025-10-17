<?php
class Session {
    private $sessionStarted = false;
    private $flashKey = 'flash_messages';
    private $csrfKey = 'csrf_tokens';
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            $this->start();
        }
    }
    
    /**
     * Start session with security settings
     */
    public function start() {
        if ($this->sessionStarted) {
            return true;
        }
        
        // Session configuration
        $config = require __DIR__ . '/../../config/app.php';
        $sessionConfig = $config['session'] ?? [];
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $sessionConfig['lifetime'] ?? 7200,
            'path' => $sessionConfig['path'] ?? '/',
            'domain' => $sessionConfig['domain'] ?? '',
            'secure' => $sessionConfig['secure'] ?? false,
            'httponly' => $sessionConfig['httponly'] ?? true,
            'samesite' => $sessionConfig['same_site'] ?? 'Lax'
        ]);
        
        // Set session name
        session_name('DO4ME_SESSION');
        
        // Start session
        if (session_start()) {
            $this->sessionStarted = true;
            
            // Regenerate session ID to prevent fixation
            if (empty($this->get('session_created'))) {
                $this->regenerate();
                $this->set('session_created', time());
            }
            
            // Check for session hijacking
            $this->validateSession();
            
            // Process flash messages
            $this->ageFlashData();
            
            return true;
        }
        
        throw new Exception('Failed to start session');
    }
    
    /**
     * Set session value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
        return $this;
    }
    
    /**
     * Get session value
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public function remove($key) {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
        return $this;
    }
    
    /**
     * Get all session data
     */
    public function all() {
        return $_SESSION;
    }
    
    /**
     * Destroy session
     */
    public function destroy() {
        if ($this->sessionStarted) {
            $_SESSION = [];
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            session_destroy();
            $this->sessionStarted = false;
        }
        return $this;
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerate($deleteOldSession = true) {
        if ($this->sessionStarted) {
            session_regenerate_id($deleteOldSession);
            
            // Update session creation time
            $this->set('session_created', time());
        }
        return $this;
    }
    
    /**
     * Set flash message for next request
     */
    public function setFlash($type, $message) {
        $flashMessages = $this->get($this->flashKey, []);
        $flashMessages[$type] = $message;
        $this->set($this->flashKey, $flashMessages);
        return $this;
    }
    
    /**
     * Get flash message and remove it
     */
    public function getFlash($type) {
        $flashMessages = $this->get($this->flashKey, []);
        $message = $flashMessages[$type] ?? null;
        
        // Remove the message after retrieving
        if (isset($flashMessages[$type])) {
            unset($flashMessages[$type]);
            $this->set($this->flashKey, $flashMessages);
        }
        
        return $message;
    }
    
    /**
     * Check if flash message exists
     */
    public function hasFlash($type) {
        $flashMessages = $this->get($this->flashKey, []);
        return isset($flashMessages[$type]);
    }
    
    /**
     * Age flash data (move current to old for next request)
     */
    private function ageFlashData() {
        $currentFlash = $this->get($this->flashKey, []);
        
        // Move current flash to old (for display in current request)
        if (!empty($currentFlash)) {
            $this->set('old_' . $this->flashKey, $currentFlash);
            $this->remove($this->flashKey);
        }
        
        // Clean up old flash data from previous request
        $this->remove('old_' . $this->flashKey);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken($formName = 'default') {
        $tokens = $this->get($this->csrfKey, []);
        
        if (!isset($tokens[$formName]) || time() > $tokens[$formName]['expires']) {
            $token = bin2hex(random_bytes(32));
            $tokens[$formName] = [
                'token' => $token,
                'expires' => time() + 3600, // 1 hour expiration
                'created' => time()
            ];
            $this->set($this->csrfKey, $tokens);
        }
        
        return $tokens[$formName]['token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken($token, $formName = 'default') {
        $tokens = $this->get($this->csrfKey, []);
        
        if (!isset($tokens[$formName])) {
            return false;
        }
        
        $storedToken = $tokens[$formName];
        
        // Check if token expired
        if (time() > $storedToken['expires']) {
            $this->removeCsrfToken($formName);
            return false;
        }
        
        // Validate token
        if (hash_equals($storedToken['token'], $token)) {
            // Remove token after successful validation (one-time use)
            $this->removeCsrfToken($formName);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove CSRF token
     */
    public function removeCsrfToken($formName = 'default') {
        $tokens = $this->get($this->csrfKey, []);
        if (isset($tokens[$formName])) {
            unset($tokens[$formName]);
            $this->set($this->csrfKey, $tokens);
        }
        return $this;
    }
    
    /**
     * Get CSRF token HTML input field
     */
    public function csrfField($formName = 'default') {
        $token = $this->generateCsrfToken($formName);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate session integrity
     */
    private function validateSession() {
        $currentFingerprint = $this->generateSessionFingerprint();
        $storedFingerprint = $this->get('session_fingerprint');
        
        if (!$storedFingerprint) {
            // First time, store fingerprint
            $this->set('session_fingerprint', $currentFingerprint);
        } elseif ($currentFingerprint !== $storedFingerprint) {
            // Session hijacking detected
            $this->destroy();
            $this->start();
            throw new Exception('Session security violation detected');
        }
    }
    
    /**
     * Generate session fingerprint for security validation
     */
    private function generateSessionFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->getClientIp();
        
        return hash('sha256', $userAgent . $ipAddress . session_id());
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Set user authentication data
     */
    public function setAuth($userData) {
        $this->set('user_id', $userData['id']);
        $this->set('user_email', $userData['email']);
        $this->set('user_role', $userData['role']);
        $this->set('user_name', $userData['first_name'] . ' ' . $userData['last_name']);
        $this->set('user_authenticated', true);
        $this->set('login_time', time());
        
        // Regenerate session after login for security
        $this->regenerate();
        
        return $this;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return $this->get('user_authenticated', false) && $this->get('user_id');
    }
    
    /**
     * Get authenticated user ID
     */
    public function getUserId() {
        return $this->get('user_id');
    }
    
    /**
     * Get authenticated user role
     */
    public function getUserRole() {
        return $this->get('user_role');
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return $this->getUserRole() === $role;
    }
    
    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles) {
        return in_array($this->getUserRole(), (array)$roles);
    }
    
    /**
     * Clear authentication data
     */
    public function clearAuth() {
        $this->remove('user_id');
        $this->remove('user_email');
        $this->remove('user_role');
        $this->remove('user_name');
        $this->remove('user_authenticated');
        $this->remove('login_time');
        
        return $this;
    }
    
    /**
     * Get session lifetime remaining
     */
    public function getLifetimeRemaining() {
        $loginTime = $this->get('login_time');
        $config = require __DIR__ . '/../../config/app.php';
        $lifetime = $config['session']['lifetime'] ?? 7200;
        
        if (!$loginTime) {
            return $lifetime;
        }
        
        $elapsed = time() - $loginTime;
        $remaining = $lifetime - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Check if session is about to expire
     */
    public function isExpiringSoon($threshold = 300) {
        return $this->getLifetimeRemaining() <= $threshold;
    }
    
    /**
     * Set temporary data (expires after specified time)
     */
    public function setTemp($key, $value, $ttl = 3600) {
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        $this->set('temp_' . $key, $data);
        return $this;
    }
    
    /**
     * Get temporary data
     */
    public function getTemp($key, $default = null) {
        $data = $this->get('temp_' . $key);
        
        if (!$data || time() > $data['expires']) {
            $this->remove('temp_' . $key);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Increment session value
     */
    public function increment($key, $by = 1) {
        $value = $this->get($key, 0);
        $this->set($key, $value + $by);
        return $this->get($key);
    }
    
    /**
     * Decrement session value
     */
    public function decrement($key, $by = 1) {
        $value = $this->get($key, 0);
        $this->set($key, max(0, $value - $by));
        return $this->get($key);
    }
    
    /**
     * Push value to session array
     */
    public function push($key, $value) {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->set($key, $array);
        return $this;
    }
    
    /**
     * Pop value from session array
     */
    public function pop($key) {
        $array = $this->get($key, []);
        $value = array_pop($array);
        $this->set($key, $array);
        return $value;
    }
    
    /**
     * Merge array with session array
     */
    public function merge($key, $array) {
        $current = $this->get($key, []);
        $merged = array_merge($current, $array);
        $this->set($key, $merged);
        return $this;
    }
    
    /**
     * Get session status information
     */
    public function getStatus() {
        return [
            'started' => $this->sessionStarted,
            'id' => session_id(),
            'name' => session_name(),
            'user_id' => $this->get('user_id'),
            'user_role' => $this->get('user_role'),
            'authenticated' => $this->isAuthenticated(),
            'lifetime_remaining' => $this->getLifetimeRemaining(),
            'created' => $this->get('session_created'),
            'data_size' => strlen(serialize($_SESSION))
        ];
    }
    
    /**
     * Close session for writing (improves performance)
     */
    public function close() {
        if ($this->sessionStarted) {
            session_write_close();
            $this->sessionStarted = false;
        }
        return $this;
    }
    
    /**
     * Reopen session for writing
     */
    public function reopen() {
        if (!$this->sessionStarted) {
            session_start();
            $this->sessionStarted = true;
        }
        return $this;
    }
    
    /**
     * Garbage collection for expired temporary data
     */
    public function gc() {
        $prefix = 'temp_';
        $currentTime = time();
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $prefix) === 0 && is_array($value) && isset($value['expires'])) {
                if ($currentTime > $value['expires']) {
                    unset($_SESSION[$key]);
                }
            }
        }
        
        // Clean expired CSRF tokens
        $tokens = $this->get($this->csrfKey, []);
        foreach ($tokens as $formName => $tokenData) {
            if ($currentTime > $tokenData['expires']) {
                unset($tokens[$formName]);
            }
        }
        $this->set($this->csrfKey, $tokens);
        
        return $this;
    }
}