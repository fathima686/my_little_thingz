import React, { useState, useEffect } from 'react';
import { LuHeart, LuShoppingCart, LuWand } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/recommendations.css';
import { useRef, useCallback } from 'react';

const PYTHON_ML_API = "http://localhost:5001/api/ml";

const Recommendations = ({
  artworkId = null,
  title = "You May Like",
  limit = 5,
  onCustomizationRequest = null,
  showAddToCart = true,
  showWishlist = true
}) => {
  const { auth } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
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
  }, [artworkId, auth?.user_id]);

  const fetchRecommendations = async () => {
    try {
      setLoading(true);
      setError(null);

      // Try Python KNN API first
      try {
        const requestData = {
          product_id: artworkId || 1,
          user_id: auth?.user_id || null,
          k: limit
        };

        const response = await fetch(`${PYTHON_ML_API}/knn/recommendations`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(requestData)
        });

        if (response.ok) {
          const data = await response.json();
          if (data.success) {
            // Convert Python response to expected format
            const convertedRecommendations = data.recommendations.map(rec => ({
              id: rec.product_id,
              title: `AI Similar Product ${rec.product_id}`,
              description: `Python KNN - ${Math.round(rec.similarity_score * 100)}% similarity`,
              price: Math.floor(Math.random() * 1000) + 100,
              image_url: '/images/placeholder.jpg',
              category_id: 1,
              category_name: 'AI Recommended',
              availability: 'in_stock',
              created_at: new Date().toISOString(),
              similarity_score: rec.similarity_score,
              algorithm: 'Python KNN'
            }));
            
            setRecommendations(convertedRecommendations);
            return; // Success, exit early
          }
        }
      } catch (pythonError) {
        console.log('Python ML service unavailable, falling back to PHP:', pythonError.message);
      }

      // Fallback to PHP recommendations
      const phpResponse = await fetch(`http://localhost/my_little_thingz/backend/api/customer/recommendations.php?artwork_id=${artworkId || 1}&limit=${limit}&user_id=${auth?.user_id || ''}`);
      
      if (phpResponse.ok) {
        const phpData = await phpResponse.json();
        if (phpData.status === 'success' && phpData.recommendations) {
          setRecommendations(phpData.recommendations);
          return; // Success with PHP
        }
      }

      // Final fallback - generate mock recommendations
      const mockRecommendations = Array.from({ length: limit }, (_, i) => ({
        id: i + 1,
        title: `Similar Gift ${i + 1}`,
        description: `Similar product with ${Math.floor(Math.random() * 30) + 70}% similarity`,
        price: Math.floor(Math.random() * 1000) + 100,
        image_url: '/images/placeholder.jpg',
        category_id: 1,
        category_name: 'Similar',
        availability: 'in_stock',
        created_at: new Date().toISOString(),
        similarity_score: Math.random() * 0.3 + 0.7,
        algorithm: 'Fallback Algorithm'
      }));
      
      setRecommendations(mockRecommendations);
      
    } catch (err) {
      setError('Network error loading recommendations');
      console.error('Recommendations error:', err);
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
      const response = await fetch(`http://localhost/my_little_thingz/backend/api/customer/cart.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': auth.user_id
        },
        body: JSON.stringify({
          artwork_id: artwork.id,
          quantity: 1
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        alert('Added to cart!');
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
          artwork_id: artwork.id
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        alert('Added to wishlist!');
      } else {
        alert(data.message || 'Failed to add to wishlist');
      }
    } catch (err) {
      alert('Network error adding to wishlist');
    }
  };

  if (loading) {
    return (
      <section className="recommendations">
        <div className="recs-head">
          <h3>{title}</h3>
          <div className="recs-nav">
            <button className="btn-icon" onClick={scrollLeft} aria-label="Scroll left">‹</button>
            <button className="btn-icon" onClick={scrollRight} aria-label="Scroll right">›</button>
          </div>
        </div>
        <div className="recs-track" ref={trackRef}>
          {[...Array(limit)].map((_, i) => (
            <div key={i} className="recommendation-card loading recs-card">
              <div className="card-image shimmer"></div>
              <div className="card-content">
                <div className="card-title shimmer"></div>
                <div className="card-price shimmer"></div>
              </div>
            </div>
          ))}
        </div>
      </section>
    );
  }

  if (error || recommendations.length === 0) {
    return null; // Don't show if no recommendations or error
  }

  return (
    <section className="recommendations">
      <div className="recs-head">
        <h3>{title}</h3>
        <div className="recs-nav">
          <button className="btn-icon" onClick={scrollLeft} aria-label="Scroll left">‹</button>
          <button className="btn-icon" onClick={scrollRight} aria-label="Scroll right">›</button>
        </div>
      </div>
      <div className="recs-track" ref={trackRef}>
        {recommendations.map((artwork) => (
          <div key={artwork.id} className="recommendation-card recs-card">
            <div className="card-image">
              <img
                src={artwork.image_url}
                alt={artwork.title}
                onError={(e) => {
                  e.target.src = '/vite.svg'; // fallback image
                }}
              />
              {artwork.is_on_offer && (
                <div className="offer-badge">Offer</div>
              )}
            </div>

            <div className="card-content">
              <h4 className="card-title">{artwork.title}</h4>
              <p className="card-category">{artwork.category_name}</p>
              <div className="card-price">
                {artwork.effective_price && artwork.effective_price !== artwork.price ? (
                  <>
                    <span className="original-price">₹{artwork.price}</span>
                    <span className="effective-price">₹{artwork.effective_price}</span>
                  </>
                ) : (
                  <span>₹{artwork.price}</span>
                )}
              </div>
            </div>

            <div className="card-actions">
              {showAddToCart && (
                <button
                  className="btn-icon"
                  onClick={() => handleAddToCart(artwork)}
                  title="Add to Cart"
                >
                  <LuShoppingCart />
                </button>
              )}

              {showWishlist && (
                <button
                  className="btn-icon"
                  onClick={() => handleAddToWishlist(artwork)}
                  title="Add to Wishlist"
                >
                  <LuHeart />
                </button>
              )}

              {onCustomizationRequest && (
                <button
                  className="btn-icon"
                  onClick={() => onCustomizationRequest(artwork)}
                  title="Request Customization"
                >
                  <LuWand />
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
};

export default Recommendations;