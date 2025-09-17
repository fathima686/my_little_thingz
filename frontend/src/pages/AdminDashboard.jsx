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
  const [showCustomizationRequests, setShowCustomizationRequests] = useState(false);
  const [activeSection, setActiveSection] = useState('overview'); // overview | suppliers | supplier-products | supplier-inventory | custom-requests | artworks | settings
  const [artForm, setArtForm] = useState({
    title: "",
    description: "",
    price: "",
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

  // Supplier Inventory view state (view-only)
  const [supplierInventory, setSupplierInventory] = useState([]);
  const [siQuery, setSiQuery] = useState("");
  const [siAvailability, setSiAvailability] = useState("");
  const [siSupplierId, setSiSupplierId] = useState("");
  const [siCategory, setSiCategory] = useState("");

  // Lightbox for images
  const [lightboxUrl, setLightboxUrl] = useState(null);
  const [lightboxAlt, setLightboxAlt] = useState("");

  // Derive admin header for simple authorization to backend admin endpoints
  const adminHeader = useMemo(() => {
    const id = auth?.user_id ? Number(auth.user_id) : 0;
    return id > 0 ? { "X-Admin-User-Id": String(id) } : {};
  }, [auth]);

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

  // Supplier Products: list and approve/reject
  const fetchSupplierProducts = async (opts = {}) => {
    const { q = spQuery, supplier_id = spSupplierId, availability = spAvailability } = opts;
    const url = new URL(`${API_BASE}/admin/supplier-products.php`);
    if (q) url.searchParams.set('q', q);
    if (supplier_id) url.searchParams.set('supplier_id', supplier_id);
    if (availability) url.searchParams.set('availability', availability);
    try {
      const res = await fetch(url.toString(), { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setSupplierProducts(data.items || []);
      }
    } catch {}
  };

  // Supplier Inventory: view-only list
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
    setUploading(true);
    try {
      const fd = new FormData();
      fd.append('title', artForm.title);
      fd.append('description', artForm.description || '');
      fd.append('price', String(artForm.price));
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
        setArtForm({ title: '', description: '', price: '', category_id: '', availability: 'in_stock', status: 'active', image: null });
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
          <button className={activeSection === 'overview' ? 'active' : ''} onClick={() => setActiveSection('overview')}>Overview</button>
          <button className={activeSection === 'suppliers' ? 'active' : ''} onClick={() => { setActiveSection('suppliers'); fetchSuppliers(filter); fetchAll(); }}>Suppliers</button>
          <button className={activeSection === 'supplier-products' ? 'active' : ''} onClick={() => { setActiveSection('supplier-products'); fetchSupplierProducts(); }}>Supplier Products</button>
          <button className={activeSection === 'supplier-inventory' ? 'active' : ''} onClick={() => { setActiveSection('supplier-inventory'); fetchSupplierInventory(); }}>Supplier Inventory</button>
          <button className={activeSection === 'custom-requests' ? 'active' : ''} onClick={() => { setActiveSection('custom-requests'); fetchRequests(reqFilter); }}>Custom Requests</button>
          <button className={activeSection === 'artworks' ? 'active' : ''} onClick={() => { setActiveSection('artworks'); fetchCategories(); fetchArtworks(); }}>Artworks</button>
          <button className="btn btn-soft small" onClick={logout}>Logout</button>
        </nav>
      </aside>

      <main className="admin-main">
        <div className="admin-topbar">
          <div className="topbar-inner container">
            <div className="topbar-title">Admin Dashboard</div>
            <div className="topbar-actions">
              <button className="btn btn-soft">Refresh</button>
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
                    <h1>Welcome back</h1>
                    <p className="muted">Manage suppliers, users, and approvals efficiently.</p>
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
                      <h3>Supplier Products</h3>
                      <p>Moderate supplier submissions</p>
                    </button>
                    <button className="action-card" onClick={() => setActiveSection('artworks')}>
                      <div className="action-icon">🖼️</div>
                      <h3>Artwork Gallery</h3>
                      <p>Upload and manage artworks</p>
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
                  <button className="btn btn-emph" onClick={() => fetchSuppliers()}>Refresh</button>
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
                <h4>Supplier Products</h4>
                <div className="controls" style={{ display:'flex', gap:8, alignItems:'center', flexWrap:'wrap' }}>

                  <label className="muted">Availability</label>
                  <select className="select" value={spAvailability} onChange={(e)=>setSpAvailability(e.target.value)}>
                    <option value="">Any</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                  </select>
                  <input className="input" placeholder="Supplier ID" value={spSupplierId} onChange={e=>setSpSupplierId(e.target.value)} style={{ width:120 }} />
                  <input className="input" placeholder="Search (name, SKU, category, supplier)" value={spQuery} onChange={e=>setSpQuery(e.target.value)} />
                  <button className="btn btn-emph" onClick={()=>fetchSupplierProducts()}>Refresh</button>
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
                      <th>Trend</th>
                      <th>Stock</th>
                      <th>Updated</th>
                    </tr>
                  </thead>
                  <tbody>
                    {supplierProducts.length === 0 ? (
                      <tr><td colSpan={10} className="muted">No products</td></tr>
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
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </section>
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
                  <button className="btn btn-emph" onClick={()=>fetchSupplierInventory()}>Refresh</button>
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
                      <th>Availability</th>
                      <th>Updated</th>
                    </tr>
                  </thead>
                  <tbody>
                    {supplierInventory.length === 0 ? (
                      <tr><td colSpan={13} className="muted">No inventory</td></tr>
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
                          <td style={{ textTransform:'capitalize' }}>{m.availability}</td>
                          <td>{new Date(m.updated_at).toLocaleString()}</td>
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
                  <button className="btn btn-emph" onClick={() => fetchRequests(reqFilter)}>Refresh</button>
                </div>
              </div>
              <div className="widget-body">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
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
                      <tr><td colSpan={9} className="muted">No records</td></tr>
                    ) : (
                      requests.map((r) => (
                        <tr key={r.id}>
                          <td>{r.id}</td>
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
                  <button className="btn btn-emph" onClick={fetchArtworks}>Refresh</button>
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
                              <button className="btn btn-danger tiny" onClick={() => deleteArtwork(a.id)}>Delete</button>
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
          </div>
        </div>
      </main>

      {/* Customization Requests Modal */}
      {showCustomizationRequests && (
        <CustomizationRequests onClose={() => setShowCustomizationRequests(false)} />
      )}
    </div>
  );
}