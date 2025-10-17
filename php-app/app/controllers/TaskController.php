<?php
class TaskController {
    private $taskModel;
    private $userModel;
    private $proposalModel;
    private $session;
    private $validator;
    
    public function __construct() {
        $this->taskModel = new TaskModel();
        $this->userModel = new UserModel();
        $this->proposalModel = new ProposalModel();
        $this->session = new Session();
        $this->validator = new Validator();
        $this->ensureAuth();
    }
    
    /**
     * Display all tasks with filtering and pagination
     */
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 12;
        
        // Get filter parameters
        $filters = [
            'category' => $_GET['category'] ?? '',
            'budget_min' => $_GET['budget_min'] ?? '',
            'budget_max' => $_GET['budget_max'] ?? '',
            'urgency' => $_GET['urgency'] ?? '',
            'status' => $_GET['status'] ?? 'open',
            'search' => $_GET['search'] ?? ''
        ];
        
        $tasks = $this->taskModel->getTasksWithFilters($filters, $page, $perPage);
        $totalTasks = $this->taskModel->getTotalTasksWithFilters($filters);
        
        $this->render('tasks/index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalTasks,
                'totalPages' => ceil($totalTasks / $perPage)
            ],
            'categories' => $this->getTaskCategories()
        ]);
    }
    
    /**
     * Show task creation form
     */
    public function create() {
        $this->ensureClientRole();
        $this->render('tasks/create', [
            'categories' => $this->getTaskCategories()
        ]);
    }
    
    /**
     * Store new task
     */
    public function store() {
        $this->ensureClientRole();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $taskData = $this->validateTaskData($_POST);
            $taskData['client_id'] = $this->session->get('user_id');
            
            $taskId = $this->taskModel->create($taskData);
            
            // Handle file uploads if any
            if (!empty($_FILES['attachments']['name'][0])) {
                $this->handleTaskAttachments($taskId, $_FILES['attachments']);
            }
            
            // Log activity
            ActivityLogger::logTaskCreated($taskId, $taskData['client_id']);
            
            $this->session->setFlash('success', 'Task created successfully!');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            $this->render('tasks/create', [
                'formData' => $_POST,
                'categories' => $this->getTaskCategories(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show single task details
     */
    public function show($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');
        
        // Get task proposals (only visible to task owner and admins)
        $proposals = [];
        if ($task['client_id'] == $userId || $userRole === 'admin') {
            $proposals = $this->proposalModel->getProposalsByTask($taskId);
        }
        
        // Check if current user can submit proposal
        $canPropose = $this->canUserPropose($task, $userId, $userRole);
        
        // Check if user has already proposed
        $userProposal = $this->proposalModel->getUserProposalForTask($taskId, $userId);
        
        $this->render('tasks/show', [
            'task' => $task,
            'proposals' => $proposals,
            'canPropose' => $canPropose,
            'userProposal' => $userProposal,
            'isTaskOwner' => $task['client_id'] == $userId,
            'canManage' => $this->canManageTask($task, $userId, $userRole)
        ]);
    }
    
    /**
     * Show task edit form
     */
    public function edit($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $this->ensureTaskOwnership($task);
        
        $this->render('tasks/edit', [
            'task' => $task,
            'categories' => $this->getTaskCategories()
        ]);
    }
    
    /**
     * Update task
     */
    public function update($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $this->ensureTaskOwnership($task);
        
        try {
            $taskData = $this->validateTaskData($_POST);
            $taskData['id'] = $taskId;
            
            $this->taskModel->update($taskData);
            
            ActivityLogger::logTaskUpdated($taskId, $this->session->get('user_id'));
            
            $this->session->setFlash('success', 'Task updated successfully!');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            $this->render('tasks/edit', [
                'task' => array_merge($task, $_POST),
                'categories' => $this->getTaskCategories(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete task
     */
    public function delete($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $this->ensureTaskOwnership($task);
        
        try {
            $this->taskModel->delete($taskId);
            
            ActivityLogger::logTaskDeleted($taskId, $this->session->get('user_id'));
            
            $this->session->setFlash('success', 'Task deleted successfully!');
            header('Location: /tasks');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', 'Failed to delete task: ' . $e->getMessage());
            header('Location: /tasks/' . $taskId);
        }
    }
    
    /**
     * Submit proposal for a task
     */
    public function submitProposal($taskId) {
        $this->ensureFreelancerRole();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $task = $this->taskModel->findById($taskId);
            
            if (!$task || $task['status'] !== 'open') {
                throw new Exception('Task not available for proposals');
            }
            
            $proposalData = $this->validateProposalData($_POST);
            $proposalData['task_id'] = $taskId;
            $proposalData['freelancer_id'] = $this->session->get('user_id');
            
            // Check if user has already proposed
            if ($this->proposalModel->getUserProposalForTask($taskId, $proposalData['freelancer_id'])) {
                throw new Exception('You have already submitted a proposal for this task');
            }
            
            $proposalId = $this->proposalModel->create($proposalData);
            
            // Notify task owner
            $this->notifyTaskOwnerNewProposal($taskId, $proposalId);
            
            ActivityLogger::logProposalSubmitted($proposalId, $proposalData['freelancer_id']);
            
            $this->session->setFlash('success', 'Proposal submitted successfully!');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /tasks/' . $taskId);
        }
    }
    
    /**
     * Accept a proposal
     */
    public function acceptProposal($taskId, $proposalId) {
        $task = $this->taskModel->findById($taskId);
        $proposal = $this->proposalModel->findById($proposalId);
        
        if (!$task || !$proposal) {
            $this->renderError('Task or proposal not found', 404);
            return;
        }
        
        $this->ensureTaskOwnership($task);
        
        try {
            if ($task['status'] !== 'open') {
                throw new Exception('Task is no longer available');
            }
            
            if ($proposal['task_id'] != $taskId) {
                throw new Exception('Proposal does not belong to this task');
            }
            
            // Begin transaction
            $this->taskModel->beginTransaction();
            
            // Update task status and assign freelancer
            $this->taskModel->assignFreelancer($taskId, $proposal['freelancer_id']);
            
            // Accept the proposal
            $this->proposalModel->updateStatus($proposalId, 'accepted');
            
            // Reject all other proposals
            $this->proposalModel->rejectOtherProposals($taskId, $proposalId);
            
            // Notify freelancer
            $this->notifyFreelancerProposalAccepted($proposalId);
            
            $this->taskModel->commit();
            
            ActivityLogger::logProposalAccepted($proposalId, $this->session->get('user_id'));
            
            $this->session->setFlash('success', 'Proposal accepted! The freelancer has been assigned to this task.');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->taskModel->rollBack();
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /tasks/' . $taskId);
        }
    }
    
    /**
     * Start working on a task (freelancer)
     */
    public function startTask($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $this->ensureTaskAssignment($task);
        
        try {
            $this->taskModel->updateStatus($taskId, 'in_progress');
            
            ActivityLogger::logTaskStarted($taskId, $this->session->get('user_id'));
            
            $this->session->setFlash('success', 'Task marked as in progress!');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /tasks/' . $taskId);
        }
    }
    
    /**
     * Mark task as completed (freelancer)
     */
    public function completeTask($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $this->ensureTaskAssignment($task);
        
        try {
            $this->taskModel->updateStatus($taskId, 'completed');
            
            ActivityLogger::logTaskCompleted($taskId, $this->session->get('user_id'));
            
            $this->session->setFlash('success', 'Task marked as completed! Waiting for client approval.');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /tasks/' . $taskId);
        }
    }
    
    /**
     * Client approves completed task
     */
    public function approveTask($taskId) {
        $task = $this->taskModel->findById($taskId);
        
        if (!$task) {
            $this->renderError('Task not found', 404);
            return;
        }
        
        $this->ensureTaskOwnership($task);
        
        try {
            if ($task['status'] !== 'completed') {
                throw new Exception('Task is not marked as completed');
            }
            
            $this->taskModel->updateStatus($taskId, 'approved');
            
            ActivityLogger::logTaskApproved($taskId, $this->session->get('user_id'));
            
            $this->session->setFlash('success', 'Task approved successfully!');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /tasks/' . $taskId);
        }
    }
    
    /**
     * API endpoint for tasks (AJAX)
     */
    public function apiIndex() {
        header('Content-Type: application/json');
        
        try {
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = intval($_GET['per_page'] ?? 12);
            
            $filters = [
                'category' => $_GET['category'] ?? '',
                'budget_min' => $_GET['budget_min'] ?? '',
                'budget_max' => $_GET['budget_max'] ?? '',
                'urgency' => $_GET['urgency'] ?? '',
                'status' => $_GET['status'] ?? 'open',
                'search' => $_GET['search'] ?? ''
            ];
            
            $tasks = $this->taskModel->getTasksWithFilters($filters, $page, $perPage);
            $totalTasks = $this->taskModel->getTotalTasksWithFilters($filters);
            
            echo json_encode([
                'success' => true,
                'tasks' => $tasks,
                'pagination' => [
                    'page' => $page,
                    'perPage' => $perPage,
                    'total' => $totalTasks,
                    'totalPages' => ceil($totalTasks / $perPage)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validate task data
     */
    private function validateTaskData($data) {
        $rules = [
            'title' => 'required|min:10|max:200',
            'description' => 'required|min:50|max:2000',
            'budget' => 'required|numeric|min:5|max:100000',
            'category' => 'required|in:' . implode(',', array_keys($this->getTaskCategories())),
            'urgency' => 'required|in:low,medium,high',
            'duration_days' => 'required|integer|min:1|max:365'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Validate proposal data
     */
    private function validateProposalData($data) {
        $rules = [
            'bid_amount' => 'required|numeric|min:1',
            'cover_letter' => 'required|min:50|max:1000',
            'estimated_days' => 'required|integer|min:1|max:180'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Handle task file attachments
     */
    private function handleTaskAttachments($taskId, $files) {
        $uploadDir = __DIR__ . '/../../public/assets/uploads/tasks/' . $taskId . '/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $attachments = [];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . $files['name'][$i];
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                    $attachments[] = [
                        'name' => $files['name'][$i],
                        'path' => '/assets/uploads/tasks/' . $taskId . '/' . $fileName,
                        'size' => $files['size'][$i]
                    ];
                }
            }
        }
        
        if (!empty($attachments)) {
            $this->taskModel->saveAttachments($taskId, $attachments);
        }
    }
    
    /**
     * Check if user can propose to task
     */
    private function canUserPropose($task, $userId, $userRole) {
        if ($userRole !== 'freelancer') {
            return false;
        }
        
        if ($task['client_id'] == $userId) {
            return false; // Can't propose to own task
        }
        
        if ($task['status'] !== 'open') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user can manage task
     */
    private function canManageTask($task, $userId, $userRole) {
        if ($userRole === 'admin') {
            return true;
        }
        
        if ($task['client_id'] == $userId) {
            return true;
        }
        
        if ($task['freelancer_id'] == $userId) {
            return true;
        }
        
        return false;
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
     * Ensure user has client role
     */
    private function ensureClientRole() {
        if ($this->session->get('user_role') !== 'client') {
            $this->renderError('Access denied. Client role required.', 403);
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
     * Ensure user owns the task
     */
    private function ensureTaskOwnership($task) {
        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');
        
        if ($task['client_id'] != $userId && $userRole !== 'admin') {
            $this->renderError('Access denied. You do not own this task.', 403);
            exit;
        }
    }
    
    /**
     * Ensure user is assigned to the task
     */
    private function ensureTaskAssignment($task) {
        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');
        
        if ($task['freelancer_id'] != $userId && $userRole !== 'admin') {
            $this->renderError('Access denied. You are not assigned to this task.', 403);
            exit;
        }
    }
    
    /**
     * Get task categories
     */
    private function getTaskCategories() {
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
    
    /**
     * Notify task owner of new proposal
     */
    private function notifyTaskOwnerNewProposal($taskId, $proposalId) {
        // Implementation would send notification/email
        error_log("New proposal #$proposalId for task #$taskId");
    }
    
    /**
     * Notify freelancer of accepted proposal
     */
    private function notifyFreelancerProposalAccepted($proposalId) {
        // Implementation would send notification/email
        error_log("Proposal #$proposalId accepted");
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