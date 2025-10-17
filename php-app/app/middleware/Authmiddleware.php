<?php
class AuthMiddleware {
    private $session;
    private $excludedRoutes = [
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/api/auth/login',
        '/api/auth/register'
    ];
    
    public function __construct() {
        $this->session = new Session();
    }
    
    /**
     * Handle incoming request - main middleware method
     */
    public function handle($route = null) {
        // Check if route is excluded from authentication
        if ($this->isExcludedRoute($route)) {
            return true;
        }
        
        // Check if user is authenticated
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        // Check if user account is still active
        if (!$this->isUserActive()) {
            return $this->handleInactiveUser();
        }
        
        // Check session expiration
        if ($this->session->isExpiringSoon()) {
            $this->session->regenerate();
        }
        
        // Update last activity timestamp
        $this->updateLastActivity();
        
        return true;
    }
    
    /**
     * Check if user has specific role
     */
    public function requireRole($role) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $userRole = $this->session->getUserRole();
        
        if ($userRole !== $role) {
            return $this->handleUnauthorized("Role '$role' required");
        }
        
        return true;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public function requireAnyRole($roles) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $userRole = $this->session->getUserRole();
        
        if (!in_array($userRole, (array)$roles)) {
            $roleList = implode(', ', (array)$roles);
            return $this->handleUnauthorized("One of these roles required: $roleList");
        }
        
        return true;
    }
    
    /**
     * Check if user is the owner of a resource
     */
    public function requireOwnership($resourceOwnerId) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $userId = $this->session->getUserId();
        
        if ($userId != $resourceOwnerId && !$this->session->hasRole('admin')) {
            return $this->handleUnauthorized("You don't own this resource");
        }
        
        return true;
    }
    
    /**
     * Check if user can access the task (owner or assigned freelancer)
     */
    public function canAccessTask($taskId) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $userId = $this->session->getUserId();
        $userRole = $this->session->getUserRole();
        
        // Admin can access any task
        if ($userRole === 'admin') {
            return true;
        }
        
        $taskModel = new TaskModel();
        $task = $taskModel->findById($taskId);
        
        if (!$task) {
            return $this->handleNotFound("Task not found");
        }
        
        // Task owner can access
        if ($task['client_id'] == $userId) {
            return true;
        }
        
        // Assigned freelancer can access
        if ($task['freelancer_id'] == $userId) {
            return true;
        }
        
        return $this->handleUnauthorized("You don't have access to this task");
    }
    
    /**
     * Check if user can submit proposal for task
     */
    public function canSubmitProposal($taskId) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        if (!$this->session->hasRole('freelancer')) {
            return $this->handleUnauthorized("Only freelancers can submit proposals");
        }
        
        $userId = $this->session->getUserId();
        $taskModel = new TaskModel();
        $proposalModel = new ProposalModel();
        
        $task = $taskModel->findById($taskId);
        
        if (!$task) {
            return $this->handleNotFound("Task not found");
        }
        
        // Can't propose to own task
        if ($task['client_id'] == $userId) {
            return $this->handleUnauthorized("You cannot submit proposals to your own tasks");
        }
        
        // Check if task is open
        if ($task['status'] !== 'open') {
            return $this->handleUnauthorized("This task is not accepting proposals");
        }
        
        // Check if user has already proposed
        if ($proposalModel->getUserProposalForTask($taskId, $userId)) {
            return $this->handleUnauthorized("You have already submitted a proposal for this task");
        }
        
        return true;
    }
    
    /**
     * Check if user can manage task (owner, assigned freelancer, or admin)
     */
    public function canManageTask($taskId) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $userId = $this->session->getUserId();
        $userRole = $this->session->getUserRole();
        
        if ($userRole === 'admin') {
            return true;
        }
        
        $taskModel = new TaskModel();
        $task = $taskModel->findById($taskId);
        
        if (!$task) {
            return $this->handleNotFound("Task not found");
        }
        
        // Task owner can manage
        if ($task['client_id'] == $userId) {
            return true;
        }
        
        // Assigned freelancer can manage
        if ($task['freelancer_id'] == $userId) {
            return true;
        }
        
        return $this->handleUnauthorized("You don't have permission to manage this task");
    }
    
    /**
     * Check if user is verified
     */
    public function requireVerified() {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $userModel = new UserModel();
        $user = $userModel->findById($this->session->getUserId());
        
        if (!$user || !$user['is_verified']) {
            $this->session->setFlash('warning', 'Please verify your email address to access this feature.');
            header('Location: /verify-email');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user has sufficient wallet balance
     */
    public function requireBalance($minimumAmount) {
        if (!$this->session->isAuthenticated()) {
            return $this->handleUnauthenticated();
        }
        
        $walletModel = new WalletModel();
        $balance = $walletModel->getBalance($this->session->getUserId());
        
        if ($balance < $minimumAmount) {
            $this->session->setFlash('error', "Insufficient balance. Minimum required: $$minimumAmount");
            header('Location: /payments/deposit');
            return false;
        }
        
        return true;
    }
    
    /**
     * Rate limiting for authentication attempts
     */
    public function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) { // 15 minutes
        $key = "rate_limit_{$identifier}";
        $attempts = $this->session->getTemp($key, 0);
        $lastAttempt = $this->session->getTemp("{$key}_time", 0);
        
        // Reset if time window has passed
        if (time() - $lastAttempt > $timeWindow) {
            $attempts = 0;
        }
        
        $attempts++;
        $this->session->setTemp($key, $attempts, $timeWindow);
        $this->session->setTemp("{$key}_time", time(), $timeWindow);
        
        if ($attempts > $maxAttempts) {
            $remainingTime = $timeWindow - (time() - $lastAttempt);
            $this->session->setFlash('error', "Too many attempts. Please try again in " . ceil($remainingTime / 60) . " minutes.");
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if route is excluded from authentication
     */
    private function isExcludedRoute($route) {
        if ($route === null) {
            return false;
        }
        
        foreach ($this->excludedRoutes as $excluded) {
            if (strpos($route, $excluded) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user account is active
     */
    private function isUserActive() {
        $userModel = new UserModel();
        $user = $userModel->findById($this->session->getUserId());
        
        if (!$user || !$user['is_approved'] || $user['is_suspended']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle unauthenticated requests
     */
    private function handleUnauthenticated() {
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required',
                'redirect' => '/login'
            ]);
            return false;
        }
        
        $this->session->setFlash('error', 'Please log in to access this page.');
        $this->session->set('intended_url', $this->getCurrentUrl());
        header('Location: /login');
        return false;
    }
    
    /**
     * Handle unauthorized requests
     */
    private function handleUnauthorized($message = 'Access denied') {
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message
            ]);
            return false;
        }
        
        http_response_code(403);
        $this->renderError($message, 403);
        return false;
    }
    
    /**
     * Handle not found resources
     */
    private function handleNotFound($message = 'Resource not found') {
        if ($this->isAjaxRequest()) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message
            ]);
            return false;
        }
        
        http_response_code(404);
        $this->renderError($message, 404);
        return false;
    }
    
    /**
     * Handle inactive user accounts
     */
    private function handleInactiveUser() {
        $this->session->clearAuth();
        
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Account suspended or not approved',
                'redirect' => '/login'
            ]);
            return false;
        }
        
        $this->session->setFlash('error', 'Your account is not active. Please contact support.');
        header('Location: /login');
        return false;
    }
    
    /**
     * Update user's last activity timestamp
     */
    private function updateLastActivity() {
        $lastActivity = $this->session->get('last_activity', 0);
        $currentTime = time();
        
        // Update every 5 minutes to reduce database writes
        if ($currentTime - $lastActivity > 300) {
            $this->session->set('last_activity', $currentTime);
            
            // Update in database (optional, for tracking user activity)
            $userModel = new UserModel();
            $userModel->updateLastActivity($this->session->getUserId());
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Get current URL
     */
    private function getCurrentUrl() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
               "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
    
    /**
     * Render error page
     */
    private function renderError($message, $code = 500) {
        http_response_code($code);
        
        // Check if custom error view exists
        $errorView = __DIR__ . "/../views/errors/{$code}.php";
        if (file_exists($errorView)) {
            require_once $errorView;
        } else {
            echo "<h1>$code Error</h1><p>$message</p>";
        }
        exit;
    }
    
    /**
     * Get user IP address for rate limiting
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
     * Add routes to exclusion list
     */
    public function excludeRoutes($routes) {
        $this->excludedRoutes = array_merge($this->excludedRoutes, (array)$routes);
        return $this;
    }
    
    /**
     * Get middleware configuration for router
     */
    public static function auth() {
        return [new self(), 'handle'];
    }
    
    public static function role($role) {
        return function() use ($role) {
            return (new self())->requireRole($role);
        };
    }
    
    public static function anyRole($roles) {
        return function() use ($roles) {
            return (new self())->requireAnyRole($roles);
        };
    }
    
    public static function verified() {
        return [new self(), 'requireVerified'];
    }
}

// Usage in router configuration:
// return [
//     // Basic authentication
//     'GET|/dashboard' => ['DashboardController@index', AuthMiddleware::auth()],
//     
//     // Role-based access
//     'GET|/admin' => ['AdminController@index', AuthMiddleware::role('admin')],
//     
//     // Multiple roles
//     'GET|/moderate' => ['ModerationController@index', AuthMiddleware::anyRole(['admin', 'moderator'])],
//     
//     // Verified email required
//     'POST|/tasks' => ['TaskController@store', AuthMiddleware::verified()],
// ];