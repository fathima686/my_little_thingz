import React, { useState, useEffect } from 'react';
import { LuHeart, LuShoppingCart, LuEye, LuSettings, LuSearch, LuX } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

// Asset image imports for fallback data
import polaroidImg from '../../assets/poloroid.png';
import polaroidPackImg from '../../assets/poloroid (2).png';
import customChocolateImg from '../../assets/custom chocolate.png';
import weddingHamperImg from '../../assets/Wedding hamper.jpg';
import giftBoxImg from '../../assets/gift box.png';
import giftBoxSetImg from '../../assets/gift box set.png';
import bouquetsImg from '../../assets/boaqutes.png';
import albumImg from '../../assets/album.png';
import drawingsImg from '../../assets/drawings.png';
// New frame assets
import frameA3Img from '../../assets/A3 frame.png';
import frame44Img from '../../assets/4 4 frame.png';
import frame44AltImg from '../../assets/4 4 frame (2).png';
import frame64Img from '../../assets/6 4 frame.png';
import frameMicroImg from '../../assets/micro frame.png';
import frameMiniImg from '../../assets/mini frame.png';
import weddingCardImg from '../../assets/wedding card.png';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

// Frontend fallback categories and products if backend returns empty
const FALLBACK_CATEGORIES = [
  { id: 'polaroids', name: 'Polaroids' },
  { id: 'chocolates', name: 'Chocolates' },
  { id: 'frames', name: 'Frames' },
  { id: 'albums', name: 'Albums' },
  { id: 'wedding_cards', name: 'Wedding Cards' },
  { id: 'birthday_theme_boxes', name: 'Birthday Theme Boxes' },
  { id: 'wedding_hampers', name: 'Wedding Hampers' },
  { id: 'gift_boxes', name: 'Gift Boxes' },
  { id: 'bouquets', name: 'Bouquets' }
];

const FALLBACK_ARTWORKS = [
  // Polaroids
  { id: 'p1', title: 'Polaroids (Single Page)', description: 'Polaroid print – per page', price: 5, image_url: polaroidImg, category_id: 'polaroids', category_name: 'Polaroids', artist_name: 'Store' },
  { id: 'p2', title: 'Polaroids Pack', description: 'Pack of polaroid prints', price: 100, image_url: polaroidPackImg, category_id: 'polaroids', category_name: 'Polaroids', artist_name: 'Store' },

  // Chocolates
  { id: 'c1', title: 'Custom Chocolate', description: 'Personalized chocolate with name', price: 30, image_url: customChocolateImg, category_id: 'chocolates', category_name: 'Chocolates', artist_name: 'Store' },

  // Frames (variety and sizes)
  { id: 'fA4', title: 'Photo Frame A4', description: 'A4 size frame', price: 250, image_url: albumImg, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },
  { id: 'fA3', title: 'Photo Frame A3', description: 'A3 size frame', price: 450, image_url: frameA3Img, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },
  { id: 'f44', title: 'Photo Frame 4×4', description: '4×4 inches square frame', price: 120, image_url: frame44Img, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },
  { id: 'f44b', title: 'Photo Frame 4×4 (Style B)', description: '4×4 square frame alternate design', price: 140, image_url: frame44AltImg, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },
  { id: 'f64', title: 'Photo Frame 6×4', description: '6×4 inches frame', price: 160, image_url: frame64Img, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },
  { id: 'fMicro', title: 'Micro Frame', description: 'Miniature micro frame', price: 90, image_url: frameMicroImg, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },
  { id: 'fMini', title: 'Mini Frame', description: 'Compact mini frame', price: 110, image_url: frameMiniImg, category_id: 'frames', category_name: 'Frames', artist_name: 'Store' },

  // Albums
  { id: 'a1', title: 'Photo Album', description: 'Handmade photo album', price: 400, image_url: albumImg, category_id: 'albums', category_name: 'Albums', artist_name: 'Store' },

  // Wedding Cards
  { id: 'wc1', title: 'Wedding Card Classic', description: 'Classic themed invitation', price: 50, image_url: weddingCardImg, category_id: 'wedding_cards', category_name: 'Wedding Cards', artist_name: 'Store' },

  // Birthday Theme Boxes
  { id: 'bt1', title: 'Birthday Theme Box', description: 'Curated birthday theme gift box', price: 350, image_url: giftBoxSetImg, category_id: 'birthday_theme_boxes', category_name: 'Birthday Theme Boxes', artist_name: 'Store' },

  // Hampers, Gift Boxes, Bouquets
  { id: 'w1', title: 'Wedding Hamper', description: 'Curated wedding gift hamper', price: 500, image_url: weddingHamperImg, category_id: 'wedding_hampers', category_name: 'Wedding Hampers', artist_name: 'Store' },
  { id: 'g1', title: 'Gift Box', description: 'Single gift box', price: 150, image_url: giftBoxImg, category_id: 'gift_boxes', category_name: 'Gift Boxes', artist_name: 'Store' },
  { id: 'g2', title: 'Gift Box Set', description: 'Premium gift box set', price: 300, image_url: giftBoxSetImg, category_id: 'gift_boxes', category_name: 'Gift Boxes', artist_name: 'Store' },
  { id: 'b1', title: 'Bouquets', description: 'Gift bouquet arrangement', price: 200, image_url: bouquetsImg, category_id: 'bouquets', category_name: 'Bouquets', artist_name: 'Store' }
];

const ArtworkGallery = ({ onClose, onOpenWishlist }) => {
  const { auth } = useAuth();
  const [artworks, setArtworks] = useState([]);
  const [filteredArtworks, setFilteredArtworks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedArtwork, setSelectedArtwork] = useState(null);
  const [filters, setFilters] = useState({
    category: '',
    search: '',
    minPrice: '',
    maxPrice: '',
    sort: 'price-asc',
    priceChip: '' // e.g., 'lte-100', 'lte-200', 'lte-300', 'lte-400', 'gte-500'
  });
  const [categories, setCategories] = useState([]);
  const [wishlist, setWishlist] = useState([]);

  useEffect(() => {
    fetchArtworks();
    fetchCategories();
    fetchWishlist();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [artworks, filters]);

  const fetchArtworks = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/artworks.php`);
      const data = await response.json();
      if (data.status === 'success' && Array.isArray(data.artworks) && data.artworks.length > 0) {
        setArtworks(data.artworks);
      } else {
        // Fallback to local products
        setArtworks(FALLBACK_ARTWORKS);
      }
    } catch (error) {
      console.warn('Using fallback artworks due to fetch error:', error);
      setArtworks(FALLBACK_ARTWORKS);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/categories.php`);
      const data = await response.json();
      if (data.status === 'success' && Array.isArray(data.categories) && data.categories.length > 0) {
        setCategories(data.categories);
      } else {
        setCategories(FALLBACK_CATEGORIES);
      }
    } catch (error) {
      console.warn('Using fallback categories due to fetch error:', error);
      setCategories(FALLBACK_CATEGORIES);
    }
  };

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
        // Store wishlist IDs as strings to avoid 1 vs "1" mismatches
        setWishlist(data.wishlist?.map(item => String(item.artwork_id)) || []);
      }
    } catch (error) {
      console.error('Error fetching wishlist:', error);
    }
  };

  const applyFilters = () => {
    let filtered = [...artworks];

    if (filters.search) {
      // normalize common typos and size formats (e.g., poloroid -> polaroid, 4*4 -> 4x4)
      const normalize = (s) => s
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase()
        .replace('poloroid','polaroid')
        .replace(/(\d)\s*[x*]\s*(\d)/g, '$1x$2');
      const q = normalize(filters.search);
      const matches = (text) => normalize(String(text || '')).includes(q);
      filtered = filtered.filter(artwork => 
        matches(artwork.title) ||
        matches(artwork.description) ||
        matches(artwork.category_name)
      );
    }

    if (filters.category) {
      filtered = filtered.filter(artwork => String(artwork.category_id) === String(filters.category));
    }

    // Quick chips
    if (filters.priceChip) {
      filtered = filtered.filter(a => {
        const price = parseFloat(a.price);
        switch (filters.priceChip) {
          case 'lte-100': return price <= 100;
          case 'lte-200': return price <= 200;
          case 'lte-300': return price <= 300;
          case 'lte-400': return price <= 400;
          case 'lte-500': return price <= 500;
          case 'lte-1000': return price <= 1000;
          case 'lte-2000': return price <= 2000;
          case 'lte-3000': return price <= 3000;
          case 'lte-5000': return price <= 5000;
          case 'gte-500': return price >= 500;
          case 'gte-1000': return price >= 1000;
          case 'gte-2000': return price >= 2000;
          case 'gte-3000': return price >= 3000;
          case 'gte-5000': return price >= 5000;
          default: return true;
        }
      });
    }

    // Min/Max inputs override chips if set
    const min = filters.minPrice !== '' ? parseFloat(filters.minPrice) : null;
    const max = filters.maxPrice !== '' ? parseFloat(filters.maxPrice) : null;
    if (min !== null || max !== null) {
      filtered = filtered.filter(a => {
        const price = parseFloat(a.price);
        if (Number.isNaN(price)) return false;
        if (min !== null && price < min) return false;
        if (max !== null && price > max) return false;
        return true;
      });
    }

    // Sorting
    if (filters.sort === 'price-asc') {
      filtered.sort((a,b) => parseFloat(a.price) - parseFloat(b.price));
    } else if (filters.sort === 'price-desc') {
      filtered.sort((a,b) => parseFloat(b.price) - parseFloat(a.price));
    }

    setFilteredArtworks(filtered);
  };

  const toggleWishlist = async (artworkId) => {
    // Always navigate to Wishlist as per requirement, regardless of API result
    if (typeof onOpenWishlist === 'function') {
      onOpenWishlist();
    }

    try {
      const idStr = String(artworkId);
      const isInWishlist = wishlist.includes(idStr);
      const method = isInWishlist ? 'DELETE' : 'POST';

      // Optimistic local state update
      if (isInWishlist) {
        setWishlist(prev => prev.filter(id => id !== idStr));
      } else {
        setWishlist(prev => [...prev, idStr]);
      }
      
      const userIdQs = auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : '';
      const response = await fetch(`${API_BASE}/customer/wishlist.php${userIdQs}`, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        },
        body: JSON.stringify({ artwork_id: idStr })
      });

      const data = await response.json();
      if (data.status !== 'success') {
        // Revert optimistic update on failure
        if (isInWishlist) {
          setWishlist(prev => [...prev, idStr]);
        } else {
          setWishlist(prev => prev.filter(id => id !== idStr));
        }
      }
    } catch (error) {
      console.error('Error updating wishlist:', error);
      // Revert on error
      const idStr = String(artworkId);
      const wasIn = wishlist.includes(idStr);
      if (!wasIn) {
        setWishlist(prev => prev.filter(id => id !== idStr));
      } else {
        setWishlist(prev => [...prev, idStr]);
      }
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

  if (loading) {
    return (
      <div className="modal-overlay">
        <div className="modal-content large">
          <div className="loading-spinner">Loading artworks...</div>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay">
      <div className="modal-content extra-large">
        <div className="modal-header">
          <h2>Artwork Gallery</h2>
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
        </div>

        {/* Filters */}
        <div className="gallery-filters">
          <div className="filter-group">
            <div className="search-box">
              <LuSearch />
              <input
                type="text"
                placeholder="Search products..."
                value={filters.search}
                onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
              />
            </div>
          </div>

          <div className="filter-group">
            <select
              value={filters.category}
              onChange={(e) => setFilters(prev => ({ ...prev, category: e.target.value }))}
            >
              <option value="">All Categories</option>
              {categories.map(category => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>

          {/* Category chips */}
          <div className="filter-group chips">
            <button className={`chip ${filters.category === '' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, category: ''}))}>All</button>
            {categories.map(cat => (
              <button
                key={cat.id}
                className={`chip ${String(cat.id) === String(filters.category) ? 'active' : ''}`}
                onClick={() => setFilters(p => ({...p, category: String(cat.id)}))}
              >
                {cat.name}
              </button>
            ))}
          </div>

          {/* Price chips */}
          <div className="filter-group chips">
            <button className={`chip ${filters.priceChip === 'lte-100' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-100' ? '' : 'lte-100', minPrice: '', maxPrice: ''}))}>≤ ₹100</button>
            <button className={`chip ${filters.priceChip === 'lte-200' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-200' ? '' : 'lte-200', minPrice: '', maxPrice: ''}))}>≤ ₹200</button>
            <button className={`chip ${filters.priceChip === 'lte-300' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-300' ? '' : 'lte-300', minPrice: '', maxPrice: ''}))}>≤ ₹300</button>
            <button className={`chip ${filters.priceChip === 'lte-400' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-400' ? '' : 'lte-400', minPrice: '', maxPrice: ''}))}>≤ ₹400</button>
            <button className={`chip ${filters.priceChip === 'lte-500' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-500' ? '' : 'lte-500', minPrice: '', maxPrice: ''}))}>≤ ₹500</button>
            <button className={`chip ${filters.priceChip === 'lte-1000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-1000' ? '' : 'lte-1000', minPrice: '', maxPrice: ''}))}>≤ ₹1000</button>
            <button className={`chip ${filters.priceChip === 'lte-2000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-2000' ? '' : 'lte-2000', minPrice: '', maxPrice: ''}))}>≤ ₹2000</button>
            <button className={`chip ${filters.priceChip === 'lte-3000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-3000' ? '' : 'lte-3000', minPrice: '', maxPrice: ''}))}>≤ ₹3000</button>
            <button className={`chip ${filters.priceChip === 'lte-5000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'lte-5000' ? '' : 'lte-5000', minPrice: '', maxPrice: ''}))}>≤ ₹5000</button>
            <button className={`chip ${filters.priceChip === 'gte-1000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'gte-1000' ? '' : 'gte-1000', minPrice: '', maxPrice: ''}))}>≥ ₹1000</button>
            <button className={`chip ${filters.priceChip === 'gte-2000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'gte-2000' ? '' : 'gte-2000', minPrice: '', maxPrice: ''}))}>≥ ₹2000</button>
            <button className={`chip ${filters.priceChip === 'gte-3000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'gte-3000' ? '' : 'gte-3000', minPrice: '', maxPrice: ''}))}>≥ ₹3000</button>
            <button className={`chip ${filters.priceChip === 'gte-5000' ? 'active' : ''}`} onClick={() => setFilters(p => ({...p, priceChip: p.priceChip === 'gte-5000' ? '' : 'gte-5000', minPrice: '', maxPrice: ''}))}>≥ ₹5000</button>
          </div>

          {/* Custom Min/Max */}
          <div className="filter-group">
            <div className="minmax">
              <input type="number" placeholder="Min ₹" value={filters.minPrice} onChange={(e) => setFilters(p => ({...p, minPrice: e.target.value, priceChip: ''}))} />
              <span>—</span>
              <input type="number" placeholder="Max ₹" value={filters.maxPrice} onChange={(e) => setFilters(p => ({...p, maxPrice: e.target.value, priceChip: ''}))} />
            </div>
          </div>

          {/* Sort */}
          <div className="filter-group">
            <select value={filters.sort} onChange={(e) => setFilters(p => ({...p, sort: e.target.value}))}>
              <option value="price-asc">Price: Low → High</option>
              <option value="price-desc">Price: High → Low</option>
            </select>
          </div>
        </div>

        {/* Gallery Grid */}
        <div className="artwork-grid">
          {filteredArtworks.map(artwork => (
            <div key={artwork.id} className="artwork-card">
              <div className="artwork-image">
                <img 
                  src={artwork.image_url || '/api/placeholder/300/300'} 
                  alt={artwork.title}
                  onClick={() => setSelectedArtwork(artwork)}
                />
                <div className="artwork-overlay">
                  <button 
                    className="btn-icon"
                    onClick={() => setSelectedArtwork(artwork)}
                    title="View Details"
                  >
                    <LuEye />
                  </button>
                  <button 
                    className={`btn-icon ${wishlist.includes(String(artwork.id)) ? 'active' : ''}`}
                    onClick={() => toggleWishlist(artwork.id)}
                    title="Add to Wishlist"
                  >
                    <LuHeart />
                  </button>
                  <button 
                    className="btn-icon"
                    onClick={() => addToCart(artwork.id)}
                    title="Add to Cart"
                  >
                    <LuShoppingCart />
                  </button>
                </div>
              </div>
              <div className="artwork-info">
                <div className="row1">
                  <h3>{artwork.title}</h3>
                  {artwork.category_name && <span className="badge">{artwork.category_name}</span>}
                </div>
                <p className="artwork-artist">by {artwork.artist_name}</p>
                <p className="artwork-price">₹{artwork.price}</p>
              </div>
            </div>
          ))}
        </div>

        {filteredArtworks.length === 0 && (
          <div className="empty-state">
            <p>No artworks found matching your criteria.</p>
          </div>
        )}

        {/* Artwork Detail Modal */}
        {selectedArtwork && (
          <div className="modal-overlay">
            <div className="modal-content large">
              <div className="modal-header">
                <h2>{selectedArtwork.title}</h2>
                <button className="btn-close" onClick={() => setSelectedArtwork(null)}>
                  <LuX />
                </button>
              </div>
              <div className="artwork-detail">
                <div className="artwork-detail-image">
                  <img 
                    src={selectedArtwork.image_url || '/api/placeholder/400/400'} 
                    alt={selectedArtwork.title}
                  />
                </div>
                <div className="artwork-detail-info">
                  <h3>{selectedArtwork.title}</h3>
                  <p className="artist">by {selectedArtwork.artist_name}</p>
                  <p className="price">₹{selectedArtwork.price}</p>
                  <p className="description">{selectedArtwork.description}</p>
                  
                  <div className="artwork-actions">
                    <button 
                      className={`btn ${wishlist.includes(String(selectedArtwork.id)) ? 'btn-secondary' : 'btn-outline'}`}
                      onClick={() => toggleWishlist(selectedArtwork.id)}
                    >
                      <LuHeart /> {wishlist.includes(String(selectedArtwork.id)) ? 'Remove from Wishlist' : 'Add to Wishlist'}
                    </button>
                    <button 
                      className="btn btn-primary"
                      onClick={() => addToCart(selectedArtwork.id)}
                    >
                      <LuShoppingCart /> Add to Cart
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

        .gallery-filters {
          display: flex;
          gap: 16px;
          margin-bottom: 24px;
          flex-wrap: wrap;
        }

        .filter-group {
          position: relative;
        }

        .filter-group.chips {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          align-items: center;
        }

        .chip {
          padding: 8px 12px;
          border: 1px solid #ddd;
          border-radius: 999px;
          background: white;
          cursor: pointer;
        }

        .chip.active {
          background: #2563eb;
          border-color: #2563eb;
          color: #fff;
        }

        .minmax {
          display: inline-flex;
          gap: 8px;
          align-items: center;
        }

        .minmax input {
          width: 110px;
          padding: 8px 12px;
          border: 1px solid #ddd;
          border-radius: 6px;
        }

        .search-box {
          position: relative;
          display: flex;
          align-items: center;
        }

        .search-box svg {
          position: absolute;
          left: 12px;
          color: #666;
          z-index: 1;
        }

        .search-box input {
          padding: 8px 12px 8px 40px;
          border: 1px solid #ddd;
          border-radius: 6px;
          width: 250px;
        }

        .filter-group select {
          padding: 8px 12px;
          border: 1px solid #ddd;
          border-radius: 6px;
          background: white;
        }

        .artwork-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
          gap: 24px;
        }

        .artwork-card {
          border: 1px solid #eee;
          border-radius: 12px;
          overflow: hidden;
          transition: transform 0.2s, box-shadow 0.2s;
        }

        .artwork-card:hover {
          transform: translateY(-4px);
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .artwork-image {
          position: relative;
          aspect-ratio: 1;
          overflow: hidden;
        }

        .artwork-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          cursor: pointer;
        }

        .artwork-overlay {
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

        .artwork-card:hover .artwork-overlay {
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

        .btn-icon.active {
          background: #e74c3c;
          color: white;
        }

        .artwork-info {
          padding: 16px;
        }

        .artwork-info h3 {
          margin: 0 0 4px 0;
          font-size: 16px;
          font-weight: 600;
        }

        .artwork-info .row1 {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 8px;
        }

        .badge {
          font-size: 12px;
          padding: 2px 8px;
          border-radius: 999px;
          background: #eaf3ff;
          color: #2563eb;
          border: 1px solid #dbeafe;
          white-space: nowrap;
        }

        .artwork-artist {
          margin: 0 0 8px 0;
          color: #666;
          font-size: 14px;
        }

        .artwork-price {
          margin: 0;
          font-size: 18px;
          font-weight: 700;
          color: #2c3e50;
        }

        .artwork-detail {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 32px;
        }

        .artwork-detail-image img {
          width: 100%;
          border-radius: 8px;
        }

        .artwork-detail-info h3 {
          font-size: 24px;
          margin: 0 0 8px 0;
        }

        .artwork-detail-info .artist {
          color: #666;
          margin: 0 0 16px 0;
        }

        .artwork-detail-info .price {
          font-size: 28px;
          font-weight: 700;
          color: #2c3e50;
          margin: 0 0 16px 0;
        }

        .artwork-detail-info .description {
          line-height: 1.6;
          margin: 0 0 24px 0;
        }

        .artwork-actions {
          display: flex;
          gap: 12px;
        }

        .btn {
          padding: 12px 24px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 500;
          transition: all 0.2s;
        }

        .btn-primary {
          background: #3498db;
          color: white;
        }

        .btn-primary:hover {
          background: #2980b9;
        }

        .btn-secondary {
          background: #e74c3c;
          color: white;
        }

        .btn-outline {
          background: transparent;
          border: 1px solid #ddd;
          color: #333;
        }

        .btn-outline:hover {
          background: #f8f9fa;
        }

        .empty-state {
          text-align: center;
          padding: 48px;
          color: #666;
        }

        .loading-spinner {
          text-align: center;
          padding: 48px;
        }

        @media (max-width: 768px) {
          .artwork-detail {
            grid-template-columns: 1fr;
          }
          
          .gallery-filters {
            flex-direction: column;
          }
          
          .search-box input {
            width: 100%;
          }
        }
      `}</style>
    </div>
  );
};

export default ArtworkGallery;