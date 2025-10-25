import React, { useState, useEffect } from 'react';
import { LuHeart, LuShoppingCart, LuEye, LuSettings, LuSearch, LuX, LuWand } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import EnhancedSearch from './EnhancedSearch';
import CustomizationModal from './CustomizationModal';
import Recommendations from './Recommendations';
import '../../styles/customization-modal.css';

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

const API_BASE = "http://localhost/my_little_thingz/backend/api";

// Frontend fallback categories and products if backend returns empty
const FALLBACK_CATEGORIES = [
  { id: 'polaroids', name: 'Polaroids' },
  { id: 'chocolates', name: 'Chocolates' },
  { id: 'frames', name: 'Frames' },
  { id: 'albums', name: 'Albums' },
  { id: 'birthday_theme_boxes', name: 'Birthday Theme Boxes' },
  { id: 'wedding_hampers', name: 'Wedding Hampers' },
  { id: 'gift_boxes', name: 'Gift Boxes' },
  { id: 'bouquets', name: 'Bouquets' }
];

// Exclude unwanted categories from UI regardless of backend data
const EXCLUDE_CATEGORY_IDS = new Set(['wedding_cards']);
const EXCLUDE_CATEGORY_NAMES = new Set(['wedding cards', 'wedding card']);
const sanitizeCategories = (list) => (
  Array.isArray(list)
    ? list.filter(cat => {
        const id = String(cat.id || '').toLowerCase().trim();
        const name = String(cat.name || '').toLowerCase().trim();
        return !EXCLUDE_CATEGORY_IDS.has(id) && !EXCLUDE_CATEGORY_NAMES.has(name);
      })
    : []
);

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

  // Birthday Theme Boxes
  { id: 'bt1', title: 'Birthday Theme Box', description: 'Curated birthday theme gift box', price: 350, image_url: giftBoxSetImg, category_id: 'birthday_theme_boxes', category_name: 'Birthday Theme Boxes', artist_name: 'Store' },

  // Hampers, Gift Boxes, Bouquets
  { id: 'w1', title: 'Wedding Hamper', description: 'Curated wedding gift hamper', price: 500, image_url: weddingHamperImg, category_id: 'wedding_hampers', category_name: 'Wedding Hampers', artist_name: 'Store' },
  { id: 'g1', title: 'Gift Box', description: 'Single gift box', price: 150, image_url: giftBoxImg, category_id: 'gift_boxes', category_name: 'Gift Boxes', artist_name: 'Store' },
  { id: 'g2', title: 'Gift Box Set', description: 'Premium gift box set', price: 300, image_url: giftBoxSetImg, category_id: 'gift_boxes', category_name: 'Gift Boxes', artist_name: 'Store' },
  { id: 'b1', title: 'Bouquets', description: 'Gift bouquet arrangement', price: 200, image_url: bouquetsImg, category_id: 'bouquets', category_name: 'Bouquets', artist_name: 'Store' }
];

const normalizeOfferEntries = (items) => {
  if (!Array.isArray(items)) return [];
  const now = Date.now();
  const parseTime = (value) => {
    if (!value) return null;
    if (typeof value === 'number') return Number.isFinite(value) ? value : null;
    const stringValue = String(value).trim();
    if (!stringValue || stringValue === '0000-00-00 00:00:00') return null;
    const normalized = stringValue.includes('T') ? stringValue : stringValue.replace(' ', 'T');
    const timestamp = Date.parse(normalized);
    if (Number.isNaN(timestamp)) {
      const fallback = new Date(stringValue).getTime();
      return Number.isFinite(fallback) ? fallback : null;
    }
    return timestamp;
  };

  return items.map((item) => {
    if (!item) return item;

    const startTime = parseTime(item.offer_starts_at);
    const endTime = parseTime(item.offer_ends_at);
    const forceOffer = Boolean(item.force_offer_badge);
    const offerStartsLater = startTime !== null && startTime > now;
    const offerExpired = endTime !== null && endTime < now;

    if (forceOffer && !offerExpired) {
      return {
        ...item,
        is_on_offer: true,
      };
    }

    if (!forceOffer && (offerStartsLater || offerExpired)) {
      return {
        ...item,
        is_on_offer: false,
        effective_price: null,
        offer_price: null,
        offer_percent: null,
        offer_starts_at: offerStartsLater ? item.offer_starts_at : null,
        offer_ends_at: null,
      };
    }

    return {
      ...item,
      is_on_offer: Boolean(item.is_on_offer) && !offerStartsLater && !offerExpired,
    };
  });
};

const parsePriceValue = (value) => {
  if (value == null) return NaN;
  if (typeof value === 'number') return Number.isFinite(value) ? value : NaN;
  const cleaned = String(value).replace(/[^0-9.\-]/g, '');
  if (!cleaned) return NaN;
  const parsed = parseFloat(cleaned);
  return Number.isFinite(parsed) ? parsed : NaN;
};

const formatCurrency = (amount) => `₹${amount.toFixed(2)}`;

const ensureCurrencyText = (raw) => {
  const text = String(raw ?? '').trim();
  if (!text) return '₹0.00';
  return text.includes('₹') ? text : `₹${text}`;
};

const ArtworkGallery = ({ onClose, onOpenWishlist, onOpenCart }) => {
  const { auth } = useAuth();
  const navigate = useNavigate();
  const [artworks, setArtworks] = useState([]);
  const [filteredArtworks, setFilteredArtworks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedArtwork, setSelectedArtwork] = useState(null);
  const [showCustomizationModal, setShowCustomizationModal] = useState(false);
  const [customizationArtwork, setCustomizationArtwork] = useState(null);
  const [filters, setFilters] = useState({
    category: '',
    search: '',
    minPrice: '',
    maxPrice: '',
    sort: 'price-asc',
    priceChip: '', // e.g., 'lte-100', 'lte-200', 'lte-300', 'lte-400', 'gte-500'
    tier: '' // New: Budget/Premium filter
  });
  const [searchSuggestions, setSearchSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [categories, setCategories] = useState([]);
  const [wishlist, setWishlist] = useState([]);
  const [showEnhancedSearch, setShowEnhancedSearch] = useState(false);
  const [mlSearchResults, setMlSearchResults] = useState([]);
  const [mlInsights, setMlInsights] = useState(null);

  useEffect(() => {
    fetchArtworks();
    fetchCategories();
    fetchWishlist();
    // Periodic refresh to pick up price/offer changes
    const intervalId = setInterval(() => {
      fetchArtworks();
    }, 30000);
    // Refresh when tab regains focus or becomes visible
    const onFocus = () => fetchArtworks();
    const onVisibility = () => { if (document.visibilityState === 'visible') fetchArtworks(); };
    window.addEventListener('focus', onFocus);
    document.addEventListener('visibilitychange', onVisibility);
    return () => {
      clearInterval(intervalId);
      window.removeEventListener('focus', onFocus);
      document.removeEventListener('visibilitychange', onVisibility);
    };
  }, []);

  useEffect(() => {
    applyFilters();
  }, [artworks, filters]);

  const fetchArtworks = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/artworks.php`);
      const data = await response.json();
      if (data.status === 'success' && Array.isArray(data.artworks) && data.artworks.length > 0) {
        const normalized = normalizeOfferEntries(data.artworks);
        setArtworks(normalized);
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
        setCategories(sanitizeCategories(data.categories));
      } else {
        setCategories(sanitizeCategories(FALLBACK_CATEGORIES));
      }
    } catch (error) {
      console.warn('Using fallback categories due to fetch error:', error);
      setCategories(sanitizeCategories(FALLBACK_CATEGORIES));
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

  const fetchSearchSuggestions = async (searchTerm) => {
    if (searchTerm.length < 2) {
      setSearchSuggestions([]);
      setShowSuggestions(false);
      return;
    }

    try {
      // Use enhanced search API for better suggestions
      const response = await fetch(`${API_BASE}/customer/enhanced-search.php?action=suggestions&term=${encodeURIComponent(searchTerm)}`);
      const data = await response.json();
      if (data.status === 'success') {
        setSearchSuggestions(data.data.suggestions || []);
        setShowSuggestions(true);
      }
    } catch (error) {
      console.error('Error fetching search suggestions:', error);
      // Fallback to original API
      try {
        const response = await fetch(`${API_BASE}/customer/search-keywords.php?action=suggestions&term=${encodeURIComponent(searchTerm)}`);
        const data = await response.json();
        if (data.status === 'success') {
          setSearchSuggestions(data.suggestions || []);
          setShowSuggestions(true);
        }
      } catch (fallbackError) {
        console.error('Fallback search suggestions error:', fallbackError);
      }
    }
  };

  const applyFilters = () => {
    // Normalize price to a number (handles strings like "1,234.00" or "₹1,234.00")
    const getPriceNumber = (item) => {
      const raw = item?.price;
      if (typeof raw === 'number') return raw;
      if (raw == null) return NaN;
      const cleaned = String(raw).replace(/[^0-9.]/g, ''); // drop currency symbols and commas
      const val = parseFloat(cleaned);
      return Number.isFinite(val) ? val : NaN;
    };

    // Derive tier when backend hasn't sent category_tier
    const getTier = (item) => {
      if (item && item.category_tier) return item.category_tier;
      const price = getPriceNumber(item);
      if (!Number.isFinite(price)) return '';
      return price >= 1000 ? 'Premium' : 'Budget';
    };

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
        matches(artwork.category_name) ||
        matches(artwork.artist_name)
      );
    }

    if (filters.category) {
      filtered = filtered.filter(artwork => String(artwork.category_id) === String(filters.category));
    }

    // Tier filter (Budget/Premium) with fallback derivation
    if (filters.tier) {
      filtered = filtered.filter(artwork => getTier(artwork) === filters.tier);
    }

    // Quick chips
    if (filters.priceChip) {
      filtered = filtered.filter(a => {
        const price = getPriceNumber(a);
        if (Number.isNaN(price)) return false;
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
        const price = getPriceNumber(a);
        if (Number.isNaN(price)) return false;
        if (min !== null && price < min) return false;
        if (max !== null && price > max) return false;
        return true;
      });
    }

    // Sorting
    if (filters.sort === 'price-asc') {
      filtered.sort((a, b) => {
        const ap = getPriceNumber(a);
        const bp = getPriceNumber(b);
        if (Number.isNaN(ap) && Number.isNaN(bp)) return 0;
        if (Number.isNaN(ap)) return 1;
        if (Number.isNaN(bp)) return -1;
        return ap - bp;
      });
    } else if (filters.sort === 'price-desc') {
      filtered.sort((a, b) => {
        const ap = getPriceNumber(a);
        const bp = getPriceNumber(b);
        if (Number.isNaN(ap) && Number.isNaN(bp)) return 0;
        if (Number.isNaN(ap)) return 1;
        if (Number.isNaN(bp)) return -1;
        return bp - ap;
      });
    }

    setFilteredArtworks(filtered);
  };

  const toggleWishlist = async (artworkId) => {
    // Always navigate to Wishlist as per requirement, regardless of API result
    if (typeof onClose === 'function') onClose();
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

  const handleCustomizationRequest = (artwork) => {
    setCustomizationArtwork(artwork);
    setShowCustomizationModal(true);
  };

  const handleCustomizationSuccess = () => {
    window.dispatchEvent(new CustomEvent('toast', { 
      detail: { 
        type: 'success', 
        message: 'Customization request submitted! Admin will review and approve before payment.' 
      } 
    }));
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
        // Close gallery modal if open and navigate to cart page
        if (typeof onClose === 'function') onClose();
        if (typeof onOpenCart === 'function') {
          // Use existing drawer if provided by dashboard
          onOpenCart();
        } else {
          // Fallback: navigate to dedicated cart page
          navigate('/cart');
        }
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Added to cart' } }));
      } else {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: data.message || 'Failed to add to cart' } }));
      }
    } catch (e) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error adding to cart' } }));
    }
  };

  const handleMLSearchResults = (results, insights) => {
    setMlSearchResults(results);
    setMlInsights(insights);
    setShowEnhancedSearch(false);
    
    // Update the main search with ML results
    if (results.length > 0) {
      setFilteredArtworks(results);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'success', 
          message: `Found ${results.length} AI-enhanced results!` 
        } 
      }));
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
    <div className="modal-overlay artwork-gallery-modal">
      <div className="modal-content extra-large">
        <div className="modal-header">
          <h2>Artwork Gallery</h2>
          <button className="btn-close" onClick={onClose} title="Close Gallery">
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
                placeholder="Search products... (try 'sweet', 'wedding', 'birthday')"
                value={filters.search}
                onChange={(e) => {
                  const value = e.target.value;
                  setFilters(prev => ({ ...prev, search: value }));
                  fetchSearchSuggestions(value);
                }}
                onFocus={() => {
                  if (searchSuggestions.length > 0) {
                    setShowSuggestions(true);
                  }
                }}
                onBlur={() => {
                  // Delay hiding suggestions to allow clicking on them
                  setTimeout(() => setShowSuggestions(false), 200);
                }}
              />
              <button 
                className="enhanced-search-btn"
                onClick={() => setShowEnhancedSearch(true)}
                title="AI Enhanced Search"
              >
                <LuWand />
              </button>
              {showSuggestions && searchSuggestions.length > 0 && (
                <div className="search-suggestions">
                  {searchSuggestions.map((suggestion, index) => (
                    <div
                      key={index}
                      className="suggestion-item"
                      onClick={() => {
                        setFilters(prev => ({ ...prev, search: suggestion }));
                        setShowSuggestions(false);
                      }}
                    >
                      {suggestion}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          <div className="filter-group">
            <select
              value={filters.category}
              onChange={(e) => setFilters(prev => ({ ...prev, category: e.target.value }))}
            >
              <option value="">Select Category</option>
              {categories.map(category => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>

          {/* Tier Filter */}
          <div className="filter-group">
            <select
              value={filters.tier}
              onChange={(e) => setFilters(prev => ({ ...prev, tier: e.target.value }))}
            >
              <option value="">All Tiers</option>
              <option value="Budget">🏷️ Budget</option>
              <option value="Premium">💎 Premium</option>
            </select>
          </div>



          {/* Price dropdown */}
          <div className="filter-group">
            <select
              value={filters.priceChip}
              onChange={(e) => setFilters(p => ({...p, priceChip: e.target.value, minPrice: '', maxPrice: ''}))}
            >
              <option value="">All Prices</option>
              <option value="lte-100">≤ ₹100</option>
              <option value="lte-200">≤ ₹200</option>
              <option value="lte-300">≤ ₹300</option>
              <option value="lte-400">≤ ₹400</option>
              <option value="lte-500">≤ ₹500</option>
              <option value="lte-1000">≤ ₹1000</option>
              <option value="lte-2000">≤ ₹2000</option>
              <option value="lte-3000">≤ ₹3000</option>
              <option value="lte-5000">≤ ₹5000</option>
              <option value="gte-1000">≥ ₹1000</option>
              <option value="gte-2000">≥ ₹2000</option>
              <option value="gte-3000">≥ ₹3000</option>
              <option value="gte-5000">≥ ₹5000</option>
            </select>
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
          {filteredArtworks.length > 0 && (
            <div style={{
              gridColumn: '1 / -1',
              background: 'linear-gradient(135deg, #93c5fd, #60a5fa)',
              color: 'white',
              padding: '10px',
              textAlign: 'center',
              fontWeight: 'bold',
              marginBottom: '20px',
              borderRadius: '8px',
              boxShadow: '0 4px 12px rgba(147, 197, 253, 0.5)'
            }}>
              🎨 ARTWORK GALLERY - {filteredArtworks.length} ITEMS AVAILABLE
            </div>
          )}
          {filteredArtworks.map(artwork => {
            const artistName = String(artwork.artist_name ?? artwork.artist ?? artwork.artistName ?? '').trim();
            const hasArtist = Boolean(artistName);
            return (
              <div key={artwork.id} className="artwork-card">
                {/* PRODUCT NAME DISPLAY - ALWAYS VISIBLE */}
                <div style={{
                  position: 'absolute',
                  top: '0',
                  left: '0',
                  right: '0',
                  background: 'linear-gradient(135deg, #93c5fd, #60a5fa)',
                  color: 'white',
                  padding: '5px',
                  textAlign: 'center',
                  fontSize: '14px',
                  fontWeight: 'bold',
                  zIndex: 1000,
                  borderRadius: '12px 12px 0 0',
                  boxShadow: '0 2px 8px rgba(147, 197, 253, 0.5)'
                }}>
                  {artwork.title}
                </div>

                {/* PREMIUM TIER LABEL */}
                {artwork.category_tier === 'Premium' && (
                  <div style={{
                    position: 'absolute',
                    top: '35px',
                    right: '8px',
                    background: 'linear-gradient(135deg, #fbbf24, #f59e0b)',
                    color: 'white',
                    padding: '4px 8px',
                    borderRadius: '12px',
                    fontSize: '10px',
                    fontWeight: 'bold',
                    zIndex: 1001,
                    boxShadow: '0 2px 4px rgba(251, 191, 36, 0.4)',
                    textTransform: 'uppercase',
                    letterSpacing: '0.5px'
                  }}>
                    💎 Premium
                  </div>
                )}
                
                <div className="artwork-image">
                  <img 
                    src={artwork.image_url || '/api/placeholder/300/300'} 
                    alt={artwork.title}
                    onClick={() => setSelectedArtwork(artwork)}
                  />
                  {artwork.is_on_offer && (
                    <>
                      <div className="offer-ribbon">OFFER</div>
                    </>
                  )}
                  
                  {/* Price Display - Always Visible */}
                  <div 
                    style={{
                      position: 'absolute',
                      bottom: '8px',
                      left: '8px',
                      right: '8px',
                      background: 'rgba(255, 255, 255, 0.95)',
                      border: '2px solid #93c5fd',
                      borderRadius: '8px',
                      padding: '8px 12px',
                      textAlign: 'center',
                      fontSize: '18px',
                      fontWeight: '800',
                      color: '#1e40af',
                      zIndex: 10,
                      boxShadow: '0 2px 8px rgba(147, 197, 253, 0.5)',
                      backdropFilter: 'blur(4px)'
                    }}
                    data-debug="price-overlay"
                  >
                    {(() => {
                      console.log('Rendering price for:', artwork.title, 'Price:', artwork.price);
                      return ensureCurrencyText(artwork.price);
                    })()}
                  </div>

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
                      onClick={() => handleCustomizationRequest(artwork)}
                      title="Request Customization"
                    >
                      <LuWand />
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
                  <h3>{artwork.title}</h3>
                  {hasArtist && <span className="artwork-artist">by {artistName}</span>}
                  {!hasArtist && artwork.category_name && <span className="artwork-category">{artwork.category_name}</span>}
                  
                  {/* Backup Price Display in Info Section */}
                  <div style={{
                    margin: '8px 0 0 0',
                    padding: '8px',
                    fontSize: '16px',
                    fontWeight: '700',
                    color: '#1e40af',
                    background: '#f8fafc',
                    border: '2px solid #93c5fd',
                    borderRadius: '6px',
                    textAlign: 'center',
                    boxShadow: '0 2px 4px rgba(147, 197, 253, 0.4)'
                  }}>
                    Price: {ensureCurrencyText(artwork.price)}
                  </div>
                </div>
              </div>
            );
          })}
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
                <button className="btn-close" onClick={() => setSelectedArtwork(null)} title="Close Details">
                  <LuX />
                </button>
              </div>
              <div className="artwork-detail">
                <div className="artwork-detail-image">
                  <img 
                    src={selectedArtwork.image_url || '/api/placeholder/400/400'} 
                    alt={selectedArtwork.title}
                  />
                  {selectedArtwork?.is_on_offer && (
                    <>
                      <div className="offer-ribbon modal">OFFER</div>
                    </>
                  )}
                </div>
                <div className="artwork-detail-info">
                  <h3>{selectedArtwork.title}</h3>
                  <p className="artist">by {selectedArtwork.artist_name}</p>
                  <div className="price">
                    {(() => {
                      const base = parsePriceValue(selectedArtwork?.price);
                      const effCandidate =
                        selectedArtwork?.effective_price ??
                        selectedArtwork?.offer_price ??
                        (base > 0 && selectedArtwork?.offer_percent != null && selectedArtwork?.offer_percent !== ''
                          ? base * (1 - (parseFloat(selectedArtwork.offer_percent) || 0) / 100)
                          : null);
                      const eff = parsePriceValue(effCandidate);
                      const showOffer = base > 0 && Number.isFinite(eff) && eff < base;
                      if (!showOffer) return <>{ensureCurrencyText(selectedArtwork?.price)}</>;
                      const pct = Math.round(((base - eff) / base) * 100);
                      return (
                        <>
                          <div style={{ lineHeight: 1 }}>
                            <span style={{ textDecoration: 'line-through', color: '#9ca3af' }}>{formatCurrency(base)}</span>
                          </div>
                          <div style={{ lineHeight: 1.3, marginTop: 6, display: 'flex', alignItems: 'center', gap: 10 }}>
                            <span style={{ color: '#1e40af', fontWeight: 900, fontSize: 28 }}>{formatCurrency(eff)}</span>
                            <span style={{ color: '#16a34a', fontWeight: 800, fontSize: 16 }}>-{pct}%</span>
                          </div>
                        </>
                      );
                    })()}
                  </div>
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

                  {/* Recommendations Section (stacked vertically) */}
                  <div style={{display:'block'}}>
                    <Recommendations
                      artworkId={selectedArtwork.id}
                      title="Similar to this"
                      limit={4}
                      onCustomizationRequest={handleCustomizationRequest}
                    />
                    <Recommendations
                      title="Recommended For You"
                      limit={4}
                      onCustomizationRequest={handleCustomizationRequest}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Customization Modal */}
        <CustomizationModal
          artwork={customizationArtwork}
          isOpen={showCustomizationModal}
          onClose={() => setShowCustomizationModal(false)}
          onSuccess={handleCustomizationSuccess}
        />
      </div>

      <style>{`
        /* Reset any conflicting styles */
        .artwork-gallery-modal * {
          box-sizing: border-box;
        }

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
          background: linear-gradient(135deg, #f8fafc, #e2e8f0);
          border: 1px solid #cbd5e1;
          font-size: 16px;
          cursor: pointer;
          padding: 6px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
          color: #475569;
          transition: all 0.2s ease;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-close:hover {
          background: linear-gradient(135deg, #93c5fd, #60a5fa);
          border-color: #3b82f6;
          color: white;
          transform: scale(1.05);
          box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-close:active {
          transform: scale(0.95);
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

        .search-suggestions {
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          background: white;
          border: 1px solid #ddd;
          border-top: none;
          border-radius: 0 0 6px 6px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
          z-index: 1000;
          max-height: 200px;
          overflow-y: auto;
        }

        .suggestion-item {
          padding: 10px 12px;
          cursor: pointer;
          border-bottom: 1px solid #f0f0f0;
          transition: background-color 0.2s;
        }

        .suggestion-item:hover {
          background-color: #f8f9fa;
        }

        .suggestion-item:last-child {
          border-bottom: none;
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
          auto-rows: auto;
        }

        .artwork-card {
          border: 2px solid #e5e7eb;
          border-radius: 12px;
          overflow: hidden;
          transition: transform 0.2s, box-shadow 0.2s;
          display: flex;
          flex-direction: column;
          background: white;
          position: relative;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          min-height: 350px;
        }

        .artwork-card:hover {
          transform: translateY(-4px);
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .artwork-image {
          position: relative;
          aspect-ratio: 1;
          overflow: hidden;
          flex-shrink: 0;
        }

        .artwork-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          cursor: pointer;
        }

        /* Price Overlay - Always Visible on Image - Using inline styles instead */

        /* Corner ribbon shown when item is on offer */
        .offer-ribbon {
          position: absolute;
          top: 12px;
          left: -40px;
          background: linear-gradient(135deg, #93c5fd, #60a5fa);
          color: #fff;
          padding: 6px 50px;
          font-size: 12px;
          font-weight: 800;
          letter-spacing: 0.5px;
          transform: rotate(-45deg);
          box-shadow: 0 2px 6px rgba(147, 197, 253, 0.5);
          pointer-events: none;
          text-transform: uppercase;
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

        /* ISOLATED ARTWORK INFO STYLES - NO CONFLICTS */
        .artwork-card .artwork-info {
          padding: 16px;
          background: white;
          flex-grow: 1;
          display: flex;
          flex-direction: column;
          gap: 8px;
          position: relative;
          z-index: 1;
          border-top: 1px solid #eee;
          min-height: 120px;
        }

        .artwork-card .artwork-info h3 {
          margin: 0;
          font-size: 18px;
          font-weight: 700;
          color: #1a202c;
          line-height: 1.3;
          text-align: left;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }

        .artwork-card .artwork-artist {
          font-size: 13px;
          color: #334155;
          text-align: left;
          font-weight: 500;
        }

        .artwork-card .artwork-category {
          font-size: 12px;
          color: #5a6c7d;
          text-transform: uppercase;
          font-weight: 600;
          letter-spacing: 0.5px;
          text-align: left;
        }

        /* CRITICAL PRICE STYLES - MAXIMUM SPECIFICITY */
        .artwork-card .artwork-info .artwork-price {
          display: block !important;
          visibility: visible !important;
          opacity: 1 !important;
          margin: 12px 0 0 0 !important;
          padding: 12px !important;
          font-size: 22px !important;
          font-weight: 900 !important;
          color: #1e40af !important;
          background: #fff3f3 !important;
          border: 3px solid #93c5fd !important;
          border-radius: 8px !important;
          text-align: center !important;
          position: relative !important;
          z-index: 9999 !important;
          box-shadow: 0 2px 4px rgba(225, 29, 72, 0.2) !important;
        }

        .artwork-card .artwork-info .artwork-price span {
          display: inline !important;
          visibility: visible !important;
          opacity: 1 !important;
          color: #1e40af !important;
          font-size: 22px !important;
          font-weight: 900 !important;
        }

        /* ADDITIONAL PRICE OVERRIDES */
        .artwork-price {
          display: block !important;
          visibility: visible !important;
          opacity: 1 !important;
          margin: 12px 0 0 0 !important;
          padding: 12px !important;
          font-size: 22px !important;
          font-weight: 900 !important;
          color: #1e40af !important;
          background: #fff3f3 !important;
          border: 3px solid #93c5fd !important;
          border-radius: 8px !important;
          text-align: center !important;
          position: relative !important;
          z-index: 9999 !important;
        }

        /* FORCE ALL PRICE ELEMENTS TO BE VISIBLE */
        div[class*="artwork-price"] {
          display: block !important;
          visibility: visible !important;
          opacity: 1 !important;
          margin: 12px 0 0 0 !important;
          padding: 12px !important;
          font-size: 22px !important;
          font-weight: 900 !important;
          color: #1e40af !important;
          background: #fff3f3 !important;
          border: 3px solid #93c5fd !important;
          border-radius: 8px !important;
          text-align: center !important;
          position: relative !important;
          z-index: 9999 !important;
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

        .enhanced-search-btn {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          border: none;
          padding: 8px 12px;
          border-radius: 6px;
          cursor: pointer;
          transition: all 0.3s ease;
          margin-left: 8px;
        }

        .enhanced-search-btn:hover {
          transform: translateY(-1px);
          box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .enhanced-search-modal .modal-content {
          max-width: 90vw;
          max-height: 90vh;
          overflow-y: auto;
        }
        }
      `}</style>

      {/* Enhanced Search Modal */}
      {showEnhancedSearch && (
        <div className="modal-overlay enhanced-search-modal">
          <div className="modal-content extra-large">
            <EnhancedSearch 
              onSearchResults={handleMLSearchResults}
              onClose={() => setShowEnhancedSearch(false)}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default ArtworkGallery;