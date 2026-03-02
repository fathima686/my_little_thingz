import React, { useState, useRef } from 'react';
import './PracticeUploadV3.css';

const PracticeUploadV3 = ({ tutorialId, tutorialTitle, tutorialCategory, userEmail }) => {
  const [files, setFiles] = useState([]);
  const [description, setDescription] = useState('');
  const [uploading, setUploading] = useState(false);
  const [uploadResult, setUploadResult] = useState(null);
  const [validationResults, setValidationResults] = useState([]);
  const fileInputRef = useRef(null);

  const handleFileSelect = (event) => {
    const selectedFiles = Array.from(event.target.files);
    setFiles(selectedFiles);
    setUploadResult(null);
    setValidationResults([]);
  };

  const handleUpload = async () => {
    if (files.length === 0) {
      alert('Please select at least one image');
      return;
    }

    setUploading(true);
    setUploadResult(null);
    setValidationResults([]);

    try {
      const formData = new FormData();
      formData.append('email', userEmail);
      formData.append('tutorial_id', tutorialId);
      formData.append('description', description);

      files.forEach((file, index) => {
        formData.append('practice_images[]', file);
      });

      const response = await fetch('/backend/api/pro/practice-upload-v3.php', {
        method: 'POST',
        headers: {
          'X-Tutorial-Email': userEmail
        },
        body: formData
      });

      const result = await response.json();
      setUploadResult(result);

      if (result.status === 'success') {
        setValidationResults(result.validation_results || []);
        
        // Clear form on successful upload
        setFiles([]);
        setDescription('');
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      }

    } catch (error) {
      console.error('Upload error:', error);
      setUploadResult({
        status: 'error',
        message: 'Upload failed: ' + error.message
      });
    } finally {
      setUploading(false);
    }
  };

  const getStatusIcon = (aiDecision) => {
    switch (aiDecision) {
      case 'auto-approve':
        return '✅';
      case 'auto-reject':
        return '❌';
      case 'flag-for-review':
        return '⏳';
      default:
        return '❓';
    }
  };

  const getStatusColor = (aiDecision) => {
    switch (aiDecision) {
      case 'auto-approve':
        return '#28a745';
      case 'auto-reject':
        return '#dc3545';
      case 'flag-for-review':
        return '#ffc107';
      default:
        return '#6c757d';
    }
  };

  const getStatusText = (aiDecision) => {
    switch (aiDecision) {
      case 'auto-approve':
        return 'Auto-Approved';
      case 'auto-reject':
        return 'Auto-Rejected';
      case 'flag-for-review':
        return 'Sent for Review';
      default:
        return 'Unknown Status';
    }
  };

  const getConfidenceColor = (confidence) => {
    if (confidence >= 0.7) return '#28a745';
    if (confidence >= 0.4) return '#ffc107';
    return '#dc3545';
  };

  return (
    <div className="practice-upload-v3">
      <div className="upload-header">
        <h3>Practice Upload - AI Validation System</h3>
        <div className="tutorial-info">
          <p><strong>Tutorial:</strong> {tutorialTitle}</p>
          <p><strong>Category:</strong> {tutorialCategory}</p>
        </div>
      </div>

      <div className="upload-form">
        <div className="form-group">
          <label htmlFor="practice-images">Select Practice Images:</label>
          <input
            ref={fileInputRef}
            type="file"
            id="practice-images"
            multiple
            accept="image/*"
            onChange={handleFileSelect}
            disabled={uploading}
          />
          {files.length > 0 && (
            <div className="file-preview">
              <p>{files.length} file(s) selected:</p>
              <ul>
                {files.map((file, index) => (
                  <li key={index}>{file.name} ({(file.size / 1024 / 1024).toFixed(2)} MB)</li>
                ))}
              </ul>
            </div>
          )}
        </div>

        <div className="form-group">
          <label htmlFor="description">Description (optional):</label>
          <textarea
            id="description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Describe your practice work..."
            disabled={uploading}
          />
        </div>

        <button
          className="upload-btn"
          onClick={handleUpload}
          disabled={uploading || files.length === 0}
        >
          {uploading ? 'Processing with AI...' : 'Upload & Validate'}
        </button>
      </div>

      {uploading && (
        <div className="upload-progress">
          <div className="progress-bar">
            <div className="progress-fill"></div>
          </div>
          <p>Uploading and running AI validation...</p>
          <small>Using trained craft_image_classifier.keras model</small>
        </div>
      )}

      {uploadResult && (
        <div className={`upload-result ${uploadResult.status}`}>
          <h4>Upload Result</h4>
          <p>{uploadResult.message}</p>

          {uploadResult.status === 'success' && uploadResult.ai_validation_summary && (
            <div className="ai-summary">
              <h5>AI Validation Summary</h5>
              <div className="summary-stats">
                <div className="stat">
                  <span className="stat-label">Total Images:</span>
                  <span className="stat-value">{uploadResult.ai_validation_summary.total_images}</span>
                </div>
                <div className="stat">
                  <span className="stat-label">Auto-Approved:</span>
                  <span className="stat-value" style={{color: '#28a745'}}>
                    {uploadResult.ai_validation_summary.auto_approved}
                  </span>
                </div>
                <div className="stat">
                  <span className="stat-label">Auto-Rejected:</span>
                  <span className="stat-value" style={{color: '#dc3545'}}>
                    {uploadResult.ai_validation_summary.auto_rejected}
                  </span>
                </div>
                <div className="stat">
                  <span className="stat-label">Flagged for Review:</span>
                  <span className="stat-value" style={{color: '#ffc107'}}>
                    {uploadResult.ai_validation_summary.flagged_for_review}
                  </span>
                </div>
              </div>
            </div>
          )}

          {validationResults.length > 0 && (
            <div className="validation-results">
              <h5>Individual Image Results</h5>
              {validationResults.map((result, index) => (
                <div key={index} className="image-result">
                  <div className="result-header">
                    <span className="file-name">{result.file_name}</span>
                    <span 
                      className="status-badge"
                      style={{ 
                        backgroundColor: getStatusColor(result.ai_decision),
                        color: 'white',
                        padding: '4px 8px',
                        borderRadius: '4px',
                        fontSize: '12px'
                      }}
                    >
                      {getStatusIcon(result.ai_decision)} {getStatusText(result.ai_decision)}
                    </span>
                  </div>

                  <div className="ai-analysis">
                    <div className="prediction-info">
                      <p><strong>AI Prediction:</strong> {result.predicted_category || 'Unknown'}</p>
                      <p>
                        <strong>Confidence:</strong> 
                        <span style={{ color: getConfidenceColor(result.confidence) }}>
                          {result.confidence ? (result.confidence * 100).toFixed(1) + '%' : 'N/A'}
                        </span>
                      </p>
                      <p>
                        <strong>Category Match:</strong> 
                        <span style={{ color: result.category_match ? '#28a745' : '#dc3545' }}>
                          {result.category_match ? 'Yes' : 'No'}
                        </span>
                      </p>
                    </div>

                    {result.decision_reasons && result.decision_reasons.length > 0 && (
                      <div className="decision-reasons">
                        <strong>AI Decision Reasons:</strong>
                        <ul>
                          {result.decision_reasons.map((reason, idx) => (
                            <li key={idx}>{reason}</li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {result.explanation && (
                      <div className="explanation">
                        <strong>Explanation:</strong>
                        <p>{result.explanation}</p>
                      </div>
                    )}
                  </div>

                  {result.ai_decision === 'auto-approve' && (
                    <div className="success-message">
                      ✅ This image was automatically approved! No admin review needed.
                    </div>
                  )}

                  {result.ai_decision === 'auto-reject' && (
                    <div className="error-message">
                      ❌ This image was automatically rejected. Please upload a different image that matches the tutorial category.
                    </div>
                  )}

                  {result.ai_decision === 'flag-for-review' && (
                    <div className="warning-message">
                      ⏳ This image has been sent for admin review. You will be notified of the decision.
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}

          {uploadResult.system_info && (
            <div className="system-info">
              <details>
                <summary>Technical Details</summary>
                <div className="tech-details">
                  <p><strong>Version:</strong> {uploadResult.system_info.version}</p>
                  <p><strong>AI Model:</strong> {uploadResult.system_info.ai_model}</p>
                  <p><strong>Validation Mode:</strong> {uploadResult.system_info.validation_mode}</p>
                  <p><strong>Fallback Disabled:</strong> {uploadResult.system_info.fallback_disabled ? 'Yes' : 'No'}</p>
                </div>
              </details>
            </div>
          )}
        </div>
      )}

      <div className="ai-info">
        <h5>AI Validation System</h5>
        <div className="info-grid">
          <div className="info-item">
            <span className="icon">🤖</span>
            <div>
              <strong>Trained Model</strong>
              <p>Uses craft_image_classifier.keras trained on 7 craft categories</p>
            </div>
          </div>
          <div className="info-item">
            <span className="icon">⚡</span>
            <div>
              <strong>Instant Decisions</strong>
              <p>Auto-approve, auto-reject, or flag for human review</p>
            </div>
          </div>
          <div className="info-item">
            <span className="icon">🎯</span>
            <div>
              <strong>Category Matching</strong>
              <p>Verifies images match the selected tutorial category</p>
            </div>
          </div>
          <div className="info-item">
            <span className="icon">📊</span>
            <div>
              <strong>Explainable AI</strong>
              <p>Provides confidence scores and reasoning for all decisions</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PracticeUploadV3;