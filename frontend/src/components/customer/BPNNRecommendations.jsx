import React, { useState, useEffect } from 'react';
import { LuHeart, LuShoppingCart, LuWand, LuBrain, LuTrendingUp } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/recommendations.css';

const PYTHON_ML_API = "http://localhost:5001/api/ml";

const BPNNRecommendations = ({
  userId = null,
  title = "AI-Powered Recommendations",
  limit = 8,
  onCustomizationRequest = null,
  showAddToCart = true,
  showWishlist = true,
  showConfidence = true
}) => {
  const { auth } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [modelInfo, setModelInfo] = useState(null);
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
        setError('User ID required for AI recommendations');
        setLoading(false);
        return;
      }

      // Use Python BPNN API
      const requestData = {
        user_data: {
          age: 25,
          purchase_frequency: 0.5,
          avg_order_value: 800,
          preferred_categories: 3,
          session_duration: 1200,
          page_views: 15,
          time_on_site: 1800,
          device_type: 1,
          location_score: 0.7
        },
        product_data: {
          price: 500,
          category_id: 2,
          rating: 4.5,
          popularity: 0.6,
          stock_level: 0.8,
          discount_percentage: 0.1
        }
      };

      const response = await fetch(`${PYTHON_ML_API}/bpnn/predict-preference`, {
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
          id: i + 1,
          title: `AI Recommended Product ${i + 1}`,
          description: `Neural network prediction with ${Math.round(data.confidence * 100)}% confidence`,
          price: Math.floor(Math.random() * 1000) + 100,
          image_url: '/images/placeholder.jpg',
          category_id: 1,
          category_name: 'AI Recommended',
          availability: 'in_stock',
          created_at: new Date().toISOString(),
          preference_score: data.preference_score,
          confidence: data.confidence,
          algorithm: 'BPNN'
        }));
        
        setRecommendations(mockRecommendations);
        setModelInfo({
          model_version: 'Python BPNN v1.0',
          generated_at: new Date().toISOString(),
          count: mockRecommendations.length,
          preference_score: data.preference_score,
          recommendation: data.recommendation
        });
      } else {
        setError(data.error || 'Failed to load AI recommendations');
      }
    } catch (err) {
      setError('Network error loading AI recommendations');
      console.error('BPNN Recommendations error:', err);
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
        </h2>
        {modelInfo && (
          <div className="model-info">
            <LuTrendingUp className="info-icon" />
            <span>{modelInfo.count} AI recommendations</span>
          </div>
        )}
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
                    {artwork.offer_percent ? `${artwork.offer_percent}% OFF` : 'OFFER'}
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
    </div>
  );
};

export default BPNNRecommendations;

















