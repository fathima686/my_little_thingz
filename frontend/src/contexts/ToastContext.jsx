import React, { createContext, useContext, useCallback, useState, useEffect } from 'react';

const ToastContext = createContext();

export const useToast = () => {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within ToastProvider');
  return ctx;
};

let idCounter = 1;

export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);

  const remove = useCallback((id) => {
    setToasts((prev) => prev.filter(t => t.id !== id));
  }, []);

  const show = useCallback((message, options = {}) => {
    const id = idCounter++;
    const toast = { id, message, type: options.type || 'info', duration: options.duration ?? 3000 };
    setToasts((prev) => [...prev, toast]);
    if (toast.duration > 0) {
      setTimeout(() => remove(id), toast.duration);
    }
    return id;
  }, [remove]);

  // Listen to global 'toast' CustomEvents for convenience
  useEffect(() => {
    const handler = (e) => {
      const d = e.detail || {};
      const type = d.type || 'info';
      const message = d.message || '';
      const duration = d.duration;
      show(message, { type, duration });
    };
    window.addEventListener('toast', handler);
    return () => window.removeEventListener('toast', handler);
  }, [show]);

  const value = {
    show,
    success: (m, o) => show(m, { ...o, type: 'success' }),
    error: (m, o) => show(m, { ...o, type: 'error' }),
    info: (m, o) => show(m, { ...o, type: 'info' }),
    warn: (m, o) => show(m, { ...o, type: 'warning' }),
  };

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="toast-container" aria-live="polite" aria-atomic="true">
        {toasts.map(t => (
          <div key={t.id} className={`toast toast-${t.type}`} role="status">
            <span>{t.message}</span>
            <button className="toast-close" onClick={() => remove(t.id)} aria-label="Close">×</button>
          </div>
        ))}
      </div>
      <style>{`
        .toast-container { position: fixed; top: 16px; right: 16px; display: grid; gap: 8px; z-index: 2000; }
        .toast { background: #222; color: #fff; padding: 10px 12px; border-radius: 8px; box-shadow: 0 6px 18px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 10px; }
        .toast-success { background: #155724; }
        .toast-error { background: #7f1d1d; }
        .toast-info { background: #1e40af; }
        .toast-warning { background: #8a6d3b; }
        .toast-close { background: transparent; border: none; color: inherit; font-size: 18px; cursor: pointer; }
      `}</style>
    </ToastContext.Provider>
  );
};