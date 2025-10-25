import React, { useState } from 'react';
import { LuStar, LuStarOff, LuX, LuSend } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const ProductRating = ({ order, artwork, onClose, onRatingSubmitted }) => {
  const { auth } = useAuth();
  const [rating, setRating] = useState(0);
  const [hoveredRating, setHoveredRating] = useState(0);
  const [feedback, setFeedback] = useState('');
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleRatingSubmit = async (e) => {
    e.preventDefault();
    
    if (rating === 0) {
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { type: 'error', message: 'Please select a rating' } 
      }));
      return;
    }

    setSubmitting(true);

    try {
      const response = await fetch(`${API_BASE}/customer/submit-rating.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': auth?.user_id
        },
        body: JSON.stringify({
          user_id: auth?.user_id,
          artwork_id: artwork.artwork_id,
          rating: rating,
          feedback: feedback.trim() || null,
          is_anonymous: isAnonymous
        })
      });

      const data = await response.json();

      if (data.status === 'success') {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { type: 'success', message: 'Thank you for your rating!' } 
        }));
        
        if (onRatingSubmitted) {
          onRatingSubmitted();
        }
        
        onClose();
      } else {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { type: 'error', message: data.message || 'Failed to submit rating' } 
        }));
      }
    } catch (error) {
      console.error('Error submitting rating:', error);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { type: 'error', message: 'Error submitting rating. Please try again.' } 
      }));
    } finally {
      setSubmitting(false);
    }
  };

  const renderStars = () => {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      const isFilled = i <= (hoveredRating || rating);
      stars.push(
        <button
          key={i}
          type="button"
          className={`star-button ${isFilled ? 'filled' : ''}`}
          onClick={() => setRating(i)}
          onMouseEnter={() => setHoveredRating(i)}
          onMouseLeave={() => setHoveredRating(0)}
        >
          {isFilled ? <LuStar /> : <LuStarOff />}
        </button>
      );
    }
    return stars;
  };

  const getRatingText = (rating) => {
    const texts = {
      1: 'Poor',
      2: 'Fair',
      3: 'Good',
      4: 'Very Good',
      5: 'Excellent'
    };
    return texts[rating] || '';
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <div className="modal-header">
          <h2>Rate Your Purchase</h2>
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
        </div>

        <div className="rating-content">
          <div className="product-info">
            <img 
              src={artwork.image_url || '/api/placeholder/100/100'} 
              alt={artwork.artwork_title}
              className="product-image"
            />
            <div className="product-details">
              <h3>{artwork.artwork_title}</h3>
              <p>Order: {order.order_number}</p>
              <p>Delivered: {order.delivered_at}</p>
            </div>
          </div>

          <form onSubmit={handleRatingSubmit} className="rating-form">
            <div className="rating-section">
              <label className="rating-label">How would you rate this product?</label>
              <div className="stars-container">
                {renderStars()}
                {rating > 0 && (
                  <span className="rating-text">{getRatingText(rating)}</span>
                )}
              </div>
            </div>

            <div className="feedback-section">
              <label htmlFor="feedback" className="feedback-label">
                Share your experience (optional)
              </label>
              <textarea
                id="feedback"
                value={feedback}
                onChange={(e) => setFeedback(e.target.value)}
                placeholder="Tell us about your experience with this product..."
                className="feedback-textarea"
                rows={4}
                maxLength={500}
              />
              <div className="character-count">
                {feedback.length}/500 characters
              </div>
            </div>

            <div className="anonymous-section">
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={isAnonymous}
                  onChange={(e) => setIsAnonymous(e.target.checked)}
                />
                <span className="checkmark"></span>
                Submit anonymously
              </label>
            </div>

            <div className="form-actions">
              <button 
                type="button" 
                className="btn btn-outline" 
                onClick={onClose}
                disabled={submitting}
              >
                Cancel
              </button>
              <button 
                type="submit" 
                className="btn btn-primary" 
                disabled={submitting || rating === 0}
              >
                {submitting ? (
                  <>Submitting...</>
                ) : (
                  <>
                    <LuSend /> Submit Rating
                  </>
                )}
              </button>
            </div>
          </form>
        </div>

        <style>{`
          .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
          }

          .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
          }

          .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
          }

          .modal-header h2 {
            margin: 0;
            color: #2c3e50;
          }

          .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
          }

          .btn-close:hover {
            background: #f5f5f5;
          }

          .rating-content {
            display: flex;
            flex-direction: column;
            gap: 24px;
          }

          .product-info {
            display: flex;
            gap: 16px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
          }

          .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
          }

          .product-details h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #2c3e50;
          }

          .product-details p {
            margin: 0 0 4px 0;
            font-size: 14px;
            color: #666;
          }

          .rating-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
          }

          .rating-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
          }

          .rating-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
          }

          .stars-container {
            display: flex;
            align-items: center;
            gap: 8px;
          }

          .star-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #ddd;
            transition: color 0.2s;
          }

          .star-button.filled {
            color: #ffc107;
          }

          .star-button:hover {
            color: #ffc107;
          }

          .rating-text {
            margin-left: 12px;
            font-weight: 600;
            color: #2c3e50;
          }

          .feedback-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
          }

          .feedback-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
          }

          .feedback-textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
          }

          .feedback-textarea:focus {
            outline: none;
            border-color: #6b46c1;
            box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
          }

          .character-count {
            font-size: 12px;
            color: #666;
            text-align: right;
          }

          .anonymous-section {
            display: flex;
            align-items: center;
          }

          .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            color: #2c3e50;
          }

          .checkbox-label input[type="checkbox"] {
            display: none;
          }

          .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
          }

          .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: #6b46c1;
            border-color: #6b46c1;
            color: white;
          }

          .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            font-size: 12px;
            font-weight: bold;
          }

          .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid #eee;
          }

          .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
          }

          .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
          }

          .btn-primary {
            background: #6b46c1;
            color: white;
          }

          .btn-primary:hover:not(:disabled) {
            background: #553c9a;
          }

          .btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: #333;
          }

          .btn-outline:hover {
            background: #f8f9fa;
          }

          @media (max-width: 768px) {
            .modal-content {
              margin: 20px;
              width: calc(100% - 40px);
            }

            .product-info {
              flex-direction: column;
              text-align: center;
            }

            .form-actions {
              flex-direction: column;
            }
          }
        `}</style>
      </div>
    </div>
  );
};

export default ProductRating;
