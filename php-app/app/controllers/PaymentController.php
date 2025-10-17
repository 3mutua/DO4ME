<?php
class PaymentController {
    private $paymentModel;
    private $userModel;
    private $taskModel;
    private $walletModel;
    private $session;
    
    public function __construct() {
        $this->paymentModel = new PaymentModel();
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->walletModel = new WalletModel();
        $this->session = new Session();
        $this->ensureAuth();
    }
    
    /**
     * Show wallet dashboard with balance and transaction history
     */
    public function wallet() {
        $userId = $this->session->get('user_id');
        $walletBalance = $this->walletModel->getBalance($userId);
        $transactions = $this->walletModel->getTransactions($userId, 20);
        
        $this->render('payments/wallet', [
            'balance' => $walletBalance,
            'transactions' => $transactions
        ]);
    }
    
    /**
     * Show deposit form with payment methods
     */
    public function deposit() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleDeposit();
            return;
        }
        
        $this->render('payments/deposit', [
            'paymentMethods' => $this->getAvailablePaymentMethods()
        ]);
    }
    
    /**
     * Process deposit request
     */
    private function handleDeposit() {
        try {
            $userId = $this->session->get('user_id');
            $amount = floatval($_POST['amount']);
            $paymentMethod = $_POST['payment_method'];
            
            // Validate input
            if ($amount < 5) {
                throw new Exception('Minimum deposit amount is $5');
            }
            
            if ($amount > 10000) {
                throw new Exception('Maximum deposit amount is $10,000');
            }
            
            // Create payment intent via Python API
            $paymentIntent = $this->createPaymentIntent($userId, $amount, $paymentMethod, $_POST);
            
            // Redirect to payment gateway or show payment instructions
            if ($paymentMethod === 'stripe') {
                $this->handleStripePayment($paymentIntent);
            } elseif ($paymentMethod === 'mpesa') {
                $this->handleMpesaPayment($paymentIntent, $_POST['phone_number']);
            } else {
                $this->handleGenericPayment($paymentIntent);
            }
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /payments/deposit');
            exit;
        }
    }
    
    /**
     * Create payment intent via Python API
     */
    private function createPaymentIntent($userId, $amount, $paymentMethod, $metadata = []) {
        $apiUrl = $_ENV['PAYMENT_API_URL'] . '/api/v1/payments/create-intent';
        
        $payload = [
            'user_id' => $userId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'currency' => 'USD',
            'metadata' => array_merge($metadata, [
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ])
        ];
        
        $response = $this->callPaymentApi($apiUrl, $payload);
        
        if (!$response['success']) {
            throw new Exception($response['error'] ?? 'Payment initialization failed');
        }
        
        return $response['data'];
    }
    
    /**
     * Handle Stripe payment flow
     */
    private function handleStripePayment($paymentIntent) {
        $this->render('payments/stripe-checkout', [
            'client_secret' => $paymentIntent['client_secret'],
            'payment_intent_id' => $paymentIntent['payment_intent_id'],
            'amount' => $paymentIntent['amount']
        ]);
    }
    
    /**
     * Handle M-Pesa payment flow
     */
    private function handleMpesaPayment($paymentIntent, $phoneNumber) {
        // Store payment intent in session for callback verification
        $this->session->set('pending_mpesa_payment', $paymentIntent);
        
        $this->render('payments/mpesa-instructions', [
            'payment_intent_id' => $paymentIntent['payment_intent_id'],
            'phone_number' => $phoneNumber,
            'amount' => $paymentIntent['amount']
        ]);
    }
    
    /**
     * Handle payment callback from gateway
     */
    public function callback() {
        $gateway = $_GET['gateway'] ?? 'stripe';
        $paymentIntentId = $_GET['payment_intent'] ?? $_POST['CheckoutRequestID'] ?? null;
        
        if (!$paymentIntentId) {
            http_response_code(400);
            echo "Invalid callback";
            return;
        }
        
        try {
            // Verify payment with Python API
            $verificationUrl = $_ENV['PAYMENT_API_URL'] . '/api/v1/payments/confirm';
            $response = $this->callPaymentApi($verificationUrl, ['payment_intent_id' => $paymentIntentId]);
            
            if ($response['success'] && $response['data']['status'] === 'succeeded') {
                $this->session->setFlash('success', 'Payment completed successfully!');
                
                // Update user wallet
                $payment = $this->paymentModel->getByGatewayReference($paymentIntentId);
                if ($payment) {
                    $this->walletModel->addFunds($payment['user_id'], $payment['amount'], 'deposit', $paymentIntentId);
                }
                
                header('Location: /payments/success');
            } else {
                throw new Exception($response['error'] ?? 'Payment verification failed');
            }
            
        } catch (Exception $e) {
            error_log("Payment callback error: " . $e->getMessage());
            $this->session->setFlash('error', 'Payment failed: ' . $e->getMessage());
            header('Location: /payments/failed');
        }
        exit;
    }
    
    /**
     * Show payment success page
     */
    public function success() {
        $this->render('payments/success');
    }
    
    /**
     * Show payment failed page
     */
    public function failed() {
        $this->render('payments/failed');
    }
    
    /**
     * Process task payment (client pays freelancer)
     */
    public function payTask() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        
        try {
            $taskId = intval($_POST['task_id']);
            $userId = $this->session->get('user_id');
            
            // Verify task ownership and status
            $task = $this->taskModel->findById($taskId);
            if (!$task || $task['client_id'] !== $userId) {
                throw new Exception('Task not found or access denied');
            }
            
            if ($task['status'] !== 'completed') {
                throw new Exception('Task must be completed before payment');
            }
            
            if ($task['freelancer_id'] === null) {
                throw new Exception('No freelancer assigned to this task');
            }
            
            // Check if client has sufficient balance
            $clientBalance = $this->walletModel->getBalance($userId);
            $totalAmount = $task['budget'] + $task['platform_fee'];
            
            if ($clientBalance < $totalAmount) {
                throw new Exception('Insufficient balance. Please deposit funds.');
            }
            
            // Process payment
            $this->processTaskPayment($task, $totalAmount);
            
            $this->session->setFlash('success', 'Payment processed successfully!');
            header('Location: /tasks/' . $taskId);
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        }
        exit;
    }
    
    /**
     * Process task payment transaction
     */
    private function processTaskPayment($task, $totalAmount) {
        $this->paymentModel->beginTransaction();
        
        try {
            // Deduct amount from client's wallet
            $this->walletModel->deductFunds(
                $task['client_id'], 
                $totalAmount, 
                'task_payment', 
                'Payment for task: ' . $task['title']
            );
            
            // Calculate freelancer amount (after platform fee)
            $freelancerAmount = $task['budget'] - $task['transaction_fee'];
            
            // Add funds to freelancer's wallet
            $this->walletModel->addFunds(
                $task['freelancer_id'],
                $freelancerAmount,
                'task_earning',
                'Earning from task: ' . $task['title']
            );
            
            // Record platform earnings
            $platformEarnings = $task['platform_fee'] + $task['transaction_fee'];
            $this->walletModel->addFunds(
                1, // Admin/system account
                $platformEarnings,
                'commission',
                'Platform commission from task: ' . $task['title']
            );
            
            // Update task status to paid
            $this->taskModel->updateStatus($task['id'], 'paid');
            
            // Log the transaction
            ActivityLogger::logTaskPayment($task['id'], $task['client_id'], $task['freelancer_id'], $totalAmount);
            
            $this->paymentModel->commit();
            
        } catch (Exception $e) {
            $this->paymentModel->rollBack();
            throw $e;
        }
    }
    
    /**
     * Initiate withdrawal request
     */
    public function withdraw() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleWithdrawal();
            return;
        }
        
        $userId = $this->session->get('user_id');
        $balance = $this->walletModel->getBalance($userId);
        $pendingWithdrawals = $this->paymentModel->getPendingWithdrawals($userId);
        
        $this->render('payments/withdraw', [
            'balance' => $balance,
            'pendingWithdrawals' => $pendingWithdrawals,
            'withdrawalMethods' => $this->getWithdrawalMethods()
        ]);
    }
    
    /**
     * Process withdrawal request
     */
    private function handleWithdrawal() {
        try {
            $userId = $this->session->get('user_id');
            $amount = floatval($_POST['amount']);
            $method = $_POST['withdrawal_method'];
            $accountDetails = $_POST['account_details'];
            
            // Validate withdrawal
            $this->validateWithdrawal($userId, $amount, $method);
            
            // Process withdrawal
            $withdrawalId = $this->paymentModel->createWithdrawal([
                'user_id' => $userId,
                'amount' => $amount,
                'method' => $method,
                'account_details' => json_encode($accountDetails),
                'status' => 'pending'
            ]);
            
            // Deduct funds immediately
            $this->walletModel->deductFunds(
                $userId,
                $amount,
                'withdrawal',
                'Withdrawal request #' . $withdrawalId
            );
            
            // Notify admin for approval
            $this->notifyAdminOfWithdrawal($withdrawalId);
            
            $this->session->setFlash('success', 'Withdrawal request submitted. It will be processed within 24-48 hours.');
            header('Location: /payments/withdraw');
            
        } catch (Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            header('Location: /payments/withdraw');
        }
        exit;
    }
    
    /**
     * Validate withdrawal request
     */
    private function validateWithdrawal($userId, $amount, $method) {
        $balance = $this->walletModel->getBalance($userId);
        $minWithdrawal = 10; // $10 minimum
        
        if ($amount < $minWithdrawal) {
            throw new Exception("Minimum withdrawal amount is $$minWithdrawal");
        }
        
        if ($amount > $balance) {
            throw new Exception("Insufficient balance for withdrawal");
        }
        
        if (!in_array($method, ['bank_transfer', 'paypal', 'mpesa'])) {
            throw new Exception("Invalid withdrawal method");
        }
        
        // Check if user has pending withdrawals
        $pendingAmount = $this->paymentModel->getPendingWithdrawalAmount($userId);
        if (($balance - $pendingAmount) < $amount) {
            throw new Exception("You have pending withdrawals. Please wait for them to be processed.");
        }
    }
    
    /**
     * Webhook handler for payment services
     */
    public function webhook() {
        // Get the raw POST data
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? $_SERVER['HTTP_MPESA_SIGNATURE'] ?? '';
        
        try {
            // Verify webhook signature and process
            $webhookData = $this->verifyWebhook($payload, $signature);
            $this->processWebhook($webhookData);
            
            http_response_code(200);
            echo "Webhook processed successfully";
            
        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            http_response_code(400);
            echo "Webhook processing failed";
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verifyWebhook($payload, $signature) {
        // Implementation depends on the payment gateway
        // This is a simplified version
        
        if (strpos($signature, 'whsec_') === 0) {
            // Stripe webhook
            return $this->verifyStripeWebhook($payload, $signature);
        } else {
            // M-Pesa or other webhook
            return json_decode($payload, true);
        }
    }
    
    /**
     * Process webhook data
     */
    private function processWebhook($webhookData) {
        $eventType = $webhookData['type'] ?? $webhookData['event'] ?? '';
        
        switch ($eventType) {
            case 'payment_intent.succeeded':
            case 'mpesa_payment_success':
                $this->handleSuccessfulPaymentWebhook($webhookData);
                break;
                
            case 'payment_intent.payment_failed':
            case 'mpesa_payment_failed':
                $this->handleFailedPaymentWebhook($webhookData);
                break;
                
            default:
                // Log unhandled webhook
                error_log("Unhandled webhook type: " . $eventType);
        }
    }
    
    /**
     * Call Python payment API
     */
    private function callPaymentApi($url, $data) {
        $apiKey = $_ENV['PAYMENT_API_KEY'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'X-API-Key: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Payment API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get available payment methods
     */
    private function getAvailablePaymentMethods() {
        return [
            'stripe' => [
                'name' => 'Credit/Debit Card (Stripe)',
                'currencies' => ['USD', 'EUR', 'GBP'],
                'min_amount' => 5,
                'max_amount' => 10000
            ],
            'mpesa' => [
                'name' => 'M-Pesa',
                'currencies' => ['KES'],
                'min_amount' => 100,
                'max_amount' => 70000,
                'requires_phone' => true
            ],
            'paypal' => [
                'name' => 'PayPal',
                'currencies' => ['USD', 'EUR'],
                'min_amount' => 10,
                'max_amount' => 10000
            ]
        ];
    }
    
    /**
     * Get available withdrawal methods
     */
    private function getWithdrawalMethods() {
        return [
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'mpesa' => 'M-Pesa'
        ];
    }
    
    /**
     * Notify admin of new withdrawal request
     */
    private function notifyAdminOfWithdrawal($withdrawalId) {
        // Implementation would send email/notification to admin
        error_log("New withdrawal request: #" . $withdrawalId);
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
     * Render view with data
     */
    private function render($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.php";
    }
}