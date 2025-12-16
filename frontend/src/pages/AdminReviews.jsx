import React, { useEffect, useState } from 'react';
import { LuCheck, LuX, LuReply, LuRefreshCcw } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const AdminReviews = () => {
  const [items, setItems] = useState([]);
  const [status, setStatus] = useState('pending');
  const [loading, setLoading] = useState(false);
  const [replyDraft, setReplyDraft] = useState({});
  const [sentiments, setSentiments] = useState({});

  const load = async () => {
    try {
      setLoading(true);
      const q = status ? `?status=${encodeURIComponent(status)}` : '';
      const res = await fetch(`${API_BASE}/admin/reviews.php${q}`);
      const data = await res.json();
      if (data.status === 'success') {
        setItems(data.items);
        // Analyze sentiments for reviews with comments
        analyzeSentiments(data.items.filter(r => r.comment));
      }
    } catch (e) {
      console.error('Failed to load reviews', e);
    } finally {
      setLoading(false);
    }
  };

  const analyzeSentiments = async (reviews) => {
    try {
      const sentimentPromises = reviews.map(async (r) => {
        if (!r.comment) return { id: r.id, error: true };
        
        try {
          const res = await fetch('http://localhost:5001/api/ml/sentiment/analyze', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ review_text: r.comment })
          });
          const data = await res.json();
          return { id: r.id, ...data };
        } catch (err) {
          console.error(`Failed to analyze review ${r.id}:`, err);
          return { id: r.id, error: true };
        }
      });
      
      const results = await Promise.all(sentimentPromises);
      const sentimentMap = {};
      results.forEach(result => {
        sentimentMap[result.id] = result;
      });
      setSentiments(sentimentMap);
    } catch (e) {
      console.error('Failed to analyze sentiments:', e);
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

  const getSentimentBadge = (sentiment) => {
    if (!sentiment || sentiment.error) return null;
    
    const sentimentStyle = {
      padding: '4px 8px',
      borderRadius: '4px',
      fontSize: '12px',
      fontWeight: 'bold',
      display: 'inline-block'
    };
    
    if (sentiment.sentiment === 'positive') {
      return (
        <span style={{ ...sentimentStyle, background: '#dcfce7', color: '#166534' }}>
          ✓ POSITIVE ({sentiment.confidence_percent}%)
        </span>
      );
    } else if (sentiment.sentiment === 'negative') {
      return (
        <span style={{ ...sentimentStyle, background: '#fee2e2', color: '#991b1b' }}>
          ✗ NEGATIVE ({sentiment.confidence_percent}%)
        </span>
      );
    } else {
      return (
        <span style={{ ...sentimentStyle, background: '#fef3c7', color: '#92400e' }}>
          ○ NEUTRAL ({sentiment.confidence_percent}%)
        </span>
      );
    }
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
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: 8 }}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 4, flex: 1 }}>
                  <div>
                    <strong>#{r.id} • {r.artwork_title || `Artwork ${r.artwork_id}`}</strong>
                  </div>
                  <div style={{ color: '#6b7280', fontSize: '14px' }}>
                    Rating: {r.rating}/5 • {new Date(r.created_at).toLocaleDateString()}
                  </div>
                  {r.comment && (
                    <div style={{ marginTop: 8, padding: 8, background: '#f9fafb', borderRadius: 4 }}>
                      <div style={{ marginBottom: 4 }}>
                        <strong>Customer Review:</strong>
                      </div>
                      <div style={{ marginBottom: 6 }}>"{r.comment}"</div>
                      {getSentimentBadge(sentiments[r.id])}
                    </div>
                  )}
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








