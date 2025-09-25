import React, { useEffect, useState, useMemo } from 'react';
import { LuX, LuTrash2, LuPlus, LuMinus, LuShoppingCart } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function CartDrawer({ open, onClose, onCartCountChange }) {
  const { auth } = useAuth();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [placing, setPlacing] = useState(false);

  const fetchCart = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/customer/cart.php`, {
        headers: { 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` }
      });
      const data = await res.json();
      if (data.status === 'success') {
        setItems(Array.isArray(data.cart_items) ? data.cart_items : []);
      }
    } catch { /* ignore */ }
    finally { setLoading(false); }
  };

  useEffect(() => {
    if (open) fetchCart();
  }, [open]);

  // Periodic refresh and visibility/focus-based fetch to reflect live discounts
  useEffect(() => {
    if (!open) return;
    const intervalId = setInterval(fetchCart, 25000);
    const onFocus = () => fetchCart();
    const onVisibility = () => { if (document.visibilityState === 'visible') fetchCart(); };
    window.addEventListener('focus', onFocus);
    document.addEventListener('visibilitychange', onVisibility);
    return () => {
      clearInterval(intervalId);
      window.removeEventListener('focus', onFocus);
      document.removeEventListener('visibilitychange', onVisibility);
    };
  }, [open]);

  const subtotal = useMemo(() => {
    return items.reduce((sum, it) => {
      const base = parseFloat(String(it.price).replace(/[^0-9.]/g,'')) || 0;
      const eff = (it?.effective_price != null) ? parseFloat(it.effective_price) : base;
      return sum + eff * it.quantity;
    }, 0);
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
      setItems(prev); // naive revert on error
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

  const placeOrder = async () => {
    if (items.length === 0) return;
    setPlacing(true);
    try {
      const res = await fetch(`${API_BASE}/customer/checkout.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-User-ID': auth?.user_id, 'Authorization': `Bearer ${auth?.token}` },
        body: JSON.stringify({ shipping_address: 'N/A' })
      });
      const data = await res.json();
      if (data.status === 'success') {
        // Clear UI cart and close
        setItems([]);
        alert(`Order placed: ${data.order.order_number}`);
        if (typeof onClose === 'function') onClose();
      } else {
        alert(data.message || 'Failed to place order');
      }
    } catch (e) {
      alert('Network error while placing order');
    } finally { setPlacing(false); }
  };

  return (
    <div className={`cart-drawer ${open ? 'open' : ''}`}>
      <div className="cart-header">
        <h3><LuShoppingCart /> Your Cart</h3>
        <button className="btn-close" onClick={onClose}><LuX /></button>
      </div>

      <div className="cart-body">
        {loading ? (
          <div className="muted">Loading…</div>
        ) : items.length === 0 ? (
          <div className="muted">Your cart is empty</div>
        ) : (
          items.map(item => (
            <div key={item.id} className="cart-row">
              <img src={item.image_url || '/api/placeholder/56/56'} alt={item.title} className="thumb" />
              <div className="info">
                <div className="title">{item.title}</div>
                <div className="price">
                  {(() => {
                    const base = parseFloat(String(item.price).replace(/[^0-9.]/g,'')) || 0;
                    const effRaw = item?.effective_price ?? item?.offer_price ?? (base > 0 && (item?.offer_percent ?? '') !== '' ? (base * (1 - (parseFloat(item.offer_percent) || 0) / 100)) : null);
                    const eff = effRaw != null ? parseFloat(effRaw) : NaN;
                    const showOffer = base > 0 && Number.isFinite(eff) && eff < base;
                    if (!showOffer) return <>₹{base.toFixed(2)}</>;
                    const pct = Math.round(((base - eff) / base) * 100);
                    return (
                      <>
                        <div style={{ lineHeight: 1 }}>
                          <span style={{ textDecoration:'line-through', color:'#6b7280' }}>₹{base.toFixed(2)}</span>
                        </div>
                        <div style={{ lineHeight: 1.2, marginTop: 2, display:'flex', alignItems:'center', gap:8 }}>
                          <span style={{ color:'#c2410c', fontWeight:800 }}>₹{eff.toFixed(2)}</span>
                          <span style={{ color:'#16a34a', fontWeight:700, fontSize:12 }}>-{pct}%</span>
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
              </div>
              <button className="trash" title="Remove" onClick={() => removeItem(item)}><LuTrash2 /></button>
            </div>
          ))
        )}
      </div>

      <div className="cart-footer">
        <div className="row">
          <span>Subtotal</span>
          <strong>₹{subtotal.toFixed(2)}</strong>
        </div>
        <button className="btn btn-primary" disabled={placing || items.length===0} onClick={placeOrder}>
          {placing ? 'Placing Order…' : 'Checkout'}
        </button>
      </div>

      <style>{`
        .cart-drawer { position: fixed; top: 0; right: -420px; width: 400px; height: 100vh; background: #fff; box-shadow: -2px 0 12px rgba(0,0,0,0.08); transition: right .25s; display: flex; flex-direction: column; z-index: 1000; }
        .cart-drawer.open { right: 0; }
        .cart-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #eee; }
        .cart-body { padding: 10px 12px; overflow: auto; flex: 1; }
        .cart-row { display: grid; grid-template-columns: 56px 1fr 32px; gap: 10px; align-items: center; padding: 8px; border: 1px solid #f1f1f1; border-radius: 8px; margin-bottom: 8px; }
        .cart-row .thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        .cart-row .info { display: grid; gap: 6px; }
        .qty { display: inline-flex; align-items: center; gap: 6px; }
        .qty button { border: 1px solid #ddd; background: #fff; width: 26px; height: 26px; border-radius: 6px; }
        .trash { background: none; border: none; color: #c00; }
        .cart-footer { border-top: 1px solid #eee; padding: 12px 16px; display: grid; gap: 10px; }
        .cart-footer .row { display: flex; justify-content: space-between; }
      `}</style>
    </div>
  );
}