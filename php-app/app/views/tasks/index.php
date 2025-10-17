<?php 
$title = "Browse Tasks - DO4ME";
$description = "Find freelance tasks and projects. Browse available opportunities across various categories.";
$css_files = ['/assets/css/tasks.css'];
include __DIR__ . '/../layout/header.php'; 
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                </div>
                <div class="card-body">
                    <form id="task-filters" method="GET" action="/tasks">
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <div class="category-filters">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" value="" 
                                           id="category-all" <?php echo empty($filters['category']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="category-all">All Categories</label>
                                </div>
                                <?php foreach ($categories as $value => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="category" value="<?php echo $value; ?>" 
                                               id="category-<?php echo $value; ?>" 
                                               <?php echo ($filters['category'] ?? '') === $value ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-<?php echo $value; ?>">
                                            <?php echo $label; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Budget Range -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Budget Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="budget_min" 
                                           placeholder="Min" value="<?php echo $filters['budget_min'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="budget_max" 
                                           placeholder="Max" value="<?php echo $filters['budget_max'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Urgency -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Urgency</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="urgency" value="" 
                                       id="urgency-any" <?php echo empty($filters['urgency']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="urgency-any">Any Urgency</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="urgency" value="high" 
                                       id="urgency-high" <?php echo ($filters['urgency'] ?? '') === 'high' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-danger" for="urgency-high">
                                    <i class="fas fa-fire me-1"></i>High Priority
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="urgency" value="medium" 
                                       id="urgency-medium" <?php echo ($filters['urgency'] ?? '') === 'medium' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-warning" for="urgency-medium">Medium Priority</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="urgency" value="low" 
                                       id="urgency-low" <?php echo ($filters['urgency'] ?? '') === 'low' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-success" for="urgency-low">Low Priority</label>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Task Status</label>
                            <select class="form-select" name="status">
                                <option value="open" <?php echo ($filters['status'] ?? 'open') === 'open' ? 'selected' : ''; ?>>Open Tasks</option>
                                <option value="assigned" <?php echo ($filters['status'] ?? '') === 'assigned' ? 'selected' : ''; ?>>Assigned Tasks</option>
                                <option value="in_progress" <?php echo ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo ($_GET['sort'] ?? 'newest') === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="budget_high" <?php echo ($_GET['sort'] ?? '') === 'budget_high' ? 'selected' : ''; ?>>Budget: High to Low</option>
                                <option value="budget_low" <?php echo ($_GET['sort'] ?? '') === 'budget_low' ? 'selected' : ''; ?>>Budget: Low to High</option>
                                <option value="urgent" <?php echo ($_GET['sort'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Most Urgent</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                            <a href="/tasks" class="btn btn-outline-secondary">Reset Filters</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="fw-bold">Marketplace Stats</h6>
                    <div class="small">
                        <div class="d-flex justify-content-between">
                            <span>Open Tasks:</span>
                            <span class="fw-bold"><?php echo number_format($totalTasks); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Avg. Budget:</span>
                            <span class="fw-bold">$150</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Avg. Response Time:</span>
                            <span class="fw-bold">2 hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Available Tasks</h1>
                    <p class="text-muted mb-0">
                        <?php if (!empty($filters['category'])): ?>
                            Showing tasks in <?php echo $categories[$filters['category']]; ?>
                        <?php else: ?>
                            Showing all available tasks
                        <?php endif; ?>
                        (<?php echo number_format($totalTasks); ?> found)
                    </p>
                </div>
                
                <div class="d-flex gap-2">
                    <!-- View Toggle -->
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="view-mode" id="view-grid" checked>
                        <label class="btn btn-outline-primary" for="view-grid" data-bs-toggle="tooltip" title="Grid View">
                            <i class="fas fa-th"></i>
                        </label>
                        
                        <input type="radio" class="btn-check" name="view-mode" id="view-list">
                        <label class="btn btn-outline-primary" for="view-list" data-bs-toggle="tooltip" title="List View">
                            <i class="fas fa-list"></i>
                        </label>
                    </div>
                    
                    <?php if ($session->isAuthenticated() && $session->getUserRole() === 'client'): ?>
                        <a href="/tasks/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Post a Task
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="card mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="/tasks" class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light" name="search" 
                                       placeholder="Search tasks by keywords, skills, or requirements..." 
                                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tasks Grid -->
            <div id="tasks-container">
                <?php if (empty($tasks)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <h4>No tasks found</h4>
                        <p class="text-muted">Try adjusting your filters or search terms.</p>
                        <?php if ($session->isAuthenticated() && $session->getUserRole() === 'client'): ?>
                            <a href="/tasks/create" class="btn btn-primary">Post the First Task</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row" id="tasks-grid">
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="task-card card h-100 shadow-sm">
                                    <div class="card-body">
                                        <!-- Task Header -->
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-<?php echo getUrgencyBadgeClass($task['urgency']); ?>">
                                                <?php echo ucfirst($task['urgency']); ?>
                                            </span>
                                            <small class="text-muted">
                                                <?php echo timeAgo($task['created_at']); ?>
                                            </small>
                                        </div>
                                        
                                        <!-- Task Title -->
                                        <h5 class="card-title">
                                            <a href="/tasks/<?php echo $task['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </a>
                                        </h5>
                                        
                                        <!-- Task Description Excerpt -->
                                        <p class="card-text text-muted small">
                                            <?php echo substr(strip_tags($task['description']), 0, 120); ?>...
                                        </p>
                                        
                                        <!-- Task Meta -->
                                        <div class="task-meta small text-muted mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo $categories[$task['category']] ?? $task['category']; ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-comments me-1"></i>
                                                    <?php echo $task['proposals_count'] ?? 0; ?> proposals
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Budget -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="text-primary mb-0">
                                                $<?php echo number_format($task['budget'], 2); ?>
                                            </h5>
                                            <a href="/tasks/<?php echo $task['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Card Footer -->
                                    <div class="card-footer bg-light">
                                        <div class="d-flex justify-content-between align-items-center small">
                                            <span class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($task['client_first_name'] . ' ' . $task['client_last_name']); ?>
                                            </span>
                                            <span class="text-muted">
                                                <i class="fas fa-star me-1 text-warning"></i>
                                                4.8
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav aria-label="Task pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Previous Page -->
                                <li class="page-item <?php echo $pagination['page'] <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['page'] - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <!-- Page Numbers -->
                                <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['totalPages'], $pagination['page'] + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $pagination['page'] ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Next Page -->
                                <li class="page-item <?php echo $pagination['page'] >= $pagination['totalPages'] ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['page'] + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View mode toggle
    const viewGrid = document.getElementById('view-grid');
    const viewList = document.getElementById('view-list');
    const tasksGrid = document.getElementById('tasks-grid');
    
    viewGrid.addEventListener('change', function() {
        if (this.checked) {
            tasksGrid.className = 'row';
            localStorage.setItem('tasks-view-mode', 'grid');
        }
    });
    
    viewList.addEventListener('change', function() {
        if (this.checked) {
            tasksGrid.className = 'row row-cols-1';
            localStorage.setItem('tasks-view-mode', 'list');
        }
    });
    
    // Load saved view mode
    const savedViewMode = localStorage.getItem('tasks-view-mode') || 'grid';
    if (savedViewMode === 'list') {
        viewList.checked = true;
        tasksGrid.className = 'row row-cols-1';
    }
    
    // Real-time filter updates
    const filterForm = document.getElementById('task-filters');
    const filterInputs = filterForm.querySelectorAll('input, select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // For better UX, we could add debouncing here
            // filterForm.submit();
        });
    });
});

function getUrgencyBadgeClass(urgency) {
    switch (urgency) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'success';
        default: return 'secondary';
    }
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return date.toLocaleDateString();
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>