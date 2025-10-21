import React, { useState, useEffect } from 'react';
import { LuGift, LuRibbon, LuFileText, LuPackage, LuStar, LuCheckCircle, LuInfo, LuShoppingCart, LuHeart } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/auto-addon-display.css';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const AutoAddonDisplay = ({
  artworkId = null,
  price = null,
  category = null,
  occasion = null,
  onAddonSelect = null,
  showDetails = true,
  showPricing = true,
  showActions = true
}) => {
  const { auth } = useAuth();
  const [addons, setAddons] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [decisionPath, setDecisionPath] = useState([]);
  const [selectedAddons, setSelectedAddons] = useState([]);
  const [artwork, setArtwork] = useState(null);

  useEffect(() => {
    fetchAddonRecommendations();
    if (artworkId) {
      fetchArtworkDetails();
    }
  }, [artworkId, price, category, occasion]);

  const fetchArtworkDetails = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/artwork_details.php?id=${artworkId}`);
      const data = await response.json();
      
      if (data.status === 'success' && data.artwork) {
        setArtwork(data.artwork);
      }
    } catch (err) {
      console.error('Error fetching artwork details:', err);
    }
  };

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
        setAddons(data.addon_recommendations || []);
        setDecisionPath(data.decision_path || []);
        if (data.artwork) {
          setArtwork(data.artwork);
        }
      } else {
        setError(data.message || 'Failed to load add-on recommendations');
      }
    } catch (err) {
      setError('Network error loading recommendations');
      console.error('Auto Add-on Display error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAddonToggle = (addon) => {
    setSelectedAddons(prev => {
      const isSelected = prev.find(item => item.addon_type === addon.addon_type);
      if (isSelected) {
        return prev.filter(item => item.addon_type !== addon.addon_type);
      } else {
        return [...prev, addon];
      }
    });
  };

  const handleAddAllToCart = () => {
    if (onAddonSelect) {
      selectedAddons.forEach(addon => onAddonSelect(addon));
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
    if (confidence >= 0.8) return '#10b981';
    if (confidence >= 0.6) return '#f59e0b';
    if (confidence >= 0.4) return '#f97316';
    return '#ef4444';
  };

  const getConfidenceLabel = (confidence) => {
    if (confidence >= 0.8) return 'Highly Recommended';
    if (confidence >= 0.6) return 'Recommended';
    if (confidence >= 0.4) return 'Good Option';
    return 'Consider';
  };

  const getTotalPrice = () => {
    return selectedAddons.reduce((total, addon) => total + addon.price, 0);
  };

  if (loading) {
    return (
      <div className="auto-addon-container">
        <div className="addon-header">
          <h3 className="addon-title">
            <LuGift className="title-icon" />
            Recommended Add-ons
          </h3>
        </div>
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Analyzing your gift for add-on suggestions...</p>
        </div>
      </div>
    );
  }

  if (error || addons.length === 0) {
    return null; // Don't show anything if no addons or error
  }

  return (
    <div className="auto-addon-container">
      <div className="addon-header">
        <h3 className="addon-title">
          <LuGift className="title-icon" />
          Complete Your Gift
        </h3>
        <div className="price-info">
          <span className="price-label">Gift Price: ₹{price || 'N/A'}</span>
        </div>
      </div>

      {artwork && (
        <div className="artwork-info">
          <div className="artwork-image-container">
            <img 
              src={artwork.image_url} 
              alt={artwork.title} 
              className="artwork-thumbnail"
              onError={(e) => {
                e.target.src = '/images/placeholder-product.jpg';
                e.target.alt = 'Product image not available';
              }}
            />
            {artwork.has_offer && (
              <div className="offer-badge">
                {artwork.offer_percent ? `${artwork.offer_percent}% OFF` : 'OFFER'}
              </div>
            )}
          </div>
          <div className="artwork-details">
            <h4>{artwork.title}</h4>
            <p className="artwork-description">{artwork.description}</p>
            <div className="artwork-pricing">
              {artwork.effective_price && artwork.effective_price < artwork.price ? (
                <div className="price-container">
                  <span className="original-price">₹{artwork.price}</span>
                  <span className="offer-price">₹{artwork.effective_price}</span>
                </div>
              ) : (
                <span className="price">₹{artwork.price}</span>
              )}
              <span className="category">• {artwork.category_name}</span>
            </div>
          </div>
        </div>
      )}

      {showDetails && decisionPath.length > 0 && (
        <div className="decision-logic">
          <div className="logic-header">
            <LuInfo className="logic-icon" />
            <span className="logic-title">Why these add-ons?</span>
          </div>
          <div className="logic-steps">
            {decisionPath.map((step, index) => (
              <div key={index} className="logic-step">
                <div className="step-number">{index + 1}</div>
                <div className="step-text">{step}</div>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="addon-grid">
        {addons.map((addon, index) => {
          const isSelected = selectedAddons.find(item => item.addon_type === addon.addon_type);
          
          return (
            <div 
              key={index} 
              className={`addon-card ${isSelected ? 'selected' : ''}`}
              onClick={() => handleAddonToggle(addon)}
            >
              <div className="addon-card-header">
                <div className="addon-icon-container">
                  {getAddonIcon(addon.type)}
                </div>
                <div className="addon-badge">
                  <LuStar className="badge-icon" />
                  <span className="badge-text">{getConfidenceLabel(addon.confidence)}</span>
                </div>
              </div>

              <div className="addon-card-body">
                <h4 className="addon-name">{addon.name}</h4>
                <p className="addon-description">{addon.description}</p>
                
                {showPricing && (
                  <div className="addon-pricing">
                    <span className="addon-price">₹{addon.price}</span>
                    <span className="addon-type">{addon.type}</span>
                  </div>
                )}

                <div className="addon-reason">
                  <LuCheckCircle className="reason-icon" />
                  <span className="reason-text">{addon.reason}</span>
                </div>

                <div className="addon-metrics">
                  <div className="confidence-bar">
                    <div 
                      className="confidence-fill"
                      style={{ 
                        width: `${addon.confidence * 100}%`,
                        backgroundColor: getConfidenceColor(addon.confidence)
                      }}
                    ></div>
                  </div>
                  <span 
                    className="confidence-text"
                    style={{ color: getConfidenceColor(addon.confidence) }}
                  >
                    {Math.round(addon.confidence * 100)}% match
                  </span>
                </div>
              </div>

              <div className="addon-card-footer">
                <div className="selection-indicator">
                  {isSelected ? (
                    <LuCheckCircle className="check-icon selected" />
                  ) : (
                    <div className="check-circle"></div>
                  )}
                </div>
                <span className="selection-text">
                  {isSelected ? 'Selected' : 'Click to select'}
                </span>
              </div>
            </div>
          );
        })}
      </div>

      {selectedAddons.length > 0 && (
        <div className="addon-summary">
          <div className="summary-header">
            <h4>Selected Add-ons ({selectedAddons.length})</h4>
            <span className="total-price">Total: ₹{getTotalPrice()}</span>
          </div>
          
          <div className="selected-items">
            {selectedAddons.map((addon, index) => (
              <div key={index} className="selected-item">
                <span className="item-name">{addon.name}</span>
                <span className="item-price">₹{addon.price}</span>
              </div>
            ))}
          </div>

          {showActions && (
            <div className="addon-actions">
              <button 
                className="add-all-button"
                onClick={handleAddAllToCart}
              >
                <LuShoppingCart className="button-icon" />
                Add All to Cart
              </button>
              
              <button 
                className="wishlist-button"
                onClick={() => {
                  // Handle wishlist functionality
                  console.log('Add to wishlist:', selectedAddons);
                }}
              >
                <LuHeart className="button-icon" />
                Save for Later
              </button>
            </div>
          )}
        </div>
      )}

      <div className="addon-footer">
        <p className="footer-text">
          <LuInfo className="footer-icon" />
          Add-ons are automatically suggested based on your gift's price and category
        </p>
      </div>
    </div>
  );
};

export default AutoAddonDisplay;
