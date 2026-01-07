/**
 * Product Chat System JavaScript
 * Handles real-time messaging for product customization with AJAX polling
 */

class ProductChat {
    constructor(productId, userId = null, cartItemId = null, containerId = 'product-chat') {
        this.productId = productId;
        this.userId = userId;
        this.cartItemId = cartItemId;
        this.container = document.getElementById(containerId);
        this.messagesContainer = null;
        this.inputField = null;
        this.sendButton = null;
        this.pollingInterval = null;
        this.lastMessageId = 0;
        this.isLoading = false;
        this.pollDelay = 3000; // 3 seconds polling interval
        this.productInfo = null;
        
        this.init();
    }
    
    /**
     * Initialize the chat interface
     */
    init() {
        if (!this.container) {
            console.error('Chat container not found');
            return;
        }
        
        this.render();
        this.bindEvents();
        this.loadMessages();
        this.startPolling();
    }
    
    /**
     * Render the chat interface HTML
     */
    render() {
        this.container.innerHTML = `
            <div class="product-chat-container">
                <div class="chat-header">
                    <h3 class="chat-title">Customization Chat</h3>
                    <div class="chat-status">
                        <span class="product-name">Loading...</span>
                        <span class="chat-indicator">💬 Live</span>
                    </div>
                </div>
                
                <div class="chat-messages" id="chat-messages-${this.productId}">
                    <div class="chat-loading">Loading messages...</div>
                </div>
                
                <div class="chat-input-area">
                    <div class="customization-quick-actions">
                        <button type="button" class="quick-action-btn" data-action="size">Ask about Size</button>
                        <button type="button" class="quick-action-btn" data-action="color">Ask about Color</button>
                        <button type="button" class="quick-action-btn" data-action="material">Ask about Material</button>
                        <button type="button" class="quick-action-btn" data-action="custom">Custom Request</button>
                    </div>
                    <form class="chat-input-form" id="chat-form-${this.productId}">
                        <textarea 
                            class="chat-input" 
                            id="chat-input-${this.productId}"
                            placeholder="Ask about customization, size, color, or any special requirements..."
                            rows="1"
                            maxlength="1000"
                        ></textarea>
                        <button type="submit" class="chat-send-btn" id="chat-send-${this.productId}">
                            Send
                        </button>
                    </form>
                </div>
            </div>
        `;
        
        // Get references to elements
        this.messagesContainer = document.getElementById(`chat-messages-${this.productId}`);
        this.inputField = document.getElementById(`chat-input-${this.productId}`);
        this.sendButton = document.getElementById(`chat-send-${this.productId}`);
        this.form = document.getElementById(`chat-form-${this.productId}`);
        this.productNameElement = this.container.querySelector('.product-name');
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Quick action buttons
        this.container.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleQuickAction(e.target.dataset.action);
            });
        });
        
        // Auto-resize textarea
        this.inputField.addEventListener('input', () => {
            this.autoResizeTextarea();
        });
        
        // Send on Ctrl+Enter
        this.inputField.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Handle page visibility change for polling
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }
    
    /**
     * Handle quick action buttons
     */
    handleQuickAction(action) {
        const templates = {
            size: "Hi! I'd like to know about available sizes for this product. What options do you have?",
            color: "Hello! Can you tell me about the color options available for this item?",
            material: "Hi! I'm interested in the materials used for this product. Can you provide more details?",
            custom: "Hello! I have a custom request for this product. Can we discuss the possibilities?"
        };
        
        if (templates[action]) {
            this.inputField.value = templates[action];
            this.autoResizeTextarea();
            this.inputField.focus();
        }
    }
    
    /**
     * Auto-resize textarea based on content
     */
    autoResizeTextarea() {
        this.inputField.style.height = 'auto';
        this.inputField.style.height = Math.min(this.inputField.scrollHeight, 100) + 'px';
    }
    
    /**
     * Load messages from server
     */
    async loadMessages() {
        try {
            this.isLoading = true;
            
            let url = `/my_little_thingz/backend/api/product_chat/get_messages.php?product_id=${this.productId}`;
            if (this.userId) url += `&user_id=${this.userId}`;
            if (this.cartItemId) url += `&cart_item_id=${this.cartItemId}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderMessages(data.data.messages);
                this.updateLastMessageId(data.data.messages);
                this.updateProductInfo(data.data.product_info);
            } else {
                this.showError(data.message || 'Failed to load messages');
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            this.showError('Network error occurred');
        } finally {
            this.isLoading = false;
        }
    }
    
    /**
     * Send a new message
     */
    async sendMessage(messageType = 'text', customizationDetails = null) {
        const message = this.inputField.value.trim();
        
        if (!message || this.isLoading) {
            return;
        }
        
        try {
            this.isLoading = true;
            this.sendButton.disabled = true;
            this.sendButton.textContent = 'Sending...';
            
            const payload = {
                product_id: this.productId,
                message: message,
                message_type: messageType
            };
            
            if (this.userId) payload.user_id = this.userId;
            if (this.cartItemId) payload.cart_item_id = this.cartItemId;
            if (customizationDetails) payload.customization_details = customizationDetails;
            
            const response = await fetch('/my_little_thingz/backend/api/product_chat/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.inputField.value = '';
                this.autoResizeTextarea();
                this.loadMessages(); // Refresh messages
            } else {
                this.showError(data.message || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showError('Network error occurred');
        } finally {
            this.isLoading = false;
            this.sendButton.disabled = false;
            this.sendButton.textContent = 'Send';
        }
    }
    
    /**
     * Update product information in header
     */
    updateProductInfo(productInfo) {
        if (productInfo && this.productNameElement) {
            this.productInfo = productInfo;
            this.productNameElement.textContent = productInfo.name;
        }
    }
    
    /**
     * Render messages in the chat container
     */
    renderMessages(messages) {
        if (!messages || messages.length === 0) {
            this.messagesContainer.innerHTML = `
                <div class="chat-empty">
                    <div class="chat-empty-icon">🎨</div>
                    <div class="chat-empty-text">
                        Start a conversation about customization!<br>
                        Ask about sizes, colors, materials, or any special requests.
                    </div>
                </div>
            `;
            return;
        }
        
        const messagesHtml = messages.map(message => this.renderMessage(message)).join('');
        this.messagesContainer.innerHTML = messagesHtml;
        this.scrollToBottom();
    }
    
    /**
     * Render a single message
     */
    renderMessage(message) {
        const isOwnMessage = message.is_own_message;
        const messageClass = isOwnMessage ? 'own-message' : 'other-message';
        
        let customizationHtml = '';
        if (message.customization_data) {
            customizationHtml = `
                <div class="customization-details">
                    <strong>Customization Request:</strong>
                    <pre>${JSON.stringify(message.customization_data, null, 2)}</pre>
                </div>
            `;
        }
        
        let messageTypeIcon = '';
        if (message.message_type === 'customization_request') {
            messageTypeIcon = '<span class="message-type-icon">🎨</span>';
        }
        
        return `
            <div class="chat-message ${messageClass}" data-message-id="${message.id}">
                <div class="message-bubble">
                    ${messageTypeIcon}
                    ${this.escapeHtml(message.message_content)}
                    ${customizationHtml}
                </div>
                <div class="message-meta">
                    <span class="sender-name">${this.escapeHtml(message.sender_name)}</span>
                    <span class="message-time">${message.formatted_time}</span>
                </div>
            </div>
        `;
    }
    
    /**
     * Update the last message ID for polling
     */
    updateLastMessageId(messages) {
        if (messages && messages.length > 0) {
            this.lastMessageId = Math.max(...messages.map(m => parseInt(m.id)));
        }
    }
    
    /**
     * Scroll to the bottom of messages
     */
    scrollToBottom() {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    }
    
    /**
     * Start polling for new messages
     */
    startPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        this.pollingInterval = setInterval(() => {
            if (!this.isLoading && !document.hidden) {
                this.loadMessages();
            }
        }, this.pollDelay);
    }
    
    /**
     * Stop polling for new messages
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
    
    /**
     * Show error message
     */
    showError(message) {
        const errorHtml = `
            <div class="chat-error">
                ${this.escapeHtml(message)}
            </div>
        `;
        
        // Show error in messages area if empty, otherwise show as notification
        if (this.messagesContainer.querySelector('.chat-empty') || this.messagesContainer.querySelector('.chat-loading')) {
            this.messagesContainer.innerHTML = errorHtml;
        } else {
            // Create temporary error notification
            const errorDiv = document.createElement('div');
            errorDiv.innerHTML = errorHtml;
            this.messagesContainer.appendChild(errorDiv);
            
            // Remove error after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.parentNode.removeChild(errorDiv);
                }
            }, 5000);
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Destroy the chat instance
     */
    destroy() {
        this.stopPolling();
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

/**
 * Initialize product chat when DOM is ready
 */
function initProductChat(productId, userId = null, cartItemId = null, containerId = 'product-chat') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new ProductChat(productId, userId, cartItemId, containerId);
        });
    } else {
        new ProductChat(productId, userId, cartItemId, containerId);
    }
}

// Export for use in other scripts
window.ProductChat = ProductChat;
window.initProductChat = initProductChat;