import React, { useState, useEffect } from 'react';
import { LuUpload, LuVideo, LuClock, LuCheck, LuX, LuEye } from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

/**
 * UNBOXING VIDEO VERIFICATION COMPONENT
 * Academic Project - Customer Module
 * 
 * Allows customers to upload unboxing videos for refund/replacement requests
 */
const UnboxingVideoRequest = ({ auth, order }) => {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
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
            setSuccess('Request submitted successfully! We will review your video and get back to you.');
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
      case 'pending': return <LuClock size={16} />;
      case 'under_review': return <LuEye size={16} />;
      case 'refund_approved': return <LuCheck size={16} />;
      case 'replacement_approved': return <LuCheck size={16} />;
      case 'rejected': return <LuX size={16} />;
      default: return <LuClock size={16} />;
    }
  };

  if (!order) {
    return null;
  }

  return (
    <div style={{ marginTop: 24, padding: 20, background: '#f9fafb', borderRadius: 8, border: '1px solid #e5e7eb' }}>
      <h3 style={{ margin: 0, marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
        <LuVideo size={20} />
        Report an Issue - Unboxing Video Verification
      </h3>
      
      {/* Error/Success Messages */}
      {error && (
        <div style={{ padding: 12, background: '#fee2e2', color: '#991b1b', borderRadius: 6, marginBottom: 16 }}>
          {error}
        </div>
      )}
      
      {success && (
        <div style={{ padding: 12, background: '#d1fae5', color: '#065f46', borderRadius: 6, marginBottom: 16 }}>
          {success}
        </div>
      )}
      
      {/* Existing Requests */}
      {requests.length > 0 && (
        <div style={{ marginBottom: 20 }}>
          <h4>Your Requests</h4>
          {requests.map(request => (
            <div key={request.id} style={{ 
              padding: 16, 
              background: '#fff', 
              borderRadius: 8, 
              border: '1px solid #e5e7eb',
              marginBottom: 12
            }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                <div style={{ fontWeight: 600 }}>Order #{request.order_number}</div>
                <div style={{ 
                  display: 'flex', 
                  alignItems: 'center', 
                  gap: 6,
                  padding: '4px 12px',
                  borderRadius: 20,
                  background: getStatusColor(request.request_status) + '20',
                  color: getStatusColor(request.request_status),
                  fontSize: 14,
                  fontWeight: 600
                }}>
                  {getStatusIcon(request.request_status)}
                  {request.request_status.replace('_', ' ').toUpperCase()}
                </div>
              </div>
              
              <div style={{ fontSize: 14, color: '#6b7280', marginBottom: 8 }}>
                <strong>Issue:</strong> {request.issue_type.replace('_', ' ')} | 
                <strong> Request:</strong> {request.request_type} |
                <strong> Submitted:</strong> {new Date(request.created_at).toLocaleDateString()}
              </div>
              
              {request.customer_description && (
                <div style={{ fontSize: 14, color: '#374151', marginBottom: 8 }}>
                  <strong>Description:</strong> {request.customer_description}
                </div>
              )}
              
              {request.admin_notes && (
                <div style={{ 
                  padding: 12, 
                  background: '#f3f4f6', 
                  borderRadius: 6,
                  fontSize: 14,
                  color: '#374151'
                }}>
                  <strong>Admin Response:</strong> {request.admin_notes}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
      
      {/* Request Form */}
      {canSubmitRequest() ? (
        !showForm ? (
          <button 
            onClick={() => setShowForm(true)}
            style={{
              padding: '12px 24px',
              background: '#dc2626',
              color: '#fff',
              border: 'none',
              borderRadius: 8,
              fontWeight: 600,
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              gap: 8
            }}
          >
            <LuVideo size={18} />
            Report Issue with This Order
          </button>
        ) : (
          <form onSubmit={handleSubmit} style={{ background: '#fff', padding: 20, borderRadius: 8, border: '1px solid #e5e7eb' }}>
            <h4 style={{ margin: 0, marginBottom: 16 }}>Submit Unboxing Video Request</h4>
            
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: 'block', marginBottom: 8, fontWeight: 600 }}>
                What's the issue? *
              </label>
              <select 
                value={formData.issueType}
                onChange={(e) => setFormData({ ...formData, issueType: e.target.value })}
                style={{ width: '100%', padding: 12, border: '1px solid #d1d5db', borderRadius: 6 }}
                required
              >
                <option value="">Select issue type</option>
                <option value="product_damaged">Product Damaged</option>
                <option value="frame_broken">Frame Broken</option>
                <option value="wrong_item_received">Wrong Item Received</option>
                <option value="quality_issue">Quality Issue</option>
              </select>
            </div>
            
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: 'block', marginBottom: 8, fontWeight: 600 }}>
                What would you like? *
              </label>
              <select 
                value={formData.requestType}
                onChange={(e) => setFormData({ ...formData, requestType: e.target.value })}
                style={{ width: '100%', padding: 12, border: '1px solid #d1d5db', borderRadius: 6 }}
                required
              >
                <option value="">Select request type</option>
                <option value="refund">Full Refund</option>
                <option value="replacement">Replacement</option>
              </select>
            </div>
            
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: 'block', marginBottom: 8, fontWeight: 600 }}>
                Upload Unboxing Video * (Max 100MB)
              </label>
              <input 
                type="file"
                accept="video/mp4,video/quicktime,video/x-msvideo"
                onChange={handleVideoChange}
                style={{ width: '100%', padding: 12, border: '1px solid #d1d5db', borderRadius: 6 }}
                required
              />
              <div style={{ fontSize: 12, color: '#6b7280', marginTop: 4 }}>
                Supported formats: MP4, MOV, AVI. Please record the complete unboxing process.
              </div>
            </div>
            
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: 'block', marginBottom: 8, fontWeight: 600 }}>
                Description (Optional)
              </label>
              <textarea 
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Please describe the issue in detail..."
                rows={4}
                style={{ width: '100%', padding: 12, border: '1px solid #d1d5db', borderRadius: 6, resize: 'vertical' }}
              />
            </div>
            
            {uploadProgress > 0 && (
              <div style={{ marginBottom: 16 }}>
                <div style={{ fontSize: 14, marginBottom: 4 }}>Uploading: {uploadProgress}%</div>
                <div style={{ width: '100%', height: 8, background: '#e5e7eb', borderRadius: 4 }}>
                  <div style={{ 
                    width: `${uploadProgress}%`, 
                    height: '100%', 
                    background: '#3b82f6', 
                    borderRadius: 4,
                    transition: 'width 0.3s'
                  }} />
                </div>
              </div>
            )}
            
            <div style={{ display: 'flex', gap: 12 }}>
              <button 
                type="submit"
                disabled={loading}
                style={{
                  padding: '12px 24px',
                  background: loading ? '#9ca3af' : '#dc2626',
                  color: '#fff',
                  border: 'none',
                  borderRadius: 6,
                  fontWeight: 600,
                  cursor: loading ? 'not-allowed' : 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: 8
                }}
              >
                <LuUpload size={18} />
                {loading ? 'Submitting...' : 'Submit Request'}
              </button>
              
              <button 
                type="button"
                onClick={() => setShowForm(false)}
                style={{
                  padding: '12px 24px',
                  background: '#f3f4f6',
                  color: '#374151',
                  border: 'none',
                  borderRadius: 6,
                  fontWeight: 600,
                  cursor: 'pointer'
                }}
              >
                Cancel
              </button>
            </div>
          </form>
        )
      ) : (
        <div style={{ padding: 16, background: '#f3f4f6', borderRadius: 8, color: '#6b7280' }}>
          {order.status !== 'delivered' ? (
            "Unboxing requests are only available for delivered orders."
          ) : requests.some(req => req.order_id === order.id) ? (
            "You have already submitted a request for this order."
          ) : (
            "Unboxing requests must be submitted within 48 hours of delivery."
          )}
        </div>
      )}
    </div>
  );
};

export default UnboxingVideoRequest;