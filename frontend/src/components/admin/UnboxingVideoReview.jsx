import React, { useState, useEffect } from 'react';
import { LuVideo, LuEye, LuCheck, LuX, LuClock, LuUser, LuCalendar } from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

/**
 * UNBOXING VIDEO REVIEW COMPONENT
 * Academic Project - Admin Module
 * 
 * Allows admins to review unboxing video requests and make decisions
 */
const UnboxingVideoReview = ({ adminHeader }) => {
  const [requests, setRequests] = useState([]);
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(false);
  const [filter, setFilter] = useState('all');
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [reviewModal, setReviewModal] = useState(false);
  const [reviewData, setReviewData] = useState({
    status: '',
    notes: ''
  });

  useEffect(() => {
    fetchRequests();
  }, [filter]);

  const fetchRequests = async () => {
    try {
      setLoading(true);
      const url = `${API_BASE}/admin/unboxing-review.php${filter !== 'all' ? `?status=${filter}` : ''}`;
      
      console.log('🔧 Fetching unboxing requests:', url);
      console.log('🔧 Admin headers:', adminHeader);
      
      const res = await fetch(url, {
        headers: adminHeader
      });
      
      console.log('🔧 Response status:', res.status, res.statusText);
      
      const data = await res.json();
      console.log('🔧 Response data:', data);
      
      if (data.status === 'success') {
        setRequests(data.requests);
        setStatistics(data.statistics);
        console.log('✅ Requests loaded:', data.requests.length);
      } else {
        console.error('❌ API error:', data.message);
        // Show error to user
        alert(`Failed to load requests: ${data.message}`);
      }
    } catch (err) {
      console.error('❌ Network error:', err);
      alert(`Network error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const handleReviewSubmit = async (e) => {
    e.preventDefault();
    
    if (!reviewData.status) {
      alert('Please select a decision');
      return;
    }
    
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/unboxing-review.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          ...adminHeader
        },
        body: JSON.stringify({
          request_id: selectedRequest.id,
          status: reviewData.status,
          admin_notes: reviewData.notes
        })
      });
      
      const data = await res.json();
      
      if (data.status === 'success') {
        alert('Request reviewed successfully');
        setReviewModal(false);
        setSelectedRequest(null);
        setReviewData({ status: '', notes: '' });
        fetchRequests();
      } else {
        alert(data.message || 'Failed to update request');
      }
    } catch (err) {
      alert('Failed to submit review');
    } finally {
      setLoading(false);
    }
  };

  const processRefund = async (requestId, orderNumber) => {
    if (!confirm(`Process refund for order #${orderNumber}? This will initiate the actual refund and send notification to the customer.`)) {
      return;
    }
    
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/process-refund.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...adminHeader
        },
        body: JSON.stringify({
          request_id: requestId,
          admin_notes: 'Refund processed through admin dashboard'
        })
      });
      
      const data = await res.json();
      
      if (data.status === 'success') {
        alert(`✅ Refund processed successfully!\n\n💰 Amount: ₹${data.refund_details.amount}\n📧 Customer notification sent\n\nThe customer will receive their refund in 1-7 business days.`);
        fetchRequests(); // Refresh the list
      } else {
        alert(`❌ Refund processing failed: ${data.message}`);
      }
    } catch (err) {
      alert(`❌ Network error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return '#f59e0b';
      case 'under_review': return '#3b82f6';
      case 'refund_approved': return '#10b981';
      case 'refund_processed': return '#059669';
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
      case 'refund_processed': return '💰';
      case 'replacement_approved': return <LuCheck size={16} />;
      case 'rejected': return <LuX size={16} />;
      default: return <LuClock size={16} />;
    }
  };

  const formatIssueType = (type) => {
    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  };

  return (
    <div style={{ padding: 20 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <h2 style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          <LuVideo size={24} />
          Unboxing Video Review
        </h2>
        
        <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
          <select 
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
            style={{ padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6 }}
          >
            <option value="all">All Requests ({Object.values(statistics).reduce((a, b) => a + b, 0)})</option>
            <option value="pending">Pending ({statistics.pending || 0})</option>
            <option value="under_review">Under Review ({statistics.under_review || 0})</option>
            <option value="refund_approved">Refund Approved ({statistics.refund_approved || 0})</option>
            <option value="refund_processed">Refund Processed ({statistics.refund_processed || 0})</option>
            <option value="replacement_approved">Replacement Approved ({statistics.replacement_approved || 0})</option>
            <option value="rejected">Rejected ({statistics.rejected || 0})</option>
          </select>
        </div>
      </div>

      {/* Statistics Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16, marginBottom: 24 }}>
        {Object.entries(statistics).map(([status, count]) => (
          <div key={status} style={{
            padding: 16,
            background: '#fff',
            borderRadius: 8,
            border: '1px solid #e5e7eb',
            textAlign: 'center'
          }}>
            <div style={{ 
              color: getStatusColor(status), 
              fontSize: 24, 
              fontWeight: 'bold',
              marginBottom: 4 
            }}>
              {count}
            </div>
            <div style={{ fontSize: 14, color: '#6b7280', textTransform: 'capitalize' }}>
              {status.replace('_', ' ')}
            </div>
          </div>
        ))}
      </div>

      {/* Requests Table */}
      <div style={{ background: '#fff', borderRadius: 8, border: '1px solid #e5e7eb', overflow: 'hidden' }}>
        {loading ? (
          <div style={{ padding: 40, textAlign: 'center' }}>Loading requests...</div>
        ) : requests.length === 0 ? (
          <div style={{ padding: 40, textAlign: 'center', color: '#6b7280' }}>
            No requests found
          </div>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead style={{ background: '#f9fafb' }}>
              <tr>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Order</th>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Customer</th>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Issue</th>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Request</th>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Status</th>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Submitted</th>
                <th style={{ padding: 12, textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((request) => (
                <tr key={request.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td style={{ padding: 12 }}>
                    <div style={{ fontWeight: 600 }}>#{request.order_number}</div>
                    <div style={{ fontSize: 12, color: '#6b7280' }}>₹{request.total_amount}</div>
                  </td>
                  <td style={{ padding: 12 }}>
                    <div style={{ fontWeight: 600 }}>{request.first_name} {request.last_name}</div>
                    <div style={{ fontSize: 12, color: '#6b7280' }}>{request.customer_email}</div>
                  </td>
                  <td style={{ padding: 12 }}>
                    <div style={{ fontWeight: 600 }}>{formatIssueType(request.issue_type)}</div>
                  </td>
                  <td style={{ padding: 12 }}>
                    <div style={{ 
                      padding: '4px 8px',
                      borderRadius: 4,
                      background: request.request_type === 'refund' ? '#fee2e2' : '#dbeafe',
                      color: request.request_type === 'refund' ? '#991b1b' : '#1e40af',
                      fontSize: 12,
                      fontWeight: 600,
                      textTransform: 'uppercase',
                      display: 'inline-block'
                    }}>
                      {request.request_type}
                    </div>
                  </td>
                  <td style={{ padding: 12 }}>
                    <div style={{ 
                      display: 'flex', 
                      alignItems: 'center', 
                      gap: 6,
                      color: getStatusColor(request.request_status),
                      fontWeight: 600,
                      fontSize: 14
                    }}>
                      {getStatusIcon(request.request_status)}
                      {request.request_status.replace('_', ' ').toUpperCase()}
                    </div>
                  </td>
                  <td style={{ padding: 12 }}>
                    <div style={{ fontSize: 14 }}>{new Date(request.created_at).toLocaleDateString()}</div>
                    <div style={{ fontSize: 12, color: '#6b7280' }}>
                      {request.hours_since_request}h ago
                    </div>
                  </td>
                  <td style={{ padding: 12 }}>
                    <div style={{ display: 'flex', gap: 8 }}>
                      <button
                        onClick={() => {
                          setSelectedRequest(request);
                          setReviewModal(true);
                          setReviewData({ status: request.request_status, notes: request.admin_notes || '' });
                        }}
                        style={{
                          padding: '6px 12px',
                          background: '#3b82f6',
                          color: '#fff',
                          border: 'none',
                          borderRadius: 4,
                          fontSize: 12,
                          cursor: 'pointer',
                          display: 'flex',
                          alignItems: 'center',
                          gap: 4
                        }}
                      >
                        <LuEye size={14} />
                        Review
                      </button>
                      
                      {/* Process Refund Button for approved refunds */}
                      {request.request_status === 'refund_approved' && (
                        <button
                          onClick={() => processRefund(request.id, request.order_number)}
                          style={{
                            padding: '6px 12px',
                            background: '#10b981',
                            color: '#fff',
                            border: 'none',
                            borderRadius: 4,
                            fontSize: 12,
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: 4
                          }}
                        >
                          💰 Process Refund
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Review Modal */}
      {reviewModal && selectedRequest && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.5)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        }}>
          <div style={{
            background: '#fff',
            borderRadius: 8,
            width: '90%',
            maxWidth: 800,
            maxHeight: '90vh',
            overflow: 'auto'
          }}>
            <div style={{ padding: 20, borderBottom: '1px solid #e5e7eb' }}>
              <h3 style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
                <LuVideo size={20} />
                Review Unboxing Request - Order #{selectedRequest.order_number}
              </h3>
            </div>
            
            <div style={{ padding: 20 }}>
              {/* Request Details */}
              <div style={{ marginBottom: 20 }}>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
                  <div>
                    <strong>Customer:</strong> {selectedRequest.first_name} {selectedRequest.last_name}
                    <br />
                    <strong>Email:</strong> {selectedRequest.customer_email}
                  </div>
                  <div>
                    <strong>Issue:</strong> {formatIssueType(selectedRequest.issue_type)}
                    <br />
                    <strong>Request Type:</strong> {selectedRequest.request_type.toUpperCase()}
                  </div>
                </div>
                
                {selectedRequest.customer_description && (
                  <div style={{ marginBottom: 16 }}>
                    <strong>Customer Description:</strong>
                    <div style={{ 
                      padding: 12, 
                      background: '#f9fafb', 
                      borderRadius: 6, 
                      marginTop: 4,
                      border: '1px solid #e5e7eb'
                    }}>
                      {selectedRequest.customer_description}
                    </div>
                  </div>
                )}
              </div>

              {/* Video Player */}
              <div style={{ marginBottom: 20 }}>
                <strong>Unboxing Video:</strong>
                <div style={{ marginTop: 8 }}>
                  <video 
                    controls 
                    style={{ width: '100%', maxHeight: 400, borderRadius: 6 }}
                    src={`http://localhost/my_little_thingz/backend/${selectedRequest.video_path}`}
                  >
                    Your browser does not support the video tag.
                  </video>
                </div>
                <div style={{ fontSize: 12, color: '#6b7280', marginTop: 4 }}>
                  File: {selectedRequest.video_filename} ({(selectedRequest.video_size_bytes / 1024 / 1024).toFixed(2)} MB)
                </div>
              </div>

              {/* Review Form */}
              <form onSubmit={handleReviewSubmit}>
                <div style={{ marginBottom: 16 }}>
                  <label style={{ display: 'block', marginBottom: 8, fontWeight: 600 }}>
                    Admin Decision *
                  </label>
                  <select 
                    value={reviewData.status}
                    onChange={(e) => setReviewData({ ...reviewData, status: e.target.value })}
                    style={{ width: '100%', padding: 12, border: '1px solid #d1d5db', borderRadius: 6 }}
                    required
                  >
                    <option value="">Select decision</option>
                    <option value="under_review">Mark as Under Review</option>
                    <option value="refund_approved">Approve Refund</option>
                    <option value="replacement_approved">Approve Replacement</option>
                    <option value="rejected">Reject Request</option>
                  </select>
                </div>
                
                <div style={{ marginBottom: 20 }}>
                  <label style={{ display: 'block', marginBottom: 8, fontWeight: 600 }}>
                    Admin Notes
                  </label>
                  <textarea 
                    value={reviewData.notes}
                    onChange={(e) => setReviewData({ ...reviewData, notes: e.target.value })}
                    placeholder="Add notes about your decision..."
                    rows={4}
                    style={{ width: '100%', padding: 12, border: '1px solid #d1d5db', borderRadius: 6, resize: 'vertical' }}
                  />
                </div>
                
                <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                  <button 
                    type="button"
                    onClick={() => {
                      setReviewModal(false);
                      setSelectedRequest(null);
                      setReviewData({ status: '', notes: '' });
                    }}
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
                      cursor: loading ? 'not-allowed' : 'pointer'
                    }}
                  >
                    {loading ? 'Saving...' : 'Submit Review'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default UnboxingVideoReview;