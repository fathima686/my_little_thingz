import React, { useState, useEffect, useRef } from 'react';
import { LuBell, LuCheck, LuX, LuExternalLink, LuCheckCheck } from 'react-icons/lu';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import '../styles/notification-dropdown.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function NotificationDropdown() {
  const { tutorialAuth } = useTutorialAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    if (tutorialAuth?.email) {
      fetchNotifications();
      // Poll for new notifications every 30 seconds
      const interval = setInterval(fetchNotifications, 30000);
      return () => clearInterval(interval);
    }
  }, [tutorialAuth?.email]);

  useEffect(() => {
    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const fetchNotifications = async () => {
    if (!tutorialAuth?.email) return;
    
    try {
      const res = await fetch(`${API_BASE}/customer/notifications.php?limit=10`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth.email
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setNotifications(data.notifications);
        setUnreadCount(data.unread_count);
      }
    } catch (error) {
      console.error('Error fetching notifications:', error);
    }
  };

  const markAsRead = async (notificationId) => {
    try {
      const res = await fetch(`${API_BASE}/customer/notifications.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Tutorial-Email': tutorialAuth.email
        },
        body: JSON.stringify({ notification_id: notificationId })
      });
      
      if (res.ok) {
        fetchNotifications(); // Refresh notifications
      }
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const markAllAsRead = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/customer/notifications.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Tutorial-Email': tutorialAuth.email
        },
        body: JSON.stringify({ mark_all_read: true })
      });
      
      if (res.ok) {
        fetchNotifications(); // Refresh notifications
      }
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleNotificationClick = (notification) => {
    if (!notification.is_read) {
      markAsRead(notification.id);
    }
    
    if (notification.action_url) {
      window.location.href = notification.action_url;
    }
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'success':
        return <LuCheck className="notification-icon success" />;
      case 'error':
        return <LuX className="notification-icon error" />;
      case 'warning':
        return <LuBell className="notification-icon warning" />;
      default:
        return <LuBell className="notification-icon info" />;
    }
  };

  const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
  };

  return (
    <div className="notification-dropdown" ref={dropdownRef}>
      <button 
        className="notification-trigger"
        onClick={() => setIsOpen(!isOpen)}
        aria-label="Notifications"
      >
        <LuBell size={18} />
        {unreadCount > 0 && (
          <span className="notification-badge">{unreadCount > 99 ? '99+' : unreadCount}</span>
        )}
      </button>

      {isOpen && (
        <div className="notification-dropdown-menu">
          <div className="notification-header">
            <h3>Notifications</h3>
            {unreadCount > 0 && (
              <button 
                className="mark-all-read-btn"
                onClick={markAllAsRead}
                disabled={loading}
              >
                <LuCheckCheck size={16} />
                Mark all read
              </button>
            )}
          </div>

          <div className="notification-list">
            {notifications.length > 0 ? (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`notification-item ${!notification.is_read ? 'unread' : ''}`}
                  onClick={() => handleNotificationClick(notification)}
                >
                  <div className="notification-content">
                    <div className="notification-main">
                      {getNotificationIcon(notification.type)}
                      <div className="notification-text">
                        <h4>{notification.title}</h4>
                        <p>{notification.message}</p>
                      </div>
                      {notification.action_url && (
                        <LuExternalLink className="notification-action" size={14} />
                      )}
                    </div>
                    <div className="notification-meta">
                      <span className="notification-time">
                        {formatTimeAgo(notification.created_at)}
                      </span>
                      {!notification.is_read && <div className="unread-dot"></div>}
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div className="no-notifications">
                <LuBell size={32} />
                <p>No notifications yet</p>
              </div>
            )}
          </div>

          {notifications.length > 0 && (
            <div className="notification-footer">
              <button className="view-all-btn">
                View All Notifications
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}