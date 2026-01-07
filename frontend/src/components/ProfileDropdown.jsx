import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { LuUser, LuSettings, LuLogOut, LuCrown, LuTrendingUp, LuMail } from 'react-icons/lu';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import '../styles/profile-dropdown.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function ProfileDropdown() {
  const { tutorialAuth, tutorialLogout } = useTutorialAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    if (tutorialAuth?.email) {
      fetchProfile();
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

  const fetchProfile = async () => {
    if (!tutorialAuth?.email) return;
    
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/customer/profile.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth.email
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setProfile(data);
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    tutorialLogout();
    setIsOpen(false);
  };

  const getUserInitials = () => {
    const email = tutorialAuth?.email || '';
    return email.charAt(0).toUpperCase() || 'U';
  };

  const getUserDisplayName = () => {
    return tutorialAuth?.email?.split('@')[0] || 'User';
  };

  const getPlanBadgeColor = (planCode) => {
    switch (planCode) {
      case 'pro':
        return '#f59e0b';
      case 'premium':
        return '#8b5cf6';
      case 'basic':
      default:
        return '#64748b';
    }
  };

  const getPlanDisplayName = (planCode) => {
    switch (planCode) {
      case 'pro':
        return 'Pro Plan';
      case 'premium':
        return 'Premium Plan';
      case 'basic':
      default:
        return 'Basic Plan';
    }
  };

  return (
    <div className="profile-dropdown" ref={dropdownRef}>
      <button 
        className="profile-trigger"
        onClick={() => setIsOpen(!isOpen)}
        aria-label="User Profile"
      >
        <div className="profile-avatar">
          {getUserInitials()}
        </div>
        <span className="profile-name">{getUserDisplayName()}</span>
      </button>

      {isOpen && (
        <div className="profile-dropdown-menu">
          <div className="profile-header">
            <div className="profile-avatar large">
              {getUserInitials()}
            </div>
            <div className="profile-info">
              <h3>{getUserDisplayName()}</h3>
              <div className="profile-email">
                <LuMail size={14} />
                <span>{tutorialAuth?.email}</span>
              </div>
            </div>
          </div>

          {/* Current Plan Section */}
          <div className="profile-plan-section">
            <div className="plan-header">
              <LuCrown 
                size={16} 
                style={{ color: getPlanBadgeColor(profile?.subscription?.plan_code || 'basic') }}
              />
              <span>Current Plan</span>
            </div>
            <div className="plan-details">
              <span 
                className="plan-name"
                style={{ color: getPlanBadgeColor(profile?.subscription?.plan_code || 'basic') }}
              >
                {getPlanDisplayName(profile?.subscription?.plan_code || 'basic')}
              </span>
              <span className="plan-status">
                {profile?.subscription?.status === 'active' ? 'Active' : 'Inactive'}
              </span>
            </div>
          </div>

          {/* Quick Stats */}
          {profile?.stats && (
            <div className="profile-stats">
              {profile.stats.is_pro_user ? (
                // Pro user stats: Learning focused
                <>
                  <div className="stat-item">
                    <span className="stat-number">{profile.stats.learning_hours}h</span>
                    <span className="stat-label">Learning</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-number">{profile.stats.completed_tutorials}</span>
                    <span className="stat-label">Completed</span>
                  </div>
                  {profile.subscription?.plan_code === 'pro' && (
                    <div className="stat-item">
                      <span className="stat-number">{profile.stats.practice_uploads}</span>
                      <span className="stat-label">Uploads</span>
                    </div>
                  )}
                </>
              ) : (
                // Basic user stats: Purchase focused
                <>
                  <div className="stat-item">
                    <span className="stat-number">{profile.stats.purchased_tutorials}</span>
                    <span className="stat-label">Purchased</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-number">{profile.stats.completed_tutorials}</span>
                    <span className="stat-label">Completed</span>
                  </div>
                </>
              )}
            </div>
          )}

          <div className="profile-menu">
            {profile?.subscription?.plan_code === 'pro' && (
              <Link to="/pro-dashboard" className="profile-menu-item" onClick={() => setIsOpen(false)}>
                <LuTrendingUp size={16} />
                <span>My Progress</span>
              </Link>
            )}

            <Link to="/tutorials" className="profile-menu-item" onClick={() => setIsOpen(false)}>
              <LuSettings size={16} />
              <span>Subscription</span>
            </Link>

            <div className="profile-menu-divider"></div>

            <button className="profile-menu-item logout" onClick={handleLogout}>
              <LuLogOut size={16} />
              <span>Logout</span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}