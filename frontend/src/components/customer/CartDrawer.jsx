import React, { useEffect, useState, useMemo } from 'react';
import { LuX, LuTrash2, LuPlus, LuMinus, LuShoppingCart } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

// WhatsApp number - can be moved to config later
const WHATSAPP_NUMBER = '919495470077'; // Format: country code + number (no + or spaces)

// Helper function to generate WhatsApp message for a cart item
const generateWhatsAppMessage = (item) => {
  const base = parseFloat(String(item.price).replace(/[^0-9.]/g,'')) || 0;
  const effRaw = item?.effective_price ?? item?.offer_price ?? (base > 0 && (item?.offer_percent ?? '') !== '' ? (base * (1 - (parseFloat(item.offer_percent) || 0) / 100)) : null);
  const eff = effRaw != null ? parseFloat(effRaw) : NaN;
  const showOffer = base > 0 && Number.isFinite(eff) && eff < base;
  const finalPrice = showOffer ? eff : base;
  
  // Parse selected options if available
  let optionsText = '';
  if (item.selected_options) {
    try {
      const opts = typeof item.selected_options === 'string' 
        ? JSON.parse(item.selected_options) 
        : item.selected_options;
      if (opts && typeof opts === 'object' && Object.keys(opts).length > 0) {
        optionsText = '\n\n*Selected Options:*\n' + 
          Object.entries(opts).map(([k, v]) => `• ${k}: ${v}`).join('\n');
      }
    } catch (e) {
      // Ignore parsing errors
    }
  }
  
  const message = `Hello! I'm interested in this product:

*${item.title}*
Price: ₹${finalPrice.toFixed(2)}${showOffer ? ` (Original: ₹${base.toFixed(2)})` : ''}
Quantity: ${item.quantity}${optionsText}

Could you please provide more details?`;
  
  return encodeURIComponent(message);
};

// WhatsApp Icon Component
const WhatsAppIcon = ({ size = 20 }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
  </svg>
);

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
                <div className="cart-item-actions">
                  <a
                    href={`https://wa.me/${WHATSAPP_NUMBER}?text=${generateWhatsAppMessage(item)}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="whatsapp-btn"
                    title="Contact via WhatsApp"
                  >
                    <WhatsAppIcon size={18} />
                    <span>WhatsApp</span>
                  </a>
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
        .cart-item-actions { display: flex; gap: 8px; margin-top: 4px; }
        .whatsapp-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #25D366; color: white; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 500; transition: all 0.2s; }
        .whatsapp-btn:hover { background: #20BA5A; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3); }
        .whatsapp-btn svg { flex-shrink: 0; }
        .trash { background: none; border: none; color: #c00; }
        .cart-footer { border-top: 1px solid #eee; padding: 12px 16px; display: grid; gap: 10px; }
        .cart-footer .row { display: flex; justify-content: space-between; }
      `}</style>
    </div>
  );
}