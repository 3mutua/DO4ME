<footer class="bg-dark text-light mt-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-handshake me-2"></i>DO4ME
                </h5>
                <p class="text-light-emphasis">
                    Connect with skilled freelancers or find meaningful work. 
                    Get things done quickly, efficiently, and affordably.
                </p>
                <div class="social-links">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">For Clients</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/how-it-works#clients" class="text-light-emphasis text-decoration-none">How it Works</a></li>
                    <li class="mb-2"><a href="/categories" class="text-light-emphasis text-decoration-none">Browse Categories</a></li>
                    <li class="mb-2"><a href="/freelancers" class="text-light-emphasis text-decoration-none">Find Freelancers</a></li>
                    <li class="mb-2"><a href="/pricing" class="text-light-emphasis text-decoration-none">Pricing</a></li>
                    <li class="mb-2"><a href="/success-stories" class="text-light-emphasis text-decoration-none">Success Stories</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">For Freelancers</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/how-it-works#freelancers" class="text-light-emphasis text-decoration-none">How it Works</a></li>
                    <li class="mb-2"><a href="/become-freelancer" class="text-light-emphasis text-decoration-none">Become a Freelancer</a></li>
                    <li class="mb-2"><a href="/categories" class="text-light-emphasis text-decoration-none">Browse Jobs</a></li>
                    <li class="mb-2"><a href="/freelancer-guide" class="text-light-emphasis text-decoration-none">Freelancer Guide</a></li>
                    <li class="mb-2"><a href="/earning-potential" class="text-light-emphasis text-decoration-none">Earning Potential</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Company</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/about" class="text-light-emphasis text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="/careers" class="text-light-emphasis text-decoration-none">Careers</a></li>
                    <li class="mb-2"><a href="/press" class="text-light-emphasis text-decoration-none">Press</a></li>
                    <li class="mb-2"><a href="/contact" class="text-light-emphasis text-decoration-none">Contact</a></li>
                    <li class="mb-2"><a href="/blog" class="text-light-emphasis text-decoration-none">Blog</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/help-center" class="text-light-emphasis text-decoration-none">Help Center</a></li>
                    <li class="mb-2"><a href="/faq" class="text-light-emphasis text-decoration-none">FAQ</a></li>
                    <li class="mb-2"><a href="/disputes" class="text-light-emphasis text-decoration-none">Dispute Resolution</a></li>
                    <li class="mb-2"><a href="/safety" class="text-light-emphasis text-decoration-none">Safety Tips</a></li>
                    <li class="mb-2"><a href="/status" class="text-light-emphasis text-decoration-none">System Status</a></li>
                </ul>
            </div>
        </div>
        
        <div class="row pt-4 border-top border-secondary">
            <div class="col-md-6">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item"><a href="/privacy" class="text-light-emphasis text-decoration-none">Privacy Policy</a></li>
                    <li class="list-inline-item mx-2">•</li>
                    <li class="list-inline-item"><a href="/terms" class="text-light-emphasis text-decoration-none">Terms of Service</a></li>
                    <li class="list-inline-item mx-2">•</li>
                    <li class="list-inline-item"><a href="/cookie-policy" class="text-light-emphasis text-decoration-none">Cookie Policy</a></li>
                    <li class="list-inline-item mx-2">•</li>
                    <li class="list-inline-item"><a href="/gdpr" class="text-light-emphasis text-decoration-none">GDPR</a></li>
                </ul>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-light-emphasis">
                    &copy; <?php echo date('2025'); ?> DO4ME Platform. All rights reserved.
                </p>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="trust-badges text-center">
                    <small class="text-light-emphasis me-3">
                        <i class="fas fa-shield-alt me-1"></i> Secure Payments
                    </small>
                    <small class="text-light-emphasis me-3">
                        <i class="fas fa-lock me-1"></i> SSL Encrypted
                    </small>
                    <small class="text-light-emphasis">
                        <i class="fas fa-check-circle me-1"></i> Verified Users
                    </small>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Modal for quick actions -->
<div class="modal fade" id="quickActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickActionModalLabel">Quick Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quickActionModalBody">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Loading spinner -->
<div id="loadingSpinner" class="d-none">
    <div class="spinner-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<!-- Toast notifications container -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3">
    <!-- Toasts will be inserted here dynamically -->
</div>

<script>
// Global utility functions
function showLoading() {
    document.getElementById('loadingSpinner').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loadingSpinner').classList.add('d-none');
}

function showToast(message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast from DOM after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>