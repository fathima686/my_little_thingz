import React, { useState } from 'react';
import { LuX, LuUpload, LuImage, LuCalendar, LuDollarSign, LuMessageSquare } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const CustomizationModal = ({ artwork, isOpen, onClose, onSuccess }) => {
  const { auth } = useAuth();
  const [formData, setFormData] = useState({
    description: '',
    occasion: '',
    deadline: ''
  });
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  if (!isOpen || !artwork) return null;

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const handleImageUpload = (e) => {
    const files = Array.from(e.target.files);
    if (images.length + files.length > 5) {
      alert('Maximum 5 images allowed');
      return;
    }
    setImages(prev => [...prev, ...files]);
  };

  const removeImage = (index) => {
    setImages(prev => prev.filter((_, i) => i !== index));
  };

  const validateForm = () => {
    const newErrors = {};
    if (!formData.description.trim()) {
      newErrors.description = 'Description is required';
    }
    if (!formData.occasion.trim()) {
      newErrors.occasion = 'Occasion is required';
    }
    if (!formData.deadline) {
      newErrors.deadline = 'Date is required';
    }
    if (images.length === 0) {
      newErrors.images = 'At least one reference image is required';
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setLoading(true);
    
    try {
      // Create FormData for file upload
      const submitData = new FormData();
      // Include the artwork id so backend can add to cart
      submitData.append('artwork_id', String(artwork.id));
      submitData.append('quantity', '1');
      submitData.append('description', formData.description);
      submitData.append('occasion', formData.occasion);
      submitData.append('date', formData.deadline);
      submitData.append('source', 'cart');

      // Add images as reference_images[] to match backend
      images.forEach((image) => {
        submitData.append('reference_images[]', image);
      });

      const response = await fetch(`${API_BASE}/customer/cart-with-customization.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${auth?.token}`,
          'X-User-ID': auth?.user_id
        },
        body: submitData
      });

      const data = await response.json();
      
      if (data.status === 'success') {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'success', 
            message: 'Customization request submitted successfully! Admin will review and approve before payment.' 
          } 
        }));
        onSuccess && onSuccess(data);
        onClose();
        resetForm();
      } else {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'error', 
            message: data.message || 'Failed to submit customization request' 
          } 
        }));
      }
    } catch (error) {
      console.error('Error submitting customization request:', error);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'error', 
          message: 'Error submitting customization request' 
        } 
      }));
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setFormData({
      description: '',
      occasion: '',
      deadline: ''
    });
    setImages([]);
    setErrors({});
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  return (
    <div className="modal-overlay" onClick={handleClose}>
      <div className="modal-content customization-modal" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>Customization Request</h2>
          <button className="modal-close" onClick={handleClose}>
            <LuX />
          </button>
        </div>

        <div className="modal-body">
          <div className="product-preview">
            <img src={artwork.image_url} alt={artwork.title} />
            <div className="product-info">
              <h3>{artwork.title}</h3>
              <p className="price">₹{artwork.price}</p>
              <p className="description">{artwork.description}</p>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="customization-form">
            <div className="form-group">
              <label htmlFor="description">Description *</label>
              <textarea
                id="description"
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                placeholder="Describe what customization you need..."
                rows="4"
                className={errors.description ? 'error' : ''}
              />
              {errors.description && <span className="error-text">{errors.description}</span>}
            </div>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="occasion">Occasion *</label>
                <select
                  id="occasion"
                  name="occasion"
                  value={formData.occasion}
                  onChange={handleInputChange}
                >
                  <option value="">Select occasion</option>
                  <option value="wedding">Wedding</option>
                  <option value="birthday">Birthday</option>
                  <option value="anniversary">Anniversary</option>
                  <option value="graduation">Graduation</option>
                  <option value="baby_shower">Baby Shower</option>
                  <option value="valentine">Valentine's Day</option>
                  <option value="christmas">Christmas</option>
                  <option value="other">Other</option>
                </select>
                {errors.occasion && <span className="error-text">{errors.occasion}</span>}
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="deadline">Date *</label>
              <input
                type="date"
                id="deadline"
                name="deadline"
                value={formData.deadline}
                onChange={handleInputChange}
                min={new Date().toISOString().split('T')[0]}
                className={errors.deadline ? 'error' : ''}
              />
              {errors.deadline && <span className="error-text">{errors.deadline}</span>}
            </div>

            <div className="form-group">
              <label>Reference Images *</label>
              <div className="image-upload-area">
                <input
                  type="file"
                  id="images"
                  multiple
                  accept="image/*"
                  onChange={handleImageUpload}
                  style={{ display: 'none' }}
                />
                <label htmlFor="images" className="upload-button">
                  <LuUpload />
                  Upload Images (Max 5)
                </label>
                <p className="upload-hint">Upload reference images to help us understand your requirements</p>
              </div>
              {errors.images && <span className="error-text">{errors.images}</span>}
              
              {images.length > 0 && (
                <div className="image-preview-grid">
                  {images.map((image, index) => (
                    <div key={index} className="image-preview">
                      <img src={URL.createObjectURL(image)} alt={`Preview ${index + 1}`} />
                      <button
                        type="button"
                        className="remove-image"
                        onClick={() => removeImage(index)}
                      >
                        <LuX />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="form-actions">
              <button type="button" className="btn btn-outline" onClick={handleClose}>
                Cancel
              </button>
              <button type="submit" className="btn btn-primary" disabled={loading}>
                {loading ? 'Submitting...' : 'Submit Request'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default CustomizationModal;
