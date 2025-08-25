import React, { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { SessionCleanup } from '../utils/sessionCleanup';
import { HistoryManager } from '../utils/historyManager';

const SessionGuard = ({ children }) => {
  const { auth, logout } = useAuth();
  const location = useLocation();

  useEffect(() => {
    const currentPath = location.pathname;
    
    // If user navigates to login/auth pages, clear session
    const authPages = ['/login', '/register', '/auth', '/forgot'];
    if (authPages.includes(currentPath)) {
      SessionCleanup.cleanupOnLogin();
      HistoryManager.clearHistory();
    }
  }, [location.pathname]);

  // Handle page visibility change (when user switches tabs/windows)
  useEffect(() => {
    const cleanup = HistoryManager.handlePageVisibility(
      () => {
        // Page hidden - could implement additional security measures here
      },
      () => {
        // Page visible - check session validity
        const sessionTimestamp = localStorage.getItem('sessionTimestamp');
        if (sessionTimestamp && auth) {
          const timestamp = parseInt(sessionTimestamp);
          const now = Date.now();
          const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
          
          if (now - timestamp > SESSION_TIMEOUT) {
            logout();
          }
        }
      }
    );

    return cleanup;
  }, [auth, logout]);

  // Handle beforeunload event to clear session data
  useEffect(() => {
    const cleanup = SessionCleanup.setupBeforeUnloadHandler();
    return cleanup;
  }, []);

  // Setup security measures for protected pages
  useEffect(() => {
    const protectedRoutes = ['/dashboard', '/admin', '/supplier'];
    const isProtectedRoute = protectedRoutes.some(route => 
      location.pathname.startsWith(route)
    );

    if (isProtectedRoute && auth) {
      const cleanup = HistoryManager.disableDevTools();
      return cleanup;
    }
  }, [location.pathname, auth]);

  return children;
};

export default SessionGuard;