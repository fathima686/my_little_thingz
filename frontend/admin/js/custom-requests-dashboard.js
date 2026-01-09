/**
 * Custom Requests Dashboard - Admin Interface
 * Manages the display and interaction with custom design requests
 */

class CustomRequestsDashboard {
    constructor() {
        this.apiBaseUrl = '../../backend/api/admin/custom-requests-database-only.php';
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
            
            // Debug logging
            console.log('🔍 API Response:', data);
            if (data.requests && data.requests.length > 0) {
                console.log('🖼️ First request images:', data.requests[0].images);
            }
            
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
                    <td colspan="8" class="text-center py-4">
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
                    ${this.renderImages(request)}
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
        if (stats) {
            document.getElementById('totalRequests').textContent = stats.total_requests || 0;
            document.getElementById('pendingRequests').textContent = stats.pending_requests || 0;
            document.getElementById('completedRequests').textContent = stats.completed_requests || 0;
            // Calculate urgent requests (high priority + due soon)
            const urgentCount = this.currentRequests.filter(r => 
                r.priority === 'high' || (r.days_until_deadline !== undefined && r.days_until_deadline <= 3)
            ).length;
            document.getElementById('urgentRequests').textContent = urgentCount;
        } else {
            // Fallback if no stats provided
            document.getElementById('totalRequests').textContent = this.currentRequests.length;
            document.getElementById('pendingRequests').textContent = this.currentRequests.filter(r => r.status === 'submitted' || r.status === 'pending').length;
            document.getElementById('completedRequests').textContent = this.currentRequests.filter(r => r.status === 'completed').length;
            document.getElementById('urgentRequests').textContent = this.currentRequests.filter(r => r.priority === 'high').length;
        }
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
                <td colspan="8">
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
    
    renderImages(request) {
        if (!request.images || request.images.length === 0) {
            return `
                <div class="text-muted small no-images">
                    <i class="fas fa-image"></i> No images
                </div>
            `;
        }
        
        const imageCount = request.images.length;
        const firstImage = request.images[0];
        
        // Ensure we have a valid URL string
        let imageUrl = '';
        if (firstImage && typeof firstImage === 'object') {
            imageUrl = firstImage.url || firstImage.image_url || '';
        } else if (typeof firstImage === 'string') {
            imageUrl = firstImage;
        }
        
        // Fallback if no valid URL
        if (!imageUrl || imageUrl === '[object Object]') {
            return `
                <div class="text-muted small no-images">
                    <i class="fas fa-exclamation-triangle"></i> Image error
                </div>
            `;
        }
        
        // Get original name safely
        const originalName = (firstImage && firstImage.original_name) || 
                           (firstImage && firstImage.filename) || 
                           'Reference image';
        
        // Show first image as thumbnail with count
        return `
            <div class="image-preview">
                <img src="${this.escapeHtml(imageUrl)}" 
                     alt="${this.escapeHtml(originalName)}" 
                     class="img-thumbnail request-image-thumb"
                     onclick="showImageModal(${request.id}, '${this.escapeHtml(request.order_id)}')"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yMCAyOEMyNCA0IDI4IDggMjggMTJDMjggMTYgMjQgMjAgMjAgMjBDMTYgMjAgMTIgMTYgMTIgMTJDMTIgOCAxNiA0IDIwIDRaIiBmaWxsPSIjQ0NDIi8+CjxjaXJjbGUgY3g9IjIwIiBjeT0iMTIiIHI9IjMiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo='; this.onerror=null;"
                     style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;">
                ${imageCount > 1 ? `<small class="badge bg-primary position-absolute" style="top: -5px; right: -5px;">+${imageCount - 1}</small>` : ''}
            </div>
        `;
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

// Global function for showing image modal
window.showImageModal = function(requestId, orderID) {
    const dashboard = window.customRequestsDashboard;
    if (!dashboard) return;
    
    const request = dashboard.currentRequests.find(r => r.id == requestId);
    if (!request || !request.images || request.images.length === 0) return;
    
    // Helper function to get safe image URL
    function getSafeImageUrl(img) {
        if (!img) return '';
        if (typeof img === 'string') return img;
        if (typeof img === 'object') {
            return img.url || img.image_url || '';
        }
        return '';
    }
    
    // Helper function to get safe image name
    function getSafeImageName(img) {
        if (!img) return 'Reference Image';
        if (typeof img === 'object') {
            return img.original_name || img.filename || 'Reference Image';
        }
        return 'Reference Image';
    }
    
    // Helper function to get safe upload date
    function getSafeUploadDate(img) {
        if (!img || typeof img !== 'object' || !img.uploaded_at) {
            return 'Unknown date';
        }
        try {
            return new Date(img.uploaded_at).toLocaleDateString();
        } catch (e) {
            return 'Unknown date';
        }
    }
    
    // Create modal HTML with safe URL handling
    const modalHtml = `
        <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel">Reference Images - ${orderID}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            ${request.images.map((img, index) => {
                                const imageUrl = getSafeImageUrl(img);
                                const imageName = getSafeImageName(img);
                                const uploadDate = getSafeUploadDate(img);
                                
                                if (!imageUrl || imageUrl === '[object Object]') {
                                    return `
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                                    <h6 class="card-title">Image Error</h6>
                                                    <p class="text-muted">Unable to load image</p>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }
                                
                                return `
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <img src="${imageUrl}" 
                                                 class="card-img-top" 
                                                 alt="${imageName}"
                                                 style="height: 200px; object-fit: cover;"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMDAgMTQwQzEyMCAyMCAxNDAgNDAgMTQwIDYwQzE0MCA4MCAxMjAgMTAwIDEwMCAxMDBDODAgMTAwIDYwIDgwIDYwIDYwQzYwIDQwIDgwIDIwIDEwMCAyMFoiIGZpbGw9IiNDQ0MiLz4KPGNpcmNsZSBjeD0iMTAwIiBjeT0iNjAiIHI9IjE1IiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K'; this.onerror=null;">
                                            <div class="card-body">
                                                <h6 class="card-title">${imageName}</h6>
                                                <small class="text-muted">Uploaded: ${uploadDate}</small>
                                                <br>
                                                <a href="${imageUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="fas fa-external-link-alt"></i> View Full Size
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('imageModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
    
    // Clean up modal after it's hidden
    document.getElementById('imageModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
};

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.customRequestsDashboard = new CustomRequestsDashboard();
});