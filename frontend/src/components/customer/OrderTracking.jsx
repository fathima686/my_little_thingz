import React, { useState, useEffect } from 'react';
import { LuX, LuPackage, LuTruck, LuCheck, LuClock, LuMapPin, LuCalendar, LuDollarSign, LuEye, LuRefreshCw, LuExternalLink, LuDownload, LuStar } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import RateOrders from './RateOrders';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const OrderTracking = ({ onClose }) => {
  const { auth } = useAuth();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [filter, setFilter] = useState('all');
  const [trackingData, setTrackingData] = useState(null);
  const [trackingLoading, setTrackingLoading] = useState(false);
  const [showRateOrders, setShowRateOrders] = useState(false);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/orders.php`, {
        headers: {
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        }
      });
      const data = await response.json();
      if (data.status === 'success') {
        setOrders(data.orders || []);
      }
    } catch (error) {
      console.error('Error fetching orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchLiveTracking = async (orderId) => {
    setTrackingLoading(true);
    try {
      const response = await fetch(`${API_BASE}/customer/track-shipment.php?order_id=${orderId}&user_id=${auth?.user_id}`, {
        headers: {
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        }
      });
      const data = await response.json();
      if (data.status === 'success') {
        setTrackingData(data);
      }
    } catch (error) {
      console.error('Error fetching tracking:', error);
    } finally {
      setTrackingLoading(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status?.toLowerCase()) {
      case 'pending':
        return <LuClock className="status-icon pending" />;
      case 'processing':
        return <LuPackage className="status-icon processing" />;
      case 'shipped':
        return <LuTruck className="status-icon shipped" />;
      case 'delivered':
        return <LuCheck className="status-icon delivered" />;
      default:
        return <LuClock className="status-icon" />;
    }
  };

  const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
      case 'pending':
        return '#f39c12';
      case 'processing':
        return '#3498db';
      case 'shipped':
        return '#9b59b6';
      case 'delivered':
        return '#27ae60';
      case 'cancelled':
        return '#e74c3c';
      default:
        return '#95a5a6';
    }
  };

  const filteredOrders = orders.filter(order => {
    if (filter === 'all') return true;
    return order.status?.toLowerCase() === filter;
  });

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getTrackingSteps = (order) => {
    const steps = [
      {
        title: 'Order Placed',
        date: order.created_at,
        completed: true,
        icon: <LuCheck />
      },
      {
        title: 'Processing',
        date: order.shiprocket_order_id ? order.created_at : null,
        completed: order.shiprocket_order_id || ['processing', 'shipped', 'delivered'].includes(order.status?.toLowerCase()),
        icon: <LuPackage />
      },
      {
        title: 'Shipped',
        date: order.shipped_at || order.pickup_scheduled_date,
        completed: order.awb_code || ['shipped', 'delivered'].includes(order.status?.toLowerCase()),
        icon: <LuTruck />
      },
      {
        title: 'Delivered',
        date: order.delivered_at,
        completed: order.status?.toLowerCase() === 'delivered',
        icon: <LuCheck />
      }
    ];

    return steps;
  };

  const handleViewDetails = async (order) => {
    setSelectedOrder(order);
    setTrackingData(null);
    
    // Fetch live tracking if AWB code exists
    if (order.awb_code || order.shiprocket_shipment_id) {
      await fetchLiveTracking(order.id);
    }
  };

  const handleRefreshTracking = () => {
    if (selectedOrder) {
      fetchLiveTracking(selectedOrder.id);
    }
  };

  const handleDownloadInvoice = async (order) => {
    try {
      const url = `${API_BASE}/customer/download-invoice.php?order_id=${order.id}`;
      
      // Create a temporary link to download the invoice
      const link = document.createElement('a');
      link.href = url;
      link.download = `Invoice-${order.order_number}.html`;
      
      // Add authentication headers by using fetch first
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'X-User-ID': String(auth?.user_id ?? ''),
          'Authorization': `Bearer ${auth?.token ?? ''}`
        }
      });
      
      if (response.ok) {
        // Get the blob content
        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        
        // Create and click the download link
        const downloadLink = document.createElement('a');
        downloadLink.href = blobUrl;
        downloadLink.download = `Invoice-${order.order_number}.html`;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
        
        // Clean up the blob URL
        window.URL.revokeObjectURL(blobUrl);
        
        // Show success message
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { type: 'success', message: 'Invoice downloaded successfully!' } 
        }));
      } else {
        throw new Error('Failed to download invoice');
      }
    } catch (error) {
      console.error('Error downloading invoice:', error);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { type: 'error', message: 'Failed to download invoice. Please try again.' } 
      }));
    }
  };

  if (loading) {
    return (
      <div className="modal-overlay">
        <div className="modal-content large">
          <div className="loading-spinner">Loading orders...</div>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay">
      <div className="modal-content extra-large">
        <div className="modal-header">
          <h2>📦 Order Tracking</h2>
          <div className="header-actions">
            <button 
              className="btn btn-primary"
              onClick={() => setShowRateOrders(true)}
              title="Rate Your Orders"
            >
              <LuStar /> Rate Orders
            </button>
            <button className="btn-close" onClick={onClose}>
              <LuX />
            </button>
          </div>
        </div>

        {/* Filter Tabs */}
        <div className="filter-tabs">
          <button 
            className={`filter-tab ${filter === 'all' ? 'active' : ''}`}
            onClick={() => setFilter('all')}
          >
            All Orders ({orders.length})
          </button>
          <button 
            className={`filter-tab ${filter === 'pending' ? 'active' : ''}`}
            onClick={() => setFilter('pending')}
          >
            Pending ({orders.filter(o => o.status?.toLowerCase() === 'pending').length})
          </button>
          <button 
            className={`filter-tab ${filter === 'processing' ? 'active' : ''}`}
            onClick={() => setFilter('processing')}
          >
            Processing ({orders.filter(o => o.status?.toLowerCase() === 'processing').length})
          </button>
          <button 
            className={`filter-tab ${filter === 'shipped' ? 'active' : ''}`}
            onClick={() => setFilter('shipped')}
          >
            Shipped ({orders.filter(o => o.status?.toLowerCase() === 'shipped').length})
          </button>
          <button 
            className={`filter-tab ${filter === 'delivered' ? 'active' : ''}`}
            onClick={() => setFilter('delivered')}
          >
            Delivered ({orders.filter(o => o.status?.toLowerCase() === 'delivered').length})
          </button>
        </div>

        {/* Orders List */}
        <div className="orders-list">
          {filteredOrders.map(order => (
            <div key={order.id} className="order-card">
              <div className="order-header">
                <div className="order-info">
                  <h3>Order #{order.order_number}</h3>
                  <p className="order-date">
                    <LuCalendar /> Placed on {formatDate(order.created_at)}
                  </p>
                  {order.awb_code && (
                    <p className="tracking-badge">
                      <LuTruck /> AWB: {order.awb_code}
                    </p>
                  )}
                  {order.courier_name && (
                    <p className="courier-badge">
                      📮 {order.courier_name}
                    </p>
                  )}
                </div>
                <div className="order-status">
                  {getStatusIcon(order.status)}
                  <span style={{ color: getStatusColor(order.status) }}>
                    {order.current_status || order.shipment_status || order.status}
                  </span>
                </div>
              </div>

              <div className="order-details">
                <div className="order-items">
                  <h4>Items ({order.items?.length || 0})</h4>
                  <div className="items-preview">
                    {order.items?.slice(0, 3).map((item, index) => (
                      <div key={index} className="item-preview">
                        <img 
                          src={item.image_url || '/api/placeholder/60/60'} 
                          alt={item.name}
                        />
                        <div className="item-info">
                          <span className="item-name">{item.name}</span>
                          <span className="item-quantity">Qty: {item.quantity}</span>
                        </div>
                      </div>
                    ))}
                    {order.items?.length > 3 && (
                      <div className="more-items">
                        +{order.items.length - 3} more items
                      </div>
                    )}
                  </div>
                </div>

                <div className="order-summary">
                  <div className="summary-item">
                    <LuDollarSign />
                    <span>Total: ₹{order.total_amount}</span>
                  </div>
                  {order.shipping_address && (
                    <div className="summary-item">
                      <LuMapPin />
                      <div style={{ display: 'flex', flexDirection: 'column', gap: '2px' }}>
                        {order.shipping_address.split('\n').slice(0, 2).map((line, idx) => (
                          <span key={idx}>{line}</span>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <div className="order-actions">
                <button 
                  className="btn btn-outline"
                  onClick={() => handleViewDetails(order)}
                >
                  <LuEye /> View Live Tracking
                </button>
                {(order.payment_status === 'paid' || order.status === 'processing' || order.status === 'shipped' || order.status === 'delivered') && (
                  <button 
                    className="btn btn-primary"
                    onClick={() => handleDownloadInvoice(order)}
                    title="Download Invoice"
                  >
                    <LuDownload /> Download Invoice
                  </button>
                )}
                {order.awb_code && (
                  <span className="tracking-status">
                    🚚 Tracking Available
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>

        {filteredOrders.length === 0 && (
          <div className="empty-state">
            <LuPackage size={48} />
            <h3>No orders found</h3>
            <p>You don't have any orders matching the selected filter.</p>
          </div>
        )}

        {/* Order Detail Modal with Live Tracking */}
        {selectedOrder && (
          <div className="modal-overlay" style={{ zIndex: 1001 }}>
            <div className="modal-content large">
              <div className="modal-header">
                <h2>🚚 Order #{selectedOrder.order_number}</h2>
                <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                  {(selectedOrder.payment_status === 'paid' || selectedOrder.status === 'processing' || selectedOrder.status === 'shipped' || selectedOrder.status === 'delivered') && (
                    <button 
                      className="btn btn-primary small"
                      onClick={() => handleDownloadInvoice(selectedOrder)}
                      title="Download Invoice"
                    >
                      <LuDownload /> Invoice
                    </button>
                  )}
                  {(selectedOrder.awb_code || selectedOrder.shiprocket_shipment_id) && (
                    <button 
                      className="btn btn-soft small"
                      onClick={handleRefreshTracking}
                      disabled={trackingLoading}
                    >
                      <LuRefreshCw className={trackingLoading ? 'spinning' : ''} /> Refresh
                    </button>
                  )}
                  <button className="btn-close" onClick={() => setSelectedOrder(null)}>
                    <LuX />
                  </button>
                </div>
              </div>

              <div className="order-detail">
                {/* Shiprocket Live Tracking Section */}
                {(selectedOrder.awb_code || selectedOrder.shiprocket_shipment_id) && (
                  <div className="live-tracking-section">
                    <h3>📍 Live Shipment Tracking</h3>
                    
                    {trackingLoading && (
                      <div className="tracking-loading">
                        <LuRefreshCw className="spinning" /> Loading live tracking data...
                      </div>
                    )}

                    {!trackingLoading && trackingData && trackingData.tracking_available && (
                      <>
                        {/* Shipment Info Card */}
                        <div className="shipment-info-card">
                          <div className="info-row">
                            <span className="label">AWB Code:</span>
                            <span className="value">{trackingData.order.awb_code}</span>
                          </div>
                          <div className="info-row">
                            <span className="label">Courier:</span>
                            <span className="value">{trackingData.order.courier_name || 'N/A'}</span>
                          </div>
                          {trackingData.order.pickup_scheduled_date && (
                            <div className="info-row">
                              <span className="label">Pickup Date:</span>
                              <span className="value">{formatDate(trackingData.order.pickup_scheduled_date)}</span>
                            </div>
                          )}
                          {trackingData.order.estimated_delivery && (
                            <div className="info-row">
                              <span className="label">Est. Delivery:</span>
                              <span className="value">{formatDate(trackingData.order.estimated_delivery)}</span>
                            </div>
                          )}
                        </div>

                        {/* Live Tracking Timeline */}
                        {trackingData.tracking_data && trackingData.tracking_data.shipment_track && (
                          <div className="live-tracking-timeline">
                            <h4>📦 Shipment Journey</h4>
                            <div className="timeline">
                              {trackingData.tracking_data.shipment_track.map((track, index) => (
                                <div key={index} className="timeline-item">
                                  <div className="timeline-marker">
                                    <div className="marker-dot"></div>
                                    {index < trackingData.tracking_data.shipment_track.length - 1 && (
                                      <div className="marker-line"></div>
                                    )}
                                  </div>
                                  <div className="timeline-content">
                                    <div className="timeline-header">
                                      <span className="timeline-status">{track.status}</span>
                                      <span className="timeline-date">{formatDateTime(track.date)}</span>
                                    </div>
                                    {track.location && (
                                      <div className="timeline-location">
                                        <LuMapPin size={14} /> {track.location}
                                      </div>
                                    )}
                                    {track.remarks && (
                                      <div className="timeline-remarks">{track.remarks}</div>
                                    )}
                                  </div>
                                </div>
                              ))}
                            </div>
                          </div>
                        )}

                        {/* Current Status from Shiprocket */}
                        {trackingData.tracking_data && trackingData.tracking_data.shipment_status && (
                          <div className="current-status-card">
                            <h4>Current Status</h4>
                            <div className="status-content">
                              <div className="status-badge" style={{ 
                                backgroundColor: getStatusColor(trackingData.tracking_data.shipment_status),
                                color: 'white',
                                padding: '8px 16px',
                                borderRadius: '20px',
                                fontWeight: 'bold'
                              }}>
                                {trackingData.tracking_data.shipment_status}
                              </div>
                              {trackingData.tracking_data.current_status && (
                                <p style={{ marginTop: '10px', color: '#666' }}>
                                  {trackingData.tracking_data.current_status}
                                </p>
                              )}
                            </div>
                          </div>
                        )}
                      </>
                    )}

                    {!trackingLoading && trackingData && !trackingData.tracking_available && (
                      <div className="tracking-unavailable">
                        <LuClock size={32} />
                        <p>Shipment tracking will be available once the courier picks up your package.</p>
                      </div>
                    )}

                    {!trackingLoading && !trackingData && (
                      <div className="tracking-unavailable">
                        <LuPackage size={32} />
                        <p>Shipment is being prepared. Tracking information will be available soon.</p>
                      </div>
                    )}
                  </div>
                )}

                {/* Traditional Tracking Progress */}
                <div className="tracking-section">
                  <h3>Order Progress</h3>
                  <div className="tracking-steps">
                    {getTrackingSteps(selectedOrder).map((step, index) => (
                      <div key={index} className={`tracking-step ${step.completed ? 'completed' : ''}`}>
                        <div className="step-icon">
                          {step.icon}
                        </div>
                        <div className="step-content">
                          <h4>{step.title}</h4>
                          {step.date && <p>{formatDate(step.date)}</p>}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Order Items */}
                <div className="items-section">
                  <h3>Order Items</h3>
                  <div className="detailed-items">
                    {selectedOrder.items?.map((item, index) => (
                      <div key={index} className="detailed-item">
                        <img 
                          src={item.image_url || '/api/placeholder/80/80'} 
                          alt={item.name}
                        />
                        <div className="item-details">
                          <h4>{item.name}</h4>
                          <p>Quantity: {item.quantity}</p>
                          <p>Price: ₹{item.price}</p>
                        </div>
                        <div className="item-total">
                          ₹{(parseFloat(item.price) * item.quantity).toFixed(2)}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Order Summary */}
                <div className="summary-section">
                  <h3>Order Summary</h3>
                  <div className="summary-details">
                    <div className="summary-row">
                      <span>Subtotal:</span>
                      <span>₹{selectedOrder.subtotal || selectedOrder.total_amount}</span>
                    </div>
                    <div className="summary-row">
                      <span>Shipping:</span>
                      <span>₹{selectedOrder.shipping_charges || selectedOrder.shipping_cost || '0.00'}</span>
                    </div>
                    <div className="summary-row">
                      <span>Tax:</span>
                      <span>₹{selectedOrder.tax_amount || '0.00'}</span>
                    </div>
                    <div className="summary-row total">
                      <span>Total:</span>
                      <span>₹{selectedOrder.total_amount}</span>
                    </div>
                  </div>
                </div>

                {/* Shipping Information */}
                {selectedOrder.shipping_address && (
                  <div className="shipping-section">
                    <h3>Shipping Information</h3>
                    <div className="shipping-details">
                      <div style={{ marginBottom: '8px' }}>
                        <strong>Address:</strong>
                        <div style={{ marginTop: '4px', display: 'flex', flexDirection: 'column', gap: '2px' }}>
                          {selectedOrder.shipping_address.split('\n').map((line, idx) => (
                            <span key={idx}>{line}</span>
                          ))}
                        </div>
                      </div>
                      {selectedOrder.awb_code && (
                        <p><strong>AWB Tracking Code:</strong> {selectedOrder.awb_code}</p>
                      )}
                      {selectedOrder.estimated_delivery && (
                        <p><strong>Estimated Delivery:</strong> {formatDate(selectedOrder.estimated_delivery)}</p>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Rate Orders Modal */}
        {showRateOrders && (
          <RateOrders
            onClose={() => setShowRateOrders(false)}
          />
        )}
      </div>

      <style>{`
        .modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.8);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          overflow-y: auto;
          padding: 20px;
        }

        .modal-content {
          background: white;
          border-radius: 16px;
          width: 100%;
          max-width: 600px;
          max-height: 90vh;
          overflow-y: auto;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-content.large {
          max-width: 800px;
        }

        .modal-content.extra-large {
          max-width: 1200px;
        }

        .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 24px;
          border-bottom: 1px solid #e5e7eb;
          position: sticky;
          top: 0;
          background: white;
          z-index: 10;
        }

        .modal-header h2 {
          margin: 0;
          font-size: 24px;
          color: #1f2937;
        }

        .header-actions {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .btn-close {
          background: none;
          border: none;
          font-size: 24px;
          cursor: pointer;
          color: #6b7280;
          padding: 4px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: color 0.2s;
        }

        .btn-close:hover {
          color: #1f2937;
        }

        .filter-tabs {
          display: flex;
          gap: 8px;
          padding: 16px 24px;
          border-bottom: 1px solid #e5e7eb;
          overflow-x: auto;
          background: #f9fafb;
        }

        .filter-tab {
          padding: 8px 16px;
          border: none;
          background: white;
          border-radius: 8px;
          cursor: pointer;
          font-size: 14px;
          font-weight: 500;
          color: #6b7280;
          transition: all 0.2s;
          white-space: nowrap;
        }

        .filter-tab:hover {
          background: #f3f4f6;
          color: #1f2937;
        }

        .filter-tab.active {
          background: #3b82f6;
          color: white;
        }

        .orders-list {
          padding: 24px;
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .order-card {
          border: 1px solid #e5e7eb;
          border-radius: 12px;
          padding: 20px;
          background: white;
          transition: box-shadow 0.2s;
        }

        .order-card:hover {
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .order-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 16px;
        }

        .order-info h3 {
          margin: 0 0 8px 0;
          font-size: 18px;
          color: #1f2937;
        }

        .order-date {
          display: flex;
          align-items: center;
          gap: 6px;
          color: #6b7280;
          font-size: 14px;
          margin: 4px 0;
        }

        .tracking-badge, .courier-badge {
          display: flex;
          align-items: center;
          gap: 6px;
          color: #059669;
          font-size: 13px;
          font-weight: 600;
          margin: 4px 0;
        }

        .courier-badge {
          color: #7c3aed;
        }

        .order-status {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 600;
          font-size: 14px;
        }

        .status-icon {
          font-size: 20px;
        }

        .order-details {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
          margin-bottom: 16px;
        }

        .order-items h4 {
          margin: 0 0 12px 0;
          font-size: 14px;
          color: #6b7280;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .items-preview {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .item-preview {
          display: flex;
          gap: 12px;
          align-items: center;
        }

        .item-preview img {
          width: 50px;
          height: 50px;
          object-fit: cover;
          border-radius: 8px;
        }

        .item-info {
          display: flex;
          flex-direction: column;
          gap: 2px;
        }

        .item-name {
          font-size: 14px;
          color: #1f2937;
          font-weight: 500;
        }

        .item-quantity {
          font-size: 12px;
          color: #6b7280;
        }

        .more-items {
          font-size: 13px;
          color: #6b7280;
          font-style: italic;
          padding: 8px;
          background: #f9fafb;
          border-radius: 6px;
          text-align: center;
        }

        .order-summary {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .summary-item {
          display: flex;
          gap: 8px;
          align-items: flex-start;
          font-size: 14px;
          color: #4b5563;
        }

        .order-actions {
          display: flex;
          gap: 12px;
          align-items: center;
          padding-top: 16px;
          border-top: 1px solid #e5e7eb;
        }

        .btn {
          padding: 10px 20px;
          border-radius: 8px;
          font-size: 14px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s;
          display: flex;
          align-items: center;
          gap: 8px;
          border: none;
        }

        .btn-outline {
          background: white;
          border: 2px solid #3b82f6;
          color: #3b82f6;
        }

        .btn-outline:hover {
          background: #3b82f6;
          color: white;
        }

        .btn-primary {
          background: #3b82f6;
          color: white;
        }

        .btn-primary:hover {
          background: #2563eb;
        }

        .btn-soft {
          background: #f3f4f6;
          color: #1f2937;
        }

        .btn-soft:hover {
          background: #e5e7eb;
        }

        .btn.small {
          padding: 6px 12px;
          font-size: 13px;
        }

        .tracking-status {
          color: #059669;
          font-size: 13px;
          font-weight: 600;
        }

        .empty-state {
          text-align: center;
          padding: 60px 20px;
          color: #6b7280;
        }

        .empty-state svg {
          color: #d1d5db;
          margin-bottom: 16px;
        }

        .empty-state h3 {
          margin: 0 0 8px 0;
          color: #1f2937;
        }

        .order-detail {
          padding: 24px;
          display: flex;
          flex-direction: column;
          gap: 24px;
        }

        .live-tracking-section {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          border-radius: 12px;
          padding: 24px;
          color: white;
        }

        .live-tracking-section h3 {
          margin: 0 0 20px 0;
          font-size: 20px;
        }

        .shipment-info-card {
          background: rgba(255, 255, 255, 0.15);
          backdrop-filter: blur(10px);
          border-radius: 10px;
          padding: 16px;
          margin-bottom: 20px;
        }

        .info-row {
          display: flex;
          justify-content: space-between;
          padding: 8px 0;
          border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .info-row:last-child {
          border-bottom: none;
        }

        .info-row .label {
          font-weight: 600;
          opacity: 0.9;
        }

        .info-row .value {
          font-weight: 700;
        }

        .live-tracking-timeline {
          background: white;
          border-radius: 10px;
          padding: 20px;
          color: #1f2937;
        }

        .live-tracking-timeline h4 {
          margin: 0 0 20px 0;
          color: #1f2937;
        }

        .timeline {
          display: flex;
          flex-direction: column;
          gap: 0;
        }

        .timeline-item {
          display: flex;
          gap: 16px;
          position: relative;
        }

        .timeline-marker {
          display: flex;
          flex-direction: column;
          align-items: center;
          position: relative;
        }

        .marker-dot {
          width: 16px;
          height: 16px;
          border-radius: 50%;
          background: #3b82f6;
          border: 3px solid white;
          box-shadow: 0 0 0 2px #3b82f6;
          z-index: 1;
        }

        .marker-line {
          width: 2px;
          flex: 1;
          background: #e5e7eb;
          min-height: 40px;
        }

        .timeline-content {
          flex: 1;
          padding-bottom: 24px;
        }

        .timeline-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;
        }

        .timeline-status {
          font-weight: 700;
          color: #1f2937;
          font-size: 15px;
        }

        .timeline-date {
          font-size: 13px;
          color: #6b7280;
        }

        .timeline-location {
          display: flex;
          align-items: center;
          gap: 6px;
          color: #059669;
          font-size: 14px;
          margin-bottom: 4px;
        }

        .timeline-remarks {
          color: #6b7280;
          font-size: 13px;
          font-style: italic;
        }

        .current-status-card {
          background: white;
          border-radius: 10px;
          padding: 20px;
          color: #1f2937;
          margin-top: 16px;
        }

        .current-status-card h4 {
          margin: 0 0 12px 0;
        }

        .tracking-loading, .tracking-unavailable {
          text-align: center;
          padding: 40px 20px;
          color: white;
        }

        .tracking-unavailable {
          background: rgba(255, 255, 255, 0.1);
          border-radius: 10px;
        }

        .tracking-unavailable svg {
          margin-bottom: 12px;
          opacity: 0.8;
        }

        .spinning {
          animation: spin 1s linear infinite;
        }

        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }

        .tracking-section {
          background: #f9fafb;
          border-radius: 12px;
          padding: 20px;
        }

        .tracking-section h3 {
          margin: 0 0 20px 0;
          color: #1f2937;
        }

        .tracking-steps {
          display: flex;
          flex-direction: column;
          gap: 0;
        }

        .tracking-step {
          display: flex;
          gap: 16px;
          padding: 16px 0;
          position: relative;
          opacity: 0.5;
        }

        .tracking-step.completed {
          opacity: 1;
        }

        .tracking-step::after {
          content: '';
          position: absolute;
          left: 19px;
          top: 50px;
          width: 2px;
          height: calc(100% - 20px);
          background: #e5e7eb;
        }

        .tracking-step:last-child::after {
          display: none;
        }

        .tracking-step.completed::after {
          background: #3b82f6;
        }

        .step-icon {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          background: #e5e7eb;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          color: #6b7280;
          flex-shrink: 0;
        }

        .tracking-step.completed .step-icon {
          background: #3b82f6;
          color: white;
        }

        .step-content h4 {
          margin: 0 0 4px 0;
          color: #1f2937;
          font-size: 16px;
        }

        .step-content p {
          margin: 0;
          color: #6b7280;
          font-size: 14px;
        }

        .items-section, .summary-section, .shipping-section {
          background: #f9fafb;
          border-radius: 12px;
          padding: 20px;
        }

        .items-section h3, .summary-section h3, .shipping-section h3 {
          margin: 0 0 16px 0;
          color: #1f2937;
        }

        .detailed-items {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .detailed-item {
          display: flex;
          gap: 16px;
          align-items: center;
          background: white;
          padding: 12px;
          border-radius: 8px;
        }

        .detailed-item img {
          width: 60px;
          height: 60px;
          object-fit: cover;
          border-radius: 8px;
        }

        .item-details {
          flex: 1;
        }

        .item-details h4 {
          margin: 0 0 4px 0;
          font-size: 15px;
          color: #1f2937;
        }

        .item-details p {
          margin: 2px 0;
          font-size: 13px;
          color: #6b7280;
        }

        .item-total {
          font-weight: 700;
          color: #1f2937;
          font-size: 16px;
        }

        .summary-details {
          background: white;
          border-radius: 8px;
          padding: 16px;
        }

        .summary-row {
          display: flex;
          justify-content: space-between;
          padding: 8px 0;
          border-bottom: 1px solid #e5e7eb;
        }

        .summary-row:last-child {
          border-bottom: none;
        }

        .summary-row.total {
          font-weight: 700;
          font-size: 18px;
          color: #1f2937;
          padding-top: 12px;
          margin-top: 8px;
          border-top: 2px solid #1f2937;
        }

        .shipping-details {
          background: white;
          border-radius: 8px;
          padding: 16px;
        }

        .shipping-details p {
          margin: 8px 0;
          color: #4b5563;
        }

        .loading-spinner {
          text-align: center;
          padding: 40px;
          color: #6b7280;
        }

        @media (max-width: 768px) {
          .modal-content {
            max-width: 100%;
            max-height: 100vh;
            border-radius: 0;
          }

          .order-details {
            grid-template-columns: 1fr;
          }

          .filter-tabs {
            overflow-x: scroll;
          }
        }
      `}</style>
    </div>
  );
};

export default OrderTracking;