import React, { useState, useRef } from 'react';
import { LuUpload, LuImage, LuX, LuCheck, LuClock, LuZap } from 'react-icons/lu';
import '../styles/practice-upload.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const PracticeUpload = ({ tutorialId, tutorialTitle, userEmail, onUploadSuccess }) => {
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [uploadStatus, setUploadStatus] = useState(null);
  const [description, setDescription] = useState('');
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

  const handleUpload = () => {
    // Open popup upload window as workaround for React upload issues
    const popupUrl = 'http://localhost/my_little_thingz/practice-upload-popup.html';
    const popup = window.open(
      popupUrl, 
      'practiceUpload', 
      'width=600,height=700,scrollbars=yes,resizable=yes'
    );
    
    if (!popup) {
      alert('Please allow popups for this site to upload practice work.');
      return;
    }
    
    // Check if popup was closed (indicating successful upload)
    const checkClosed = setInterval(() => {
      if (popup.closed) {
        clearInterval(checkClosed);
        // Refresh the component or trigger success callback
        onUploadSuccess && onUploadSuccess();
        setUploadStatus('success');
        setTimeout(() => setUploadStatus(null), 3000);
      }
    }, 1000);
  };

  const triggerFileSelect = () => {
    fileInputRef.current?.click();
  };

  return (
    <div className="practice-upload">
      <div className="practice-upload-header">
        <h3>Upload Practice Work</h3>
        <p>Share your practice work for "{tutorialTitle}" to get feedback from instructors</p>
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
          <div className="upload-dropzone" onClick={handleUpload}>
            <LuUpload size={48} />
            <h4>Click to upload images</h4>
            <p>Opens upload window (popup)</p>
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
          disabled={uploading}
        >
          {uploading ? (
            <>
              <div className="upload-spinner"></div>
              Opening Upload Window...
            </>
          ) : (
            <>
              <LuUpload size={20} />
              Upload Practice Work
            </>
          )}
        </button>
      </div>

      {uploadStatus && (
        <div className={`upload-status ${uploadStatus}`}>
          {uploadStatus === 'success' ? (
            <>
              <LuCheck size={20} />
              <span>Practice work uploaded successfully! You'll receive feedback soon.</span>
            </>
          ) : (
            <>
              <LuZap size={20} />
              <span>Upload failed. Please try again.</span>
            </>
          )}
        </div>
      )}

      <div className="upload-guidelines">
        <h4>Upload Guidelines:</h4>
        <ul>
          <li>Upload clear, well-lit photos of your practice work</li>
          <li>Include multiple angles if relevant</li>
          <li>Maximum 5 images per submission</li>
          <li>Each image should be under 5MB</li>
          <li>You'll receive feedback within 24-48 hours</li>
        </ul>
      </div>
    </div>
  );
};

export default PracticeUpload;