import React, { useState, useEffect } from 'react';
import { LuClock, LuCheck, LuX, LuRefreshCw, LuMail, LuCreditCard } from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

/**
 * REFUND STATUS COMPONENT
 * Shows refund status for orders with unboxing requests
 */
const RefundStatus = ({ auth, order }) => {
  const [refundStatus, setRefundStatus] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (order && order.status === 'delivered') {
      fetchRefundStatus();
    }
  }, [order]);

  const fetchRefundStatus = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/customer/refund-status.php?order_id=${order.id}`, {
        headers: {
          'X-User-ID': auth.user_id
        }
      });
      const data = await res.json();
      
      if (data.status === 'success' && data.refund_request) {
        setRefundStatus(data.refund_request);
      }
    } catch (err) {
      console.error('Failed to fetch refund status:', err);
    } finally {
      setLoading(false);
    }
  };

  const getStatusInfo = (status) => {
    switch (status) {
      case 'pending':
        return {
          icon: <LuClock size={16} />,
          color: '#f59e0b',
          text: 'Under Review',
          description: 'Your refund request is being reviewed by our team.'
        };
      case 'under_review':
        return {
          icon: <LuRefreshCw size={16} />,
          color: '#3b82f6',
          text: 'Under Review',
          description: 'Our team is currently reviewing your unboxing video and request.'
        };
      case 'refund_approved':
        return {
          icon: <LuCheck size={16} />,
          color: '#10b981',
          text: 'Refund Approved',
          description: 'Your refund has been approved and will be processed shortly.'
        };
      case 'refund_processed':
        return {
          icon: '💰',
          color: '#059669',
          text: 'Refund Processed',
          description: 'Your refund has been processed. You will receive the money in 1-7 business days.'
        };
      case 'replacement_approved':
        return {
          icon: <LuCheck size={16} />,
          color: '#10b981',
          text: 'Replacement Approved',
          description: 'Your replacement request has been approved. We will contact you soon.'
        };
      case 'rejected':
        return {
          icon: <LuX size={16} />,
          color: '#ef4444',
          text: 'Request Rejected',
          description: 'Your request has been reviewed and rejected. Contact support for more details.'
        };
      default:
        return {
          icon: <LuClock size={16} />,
          color: '#6b7280',
          text: 'Unknown Status',
          description: 'Please contact support for status update.'
        };
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div style={{ padding: 16, textAlign: 'center', color: '#6b7280' }}>
        Loading refund status...
      </div>
    );
  }

  if (!refundStatus) {
    return null; // No refund request for this order
  }

  const statusInfo = getStatusInfo(refundStatus.request_status);

  return (
    <div style={{
      border: '2px solid #e5e7eb',
      borderRadius: 12,
      padding: 20,
      margin: '16px 0',
      background: '#f8fafc'
    }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
        <div style={{
          width: 40,
          height: 40,
          borderRadius: '50%',
          background: statusInfo.color,
          color: 'white',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: 18
        }}>
          {statusInfo.icon}
        </div>
        <div>
          <h3 style={{ margin: 0, color: statusInfo.color, fontSize: 18 }}>
            {statusInfo.text}
          </h3>
          <p style={{ margin: 0, color: '#6b7280', fontSize: 14 }}>
            Refund Request #{refundStatus.id}
          </p>
        </div>
      </div>

      <div style={{ marginBottom: 16 }}>
        <p style={{ margin: 0, color: '#374151', lineHeight: 1.5 }}>
          {statusInfo.description}
        </p>
      </div>

      {/* Refund Details */}
      <div style={{
        background: 'white',
        padding: 16,
        borderRadius: 8,
        border: '1px solid #e5e7eb'
      }}>
        <h4 style={{ margin: '0 0 12px 0', color: '#374151' }}>📋 Request Details</h4>
        
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, fontSize: 14 }}>
          <div>
            <strong>Issue Type:</strong><br />
            {refundStatus.issue_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
          </div>
          <div>
            <strong>Request Type:</strong><br />
            <span style={{ 
              color: refundStatus.request_type === 'refund' ? '#dc2626' : '#2563eb',
              fontWeight: 600,
              textTransform: 'uppercase'
            }}>
              {refundStatus.request_type}
            </span>
          </div>
          <div>
            <strong>Submitted:</strong><br />
            {formatDate(refundStatus.created_at)}
          </div>
          {refundStatus.refund_processed_at && (
            <div>
              <strong>Processed:</strong><br />
              {formatDate(refundStatus.refund_processed_at)}
            </div>
          )}
        </div>

        {refundStatus.refund_amount && (
          <div style={{
            marginTop: 16,
            padding: 12,
            background: '#f0f9ff',
            border: '1px solid #0ea5e9',
            borderRadius: 6,
            textAlign: 'center'
          }}>
            <div style={{ color: '#0c4a6e', fontSize: 14, marginBottom: 4 }}>
              <LuCreditCard style={{ display: 'inline', marginRight: 4 }} />
              Refund Amount
            </div>
            <div style={{ color: '#0c4a6e', fontSize: 24, fontWeight: 'bold' }}>
              ₹{refundStatus.refund_amount}
            </div>
          </div>
        )}

        {refundStatus.admin_notes && (
          <div style={{
            marginTop: 16,
            padding: 12,
            background: '#fef3c7',
            border: '1px solid #f59e0b',
            borderRadius: 6
          }}>
            <div style={{ color: '#92400e', fontSize: 14, fontWeight: 600, marginBottom: 4 }}>
              Admin Notes:
            </div>
            <div style={{ color: '#92400e', fontSize: 14 }}>
              {refundStatus.admin_notes}
            </div>
          </div>
        )}
      </div>

      {/* Refund Timeline for processed refunds */}
      {refundStatus.request_status === 'refund_processed' && (
        <div style={{
          marginTop: 16,
          padding: 16,
          background: '#ecfdf5',
          border: '1px solid #10b981',
          borderRadius: 8
        }}>
          <h4 style={{ margin: '0 0 12px 0', color: '#065f46', display: 'flex', alignItems: 'center', gap: 8 }}>
            <LuMail size={16} />
            What happens next?
          </h4>
          <ul style={{ margin: 0, paddingLeft: 20, color: '#065f46', fontSize: 14 }}>
            <li>✅ Refund has been initiated with your payment provider</li>
            <li>📧 You will receive a confirmation email with refund details</li>
            <li>💳 Money will be credited to your original payment method</li>
            <li>⏰ Processing time: 1-7 business days (depending on your bank)</li>
          </ul>
        </div>
      )}

      {/* Contact Support */}
      <div style={{
        marginTop: 16,
        padding: 12,
        background: '#f1f5f9',
        border: '1px solid #cbd5e1',
        borderRadius: 6,
        textAlign: 'center',
        fontSize: 14,
        color: '#475569'
      }}>
        Need help? Contact us at <strong>support@mylittlethingz.com</strong> or <strong>+91 9876543210</strong>
      </div>
    </div>
  );
};

export default RefundStatus;