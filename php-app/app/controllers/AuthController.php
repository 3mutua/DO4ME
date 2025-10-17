<?php
class AuthController {
    private $userModel;
    private $session;
    private $validator;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->session = new Session();
        $this->validator = new Validator();
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = $this->validateRegistration($_POST);
                
                // Check if email already exists
                if ($this->userModel->findByEmail($data['email'])) {
                    throw new Exception('Email already registered');
                }
                
                $userId = $this->userModel->createUser($data);
                
                // Send verification email
                $this->sendVerificationEmail($data['email'], $data['first_name']);
                
                $this->session->setFlash('success', 'Registration successful! Please check your email for verification.');
                header('Location: /login');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->render('auth/register', ['error' => $error, 'formData' => $_POST]);
            }
        } else {
            $this->render('auth/register');
        }
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $user = $this->userModel->authenticate($email, $password);
                
                if ($user) {
                    $this->session->regenerate();
                    $this->session->set('user_id', $user['id']);
                    $this->session->set('user_email', $user['email']);
                    $this->session->set('user_role', $user['role']);
                    $this->session->set('user_name', $user['first_name'] . ' ' . $user['last_name']);
                    
                    // Update last login
                    $this->userModel->updateLastLogin($user['id']);
                    
                    // Redirect based on role
                    $redirectUrl = $user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard';
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    throw new Exception('Invalid email or password');
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->render('auth/login', ['error' => $error, 'email' => $_POST['email'] ?? '']);
            }
        } else {
            $this->render('auth/login');
        }
    }
    
    public function logout() {
        $this->session->destroy();
        header('Location: /login');
        exit;
    }
    
    private function validateRegistration($data) {
        $rules = [
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:client,freelancer',
            'phone' => 'optional|phone',
            'country' => 'optional|alpha|size:3'
        ];
        
        return $this->validator->validate($data, $rules);
    }
    
    private function sendVerificationEmail($email, $name) {
        // Implementation for sending verification email
        $token = bin2hex(random_bytes(32));
        $verificationUrl = $_ENV['APP_URL'] . "/verify-email?token=" . $token;
        
        // Store token in database
        $this->userModel->storeVerificationToken($email, $token);
        
        // Send email (implementation details would go here)
        $subject = "Verify Your DO4ME Account";
        $message = "Hello $name,<br><br>Please verify your email by clicking: <a href='$verificationUrl'>Verify Email</a>";
        
        // mail($email, $subject, $message);
        error_log("Verification email would be sent to: $email with token: $token");
    }
    
    private function render($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.php";
    }
}