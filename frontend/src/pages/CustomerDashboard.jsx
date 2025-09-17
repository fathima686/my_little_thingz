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

import logo from "../assets/logo.png";
import poloroid2 from "../assets/poloroid (2).png";
import weddingHamper from "../assets/Wedding hamper.jpg";
import customChocolate from "../assets/custom chocolate.png";
import "../styles/dashboard.css";

export default function CustomerDashboard() {
  const { auth, logout, isLoading } = useAuth();
  const navigate = useNavigate();
  const [activeModal, setActiveModal] = useState(null);
  const [profileImageUrl, setProfileImageUrl] = useState(null);
  const [cartOpen, setCartOpen] = useState(false);
  const [showCustomizationModal, setShowCustomizationModal] = useState(false);
  const [customizationArtwork, setCustomizationArtwork] = useState(null);
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

          {/* Search */}
          <div className="searchbar">
            <span className="icon" aria-hidden>
              <LuSearch />
            </span>
            <input placeholder="Search gifts, makers, categories…" aria-label="Search" />
          </div>

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
              <p className="muted">Track your recent purchases</p>
            </header>
            <div className="widget-body">
              {ordersLoading && (
                <div className="order-item"><div className="order-title">Loading…</div></div>
              )}
              {!ordersLoading && recentOrders.length === 0 && (
                <div className="order-item"><div className="order-title">No recent orders</div></div>
              )}
              {!ordersLoading && recentOrders.map((o) => {
                // Derive a display name using first item or order number
                const firstItem = (o.items && o.items[0]) || null;
                const title = firstItem?.name || `Order #${o.order_number}`;
                const date = new Date(o.created_at);
                const dateLabel = date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
                const status = (o.status || '').toLowerCase();
                const statusClass = status === 'delivered' ? 'success' : status === 'shipped' || status === 'processing' ? 'info' : status === 'cancelled' ? 'danger' : 'warning';
                const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
                return (
                  <div className="order-item" key={o.id}>
                    <div>
                      <div className="order-title">{title}</div>
                      <div className="order-date">{dateLabel}</div>
                    </div>
                    <span className={`status ${statusClass}`}>{statusLabel}</span>
                  </div>
                );
              })}
            </div>
          </article>
        </div>
      </section>

      {/* Modal Components */}
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