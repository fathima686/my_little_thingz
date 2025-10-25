import React, { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import CustomizationRequests from "../components/admin/CustomizationRequests";
import "../styles/admin.css";

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function AdminDashboard() {
  const navigate = useNavigate();
  const { auth, logout, isLoading } = useAuth();
  const [suppliers, setSuppliers] = useState([]);
  const [suppliersAll, setSuppliersAll] = useState([]);
  const [filter, setFilter] = useState("pending");



  // Custom Requests state
  const [requests, setRequests] = useState([]);
  const [reqFilter, setReqFilter] = useState("pending");

  // Artworks management state
  const [artworks, setArtworks] = useState([]);
  const [categories, setCategories] = useState([]);
  const [uploading, setUploading] = useState(false);
  // Edit artwork modal state
  const [editArtwork, setEditArtwork] = useState(null); // full artwork row
  const [editSaving, setEditSaving] = useState(false);

  // Requirements state
  const [requirements, setRequirements] = useState([]);
  const [reqForm, setReqForm] = useState({ supplier_id: '', order_ref: '', material_name: '', required_qty: '', unit: 'pcs', due_date: '' });
  const [showCustomizationRequests, setShowCustomizationRequests] = useState(false);

  // Orders management state
  const [orders, setOrders] = useState([]);
  const [orderStatusFilter, setOrderStatusFilter] = useState("all");

  // Reviews management state
  const [reviews, setReviews] = useState([]);
  const [reviewsStatus, setReviewsStatus] = useState("pending");
  const [reviewsLoading, setReviewsLoading] = useState(false);
  const [reviewsReplyDraft, setReviewsReplyDraft] = useState({});

  const [activeSection, setActiveSection] = useState('overview'); // overview | suppliers | supplier-products | supplier-inventory | custom-requests | artworks | requirements | orders | settings
  const [artForm, setArtForm] = useState({
    title: "",
    description: "",
    price: "",
    offer_price: "",
    offer_percent: "",
    offer_starts_at: "",
    offer_ends_at: "",
    force_offer_badge: false,
    category_id: "",
    availability: "in_stock",
    status: "active",
    image: null,
  });
  // Inline notice for artwork actions
  const [artNotice, setArtNotice] = useState({ type: "", text: "" });
  const [imagePreview, setImagePreview] = useState(null);

  // Supplier Products moderation state
  const [supplierProducts, setSupplierProducts] = useState([]);
  const [spStatus, setSpStatus] = useState("");
  const [spQuery, setSpQuery] = useState("");
  const [spAvailability, setSpAvailability] = useState("");
  const [spSupplierId, setSpSupplierId] = useState("");

  // Admin procurement cart state
  const [adminCart, setAdminCart] = useState([]); // [{id, name, price, quantity, colors?: [{color, qty}]}]
  const [warehouseAddress, setWarehouseAddress] = useState('');
  const [warehouseAddressFields, setWarehouseAddressFields] = useState(null); // {name, address_line1, address_line2, city, state, pincode, country, phone}

  // Color breakdown modal state for "Buy"
  const [showColorModal, setShowColorModal] = useState(false);
  const [colorModalProduct, setColorModalProduct] = useState(null); // {id, name, price}
  const [colorRows, setColorRows] = useState([{ color: '', qty: 1 }]);
  const [editingColorsForId, setEditingColorsForId] = useState(null);

  const openBuyModal = (p) => {
    setEditingColorsForId(null);
    setColorModalProduct({ id: p.id, name: p.name, price: Number(p.price) });
    setColorRows([{ color: '', qty: 1 }]);
    setShowColorModal(true);
  };





  // Supplier Inventory view state (view-only)
  const [supplierInventory, setSupplierInventory] = useState([]);
  const [siQuery, setSiQuery] = useState("");
  const [siAvailability, setSiAvailability] = useState("");
  const [siSupplierId, setSiSupplierId] = useState("");
  const [siCategory, setSiCategory] = useState("");

  // Admin promotional offers (banners/cards)
  const [offers, setOffers] = useState([]); // [{id, title, image_url}]
  const [offerForm, setOfferForm] = useState({ title: "", image: null });
  const [offerNotice, setOfferNotice] = useState({ type: "", text: "" });

  // Lightbox for images
  const [lightboxUrl, setLightboxUrl] = useState(null);

  // Comprehensive refresh function for all sections
  const refreshAllData = async () => {
    try {
      // Refresh all data based on current active section
      switch (activeSection) {
        case 'overview':
          await Promise.all([
            fetchSuppliers(),
            fetchRequests(reqFilter),
            fetchRequirements(),
            fetchArtworks()
          ]);
          break;
        case 'suppliers':
          await fetchSuppliers();
          break;
        case 'supplier-products':
          await fetchSupplierProducts();
          break;
        case 'supplier-inventory':
          await fetchSupplierInventory();
          break;
        case 'custom-requests':
          await fetchRequests(reqFilter);
          break;
        case 'artworks':
          await Promise.all([fetchCategories(), fetchArtworks()]);
          break;
        case 'requirements':
          await fetchRequirements();
          break;
        default:
          // Refresh all data for unknown sections
          await Promise.all([
            fetchSuppliers(),
            fetchSupplierProducts(),
            fetchSupplierInventory(),
            fetchRequests(reqFilter),
            fetchCategories(),
            fetchArtworks(),
            fetchRequirements()
          ]);
      }
    } catch (error) {
      console.error('Error refreshing data:', error);
    }
  };
  const [lightboxAlt, setLightboxAlt] = useState("");
  // Message thread modal state (shows history + send box)
  const [messageModal, setMessageModal] = useState({ open: false, requirement: null, text: '' });

  // Derive admin header for simple authorization to backend admin endpoints
  const adminHeader = useMemo(() => {
    const id = auth?.user_id ? Number(auth.user_id) : 0;
    return id > 0 ? { "X-Admin-User-Id": String(id) } : {};
  }, [auth]);

  // Sidebar cart drawer state and helpers
  const [showCartDrawer, setShowCartDrawer] = useState(false);
  const cartSubtotal = useMemo(() => adminCart.reduce((s,x)=> s + x.price * x.quantity, 0), [adminCart]);

  // Admin offers: list/create/delete
  const fetchOffers = async () => {
    try {
      const res = await fetch(`${API_BASE}/admin/offers-promos.php`, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') { setOffers(data.offers || []); }
    } catch {}
  };

  const uploadOffer = async () => {
    try {
      if (!offerForm.title || !offerForm.image) { setOfferNotice({ type:'error', text:'Title and image are required' }); return; }
      const fd = new FormData();
      fd.append('title', offerForm.title);
      fd.append('image', offerForm.image);
      const res = await fetch(`${API_BASE}/admin/offers-promos.php`, { method:'POST', headers: { ...adminHeader }, body: fd });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setOfferNotice({ type:'success', text:'Offer created' });
        setOfferForm({ title:'', image:null });
        fetchOffers();
      } else {
        setOfferNotice({ type:'error', text: data.message || 'Failed to create offer' });
      }
    } catch {
      setOfferNotice({ type:'error', text:'Network error uploading offer' });
    }
  };

  const deleteOffer = async (id) => {
    if (!id) return;
    try {
      const res = await fetch(`${API_BASE}/admin/offers-promos.php?id=${encodeURIComponent(id)}`, { method:'DELETE', headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') { fetchOffers(); }
      else { alert(data.message || 'Failed to delete offer'); }
    } catch {
      alert('Network error deleting offer');
    }
  };

  const checkoutAdminCart = async () => {
    try {
      // Validate color-wise quantities equal quantity
      for (const x of adminCart) {
        if (Array.isArray(x.colors) && x.colors.length) {
          const sum = x.colors.reduce((a,c)=> a + (parseInt(c.qty||0,10)||0), 0);
          if (sum !== x.quantity) { alert(`Color quantities must sum to total for ${x.name}`); return; }
        }
      }
      // Lazy-load Razorpay if needed
      if (!window.Razorpay) {
        await new Promise((resolve,reject)=>{
          const s=document.createElement('script');
          s.src='https://checkout.razorpay.com/v1/checkout.js';
          s.onload=resolve; s.onerror=()=>reject(new Error('Failed to load Razorpay'));
          document.body.appendChild(s);
        });
      }
      const res = await fetch(`${API_BASE}/admin/procurement-create-order.php`, {
        method:'POST',
        headers: { 'Content-Type':'application/json', ...adminHeader },
        body: JSON.stringify({ items: adminCart.map(x=>({ id:x.id, quantity:x.quantity, colors: x.colors || [], type: x.type || x.source || 'product' })) })
      });
      const data = await res.json();
      if (data.status !== 'success') { alert(data.message || 'Failed to create order'); return; }
      const { key_id, order } = data;
      const rzp = new window.Razorpay({
        key: key_id,
        amount: Math.round(order.amount * 100),
        currency: order.currency || 'INR',
        name: 'My Little Thingz (Admin PO)',
        description: `PO ${order.order_number}`,
        order_id: order.razorpay_order_id,
        theme: { color: '#6b46c1' },
        handler: async function (response) {
          try {
            const vres = await fetch(`${API_BASE}/admin/procurement-verify.php`, {
              method:'POST',
              headers: { 'Content-Type':'application/json', ...adminHeader },
              body: JSON.stringify({
                purchase_order_id: order.id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature
              })
            });
            const vdata = await vres.json();
            if (vdata.status==='success') { alert('Payment successful'); setAdminCart([]); setShowCartDrawer(false); }
            else { alert(vdata.message || 'Payment verification failed'); }
          } catch(e) { alert('Network error during verification'); }
        }
      });
      rzp.open();
    } catch(e) { alert(e.message || 'Unable to initialize payment'); }
  };

  const fetchSuppliers = async (st = filter) => {
    try {
      const url = `${API_BASE}/admin/suppliers.php?status=${encodeURIComponent(st)}`;
      const res = await fetch(url, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        setSuppliers(data.suppliers || []);
      } else if (res.status === 401 || res.status === 403) {
        alert("Admin session required.");
        navigate("/login");
      } else {
        alert(data.message || "Failed to load suppliers");
      }
    } catch {
      alert("Network error loading suppliers");
    }
  };

  const fetchAll = async () => {
    try {
      const url = `${API_BASE}/admin/suppliers.php?status=all`;
      const res = await fetch(url, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        setSuppliersAll(data.suppliers || []);
      }
    } catch {}
  };

  const fetchRequests = async (st = reqFilter) => {
    try {
      const url = `${API_BASE}/admin/custom-requests.php?status=${encodeURIComponent(st)}`;
      const res = await fetch(url, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        setRequests(data.requests || []);
      }
    } catch {}
  };

  const updateRequestStatus = async (requestId, status) => {
    try {
      const res = await fetch(`${API_BASE}/admin/custom-requests.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify({ request_id: requestId, status })
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        fetchRequests(reqFilter);
      } else {
        alert(data.message || 'Failed to update request');
      }
    } catch {
      alert('Network error updating request');
    }
  };

  // Upload an image for a custom request (admin)
  const uploadAdminRequestImage = async (requestId, file) => {
    if (!file) return;
    try {
      const fd = new FormData();
      fd.append('request_id', String(requestId));
      fd.append('image', file);
      const res = await fetch(`${API_BASE}/admin/custom-request-images.php`, {
        method: 'POST',
        headers: { ...adminHeader },
        body: fd
      });
      const data = await res.json();
      if (!(res.ok && data.status === 'success')) {
        throw new Error(data.message || 'Upload failed');
      }
      // Reload current list to reflect any counters later
      await fetchRequests(reqFilter);
      return data;
    } catch (err) {
      alert(err.message || 'Network error uploading image');
    }
  };

  // Artworks admin helpers
  const fetchArtworks = async () => {
    try {
      const res = await fetch(`${API_BASE}/admin/artworks.php`, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setArtworks(data.artworks || []);
      }
    } catch {}
  };

  const fetchCategories = async () => {
    try {
      // Ensure fixed categories exist and are active, then load them
      const res = await fetch(`${API_BASE}/admin/categories-set.php`, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setCategories(data.categories || []);
      } else {
        // Fallback to customer categories if admin endpoint not available
        const res2 = await fetch(`${API_BASE}/customer/categories.php`);
        const data2 = await res2.json();
        if (res2.ok && data2.status === 'success') setCategories(data2.categories || []);
      }
    } catch {
      try {
        const res2 = await fetch(`${API_BASE}/customer/categories.php`);
        const data2 = await res2.json();
        if (res2.ok && data2.status === 'success') setCategories(data2.categories || []);
      } catch {}
    }
  };

  const fetchRequirements = async () => {
    try {
      const res = await fetch(`${API_BASE}/admin/requirements.php`, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setRequirements(data.items || []);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchOrders = async () => {
    try {
      const res = await fetch(`${API_BASE}/admin/orders.php`, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setOrders(data.orders || []);
      }
    } catch (e) {
      console.error(e);
    }
  };

  // Reviews management functions
  const fetchReviews = async () => {
    try {
      setReviewsLoading(true);
      const res = await fetch(`${API_BASE}/admin/reviews.php?status=${reviewsStatus}`, { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setReviews(data.items || []);
      }
    } catch (e) {
      console.error('Failed to load reviews:', e);
    } finally {
      setReviewsLoading(false);
    }
  };

  const updateReview = async (id, payload) => {
    try {
      const res = await fetch(`${API_BASE}/admin/reviews.php`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify({ id, ...payload })
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        fetchReviews(); // Refresh the list
      } else {
        alert(data.message || 'Failed to update review');
      }
    } catch (e) {
      alert('Network error updating review');
    }
  };

  const updateOrderStatus = async (orderId, action) => {
    try {
      const res = await fetch(`${API_BASE}/admin/orders.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify({ order_id: orderId, action })
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        alert(data.message);
        fetchOrders();
      } else {
        alert(data.message || 'Failed to update order');
      }
    } catch (e) {
      alert('Network error updating order');
    }
  };

  // Supplier Products: list and approve/reject
  const fetchSupplierProducts = async (opts = {}) => {
    const { q = spQuery, supplier_id = spSupplierId, availability = spAvailability } = opts;
    const url = new URL(`${API_BASE}/admin/supplier-products.php`);
    if (q) url.searchParams.set('q', q);
    if (supplier_id) url.searchParams.set('supplier_id', supplier_id);
    if (availability) url.searchParams.set('availability', availability);
    url.searchParams.set('trending', '1'); // show only trending products
    try {
      const res = await fetch(url.toString(), { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setSupplierProducts(data.items || []);
      }
    } catch {}
  };

  // Supplier Inventory: list with price and bulk-buy helpers
  const fetchSupplierInventory = async (opts = {}) => {
    const { q = siQuery, supplier_id = siSupplierId, availability = siAvailability, category = siCategory } = opts;
    const url = new URL(`${API_BASE}/admin/supplier-inventory.php`);
    if (q) url.searchParams.set('q', q);
    if (supplier_id) url.searchParams.set('supplier_id', supplier_id);
    if (availability) url.searchParams.set('availability', availability);
    if (category) url.searchParams.set('category', category);
    try {
      const res = await fetch(url.toString(), { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setSupplierInventory(data.items || []);
      }
    } catch {}
  };

  // Add inventory material to Admin Cart with color-wise quantities
  const addInventoryToCart = (m) => {
    setColorModalProduct({ ...m, type: 'material' });
    setEditingColorsForId(m.id);
    // Default one row
    setColorRows([{ color: '', qty: 1 }]);
    setShowColorModal(true);
  };

  // Confirm colors for either product or material
  const confirmColorModal = () => {
    if (!colorModalProduct) { setShowColorModal(false); return; }
    const rows = (colorRows || []).filter(r => (r.qty||0) > 0).map(r => ({ color: (r.color||'').trim() || '-', qty: Number(r.qty)||0 }));
    const totalQty = rows.reduce((a,c)=>a + (c.qty||0), 0) || 0;
    if (totalQty <= 0) { alert('Please enter at least one color quantity'); return; }
    const item = {
      id: colorModalProduct.id,
      name: colorModalProduct.name,
      price: Number(colorModalProduct.price||0),
      quantity: totalQty,
      colors: rows,
      type: colorModalProduct.type || 'product',
      supplier_id: colorModalProduct.supplier_id
    };
    setAdminCart(prev => {
      // merge by id and type
      const idx = prev.findIndex(x => x.id === item.id && (x.type||'product') === (item.type||'product'));
      if (idx >= 0) {
        const copy = [...prev];
        copy[idx] = { ...copy[idx], quantity: item.quantity, colors: item.colors, price: item.price, name: item.name, supplier_id: item.supplier_id };
        return copy;
      }
      return [...prev, item];
    });
    setShowColorModal(false);
    setEditingColorsForId(null);
    setColorRows([]);
    setColorModalProduct(null);
  };

  const openEditColors = (it) => {
    setColorModalProduct({ ...it });
    setEditingColorsForId(it.id);
    setColorRows(it.colors && it.colors.length ? it.colors.map(c=>({ color: c.color || '', qty: c.qty || 0 })) : [{ color: '', qty: it.quantity || 1 }]);
    setShowColorModal(true);
  };

  // Export current Supplier Inventory table to CSV (respects current list in state)
  const exportSupplierInventoryCSV = () => {
    try {
      const rows = supplierInventory || [];
      if (!rows.length) {
        alert('No inventory to export');
        return;
      }
      const headers = [
        'ID','Supplier','Supplier ID','Image URL','Name','SKU','Category','Type','Size','Color','Brand','Qty','Availability','Updated'
      ];
      const escapeCSV = (val) => {
        const s = val === null || val === undefined ? '' : String(val);
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
      };
      const lines = [headers.join(',')];
      for (const m of rows) {
        const line = [
          m.id,
          m.supplier_name || '',
          m.supplier_id || '',
          m.image_url || '',
          m.name || '',
          m.sku || '',
          m.category || '',
          m.type || '',
          m.size || '',
          m.color || '',
          m.brand || '',
          m.quantity ?? '',
          m.availability || '',
          m.updated_at ? new Date(m.updated_at).toISOString() : ''
        ].map(escapeCSV).join(',');
        lines.push(line);
      }
      const csv = '\uFEFF' + lines.join('\n'); // BOM for Excel compatibility
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      const ts = new Date().toISOString().slice(0,19).replace(/[:T]/g, '-');
      a.href = url;
      a.download = `supplier_inventory_${ts}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (err) {
      console.error('CSV export failed', err);
      alert('CSV export failed');
    }
  };


  const actSupplierProduct = async (id, action) => {
    if (!window.confirm(`Confirm ${action} for product #${id}?`)) return;
    try {
      const res = await fetch(`${API_BASE}/admin/supplier-products.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify({ id, action })
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        fetchSupplierProducts();
      } else {
        alert(data.message || 'Action failed');
      }
    } catch {
      alert('Network error');
    }
  };

  const uploadArtwork = async (e) => {
    e.preventDefault();
    if (!artForm.title || !artForm.price || (!artForm.image)) {
      alert('Title, price, and image are required');
      return;
    }
    if (artForm.offer_price && parseFloat(artForm.offer_price) >= parseFloat(artForm.price)) {
      alert('Offer price must be less than the original price.');
      return;
    }
    if (artForm.offer_percent && (parseFloat(artForm.offer_percent) <= 0 || parseFloat(artForm.offer_percent) > 100)) {
      alert('Offer percentage must be between 1 and 100.');
      return;
    }
    setUploading(true);
    try {
      const fd = new FormData();
      fd.append('title', artForm.title);
      fd.append('description', artForm.description || '');
      fd.append('price', String(artForm.price));
      if (artForm.offer_price) fd.append('offer_price', String(artForm.offer_price));
      if (artForm.offer_percent) fd.append('offer_percent', String(artForm.offer_percent));
      if (artForm.offer_starts_at) fd.append('offer_starts_at', artForm.offer_starts_at);
      if (artForm.offer_ends_at) fd.append('offer_ends_at', artForm.offer_ends_at);
      if (artForm.force_offer_badge) fd.append('force_offer_badge', '1');
      if (artForm.category_id) fd.append('category_id', String(artForm.category_id));
      fd.append('availability', artForm.availability);
      fd.append('status', artForm.status);
      fd.append('image', artForm.image);

      const res = await fetch(`${API_BASE}/admin/artworks.php`, {
        method: 'POST',
        headers: { ...adminHeader },
        body: fd
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setArtForm({ title: '', description: '', price: '', offer_price: '', offer_percent: '', offer_starts_at: '', offer_ends_at: '', force_offer_badge: false, category_id: '', availability: 'in_stock', status: 'active', image: null });
        if (imagePreview) URL.revokeObjectURL(imagePreview);
        setImagePreview(null);
        await fetchArtworks();
        setArtNotice({ type: 'success', text: 'Artwork uploaded successfully.' });
      } else {
        setArtNotice({ type: 'error', text: data.message || 'Upload failed' });
      }
    } catch (err) {
      setArtNotice({ type: 'error', text: 'Network error uploading artwork' });
    } finally {
      setUploading(false);
      // Auto-hide message after a short delay
      setTimeout(() => setArtNotice({ type: '', text: '' }), 3000);
    }
  };

  const openEditArtwork = (a) => {
    setEditArtwork({
      id: a.id,
      title: a.title || '',
      description: a.description || '',
      price: a.price || '',
      offer_price: a.offer_price ?? '',
      offer_percent: a.offer_percent ?? '',
      offer_starts_at: a.offer_starts_at ?? '',
      offer_ends_at: a.offer_ends_at ?? '',
      force_offer_badge: !!a.force_offer_badge,
      category_id: a.category_id ?? '',
      availability: a.availability || 'in_stock',
      status: a.status || 'active',
      image_url: a.image_url || ''
    });
  };

  const saveEditArtwork = async () => {
    if (!editArtwork) return;

    // Validation
    const priceNum = Number(editArtwork.price);
    const offerPriceNum = editArtwork.offer_price === '' ? null : Number(editArtwork.offer_price);
    const offerPercentNum = editArtwork.offer_percent === '' ? null : Number(editArtwork.offer_percent);

    if (offerPriceNum !== null && offerPriceNum >= priceNum) {
      alert("Offer price must be less than the regular price.");
      return;
    }
    if (offerPercentNum !== null && (offerPercentNum <= 0 || offerPercentNum > 100)) {
      alert("Offer percent must be between 0 and 100.");
      return;
    }

    setEditSaving(true);
    try {
      const payload = {
        title: editArtwork.title,
        description: editArtwork.description,
        price: priceNum,
        offer_price: offerPriceNum,
        offer_percent: offerPercentNum,
        offer_starts_at: editArtwork.offer_starts_at || null,
        offer_ends_at: editArtwork.offer_ends_at || null,
        force_offer_badge: editArtwork.force_offer_badge ? 1 : 0,
        category_id: editArtwork.category_id === '' ? null : Number(editArtwork.category_id),
        availability: editArtwork.availability,
        status: editArtwork.status,
        image_url: editArtwork.image_url
      };
      const res = await fetch(`${API_BASE}/admin/artworks.php?id=${encodeURIComponent(editArtwork.id)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setEditArtwork(null);
        await fetchArtworks();
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Artwork updated' } }));
      } else {
        alert(data.message || 'Update failed');
      }
    } catch (e) {
      alert('Network error updating artwork');
    } finally {
      setEditSaving(false);
    }
  };

  const deleteArtwork = async (id) => {
    if (!window.confirm('Delete this artwork?')) return;
    try {
      const res = await fetch(`${API_BASE}/admin/artworks.php?id=${encodeURIComponent(id)}`, { method: 'DELETE', headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        await fetchArtworks();
      } else {
        alert(data.message || 'Delete failed');
      }
    } catch {}
  };

  useEffect(() => {
    if (!isLoading && adminHeader["X-Admin-User-Id"]) {
      fetchAll();
      fetchSuppliers();
      fetchRequests();
      fetchArtworks();
      fetchCategories();
      fetchSupplierProducts();
      fetchSupplierInventory();
      // Load fixed warehouse address
      (async ()=>{
        try{
          const res = await fetch(`${API_BASE}/admin/warehouse.php`, { headers: { ...adminHeader }});
          const data = await res.json();
          if (res.ok && data.status==='success') {
            setWarehouseAddress(data.address||'');
            if (data.address_fields) setWarehouseAddressFields(data.address_fields);
          }
        }catch{}
      })();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isLoading, adminHeader]);

  useEffect(() => {
    if (!isLoading && adminHeader["X-Admin-User-Id"]) {
      fetchSuppliers();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filter]);

  useEffect(() => {
    if (!isLoading && adminHeader["X-Admin-User-Id"]) {
      fetchSupplierProducts();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [spStatus, spQuery, spSupplierId]);

  useEffect(() => {
    if (!isLoading && adminHeader["X-Admin-User-Id"]) {
      fetchRequests(reqFilter);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [reqFilter]);

  useEffect(() => {
    if (!isLoading && adminHeader["X-Admin-User-Id"]) {
      fetchReviews();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [reviewsStatus]);

  const act = async (userId, action) => {
    if (!window.confirm(`Confirm ${action} for #${userId}?`)) return;
    try {
      const res = await fetch(`${API_BASE}/admin/suppliers.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json", ...adminHeader },
        body: JSON.stringify({ user_id: userId, action }),
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        fetchAll();
        fetchSuppliers();
      } else {
        alert(data.message || "Action failed");
      }
    } catch {
      alert("Network error");
    }
  };

  if (isLoading) return <div className="admin-content container">Loading…</div>;

  const pendingCount = suppliersAll.filter(s => s.status === 'pending').length;
  const approvedCount = suppliersAll.filter(s => s.status === 'approved').length;
  const rejectedCount = suppliersAll.filter(s => s.status === 'rejected').length;
  const totalSuppliers = suppliersAll.length;

  return (
    <div className="admin-shell">
      {lightboxUrl ? (
        <div
          onClick={() => setLightboxUrl(null)}
          style={{
            position: 'fixed', inset: 0, background: 'rgba(0,0,0,.75)', zIndex: 9999,
            display: 'flex', alignItems: 'center', justifyContent: 'center'
          }}
        >
          <img
            src={lightboxUrl}
            alt={lightboxAlt}
            style={{ maxWidth: '90vw', maxHeight: '90vh', boxShadow: '0 8px 30px rgba(0,0,0,.4)', borderRadius: 8 }}
          />
        </div>
      ) : null}

      <aside className="admin-sidebar">
        <div className="brand">
          <div className="brand-badge">A</div>
          <div className="brand-name">Admin</div>
        </div>
        <nav className="nav">
          <button className={activeSection === 'overview' ? 'active' : ''} onClick={() => { setActiveSection('overview'); refreshAllData(); }} title="Dashboard Overview">Overview</button>
          <button className={activeSection === 'suppliers' ? 'active' : ''} onClick={() => { setActiveSection('suppliers'); fetchSuppliers(filter); fetchAll(); }} title="Suppliers Management">Suppliers</button>
          <button className={activeSection === 'supplier-products' ? 'active' : ''} onClick={() => { setActiveSection('supplier-products'); fetchSupplierProducts(); }} title="Supplier Trending Products">Supplier Trending Products</button>
          <button className={activeSection === 'supplier-inventory' ? 'active' : ''} onClick={() => { setActiveSection('supplier-inventory'); fetchSupplierInventory(); }} title="Supplier Inventory">Supplier Inventory</button>
          <button className={activeSection === 'custom-requests' ? 'active' : ''} onClick={() => { setActiveSection('custom-requests'); fetchRequests(reqFilter); }} title="Custom Requests">Custom Requests</button>
          <button className={activeSection === 'artworks' ? 'active' : ''} onClick={() => { setActiveSection('artworks'); fetchCategories(); fetchArtworks(); }} title="Artwork Gallery">Artworks</button>
          <button className={activeSection === 'requirements' ? 'active' : ''} onClick={() => { setActiveSection('requirements'); fetchRequirements(); }} title="Order Requirements">Order Requirements</button>
          <button className={activeSection === 'reviews' ? 'active' : ''} onClick={() => { setActiveSection('reviews'); fetchReviews(); }} title="Customer Reviews">Customer Reviews</button>
          {/* Promotional Offers removed as requested */}
          <div className="cart-mini" style={{marginTop:12, padding:'10px 8px', background:'#f8f7ff', borderRadius:8}}>
            <div style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
              <div style={{fontWeight:600}}>Cart</div>
              <div style={{display:'flex', gap:'8px'}}>
                <button className="btn btn-soft tiny" onClick={()=> setShowCartDrawer(true)}>Open Cart</button>
              </div>
            </div>
            <div className="muted" style={{marginTop:4, fontSize:12}}>{adminCart.length} items • Rs {cartSubtotal.toFixed(2)}</div>
          </div>
          <button className="btn btn-soft small" onClick={logout} style={{marginTop:10}}>Logout</button>
        </nav>
      </aside>

      <main className="admin-main">
        {/* Full-screen cart overlay */}
        {showCartDrawer && (
          <div style={{position:'fixed', inset:0, background:'rgba(0,0,0,.45)', zIndex:1600, display:'flex', alignItems:'center', justifyContent:'center'}}>
            <div className="box" style={{ width:'min(980px, 95vw)', maxHeight:'88vh', overflow:'auto', background:'#fff', padding:16, borderRadius:12 }}>
              <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:8}}>
                <h3 style={{margin:0}}>Admin Cart</h3>
                <button className="btn" onClick={()=> setShowCartDrawer(false)}>Close</button>
              </div>

              {/* Fixed delivery address */}
              {(warehouseAddressFields || warehouseAddress) ? (
                <div className="muted" style={{marginBottom:8, lineHeight:1.4}}>
                  <div style={{fontWeight:600}}>Deliver to (fixed):</div>
                  {warehouseAddressFields ? (
                    <div style={{whiteSpace:'pre-wrap'}}>
                      {[warehouseAddressFields.name, warehouseAddressFields.address_line1, warehouseAddressFields.address_line2]
                        .filter(Boolean).join(', ')}
                      {"\n"}
                      {[warehouseAddressFields.city, warehouseAddressFields.state, warehouseAddressFields.pincode]
                        .filter(Boolean).join(', ')}
                      {"\n"}
                      {warehouseAddressFields.country}
                      {warehouseAddressFields.phone ? `\nPhone: ${warehouseAddressFields.phone}` : ''}
                    </div>
                  ) : (
                    <div style={{whiteSpace:'pre-wrap'}}>{warehouseAddress}</div>
                  )}
                </div>
              ) : (
                <div className="muted" style={{marginBottom:8}}>No warehouse address set.</div>
              )}

              {adminCart.length===0 ? (
                <div className="muted">No items in cart.</div>
              ) : (
                <>
                  <table className="table">
                    <thead>
                      <tr><th>Name</th><th>Qty</th><th>Colors</th><th>Price</th><th>Total</th><th></th></tr>
                    </thead>
                    <tbody>
                      {adminCart.map(it=> (
                        <tr key={it.id}>
                          <td>{it.name}</td>
                          <td>
                            <div style={{display:'flex',gap:6,alignItems:'center'}}>
                              <span className="muted" style={{minWidth:50, display:'inline-block', textAlign:'center'}}>{it.quantity}</span>
                              <button className="btn btn-soft tiny" onClick={()=> openEditColors(it)}>Edit colors</button>
                            </div>
                          </td>
                          <td>
                            {(it.colors && it.colors.length>0) ? (
                              <span className="muted" style={{fontSize:12}}>
                                {it.colors.map(c=>`${c.color}:${c.qty}`).join(', ')}
                              </span>
                            ) : (
                              <span className="muted">-</span>
                            )}
                          </td>
                          <td>{Number(it.price).toFixed(2)}</td>
                          <td>{(Number(it.price)*Number(it.quantity)).toFixed(2)}</td>
                          <td><button className="btn btn-soft tiny" onClick={()=> setAdminCart(prev=>prev.filter(x=>x.id!==it.id))}>Remove</button></td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  <div style={{display:'flex',justifyContent:'space-between',alignItems:'center', marginTop:8}}>
                    <div className="muted">Subtotal: Rs {cartSubtotal.toFixed(2)}</div>
                    <button className="btn btn-emph" onClick={checkoutAdminCart}>Proceed to Payment</button>
                  </div>
                </>
              )}
            </div>
          </div>
        )}
        <div className="admin-topbar">
          <div className="topbar-inner container">
            <div className="topbar-title">Admin Dashboard</div>
            <div className="topbar-actions">
              {/* Refresh removed as requested */}
            </div>
          </div>
        </div>

        <div className="admin-content">
          <div className="container">
            {activeSection === 'overview' && (
              <>
                {/* Hero */}
                <section className="hero">
                  <div className="hero-card">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
                      <div>
                        <h1>Welcome back</h1>
                        <p className="muted">Manage suppliers, users, and approvals efficiently.</p>
                      </div>
                    </div>
                    <div className="hero-mark">⚙️</div>
                  </div>
                </section>

                {/* Stats */}
                <section className="stats">
                  <div className="grid stats-grid">
                    <div className="stat-card">
                      <div className="stat-label">Total Suppliers</div>
                      <div className="stat-value">{totalSuppliers}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Pending</div>
                      <div className="stat-value">{pendingCount}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Approved</div>
                      <div className="stat-value">{approvedCount}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Rejected</div>
                      <div className="stat-value">{rejectedCount}</div>
                    </div>
                  </div>
                </section>

                {/* Quick Actions */}
                <section className="quick-actions">
                  <div className="grid actions-grid">
                    <button 
                      className="action-card"
                      onClick={() => setActiveSection('custom-requests')}
                    >
                      <div className="action-icon">🎨</div>
                      <h3>Customization Requests</h3>
                      <p>Review and approve customer customization requests</p>
                    </button>
                    <button className="action-card" onClick={() => setActiveSection('supplier-products')}>
                      <div className="action-icon">📦</div>
                      <h3>Supplier Trending Products</h3>
                      <p>Moderate supplier submissions</p>
                    </button>
                    <button className="action-card" onClick={() => setActiveSection('artworks')}>
                      <div className="action-icon">🖼️</div>
                      <h3>Artwork Gallery</h3>
                      <p>Upload and manage artworks</p>
                    </button>
                    <button className="action-card" onClick={() => { setActiveSection('reviews'); fetchReviews(); }}>
                      <div className="action-icon">⭐</div>
                      <h3>Customer Reviews</h3>
                      <p>Moderate and respond to customer reviews</p>
                    </button>
                  </div>
                </section>
              </>
            )}

            {activeSection === 'suppliers' && (
            <section id="suppliers" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Supplier Approvals</h4>
                <div className="controls">
                  <label className="muted">Status</label>
                  <select className="select" value={filter} onChange={(e) => setFilter(e.target.value)}>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="all">All</option>
                  </select>
                </div>
              </div>
              <div className="widget-body">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {suppliers.length === 0 ? (
                      <tr><td colSpan={5} className="muted">No records</td></tr>
                    ) : (
                      suppliers.map((s) => (
                        <tr key={s.id}>
                          <td>{s.id}</td>
                          <td>{s.first_name} {s.last_name}</td>
                          <td>{s.email}</td>
                          <td style={{ textTransform: "capitalize" }}>{s.status}</td>
                          <td className="actions">
                            <div style={{ display: "flex", gap: 8 }}>
                              <button className="btn btn-soft tiny" onClick={() => act(s.id, "approve")} disabled={s.status === 'approved'}>Approve</button>
                              <button className="btn btn-danger tiny" onClick={() => act(s.id, "reject")} disabled={s.status === 'rejected'}>Reject</button>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </section>
            )}

            {activeSection === 'supplier-products' && (
            <section id="supplier-products" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Supplier Trending Products</h4>
                <div className="controls" style={{ display:'flex', gap:8, alignItems:'center', flexWrap:'wrap' }}>

                  <label className="muted">Availability</label>
                  <select className="select" value={spAvailability} onChange={(e)=>setSpAvailability(e.target.value)}>
                    <option value="">Any</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                  </select>
                  <input className="input" placeholder="Supplier ID" value={spSupplierId} onChange={e=>setSpSupplierId(e.target.value)} style={{ width:120 }} />
                  <input className="input" placeholder="Search (name, SKU, category, supplier)" value={spQuery} onChange={e=>setSpQuery(e.target.value)} />
                </div>
              </div>
              <div className="widget-body">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Supplier</th>
                      <th>Image</th>
                      <th>Name</th>
                      <th>Category</th>
                      <th>Price</th>
                      <th>Qty</th>
                      <th>Trending</th>
                      <th>Stock</th>
                      <th>Updated</th>
                      <th style={{width:120}}>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {supplierProducts.length === 0 ? (
                      <tr><td colSpan={11} className="muted">No trending products</td></tr>
                    ) : (
                      supplierProducts.map(p => (
                        <tr key={p.id}>
                          <td>{p.id}</td>
                          <td>{p.supplier_name} <div className="muted" style={{fontSize:12}}>#{p.supplier_id}</div></td>
                          <td>
                            {p.image_url ? (
                              <img
                                src={p.image_url}
                                alt={p.name}
                                style={{ width: 48, height: 48, objectFit: 'cover', borderRadius: 4, cursor: 'zoom-in' }}
                                onClick={() => { setLightboxUrl(p.image_url); setLightboxAlt(p.name || 'Image'); }}
                              />
                            ) : (
                              <span className="muted">No image</span>
                            )}
                          </td>
                          <td>{p.name}</td>
                          <td>{p.category || '-'}</td>
                          <td>{Number(p.price).toFixed(2)}</td>
                          <td>{p.quantity}</td>
                          <td>{p.is_trending ? 'Yes' : 'No'}</td>
                          <td style={{ textTransform:'capitalize' }}>{p.stock}</td>
                          <td>{new Date(p.updated_at).toLocaleString()}</td>
                          <td>
                            <button className="btn btn-soft tiny" onClick={()=> openBuyModal(p)}>Add to Cart</button>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>

                {/* Admin cart panel */}
                <div className="box hide-admin-cart-items" style={{marginTop:12}}>
                  <h5>Admin Cart</h5>
                  <div className="muted" style={{margin:'6px 0 10px'}}>Use Add to Cart for Trending and Inventory. Click Create Purchase Order to pay.</div>
                  {(warehouseAddressFields || warehouseAddress) ? (
                    <div className="muted" style={{marginBottom:8, lineHeight:1.4}}>
                      <div style={{fontWeight:600}}>Deliver to (fixed):</div>
                      {warehouseAddressFields ? (
                        <div style={{whiteSpace:'pre-wrap'}}>
                          {[warehouseAddressFields.name, warehouseAddressFields.address_line1, warehouseAddressFields.address_line2]
                            .filter(Boolean).join(', ')}
                          {"\n"}
                          {[warehouseAddressFields.city, warehouseAddressFields.state, warehouseAddressFields.pincode]
                            .filter(Boolean).join(', ')}
                          {"\n"}
                          {warehouseAddressFields.country}
                          {warehouseAddressFields.phone ? `\nPhone: ${warehouseAddressFields.phone}` : ''}
                        </div>
                      ) : (
                        <div style={{whiteSpace:'pre-wrap'}}>{warehouseAddress}</div>
                      )}
                    </div>
                  ) : null}
                  {adminCart.length===0 ? (
                    <div className="muted">No items added.</div>
                  ) : (
                    <>
                      <table className="table compact">
                        <thead>
                          <tr><th>Name</th><th>Qty</th><th>Colors</th><th>Price</th><th>Total</th><th></th></tr>
                        </thead>
                        <tbody>
                          {adminCart.map(it=> (
                            <tr key={it.id}>
                              <td>{it.name}</td>
                              <td>
                                <div style={{display:'flex',gap:6,alignItems:'center'}}>
                                  <span className="muted" style={{minWidth:50, display:'inline-block', textAlign:'center'}}>{it.quantity}</span>
                                  <button className="btn btn-soft tiny" onClick={()=> openEditColors(it)}>Edit colors</button>
                                </div>
                              </td>
                              <td>
                                {(it.colors && it.colors.length>0) ? (
                                  <span className="muted" style={{fontSize:12}}>
                                    {it.colors.map(c=>`${c.color}:${c.qty}`).join(', ')}
                                  </span>
                                ) : (
                                  <span className="muted">-</span>
                                )}
                              </td>
                              <td>{it.price.toFixed(2)}</td>
                              <td>{(it.price*it.quantity).toFixed(2)}</td>
                              <td><button className="btn btn-soft tiny" onClick={()=> setAdminCart(prev=>prev.filter(x=>x.id!==it.id))}>Remove</button></td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                        <div className="muted">Subtotal: Rs {adminCart.reduce((s,x)=>s + x.price*x.quantity, 0).toFixed(2)}</div>
                        <button className="btn btn-emph" onClick={async()=>{
                          try {
                            // Validate color-wise quantities equal quantity
                            for (const x of adminCart) {
                              if (Array.isArray(x.colors) && x.colors.length) {
                                const sum = x.colors.reduce((a,c)=>a + (parseInt(c.qty||0,10)||0), 0);
                                if (sum !== x.quantity) { alert(`Color quantities must sum to total for ${x.name}`); return; }
                              }
                            }
                            // Lazy-load Razorpay
                            if (!window.Razorpay) {
                              await new Promise((resolve,reject)=>{
                                const s=document.createElement('script');
                                s.src='https://checkout.razorpay.com/v1/checkout.js';
                                s.onload=resolve; s.onerror=()=>reject(new Error('Failed to load Razorpay'));
                                document.body.appendChild(s);
                              });
                            }
                            const res = await fetch(`${API_BASE}/admin/procurement-create-order.php`, {
                              method:'POST',
                              headers: { 'Content-Type':'application/json', ...adminHeader },
                              body: JSON.stringify({ items: adminCart.map(x=>({ id:x.id, quantity:x.quantity, colors: x.colors || [], type: x.type || x.source || 'product' })) })
                            });
                            const data = await res.json();
                            if (data.status !== 'success') { alert(data.message || 'Failed to create order'); return; }
                            const { key_id, order } = data;
                            const rzp = new window.Razorpay({
                              key: key_id,
                              amount: Math.round(order.amount * 100),
                              currency: order.currency || 'INR',
                              name: 'My Little Thingz (Admin PO)',
                              description: `PO ${order.order_number}`,
                              order_id: order.razorpay_order_id,
                              theme: { color: '#6b46c1' },
                              handler: async function (response) {
                                try {
                                  const vres = await fetch(`${API_BASE}/admin/procurement-verify.php`, {
                                    method:'POST',
                                    headers: { 'Content-Type':'application/json', ...adminHeader },
                                    body: JSON.stringify({
                                      purchase_order_id: order.id,
                                      razorpay_order_id: response.razorpay_order_id,
                                      razorpay_payment_id: response.razorpay_payment_id,
                                      razorpay_signature: response.razorpay_signature
                                    })
                                  });
                                  const vdata = await vres.json();
                                  if (vdata.status==='success') { alert('Payment successful'); setAdminCart([]); }
                                  else { alert(vdata.message || 'Payment verification failed'); }
                                } catch(e) { alert('Network error during verification'); }
                              }
                            });
                            rzp.open();
                          } catch(e) { alert(e.message || 'Unable to initialize payment'); }
                        }}>Create Purchase Order</button>
                      </div>
                    </>
                  )}
                </div>

              </div>
            </section>
            )}

            {/* Color & quantity modal */}
            {showColorModal && (
              <div className="modal-backdrop" style={{position:'fixed', inset:0, background:'rgba(0,0,0,0.4)', zIndex:1000, display:'flex', alignItems:'center', justifyContent:'center'}}>
                <div className="box" style={{ width:'min(520px, 92vw)', background:'#fff', padding:16, borderRadius:8 }}>
                  <h5>Enter colors and quantities</h5>
                  <div className="muted" style={{marginBottom:8}}>{colorModalProduct?.name}</div>
                  <table className="table compact">
                    <thead>
                      <tr><th>Color</th><th style={{width:120}}>Qty</th><th style={{width:80}}></th></tr>
                    </thead>
                    <tbody>
                      {colorRows.map((row, idx)=> (
                        <tr key={idx}>
                          <td>
                            <input className="input" placeholder="e.g. Red" value={row.color}
                              onChange={e=>{
                                const v = e.target.value;
                                setColorRows(prev=>prev.map((r,i)=> i===idx ? {...r, color:v} : r));
                              }} />
                          </td>
                          <td>
                            <input type="number" className="input" min={0} value={row.qty}
                              onChange={e=>{
                                const v = Math.max(0, parseInt(e.target.value||'0',10));
                                setColorRows(prev=>prev.map((r,i)=> i===idx ? {...r, qty:v} : r));
                              }} style={{width:110}} />
                          </td>
                          <td>
                            <button className="btn btn-soft tiny" onClick={()=> setColorRows(prev=> prev.filter((_,i)=>i!==idx))}>Remove</button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  <div style={{display:'flex',gap:8,justifyContent:'space-between',alignItems:'center'}}>
                    <button className="btn" onClick={()=> setColorRows(prev=> [...prev, {color:'', qty:1}])}>Add color</button>
                    <div style={{display:'flex',gap:8}}>
                      <button className="btn" onClick={()=> { setShowColorModal(false); setEditingColorsForId(null); }}>Cancel</button>
                      <button className="btn btn-emph" onClick={confirmColorModal}>Confirm</button>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Message thread modal */}
            {messageModal.open && (
              <div className="modal-backdrop" style={{position:'fixed', inset:0, background:'rgba(0,0,0,0.4)', zIndex:1100, display:'flex', alignItems:'center', justifyContent:'center'}}>
                <div className="box" style={{ width:'min(620px, 96vw)', background:'#fff', padding:16, borderRadius:8, maxHeight:'80vh', display:'flex', flexDirection:'column' }}>
                  <h5>Messages • #{messageModal.requirement?.id} · {messageModal.requirement?.supplier_name}</h5>
                  <div className="muted" style={{marginBottom:8}}>Order: {messageModal.requirement?.order_ref} • Material: {messageModal.requirement?.material_name}</div>
                  <div style={{flex:'1 1 auto', overflow:'auto', border:'1px solid #eee', borderRadius:6, padding:8, marginBottom:8}}>
                    {(messageModal.requirement?.messages||[]).length === 0 ? (
                      <div className="muted">No messages yet</div>
                    ) : (
                      messageModal.requirement.messages.map((m,idx)=> (
                        <div key={idx} style={{marginBottom:8}}>
                          <div style={{fontSize:12, color:'#666'}}>{m.sender} • {new Date(m.created_at).toLocaleString()}</div>
                          <div>{m.message}</div>
                        </div>
                      ))
                    )}
                  </div>
                  <textarea className="input" rows={3} placeholder="Type your message..." value={messageModal.text} onChange={e=> setMessageModal(mm=> ({ ...mm, text: e.target.value }))} />
                  <div style={{display:'flex',gap:8, justifyContent:'flex-end', marginTop:8}}>
                    <button className="btn" onClick={()=> setMessageModal({ open:false, requirement:null, text:'' })}>Close</button>
                    <button className="btn btn-emph" onClick={async ()=>{
                      const text = (messageModal.text||'').trim();
                      if (!text) return;
                      try {
                        await fetch(`${API_BASE}/admin/requirements.php`, {
                          method:'POST',
                          headers: { 'Content-Type':'application/json', ...adminHeader },
                          body: JSON.stringify({ requirement_id: messageModal.requirement?.id, message: text })
                        });
                        setMessageModal(mm=> ({ ...mm, text:'' }));
                        await fetchRequirements();
                        // refresh the requirement in the modal with fresh messages
                        const updated = requirements.find(x=> x.id === messageModal.requirement?.id);
                        if (updated) setMessageModal(mm=> ({ ...mm, requirement: updated }));
                      } catch {}
                    }}>Send</button>
                  </div>
                </div>
              </div>
            )}

            {activeSection === 'supplier-inventory' && (
            <section id="supplier-inventory" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Supplier Inventory</h4>
                <div className="controls" style={{ display:'flex', gap:8, alignItems:'center', flexWrap:'wrap' }}>
                  <label className="muted">Availability</label>
                  <select className="select" value={siAvailability} onChange={(e)=>setSiAvailability(e.target.value)}>
                    <option value="">Any</option>
                    <option value="available">Available</option>
                    <option value="out_of_stock">Out of Stock</option>
                  </select>
                  <input className="input" placeholder="Supplier ID" value={siSupplierId} onChange={e=>setSiSupplierId(e.target.value)} style={{ width:120 }} />
                  <input className="input" placeholder="Category" value={siCategory} onChange={e=>setSiCategory(e.target.value)} style={{ width:160 }} />
                  <input className="input" placeholder="Search (name, SKU, tags, brand)" value={siQuery} onChange={e=>setSiQuery(e.target.value)} />
                  <button className="btn" onClick={exportSupplierInventoryCSV}>Export CSV</button>
                </div>
              </div>
              <div className="widget-body">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Supplier</th>
                      <th>Image</th>
                      <th>Name</th>
                      <th>SKU</th>
                      <th>Category</th>
                      <th>Type</th>
                      <th>Size</th>
                      <th>Color</th>
                      <th>Brand</th>
                      <th>Qty</th>
                      <th>Price</th>
                      <th>Availability</th>
                      <th>Updated</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {supplierInventory.length === 0 ? (
                      <tr><td colSpan={15} className="muted">No inventory</td></tr>
                    ) : (
                      supplierInventory.map(m => (
                        <tr key={m.id}>
                          <td>{m.id}</td>
                          <td>{m.supplier_name} <div className="muted" style={{fontSize:12}}>#{m.supplier_id}</div></td>
                          <td>
                            {m.image_url ? (
                              <img
                                src={m.image_url}
                                alt={m.name}
                                style={{ width: 40, height: 40, objectFit: 'cover', borderRadius: 4, cursor: 'zoom-in' }}
                                onClick={() => { setLightboxUrl(m.image_url); setLightboxAlt(m.name || 'Image'); }}
                              />
                            ) : (
                              <span className="muted">-</span>
                            )}
                          </td>
                          <td>{m.name}</td>
                          <td>{m.sku || '-'}</td>
                          <td>{m.category || '-'}</td>
                          <td>{m.type || '-'}</td>
                          <td>{m.size || '-'}</td>
                          <td>{m.color || '-'}</td>
                          <td>{m.brand || '-'}</td>
                          <td>{m.quantity}</td>
                          <td>{Number(m.price||0).toFixed(2)}</td>
                          <td style={{ textTransform:'capitalize' }}>{m.availability}</td>
                          <td>{new Date(m.updated_at).toLocaleString()}</td>
                          <td>
                            <button className="btn btn-soft tiny" onClick={()=> addInventoryToCart(m)}>Add to Cart</button>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </section>
            )}

            {activeSection === 'custom-requests' && (
            <section id="custom-requests" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Custom Requests</h4>
                <div className="controls">
                  <label className="muted">Status</label>
                  <select className="select" value={reqFilter} onChange={(e) => setReqFilter(e.target.value)}>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="all">All</option>
                  </select>
                </div>
              </div>
              <div className="widget-body">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Image</th>
                      <th>Customer</th>
                      <th>Title</th>
                      <th>Occasion</th>
                      <th>Category</th>
                      <th>Budget</th>
                      <th>Deadline</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {requests.length === 0 ? (
                      <tr><td colSpan={10} className="muted">No records</td></tr>
                    ) : (
                      requests.map((r) => (
                        <tr key={r.id}>
                          <td>{r.id}</td>
                          <td>
                            {Array.isArray(r.images) && r.images.length ? (
                              <img
                                src={r.images[0]}
                                alt={r.title || 'Reference'}
                                style={{ width: 48, height: 48, objectFit: 'cover', borderRadius: 4, cursor: 'zoom-in' }}
                                onClick={() => { setLightboxUrl(r.images[0]); setLightboxAlt(r.title || 'Reference'); }}
                              />
                            ) : (
                              <span className="muted">-</span>
                            )}
                          </td>
                          <td>{r.first_name} {r.last_name}<div className="muted" style={{fontSize:12}}>{r.email}</div></td>
                          <td>{r.title}</td>
                          <td>{r.occasion || '-'}</td>
                          <td>{r.category_name || '-'}</td>
                          <td>{(r.budget_min ?? '') || (r.budget_max ?? '') ? `${r.budget_min ?? ''}${r.budget_min && r.budget_max ? ' - ' : ''}${r.budget_max ?? ''}` : '-'}</td>
                          <td>{r.deadline || '-'}</td>
                          <td style={{ textTransform: 'capitalize' }}>{r.status}</td>
                          <td>
                            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                              <button className="btn btn-soft tiny" onClick={() => updateRequestStatus(r.id, 'in_progress')} disabled={r.status==='in_progress'}>Start</button>
                              <button className="btn btn-soft tiny" onClick={() => updateRequestStatus(r.id, 'completed')} disabled={r.status==='completed'}>Complete</button>
                              <button className="btn btn-danger tiny" onClick={() => updateRequestStatus(r.id, 'cancelled')} disabled={r.status==='cancelled'}>Cancel</button>
                              <label className="btn btn-outline tiny">
                                Upload
                                <input type="file" accept="image/*" style={{ display: 'none' }}
                                  onChange={async (e) => {
                                    const f = e.target.files?.[0];
                                    if (f) {
                                      await uploadAdminRequestImage(r.id, f);
                                      e.target.value = '';
                                    }
                                  }}
                                />
                              </label>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </section>
            )}



            {activeSection === 'artworks' && (
            <section id="artworks" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Artwork Gallery</h4>
                <div className="controls">
                </div>
              </div>

              <div className="widget-body">
                {artNotice.text ? (
                  <div className={`notice ${artNotice.type === 'success' ? 'notice-success' : 'notice-error'}`} style={{ marginBottom: 12 }}>
                    {artNotice.text}
                  </div>
                ) : null}
                <form onSubmit={uploadArtwork} className="grid" style={{ gap: 12, alignItems: 'end' }}>
                  <div>
                    <label className="muted">Title</label>
                    <input className="input" value={artForm.title} onChange={e => setArtForm(f => ({...f, title: e.target.value}))} required />
                  </div>
                  {imagePreview ? (
                    <div style={{ gridColumn: '1 / -1' }}>
                      <label className="muted">Preview</label>
                      <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
                        <img src={imagePreview} alt="Preview" style={{ width: 120, height: 120, objectFit: 'cover', borderRadius: 8, boxShadow: '0 1px 4px rgba(0,0,0,.1)' }} />
                        <button type="button" className="btn btn-soft small" onClick={() => { if (imagePreview) URL.revokeObjectURL(imagePreview); setImagePreview(null); setArtForm(f => ({ ...f, image: null })); }}>Clear</button>
                      </div>
                    </div>
                  ) : null}
                  <div>
                    <label className="muted">Price</label>
                    <input type="number" step="0.01" className="input" value={artForm.price} onChange={e => setArtForm(f => ({...f, price: e.target.value}))} required />
                  </div>
                  <div>
                    <label className="muted">Offer Price (optional)</label>
                    <div className="input-with-actions">
                      <input type="number" step="0.01" min="0" max={artForm.price ? parseFloat(artForm.price) - 0.01 : undefined} className="input" value={artForm.offer_price}
                        onChange={e => {
                          const v = e.target.value;
                          setArtForm(f => {
                            const base = parseFloat(f.price) || 0;
                            let nextPercent = f.offer_percent;
                            // Only compute percent if it's currently empty
                            if ((nextPercent === '' || nextPercent == null) && base > 0 && v !== '') {
                              const op = parseFloat(v);
                              if (!isNaN(op)) {
                                const p = Math.max(0, Math.min(100, ((base - op) / base) * 100));
                                nextPercent = p.toFixed(2);
                              }
                            }
                            return { ...f, offer_price: v, offer_percent: nextPercent };
                          });
                        }}
                        placeholder="e.g., 199.99" />
                      <button type="button" className="btn btn-soft small"
                        onClick={() => {
                          setArtForm(f => {
                            const base = parseFloat(f.price) || 0;
                            const perc = parseFloat(f.offer_percent);
                            if (!base || isNaN(base) || isNaN(perc)) {
                              return f;
                            }
                            const clamped = Math.max(0, Math.min(100, perc));
                            const derived = (base * (1 - clamped / 100)).toFixed(2);
                            return { ...f, offer_percent: String(clamped), offer_price: String(derived) };
                          });
                        }}>Apply %</button>
                    </div>
                  </div>
                  <div>
                    <label className="muted">Offer Percent (optional)</label>
                    <div className="input-with-actions">
                      <input type="number" step="0.01" min="0" max="100" className="input" value={artForm.offer_percent}
                        onChange={e => {
                          const v = e.target.value;
                          setArtForm(f => {
                            const base = parseFloat(f.price) || 0;
                            // Only compute offer price if it's currently empty
                            if ((f.offer_price === '' || f.offer_price == null) && base > 0 && v !== '') {
                              const perc = parseFloat(v);
                              if (!isNaN(perc)) {
                                const clamped = Math.max(0, Math.min(100, perc));
                                const op = (base * (1 - clamped / 100)).toFixed(2);
                                return { ...f, offer_percent: String(clamped), offer_price: String(op) };
                              }
                            }
                            return { ...f, offer_percent: v };
                          });
                        }}
                        placeholder="e.g., 20 for 20% off" />
                      <button type="button" className="btn btn-soft small"
                        onClick={() => {
                          setArtForm(f => {
                            const base = parseFloat(f.price) || 0;
                            const offerPrice = parseFloat(f.offer_price);
                            if (!base || isNaN(base) || isNaN(offerPrice)) { return f; }
                            const discount = ((base - offerPrice) / base) * 100;
                            const clamped = Math.max(0, Math.min(100, discount));
                            const normalizedOffer = offerPrice.toFixed(2);
                            return { ...f, offer_percent: clamped.toFixed(2), offer_price: normalizedOffer };
                          });
                        }}>Apply ₹</button>
                    </div>
                  </div>
                  <div>
                    <label className="muted">Offer Starts</label>
                    <input type="datetime-local" className="input" value={artForm.offer_starts_at}
                      onChange={e => setArtForm(f => ({...f, offer_starts_at: e.target.value}))} />
                  </div>
                  <div>
                    <label className="muted">Offer Ends</label>
                    <input type="datetime-local" className="input" value={artForm.offer_ends_at}
                      onChange={e => setArtForm(f => ({...f, offer_ends_at: e.target.value}))} />
                  </div>
                  <div>
                    <label className="muted">Force Offer Badge</label>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                      <input type="checkbox" id="force_offer_badge" checked={!!artForm.force_offer_badge}
                        onChange={e => setArtForm(f => ({ ...f, force_offer_badge: e.target.checked }))} />
                      <label htmlFor="force_offer_badge">Show OFFER ribbon without changing price</label>
                    </div>
                  </div>
                  <div>
                    <label className="muted">Category</label>
                    <select className="select" value={artForm.category_id} onChange={e => setArtForm(f => ({...f, category_id: e.target.value}))}>
                      <option value="">—</option>
                      {[...new Map(categories.map(c => [c.name, c])).values()].map(c => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="muted">Availability</label>
                    <select className="select" value={artForm.availability} onChange={e => setArtForm(f => ({...f, availability: e.target.value}))}>
                      <option value="in_stock">In Stock</option>
                      <option value="out_of_stock">Out of Stock</option>
                      <option value="made_to_order">Made to Order</option>
                    </select>
                  </div>
                  <div>
                    <label className="muted">Status</label>
                    <select className="select" value={artForm.status} onChange={e => setArtForm(f => ({...f, status: e.target.value}))}>
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                  <div style={{ gridColumn: '1 / -1' }}>
                    <label className="muted">Description</label>
                    <textarea className="input" rows={2} value={artForm.description} onChange={e => setArtForm(f => ({...f, description: e.target.value}))} />
                  </div>
                  <div>
                    <label className="muted">Image</label>
                    <input
                      type="file"
                      accept="image/*"
                      className="input"
                      onChange={e => {
                        const file = e.target.files?.[0] || null;
                        setArtForm(f => ({ ...f, image: file }));
                        if (file) {
                          const url = URL.createObjectURL(file);
                          setImagePreview(prev => {
                            if (prev) URL.revokeObjectURL(prev); // cleanup previous
                            return url;
                          });
                        } else {
                          if (imagePreview) URL.revokeObjectURL(imagePreview);
                          setImagePreview(null);
                        }
                      }}
                      required
                    />
                  </div>
                  <div>
                    <button disabled={uploading} className="btn btn-emph" type="submit">{uploading ? 'Uploading…' : 'Add Artwork'}</button>
                  </div>
                </form>

                <div style={{ marginTop: 16 }}>
                  <table className="table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {artworks.length === 0 ? (
                        <tr><td colSpan={7} className="muted">No artworks</td></tr>
                      ) : (
                        artworks.map(a => (
                          <tr key={a.id}>
                            <td>{a.id}</td>
                            <td>{a.image_url ? <img src={a.image_url} alt={a.title} style={{ width: 64, height: 64, objectFit: 'cover', borderRadius: 6, cursor: 'zoom-in' }} onClick={() => { setLightboxUrl(a.image_url); setLightboxAlt(a.title || 'Artwork'); }} /> : '-'}</td>
                            <td>{a.title}</td>
                            <td>{a.category_name || '-'}</td>
                            <td>{a.price}</td>
                            <td style={{ textTransform: 'capitalize' }}>{a.status}</td>
                            <td>
                              <div style={{ display:'flex', gap:8 }}>
                                <button className="btn tiny" onClick={() => openEditArtwork(a)}>Edit</button>
                                <button className="btn btn-danger tiny" onClick={() => deleteArtwork(a.id)}>Delete</button>
                              </div>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
            )}

            {activeSection === 'requirements' && (
            <section id="requirements" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Order Requirements</h4>
                <div className="controls">
                </div>
              </div>
              <div className="widget-body">
                <form onSubmit={async (e) => {
                  e.preventDefault();
                  if (!reqForm.supplier_id || !reqForm.order_ref.trim() || !reqForm.material_name.trim()) { alert('Supplier, Order Ref, and Material are required'); return; }
                  try {
                    const res = await fetch(`${API_BASE}/admin/requirements.php`, {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json', ...adminHeader },
                      body: JSON.stringify(reqForm)
                    });
                    const data = await res.json();
                    if (res.ok && data.status === 'success') {
                      setReqForm({ supplier_id: '', order_ref: '', material_name: '', required_qty: '', unit: 'pcs', due_date: '' });
                      fetchRequirements();
                    } else {
                      alert(data.message || 'Failed to add requirement');
                    }
                  } catch (err) {
                    alert('Network error');
                  }
                }} className="grid" style={{ gap: 12, alignItems: 'end', marginBottom: 16 }}>
                  <div>
                    <label className="muted">Supplier ID</label>
                    <select className="select" required value={String(reqForm.supplier_id)} onChange={e => setReqForm(f => ({ ...f, supplier_id: Number(e.target.value) || '' }))}>
                      <option value="">— Select Supplier —</option>
                      {suppliersAll.filter(s => s.status === 'approved').map(s => (
                        <option key={s.id} value={s.id}>{s.first_name} {s.last_name} (ID: {s.id})</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="muted">Order Reference</label>
                    <input className="input" value={reqForm.order_ref} onChange={e => setReqForm(f => ({ ...f, order_ref: e.target.value }))} required />
                  </div>
                  <div>
                    <label className="muted">Material Name</label>
                    <input className="input" value={reqForm.material_name} onChange={e => setReqForm(f => ({ ...f, material_name: e.target.value }))} required />
                  </div>
                  <div>
                    <label className="muted">Required Qty</label>
                    <input type="number" className="input" value={reqForm.required_qty} onChange={e => setReqForm(f => ({ ...f, required_qty: e.target.value }))} min="0" />
                  </div>
                  <div>
                    <label className="muted">Unit</label>
                    <select className="select" value={reqForm.unit} onChange={e => setReqForm(f => ({ ...f, unit: e.target.value }))}>
                      <option value="pcs">pcs</option>
                      <option value="kg">kg</option>
                      <option value="m">m</option>
                      <option value="l">l</option>
                    </select>
                  </div>
                  <div>
                    <label className="muted">Due Date</label>
                    <input type="date" className="input" value={reqForm.due_date} onChange={e => setReqForm(f => ({ ...f, due_date: e.target.value }))} />
                  </div>
                  <div>
                    <button className="btn btn-emph" type="submit">Add Requirement</button>
                  </div>
                </form>

                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Supplier</th>
                      <th>Order Ref</th>
                      <th>Material</th>
                      <th>Required</th>
                      <th>Due</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {requirements.length === 0 ? (
                      <tr><td colSpan={8} className="muted">No requirements</td></tr>
                    ) : (
                      requirements.map(r => (
                        <tr key={r.id}>
                          <td>{r.id}</td>
                          <td>{r.supplier_name}</td>
                          <td>{r.order_ref}</td>
                          <td>{r.material_name}</td>
                          <td>{r.required_qty} {r.unit}</td>
                          <td>{r.due_date || 'TBD'}</td>
                          <td style={{ textTransform: 'capitalize' }}>{r.status}</td>
                          <td>
                            <button className="btn btn-soft tiny" onClick={() => setMessageModal({ open: true, requirement: r, text: '' })}>Message</button>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </section>
            )}

            {activeSection === 'reviews' && (
            <section id="reviews" className="widget" style={{ marginTop: 12 }}>
              <div className="widget-head">
                <h4>Customer Reviews</h4>
                <div className="controls">
                  <label className="muted">Status</label>
                  <select className="select" value={reviewsStatus} onChange={(e) => setReviewsStatus(e.target.value)}>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                  </select>
                  <button className="btn btn-soft tiny" onClick={fetchReviews}>Refresh</button>
                </div>
              </div>
              <div className="widget-body">
                {reviewsLoading ? (
                  <div className="muted">Loading reviews...</div>
                ) : reviews.length === 0 ? (
                  <div className="muted">No reviews found</div>
                ) : (
                  <div style={{ display: 'grid', gap: 12 }}>
                    {reviews.map((r) => (
                      <div key={r.id} style={{ border: '1px solid #e5e7eb', borderRadius: 8, padding: 12 }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                          <div>
                            <strong>#{r.id} • {r.artwork_title || `Artwork ${r.artwork_id}`}</strong>
                            <div style={{ color: '#6b7280', fontSize: 14 }}>Rating: {r.rating}/5 • {new Date(r.created_at).toLocaleDateString()}</div>
                          </div>
                          <div style={{ display: 'flex', gap: 8 }}>
                            <button 
                              className="btn btn-soft tiny" 
                              onClick={() => updateReview(r.id, { status: 'approved' })}
                              disabled={r.status === 'approved'}
                            >
                              Approve
                            </button>
                            <button 
                              className="btn btn-danger tiny" 
                              onClick={() => updateReview(r.id, { status: 'rejected' })}
                              disabled={r.status === 'rejected'}
                            >
                              Reject
                            </button>
                          </div>
                        </div>
                        {r.comment && (
                          <div style={{ marginBottom: 8, padding: 8, background: '#f9fafb', borderRadius: 4 }}>
                            <strong>Customer Review:</strong> {r.comment}
                          </div>
                        )}
                        <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                          <input
                            type="text"
                            placeholder="Reply to customer..."
                            value={reviewsReplyDraft[r.id] ?? (r.admin_reply || '')}
                            onChange={(e) => setReviewsReplyDraft({ ...reviewsReplyDraft, [r.id]: e.target.value })}
                            style={{ flex: 1, padding: 6, border: '1px solid #d1d5db', borderRadius: 4 }}
                          />
                          <button 
                            className="btn btn-soft tiny" 
                            onClick={() => updateReview(r.id, { admin_reply: reviewsReplyDraft[r.id] ?? '' })}
                          >
                            Reply
                          </button>
                        </div>
                        {r.admin_reply && (
                          <div style={{ marginTop: 8, padding: 8, background: '#f0f9ff', borderRadius: 4, fontSize: 14 }}>
                            <strong>Admin Reply:</strong> {r.admin_reply}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </section>
            )}

            {/* Promotional Offers section removed as requested */}
          </div>
        </div>
      </main>

      {/* Edit Artwork Modal */}
      {editArtwork && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.4)', zIndex: 10000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }}>
          <div style={{ background: '#fff', borderRadius: 8, width: 'min(860px, 96vw)', maxHeight: '90vh', overflow: 'auto', boxShadow: '0 10px 30px rgba(0,0,0,.2)' }}>
            <div style={{ padding: '12px 16px', borderBottom: '1px solid #eee', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <h3 style={{ margin: 0 }}>Edit Artwork #{editArtwork.id}</h3>
              <button className="btn small" onClick={() => setEditArtwork(null)}>Close</button>
            </div>
            <div style={{ padding: 16 }}>
              <div className="grid" style={{ gap: 12, alignItems: 'end' }}>
                <div>
                  <label className="muted">Title</label>
                  <input className="input" value={editArtwork.title} onChange={e => setEditArtwork(f => ({ ...f, title: e.target.value }))} />
                </div>
                <div>
                  <label className="muted">Price</label>
                  <input type="number" step="0.01" className="input" value={editArtwork.price} onChange={e => setEditArtwork(f => ({ ...f, price: e.target.value }))} />
                </div>
                <div>
                  <label className="muted">Offer Price (optional)</label>
                  <div className="input-with-actions">
                    <input type="number" step="0.01" min="0" max={editArtwork.price ? parseFloat(editArtwork.price) - 0.01 : undefined} className="input" value={editArtwork.offer_price}
                      onChange={e => {
                        const v = e.target.value;
                        setEditArtwork(f => {
                          const base = parseFloat(f.price) || 0;
                          let nextPercent = f.offer_percent;
                          // Only compute percent if it's currently empty
                          if ((nextPercent === '' || nextPercent == null) && base > 0 && v !== '') {
                            const op = parseFloat(v);
                            if (!isNaN(op)) {
                              const p = Math.max(0, Math.min(100, ((base - op) / base) * 100));
                              nextPercent = p.toFixed(2);
                            }
                          }
                          return { ...f, offer_price: v, offer_percent: nextPercent };
                        });
                      }}
                      placeholder="e.g., 199.99" />
                    <button type="button" className="btn btn-soft small"
                      onClick={() => {
                        setEditArtwork(f => {
                          const base = parseFloat(f.price) || 0;
                          const perc = parseFloat(f.offer_percent);
                          if (!base || isNaN(base) || isNaN(perc)) {
                            return f;
                          }
                          const clamped = Math.max(0, Math.min(100, perc));
                          const derived = (base * (1 - clamped / 100)).toFixed(2);
                          return { ...f, offer_percent: String(clamped), offer_price: String(derived) };
                        });
                      }}>Apply %</button>
                  </div>
                </div>
                <div>
                  <label className="muted">Offer Percent (optional)</label>
                  <div className="input-with-actions">
                    <input type="number" step="0.01" min="0" max="100" className="input" value={editArtwork.offer_percent}
                      onChange={e => {
                        const v = e.target.value;
                        setEditArtwork(f => {
                          const base = parseFloat(f.price) || 0;
                          // Only compute offer price if it's currently empty
                          if ((f.offer_price === '' || f.offer_price == null) && base > 0 && v !== '') {
                            const perc = parseFloat(v);
                            if (!isNaN(perc)) {
                              const clamped = Math.max(0, Math.min(100, perc));
                              const op = (base * (1 - clamped / 100)).toFixed(2);
                              return { ...f, offer_percent: String(clamped), offer_price: String(op) };
                            }
                          }
                          return { ...f, offer_percent: v };
                        });
                      }}
                      placeholder="e.g., 20 for 20% off" />
                    <button type="button" className="btn btn-soft small"
                      onClick={() => {
                        setEditArtwork(f => {
                          const base = parseFloat(f.price) || 0;
                          const offerPrice = parseFloat(f.offer_price);
                          if (!base || isNaN(base) || isNaN(offerPrice)) { return f; }
                          const discount = ((base - offerPrice) / base) * 100;
                          const clamped = Math.max(0, Math.min(100, discount));
                          const normalizedOffer = offerPrice.toFixed(2);
                          return { ...f, offer_percent: clamped.toFixed(2), offer_price: normalizedOffer };
                        });
                      }}>Apply ₹</button>
                  </div>
                </div>
                <div>
                  <label className="muted">Offer Starts</label>
                  <input type="datetime-local" className="input"
                    value={editArtwork.offer_starts_at ? editArtwork.offer_starts_at.replace(' ', 'T').slice(0, 16) : ''}
                    onChange={e => setEditArtwork(f => ({ ...f, offer_starts_at: e.target.value }))} />
                </div>
                <div>
                  <label className="muted">Offer Ends</label>
                  <input type="datetime-local" className="input"
                    value={editArtwork.offer_ends_at ? editArtwork.offer_ends_at.replace(' ', 'T').slice(0, 16) : ''}
                    onChange={e => setEditArtwork(f => ({ ...f, offer_ends_at: e.target.value }))} />
                </div>
                <div>
                  <label className="muted">Force Offer Badge</label>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <input type="checkbox" id="edit_force_offer_badge" checked={!!editArtwork.force_offer_badge}
                      onChange={e => setEditArtwork(f => ({ ...f, force_offer_badge: e.target.checked }))} />
                    <label htmlFor="edit_force_offer_badge">Show OFFER ribbon without changing price</label>
                  </div>
                </div>
                <div>
                  <label className="muted">Category</label>
                  <select className="select" value={editArtwork.category_id ?? ''} onChange={e => setEditArtwork(f => ({ ...f, category_id: e.target.value }))}>
                    <option value="">—</option>
                    {[...new Map(categories.map(c => [c.name, c])).values()].map(c => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="muted">Availability</label>
                  <select className="select" value={editArtwork.availability} onChange={e => setEditArtwork(f => ({ ...f, availability: e.target.value }))}>
                    <option value="in_stock">In Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                    <option value="made_to_order">Made to Order</option>
                  </select>
                </div>
                <div>
                  <label className="muted">Status</label>
                  <select className="select" value={editArtwork.status} onChange={e => setEditArtwork(f => ({ ...f, status: e.target.value }))}>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
                <div style={{ gridColumn: '1 / -1' }}>
                  <label className="muted">Image URL</label>
                  <input className="input" value={editArtwork.image_url || ''} onChange={e => setEditArtwork(f => ({ ...f, image_url: e.target.value }))} placeholder="https://..." />
                </div>
                <div style={{ gridColumn: '1 / -1' }}>
                  <label className="muted">Description</label>
                  <textarea className="input" rows={3} value={editArtwork.description} onChange={e => setEditArtwork(f => ({ ...f, description: e.target.value }))} />
                </div>
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 16 }}>
                <button className="btn" onClick={() => setEditArtwork(null)} disabled={editSaving}>Cancel</button>
                <button className="btn btn-emph" onClick={saveEditArtwork} disabled={editSaving}>{editSaving ? 'Saving…' : 'Save Changes'}</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Customization Requests Modal */}
      {showCustomizationRequests && (
        <CustomizationRequests onClose={() => setShowCustomizationRequests(false)} />
      )}
    </div>
  );
}