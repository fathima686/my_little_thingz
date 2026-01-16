import React, { useState, useRef } from 'react';
import { LuUpload, LuImage, LuX, LuCheck, LuZap } from 'react-icons/lu';
import ImageAnalysisResults from './ImageAnalysisResults';
import '../styles/practice-upload.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const PracticeUpload = ({ tutorialId, tutorialTitle, userEmail, onUploadSuccess }) => {
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [uploadStatus, setUploadStatus] = useState(null);
  const [description, setDescription] = useState('');
  const [analysisResults, setAnalysisResults] = useState(null);
  const [showAnalysis, setShowAnalysis] = useState(false);
  const fileInputRef = useRef(null);

  const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    const validFiles = files.filter(file => {
      const isImage = file.type.startsWith('image/');
      const isValidSize = file.size <= 5 * 1024 * 1024; // 5MB limit
      return isImage && isValidSize;
    });

    if (validFiles.length !== files.length) {
      alert('Some files were skipped. Only image files under 5MB are allowed.');
    }

    setSelectedFiles(prev => [...prev, ...validFiles].slice(0, 5)); // Max 5 files
  };

  const removeFile = (index) => {
    setSelectedFiles(prev => prev.filter((_, i) => i !== index));
  };

  const handleUpload = async () => {
    if (selectedFiles.length === 0) {
      alert('Please select at least one image to upload.');
      return;
    }

    setUploading(true);
    setUploadStatus(null);

    try {
      const formData = new FormData();
      formData.append('tutorial_id', tutorialId);
      formData.append('description', description);
      formData.append('email', userEmail);

      selectedFiles.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
      });

      const response = await fetch(`${API_BASE}/pro/practice-upload.php`, {
        method: 'POST',
        headers: {
          'X-Tutorial-Email': userEmail
        },
        body: formData
      });

      const result = await response.json();

      if (result.status === 'success') {
        setUploadStatus('success');
        
        // Show AI analysis results if available
        if (result.ai_analysis && result.ai_analysis.analysis_results) {
          setAnalysisResults(result.ai_analysis.analysis_results);
          setShowAnalysis(true);
        }
        
        // Clear form
        setSelectedFiles([]);
        setDescription('');
        
        // Call success callback
        onUploadSuccess && onUploadSuccess(result);
      } else {
        setUploadStatus('error');
        alert(result.message || 'Upload failed. Please try again.');
      }
    } catch (error) {
      console.error('Upload error:', error);
      setUploadStatus('error');
      alert('Upload failed. Please check your connection and try again.');
    } finally {
      setUploading(false);
    }
  };

  const triggerFileSelect = () => {
    fileInputRef.current?.click();
  };

  const closeAnalysis = () => {
    setShowAnalysis(false);
    setAnalysisResults(null);
  };

  return (
    <>
      <div className="practice-upload">
        <div className="practice-upload-header">
          <h3>Upload Practice Work</h3>
          <p>Share your practice work for "{tutorialTitle}" to get AI analysis and instructor feedback</p>
        </div>

        <div className="upload-area">
          <input
            ref={fileInputRef}
            type="file"
            multiple
            accept="image/*"
            onChange={handleFileSelect}
            style={{ display: 'none' }}
          />

          {selectedFiles.length === 0 ? (
            <div className="upload-dropzone" onClick={triggerFileSelect}>
              <LuUpload size={48} />
              <h4>Click to select images</h4>
              <p>AI will analyze your work for authenticity and quality</p>
              <p className="supported-formats">Supported: JPG, PNG, GIF, WebP</p>
            </div>
          ) : (
            <div className="selected-files">
              <div className="files-grid">
                {selectedFiles.map((file, index) => (
                  <div key={index} className="file-preview">
                    <img
                      src={URL.createObjectURL(file)}
                      alt={`Preview ${index + 1}`}
                      className="preview-image"
                    />
                    <button
                      className="remove-file"
                      onClick={() => removeFile(index)}
                      type="button"
                    >
                      <LuX size={16} />
                    </button>
                    <div className="file-info">
                      <span className="file-name">{file.name}</span>
                      <span className="file-size">
                        {(file.size / 1024 / 1024).toFixed(1)}MB
                      </span>
                    </div>
                  </div>
                ))}
                
                {selectedFiles.length < 5 && (
                  <div className="add-more-files" onClick={triggerFileSelect}>
                    <LuImage size={24} />
                    <span>Add More</span>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        <div className="upload-description">
          <label htmlFor="description">Description (Optional)</label>
          <textarea
            id="description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Describe your practice work, any challenges you faced, or questions you have..."
            rows={3}
            maxLength={500}
          />
          <div className="character-count">
            {description.length}/500 characters
          </div>
        </div>

        <div className="upload-actions">
          <button
            className="upload-button"
            onClick={handleUpload}
            disabled={uploading || selectedFiles.length === 0}
          >
            {uploading ? (
              <>
                <div className="upload-spinner"></div>
                Analyzing with AI...
              </>
            ) : (
              <>
                <LuZap size={20} />
                Upload & Analyze with AI
              </>
            )}
          </button>
        </div>

        {uploadStatus && !showAnalysis && (
          <div className={`upload-status ${uploadStatus}`}>
            {uploadStatus === 'success' ? (
              <>
                <LuCheck size={20} />
                <span>Practice work uploaded and analyzed successfully!</span>
              </>
            ) : (
              <>
                <LuX size={20} />
                <span>Upload failed. Please try again.</span>
              </>
            )}
          </div>
        )}

        <div className="upload-guidelines">
          <h4>AI Analysis Features:</h4>
          <ul>
            <li>🔍 Authenticity verification and quality assessment</li>
            <li>📷 Camera and metadata analysis</li>
            <li>🎨 Editing software detection</li>
            <li>🔄 Similarity checking against existing submissions</li>
            <li>📊 Professional scoring and detailed feedback</li>
          </ul>
        </div>
      </div>

      {showAnalysis && analysisResults && (
        <ImageAnalysisResults 
          analysisResults={analysisResults}
          onClose={closeAnalysis}
        />
      )}
    </>
  );
};

export default PracticeUpload;