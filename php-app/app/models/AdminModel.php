<?php
class AdminModel extends Database {
    
    public function getDashboardStats() {
        $stats = [];
        
        // Total users
        $stats['total_users'] = $this->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
        
        // Total tasks
        $stats['total_tasks'] = $this->query("SELECT COUNT(*) as count FROM tasks")->fetch()['count'];
        
        // Total earnings (platform fees)
        $stats['total_earnings'] = $this->query("
            SELECT SUM(platform_fee + transaction_fee) as earnings 
            FROM payments WHERE status = 'completed'
        ")->fetch()['earnings'] ?? 0;
        
        // Pending verifications
        $stats['pending_verifications'] = $this->query("
            SELECT COUNT(*) as count FROM users WHERE is_verified = 0
        ")->fetch()['count'];
        
        return $stats;
    }
    
    public function getUsersPaginated($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        return $this->query("
            SELECT id, email, first_name, last_name, role, country, 
                   wallet_balance, is_verified, is_approved, created_at
            FROM users 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ", [$perPage, $offset])->fetchAll();
    }
    
    public function getTasksPaginated($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        return $this->query("
            SELECT t.*, u.email as client_email, 
                   u.first_name as client_first_name, u.last_name as client_last_name
            FROM tasks t
            LEFT JOIN users u ON t.client_id = u.id
            ORDER BY t.created_at DESC 
            LIMIT ? OFFSET ?
        ", [$perPage, $offset])->fetchAll();
    }
    
    public function getPaymentsPaginated($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        return $this->query("
            SELECT p.*, u.email as user_email, 
                   u.first_name, u.last_name, t.title as task_title
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN tasks t ON p.task_id = t.id
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ", [$perPage, $offset])->fetchAll();
    }
    
    public function approveUser($userId) {
        return $this->query("
            UPDATE users SET is_approved = 1 WHERE id = ?
        ", [$userId]);
    }
    
    public function suspendUser($userId) {
        return $this->query("
            UPDATE users SET is_approved = 0 WHERE id = ?
        ", [$userId]);
    }
}