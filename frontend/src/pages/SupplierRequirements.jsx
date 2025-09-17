import React, { useEffect, useMemo, useState } from "react";
import { useAuth } from "../contexts/AuthContext";
import { LuClipboardList } from "react-icons/lu";

const API_BASE = "http://localhost/my_little_thingz/backend/api/supplier";

function ReplyBox({ onSend, disabled }) {
  const [text, setText] = React.useState('');
  return (
    <div style={{ display:'flex', gap:8, marginTop:8 }}>
      <input className="input" placeholder="Type your reply" value={text} onChange={(e)=>setText(e.target.value)} />
      <button className="btn btn-soft" disabled={disabled || !text.trim()} onClick={()=>{ onSend(text); setText(''); }}>Send</button>
    </div>
  );
}

export default function SupplierRequirements() {
  const { auth } = useAuth();
  const [supplierId, setSupplierId] = useState(0);
  const [requirements, setRequirements] = useState([]);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  useEffect(() => { if (auth?.user_id) setSupplierId(Number(auth.user_id)); }, [auth]);
  const headers = useMemo(() => ({ "Content-Type": "application/json", "X-SUPPLIER-ID": String(supplierId) }), [supplierId]);

  const load = async () => {
    if (!supplierId) return;
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/requirements.php?supplier_id=${supplierId}`);
      const data = await res.json();
      if (res.ok && data.status === 'success') setRequirements(data.items || []);
    } finally { setLoading(false); }
  };

  useEffect(() => { load(); }, [supplierId]);

  const markPacked = async (id) => {
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/requirements.php?supplier_id=${supplierId}`, {
        method: 'PUT', headers, body: JSON.stringify({ id, status: 'packed' })
      });
      const data = await res.json();
      if (!(res.ok && data.status === 'success')) alert(data.message || 'Failed to update');
      await load();
    } finally { setUpdating(false); }
  };

  const sendReply = async (requirementId, message) => {
    if (!message.trim()) return;
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/requirements.php?supplier_id=${supplierId}`, {
        method: 'POST', headers, body: JSON.stringify({ requirement_id: requirementId, message })
      });
      const data = await res.json();
      if (!(res.ok && data.status === 'success')) alert(data.message || 'Failed to send');
      await load();
    } finally { setUpdating(false); }
  };

  const [newReq, setNewReq] = useState({ order_ref: '', material_name: '', required_qty: '', unit: 'pcs', due_date: '' });

  const createRequirement = async (e) => {
    e?.preventDefault?.();
    if (!supplierId) { alert('Not logged in as supplier'); return; }
    if (!newReq.order_ref.trim() || !newReq.material_name.trim()) { alert('order_ref and material_name are required'); return; }
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/requirements.php?supplier_id=${supplierId}`, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          order_ref: newReq.order_ref,
          material_name: newReq.material_name,
          required_qty: Number(newReq.required_qty) || 0,
          unit: newReq.unit || 'pcs',
          due_date: newReq.due_date || undefined,
        })
      });
      const data = await res.json();
      if (!(res.ok && data.status === 'success')) {
        alert(data.message || 'Create failed');
      } else {
        setNewReq({ order_ref: '', material_name: '', required_qty: '', unit: 'pcs', due_date: '' });
        await load();
      }
    } finally { setUpdating(false); }
  };

  return (
    <div className="container" style={{ padding: 24 }}>
      <div style={{ display:'flex', alignItems:'center', gap:8, marginBottom: 16 }}>
        <LuClipboardList />
        <h2 style={{ margin: 0 }}>Supplier Requirements</h2>
      </div>

      {/* Create Requirement */}
      <form onSubmit={createRequirement} className="grid" style={{ gap: 8, gridTemplateColumns: 'repeat(6, 1fr)', marginBottom: 16 }}>
        <input className="select" placeholder="Order Ref" value={newReq.order_ref} onChange={e=>setNewReq(v=>({...v, order_ref:e.target.value}))} />
        <input className="select" placeholder="Material Name" value={newReq.material_name} onChange={e=>setNewReq(v=>({...v, material_name:e.target.value}))} />
        <input className="select" placeholder="Qty" type="number" value={newReq.required_qty} onChange={e=>setNewReq(v=>({...v, required_qty:e.target.value}))} />
        <select className="select" value={newReq.unit} onChange={e=>setNewReq(v=>({...v, unit:e.target.value}))}>
          <option value="pcs">pcs</option>
          <option value="sets">sets</option>
          <option value="kg">kg</option>
        </select>
        <input className="select" type="date" value={newReq.due_date} onChange={e=>setNewReq(v=>({...v, due_date:e.target.value}))} />
        <button className="btn btn-emph" type="submit" disabled={updating}>Create</button>
      </form>

      {loading ? (
        <div>Loading…</div>
      ) : (
        <div className="grid" style={{ gap: 10 }}>
          {requirements.map(req => (
            <div key={req.id} className="order-item">
              <div>
                <div className="order-title">{req.order_ref} — {req.material_name}</div>
                <div className="order-date">Need {req.required_qty} {req.unit} by {req.due_date || 'TBD'}</div>
                {/* Messages thread */}
                <div style={{ marginTop: 8 }}>
                  <div className="muted" style={{ marginBottom: 6 }}>Messages</div>
                  <div style={{ background:'#f7f7f7', padding: 8, borderRadius: 6 }}>
                    {(req.messages || []).map((m, idx) => (
                      <div key={idx} style={{ marginBottom: 6 }}>
                        <div style={{ fontSize: 12, color: '#555' }}>{m.sender} • {new Date(m.created_at).toLocaleString()}</div>
                        <div>{m.message}</div>
                      </div>
                    ))}
                    <ReplyBox onSend={(text) => sendReply(req.id, text)} disabled={updating} />
                  </div>
                </div>
              </div>
              <div style={{ display:'flex', flexDirection:'column', alignItems:'flex-end', gap:8 }}>
                <span className={`status ${req.status==='packed' ? 'success' : 'info'}`}>{req.status}</span>
                {req.status !== 'packed' && (
                  <button className="btn btn-soft tiny" onClick={() => markPacked(req.id)} disabled={updating}>Mark Packed</button>
                )}
              </div>
            </div>
          ))}
          {requirements.length === 0 && <div className="ph-card" />}
        </div>
      )}
    </div>
  );
}