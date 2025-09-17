import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { SessionCleanup } from '../utils/sessionCleanup';
import { HistoryManager } from '../utils/historyManager';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [auth, setAuth] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [sessionExpired, setSessionExpired] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  // Session timeout (30 minutes)
  const SESSION_TIMEOUT = 30 * 60 * 1000;
  
  // Check if current path is a protected route
  const isProtectedRoute = useCallback((path) => {
    const protectedRoutes = ['/dashboard', '/admin', '/supplier', '/cart', '/profile'];
    return protectedRoutes.some(route => path.startsWith(route));
  }, []);

  // Check if current path is an auth route
  const isAuthRoute = useCallback((path) => {
    const authRoutes = ['/login', '/register', '/auth', '/forgot'];
    return authRoutes.includes(path) || path === '/';
  }, []);

  // Load authentication data from localStorage
  const loadAuth = useCallback(() => {
    try {
      const authData = localStorage.getItem('auth');
      const sessionTimestamp = localStorage.getItem('sessionTimestamp');
      
      if (authData && sessionTimestamp) {
        const parsedAuth = JSON.parse(authData);
        const timestamp = parseInt(sessionTimestamp);
        const now = Date.now();
        
        // Check if session has expired
        if (now - timestamp > SESSION_TIMEOUT) {
          // Session expired
          clearAuth();
          setSessionExpired(true);
          return null;
        }
        
        // Update session timestamp
        localStorage.setItem('sessionTimestamp', now.toString());
        return parsedAuth;
      }
      return null;
    } catch (error) {
      console.error('Error loading auth data:', error);
      clearAuth();
      return null;
    }
  }, [SESSION_TIMEOUT]);

  // Clear authentication data
  const clearAuth = useCallback(() => {
    try {
      SessionCleanup.cleanupOnLogout();
    } catch (error) {
      console.error('Error clearing auth data:', error);
    }
  }, []);

  // Login function
  const login = useCallback((authData) => {
    try {
      const timestamp = Date.now();
      localStorage.setItem('auth', JSON.stringify(authData));
      localStorage.setItem('sessionTimestamp', timestamp.toString());
      setAuth(authData);
      setSessionExpired(false);
      
      // Determine redirect based on roles
      const roles = Array.isArray(authData.roles) 
        ? authData.roles.map(r => String(r).toLowerCase()) 
        : [];
      
      let redirectPath = '/dashboard';
      if (roles.includes('admin')) {
        redirectPath = '/admin';
      } else if (roles.includes('supplier')) {
        redirectPath = '/supplier';
      }
      
      // Replace current history entry to prevent back navigation
      navigate(redirectPath, { replace: true });
    } catch (error) {
      console.error('Error during login:', error);
    }
  }, [navigate]);

  // Logout function
  const logout = useCallback(() => {
    clearAuth();
    setAuth(null);
    setSessionExpired(false);
    
    // Navigate to login and replace history
    navigate('/login', { replace: true });
    
    // Additional security measures
    setTimeout(() => {
      HistoryManager.clearHistory();
      HistoryManager.disableForwardNavigation();
    }, 100);
  }, [navigate, clearAuth]);

  // Check authentication status
  const checkAuth = useCallback(() => {
    const authData = loadAuth();
    setAuth(authData);
    setIsLoading(false);
    return authData;
  }, [loadAuth]);

  // Handle browser navigation events
  useEffect(() => {
    const cleanup = SessionCleanup.setupPopstateHandler(!!auth, navigate);
    return cleanup;
  }, [auth, navigate]);

  // Handle page refresh and initial load
  useEffect(() => {
    const authData = checkAuth();
    
    // If on a protected route without auth, redirect to login
    if (isProtectedRoute(location.pathname) && !authData) {
      navigate('/login', { replace: true });
    }
    
    // If authenticated and on auth route, redirect to appropriate dashboard
    if (authData && isAuthRoute(location.pathname)) {
      const roles = Array.isArray(authData.roles) 
        ? authData.roles.map(r => String(r).toLowerCase()) 
        : [];
      
      let redirectPath = '/dashboard';
      if (roles.includes('admin')) {
        redirectPath = '/admin';
      } else if (roles.includes('supplier')) {
        redirectPath = '/supplier';
      }
      
      navigate(redirectPath, { replace: true });
    }
  }, [location.pathname, navigate, checkAuth, isProtectedRoute, isAuthRoute]);

  // Session activity tracking
  useEffect(() => {
    if (!auth) return;

    const cleanup = SessionCleanup.setupInactivityTimer(SESSION_TIMEOUT, () => {
      logout();
      setSessionExpired(true);
    });

    return cleanup;
  }, [auth, logout, SESSION_TIMEOUT]);

  // Setup secure navigation for protected pages
  useEffect(() => {
    if (isProtectedRoute(location.pathname)) {
      HistoryManager.setupSecureNavigation(location.pathname);
      SessionCleanup.preventPageCaching();
      SessionCleanup.createSecurityHeaders();
    }
  }, [location.pathname, isProtectedRoute]);

  const value = {
    auth,
    isLoading,
    sessionExpired,
    login,
    logout,
    checkAuth,
    isAuthenticated: !!auth,
    roles: auth?.roles || [],
    userId: auth?.user_id,
    userEmail: auth?.email
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};