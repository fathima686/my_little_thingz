import React, { useState, useEffect } from 'react';
import { LuTrendingUp, LuVideo, LuUpload, LuAward, LuClock, LuCheck, LuTarget } from 'react-icons/lu';
import '../styles/progress-tracker.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const ProgressTracker = ({ userEmail, subscriptionPlan }) => {
  const [progressData, setProgressData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (userEmail && (subscriptionPlan === 'pro' || subscriptionPlan === 'premium')) {
      fetchProgressData();
    } else {
      setLoading(false);
    }
  }, [userEmail, subscriptionPlan]);

  const fetchProgressData = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE}/pro/learning-progress-standardized.php`, {
        headers: {
          'X-Tutorial-Email': userEmail
        }
      });
      
      const data = await response.json();
      if (data.status === 'success') {
        setProgressData(data);
      } else {
        setError(data.message || 'Failed to fetch progress data');
      }
    } catch (error) {
      console.error('Error fetching progress:', error);
      setError('Failed to load progress data');
    } finally {
      setLoading(false);
    }
  };

  const calculateOverallProgress = () => {
    if (!progressData || !progressData.overall_progress) return 0;
    return progressData.overall_progress.completion_percentage || 0;
  };

  const getProgressStats = () => {
    if (!progressData || !progressData.overall_progress) return { completed: 0, inProgress: 0, total: 0, practiceUploads: 0 };
    
    return {
      completed: progressData.overall_progress.completed_tutorials || 0,
      inProgress: (progressData.overall_progress.total_tutorials || 0) - (progressData.overall_progress.completed_tutorials || 0),
      total: progressData.overall_progress.total_tutorials || 0,
      practiceUploads: progressData.tutorial_progress?.filter(t => t.practice_approved).length || 0
    };
  };

  if (subscriptionPlan !== 'pro' && subscriptionPlan !== 'premium') {
    return (
      <div className="progress-tracker-upgrade">
        <div className="upgrade-message">
          <LuTrendingUp size={48} />
          <h3>Upgrade to Track Your Progress</h3>
          <p>Get detailed progress tracking, certificates, and more with Premium or Pro plans.</p>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="progress-tracker-loading">
        <div className="loading-spinner"></div>
        <p>Loading your progress...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="progress-tracker-error">
        <p>Error loading progress: {error}</p>
        <button onClick={fetchProgressData} className="retry-button">
          Try Again
        </button>
      </div>
    );
  }

  const overallProgress = calculateOverallProgress();
  const stats = getProgressStats();
  const canGenerateCertificate = progressData?.certificate_rules?.eligible || false;

  return (
    <div className="progress-tracker">
      <div className="progress-header">
        <h2>Your Learning Progress</h2>
        <div className="progress-summary">
          <div className="overall-progress">
            <div className="progress-circle">
              <svg viewBox="0 0 36 36" className="circular-chart">
                <path
                  className="circle-bg"
                  d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831"
                />
                <path
                  className="circle"
                  strokeDasharray={`${overallProgress}, 100`}
                  d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831"
                />
              </svg>
              <div className="progress-text">
                <span className="progress-percentage">{overallProgress}%</span>
                <span className="progress-label">Complete</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="progress-stats-grid">
        <div className="stat-card">
          <div className="stat-icon completed">
            <LuCheck size={24} />
          </div>
          <div className="stat-content">
            <div className="stat-number">{stats.completed}</div>
            <div className="stat-label">Completed</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon in-progress">
            <LuClock size={24} />
          </div>
          <div className="stat-content">
            <div className="stat-number">{stats.inProgress}</div>
            <div className="stat-label">In Progress</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon total">
            <LuVideo size={24} />
          </div>
          <div className="stat-content">
            <div className="stat-number">{stats.total}</div>
            <div className="stat-label">Total Tutorials</div>
          </div>
        </div>

        {subscriptionPlan === 'pro' && (
          <div className="stat-card">
            <div className="stat-icon uploads">
              <LuUpload size={24} />
            </div>
            <div className="stat-content">
              <div className="stat-number">{stats.practiceUploads}</div>
              <div className="stat-label">Practice Uploads</div>
            </div>
          </div>
        )}
      </div>

      {subscriptionPlan === 'pro' && (
        <div className="certificate-section">
          <div className="certificate-card">
            <div className="certificate-icon">
              <LuAward size={32} />
            </div>
            <div className="certificate-content">
              <h3>Certificate of Completion</h3>
              <p>
                {canGenerateCertificate 
                  ? 'Congratulations! You can now generate your certificate.'
                  : progressData?.certificate_rules?.message || `Complete 80% of the course to unlock your certificate. (${Math.round(overallProgress)}% complete)`
                }
              </p>
              <button 
                className={`certificate-button ${canGenerateCertificate ? 'enabled' : 'disabled'}`}
                disabled={!canGenerateCertificate}
                onClick={() => {
                  if (canGenerateCertificate) {
                    window.open(`${API_BASE}/pro/certificate-standardized.php?email=${userEmail}&format=pdf`, '_blank');
                  }
                }}
              >
                {canGenerateCertificate ? 'Generate Certificate' : 'Certificate Locked'}
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="tutorial-progress-list">
        <h3>Tutorial Progress Details</h3>
        {progressData?.tutorial_progress?.length > 0 ? (
          <div className="tutorial-progress-items">
            {progressData.tutorial_progress.map((tutorial, index) => (
              <div key={index} className="tutorial-progress-item">
                <div className="tutorial-info">
                  <h4>{tutorial.title}</h4>
                  <div className="tutorial-meta">
                    <span className="tutorial-category">{tutorial.category}</span>
                    <span className="tutorial-duration">{tutorial.duration} min</span>
                  </div>
                </div>
                <div className="tutorial-progress-bar">
                  <div className="progress-bar-bg">
                    <div 
                      className="progress-bar-fill"
                      style={{ width: `${tutorial.progress_percentage || 0}%` }}
                    ></div>
                  </div>
                  <span className="progress-percentage">{tutorial.progress_percentage || 0}%</span>
                </div>
                <div className="tutorial-components">
                  <div className={`component-status ${tutorial.video_completed ? 'completed' : 'incomplete'}`}>
                    📹 Video: {tutorial.video_completed ? 'Complete' : 'Incomplete'}
                  </div>
                  <div className={`component-status ${tutorial.practice_approved ? 'completed' : (tutorial.practice_uploaded ? 'pending' : 'incomplete')}`}>
                    📤 Practice: {tutorial.practice_approved ? 'Approved' : (tutorial.practice_uploaded ? 'Pending' : 'Not Uploaded')}
                  </div>
                  {subscriptionPlan === 'pro' && (
                    <div className={`component-status ${tutorial.live_session_completed ? 'completed' : 'incomplete'}`}>
                      👥 Live: {tutorial.live_session_completed ? 'Complete' : 'Not Attended'}
                    </div>
                  )}
                </div>
                {tutorial.admin_feedback && (
                  <div className="admin-feedback">
                    <strong>Feedback:</strong> {tutorial.admin_feedback}
                  </div>
                )}
              </div>
            ))}
          </div>
        ) : (
          <div className="no-progress">
            <LuTrendingUp size={48} />
            <p>Start watching tutorials to see your progress here!</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProgressTracker;