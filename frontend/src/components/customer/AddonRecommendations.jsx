import React, { useState, useEffect } from 'react';
import { LuGift, LuRibbon, LuFileText, LuPackage, LuStar, LuCheckCircle, LuInfo } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/addon-recommendations.css';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const AddonRecommendations = ({
  artworkId = null,
  price = null,
  category = null,
  occasion = null,
  title = "Recommended Add-ons",
  onAddonSelect = null,
  showDecisionPath = true,
  showConfidence = true
}) => {
  const { auth } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [decisionPath, setDecisionPath] = useState([]);
  const [overallConfidence, setOverallConfidence] = useState(0);
  const [artwork, setArtwork] = useState(null);

  useEffect(() => {
    fetchAddonRecommendations();
  }, [artworkId, price, category, occasion]);

  const fetchAddonRecommendations = async () => {
    try {
      setLoading(true);
      setError(null);

      const params = new URLSearchParams();
      
      if (artworkId) {
        params.append('artwork_id', artworkId);
      }
      if (price) {
        params.append('price', price);
      }
      if (category) {
        params.append('category', category);
      }
      if (occasion) {
        params.append('occasion', occasion);
      }
      if (auth?.user_id) {
        params.append('user_id', auth.user_id);
      }

      const response = await fetch(`${API_BASE}/customer/addon_recommendations.php?${params}`);
      const data = await response.json();

      if (data.status === 'success') {
        setRecommendations(data.addon_recommendations || []);
        setDecisionPath(data.decision_path || []);
        setOverallConfidence(data.overall_confidence || 0);
        if (data.artwork) {
          setArtwork(data.artwork);
        }
      } else {
        setError(data.message || 'Failed to load add-on recommendations');
      }
    } catch (err) {
      setError('Network error loading recommendations');
      console.error('Add-on Recommendations error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAddonSelect = (addon) => {
    if (onAddonSelect) {
      onAddonSelect(addon);
    }
  };

  const getAddonIcon = (type) => {
    switch (type) {
      case 'card':
        return <LuFileText className="addon-icon" />;
      case 'ribbon':
        return <LuRibbon className="addon-icon" />;
      case 'packaging':
        return <LuPackage className="addon-icon" />;
      default:
        return <LuGift className="addon-icon" />;
    }
  };

  const getConfidenceColor = (confidence) => {
    if (confidence >= 0.8) return '#10b981'; // Green
    if (confidence >= 0.6) return '#f59e0b'; // Yellow
    if (confidence >= 0.4) return '#f97316'; // Orange
    return '#ef4444'; // Red
  };

  const getConfidenceLabel = (confidence) => {
    if (confidence >= 0.8) return 'High Confidence';
    if (confidence >= 0.6) return 'Medium Confidence';
    if (confidence >= 0.4) return 'Low Confidence';
    return 'Very Low Confidence';
  };

  const getPriorityColor = (priority) => {
    if (priority >= 0.8) return '#dc2626'; // Red - High priority
    if (priority >= 0.6) return '#ea580c'; // Orange - Medium-high priority
    if (priority >= 0.4) return '#d97706'; // Amber - Medium priority
    return '#65a30d'; // Green - Low priority
  };

  const getPriorityLabel = (priority) => {
    if (priority >= 0.8) return 'High Priority';
    if (priority >= 0.6) return 'Medium-High Priority';
    if (priority >= 0.4) return 'Medium Priority';
    return 'Low Priority';
  };

  if (loading) {
    return (
      <div className="addon-recommendations-container">
        <div className="addon-header">
          <h3 className="addon-title">
            <LuGift className="title-icon" />
            {title}
          </h3>
        </div>
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Analyzing gift for add-on suggestions...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="addon-recommendations-container">
        <div className="addon-header">
          <h3 className="addon-title">
            <LuGift className="title-icon" />
            {title}
          </h3>
        </div>
        <div className="error-container">
          <p>{error}</p>
          <button onClick={fetchAddonRecommendations} className="retry-button">
            Try Again
          </button>
        </div>
      </div>
    );
  }

  if (recommendations.length === 0) {
    return (
      <div className="addon-recommendations-container">
        <div className="addon-header">
          <h3 className="addon-title">
            <LuGift className="title-icon" />
            {title}
          </h3>
        </div>
        <div className="empty-container">
          <LuGift className="empty-icon" />
          <p>No add-on recommendations available for this gift.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="addon-recommendations-container">
      <div className="addon-header">
        <h3 className="addon-title">
          <LuGift className="title-icon" />
          {title}
        </h3>
        {showConfidence && (
          <div className="overall-confidence">
            <LuStar className="confidence-icon" />
            <span 
              className="confidence-value"
              style={{ color: getConfidenceColor(overallConfidence) }}
            >
              {Math.round(overallConfidence * 100)}% Confidence
            </span>
          </div>
        )}
      </div>

      {artwork && (
        <div className="artwork-info">
          <img src={artwork.image_url} alt={artwork.title} className="artwork-thumbnail" />
          <div className="artwork-details">
            <h4>{artwork.title}</h4>
            <p>₹{artwork.price} • {artwork.category_name}</p>
          </div>
        </div>
      )}

      {showDecisionPath && decisionPath.length > 0 && (
        <div className="decision-path">
          <h4 className="decision-path-title">
            <LuInfo className="path-icon" />
            Decision Logic
          </h4>
          <div className="path-steps">
            {decisionPath.map((step, index) => (
              <div key={index} className="path-step">
                <div className="step-number">{index + 1}</div>
                <div className="step-content">{step}</div>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="addon-list">
        {recommendations.map((addon, index) => (
          <div key={index} className="addon-item">
            <div className="addon-main">
              <div className="addon-icon-container">
                {getAddonIcon(addon.type)}
              </div>
              
              <div className="addon-details">
                <h4 className="addon-name">{addon.name}</h4>
                <p className="addon-description">{addon.description}</p>
                <div className="addon-price">₹{addon.price}</div>
                
                <div className="addon-reason">
                  <LuCheckCircle className="reason-icon" />
                  <span className="reason-text">{addon.reason}</span>
                </div>
              </div>

              <div className="addon-metrics">
                <div className="confidence-metric">
                  <div 
                    className="confidence-bar"
                    style={{ 
                      width: `${addon.confidence * 100}%`,
                      backgroundColor: getConfidenceColor(addon.confidence)
                    }}
                  ></div>
                  <span 
                    className="confidence-label"
                    style={{ color: getConfidenceColor(addon.confidence) }}
                  >
                    {getConfidenceLabel(addon.confidence)}
                  </span>
                </div>

                <div className="priority-metric">
                  <div 
                    className="priority-indicator"
                    style={{ backgroundColor: getPriorityColor(addon.priority) }}
                  ></div>
                  <span 
                    className="priority-label"
                    style={{ color: getPriorityColor(addon.priority) }}
                  >
                    {getPriorityLabel(addon.priority)}
                  </span>
                </div>
              </div>
            </div>

            <div className="addon-actions">
              <button 
                className="addon-button primary"
                onClick={() => handleAddonSelect(addon)}
              >
                <LuGift className="button-icon" />
                Add to Gift
              </button>
              
              <div className="addon-category">
                <span className="category-badge">{addon.category}</span>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="addon-footer">
        <p className="footer-text">
          <LuInfo className="footer-icon" />
          Add-ons are selected based on your gift's price, category, and occasion
        </p>
      </div>
    </div>
  );
};

export default AddonRecommendations;
















