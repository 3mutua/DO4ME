<?php
class TaskModel extends Database {
    protected $table = 'tasks';
    
    public function create($taskData) {
        $this->validateTaskData($taskData);
        
        $taskId = $this->insert($this->table, $taskData);
        
        // Create initial task milestone if needed
        if ($taskData['has_milestones'] ?? false) {
            $this->createInitialMilestone($taskId, $taskData);
        }
        
        return $taskId;
    }
    
    public function findById($taskId) {
        return $this->query("
            SELECT t.*, 
                   c.first_name as client_first_name, 
                   c.last_name as client_last_name,
                   c.profile_picture as client_avatar,
                   f.first_name as freelancer_first_name,
                   f.last_name as freelancer_last_name,
                   f.profile_picture as freelancer_avatar
            FROM tasks t
            LEFT JOIN users c ON t.client_id = c.id
            LEFT JOIN users f ON t.freelancer_id = f.id
            WHERE t.id = ?
        ", [$taskId])->fetch();
    }
    
    public function update($taskData) {
        $allowedFields = ['title', 'description', 'budget', 'category', 'urgency', 'duration_days', 'attachments'];
        $data = array_intersect_key($taskData, array_flip($allowedFields));
        
        return $this->update($this->table, $data, 'id = ?', [$taskData['id']]);
    }
    
    public function delete($taskId) {
        return $this->delete($this->table, 'id = ? AND status = "draft"', [$taskId]);
    }
    
    public function assignFreelancer($taskId, $freelancerId) {
        $data = [
            'freelancer_id' => $freelancerId,
            'status' => 'assigned',
            'assigned_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($this->table, $data, 'id = ?', [$taskId]);
    }
    
    public function updateStatus($taskId, $status) {
        $data = ['status' => $status];
        
        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($this->table, $data, 'id = ?', [$taskId]);
    }
    
    public function getTasksWithFilters($filters = [], $page = 1, $perPage = 12) {
        $where = '1=1';
        $params = [];
        
        // Status filter
        if (!empty($filters['status'])) {
            $where .= " AND t.status = ?";
            $params[] = $filters['status'];
        } else {
            $where .= " AND t.status IN ('open', 'assigned', 'in_progress')";
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $where .= " AND t.category = ?";
            $params[] = $filters['category'];
        }
        
        // Budget range filter
        if (!empty($filters['budget_min'])) {
            $where .= " AND t.budget >= ?";
            $params[] = floatval($filters['budget_min']);
        }
        
        if (!empty($filters['budget_max'])) {
            $where .= " AND t.budget <= ?";
            $params[] = floatval($filters['budget_max']);
        }
        
        // Urgency filter
        if (!empty($filters['urgency'])) {
            $where .= " AND t.urgency = ?";
            $params[] = $filters['urgency'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        $sql = "
            SELECT t.*, 
                   c.first_name as client_first_name, 
                   c.last_name as client_last_name,
                   c.profile_picture as client_avatar,
                   COUNT(p.id) as proposals_count
            FROM tasks t
            LEFT JOIN users c ON t.client_id = c.id
            LEFT JOIN proposals p ON t.id = p.task_id AND p.status = 'pending'
            WHERE {$where}
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ";
        
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function getTotalTasksWithFilters($filters = []) {
        $where = '1=1';
        $params = [];
        
        if (!empty($filters['status'])) {
            $where .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        return $this->count($this->table, $where, $params);
    }
    
    public function getUserTasks($userId, $page = 1, $perPage = 10) {
        return $this->paginate(
            $this->table, 
            $page, 
            $perPage, 
            'client_id = ?', 
            [$userId], 
            'created_at DESC'
        );
    }
    
    public function getFreelancerTasks($userId, $page = 1, $perPage = 10) {
        return $this->paginate(
            $this->table, 
            $page, 
            $perPage, 
            'freelancer_id = ?', 
            [$userId], 
            'created_at DESC'
        );
    }
    
    public function getUserTaskCount($userId) {
        return $this->count($this->table, 'client_id = ?', [$userId]);
    }
    
    public function getUserActiveTaskCount($userId) {
        return $this->count($this->table, 'client_id = ? AND status IN ("assigned", "in_progress")', [$userId]);
    }
    
    public function getUserCompletedTaskCount($userId) {
        return $this->count($this->table, 'client_id = ? AND status = "completed"', [$userId]);
    }
    
    public function getUserOpenTaskCount($userId) {
        return $this->count($this->table, 'client_id = ? AND status = "open"', [$userId]);
    }
    
    public function getUserTotalSpent($userId) {
        $result = $this->query("
            SELECT COALESCE(SUM(budget), 0) as total_spent 
            FROM tasks 
            WHERE client_id = ? AND status = 'completed'
        ", [$userId])->fetch();
        
        return $result['total_spent'] ?? 0;
    }
    
    public function getFreelancerTotalEarnings($userId) {
        $result = $this->query("
            SELECT COALESCE(SUM(budget), 0) as total_earnings 
            FROM tasks 
            WHERE freelancer_id = ? AND status = 'completed'
        ", [$userId])->fetch();
        
        return $result['total_earnings'] ?? 0;
    }
    
    public function getFreelancerCompletedTaskCount($userId) {
        return $this->count($this->table, 'freelancer_id = ? AND status = "completed"', [$userId]);
    }
    
    public function getFreelancerActiveTaskCount($userId) {
        return $this->count($this->table, 'freelancer_id = ? AND status IN ("assigned", "in_progress")', [$userId]);
    }
    
    public function getFreelancerSuccessRate($userId) {
        $total = $this->count($this->table, 'freelancer_id = ? AND status IN ("completed", "cancelled")', [$userId]);
        $completed = $this->count($this->table, 'freelancer_id = ? AND status = "completed"', [$userId]);
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
    
    public function getAvailableTasksForFreelancer($userId, $limit = 10) {
        return $this->query("
            SELECT t.*, c.first_name, c.last_name, c.profile_picture
            FROM tasks t
            JOIN users c ON t.client_id = c.id
            WHERE t.status = 'open' 
            AND t.client_id != ?
            AND t.id NOT IN (
                SELECT task_id FROM proposals WHERE freelancer_id = ?
            )
            ORDER BY t.created_at DESC
            LIMIT ?
        ", [$userId, $userId, $limit])->fetchAll();
    }
    
    public function getUserRecentTasks($userId, $limit = 5) {
        return $this->select($this->table, '*', 'client_id = ?', [$userId], 'created_at DESC', $limit);
    }
    
    public function getFreelancerRecentTasks($userId, $limit = 5) {
        return $this->select($this->table, '*', 'freelancer_id = ?', [$userId], 'created_at DESC', $limit);
    }
    
    public function getUserPendingProposalsCount($userId) {
        return $this->query("
            SELECT COUNT(*) as count
            FROM proposals p
            JOIN tasks t ON p.task_id = t.id
            WHERE t.client_id = ? AND p.status = 'pending'
        ", [$userId])->fetchColumn();
    }
    
    public function saveAttachments($taskId, $attachments) {
        $current = $this->findById($taskId);
        $currentAttachments = json_decode($current['attachments'] ?? '[]', true);
        $updatedAttachments = array_merge($currentAttachments, $attachments);
        
        return $this->update($this->table, ['attachments' => json_encode($updatedAttachments)], 'id = ?', [$taskId]);
    }
    
    public function getTaskCategories() {
        return [
            'writing' => 'Writing & Translation',
            'design' => 'Design & Creative',
            'programming' => 'Programming & Tech',
            'marketing' => 'Digital Marketing',
            'data_entry' => 'Data Entry',
            'customer_service' => 'Customer Service',
            'other' => 'Other'
        ];
    }
    
    private function validateTaskData($taskData) {
        $required = ['title', 'description', 'budget', 'client_id'];
        foreach ($required as $field) {
            if (empty($taskData[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if ($taskData['budget'] < 5) {
            throw new Exception("Minimum budget is $5");
        }
        
        if ($taskData['budget'] > 100000) {
            throw new Exception("Maximum budget is $100,000");
        }
        
        $categories = array_keys($this->getTaskCategories());
        if (!in_array($taskData['category'], $categories)) {
            throw new Exception("Invalid task category");
        }
        
        return true;
    }
    
    private function createInitialMilestone($taskId, $taskData) {
        $milestoneData = [
            'task_id' => $taskId,
            'title' => 'Initial Delivery',
            'description' => 'Complete the main task requirements',
            'amount' => $taskData['budget'],
            'due_date' => date('Y-m-d', strtotime("+{$taskData['duration_days']} days"))
        ];
        
        return $this->insert('task_milestones', $milestoneData);
    }
}