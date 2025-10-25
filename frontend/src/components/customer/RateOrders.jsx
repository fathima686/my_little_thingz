import React, { useState, useEffect } from 'react';
import { LuStar, LuStarOff, LuX, LuPackage, LuCalendar } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import ProductRating from './ProductRating';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const RateOrders = ({ onClose }) => {
  const { auth } = useAuth();
  const [rateableOrders, setRateableOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedItem, setSelectedItem] = useState(null);

  useEffect(() => {
    fetchRateableOrders();
  }, []);

  const fetchRateableOrders = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/rateable-orders.php`, {
        headers: {
          'X-User-ID': auth?.user_id
        }
      });

      const data = await response.json();
      if (data.status === 'success') {
        setRateableOrders(data.orders);
      }
    } catch (error) {
      console.error('Error fetching rateable orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRatingSubmitted = () => {
    // Refresh the list after rating is submitted
    fetchRateableOrders();
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

  if (loading) {
    return (
      <div className="modal-overlay">
        <div className="modal-content large">
          <div className="loading-spinner">Loading orders...</div>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay">
      <div className="modal-content large">
        <div className="modal-header">
          <h2>Rate Your Orders</h2>
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
        </div>

        {rateableOrders.length > 0 ? (
          <div className="orders-list">
            {rateableOrders.map(order => (
              <div key={order.order_id} className="order-card">
                <div className="order-header">
                  <div className="order-info">
                    <h3>Order #{order.order_number}</h3>
                    <p className="order-date">
                      <LuCalendar /> Delivered: {order.delivered_at}
                    </p>
                    <p className="order-stats">
                      <LuPackage /> {order.rated_items} of {order.total_items} items rated
                    </p>
                  </div>
                </div>

                <div className="items-list">
                  {order.items.map(item => (
                    <div key={item.artwork_id} className="item-card">
                      <img 
                        src={item.image_url || '/api/placeholder/80/80'} 
                        alt={item.artwork_title}
                        className="item-image"
                      />
                      <div className="item-info">
                        <h4>{item.artwork_title}</h4>
                        <p>Quantity: {item.quantity}</p>
                        <p>Price: ₹{parseFloat(item.price).toFixed(2)}</p>
                      </div>
                      <div className="item-rating">
                        {item.rating_id ? (
                          <div className="rated">
                            <div className="stars">
                              {renderStars(item.rating)}
                            </div>
                            <p className="rating-text">Rated: {item.rating}/5</p>
                            {item.feedback && (
                              <p className="feedback-preview">
                                "{item.feedback.substring(0, 50)}..."
                              </p>
                            )}
                          </div>
                        ) : (
                          <button 
                            className="btn btn-primary"
                            onClick={() => setSelectedItem({ order, artwork: item })}
                          >
                            <LuStar /> Rate Product
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="empty-state">
            <LuStar size={64} />
            <h3>No orders to rate</h3>
            <p>You don't have any delivered orders that need rating yet.</p>
            <button className="btn btn-primary" onClick={onClose}>
              Close
            </button>
          </div>
        )}

        {selectedItem && (
          <ProductRating
            order={selectedItem.order}
            artwork={selectedItem.artwork}
            onClose={() => setSelectedItem(null)}
            onRatingSubmitted={handleRatingSubmitted}
          />
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

          .loading-spinner {
            text-align: center;
            padding: 48px;
            color: #666;
          }

          .orders-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
          }

          .order-card {
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 20px;
            background: #fafafa;
          }

          .order-header {
            margin-bottom: 16px;
          }

          .order-info h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 18px;
          }

          .order-date, .order-stats {
            margin: 4px 0;
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
          }

          .items-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
          }

          .item-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            border: 1px solid #eee;
          }

          .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
          }

          .item-info {
            flex: 1;
          }

          .item-info h4 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 16px;
          }

          .item-info p {
            margin: 4px 0;
            color: #666;
            font-size: 14px;
          }

          .item-rating {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
          }

          .rated {
            text-align: center;
          }

          .stars {
            display: flex;
            gap: 2px;
            margin-bottom: 4px;
          }

          .star {
            color: #ddd;
            font-size: 16px;
          }

          .star.filled {
            color: #ffc107;
          }

          .rating-text {
            margin: 0;
            font-size: 12px;
            color: #666;
            font-weight: 500;
          }

          .feedback-preview {
            margin: 4px 0 0 0;
            font-size: 11px;
            color: #999;
            font-style: italic;
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

          .btn-primary {
            background: #6b46c1;
            color: white;
          }

          .btn-primary:hover {
            background: #553c9a;
          }

          .empty-state {
            text-align: center;
            padding: 64px 32px;
            color: #666;
          }

          .empty-state svg {
            color: #ddd;
            margin-bottom: 16px;
          }

          .empty-state h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
          }

          .empty-state p {
            margin: 0 0 24px 0;
          }

          @media (max-width: 768px) {
            .modal-content {
              margin: 20px;
              width: calc(100% - 40px);
            }

            .item-card {
              flex-direction: column;
              text-align: center;
            }

            .item-rating {
              width: 100%;
            }
          }
        `}</style>
      </div>
    </div>
  );
};

export default RateOrders;
