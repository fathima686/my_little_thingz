import React, { useState, useEffect } from 'react';
import { LuX, LuCalendar, LuClock, LuLink, LuUsers, LuBookOpen } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function CreateLiveSessionModal({ isOpen, onClose, onSuccess, session = null, auth, isAdmin = false, adminHeader = {} }) {
  const [subjects, setSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [formData, setFormData] = useState({
    subject_id: '',
    title: '',
    description: '',
    google_meet_link: '',
    scheduled_date: '',
    scheduled_time: '',
    duration_minutes: 60,
    max_participants: 50
  });

  // Validation functions
  const validateField = (name, value) => {
    const newErrors = { ...errors };
    
    switch (name) {
      case 'subject_id':
        if (!value || value === '') {
          newErrors.subject_id = 'Please select a subject';
        } else {
          delete newErrors.subject_id;
        }
        break;
        
      case 'title':
        if (!value || value.trim() === '') {
          newErrors.title = 'Title is required';
        } else if (value.trim().length < 5) {
          newErrors.title = 'Title must be at least 5 characters long';
        } else if (value.trim().length > 100) {
          newErrors.title = 'Title must not exceed 100 characters';
        } else if (!/^[a-zA-Z0-9\s\-\.\,\:\(\)]+$/.test(value.trim())) {
          newErrors.title = 'Title contains invalid characters';
        } else {
          delete newErrors.title;
        }
        break;
        
      case 'description':
        if (value && value.trim().length > 500) {
          newErrors.description = 'Description must not exceed 500 characters';
        } else {
          delete newErrors.description;
        }
        break;
        
      case 'google_meet_link':
        if (!value || value.trim() === '') {
          newErrors.google_meet_link = 'Google Meet link is required';
        } else {
          const meetLinkPattern = /^https:\/\/meet\.google\.com\/[a-z0-9\-]+$/;
          if (!meetLinkPattern.test(value.trim())) {
            newErrors.google_meet_link = 'Please enter a valid Google Meet link (e.g., https://meet.google.com/abc-defg-hij)';
          } else {
            delete newErrors.google_meet_link;
          }
        }
        break;
        
      case 'scheduled_date':
        if (!value) {
          newErrors.scheduled_date = 'Date is required';
        } else {
          const selectedDate = new Date(value);
          const today = new Date();
          today.setHours(0, 0, 0, 0);
          
          if (selectedDate < today) {
            newErrors.scheduled_date = 'Date cannot be in the past';
          } else {
            const maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 6); // 6 months from now
            if (selectedDate > maxDate) {
              newErrors.scheduled_date = 'Date cannot be more than 6 months in the future';
            } else {
              delete newErrors.scheduled_date;
            }
          }
        }
        break;
        
      case 'scheduled_time':
        if (!value) {
          newErrors.scheduled_time = 'Time is required';
        } else {
          // If date is today, check if time is in the future
          if (formData.scheduled_date === new Date().toISOString().split('T')[0]) {
            const now = new Date();
            const [hours, minutes] = value.split(':');
            const selectedTime = new Date();
            selectedTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);
            
            if (selectedTime <= now) {
              newErrors.scheduled_time = 'Time must be in the future for today\'s date';
            } else {
              delete newErrors.scheduled_time;
            }
          } else {
            delete newErrors.scheduled_time;
          }
        }
        break;
        
      case 'duration_minutes':
        const duration = parseInt(value);
        if (!duration || isNaN(duration)) {
          newErrors.duration_minutes = 'Duration is required';
        } else if (duration < 15) {
          newErrors.duration_minutes = 'Duration must be at least 15 minutes';
        } else if (duration > 240) {
          newErrors.duration_minutes = 'Duration cannot exceed 240 minutes (4 hours)';
        } else {
          delete newErrors.duration_minutes;
        }
        break;
        
      case 'max_participants':
        const participants = parseInt(value);
        if (!participants || isNaN(participants)) {
          newErrors.max_participants = 'Max participants is required';
        } else if (participants < 1) {
          newErrors.max_participants = 'Must allow at least 1 participant';
        } else if (participants > 100) {
          newErrors.max_participants = 'Cannot exceed 100 participants';
        } else {
          delete newErrors.max_participants;
        }
        break;
        
      default:
        break;
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const validateForm = () => {
    const fields = ['subject_id', 'title', 'google_meet_link', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'max_participants'];
    let isValid = true;
    
    fields.forEach(field => {
      const fieldValid = validateField(field, formData[field]);
      if (!fieldValid) isValid = false;
    });
    
    // Additional cross-field validation
    if (formData.scheduled_date && formData.scheduled_time) {
      const sessionDateTime = new Date(`${formData.scheduled_date}T${formData.scheduled_time}`);
      const now = new Date();
      
      if (sessionDateTime <= now) {
        setErrors(prev => ({
          ...prev,
          scheduled_time: 'Session date and time must be in the future'
        }));
        isValid = false;
      }
    }
    
    return isValid;
  };

  const handleInputChange = (name, value) => {
    // Sanitize input based on field type
    let sanitizedValue = value;
    
    switch (name) {
      case 'title':
        // Remove potentially harmful characters but keep basic punctuation
        sanitizedValue = value.replace(/[<>\"'&]/g, '').substring(0, 100);
        break;
      case 'description':
        sanitizedValue = value.replace(/[<>\"'&]/g, '').substring(0, 500);
        break;
      case 'google_meet_link':
        // Ensure it starts with https://meet.google.com/
        sanitizedValue = value.trim().toLowerCase();
        break;
      case 'duration_minutes':
      case 'max_participants':
        // Ensure numeric values are within bounds
        const numValue = parseInt(value);
        if (!isNaN(numValue)) {
          sanitizedValue = Math.max(0, numValue);
        }
        break;
      default:
        sanitizedValue = typeof value === 'string' ? value.trim() : value;
        break;
    }
    
    setFormData({ ...formData, [name]: sanitizedValue });
    
    // Validate field if it has been touched
    if (touched[name]) {
      validateField(name, sanitizedValue);
    }
  };

  const handleBlur = (name) => {
    setTouched({ ...touched, [name]: true });
    validateField(name, formData[name]);
  };

  useEffect(() => {
    if (isOpen) {
      fetchSubjects();
      if (session) {
        setFormData({
          subject_id: session.subject_id || '',
          title: session.title || '',
          description: session.description || '',
          google_meet_link: session.google_meet_link || '',
          scheduled_date: session.scheduled_date || '',
          scheduled_time: session.scheduled_time || '',
          duration_minutes: session.duration_minutes || 60,
          max_participants: session.max_participants || 50
        });
      } else {
        resetForm();
      }
    }
  }, [isOpen, session]);

  const fetchSubjects = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/live-subjects.php`);
      const data = await res.json();
      if (data.status === 'success') {
        setSubjects(data.subjects);
      }
    } catch (error) {
      console.error('Failed to fetch subjects:', error);
    }
  };

  const resetForm = () => {
    setFormData({
      subject_id: '',
      title: '',
      description: '',
      google_meet_link: '',
      scheduled_date: '',
      scheduled_time: '',
      duration_minutes: 60,
      max_participants: 50
    });
    setErrors({});
    setTouched({});
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Mark all fields as touched for validation display
    const allFields = ['subject_id', 'title', 'google_meet_link', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'max_participants'];
    const newTouched = {};
    allFields.forEach(field => newTouched[field] = true);
    setTouched(newTouched);
    
    // Validate entire form
    if (!validateForm()) {
      window.dispatchEvent(new CustomEvent('toast', {
        detail: { type: 'error', message: 'Please fix all validation errors before submitting' }
      }));
      return;
    }
    
    setLoading(true);

    try {
      const url = session 
        ? `${API_BASE}/teacher/live-sessions.php`
        : `${API_BASE}/teacher/live-sessions.php`;
      
      const method = session ? 'PUT' : 'POST';
      
      const body = session 
        ? { ...formData, id: session.id }
        : formData;

      // Use admin headers if admin, otherwise use teacher headers
      const headers = {
        'Content-Type': 'application/json',
        ...(isAdmin ? adminHeader : {}),
        'X-User-ID': auth?.user_id || '',
      };
      if (auth?.token) {
        headers['Authorization'] = `Bearer ${auth.token}`;
      }
      
      const res = await fetch(url, {
        method,
        headers,
        body: JSON.stringify(body)
      });

      const data = await res.json();

      if (data.status === 'success') {
        window.dispatchEvent(new CustomEvent('toast', {
          detail: { type: 'success', message: session ? 'Session updated successfully' : 'Session created successfully' }
        }));
        onSuccess();
        onClose();
        resetForm();
      } else {
        window.dispatchEvent(new CustomEvent('toast', {
          detail: { type: 'error', message: data.message || 'Failed to save session' }
        }));
      }
    } catch (error) {
      window.dispatchEvent(new CustomEvent('toast', {
        detail: { type: 'error', message: 'Network error. Please try again.' }
      }));
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>{session ? 'Edit Live Session' : 'Create Live Session'}</h2>
          <button className="close-btn" onClick={onClose}><LuX /></button>
        </div>

        <form onSubmit={handleSubmit} className="live-session-form">
          <div className="form-group">
            <label>
              <LuBookOpen size={18} />
              Subject *
            </label>
            <select
              value={formData.subject_id}
              onChange={(e) => handleInputChange('subject_id', e.target.value)}
              onBlur={() => handleBlur('subject_id')}
              className={errors.subject_id ? 'error' : ''}
              required
            >
              <option value="">Select a subject</option>
              {subjects.map(subject => (
                <option key={subject.id} value={subject.id}>
                  {subject.name}
                </option>
              ))}
            </select>
            {errors.subject_id && (
              <div className="error-message">
                ⚠️ {errors.subject_id}
              </div>
            )}
          </div>

          <div className="form-group">
            <label>Title *</label>
            <input
              type="text"
              value={formData.title}
              onChange={(e) => handleInputChange('title', e.target.value)}
              onBlur={() => handleBlur('title')}
              placeholder="e.g., Introduction to Hand Embroidery"
              className={errors.title ? 'error' : ''}
              maxLength="100"
              required
            />
            <div className="input-info">
              <span className={`char-count ${formData.title.length > 90 ? 'warning' : ''}`}>
                {formData.title.length}/100 characters
              </span>
            </div>
            {errors.title && (
              <div className="error-message">
                ⚠️ {errors.title}
              </div>
            )}
          </div>

          <div className="form-group">
            <label>Description</label>
            <textarea
              value={formData.description}
              onChange={(e) => handleInputChange('description', e.target.value)}
              onBlur={() => handleBlur('description')}
              placeholder="Brief description of what will be covered..."
              className={errors.description ? 'error' : ''}
              maxLength="500"
              rows="3"
            />
            <div className="input-info">
              <span className={`char-count ${formData.description.length > 450 ? 'warning' : ''}`}>
                {formData.description.length}/500 characters
              </span>
            </div>
            {errors.description && (
              <div className="error-message">
                ⚠️ {errors.description}
              </div>
            )}
          </div>

          <div className="form-group">
            <label>
              <LuLink size={18} />
              Google Meet Link *
            </label>
            <input
              type="url"
              value={formData.google_meet_link}
              onChange={(e) => handleInputChange('google_meet_link', e.target.value)}
              onBlur={() => handleBlur('google_meet_link')}
              placeholder="https://meet.google.com/xxx-xxxx-xxx"
              className={errors.google_meet_link ? 'error' : ''}
              required
            />
            <div className="input-info">
              Must be a valid Google Meet link
            </div>
            {errors.google_meet_link && (
              <div className="error-message">
                ⚠️ {errors.google_meet_link}
              </div>
            )}
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>
                <LuCalendar size={18} />
                Date *
              </label>
              <input
                type="date"
                value={formData.scheduled_date}
                onChange={(e) => handleInputChange('scheduled_date', e.target.value)}
                onBlur={() => handleBlur('scheduled_date')}
                min={new Date().toISOString().split('T')[0]}
                max={new Date(Date.now() + 180 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]} // 6 months from now
                className={errors.scheduled_date ? 'error' : ''}
                required
              />
              {errors.scheduled_date && (
                <div className="error-message">
                  ⚠️ {errors.scheduled_date}
                </div>
              )}
            </div>

            <div className="form-group">
              <label>
                <LuClock size={18} />
                Time *
              </label>
              <input
                type="time"
                value={formData.scheduled_time}
                onChange={(e) => handleInputChange('scheduled_time', e.target.value)}
                onBlur={() => handleBlur('scheduled_time')}
                className={errors.scheduled_time ? 'error' : ''}
                required
              />
              {errors.scheduled_time && (
                <div className="error-message">
                  ⚠️ {errors.scheduled_time}
                </div>
              )}
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Duration (minutes) *</label>
              <input
                type="number"
                value={formData.duration_minutes}
                onChange={(e) => handleInputChange('duration_minutes', e.target.value)}
                onBlur={() => handleBlur('duration_minutes')}
                min="15"
                max="240"
                step="15"
                className={errors.duration_minutes ? 'error' : ''}
                required
              />
              <div className="input-info">
                Between 15-240 minutes (15 min increments recommended)
              </div>
              {errors.duration_minutes && (
                <div className="error-message">
                  ⚠️ {errors.duration_minutes}
                </div>
              )}
            </div>

            <div className="form-group">
              <label>
                <LuUsers size={18} />
                Max Participants *
              </label>
              <input
                type="number"
                value={formData.max_participants}
                onChange={(e) => handleInputChange('max_participants', e.target.value)}
                onBlur={() => handleBlur('max_participants')}
                min="1"
                max="100"
                className={errors.max_participants ? 'error' : ''}
                required
              />
              <div className="input-info">
                1-100 participants
              </div>
              {errors.max_participants && (
                <div className="error-message">
                  ⚠️ {errors.max_participants}
                </div>
              )}
            </div>
          </div>

          <div className="form-actions">
            <button type="button" className="btn btn-outline" onClick={onClose}>
              Cancel
            </button>
            <button 
              type="submit" 
              className="btn btn-primary" 
              disabled={loading || Object.keys(errors).length > 0}
            >
              {loading ? 'Saving...' : (session ? 'Update Session' : 'Create Session')}
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
          background: rgba(0, 0, 0, 0.5);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          padding: 20px;
        }

        .modal-content {
          background: white;
          border-radius: 12px;
          max-width: 600px;
          width: 100%;
          max-height: 90vh;
          overflow-y: auto;
          box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 20px 24px;
          border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h2 {
          margin: 0;
          font-size: 20px;
          font-weight: 600;
        }

        .close-btn {
          background: none;
          border: none;
          font-size: 24px;
          color: #6b7280;
          cursor: pointer;
          padding: 4px;
          border-radius: 4px;
        }

        .close-btn:hover {
          background: #f3f4f6;
        }

        .live-session-form {
          padding: 24px;
        }

        .form-group {
          margin-bottom: 20px;
        }

        .form-group label {
          display: flex;
          align-items: center;
          gap: 8px;
          margin-bottom: 8px;
          font-weight: 500;
          color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
          width: 100%;
          padding: 10px 12px;
          border: 1px solid #d1d5db;
          border-radius: 8px;
          font-size: 14px;
          transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
          outline: none;
          border-color: #667eea;
          box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
          border-color: #ef4444;
          box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .error-message {
          display: flex;
          align-items: center;
          gap: 6px;
          margin-top: 6px;
          color: #ef4444;
          font-size: 13px;
          font-weight: 500;
        }

        .input-info {
          margin-top: 4px;
          font-size: 12px;
          color: #6b7280;
        }

        .char-count {
          font-size: 12px;
          color: #6b7280;
        }

        .char-count.warning {
          color: #f59e0b;
          font-weight: 500;
        }

        .form-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 16px;
        }

        .form-actions {
          display: flex;
          justify-content: flex-end;
          gap: 12px;
          margin-top: 24px;
          padding-top: 20px;
          border-top: 1px solid #e5e7eb;
        }

        .btn {
          padding: 10px 20px;
          border-radius: 8px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.2s;
          border: none;
        }

        .btn-outline {
          background: white;
          border: 1px solid #d1d5db;
          color: #374151;
        }

        .btn-outline:hover {
          background: #f9fafb;
        }

        .btn-primary {
          background: #667eea;
          color: white;
        }

        .btn-primary:hover:not(:disabled) {
          background: #5568d3;
        }

        .btn-primary:disabled {
          opacity: 0.6;
          cursor: not-allowed;
          background: #9ca3af;
        }

        .btn-primary:disabled:hover {
          background: #9ca3af;
        }
      `}</style>
    </div>
  );
}

