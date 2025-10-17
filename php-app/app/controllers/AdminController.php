<?php
class AdminController {
    private $userModel;
    private $taskModel;
    private $paymentModel;
    private $session;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->paymentModel = new PaymentModel();
        $this->session = new Session();
        $this->ensureAdminRole();
    }
    
    public function dashboard() {
        $stats = [
            'total_users' => $this->userModel->getTotalUsers(),
            'total_tasks' => $this->taskModel->getTotalTasks(),
            'total_earnings' => $this->paymentModel->getTotalEarnings(),
            'pending_verifications' => $this->userModel->getPendingVerifications()
        ];
        
        $recentTasks = $this->taskModel->getRecentTasks(10);
        $recentUsers = $this->userModel->getRecentUsers(10);
        
        $this->render('admin/dashboard', [
            'stats' => $stats,
            'recentTasks' => $recentTasks,
            'recentUsers' => $recentUsers
        ]);
    }
    
    public function users() {
        $page = $_GET['page'] ?? 1;
        $perPage = 20;
        $users = $this->userModel->getUsersPaginated($page, $perPage);
        $totalUsers = $this->userModel->getTotalUsers();
        
        $this->render('admin/users', [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalUsers,
                'totalPages' => ceil($totalUsers / $perPage)
            ]
        ]);
    }
    
    public function tasks() {
        $page = $_GET['page'] ?? 1;
        $perPage = 20;
        $tasks = $this->taskModel->getTasksPaginated($page, $perPage);
        $totalTasks = $this->taskModel->getTotalTasks();
        
        $this->render('admin/tasks', [
            'tasks' => $tasks,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalTasks,
                'totalPages' => ceil($totalTasks / $perPage)
            ]
        ]);
    }
    
    public function payments() {
        $page = $_GET['page'] ?? 1;
        $perPage = 20;
        $payments = $this->paymentModel->getPaymentsPaginated($page, $perPage);
        $totalPayments = $this->paymentModel->getTotalPayments();
        
        $this->render('admin/payments', [
            'payments' => $payments,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalPayments,
                'totalPages' => ceil($totalPayments / $perPage)
            ]
        ]);
    }
    
    public function approveUser($userId) {
        try {
            $this->userModel->approveUser($userId);
            $this->session->setFlash('success', 'User approved successfully.');
        } catch (Exception $e) {
            $this->session->setFlash('error', 'Failed to approve user: ' . $e->getMessage());
        }
        header('Location: /admin/users');
        exit;
    }
    
    public function suspendUser($userId) {
        try {
            $this->userModel->suspendUser($userId);
            $this->session->setFlash('success', 'User suspended successfully.');
        } catch (Exception $e) {
            $this->session->setFlash('error', 'Failed to suspend user: ' . $e->getMessage());
        }
        header('Location: /admin/users');
        exit;
    }
    
    public function deleteTask($taskId) {
        try {
            $this->taskModel->deleteTask($taskId);
            $this->session->setFlash('success', 'Task deleted successfully.');
        } catch (Exception $e) {
            $this->session->setFlash('error', 'Failed to delete task: ' . $e->getMessage());
        }
        header('Location: /admin/tasks');
        exit;
    }
    
    public function refundPayment($paymentId) {
        try {
            $this->paymentModel->refundPayment($paymentId);
            $this->session->setFlash('success', 'Payment refunded successfully.');
        } catch (Exception $e) {
            $this->session->setFlash('error', 'Failed to refund payment: ' . $e->getMessage());
        }
        header('Location: /admin/payments');
        exit;
    }
    
    private function ensureAdminRole() {
        if ($this->session->get('user_role') !== 'admin') {
            http_response_code(403);
            $this->renderError('Access denied. Admin privileges required.', 403);
            exit;
        }
    }
    
    private function render($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/admin/{$view}.php";
    }
    
    private function renderError($message, $code) {
        http_response_code($code);
        echo "<h1>$code Error</h1><p>$message</p>";
        exit;
    }
}