import React, { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { LuShoppingCart, LuTrash2, LuPlus, LuMinus, LuArrowLeft, LuX, LuWand } from 'react-icons/lu';
import { useAuth } from '../contexts/AuthContext';
import CustomizationModal from '../components/customer/CustomizationModal';
import '../styles/customization-modal.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function CartPage() {
  const { auth } = useAuth();
  const navigate = useNavigate();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [placing, setPlacing] = useState(false);
  const [error, setError] = useState('');
  const [showCustomizationModal, setShowCustomizationModal] = useState(false);
  const [customizationArtwork, setCustomizationArtwork] = useState(null);
  const [showCustomizationOptions, setShowCustomizationOptions] = useState(false);
  const [customizationStatus, setCustomizationStatus] = useState(null);
  const [showApprovalPopup, setShowApprovalPopup] = useState(false);

  // Normalized address fields
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [addressLine1, setAddressLine1] = useState('');
  const [addressLine2, setAddressLine2] = useState('');
  const [city, setCity] = useState('');
  const [stateRegion, setStateRegion] = useState('');
  const [pincode, setPincode] = useState('');
  const [country, setCountry] = useState('India');
  const [phone, setPhone] = useState('');
  const [locating, setLocating] = useState(false);

  // Compose a single shipping string to send to backend (DB stores one field)
  const normalizedShippingAddress = useMemo(() => {
    const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
    const parts = [
      fullName,
      addressLine1,
      addressLine2,
      [city, stateRegion, pincode].filter(Boolean).join(', '),
      country,
      phone ? `Phone: ${phone}` : ''
    ].filter(Boolean);
    return parts.join('\n');
  }, [firstName, lastName, addressLine1, addressLine2, city, stateRegion, pincode, country, phone]);

  const fetchCart = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await fetch(`${API_BASE}/customer/cart.php`, {
        headers: { 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` }
      });
      const data = await res.json();
      if (data.status === 'success') {
        setItems(Array.isArray(data.cart_items) ? data.cart_items : []);
      } else {
        setError(data.message || 'Failed to load cart');
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: data.message || 'Failed to load cart' } }));
      }
    } catch (e) {
      setError('Network error while loading cart');
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error while loading cart' } }));
    } finally {
      setLoading(false);
    }
  };

  const checkCustomizationStatus = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/check-customization-status.php`, {
        headers: { 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` }
      });
      const data = await res.json();
      if (data.status === 'success') {
        setCustomizationStatus(data.customization_status);
      }
    } catch (e) {
      console.error('Failed to check customization status:', e);
    }
  };

  const approveCustomizationNow = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/approve-customization.php${auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : ''}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': auth?.user_id,
          'Authorization': `Bearer ${auth?.token}`
        },
        body: JSON.stringify({})
      });
      const data = await res.json();
      if (data.status === 'success') {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Customization approved. You can proceed to payment.' } }));
        setShowApprovalPopup(false);
        await checkCustomizationStatus();
      } else {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: data.message || 'Approval failed' } }));
      }
    } catch (e) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error during approval' } }));
    }
  };

  useEffect(() => {
    fetchCart();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (items.length > 0) {
      checkCustomizationStatus();
    }
  }, [items, auth?.user_id]);

  // Load Razorpay script once
  const loadRazorpay = () =>
    new Promise((resolve, reject) => {
      if (window.Razorpay) return resolve();
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = resolve;
      script.onerror = () => reject(new Error('Failed to load Razorpay'));
      document.body.appendChild(script);
    });

  // Start Razorpay Checkout flow
  async function startRazorpay() {
    if (!auth?.user_id) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Please log in again to continue.' } }));
      navigate('/login');
      return;
    }
    if (items.length === 0) return;

    // Check customization status before proceeding
    if (customizationStatus && !customizationStatus.can_proceed_payment) {
      setShowApprovalPopup(true);
      return;
    }

    setPlacing(true);
    try {
      // Require a normalized shipping address before payment
      const requiredOk = firstName && lastName && addressLine1 && city && stateRegion && country && /^\d{6}$/.test(pincode) && /^\+?\d{10,15}$/.test(phone);
      if (!requiredOk) {
        alert('Please fill first name, last name, address line 1, city, state, country, a valid 6-digit pincode, and phone number.');
        setPlacing(false);
        return;
      }
      await loadRazorpay();
      const createUrl = `${API_BASE}/customer/razorpay-create-order.php${auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : ''}`;
      const res = await fetch(createUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': String(auth?.user_id ?? ''),
          'Authorization': `Bearer ${auth?.token ?? ''}`
        },
        body: JSON.stringify({ user_id: auth?.user_id, shipping_address: normalizedShippingAddress || 'N/A' })
      });
      const data = await res.json();
      if (data.status !== 'success') {
        setPlacing(false);
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: data.message || 'Failed to start payment' } }));
        return;
      }
      const { key_id, order } = data;

      const rzp = new window.Razorpay({
        key: key_id,
        amount: Math.round(order.amount * 100),
        currency: order.currency || 'INR',
        name: 'My Little Thingz',
        description: `Order ${order.order_number}`,
        order_id: order.razorpay_order_id,
        theme: { color: '#6b46c1' },
        handler: async function (response) {
          try {
            const verifyUrl = `${API_BASE}/customer/razorpay-verify.php${auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : ''}`;
            const verifyRes = await fetch(verifyUrl, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-User-ID': String(auth?.user_id ?? ''),
                'Authorization': `Bearer ${auth?.token ?? ''}`
              },
              body: JSON.stringify({
                order_id: order.id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature
              })
            });
            const verifyData = await verifyRes.json();
            if (verifyData.status === 'success') {
              setItems([]);
              window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: `Payment successful. Order: ${order.order_number}` } }));
              navigate('/dashboard?show=orders');
            } else {
              window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: verifyData.message || 'Payment verification failed' } }));
            }
          } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error during payment verification' } }));
          } finally {
            setPlacing(false);
          }
        },
        modal: {
          ondismiss: function () {
            setPlacing(false);
          }
        },
        prefill: {
          name: '',
          email: '',
          contact: ''
        }
      });

      rzp.open();
    } catch (e) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message || 'Unable to initialize payment' } }));
      setPlacing(false);
    }
  };

  const parsePrice = (p) => parseFloat(String(p).replace(/[^0-9.]/g, '')) || 0;

  const subtotal = useMemo(() => {
    return items.reduce((sum, it) => sum + parsePrice((it?.effective_price ?? it.price)) * it.quantity, 0);
  }, [items]);

  const updateQty = async (item, next) => {
    const qty = Math.max(1, next);
    const prev = items;
    setItems(prev => prev.map(it => it.id === item.id ? { ...it, quantity: qty } : it));
    try {
      await fetch(`${API_BASE}/customer/cart.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` },
        body: JSON.stringify({ cart_id: item.id, quantity: qty })
      });
    } catch {
      setItems(prev); // revert on error
    }
  };

  const removeItem = async (item) => {
    const prev = items;
    setItems(prev => prev.filter(it => it.id !== item.id));
    try {
      await fetch(`${API_BASE}/customer/cart.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` },
        body: JSON.stringify({ cart_id: item.id })
      });
    } catch {
      setItems(prev); // revert on error
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
    // Refresh customization status
    checkCustomizationStatus();
  };

  const placeOrder = async () => {
    if (items.length === 0) return;
    setPlacing(true);
    try {
      // Validate normalized fields for COD/regular checkout
      const requiredOk = firstName && lastName && addressLine1 && city && stateRegion && country && /^\d{6}$/.test(pincode) && /^\+?\d{10,15}$/.test(phone);
      if (!requiredOk) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Please fill first name, last name, address line 1, city, state, country, a valid 6-digit pincode, and phone number.' } }));
        setPlacing(false);
        return;
      }
      const res = await fetch(`${API_BASE}/customer/checkout.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` },
        body: JSON.stringify({ shipping_address: normalizedShippingAddress || 'N/A' })
      });
      const data = await res.json();
      if (data.status === 'success') {
        setItems([]);
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: `Order placed: ${data.order.order_number}` } }));
        navigate('/dashboard');
      } else {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: data.message || 'Failed to place order' } }));
      }
    } catch (e) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error while placing order' } }));
    } finally { setPlacing(false); }
  };

  return (
    <div className="cart-page container">
      <header className="cart-page-header">
        <button
          className="btn btn-soft"
          onClick={() => navigate('/dashboard')}
        >
          <LuArrowLeft /> Back
        </button>
        <h1><LuShoppingCart /> My Cart</h1>
        <div />
      </header>

      {loading ? (
        <div className="muted">Loading…</div>
      ) : error ? (
        <div className="error">{error}</div>
      ) : items.length === 0 ? (
        <div className="empty">
          <p>Your cart is empty.</p>
          <Link className="btn btn-primary" to="/dashboard">Browse items</Link>
        </div>
      ) : (
        <div className="cart-grid">
          <div className="cart-list">
            {items.map(item => (
              <div key={item.id} className="cart-row">
                <img src={item.image_url || '/api/placeholder/80/80'} alt={item.title} className="thumb" />
                <div className="info">
                  <div className="title">{item.title}</div>
                  <div className="meta">
                    {(() => {
                      const base = parsePrice(item.price);
                      const effRaw = item?.effective_price ?? item?.offer_price ?? (base > 0 && (item?.offer_percent ?? '') !== '' ? (base * (1 - (parseFloat(item.offer_percent) || 0) / 100)) : null);
                      const eff = effRaw != null ? parseFloat(effRaw) : NaN;
                      const showOffer = base > 0 && Number.isFinite(eff) && eff < base;
                      if (!showOffer) return <span className="price">₹{base.toFixed(2)}</span>;
                      const pct = Math.round(((base - eff) / base) * 100);
                      return (
                        <>
                          <div style={{ lineHeight: 1 }}>
                            <span style={{ textDecoration:'line-through', color:'#6b7280' }}>₹{base.toFixed(2)}</span>
                          </div>
                          <div style={{ lineHeight: 1.2, marginTop: 2, display:'flex', alignItems:'center', gap:8 }}>
                            <span className="price" style={{ color:'#c2410c', fontWeight:800 }}>₹{eff.toFixed(2)}</span>
                            <span style={{ color:'#16a34a', fontWeight:700 }}>-{pct}%</span>
                          </div>
                        </>
                      );
                    })()}
                  </div>
                  <div className="qty">
                    <button onClick={() => updateQty(item, item.quantity - 1)}><LuMinus /></button>
                    <span>{item.quantity}</span>
                    <button onClick={() => updateQty(item, item.quantity + 1)}><LuPlus /></button>
                  </div>
                  <button 
                    className="customize-item-btn"
                    onClick={() => handleCustomizationRequest({
                      id: item.artwork_id,
                      title: item.title,
                      description: `Customize ${item.title}`,
                      price: item.price,
                      image_url: item.image_url
                    })}
                    title="Customize this item"
                  >
                    <LuWand /> Customize
                  </button>
                </div>
                <button className="trash" title="Remove" onClick={() => removeItem(item)}><LuTrash2 /></button>
              </div>
            ))}
          </div>

          <aside className="cart-summary">
            <div className="row"><span>Subtotal</span><strong>₹{subtotal.toFixed(2)}</strong></div>
            <div className="row"><span>Shipping</span><strong>Calculated at checkout</strong></div>

            {/* Shipping Address (normalized fields) */}
            <div className="row" style={{ display: 'block' }}>
              <label style={{ display: 'block', fontWeight: 600, margin: '8px 0 6px' }}>Shipping address</label>
              <div style={{ display: 'grid', gap: 8 }}>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                  <input
                    type="text"
                    placeholder="First name"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                  <input
                    type="text"
                    placeholder="Last name"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                </div>

                <button
                  type="button"
                  className="btn btn-soft"
                  onClick={async () => {
                    if (!navigator.geolocation) {
                      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Geolocation is not supported by your browser.' } }));
                      return;
                    }
                    try {
                      setLocating(true);
                      // Get current position
                      const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
                      });
                      const { latitude, longitude } = position.coords;

                      // Reverse geocode using Nominatim (OpenStreetMap)
                      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}&zoom=18&addressdetails=1`;
                      const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                      if (!resp.ok) throw new Error('Reverse geocoding failed');
                      const geo = await resp.json();
                      const a = geo.address || {};

                      // Derive fields
                      const house = a.house_number || '';
                      const road = a.road || a.residential || a.neighbourhood || a.suburb || '';
                      const line1 = [house, road].filter(Boolean).join(' ').trim();
                      const line2 = [a.village || a.town || a.hamlet || a.suburb || '', a.state_district || a.county || ''].filter(Boolean).join(', ');
                      const cityGuess = a.city || a.town || a.village || a.suburb || '';
                      const stateGuess = a.state || a.state_district || '';
                      const pincodeGuess = a.postcode || '';

                      setAddressLine1(prev => prev || line1);
                      setAddressLine2(prev => prev || line2);
                      setCity(prev => prev || cityGuess);
                      setStateRegion(prev => prev || stateGuess);
                      setPincode(prev => prev || (pincodeGuess.match(/\d{6}/)?.[0] || pincodeGuess));

                      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Location detected. Please verify address.' } }));
                    } catch (err) {
                      console.error(err);
                      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: err.message || 'Unable to get location' } }));
                    } finally {
                      setLocating(false);
                    }
                  }}
                  disabled={locating}
                  style={{ justifySelf: 'start' }}
                >
                  {locating ? 'Detecting location…' : 'Use my location'}
                </button>

                <input
                  type="text"
                  placeholder="House/Flat, Street"
                  value={addressLine1}
                  onChange={(e) => setAddressLine1(e.target.value)}
                  style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                />
                <input
                  type="text"
                  placeholder="Area / Landmark (optional)"
                  value={addressLine2}
                  onChange={(e) => setAddressLine2(e.target.value)}
                  style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                />
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                  <input
                    type="text"
                    placeholder="City"
                    value={city}
                    onChange={(e) => setCity(e.target.value)}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                  <input
                    type="text"
                    placeholder="State/Region"
                    value={stateRegion}
                    onChange={(e) => setStateRegion(e.target.value)}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                  <input
                    type="text"
                    placeholder="Country"
                    value={country}
                    onChange={(e) => setCountry(e.target.value)}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                  <input
                    type="text"
                    inputMode="numeric"
                    pattern="\\d{6}"
                    placeholder="Pincode (6 digits)"
                    value={pincode}
                    onChange={(e) => setPincode(e.target.value.replace(/[^0-9]/g, '').slice(0, 6))}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                  <input
                    type="tel"
                    placeholder="Phone"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value.replace(/[^0-9+]/g, ''))}
                    style={{ width: '100%', padding: 8, borderRadius: 8, border: '1px solid #ddd' }}
                  />
                  <div />
                </div>
              </div>
            </div>
            
            {/* Hidden Customization Options */}
            <div 
              className="customization-options"
              onMouseEnter={() => setShowCustomizationOptions(true)}
              onMouseLeave={() => setShowCustomizationOptions(false)}
            >
              <button 
                className="btn btn-outline customization-btn"
                onClick={() => setShowCustomizationOptions(!showCustomizationOptions)}
              >
                <LuWand /> Customize Items
              </button>
              
              {showCustomizationOptions && (
                <div className="customization-dropdown">
                  <p className="customization-hint">Customize your cart items before payment</p>
                  {items.map(item => (
                    <button
                      key={item.id}
                      className="customization-item-btn"
                      onClick={() => handleCustomizationRequest({
                        id: item.artwork_id,
                        title: item.title,
                        description: `Customize ${item.title}`,
                        price: item.price,
                        image_url: item.image_url
                      })}
                    >
                      <img src={item.image_url || '/api/placeholder/40/40'} alt={item.title} />
                      <span>{item.title}</span>
                    </button>
                  ))}
                </div>
              )}
            </div>

            <button 
              className={`btn ${customizationStatus && !customizationStatus.can_proceed_payment ? 'btn-outline' : 'btn-primary'}`} 
              disabled={placing || items.length===0} 
              onClick={startRazorpay}
            >
              {placing ? 'Processing…' : 
               customizationStatus && !customizationStatus.can_proceed_payment ? 
               'Awaiting Approval' : 'Pay Securely'}
            </button>
            <Link className="btn btn-soft" to="/dashboard">Continue Shopping</Link>
          </aside>
        </div>
      )}

      <style>{`
        .container { max-width: 1100px; margin: 0 auto; padding: 16px; }
        .cart-page-header { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; margin-bottom: 16px; }
        .cart-page-header h1 { display: flex; align-items: center; gap: 10px; justify-self: center; margin: 0; }
        .cart-grid { display: grid; grid-template-columns: 1fr 320px; gap: 16px; }
        .cart-list { display: grid; gap: 10px; }
        .cart-row { display: grid; grid-template-columns: 80px 1fr 32px; gap: 12px; align-items: center; padding: 10px; border: 1px solid #eee; border-radius: 10px; }
        .thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        .info { display: grid; gap: 8px; }
        .title { font-weight: 600; }
        .meta { color: #555; }
        .qty { display: inline-flex; align-items: center; gap: 8px; }
        .qty button { border: 1px solid #ddd; background: #fff; width: 28px; height: 28px; border-radius: 6px; }
        .trash { background: none; border: none; color: #c00; }
        .cart-summary { border: 1px solid #eee; border-radius: 10px; padding: 14px; height: fit-content; display: grid; gap: 10px; }
        .row { display: flex; justify-content: space-between; }
        .empty { text-align: center; display: grid; gap: 12px; justify-items: center; }
        @media (max-width: 900px) { .cart-grid { grid-template-columns: 1fr; } }

        /* Customization Options Styles */
        .customization-options {
          position: relative;
          margin-bottom: 12px;
        }

        .customization-btn {
          width: 100%;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          padding: 10px 16px;
          border: 1px solid #6b46c1;
          color: #6b46c1;
          background: transparent;
          border-radius: 8px;
          font-weight: 500;
          transition: all 0.2s;
        }

        .customization-btn:hover {
          background: #6b46c1;
          color: white;
        }

        .customization-dropdown {
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          background: white;
          border: 1px solid #e5e7eb;
          border-radius: 8px;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
          padding: 16px;
          z-index: 10;
          margin-top: 4px;
        }

        .customization-hint {
          margin: 0 0 12px 0;
          font-size: 14px;
          color: #6b7280;
          text-align: center;
        }

        .customization-item-btn {
          display: flex;
          align-items: center;
          gap: 12px;
          width: 100%;
          padding: 8px 12px;
          border: 1px solid #e5e7eb;
          background: white;
          border-radius: 6px;
          cursor: pointer;
          transition: all 0.2s;
          margin-bottom: 8px;
        }

        .customization-item-btn:hover {
          background: #f8f9fa;
          border-color: #6b46c1;
        }

        .customization-item-btn img {
          width: 40px;
          height: 40px;
          object-fit: cover;
          border-radius: 4px;
        }

        .customization-item-btn span {
          font-size: 14px;
          font-weight: 500;
          color: #374151;
        }

        .customize-item-btn {
          display: flex;
          align-items: center;
          gap: 4px;
          padding: 6px 12px;
          border: 1px solid #6b46c1;
          background: transparent;
          color: #6b46c1;
          border-radius: 6px;
          font-size: 12px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.2s;
          margin-top: 8px;
        }

        .customize-item-btn:hover {
          background: #6b46c1;
          color: white;
        }

        /* Approval Popup Styles */
        .approval-popup-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.5);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
        }

        .approval-popup {
          background: white;
          border-radius: 12px;
          max-width: 500px;
          width: 90%;
          max-height: 80vh;
          overflow-y: auto;
          box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .approval-popup-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 20px 24px;
          border-bottom: 1px solid #e5e7eb;
        }

        .approval-popup-header h3 {
          margin: 0;
          color: #374151;
          font-size: 18px;
          font-weight: 600;
        }

        .approval-popup-header .close-btn {
          background: none;
          border: none;
          font-size: 20px;
          color: #6b7280;
          cursor: pointer;
          padding: 4px;
          border-radius: 4px;
        }

        .approval-popup-header .close-btn:hover {
          background: #f3f4f6;
          color: #374151;
        }

        .approval-popup-content {
          padding: 24px;
        }

        .approval-popup-content p {
          margin: 0 0 20px 0;
          color: #6b7280;
          line-height: 1.5;
        }

        .pending-requests {
          margin-bottom: 20px;
        }

        .pending-requests h4 {
          margin: 0 0 12px 0;
          color: #374151;
          font-size: 16px;
          font-weight: 600;
        }

        .pending-requests ul {
          margin: 0;
          padding-left: 20px;
        }

        .pending-requests li {
          margin-bottom: 8px;
          color: #6b7280;
          line-height: 1.4;
        }

        .pending-requests li strong {
          color: #374151;
        }

        .admin-notes {
          margin-top: 4px;
          padding: 8px 12px;
          background: #fef3c7;
          border: 1px solid #f59e0b;
          border-radius: 6px;
          font-size: 14px;
          color: #92400e;
        }

        .approval-popup-actions {
          display: flex;
          justify-content: flex-end;
          gap: 12px;
        }
      `}</style>

      {/* Customization Modal */}
      <CustomizationModal
        artwork={customizationArtwork}
        isOpen={showCustomizationModal}
        onClose={() => setShowCustomizationModal(false)}
        onSuccess={handleCustomizationSuccess}
      />

      {/* Approval Pending Popup */}
      {showApprovalPopup && (
        <div className="approval-popup-overlay">
          <div className="approval-popup">
            <div className="approval-popup-header">
              <h3>⏳ Admin Approval Pending</h3>
              <button 
                className="close-btn"
                onClick={() => setShowApprovalPopup(false)}
              >
                <LuX />
              </button>
            </div>
            <div className="approval-popup-content">
              <p>Your customization requests are pending admin approval. You cannot proceed with payment until all customization requests are approved.</p>
              
              {customizationStatus && customizationStatus.pending_requests.length > 0 && (
                <div className="pending-requests">
                  <h4>Pending Requests:</h4>
                  <ul>
                    {customizationStatus.pending_requests.map((request, index) => (
                      <li key={index}>
                        <strong>{request.title}</strong> - {request.message}
                        {request.admin_notes && (
                          <div className="admin-notes">Admin Notes: {request.admin_notes}</div>
                        )}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              <div className="approval-popup-actions">
                <button 
                  className="btn btn-outline"
                  onClick={() => setShowApprovalPopup(false)}
                >
                  Close
                </button>
                <button 
                  className="btn btn-primary"
                  onClick={approveCustomizationNow}
                >
                  Approve Now & Enable Payment
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}