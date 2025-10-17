<?php
$currentUser = [
    'id' => $session->get('user_id'),
    'name' => $session->get('user_name'),
    'email' => $session->get('user_email'),
    'role' => $session->get('user_role')
];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'DO4ME - Get Things Done'; ?></title>
    <meta name="description" content="<?php echo $description ?? 'Connect with skilled freelancers or find meaningful work on DO4ME platform.'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/responsive.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if (isset($css_files)): ?>
        <?php foreach ($css_files as $css_file): ?>
            <link href="<?php echo $css_file; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?php echo $session->generateCsrfToken('ajax'); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand fw-bold text-primary" href="/">
                <i class="fas fa-handshake me-2"></i>DO4ME
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Main Navigation -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/tasks">
                            <i class="fas fa-tasks me-1"></i>Browse Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/freelancers">
                            <i class="fas fa-users me-1"></i>Find Freelancers
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-th me-1"></i>Categories
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/categories/writing"><i class="fas fa-pen me-2"></i>Writing & Translation</a></li>
                            <li><a class="dropdown-item" href="/categories/design"><i class="fas fa-paint-brush me-2"></i>Design & Creative</a></li>
                            <li><a class="dropdown-item" href="/categories/programming"><i class="fas fa-code me-2"></i>Programming & Tech</a></li>
                            <li><a class="dropdown-item" href="/categories/marketing"><i class="fas fa-chart-line me-2"></i>Digital Marketing</a></li>
                            <li><a class="dropdown-item" href="/categories/data-entry"><i class="fas fa-database me-2"></i>Data Entry</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/categories"><i class="fas fa-list me-2"></i>All Categories</a></li>
                        </ul>
                    </li>
                </ul>
                
                <!-- Search Bar -->
                <form class="d-none d-lg-flex me-3" action="/tasks" method="GET" style="min-width: 300px;">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search tasks or freelancers..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Right Navigation -->
                <ul class="navbar-nav">
                    <?php if ($session->isAuthenticated()): ?>
                        <!-- Post Task Button (for clients) -->
                        <?php if ($currentUser['role'] === 'client'): ?>
                            <li class="nav-item me-2 d-none d-lg-block">
                                <a href="/tasks/create" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Post a Task
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php
                                $unreadCount = 0; // This would come from database
                                if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 300px;">
                                <div class="dropdown-header bg-light">
                                    <strong>Notifications</strong>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-primary ms-2"><?php echo $unreadCount; ?> new</span>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-list" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Notifications would be loaded here -->
                                    <div class="text-center p-3 text-muted">
                                        <i class="fas fa-bell-slash fa-2x mb-2"></i><br>
                                        No new notifications
                                    </div>
                                </div>
                                <div class="dropdown-footer text-center p-2 border-top">
                                    <a href="/notifications" class="text-decoration-none">View All</a>
                                </div>
                            </div>
                        </li>
                        
                        <!-- Messages -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-envelope"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                    3
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 300px;">
                                <div class="dropdown-header bg-light">
                                    <strong>Messages</strong>
                                    <span class="badge bg-warning ms-2">3 unread</span>
                                </div>
                                <div class="message-list" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Messages would be loaded here -->
                                </div>
                                <div class="dropdown-footer text-center p-2 border-top">
                                    <a href="/messages" class="text-decoration-none">View All Messages</a>
                                </div>
                            </div>
                        </li>
                        
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-initials bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 32px; height: 32px; font-size: 14px;">
                                        <?php 
                                        $initials = '';
                                        $nameParts = explode(' ', $currentUser['name']);
                                        foreach ($nameParts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        echo substr($initials, 0, 2);
                                        ?>
                                    </div>
                                </div>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="/dashboard">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/profile">
                                        <i class="fas fa-user me-2"></i>My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/tasks/my-tasks">
                                        <i class="fas fa-tasks me-2"></i>My Tasks
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/payments/wallet">
                                        <i class="fas fa-wallet me-2"></i>My Wallet
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <?php if ($currentUser['role'] === 'client'): ?>
                                    <li>
                                        <a class="dropdown-item" href="/tasks/create">
                                            <i class="fas fa-plus me-2"></i>Post a Task
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a class="dropdown-item" href="/tasks">
                                            <i class="fas fa-search me-2"></i>Find Tasks
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/settings">
                                        <i class="fas fa-cog me-2"></i>Settings
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/help">
                                        <i class="fas fa-question-circle me-2"></i>Help & Support
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="/logout" method="POST" class="d-inline">
                                        <?php echo $session->csrfField('logout'); ?>
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest User -->
                        <li class="nav-item">
                            <a class="nav-link" href="/login">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary btn-sm" href="/register">
                                <i class="fas fa-user-plus me-1"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Search Bar -->
    <div class="d-lg-none bg-light border-bottom">
        <div class="container py-2">
            <form action="/tasks" method="GET">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search tasks...">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content Container -->
    <main class="main-content">
        <!-- Add padding for fixed navbar -->
        <div style="padding-top: 80px;"></div>
        
        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $session->getFlash('success'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($session->hasFlash('error')): ?>
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $session->getFlash('error'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($session->hasFlash('warning')): ?>
            <div class="container mt-3">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo $session->getFlash('warning'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>