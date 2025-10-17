<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DO4ME</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../layout/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../layout/' . ($userType === 'client' ? 'client-sidebar.php' : 'freelancer-sidebar.php'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($userType === 'client'): ?>
                            <a href="/tasks/create" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Post a Task
                            </a>
                        <?php else: ?>
                            <a href="/tasks" class="btn btn-primary">
                                <i class="fas fa-search"></i> Find Tasks
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <?php if ($userType === 'client'): ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Tasks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['totalTasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Tasks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['activeTasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Spent</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                $<?php echo number_format($stats['totalSpent'], 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Pending Proposals</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['pendingProposals']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: // Freelancer Dashboard ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Earnings</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                $<?php echo number_format($stats['totalEarnings'], 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Completed Tasks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['completedTasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Active Tasks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['activeTasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Success Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['successRate']; ?>%
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Tasks</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentTasks)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recentTasks as $task): ?>
                                            <a href="/tasks/<?php echo $task['id']; ?>" 
                                               class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                    <small class="text-muted">$<?php echo number_format($task['budget'], 2); ?></small>
                                                </div>
                                                <p class="mb-1 text-muted"><?php echo substr($task['description'], 0, 100); ?>...</p>
                                                <small class="text-muted">
                                                    Status: <span class="badge bg-<?php echo $this->getStatusBadgeClass($task['status']); ?>">
                                                        <?php echo ucfirst($task['status']); ?>
                                                    </span>
                                                    • Created: <?php echo date('M j, Y', strtotime($task['created_at'])); ?>
                                                </small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent tasks found.</p>
                                    <?php if ($userType === 'client'): ?>
                                        <a href="/tasks/create" class="btn btn-primary btn-sm">Post Your First Task</a>
                                    <?php else: ?>
                                        <a href="/tasks" class="btn btn-primary btn-sm">Browse Available Tasks</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if ($userType === 'client'): ?>
                                        <a href="/tasks/create" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-2"></i>Post New Task
                                        </a>
                                        <a href="/tasks" class="btn btn-outline-secondary">
                                            <i class="fas fa-tasks me-2"></i>View My Tasks
                                        </a>
                                        <a href="/payments/deposit" class="btn btn-outline-success">
                                            <i class="fas fa-wallet me-2"></i>Add Funds
                                        </a>
                                    <?php else: ?>
                                        <a href="/tasks" class="btn btn-outline-primary">
                                            <i class="fas fa-search me-2"></i>Find Tasks
                                        </a>
                                        <a href="/tasks/my-proposals" class="btn btn-outline-secondary">
                                            <i class="fas fa-paper-plane me-2"></i>My Proposals
                                        </a>
                                        <a href="/payments/withdraw" class="btn btn-outline-success">
                                            <i class="fas fa-money-bill-wave me-2"></i>Withdraw Earnings
                                        </a>
                                    <?php endif; ?>
                                    <a href="/profile" class="btn btn-outline-info">
                                        <i class="fas fa-user me-2"></i>Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $notifications = $userModel->getUserNotifications($session->getUserId(), 5);
                                if (!empty($notifications)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($notifications as $notification): ?>
                                            <div class="list-group-item <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                    <small class="text-muted"><?php echo $this->timeAgo($notification['created_at']); ?></small>
                                                </div>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-2">
                                        <a href="/notifications" class="btn btn-sm btn-outline-primary">View All</a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No new notifications</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>