import React, { useState, useEffect } from 'react';
import './CraftValidationDashboardV3.css';

const CraftValidationDashboardV3 = () => {
  const [submissions, setSubmissions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [statistics, setStatistics] = useState({});
  const [pagination, setPagination] = useState({});
  const [filters, setFilters] = useState({
    status: 'flagged',
    category: '',
    sort_by: 'upload_date',
    sort_order: 'DESC'
  });
  const [processingDecision, setProcessingDecision] = useState(null);

  useEffect(() => {
    loadFlaggedSubmissions();
  }, [filters]);

  const loadFlaggedSubmissions = async (page = 1) => {
    setLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: '20',
        ...filters
      });

      const response = await fetch(`/backend/api/admin/craft-validation-dashboard-v3.php?${params}`);
      const result = await response.json();

      if (result.success) {
        setSubmissions(result.data);
        setStatistics(result.statistics);
        setPagination(result.pagination);
      } else {
        setError(result.message || 'Failed to load submissions');
      }
    } catch (err) {
      setError('Network error: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleAdminDecision = async (uploadId, decision, adminNotes = '') => {
    setProcessingDecision(uploadId);

    try {
      const response = await fetch('/backend/api/admin/craft-validation-decision-v3.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          upload_id: uploadId,
          decision: decision,
          admin_notes: adminNotes,
          admin_id: 1 // TODO: Get from auth context
        })
      });

      const result = await response.json();

      if (result.success) {
        // Remove the decided submission from the list
        setSubmissions(prev => prev.filter(sub => sub.upload_id !== uploadId));
        
        // Update statistics
        setStatistics(prev => ({
          ...prev,
          pending_review: Math.max(0, prev.pending_review - 1),
          [decision === 'approved' ? 'approved_after_review' : 'rejected_after_review']: 
            (prev[decision === 'approved' ? 'approved_after_review' : 'rejected_after_review'] || 0) + 1
        }));

        alert(`Submission ${decision} successfully!`);
      } else {
        alert('Failed to process decision: ' + result.message);
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    } finally {
      setProcessingDecision(null);
    }
  };

  const getConfidenceColor = (confidence) => {
    if (confidence >= 0.7) return '#28a745';
    if (confidence >= 0.4) return '#ffc107';
    return '#dc3545';
  };

  const getConfidenceLabel = (confidence) => {
    if (confidence >= 0.7) return 'High';
    if (confidence >= 0.4) return 'Medium';
    if (confidence >= 0.1) return 'Low';
    return 'Very Low';
  };

  if (loading && submissions.length === 0) {
    return (
      <div className="craft-dashboard-v3">
        <div className="loading">
          <div className="spinner"></div>
          <p>Loading AI-flagged submissions...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="craft-dashboard-v3">
      <div className="dashboard-header">
        <h2>AI Craft Validation Dashboard V3</h2>
        <p className="subtitle">Human-in-the-Loop Review for AI-Flagged Submissions</p>
        
        <div className="system-info">
          <div className="info-badge">
            <span className="icon">🤖</span>
            <span>Trained Model Only</span>
          </div>
          <div className="info-badge">
            <span className="icon">⚡</span>
            <span>Auto-Approved Bypass</span>
          </div>
          <div className="info-badge">
            <span className="icon">🎯</span>
            <span>Flagged Only</span>
          </div>
        </div>
      </div>

      {statistics && (
        <div className="statistics-panel">
          <h3>Review Statistics</h3>
          <div className="stats-grid">
            <div className="stat-card">
              <div className="stat-number">{statistics.total_flagged || 0}</div>
              <div className="stat-label">Total Flagged</div>
            </div>
            <div className="stat-card pending">
              <div className="stat-number">{statistics.pending_review || 0}</div>
              <div className="stat-label">Pending Review</div>
            </div>
            <div className="stat-card approved">
              <div className="stat-number">{statistics.approved_after_review || 0}</div>
              <div className="stat-label">Approved After Review</div>
            </div>
            <div className="stat-card rejected">
              <div className="stat-number">{statistics.rejected_after_review || 0}</div>
              <div className="stat-label">Rejected After Review</div>
            </div>
            <div className="stat-card">
              <div className="stat-number">{statistics.avg_confidence ? (statistics.avg_confidence * 100).toFixed(1) + '%' : 'N/A'}</div>
              <div className="stat-label">Avg AI Confidence</div>
            </div>
          </div>
        </div>
      )}

      <div className="filters-panel">
        <div className="filter-group">
          <label>Status:</label>
          <select 
            value={filters.status} 
            onChange={(e) => setFilters(prev => ({...prev, status: e.target.value}))}
          >
            <option value="flagged">Flagged Only</option>
            <option value="all">All Requiring Review</option>
          </select>
        </div>

        <div className="filter-group">
          <label>Sort By:</label>
          <select 
            value={filters.sort_by} 
            onChange={(e) => setFilters(prev => ({...prev, sort_by: e.target.value}))}
          >
            <option value="upload_date">Upload Date</option>
            <option value="prediction_confidence">AI Confidence</option>
            <option value="user_id">User</option>
          </select>
        </div>

        <div className="filter-group">
          <label>Order:</label>
          <select 
            value={filters.sort_order} 
            onChange={(e) => setFilters(prev => ({...prev, sort_order: e.target.value}))}
          >
            <option value="DESC">Newest First</option>
            <option value="ASC">Oldest First</option>
          </select>
        </div>
      </div>

      {error && (
        <div className="error-message">
          <p>Error: {error}</p>
          <button onClick={() => loadFlaggedSubmissions()}>Retry</button>
        </div>
      )}

      {submissions.length === 0 && !loading ? (
        <div className="no-submissions">
          <div className="empty-state">
            <span className="icon">🎉</span>
            <h3>No Flagged Submissions!</h3>
            <p>All submissions are being handled automatically by the AI system.</p>
            <div className="empty-stats">
              <p>✅ Auto-approved submissions bypass this dashboard</p>
              <p>❌ Auto-rejected submissions don't appear here</p>
              <p>⏳ Only ambiguous cases need human review</p>
            </div>
          </div>
        </div>
      ) : (
        <div className="submissions-list">
          {submissions.map((submission) => (
            <div key={submission.upload_id} className="submission-card">
              <div className="submission-header">
                <div className="submission-info">
                  <h4>Upload #{submission.upload_id}</h4>
                  <p className="user-info">
                    <strong>{submission.user_info.name || submission.user_info.email}</strong>
                    <span className="email">({submission.user_info.email})</span>
                  </p>
                  <p className="tutorial-info">
                    <strong>Tutorial:</strong> {submission.tutorial_info.title}
                    <span className="category">({submission.tutorial_info.category})</span>
                  </p>
                  <p className="upload-date">
                    <strong>Uploaded:</strong> {new Date(submission.submission_info.upload_date).toLocaleString()}
                  </p>
                </div>

                <div className="flag-reason">
                  <div className="flag-badge">
                    <span className="icon">🚩</span>
                    <span>Flagged for Review</span>
                  </div>
                  <p className="reason">{submission.flag_reason}</p>
                </div>
              </div>

              <div className="ai-evidence">
                <h5>AI Evidence</h5>
                <div className="evidence-grid">
                  <div className="evidence-item">
                    <strong>AI Prediction:</strong>
                    <span>{submission.ai_evidence.predicted_category || 'Unknown'}</span>
                  </div>
                  <div className="evidence-item">
                    <strong>Confidence:</strong>
                    <span style={{ color: getConfidenceColor(submission.ai_evidence.prediction_confidence) }}>
                      {(submission.ai_evidence.prediction_confidence * 100).toFixed(1)}% 
                      ({getConfidenceLabel(submission.ai_evidence.prediction_confidence)})
                    </span>
                  </div>
                  <div className="evidence-item">
                    <strong>Category Match:</strong>
                    <span style={{ color: submission.ai_evidence.category_matches ? '#28a745' : '#dc3545' }}>
                      {submission.ai_evidence.category_matches ? 'Yes' : 'No'}
                    </span>
                  </div>
                  <div className="evidence-item">
                    <strong>AI Decision:</strong>
                    <span className="ai-decision">{submission.ai_evidence.ai_decision}</span>
                  </div>
                </div>

                {submission.ai_evidence.explanation && (
                  <div className="ai-explanation">
                    <strong>AI Explanation:</strong>
                    <p>{submission.ai_evidence.explanation}</p>
                  </div>
                )}

                {submission.ai_evidence.all_predictions && submission.ai_evidence.all_predictions.length > 0 && (
                  <div className="all-predictions">
                    <strong>All AI Predictions:</strong>
                    <div className="predictions-list">
                      {submission.ai_evidence.all_predictions.slice(0, 3).map((pred, idx) => (
                        <div key={idx} className="prediction-item">
                          <span className="category">{pred.name || pred.category}</span>
                          <span className="confidence" style={{ color: getConfidenceColor(pred.confidence) }}>
                            {(pred.confidence * 100).toFixed(1)}%
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              <div className="images-preview">
                <h5>Images ({submission.images.length})</h5>
                <div className="images-grid">
                  {submission.images.map((image, idx) => (
                    <div key={idx} className="image-item">
                      <div className="image-placeholder">
                        <span className="icon">🖼️</span>
                        <p>{image.original_name}</p>
                        <small>{(image.file_size / 1024 / 1024).toFixed(2)} MB</small>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {submission.submission_info.description && (
                <div className="description">
                  <strong>User Description:</strong>
                  <p>{submission.submission_info.description}</p>
                </div>
              )}

              <div className="admin-actions">
                <h5>Admin Decision</h5>
                <div className="decision-buttons">
                  <button
                    className="approve-btn"
                    onClick={() => {
                      const notes = prompt('Admin notes (optional):');
                      if (notes !== null) {
                        handleAdminDecision(submission.upload_id, 'approved', notes);
                      }
                    }}
                    disabled={processingDecision === submission.upload_id}
                  >
                    {processingDecision === submission.upload_id ? 'Processing...' : '✅ Approve'}
                  </button>
                  
                  <button
                    className="reject-btn"
                    onClick={() => {
                      const notes = prompt('Rejection reason (required):');
                      if (notes && notes.trim()) {
                        handleAdminDecision(submission.upload_id, 'rejected', notes);
                      } else if (notes !== null) {
                        alert('Rejection reason is required');
                      }
                    }}
                    disabled={processingDecision === submission.upload_id}
                  >
                    {processingDecision === submission.upload_id ? 'Processing...' : '❌ Reject'}
                  </button>
                </div>
                
                <div className="decision-guidance">
                  <p><strong>Decision Guidance:</strong></p>
                  <ul>
                    <li>Consider AI confidence and category match</li>
                    <li>Verify image actually shows craft work</li>
                    <li>Check if image matches tutorial category</li>
                    <li>Look for obvious non-craft content (selfies, nature, etc.)</li>
                  </ul>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {pagination && pagination.total_pages > 1 && (
        <div className="pagination">
          <button 
            onClick={() => loadFlaggedSubmissions(pagination.page - 1)}
            disabled={!pagination.has_prev || loading}
          >
            Previous
          </button>
          
          <span className="page-info">
            Page {pagination.page} of {pagination.total_pages} 
            ({pagination.total} total submissions)
          </span>
          
          <button 
            onClick={() => loadFlaggedSubmissions(pagination.page + 1)}
            disabled={!pagination.has_next || loading}
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
};

export default CraftValidationDashboardV3;