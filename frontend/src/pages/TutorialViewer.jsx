import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { LuArrowLeft, LuDownload, LuLock, LuUpload, LuCheck, LuX, LuClock } from 'react-icons/lu';
import ImageAnalysisResults from '../components/ImageAnalysisResults';
import '../styles/tutorial-viewer.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';
const ROOT_BASE = API_BASE.replace('/backend/api', '');

export default function TutorialViewer() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { tutorialAuth } = useTutorialAuth();
  const [tutorial, setTutorial] = useState(null);
  const [loading, setLoading] = useState(true);
  const [hasAccess, setHasAccess] = useState(false);
  const [subscriptionStatus, setSubscriptionStatus] = useState(null);
  const [error, setError] = useState('');
  const [practiceUpload, setPracticeUpload] = useState(null);
  const [uploadStatus, setUploadStatus] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [analysisResults, setAnalysisResults] = useState(null);
  const [showAnalysis, setShowAnalysis] = useState(false);

  // Check if user can download videos (Premium and Pro feature)
  const canDownloadVideos = () => {
    return subscriptionStatus?.feature_access?.access_levels?.can_download_videos || false;
  };

  // Check if user can upload practice work (Pro only)
  const canUploadPractice = () => {
    return subscriptionStatus?.feature_access?.access_levels?.can_upload_practice_work || 
           subscriptionStatus?.plan_code === 'pro' || false;
  };

  // Fetch existing practice upload for this tutorial
  const fetchPracticeUpload = async () => {
    if (!canUploadPractice()) return;
    
    try {
      const res = await fetch(`${API_BASE}/pro/practice-upload-simple.php?tutorial_id=${id}`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      if (data.status === 'success' && data.uploads.length > 0) {
        setPracticeUpload(data.uploads[0]);
      }
    } catch (error) {
      console.error('Error fetching practice upload:', error);
    }
  };

  useEffect(() => {
    fetchTutorial();
    checkAccess();
    if (tutorialAuth?.email) {
      fetchPracticeUpload();
    }
  }, [id, tutorialAuth?.email]);

  const fetchTutorial = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/tutorial-detail.php?id=${id}`);
      const data = await res.json();
      
      if (data.status === 'success') {
        setTutorial(data.tutorial);
      } else {
        setError(data.message || 'Failed to load tutorial');
      }
    } catch (error) {
      console.error('Error fetching tutorial:', error);
      setError('Failed to load tutorial');
    } finally {
      setLoading(false);
    }
  };

  const checkAccess = async () => {
    try {
      // First check subscription status
      const subRes = await fetch(`${API_BASE}/customer/subscription-status.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      const subData = await subRes.json();
      
      console.log('TutorialViewer - Subscription status:', subData);
      setSubscriptionStatus(subData); // Store subscription status
      
      // If user has premium/pro subscription (active, pending, or authenticated), grant access
      if (subData.status === 'success' && 
          (subData.plan_code === 'premium' || subData.plan_code === 'pro') &&
          (subData.is_active || subData.subscription_status === 'pending' || subData.subscription_status === 'authenticated')) {
        console.log('TutorialViewer - Access granted via subscription:', subData.plan_code, 'status:', subData.subscription_status);
        setHasAccess(true);
        return;
      }
      
      // Otherwise check individual tutorial access
      const res = await fetch(`${API_BASE}/customer/check-tutorial-access.php?tutorial_id=${id}&email=${tutorialAuth?.email}`, {
        headers: {
          'X-Tutorials-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      console.log('TutorialViewer - Tutorial access check:', data);
      
      if (data.status === 'success' && data.has_access) {
        setHasAccess(true);
      }
    } catch (error) {
      console.error('Error checking access:', error);
    }
  };

  if (loading) {
    return (
      <div className="tutorial-viewer-container">
        <div className="loading">Loading tutorial...</div>
      </div>
    );
  }

  if (error || !tutorial) {
    return (
      <div className="tutorial-viewer-container">
        <div className="error-message">
          <p>{error || 'Tutorial not found'}</p>
          <Link to="/tutorials" className="btn btn-primary">Back to Tutorials</Link>
        </div>
      </div>
    );
  }

  if (!hasAccess && !tutorial.is_free && tutorial.price > 0) {
    return (
      <div className="tutorial-viewer-container">
        <div className="error-message">
          <p>You don't have access to this tutorial. Please purchase it first.</p>
          <Link to="/tutorials" className="btn btn-primary">Back to Tutorials</Link>
        </div>
      </div>
    );
  }

  const resolveUrl = (url) => {
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url; // already absolute

    const cleaned = url.replace(/^\/+/, '');

    // If stored path starts with uploads/, serve from backend/uploads/...
    if (cleaned.startsWith('uploads/')) {
      return `${ROOT_BASE}/backend/${cleaned}`;
    }

    // If it already includes backend/ prefix, just attach to root.
    if (cleaned.startsWith('backend/')) {
      return `${ROOT_BASE}/${cleaned}`;
    }

    // Fallback: assume relative to backend/
    return `${ROOT_BASE}/backend/${cleaned}`;
  };

  const resourceUrl =
    resolveUrl(tutorial.resource_url || tutorial.resources_url || tutorial.download_url || tutorial.video_url);

  // Handle practice work upload
  const handlePracticeUpload = async (event) => {
    const files = Array.from(event.target.files);
    if (!files.length) return;

    // Validate files
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/avi', 'application/pdf'];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    for (const file of files) {
      if (!allowedTypes.includes(file.type)) {
        alert(`Invalid file type: ${file.name}. Please upload JPG, PNG, GIF, WebP, MP4, AVI, or PDF files.`);
        return;
      }
      
      if (file.size > maxSize) {
        alert(`File too large: ${file.name}. Please upload files smaller than 10MB.`);
        return;
      }
    }

    setUploading(true);
    setUploadStatus(null);

    const formData = new FormData();
    formData.append('tutorial_id', id);
    formData.append('email', tutorialAuth?.email || '');
    formData.append('description', `Practice work for tutorial: ${tutorial.title}`);

    // Add all files
    files.forEach((file, index) => {
      formData.append('practice_images[]', file);
    });

    try {
      const res = await fetch(`${API_BASE}/pro/practice-upload-direct.php`, {
        method: 'POST',
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        },
        body: formData
      });

      const data = await res.json();
      
      if (data.status === 'success') {
        setUploadStatus('success');
        
        // Show AI analysis results if available
        if (data.ai_analysis && data.ai_analysis.analysis_results) {
          setAnalysisResults(data.ai_analysis.analysis_results);
          setShowAnalysis(true);
        } else {
          // Fallback to enhanced success message if no AI analysis
          const successMessage = data.auto_approved ? 
            `🎉 Upload Successful & Auto-Approved!\n\n` +
            `✅ ${data.files_uploaded} file(s) uploaded\n` +
            `✅ Upload ID: ${data.upload_id}\n` +
            `✅ Status: Approved\n` +
            `✅ Progress Bonus: +${data.practice_bonus}%\n\n` +
            `📈 Your tutorial progress has been updated!\n` +
            `Files uploaded:\n${data.files.map(f => `• ${f.original_name}`).join('\n')}\n\n` +
            `${data.message_detail}` :
            `✅ Upload Successful!\n\n` +
            `${data.files_uploaded} file(s) uploaded\n` +
            `Upload ID: ${data.upload_id}\n` +
            `Status: Pending Review\n\n` +
            `Files uploaded:\n${data.files.map(f => `• ${f.original_name}`).join('\n')}\n\n` +
            `You'll receive feedback within 24-48 hours.`;
          
          alert(successMessage);
        }
        
        fetchPracticeUpload(); // Refresh upload status
      } else {
        setUploadStatus('error');
        console.error('Upload error:', data);
        const errorMsg = data.message || 'Upload failed';
        const debugInfo = data.debug ? '\n\nDebug info: ' + JSON.stringify(data.debug, null, 2) : '';
        alert(`❌ Upload Failed\n\n${errorMsg}${debugInfo}`);
      }
    } catch (error) {
      console.error('Error uploading practice work:', error);
      setUploadStatus('error');
      alert(`❌ Network Error\n\n${error.message}\n\nPlease check that your local server is running.`);
    } finally {
      setUploading(false);
      // Reset the file input
      event.target.value = '';
    }
  };

  // Get status icon for practice upload
  const getPracticeStatusIcon = (status) => {
    switch (status) {
      case 'approved':
        return <LuCheck size={18} className="status-approved" />;
      case 'rejected':
        return <LuX size={18} className="status-rejected" />;
      case 'pending':
      default:
        return <LuClock size={18} className="status-pending" />;
    }
  };

  const closeAnalysis = () => {
    setShowAnalysis(false);
    setAnalysisResults(null);
  };

  return (
    <>
      <div className="tutorial-viewer-container">
        <header className="viewer-header">
          <Link to="/tutorials" className="back-button">
            <LuArrowLeft size={20} />
            Back to Tutorials
          </Link>
          <h1>{tutorial.title}</h1>
        </header>

        <div className="viewer-content">
          <div className="video-container">
            {tutorial.video_url ? (
              <iframe
                width="100%"
                height="600"
                src={resolveUrl(tutorial.video_url)}
                title={tutorial.title}
                frameBorder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
              />
            ) : (
              <div className="video-placeholder">
                <p>Video not available</p>
              </div>
            )}
          </div>

          <div className="viewer-sidebar">
            <div className="tutorial-info">
              <h2>{tutorial.title}</h2>
              
              <div className="info-grid">
                <div className="info-item">
                  <span className="label">Duration</span>
                  <span className="value">{tutorial.duration || 'N/A'} minutes</span>
                </div>
                <div className="info-item">
                  <span className="label">Level</span>
                  <span className="value">{tutorial.difficulty_level || 'Beginner'}</span>
                </div>
                <div className="info-item">
                  <span className="label">Category</span>
                  <span className="value">{tutorial.category && tutorial.category !== '0' ? tutorial.category : 'General'}</span>
                </div>
              </div>

              <div className="description">
                <h3>About This Tutorial</h3>
                <p>{tutorial.description}</p>
              </div>

              <div className="tutorial-tools">
                {canDownloadVideos() ? (
                  resourceUrl ? (
                    <a className="tool-btn" href={resourceUrl} download target="_blank" rel="noopener noreferrer">
                      <LuDownload size={18} />
                      Download Resources
                    </a>
                  ) : (
                    <button className="tool-btn" disabled title="No resources available">
                      <LuDownload size={18} />
                      Download Resources
                    </button>
                  )
                ) : (
                  <div className="download-restricted">
                    <button className="tool-btn restricted" disabled title="Premium/Pro feature">
                      <LuLock size={18} />
                      Download Restricted
                    </button>
                    <p className="restriction-note">
                      Download feature is available for Premium and Pro subscribers only.
                      <br />
                      <span>Current plan: <strong>{subscriptionStatus?.plan_code || 'Basic'}</strong></span>
                    </p>
                  </div>
                )}

                {/* Practice Upload Section - Pro Only */}
                {canUploadPractice() && (
                  <div className="practice-upload-section">
                    <h4>Submit Practice Work</h4>
                    
                    {practiceUpload ? (
                      <div className="existing-upload">
                        <div className="upload-status">
                          {getPracticeStatusIcon(practiceUpload.status)}
                          <span className={`status-text status-${practiceUpload.status}`}>
                            {practiceUpload.status === 'approved' && 'Approved'}
                            {practiceUpload.status === 'rejected' && 'Needs Revision'}
                            {practiceUpload.status === 'pending' && 'Under Review'}
                          </span>
                        </div>
                        
                        <div className="upload-info">
                          <p><strong>File:</strong> {practiceUpload.original_filename}</p>
                          <p><strong>Uploaded:</strong> {new Date(practiceUpload.upload_date).toLocaleDateString()}</p>
                          {practiceUpload.admin_feedback && (
                            <div className="admin-feedback">
                              <p><strong>Feedback:</strong></p>
                              <p>{practiceUpload.admin_feedback}</p>
                            </div>
                          )}
                        </div>
                        
                        <label className="upload-btn secondary">
                          <LuUpload size={18} />
                          {uploading ? 'Uploading...' : (practiceUpload.status === 'rejected' ? 'Resubmit Work' : 'Update Submission')}
                          <input
                            type="file"
                            accept="image/*,video/*,.pdf"
                            multiple
                            onChange={handlePracticeUpload}
                            disabled={uploading}
                            style={{ display: 'none' }}
                          />
                        </label>
                      </div>
                    ) : (
                      <div className="new-upload">
                        <p>Upload your practice work for this tutorial to track your progress.</p>
                        <label className="upload-btn">
                          <LuUpload size={18} />
                          {uploading ? 'Uploading...' : 'Upload Practice Work'}
                          <input
                            type="file"
                            accept="image/*,video/*,.pdf"
                            multiple
                            onChange={handlePracticeUpload}
                            disabled={uploading}
                            style={{ display: 'none' }}
                          />
                        </label>
                        <p className="upload-hint">
                          Accepted formats: JPG, PNG, GIF, WebP, MP4, AVI, PDF (Max 10MB each)
                        </p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* AI Analysis Results Modal */}
      {showAnalysis && analysisResults && (
        <ImageAnalysisResults 
          analysisResults={analysisResults}
          onClose={closeAnalysis}
        />
      )}
    </>
  );
}
