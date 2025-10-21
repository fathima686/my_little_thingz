import React, { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  LuGift, LuHeart, LuPackage, LuUser, LuLogOut, LuSearch, LuShoppingBag, LuWand
} from "react-icons/lu";
import { useAuth } from "../contexts/AuthContext";

import ArtworkGallery from "../components/customer/ArtworkGallery";
import CustomGiftRequest from "../components/customer/CustomGiftRequest";
import OrderTracking from "../components/customer/OrderTracking";
const CustomRequestStatus = React.lazy(() => import("../components/customer/CustomRequestStatus"));
import WishlistManager from "../components/customer/WishlistManager";
import CartDrawer from "../components/customer/CartDrawer";
import CustomizationModal from "../components/customer/CustomizationModal";
import PurchaseHistoryRecommendations from "../components/customer/PurchaseHistoryRecommendations";
// import Recommendations from "../components/customer/Recommendations";

import logo from "../assets/logo.png";
import poloroid2 from "../assets/poloroid (2).png";
import weddingHamper from "../assets/Wedding hamper.jpg";
import customChocolate from "../assets/custom chocolate.png";
import "../styles/dashboard.css";

// Simple offers strip for dashboard (fetches active offers and displays banners)
// onSelect(offer) opens the clicked offer in full view
const OffersStrip = ({ onSelect }) => {
  const [offers, setOffers] = React.useState([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const res = await fetch("http://localhost/my_little_thingz/backend/api/customer/offers-promos.php");
        const data = await res.json();
        if (!alive) return;
        if (res.ok && data.status === 'success') {
          const list = data.offers || [];
          setOffers(list);
          // Auto-select first offer for full-view if consumer wants it
          if (onSelect && list.length > 0) {
            onSelect(list[0]);
          }
        }
      } catch {}
      finally { if (alive) setLoading(false); }
    })();
    return () => { alive = false; };
  }, []);

  if (loading || offers.length === 0) return null;

  return (
    <section className="offers-strip" aria-label="Promotional Offers">
      <div className="container offers-scroller">
        {offers.map((o) => (
          <div key={o.id} className="offer-card" onClick={() => onSelect?.(o)}>
            <img src={o.image_url} alt={o.title || 'Offer banner'} />
          </div>
        ))}
      </div>
    </section>
  );
};

const parsePriceValue = (value) => {
  if (value == null) return NaN;
  if (typeof value === 'number') return Number.isFinite(value) ? value : NaN;
  const cleaned = String(value).replace(/[^0-9.\-]/g, '');
  if (!cleaned) return NaN;
  const parsed = parseFloat(cleaned);
  return Number.isFinite(parsed) ? parsed : NaN;
};

const ensureCurrencyText = (raw) => {
  const text = String(raw ?? '').trim();
  if (!text) return '₹0.00';
  return text.includes('₹') ? text : `₹${text}`;
};

const formatCurrency = (amount) => `₹${amount.toFixed(2)}`;

const formatPriceDisplay = ({ basePrice, currencyText }) => {
  if (Number.isFinite(basePrice) && basePrice > 0) {
    return formatCurrency(basePrice);
  }
  const fallback = parsePriceValue(currencyText);
  if (Number.isFinite(fallback) && fallback > 0) {
    return formatCurrency(fallback);
  }
  return ensureCurrencyText(currencyText);
};

const parseTimestamp = (value) => {
  if (value == null) return null;
  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : null;
  }
  const stringValue = String(value).trim();
  if (!stringValue || stringValue === '0000-00-00 00:00:00') return null;
  const normalized = stringValue.includes('T') ? stringValue : stringValue.replace(' ', 'T');
  const fromParse = Date.parse(normalized);
  if (!Number.isNaN(fromParse)) {
    return fromParse;
  }
  const fromDate = new Date(stringValue).getTime();
  return Number.isFinite(fromDate) ? fromDate : null;
};

const isWithinOfferWindow = (item) => {
  const now = Date.now();
  const start = parseTimestamp(item?.offer_starts_at);
  const end = parseTimestamp(item?.offer_ends_at);
  const started = start == null || start <= now;
  const notEnded = end == null || end >= now;
  return started && notEnded;
};

const computeOfferPricing = (item) => {
  const basePrice = parsePriceValue(item?.price);
  const offerCandidate =
    item?.effective_price ??
    item?.offer_price ??
    (Number.isFinite(basePrice) && basePrice > 0 && item?.offer_percent != null && item.offer_percent !== ''
      ? basePrice * (1 - (parseFloat(item.offer_percent) || 0) / 100)
      : null);
  const effectivePrice = parsePriceValue(offerCandidate);
  const hasDiscount =
    Number.isFinite(basePrice) &&
    Number.isFinite(effectivePrice) &&
    effectivePrice > 0 &&
    effectivePrice < basePrice;

  return { basePrice, effectivePrice, hasDiscount };
};

const isOfferCurrentlyActive = (item) => {
  if (!isWithinOfferWindow(item)) return false;
  const { hasDiscount } = computeOfferPricing(item);
  return hasDiscount || Boolean(item?.force_offer_badge);
};

const renderPriceBlock = ({ basePrice, offerPrice, currencyText }) => {
  const baseValid = Number.isFinite(basePrice) && basePrice > 0;
  const offerValid = Number.isFinite(offerPrice) && offerPrice < basePrice;

  if (!baseValid || !offerValid) {
    return <span>{formatPriceDisplay({ basePrice, currencyText })}</span>;
  }

  const percent = Math.round(((basePrice - offerPrice) / basePrice) * 100);
  return (
    <>
      <div style={{ lineHeight: 1 }}>
        <span style={{ textDecoration: 'line-through', color: '#9ca3af', fontWeight: 600 }}>{formatCurrency(basePrice)}</span>
      </div>
      <div style={{ lineHeight: 1.3, marginTop: 4 }}>
        <span style={{ color: '#c2410c', fontWeight: 800 }}>{formatCurrency(offerPrice)}</span>
        <span style={{ color: '#16a34a', fontWeight: 700, fontSize: 13, marginLeft: 6 }}>-{percent}%</span>
      </div>
    </>
  );
};

export default function CustomerDashboard() {
  const { auth, logout, isLoading } = useAuth();
  const navigate = useNavigate();
  const [activeModal, setActiveModal] = useState(null);
  const [profileImageUrl, setProfileImageUrl] = useState(null);
  const [cartOpen, setCartOpen] = useState(false);
  const [showCustomizationModal, setShowCustomizationModal] = useState(false);
  const [customizationArtwork, setCustomizationArtwork] = useState(null);
  const [fullOffer, setFullOffer] = useState(null); // selected offer for full image view
  // On-offer artworks for OFFERS modal
  const [offerArtworks, setOfferArtworks] = useState([]);
  const [offerItemsLoading, setOfferItemsLoading] = useState(false);
  // Recent orders state
  const [recentOrders, setRecentOrders] = useState([]);
  const [ordersLoading, setOrdersLoading] = useState(false);

  // Auto-open Orders modal when redirected with query ?show=orders
  useEffect(() => {
    try {
      const params = new URLSearchParams(window.location.search);
      if (params.get('show') === 'orders') {
        setActiveModal('orders');
        // Clean the query param from URL without reload
        const url = new URL(window.location.href);
        url.searchParams.delete('show');
        window.history.replaceState({}, '', url.toString());
      }
    } catch {}
  }, []);

  const handleLogout = () => {
    logout();
  };

  // Load profile image on mount
  useEffect(() => {
    const loadProfile = async () => {
      try {
        const userIdQs = auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : '';
        const res = await fetch(`http://localhost/my_little_thingz/backend/api/customer/profile.php${userIdQs}`, {
          headers: { 'X-User-ID': auth?.user_id }
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          setProfileImageUrl(data.profile?.profile_image_url || null);
        }
      } catch {}
    };
    loadProfile();
  }, [auth?.user_id]);

  // Load recent orders for the logged-in user
  useEffect(() => {
    const loadOrders = async () => {
      if (!auth?.user_id) return;
      setOrdersLoading(true);
      try {
        const userIdQs = `?user_id=${encodeURIComponent(auth.user_id)}`;
        const res = await fetch(`http://localhost/my_little_thingz/backend/api/customer/orders.php${userIdQs}`, {
          headers: { 'X-User-ID': auth.user_id }
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          // Keep only the latest 2 orders for the widget
          const sorted = (data.orders || []).sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
          setRecentOrders(sorted.slice(0, 2));
        } else {
          setRecentOrders([]);
        }
      } catch (e) {
        setRecentOrders([]);
      } finally {
        setOrdersLoading(false);
      }
    };
    loadOrders();
  }, [auth?.user_id]);

  const openModal = (modalType) => {
    setActiveModal(modalType);
    if (modalType === 'offers') {
      // Fetch artworks and filter on-offer for the modal grid
      setOfferItemsLoading(true);
      fetch('http://localhost/my_little_thingz/backend/api/customer/artworks.php')
        .then(r => r.json())
        .then(data => {
          if (data?.status === 'success' && Array.isArray(data.artworks)) {
            const items = data.artworks.filter((item) => {
              if (!isOfferCurrentlyActive(item)) {
                return false;
              }
              const { basePrice, effectivePrice, hasDiscount } = computeOfferPricing(item);
              return hasDiscount || Boolean(item?.force_offer_badge);
            });
            setOfferArtworks(items);
          } else {
            setOfferArtworks([]);
          }
        })
        .catch(() => setOfferArtworks([]))
        .finally(() => setOfferItemsLoading(false));
    }
  };

  const closeModal = () => {
    setActiveModal(null);
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

  if (isLoading) {
    return (
      <div className="dash-page">
        <header className="dash-header">
          <div className="container dash-header-inner">
            <div className="brand">
              <img src={logo} alt="My Little Thingz" className="brand-logo" />
              <span className="brand-name">My Little Thingz</span>
            </div>
          </div>
        </header>
        <section className="dash-hero">
          <div className="container">
            <div className="hero-card">
              <div className="hero-copy">
                <h1>Loading your dashboard…</h1>
                <p>Please wait</p>
              </div>
            </div>
          </div>
        </section>
      </div>
    );
  }

  return (
    <div className="dash-page">
      {/* Header */}
      <header className="dash-header">
        <div className="container dash-header-inner">
          <div className="brand">
            <img src={logo} alt="My Little Thingz" className="brand-logo" />
            <span className="brand-name">My Little Thingz</span>
          </div>

          {/* Offers button (replaces search) */}
          <button type="button" className="nav-item offers-btn" onClick={() => openModal('offers')}>
            OFFERS
          </button>

          {/* Right actions */}
          <div className="dash-actions-right">
            <button type="button" className="nav-item" onClick={() => openModal('gallery')}><LuGift /> Browse</button>
            <button type="button" className="nav-item" onClick={() => navigate('/cart')}><LuPackage /> Cart</button>
            <button type="button" className="nav-item" onClick={() => openModal('orders')}><LuPackage /> Orders</button>
            <button type="button" className="nav-item" onClick={() => openModal('custom-requests')}><LuPackage /> Requests</button>
            <button type="button" className="nav-item" onClick={() => openModal('wishlist')}><LuHeart /> Wishlist</button>
            <button type="button" className="nav-item" onClick={() => navigate('/profile')}><LuUser /> Profile</button>
            <button type="button" className="btn btn-soft small" onClick={handleLogout}><LuLogOut /> Logout</button>
            {profileImageUrl ? (
              <img src={profileImageUrl} alt="You" className="avatar" title="You" onClick={() => navigate('/profile')} style={{ width: 36, height: 36, borderRadius: '50%', objectFit: 'cover' }} />
            ) : (
              <span className="avatar" title="You" onClick={() => navigate('/profile')}>{(auth?.user_id ?? "U").toString().slice(-2)}</span>
            )}
          </div>
        </div>
      </header>

      {/* Hero Banner */}
      <section className="dash-hero">
        <div className="container">
          <div className="hero-card">
            <div className="decor">
              <span className="blob b1" />
              <span className="blob b2" />
            </div>
            <div className="hero-copy">
              <h1>Welcome back!</h1>
              <p>Discover unique, handcrafted treasures just for you</p>
              <div style={{display:'flex', gap:10, flexWrap:'wrap'}}>
                <button className="btn btn-primary" onClick={() => openModal('custom-request')}><span>＋</span> Custom Request</button>
                <button className="btn btn-soft" onClick={() => openModal('gallery')}>Browse Catalog</button>
              </div>
            </div>
            <div className="hero-mark" aria-hidden>
              <LuGift />
            </div>
          </div>
        </div>
      </section>

      {/* Quick Actions */}
      <section className="dash-actions">
        <div className="container grid actions-grid">
          <button type="button" className="action-card" onClick={() => openModal('gallery')}>
            <div className="action-icon"><LuSearch /></div>
            <h3>Browse Catalog</h3>
            <p>Explore our curated collection</p>
          </button>
          <button type="button" className="action-card" onClick={() => openModal('custom-request')}>
            <div className="action-icon"><LuGift /></div>
            <h3>Custom Request</h3>
            <p>Request something special</p>
          </button>
          <button type="button" className="action-card" onClick={() => openModal('orders')}>
            <div className="action-icon"><LuShoppingBag /></div>
            <h3>Track Orders</h3>
            <p>Monitor your purchases</p>
          </button>
        </div>
      </section>

      {/* Recommendations removed from dashboard as requested */}

      {/* Two-column widgets */}
      <section className="dash-widgets">
        <div className="container grid widgets-grid">
          <article className="widget">
            <header className="widget-head">
              <h4><LuHeart /> For You</h4>
              <button className="btn btn-soft tiny" onClick={() => openModal('gallery')}>View All</button>
            </header>
            <div className="widget-body">
              <div className="for-you">
                <div className="product-card" onClick={() => handleCustomizationRequest({
                  id: 'polaroids-pack',
                  title: 'Polaroids Pack',
                  description: 'Vintage instant prints for special moments',
                  price: 100,
                  image_url: poloroid2
                })}>
                  <div className="product-thumb">
                    <img src={poloroid2} alt="Polaroids" />
                    <div className="product-overlay">
                      <button className="btn-icon" title="Request Customization">
                        <LuWand />
                      </button>
                    </div>
                  </div>
                  <div className="product-body">
                    <p className="product-title">Polaroids Pack</p>
                    <p className="product-sub">Vintage • Instant prints</p>
                  </div>
                </div>
                <div className="product-card" onClick={() => handleCustomizationRequest({
                  id: 'wedding-hamper',
                  title: 'Wedding Hamper',
                  description: 'Curated celebration set for special occasions',
                  price: 150,
                  image_url: weddingHamper
                })}>
                  <div className="product-thumb">
                    <img src={weddingHamper} alt="Wedding Hamper" />
                    <div className="product-overlay">
                      <button className="btn-icon" title="Request Customization">
                        <LuWand />
                      </button>
                    </div>
                  </div>
                  <div className="product-body">
                    <p className="product-title">Wedding Hamper</p>
                    <p className="product-sub">Curated • Celebration set</p>
                  </div>
                </div>
                <div className="product-card" onClick={() => handleCustomizationRequest({
                  id: 'custom-chocolate',
                  title: 'Custom Chocolate',
                  description: 'Personalized chocolate with custom designs',
                  price: 30,
                  image_url: customChocolate
                })}>
                  <div className="product-thumb">
                    <img src={customChocolate} alt="Custom Chocolate" />
                    <div className="product-overlay">
                      <button className="btn-icon" title="Request Customization">
                        <LuWand />
                      </button>
                    </div>
                  </div>
                  <div className="product-body">
                    <p className="product-title">Custom Chocolate</p>
                    <p className="product-sub">Personalized • Sweet gift</p>
                  </div>
                </div>
              </div>
            </div>
          </article>

          <article className="widget">
            <header className="widget-head">
              <h4><LuPackage /> Recent Orders</h4>
              <button className="btn btn-soft tiny" onClick={() => openModal('orders')}>View All</button>
            </header>
            <div className="widget-body">
              {ordersLoading && (
                <div className="order-item"><div className="order-title">Loading…</div></div>
              )}
              {!ordersLoading && recentOrders.length === 0 && (
                <div className="order-item">
                  <div className="order-title">No recent orders</div>
                  <p style={{ fontSize: '13px', color: '#6b7280', margin: '4px 0 0 0' }}>
                    Start shopping to see your orders here
                  </p>
                </div>
              )}
              {!ordersLoading && recentOrders.map((o) => {
                // Derive a display name using first item or order number
                const firstItem = (o.items && o.items[0]) || null;
                const title = firstItem?.name || `Order #${o.order_number}`;
                const date = new Date(o.created_at);
                const dateLabel = date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
                const status = (o.status || '').toLowerCase();
                const statusClass = status === 'delivered' ? 'success' : status === 'shipped' || status === 'processing' ? 'info' : status === 'cancelled' ? 'danger' : 'warning';
                const statusLabel = o.current_status || o.shipment_status || (status.charAt(0).toUpperCase() + status.slice(1));
                
                return (
                  <div 
                    className="order-item" 
                    key={o.id}
                    onClick={() => openModal('orders')}
                    style={{ cursor: 'pointer', transition: 'background 0.2s' }}
                    onMouseEnter={(e) => e.currentTarget.style.background = '#f9fafb'}
                    onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}
                  >
                    <div style={{ flex: 1 }}>
                      <div className="order-title">{title}</div>
                      <div className="order-date">{dateLabel}</div>
                      {o.awb_code && (
                        <div style={{ 
                          fontSize: '12px', 
                          color: '#059669', 
                          marginTop: '4px',
                          fontWeight: '600',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '4px'
                        }}>
                          <LuTruck size={14} /> {o.courier_name || 'In Transit'}
                        </div>
                      )}
                      {o.awb_code && (
                        <div style={{ 
                          fontSize: '11px', 
                          color: '#6b7280', 
                          marginTop: '2px',
                          fontFamily: 'monospace'
                        }}>
                          AWB: {o.awb_code}
                        </div>
                      )}
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' }}>
                      <span className={`status ${statusClass}`}>{statusLabel}</span>
                      {o.awb_code && (
                        <span style={{ 
                          fontSize: '11px', 
                          color: '#3b82f6',
                          fontWeight: '600',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '4px'
                        }}>
                          🔴 Live Tracking
                        </span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </article>
        </div>
      </section>

      {/* Purchase History Recommendations */}
      <section className="recommendations-section">
        <div className="container">
          <PurchaseHistoryRecommendations
            userId={auth?.user_id}
            title="💝 Just for You"
            limit={8}
            showAddToCart={true}
            showWishlist={true}
            showAnalysis={false}
            onCustomizationRequest={handleCustomizationRequest}
          />
        </div>
      </section>

      {/* Modal Components */}
      {activeModal === 'offers' && (
        <div className="modal-overlay" onClick={closeModal}>
          <div className="modal-content" style={{width: 'min(1024px, 96%)'}} onClick={(e) => e.stopPropagation()}>
            <div className="modal-header" style={{display:'grid', gridTemplateColumns:'1fr auto', alignItems:'center'}}>
              {/* Highlighted OFFERS title (no small picture) */}
              <h2 className="offers-title">OFFERS</h2>
              <button className="modal-close" onClick={closeModal}>×</button>
            </div>
            <div className="modal-body" style={{display:'grid', gap:14}}>
              {/* Horizontal strip; clicking opens full view */}
              <OffersStrip onSelect={(offer) => setFullOffer(offer)} />

              {/* On-offer products grid */}
              <div style={{display:'grid', gap:12}}>
                <h3 style={{margin:'8px 0'}}>Products on Offer</h3>
                {offerItemsLoading ? (
                  <div>Loading offers…</div>
                ) : offerArtworks.length === 0 ? (
                  <div>No active offers right now.</div>
                ) : (
                  <div style={{
                    display:'grid',
                    gridTemplateColumns:'repeat(auto-fill, minmax(220px, 1fr))',
                    gap:12
                  }}>
                    {offerArtworks.map((a) => (
                      <div key={a.id} style={{
                        border:'1px solid var(--line)', borderRadius:12, overflow:'hidden', background:'#fff'
                      }}>
                        <div style={{position:'relative'}}>
                          <img src={a.image_url} alt={a.title} style={{width:'100%', height:180, objectFit:'cover', display:'block'}} />
                          {isOfferCurrentlyActive(a) && (
                            <div style={{
                              position:'absolute', top:12, left:-40,
                              background:'#e11d48', color:'#fff', padding:'6px 50px', fontSize:12,
                              fontWeight:800, letterSpacing:'0.5px', transform:'rotate(-45deg)',
                              boxShadow:'0 2px 6px rgba(0,0,0,0.2)', textTransform:'uppercase', pointerEvents:'none'
                            }}>OFFER</div>
                          )}
                        </div>
                        <div style={{padding:12}}>
                          <div style={{fontWeight:700, marginBottom:4}}>{a.title}</div>
                          <div style={{color:'#64748b', fontSize:14, marginBottom:6}}>by {a.artist_name}</div>
                          <div aria-label="price-block">
                            {(() => {
                              const base = parseFloat(a.price) || 0;
                              const effRaw =
                                a.effective_price ??
                                a.offer_price ??
                                (base > 0 && a.offer_percent != null && a.offer_percent !== ''
                                  ? (base * (1 - (parseFloat(a.offer_percent) || 0) / 100))
                                  : null);
                              const eff = effRaw != null ? parseFloat(effRaw) : NaN;
                              const showOffer = base > 0 && Number.isFinite(eff) && eff < base;
                              if (!showOffer) {
                                return <span>{formatPriceDisplay({ basePrice: base, currencyText: a.price })}</span>;
                              }
                              return (
                                <div style={{ display: 'inline-flex', flexDirection: 'column', alignItems: 'flex-start', gap: 4 }}>
                                  {renderPriceBlock({ basePrice: base, offerPrice: eff, currencyText: a.price })}
                                </div>
                              );
                            })()}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Full image viewer when an offer is selected */}
              {fullOffer && (
                <div style={{ position:'relative', border:'1px solid var(--line)', borderRadius:12, overflow:'hidden', background:'#000' }}>
                  {/* Small red corner ribbon */}
                  <div style={{
                    position:'absolute',
                    top:16,
                    left:-52,
                    background:'#e11d48',
                    color:'#fff',
                    padding:'8px 60px',
                    fontSize:13,
                    fontWeight:800,
                    letterSpacing:'0.5px',
                    transform:'rotate(-45deg)',
                    boxShadow:'0 2px 6px rgba(0,0,0,0.2)',
                    textTransform:'uppercase',
                    pointerEvents:'none',
                    zIndex:2
                  }}>OFFER</div>

                  {/* Full image: edge-to-edge, constrained by viewport height */}
                  <img
                    src={fullOffer.image_url}
                    alt={fullOffer.title || 'Offer'}
                    style={{
                      width: '100%',
                      height: 'auto',
                      maxHeight: '70vh',
                      display: 'block',
                      objectFit: 'contain'
                    }}
                  />
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {activeModal === 'gallery' && (
        <ArtworkGallery 
          onClose={closeModal} 
          onOpenWishlist={() => setActiveModal('wishlist')} 
          onOpenCart={() => setCartOpen(true)}
        />
      )}
      {activeModal === 'custom-request' && <CustomGiftRequest onClose={closeModal} />}
      {activeModal === 'orders' && <OrderTracking onClose={closeModal} />}
      {activeModal === 'custom-requests' && (
        <React.Suspense fallback={<div className="modal-overlay"><div className="modal-content"><div>Loading requests...</div></div></div>}>
          <CustomRequestStatus onClose={closeModal} />
        </React.Suspense>
      )}
      {activeModal === 'wishlist' && <WishlistManager onClose={closeModal} />}

      {/* Cart Drawer */}
      <CartDrawer open={cartOpen} onClose={() => setCartOpen(false)} />

      {/* Customization Modal */}
      <CustomizationModal
        artwork={customizationArtwork}
        isOpen={showCustomizationModal}
        onClose={() => setShowCustomizationModal(false)}
        onSuccess={handleCustomizationSuccess}
      />

      <style>{`
        .nav-item {
          background: none;
          border: none;
          color: inherit;
          text-decoration: none;
          cursor: pointer;
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 8px 12px;
          border-radius: 6px;
          transition: background-color 0.2s;
        }

        .nav-item:hover {
          background: rgba(255, 255, 255, 0.1);
        }

        .action-card {
          background: none;
          border: none;
          text-align: left;
          cursor: pointer;
          width: 100%;
          height: 100%;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 24px;
          border-radius: 12px;
          transition: all 0.2s;
        }

        .action-card:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .action-card .action-icon {
          margin-bottom: 16px;
        }

        .action-card h3 {
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 600;
        }

        .action-card p {
          margin: 0;
          color: #666;
          text-align: center;
        }

        /* Product card customization overlay */
        .product-card {
          position: relative;
          cursor: pointer;
        }

        .product-thumb {
          position: relative;
          overflow: hidden;
        }

        .product-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          display: flex;
          align-items: center;
          justify-content: center;
          opacity: 0;
          transition: opacity 0.2s;
        }

        .product-card:hover .product-overlay {
          opacity: 1;
        }

        .product-overlay .btn-icon {
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
          color: #6b46c1;
        }

        .product-overlay .btn-icon:hover {
          transform: scale(1.1);
          background: #6b46c1;
          color: white;
        }
      `}</style>
    </div>
  );
}