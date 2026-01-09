import React, { useState, useEffect } from 'react';
import { LuDownload, LuImage, LuFileText, LuCheckCircle, LuClock, LuX } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function CustomRequestDesignView({ requestId, isOpen, onClose }) {
  const { auth } = useAuth();
  const [designs, setDesigns] = useState([]);
  const [request, setRequest] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedDesign, setSelectedDesign] = useState(null);

  useEffect(() => {
    if (isOpen && requestId) {
      loadDesignData();
    }
  }, [isOpen, requestId]);

  const loadDesignData = async () => {
    setLoading(true);
    try {
      // Load designs for customer (with user authentication)
      const designRes = await fetch(`${API_BASE}/admin/get-design.php?request_id=${requestId}`, {
        headers: {
          'X-User-ID': auth?.user_id || ''
        }
      });
      const designData = await designRes.json();
      
      if (designData.status === 'success') {
        // Get all designs or latest
        if (designData.designs && Array.isArray(designData.designs)) {
          setDesigns(designData.designs);
          if (designData.designs.length > 0) {
            setSelectedDesign(designData.designs[0]); // Latest is first
          }
        } else if (designData.latest) {
          setDesigns([designData.latest]);
          setSelectedDesign(designData.latest);
        } else if (designData.design) {
          setDesigns([designData.design]);
          setSelectedDesign(designData.design);
        }
        
        // Load request details if provided
        if (designData.request) {
          setRequest(designData.request);
        }
      }
    } catch (err) {
      console.error('Error loading design data:', err);
      alert('Error loading design data: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      'draft': { text: 'Draft', color: '#666', bg: '#f0f0f0' },
      'designing': { text: 'Designing', color: '#2196f3', bg: '#e3f2fd' },
      'design_completed': { text: 'Design Completed', color: '#4caf50', bg: '#e8f5e9' },
      'approved': { text: 'Approved', color: '#4caf50', bg: '#e8f5e9' },
      'rejected': { text: 'Rejected', color: '#f44336', bg: '#ffebee' }
    };
    const badge = badges[status] || badges['draft'];
    return (
      <span style={{
        padding: '4px 12px',
        borderRadius: 12,
        fontSize: 12,
        fontWeight: 'bold',
        color: badge.color,
        background: badge.bg,
        display: 'inline-block'
      }}>
        {badge.text}
      </span>
    );
  };

  const downloadDesign = (design) => {
    if (design.design_image_url) {
      const link = document.createElement('a');
      link.href = design.design_image_url;
      link.download = `design_request_${requestId}_v${design.version}.png`;
      link.click();
    } else {
      alert('No design image available');
    }
  };

  const downloadPDF = (design) => {
    if (design.design_pdf_url) {
      const link = document.createElement('a');
      link.href = design.design_pdf_url;
      link.download = `design_request_${requestId}_v${design.version}.pdf`;
      link.click();
    } else {
      alert('No PDF available');
    }
  };

  if (!isOpen) return null;

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      background: 'rgba(0,0,0,0.8)',
      zIndex: 10000,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: 20
    }}>
      <div style={{
        background: 'white',
        borderRadius: 8,
        maxWidth: 1200,
        maxHeight: '90%',
        width: '100%',
        display: 'flex',
        flexDirection: 'column',
        overflow: 'hidden'
      }}>
        {/* Header */}
        <div style={{
          padding: '20px',
          borderBottom: '1px solid #eee',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center'
        }}>
          <div>
            <h3 style={{ margin: 0, marginBottom: 8 }}>Design Progress</h3>
            {request && (
              <div style={{ fontSize: 14, color: '#666' }}>
                Request: {request.title || `#${requestId}`}
                {request.status && (
                  <span style={{ marginLeft: 12 }}>
                    Status: <strong style={{ textTransform: 'capitalize' }}>{request.status}</strong>
                  </span>
                )}
              </div>
            )}
          </div>
          <button
            onClick={onClose}
            style={{
              border: 'none',
              background: 'none',
              cursor: 'pointer',
              fontSize: 24,
              padding: 4
            }}
          >
            <LuX />
          </button>
        </div>

        {loading ? (
          <div style={{
            flex: 1,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 18,
            color: '#666'
          }}>
            Loading design data...
          </div>
        ) : (
          <div style={{ flex: 1, display: 'flex', overflow: 'hidden' }}>
            {/* Sidebar - Design Versions */}
            <div style={{
              width: 300,
              borderRight: '1px solid #eee',
              overflow: 'auto',
              background: '#f9f9f9'
            }}>
              <div style={{ padding: 16 }}>
                <h4 style={{ margin: '0 0 16px 0', fontSize: 16 }}>Design Versions</h4>
                {designs.length === 0 ? (
                  <div style={{ color: '#999', fontSize: 14, textAlign: 'center', padding: 20 }}>
                    No designs yet. Design is in progress.
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    {designs.map((design, index) => (
                      <div
                        key={design.id}
                        onClick={() => setSelectedDesign(design)}
                        style={{
                          padding: 12,
                          borderRadius: 8,
                          background: selectedDesign?.id === design.id ? '#e3f2fd' : 'white',
                          border: selectedDesign?.id === design.id ? '2px solid #2196f3' : '1px solid #ddd',
                          cursor: 'pointer',
                          transition: 'all 0.2s'
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                          <strong style={{ fontSize: 14 }}>Version {design.version}</strong>
                          {getStatusBadge(design.status)}
                        </div>
                        {design.design_image_url && (
                          <img
                            src={design.design_image_url}
                            alt={`Design v${design.version}`}
                            style={{
                              width: '100%',
                              height: 80,
                              objectFit: 'cover',
                              borderRadius: 4,
                              marginTop: 8
                            }}
                          />
                        )}
                        <div style={{ fontSize: 12, color: '#666', marginTop: 8 }}>
                          {new Date(design.created_at).toLocaleDateString()}
                        </div>
                        {index === 0 && (
                          <div style={{
                            marginTop: 8,
                            padding: 4,
                            background: '#4caf50',
                            color: 'white',
                            fontSize: 11,
                            borderRadius: 4,
                            textAlign: 'center'
                          }}>
                            Latest
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>

            {/* Main Content - Selected Design */}
            <div style={{ flex: 1, overflow: 'auto', padding: 24, display: 'flex', flexDirection: 'column' }}>
              {!selectedDesign ? (
                <div style={{
                  flex: 1,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#999',
                  fontSize: 16
                }}>
                  {designs.length === 0
                    ? 'No designs available yet. Your design is being worked on!'
                    : 'Select a design version to view'}
                </div>
              ) : (
                <>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
                    <div>
                      <h3 style={{ margin: 0 }}>Version {selectedDesign.version}</h3>
                      <div style={{ marginTop: 8, display: 'flex', gap: 12, alignItems: 'center' }}>
                        {getStatusBadge(selectedDesign.status)}
                        <span style={{ fontSize: 14, color: '#666' }}>
                          Created: {new Date(selectedDesign.created_at).toLocaleString()}
                        </span>
                      </div>
                    </div>
                    <div style={{ display: 'flex', gap: 8 }}>
                      {selectedDesign.design_image_url && (
                        <button
                          onClick={() => downloadDesign(selectedDesign)}
                          style={{
                            padding: '8px 16px',
                            border: '1px solid #ddd',
                            borderRadius: 4,
                            background: 'white',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: 6,
                            fontSize: 14
                          }}
                        >
                          <LuDownload /> Download Image
                        </button>
                      )}
                      {selectedDesign.design_pdf_url && (
                        <button
                          onClick={() => downloadPDF(selectedDesign)}
                          style={{
                            padding: '8px 16px',
                            border: '1px solid #ddd',
                            borderRadius: 4,
                            background: 'white',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: 6,
                            fontSize: 14
                          }}
                        >
                          <LuFileText /> Download PDF
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Design Preview */}
                  {selectedDesign.design_image_url ? (
                    <div style={{
                      flex: 1,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      background: '#f5f5f5',
                      borderRadius: 8,
                      padding: 20,
                      minHeight: 400
                    }}>
                      <img
                        src={selectedDesign.design_image_url}
                        alt={`Design version ${selectedDesign.version}`}
                        style={{
                          maxWidth: '100%',
                          maxHeight: '100%',
                          objectFit: 'contain',
                          borderRadius: 4,
                          boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                        }}
                      />
                    </div>
                  ) : (
                    <div style={{
                      flex: 1,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      background: '#f5f5f5',
                      borderRadius: 8,
                      color: '#999',
                      fontSize: 16
                    }}>
                      Design preview not available yet
                    </div>
                  )}

                  {/* Design Info */}
                  {(selectedDesign.admin_notes || selectedDesign.customer_feedback) && (
                    <div style={{
                      marginTop: 20,
                      padding: 16,
                      background: '#f9f9f9',
                      borderRadius: 8
                    }}>
                      {selectedDesign.admin_notes && (
                        <div style={{ marginBottom: 12 }}>
                          <strong style={{ fontSize: 14, display: 'block', marginBottom: 4 }}>Admin Notes:</strong>
                          <div style={{ fontSize: 14, color: '#666' }}>{selectedDesign.admin_notes}</div>
                        </div>
                      )}
                      {selectedDesign.customer_feedback && (
                        <div>
                          <strong style={{ fontSize: 14, display: 'block', marginBottom: 4 }}>Your Feedback:</strong>
                          <div style={{ fontSize: 14, color: '#666' }}>{selectedDesign.customer_feedback}</div>
                        </div>
                      )}
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

