import React, { useState, useEffect } from 'react';
import { LuX, LuUpload, LuCalendar, LuDollarSign, LuMessageCircle, LuImagePlus } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const CustomGiftRequest = ({ onClose }) => {
  const { auth } = useAuth();
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    occasion: '',
    budget_min: '',
    budget_max: '',
    deadline: '',
    special_instructions: '',
    reference_images: []
  });
  const OCCASIONS = [
    'Birthday',
    'Wedding',
    'Name Ceremony',
    'Festival',
    'Anniversary',
    'Housewarming',
    'Baby Shower',
    'Other'
  ];
  const [loading, setLoading] = useState(false);
  const [imageFiles, setImageFiles] = useState([]);
  const [imagePreviews, setImagePreviews] = useState([]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleImageUpload = (e) => {
    const files = Array.from(e.target.files);
    const maxFiles = 5;
    
    if (imageFiles.length + files.length > maxFiles) {
      alert(`You can only upload up to ${maxFiles} images`);
      return;
    }

    setImageFiles(prev => [...prev, ...files]);

    // Create previews
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = (e) => {
        setImagePreviews(prev => [...prev, {
          file: file,
          url: e.target.result,
          name: file.name
        }]);
      };
      reader.readAsDataURL(file);
    });
  };

  const removeImage = (index) => {
    setImageFiles(prev => prev.filter((_, i) => i !== index));
    setImagePreviews(prev => prev.filter((_, i) => i !== index));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      // Create FormData for file upload
      const submitData = new FormData();
      
      // Add only the required fields
      submitData.append('title', formData.title);
      submitData.append('occasion', formData.occasion || '');
      submitData.append('description', formData.description);
      // Use single budget field
      const budgetSingle = formData.budget_max || formData.budget_min || '';
      submitData.append('budget', budgetSingle);
      submitData.append('date', formData.deadline || '');

      // Add user ID (header is primary, but include for completeness)
      submitData.append('user_id', auth?.user_id);

      // Add images
      imageFiles.forEach((file, index) => {
        submitData.append(`reference_images[${index}]`, file);
      });

      const response = await fetch(`${API_BASE}/customer/custom-requests.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        },
        body: submitData
      });

      const data = await response.json();
      
      if (data.status === 'success') {
        alert('Custom gift request submitted successfully! We will contact you soon.');
        onClose();
      } else {
        alert(data.message || 'Failed to submit request');
      }
    } catch (error) {
      console.error('Error submitting request:', error);
      alert('Error submitting request. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const today = new Date().toISOString().split('T')[0];

  return (
    <div className="modal-overlay">
      <div className="modal-content large">
        <div className="modal-header">
          <h2>Custom Gift Request</h2>
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="custom-request-form">
          <div className="form-section">
            <h3>Basic Information</h3>
            
            <div className="form-group">
              <label htmlFor="title">Request Title *</label>
              <input
                type="text"
                id="title"
                name="title"
                value={formData.title}
                onChange={handleInputChange}
                placeholder="e.g., Custom Wedding Anniversary Gift"
                required
              />
            </div>

            <div className="form-group">
              <label htmlFor="occasion">Occasion</label>
              <select
                id="occasion"
                name="occasion"
                value={formData.occasion}
                onChange={handleInputChange}
              >
                <option value="">Select an occasion</option>
                {OCCASIONS.map((o) => (
                  <option key={o} value={o}>{o}</option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="description">Description *</label>
              <textarea
                id="description"
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                placeholder="Describe your custom gift idea in detail..."
                rows="4"
                required
              />
            </div>
          </div>

          <div className="form-section">
            <h3>Budget & Timeline</h3>
            
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="budget_min">
                  <LuDollarSign /> Minimum Budget
                </label>
                <input
                  type="number"
                  id="budget_min"
                  name="budget_min"
                  value={formData.budget_min}
                  onChange={handleInputChange}
                  placeholder="0"
                  min="0"
                  step="0.01"
                />
              </div>

              <div className="form-group">
                <label htmlFor="budget_max">
                  <LuDollarSign /> Maximum Budget
                </label>
                <input
                  type="number"
                  id="budget_max"
                  name="budget_max"
                  value={formData.budget_max}
                  onChange={handleInputChange}
                  placeholder="0"
                  min="0"
                  step="0.01"
                />
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="deadline">
                <LuCalendar /> Preferred Completion Date
              </label>
              <input
                type="date"
                id="deadline"
                name="deadline"
                value={formData.deadline}
                onChange={handleInputChange}
                min={today}
              />
            </div>
          </div>

          <div className="form-section">
            <h3>Additional Details</h3>
            
            <div className="form-group">
              <label htmlFor="special_instructions">
                <LuMessageCircle /> Special Instructions
              </label>
              <textarea
                id="special_instructions"
                name="special_instructions"
                value={formData.special_instructions}
                onChange={handleInputChange}
                placeholder="Any special requirements, materials, colors, sizes, etc."
                rows="3"
              />
            </div>
          </div>

          <div className="form-section">
            <h3>Reference Images</h3>
            <p className="form-help">Upload up to 5 images to help us understand your vision</p>
            
            <div className="image-upload-area">
              <input
                type="file"
                id="reference_images"
                multiple
                accept="image/*"
                onChange={handleImageUpload}
                style={{ display: 'none' }}
              />
              <label htmlFor="reference_images" className="upload-button">
                <LuUpload />
                <span>Choose Images</span>
              </label>
            </div>

            {imagePreviews.length > 0 && (
              <div className="image-previews">
                {imagePreviews.map((preview, index) => (
                  <div key={index} className="image-preview">
                    <img src={preview.url} alt={`Preview ${index + 1}`} />
                    <button
                      type="button"
                      className="remove-image"
                      onClick={() => removeImage(index)}
                    >
                      <LuX />
                    </button>
                    <span className="image-name">{preview.name}</span>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="form-actions">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Submitting...' : 'Submit Request'}
            </button>
          </div>
        </form>
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
          max-width: 900px;
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

        .custom-request-form {
          display: flex;
          flex-direction: column;
          gap: 32px;
        }

        .form-section {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .form-section h3 {
          margin: 0;
          color: #2c3e50;
          font-size: 18px;
          border-bottom: 2px solid #3498db;
          padding-bottom: 8px;
        }

        .form-group {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .form-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 16px;
        }

        .form-group label {
          font-weight: 500;
          color: #2c3e50;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
          padding: 12px;
          border: 1px solid #ddd;
          border-radius: 6px;
          font-size: 14px;
          transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
          outline: none;
          border-color: #3498db;
          box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-help {
          font-size: 14px;
          color: #666;
          margin: 0;
        }

        .image-upload-area {
          border: 2px dashed #ddd;
          border-radius: 8px;
          padding: 32px;
          text-align: center;
          transition: border-color 0.2s;
        }

        .image-upload-area:hover {
          border-color: #3498db;
        }

        .upload-button {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 12px 24px;
          background: #3498db;
          color: white;
          border-radius: 6px;
          cursor: pointer;
          transition: background-color 0.2s;
        }

        .upload-button:hover {
          background: #2980b9;
        }

        .image-previews {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
          gap: 16px;
          margin-top: 16px;
        }

        .image-preview {
          position: relative;
          border: 1px solid #ddd;
          border-radius: 8px;
          overflow: hidden;
        }

        .image-preview img {
          width: 100%;
          height: 120px;
          object-fit: cover;
        }

        .remove-image {
          position: absolute;
          top: 4px;
          right: 4px;
          background: rgba(231, 76, 60, 0.9);
          color: white;
          border: none;
          border-radius: 50%;
          width: 24px;
          height: 24px;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          font-size: 12px;
        }

        .image-name {
          position: absolute;
          bottom: 0;
          left: 0;
          right: 0;
          background: rgba(0, 0, 0, 0.7);
          color: white;
          padding: 4px 8px;
          font-size: 12px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .form-actions {
          display: flex;
          gap: 16px;
          justify-content: flex-end;
          padding-top: 16px;
          border-top: 1px solid #eee;
        }

        .btn {
          padding: 12px 24px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 500;
          transition: all 0.2s;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .btn-primary {
          background: #3498db;
          color: white;
        }

        .btn-primary:hover:not(:disabled) {
          background: #2980b9;
        }

        .btn-primary:disabled {
          background: #bdc3c7;
          cursor: not-allowed;
        }

        .btn-secondary {
          background: #95a5a6;
          color: white;
        }

        .btn-secondary:hover {
          background: #7f8c8d;
        }

        @media (max-width: 768px) {
          .form-row {
            grid-template-columns: 1fr;
          }
          
          .modal-content {
            width: 95%;
            padding: 16px;
          }
          
          .form-actions {
            flex-direction: column;
          }
        }
      `}</style>
    </div>
  );
};

export default CustomGiftRequest;