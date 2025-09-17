import React, { useEffect, useState } from 'react';
import {
  LuX, LuPackage, LuCalendar, LuDollarSign, LuCheck, LuTruck, LuClock, LuMapPin
} from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function ProfileModal({ onClose, onImageUpdated }) {
  const { auth } = useAuth();
  // Profile state
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [profile, setProfile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [localPreview, setLocalPreview] = useState(null);

  // Orders state
  const [orders, setOrders] = useState([]);
  const [ordersLoading, setOrdersLoading] = useState(true);

  useEffect(() => {
    const loadProfile = async () => {
      setLoading(true);
      setError('');
      try {
        const userIdQs = auth?.user_id ? `?user_id=${encodeURIComponent(auth.user_id)}` : '';
        const res = await fetch(`${API_BASE}/customer/profile.php${userIdQs}`, {
          headers: { 'X-User-ID': auth?.user_id }
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          setProfile(data.profile);
        } else {
          setError(data.message || 'Failed to load profile');
        }
      } catch (e) {
        setError('Network error loading profile');
      } finally {
        setLoading(false);
      }
    };

    const loadOrders = async () => {
      setOrdersLoading(true);
      try {
        const res = await fetch(`${API_BASE}/customer/orders.php`, {
          headers: {
            'Authorization': `Bearer ${auth?.token}`,
            'X-User-ID': auth?.user_id
          }
        });
        const data = await res.json();
        if (data.status === 'success') {
          setOrders(Array.isArray(data.orders) ? data.orders : []);
        } else {
          setOrders([]);
        }
      } catch (e) {
        setOrders([]);
      } finally {
        setOrdersLoading(false);
      }
    };

    loadProfile();
    loadOrders();
  }, [auth]);

  const formatDate = (dateString) => {
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  };

  const getStatusIcon = (status) => {
    const s = String(status || '').toLowerCase();
    switch (s) {
      case 'pending':
        return <LuClock className="status-icon pending" title="Pending"/>;
      case 'processing':
        return <LuPackage className="status-icon processing" title="Processing"/>;
      case 'shipped':
        return <LuTruck className="status-icon shipped" title="Shipped"/>;
      case 'delivered':
        return <LuCheck className="status-icon delivered" title="Delivered"/>;
      default:
        return <LuClock className="status-icon" title={status}/>;
    }
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content extra-large profile-modal">
        <div className="modal-header">
          <h2>Your Profile</h2>
          <button className="btn-close" onClick={onClose}><LuX /></button>
        </div>

        <div className="modal-body">
          {loading ? (
            <div>Loading…</div>
          ) : error ? (
            <div className="banner error">{error}</div>
          ) : profile ? (
            <div className="profile-grid">
              {/* Avatar + Quick info */}
              <section className="section-card span-2">
                <div className="avatar-row">
                  <img
                    src={localPreview || profile.profile_image_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent((profile.first_name||'') + ' ' + (profile.last_name||''))}
                    alt="Profile"
                    className="avatar-lg"
                  />
                  <div className="avatar-actions">
                    <label className="muted">Profile photo</label>
                    <div className="inline">
                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => {
                          const file = e.target.files?.[0];
                          if (!file) return;
                          const url = URL.createObjectURL(file);
                          setLocalPreview((prev) => { if (prev) URL.revokeObjectURL(prev); return url; });
                          // Upload immediately
                          const doUpload = async () => {
                            setUploading(true);
                            try {
                              const fd = new FormData();
                              fd.append('image', file);
                              const res = await fetch(`${API_BASE}/customer/profile-image.php`, {
                                method: 'POST',
                                headers: { 'X-User-ID': auth?.user_id },
                                body: fd
                              });
                              const data = await res.json();
                              if (res.ok && data.status === 'success') {
                                setProfile(p => ({ ...p, profile_image_url: data.url }));
                                if (typeof onImageUpdated === 'function') onImageUpdated(data.url);
                              } else {
                                alert(data.message || 'Upload failed');
                              }
                            } catch (err) {
                              alert('Network error uploading image');
                            } finally {
                              setUploading(false);
                            }
                          };
                          doUpload();
                        }}
                      />
                      {uploading && <span className="muted">Uploading…</span>}
                    </div>
                  </div>
                </div>

                <div className="info-grid">
                  <div className="info-item">
                    <span className="label">User ID</span>
                    <span className="value">{profile.id}</span>
                  </div>
                  <div className="info-item">
                    <span className="label">Name</span>
                    <span className="value">{profile.first_name} {profile.last_name}</span>
                  </div>
                  <div className="info-item">
                    <span className="label">Email</span>
                    <span className="value">{profile.email}</span>
                  </div>
                  <div className="info-item">
                    <span className="label">Roles</span>
                    <span className="value">{Array.isArray(profile.roles) ? profile.roles.join(', ') : '-'}</span>
                  </div>
                  {profile.supplier_status ? (
                    <div className="info-item">
                      <span className="label">Supplier Status</span>
                      <span className="value" style={{ textTransform: 'capitalize' }}>{profile.supplier_status}</span>
                    </div>
                  ) : null}
                  <div className="info-item">
                    <span className="label">Joined</span>
                    <span className="value">{new Date(profile.created_at).toLocaleString()}</span>
                  </div>
                  <div className="info-item">
                    <span className="label">Updated</span>
                    <span className="value">{new Date(profile.updated_at).toLocaleString()}</span>
                  </div>
                </div>
              </section>

              {/* Linked accounts */}
              <section className="section-card">
                <h3 className="section-title">Linked Accounts</h3>
                {profile.providers && profile.providers.length > 0 ? (
                  <ul className="linked-list">
                    {profile.providers.map((p, idx) => (
                      <li key={idx}>{p.provider} • {p.provider_user_id} • linked {new Date(p.linked_at).toLocaleDateString()}</li>
                    ))}
                  </ul>
                ) : (
                  <div className="muted">None</div>
                )}
              </section>

              {/* Recent Orders (hidden for supplier-only accounts) */}
              {(() => {
                const rolesLower = Array.isArray(profile?.roles) ? profile.roles.map(r => String(r).toLowerCase()) : [];
                const supplierOnly = rolesLower.includes('supplier') && !rolesLower.includes('customer');
                if (supplierOnly) return null;
                return (
                  <section className="section-card">
                    <div className="section-head">
                      <h3 className="section-title"><LuPackage /> Recent Orders</h3>
                      <span className="muted">{orders.length} total</span>
                    </div>
                    {ordersLoading ? (
                      <div className="muted">Loading orders…</div>
                    ) : orders.length === 0 ? (
                      <div className="muted">No orders yet.</div>
                    ) : (
                      <div className="orders-list-compact">
                        {orders.slice(0, 5).map((order) => (
                          <div key={order.id} className="order-row">
                            <div className="order-status-icon">{getStatusIcon(order.status)}</div>
                            <div className="order-main">
                              <div className="order-top">
                                <strong>Order #{order.order_number}</strong>
                                <span className="muted"><LuCalendar /> {formatDate(order.created_at)}</span>
                                <span className="pill">{String(order.status).toUpperCase()}</span>
                              </div>
                              <div className="thumbs">
                                {(order.items || []).slice(0, 4).map((it, idx) => (
                                  <img key={idx} src={it.image_url || '/api/placeholder/28/28'} alt={it.name} className="thumb" />
                                ))}
                                {(order.items || []).length > 4 && (
                                  <span className="muted">+{(order.items || []).length - 4} more</span>
                                )}
                              </div>
                            </div>
                            <div className="order-side">
                              <div className="total"><LuDollarSign /> ₹{order.total_amount}</div>
                              {order.shipping_address && (
                                <div className="addr" title={order.shipping_address}><LuMapPin /> {order.shipping_address}</div>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </section>
                );
              })()}
            </div>
          ) : (
            <div className="muted">No profile data</div>
          )}
        </div>
      </div>

      <style>{`
        .profile-modal { background: #fff; }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .section-card { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 16px; }
        .span-2 { grid-column: 1 / -1; }
        .section-title { margin: 0 0 8px 0; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }

        .avatar-row { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; }
        .avatar-lg { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; border: 2px solid #f2f2f2; }
        .avatar-actions .inline { display: flex; gap: 8px; align-items: center; }

        .info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 8px; }
        .info-item { display: flex; flex-direction: column; gap: 4px; background: #fafafa; border: 1px solid #f0f0f0; border-radius: 8px; padding: 10px 12px; }
        .label { color: #6b7280; font-size: 12px; }
        .value { font-weight: 600; }

        .linked-list { margin: 0; padding-left: 18px; }

        .orders-list-compact { display: grid; gap: 10px; }
        .order-row { display: grid; grid-template-columns: 32px 1fr auto; gap: 12px; padding: 10px 12px; border: 1px solid #eee; border-radius: 10px; background: #fff; }
        .order-status-icon { display: flex; align-items: center; justify-content: center; }
        .order-top { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .thumbs { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
        .thumb { width: 28px; height: 28px; border-radius: 4px; object-fit: cover; border: 1px solid #eee; }
        .order-side { text-align: right; min-width: 180px; }
        .total { font-weight: 600; }
        .addr { color: #6b7280; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pill { background: #f3f4f6; border: 1px solid #e5e7eb; color: #374151; font-size: 12px; padding: 2px 8px; border-radius: 999px; }

        .status-icon { opacity: 0.9; }
        .status-icon.pending { color: #f39c12; }
        .status-icon.processing { color: #3498db; }
        .status-icon.shipped { color: #9b59b6; }
        .status-icon.delivered { color: #27ae60; }

        @media (max-width: 900px) {
          .profile-grid { grid-template-columns: 1fr; }
          .order-row { grid-template-columns: 24px 1fr; }
          .order-side { grid-column: 2; text-align: left; margin-top: 6px; }
        }
      `}</style>
    </div>
  );
}