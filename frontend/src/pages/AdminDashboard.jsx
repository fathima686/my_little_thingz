import React, { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
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
      <aside className="admin-sidebar">
        <div className="brand">
          <div className="brand-badge">A</div>
          <div className="brand-name">Admin</div>
        </div>
        <nav className="nav">
          <a className="active" href="#overview">Overview</a>
          <a href="#suppliers">Suppliers</a>
          <a href="#custom-requests">Custom Requests</a>
          <a href="#users">Users</a>
          <a href="#settings">Settings</a>
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

            {/* Supplier Approvals */}
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

            {/* Custom Requests */}
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
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </section>

            {/* Artwork Gallery (Admin) */}
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
                    <input type="file" accept="image/*" className="input" onChange={e => setArtForm(f => ({...f, image: e.target.files?.[0] || null}))} required />
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
                            <td>{a.image_url ? <img src={a.image_url} alt={a.title} style={{ width: 64, height: 64, objectFit: 'cover', borderRadius: 6 }} /> : '-'}</td>
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
          </div>
        </div>
      </main>
    </div>
  );
}