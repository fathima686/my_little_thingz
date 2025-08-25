import React, { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  LuPackage, LuBoxes, LuClipboardList, LuUpload, LuLogOut, LuUser
} from "react-icons/lu";
import { useAuth } from "../contexts/AuthContext";
import logo from "../assets/logo.png";
import "../styles/dashboard.css";

const API_BASE = "http://localhost/my_little_thingz/backend/api/supplier";

export default function SupplierDashboard() {
  const navigate = useNavigate();
  const { auth, logout, isLoading } = useAuth();
  const [supplierId, setSupplierId] = useState(0);
  const [inventory, setInventory] = useState([]);
  const [requirements, setRequirements] = useState([]);
  const [form, setForm] = useState({ name: "", sku: "", quantity: 0, unit: "pcs" });
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    if (auth?.user_id) {
      setSupplierId(Number(auth.user_id));
    }
  }, [auth]);

  const headers = useMemo(() => ({ "Content-Type": "application/json", "X-SUPPLIER-ID": String(supplierId) }), [supplierId]);

  const loadData = async () => {
    if (!supplierId) return;
    const inv = await fetch(`${API_BASE}/inventory.php?supplier_id=${supplierId}`);
    const invJson = await inv.json();
    if (inv.ok && invJson.status === "success") setInventory(invJson.items);

    const req = await fetch(`${API_BASE}/requirements.php?supplier_id=${supplierId}`);
    const reqJson = await req.json();
    if (req.ok && reqJson.status === "success") setRequirements(reqJson.items);
  };

  useEffect(() => { loadData(); }, [supplierId]);

  const handleLogout = () => {
    logout();
  };

  const handleAdd = async (e) => {
    e.preventDefault();
    if (!form.name.trim()) { alert("Material name required"); return; }
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/inventory.php`, {
        method: "POST",
        headers,
        body: JSON.stringify({ ...form })
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        setForm({ name: "", sku: "", quantity: 0, unit: "pcs" });
        await loadData();
      } else {
        alert(data.message || "Failed to add material");
      }
    } finally { setUpdating(false); }
  };

  const handleUpdateQty = async (id, quantity) => {
    setUpdating(true);
    try {
      const item = inventory.find(i => i.id === id);
      const res = await fetch(`${API_BASE}/inventory.php`, {
        method: "PUT",
        headers,
        body: JSON.stringify({ id, name: item.name, sku: item.sku, quantity: Number(quantity), unit: item.unit })
      });
      const data = await res.json();
      if (!(res.ok && data.status === "success")) {
        alert(data.message || "Update failed");
      }
      await loadData();
    } finally { setUpdating(false); }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("Delete this material?")) return;
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/inventory.php?id=${id}&supplier_id=${supplierId}`, { method: "DELETE", headers });
      const data = await res.json();
      if (!(res.ok && data.status === "success")) {
        alert(data.message || "Delete failed");
      }
      await loadData();
    } finally { setUpdating(false); }
  };

  const markPacked = async (id) => {
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/requirements.php`, {
        method: "PUT",
        headers,
        body: JSON.stringify({ id, status: "packed" })
      });
      const data = await res.json();
      if (!(res.ok && data.status === "success")) {
        alert(data.message || "Failed to update status");
      }
      await loadData();
    } finally { setUpdating(false); }
  };

  return (
    <div className="dash-page">
      {/* Header */}
      <header className="dash-header">
        <div className="container dash-header-inner">
          <div className="brand">
            <img src={logo} alt="My Little Thingz" className="brand-logo" />
            <span className="brand-name">Supplier Portal</span>
          </div>
          <nav className="dash-nav">
            <Link to="#" className="nav-item"><LuBoxes /> Inventory</Link>
            <Link to="#" className="nav-item"><LuClipboardList /> Requirements</Link>
            <Link to="#" className="nav-item"><LuUser /> Profile</Link>
            <button type="button" className="btn btn-soft small" onClick={handleLogout}><LuLogOut /> Logout</button>
          </nav>
        </div>
      </header>

      {/* Hero */}
      <section className="dash-hero">
        <div className="container">
          <div className="hero-card">
            <div className="hero-copy">
              <h1>Welcome Supplier</h1>
              <p>Manage materials and pack order requirements</p>
              <button className="btn btn-emph"><LuUpload /> Upload Availability</button>
            </div>
            <div className="hero-mark" aria-hidden>
              <LuPackage />
            </div>
          </div>
        </div>
      </section>

      {/* Actions */}
      <section className="dash-actions">
        <div className="container grid actions-grid">
          <div className="action-card">
            <div className="action-icon"><LuBoxes /></div>
            <h3>Add Material</h3>
            <p>Record availability</p>
          </div>
          <div className="action-card">
            <div className="action-icon"><LuClipboardList /></div>
            <h3>View Requirements</h3>
            <p>See packing list from orders</p>
          </div>
          <div className="action-card">
            <div className="action-icon"><LuUpload /></div>
            <h3>Bulk Upload (coming soon)</h3>
            <p>CSV support</p>
          </div>
        </div>
      </section>

      {/* Widgets */}
      <section className="dash-widgets">
        <div className="container grid widgets-grid">
          {/* Inventory widget */}
          <article className="widget">
            <header className="widget-head">
              <h4><LuBoxes /> Inventory</h4>
              <button className="btn btn-soft tiny" onClick={loadData} disabled={updating}>Refresh</button>
            </header>
            <div className="widget-body">
              <form onSubmit={handleAdd} className="grid" style={{gridTemplateColumns:'1.5fr 1fr .8fr .6fr auto', gap: 10}}>
                <input placeholder="Name" value={form.name} onChange={e=>setForm({...form, name:e.target.value})} required />
                <input placeholder="SKU" value={form.sku} onChange={e=>setForm({...form, sku:e.target.value})} />
                <input type="number" placeholder="Qty" value={form.quantity} onChange={e=>setForm({...form, quantity:Number(e.target.value)})} min={0} />
                <input placeholder="Unit" value={form.unit} onChange={e=>setForm({...form, unit:e.target.value})} />
                <button className="btn btn-emph" type="submit" disabled={updating}><LuUpload /> Add</button>
              </form>
              <div className="grid" style={{gap:10}}>
                {inventory.map(item => (
                  <div key={item.id} className="order-item" style={{alignItems:'center'}}>
                    <div>
                      <div className="order-title">{item.name} {item.sku && <span className="muted">({item.sku})</span>}</div>
                      <div className="order-date">{item.unit} — updated {new Date(item.updated_at).toLocaleString()}</div>
                    </div>
                    <div style={{display:'flex', gap:8, alignItems:'center'}}>
                      <input type="number" value={item.quantity} min={0} onChange={(e)=>handleUpdateQty(item.id, e.target.value)} style={{width:90}} />
                      <button className="btn btn-soft tiny" onClick={()=>handleDelete(item.id)} disabled={updating}>Delete</button>
                    </div>
                  </div>
                ))}
                {inventory.length === 0 && <div className="ph-card" />}
              </div>
            </div>
          </article>

          {/* Requirements widget */}
          <article className="widget">
            <header className="widget-head">
              <h4><LuClipboardList /> Order Requirements</h4>
              <p className="muted">Mark packed when ready</p>
            </header>
            <div className="widget-body">
              {requirements.map(req => (
                <div key={req.id} className="order-item">
                  <div>
                    <div className="order-title">{req.order_ref} — {req.material_name}</div>
                    <div className="order-date">Need {req.required_qty} {req.unit} by {req.due_date || 'TBD'}</div>
                  </div>
                  <div>
                    <span className={`status ${req.status==='packed'?'success':'info'}`}>{req.status}</span>
                    {req.status !== 'packed' && (
                      <button className="btn btn-soft tiny" onClick={()=>markPacked(req.id)} disabled={updating} style={{marginLeft:8}}>Mark Packed</button>
                    )}
                  </div>
                </div>
              ))}
              {requirements.length === 0 && <div className="ph-card" />}
            </div>
          </article>
        </div>
      </section>
    </div>
  );
}