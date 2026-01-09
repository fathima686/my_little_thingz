import React, { useEffect, useState } from 'react';
import { LuX, LuClock, LuWrench, LuCheck, LuBan, LuEye, LuImage, LuDownload } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import CustomRequestDesignView from './CustomRequestDesignView';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

// Map status to icon and color
const statusMeta = {
  pending: { icon: <LuClock />, color: '#f39c12', label: 'Pending' },
  in_progress: { icon: <LuWrench />, color: '#3498db', label: 'In Progress' },
  designing: { icon: <LuWrench />, color: '#9b59b6', label: 'Designing' },
  design_completed: { icon: <LuCheck />, color: '#27ae60', label: 'Design Completed' },
  completed: { icon: <LuCheck />, color: '#27ae60', label: 'Completed' },
  cancelled: { icon: <LuBan />, color: '#e74c3c', label: 'Cancelled' }
};

// Small utility to compute a 0-100% progress for statuses
export function getRequestProgress(status, designStatus = null) {
  const s = (status || '').toLowerCase();
  const ds = designStatus ? (designStatus || '').toLowerCase() : null;
  
  // If design is completed, show higher progress
  if (ds === 'design_completed' || ds === 'design_completed') return 90;
  if (ds === 'designing') return 70;
  
  if (s === 'pending') return 10;
  if (s === 'in_progress' || s === 'designing') return 60;
  if (s === 'completed' || s === 'design_completed') return 100;
  if (s === 'cancelled') return 0;
  return 0;
}

export default function CustomRequestStatus({ onClose }) {
  const { auth } = useAuth();
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');
  const [selected, setSelected] = useState(null);
  const [viewingDesign, setViewingDesign] = useState(null); // request ID for design view

  const loadRequests = async () => {
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

  useEffect(() => {
    loadRequests();
    
    // Auto-refresh every 30 seconds to see design updates
    const interval = setInterval(() => {
      loadRequests();
    }, 30000);
    
    return () => clearInterval(interval);
  }, [auth?.user_id]);

  const filtered = requests.filter(r => {
    if (filter === 'all') return true;
    // Check both status and design_status for filtering
    const statusMatch = (r.status || '').toLowerCase() === filter;
    const designStatusMatch = r.design_status && (r.design_status.toLowerCase() === filter || 
      (filter === 'in_progress' && r.design_status.toLowerCase() === 'designing'));
    
    // For 'completed' filter, include both completed requests and design_completed designs
    if (filter === 'completed') {
      return statusMatch || (r.design_status === 'design_completed');
    }
    
    return statusMatch || designStatusMatch;
  });

  const handlePayment = (request) => {
    // Navigate to cart/checkout with customization data
    // In a real app, this would add the item to cart and redirect to checkout
    if (request.artwork_id && auth?.user_id) {
      // Add to cart then redirect
      fetch(`${API_BASE}/customer/cart.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': auth.user_id
        },
        body: JSON.stringify({
          artwork_id: request.artwork_id,
          quantity: 1,
          customization_request_id: request.id
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          // Redirect to checkout
          window.location.href = '/checkout';
        } else {
          alert('Failed to add to cart: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error('Error proceeding to payment:', err);
        alert('Error proceeding to payment');
      });
    } else {
      alert('Please log in to proceed to payment');
    }
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content large">
        <div className="modal-header">
          <h2>My Custom Requests</h2>
          <button className="btn-close" onClick={onClose}><LuX /></button>
        </div>

        <div className="filter-tabs">
          {['all','pending','in_progress','completed','cancelled'].map(f => {
            const count = f === 'all' 
              ? requests.length 
              : requests.filter(r => {
                  const statusMatch = (r.status || '').toLowerCase() === f;
                  if (f === 'completed') {
                    return statusMatch || (r.design_status === 'design_completed');
                  }
                  if (f === 'in_progress') {
                    return statusMatch || (r.design_status && ['designing', 'design_completed'].includes(r.design_status));
                  }
                  return statusMatch;
                }).length;
            
            return (
              <button 
                key={f} 
                className={`filter-tab ${filter === f ? 'active' : ''}`} 
                onClick={() => setFilter(f)}
              >
                {f === 'all' 
                  ? `All (${count})` 
                  : `${f.replace('_',' ').replace(/^./,c=>c.toUpperCase())} (${count})`}
              </button>
            );
          })}
        </div>

        {loading ? (
          <div className="loading-spinner">Loading requests...</div>
        ) : (
          <div className="requests-list">
            {filtered.map(req => {
              // Determine display status - prioritize design_status if available
              const displayStatus = req.design_status || req.status || 'pending';
              const meta = statusMeta[displayStatus.toLowerCase()] || statusMeta.pending;
              const progress = getRequestProgress(req.status, req.design_status);
              
              return (
                <div key={req.id} className="request-card">
                  <div className="req-head">
                    <div className="req-title">
                      <span className="status-icon" style={{color: meta.color}}>{meta.icon}</span>
                      <h3>{req.title}</h3>
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4 }}>
                      <span className="req-status" style={{color: meta.color}}>{meta.label}</span>
                      {req.design_status && req.design_status !== req.status && (
                        <span style={{fontSize: 11, color: '#666'}}>
                          Design: {req.design_status === 'design_completed' ? '✅ Completed' : req.design_status === 'designing' ? '🎨 In Progress' : req.design_status}
                        </span>
                      )}
                    </div>
                  </div>
                  <p className="req-desc">{req.description}</p>
                  <div className="req-meta">
                    {req.occasion && <span><strong>Occasion:</strong> {req.occasion}</span>}
                    {req.deadline && <span><strong>Deadline:</strong> {new Date(req.deadline).toLocaleDateString()}</span>}
                    {(req.budget_min || req.budget_max) && (
                      <span><strong>Budget:</strong> {req.budget_min || req.budget_max}</span>
                    )}
                    {req.gift_tier && (
                      <span><strong>Tier:</strong> <span style={{textTransform: 'capitalize'}}>{req.gift_tier === 'premium' ? '✨ Premium' : '🎁 Budget-Friendly'}</span></span>
                    )}
                    {req.category_name && <span><strong>Category:</strong> {req.category_name}</span>}
                  </div>
                  
                  {/* Show design preview if available */}
                  {req.design_image_url && (
                    <div style={{ marginTop: 12, marginBottom: 8 }}>
                      <div style={{ 
                        border: '2px solid #ddd', 
                        borderRadius: 8, 
                        overflow: 'hidden',
                        position: 'relative'
                      }}>
                        <img 
                          src={req.design_image_url} 
                          alt="Design Preview" 
                          style={{ 
                            width: '100%', 
                            height: 200, 
                            objectFit: 'contain', 
                            background: '#f5f5f5' 
                          }} 
                        />
                        <div style={{
                          position: 'absolute',
                          top: 8,
                          right: 8,
                          background: 'rgba(33, 150, 243, 0.9)',
                          color: 'white',
                          padding: '4px 8px',
                          borderRadius: 4,
                          fontSize: 11,
                          fontWeight: 'bold'
                        }}>
                          Design Ready
                        </div>
                      </div>
                    </div>
                  )}
                  
                  <div className="req-progress">
                    <div className="bar">
                      <div className="fill" style={{width: `${progress}%`, background: meta.color}} />
                    </div>
                    <span className="pct">{progress}%</span>
                  </div>
                  <div className="req-actions">
                    <button className="btn btn-outline" onClick={() => setSelected(req)}>
                      <LuEye /> View Details
                    </button>
                    {(req.design_status === 'design_completed' || req.design_image_url) && (
                      <button 
                        className="btn btn-outline" 
                        style={{ background: '#e3f2fd', borderColor: '#2196f3', color: '#1976d2' }}
                        onClick={() => setViewingDesign(req.id)}
                      >
                        <LuImage /> View Design
                      </button>
                    )}
                    {req.design_pdf_url && (
                      <a 
                        href={req.design_pdf_url} 
                        target="_blank" 
                        rel="noreferrer"
                        className="btn btn-outline"
                        style={{ textDecoration: 'none' }}
                      >
                        <LuDownload /> Download PDF
                      </a>
                    )}
                    {req.status === 'completed' && (
                      <button 
                        className="btn" 
                        style={{
                          background: '#10b981',
                          color: '#fff',
                          border: 'none'
                        }}
                        onClick={() => handlePayment(req)}
                      >
                        💳 Proceed to Payment
                      </button>
                    )}
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
                <p><strong>Status:</strong> <span style={{textTransform: 'capitalize', color: statusMeta[(selected.status || 'pending').toLowerCase()]?.color}}>
                  {statusMeta[(selected.status || 'pending').toLowerCase()]?.label || selected.status}
                </span></p>
                {selected.design_status && (
                  <p><strong>Design Status:</strong> <span style={{textTransform: 'capitalize', color: statusMeta[selected.design_status.toLowerCase()]?.color || '#666'}}>
                    {statusMeta[selected.design_status.toLowerCase()]?.label || selected.design_status}
                  </span></p>
                )}
                <p><strong>Description:</strong> {selected.description}</p>
                {selected.occasion && <p><strong>Occasion:</strong> {selected.occasion}</p>}
                {selected.deadline && <p><strong>Deadline:</strong> {new Date(selected.deadline).toLocaleDateString()}</p>}
                {(selected.budget_min || selected.budget_max) && (
                  <p><strong>Budget:</strong> ₹{selected.budget_min || selected.budget_max}</p>
                )}
                {selected.gift_tier && (
                  <p><strong>Gift Tier:</strong> <span style={{textTransform: 'capitalize'}}>{selected.gift_tier === 'premium' ? '✨ Premium' : '🎁 Budget-Friendly'}</span></p>
                )}
                <p><small>Requested on {new Date(selected.created_at).toLocaleString()}</small></p>
                {selected.design_updated_at && (
                  <p><small>Design updated on {new Date(selected.design_updated_at).toLocaleString()}</small></p>
                )}

                {/* Show completed design if available */}
                {selected.design_image_url && (
                  <div style={{ marginTop: 16, padding: 12, background: '#f9f9f9', borderRadius: 8 }}>
                    <p><strong style={{fontSize: 16}}>✨ Completed Design:</strong></p>
                    <img 
                      src={selected.design_image_url} 
                      alt="Completed Design" 
                      style={{ 
                        width: '100%', 
                        maxHeight: 400, 
                        objectFit: 'contain', 
                        borderRadius: 8,
                        border: '2px solid #ddd',
                        marginTop: 8,
                        background: 'white'
                      }} 
                    />
                    <div style={{ marginTop: 8, display: 'flex', gap: 8 }}>
                      <a 
                        href={selected.design_image_url} 
                        target="_blank" 
                        rel="noreferrer"
                        className="btn btn-outline"
                        style={{ textDecoration: 'none' }}
                      >
                        <LuImage /> View Full Size
                      </a>
                      {selected.design_pdf_url && (
                        <a 
                          href={selected.design_pdf_url} 
                          target="_blank" 
                          rel="noreferrer"
                          className="btn btn-outline"
                          style={{ textDecoration: 'none' }}
                        >
                          <LuDownload /> Download PDF
                        </a>
                      )}
                    </div>
                  </div>
                )}

                {Array.isArray(selected.images) && selected.images.length > 0 && (
                  <div style={{ marginTop: 16 }}>
                    <p><strong>Your Reference Images:</strong></p>
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

        {/* Design View Modal */}
        {viewingDesign && (
          <CustomRequestDesignView
            requestId={viewingDesign}
            isOpen={true}
            onClose={() => setViewingDesign(null)}
          />
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
          .req-actions .btn { padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
          .req-actions .btn:hover { opacity: 0.9; transform: translateY(-1px); }
          .req-progress { display:flex; align-items:center; gap:8px; margin-top:8px }
          .req-progress .bar { position:relative; flex:1; height:8px; background:#f1f5f9; border-radius:999px; overflow:hidden }
          .req-progress .fill { position:absolute; inset:0 0 0 0; width:0; height:100%; border-radius:999px; transition: width .25s ease }
          .req-progress .pct { font-size:12px; color:#64748b }
        `}</style>
      </div>
    </div>
  );
}