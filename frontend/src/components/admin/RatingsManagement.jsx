import React, { useState, useEffect } from 'react';
import { LuStar, LuStarOff, LuX, LuFilter, LuSearch, LuEye, LuCheck, LuXCircle, LuMessageSquare } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const RatingsManagement = ({ onClose }) => {
  const { auth } = useAuth();
  const [ratings, setRatings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    status: 'all',
    rating: '',
    artwork_id: ''
  });
  const [pagination, setPagination] = useState({
    total: 0,
    limit: 20,
    offset: 0,
    has_more: false
  });
  const [selectedRating, setSelectedRating] = useState(null);

  useEffect(() => {
    fetchRatings();
  }, [filters, pagination.offset]);

  const fetchRatings = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        limit: pagination.limit,
        offset: pagination.offset
      });

      if (filters.status !== 'all') params.append('status', filters.status);
      if (filters.rating) params.append('rating', filters.rating);
      if (filters.artwork_id) params.append('artwork_id', filters.artwork_id);

      const response = await fetch(`${API_BASE}/admin/ratings.php?${params}`, {
        headers: {
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        }
      });

      const data = await response.json();
      if (data.status === 'success') {
        setRatings(data.ratings);
        setPagination(prev => ({
          ...prev,
          total: data.pagination.total,
          has_more: data.pagination.has_more
        }));
      }
    } catch (error) {
      console.error('Error fetching ratings:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateRatingStatus = async (ratingId, status, adminNotes = null) => {
    try {
      const response = await fetch(`${API_BASE}/admin/ratings.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        },
        body: JSON.stringify({
          rating_id: ratingId,
          status: status,
          admin_notes: adminNotes
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { type: 'success', message: 'Rating updated successfully' } 
        }));
        fetchRatings();
        setSelectedRating(null);
      } else {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { type: 'error', message: data.message || 'Failed to update rating' } 
        }));
      }
    } catch (error) {
      console.error('Error updating rating:', error);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { type: 'error', message: 'Error updating rating' } 
      }));
    }
  };

  const renderStars = (rating) => {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      const isFilled = i <= rating;
      stars.push(
        <span key={i} className={`star ${isFilled ? 'filled' : ''}`}>
          {isFilled ? <LuStar /> : <LuStarOff />}
        </span>
      );
    }
    return stars;
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'approved': return '#16a34a';
      case 'pending': return '#f59e0b';
      case 'rejected': return '#dc2626';
      default: return '#6b7280';
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setPagination(prev => ({ ...prev, offset: 0 }));
  };

  const loadMore = () => {
    setPagination(prev => ({
      ...prev,
      offset: prev.offset + prev.limit
    }));
  };

  if (loading && ratings.length === 0) {
    return (
      <div className="modal-overlay">
        <div className="modal-content large">
          <div className="loading-spinner">Loading ratings...</div>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay">
      <div className="modal-content extra-large">
        <div className="modal-header">
          <h2>📊 Ratings & Feedback Management</h2>
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
        </div>

        {/* Filters */}
        <div className="filters-section">
          <div className="filter-group">
            <label>Status:</label>
            <select 
              value={filters.status} 
              onChange={(e) => handleFilterChange('status', e.target.value)}
            >
              <option value="all">All Status</option>
              <option value="approved">Approved</option>
              <option value="pending">Pending</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>

          <div className="filter-group">
            <label>Rating:</label>
            <select 
              value={filters.rating} 
              onChange={(e) => handleFilterChange('rating', e.target.value)}
            >
              <option value="">All Ratings</option>
              <option value="5">5 Stars</option>
              <option value="4">4 Stars</option>
              <option value="3">3 Stars</option>
              <option value="2">2 Stars</option>
              <option value="1">1 Star</option>
            </select>
          </div>

          <div className="filter-group">
            <label>Artwork ID:</label>
            <input
              type="text"
              placeholder="Enter artwork ID"
              value={filters.artwork_id}
              onChange={(e) => handleFilterChange('artwork_id', e.target.value)}
            />
          </div>
        </div>

        {/* Ratings List */}
        <div className="ratings-list">
          {ratings.map(rating => (
            <div key={rating.id} className="rating-card">
              <div className="rating-header">
                <div className="rating-info">
                  <div className="stars">
                    {renderStars(rating.rating)}
                  </div>
                  <div className="rating-details">
                    <h4>{rating.artwork_title}</h4>
                    <p>Order: {rating.order_number}</p>
                    <p>Customer: {rating.first_name} {rating.last_name}</p>
                    <p>Date: {rating.created_at}</p>
                  </div>
                </div>
                <div className="rating-status">
                  <span 
                    className="status-badge"
                    style={{ backgroundColor: getStatusColor(rating.status) }}
                  >
                    {rating.status}
                  </span>
                  <button 
                    className="btn btn-outline small"
                    onClick={() => setSelectedRating(rating)}
                  >
                    <LuEye /> View Details
                  </button>
                </div>
              </div>

              {rating.feedback && (
                <div className="feedback-section">
                  <p className="feedback-text">"{rating.feedback}"</p>
                </div>
              )}

              {rating.admin_notes && (
                <div className="admin-notes">
                  <strong>Admin Notes:</strong> {rating.admin_notes}
                </div>
              )}
            </div>
          ))}
        </div>

        {/* Load More */}
        {pagination.has_more && (
          <div className="load-more-section">
            <button 
              className="btn btn-outline"
              onClick={loadMore}
              disabled={loading}
            >
              {loading ? 'Loading...' : 'Load More'}
            </button>
          </div>
        )}

        {/* Rating Detail Modal */}
        {selectedRating && (
          <div className="modal-overlay" style={{ zIndex: 1001 }}>
            <div className="modal-content">
              <div className="modal-header">
                <h3>Rating Details</h3>
                <button className="btn-close" onClick={() => setSelectedRating(null)}>
                  <LuX />
                </button>
              </div>

              <div className="rating-detail-content">
                <div className="product-info">
                  <img 
                    src={selectedRating.image_url || '/api/placeholder/100/100'} 
                    alt={selectedRating.artwork_title}
                    className="product-image"
                  />
                  <div className="product-details">
                    <h4>{selectedRating.artwork_title}</h4>
                    <p>Order: {selectedRating.order_number}</p>
                    <p>Delivered: {selectedRating.delivered_at}</p>
                  </div>
                </div>

                <div className="rating-details">
                  <div className="stars">
                    {renderStars(selectedRating.rating)}
                  </div>
                  <p><strong>Customer:</strong> {selectedRating.first_name} {selectedRating.last_name}</p>
                  <p><strong>Email:</strong> {selectedRating.email}</p>
                  <p><strong>Anonymous:</strong> {selectedRating.is_anonymous ? 'Yes' : 'No'}</p>
                  <p><strong>Date:</strong> {selectedRating.created_at}</p>
                </div>

                {selectedRating.feedback && (
                  <div className="feedback-detail">
                    <h5>Customer Feedback:</h5>
                    <p>"{selectedRating.feedback}"</p>
                  </div>
                )}

                <div className="admin-actions">
                  <h5>Admin Actions:</h5>
                  <div className="action-buttons">
                    <button 
                      className="btn btn-success"
                      onClick={() => updateRatingStatus(selectedRating.id, 'approved')}
                      disabled={selectedRating.status === 'approved'}
                    >
                      <LuCheck /> Approve
                    </button>
                    <button 
                      className="btn btn-warning"
                      onClick={() => updateRatingStatus(selectedRating.id, 'pending')}
                      disabled={selectedRating.status === 'pending'}
                    >
                      <LuMessageSquare /> Mark Pending
                    </button>
                    <button 
                      className="btn btn-danger"
                      onClick={() => updateRatingStatus(selectedRating.id, 'rejected')}
                      disabled={selectedRating.status === 'rejected'}
                    >
                      <LuXCircle /> Reject
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

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
            max-height: 90vh;
            overflow-y: auto;
            width: 90%;
            max-width: 800px;
          }

          .modal-content.large {
            max-width: 1000px;
          }

          .modal-content.extra-large {
            max-width: 1200px;
          }

          .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
          }

          .modal-header h2, .modal-header h3 {
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

          .loading-spinner {
            text-align: center;
            padding: 48px;
            color: #666;
          }

          .filters-section {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            flex-wrap: wrap;
          }

          .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
          }

          .filter-group label {
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
          }

          .filter-group select,
          .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
          }

          .ratings-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
          }

          .rating-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 16px;
            background: white;
          }

          .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
          }

          .rating-info {
            display: flex;
            gap: 16px;
            flex: 1;
          }

          .stars {
            display: flex;
            gap: 2px;
            margin-top: 4px;
          }

          .star {
            color: #ddd;
            font-size: 16px;
          }

          .star.filled {
            color: #ffc107;
          }

          .rating-details h4 {
            margin: 0 0 4px 0;
            color: #2c3e50;
            font-size: 16px;
          }

          .rating-details p {
            margin: 2px 0;
            color: #666;
            font-size: 14px;
          }

          .rating-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
          }

          .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            color: white;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
          }

          .feedback-section {
            margin-top: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
          }

          .feedback-text {
            margin: 0;
            font-style: italic;
            color: #2c3e50;
          }

          .admin-notes {
            margin-top: 8px;
            padding: 8px;
            background: #fff3cd;
            border-radius: 4px;
            font-size: 14px;
            color: #856404;
          }

          .load-more-section {
            text-align: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #eee;
          }

          .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
          }

          .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
          }

          .btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: #333;
          }

          .btn-outline:hover {
            background: #f8f9fa;
          }

          .btn-outline.small {
            padding: 6px 12px;
            font-size: 12px;
          }

          .btn-success {
            background: #16a34a;
            color: white;
          }

          .btn-success:hover:not(:disabled) {
            background: #15803d;
          }

          .btn-warning {
            background: #f59e0b;
            color: white;
          }

          .btn-warning:hover:not(:disabled) {
            background: #d97706;
          }

          .btn-danger {
            background: #dc2626;
            color: white;
          }

          .btn-danger:hover:not(:disabled) {
            background: #b91c1c;
          }

          .rating-detail-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
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

          .product-details h4 {
            margin: 0 0 8px 0;
            color: #2c3e50;
          }

          .product-details p {
            margin: 4px 0;
            color: #666;
            font-size: 14px;
          }

          .rating-details {
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
          }

          .rating-details p {
            margin: 8px 0;
            color: #2c3e50;
          }

          .feedback-detail {
            padding: 16px;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 4px solid #0ea5e9;
          }

          .feedback-detail h5 {
            margin: 0 0 8px 0;
            color: #2c3e50;
          }

          .feedback-detail p {
            margin: 0;
            font-style: italic;
            color: #2c3e50;
          }

          .admin-actions {
            padding: 16px;
            background: #fef3c7;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
          }

          .admin-actions h5 {
            margin: 0 0 12px 0;
            color: #2c3e50;
          }

          .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
          }

          @media (max-width: 768px) {
            .filters-section {
              flex-direction: column;
            }

            .rating-header {
              flex-direction: column;
              gap: 12px;
            }

            .rating-status {
              align-items: flex-start;
            }

            .action-buttons {
              flex-direction: column;
            }
          }
        `}</style>
      </div>
    </div>
  );
};

export default RatingsManagement;











