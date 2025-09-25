import React, { useState, useEffect } from 'react';
import { LuHeart, LuShoppingCart, LuWand } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/recommendations.css';
import { useRef, useCallback } from 'react';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

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

      const params = new URLSearchParams();
      if (artworkId) {
        params.set('artwork_id', artworkId);
      } else if (auth?.user_id) {
        params.set('user_id', auth.user_id);
      } else {
        // No artwork or user, show popular items
        params.set('limit', limit);
      }
      params.set('limit', limit);

      const response = await fetch(`${API_BASE}/customer/recommendations.php?${params}`);
      const data = await response.json();

      if (data.status === 'success') {
        setRecommendations(data.recommendations || []);
      } else {
        setError(data.message || 'Failed to load recommendations');
      }
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
      const response = await fetch(`${API_BASE}/customer/cart.php`, {
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