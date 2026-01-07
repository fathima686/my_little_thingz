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
      const response = await fetch(`${API_BASE}/pro/learning-progress.php`, {
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
    if (!progressData || !progressData.tutorial_progress) return 0;
    
    const tutorials = progressData.tutorial_progress;
    if (tutorials.length === 0) return 0;
    
    const totalProgress = tutorials.reduce((sum, tutorial) => {
      return sum + (tutorial.completion_percentage || 0);
    }, 0);
    
    return Math.round(totalProgress / tutorials.length);
  };

  const getProgressStats = () => {
    if (!progressData) return { completed: 0, inProgress: 0, total: 0, practiceUploads: 0 };
    
    const tutorials = progressData.tutorial_progress || [];
    const completed = tutorials.filter(t => (t.completion_percentage || 0) >= 80).length;
    const inProgress = tutorials.filter(t => (t.completion_percentage || 0) > 0 && (t.completion_percentage || 0) < 80).length;
    const practiceUploads = tutorials.filter(t => t.practice_status === 'approved').length;
    
    return {
      completed,
      inProgress,
      total: tutorials.length,
      practiceUploads
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
  const canGenerateCertificate = overallProgress >= 100 && subscriptionPlan === 'pro';

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
                  : `Complete all tutorials to unlock your certificate. (${overallProgress}% complete)`
                }
              </p>
              <button 
                className={`certificate-button ${canGenerateCertificate ? 'enabled' : 'disabled'}`}
                disabled={!canGenerateCertificate}
                onClick={() => {
                  if (canGenerateCertificate) {
                    window.open(`${API_BASE}/pro/certificate.php?email=${userEmail}`, '_blank');
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
                      style={{ width: `${tutorial.completion_percentage || 0}%` }}
                    ></div>
                  </div>
                  <span className="progress-percentage">{tutorial.completion_percentage || 0}%</span>
                </div>
                {subscriptionPlan === 'pro' && tutorial.practice_status && (
                  <div className={`practice-status ${tutorial.practice_status}`}>
                    <LuUpload size={16} />
                    <span>{tutorial.practice_status}</span>
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