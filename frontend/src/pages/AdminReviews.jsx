import React, { useEffect, useState } from 'react';
import { LuCheck, LuX, LuReply, LuRefreshCcw } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const AdminReviews = () => {
  const [items, setItems] = useState([]);
  const [status, setStatus] = useState('pending');
  const [loading, setLoading] = useState(false);
  const [replyDraft, setReplyDraft] = useState({});

  const load = async () => {
    try {
      setLoading(true);
      const q = status ? `?status=${encodeURIComponent(status)}` : '';
      const res = await fetch(`${API_BASE}/admin/reviews.php${q}`);
      const data = await res.json();
      if (data.status === 'success') setItems(data.items);
    } catch (e) {
      console.error('Failed to load reviews', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, [status]);

  const updateReview = async (id, payload) => {
    const res = await fetch(`${API_BASE}/admin/reviews.php`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, ...payload })
    });
    const data = await res.json();
    if (data.status === 'success') load();
  };

  return (
    <div style={{ padding: 16 }}>
      <h2>Customer Reviews</h2>
      <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 12 }}>
        <label>Status</label>
        <select value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <button onClick={load} title="Refresh"><LuRefreshCcw /></button>
      </div>

      {loading ? (
        <div>Loading…</div>
      ) : items.length === 0 ? (
        <div>No reviews</div>
      ) : (
        <div style={{ display: 'grid', gap: 12 }}>
          {items.map((r) => (
            <div key={r.id} style={{ border: '1px solid #e5e7eb', borderRadius: 8, padding: 12 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div style={{ display: 'flex', flexDirection: 'column' }}>
                  <strong>#{r.id} • {r.artwork_title || `Artwork ${r.artwork_id}`}</strong>
                  <span>Rating: {r.rating}/5</span>
                  {r.comment && <span>“{r.comment}”</span>}
                </div>
                <div style={{ display: 'flex', gap: 8 }}>
                  <button onClick={() => updateReview(r.id, { status: 'approved' })} title="Approve"><LuCheck /></button>
                  <button onClick={() => updateReview(r.id, { status: 'rejected' })} title="Reject"><LuX /></button>
                </div>
              </div>
              <div style={{ marginTop: 8, display: 'flex', gap: 8 }}>
                <input
                  type="text"
                  placeholder="Reply to customer"
                  value={replyDraft[r.id] ?? (r.admin_reply || '')}
                  onChange={(e) => setReplyDraft({ ...replyDraft, [r.id]: e.target.value })}
                  style={{ flex: 1, padding: 6 }}
                />
                <button onClick={() => updateReview(r.id, { admin_reply: replyDraft[r.id] ?? '' })} title="Reply"><LuReply /></button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default AdminReviews;














