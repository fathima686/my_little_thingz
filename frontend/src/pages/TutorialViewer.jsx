import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { LuArrowLeft, LuDownload, LuShare2 } from 'react-icons/lu';
import '../styles/tutorial-viewer.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';
const ROOT_BASE = API_BASE.replace('/backend/api', '');

export default function TutorialViewer() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { tutorialAuth } = useTutorialAuth();
  const [tutorial, setTutorial] = useState(null);
  const [loading, setLoading] = useState(true);
  const [hasAccess, setHasAccess] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchTutorial();
    checkAccess();
  }, [id, tutorialAuth?.email]);

  const fetchTutorial = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/tutorial-detail.php?id=${id}`);
      const data = await res.json();
      
      if (data.status === 'success') {
        setTutorial(data.tutorial);
      } else {
        setError(data.message || 'Failed to load tutorial');
      }
    } catch (error) {
      console.error('Error fetching tutorial:', error);
      setError('Failed to load tutorial');
    } finally {
      setLoading(false);
    }
  };

  const checkAccess = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/check-tutorial-access.php?tutorial_id=${id}&email=${tutorialAuth?.email}`, {
        headers: {
          'X-Tutorials-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      if (data.status === 'success' && data.has_access) {
        setHasAccess(true);
      }
    } catch (error) {
      console.error('Error checking access:', error);
    }
  };

  if (loading) {
    return (
      <div className="tutorial-viewer-container">
        <div className="loading">Loading tutorial...</div>
      </div>
    );
  }

  if (error || !tutorial) {
    return (
      <div className="tutorial-viewer-container">
        <div className="error-message">
          <p>{error || 'Tutorial not found'}</p>
          <Link to="/tutorials" className="btn btn-primary">Back to Tutorials</Link>
        </div>
      </div>
    );
  }

  if (!hasAccess && !tutorial.is_free && tutorial.price > 0) {
    return (
      <div className="tutorial-viewer-container">
        <div className="error-message">
          <p>You don't have access to this tutorial. Please purchase it first.</p>
          <Link to="/tutorials" className="btn btn-primary">Back to Tutorials</Link>
        </div>
      </div>
    );
  }

  const resolveUrl = (url) => {
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url; // already absolute

    const cleaned = url.replace(/^\/+/, '');

    // If stored path starts with uploads/, serve from backend/uploads/...
    if (cleaned.startsWith('uploads/')) {
      return `${ROOT_BASE}/backend/${cleaned}`;
    }

    // If it already includes backend/ prefix, just attach to root.
    if (cleaned.startsWith('backend/')) {
      return `${ROOT_BASE}/${cleaned}`;
    }

    // Fallback: assume relative to backend/
    return `${ROOT_BASE}/backend/${cleaned}`;
  };

  const resourceUrl =
    resolveUrl(tutorial.resource_url || tutorial.resources_url || tutorial.download_url || tutorial.video_url);

  return (
    <div className="tutorial-viewer-container">
      <header className="viewer-header">
        <Link to="/tutorials" className="back-button">
          <LuArrowLeft size={20} />
          Back to Tutorials
        </Link>
        <h1>{tutorial.title}</h1>
        <div className="viewer-actions">
          <button className="action-btn" title="Share">
            <LuShare2 size={20} />
          </button>
        </div>
      </header>

      <div className="viewer-content">
        <div className="video-container">
          {tutorial.video_url ? (
            <iframe
              width="100%"
              height="600"
              src={resolveUrl(tutorial.video_url)}
              title={tutorial.title}
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
            />
          ) : (
            <div className="video-placeholder">
              <p>Video not available</p>
            </div>
          )}
        </div>

        <div className="viewer-sidebar">
          <div className="tutorial-info">
            <h2>{tutorial.title}</h2>
            
            <div className="info-grid">
              <div className="info-item">
                <span className="label">Duration</span>
                <span className="value">{tutorial.duration || 'N/A'} minutes</span>
              </div>
              <div className="info-item">
                <span className="label">Level</span>
                <span className="value">{tutorial.difficulty_level || 'Beginner'}</span>
              </div>
              <div className="info-item">
                <span className="label">Category</span>
                <span className="value">{tutorial.category && tutorial.category !== '0' ? tutorial.category : 'General'}</span>
              </div>
            </div>

            <div className="description">
              <h3>About This Tutorial</h3>
              <p>{tutorial.description}</p>
            </div>

            <div className="tutorial-tools">
              {resourceUrl ? (
                <a className="tool-btn" href={resourceUrl} download target="_blank" rel="noopener noreferrer">
                  <LuDownload size={18} />
                  Download Resources
                </a>
              ) : (
                <button className="tool-btn" disabled title="No resources available">
                  <LuDownload size={18} />
                  Download Resources
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
