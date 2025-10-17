<?php 
$title = "Post a New Task - DO4ME";
$description = "Post your task and find skilled freelancers to get it done quickly and affordably.";
$css_files = ['/assets/css/tasks.css'];
include __DIR__ . '/../layout/header.php'; 
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0"><i class="fas fa-plus-circle me-2"></i>Post a New Task</h2>
                    <p class="mb-0 opacity-75">Describe your task and find the perfect freelancer</p>
                </div>
                
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form id="create-task-form" method="POST" action="/tasks" enctype="multipart/form-data" novalidate>
                        <?php echo $session->csrfField('create_task'); ?>
                        
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Task Details</h4>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Task Title *</label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>" 
                                       placeholder="e.g., Need a logo design for my bakery" required maxlength="200">
                                <div class="form-text">Be specific about what you need. Good titles get more responses.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Task Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="6" 
                                          placeholder="Describe your task in detail. Include requirements, expectations, and any specific instructions..." 
                                          required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <span id="charCount">0</span>/2000 characters. Include all necessary details.
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category" class="form-label fw-bold">Category *</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" 
                                                    <?php echo ($formData['category'] ?? '') === $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget" class="form-label fw-bold">Budget ($) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="budget" name="budget" 
                                                   value="<?php echo htmlspecialchars($formData['budget'] ?? ''); ?>" 
                                                   min="5" max="100000" step="0.01" placeholder="50.00" required>
                                        </div>
                                        <div class="form-text">Minimum budget is $5. Average task budget: $50-200</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline & Urgency -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Timeline & Urgency</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="urgency" class="form-label fw-bold">Urgency Level *</label>
                                        <select class="form-select" id="urgency" name="urgency" required>
                                            <option value="low" <?php echo ($formData['urgency'] ?? '') === 'low' ? 'selected' : ''; ?>>
                                                Low (7+ days) - Flexible timeline
                                            </option>
                                            <option value="medium" <?php echo ($formData['urgency'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>
                                                Medium (3-7 days) - Standard timeline
                                            </option>
                                            <option value="high" <?php echo ($formData['urgency'] ?? '') === 'high' ? 'selected' : ''; ?>>
                                                High (1-3 days) - Urgent delivery needed
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration_days" class="form-label fw-bold">Expected Duration (days) *</label>
                                        <input type="number" class="form-control" id="duration_days" name="duration_days" 
                                               value="<?php echo htmlspecialchars($formData['duration_days'] ?? '7'); ?>" 
                                               min="1" max="365" required>
                                        <div class="form-text">How many days do you expect this task to take?</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attachments -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Attachments (Optional)</h4>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label fw-bold">Supporting Files</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple 
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.zip">
                                <div class="form-text">
                                    Upload reference files, documents, or examples. Max 10MB per file. Supported formats: JPG, PNG, PDF, DOC, ZIP.
                                </div>
                            </div>
                            
                            <div id="file-preview" class="d-none">
                                <h6 class="fw-bold">Selected Files:</h6>
                                <div id="file-list" class="mb-3"></div>
                            </div>
                        </div>
                        
                        <!-- Skills Required -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Skills Required</h4>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">What skills should the freelancer have?</label>
                                <div class="skills-tags" id="skills-container">
                                    <!-- Skills will be added here dynamically -->
                                </div>
                                <input type="text" class="form-control mt-2" id="skill-input" 
                                       placeholder="Type a skill and press Enter to add it">
                                <div class="form-text">Add relevant skills to help us match you with the right freelancers.</div>
                            </div>
                        </div>
                        
                        <!-- Budget Breakdown -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Budget Breakdown</h4>
                            
                            <div class="budget-breakdown bg-light p-3 rounded">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between">
                                            <span>Task Budget:</span>
                                            <span id="display-budget">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Platform Fee (10%):</span>
                                            <span id="platform-fee">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Transaction Fee:</span>
                                            <span id="transaction-fee">$0.00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total Amount:</span>
                                            <span id="total-amount">$0.00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle me-1"></i>
                                                The freelancer receives the task budget. Platform and transaction fees are additional.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Section -->
                        <div class="border-top pt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold">Ready to post your task?</h6>
                                    <p class="text-muted mb-0">Review your task details before posting.</p>
                                </div>
                                <div>
                                    <a href="/tasks" class="btn btn-outline-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Post Task
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5><i class="fas fa-lightbulb me-2"></i>Tips for Getting Great Responses</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Be specific about your requirements</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Include examples or references</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Set a realistic budget and timeline</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Mention any specific skills needed</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Be available to answer questions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-task-form');
    const description = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const budgetInput = document.getElementById('budget');
    const skillsContainer = document.getElementById('skills-container');
    const skillInput = document.getElementById('skill-input');
    const fileInput = document.getElementById('attachments');
    const filePreview = document.getElementById('file-preview');
    const fileList = document.getElementById('file-list');
    
    let selectedSkills = [];
    let selectedFiles = [];
    
    // Character count for description
    description.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    
    // Budget calculation
    budgetInput.addEventListener('input', updateBudgetBreakdown);
    
    // Skills management
    skillInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const skill = this.value.trim();
            if (skill && !selectedSkills.includes(skill)) {
                selectedSkills.push(skill);
                renderSkills();
                this.value = '';
            }
        }
    });
    
    function renderSkills() {
        skillsContainer.innerHTML = '';
        selectedSkills.forEach((skill, index) => {
            const skillElement = document.createElement('span');
            skillElement.className = 'badge bg-primary me-2 mb-2';
            skillElement.innerHTML = `
                ${skill}
                <button type="button" class="btn-close btn-close-white ms-1" onclick="removeSkill(${index})"></button>
            `;
            skillsContainer.appendChild(skillElement);
        });
        
        // Add hidden input for skills
        const existingInput = document.querySelector('input[name="skills"]');
        if (existingInput) existingInput.remove();
        
        const skillsInput = document.createElement('input');
        skillsInput.type = 'hidden';
        skillsInput.name = 'skills';
        skillsInput.value = JSON.stringify(selectedSkills);
        form.appendChild(skillsInput);
    }
    
    window.removeSkill = function(index) {
        selectedSkills.splice(index, 1);
        renderSkills();
    };
    
    // File preview
    fileInput.addEventListener('change', function() {
        selectedFiles = Array.from(this.files);
        renderFilePreview();
    });
    
    function renderFilePreview() {
        fileList.innerHTML = '';
        
        if (selectedFiles.length > 0) {
            filePreview.classList.remove('d-none');
            selectedFiles.forEach((file, index) => {
                const fileElement = document.createElement('div');
                fileElement.className = 'd-flex justify-content-between align-items-center border p-2 mb-2 rounded';
                fileElement.innerHTML = `
                    <div>
                        <i class="fas fa-file me-2"></i>
                        ${file.name} (${(file.size / 1024).toFixed(1)} KB)
                    </div>
                    <button type="button" class="btn-close" onclick="removeFile(${index})"></button>
                `;
                fileList.appendChild(fileElement);
            });
        } else {
            filePreview.classList.add('d-none');
        }
    }
    
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        
        // Update the file input
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        
        renderFilePreview();
    };
    
    // Budget breakdown calculation
    function updateBudgetBreakdown() {
        const budget = parseFloat(budgetInput.value) || 0;
        const platformFee = budget * 0.10; // 10% platform fee
        const transactionFee = budget * 0.029 + 0.30; // 2.9% + $0.30 transaction fee
        
        document.getElementById('display-budget').textContent = '$' + budget.toFixed(2);
        document.getElementById('platform-fee').textContent = '$' + platformFee.toFixed(2);
        document.getElementById('transaction-fee').textContent = '$' + transactionFee.toFixed(2);
        document.getElementById('total-amount').textContent = '$' + (budget + platformFee + transactionFee).toFixed(2);
    }
    
    // Initial budget calculation
    updateBudgetBreakdown();
    
    // Form validation
    form.addEventListener('submit', function(e) {
        let valid = true;
        
        // Basic validation
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Budget validation
        const budget = parseFloat(budgetInput.value);
        if (budget < 5) {
            valid = false;
            budgetInput.classList.add('is-invalid');
            showToast('Minimum budget is $5', 'error');
        }
        
        if (!valid) {
            e.preventDefault();
            showToast('Please fill in all required fields correctly', 'error');
        }
    });
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>