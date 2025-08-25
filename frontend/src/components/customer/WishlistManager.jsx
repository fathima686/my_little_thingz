import React, { useState, useEffect } from 'react';
import { LuX, LuHeart, LuShoppingCart, LuTrash2, LuShare2, LuEye, LuSettings } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const WishlistManager = ({ onClose }) => {
  const { auth } = useAuth();
  const [wishlistItems, setWishlistItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedItem, setSelectedItem] = useState(null);
  const [filter, setFilter] = useState('all');
  const [sortBy, setSortBy] = useState('date_added');

  useEffect(() => {
    fetchWishlist();
  }, []);

  const fetchWishlist = async () => {
    try {
      const userIdQs = auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : '';
      const response = await fetch(`${API_BASE}/customer/wishlist.php${userIdQs}`, {
        headers: {
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        }
      });
      const data = await response.json();
      if (data.status === 'success') {
        setWishlistItems(data.wishlist || []);
      }
    } catch (error) {
      console.error('Error fetching wishlist:', error);
    } finally {
      setLoading(false);
    }
  };

  const removeFromWishlist = async (itemId) => {
    try {
      const userIdQs = auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : '';
      const response = await fetch(`${API_BASE}/customer/wishlist.php${userIdQs}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        },
        body: JSON.stringify({ artwork_id: itemId })
      });

      const data = await response.json();
      if (data.status === 'success') {
        setWishlistItems(prev => prev.filter(item => item.artwork_id !== itemId));
      } else {
        alert(data.message || 'Failed to remove item');
      }
    } catch (error) {
      console.error('Error removing from wishlist:', error);
      alert('Error removing item from wishlist');
    }
  };

  const addToCart = async (artworkId) => {
    try {
      const response = await fetch(`${API_BASE}/customer/cart.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        },
        body: JSON.stringify({ 
          artwork_id: artworkId,
          quantity: 1
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        alert('Added to cart successfully!');
      } else {
        alert(data.message || 'Failed to add to cart');
      }
    } catch (error) {
      console.error('Error adding to cart:', error);
      alert('Error adding to cart');
    }
  };

  const shareWishlist = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/wishlist-share.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        }
      });

      const data = await response.json();
      if (data.status === 'success') {
        const shareUrl = `${window.location.origin}/wishlist/shared/${data.share_token}`;
        
        if (navigator.share) {
          await navigator.share({
            title: 'My Wishlist - My Little Thingz',
            text: 'Check out my wishlist!',
            url: shareUrl
          });
        } else {
          // Fallback: copy to clipboard
          await navigator.clipboard.writeText(shareUrl);
          alert('Wishlist link copied to clipboard!');
        }
      } else {
        alert(data.message || 'Failed to generate share link');
      }
    } catch (error) {
      console.error('Error sharing wishlist:', error);
      alert('Error sharing wishlist');
    }
  };

  const getSortedAndFilteredItems = () => {
    let filtered = [...wishlistItems];

    // Apply filters
    if (filter === 'available') {
      filtered = filtered.filter(item => item.availability === 'available');
    } else if (filter === 'out_of_stock') {
      filtered = filtered.filter(item => item.availability === 'out_of_stock');
    }

    // Apply sorting
    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'date_added':
          return new Date(b.added_at) - new Date(a.added_at);
        case 'price_low':
          return parseFloat(a.price) - parseFloat(b.price);
        case 'price_high':
          return parseFloat(b.price) - parseFloat(a.price);
        case 'name':
          return a.title.localeCompare(b.title);
        default:
          return 0;
      }
    });

    return filtered;
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (loading) {
    return (
      <div className="modal-overlay">
        <div className="modal-content large">
          <div className="loading-spinner">Loading wishlist...</div>
        </div>
      </div>
    );
  }

  const sortedItems = getSortedAndFilteredItems();

  return (
    <div className="modal-overlay">
      <div className="modal-content extra-large">
        <div className="modal-header">
          <h2>My Wishlist ({wishlistItems.length} items)</h2>
          <div className="header-actions">
            {wishlistItems.length > 0 && (
              <button className="btn btn-outline" onClick={shareWishlist}>
                <LuShare2 /> Share Wishlist
              </button>
            )}
            <button className="btn-close" onClick={onClose}>
              <LuX />
            </button>
          </div>
        </div>

        {wishlistItems.length > 0 && (
          <div className="wishlist-controls">
            <div className="filter-controls">
              <div className="filter-group">
                <label>Filter:</label>
                <select value={filter} onChange={(e) => setFilter(e.target.value)}>
                  <option value="all">All Items</option>
                  <option value="available">Available</option>
                  <option value="out_of_stock">Out of Stock</option>
                </select>
              </div>

              <div className="filter-group">
                <label>Sort by:</label>
                <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
                  <option value="date_added">Date Added</option>
                  <option value="name">Name</option>
                  <option value="price_low">Price: Low to High</option>
                  <option value="price_high">Price: High to Low</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {sortedItems.length > 0 ? (
          <div className="wishlist-grid">
            {sortedItems.map(item => (
              <div key={item.id} className="wishlist-item">
                <div className="item-image">
                  <img 
                    src={item.image_url || '/api/placeholder/300/300'} 
                    alt={item.title}
                    onClick={() => setSelectedItem(item)}
                  />
                  <div className="item-overlay">
                    <button 
                      className="btn-icon"
                      onClick={() => setSelectedItem(item)}
                      title="View Details"
                    >
                      <LuEye />
                    </button>
                    <button 
                      className="btn-icon"
                      onClick={() => removeFromWishlist(item.artwork_id)}
                      title="Remove from Wishlist"
                    >
                      <LuTrash2 />
                    </button>
                  </div>
                  {item.availability === 'out_of_stock' && (
                    <div className="stock-badge out-of-stock">Out of Stock</div>
                  )}
                </div>

                <div className="item-info">
                  <h3>{item.title}</h3>
                  <p className="item-artist">by {item.artist_name}</p>
                  <p className="item-price">${item.price}</p>
                  <p className="item-added">Added {formatDate(item.added_at)}</p>
                  
                  <div className="item-actions">
                    <button 
                      className="btn btn-primary"
                      onClick={() => addToCart(item.artwork_id)}
                      disabled={item.availability === 'out_of_stock'}
                    >
                      <LuShoppingCart /> Add to Cart
                    </button>
                    <button 
                      className="btn btn-outline"
                      onClick={() => removeFromWishlist(item.artwork_id)}
                    >
                      <LuTrash2 /> Remove
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="empty-wishlist">
            <LuHeart size={64} />
            <h3>Your wishlist is empty</h3>
            <p>Start adding items you love to keep track of them!</p>
            <button className="btn btn-primary" onClick={onClose}>
              Browse Artworks
            </button>
          </div>
        )}

        {/* Item Detail Modal */}
        {selectedItem && (
          <div className="modal-overlay">
            <div className="modal-content large">
              <div className="modal-header">
                <h2>{selectedItem.title}</h2>
                <button className="btn-close" onClick={() => setSelectedItem(null)}>
                  <LuX />
                </button>
              </div>
              
              <div className="item-detail">
                <div className="item-detail-image">
                  <img 
                    src={selectedItem.image_url || '/api/placeholder/400/400'} 
                    alt={selectedItem.title}
                  />
                </div>
                
                <div className="item-detail-info">
                  <h3>{selectedItem.title}</h3>
                  <p className="artist">by {selectedItem.artist_name}</p>
                  <p className="price">${selectedItem.price}</p>
                  <p className="description">{selectedItem.description}</p>
                  
                  <div className="detail-meta">
                    <p><strong>Added to wishlist:</strong> {formatDate(selectedItem.added_at)}</p>
                    <p><strong>Availability:</strong> 
                      <span className={`availability ${selectedItem.availability}`}>
                        {selectedItem.availability === 'available' ? 'In Stock' : 'Out of Stock'}
                      </span>
                    </p>
                  </div>
                  
                  <div className="detail-actions">
                    <button 
                      className="btn btn-primary"
                      onClick={() => addToCart(selectedItem.artwork_id)}
                      disabled={selectedItem.availability === 'out_of_stock'}
                    >
                      <LuShoppingCart /> Add to Cart
                    </button>
                    <button 
                      className="btn btn-outline"
                      onClick={() => {
                        removeFromWishlist(selectedItem.artwork_id);
                        setSelectedItem(null);
                      }}
                    >
                      <LuTrash2 /> Remove from Wishlist
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
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

        .header-actions {
          display: flex;
          align-items: center;
          gap: 12px;
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

        .wishlist-controls {
          margin-bottom: 24px;
          padding: 16px;
          background: #f8f9fa;
          border-radius: 8px;
        }

        .filter-controls {
          display: flex;
          gap: 24px;
          align-items: center;
        }

        .filter-group {
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .filter-group label {
          font-weight: 500;
          color: #2c3e50;
        }

        .filter-group select {
          padding: 6px 12px;
          border: 1px solid #ddd;
          border-radius: 4px;
          background: white;
        }

        .wishlist-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          gap: 24px;
        }

        .wishlist-item {
          border: 1px solid #eee;
          border-radius: 12px;
          overflow: hidden;
          transition: transform 0.2s, box-shadow 0.2s;
        }

        .wishlist-item:hover {
          transform: translateY(-4px);
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .item-image {
          position: relative;
          aspect-ratio: 1;
          overflow: hidden;
        }

        .item-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          cursor: pointer;
        }

        .item-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 12px;
          opacity: 0;
          transition: opacity 0.2s;
        }

        .wishlist-item:hover .item-overlay {
          opacity: 1;
        }

        .btn-icon {
          background: white;
          border: none;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          transition: transform 0.2s;
        }

        .btn-icon:hover {
          transform: scale(1.1);
        }

        .stock-badge {
          position: absolute;
          top: 8px;
          right: 8px;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 12px;
          font-weight: 500;
        }

        .stock-badge.out-of-stock {
          background: #e74c3c;
          color: white;
        }

        .item-info {
          padding: 16px;
        }

        .item-info h3 {
          margin: 0 0 4px 0;
          font-size: 16px;
          font-weight: 600;
        }

        .item-artist {
          margin: 0 0 8px 0;
          color: #666;
          font-size: 14px;
        }

        .item-price {
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 700;
          color: #2c3e50;
        }

        .item-added {
          margin: 0 0 16px 0;
          font-size: 12px;
          color: #999;
        }

        .item-actions {
          display: flex;
          flex-direction: column;
          gap: 8px;
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

        .btn-primary {
          background: #3498db;
          color: white;
        }

        .btn-primary:hover:not(:disabled) {
          background: #2980b9;
        }

        .btn-outline {
          background: transparent;
          border: 1px solid #ddd;
          color: #333;
        }

        .btn-outline:hover {
          background: #f8f9fa;
        }

        .empty-wishlist {
          text-align: center;
          padding: 64px 32px;
          color: #666;
        }

        .empty-wishlist svg {
          color: #ddd;
          margin-bottom: 16px;
        }

        .empty-wishlist h3 {
          margin: 0 0 8px 0;
          color: #2c3e50;
        }

        .empty-wishlist p {
          margin: 0 0 24px 0;
        }

        .loading-spinner {
          text-align: center;
          padding: 48px;
        }

        .item-detail {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 32px;
        }

        .item-detail-image img {
          width: 100%;
          border-radius: 8px;
        }

        .item-detail-info h3 {
          font-size: 24px;
          margin: 0 0 8px 0;
        }

        .item-detail-info .artist {
          color: #666;
          margin: 0 0 16px 0;
        }

        .item-detail-info .price {
          font-size: 28px;
          font-weight: 700;
          color: #2c3e50;
          margin: 0 0 16px 0;
        }

        .item-detail-info .description {
          line-height: 1.6;
          margin: 0 0 24px 0;
        }

        .detail-meta {
          margin-bottom: 24px;
          padding: 16px;
          background: #f8f9fa;
          border-radius: 8px;
        }

        .detail-meta p {
          margin: 0 0 8px 0;
          font-size: 14px;
        }

        .availability {
          margin-left: 8px;
          padding: 2px 8px;
          border-radius: 12px;
          font-size: 12px;
          font-weight: 500;
        }

        .availability.available {
          background: #d4edda;
          color: #155724;
        }

        .availability.out_of_stock {
          background: #f8d7da;
          color: #721c24;
        }

        .detail-actions {
          display: flex;
          gap: 12px;
        }

        @media (max-width: 768px) {
          .item-detail {
            grid-template-columns: 1fr;
          }
          
          .filter-controls {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
          }
          
          .header-actions {
            flex-direction: column;
            gap: 8px;
          }
          
          .detail-actions {
            flex-direction: column;
          }
        }
      `}</style>
    </div>
  );
};

export default WishlistManager;