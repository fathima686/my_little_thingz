import React, { useState } from 'react';
import PriceCalculator from './PriceCalculator';
import { LuX, LuUpload, LuImage, LuCalendar, LuDollarSign, LuMessageSquare } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';
import { trimWhitespace, validateRequired } from '../../utils/validation';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const ALLOWED_OCCASIONS = ['wedding', 'birthday', 'anniversary', 'graduation', 'baby_shower', 'valentine', 'christmas', 'other'];

const CustomizationModal = ({ artwork, isOpen, onClose, onSuccess, onOptionsChange }) => {
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

  // Product types that require picture customization
  const requiresPictures = ['frame', 'polaroid', 'album', 'wedding_cards', 'wedding card', 'photo frame', 'photo album'];
  const categoryName = (artwork.category_name || '').toLowerCase();
  const requiresPicturesUpload = requiresPictures.some(type => 
    categoryName.includes(type) || artwork.title?.toLowerCase().includes(type)
  );

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    const trimmedValue = name === 'description' ? trimWhitespace(value) : value;
    setFormData(prev => ({
      ...prev,
      [name]: trimmedValue
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
    const maxFiles = 5;
    const maxFileSize = 5 * 1024 * 1024; // 5MB per file
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Validate file count
    if (images.length + files.length > maxFiles) {
      setErrors(prev => ({ ...prev, images: `Maximum ${maxFiles} images allowed` }));
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: `You can only upload up to ${maxFiles} images` } }));
      return;
    }

    // Validate each file
    const validFiles = [];
    const invalidFiles = [];
    
    files.forEach(file => {
      if (!allowedTypes.includes(file.type)) {
        invalidFiles.push(`${file.name} - Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.`);
      } else if (file.size > maxFileSize) {
        invalidFiles.push(`${file.name} - File size exceeds 5MB limit.`);
      } else {
        validFiles.push(file);
      }
    });

    if (invalidFiles.length > 0) {
      setErrors(prev => ({ ...prev, images: invalidFiles.join(' ') }));
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: invalidFiles.join(' ') } }));
      if (validFiles.length === 0) return;
    } else {
      setErrors(prev => ({ ...prev, images: '' }));
    }

    setImages(prev => [...prev, ...validFiles]);
  };

  const removeImage = (index) => {
    setImages(prev => prev.filter((_, i) => i !== index));
    // Clear image error when images are removed if required
    if (errors.images && images.length > 1) {
      setErrors(prev => ({ ...prev, images: '' }));
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    // Validate description - required, not empty/whitespace
    const descError = validateRequired(formData.description, 'Description');
    if (descError) {
      newErrors.description = descError;
    } else if (trimWhitespace(formData.description).length < 10) {
      newErrors.description = 'Description must be at least 10 characters';
    } else if (trimWhitespace(formData.description).length > 5000) {
      newErrors.description = 'Description must be no more than 5000 characters';
    }
    
    // Validate occasion - required, must be from allowed list
    if (!formData.occasion || !formData.occasion.trim()) {
      newErrors.occasion = 'Occasion is required';
    } else if (!ALLOWED_OCCASIONS.includes(formData.occasion)) {
      newErrors.occasion = 'Please select a valid occasion';
    }
    
    // Validate deadline - required, must be valid future date
    if (!formData.deadline) {
      newErrors.deadline = 'Date is required';
    } else {
      const deadlineDate = new Date(formData.deadline);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (isNaN(deadlineDate.getTime())) {
        newErrors.deadline = 'Please enter a valid date';
      } else if (deadlineDate < today) {
        newErrors.deadline = 'Date cannot be in the past';
      }
    }
    
    // Require images for frames, polaroids, albums, and wedding cards
    if (images.length === 0 && requiresPicturesUpload) {
      newErrors.images = 'At least one picture is required for customization';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Please fix the validation errors before submitting' } }));
      return;
    }

    setLoading(true);
    
    try {
      // Create FormData for file upload
      const submitData = new FormData();
      // Include the artwork id so backend can add to cart
      submitData.append('artwork_id', String(artwork.id));
      submitData.append('quantity', '1');
      submitData.append('description', trimWhitespace(formData.description));
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
            message: '✅ Customization request submitted! Admin will review your pictures and approve before you can proceed to payment.' 
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
        
        {/* Admin Approval Notice */}
        <div style={{
          background: '#fef3c7',
          border: '2px solid #f59e0b',
          borderRadius: '8px',
          padding: '12px 16px',
          margin: '16px',
          display: 'flex',
          alignItems: 'center',
          gap: '12px'
        }}>
          <span style={{ fontSize: '24px' }}>⏳</span>
          <div>
            <strong style={{ color: '#92400e', display: 'block' }}>Admin Approval Required</strong>
            <span style={{ color: '#92400e', fontSize: '13px' }}>
              Your customization request will be reviewed by admin. Payment can only proceed after approval.
            </span>
          </div>
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
              <label>Customize and see price</label>
              <PriceCalculator artwork={artwork} onChange={({ selectedOptions, total }) => {
                setFormData(prev => ({ ...prev, selected_options: selectedOptions, computed_total: total }));
                if (typeof onOptionsChange === 'function' && artwork?.id) {
                  onOptionsChange(artwork.id, selectedOptions, total);
                }
              }} />
            </div>
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
              <label>
                Reference Images {requiresPicturesUpload && '*'}
                {requiresPicturesUpload && (
                  <span style={{ color: '#ef4444', fontSize: '13px', fontWeight: 'normal' }}>
                    {' '}(Required for this product)
                  </span>
                )}
              </label>
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
                  Upload Pictures (Max 5)
                </label>
                <p className="upload-hint">
                  {requiresPicturesUpload 
                    ? '⚠️ This product requires pictures for customization. Admin will review before approval.'
                    : 'Upload reference images to help us understand your requirements'
                  }
                </p>
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
