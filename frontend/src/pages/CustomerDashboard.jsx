import React, { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  LuGift, LuHeart, LuPackage, LuUser, LuLogOut, LuSearch, LuShoppingBag
} from "react-icons/lu";
import { useAuth } from "../contexts/AuthContext";

import ArtworkGallery from "../components/customer/ArtworkGallery";
import CustomGiftRequest from "../components/customer/CustomGiftRequest";
import OrderTracking from "../components/customer/OrderTracking";
import WishlistManager from "../components/customer/WishlistManager";
import logo from "../assets/logo.png";
import poloroid2 from "../assets/poloroid (2).png";
import weddingHamper from "../assets/Wedding hamper.jpg";
import customChocolate from "../assets/custom chocolate.png";
import "../styles/dashboard.css";

export default function CustomerDashboard() {
  const { auth, logout, isLoading } = useAuth();
  const [activeModal, setActiveModal] = useState(null);

  const handleLogout = () => {
    logout();
  };

  const openModal = (modalType) => {
    setActiveModal(modalType);
  };

  const closeModal = () => {
    setActiveModal(null);
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
            <button type="button" className="nav-item" onClick={() => openModal('wishlist')}><LuHeart /> Wishlist</button>
            <button type="button" className="nav-item" onClick={() => openModal('orders')}><LuPackage /> Orders</button>
            <Link to="#" className="nav-item"><LuUser /> Profile</Link>
            <button type="button" className="btn btn-soft small" onClick={handleLogout}><LuLogOut /> Logout</button>
            <span className="avatar" title="You">{(auth?.user_id ?? "U").toString().slice(-2)}</span>
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
                <div className="product-card">
                  <div className="product-thumb">
                    <img src={poloroid2} alt="Polaroids" />
                  </div>
                  <div className="product-body">
                    <p className="product-title">Polaroids Pack</p>
                    <p className="product-sub">Vintage • Instant prints</p>
                  </div>
                </div>
                <div className="product-card">
                  <div className="product-thumb">
                    <img src={weddingHamper} alt="Wedding Hamper" />
                  </div>
                  <div className="product-body">
                    <p className="product-title">Wedding Hamper</p>
                    <p className="product-sub">Curated • Celebration set</p>
                  </div>
                </div>
                <div className="product-card">
                  <div className="product-thumb">
                    <img src={customChocolate} alt="Custom Chocolate" />
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
              <div className="order-item">
                <div>
                  <div className="order-title">Personalized Keychain</div>
                  <div className="order-date">Jan 8, 2025</div>
                </div>
                <span className="status success">Delivered</span>
              </div>
              <div className="order-item">
                <div>
                  <div className="order-title">Photo Frame</div>
                  <div className="order-date">Jan 2, 2025</div>
                </div>
                <span className="status info">Shipped</span>
              </div>
            </div>
          </article>
        </div>
      </section>

      {/* Modal Components */}
      {activeModal === 'gallery' && (
        <ArtworkGallery 
          onClose={closeModal} 
          onOpenWishlist={() => setActiveModal('wishlist')} 
        />
      )}
      {activeModal === 'custom-request' && <CustomGiftRequest onClose={closeModal} />}
      {activeModal === 'orders' && <OrderTracking onClose={closeModal} />}
      {activeModal === 'wishlist' && <WishlistManager onClose={closeModal} />}

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
      `}</style>
    </div>
  );
}