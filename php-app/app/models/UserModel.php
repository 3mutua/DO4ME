<?php
class UserModel extends Database {
    protected $table = 'users';
    
    public function create($userData) {
        $this->validateUserData($userData);
        
        // Check if email already exists
        if ($this->findByEmail($userData['email'])) {
            throw new Exception('Email address already registered');
        }
        
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']); // Remove plain text password
        
        $userId = $this->insert($this->table, $userData);
        
        // Create user wallet
        $this->createUserWallet($userId);
        
        // Log user creation
        ActivityLogger::logUserRegistered($userId);
        
        return $userId;
    }
    
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            // Log failed login attempt
            ActivityLogger::logFailedLogin($email);
            return false;
        }
        
        if (!$user['is_verified']) {
            throw new Exception('Please verify your email address before logging in.');
        }
        
        if (!$user['is_approved'] && $user['role'] === 'freelancer') {
            throw new Exception('Your freelancer account is pending approval.');
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Log successful login
        ActivityLogger::logSuccessfulLogin($user['id']);
        
        return $user;
    }
    
    public function findByEmail($email) {
        return $this->query("SELECT * FROM {$this->table} WHERE email = ?", [$email])->fetch();
    }
    
    public function findById($id) {
        return $this->query("SELECT * FROM {$this->table} WHERE id = ?", [$id])->fetch();
    }
    
    public function updateProfile($userId, $profileData) {
        $allowedFields = ['first_name', 'last_name', 'phone', 'country', 'bio', 'profile_picture'];
        $data = array_intersect_key($profileData, array_flip($allowedFields));
        
        return $this->update($this->table, $data, 'id = ?', [$userId]);
    }
    
    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($this->table, ['password_hash' => $passwordHash], 'id = ?', [$userId]);
    }
    
    public function updateLastLogin($userId) {
        return $this->update($this->table, ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }
    
    public function updateLastActivity($userId) {
        return $this->update($this->table, ['last_activity' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }
    
    public function verifyEmail($userId) {
        return $this->update($this->table, ['is_verified' => 1], 'id = ?', [$userId]);
    }
    
    public function approveUser($userId) {
        return $this->update($this->table, ['is_approved' => 1], 'id = ?', [$userId]);
    }
    
    public function suspendUser($userId) {
        return $this->update($this->table, ['is_approved' => 0], 'id = ?', [$userId]);
    }
    
    public function convertToFreelancer($userId, $freelancerData) {
        $data = [
            'role' => 'freelancer',
            'skills' => json_encode($freelancerData['skills']),
            'bio' => $freelancerData['bio'],
            'hourly_rate' => $freelancerData['hourly_rate'] ?? null,
            'experience' => $freelancerData['experience'] ?? 'beginner'
        ];
        
        return $this->update($this->table, $data, 'id = ?', [$userId]);
    }
    
    public function updateFreelancerProfile($userId, $profileData) {
        $allowedFields = ['skills', 'bio', 'hourly_rate', 'experience', 'portfolio_url'];
        $data = array_intersect_key($profileData, array_flip($allowedFields));
        
        if (isset($data['skills'])) {
            $data['skills'] = json_encode($data['skills']);
        }
        
        return $this->update($this->table, $data, 'id = ?', [$userId]);
    }
    
    public function getUsersPaginated($page = 1, $perPage = 20, $filters = []) {
        $where = '1=1';
        $params = [];
        
        if (!empty($filters['role'])) {
            $where .= " AND role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'verified') {
                $where .= " AND is_verified = 1";
            } elseif ($filters['status'] === 'unverified') {
                $where .= " AND is_verified = 0";
            } elseif ($filters['status'] === 'approved') {
                $where .= " AND is_approved = 1";
            } elseif ($filters['status'] === 'pending') {
                $where .= " AND is_approved = 0";
            }
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        return $this->paginate($this->table, $page, $perPage, $where, $params, 'created_at DESC');
    }
    
    public function getTotalUsers() {
        return $this->count($this->table);
    }
    
    public function getRecentUsers($limit = 10) {
        return $this->select($this->table, '*', 'is_verified = 1', [], 'created_at DESC', $limit);
    }
    
    public function getUserStats($userId) {
        $user = $this->findById($userId);
        
        if ($user['role'] === 'client') {
            return $this->getClientStats($userId);
        } else {
            return $this->getFreelancerStats($userId);
        }
    }
    
    public function getUserReviews($userId) {
        return $this->query("
            SELECT r.*, u.first_name, u.last_name, u.profile_picture, t.title as task_title
            FROM reviews r
            LEFT JOIN users u ON r.reviewer_id = u.id
            LEFT JOIN tasks t ON r.task_id = t.id
            WHERE r.reviewee_id = ?
            ORDER BY r.created_at DESC
        ", [$userId])->fetchAll();
    }
    
    public function getUserNotifications($userId, $limit = 20) {
        return $this->query("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$userId, $limit])->fetchAll();
    }
    
    public function markNotificationRead($notificationId, $userId) {
        return $this->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }
    
    public function getNotificationSettings($userId) {
        $settings = $this->query("
            SELECT email_notifications, sms_notifications, newsletter, profile_visibility 
            FROM user_settings 
            WHERE user_id = ?
        ", [$userId])->fetch();
        
        return $settings ?: [
            'email_notifications' => 1,
            'sms_notifications' => 0,
            'newsletter' => 1,
            'profile_visibility' => 'public'
        ];
    }
    
    public function updateSettings($userId, $settings) {
        $allowed = ['email_notifications', 'sms_notifications', 'newsletter', 'profile_visibility'];
        $data = array_intersect_key($settings, array_flip($allowed));
        
        // Check if settings exist
        $exists = $this->exists('user_settings', 'user_id = ?', [$userId]);
        
        if ($exists) {
            return $this->update('user_settings', $data, 'user_id = ?', [$userId]);
        } else {
            $data['user_id'] = $userId;
            return $this->insert('user_settings', $data);
        }
    }
    
    private function getClientStats($userId) {
        $stats = $this->query("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tasks,
                COALESCE(SUM(budget), 0) as total_spent
            FROM tasks 
            WHERE client_id = ?
        ", [$userId])->fetch();
        
        $stats['pending_proposals'] = $this->query("
            SELECT COUNT(DISTINCT p.task_id) as count
            FROM proposals p
            JOIN tasks t ON p.task_id = t.id
            WHERE t.client_id = ? AND p.status = 'pending'
        ", [$userId])->fetchColumn();
        
        return $stats;
    }
    
    private function getFreelancerStats($userId) {
        $stats = $this->query("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status IN ('assigned', 'in_progress') THEN 1 ELSE 0 END) as active_tasks,
                COALESCE(SUM(budget), 0) as total_earnings
            FROM tasks 
            WHERE freelancer_id = ?
        ", [$userId])->fetch();
        
        // Calculate success rate
        $totalAssigned = $this->count('tasks', 'freelancer_id = ? AND status IN ("completed", "cancelled")', [$userId]);
        $completed = $this->count('tasks', 'freelancer_id = ? AND status = "completed"', [$userId]);
        $stats['success_rate'] = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100, 2) : 0;
        
        return $stats;
    }
    
    private function createUserWallet($userId) {
        // Wallet is automatically created via database default value
        // This method is for future expansion
        return true;
    }
    
    private function validateUserData($userData) {
        $required = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (strlen($userData['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        
        if ($userData['role'] && !in_array($userData['role'], ['client', 'freelancer'])) {
            throw new Exception("Invalid user role");
        }
        
        return true;
    }
}