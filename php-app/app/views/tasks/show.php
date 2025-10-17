<?php 
$title = htmlspecialchars($task['title']) . " - DO4ME";
$description = substr(strip_tags($task['description']), 0, 160) . "...";
$css_files = ['/assets/css/tasks.css'];
include __DIR__ . '/../layout/header.php'; 
?>

<div class="container py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Task Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/tasks">Tasks</a></li>
                            <li class="breadcrumb-item"><a href="/categories/<?php echo $task['category']; ?>">
                                <?php echo $categories[$task['category']] ?? ucfirst($task['category']); ?>
                            </a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($task['title']); ?></li>
                        </ol>
                    </nav>
                    
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="h2 mb-2"><?php echo htmlspecialchars($task['title']); ?></h1>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-<?php echo getUrgencyBadgeClass($task['urgency']); ?> fs-6">
                                    <i class="fas fa-<?php echo $task['urgency'] === 'high' ? 'fire' : 'clock'; ?> me-1"></i>
                                    <?php echo ucfirst($task['urgency']); ?> Priority
                                </span>
                                <span class="badge bg-secondary fs-6">
                                    <?php echo $categories[$task['category']] ?? ucfirst($task['category']); ?>
                                </span>
                                <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?> fs-6">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <h3 class="text-primary mb-0">$<?php echo number_format($task['budget'], 2); ?></h3>
                            <small class="text-muted">Task Budget</small>
                        </div>
                    </div>
                    
                    <!-- Client Info -->
                    <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
                        <div class="avatar me-3">
                            <?php if ($task['client_avatar']): ?>
                                <img src="<?php echo $task['client_avatar']; ?>" alt="Client" class="rounded-circle" width="50" height="50">
                            <?php else: ?>
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px; font-size: 18px;">
                                    <?php echo strtoupper(substr($task['client_first_name'], 0, 1) . substr($task['client_last_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <a href="/profile/<?php echo $task['client_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($task['client_first_name'] . ' ' . $task['client_last_name']); ?>
                                </a>
                            </h6>
                            <div class="small text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>Location not specified •
                                <i class="fas fa-star text-warning me-1"></i>4.8 (24 reviews)
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Posted</small>
                            <small class="fw-bold"><?php echo date('M j, Y', strtotime($task['created_at'])); ?></small>
                        </div>
                    </div>
                    
                    <!-- Task Actions -->
                    <?php if ($canManage): ?>
                        <div class="task-actions mb-4">
                            <div class="btn-group" role="group">
                                <?php if ($isTaskOwner && $task['status'] === 'open'): ?>
                                    <a href="/tasks/<?php echo $task['id']; ?>/edit" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-2"></i>Edit Task
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteTaskModal">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </button>
                                <?php elseif ($task['freelancer_id'] == $session->getUserId()): ?>
                                    <?php if ($task['status'] === 'assigned'): ?>
                                        <form action="/tasks/<?php echo $task['id']; ?>/start" method="POST" class="d-inline">
                                            <?php echo $session->csrfField('start_task'); ?>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-play me-2"></i>Start Task
                                            </button>
                                        </form>
                                    <?php elseif ($task['status'] === 'in_progress'): ?>
                                        <form action="/tasks/<?php echo $task['id']; ?>/complete" method="POST" class="d-inline">
                                            <?php echo $session->csrfField('complete_task'); ?>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check me-2"></i>Mark Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php elseif ($isTaskOwner && $task['status'] === 'completed'): ?>
                                    <form action="/tasks/<?php echo $task['id']; ?>/approve" method="POST" class="d-inline">
                                        <?php echo $session->csrfField('approve_task'); ?>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-thumbs-up me-2"></i>Approve Work
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Task Description -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Task Description</h5>
                </div>
                <div class="card-body">
                    <div class="task-description">
                        <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                    </div>
                    
                    <?php if (!empty($task['attachments'])): ?>
                        <div class="attachments mt-4">
                            <h6><i class="fas fa-paperclip me-2"></i>Attachments</h6>
                            <?php $attachments = json_decode($task['attachments'], true); ?>
                            <div class="list-group">
                                <?php foreach ($attachments as $attachment): ?>
                                    <a href="<?php echo $attachment['path']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file-<?php echo getFileIcon($attachment['name']); ?> me-2"></i>
                                                <?php echo htmlspecialchars($attachment['name']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo round($attachment['size'] / 1024, 1); ?> KB</small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Task Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Task Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Category:</th>
                                    <td><?php echo $categories[$task['category']] ?? ucfirst($task['category']); ?></td>
                                </tr>
                                <tr>
                                    <th>Budget:</th>
                                    <td class="fw-bold text-primary">$<?php echo number_format($task['budget'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Duration:</th>
                                    <td><?php echo $task['duration_days']; ?> days</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                                            <?php echo ucfirst($task['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Posted:</th>
                                    <td><?php echo date('F j, Y', strtotime($task['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Proposals:</th>
                                    <td><?php echo count($proposals); ?> received</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Proposals Section (visible to task owner) -->
            <?php if ($isTaskOwner && !empty($proposals)): ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Proposals (<?php echo count($proposals); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($proposals as $proposal): ?>
                            <div class="proposal-card border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <?php if ($proposal['freelancer_avatar']): ?>
                                                <img src="<?php echo $proposal['freelancer_avatar']; ?>" alt="Freelancer" class="rounded-circle" width="40" height="40">
                                            <?php else: ?>
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px; font-size: 14px;">
                                                    <?php echo strtoupper(substr($proposal['freelancer_first_name'], 0, 1) . substr($proposal['freelancer_last_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">
                                                <a href="/profile/<?php echo $proposal['freelancer_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($proposal['freelancer_first_name'] . ' ' . $proposal['freelancer_last_name']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-star text-warning me-1"></i>4.7 • 
                                                <i class="fas fa-check-circle text-success me-1"></i>15 tasks completed
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="text-success mb-0">$<?php echo number_format($proposal['bid_amount'], 2); ?></h5>
                                        <small class="text-muted">Bid amount</small>
                                    </div>
                                </div>
                                
                                <div class="proposal-content mb-3">
                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($proposal['cover_letter'])); ?></p>
                                    <div class="small text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Estimated delivery: <?php echo $proposal['estimated_days']; ?> days
                                    </div>
                                </div>
                                
                                <?php if ($task['status'] === 'open'): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Submitted <?php echo date('M j, Y g:i A', strtotime($proposal['created_at'])); ?>
                                        </small>
                                        <form action="/tasks/<?php echo $task['id']; ?>/proposals/<?php echo $proposal['id']; ?>/accept" method="POST">
                                            <?php echo $session->csrfField('accept_proposal'); ?>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Accept Proposal
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <span class="badge bg-<?php echo $proposal['status'] === 'accepted' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($proposal['status']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Submit Proposal (for freelancers) -->
            <?php if ($canPropose && !$userProposal): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Submit Proposal</h5>
                    </div>
                    <div class="card-body">
                        <form id="proposal-form" method="POST" action="/tasks/<?php echo $task['id']; ?>/proposals">
                            <?php echo $session->csrfField('submit_proposal'); ?>
                            
                            <div class="mb-3">
                                <label for="bid_amount" class="form-label fw-bold">Your Bid Amount ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="bid_amount" name="bid_amount" 
                                           value="<?php echo $task['budget']; ?>" min="1" step="0.01" required>
                                </div>
                                <div class="form-text">
                                    Task budget: $<?php echo number_format($task['budget'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estimated_days" class="form-label fw-bold">Estimated Days</label>
                                <input type="number" class="form-control" id="estimated_days" name="estimated_days" 
                                       value="<?php echo $task['duration_days']; ?>" min="1" max="365" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cover_letter" class="form-label fw-bold">Cover Letter</label>
                                <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5" 
                                          placeholder="Explain why you're the best fit for this task. Describe your approach and relevant experience..." 
                                          required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-paper-plane me-2"></i>Submit Proposal
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif ($userProposal): ?>
                <!-- User's Proposal Status -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Your Proposal</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <h6 class="text-<?php echo $userProposal['status'] === 'accepted' ? 'success' : 'warning'; ?>">
                                <i class="fas fa-<?php echo $userProposal['status'] === 'accepted' ? 'check-circle' : 'clock'; ?> me-2"></i>
                                <?php echo ucfirst($userProposal['status']); ?>
                            </h6>
                            <p class="mb-2">Bid Amount: <strong>$<?php echo number_format($userProposal['bid_amount'], 2); ?></strong></p>
                            <p class="mb-3">Estimated: <strong><?php echo $userProposal['estimated_days']; ?> days</strong></p>
                            <a href="/proposals/<?php echo $userProposal['id']; ?>" class="btn btn-outline-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Task Stats -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Task Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="task-stats">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Proposals Received:</span>
                            <strong><?php echo count($proposals); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Avg. Bid Amount:</span>
                            <strong>$<?php echo number_format(calculateAverageBid($proposals), 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Task Views:</span>
                            <strong>1,247</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Interest Level:</span>
                            <strong>High</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Similar Tasks -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Similar Tasks</h5>
                </div>
                <div class="card-body">
                    <div class="similar-tasks">
                        <!-- This would be populated with similar tasks from the database -->
                        <div class="similar-task-item mb-3">
                            <h6 class="mb-1">
                                <a href="#" class="text-decoration-none">Need a website redesign</a>
                            </h6>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>$150</span>
                                <span>2 days ago</span>
                            </div>
                        </div>
                        <div class="similar-task-item mb-3">
                            <h6 class="mb-1">
                                <a href="#" class="text-decoration-none">Logo design for startup</a>
                            </h6>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>$80</span>
                                <span>1 day ago</span>
                            </div>
                        </div>
                        <div class="similar-task-item">
                            <h6 class="mb-1">
                                <a href="#" class="text-decoration-none">Social media graphics</a>
                            </h6>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>$50</span>
                                <span>3 hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Task Modal -->
<?php if ($isTaskOwner): ?>
    <div class="modal fade" id="deleteTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this task? This action cannot be undone.</p>
                    <p class="text-muted small">Any proposals submitted will be lost.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="/tasks/<?php echo $task['id']; ?>/delete" method="POST">
                        <?php echo $session->csrfField('delete_task'); ?>
                        <button type="submit" class="btn btn-danger">Delete Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Proposal form validation
    const proposalForm = document.getElementById('proposal-form');
    if (proposalForm) {
        proposalForm.addEventListener('submit', function(e) {
            const bidAmount = parseFloat(document.getElementById('bid_amount').value);
            const taskBudget = <?php echo $task['budget']; ?>;
            
            if (bidAmount < 1) {
                e.preventDefault();
                showToast('Bid amount must be at least $1', 'error');
                return;
            }
            
            if (bidAmount > taskBudget * 3) {
                if (!confirm('Your bid is significantly higher than the task budget. Are you sure you want to proceed?')) {
                    e.preventDefault();
                    return;
                }
            }
        });
    }
    
    // Character count for cover letter
    const coverLetter = document.getElementById('cover_letter');
    if (coverLetter) {
        coverLetter.addEventListener('input', function() {
            const charCount = this.value.length;
            if (charCount < 50) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
});

function getStatusBadgeClass(status) {
    switch (status) {
        case 'open': return 'success';
        case 'assigned': return 'primary';
        case 'in_progress': return 'warning';
        case 'completed': return 'info';
        case 'cancelled': return 'secondary';
        default: return 'secondary';
    }
}

function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf': return 'pdf';
        case 'doc': case 'docx': return 'word';
        case 'xls': case 'xlsx': return 'excel';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return 'image';
        case 'zip': case 'rar': return 'archive';
        default: return 'file';
    }
}

function calculateAverageBid(proposals) {
    if (proposals.length === 0) return 0;
    const total = proposals.reduce((sum, prop) => sum + prop.bid_amount, 0);
    return total / proposals.length;
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>