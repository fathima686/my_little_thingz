import React, { useState, useEffect } from 'react';
import { LuCalendar, LuClock, LuUsers, LuVideo, LuFilter, LuExternalLink, LuCheck } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function LiveSessionsList({ auth, isTeacher = false }) {
  const [sessions, setSessions] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [selectedSubject, setSelectedSubject] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchSubjects();
    fetchSessions();
  }, [selectedSubject, isTeacher]);

  const fetchSubjects = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/live-subjects.php`);
      
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      
      const text = await res.text();
      
      let data;
      try {
        data = JSON.parse(text);
      } catch (parseError) {
        console.error('Subjects API response is not valid JSON:', text);
        return; // Fail silently for subjects
      }
      
      if (data.status === 'success') {
        setSubjects(data.subjects);
      }
    } catch (error) {
      console.error('Failed to fetch subjects:', error);
    }
  };

  const fetchSessions = async () => {
    setLoading(true);
    try {
      const url = isTeacher
        ? `${API_BASE}/teacher/live-sessions.php${selectedSubject ? `?subject_id=${selectedSubject}` : ''}`
        : `${API_BASE}/customer/live-sessions.php${selectedSubject ? `?subject_id=${selectedSubject}` : ''}`;

      const headers = isTeacher
        ? {
            'X-User-ID': auth?.user_id || '',
            'Authorization': `Bearer ${auth?.token || ''}`
          }
        : {
            'X-User-ID': auth?.user_id || ''
          };

      const res = await fetch(url, { headers });
      
      // Check if response is ok
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      
      // Get response text first
      const text = await res.text();
      
      // Try to parse as JSON
      let data;
      try {
        data = JSON.parse(text);
      } catch (parseError) {
        console.error('Response is not valid JSON:', text);
        throw new Error('Server returned invalid JSON response. Please check if the database tables are set up correctly.');
      }

      if (data.status === 'success') {
        setSessions(data.sessions);
      } else {
        console.error('API error:', data.message);
        throw new Error(data.message || 'Unknown API error');
      }
    } catch (error) {
      console.error('Failed to fetch sessions:', error);
      // You could set an error state here to show to the user
    } finally {
      setLoading(false);
    }
  };

  const formatDateTime = (date, time) => {
    const dateObj = new Date(`${date}T${time}`);
    return dateObj.toLocaleString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusBadge = (status) => {
    const badges = {
      scheduled: { text: 'Scheduled', color: '#3b82f6' },
      live: { text: 'Live Now', color: '#10b981' },
      completed: { text: 'Completed', color: '#6b7280' },
      cancelled: { text: 'Cancelled', color: '#ef4444' }
    };
    const badge = badges[status] || badges.scheduled;
    return (
      <span className="status-badge" style={{ background: `${badge.color}20`, color: badge.color }}>
        {badge.text}
      </span>
    );
  };

  return (
    <div className="live-sessions-container">
      <div className="sessions-header">
        <h2>Live Teaching Sessions</h2>
        <div className="filter-controls">
          <LuFilter size={18} />
          <select
            value={selectedSubject || ''}
            onChange={(e) => setSelectedSubject(e.target.value || null)}
            className="subject-filter"
          >
            <option value="">All Subjects</option>
            {subjects.map(subject => (
              <option key={subject.id} value={subject.id}>
                {subject.name} ({subject.session_count || 0})
              </option>
            ))}
          </select>
        </div>
      </div>

      {loading ? (
        <div className="loading">Loading sessions...</div>
      ) : sessions.length === 0 ? (
        <div className="empty-state">
          <p>No live sessions available{selectedSubject ? ' for this subject' : ''}.</p>
        </div>
      ) : (
        <div className="sessions-grid">
          {sessions.map(session => {
            const isPast = new Date(`${session.scheduled_date}T${session.scheduled_time}`) < new Date();
            const canJoin = session.status === 'live' || (session.status === 'scheduled' && !isPast);

            return (
              <div key={session.id} className="session-card">
                <div className="session-header">
                  <div className="subject-tag" style={{ background: `${session.subject_color}20`, color: session.subject_color }}>
                    {session.subject_name}
                  </div>
                  {getStatusBadge(session.status)}
                </div>

                <h3 className="session-title">{session.title}</h3>
                {session.description && (
                  <p className="session-description">{session.description}</p>
                )}

                <div className="session-details">
                  <div className="detail-item">
                    <LuCalendar size={16} />
                    <span>{formatDateTime(session.scheduled_date, session.scheduled_time)}</span>
                  </div>
                  <div className="detail-item">
                    <LuClock size={16} />
                    <span>{session.duration_minutes} minutes</span>
                  </div>
                  <div className="detail-item">
                    <LuUsers size={16} />
                    <span>{session.registered_count || 0} / {session.max_participants} registered</span>
                  </div>
                </div>

                <div className="session-actions">
                  {isTeacher ? (
                    <a
                      href={session.google_meet_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn btn-primary"
                    >
                      <LuVideo size={18} />
                      Open Meet Link
                    </a>
                  ) : (
                    <>
                      {canJoin && (
                        <a
                          href={session.google_meet_link}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="btn btn-primary"
                        >
                          <LuExternalLink size={18} />
                          Join Session
                        </a>
                      )}
                    </>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      <style>{`
        .live-sessions-container {
          padding: 20px;
        }

        .sessions-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 24px;
        }

        .sessions-header h2 {
          margin: 0;
          font-size: 24px;
          font-weight: 600;
        }

        .filter-controls {
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .subject-filter {
          padding: 8px 12px;
          border: 1px solid #d1d5db;
          border-radius: 8px;
          font-size: 14px;
        }

        .loading, .empty-state {
          text-align: center;
          padding: 40px;
          color: #6b7280;
        }

        .sessions-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
          gap: 20px;
        }

        .session-card {
          background: white;
          border: 1px solid #e5e7eb;
          border-radius: 12px;
          padding: 20px;
          transition: all 0.2s;
        }

        .session-card:hover {
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          transform: translateY(-2px);
        }

        .session-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 12px;
        }

        .subject-tag {
          padding: 4px 12px;
          border-radius: 6px;
          font-size: 12px;
          font-weight: 500;
        }

        .status-badge {
          padding: 4px 10px;
          border-radius: 6px;
          font-size: 11px;
          font-weight: 600;
          text-transform: uppercase;
        }

        .session-title {
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 600;
          color: #111827;
        }

        .session-description {
          margin: 0 0 16px 0;
          color: #6b7280;
          font-size: 14px;
          line-height: 1.5;
        }

        .session-details {
          display: flex;
          flex-direction: column;
          gap: 8px;
          margin-bottom: 16px;
          padding: 12px;
          background: #f9fafb;
          border-radius: 8px;
        }

        .detail-item {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 13px;
          color: #374151;
        }

        .session-actions {
          display: flex;
          gap: 8px;
        }

        .btn {
          flex: 1;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          padding: 10px 16px;
          border-radius: 8px;
          font-weight: 500;
          text-decoration: none;
          transition: all 0.2s;
          border: none;
          cursor: pointer;
          font-size: 14px;
        }

        .btn-primary {
          background: #667eea;
          color: white;
        }

        .btn-primary:hover {
          background: #5568d3;
        }

        .btn-outline {
          background: white;
          border: 1px solid #d1d5db;
          color: #374151;
        }

        .btn-outline:hover:not(:disabled) {
          background: #f9fafb;
        }

        .btn:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        .registered-badge {
          flex: 1;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          padding: 10px 16px;
          background: #d1fae5;
          color: #065f46;
          border-radius: 8px;
          font-weight: 500;
          font-size: 14px;
        }
      `}</style>
    </div>
  );
}

