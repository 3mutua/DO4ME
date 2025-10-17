/**
 * DO4ME Platform - Payment Handling JavaScript
 * Handles payment processing, wallet management, and transaction tracking
 */

class PaymentManager {
    constructor() {
        this.stripe = null;
        this.elements = null;
        this.cardElement = null;
        this.paymentIntent = null;
        this.currentPaymentMethod = null;
        
        this.init();
    }
    
    init() {
        this.loadStripe();
        this.setupEventListeners();
        this.initializeWallet();
    }
    
    async loadStripe() {
        // Load Stripe.js if not already loaded
        if (!window.Stripe) {
            await this.loadScript('https://js.stripe.com/v3/');
        }
        
        // Initialize Stripe with publishable key
        const stripeKey = document.querySelector('meta[name="stripe-publishable-key"]')?.content;
        if (stripeKey) {
            this.stripe = Stripe(stripeKey);
            this.setupStripeElements();
        }
    }
    
    setupStripeElements() {
        if (!this.stripe) return;
        
        this.elements = this.stripe.elements();
        const style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
        
        this.cardElement = this.elements.create('card', { style: style });
        this.cardElement.mount('#card-element');
        
        this.cardElement.on('change', (event) => {
            this.handleCardElementChange(event);
        });
    }
    
    setupEventListeners() {
        // Payment method selection
        document.addEventListener('change', (e) => {
            if (e.target.name === 'payment_method') {
                this.handlePaymentMethodChange(e.target.value);
            }
        });
        
        // Deposit form submission
        const depositForm = document.getElementById('deposit-form');
        if (depositForm) {
            depositForm.addEventListener('submit', (e) => {
                this.handleDepositSubmission(e);
            });
        }
        
        // Withdrawal form submission
        const withdrawForm = document.getElementById('withdraw-form');
        if (withdrawForm) {
            withdrawForm.addEventListener('submit', (e) => {
                this.handleWithdrawalSubmission(e);
            });
        }
        
        // Task payment
        document.addEventListener('click', (e) => {
            if (e.target.matches('.pay-task-btn')) {
                this.handleTaskPayment(e);
            }
        });
    }
    
    initializeWallet() {
        this.updateWalletBalance();
        this.loadTransactionHistory();
    }
    
    async handleDepositSubmission(e) {
        e.preventDefault();
        
        const form = e.target;
        const amount = parseFloat(form.amount.value);
        const paymentMethod = form.payment_method.value;
        
        if (!this.validateDeposit(amount, paymentMethod)) {
            return;
        }
        
        this.showPaymentProcessing();
        
        try {
            if (paymentMethod === 'stripe') {
                await this.processStripeDeposit(amount, form);
            } else if (paymentMethod === 'mpesa') {
                await this.processMpesaDeposit(amount, form);
            } else if (paymentMethod === 'paypal') {
                await this.processPaypalDeposit(amount, form);
            }
        } catch (error) {
            this.handlePaymentError(error);
        }
    }
    
    async processStripeDeposit(amount, form) {
        // Create payment intent
        const response = await do4me.apiCall('/payments/create-intent', {
            method: 'POST',
            body: JSON.stringify({
                amount: amount,
                payment_method: 'stripe',
                currency: 'usd'
            })
        });
        
        this.paymentIntent = response.payment_intent;
        
        // Confirm payment with Stripe
        const { error, paymentIntent } = await this.stripe.confirmCardPayment(
            response.client_secret,
            {
                payment_method: {
                    card: this.cardElement,
                    billing_details: {
                        name: form.card_holder_name?.value || '',
                        email: do4me.currentUser?.email || ''
                    }
                }
            }
        );
        
        if (error) {
            throw new Error(error.message);
        }
        
        if (paymentIntent.status === 'succeeded') {
            await this.handleSuccessfulPayment(paymentIntent.id, amount);
        }
    }
    
    async processMpesaDeposit(amount, form) {
        const phoneNumber = form.phone_number.value;
        
        if (!this.validateMpesaPhone(phoneNumber)) {
            throw new Error('Invalid phone number format');
        }
        
        const response = await do4me.apiCall('/payments/create-intent', {
            method: 'POST',
            body: JSON.stringify({
                amount: amount,
                payment_method: 'mpesa',
                currency: 'kes',
                metadata: {
                    phone_number: phoneNumber
                }
            })
        });
        
        this.paymentIntent = response.payment_intent;
        
        // Show M-Pesa instructions
        this.showMpesaInstructions(response, phoneNumber);
        
        // Poll for payment confirmation
        this.pollPaymentStatus(response.payment_intent_id);
    }
    
    async processPaypalDeposit(amount, form) {
        const response = await do4me.apiCall('/payments/create-intent', {
            method: 'POST',
            body: JSON.stringify({
                amount: amount,
                payment_method: 'paypal',
                currency: 'usd'
            })
        });
        
        // Redirect to PayPal
        window.location.href = response.approval_url;
    }
    
    async handleWithdrawalSubmission(e) {
        e.preventDefault();
        
        const form = e.target;
        const amount = parseFloat(form.amount.value);
        const method = form.withdrawal_method.value;
        const accountDetails = this.getWithdrawalAccountDetails(form);
        
        if (!this.validateWithdrawal(amount, method, accountDetails)) {
            return;
        }
        
        this.showProcessing('Processing withdrawal...');
        
        try {
            const response = await do4me.apiCall('/payments/withdraw', {
                method: 'POST',
                body: JSON.stringify({
                    amount: amount,
                    withdrawal_method: method,
                    account_details: accountDetails
                })
            });
            
            if (response.success) {
                this.showSuccess('Withdrawal request submitted successfully!');
                this.updateWalletBalance();
                form.reset();
            }
        } catch (error) {
            this.handlePaymentError(error);
        }
    }
    
    async handleTaskPayment(e) {
        const button = e.target;
        const taskId = button.dataset.taskId;
        
        if (!confirm('Are you sure you want to pay for this task?')) {
            return;
        }
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        try {
            const response = await do4me.apiCall(`/payments/task/${taskId}`, {
                method: 'POST'
            });
            
            if (response.success) {
                this.showSuccess('Payment processed successfully!');
                this.updateWalletBalance();
                
                // Reload the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            this.handlePaymentError(error);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay Now';
        }
    }
    
    // Validation Methods
    validateDeposit(amount, method) {
        if (amount < 5) {
            this.showError('Minimum deposit amount is $5');
            return false;
        }
        
        if (amount > 10000) {
            this.showError('Maximum deposit amount is $10,000');
            return false;
        }
        
        if (!method) {
            this.showError('Please select a payment method');
            return false;
        }
        
        return true;
    }
    
    validateWithdrawal(amount, method, accountDetails) {
        if (amount < 10) {
            this.showError('Minimum withdrawal amount is $10');
            return false;
        }
        
        if (!method) {
            this.showError('Please select a withdrawal method');
            return false;
        }
        
        if (method === 'bank_transfer' && !accountDetails.account_number) {
            this.showError('Please provide bank account details');
            return false;
        }
        
        if (method === 'mpesa' && !accountDetails.phone_number) {
            this.showError('Please provide M-Pesa phone number');
            return false;
        }
        
        return true;
    }
    
    validateMpesaPhone(phone) {
        const regex = /^(?:254|\+254|0)?(7[0-9]{8})$/;
        return regex.test(phone);
    }
    
    // UI Methods
    handlePaymentMethodChange(method) {
        this.currentPaymentMethod = method;
        
        // Show/hide method-specific fields
        document.querySelectorAll('.payment-method-fields').forEach(field => {
            field.classList.add('d-none');
        });
        
        const specificFields = document.getElementById(`${method}-fields`);
        if (specificFields) {
            specificFields.classList.remove('d-none');
        }
        
        // Update payment button text
        const submitButton = document.querySelector('#deposit-form button[type="submit"]');
        if (submitButton) {
            submitButton.textContent = `Pay with ${this.getMethodDisplayName(method)}`;
        }
    }
    
    handleCardElementChange(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    }
    
    showPaymentProcessing() {
        this.showProcessing('Processing payment...');
    }
    
    showProcessing(message) {
        do4me.showLoading(message);
    }
    
    hideProcessing() {
        do4me.hideLoading();
    }
    
    showMpesaInstructions(paymentData, phoneNumber) {
        const modalHtml = `
            <div class="modal fade" id="mpesaInstructionsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">M-Pesa Payment Instructions</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <h6>Follow these steps to complete your payment:</h6>
                                <ol>
                                    <li>Ensure you have sufficient funds in your M-Pesa account</li>
                                    <li>You will receive an STK push prompt on your phone</li>
                                    <li>Enter your M-Pesa PIN when prompted</li>
                                    <li>Wait for confirmation message</li>
                                </ol>
                            </div>
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3"></div>
                                <p>Waiting for payment confirmation...</p>
                                <small class="text-muted">Payment ID: ${paymentData.payment_intent_id}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('mpesaInstructionsModal'));
        modal.show();
    }
    
    // API Methods
    async updateWalletBalance() {
        try {
            const response = await do4me.apiCall('/payments/wallet/balance');
            this.updateBalanceDisplay(response.balance);
        } catch (error) {
            console.error('Failed to update wallet balance:', error);
        }
    }
    
    async loadTransactionHistory() {
        try {
            const response = await do4me.apiCall('/payments/transactions');
            this.renderTransactionHistory(response.transactions);
        } catch (error) {
            console.error('Failed to load transaction history:', error);
        }
    }
    
    async pollPaymentStatus(paymentIntentId, maxAttempts = 30) {
        let attempts = 0;
        
        const poll = async () => {
            try {
                const response = await do4me.apiCall(`/payments/status/${paymentIntentId}`);
                
                if (response.status === 'succeeded') {
                    await this.handleSuccessfulPayment(paymentIntentId, response.amount);
                    return;
                }
                
                if (response.status === 'failed') {
                    throw new Error('Payment failed or was cancelled');
                }
                
                // Continue polling
                attempts++;
                if (attempts < maxAttempts) {
                    setTimeout(poll, 2000); // Poll every 2 seconds
                } else {
                    throw new Error('Payment timeout. Please try again.');
                }
            } catch (error) {
                this.handlePaymentError(error);
            }
        };
        
        await poll();
    }
    
    async handleSuccessfulPayment(paymentId, amount) {
        this.hideProcessing();
        
        // Update wallet balance
        await this.updateWalletBalance();
        
        // Show success message
        this.showSuccess(`Payment of $${amount} completed successfully!`);
        
        // Close any open modals
        const modal = bootstrap.Modal.getInstance(document.getElementById('mpesaInstructionsModal'));
        if (modal) modal.hide();
        
        // Redirect to success page or update UI
        setTimeout(() => {
            window.location.href = '/payments/success?payment_id=' + paymentId;
        }, 2000);
    }
    
    handlePaymentError(error) {
        this.hideProcessing();
        console.error('Payment error:', error);
        this.showError(error.message || 'Payment failed. Please try again.');
    }
    
    // Utility Methods
    getMethodDisplayName(method) {
        const names = {
            stripe: 'Credit Card',
            mpesa: 'M-Pesa',
            paypal: 'PayPal',
            flutterwave: 'Flutterwave'
        };
        return names[method] || method;
    }
    
    getWithdrawalAccountDetails(form) {
        const method = form.withdrawal_method.value;
        const details = {};
        
        if (method === 'bank_transfer') {
            details.account_number = form.account_number.value;
            details.bank_name = form.bank_name.value;
            details.account_name = form.account_name.value;
        } else if (method === 'mpesa') {
            details.phone_number = form.phone_number.value;
        } else if (method === 'paypal') {
            details.paypal_email = form.paypal_email.value;
        }
        
        return details;
    }
    
    updateBalanceDisplay(balance) {
        const balanceElements = document.querySelectorAll('.wallet-balance');
        balanceElements.forEach(element => {
            element.textContent = do4me.formatCurrency(balance);
        });
    }
    
    renderTransactionHistory(transactions) {
        const container = document.getElementById('transaction-history');
        if (!container) return;
        
        if (transactions.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No transactions yet</p>';
            return;
        }
        
        const html = transactions.map(transaction => `
            <div class="transaction-item border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${transaction.description}</h6>
                        <small class="text-muted">${do4me.formatRelativeTime(transaction.created_at)}</small>
                    </div>
                    <div class="text-end">
                        <span class="fw-bold ${transaction.amount > 0 ? 'text-success' : 'text-danger'}">
                            ${transaction.amount > 0 ? '+' : ''}${do4me.formatCurrency(Math.abs(transaction.amount))}
                        </span>
                        <div class="small text-muted">Balance: ${do4me.formatCurrency(transaction.balance_after)}</div>
                    </div>
                </div>
                <div class="small">
                    <span class="badge bg-${this.getTransactionTypeClass(transaction.type)}">
                        ${transaction.type.replace('_', ' ')}
                    </span>
                    ${transaction.reference_id ? `<span class="text-muted ms-2">Ref: ${transaction.reference_id}</span>` : ''}
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }
    
    getTransactionTypeClass(type) {
        const classes = {
            deposit: 'success',
            withdrawal: 'warning',
            task_payment: 'primary',
            task_earning: 'info',
            refund: 'secondary',
            commission: 'dark'
        };
        return classes[type] || 'secondary';
    }
    
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    // Alias for do4me methods
    showSuccess(message) { do4me.showSuccess(message); }
    showError(message) { do4me.showError(message); }
    showWarning(message) { do4me.showWarning(message); }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('[data-payment-manager]')) {
        window.paymentManager = new PaymentManager();
    }
});