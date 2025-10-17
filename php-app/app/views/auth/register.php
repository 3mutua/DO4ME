<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join DO4ME - Find Freelancers or Work</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="auth-card card">
                    <div class="card-header">
                        <h4 class="text-center mb-0">Join DO4ME</h4>
                        <p class="text-center text-muted">Create your account to get started</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form id="register-form" method="POST" action="/register" novalidate>
                            <?php echo $session->csrfField('register'); ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">I want to:</label>
                                <div class="role-selection">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="role" 
                                               id="role-client" value="client" 
                                               <?php echo ($formData['role'] ?? 'client') === 'client' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="role-client">
                                            <i class="fas fa-briefcase"></i> Hire Freelancers
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="role" 
                                               id="role-freelancer" value="freelancer"
                                               <?php echo ($formData['role'] ?? '') === 'freelancer' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="role-freelancer">
                                            <i class="fas fa-code"></i> Work as Freelancer
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">Must be at least 8 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="password_confirmation" 
                                               name="password_confirmation" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number (Optional)</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Select Country</option>
                                    <option value="USA" <?php echo ($formData['country'] ?? '') === 'USA' ? 'selected' : ''; ?>>United States</option>
                                    <option value="GBR" <?php echo ($formData['country'] ?? '') === 'GBR' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="KEN" <?php echo ($formData['country'] ?? '') === 'KEN' ? 'selected' : ''; ?>>Kenya</option>
                                    <option value="IND" <?php echo ($formData['country'] ?? '') === 'IND' ? 'selected' : ''; ?>>India</option>
                                    <!-- More countries would be added in production -->
                                </select>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="/terms" class="text-decoration-none">Terms of Service</a> 
                                    and <a href="/privacy" class="text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter" checked>
                                <label class="form-check-label" for="newsletter">
                                    Send me product updates and tips
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>
                            
                            <div class="text-center">
                                <span class="text-muted">Already have an account? </span>
                                <a href="/login" class="text-decoration-none">Sign in</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/auth.js"></script>
</body>
</html>