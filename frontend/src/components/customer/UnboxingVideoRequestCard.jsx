import React, { useState, useEffect } from 'react';
import { LuUpload, LuVideo, LuClock, LuCheck, LuX, LuEye, LuChevronDown, LuChevronUp } from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

/**
 * COMPACT UNBOXING VIDEO REQUEST COMPONENT
 * For use in order cards - more compact than the full modal version
 */
const UnboxingVideoRequestCard = ({ auth, order }) => {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [expanded, setExpanded] = useState(false);
  const [formData, setFormData] = useState({
    issueType: '',
    requestType: '',
    description: '',
    video: null
  });
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    if (auth?.user_id) {
      fetchRequests();
    }
  }, [auth]);

  const fetchRequests = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/customer/unboxing-requests.php`, {
        headers: {
          'X-User-ID': auth.user_id
        }
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setRequests(data.requests);
      }
    } catch (err) {
      console.error('Failed to fetch requests:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleVideoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type
      const allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
      if (!allowedTypes.includes(file.type)) {
        setError('Only MP4, MOV, and AVI video formats are allowed');
        return;
      }
      
      // Validate file size (100MB)
      if (file.size > 100 * 1024 * 1024) {
        setError('Video file must be less than 100MB');
        return;
      }
      
      setFormData({ ...formData, video: file });
      setError('');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    
    if (!formData.video || !formData.issueType || !formData.requestType) {
      setError('Please fill all required fields and upload a video');
      return;
    }
    
    try {
      setLoading(true);
      setUploadProgress(0);
      
      const formDataToSend = new FormData();
      formDataToSend.append('order_id', order.id);
      formDataToSend.append('issue_type', formData.issueType);
      formDataToSend.append('request_type', formData.requestType);
      formDataToSend.append('description', formData.description);
      formDataToSend.append('video', formData.video);
      
      const xhr = new XMLHttpRequest();
      
      // Track upload progress
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const progress = Math.round((e.loaded / e.total) * 100);
          setUploadProgress(progress);
        }
      });
      
      xhr.onload = function() {
        if (xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.status === 'success') {
            setSuccess('Request submitted successfully!');
            setShowForm(false);
            setFormData({ issueType: '', requestType: '', description: '', video: null });
            fetchRequests();
          } else {
            setError(response.message || 'Failed to submit request');
          }
        } else {
          const response = JSON.parse(xhr.responseText);
          setError(response.message || 'Failed to submit request');
        }
        setLoading(false);
        setUploadProgress(0);
      };
      
      xhr.onerror = function() {
        setError('Network error occurred');
        setLoading(false);
        setUploadProgress(0);
      };
      
      xhr.open('POST', `${API_BASE}/customer/unboxing-requests.php`);
      xhr.setRequestHeader('X-User-ID', auth.user_id);
      xhr.send(formDataToSend);
      
    } catch (err) {
      setError('Failed to submit request');
      setLoading(false);
      setUploadProgress(0);
    }
  };

  const canSubmitRequest = () => {
    if (!order) return false;
    
    // Check if order is delivered
    if (order.status !== 'delivered') return false;
    
    // Check if already has a request
    const hasExistingRequest = requests.some(req => req.order_id === order.id);
    if (hasExistingRequest) return false;
    
    // Check 48-hour window
    if (order.delivered_at) {
      const deliveredTime = new Date(order.delivered_at);
      const now = new Date();
      const hoursDiff = (now - deliveredTime) / (1000 * 60 * 60);
      if (hoursDiff > 48) return false;
    }
    
    return true;
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return '#f59e0b';
      case 'under_review': return '#3b82f6';
      case 'refund_approved': return '#10b981';
      case 'replacement_approved': return '#10b981';
      case 'rejected': return '#ef4444';
      default: return '#6b7280';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'pending': return <LuClock size={14} />;
      case 'under_review': return <LuEye size={14} />;
      case 'refund_approved': return <LuCheck size={14} />;
      case 'replacement_approved': return <LuCheck size={14} />;
      case 'rejected': return <LuX size={14} />;
      default: return <LuClock size={14} />;
    }
  };

  if (!order) {
    return null;
  }

  // Check if there are existing requests for this order
  const orderRequests = requests.filter(req => req.order_id === order.id);

  return (
    <div style={{ 
      marginTop: 16, 
      padding: 16, 
      background: '#fef7ff', 
      borderRadius: 8, 
      border: '1px solid #e9d5ff',
      borderTop: '3px solid #7c3aed'
    }}>
      {/* Header */}
      <div style={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center',
        marginBottom: expanded ? 12 : 0
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <LuVideo size={18} style={{ color: '#7c3aed' }} />
          <span style={{ fontWeight: 600, color: '#581c87', fontSize: 14 }}>
            Unboxing Video Verification
          </span>
        </div>
        <button 
          onClick={() => setExpanded(!expanded)}
          style={{
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            color: '#7c3aed',
            display: 'flex',
            alignItems: 'center',
            gap: 4,
            fontSize: 12,
            fontWeight: 600
          }}
        >
          {expanded ? 'Hide' : 'Show'}
          {expanded ? <LuChevronUp size={16} /> : <LuChevronDown size={16} />}
        </button>
      </div>

      {expanded && (
        <>
          {/* Error/Success Messages */}
          {error && (
            <div style={{ 
              padding: 8, 
              background: '#fee2e2', 
              color: '#991b1b', 
              borderRadius: 4, 
              marginBottom: 12,
              fontSize: 13
            }}>
              {error}
            </div>
          )}
          
          {success && (
            <div style={{ 
              padding: 8, 
              background: '#d1fae5', 
              color: '#065f46', 
              borderRadius: 4, 
              marginBottom: 12,
              fontSize: 13
            }}>
              {success}
            </div>
          )}

          {/* Existing Requests */}
          {orderRequests.length > 0 && (
            <div style={{ marginBottom: 16 }}>
              {orderRequests.map(request => (
                <div key={request.id} style={{ 
                  padding: 12, 
                  background: '#fff', 
                  borderRadius: 6, 
                  border: '1px solid #e5e7eb',
                  marginBottom: 8
                }}>
                  <div style={{ 
                    display: 'flex', 
                    justifyContent: 'space-between', 
                    alignItems: 'center',
                    marginBottom: 6
                  }}>
                    <div style={{ fontSize: 13, fontWeight: 600, color: '#374151' }}>
                      {request.issue_type.replace('_', ' ')} - {request.request_type}
                    </div>
                    <div style={{ 
                      display: 'flex', 
                      alignItems: 'center', 
                      gap: 4,
                      padding: '2px 8px',
                      borderRadius: 12,
                      background: getStatusColor(request.request_status) + '20',
                      color: getStatusColor(request.request_status),
                      fontSize: 12,
                      fontWeight: 600
                    }}>
                      {getStatusIcon(request.request_status)}
                      {request.request_status.replace('_', ' ').toUpperCase()}
                    </div>
                  </div>
                  
                  {request.admin_notes && (
                    <div style={{ 
                      fontSize: 12, 
                      color: '#6b7280',
                      background: '#f9fafb',
                      padding: 8,
                      borderRadius: 4,
                      marginTop: 6
                    }}>
                      <strong>Admin Response:</strong> {request.admin_notes}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* Request Form or Button */}
          {canSubmitRequest() ? (
            !showForm ? (
              <button 
                onClick={() => setShowForm(true)}
                style={{
                  width: '100%',
                  padding: '10px 16px',
                  background: '#dc2626',
                  color: '#fff',
                  border: 'none',
                  borderRadius: 6,
                  fontWeight: 600,
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: 8,
                  fontSize: 14
                }}
              >
                <LuVideo size={16} />
                Report Issue with This Order
              </button>
            ) : (
              <form onSubmit={handleSubmit} style={{ background: '#fff', padding: 16, borderRadius: 6 }}>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
                  <div>
                    <label style={{ display: 'block', marginBottom: 4, fontWeight: 600, fontSize: 12 }}>
                      Issue Type *
                    </label>
                    <select 
                      value={formData.issueType}
                      onChange={(e) => setFormData({ ...formData, issueType: e.target.value })}
                      style={{ width: '100%', padding: 8, border: '1px solid #d1d5db', borderRadius: 4, fontSize: 13 }}
                      required
                    >
                      <option value="">Select issue</option>
                      <option value="product_damaged">Product Damaged</option>
                      <option value="frame_broken">Frame Broken</option>
                      <option value="wrong_item_received">Wrong Item</option>
                      <option value="quality_issue">Quality Issue</option>
                    </select>
                  </div>
                  
                  <div>
                    <label style={{ display: 'block', marginBottom: 4, fontWeight: 600, fontSize: 12 }}>
                      Request Type *
                    </label>
                    <select 
                      value={formData.requestType}
                      onChange={(e) => setFormData({ ...formData, requestType: e.target.value })}
                      style={{ width: '100%', padding: 8, border: '1px solid #d1d5db', borderRadius: 4, fontSize: 13 }}
                      required
                    >
                      <option value="">Select request</option>
                      <option value="refund">Refund</option>
                      <option value="replacement">Replacement</option>
                    </select>
                  </div>
                </div>
                
                <div style={{ marginBottom: 12 }}>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 600, fontSize: 12 }}>
                    Upload Video * (Max 100MB)
                  </label>
                  <input 
                    type="file"
                    accept="video/mp4,video/quicktime,video/x-msvideo"
                    onChange={handleVideoChange}
                    style={{ width: '100%', padding: 8, border: '1px solid #d1d5db', borderRadius: 4, fontSize: 12 }}
                    required
                  />
                </div>
                
                <div style={{ marginBottom: 12 }}>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 600, fontSize: 12 }}>
                    Description
                  </label>
                  <textarea 
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Describe the issue..."
                    rows={2}
                    style={{ width: '100%', padding: 8, border: '1px solid #d1d5db', borderRadius: 4, resize: 'vertical', fontSize: 12 }}
                  />
                </div>
                
                {uploadProgress > 0 && (
                  <div style={{ marginBottom: 12 }}>
                    <div style={{ fontSize: 12, marginBottom: 4 }}>Uploading: {uploadProgress}%</div>
                    <div style={{ width: '100%', height: 6, background: '#e5e7eb', borderRadius: 3 }}>
                      <div style={{ 
                        width: `${uploadProgress}%`, 
                        height: '100%', 
                        background: '#3b82f6', 
                        borderRadius: 3,
                        transition: 'width 0.3s'
                      }} />
                    </div>
                  </div>
                )}
                
                <div style={{ display: 'flex', gap: 8 }}>
                  <button 
                    type="submit"
                    disabled={loading}
                    style={{
                      flex: 1,
                      padding: '8px 16px',
                      background: loading ? '#9ca3af' : '#dc2626',
                      color: '#fff',
                      border: 'none',
                      borderRadius: 4,
                      fontWeight: 600,
                      cursor: loading ? 'not-allowed' : 'pointer',
                      fontSize: 13
                    }}
                  >
                    {loading ? 'Submitting...' : 'Submit'}
                  </button>
                  
                  <button 
                    type="button"
                    onClick={() => setShowForm(false)}
                    style={{
                      padding: '8px 16px',
                      background: '#f3f4f6',
                      color: '#374151',
                      border: 'none',
                      borderRadius: 4,
                      fontWeight: 600,
                      cursor: 'pointer',
                      fontSize: 13
                    }}
                  >
                    Cancel
                  </button>
                </div>
              </form>
            )
          ) : (
            <div style={{ 
              padding: 12, 
              background: '#f3f4f6', 
              borderRadius: 6, 
              color: '#6b7280',
              fontSize: 13,
              textAlign: 'center'
            }}>
              {order.status !== 'delivered' ? (
                "Available for delivered orders only"
              ) : orderRequests.length > 0 ? (
                "Request already submitted for this order"
              ) : (
                "Request window expired (48 hours from delivery)"
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default UnboxingVideoRequestCard;