import React, { useMemo, useState } from 'react';
import { createKeydownHandler } from "../utils/validation";
import { Link, useNavigate } from 'react-router-dom';
import { LuPackage, LuArrowLeft, LuUpload } from 'react-icons/lu';
import { useAuth } from '../contexts/AuthContext';
import logo from "../assets/logo.png";

const API_BASE = "http://localhost/my_little_thingz/backend/api/supplier";
const CUSTOMER_API = "http://localhost/my_little_thingz/backend/api/customer";

export default function SupplierProductNew() {
  const navigate = useNavigate();
  const { auth } = useAuth();
  const supplierId = Number(auth?.user_id || 0);

  const [form, setForm] = useState({
    name: '',
    category: '',
    price: '',
    quantity: '',
    availability: 'available',
    is_trending: false,
    image_url: '',
    description: ''
  });
  const [saving, setSaving] = useState(false);
  const [categories, setCategories] = useState([]); // array of category names
  const [selectedCategory, setSelectedCategory] = useState('');
  const headers = useMemo(() => ({ 'Content-Type': 'application/json', 'X-SUPPLIER-ID': String(supplierId) }), [supplierId]);

  const submit = async (e) => {
    e.preventDefault();
    if (!supplierId) { alert('Not logged in as supplier'); return; }
    if (!form.name.trim()) { alert('Product name required'); return; }
    setSaving(true);
    try {
      let imageUrl = form.image_url;
      if (form.file) {
        const fd = new FormData();
        fd.append('image', form.file);
        const up = await fetch(`${API_BASE}/upload.php?supplier_id=${supplierId}`, { method: 'POST', body: fd, headers: { 'X-SUPPLIER-ID': String(supplierId) } });
        const upJson = await up.json();
        if (!(up.ok && upJson.status === 'success')) {
          alert(upJson.message || 'Image upload failed');
          setSaving(false);
          return;
        }
        imageUrl = upJson.url;
      }

      const res = await fetch(`${API_BASE}/products.php?supplier_id=${supplierId}`, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          name: form.name,
          category: form.category,
          price: Number(form.price) || 0,
          quantity: Number(form.quantity) || 0,
          availability: form.availability,
          is_trending: !!form.is_trending,
          image_url: imageUrl,
          description: form.description
        })
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        navigate('/supplier');
      } else {
        alert(data.message || 'Failed to create product');
      }
    } finally {
      setSaving(false);
    }
  };

  React.useEffect(() => {
    // Load categories from customer API and map to unique names
    (async () => {
      try {
        const res = await fetch(`${CUSTOMER_API}/categories.php`);
        const data = await res.json();
        if (res.ok && data.status === 'success' && Array.isArray(data.categories)) {
          const names = [...new Set(data.categories.map(c => c.name).filter(Boolean))];
          setCategories(names);
        } else {
          setCategories(['Polaroids', 'Chocolates', 'Frames', 'Albums', 'Wedding Cards', 'Birthday Theme Boxes', 'Wedding Hampers', 'Gift Boxes', 'Bouquets']);
        }
      } catch (e) {
        setCategories(['Polaroids', 'Chocolates', 'Frames', 'Albums', 'Wedding Cards', 'Birthday Theme Boxes', 'Wedding Hampers', 'Gift Boxes', 'Bouquets']);
      }
    })();
  }, []);

  return (
    <div className="dash-page">
      <header className="dash-header">
        <div className="container dash-header-inner">
          <div className="brand">
            <img src={logo} alt="My Little Thingz" className="brand-logo" />
            <span className="brand-name">Supplier Portal</span>
          </div>
          <nav className="dash-nav">
            <Link to="/supplier" className="nav-item"><LuArrowLeft /> Back to Dashboard</Link>
          </nav>
        </div>
      </header>

      <main className="container" style={{ maxWidth: 880, marginTop: 24 }}>
        <div className="widget">
          <div className="widget-head">
            <h4><LuPackage /> New Product</h4>
            <div className="muted">Fill product details and submit for admin review</div>
          </div>
          <div className="widget-body">
            <form onSubmit={submit} className="grid" style={{ gridTemplateColumns: '1fr 1fr', gap: 16 }}>
              <div style={{ gridColumn: '1 / -1' }}>
                <label className="muted">Name</label>
                <input className="input" value={form.name} onChange={e=>setForm({ ...form, name: e.target.value })} onKeyDown={createKeydownHandler(true)} required />
              </div>

              <div>
                <label className="muted">Category</label>
                <select className="select" value={form.category} onChange={e=>setForm({ ...form, category: e.target.value })}>
                  <option value="">— Select —</option>
                  {categories.map(name => (
                    <option key={name} value={name}>{name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="muted">Price</label>
                <input className="input" type="number" step="0.01" value={form.price} onChange={e=>setForm({ ...form, price: e.target.value })} min={0} />
              </div>
              <div>
                <label className="muted">Quantity</label>
                <input className="input" type="number" value={form.quantity} onChange={e=>setForm({ ...form, quantity: e.target.value })} min={0} />
              </div>

              <div>
                <label className="muted">Availability</label>
                <select className="select" value={form.availability} onChange={e=>setForm({ ...form, availability: e.target.value })}>
                  <option value="available">Available</option>
                  <option value="unavailable">Unavailable</option>
                </select>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <input id="trend" type="checkbox" checked={form.is_trending} onChange={e=>setForm({ ...form, is_trending: e.target.checked })} />
                <label htmlFor="trend">Mark as Trending</label>
              </div>

              <div style={{ gridColumn: '1 / -1' }}>
                <label className="muted">Image</label>
                <input className="input" type="file" accept="image/png,image/jpeg,image/webp" onChange={e=>setForm({ ...form, file: e.target.files?.[0] || null })} />
                {form.image_url && <div className="muted" style={{ marginTop: 6 }}>Uploaded: {form.image_url}</div>}
              </div>

              <div style={{ gridColumn: '1 / -1' }}>
                <label className="muted">Description</label>
                <textarea className="input" rows={3} value={form.description} onChange={e=>setForm({ ...form, description: e.target.value })} onKeyDown={createKeydownHandler(true)} />
              </div>

              <div style={{ gridColumn: '1 / -1', display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                <button type="button" className="btn btn-soft" onClick={()=>navigate('/supplier')}>Cancel</button>
                <button className="btn btn-emph" type="submit" disabled={saving}><LuUpload /> {saving ? 'Saving…' : 'Save Product'}</button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  );
}