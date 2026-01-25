import React, { useState, useEffect } from 'react';
import { createKeydownHandler, trimWhitespace, validateRequired } from '../../utils/validation';
import { LuX, LuUpload, LuCalendar, LuDollarSign, LuMessageCircle, LuImagePlus } from 'react-icons/lu';
import { useAuth } from '../../contexts/AuthContext';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const CustomGiftRequest = ({ onClose }) => {
  const { auth } = useAuth();
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    occasion: '',
    fixed_price: '',
    deadline: '',
    gift_tier: 'budget', // Default to budget
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
  const [errors, setErrors] = useState({});

  const handlePriceKeyDown = (e) => {
    // Prevent typing negative sign, plus sign, and 'e' (scientific notation)
    if (e.key === '-' || e.key === '+' || e.key === 'e' || e.key === 'E') {
      e.preventDefault();
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    // Handle fixed price field with strict validation
    if (name === 'fixed_price') {
      // Only allow positive numbers, prevent negative values and zero
      const numericValue = parseFloat(value);
      if (value === '' || (numericValue > 0 && !isNaN(numericValue) && numericValue <= 100000)) {
        setFormData(prev => ({
          ...prev,
          [name]: value
        }));
        
        // Real-time price validation
        if (value !== '') {
          if (numericValue < 10) {
            setErrors(prev => ({
              ...prev,
              [name]: 'Fixed price must be at least ₹10'
            }));
          } else if (numericValue > 100000) {
            setErrors(prev => ({
              ...prev,
              [name]: 'Fixed price cannot exceed ₹1,00,000'
            }));
          } else {
            setErrors(prev => ({
              ...prev,
              [name]: ''
            }));
          }
        }
      }
      return;
    }
    
    // Handle title with character restrictions
    if (name === 'title') {
      const processedValue = trimWhitespace(value);
      // Allow only valid characters during typing
      if (processedValue === '' || /^[a-zA-Z0-9\s\-.,!?&()]*$/.test(processedValue)) {
        setFormData(prev => ({
          ...prev,
          [name]: processedValue
        }));
        
        // Real-time title validation
        if (processedValue.length > 0 && processedValue.length < 5) {
          setErrors(prev => ({
            ...prev,
            title: 'Title must be at least 5 characters'
          }));
        } else if (processedValue.length > 100) {
          setErrors(prev => ({
            ...prev,
            title: 'Title must be no more than 100 characters'
          }));
        } else {
          setErrors(prev => ({
            ...prev,
            title: ''
          }));
        }
      }
      return;
    }
    
    // Handle description with word count validation
    if (name === 'description') {
      setFormData(prev => ({
        ...prev,
        [name]: value
      }));
      
      // Real-time description validation
      const trimmedDesc = trimWhitespace(value);
      const wordCount = trimmedDesc.split(/\s+/).filter(word => word.length > 0).length;
      
      if (trimmedDesc.length > 0) {
        if (trimmedDesc.length < 20) {
          setErrors(prev => ({
            ...prev,
            description: 'Description must be at least 20 characters'
          }));
        } else if (trimmedDesc.length > 2000) {
          setErrors(prev => ({
            ...prev,
            description: 'Description must be no more than 2000 characters'
          }));
        } else if (wordCount < 5) {
          setErrors(prev => ({
            ...prev,
            description: 'Description must contain at least 5 words'
          }));
        } else {
          setErrors(prev => ({
            ...prev,
            description: ''
          }));
        }
      }
      return;
    }
    
    // Handle deadline with real-time validation
    if (name === 'deadline') {
      setFormData(prev => ({
        ...prev,
        [name]: value
      }));
      
      if (value) {
        const deadlineDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        deadlineDate.setHours(0, 0, 0, 0);
        
        const diffTime = deadlineDate.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (deadlineDate < today) {
          setErrors(prev => ({
            ...prev,
            deadline: 'Deadline cannot be in the past'
          }));
        } else if (diffDays < 3) {
          setErrors(prev => ({
            ...prev,
            deadline: 'Please allow at least 3 days for custom gift creation'
          }));
        } else {
          setErrors(prev => ({
            ...prev,
            deadline: ''
          }));
        }
      }
      return;
    }
    
    // Handle special instructions with character limit
    if (name === 'special_instructions') {
      if (value.length <= 1000) {
        setFormData(prev => ({
          ...prev,
          [name]: value
        }));
        
        // Real-time validation for special instructions
        const trimmedInstructions = trimWhitespace(value);
        if (trimmedInstructions.length > 0 && trimmedInstructions.length < 10) {
          setErrors(prev => ({
            ...prev,
            special_instructions: 'Special instructions must be at least 10 characters if provided'
          }));
        } else {
          setErrors(prev => ({
            ...prev,
            special_instructions: ''
          }));
        }
      }
      return;
    }
    
    // Default handling for other fields
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
    const maxFiles = 5;
    const maxFileSize = 5 * 1024 * 1024; // 5MB per file
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Validate file count
    if (imageFiles.length + files.length > maxFiles) {
      window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: `You can only upload up to ${maxFiles} images` } }));
      setErrors(prev => ({ ...prev, images: `Maximum ${maxFiles} images allowed` }));
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

    setImageFiles(prev => [...prev, ...validFiles]);

    // Create previews
    validFiles.forEach(file => {
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

  const validateForm = () => {
    const newErrors = {};
    
    // Validate title - required, not empty/whitespace, strict character limits
    const titleError = validateRequired(formData.title, 'Request title');
    if (titleError) {
      newErrors.title = titleError;
    } else {
      const trimmedTitle = trimWhitespace(formData.title);
      if (trimmedTitle.length < 5) {
        newErrors.title = 'Request title must be at least 5 characters';
      } else if (trimmedTitle.length > 100) {
        newErrors.title = 'Request title must be no more than 100 characters';
      } else if (!/^[a-zA-Z0-9\s\-.,!?&()]+$/.test(trimmedTitle)) {
        newErrors.title = 'Request title contains invalid characters. Only letters, numbers, spaces, and basic punctuation allowed';
      }
    }
    
    // Validate description - required, not empty/whitespace, strict length and content
    const descError = validateRequired(formData.description, 'Description');
    if (descError) {
      newErrors.description = descError;
    } else {
      const trimmedDesc = trimWhitespace(formData.description);
      if (trimmedDesc.length < 20) {
        newErrors.description = 'Description must be at least 20 characters to provide adequate detail';
      } else if (trimmedDesc.length > 2000) {
        newErrors.description = 'Description must be no more than 2000 characters';
      } else if (trimmedDesc.split(' ').length < 5) {
        newErrors.description = 'Description must contain at least 5 words';
      }
    }
    
    // Validate occasion - if provided, must be from allowed list (strict validation)
    if (formData.occasion && !OCCASIONS.includes(formData.occasion)) {
      newErrors.occasion = 'Please select a valid occasion from the dropdown list';
    }
    
    // Strict fixed price validation - must be positive number, reasonable range
    if (formData.fixed_price) {
      const fixedPrice = parseFloat(formData.fixed_price) || 0;
      
      if (isNaN(fixedPrice) || fixedPrice <= 0) {
        newErrors.fixed_price = 'Fixed price must be a positive number greater than 0';
      } else if (fixedPrice < 10) {
        newErrors.fixed_price = 'Fixed price must be at least ₹10';
      } else if (fixedPrice > 100000) {
        newErrors.fixed_price = 'Fixed price cannot exceed ₹1,00,000';
      }
    } else {
      // Fixed price is required
      newErrors.fixed_price = 'Fixed price is required. Please specify the price for your custom gift';
    }
    
    // Strict deadline validation - must be valid future date with reasonable timeframe
    if (formData.deadline) {
      const deadlineDate = new Date(formData.deadline);
      const today = new Date();
      const maxDate = new Date();
      maxDate.setFullYear(today.getFullYear() + 2); // Max 2 years in future
      
      today.setHours(0, 0, 0, 0);
      deadlineDate.setHours(0, 0, 0, 0);
      
      if (isNaN(deadlineDate.getTime())) {
        newErrors.deadline = 'Please enter a valid date';
      } else if (deadlineDate < today) {
        newErrors.deadline = 'Deadline cannot be in the past';
      } else if (deadlineDate > maxDate) {
        newErrors.deadline = 'Deadline cannot be more than 2 years in the future';
      } else {
        // Check if deadline is too soon (less than 3 days)
        const diffTime = deadlineDate.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays < 3) {
          newErrors.deadline = 'Please allow at least 3 days for custom gift creation';
        }
      }
    }
    
    // Validate gift tier - strict validation
    if (!formData.gift_tier || !['budget', 'premium'].includes(formData.gift_tier)) {
      newErrors.gift_tier = 'Please select a valid gift tier (Budget-Friendly or Premium)';
    }
    
    // Validate special instructions if provided
    if (formData.special_instructions) {
      const trimmedInstructions = trimWhitespace(formData.special_instructions);
      if (trimmedInstructions.length > 1000) {
        newErrors.special_instructions = 'Special instructions must be no more than 1000 characters';
      } else if (trimmedInstructions.length > 0 && trimmedInstructions.length < 10) {
        newErrors.special_instructions = 'Special instructions must be at least 10 characters if provided';
      }
    }
    
    // Validate images - strict file validation
    if (imageFiles.length > 5) {
      newErrors.images = 'Maximum 5 images allowed';
    }
    
    // Additional validation: Check for duplicate image names
    if (imageFiles.length > 1) {
      const imageNames = imageFiles.map(file => file.name.toLowerCase());
      const duplicates = imageNames.filter((name, index) => imageNames.indexOf(name) !== index);
      if (duplicates.length > 0) {
        newErrors.images = 'Duplicate image files detected. Please select different images';
      }
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validate form before submission
    if (!validateForm()) {
      // Count validation errors
      const errorCount = Object.keys(errors).length;
      const errorFields = Object.keys(errors).join(', ');
      
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'error', 
          message: `Please fix ${errorCount} validation error${errorCount > 1 ? 's' : ''} in: ${errorFields}` 
        } 
      }));
      
      // Scroll to first error field
      const firstErrorField = document.querySelector('.form-group input.error, .form-group textarea.error, .form-group select.error');
      if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstErrorField.focus();
      }
      
      return;
    }
    
    // Final validation check before submission
    const trimmedTitle = trimWhitespace(formData.title);
    const trimmedDesc = trimWhitespace(formData.description);
    
    if (trimmedTitle.length < 5 || trimmedDesc.length < 20) {
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'error', 
          message: 'Please ensure all required fields meet the minimum length requirements' 
        } 
      }));
      return;
    }
    
    // Check fixed price requirement
    if (!formData.fixed_price) {
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'error', 
          message: 'Fixed price is required. Please specify the price for your custom gift.' 
        } 
      }));
      return;
    }
    
    setLoading(true);

    try {
      // Create FormData for file upload
      const submitData = new FormData();
      
      // Add only the required fields with trimmed values
      submitData.append('title', trimmedTitle);
      submitData.append('occasion', formData.occasion || '');
      submitData.append('description', trimmedDesc);
      // Use fixed price field
      const fixedPrice = formData.fixed_price || '';
      submitData.append('budget', fixedPrice);
      submitData.append('date', formData.deadline || '');
      submitData.append('gift_tier', formData.gift_tier || 'budget');
      
      // Add special instructions if provided
      if (formData.special_instructions && trimWhitespace(formData.special_instructions)) {
        submitData.append('special_instructions', trimWhitespace(formData.special_instructions));
      }

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
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'success', 
            message: 'Custom gift request submitted successfully! We will contact you within 24 hours.' 
          } 
        }));
        onClose();
      } else {
        window.dispatchEvent(new CustomEvent('toast', { 
          detail: { 
            type: 'error', 
            message: data.message || 'Failed to submit request. Please try again.' 
          } 
        }));
      }
    } catch (error) {
      console.error('Error submitting request:', error);
      window.dispatchEvent(new CustomEvent('toast', { 
        detail: { 
          type: 'error', 
          message: 'Network error occurred. Please check your connection and try again.' 
        } 
      }));
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
                onKeyDown={createKeydownHandler(true)}
                placeholder="e.g., Custom Wedding Anniversary Gift"
                className={errors.title ? 'error' : ''}
                maxLength="100"
                required
              />
              <div className="field-info">
                <span className={`char-count ${formData.title.length > 90 ? 'warning' : ''}`}>
                  {formData.title.length}/100 characters
                </span>
              </div>
              {errors.title && <span className="error-text">{errors.title}</span>}
            </div>

            <div className="form-group">
              <label htmlFor="occasion">Occasion</label>
              <select
                id="occasion"
                name="occasion"
                value={formData.occasion}
                onChange={handleInputChange}
                className={errors.occasion ? 'error' : ''}
              >
                <option value="">Select an occasion</option>
                {OCCASIONS.map((o) => (
                  <option key={o} value={o}>{o}</option>
                ))}
              </select>
              {errors.occasion && <span className="error-text">{errors.occasion}</span>}
            </div>

            <div className="form-group">
              <label htmlFor="description">Description *</label>
              <textarea
                id="description"
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                placeholder="Describe your custom gift idea in detail... (minimum 20 characters, 5 words)"
                rows="4"
                className={errors.description ? 'error' : ''}
                maxLength="2000"
                required
              />
              <div className="field-info">
                <span className={`char-count ${formData.description.length > 1800 ? 'warning' : ''}`}>
                  {formData.description.length}/2000 characters
                </span>
                <span className="word-count">
                  {formData.description.trim().split(/\s+/).filter(word => word.length > 0).length} words
                </span>
              </div>
              {errors.description && <span className="error-text">{errors.description}</span>}
            </div>
          </div>

          <div className="form-section">
            <h3>Budget & Timeline</h3>
            
            <div className="form-group">
              <label htmlFor="fixed_price">
                <LuDollarSign /> Fixed Price *
              </label>
              <input
                type="number"
                id="fixed_price"
                name="fixed_price"
                value={formData.fixed_price}
                onChange={handleInputChange}
                onKeyDown={handlePriceKeyDown}
                placeholder="Enter fixed price for your custom gift"
                min="10"
                step="0.01"
                className={errors.fixed_price ? 'error' : ''}
                required
              />
              <small style={{ marginTop: '0.5rem', display: 'block', color: '#666' }}>
                Specify the exact price you want to pay for this custom gift (₹10 - ₹1,00,000)
              </small>
              {errors.fixed_price && <span className="error-text">{errors.fixed_price}</span>}
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
                className={errors.deadline ? 'error' : ''}
              />
              {errors.deadline && <span className="error-text">{errors.deadline}</span>}
            </div>

            <div className="form-group">
              <label htmlFor="gift_tier">
                Gift Tier Classification
              </label>
              <select
                id="gift_tier"
                name="gift_tier"
                value={formData.gift_tier}
                onChange={handleInputChange}
              >
                <option value="budget">🎁 Budget-Friendly</option>
                <option value="premium">✨ Premium</option>
              </select>
              <small style={{ marginTop: '0.5rem', display: 'block', color: '#666' }}>
                {formData.gift_tier === 'budget' 
                  ? 'Best for cost-effective, thoughtful gifts' 
                  : 'For luxurious, high-end customizations'}
              </small>
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
                maxLength="1000"
              />
              <div className="field-info">
                <span className={`char-count ${formData.special_instructions.length > 900 ? 'warning' : ''}`}>
                  {formData.special_instructions.length}/1000 characters
                </span>
              </div>
              {errors.special_instructions && <span className="error-text">{errors.special_instructions}</span>}
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
            {errors.images && <span className="error-text">{errors.images}</span>}
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
        
        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
          border-color: #e74c3c;
        }
        
        .form-group input.error:focus,
        .form-group select.error:focus,
        .form-group textarea.error:focus {
          border-color: #e74c3c;
          box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        /* Budget field specific styling */
        .form-group input[type="number"] {
          -moz-appearance: textfield; /* Firefox */
        }
        
        .form-group input[type="number"]::-webkit-outer-spin-button,
        .form-group input[type="number"]::-webkit-inner-spin-button {
          -webkit-appearance: none;
          margin: 0;
        }
        
        /* Prevent negative value styling */
        .form-group input[type="number"]:invalid {
          border-color: #e74c3c;
        }
        
        .error-text {
          color: #e74c3c;
          font-size: 13px;
          margin-top: 4px;
          display: block;
          font-weight: 500;
        }
        
        .field-info {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-top: 4px;
          font-size: 12px;
          color: #666;
        }
        
        .char-count {
          color: #666;
        }
        
        .char-count.warning {
          color: #f39c12;
          font-weight: 500;
        }
        
        .word-count {
          color: #666;
          font-style: italic;
        }
        
        /* Enhanced validation styling */
        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
          border-color: #e74c3c;
          background-color: #fdf2f2;
        }
        
        .form-group input.error:focus,
        .form-group select.error:focus,
        .form-group textarea.error:focus {
          border-color: #e74c3c;
          box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
          background-color: #fff;
        }
        
        /* Success state styling */
        .form-group input:valid:not(:placeholder-shown),
        .form-group textarea:valid:not(:placeholder-shown) {
          border-color: #27ae60;
        }
        
        .form-group input:valid:not(:placeholder-shown):focus,
        .form-group textarea:valid:not(:placeholder-shown):focus {
          border-color: #27ae60;
          box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
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