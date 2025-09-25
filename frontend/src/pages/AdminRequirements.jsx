import React, { useEffect, useMemo, useState } from "react";
import { useAuth } from "../contexts/AuthContext";

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function AdminRequirements() {
  const { auth } = useAuth();

  // Admin header (same convention as AdminDashboard)
  const adminHeader = useMemo(() => {
    const id = auth?.user_id ? Number(auth.user_id) : 0;
    return id > 0 ? { "X-Admin-User-Id": String(id) } : {};
  }, [auth]);

  // Filters
  const [q, setQ] = useState("");
  const [status, setStatus] = useState(""); // pending|packed|fulfilled|cancelled|''
  const [supplierId, setSupplierId] = useState("");

  // Data
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [updating, setUpdating] = useState(false);

  // New requirement form
  const [form, setForm] = useState({ supplier_id: "", order_ref: "", material_name: "", required_qty: "", unit: "pcs", due_date: "" });

  const fetchList = async () => {
    setLoading(true);
    try {
      const url = new URL(`${API_BASE}/admin/requirements.php`);
      if (q) url.searchParams.set('q', q);
      if (status) url.searchParams.set('status', status);
      if (supplierId) url.searchParams.set('supplier_id', supplierId);
      const res = await fetch(url.toString(), { headers: { ...adminHeader } });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setItems(data.items || []);
      } else {
        alert(data.message || 'Failed to load requirements');
      }
    } catch {
      alert('Network error loading requirements');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchList(); /* eslint-disable-next-line */ }, []);

  const postMessage = async (requirement_id, message) => {
    if (!message.trim()) return;
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/admin/requirements.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify({ requirement_id, message })
      });
      const data = await res.json();
      if (!(res.ok && data.status === 'success')) {
        alert(data.message || 'Failed to send message');
      } else {
        await fetchList();
      }
    } finally {
      setUpdating(false);
    }
  };

  const createRequirement = async (e) => {
    e.preventDefault();
    if (!form.supplier_id || !form.order_ref.trim() || !form.material_name.trim()) {
      alert('supplier_id, order_ref and material_name required');
      return;
    }
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/admin/requirements.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...adminHeader },
        body: JSON.stringify({
          supplier_id: Number(form.supplier_id),
          order_ref: form.order_ref,
          material_name: form.material_name,
          required_qty: Number(form.required_qty) || 0,
          unit: form.unit || 'pcs',
          due_date: form.due_date || undefined
        })
      });
      const data = await res.json();
      if (!(res.ok && data.status === 'success')) {
        alert(data.message || 'Create failed');
      } else {
        setForm({ supplier_id: "", order_ref: "", material_name: "", required_qty: "", unit: 'pcs', due_date: "" });
        await fetchList();
      }
    } finally {
      setUpdating(false);
    }
  };

  return (
    <div className="container" style={{ padding: 24 }}>
      <h2 style={{ marginTop: 0 }}>Admin · Supplier Requirements</h2>

      {/* Filters */}
      <div className="grid" style={{ gap: 8, gridTemplateColumns: '1fr 1fr 1fr auto' }}>
        <input className="select" placeholder="Search (order_ref, material, supplier)" value={q} onChange={e=>setQ(e.target.value)} />
        <select className="select" value={status} onChange={e=>setStatus(e.target.value)}>
          <option value="">All statuses</option>
          <option value="pending">pending</option>
          <option value="packed">packed</option>
          <option value="fulfilled">fulfilled</option>
          <option value="cancelled">cancelled</option>
        </select>
        <input className="select" placeholder="Supplier ID" value={supplierId} onChange={e=>setSupplierId(e.target.value)} />
        <button className="btn btn-soft" onClick={fetchList} disabled={loading}>Refresh</button>
      </div>

      {/* Create requirement */}
      <form onSubmit={createRequirement} className="grid" style={{ gap: 8, gridTemplateColumns: 'repeat(6, 1fr)', margin: '12px 0' }}>
        <input className="select" placeholder="Supplier ID" required value={form.supplier_id} onChange={e=>setForm(v=>({...v, supplier_id:e.target.value}))} />
        <input className="select" placeholder="Order Ref" required value={form.order_ref} onChange={e=>setForm(v=>({...v, order_ref:e.target.value}))} />
        <input className="select" placeholder="Material Name" required value={form.material_name} onChange={e=>setForm(v=>({...v, material_name:e.target.value}))} />
        <input className="select" placeholder="Qty" type="number" value={form.required_qty} onChange={e=>setForm(v=>({...v, required_qty:e.target.value}))} />
        <select className="select" value={form.unit} onChange={e=>setForm(v=>({...v, unit:e.target.value}))}>
          <option value="pcs">pcs</option>
          <option value="sets">sets</option>
          <option value="kg">kg</option>
        </select>
        <div style={{ display:'flex', gap:8 }}>
          <input className="select" type="date" value={form.due_date} onChange={e=>setForm(v=>({...v, due_date:e.target.value}))} />
          <button className="btn btn-emph" type="submit" disabled={updating}>Create</button>
        </div>
      </form>

      {/* List */}
      {loading ? (
        <div>Loading…</div>
      ) : (
        <div className="grid" style={{ gap: 10 }}>
          {items.map(r => (
            <div key={r.id} className="order-item">
              <div>
                <div className="order-title">#{r.id} · {r.order_ref} — {r.material_name}</div>
                <div className="order-date">
                  Supplier: {r.supplier_name || ''} (ID {r.supplier_id}) · Qty {r.required_qty} {r.unit} · Due {r.due_date || 'TBD'} · Status {r.status}
                </div>
                {/* Messages */}
                <div style={{ marginTop: 8 }}>
                  <div className="muted" style={{ marginBottom: 6 }}>Messages</div>
                  <div style={{ background:'#f7f7f7', padding: 8, borderRadius: 6 }}>
                    {(r.messages || []).map((m, idx) => (
                      <div key={idx} style={{ marginBottom: 6 }}>
                        <div style={{ fontSize: 12, color: '#555' }}>{m.sender} • {new Date(m.created_at).toLocaleString()}</div>
                        <div>{m.message}</div>
                      </div>
                    ))}
                    <MessageBox disabled={updating} onSend={(text)=>postMessage(r.id, text)} />
                  </div>
                </div>
              </div>
              <div style={{ display:'flex', flexDirection:'column', alignItems:'flex-end', gap:8 }}>
                <span className={`status ${r.status==='pending' ? 'info' : 'success'}`}>{r.status}</span>
                <span className="muted" style={{ fontSize: 12 }}>Updated {new Date(r.updated_at).toLocaleString()}</span>
              </div>
            </div>
          ))}
          {items.length === 0 && <div className="ph-card" />}
        </div>
      )}
    </div>
  );
}

function MessageBox({ onSend, disabled }) {
  const [text, setText] = useState('');
  return (
    <div style={{ display:'flex', gap:8, marginTop:8 }}>
      <input className="input" placeholder="Type a message to supplier" value={text} onChange={(e)=>setText(e.target.value)} />
      <button className="btn btn-soft" disabled={disabled || !text.trim()} onClick={()=>{ onSend(text); setText(''); }}>Send</button>
    </div>
  );
}