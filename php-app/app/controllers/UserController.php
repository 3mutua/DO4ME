<?php
class UserController {
    private $userModel;
    private $taskModel;
    private $session;
    private $validator;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->session = new Session();
        $this->validator = new Validator();
        $this->ensureAuth();
    }
    
    /**
     * User dashboard
     */
    public function dashboard() {
        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');
        
        $dashboardData = [];
        
        if ($userRole === 'client') {
            $dashboardData = $this->getClientDashboard($userId);
        } elseif ($userRole === 'freelancer') {
            $dashboardData = $this->getFreelancerDashboard($userId);
        } else {
            // Admin dashboard handled by AdminController
            header('Location: /admin/dashboard');
            exit;
        }
        
        $this->render('dashboard/index', $dashboardData);
    }
    
    /**
     * Show user profile
     */
    public function profile() {
        $userId = $this->session->get('user_id');
        $user = $this->userModel->findById($userId);
        
        $this->render('users/profile', [
            'user' => $user,
            'stats' => $this->getUserStats($userId)
        ]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $userId = $this->session->get('user_id');
            $profileData = $this->validateProfileData($_POST);
            
            // Handle profile picture upload
            if (!empty($_FILES['profile_picture']['name'])) {
                $profileData['profile_picture'] = $this->handleProfilePictureUpload($userId);
            }
            
            $this->userModel->updateProfile($userId, $profileData);
            
            // Update session data if name changed
            if (isset($profileData['first_name']) || isset($profileData['last_name'])) {
                $updatedUser = $this->userModel->findById($userId);
                $this->session->set('user_name', $updatedUser['first_name'] . ' ' . $updatedUser['last_name']);
            }
            
            $this->session->setFlash('success', 'Profile updated successfully!');
            header('Location: /profile');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /profile');
        }
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $userId = $this->session->get('user_id');
            $passwordData = $this->validatePasswordData($_POST);
            
            // Verify current password
            $user = $this->userModel->findById($userId);
            if (!password_verify($passwordData['current_password'], $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $this->userModel->updatePassword($userId, $passwordData['new_password']);
            
            ActivityLogger::logPasswordChanged($userId);
            
            $this->session->setFlash('success', 'Password changed successfully!');
            header('Location: /profile');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /profile');
        }
    }
    
    /**
     * Show public profile
     */
    public function publicProfile($userId) {
        $user = $this->userModel->findById($userId);
        
        if (!$user || !$user['is_verified']) {
            $this->renderError('User not found', 404);
            return;
        }
        
        $userStats = $this->getUserStats($userId);
        $recentTasks = $this->getUserRecentTasks($userId);
        $reviews = $this->userModel->getUserReviews($userId);
        
        $this->render('users/public-profile', [
            'user' => $user,
            'stats' => $userStats,
            'recentTasks' => $recentTasks,
            'reviews' => $reviews,
            'isOwnProfile' => $this->session->get('user_id') == $userId
        ]);
    }
    
    /**
     * Become a freelancer (client switching to freelancer role)
     */
    public function becomeFreelancer() {
        $userId = $this->session->get('user_id');
        $user = $this->userModel->findById($userId);
        
        if ($user['role'] !== 'client') {
            $this->session->setFlash('error', 'You are already a freelancer');
            header('Location: /dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleBecomeFreelancer($userId);
            return;
        }
        
        $this->render('users/become-freelancer', [
            'user' => $user,
            'skills' => $this->getSkillsList()
        ]);
    }
    
    /**
     * Update freelancer profile
     */
    public function updateFreelancerProfile() {
        $this->ensureFreelancerRole();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $userId = $this->session->get('user_id');
            $profileData = $this->validateFreelancerData($_POST);
            
            $this->userModel->updateFreelancerProfile($userId, $profileData);
            
            $this->session->setFlash('success', 'Freelancer profile updated successfully!');
            header('Location: /profile');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /profile');
        }
    }
    
    /**
     * User notifications
     */
    public function notifications() {
        $userId = $this->session->get('user_id');
        $notifications = $this->userModel->getUserNotifications($userId);
        
        $this->render('users/notifications', [
            'notifications' => $notifications
        ]);
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($notificationId) {
        $userId = $this->session->get('user_id');
        
        try {
            $this->userModel->markNotificationRead($notificationId, $userId);
            
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => true]);
            } else {
                header('Location: /notifications');
            }
            
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            } else {
                $this->session->setFlash('error', $e->getMessage());
                header('Location: /notifications');
            }
        }
    }
    
    /**
     * User settings
     */
    public function settings() {
        $userId = $this->session->get('user_id');
        $user = $this->userModel->findById($userId);
        
        $this->render('users/settings', [
            'user' => $user,
            'notificationSettings' => $this->getNotificationSettings($userId)
        ]);
    }
    
    /**
     * Update user settings
     */
    public function updateSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $userId = $this->session->get('user_id');
            $settings = $this->validateSettingsData($_POST);
            
            $this->userModel->updateSettings($userId, $settings);
            
            $this->session->setFlash('success', 'Settings updated successfully!');
            header('Location: /settings');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /settings');
        }
    }
    
    /**
     * Get client dashboard data
     */
    private function getClientDashboard($userId) {
        return [
            'userType' => 'client',
            'stats' => [
                'totalTasks' => $this->taskModel->getUserTaskCount($userId),
                'activeTasks' => $this->taskModel->getUserActiveTaskCount($userId),
                'totalSpent' => $this->taskModel->getUserTotalSpent($userId),
                'openTasks' => $this->taskModel->getUserOpenTaskCount($userId)
            ],
            'recentTasks' => $this->taskModel->getUserRecentTasks($userId, 5),
            'pendingProposals' => $this->taskModel->getUserPendingProposalsCount($userId)
        ];
    }
    
    /**
     * Get freelancer dashboard data
     */
    private function getFreelancerDashboard($userId) {
        return [
            'userType' => 'freelancer',
            'stats' => [
                'totalEarnings' => $this->taskModel->getFreelancerTotalEarnings($userId),
                'completedTasks' => $this->taskModel->getFreelancerCompletedTaskCount($userId),
                'activeTasks' => $this->taskModel->getFreelancerActiveTaskCount($userId),
                'successRate' => $this->taskModel->getFreelancerSuccessRate($userId)
            ],
            'activeTasks' => $this->taskModel->getFreelancerActiveTasks($userId, 5),
            'availableTasks' => $this->taskModel->getAvailableTasksForFreelancer($userId, 5)
        ];
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats($userId) {
        $user = $this->userModel->findById($userId);
        
        if ($user['role'] === 'client') {
            return [
                'totalTasks' => $this->taskModel->getUserTaskCount($userId),
                'completedTasks' => $this->taskModel->getUserCompletedTaskCount($userId),
                'totalSpent' => $this->taskModel->getUserTotalSpent($userId),
                'memberSince' => $user['created_at']
            ];
        } else {
            return [
                'totalEarnings' => $this->taskModel->getFreelancerTotalEarnings($userId),
                'completedTasks' => $this->taskModel->getFreelancerCompletedTaskCount($userId),
                'successRate' => $this->taskModel->getFreelancerSuccessRate($userId),
                'memberSince' => $user['created_at'],
                'averageRating' => $user['average_rating']
            ];
        }
    }
    
    /**
     * Handle become freelancer request
     */
    private function handleBecomeFreelancer($userId) {
        try {
            $freelancerData = $this->validateFreelancerData($_POST);
            
            $this->userModel->convertToFreelancer($userId, $freelancerData);
            
            // Update session role
            $this->session->set('user_role', 'freelancer');
            
            ActivityLogger::logBecameFreelancer($userId);
            
            $this->session->setFlash('success', 'Congratulations! You are now a freelancer.');
            header('Location: /dashboard');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            $this->render('users/become-freelancer', [
                'user' => $this->userModel->findById($userId),
                'skills' => $this->getSkillsList(),
                'formData' => $_POST,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validate profile data
     */
    private function validateProfileData($data) {
        $rules = [
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'phone' => 'optional|phone',
            'country' => 'optional|alpha|size:3',
            'bio' => 'optional|max:1000'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Validate password data
     */
    private function validatePasswordData($data) {
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Validate freelancer data
     */
    private function validateFreelancerData($data) {
        $rules = [
            'skills' => 'required|array',
            'hourly_rate' => 'optional|numeric|min:5|max:500',
            'bio' => 'required|min:50|max:1000',
            'experience' => 'required|in:beginner,intermediate,expert'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Validate settings data
     */
    private function validateSettingsData($data) {
        $rules = [
            'email_notifications' => 'optional|boolean',
            'sms_notifications' => 'optional|boolean',
            'newsletter' => 'optional|boolean',
            'profile_visibility' => 'required|in:public,private'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Handle profile picture upload
     */
    private function handleProfilePictureUpload($userId) {
        $file = $_FILES['profile_picture'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Only JPG, PNG and GIF images are allowed');
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Image size must be less than 2MB');
        }
        
        $uploadDir = __DIR__ . '/../../public/assets/uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = 'profile_' . $userId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return '/assets/uploads/profiles/' . $fileName;
        }
        
        throw new Exception('Failed to upload profile picture');
    }
    
    /**
     * Get skills list
     */
    private function getSkillsList() {
        return [
            'web_development' => 'Web Development',
            'mobile_development' => 'Mobile Development',
            'graphic_design' => 'Graphic Design',
            'content_writing' => 'Content Writing',
            'digital_marketing' => 'Digital Marketing',
            'data_analysis' => 'Data Analysis',
            'customer_support' => 'Customer Support',
            'virtual_assistance' => 'Virtual Assistance'
        ];
    }
    
    /**
     * Get user recent tasks
     */
    private function getUserRecentTasks($userId, $limit = 5) {
        $user = $this->userModel->findById($userId);
        
        if ($user['role'] === 'client') {
            return $this->taskModel->getUserRecentTasks($userId, $limit);
        } else {
            return $this->taskModel->getFreelancerRecentTasks($userId, $limit);
        }
    }
    
    /**
     * Get notification settings
     */
    private function getNotificationSettings($userId) {
        return $this->userModel->getNotificationSettings($userId);
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Ensure user is authenticated
     */
    private function ensureAuth() {
        if (!$this->session->get('user_id')) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Ensure user has freelancer role
     */
    private function ensureFreelancerRole() {
        if ($this->session->get('user_role') !== 'freelancer') {
            $this->renderError('Access denied. Freelancer role required.', 403);
            exit;
        }
    }
    
    /**
     * Render view with data
     */
    private function render($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.php";
    }
    
    /**
     * Render error page
     */
    private function renderError($message, $code = 500) {
        http_response_code($code);
        echo "<h1>$code Error</h1><p>$message</p>";
        exit;
    }
}