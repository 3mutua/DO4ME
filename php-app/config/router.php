<?php
$router = new Router();

// ==================== AUTHENTICATION ROUTES ====================
$router->add('GET', '/login', 'AuthController@login');
$router->add('POST', '/login', 'AuthController@handleLogin');
$router->add('GET', '/register', 'AuthController@register');
$router->add('POST', '/register', 'AuthController@handleRegister');
$router->add('POST', '/logout', 'AuthController@logout');
$router->add('GET', '/forgot-password', 'AuthController@forgotPassword');
$router->add('POST', '/forgot-password', 'AuthController@sendResetLink');
$router->add('GET', '/reset-password', 'AuthController@resetPassword');
$router->add('POST', '/reset-password', 'AuthController@handleResetPassword');
$router->add('GET', '/verify-email', 'AuthController@verifyEmail');

// ==================== TASK ROUTES ====================
$router->add('GET', '/tasks', 'TaskController@index');
$router->add('GET', '/tasks/create', 'TaskController@create', [AuthMiddleware::class, 'requireRole'], ['client']);
$router->add('POST', '/tasks', 'TaskController@store', [AuthMiddleware::class, 'requireRole'], ['client']);
$router->add('GET', '/tasks/{id}', 'TaskController@show');
$router->add('GET', '/tasks/{id}/edit', 'TaskController@edit', [AuthMiddleware::class, 'requireOwnership']);
$router->add('POST', '/tasks/{id}/update', 'TaskController@update', [AuthMiddleware::class, 'requireOwnership']);
$router->add('POST', '/tasks/{id}/delete', 'TaskController@delete', [AuthMiddleware::class, 'requireOwnership']);
$router->add('POST', '/tasks/{id}/proposals', 'TaskController@submitProposal', [AuthMiddleware::class, 'requireRole'], ['freelancer']);
$router->add('POST', '/tasks/{taskId}/proposals/{proposalId}/accept', 'TaskController@acceptProposal', [AuthMiddleware::class, 'requireOwnership']);
$router->add('POST', '/tasks/{id}/start', 'TaskController@startTask', [AuthMiddleware::class, 'canManageTask']);
$router->add('POST', '/tasks/{id}/complete', 'TaskController@completeTask', [AuthMiddleware::class, 'canManageTask']);
$router->add('POST', '/tasks/{id}/approve', 'TaskController@approveTask', [AuthMiddleware::class, 'requireOwnership']);

// ==================== USER & DASHBOARD ROUTES ====================
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/dashboard', 'UserController@dashboard', [AuthMiddleware::class, 'handle']);
$router->add('GET', '/profile', 'UserController@profile', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/profile', 'UserController@updateProfile', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/profile/change-password', 'UserController@changePassword', [AuthMiddleware::class, 'handle']);
$router->add('GET', '/profile/{id}', 'UserController@publicProfile');
$router->add('GET', '/become-freelancer', 'UserController@becomeFreelancer', [AuthMiddleware::class, 'requireRole'], ['client']);
$router->add('POST', '/become-freelancer', 'UserController@handleBecomeFreelancer', [AuthMiddleware::class, 'requireRole'], ['client']);
$router->add('GET', '/settings', 'UserController@settings', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/settings', 'UserController@updateSettings', [AuthMiddleware::class, 'handle']);

// ==================== PAYMENT ROUTES ====================
$router->add('GET', '/payments/wallet', 'PaymentController@wallet', [AuthMiddleware::class, 'handle']);
$router->add('GET', '/payments/deposit', 'PaymentController@deposit', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/payments/deposit', 'PaymentController@handleDeposit', [AuthMiddleware::class, 'handle']);
$router->add('GET', '/payments/withdraw', 'PaymentController@withdraw', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/payments/withdraw', 'PaymentController@handleWithdrawal', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/payments/task/{taskId}', 'PaymentController@payTask', [AuthMiddleware::class, 'requireOwnership']);
$router->add('GET', '/payments/success', 'PaymentController@success');
$router->add('GET', '/payments/failed', 'PaymentController@failed');
$router->add('POST', '/payments/webhook/stripe', 'PaymentController@stripeWebhook');
$router->add('POST', '/payments/webhook/mpesa', 'PaymentController@mpesaWebhook');
$router->add('GET', '/payments/callback', 'PaymentController@callback');

// ==================== MESSAGE & NOTIFICATION ROUTES ====================
$router->add('GET', '/messages', 'MessageController@index', [AuthMiddleware::class, 'handle']);
$router->add('GET', '/messages/{taskId}', 'MessageController@show', [AuthMiddleware::class, 'canAccessTask']);
$router->add('POST', '/messages/{taskId}', 'MessageController@sendMessage', [AuthMiddleware::class, 'canAccessTask']);
$router->add('GET', '/notifications', 'UserController@notifications', [AuthMiddleware::class, 'handle']);
$router->add('POST', '/notifications/{id}/read', 'UserController@markNotificationRead', [AuthMiddleware::class, 'handle']);

// ==================== ADMIN ROUTES ====================
$router->add('GET', '/admin', 'AdminController@dashboard', [AuthMiddleware::class, 'requireRole'], ['admin']);
$router->add('GET', '/admin/users', 'AdminController@users', [AuthMiddleware::class, 'requireRole'], ['admin']);
$router->add('GET', '/admin/tasks', 'AdminController@tasks', [AuthMiddleware::class, 'requireRole'], ['admin']);
$router->add('GET', '/admin/payments', 'AdminController@payments', [AuthMiddleware::class, 'requireRole'], ['admin']);
$router->add('POST', '/admin/users/{id}/approve', 'AdminController@approveUser', [AuthMiddleware::class, 'requireRole'], ['admin']);
$router->add('POST', '/admin/users/{id}/suspend', 'AdminController@suspendUser', [AuthMiddleware::class, 'requireRole'], ['admin']);
$router->add('POST', '/admin/tasks/{id}/delete', 'AdminController@deleteTask', [AuthMiddleware::class, 'requireRole'], ['admin']);

// ==================== API ROUTES ====================
$router->add('GET', '/api/tasks', 'TaskController@apiIndex');
$router->add('GET', '/api/tasks/{id}', 'TaskController@apiShow');
$router->add('POST', '/api/tasks/{id}/proposals', 'TaskController@apiSubmitProposal', [AuthMiddleware::class, 'requireRole'], ['freelancer']);
$router->add('GET', '/api/user/notifications', 'UserController@apiNotifications', [AuthMiddleware::class, 'handle']);

// ==================== STATIC PAGES ====================
$router->add('GET', '/about', 'PageController@about');
$router->add('GET', '/contact', 'PageController@contact');
$router->add('POST', '/contact', 'PageController@handleContact');
$router->add('GET', '/how-it-works', 'PageController@howItWorks');
$router->add('GET', '/faq', 'PageController@faq');
$router->add('GET', '/terms', 'PageController@terms');
$router->add('GET', '/privacy', 'PageController@privacy');

// ==================== ERROR ROUTES ====================
$router->add('GET', '/404', 'ErrorController@notFound');
$router->add('GET', '/403', 'ErrorController@forbidden');
$router->add('GET', '/500', 'ErrorController@serverError');

return $router;