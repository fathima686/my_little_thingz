import React, { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import {
  LuPackage, LuClipboardList, LuUpload
} from "react-icons/lu";

// Reuse the Admin theme so Supplier matches the Admin look & feel
import "../styles/admin.css";

const API_SUP = "http://localhost/my_little_thingz/backend/api/supplier";
const API_CUST = "http://localhost/my_little_thingz/backend/api/customer";

export default function SupplierDashboard() {
  const navigate = useNavigate();
  const { auth, logout } = useAuth();

  // Navigation state similar to Admin
  const [activeSection, setActiveSection] = useState('overview'); // overview | products | inventory | requirements | profile

  // Supplier identity
  const [supplierId, setSupplierId] = useState(0);

  // Data
  const [requirements, setRequirements] = useState([]);
  const [products, setProducts] = useState([]);
  const [materials, setMaterials] = useState([]); // kept for overview stats (can be removed later)
  const [allMaterials, setAllMaterials] = useState([]); // full list for inventory section

  // Forms
  const [matForm, setMatForm] = useState({ name: "", sku: "", category: "", quantity: "", image_url: "" });
  const [updating, setUpdating] = useState(false);

  // Product modal (edit/delete)
  const [prodModalOpen, setProdModalOpen] = useState(false);
  const [prodForm, setProdForm] = useState({ id: 0, name: "", price: 0, quantity: 0, category: "", image_url: "", availability: "available", is_trending: 0 });

  // Material modal (edit/delete)
  const [matModalOpen, setMatModalOpen] = useState(false);
  const [matEditForm, setMatEditForm] = useState({ id: 0, name: "", sku: "", category: "", quantity: 0, image_url: "" });

  // Requirements message modal
  const [reqModalOpen, setReqModalOpen] = useState(false);
  const [reqModalData, setReqModalData] = useState({ id: 0, order_ref: '', material_name: '', messages: [] });
  const [newMessage, setNewMessage] = useState('');

  const [categories, setCategories] = useState([]);

  useEffect(() => {
    if (auth?.user_id) setSupplierId(Number(auth.user_id));
  }, [auth]);

  const headers = useMemo(() => ({ "Content-Type": "application/json", "X-SUPPLIER-ID": String(supplierId) }), [supplierId]);

  const loadData = async () => {
    if (!supplierId) return;
    try {
      // Requirements
      const req = await fetch(`${API_SUP}/requirements.php?supplier_id=${supplierId}`);
      const reqJson = await req.json();
      if (req.ok && reqJson.status === "success") setRequirements(reqJson.items || []);

      // Products (include trending first)
      const prod = await fetch(`${API_SUP}/products.php?supplier_id=${supplierId}`);
      const prodJson = await prod.json();
      if (prod.ok && prodJson.status === "success") setProducts(prodJson.items || []);

      // Materials inventory — for overview count
      const inv = await fetch(`${API_SUP}/inventory.php?supplier_id=${supplierId}`);
      const invJson = await inv.json();
      if (inv.ok && invJson.status === "success") {
        setMaterials(invJson.items || []);
        setAllMaterials(invJson.items || []); // also set here
      }
    } catch (e) {
      console.error(e);
      alert("Network error loading dashboard");
    }
  };

  const loadInventory = async () => {
    if (!supplierId) return;
    try {
      const inv = await fetch(`${API_SUP}/inventory.php?supplier_id=${supplierId}`);
      const invJson = await inv.json();
      if (inv.ok && invJson.status === "success") setAllMaterials(invJson.items || []);
    } catch (e) {
      console.error(e);
      alert("Network error loading inventory");
    }
  };

  useEffect(() => { loadData(); /* eslint-disable-next-line */ }, [supplierId]);

  useEffect(() => {
    if (activeSection === 'inventory') {
      loadInventory();
    }
  }, [activeSection, supplierId]);

  // Load categories for selects (admin-managed)
  useEffect(() => {
    (async () => {
      try {
        const res = await fetch(`${API_CUST}/categories.php`);
        const data = await res.json();
        if (res.ok && data.status === 'success' && Array.isArray(data.categories)) {
          const names = [...new Set(data.categories.map(c => c.name).filter(Boolean))];
          setCategories(names);
        } else {
          setCategories(['Gift box','boquetes','frames','poloroid','custom chocolate','Wedding card','drawings','album']);
        }
      } catch {
        setCategories(['Gift box','boquetes','frames','poloroid','custom chocolate','Wedding card','drawings','album']);
      }
    })();
  }, []);

  // Actions
  const handleAddMaterial = async (e) => {
    e.preventDefault();
    if (!supplierId) { alert("Not logged in as supplier. Please login again."); return; }
    if (!matForm.name.trim()) { alert("Material name required"); return; }
    setUpdating(true);
    try {
      const res = await fetch(`${API_SUP}/inventory.php?supplier_id=${supplierId}`, {
        method: "POST",
        headers,
        body: JSON.stringify({
          name: matForm.name,
          sku: matForm.sku,
          category: matForm.category,
          quantity: Number(matForm.quantity) || 0,
          image_url: matForm.image_url
        })
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        setMatForm({ name: "", sku: "", category: "", quantity: 0, image_url: "" });
        await loadInventory();
        setActiveSection('inventory');
      } else {
        alert(data.message || "Failed to add material");
      }
    } finally { setUpdating(false); }
  };

  const markPacked = async (id) => {
    setUpdating(true);
    try {
      const res = await fetch(`${API_SUP}/requirements.php?supplier_id=${supplierId}`, {
        method: "PUT",
        headers,
        body: JSON.stringify({ id, status: "packed" })
      });
      const data = await res.json();
      if (!(res.ok && data.status === "success")) {
        alert(data.message || "Failed to update status");
      }
      await loadData();
      setActiveSection('requirements');
    } finally { setUpdating(false); }
  };

  const openReqModal = (req) => {
    setReqModalData({ id: req.id, order_ref: req.order_ref, material_name: req.material_name, messages: req.messages || [] });
    setReqModalOpen(true);
  };

  const sendMessage = async () => {
    if (!newMessage.trim()) return;
    try {
      const res = await fetch(`${API_SUP}/requirements.php?supplier_id=${supplierId}`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ requirement_id: reqModalData.id, message: newMessage })
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setNewMessage('');
        await loadData(); // refresh requirements to update messages
        const updatedReq = requirements.find(r => r.id === reqModalData.id);
        if (updatedReq) setReqModalData({ ...reqModalData, messages: updatedReq.messages });
      } else {
        alert(data.message || 'Failed to send message');
      }
    } catch (e) {
      alert('Network error');
    }
  };

  // Derived counts for Overview
  const totalProducts = products.length;
  const totalMaterials = materials.length;
  const pendingReqs = requirements.filter(r => r.status !== 'packed').length;

  return (
    <div className="admin-shell">{/* Reuse Admin shell styles */}
      <aside className="admin-sidebar">
        <div className="brand">
          <div className="brand-badge">S</div>
          <div className="brand-name">Supplier</div>
        </div>
        <nav className="nav">
          <button className={activeSection === 'overview' ? 'active' : ''} onClick={() => setActiveSection('overview')}>Overview</button>
          <button className={activeSection === 'products' ? 'active' : ''} onClick={() => setActiveSection('products')}>Supplier Trending Products</button>
          <button className={activeSection === 'inventory' ? 'active' : ''} onClick={() => setActiveSection('inventory')}>Inventory</button>
          <button className={activeSection === 'requirements' ? 'active' : ''} onClick={() => setActiveSection('requirements')}>Order Requirements</button>
          <button className={activeSection === 'profile' ? 'active' : ''} onClick={() => navigate('/supplier/profile')}>Profile</button>
          <button className="btn btn-soft small" onClick={logout}>Logout</button>
        </nav>
      </aside>

      <main className="admin-main">
        <div className="admin-topbar">
          <div className="topbar-inner container">
            <div className="topbar-title">Supplier Dashboard</div>
            <div className="topbar-actions">
              <button className="btn btn-soft" onClick={loadData}>Refresh</button>
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
                    <h1>Welcome</h1>
                    <p className="muted">Manage inventory, products, and packing requirements efficiently.</p>
                    <div className="hero-mark">📦</div>
                  </div>
                </section>

                {/* Stats */}
                <section className="stats">
                  <div className="grid stats-grid">
                    <div className="stat-card">
                      <div className="stat-label">Supplier Trending Products</div>
                      <div className="stat-value">{totalProducts}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Inventory Items</div>
                      <div className="stat-value">{totalMaterials}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Pending Packs</div>
                      <div className="stat-value">{pendingReqs}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Approved Status</div>
                      <div className="stat-value">{auth?.is_supplier_approved ? 'Yes' : 'No'}</div>
                    </div>
                  </div>
                </section>

                {/* Quick Actions */}
                <section className="quick-actions">
                  <div className="grid actions-grid">
                    <button className="action-card" onClick={() => setActiveSection('requirements')}>
                      <div className="action-icon">🧾</div>
                      <h3>Order Requirements</h3>
                      <p>Check what's needed and mark packed</p>
                    </button>
                    <button className="action-card" onClick={() => setActiveSection('products')}>
                      <div className="action-icon">📦</div>
                      <h3>Supplier Trending Products</h3>
                      <p>Add items for admin review</p>
                    </button>
                    <button className="action-card" onClick={() => setActiveSection('inventory')}>
                      <div className="action-icon">🧰</div>
                      <h3>Inventory</h3>
                      <p>Track raw materials and stock</p>
                    </button>
                  </div>
                </section>
              </>
            )}

            {activeSection === 'products' && (
              <section id="products" className="widget" style={{ marginTop: 12 }}>
                <div className="widget-head">
                  <h4><LuPackage /> Supplier Trending Products</h4>
                  <div className="controls">
                    <button className="btn btn-emph" onClick={() => navigate('/supplier/products/new')}><LuUpload /> Add Product</button>
                  </div>
                </div>
                <div className="widget-body">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Trending</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {products.length === 0 ? (
                        <tr><td colSpan={10} className="muted">No products yet</td></tr>
                      ) : (
                        products.map(p => (
                          <tr key={p.id}>
                            <td>{p.id}</td>
                            <td>{p.image_url ? <img alt={p.name} src={p.image_url} style={{width:48,height:48,objectFit:'cover',borderRadius:6}}/> : '—'}</td>
                            <td>{p.name}</td>
                            <td>{p.category || '-'}</td>
                            <td>{Number(p.price).toFixed(2)}</td>
                            <td>{p.quantity}</td>
                            <td>{p.is_trending ? 'Yes' : 'No'}</td>
                            <td style={{ textTransform:'capitalize' }}>{p.status || '-'}</td>
                            <td>{new Date(p.updated_at).toLocaleString()}</td>
                            <td>
                              <button className="btn btn-soft tiny" onClick={()=>{ setProdForm({ id:p.id, name:p.name, price:p.price, quantity:p.quantity, category:p.category, image_url:p.image_url, availability:p.availability, is_trending:p.is_trending }); setProdModalOpen(true); }}>Edit</button>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </section>
            )}

            {activeSection === 'inventory' && (
              <section id="inventory" className="widget" style={{ marginTop: 12 }}>
                <div className="widget-head">
                  <h4><LuClipboardList /> Inventory</h4>
                  <div className="controls" style={{display:'flex', gap:8}}>
                    <button className="btn btn-soft" onClick={()=>setActiveSection('overview')}>Back</button>
                    <span className="muted">Add new material</span>
                  </div>
                </div>
                <div className="widget-body">
                  <form className="grid" style={{gap:10, gridTemplateColumns:'repeat(4, 1fr)'}} onSubmit={handleAddMaterial}>
                    <input className="select" placeholder="Name (e.g., Handmade paper)" value={matForm.name} onChange={e=>setMatForm(v=>({...v, name:e.target.value}))} />
                    <input className="select" placeholder="SKU (opt)" value={matForm.sku} onChange={e=>setMatForm(v=>({...v, sku:e.target.value}))} />
                    <select className="select" value={matForm.category} onChange={e=>setMatForm(v=>({...v, category:e.target.value}))}>
                      <option value="">— Select category —</option>
                      {categories.map(opt => (
                        <option key={opt} value={opt}>{opt}</option>
                      ))}
                    </select>
                    <input className="select" placeholder="Qty" type="number" value={matForm.quantity} onChange={e=>setMatForm(v=>({...v, quantity:e.target.value}))} />

                    <div style={{gridColumn:'span 6'}}>
                      <label className="muted">Or upload image</label>
                      <input className="select" type="file" accept="image/png,image/jpeg,image/webp" onChange={async (e)=>{
                        const file = e.target.files?.[0];
                        if (!file) return;
                        try {
                          const fd = new FormData();
                          fd.append('image', file);
                          const up = await fetch(`${API_SUP}/upload.php?supplier_id=${supplierId}`, { method:'POST', body: fd, headers: { 'X-SUPPLIER-ID': String(supplierId) } });
                          const upJson = await up.json();
                          if (up.ok && upJson.status === 'success') {
                            // store uploaded url internally; field is hidden from UI
                            setMatForm(v=>({...v, image_url: upJson.url }));
                          } else {
                            alert(upJson.message || 'Upload failed');
                          }
                        } catch (err) {
                          alert('Upload error');
                        }
                      }} />
                    </div>
                    <div style={{gridColumn:'span 6'}}>
                      <button className="btn btn-emph" disabled={updating} type="submit">Add to Inventory</button>
                    </div>
                  </form>

                  <table className="table" style={{ marginTop: 12 }}>
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Availability</th>
                        <th>Updated</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {allMaterials.length === 0 ? (
                        <tr><td colSpan={8} className="muted">No inventory yet</td></tr>
                      ) : (
                        allMaterials.map(m => (
                          <tr key={m.id}>
                            <td>{m.id}</td>
                            <td style={{display:'flex', alignItems:'center', gap:8}}>
                              {m.image_url ? <img alt={m.name} src={m.image_url} style={{width:36,height:36,objectFit:'cover',borderRadius:6}}/> : null}
                              <span>{m.name}</span>
                            </td>
                            <td>{m.category || '—'}</td>
                            <td>{m.sku || '—'}</td>
                            <td>{m.quantity}</td>
                            <td style={{ textTransform:'capitalize' }}>{m.availability}</td>
                            <td>{new Date(m.updated_at).toLocaleString()}</td>
                            <td>
                              <button className="btn btn-soft tiny" onClick={() => {
                                setMatEditForm({ id: m.id, name: m.name, sku: m.sku || '', category: m.category || '', quantity: m.quantity, image_url: m.image_url || '' });
                                setMatModalOpen(true);
                              }}>Edit</button>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </section>
            )}

            {/* Product Edit Modal */}
            {prodModalOpen && (
              <div className="modal-overlay">
                <div className="modal-content" style={{maxWidth:560}}>
                  <div className="modal-head">
                    <h3>Edit Product</h3>
                    <button className="btn btn-soft small" onClick={()=>setProdModalOpen(false)}>Close</button>
                  </div>
                  <div className="modal-body">
                    <div className="grid" style={{gap:10, gridTemplateColumns:'repeat(2, 1fr)'}}>
                      <input className="select" placeholder="Name" value={prodForm.name} onChange={e=>setProdForm(v=>({...v, name:e.target.value}))} />
                      <select className="select" value={prodForm.category} onChange={e=>setProdForm(v=>({...v, category: e.target.value }))}>
                        <option value="">— Select category —</option>
                        {categories.map(n => (<option key={n} value={n}>{n}</option>))}
                      </select>
                      <input className="select" placeholder="Price" type="number" step="0.01" value={prodForm.price} onChange={e=>setProdForm(v=>({...v, price:e.target.value}))} />
                      <input className="select" placeholder="Qty" type="number" value={prodForm.quantity} onChange={e=>setProdForm(v=>({...v, quantity:e.target.value}))} />

                      <input className="select" placeholder="Image URL" value={prodForm.image_url} onChange={e=>setProdForm(v=>({...v, image_url:e.target.value}))} />
                      <div style={{gridColumn:'span 2'}}>
                        <label className="muted">Or upload image</label>
                        <input className="select" type="file" accept="image/png,image/jpeg,image/webp" onChange={async (e)=>{
                          const file = e.target.files?.[0];
                          if (!file) return;
                          try {
                            const fd = new FormData();
                            fd.append('image', file);
                            const up = await fetch(`${API_SUP}/upload.php?supplier_id=${supplierId}`, { method:'POST', body: fd, headers: { 'X-SUPPLIER-ID': String(supplierId) } });
                            const upJson = await up.json();
                            if (up.ok && upJson.status === 'success') {
                              setProdForm(v=>({...v, image_url: upJson.url }));
                            } else {
                              alert(upJson.message || 'Upload failed');
                            }
                          } catch (err) {
                            alert('Upload error');
                          }
                        }} />
                      </div>
                      <select className="select" value={prodForm.availability} onChange={e=>setProdForm(v=>({...v, availability:e.target.value}))}>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                      </select>
                      <label style={{display:'flex',alignItems:'center',gap:8}}>
                        <input type="checkbox" checked={!!prodForm.is_trending} onChange={e=>setProdForm(v=>({...v, is_trending: e.target.checked ? 1 : 0}))} /> Trending
                      </label>
                    </div>
                  </div>
                  <div className="modal-foot" style={{display:'flex',justifyContent:'space-between'}}>
                    <button className="btn btn-soft" onClick={async ()=>{
                      if (!supplierId || !prodForm.id) return;
                      setUpdating(true);
                      try {
                        const res = await fetch(`${API_SUP}/products.php?supplier_id=${supplierId}`, {
                          method: 'PUT',
                          headers,
                          body: JSON.stringify(prodForm)
                        });
                        const data = await res.json();
                        if (!(res.ok && data.status==='success')) { alert(data.message || 'Update failed'); return; }
                        await loadData();
                        setProdModalOpen(false);
                      } finally { setUpdating(false); }
                    }}>Save</button>
                    <button className="btn btn-danger" onClick={async ()=>{
                      if (!supplierId || !prodForm.id) return;
                      if (!confirm('Delete this product?')) return;
                      setUpdating(true);
                      try {
                        const res = await fetch(`${API_SUP}/products.php?supplier_id=${supplierId}&id=${prodForm.id}`, { method:'DELETE', headers });
                        const data = await res.json();
                        if (!(res.ok && data.status==='success')) { alert(data.message || 'Delete failed'); return; }
                        await loadData();
                        setProdModalOpen(false);
                      } finally { setUpdating(false); }
                    }}>Delete</button>
                  </div>
                </div>
              </div>
            )}

            {/* Material Edit Modal */}
            {matModalOpen && (
              <div className="modal-overlay">
                <div className="modal-content" style={{maxWidth:560}}>
                  <div className="modal-head">
                    <h3>Edit Material</h3>
                    <button className="btn btn-soft small" onClick={()=>setMatModalOpen(false)}>Close</button>
                  </div>
                  <div className="modal-body">
                    <div className="grid" style={{gap:10, gridTemplateColumns:'repeat(2, 1fr)'}}>
                      <input className="select" placeholder="Name" value={matEditForm.name} onChange={e=>setMatEditForm(v=>({...v, name:e.target.value}))} />
                      <input className="select" placeholder="SKU" value={matEditForm.sku} onChange={e=>setMatEditForm(v=>({...v, sku:e.target.value}))} />
                      <select className="select" value={matEditForm.category} onChange={e=>setMatEditForm(v=>({...v, category: e.target.value }))}>
                        <option value="">— Select category —</option>
                        {categories.map(n => (<option key={n} value={n}>{n}</option>))}
                      </select>
                      <input className="select" placeholder="Qty" type="number" value={matEditForm.quantity} onChange={e=>setMatEditForm(v=>({...v, quantity:e.target.value}))} />

                      <input className="select" placeholder="Image URL" value={matEditForm.image_url} onChange={e=>setMatEditForm(v=>({...v, image_url:e.target.value}))} />
                      <div style={{gridColumn:'span 2'}}>
                        <label className="muted">Or upload image</label>
                        <input className="select" type="file" accept="image/png,image/jpeg,image/webp" onChange={async (e)=>{
                          const file = e.target.files?.[0];
                          if (!file) return;
                          try {
                            const fd = new FormData();
                            fd.append('image', file);
                            const up = await fetch(`${API_SUP}/upload.php?supplier_id=${supplierId}`, { method:'POST', body: fd, headers: { 'X-SUPPLIER-ID': String(supplierId) } });
                            const upJson = await up.json();
                            if (up.ok && upJson.status === 'success') {
                              setMatEditForm(v=>({...v, image_url: upJson.url }));
                            } else {
                              alert(upJson.message || 'Upload failed');
                            }
                          } catch (err) {
                            alert('Upload error');
                          }
                        }} />
                      </div>
                    </div>
                  </div>
                  <div className="modal-foot" style={{display:'flex',justifyContent:'space-between'}}>
                    <button className="btn btn-soft" onClick={async ()=>{
                      if (!supplierId || !matEditForm.id) return;
                      setUpdating(true);
                      try {
                        const res = await fetch(`${API_SUP}/inventory.php?supplier_id=${supplierId}`, {
                          method: 'PUT',
                          headers,
                          body: JSON.stringify(matEditForm)
                        });
                        const data = await res.json();
                        if (!(res.ok && data.status==='success')) { alert(data.message || 'Update failed'); return; }
                        await loadInventory();
                        setMatModalOpen(false);
                      } finally { setUpdating(false); }
                    }}>Save</button>
                    <button className="btn btn-danger" onClick={async ()=>{
                      if (!supplierId || !matEditForm.id) return;
                      if (!confirm('Delete this material?')) return;
                      setUpdating(true);
                      try {
                        const res = await fetch(`${API_SUP}/inventory.php?supplier_id=${supplierId}&id=${matEditForm.id}`, { method:'DELETE', headers });
                        const data = await res.json();
                        if (!(res.ok && data.status==='success')) { alert(data.message || 'Delete failed'); return; }
                        await loadInventory();
                        setMatModalOpen(false);
                      } finally { setUpdating(false); }
                    }}>Delete</button>
                  </div>
                </div>
              </div>
            )}

            {activeSection === 'requirements' && (
              <section id="requirements" className="widget" style={{ marginTop: 12 }}>
                <div className="widget-head">
                  <h4>Order Requirements</h4>
                </div>
                <div className="widget-body">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>ID</th>
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
                        <tr><td colSpan={7} className="muted">No requirements yet</td></tr>
                      ) : (
                        requirements.map(req => (
                          <tr key={req.id}>
                            <td>{req.id}</td>
                            <td>{req.order_ref}</td>
                            <td>{req.material_name}</td>
                            <td>{req.required_qty} {req.unit}</td>
                            <td>{req.due_date || 'TBD'}</td>
                            <td style={{ textTransform:'capitalize' }}>{req.status}</td>
                            <td>
                              <button className="btn btn-soft tiny" onClick={() => openReqModal(req)}>Messages</button>
                              {req.status !== 'packed' && (
                                <button className="btn btn-soft tiny" disabled={updating} onClick={() => markPacked(req.id)}>Mark Packed</button>
                              )}
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </section>
            )}
          </div>
        </div>
      </main>

      {/* Requirements Messages Modal */}
      {reqModalOpen && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.45)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          <div className="box" style={{ width: 'min(600px, 95vw)', maxHeight: '80vh', overflow: 'auto', background: '#fff', padding: 20, borderRadius: 12 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
              <h3 style={{ margin: 0 }}>Messages for {reqModalData.order_ref} - {reqModalData.material_name}</h3>
              <button className="btn" onClick={() => setReqModalOpen(false)}>Close</button>
            </div>
            <div style={{ marginBottom: 16, maxHeight: '300px', overflowY: 'auto', border: '1px solid #ddd', padding: 10, borderRadius: 8 }}>
              {reqModalData.messages.length === 0 ? (
                <div className="muted">No messages yet.</div>
              ) : (
                reqModalData.messages.map((msg, i) => (
                  <div key={i} style={{ marginBottom: 8, padding: 8, background: msg.sender === 'admin' ? '#f0f8ff' : '#f0fff0', borderRadius: 6 }}>
                    <div style={{ fontWeight: 'bold', fontSize: 12 }}>{msg.sender === 'admin' ? 'Admin' : 'You'} • {new Date(msg.created_at).toLocaleString()}</div>
                    <div>{msg.message}</div>
                  </div>
                ))
              )}
            </div>
            <div style={{ display: 'flex', gap: 8 }}>
              <input className="input" placeholder="Type your message..." value={newMessage} onChange={e => setNewMessage(e.target.value)} onKeyDown={e => e.key === 'Enter' && sendMessage()} />
              <button className="btn btn-emph" onClick={sendMessage}>Send</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}