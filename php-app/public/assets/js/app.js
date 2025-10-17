/**
 * DO4ME Platform - Main Application JavaScript
 * Handles global functionality, authentication, and core features
 */

class DO4MEApp {
    constructor() {
        this.currentUser = null;
        this.socket = null;
        this.isInitialized = false;
        this.apiBaseUrl = '/api';
        this.wsBaseUrl = this.getWebSocketUrl();
        
        this.init();
    }
    
    init() {
        if (this.isInitialized) return;
        
        this.loadCurrentUser();
        this.setupEventListeners();
        this.setupInterceptors();
        this.initWebSocket();
        this.setupServiceWorker();
        
        this.isInitialized = true;
        console.log('DO4ME App initialized');
    }
    
    loadCurrentUser() {
        // Check if user data is available in the DOM
        const userDataElement = document.getElementById('user-data');
        if (userDataElement) {
            try {
                this.currentUser = JSON.parse(userDataElement.dataset.user);
            } catch (e) {
                console.warn('Could not parse user data');
            }
        }
        
        // Alternatively, check localStorage
        if (!this.currentUser) {
            const storedUser = localStorage.getItem('do4me_user');
            if (storedUser) {
                this.currentUser = JSON.parse(storedUser);
            }
        }
    }
    
    setupEventListeners() {
        // Global click handlers
        document.addEventListener('click', (e) => {
            this.handleGlobalClick(e);
        });
        
        // Form submissions
        document.addEventListener('submit', (e) => {
            this.handleFormSubmission(e);
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
        
        // Window events
        window.addEventListener('online', () => this.handleOnlineStatus());
        window.addEventListener('offline', () => this.handleOfflineStatus());
        window.addEventListener('beforeunload', (e) => this.handleBeforeUnload(e));
    }
    
    setupInterceptors() {
        // Fetch API interceptor for AJAX requests
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            // Add CSRF token to all requests
            if (args[1] && args[1].headers) {
                args[1].headers['X-CSRF-Token'] = this.getCsrfToken();
            } else if (args[1]) {
                args[1].headers = {
                    'X-CSRF-Token': this.getCsrfToken(),
                    ...args[1].headers
                };
            }
            
            // Show loading indicator for requests longer than 500ms
            const loadingTimeout = setTimeout(() => {
                this.showLoading();
            }, 500);
            
            try {
                const response = await originalFetch(...args);
                clearTimeout(loadingTimeout);
                this.hideLoading();
                
                // Handle authentication errors
                if (response.status === 401) {
                    this.handleUnauthorized();
                    return response;
                }
                
                // Handle server errors
                if (response.status >= 500) {
                    this.showError('Server error occurred. Please try again.');
                }
                
                return response;
            } catch (error) {
                clearTimeout(loadingTimeout);
                this.hideLoading();
                this.showError('Network error. Please check your connection.');
                throw error;
            }
        };
    }
    
    initWebSocket() {
        if (!this.currentUser || !this.currentUser.id) return;
        
        try {
            this.socket = new WebSocket(`${this.wsBaseUrl}/ws/${this.currentUser.id}`);
            
            this.socket.onopen = () => {
                console.log('WebSocket connected');
                this.showToast('Connected', 'success');
            };
            
            this.socket.onmessage = (event) => {
                this.handleWebSocketMessage(JSON.parse(event.data));
            };
            
            this.socket.onclose = (event) => {
                console.log('WebSocket disconnected:', event);
                this.handleWebSocketDisconnect(event);
            };
            
            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
            
        } catch (error) {
            console.warn('WebSocket initialization failed:', error);
        }
    }
    
    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'notification':
                this.handleNotification(data);
                break;
            case 'message':
                this.handleNewMessage(data);
                break;
            case 'task_update':
                this.handleTaskUpdate(data);
                break;
            case 'payment_update':
                this.handlePaymentUpdate(data);
                break;
            case 'user_activity':
                this.handleUserActivity(data);
                break;
        }
    }
    
    handleWebSocketDisconnect(event) {
        if (event.code === 1006) {
            // Abnormal closure, attempt reconnect
            setTimeout(() => {
                if (this.currentUser) {
                    this.initWebSocket();
                }
            }, 5000);
        }
    }
    
    handleGlobalClick(e) {
        const target = e.target;
        
        // Handle external links
        if (target.matches('a[href^="http"]') && !target.href.includes(window.location.hostname)) {
            e.preventDefault();
            this.openExternalLink(target.href);
            return;
        }
        
        // Handle modal triggers
        if (target.matches('[data-bs-toggle="modal"]')) {
            this.handleModalTrigger(target);
            return;
        }
        
        // Handle tooltip triggers
        if (target.matches('[data-bs-toggle="tooltip"]')) {
            this.initTooltip(target);
            return;
        }
        
        // Handle dropdown toggles
        if (target.matches('.dropdown-toggle')) {
            this.handleDropdownToggle(target);
            return;
        }
    }
    
    handleFormSubmission(e) {
        const form = e.target;
        
        // Add loading state to submit button
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        }
        
        // Validate form
        if (!this.validateForm(form)) {
            e.preventDefault();
            this.resetSubmitButton(submitButton);
            return;
        }
        
        // Handle AJAX forms
        if (form.classList.contains('ajax-form')) {
            e.preventDefault();
            this.submitAjaxForm(form);
            return;
        }
    }
    
    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            this.focusSearch();
            return;
        }
        
        // Escape key to close modals
        if (e.key === 'Escape') {
            this.closeActiveModals();
            return;
        }
        
        // Number keys for quick navigation (when not in input)
        if (!this.isInputFocused() && e.key >= '1' && e.key <= '9') {
            this.handleQuickNavigation(e.key);
            return;
        }
    }
    
    // API Methods
    async apiCall(endpoint, options = {}) {
        const url = this.apiBaseUrl + endpoint;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.getCsrfToken()
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || `HTTP ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }
    
    async uploadFile(file, onProgress = null) {
        const formData = new FormData();
        formData.append('file', file);
        
        const xhr = new XMLHttpRequest();
        
        return new Promise((resolve, reject) => {
            xhr.upload.addEventListener('progress', (e) => {
                if (onProgress && e.lengthComputable) {
                    onProgress((e.loaded / e.total) * 100);
                }
            });
            
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    reject(new Error(`Upload failed: ${xhr.status}`));
                }
            });
            
            xhr.addEventListener('error', () => {
                reject(new Error('Upload failed'));
            });
            
            xhr.open('POST', '/api/upload');
            xhr.setRequestHeader('X-CSRF-Token', this.getCsrfToken());
            xhr.send(formData);
        });
    }
    
    // Authentication Methods
    async login(email, password, rememberMe = false) {
        try {
            const response = await this.apiCall('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password, remember_me: rememberMe })
            });
            
            if (response.success) {
                this.currentUser = response.user;
                this.storeUserData(response.user, response.token);
                this.initWebSocket();
                this.showSuccess('Login successful!');
                return true;
            }
        } catch (error) {
            this.showError('Login failed: ' + error.message);
            return false;
        }
    }
    
    async register(userData) {
        try {
            const response = await this.apiCall('/auth/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            if (response.success) {
                this.showSuccess('Registration successful! Please check your email.');
                return true;
            }
        } catch (error) {
            this.showError('Registration failed: ' + error.message);
            return false;
        }
    }
    
    logout() {
        this.apiCall('/auth/logout', { method: 'POST' })
            .finally(() => {
                this.currentUser = null;
                localStorage.removeItem('do4me_user');
                localStorage.removeItem('do4me_token');
                if (this.socket) this.socket.close();
                window.location.href = '/login';
            });
    }
    
    // UI Methods
    showLoading(message = 'Loading...') {
        let spinner = document.getElementById('global-loading');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'global-loading';
            spinner.className = 'global-loading-spinner';
            spinner.innerHTML = `
                <div class="spinner-overlay">
                    <div class="spinner-content">
                        <div class="spinner-border text-primary"></div>
                        <div class="mt-2">${message}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(spinner);
        }
    }
    
    hideLoading() {
        const spinner = document.getElementById('global-loading');
        if (spinner) {
            spinner.remove();
        }
    }
    
    showToast(message, type = 'info', duration = 5000) {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type}" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        const container = document.getElementById('toast-container') || this.createToastContainer();
        container.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'danger');
    }
    
    showWarning(message) {
        this.showToast(message, 'warning');
    }
    
    showInfo(message) {
        this.showToast(message, 'info');
    }
    
    // Utility Methods
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    getWebSocketUrl() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        return `${protocol}//${window.location.host}`;
    }
    
    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
    
    formatDate(date, format = 'medium') {
        const dateObj = new Date(date);
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: format
        }).format(dateObj);
    }
    
    formatRelativeTime(date) {
        const now = new Date();
        const diffMs = now - new Date(date);
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return this.formatDate(date);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // Storage Methods
    storeUserData(user, token) {
        localStorage.setItem('do4me_user', JSON.stringify(user));
        if (token) {
            localStorage.setItem('do4me_token', token);
        }
    }
    
    getStoredUser() {
        const user = localStorage.getItem('do4me_user');
        return user ? JSON.parse(user) : null;
    }
    
    clearStorage() {
        localStorage.removeItem('do4me_user');
        localStorage.removeItem('do4me_token');
    }
    
    // Service Worker
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then((registration) => {
                    console.log('SW registered: ', registration);
                })
                .catch((registrationError) => {
                    console.log('SW registration failed: ', registrationError);
                });
        }
    }
    
    // Event Handlers
    handleOnlineStatus() {
        this.showSuccess('Connection restored');
        // Retry failed requests
        this.retryFailedRequests();
    }
    
    handleOfflineStatus() {
        this.showWarning('You are currently offline');
    }
    
    handleBeforeUnload(e) {
        // Check if there are unsaved changes
        if (this.hasUnsavedChanges()) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    }
    
    // Helper Methods
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
    
    validateForm(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
    
    resetSubmitButton(button) {
        if (button) {
            button.disabled = false;
            const originalText = button.dataset.originalText || 'Submit';
            button.innerHTML = originalText;
        }
    }
    
    isInputFocused() {
        const activeElement = document.activeElement;
        return activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.tagName === 'SELECT' ||
            activeElement.isContentEditable
        );
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.do4me = new DO4MEApp();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DO4MEApp;
}