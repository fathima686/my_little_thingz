import React, { useState, useEffect } from 'react';
import { LuX, LuCalendar, LuClock, LuLink, LuUsers, LuBookOpen } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function CreateLiveSessionModal({ isOpen, onClose, onSuccess, session = null, auth, isAdmin = false, adminHeader = {} }) {
  const [subjects, setSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
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
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
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
              onChange={(e) => setFormData({ ...formData, subject_id: e.target.value })}
              required
            >
              <option value="">Select a subject</option>
              {subjects.map(subject => (
                <option key={subject.id} value={subject.id}>
                  {subject.name}
                </option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label>Title *</label>
            <input
              type="text"
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              placeholder="e.g., Introduction to Hand Embroidery"
              required
            />
          </div>

          <div className="form-group">
            <label>Description</label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Brief description of what will be covered..."
              rows="3"
            />
          </div>

          <div className="form-group">
            <label>
              <LuLink size={18} />
              Google Meet Link *
            </label>
            <input
              type="url"
              value={formData.google_meet_link}
              onChange={(e) => setFormData({ ...formData, google_meet_link: e.target.value })}
              placeholder="https://meet.google.com/xxx-xxxx-xxx"
              required
            />
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
                onChange={(e) => setFormData({ ...formData, scheduled_date: e.target.value })}
                min={new Date().toISOString().split('T')[0]}
                required
              />
            </div>

            <div className="form-group">
              <label>
                <LuClock size={18} />
                Time *
              </label>
              <input
                type="time"
                value={formData.scheduled_time}
                onChange={(e) => setFormData({ ...formData, scheduled_time: e.target.value })}
                required
              />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Duration (minutes)</label>
              <input
                type="number"
                value={formData.duration_minutes}
                onChange={(e) => setFormData({ ...formData, duration_minutes: parseInt(e.target.value) || 60 })}
                min="15"
                max="240"
              />
            </div>

            <div className="form-group">
              <label>
                <LuUsers size={18} />
                Max Participants
              </label>
              <input
                type="number"
                value={formData.max_participants}
                onChange={(e) => setFormData({ ...formData, max_participants: parseInt(e.target.value) || 50 })}
                min="1"
                max="100"
              />
            </div>
          </div>

          <div className="form-actions">
            <button type="button" className="btn btn-outline" onClick={onClose}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
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
        }
      `}</style>
    </div>
  );
}

