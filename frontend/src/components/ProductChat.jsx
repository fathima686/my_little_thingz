import React, { useState, useEffect, useRef } from 'react';
import { LuSend, LuWand, LuPalette, LuRuler, LuPackage } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const ProductChat = ({ productId, userId, cartItemId, productName, onClose }) => {
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState('');
  const messagesEndRef = useRef(null);
  const pollIntervalRef = useRef(null);

  // Quick action templates
  const quickActions = [
    {
      id: 'size',
      icon: <LuRuler size={16} />,
      label: 'Size',
      template: "Hi! I'd like to know about available sizes for this product. What options do you have?"
    },
    {
      id: 'color',
      icon: <LuPalette size={16} />,
      label: 'Color',
      template: "Hello! Can you tell me about the color options available for this item?"
    },
    {
      id: 'material',
      icon: <LuPackage size={16} />,
      label: 'Material',
      template: "Hi! I'm interested in the materials used for this product. Can you provide more details?"
    },
    {
      id: 'custom',
      icon: <LuWand size={16} />,
      label: 'Custom',
      template: "Hello! I have a custom request for this product. Can we discuss the possibilities?"
    }
  ];

  // Scroll to bottom of messages
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  // Load messages from server
  const loadMessages = async () => {
    if (!productId || !userId) return;
    
    try {
      setLoading(true);
      // Use simple test API first to debug
      let url = `${API_BASE}/test-chat-api-simple.php?product_id=${productId}&user_id=${userId}`;
      if (cartItemId) url += `&cart_item_id=${cartItemId}`;

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': userId.toString(),
        }
        // Removed credentials: 'include' to avoid CORS complexity
      });

      const data = await response.json();

      if (data.success) {
        setMessages(data.data.messages || []);
        setError('');
      } else {
        setError(data.message || 'Failed to load messages');
        // If setup is needed, show helpful message
        if (data.setup_url) {
          setError(`Database setup required. Please visit: ${window.location.origin}/${data.setup_url}`);
        }
      }
    } catch (err) {
      console.error('Error loading messages:', err);
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  // Send message to server
  const sendMessage = async (messageText = null) => {
    const message = messageText || newMessage.trim();
    if (!message || sending) return;

    try {
      setSending(true);
      
      const payload = {
        product_id: productId,
        user_id: userId,
        message: message,
        message_type: 'text'
      };

      if (cartItemId) payload.cart_item_id = cartItemId;

      // Use simple test API first to debug
      const response = await fetch(`${API_BASE}/test-chat-api-simple.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': userId.toString(),
        },
        // Removed credentials: 'include' to avoid CORS complexity
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (data.success) {
        setNewMessage('');
        loadMessages(); // Refresh messages
      } else {
        setError(data.message || 'Failed to send message');
      }
    } catch (err) {
      console.error('Error sending message:', err);
      setError('Network error occurred');
    } finally {
      setSending(false);
    }
  };

  // Handle quick action click
  const handleQuickAction = (template) => {
    setNewMessage(template);
  };

  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    sendMessage();
  };

  // Start polling for new messages
  const startPolling = () => {
    if (pollIntervalRef.current) {
      clearInterval(pollIntervalRef.current);
    }
    
    pollIntervalRef.current = setInterval(() => {
      if (!document.hidden) {
        loadMessages();
      }
    }, 3000); // Poll every 3 seconds
  };

  // Stop polling
  const stopPolling = () => {
    if (pollIntervalRef.current) {
      clearInterval(pollIntervalRef.current);
      pollIntervalRef.current = null;
    }
  };

  // Initialize component
  useEffect(() => {
    loadMessages();
    startPolling();

    // Handle page visibility change
    const handleVisibilityChange = () => {
      if (document.hidden) {
        stopPolling();
      } else {
        startPolling();
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      stopPolling();
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [productId, userId, cartItemId]);

  // Scroll to bottom when messages change
  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  return (
    <div className="product-chat-container">
      {/* Chat Header */}
      <div className="chat-header">
        <div className="chat-title">
          <LuWand size={18} />
          <span>Customization Chat</span>
        </div>
        <div className="chat-status">
          <div className="product-name">{productName || 'Product'}</div>
          <div className="chat-indicator">💬 Live</div>
        </div>
        {onClose && (
          <button className="chat-close-btn" onClick={onClose}>
            ×
          </button>
        )}
      </div>

      {/* Messages Area */}
      <div className="chat-messages">
        {loading && messages.length === 0 ? (
          <div className="chat-loading">Loading messages...</div>
        ) : error ? (
          <div className="chat-error">{error}</div>
        ) : messages.length === 0 ? (
          <div className="chat-empty">
            <div className="chat-empty-icon">🎨</div>
            <div className="chat-empty-text">
              Start a conversation about customization!<br />
              Ask about sizes, colors, materials, or any special requests.
            </div>
          </div>
        ) : (
          <>
            {messages.map((message) => (
              <div
                key={message.id}
                className={`chat-message ${message.is_own_message ? 'own-message' : 'other-message'}`}
              >
                <div className="message-bubble">
                  {message.message_type === 'customization_request' && (
                    <span className="message-type-icon">🎨</span>
                  )}
                  {message.message_content}
                  {message.customization_data && (
                    <div className="customization-details">
                      <strong>Customization Request:</strong>
                      <pre>{JSON.stringify(message.customization_data, null, 2)}</pre>
                    </div>
                  )}
                </div>
                <div className="message-meta">
                  <span className="sender-name">{message.sender_name}</span>
                  <span className="message-time">{message.formatted_time}</span>
                </div>
              </div>
            ))}
            <div ref={messagesEndRef} />
          </>
        )}
      </div>

      {/* Quick Actions */}
      <div className="customization-quick-actions">
        {quickActions.map((action) => (
          <button
            key={action.id}
            type="button"
            className="quick-action-btn"
            onClick={() => handleQuickAction(action.template)}
            title={`Ask about ${action.label}`}
          >
            {action.icon}
            {action.label}
          </button>
        ))}
      </div>

      {/* Input Area */}
      <div className="chat-input-area">
        <form className="chat-input-form" onSubmit={handleSubmit}>
          <textarea
            className="chat-input"
            value={newMessage}
            onChange={(e) => setNewMessage(e.target.value)}
            placeholder="Ask about customization, size, color, or any special requirements..."
            rows={1}
            maxLength={1000}
            onKeyDown={(e) => {
              if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
              }
            }}
            style={{
              minHeight: '40px',
              maxHeight: '100px',
              resize: 'none',
              overflow: 'auto'
            }}
          />
          <button
            type="submit"
            className="chat-send-btn"
            disabled={sending || !newMessage.trim()}
          >
            {sending ? (
              <div className="spinner" />
            ) : (
              <LuSend size={16} />
            )}
          </button>
        </form>
      </div>

      <style>{`
        .product-chat-container {
          background: #ffffff;
          border: 1px solid #e8e8e8;
          border-radius: 12px;
          box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
          margin: 20px 0;
          overflow: hidden;
          transition: box-shadow 0.3s ease;
        }

        .product-chat-container:hover {
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .chat-header {
          background: linear-gradient(135deg, #fafafa 0%, #f5f7fa 100%);
          padding: 16px 20px;
          border-bottom: 1px solid #e8e8e8;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }

        .chat-title {
          font-size: 16px;
          font-weight: 600;
          color: #2c3e50;
          margin: 0;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .chat-status {
          display: flex;
          flex-direction: column;
          align-items: flex-end;
          gap: 4px;
        }

        .product-name {
          font-size: 12px;
          color: #6c757d;
          font-weight: 500;
        }

        .chat-indicator {
          font-size: 10px;
          color: #adb5bd;
          background: #ffffff;
          padding: 2px 6px;
          border-radius: 8px;
          border: 1px solid #e8e8e8;
        }

        .chat-close-btn {
          background: none;
          border: none;
          font-size: 20px;
          cursor: pointer;
          color: #6c757d;
          padding: 4px;
          border-radius: 4px;
        }

        .chat-close-btn:hover {
          background: #f8f9fa;
          color: #2c3e50;
        }

        .chat-messages {
          height: 300px;
          overflow-y: auto;
          padding: 16px;
          background: #fafafa;
          scroll-behavior: smooth;
        }

        .chat-messages::-webkit-scrollbar {
          width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
          background: #fafafa;
        }

        .chat-messages::-webkit-scrollbar-thumb {
          background: #e8e8e8;
          border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
          background: #adb5bd;
        }

        .chat-message {
          margin-bottom: 12px;
          display: flex;
          flex-direction: column;
          animation: messageSlideIn 0.3s ease-out;
        }

        @keyframes messageSlideIn {
          from {
            opacity: 0;
            transform: translateY(10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .chat-message.own-message {
          align-items: flex-end;
        }

        .chat-message.other-message {
          align-items: flex-start;
        }

        .message-bubble {
          max-width: 70%;
          padding: 12px 16px;
          border-radius: 18px;
          position: relative;
          word-wrap: break-word;
          line-height: 1.4;
        }

        .own-message .message-bubble {
          background: #f0f4ff;
          border: 1px solid #d1e0ff;
          color: #2c3e50;
        }

        .other-message .message-bubble {
          background: #f8f9fa;
          border: 1px solid #e9ecef;
          color: #2c3e50;
        }

        .message-meta {
          font-size: 11px;
          color: #adb5bd;
          margin-top: 4px;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .own-message .message-meta {
          justify-content: flex-end;
        }

        .other-message .message-meta {
          justify-content: flex-start;
        }

        .sender-name {
          font-weight: 500;
          color: #6c757d;
        }

        .message-type-icon {
          margin-right: 6px;
          font-size: 14px;
        }

        .customization-details {
          margin-top: 8px;
          padding: 8px;
          background: rgba(255, 255, 255, 0.5);
          border-radius: 8px;
          border: 1px solid #e8e8e8;
          font-size: 12px;
        }

        .customization-details pre {
          margin: 4px 0 0 0;
          font-family: monospace;
          font-size: 11px;
          color: #6c757d;
          white-space: pre-wrap;
        }

        .chat-empty {
          text-align: center;
          padding: 40px 20px;
          color: #adb5bd;
        }

        .chat-empty-icon {
          font-size: 48px;
          margin-bottom: 12px;
          opacity: 0.5;
        }

        .chat-empty-text {
          font-size: 14px;
          line-height: 1.5;
        }

        .chat-loading {
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 20px;
          color: #adb5bd;
          font-size: 14px;
        }

        .chat-error {
          background: #fff5f5;
          border: 1px solid #fed7d7;
          color: #c53030;
          padding: 12px 16px;
          margin: 12px 0;
          border-radius: 8px;
          font-size: 14px;
          text-align: center;
        }

        .customization-quick-actions {
          display: flex;
          gap: 8px;
          margin: 12px 20px 0;
          flex-wrap: wrap;
        }

        .quick-action-btn {
          background: #fafafa;
          border: 1px solid #e8e8e8;
          border-radius: 16px;
          padding: 6px 12px;
          font-size: 12px;
          color: #6c757d;
          cursor: pointer;
          transition: all 0.2s ease;
          white-space: nowrap;
          display: flex;
          align-items: center;
          gap: 4px;
        }

        .quick-action-btn:hover {
          background: #f0f4ff;
          border-color: #d1e0ff;
          color: #2c3e50;
        }

        .quick-action-btn:active {
          transform: scale(0.98);
        }

        .chat-input-area {
          padding: 16px 20px;
          background: #ffffff;
          border-top: 1px solid #e8e8e8;
        }

        .chat-input-form {
          display: flex;
          gap: 12px;
          align-items: flex-end;
        }

        .chat-input {
          flex: 1;
          padding: 10px 14px;
          border: 1px solid #dee2e6;
          border-radius: 20px;
          background: #ffffff;
          color: #2c3e50;
          font-size: 14px;
          line-height: 1.4;
          font-family: inherit;
          transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .chat-input:focus {
          outline: none;
          border-color: #80bdff;
          box-shadow: 0 0 0 3px rgba(128, 189, 255, 0.1);
        }

        .chat-input::placeholder {
          color: #adb5bd;
        }

        .chat-send-btn {
          background: #6c63ff;
          color: #ffffff;
          border: none;
          border-radius: 20px;
          padding: 10px 16px;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
          transition: background-color 0.2s ease, transform 0.1s ease;
          white-space: nowrap;
          display: flex;
          align-items: center;
          justify-content: center;
          min-width: 44px;
        }

        .chat-send-btn:hover:not(:disabled) {
          background: #5a52d5;
          transform: translateY(-1px);
        }

        .chat-send-btn:active {
          transform: translateY(0);
        }

        .chat-send-btn:disabled {
          background: #adb5bd;
          cursor: not-allowed;
          transform: none;
        }

        .spinner {
          width: 16px;
          height: 16px;
          border: 2px solid transparent;
          border-top: 2px solid #ffffff;
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }

        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
          .product-chat-container {
            margin: 16px 0;
            border-radius: 8px;
          }
          
          .chat-header {
            padding: 12px 16px;
          }
          
          .chat-messages {
            height: 250px;
            padding: 12px;
          }
          
          .message-bubble {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 16px;
          }
          
          .chat-input-area {
            padding: 12px 16px;
          }
          
          .chat-input {
            font-size: 16px;
          }
          
          .chat-send-btn {
            padding: 10px 12px;
            font-size: 14px;
          }

          .customization-quick-actions {
            margin: 12px 16px 0;
          }
        }
      `}</style>
    </div>
  );
};

export default ProductChat;