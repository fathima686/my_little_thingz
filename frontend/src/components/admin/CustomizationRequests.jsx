import React, { useState, useEffect } from 'react';
import { LuEye, LuCheck, LuX, LuClock, LuUser, LuCalendar, LuDollarSign } from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const CustomizationRequests = ({ onClose }) => {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [filter, setFilter] = useState('all');

  useEffect(() => {
    fetchRequests();
  }, [filter]);

  const fetchRequests = async () => {
    try {
      const status = filter === 'all' ? 'all' : filter;
      const response = await fetch(`${API_BASE}/admin/custom-requests.php?status=${status}`);
      const data = await response.json();
      
      if (data.status === 'success') {
        setRequests(data.requests || []);
      }
    } catch (error) {
      console.error('Error fetching requests:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateRequestStatus = async (requestId, newStatus) => {
    try {
      const response = await fetch(`${API_BASE}/admin/custom-requests.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          request_id: requestId,
          status: newStatus
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        setRequests(prev => 
          prev.map(req => 
            req.id === requestId ? { ...req, status: newStatus } : req
          )
        );
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'success', 
            message: `Request ${newStatus} successfully` 
          } 
        }));
      } else {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'error', 
            message: data.message || 'Failed to update request' 
          } 
        }));
      }
    } catch (error) {
      console.error('Error updating request:', error);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'error', 
          message: 'Error updating request' 
        } 
      }));
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return '#f59e0b';
      case 'in_progress': return '#3b82f6';
      case 'completed': return '#10b981';
      case 'cancelled': return '#ef4444';
      default: return '#6b7280';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'pending': return <LuClock />;
      case 'in_progress': return <LuEye />;
      case 'completed': return <LuCheck />;
      case 'cancelled': return <LuX />;
      default: return <LuClock />;
    }
  };

  if (loading) {
    return (
      <div className="modal-overlay">
        <div className="modal-content large">
          <div className="modal-header">
            <h2>Customization Requests</h2>
            <button className="modal-close" onClick={onClose}>×</button>
          </div>
          <div className="loading">Loading requests...</div>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay">
      <div className="modal-content extra-large">
        <div className="modal-header">
          <h2>Customization Requests</h2>
          <button className="modal-close" onClick={onClose}>×</button>
        </div>

        <div className="modal-body">
          {/* Filter Tabs */}
          <div className="filter-tabs">
            {['all', 'pending', 'in_progress', 'completed', 'cancelled'].map(status => (
              <button
                key={status}
                className={`filter-tab ${filter === status ? 'active' : ''}`}
                onClick={() => setFilter(status)}
              >
                {status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')}
              </button>
            ))}
          </div>

          {/* Requests List */}
          <div className="requests-list">
            {requests.length === 0 ? (
              <div className="empty-state">
                <p>No {filter === 'all' ? '' : filter} requests found</p>
              </div>
            ) : (
              requests.map(request => (
                <div key={request.id} className="request-card">
                  <div className="request-header">
                    <div className="request-info">
                      <h3>{request.title}</h3>
                      <p className="request-user">
                        <LuUser /> {request.user_name || 'Unknown User'}
                      </p>
                      <p className="request-date">
                        <LuCalendar /> {new Date(request.created_at).toLocaleDateString()}
                      </p>
                    </div>
                    <div className="request-status">
                      <span 
                        className="status-badge"
                        style={{ backgroundColor: getStatusColor(request.status) }}
                      >
                        {getStatusIcon(request.status)}
                        {request.status.charAt(0).toUpperCase() + request.status.slice(1).replace('_', ' ')}
                      </span>
                    </div>
                  </div>

                  <div className="request-details">
                    <p><strong>Description:</strong> {request.description}</p>
                    {request.occasion && <p><strong>Occasion:</strong> {request.occasion}</p>}
                    {request.budget_min && <p><strong>Budget:</strong> ₹{request.budget_min}</p>}
                    {request.deadline && <p><strong>Deadline:</strong> {new Date(request.deadline).toLocaleDateString()}</p>}
                    {request.special_instructions && <p><strong>Special Instructions:</strong> {request.special_instructions}</p>}
                  </div>

                  {request.images && request.images.length > 0 && (
                    <div className="request-images">
                      <h4>Reference Images:</h4>
                      <div className="image-grid">
                        {request.images.map((image, index) => {
                          // Extract image URL - handle both object and string formats
                          const imageUrl = typeof image === 'string' 
                            ? image 
                            : (image?.url || image?.image_url || image?.full_url || '');
                          
                          return imageUrl ? (
                            <img 
                              key={index} 
                              src={imageUrl} 
                              alt={`Reference ${index + 1}`}
                              onError={(e) => {
                                e.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik01MCA3MEM2MCAyMCA3MCA0MCA3MCA2MEM3MCA4MCA2MCAxMDAgNTAgMTAwQzQwIDEwMCAzMCA4MCAzMCA2MEMzMCA0MCA0MCAyMCA1MCAyMFoiIGZpbGw9IiNDQ0MiLz4KPGNpcmNsZSBjeD0iNTAiIGN5PSI2MCIgcj0iNy41IiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K';
                              }}
                            />
                          ) : null;
                        })}
                      </div>
                    </div>
                  )}

                  <div className="request-actions">
                    {request.status === 'pending' && (
                      <>
                        <button
                          className="btn btn-success"
                          onClick={() => updateRequestStatus(request.id, 'in_progress')}
                        >
                          <LuEye /> Start Work
                        </button>
                        <button
                          className="btn btn-primary"
                          onClick={() => updateRequestStatus(request.id, 'completed')}
                        >
                          <LuCheck /> Approve
                        </button>
                        <button
                          className="btn btn-primary"
                          onClick={() => updateRequestStatus(request.id, 'completed')}
                        >
                          Submit
                        </button>
                        <button
                          className="btn btn-danger"
                          onClick={() => updateRequestStatus(request.id, 'cancelled')}
                        >
                          <LuX /> Reject
                        </button>
                      </>
                    )}
                    {request.status === 'in_progress' && (
                      <>
                        <button
                          className="btn btn-primary"
                          onClick={() => updateRequestStatus(request.id, 'completed')}
                        >
                          <LuCheck /> Complete
                        </button>
                        <button
                          className="btn btn-primary"
                          onClick={() => updateRequestStatus(request.id, 'completed')}
                        >
                          Submit
                        </button>
                        <button
                          className="btn btn-danger"
                          onClick={() => updateRequestStatus(request.id, 'cancelled')}
                        >
                          <LuX /> Cancel
                        </button>
                      </>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      <style>{`
        .filter-tabs {
          display: flex;
          gap: 8px;
          margin-bottom: 24px;
          border-bottom: 1px solid #e5e7eb;
        }

        .filter-tab {
          padding: 8px 16px;
          border: none;
          background: none;
          cursor: pointer;
          border-bottom: 2px solid transparent;
          transition: all 0.2s;
        }

        .filter-tab.active {
          border-bottom-color: #6b46c1;
          color: #6b46c1;
          font-weight: 600;
        }

        .requests-list {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .request-card {
          border: 1px solid #e5e7eb;
          border-radius: 8px;
          padding: 20px;
          background: white;
        }

        .request-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 16px;
        }

        .request-info h3 {
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 600;
        }

        .request-user,
        .request-date {
          margin: 4px 0;
          color: #6b7280;
          font-size: 14px;
          display: flex;
          align-items: center;
          gap: 4px;
        }

        .status-badge {
          display: inline-flex;
          align-items: center;
          gap: 4px;
          padding: 4px 12px;
          border-radius: 20px;
          color: white;
          font-size: 12px;
          font-weight: 500;
        }

        .request-details {
          margin-bottom: 16px;
        }

        .request-details p {
          margin: 8px 0;
          line-height: 1.5;
        }

        .request-images {
          margin-bottom: 16px;
        }

        .request-images h4 {
          margin: 0 0 8px 0;
          font-size: 14px;
          font-weight: 600;
        }

        .image-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
          gap: 8px;
        }

        .image-grid img {
          width: 100%;
          height: 100px;
          object-fit: cover;
          border-radius: 4px;
          border: 1px solid #e5e7eb;
        }

        .request-actions {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
        }

        .btn {
          padding: 8px 16px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-size: 14px;
          font-weight: 500;
          display: flex;
          align-items: center;
          gap: 4px;
          transition: all 0.2s;
        }

        .btn-success {
          background: #10b981;
          color: white;
        }

        .btn-success:hover {
          background: #059669;
        }

        .btn-primary {
          background: #6b46c1;
          color: white;
        }

        .btn-primary:hover {
          background: #5a3a9e;
        }

        .btn-danger {
          background: #ef4444;
          color: white;
        }

        .btn-danger:hover {
          background: #dc2626;
        }

        .empty-state {
          text-align: center;
          padding: 48px;
          color: #6b7280;
        }
      `}</style>
    </div>
  );
};

export default CustomizationRequests;
