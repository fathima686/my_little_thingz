import React, { useEffect, useState } from 'react';
import { LuX, LuClock, LuWrench, LuCheck, LuBan, LuEye } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

// Map status to icon and color
const statusMeta = {
  pending: { icon: <LuClock />, color: '#f39c12', label: 'Pending' },
  in_progress: { icon: <LuWrench />, color: '#3498db', label: 'In Progress' },
  completed: { icon: <LuCheck />, color: '#27ae60', label: 'Completed' },
  cancelled: { icon: <LuBan />, color: '#e74c3c', label: 'Cancelled' }
};

// Small utility to compute a 0-100% progress for statuses
export function getRequestProgress(status) {
  const s = (status || '').toLowerCase();
  if (s === 'pending') return 10;
  if (s === 'in_progress') return 60;
  if (s === 'completed') return 100;
  if (s === 'cancelled') return 0;
  return 0;
}

export default function CustomRequestStatus({ onClose }) {
  const { auth } = useAuth();
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');
  const [selected, setSelected] = useState(null);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await fetch(`${API_BASE}/customer/custom-requests.php`, {
          headers: { 'X-User-ID': auth?.user_id }
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          setRequests(data.requests || []);
        }
      } catch (e) {
        console.error('Failed to load custom requests', e);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [auth?.user_id]);

  const filtered = requests.filter(r => filter === 'all' ? true : (r.status || '').toLowerCase() === filter);

  return (
    <div className="modal-overlay">
      <div className="modal-content large">
        <div className="modal-header">
          <h2>My Custom Requests</h2>
          <button className="btn-close" onClick={onClose}><LuX /></button>
        </div>

        <div className="filter-tabs">
          {['all','pending','in_progress','completed','cancelled'].map(f => (
            <button key={f} className={`filter-tab ${filter === f ? 'active' : ''}`} onClick={() => setFilter(f)}>
              {f === 'all' ? `All (${requests.length})` : `${f.replace('_',' ').replace(/^./,c=>c.toUpperCase())} (${requests.filter(r => (r.status||'').toLowerCase()===f).length})`}
            </button>
          ))}
        </div>

        {loading ? (
          <div className="loading-spinner">Loading requests...</div>
        ) : (
          <div className="requests-list">
            {filtered.map(req => {
              const meta = statusMeta[(req.status || 'pending').toLowerCase()] || statusMeta.pending;
              return (
                <div key={req.id} className="request-card">
                  <div className="req-head">
                    <div className="req-title">
                      <span className="status-icon" style={{color: meta.color}}>{meta.icon}</span>
                      <h3>{req.title}</h3>
                    </div>
                    <span className="req-status" style={{color: meta.color}}>{meta.label}</span>
                  </div>
                  <p className="req-desc">{req.description}</p>
                  <div className="req-meta">
                    {req.occasion && <span><strong>Occasion:</strong> {req.occasion}</span>}
                    {req.deadline && <span><strong>Deadline:</strong> {new Date(req.deadline).toLocaleDateString()}</span>}
                    {(req.budget_min || req.budget_max) && (
                      <span><strong>Budget:</strong> {req.budget_min || req.budget_max}</span>
                    )}
                    {req.category_name && <span><strong>Category:</strong> {req.category_name}</span>}
                  </div>
                  <div className="req-progress">
                    <div className="bar">
                      <div className="fill" style={{width: `${getRequestProgress(req.status)}%`, background: meta.color}} />
                    </div>
                    <span className="pct">{getRequestProgress(req.status)}%</span>
                  </div>
                  <div className="req-actions">
                    <button className="btn btn-outline" onClick={() => setSelected(req)}><LuEye /> View</button>
                  </div>

                  {/* Inline preview of images if any */}
                  {Array.isArray(req.images) && req.images.length > 0 && (
                    <div style={{ marginTop: 8, display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                      {req.images.slice(0, 4).map((url, i) => {
                        return <img key={i} src={url} alt={`attachment ${i+1}`} style={{ width: 56, height: 56, objectFit: 'cover', borderRadius: 6, border: '1px solid #eee' }} />
                      })}
                    </div>
                  )}
                </div>
              );
            })}

            {filtered.length === 0 && (
              <div className="empty-state">
                <p>No requests in this filter.</p>
              </div>
            )}
          </div>
        )}

        {selected && (
          <div className="modal-overlay">
            <div className="modal-content">
              <div className="modal-header">
                <h3>{selected.title}</h3>
                <button className="btn-close" onClick={() => setSelected(null)}><LuX /></button>
              </div>
              <div className="req-detail">
                <p><strong>Status:</strong> {selected.status}</p>
                <p><strong>Description:</strong> {selected.description}</p>
                {selected.occasion && <p><strong>Occasion:</strong> {selected.occasion}</p>}
                {selected.deadline && <p><strong>Deadline:</strong> {new Date(selected.deadline).toLocaleDateString()}</p>}
                {(selected.budget_min || selected.budget_max) && (
                  <p><strong>Budget:</strong> {selected.budget_min || selected.budget_max}</p>
                )}
                <p><small>Requested on {new Date(selected.created_at).toLocaleString()}</small></p>

                {Array.isArray(selected.images) && selected.images.length > 0 && (
                  <div style={{ marginTop: 8 }}>
                    <p><strong>Images:</strong></p>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                      {selected.images.map((url, i) => {
                        return (
                          <a key={i} href={url} target="_blank" rel="noreferrer">
                            <img src={url} alt={`attachment ${i+1}`} style={{ width: 90, height: 90, objectFit: 'cover', borderRadius: 6, border: '1px solid #eee' }} />
                          </a>
                        );
                      })}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        <style>{`
          .filter-tabs { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap }
          .filter-tab { padding:8px 12px; border-radius:999px; border:1px solid #ddd; background:white; cursor:pointer }
          .filter-tab.active { background:#eef6ff; border-color:#bcdcff }
          .requests-list { display:grid; gap:12px }
          .request-card { border:1px solid #eee; border-radius:10px; padding:12px; background:#fff }
          .req-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px }
          .req-title { display:flex; align-items:center; gap:8px }
          .status-icon { display:inline-flex; align-items:center; justify-content:center; font-size:18px }
          .req-desc { color:#555; margin:8px 0 }
          .req-meta { display:flex; gap:12px; flex-wrap:wrap; font-size:13px; color:#444 }
          .req-actions { display:flex; gap:8px; margin-top:8px }
          .req-progress { display:flex; align-items:center; gap:8px; margin-top:8px }
          .req-progress .bar { position:relative; flex:1; height:8px; background:#f1f5f9; border-radius:999px; overflow:hidden }
          .req-progress .fill { position:absolute; inset:0 0 0 0; width:0; height:100%; border-radius:999px; transition: width .25s ease }
          .req-progress .pct { font-size:12px; color:#64748b }
        `}</style>
      </div>
    </div>
  );
}