import React, { useState, useEffect, useRef, useCallback } from 'react';
import { LuHeart, LuShoppingCart, LuWand, LuBrain, LuTrendingUp, LuRefreshCw } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/recommendations.css';

const PYTHON_ML_API = "http://localhost:5001/api/ml";

const DynamicSVMRecommendations = ({
  userId = null,
  title = "AI-Powered Dynamic Recommendations",
  limit = 8,
  onCustomizationRequest = null,
  showAddToCart = true,
  showWishlist = true,
  showConfidence = true,
  autoRefresh = true,
  refreshInterval = 300000 // 5 minutes
}) => {
  const { auth } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [modelInfo, setModelInfo] = useState(null);
  const [isNewUser, setIsNewUser] = useState(false);
  const [lastUpdate, setLastUpdate] = useState(null);
  const trackRef = useRef(null);
  const refreshIntervalRef = useRef(null);

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
    
    // Set up auto-refresh
    if (autoRefresh) {
      refreshIntervalRef.current = setInterval(() => {
        fetchRecommendations(true); // Silent refresh
      }, refreshInterval);
    }
    
    return () => {
      if (refreshIntervalRef.current) {
        clearInterval(refreshIntervalRef.current);
      }
    };
  }, [userId, auth?.user_id, autoRefresh, refreshInterval]);

  const fetchRecommendations = async (silent = false) => {
    try {
      if (!silent) setLoading(true);
      setError(null);

      const targetUserId = userId || auth?.user_id;
      if (!targetUserId) {
        setError('User ID required for AI recommendations');
        setLoading(false);
        return;
      }

      // Use Python SVM API
      const requestData = {
        gift_data: {
          price: Math.floor(Math.random() * 2000) + 100,
          category_id: Math.floor(Math.random() * 5) + 1,
          title: "Sample Gift",
          description: "AI classified gift",
          availability: "in_stock",
          rating: 4.5,
          popularity: 0.8
        }
      };

      const response = await fetch(`${PYTHON_ML_API}/svm/classify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
      });

      const data = await response.json();

      if (data.success) {
        // Convert Python response to expected format
        const mockRecommendations = Array.from({ length: limit }, (_, i) => ({
          artwork_id: i + 1,
          title: `${data.prediction} Gift ${i + 1}`,
          description: `SVM classified as ${data.prediction} with ${Math.round(data.confidence * 100)}% confidence`,
          price: Math.floor(Math.random() * 2000) + 100,
          image_url: '/images/placeholder.jpg',
          category_id: 1,
          category_name: data.prediction,
          availability: 'in_stock',
          created_at: new Date().toISOString(),
          prediction: data.prediction,
          confidence: data.confidence,
          score: data.score,
          reasoning: data.reasoning,
          algorithm: 'SVM'
        }));
        
        setRecommendations(mockRecommendations);
        setIsNewUser(false);
        setModelInfo({
          model_version: 'Python SVM v1.0',
          generated_at: new Date().toISOString(),
          count: mockRecommendations.length,
          prediction: data.prediction,
          confidence: data.confidence
        });
        setLastUpdate(new Date().toISOString());
      } else {
        setError(data.error || 'Failed to load AI recommendations');
      }
    } catch (err) {
      setError('Network error loading AI recommendations');
      console.error('Dynamic SVM Recommendations error:', err);
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
      await fetch(`${API_BASE}/customer/dynamic_svm_recommendations.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'update_behavior',
          user_id: auth.user_id,
          product_id: artworkId,
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

  const handleRefresh = () => {
    fetchRecommendations();
  };

  const getConfidenceColor = (confidence) => {
    if (confidence >= 0.8) return '#10b981'; // Green
    if (confidence >= 0.6) return '#f59e0b'; // Yellow
    if (confidence >= 0.4) return '#f97316'; // Orange
    return '#ef4444'; // Red
  };

  const getConfidenceLabel = (confidence) => {
    if (confidence >= 0.8) return 'Very High';
    if (confidence >= 0.6) return 'High';
    if (confidence >= 0.4) return 'Medium';
    return 'Low';
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

  const getRecommendationTypeLabel = (type) => {
    switch (type) {
      case 'new_user_popular':
        return 'Popular Choice';
      case 'personalized':
        return 'AI Recommended';
      default:
        return 'Recommended';
    }
  };

  if (loading) {
    return (
      <div className="recommendations-container">
        <div className="recommendations-header">
          <h2 className="recommendations-title">
            <LuBrain className="title-icon" />
            {title}
          </h2>
        </div>
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>AI is analyzing your preferences...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="recommendations-container">
        <div className="recommendations-header">
          <h2 className="recommendations-title">
            <LuBrain className="title-icon" />
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
            <LuBrain className="title-icon" />
            {title}
          </h2>
        </div>
        <div className="empty-container">
          <LuWand className="empty-icon" />
          <p>No AI recommendations available yet. Start browsing to get personalized suggestions!</p>
        </div>
      </div>
    );
  }

  return (
    <div className="recommendations-container">
      <div className="recommendations-header">
        <h2 className="recommendations-title">
          <LuBrain className="title-icon" />
          {title}
          {isNewUser && <span className="new-user-badge">New User</span>}
        </h2>
        <div className="recommendations-controls">
          {modelInfo && (
            <div className="model-info">
              <LuTrendingUp className="info-icon" />
              <span>v{modelInfo.model_version} • {modelInfo.count} recommendations</span>
            </div>
          )}
          <button 
            onClick={handleRefresh} 
            className="refresh-button"
            title="Refresh recommendations"
          >
            <LuRefreshCw className="refresh-icon" />
          </button>
        </div>
      </div>

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
                    {artwork.discount_percentage ? `${artwork.discount_percentage}% OFF` : 'OFFER'}
                  </div>
                )}
                {showConfidence && (
                  <div 
                    className="confidence-badge"
                    style={{ backgroundColor: getConfidenceColor(artwork.confidence) }}
                  >
                    {getConfidenceLabel(artwork.confidence)}
                  </div>
                )}
                <div className="recommendation-type-badge">
                  {getRecommendationTypeLabel(artwork.recommendation_type)}
                </div>
              </div>
              
              <div className="artwork-info">
                <h3 className="artwork-title">{artwork.title}</h3>
                <p className="artwork-category">{artwork.category_name}</p>
                <div className="price-section">
                  {formatPrice(artwork.price, artwork.effective_price)}
                </div>
                
                {showConfidence && (
                  <div className="confidence-indicator">
                    <div className="confidence-bar">
                      <div 
                        className="confidence-fill"
                        style={{ 
                          width: `${artwork.confidence * 100}%`,
                          backgroundColor: getConfidenceColor(artwork.confidence)
                        }}
                      ></div>
                    </div>
                    <span className="confidence-text">
                      {Math.round(artwork.confidence * 100)}% match
                    </span>
                  </div>
                )}
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
                    <LuWand className="action-icon" />
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
      
      {lastUpdate && (
        <div className="last-update">
          Last updated: {new Date(lastUpdate).toLocaleTimeString()}
        </div>
      )}
    </div>
  );
};

export default DynamicSVMRecommendations;

