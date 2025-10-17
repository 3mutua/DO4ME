class TaskManager {
    constructor() {
        this.currentFilters = {
            category: '',
            budget_min: '',
            budget_max: '',
            urgency: '',
            status: 'open'
        };
        this.init();
    }
    
    init() {
        this.setupTaskFilters();
        this.setupTaskActions();
        this.loadTasks();
    }
    
    setupTaskFilters() {
        const filterForm = document.getElementById('task-filters');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
            
            filterForm.addEventListener('reset', () => {
                this.resetFilters();
            });
        }
    }
    
    setupTaskActions() {
        // Task creation
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#create-task-form')) {
                this.handleTaskCreation(e);
            }
        });
        
        // Proposal submission
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#submit-proposal-form')) {
                this.handleProposalSubmission(e);
            }
        });
        
        // Task status updates
        document.addEventListener('click', (e) => {
            if (e.target.matches('.start-task-btn')) {
                this.startTask(e.target.dataset.taskId);
            }
            if (e.target.matches('.complete-task-btn')) {
                this.completeTask(e.target.dataset.taskId);
            }
        });
    }
    
    async loadTasks(filters = {}) {
        try {
            const queryString = new URLSearchParams(filters).toString();
            const response = await app.apiCall(`/api/tasks?${queryString}`);
            
            if (response.success) {
                this.renderTasks(response.tasks);
                this.updatePagination(response.pagination);
            }
        } catch (error) {
            console.error('Failed to load tasks:', error);
            app.showNotification('Failed to load tasks', 'error');
        }
    }
    
    async handleTaskCreation(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const taskData = Object.fromEntries(formData);
        
        try {
            const response = await app.apiCall('/api/tasks', {
                method: 'POST',
                body: JSON.stringify(taskData)
            });
            
            if (response.success) {
                app.showNotification('Task created successfully!', 'success');
                window.location.href = `/tasks/${response.task_id}`;
            }
        } catch (error) {
            app.showNotification('Failed to create task: ' + error.message, 'error');
        }
    }
    
    async handleProposalSubmission(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const proposalData = {
            task_id: formData.get('task_id'),
            bid_amount: parseFloat(formData.get('bid_amount')),
            cover_letter: formData.get('cover_letter'),
            estimated_days: parseInt(formData.get('estimated_days'))
        };
        
        try {
            const response = await app.apiCall('/api/proposals', {
                method: 'POST',
                body: JSON.stringify(proposalData)
            });
            
            if (response.success) {
                app.showNotification('Proposal submitted successfully!', 'success');
                window.location.reload();
            }
        } catch (error) {
            app.showNotification('Failed to submit proposal: ' + error.message, 'error');
        }
    }
    
    async startTask(taskId) {
        try {
            const response = await app.apiCall(`/api/tasks/${taskId}/start`, {
                method: 'POST'
            });
            
            if (response.success) {
                app.showNotification('Task started successfully!', 'success');
                window.location.reload();
            }
        } catch (error) {
            app.showNotification('Failed to start task: ' + error.message, 'error');
        }
    }
    
    async completeTask(taskId) {
        try {
            const response = await app.apiCall(`/api/tasks/${taskId}/complete`, {
                method: 'POST'
            });
            
            if (response.success) {
                app.showNotification('Task completed successfully!', 'success');
                window.location.reload();
            }
        } catch (error) {
            app.showNotification('Failed to complete task: ' + error.message, 'error');
        }
    }
    
    applyFilters() {
        const formData = new FormData(document.getElementById('task-filters'));
        this.currentFilters = Object.fromEntries(formData);
        this.loadTasks(this.currentFilters);
    }
    
    resetFilters() {
        this.currentFilters = {
            category: '',
            budget_min: '',
            budget_max: '',
            urgency: '',
            status: 'open'
        };
        this.loadTasks(this.currentFilters);
    }
    
    renderTasks(tasks) {
        const container = document.getElementById('tasks-container');
        if (!container) return;
        
        if (tasks.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No tasks found matching your criteria.</div>';
            return;
        }
        
        container.innerHTML = tasks.map(task => `
            <div class="task-card card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">
                                <a href="/tasks/${task.id}">${this.escapeHtml(task.title)}</a>
                            </h5>
                            <p class="card-text text-muted">${this.escapeHtml(task.description.substring(0, 200))}...</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">$${task.budget}</span>
                            <span class="badge bg-secondary">${task.category}</span>
                            <span class="badge bg-${this.getUrgencyClass(task.urgency)}">${task.urgency}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">
                            Posted ${this.formatDate(task.created_at)} • 
                            ${task.proposals_count || 0} proposals
                        </small>
                        ${this.renderTaskActions(task)}
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    renderTaskActions(task) {
        if (task.client_id === app.currentUser.id) {
            return `<a href="/tasks/${task.id}/edit" class="btn btn-sm btn-outline-primary">Edit</a>`;
        } else if (task.status === 'open') {
            return `<a href="/tasks/${task.id}" class="btn btn-sm btn-primary">Submit Proposal</a>`;
        } else {
            return `<span class="badge bg-info">${task.status}</span>`;
        }
    }
    
    getUrgencyClass(urgency) {
        const classes = {
            low: 'success',
            medium: 'warning',
            high: 'danger'
        };
        return classes[urgency] || 'secondary';
    }
    
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize task manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tasks-container')) {
        window.taskManager = new TaskManager();
    }
});