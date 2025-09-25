import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';

const NotifyContext = createContext({ notify: () => {} });

export function NotifyProvider({ children }) {
  const [toasts, setToasts] = useState([]);
  const idRef = useRef(1);

  const remove = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const notify = useCallback((message, type = 'info', duration = 4000) => {
    const id = idRef.current++;
    setToasts((prev) => [...prev, { id, message, type }]);
    if (duration > 0) {
      setTimeout(() => remove(id), duration);
    }
    return id;
  }, [remove]);

  // Globally override window.alert to use non-blocking toast notifications
  useEffect(() => {
    const originalAlert = window.alert;
    window.alert = (msg) => {
      const text = String(msg ?? '');
      const lower = text.toLowerCase();
      let kind = 'info';
      if (lower.includes('success')) kind = 'success';
      else if (lower.includes('fail') || lower.includes('error')) kind = 'error';
      else if (lower.includes('warn')) kind = 'warning';
      notify(text, kind, 4000);
    };
    return () => { window.alert = originalAlert; };
  }, [notify]);

  const value = useMemo(() => ({ notify, remove }), [notify, remove]);

  return (
    <NotifyContext.Provider value={value}>
      {children}
      {/* Toast container */}
      <div style={{
        position: 'fixed',
        top: 16,
        right: 16,
        display: 'flex',
        flexDirection: 'column',
        gap: 8,
        zIndex: 9999,
        maxWidth: '92vw'
      }}>
        {toasts.map((t) => (
          <div key={t.id}
               role="alert"
               className={`alert alert-${mapType(t.type)}`}
               style={{ boxShadow: '0 8px 24px rgba(0,0,0,.15)', minWidth: 240 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
              <span>{t.message}</span>
              <button type="button" className="btn-close" aria-label="Close"
                      onClick={() => remove(t.id)}
                      style={{ filter: 'invert(0.5)' }} />
            </div>
          </div>
        ))}
      </div>
    </NotifyContext.Provider>
  );
}

function mapType(type) {
  switch (type) {
    case 'success': return 'success';
    case 'error': return 'danger';
    case 'warning': return 'warning';
    default: return 'info';
  }
}

export function useNotify() {
  return useContext(NotifyContext).notify;
}