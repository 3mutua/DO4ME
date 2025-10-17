<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DO4ME</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card card">
                    <div class="card-header">
                        <h4 class="text-center mb-0">Welcome Back</h4>
                        <p class="text-center text-muted">Sign in to your account</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($session->hasFlash('success')): ?>
                            <div class="alert alert-success"><?php echo $session->getFlash('success'); ?></div>
                        <?php endif; ?>
                        
                        <form id="login-form" method="POST" action="/login">
                            <?php echo $session->csrfField('login'); ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                       required autofocus>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">
                                    <a href="/forgot-password" class="text-decoration-none">Forgot your password?</a>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>
                            
                            <div class="text-center">
                                <span class="text-muted">Don't have an account? </span>
                                <a href="/register" class="text-decoration-none">Sign up</a>
                            </div>
                        </form>
                        
                        <div class="divider my-4">
                            <span class="px-3 bg-white text-muted">Or continue with</span>
                        </div>
                        
                        <div class="social-login">
                            <button type="button" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="fab fa-google"></i> Continue with Google
                            </button>
                            <button type="button" class="btn btn-outline-secondary w-100">
                                <i class="fab fa-facebook"></i> Continue with Facebook
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        By continuing, you agree to our 
                        <a href="/terms" class="text-decoration-none">Terms of Service</a> and 
                        <a href="/privacy" class="text-decoration-none">Privacy Policy</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/auth.js"></script>
</body>
</html>