import React, { useState, useEffect } from 'react';
import { LuShoppingCart, LuHeart, LuEye } from 'react-icons/lu';
import TrendingBadge from './TrendingBadge';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const TrendingProducts = ({ auth }) => {
  const [allProducts, setAllProducts] = useState([]);
  const [trendingProducts, setTrendingProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('trending'); // 'trending' or 'all'

  useEffect(() => {
    loadProducts();
  }, []);

  const loadProducts = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/customer/artworks.php`);
      const data = await res.json();
      
      if (data.status === 'success' && Array.isArray(data.artworks)) {
        // Store all products
        setAllProducts(data.artworks);
        
        // Filter trending products
        const trending = data.artworks.filter(product => 
          product.is_trending || 
          (product.recent_sales_count >= 50 || product.total_views >= 1000) &&
          product.average_rating >= 4.0 &&
          product.number_of_reviews >= 15
        );
        setTrendingProducts(trending);
      } else {
        setAllProducts([]);
        setTrendingProducts([]);
      }
    } catch (e) {
      console.error('Failed to load products:', e);
      setAllProducts([]);
      setTrendingProducts([]);
    } finally {
      setLoading(false);
    }
  };

  const addToCart = async (product) => {
    if (!auth?.user_id) {
      alert('Please log in to add items to cart');
      return;
    }
    
    try {
      const res = await fetch(`${API_BASE}/customer/cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-User-ID': auth.user_id },
        body: JSON.stringify({ artwork_id: product.id, quantity: 1 })
      });
      const data = await res.json();
      
      if (res.ok && data.status === 'success') {
        alert('Added to cart!');
      } else {
        alert(data.message || 'Failed to add to cart');
      }
    } catch (e) {
      alert('Failed to add to cart');
    }
  };

  const displayProducts = filter === 'trending' 
    ? trendingProducts 
    : allProducts; // Show all products when "All Gifts" is selected

  return (
    <section style={{ marginTop: 32 }}>
      <div style={{ maxWidth: 1200, margin: '0 auto', padding: '0 20px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
          <h3 style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 24, fontWeight: 700, color: '#1f2937' }}>
            Trending & Popular Gifts
            <span style={{ fontSize: 20 }}>📈</span>
          </h3>
          
          <div style={{ display: 'flex', gap: 8 }}>
            <button 
              onClick={() => setFilter('trending')}
              style={{
                padding: '8px 16px',
                borderRadius: '20px',
                border: 'none',
                background: filter === 'trending' ? '#3b82f6' : '#fff',
                color: filter === 'trending' ? '#fff' : '#374151',
                fontWeight: '600',
                fontSize: 14,
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: 6,
                boxShadow: filter === 'trending' ? '0 2px 8px rgba(59, 130, 246, 0.3)' : '0 1px 3px rgba(0,0,0,0.1)',
                transition: 'all 0.2s'
              }}
            >
              🔥 Trending
              {filter === 'trending' && <span style={{ 
                background: 'rgba(255,255,255,0.3)', 
                padding: '2px 8px', 
                borderRadius: '10px',
                fontSize: 12
              }}>{trendingProducts.length}</span>}
            </button>
            
            <button 
              onClick={() => setFilter('all')}
              style={{
                padding: '8px 16px',
                borderRadius: '20px',
                border: 'none',
                background: filter === 'all' ? '#3b82f6' : '#fff',
                color: filter === 'all' ? '#fff' : '#374151',
                fontWeight: '600',
                fontSize: 14,
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: 6,
                boxShadow: filter === 'all' ? '0 2px 8px rgba(59, 130, 246, 0.3)' : '0 1px 3px rgba(0,0,0,0.1)',
                transition: 'all 0.2s'
              }}
            >
              📦 All Gifts
              {filter === 'all' && <span style={{ 
                background: 'rgba(255,255,255,0.3)', 
                padding: '2px 8px', 
                borderRadius: '10px',
                fontSize: 12
              }}>{allProducts.length}</span>}
            </button>
          </div>
        </div>
        
        <div style={{ 
          background: '#fff', 
          borderRadius: 16, 
          padding: 20, 
          border: '1px solid #e5e7eb',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
        }}>
          {loading ? (
            <div style={{ textAlign: 'center', padding: 40 }}>
              <div>Loading trending products...</div>
            </div>
          ) : displayProducts.length === 0 ? (
            <div style={{ textAlign: 'center', padding: 40 }}>
              <div style={{ fontSize: 48, marginBottom: 16 }}>🔥</div>
              <div style={{ fontSize: 18, color: '#6b7280', marginBottom: 8 }}>
                No {filter === 'trending' ? 'trending' : ''} products found
              </div>
              <div style={{ fontSize: 14, color: '#9ca3af' }}>
                Check back later for popular gifts
              </div>
            </div>
          ) : (
            <div style={{ 
              display: 'grid', 
              gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', 
              gap: 20 
            }}>
              {displayProducts.slice(0, 8).map((product) => (
                <div key={product.id} style={{
                  border: '1px solid #e5e7eb',
                  borderRadius: 12,
                  overflow: 'hidden',
                  background: '#fff',
                  transition: 'all 0.3s',
                  cursor: 'pointer'
                }}
                onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-4px)'}
                onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}
                onClick={() => window.location.href = `/artwork/${product.id}`}
                >
                  <div style={{ position: 'relative' }}>
                    <img 
                      src={product.image_url} 
                      alt={product.title}
                      style={{ 
                        width: '100%', 
                        height: 220, 
                        objectFit: 'cover',
                        display: 'block'
                      }}
                    />
                    <TrendingBadge product={product} />
                  </div>
                  
                  <div style={{ padding: 12 }}>
                    <div style={{ fontSize: 16, fontWeight: '700', marginBottom: 4, color: '#1f2937' }}>
                      {product.title}
                    </div>
                    <div style={{ fontSize: 14, color: '#6b7280', marginBottom: 8 }}>
                      {product.artist_name || 'Store'}
                    </div>
                    
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <div style={{ fontSize: 20, fontWeight: '800', color: '#dc2626' }}>
                        ₹{product.price}
                      </div>
                      
                      <div style={{ display: 'flex', gap: 8 }}>
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            addToCart(product);
                          }}
                          style={{
                            background: '#3b82f6',
                            color: '#fff',
                            border: 'none',
                            borderRadius: '50%',
                            width: 36,
                            height: 36,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer'
                          }}
                        >
                          <LuShoppingCart size={18} />
                        </button>
                      </div>
                    </div>
                    
                    {(product.average_rating || product.number_of_reviews) && (
                      <div style={{ 
                        marginTop: 8, 
                        fontSize: 12, 
                        color: '#6b7280',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 4
                      }}>
                        ⭐ {product.average_rating || '0.0'} ({product.number_of_reviews || 0} reviews)
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  );
};

export default TrendingProducts;

