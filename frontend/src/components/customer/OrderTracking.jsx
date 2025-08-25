import React, { useState, useEffect } from 'react';
import { LuX, LuPackage, LuTruck, LuCheck, LuClock, LuMapPin, LuCalendar, LuDollarSign, LuEye } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const OrderTracking = ({ onClose }) => {
  const { auth } = useAuth();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [filter, setFilter] = useState('all');

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

  const getStatusIcon = (status) => {
    switch (status.toLowerCase()) {
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
    switch (status.toLowerCase()) {
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
    return order.status.toLowerCase() === filter;
  });

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getTrackingSteps = (status, orderDate, shippedDate, deliveredDate) => {
    const steps = [
      {
        title: 'Order Placed',
        date: orderDate,
        completed: true,
        icon: <LuCheck />
      },
      {
        title: 'Processing',
        date: null,
        completed: ['processing', 'shipped', 'delivered'].includes(status.toLowerCase()),
        icon: <LuPackage />
      },
      {
        title: 'Shipped',
        date: shippedDate,
        completed: ['shipped', 'delivered'].includes(status.toLowerCase()),
        icon: <LuTruck />
      },
      {
        title: 'Delivered',
        date: deliveredDate,
        completed: status.toLowerCase() === 'delivered',
        icon: <LuCheck />
      }
    ];

    return steps;
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
          <h2>Order Tracking</h2>
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
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
            Pending ({orders.filter(o => o.status.toLowerCase() === 'pending').length})
          </button>
          <button 
            className={`filter-tab ${filter === 'processing' ? 'active' : ''}`}
            onClick={() => setFilter('processing')}
          >
            Processing ({orders.filter(o => o.status.toLowerCase() === 'processing').length})
          </button>
          <button 
            className={`filter-tab ${filter === 'shipped' ? 'active' : ''}`}
            onClick={() => setFilter('shipped')}
          >
            Shipped ({orders.filter(o => o.status.toLowerCase() === 'shipped').length})
          </button>
          <button 
            className={`filter-tab ${filter === 'delivered' ? 'active' : ''}`}
            onClick={() => setFilter('delivered')}
          >
            Delivered ({orders.filter(o => o.status.toLowerCase() === 'delivered').length})
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
                </div>
                <div className="order-status">
                  {getStatusIcon(order.status)}
                  <span style={{ color: getStatusColor(order.status) }}>
                    {order.status}
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
                    <span>Total: ${order.total_amount}</span>
                  </div>
                  {order.shipping_address && (
                    <div className="summary-item">
                      <LuMapPin />
                      <span>{order.shipping_address}</span>
                    </div>
                  )}
                </div>
              </div>

              <div className="order-actions">
                <button 
                  className="btn btn-outline"
                  onClick={() => setSelectedOrder(order)}
                >
                  <LuEye /> View Details
                </button>
                {order.tracking_number && (
                  <button className="btn btn-primary">
                    Track Package
                  </button>
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

        {/* Order Detail Modal */}
        {selectedOrder && (
          <div className="modal-overlay">
            <div className="modal-content large">
              <div className="modal-header">
                <h2>Order #{selectedOrder.order_number}</h2>
                <button className="btn-close" onClick={() => setSelectedOrder(null)}>
                  <LuX />
                </button>
              </div>

              <div className="order-detail">
                {/* Tracking Progress */}
                <div className="tracking-section">
                  <h3>Order Progress</h3>
                  <div className="tracking-steps">
                    {getTrackingSteps(
                      selectedOrder.status,
                      selectedOrder.created_at,
                      selectedOrder.shipped_at,
                      selectedOrder.delivered_at
                    ).map((step, index) => (
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
                          <p>Price: ${item.price}</p>
                        </div>
                        <div className="item-total">
                          ${(item.price * item.quantity).toFixed(2)}
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
                      <span>${selectedOrder.subtotal || selectedOrder.total_amount}</span>
                    </div>
                    <div className="summary-row">
                      <span>Shipping:</span>
                      <span>${selectedOrder.shipping_cost || '0.00'}</span>
                    </div>
                    <div className="summary-row">
                      <span>Tax:</span>
                      <span>${selectedOrder.tax_amount || '0.00'}</span>
                    </div>
                    <div className="summary-row total">
                      <span>Total:</span>
                      <span>${selectedOrder.total_amount}</span>
                    </div>
                  </div>
                </div>

                {/* Shipping Information */}
                {selectedOrder.shipping_address && (
                  <div className="shipping-section">
                    <h3>Shipping Information</h3>
                    <div className="shipping-details">
                      <p><strong>Address:</strong> {selectedOrder.shipping_address}</p>
                      {selectedOrder.tracking_number && (
                        <p><strong>Tracking Number:</strong> {selectedOrder.tracking_number}</p>
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
        }

        .modal-content {
          background: white;
          border-radius: 12px;
          padding: 24px;
          max-height: 90vh;
          overflow-y: auto;
          width: 90%;
          max-width: 800px;
        }

        .modal-content.large {
          max-width: 1000px;
        }

        .modal-content.extra-large {
          max-width: 1200px;
        }

        .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 24px;
          padding-bottom: 16px;
          border-bottom: 1px solid #eee;
        }

        .btn-close {
          background: none;
          border: none;
          font-size: 24px;
          cursor: pointer;
          padding: 8px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .btn-close:hover {
          background: #f5f5f5;
        }

        .filter-tabs {
          display: flex;
          gap: 8px;
          margin-bottom: 24px;
          flex-wrap: wrap;
        }

        .filter-tab {
          padding: 8px 16px;
          border: 1px solid #ddd;
          background: white;
          border-radius: 20px;
          cursor: pointer;
          transition: all 0.2s;
          font-size: 14px;
        }

        .filter-tab:hover {
          background: #f8f9fa;
        }

        .filter-tab.active {
          background: #3498db;
          color: white;
          border-color: #3498db;
        }

        .orders-list {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .order-card {
          border: 1px solid #eee;
          border-radius: 12px;
          padding: 20px;
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
          margin: 0 0 4px 0;
          font-size: 18px;
        }

        .order-date {
          margin: 0;
          color: #666;
          display: flex;
          align-items: center;
          gap: 4px;
          font-size: 14px;
        }

        .order-status {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 500;
        }

        .status-icon {
          font-size: 20px;
        }

        .status-icon.pending { color: #f39c12; }
        .status-icon.processing { color: #3498db; }
        .status-icon.shipped { color: #9b59b6; }
        .status-icon.delivered { color: #27ae60; }

        .order-details {
          display: grid;
          grid-template-columns: 2fr 1fr;
          gap: 24px;
          margin-bottom: 16px;
        }

        .order-items h4 {
          margin: 0 0 12px 0;
          font-size: 16px;
        }

        .items-preview {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .item-preview {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .item-preview img {
          width: 40px;
          height: 40px;
          border-radius: 6px;
          object-fit: cover;
        }

        .item-info {
          display: flex;
          flex-direction: column;
          gap: 2px;
        }

        .item-name {
          font-weight: 500;
          font-size: 14px;
        }

        .item-quantity {
          font-size: 12px;
          color: #666;
        }

        .more-items {
          font-size: 12px;
          color: #666;
          font-style: italic;
        }

        .order-summary {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .summary-item {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 14px;
        }

        .order-actions {
          display: flex;
          gap: 12px;
          justify-content: flex-end;
        }

        .btn {
          padding: 8px 16px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 14px;
          font-weight: 500;
          transition: all 0.2s;
        }

        .btn-primary {
          background: #3498db;
          color: white;
        }

        .btn-primary:hover {
          background: #2980b9;
        }

        .btn-outline {
          background: transparent;
          border: 1px solid #ddd;
          color: #333;
        }

        .btn-outline:hover {
          background: #f8f9fa;
        }

        .empty-state {
          text-align: center;
          padding: 48px;
          color: #666;
        }

        .empty-state h3 {
          margin: 16px 0 8px 0;
        }

        .loading-spinner {
          text-align: center;
          padding: 48px;
        }

        .order-detail {
          display: flex;
          flex-direction: column;
          gap: 32px;
        }

        .tracking-section h3,
        .items-section h3,
        .summary-section h3,
        .shipping-section h3 {
          margin: 0 0 16px 0;
          color: #2c3e50;
          border-bottom: 2px solid #3498db;
          padding-bottom: 8px;
        }

        .tracking-steps {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .tracking-step {
          display: flex;
          align-items: center;
          gap: 16px;
          padding: 12px;
          border-radius: 8px;
          transition: all 0.2s;
        }

        .tracking-step.completed {
          background: #f8f9fa;
        }

        .step-icon {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          background: #ecf0f1;
          color: #95a5a6;
        }

        .tracking-step.completed .step-icon {
          background: #27ae60;
          color: white;
        }

        .step-content h4 {
          margin: 0 0 4px 0;
        }

        .step-content p {
          margin: 0;
          font-size: 14px;
          color: #666;
        }

        .detailed-items {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .detailed-item {
          display: flex;
          align-items: center;
          gap: 16px;
          padding: 16px;
          border: 1px solid #eee;
          border-radius: 8px;
        }

        .detailed-item img {
          width: 60px;
          height: 60px;
          border-radius: 8px;
          object-fit: cover;
        }

        .item-details {
          flex: 1;
        }

        .item-details h4 {
          margin: 0 0 4px 0;
        }

        .item-details p {
          margin: 0 0 2px 0;
          font-size: 14px;
          color: #666;
        }

        .item-total {
          font-weight: 600;
          font-size: 16px;
        }

        .summary-details {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .summary-row {
          display: flex;
          justify-content: space-between;
          padding: 8px 0;
        }

        .summary-row.total {
          border-top: 1px solid #eee;
          font-weight: 600;
          font-size: 18px;
        }

        .shipping-details p {
          margin: 0 0 8px 0;
        }

        @media (max-width: 768px) {
          .order-details {
            grid-template-columns: 1fr;
          }
          
          .order-header {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
          }
          
          .filter-tabs {
            flex-direction: column;
          }
          
          .order-actions {
            justify-content: flex-start;
          }
        }
      `}</style>
    </div>
  );
};

export default OrderTracking;