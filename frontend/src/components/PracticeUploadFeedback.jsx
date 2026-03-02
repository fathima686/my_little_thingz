import React, { useState, useEffect } from 'react';
import { 
  LuCheck, 
  LuX, 
  LuClock, 
  LuInfo,
  LuRefreshCw,
  LuFileText,
  LuTrendingUp,
  LuChevronDown,
  LuChevronUp
} from 'react-icons/lu';
import './PracticeUploadFeedback.css';

const PracticeUploadFeedback = ({ userEmail, tutorialId = null }) => {
  const [feedbackData, setFeedbackData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [expandedUpload, setExpandedUpload] = useState(null);
  const [showGuidelines, setShowGuidelines] = useState(false);

  const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost/my_little_thingz/backend/api';

  useEffect(() => {
    fetchFeedback();
  }, [userEmail, tutorialId]);

  const fetchFeedback = async () => {
    if (!userEmail) return;

    setLoading(true);
    setError(null);

    try {
      const url = new URL(`${API_BASE}/pro/practice-upload-feedback.php`);
      url.searchParams.append('email', userEmail);
      if (tutorialId) {
        url.searchParams.append('tutorial_id', tutorialId);
      }

      const response = await fetch(url, {
        headers: {
          'X-Tutorial-Email': userEmail
        }
      });

      const data = await response.json();

      if (data.status === 'success') {
        setFeedbackData(data.uploads || []);
      } else {
        setError(data.message || 'Failed to load feedback');
      }
    } catch (err) {
      setError('Network error: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'approved':
        return <LuCheck className="status-icon status-icon-approved" />;
      case 'rejected':
        return <LuX className="status-icon status-icon-rejected" />;
      case 'under_review':
        return <LuClock className="status-icon status-icon-review" />;
      default:
        return <LuClock className="status-icon status-icon-pending" />;
    }
  };

  const getStatusBadge = (upload) => {
    const status = upload.overall_status;
    return (
      <div className={`status-badge status-badge-${upload.status_color}`}>
        {getStatusIcon(status)}
        <span>{upload.status_label}</span>
      </div>
    );
  };

  const toggleExpand = (uploadId) => {
    setExpandedUpload(expandedUpload === uploadId ? null : uploadId);
  };

  const renderAIValidation = (upload) => {
    const { ai_validation, ai_detection } = upload;

    return (
      <div className="ai-validation-section">
        <h4 className="section-title">
          <LuTrendingUp /> AI Validation Results
        </h4>

        {/* Craft Classification */}
        <div className="validation-card">
          <div className="validation-header">
            <span className="validation-label">Craft Category Classification</span>
            {ai_validation.category_matches ? (
              <span className="badge badge-success">Match</span>
            ) : (
              <span className="badge badge-error">Mismatch</span>
            )}
          </div>
          <div className="validation-details">
            <div className="detail-row">
              <span className="detail-label">Predicted Category:</span>
              <span className="detail-value">{ai_validation.predicted_category || 'N/A'}</span>
            </div>
            <div className="detail-row">
              <span className="detail-label">Confidence:</span>
              <span className="detail-value">
                {ai_validation.confidence ? `${ai_validation.confidence}%` : 'N/A'}
              </span>
            </div>
            <div className="detail-row">
              <span className="detail-label">Tutorial Category:</span>
              <span className="detail-value">{upload.tutorial_category}</span>
            </div>
          </div>
        </div>

        {/* AI Detection (if available) */}
        {ai_detection && (
          <div className="validation-card">
            <div className="validation-header">
              <span className="validation-label">AI-Generated Image Detection</span>
              <span className={`badge badge-${ai_detection.risk_level}`}>
                Risk: {ai_detection.risk_score}/100
              </span>
            </div>
            <div className="validation-details">
              <div className="detail-row">
                <span className="detail-label">Risk Level:</span>
                <span className="detail-value risk-level-{ai_detection.risk_level}">
                  {ai_detection.risk_level.toUpperCase()}
                </span>
              </div>
              <div className="detail-row">
                <span className="detail-label">Decision:</span>
                <span className="detail-value">{ai_detection.decision}</span>
              </div>

              {/* Detection Evidence */}
              <div className="evidence-grid">
                <div className="evidence-item">
                  <span className="evidence-label">Metadata Keywords:</span>
                  <span className={`evidence-value ${ai_detection.metadata_keywords_found ? 'text-warning' : 'text-success'}`}>
                    {ai_detection.metadata_keywords_found ? 'Found' : 'None'}
                  </span>
                </div>
                <div className="evidence-item">
                  <span className="evidence-label">Camera EXIF:</span>
                  <span className={`evidence-value ${ai_detection.exif_camera_present ? 'text-success' : 'text-warning'}`}>
                    {ai_detection.exif_camera_present === null ? 'N/A' : (ai_detection.exif_camera_present ? 'Present' : 'Missing')}
                  </span>
                </div>
                <div className="evidence-item">
                  <span className="evidence-label">Texture Variance:</span>
                  <span className="evidence-value">
                    {ai_detection.texture_variance ? ai_detection.texture_variance.toFixed(2) : 'N/A'}
                  </span>
                </div>
                <div className="evidence-item">
                  <span className="evidence-label">Watermark:</span>
                  <span className={`evidence-value ${ai_detection.watermark_detected ? 'text-warning' : 'text-success'}`}>
                    {ai_detection.watermark_detected ? 'Detected' : 'None'}
                  </span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Decision Reasons */}
        {ai_validation.reasons && ai_validation.reasons.length > 0 && (
          <div className="validation-card">
            <div className="validation-header">
              <span className="validation-label">Decision Reasoning</span>
            </div>
            <ul className="reasons-list">
              {ai_validation.reasons.map((reason, index) => (
                <li key={index} className="reason-item">{reason}</li>
              ))}
            </ul>
          </div>
        )}
      </div>
    );
  };

  const renderFeedbackMessages = (upload) => {
    const { feedback } = upload;

    return (
      <div className="feedback-messages">
        <div className={`primary-message message-${upload.status_color}`}>
          {getStatusIcon(upload.overall_status)}
          <span>{feedback.primary}</span>
        </div>

        {feedback.details && feedback.details.length > 0 && (
          <div className="details-list">
            {feedback.details.map((detail, index) => (
              <div key={index} className="detail-message">{detail}</div>
            ))}
          </div>
        )}

        {feedback.ai_explanation && (
          <div className="ai-explanation">
            <LuInfo className="info-icon" />
            <div>
              <strong>AI Explanation:</strong>
              <p>{feedback.ai_explanation}</p>
            </div>
          </div>
        )}

        {feedback.next_steps && (
          <div className="next-steps">
            <strong>Next Steps:</strong>
            <p>{feedback.next_steps}</p>
          </div>
        )}

        {upload.admin_feedback && (
          <div className="admin-feedback">
            <strong>Admin Feedback:</strong>
            <p>{upload.admin_feedback}</p>
          </div>
        )}
      </div>
    );
  };

  const renderActionItems = (upload) => {
    if (!upload.action_items || upload.action_items.length === 0) return null;

    return (
      <div className="action-items">
        <h4 className="section-title">Recommended Actions</h4>
        <div className="actions-grid">
          {upload.action_items.map((action, index) => (
            <button
              key={index}
              className={`action-button action-${action.type}`}
              onClick={() => handleAction(action.action, upload)}
            >
              <span className="action-label">{action.label}</span>
              <span className="action-description">{action.description}</span>
            </button>
          ))}
        </div>
      </div>
    );
  };

  const handleAction = (action, upload) => {
    switch (action) {
      case 'reupload':
        // Navigate to tutorial page for re-upload
        window.location.href = `/tutorial/${upload.tutorial_id}`;
        break;
      case 'view_guidelines':
        // Show guidelines modal
        setShowGuidelines(true);
        break;
      case 'continue':
        // Navigate to next tutorial or dashboard
        window.location.href = '/dashboard';
        break;
      default:
        break;
    }
  };

  const renderGuidelinesModal = () => {
    if (!showGuidelines) return null;

    return (
      <div className="guidelines-modal-overlay" onClick={() => setShowGuidelines(false)}>
        <div className="guidelines-modal" onClick={(e) => e.stopPropagation()}>
          <div className="guidelines-header">
            <h3>Practice Upload Guidelines</h3>
            <button className="close-button" onClick={() => setShowGuidelines(false)}>
              <LuX />
            </button>
          </div>
          
          <div className="guidelines-content">
            <section className="guideline-section">
              <h4><LuCheck className="section-icon success" /> What to Upload</h4>
              <ul>
                <li><strong>Your Own Craft Work:</strong> Upload photos of crafts you personally created following the tutorial</li>
                <li><strong>Real Photographs:</strong> Use actual photos taken with a camera or smartphone</li>
                <li><strong>Clear & Well-Lit:</strong> Ensure good lighting and focus so details are visible</li>
                <li><strong>Multiple Angles:</strong> Show different perspectives of your work (optional but recommended)</li>
                <li><strong>Match Tutorial Category:</strong> Your craft should match the tutorial's category (e.g., Origami for origami tutorials)</li>
              </ul>
            </section>

            <section className="guideline-section">
              <h4><LuX className="section-icon error" /> What NOT to Upload</h4>
              <ul>
                <li><strong>AI-Generated Images:</strong> No images created by AI tools (Midjourney, DALL-E, Stable Diffusion, etc.)</li>
                <li><strong>Downloaded Images:</strong> Don't upload images from Google, Pinterest, or other websites</li>
                <li><strong>Wrong Category:</strong> Don't upload crafts that don't match the tutorial category</li>
                <li><strong>Blurry or Dark Photos:</strong> Avoid poor quality images where details aren't visible</li>
                <li><strong>Duplicate Submissions:</strong> Don't resubmit the same images multiple times</li>
              </ul>
            </section>

            <section className="guideline-section">
              <h4><LuInfo className="section-icon info" /> Technical Requirements</h4>
              <ul>
                <li><strong>File Format:</strong> JPG, JPEG, PNG, or WebP</li>
                <li><strong>File Size:</strong> Maximum 10MB per image</li>
                <li><strong>Quantity:</strong> Upload 1-5 images per submission</li>
                <li><strong>Resolution:</strong> Minimum 800x600 pixels recommended</li>
              </ul>
            </section>

            <section className="guideline-section">
              <h4><LuTrendingUp className="section-icon info" /> AI Validation Process</h4>
              <p>Your submissions are automatically validated using AI technology that checks:</p>
              <ul>
                <li><strong>Craft Category:</strong> Does your work match the tutorial category?</li>
                <li><strong>Image Authenticity:</strong> Is this a real photograph or AI-generated?</li>
                <li><strong>Quality Assessment:</strong> Is the image clear and properly lit?</li>
              </ul>
              <p className="validation-note">
                <strong>Note:</strong> AI validation is probabilistic and risk-based. If your genuine work is flagged, 
                an admin will review it manually. You can also resubmit with better quality photos.
              </p>
            </section>

            <section className="guideline-section">
              <h4><LuClock className="section-icon warning" /> Review Timeline</h4>
              <ul>
                <li><strong>Automatic Approval:</strong> Most valid submissions are approved instantly by AI</li>
                <li><strong>Flagged for Review:</strong> Submissions needing manual review take 24-48 hours</li>
                <li><strong>Rejection:</strong> You'll receive detailed feedback explaining why and how to improve</li>
              </ul>
            </section>

            <section className="guideline-section tips">
              <h4>💡 Tips for Success</h4>
              <ul>
                <li>Take photos in natural daylight or bright indoor lighting</li>
                <li>Use a plain background to make your craft stand out</li>
                <li>Show the full craft and any important details</li>
                <li>Include your hands in the photo to prove it's your work (optional)</li>
                <li>Take photos immediately after completing the tutorial</li>
              </ul>
            </section>
          </div>

          <div className="guidelines-footer">
            <button className="btn-primary" onClick={() => setShowGuidelines(false)}>
              Got It!
            </button>
          </div>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="feedback-loading">
        <LuRefreshCw className="loading-spinner" />
        <p>Loading feedback...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="feedback-error">
        <LuX />
        <p>{error}</p>
        <button onClick={fetchFeedback} className="retry-button">
          <LuRefreshCw /> Retry
        </button>
      </div>
    );
  }

  if (feedbackData.length === 0) {
    return (
      <div className="feedback-empty">
        <LuFileText className="empty-icon" />
        <p>No practice uploads yet</p>
        <p className="empty-subtitle">Upload your practice work to receive AI-powered feedback</p>
      </div>
    );
  }

  return (
    <div className="practice-feedback-container">
      {renderGuidelinesModal()}
      
      <div className="feedback-header">
        <h3>Practice Upload Feedback</h3>
        <button onClick={fetchFeedback} className="refresh-button">
          <LuRefreshCw /> Refresh
        </button>
      </div>

      <div className="feedback-list">
        {feedbackData.map((upload) => (
          <div key={upload.upload_id} className="feedback-card">
            <div className="feedback-card-header" onClick={() => toggleExpand(upload.upload_id)}>
              <div className="header-left">
                {getStatusBadge(upload)}
                <div className="upload-info">
                  <h4>{upload.tutorial_title}</h4>
                  <span className="upload-date">
                    Uploaded: {new Date(upload.upload_date).toLocaleDateString()}
                  </span>
                </div>
              </div>
              <div className="header-right">
                <span className="images-count">{upload.images_count} image(s)</span>
                {expandedUpload === upload.upload_id ? <LuChevronUp /> : <LuChevronDown />}
              </div>
            </div>

            {expandedUpload === upload.upload_id && (
              <div className="feedback-card-body">
                {renderFeedbackMessages(upload)}
                {renderAIValidation(upload)}
                {renderActionItems(upload)}

                {/* Images Preview */}
                {upload.images && upload.images.length > 0 && (
                  <div className="images-preview">
                    <h4 className="section-title">Uploaded Images</h4>
                    <div className="images-grid">
                      {upload.images.map((image, index) => (
                        <div key={index} className="image-preview">
                          <img 
                            src={`http://localhost/my_little_thingz/backend/${image.file_path}`} 
                            alt={image.original_name}
                            onError={(e) => {
                              console.error('Image load error:', e.target.src);
                              e.target.style.display = 'none';
                            }}
                          />
                          <span className="image-name">{image.original_name}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        ))}
      </div>

      {/* Transparency Notice */}
      <div className="transparency-notice">
        <LuInfo />
        <p>
          <strong>AI Transparency:</strong> All validation decisions are made by our AI system 
          and are shown here for educational purposes. Decisions are based on craft category 
          classification, image authenticity analysis, and quality assessment.
        </p>
      </div>
    </div>
  );
};

export default PracticeUploadFeedback;
