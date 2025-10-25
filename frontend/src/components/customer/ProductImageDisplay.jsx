import React, { useState, useEffect } from 'react';
import { LuImage, LuAlertCircle, LuLoader2, LuStar } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import '../../styles/product-image-display.css';

const ProductImageDisplay = ({
  artworkId = null,
  imageUrl = null,
  title = "Product",
  price = 0,
  category = "",
  description = "",
  showDetails = true,
  showPricing = true,
  className = ""
}) => {
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageError, setImageError] = useState(false);
  const [artwork, setArtwork] = useState(null);
  const [loading, setLoading] = useState(false);
  const { auth } = useAuth ? useAuth() : { auth: null };
  const [reviews, setReviews] = useState([]);
  const [reviewsLoading, setReviewsLoading] = useState(false);
  const [avgRating, setAvgRating] = useState(null);
  const [reviewsTotal, setReviewsTotal] = useState(0);
  const [submitLoading, setSubmitLoading] = useState(false);
  const [ratingInput, setRatingInput] = useState(5);
  const [commentInput, setCommentInput] = useState('');
  const API_BASE = 'http://localhost/my_little_thingz/backend/api';

  useEffect(() => {
    if (artworkId && !imageUrl) {
      fetchArtworkDetails();
    } else if (imageUrl) {
      setArtwork({
        image_url: imageUrl,
        title: title,
        price: price,
        category_name: category,
        description: description
      });
    }
    if (artworkId) {
      fetchReviews();
    }
  }, [artworkId, imageUrl, title, price, category, description]);

  const fetchArtworkDetails = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE}/customer/artwork_details.php?id=${artworkId}`);
      const data = await response.json();
      
      if (data.status === 'success' && data.artwork) {
        setArtwork(data.artwork);
      }
    } catch (err) {
      console.error('Error fetching artwork details:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchReviews = async () => {
    if (!artworkId) return;
    try {
      setReviewsLoading(true);
      const res = await fetch(`${API_BASE}/customer/reviews.php?artwork_id=${artworkId}&limit=50`);
      const data = await res.json();
      if (data.status === 'success') {
        setReviews(Array.isArray(data.items) ? data.items : []);
        setAvgRating(data.avg_rating ?? null);
        setReviewsTotal(data.total ?? 0);
      }
    } catch (e) {
      console.error('Failed to load reviews', e);
    } finally {
      setReviewsLoading(false);
    }
  };

  const submitReview = async (e) => {
    e.preventDefault();
    if (!auth?.user?.id) {
      alert('Please login to submit a review.');
      return;
    }
    if (!artworkId) return;
    try {
      setSubmitLoading(true);
      const res = await fetch(`${API_BASE}/customer/reviews.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': String(auth.user.id)
        },
        body: JSON.stringify({
          user_id: auth.user.id,
          artwork_id: artworkId,
          rating: Number(ratingInput),
          comment: commentInput
        })
      });
      const data = await res.json();
      if (data.status === 'success') {
        setCommentInput('');
        setRatingInput(5);
        // Refresh list (will only show after approval)
        fetchReviews();
        alert('Review submitted for moderation.');
      } else {
        alert(data.message || 'Failed to submit review');
      }
    } catch (e) {
      alert('Failed to submit review');
    } finally {
      setSubmitLoading(false);
    }
  };

  const handleImageLoad = () => {
    setImageLoaded(true);
    setImageError(false);
  };

  const handleImageError = () => {
    setImageError(true);
    setImageLoaded(false);
  };

  const getPlaceholderImage = () => {
    return 'data:image/svg+xml;base64,' + btoa(`
      <svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#f3f4f6"/>
        <g transform="translate(150, 150)">
          <circle cx="0" cy="0" r="40" fill="#d1d5db"/>
          <path d="M-20,-20 L20,20 M20,-20 L-20,20" stroke="#9ca3af" stroke-width="3" stroke-linecap="round"/>
        </g>
        <text x="150" y="200" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#6b7280">No Image Available</text>
      </svg>
    `);
  };

  if (loading) {
    return (
      <div className={`product-image-container ${className}`}>
        <div className="image-loading">
          <LuLoader2 className="loading-spinner" />
          <p>Loading product image...</p>
        </div>
      </div>
    );
  }

  if (!artwork) {
    return (
      <div className={`product-image-container ${className}`}>
        <div className="image-error">
          <LuAlertCircle className="error-icon" />
          <p>Product not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className={`product-image-container ${className}`}>
      <div className="product-image-wrapper">
        <div className="image-container">
          {!imageLoaded && !imageError && (
            <div className="image-loading-overlay">
              <LuLoader2 className="loading-spinner" />
              <span>Loading image...</span>
            </div>
          )}
          
          <img
            src={imageError ? getPlaceholderImage() : artwork.image_url}
            alt={artwork.title}
            className={`product-image ${imageLoaded ? 'loaded' : ''} ${imageError ? 'error' : ''}`}
            onLoad={handleImageLoad}
            onError={handleImageError}
          />
          
          {imageError && (
            <div className="image-error-overlay">
              <LuImage className="error-icon" />
              <span>Image not available</span>
            </div>
          )}

          {artwork.has_offer && (
            <div className="offer-badge">
              {artwork.offer_percent ? `${artwork.offer_percent}% OFF` : 'OFFER'}
            </div>
          )}
        </div>

        {showDetails && (
          <div className="product-details">
            <h3 className="product-title">{artwork.title}</h3>
            {artwork.description && (
              <p className="product-description">{artwork.description}</p>
            )}
            
            {showPricing && (
              <div className="product-pricing">
                {artwork.effective_price && artwork.effective_price < artwork.price ? (
                  <div className="price-container">
                    <span className="original-price">₹{artwork.price}</span>
                    <span className="offer-price">₹{artwork.effective_price}</span>
                  </div>
                ) : (
                  <span className="price">₹{artwork.price}</span>
                )}
                {artwork.category_name && (
                  <span className="category">• {artwork.category_name}</span>
                )}
              </div>
            )}

            {/* Reviews Summary */}
            <div style={{ marginTop: 16 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <LuStar style={{ color: '#fbbf24' }} />
                <strong>Ratings & Reviews</strong>
                {avgRating != null && (
                  <span style={{ color: '#374151' }}>Avg {avgRating} ({reviewsTotal})</span>
                )}
              </div>
            </div>

            {/* Reviews List */}
            <div style={{ marginTop: 8 }}>
              {reviewsLoading ? (
                <div style={{ color: '#6b7280' }}>Loading reviews…</div>
              ) : reviews.length === 0 ? (
                <div style={{ color: '#6b7280' }}>No reviews yet.</div>
              ) : (
                <div style={{ display: 'grid', gap: 12 }}>
                  {reviews.map((r) => (
                    <div key={r.id} style={{ border: '1px solid #e5e7eb', borderRadius: 8, padding: 12 }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
                        <LuStar style={{ color: '#f59e0b' }} />
                        <span style={{ fontWeight: 600 }}>{r.rating}/5</span>
                        <span style={{ color: '#9ca3af', fontSize: 12 }}>{new Date(r.created_at).toLocaleDateString()}</span>
                      </div>
                      {r.comment && <div style={{ color: '#374151' }}>{r.comment}</div>}
                      {r.admin_reply && (
                        <div style={{ marginTop: 8, fontSize: 14, color: '#1f2937' }}>
                          <em>Seller reply:</em> {r.admin_reply}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Submit Review */}
            <form onSubmit={submitReview} style={{ marginTop: 16, display: 'grid', gap: 8 }}>
              <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <span style={{ width: 80 }}>Rating</span>
                <select value={ratingInput} onChange={(e) => setRatingInput(e.target.value)} style={{ padding: 6 }}>
                  {[5,4,3,2,1].map((v) => (
                    <option key={v} value={v}>{v}</option>
                  ))}
                </select>
              </label>
              <label style={{ display: 'flex', gap: 8 }}>
                <span style={{ width: 80 }}>Comment</span>
                <textarea
                  value={commentInput}
                  onChange={(e) => setCommentInput(e.target.value)}
                  placeholder="Share your experience after delivery"
                  rows={3}
                  style={{ flex: 1, padding: 8 }}
                />
              </label>
              <div>
                <button type="submit" disabled={submitLoading} className="btn-primary">
                  {submitLoading ? 'Submitting…' : 'Submit Review'}
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProductImageDisplay;










