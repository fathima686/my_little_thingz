/**
 * Custom Requests Dashboard - Admin Interface
 * Manages the display and interaction with custom design requests
 */

class CustomRequestsDashboard {
    constructor() {
        this.apiBaseUrl = '/my_little_thingz/backend/api/admin/custom-requests.php';
        this.adminEmail = 'admin@mylittlethingz.com'; // Should be set from session
        this.currentRequests = [];
        this.filteredRequests = [];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadRequests();
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            this.loadRequests(false); // Silent refresh
        }, 30000);
    }
    
    setupEventListeners() {
        // Filter event listeners
        document.getElementById('statusFilter').addEventListener('change', () => this.applyFilters());
        document.getElementById('priorityFilter').addEventListener('change', () => this.applyFilters());
        document.getElementById('searchInput').addEventListener('input', () => this.applyFilters());
        
        // Global functions for buttons
        window.refreshRequests = () => this.loadRequests();
        window.clearFilters = () => this.clearFilters();
        window.exportRequests = () => this.exportRequests();
        window.startDesign = (requestId) => this.startDesign(requestId);
        window.viewDesign = (requestId) => this.viewDesign(requestId);
        window.updateStatus = (requestId, status) => this.updateStatus(requestId, status);
        window.updatePriority = (requestId, priority) => this.updatePriority(requestId, priority);
    }
    
    async loadRequests(showLoading = true) {
        if (showLoading) {
            this.showLoadingState();
        }
        
        try {
            const response = await fetch(this.apiBaseUrl, {
                method: 'GET',
                headers: {
                    'X-Admin-Email': this.adminEmail,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.currentRequests = data.requests;
                this.updateStats(data.stats);
                this.applyFilters();
            } else {
                throw new Error(data.message || 'Failed to load requests');
            }
            
        } catch (error) {
            console.error('Error loading requests:', error);
            this.showError('Failed to load custom requests: ' + error.message);
        }
    }
    
    applyFilters() {
        const statusFilter = document.getElementById('statusFilter').value;
        const priorityFilter = document.getElementById('priorityFilter').value;
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        this.filteredRequests = this.currentRequests.filter(request => {
            const matchesStatus = !statusFilter || request.status === statusFilter;
            const matchesPriority = !priorityFilter || request.priority === priorityFilter;
            const matchesSearch = !searchTerm || 
                request.customer_name.toLowerCase().includes(searchTerm) ||
                request.customer_email.toLowerCase().includes(searchTerm) ||
                request.title.toLowerCase().includes(searchTerm) ||
                request.order_id.toLowerCase().includes(searchTerm);
            
            return matchesStatus && matchesPriority && matchesSearch;
        });
        
        this.renderRequests();
        this.updateCounts();
    }
    
    renderRequests() {
        const tbody = document.getElementById('requestsTableBody');
        
        if (this.filteredRequests.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No custom requests found</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.filteredRequests.map(request => this.renderRequestRow(request)).join('');
    }
    
    renderRequestRow(request) {
        const statusClass = `status-${request.status}`;
        const priorityClass = `priority-${request.priority}`;
        const customerInitials = this.getCustomerInitials(request.customer_name);
        const deadlineInfo = this.getDeadlineInfo(request.deadline, request.days_until_deadline);
        
        return `
            <tr class="${priorityClass}">
                <td>
                    <strong>${request.order_id}</strong>
                    <br>
                    <small class="text-muted">#${request.id}</small>
                </td>
                <td>
                    <div class="customer-info">
                        <div class="customer-avatar">${customerInitials}</div>
                        <div>
                            <div class="fw-bold">${this.escapeHtml(request.customer_name)}</div>
                            <small class="text-muted">${this.escapeHtml(request.customer_email)}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-bold">${this.escapeHtml(request.title)}</div>
                    ${request.occasion ? `<small class="text-muted">For: ${this.escapeHtml(request.occasion)}</small>` : ''}
                    ${request.description ? `<br><small class="text-muted">${this.truncateText(request.description, 50)}</small>` : ''}
                </td>
                <td>
                    <div class="${deadlineInfo.class}">
                        ${deadlineInfo.text}
                    </div>
                    <small class="text-muted">${deadlineInfo.relative}</small>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">
                        ${this.formatStatus(request.status)}
                    </span>
                </td>
                <td>
                    <select class="form-select form-select-sm" onchange="updatePriority(${request.id}, this.value)">
                        <option value="low" ${request.priority === 'low' ? 'selected' : ''}>Low</option>
                        <option value="medium" ${request.priority === 'medium' ? 'selected' : ''}>Medium</option>
                        <option value="high" ${request.priority === 'high' ? 'selected' : ''}>High</option>
                    </select>
                </td>
                <td>
                    <div class="action-buttons">
                        ${this.renderActionButtons(request)}
                    </div>
                </td>
            </tr>
        `;
    }
    
    renderActionButtons(request) {
        let buttons = '';
        
        switch (request.status) {
            case 'submitted':
            case 'changes_requested':
                buttons += `
                    <button class="btn btn-start-design btn-sm" onclick="startDesign(${request.id})" title="Start Design">
                        <i class="fas fa-paint-brush"></i>
                    </button>
                `;
                break;
                
            case 'drafted_by_admin':
                buttons += `
                    <button class="btn btn-view-design btn-sm" onclick="viewDesign(${request.id})" title="View Design">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="updateStatus(${request.id}, 'changes_requested')" title="Request Changes">
                        <i class="fas fa-edit"></i>
                    </button>
                `;
                break;
                
            case 'approved_by_customer':
                buttons += `
                    <button class="btn btn-view-design btn-sm" onclick="viewDesign(${request.id})" title="View Design">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-success btn-sm" onclick="updateStatus(${request.id}, 'locked_for_production')" title="Lock for Production">
                        <i class="fas fa-lock"></i>
                    </button>
                `;
                break;
                
            case 'locked_for_production':
                buttons += `
                    <button class="btn btn-view-design btn-sm" onclick="viewDesign(${request.id})" title="View Design">
                        <i class="fas fa-eye"></i>
                    </button>
                    <span class="badge bg-success">
                        <i class="fas fa-check"></i> Locked
                    </span>
                `;
                break;
        }
        
        return buttons;
    }
    
    async startDesign(requestId) {
        const request = this.currentRequests.find(r => r.id == requestId);
        if (!request) return;
        
        // Open design editor in modal
        const modal = new bootstrap.Modal(document.getElementById('designEditorModal'));
        document.getElementById('modalOrderId').textContent = request.order_id;
        
        const editorUrl = `design-editor.html?request_id=${requestId}&order_id=${request.order_id}`;
        document.getElementById('editorIframe').src = editorUrl;
        
        modal.show();
        
        // Update status to drafted_by_admin
        await this.updateStatus(requestId, 'drafted_by_admin');
    }
    
    async viewDesign(requestId) {
        const request = this.currentRequests.find(r => r.id == requestId);
        if (!request) return;
        
        if (request.design_url) {
            window.open(request.design_url, '_blank');
        } else {
            // Open design editor in view mode
            const modal = new bootstrap.Modal(document.getElementById('designEditorModal'));
            document.getElementById('modalOrderId').textContent = request.order_id;
            
            const editorUrl = `design-editor.html?request_id=${requestId}&order_id=${request.order_id}&mode=view`;
            document.getElementById('editorIframe').src = editorUrl;
            
            modal.show();
        }
    }
    
    async updateStatus(requestId, newStatus) {
        try {
            const response = await fetch(this.apiBaseUrl, {
                method: 'PUT',
                headers: {
                    'X-Admin-Email': this.adminEmail,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: requestId,
                    status: newStatus
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.showSuccess('Status updated successfully');
                this.loadRequests(false);
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            console.error('Error updating status:', error);
            this.showError('Failed to update status: ' + error.message);
        }
    }
    
    async updatePriority(requestId, newPriority) {
        try {
            const response = await fetch(this.apiBaseUrl, {
                method: 'PUT',
                headers: {
                    'X-Admin-Email': this.adminEmail,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: requestId,
                    priority: newPriority
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.loadRequests(false);
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            console.error('Error updating priority:', error);
            this.showError('Failed to update priority: ' + error.message);
        }
    }
    
    updateStats(stats) {
        document.getElementById('totalRequests').textContent = stats.total_requests || 0;
        document.getElementById('pendingRequests').textContent = stats.pending_requests || 0;
        document.getElementById('completedRequests').textContent = stats.completed_requests || 0;
        document.getElementById('urgentRequests').textContent = stats.urgent_requests || 0;
    }
    
    updateCounts() {
        document.getElementById('showingCount').textContent = this.filteredRequests.length;
        document.getElementById('totalCount').textContent = this.currentRequests.length;
    }
    
    clearFilters() {
        document.getElementById('statusFilter').value = '';
        document.getElementById('priorityFilter').value = '';
        document.getElementById('searchInput').value = '';
        this.applyFilters();
    }
    
    exportRequests() {
        const csvContent = this.generateCSV();
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `custom-requests-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    generateCSV() {
        const headers = ['Order ID', 'Customer Name', 'Email', 'Title', 'Occasion', 'Deadline', 'Status', 'Priority', 'Created At'];
        const rows = this.filteredRequests.map(request => [
            request.order_id,
            request.customer_name,
            request.customer_email,
            request.title,
            request.occasion || '',
            request.deadline,
            request.status,
            request.priority,
            request.created_at
        ]);
        
        return [headers, ...rows].map(row => 
            row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',')
        ).join('\n');
    }
    
    showLoadingState() {
        document.getElementById('requestsTableBody').innerHTML = `
            <tr class="loading-row">
                <td colspan="7">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0">Loading custom requests...</p>
                </td>
            </tr>
        `;
    }
    
    showError(message) {
        this.showAlert(message, 'danger');
    }
    
    showSuccess(message) {
        this.showAlert(message, 'success');
    }
    
    showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = document.querySelector('.dashboard-container .container-fluid');
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
    
    // Utility functions
    getCustomerInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }
    
    getDeadlineInfo(deadline, daysUntil) {
        const date = new Date(deadline);
        const formattedDate = date.toLocaleDateString();
        
        let className = '';
        let relative = '';
        
        if (daysUntil < 0) {
            className = 'deadline-warning';
            relative = `${Math.abs(daysUntil)} days overdue`;
        } else if (daysUntil <= 3) {
            className = 'deadline-warning';
            relative = daysUntil === 0 ? 'Due today' : `${daysUntil} days left`;
        } else if (daysUntil <= 7) {
            className = 'deadline-soon';
            relative = `${daysUntil} days left`;
        } else {
            relative = `${daysUntil} days left`;
        }
        
        return {
            text: formattedDate,
            relative: relative,
            class: className
        };
    }
    
    formatStatus(status) {
        const statusMap = {
            'submitted': 'Submitted',
            'drafted_by_admin': 'Drafted by Admin',
            'changes_requested': 'Changes Requested',
            'approved_by_customer': 'Approved by Customer',
            'locked_for_production': 'Locked for Production'
        };
        return statusMap[status] || status;
    }
    
    truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CustomRequestsDashboard();
});