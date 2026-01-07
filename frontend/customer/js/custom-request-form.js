/**
 * Custom Request Form - Customer Interface
 * Handles submission of custom design requests
 */

class CustomRequestForm {
    constructor() {
        this.apiUrl = '/my_little_thingz/backend/api/customer/custom-request.php';
        this.form = document.getElementById('customRequestForm');
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setMinDate();
        this.setupCharacterCounters();
    }
    
    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Character counters
        document.getElementById('title').addEventListener('input', () => this.updateCounter('title', 100));
        document.getElementById('description').addEventListener('input', () => this.updateCounter('description', 500));
        document.getElementById('requirements').addEventListener('input', () => this.updateCounter('requirements', 300));
        
        // Global function for reset
        window.resetForm = () => this.resetForm();
    }
    
    setMinDate() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const minDate = tomorrow.toISOString().split('T')[0];
        document.getElementById('deadline').min = minDate;
    }
    
    setupCharacterCounters() {
        this.updateCounter('title', 100);
        this.updateCounter('description', 500);
        this.updateCounter('requirements', 300);
    }
    
    updateCounter(fieldId, maxLength) {
        const field = document.getElementById(fieldId);
        const counter = document.getElementById(fieldId + 'Counter');
        const currentLength = field.value.length;
        
        counter.textContent = currentLength;
        
        // Change color based on usage
        if (currentLength > maxLength * 0.9) {
            counter.style.color = '#dc3545'; // Red
        } else if (currentLength > maxLength * 0.7) {
            counter.style.color = '#ffc107'; // Yellow
        } else {
            counter.style.color = '#6c757d'; // Gray
        }
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }
        
        this.setLoading(true);
        
        try {
            const formData = this.getFormData();
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Customer-Email': formData.customerEmail
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.showSuccess(data);
            } else {
                throw new Error(data.message || 'Failed to submit request');
            }
            
        } catch (error) {
            console.error('Error submitting request:', error);
            this.showError('Failed to submit request: ' + error.message);
        } finally {
            this.setLoading(false);
        }
    }
    
    validateForm() {
        const email = document.getElementById('customerEmail').value;
        const title = document.getElementById('title').value;
        const deadline = document.getElementById('deadline').value;
        
        // Email validation
        if (!email || !this.isValidEmail(email)) {
            this.showError('Please enter a valid email address');
            return false;
        }
        
        // Title validation
        if (!title || title.trim().length < 3) {
            this.showError('Please enter a title with at least 3 characters');
            return false;
        }
        
        // Deadline validation
        if (!deadline) {
            this.showError('Please select a deadline');
            return false;
        }
        
        const deadlineDate = new Date(deadline);
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        if (deadlineDate <= tomorrow) {
            this.showError('Deadline must be at least 1 day in the future');
            return false;
        }
        
        return true;
    }
    
    getFormData() {
        return {
            customerEmail: document.getElementById('customerEmail').value.trim(),
            title: document.getElementById('title').value.trim(),
            occasion: document.getElementById('occasion').value,
            description: document.getElementById('description').value.trim(),
            requirements: document.getElementById('requirements').value.trim(),
            deadline: document.getElementById('deadline').value
        };
    }
    
    showSuccess(data) {
        // Hide form
        this.form.style.display = 'none';
        
        // Show success message
        const successMessage = document.getElementById('successMessage');
        document.getElementById('successOrderId').textContent = data.order_id;
        document.getElementById('successPriority').textContent = this.formatPriority(data.priority);
        
        successMessage.style.display = 'block';
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    showError(message) {
        // Create or update error alert
        let errorAlert = document.querySelector('.alert-danger');
        
        if (!errorAlert) {
            errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger alert-dismissible fade show';
            errorAlert.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span class="error-message"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const formBody = document.querySelector('.form-body');
            formBody.insertBefore(errorAlert, formBody.firstChild);
        }
        
        errorAlert.querySelector('.error-message').textContent = message;
        
        // Scroll to error
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-dismiss after 8 seconds
        setTimeout(() => {
            if (errorAlert && errorAlert.parentNode) {
                const bsAlert = new bootstrap.Alert(errorAlert);
                bsAlert.close();
            }
        }, 8000);
    }
    
    setLoading(isLoading) {
        const submitBtn = document.querySelector('.btn-submit');
        const submitText = document.querySelector('.submit-text');
        const loadingSpinner = document.querySelector('.loading-spinner');
        
        if (isLoading) {
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
        } else {
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            loadingSpinner.style.display = 'none';
        }
    }
    
    resetForm() {
        // Show form again
        this.form.style.display = 'block';
        
        // Hide success message
        document.getElementById('successMessage').style.display = 'none';
        
        // Reset form fields
        this.form.reset();
        
        // Reset character counters
        this.setupCharacterCounters();
        
        // Remove any error alerts
        const errorAlert = document.querySelector('.alert-danger');
        if (errorAlert) {
            errorAlert.remove();
        }
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Utility functions
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    formatPriority(priority) {
        const priorityMap = {
            'low': 'Low Priority',
            'medium': 'Medium Priority',
            'high': 'High Priority (Rush)'
        };
        return priorityMap[priority] || priority;
    }
}

// Initialize form when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CustomRequestForm();
});