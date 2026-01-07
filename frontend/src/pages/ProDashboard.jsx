import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { 
  LuArrowLeft, LuTrendingUp, LuCheck, LuClock, LuX, LuDownload, 
  LuLock, LuAward, LuBookOpen, LuUpload, LuEye 
} from 'react-icons/lu';
import '../styles/pro-dashboard.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function ProDashboard() {
  const { tutorialAuth } = useTutorialAuth();
  const [loading, setLoading] = useState(true);
  const [progressData, setProgressData] = useState(null);
  const [certificateData, setCertificateData] = useState(null);
  const [generatingCertificate, setGeneratingCertificate] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (tutorialAuth?.email) {
      fetchProgressData();
      fetchCertificateData();
    }
  }, [tutorialAuth?.email]);

  const fetchProgressData = async () => {
    try {
      const res = await fetch(`${API_BASE}/pro/learning-progress.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setProgressData(data);
      } else {
        setError(data.message || 'Failed to load progress data');
      }
    } catch (error) {
      console.error('Error fetching progress:', error);
      setError('Failed to load progress data');
    } finally {
      setLoading(false);
    }
  };

  const fetchCertificateData = async () => {
    try {
      const res = await fetch(`${API_BASE}/pro/certificate.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setCertificateData(data);
      }
    } catch (error) {
      console.error('Error fetching certificate data:', error);
    }
  };

  const generateCertificate = async () => {
    setGeneratingCertificate(true);
    
    try {
      const res = await fetch(`${API_BASE}/pro/certificate.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        alert('Certificate generated successfully!');
        fetchCertificateData(); // Refresh certificate data
      } else {
        alert(data.message || 'Failed to generate certificate');
      }
    } catch (error) {
      console.error('Error generating certificate:', error);
      alert('Failed to generate certificate');
    } finally {
      setGeneratingCertificate(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'approved':
        return <LuCheck className="status-approved" />;
      case 'rejected':
        return <LuX className="status-rejected" />;
      case 'pending':
      default:
        return <LuClock className="status-pending" />;
    }
  };

  if (loading) {
    return (
      <div className="pro-dashboard-container">
        <div className="loading">Loading your progress...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="pro-dashboard-container">
        <div className="error-message">
          <p>{error}</p>
          <Link to="/tutorials" className="btn btn-primary">Back to Tutorials</Link>
        </div>
      </div>
    );
  }

  const { overall_progress, tutorial_progress, certificate_eligible } = progressData || {};

  return (
    <div className="pro-dashboard-container">
      <header className="dashboard-header">
        <Link to="/tutorials" className="back-button">
          <LuArrowLeft size={20} />
          Back to Tutorials
        </Link>
        <div className="header-content">
          <h1>My Learning Progress</h1>
          <p>Track your craft learning journey and earn certificates</p>
        </div>
      </header>

      {/* Progress Overview */}
      <div className="progress-overview">
        <div className="progress-card main">
          <div className="progress-header">
            <LuTrendingUp size={24} />
            <h2>Overall Progress</h2>
          </div>
          <div className="progress-stats">
            <div className="stat-item">
              <div className="stat-number">{overall_progress?.completion_percentage || 0}%</div>
              <div className="stat-label">Completed</div>
            </div>
            <div className="stat-item">
              <div className="stat-number">{overall_progress?.completed_tutorials || 0}</div>
              <div className="stat-label">Tutorials Done</div>
            </div>
            <div className="stat-item">
              <div className="stat-number">{overall_progress?.total_tutorials || 0}</div>
              <div className="stat-label">Total Tutorials</div>
            </div>
          </div>
          <div className="progress-bar-container">
            <div className="progress-bar">
              <div 
                className="progress-fill" 
                style={{ width: `${overall_progress?.completion_percentage || 0}%` }}
              ></div>
            </div>
            <span className="progress-text">
              {overall_progress?.completed_tutorials || 0} of {overall_progress?.total_tutorials || 0} tutorials completed
            </span>
          </div>
        </div>

        {/* Certificate Section */}
        <div className="certificate-card">
          <div className="certificate-header">
            <LuAward size={24} />
            <h3>Certificate of Completion</h3>
          </div>
          
          {certificate_eligible ? (
            <div className="certificate-eligible">
              <p className="eligible-text">🎉 Congratulations! You're eligible for a certificate.</p>
              
              {certificateData?.certificate_exists ? (
                <div className="existing-certificate">
                  <p>Certificate Code: <strong>{certificateData.certificate.certificate_code}</strong></p>
                  <a 
                    href={`${API_BASE.replace('/api', '')}/${certificateData.certificate.certificate_path}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn certificate-btn"
                  >
                    <LuDownload size={18} />
                    Download Certificate
                  </a>
                </div>
              ) : (
                <button 
                  className="btn certificate-btn"
                  onClick={generateCertificate}
                  disabled={generatingCertificate}
                >
                  <LuAward size={18} />
                  {generatingCertificate ? 'Generating...' : 'Generate Certificate'}
                </button>
              )}
            </div>
          ) : (
            <div className="certificate-locked">
              <LuLock size={32} className="lock-icon" />
              <p>Complete 80% of the course to unlock your certificate</p>
              <p className="progress-needed">
                Current: {overall_progress?.completion_percentage || 0}% | Required: 80%
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Tutorial Progress List */}
      <div className="tutorial-progress-section">
        <h2>Tutorial Progress</h2>
        
        {tutorial_progress && tutorial_progress.length > 0 ? (
          <div className="tutorial-progress-list">
            {tutorial_progress.map((tutorial) => (
              <div key={tutorial.tutorial_id} className="tutorial-progress-item">
                <div className="tutorial-info">
                  <h4>{tutorial.title}</h4>
                  <div className="tutorial-meta">
                    <span className="category">{tutorial.category}</span>
                    <span className="duration">{tutorial.duration} min</span>
                  </div>
                </div>
                
                <div className="tutorial-status">
                  <div className="completion-info">
                    <div className="completion-percentage">
                      {Math.round(tutorial.completion_percentage)}%
                    </div>
                    <div className="completion-bar">
                      <div 
                        className="completion-fill"
                        style={{ width: `${tutorial.completion_percentage}%` }}
                      ></div>
                    </div>
                  </div>
                  
                  {tutorial.practice_status && (
                    <div className="practice-status">
                      {getStatusIcon(tutorial.practice_status)}
                      <span className={`status-text status-${tutorial.practice_status}`}>
                        Practice {tutorial.practice_status}
                      </span>
                    </div>
                  )}
                </div>
                
                <div className="tutorial-actions">
                  <Link 
                    to={`/tutorial/${tutorial.tutorial_id}`}
                    className="btn btn-small"
                  >
                    <LuEye size={16} />
                    View
                  </Link>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="no-progress">
            <LuBookOpen size={48} />
            <p>Start watching tutorials to track your progress!</p>
            <Link to="/tutorials" className="btn btn-primary">
              Browse Tutorials
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}