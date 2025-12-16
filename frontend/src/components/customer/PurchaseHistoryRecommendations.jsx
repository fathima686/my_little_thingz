import React, { useState, useEffect } from "react";
import { LuHeart, LuShoppingCart, LuWand, LuEye } from "react-icons/lu";

const PurchaseHistoryRecommendations = ({ 
  userId, 
  title = "💝 Just for You", 
  limit = 8, 
  showAddToCart = true, 
  showWishlist = true, 
  showAnalysis = false, 
  onCustomizationRequest 
}) => {
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [wishlist, setWishlist] = useState([]);

  // Load wishlist
  useEffect(() => {
    if (!userId) return;
    
    const loadWishlist = async () => {
      try {
        const response = await fetch(`http://localhost/my_little_thingz/backend/api/customer/wishlist.php?user_id=${encodeURIComponent(userId)}`);
        const data = await response.json();
        if (data.status === 'success') {
          setWishlist(data.wishlist?.map(item => String(item.artwork_id || item.id)) || []);
        }
      } catch (error) {
        console.error('Error loading wishlist:', error);
      }
    };
    
    loadWishlist();
  }, [userId]);

  // Load recommendations
  useEffect(() => {
    if (!userId) {
      setLoading(false);
      return;
    }

    const loadRecommendations = async () => {
      try {
        setLoading(true);
        
        // First, get user's purchase history
        const ordersResponse = await fetch(`http://localhost/my_little_thingz/backend/api/customer/orders.php?user_id=${encodeURIComponent(userId)}`);
        const ordersData = await ordersResponse.json();
        
        let purchasedCategories = [];
        let purchasedArtists = [];
        
        if (ordersData.status === 'success' && ordersData.orders) {
          // Extract categories and artists from purchase history
          ordersData.orders.forEach(order => {
            if (order.items) {
              order.items.forEach(item => {
                if (item.category_name) purchasedCategories.push(item.category_name);
                if (item.artist_name) purchasedArtists.push(item.artist_name);
              });
            }
          });
        }
        
        // Get all artworks
        const artworksResponse = await fetch('http://localhost/my_little_thingz/backend/api/customer/artworks.php');
        const artworksData = await artworksResponse.json();
        
        if (artworksData.status === 'success' && artworksData.artworks) {
          let recommended = artworksData.artworks.filter(artwork => {
            // Exclude already purchased items
            const isPurchased = ordersData.orders?.some(order => 
              order.items?.some(item => item.artwork_id === artwork.id)
            );
            return !isPurchased;
          });
          
          // Score recommendations based on purchase history
          recommended = recommended.map(artwork => {
            let score = 0;
            
            // Higher score for same categories
            if (purchasedCategories.includes(artwork.category_name)) {
              score += 3;
            }
            
            // Higher score for same artists
            if (purchasedArtists.includes(artwork.artist_name)) {
              score += 5;
            }
            
            // Premium classification bonus
            if (artwork.category_tier === 'Premium') {
              score += 4; // Premium items get higher priority
            }
            
            // Bonus for popular items (lower price = more accessible)
            if (artwork.price && parseFloat(artwork.price) < 100) {
              score += 1;
            }
            
            // Bonus for items on offer
            if (artwork.is_on_offer) {
              score += 2;
            }
            
            return { ...artwork, recommendationScore: score };
          });
          
          // Sort by recommendation score and limit results
          recommended = recommended
            .sort((a, b) => b.recommendationScore - a.recommendationScore)
            .slice(0, limit);
          
          setRecommendations(recommended);
        }
      } catch (error) {
        console.error('Error loading recommendations:', error);
        setRecommendations([]);
      } finally {
        setLoading(false);
      }
    };
    
    loadRecommendations();
  }, [userId, limit]);

  const toggleWishlist = async (artworkId) => {
    if (!userId) return;
    
    try {
      const isInWishlist = wishlist.includes(String(artworkId));
      const action = isInWishlist ? 'remove' : 'add';
      
      const response = await fetch('http://localhost/my_little_thingz/backend/api/customer/wishlist.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': userId
        },
        body: JSON.stringify({
          artwork_id: artworkId,
          action: action
        })
      });
      
      if (response.ok) {
        if (isInWishlist) {
          setWishlist(prev => prev.filter(id => id !== String(artworkId)));
        } else {
          setWishlist(prev => [...prev, String(artworkId)]);
        }
        
        // Show toast notification
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'success', 
            message: isInWishlist ? 'Removed from wishlist' : 'Added to wishlist' 
          } 
        }));
      }
    } catch (error) {
      console.error('Error updating wishlist:', error);
    }
  };

  const addToCart = async (artworkId) => {
    if (!userId) return;
    
    try {
      const response = await fetch('http://localhost/my_little_thingz/backend/api/customer/cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': userId
        },
        body: JSON.stringify({
          artwork_id: artworkId,
          quantity: 1
        })
      });
      
      if (response.ok) {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'success', 
            message: 'Added to cart successfully!' 
          } 
        }));
      }
    } catch (error) {
      console.error('Error adding to cart:', error);
    }
  };

  const ensureCurrencyText = (raw) => {
    const text = String(raw ?? '').trim();
    if (!text) return '₹0.00';
    return text.includes('₹') ? text : `₹${text}`;
  };

  if (loading) {
    return (
      <div className="recommendations-widget">
        <h3>{title}</h3>
        <div style={{ textAlign: 'center', padding: '20px' }}>
          <div style={{ fontSize: '14px', color: '#666' }}>Loading personalized recommendations...</div>
        </div>
      </div>
    );
  }

  if (recommendations.length === 0) {
    return (
      <div className="recommendations-widget">
        <h3>{title}</h3>
        <div style={{ textAlign: 'center', padding: '20px' }}>
          <div style={{ fontSize: '14px', color: '#666' }}>
            {userId ? 'No recommendations available yet. Start shopping to get personalized suggestions!' : 'Please log in to see personalized recommendations.'}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="recommendations-widget">
      <h3>{title}</h3>
      <div className="recommendations-grid" style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
        gap: '16px',
        marginTop: '16px'
      }}>
        {recommendations.map(artwork => (
          <div key={artwork.id} className="recommendation-card" style={{
            border: '1px solid #e5e7eb',
            borderRadius: '12px',
            overflow: 'hidden',
            background: '#fff',
            transition: 'all 0.2s ease',
            cursor: 'pointer'
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.transform = 'translateY(-2px)';
            e.currentTarget.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.transform = 'translateY(0)';
            e.currentTarget.style.boxShadow = 'none';
          }}>
            <div style={{ position: 'relative' }}>
              <img 
                src={artwork.image_url || '/api/placeholder/200/200'} 
                alt={artwork.title}
                style={{
                  width: '100%',
                  height: '150px',
                  objectFit: 'cover',
                  display: 'block'
                }}
              />
              
              {/* Recommendation Badge */}
              {artwork.recommendationScore > 0 && (
                <div style={{
                  position: 'absolute',
                  top: '8px',
                  right: '8px',
                  background: 'linear-gradient(135deg, #93c5fd, #60a5fa)',
                  color: 'white',
                  padding: '4px 8px',
                  borderRadius: '12px',
                  fontSize: '10px',
                  fontWeight: 'bold',
                  boxShadow: '0 2px 4px rgba(0, 0, 0, 0.2)'
                }}>
                  RECOMMENDED
                </div>
              )}

              {/* Premium Tier Badge */}
              {artwork.category_tier === 'Premium' && (
                <div style={{
                  position: 'absolute',
                  top: '8px',
                  left: '8px',
                  background: 'linear-gradient(135deg, #fbbf24, #f59e0b)',
                  color: 'white',
                  padding: '4px 8px',
                  borderRadius: '12px',
                  fontSize: '10px',
                  fontWeight: 'bold',
                  boxShadow: '0 2px 4px rgba(251, 191, 36, 0.4)',
                  textTransform: 'uppercase',
                  letterSpacing: '0.5px'
                }}>
                  💎 Premium
                </div>
              )}
              
              {/* Action Buttons */}
              <div style={{
                position: 'absolute',
                top: '8px',
                left: '8px',
                display: 'flex',
                gap: '4px',
                opacity: 0,
                transition: 'opacity 0.2s ease'
              }}
              onMouseEnter={(e) => e.currentTarget.style.opacity = '1'}
              onMouseLeave={(e) => e.currentTarget.style.opacity = '0'}>
                <button 
                  style={{
                    background: 'rgba(255, 255, 255, 0.9)',
                    border: 'none',
                    borderRadius: '50%',
                    width: '28px',
                    height: '28px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    fontSize: '12px',
                    color: '#3b82f6',
                    boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
                  }}
                  onClick={(e) => {
                    e.stopPropagation();
                    toggleWishlist(artwork.id);
                  }}
                  title="Add to Wishlist"
                >
                  <LuHeart style={{ 
                    color: wishlist.includes(String(artwork.id)) ? '#ef4444' : '#3b82f6' 
                  }} />
                </button>
                
                {showAddToCart && (
                  <button 
                    style={{
                      background: 'rgba(255, 255, 255, 0.9)',
                      border: 'none',
                      borderRadius: '50%',
                      width: '28px',
                      height: '28px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      cursor: 'pointer',
                      fontSize: '12px',
                      color: '#3b82f6',
                      boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
                    }}
                    onClick={(e) => {
                      e.stopPropagation();
                      addToCart(artwork.id);
                    }}
                    title="Add to Cart"
                  >
                    <LuShoppingCart />
                  </button>
                )}
                
                <button 
                  style={{
                    background: 'rgba(255, 255, 255, 0.9)',
                    border: 'none',
                    borderRadius: '50%',
                    width: '28px',
                    height: '28px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    fontSize: '12px',
                    color: '#3b82f6',
                    boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
                  }}
                  onClick={(e) => {
                    e.stopPropagation();
                    if (onCustomizationRequest) {
                      onCustomizationRequest(artwork);
                    }
                  }}
                  title="Request Customization"
                >
                  <LuWand />
                </button>
              </div>
            </div>
            
            <div style={{ padding: '12px' }}>
              <h4 style={{
                margin: '0 0 4px 0',
                fontSize: '14px',
                fontWeight: '600',
                color: '#1f2937',
                lineHeight: '1.3'
              }}>
                {artwork.title}
              </h4>
              
              {artwork.artist_name && (
                <p style={{
                  margin: '0 0 8px 0',
                  fontSize: '12px',
                  color: '#6b7280'
                }}>
                  by {artwork.artist_name}
                </p>
              )}
              
              <div style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}>
                <span style={{
                  fontSize: '16px',
                  fontWeight: '700',
                  color: '#1e40af'
                }}>
                  {ensureCurrencyText(artwork.price)}
                </span>
                
                {artwork.is_on_offer && (
                  <span style={{
                    fontSize: '10px',
                    background: '#fef3c7',
                    color: '#d97706',
                    padding: '2px 6px',
                    borderRadius: '4px',
                    fontWeight: '600'
                  }}>
                    OFFER
                  </span>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default PurchaseHistoryRecommendations;