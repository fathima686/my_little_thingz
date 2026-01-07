import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { LuMail, LuCrown, LuArrowLeft, LuCalendar, LuTrendingUp, LuBookOpen, LuUpload } from 'react-icons/lu';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import '../styles/profile-edit.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function ProfileEdit() {
  const { tutorialAuth } = useTutorialAuth();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [profile, setProfile] = useState(null);

  useEffect(() => {
    if (!tutorialAuth?.email) {
      navigate('/tutorial-login');
      return;
    }
    fetchProfile();
  }, [tutorialAuth?.email, navigate]);

  const fetchProfile = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/profile.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth.email
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setProfile(data);
      } else {
        setError(data.message || 'Failed to load profile');
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
      setError('Failed to load profile');
    } finally {
      setLoading(false);
    }
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

  const getUserInitials = () => {
    const email = tutorialAuth?.email || '';
    return email.charAt(0).toUpperCase() || 'U';
  };

  const getUserDisplayName = () => {
    return tutorialAuth?.email?.split('@')[0] || 'User';
  };

  if (loading) {
    return (
      <div className="profile-edit-container">
        <div className="loading-spinner">Loading profile...</div>
      </div>
    );
  }

  return (
    <div className="profile-edit-container">
      <div className="profile-edit-header">
        <Link to="/tutorials" className="back-link">
          <LuArrowLeft size={20} />
          Back to Dashboard
        </Link>
        <h1>My Profile</h1>
        <p>View your account information and subscription details</p>
      </div>

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      <div className="profile-content">
        {/* User Info Card */}
        <div className="profile-card">
          <div className="profile-card-header">
            <div className="profile-avatar-large">
              {getUserInitials()}
            </div>
            <div className="profile-basic-info">
              <h2>{getUserDisplayName()}</h2>
              <div className="email-info">
                <LuMail size={16} />
                <span>{tutorialAuth?.email}</span>
              </div>
              {profile?.subscription && (
                <div className="member-since">
                  <LuCalendar size={16} />
                  <span>Member since {new Date(profile.subscription.created_at).toLocaleDateString()}</span>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Subscription Card */}
        <div className="profile-card">
          <div className="card-header">
            <LuCrown 
              size={20} 
              style={{ color: getPlanBadgeColor(profile?.subscription?.plan_code || 'basic') }}
            />
            <h3>Current Subscription</h3>
          </div>
          <div className="subscription-details">
            <div className="plan-info">
              <span 
                className="plan-name"
                style={{ color: getPlanBadgeColor(profile?.subscription?.plan_code || 'basic') }}
              >
                {getPlanDisplayName(profile?.subscription?.plan_code || 'basic')}
              </span>
              <span className={`plan-status ${profile?.subscription?.status === 'active' ? 'active' : 'inactive'}`}>
                {profile?.subscription?.status === 'active' ? 'Active' : 'Inactive'}
              </span>
            </div>
            
            {profile?.subscription?.plan_code !== 'pro' && (
              <div className="upgrade-suggestion">
                <p>Upgrade to Pro for advanced features like practice uploads and live workshops!</p>
                <Link to="/tutorials" className="upgrade-btn">
                  View Plans
                </Link>
              </div>
            )}
          </div>
        </div>

        {/* Learning Stats Card */}
        {profile?.stats && (
          <div className="profile-card">
            <div className="card-header">
              <LuTrendingUp size={20} />
              <h3>{profile.stats.is_pro_user ? 'Learning Progress' : 'Tutorial Progress'}</h3>
            </div>
            <div className="stats-grid">
              {profile.stats.is_pro_user ? (
                // Pro user stats: Learning focused
                <>
                  <div className="stat-card">
                    <div className="stat-icon">
                      <LuBookOpen size={24} />
                    </div>
                    <div className="stat-info">
                      <span className="stat-number">{profile.stats.learning_hours}h</span>
                      <span className="stat-label">Learning Hours</span>
                    </div>
                  </div>
                  
                  <div className="stat-card">
                    <div className="stat-icon">
                      <LuTrendingUp size={24} />
                    </div>
                    <div className="stat-info">
                      <span className="stat-number">{profile.stats.completed_tutorials}</span>
                      <span className="stat-label">Completed Tutorials</span>
                    </div>
                  </div>
                  
                  {profile.stats.in_progress_tutorials > 0 && (
                    <div className="stat-card">
                      <div className="stat-icon">
                        <LuBookOpen size={24} />
                      </div>
                      <div className="stat-info">
                        <span className="stat-number">{profile.stats.in_progress_tutorials}</span>
                        <span className="stat-label">In Progress</span>
                      </div>
                    </div>
                  )}
                  
                  {profile.subscription?.plan_code === 'pro' && (
                    <div className="stat-card">
                      <div className="stat-icon">
                        <LuUpload size={24} />
                      </div>
                      <div className="stat-info">
                        <span className="stat-number">{profile.stats.practice_uploads}</span>
                        <span className="stat-label">Practice Uploads</span>
                      </div>
                    </div>
                  )}
                </>
              ) : (
                // Basic user stats: Purchase focused
                <>
                  <div className="stat-card">
                    <div className="stat-icon">
                      <LuBookOpen size={24} />
                    </div>
                    <div className="stat-info">
                      <span className="stat-number">{profile.stats.purchased_tutorials}</span>
                      <span className="stat-label">Tutorials Purchased</span>
                    </div>
                  </div>
                  
                  <div className="stat-card">
                    <div className="stat-icon">
                      <LuTrendingUp size={24} />
                    </div>
                    <div className="stat-info">
                      <span className="stat-number">{profile.stats.completed_tutorials}</span>
                      <span className="stat-label">Completed</span>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        )}

        {/* Quick Actions */}
        <div className="profile-card">
          <div className="card-header">
            <h3>Quick Actions</h3>
          </div>
          <div className="quick-actions">
            <Link to="/tutorials" className="action-btn">
              <LuBookOpen size={18} />
              Browse Tutorials
            </Link>
            
            {profile?.subscription?.plan_code === 'pro' && (
              <Link to="/pro-dashboard" className="action-btn">
                <LuTrendingUp size={18} />
                Pro Dashboard
              </Link>
            )}
            
            <Link to="/tutorials" className="action-btn">
              <LuCrown size={18} />
              Manage Subscription
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}