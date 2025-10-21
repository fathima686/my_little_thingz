import React, { useState, useEffect, useRef, useCallback } from 'react';
import { LuHeart, LuShoppingCart, LuGift, LuTrendingUp, LuHistory, LuSparkles } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/recommendations.css';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const PurchaseHistoryRecommendations = ({
  userId = null,
  title = "Based on Your Purchases",
  limit = 8,
  onCustomizationRequest = null,
  showAddToCart = true,
  showWishlist = true,
  showAnalysis = false
}) => {
  const { auth } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [analysis, setAnalysis] = useState(null);
  const trackRef = useRef(null);

  const scrollByAmount = 320;
  const scrollLeft = useCallback(() => {
    if (trackRef.current) {
      trackRef.current.scrollBy({ left: -scrollByAmount, behavior: 'smooth' });
    }
  }, []);
  const scrollRight = useCallback(() => {
    if (trackRef.current) {
      trackRef.current.scrollBy({ left: scrollByAmount, behavior: 'smooth' });
    }
  }, []);

  useEffect(() => {
    fetchRecommendations();
  }, [userId, auth?.user_id]);

  const fetchRecommendations = async () => {
    try {
      setLoading(true);
      setError(null);

      const targetUserId = userId || auth?.user_id;
      if (!targetUserId) {
        setError('User ID required for purchase history recommendations');
        setLoading(false);
        return;
      }

      const params = new URLSearchParams({
        user_id: targetUserId,
        limit: limit,
        analysis: showAnalysis
      });

      const response = await fetch(`${API_BASE}/customer/purchase_history_recommendations.php?${params}`);
      const data = await response.json();

      if (data.status === 'success') {
        setRecommendations(data.recommendations || []);
        if (data.analysis) {
          setAnalysis(data.analysis);
        }
      } else {
        setError(data.message || 'Failed to load purchase history recommendations');
      }
    } catch (err) {
      setError('Network error loading recommendations');
      console.error('Purchase History Recommendations error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAddToCart = async (artwork) => {
    if (!auth?.user_id) {
      alert('Please login to add items to cart');
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/customer/cart.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': auth.user_id
        },
        body: JSON.stringify({
          artwork_id: artwork.artwork_id,
          quantity: 1
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        alert('Added to cart!');
        // Track the behavior
        trackBehavior('add_to_cart', artwork.artwork_id);
        // Dispatch custom event for cart updates
        window.dispatchEvent(new CustomEvent('cart-updated'));
      } else {
        alert(data.message || 'Failed to add to cart');
      }
    } catch (err) {
      alert('Network error adding to cart');
    }
  };

  const handleAddToWishlist = async (artwork) => {
    if (!auth?.user_id) {
      alert('Please login to add items to wishlist');
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/customer/wishlist.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': auth.user_id
        },
        body: JSON.stringify({
          artwork_id: artwork.artwork_id
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        alert('Added to wishlist!');
        // Track the behavior
        trackBehavior('add_to_wishlist', artwork.artwork_id);
      } else {
        alert(data.message || 'Failed to add to wishlist');
      }
    } catch (err) {
      alert('Network error adding to wishlist');
    }
  };

  const handleCustomizationRequest = (artwork) => {
    if (onCustomizationRequest) {
      onCustomizationRequest(artwork);
    }
  };

  const trackBehavior = async (behaviorType, artworkId) => {
    if (!auth?.user_id) return;

    try {
      await fetch(`${API_BASE}/customer/track_behavior.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          user_id: auth.user_id,
          artwork_id: artworkId,
          behavior_type: behaviorType,
          additional_data: {
            session_id: sessionStorage.getItem('session_id') || null,
            timestamp: new Date().toISOString()
          }
        })
      });
    } catch (err) {
      console.error('Failed to track behavior:', err);
    }
  };

  const handleItemClick = (artwork) => {
    // Track view behavior
    trackBehavior('view', artwork.artwork_id);
  };

  const getScoreColor = (score) => {
    if (score >= 0.8) return '#10b981'; // Green
    if (score >= 0.6) return '#f59e0b'; // Yellow
    if (score >= 0.4) return '#f97316'; // Orange
    return '#ef4444'; // Red
  };

  const getScoreLabel = (score) => {
    if (score >= 0.8) return 'Perfect Match';
    if (score >= 0.6) return 'Great Match';
    if (score >= 0.4) return 'Good Match';
    return 'Fair Match';
  };

  const formatPrice = (price, offerPrice = null) => {
    const effectivePrice = offerPrice || price;
    if (offerPrice && offerPrice < price) {
      return (
        <div className="price-container">
          <span className="original-price">₹{price}</span>
          <span className="offer-price">₹{effectivePrice}</span>
        </div>
      );
    }
    return <span className="price">₹{effectivePrice}</span>;
  };

  if (loading) {
    return (
      <div className="recommendations-container">
        <div className="recommendations-header">
          <h2 className="recommendations-title">
            <LuHistory className="title-icon" />
            {title}
          </h2>
        </div>
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Analyzing your purchase history...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="recommendations-container">
        <div className="recommendations-header">
          <h2 className="recommendations-title">
            <LuHistory className="title-icon" />
            {title}
          </h2>
        </div>
        <div className="error-container">
          <p>{error}</p>
          <button onClick={fetchRecommendations} className="retry-button">
            Try Again
          </button>
        </div>
      </div>
    );
  }

  if (recommendations.length === 0) {
    return (
      <div className="recommendations-container">
        <div className="recommendations-header">
          <h2 className="recommendations-title">
            <LuHistory className="title-icon" />
            {title}
          </h2>
        </div>
        <div className="empty-container">
          <LuGift className="empty-icon" />
          <p>No purchase history found. Start shopping to get personalized recommendations!</p>
        </div>
      </div>
    );
  }

  return (
    <div className="recommendations-container">
      <div className="recommendations-header">
        <h2 className="recommendations-title">
          <LuHistory className="title-icon" />
          {title}
        </h2>
        {analysis && (
          <div className="analysis-info">
            <LuTrendingUp className="info-icon" />
            <span>{analysis.total_purchases} purchases • {analysis.categories_purchased.length} categories</span>
          </div>
        )}
      </div>

      {analysis && showAnalysis && (
        <div className="purchase-analysis">
          <div className="analysis-item">
            <span className="analysis-label">Categories Purchased:</span>
            <span className="analysis-value">{analysis.categories_purchased.join(', ')}</span>
          </div>
          {analysis.occasions_detected.length > 0 && (
            <div className="analysis-item">
              <span className="analysis-label">Occasions Detected:</span>
              <span className="analysis-value">{analysis.occasions_detected.join(', ')}</span>
            </div>
          )}
          <div className="analysis-item">
            <span className="analysis-label">Price Range:</span>
            <span className="analysis-value">{analysis.price_range}</span>
          </div>
          <div className="analysis-item">
            <span className="analysis-label">Most Active Season:</span>
            <span className="analysis-value">{analysis.most_active_season}</span>
          </div>
        </div>
      )}

      <div className="recommendations-track" ref={trackRef}>
        <button className="scroll-button scroll-left" onClick={scrollLeft}>
          ‹
        </button>
        
        <div className="recommendations-list">
          {recommendations.map((artwork) => (
            <div key={artwork.artwork_id} className="recommendation-item" onClick={() => handleItemClick(artwork)}>
              <div className="artwork-image-container">
                <img 
                  src={artwork.image_url} 
                  alt={artwork.title}
                  className="artwork-image"
                  loading="lazy"
                />
                {artwork.has_offer && (
                  <div className="offer-badge">
                    {artwork.offer_percent ? `${artwork.offer_percent}% OFF` : 'OFFER'}
                  </div>
                )}
                <div 
                  className="match-badge"
                  style={{ backgroundColor: getScoreColor(artwork.score) }}
                >
                  <LuSparkles className="match-icon" />
                  {getScoreLabel(artwork.score)}
                </div>
              </div>
              
              <div className="artwork-info">
                <h3 className="artwork-title">{artwork.title}</h3>
                <p className="artwork-category">{artwork.category_name}</p>
                <div className="price-section">
                  {formatPrice(artwork.price, artwork.effective_price)}
                </div>
                
                {artwork.reason && (
                  <div className="recommendation-reason">
                    <LuGift className="reason-icon" />
                    <span className="reason-text">{artwork.reason}</span>
                  </div>
                )}
                
                <div className="match-indicator">
                  <div className="match-bar">
                    <div 
                      className="match-fill"
                      style={{ 
                        width: `${artwork.score * 100}%`,
                        backgroundColor: getScoreColor(artwork.score)
                      }}
                    ></div>
                  </div>
                  <span className="match-text">
                    {Math.round(artwork.score * 100)}% match
                  </span>
                </div>
              </div>

              <div className="artwork-actions">
                {showAddToCart && (
                  <button 
                    className="action-button add-to-cart"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleAddToCart(artwork);
                    }}
                  >
                    <LuShoppingCart className="action-icon" />
                    Add to Cart
                  </button>
                )}
                
                {showWishlist && (
                  <button 
                    className="action-button add-to-wishlist"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleAddToWishlist(artwork);
                    }}
                  >
                    <LuHeart className="action-icon" />
                    Wishlist
                  </button>
                )}

                {onCustomizationRequest && (
                  <button 
                    className="action-button customize"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleCustomizationRequest(artwork);
                    }}
                  >
                    <LuGift className="action-icon" />
                    Customize
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
        
        <button className="scroll-button scroll-right" onClick={scrollRight}>
          ›
        </button>
      </div>
    </div>
  );
};

export default PurchaseHistoryRecommendations;

