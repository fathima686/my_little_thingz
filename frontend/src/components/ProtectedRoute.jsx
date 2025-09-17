import React, { useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const ProtectedRoute = ({ children, requiredRoles = [], fallbackPath = '/login' }) => {
  const { auth, isLoading, sessionExpired } = useAuth();
  const location = useLocation();

  // Prevent browser caching of protected routes
  // Allow normal browser back behavior; rely on auth guards for protection
  useEffect(() => {
    return () => {};
  }, [location.pathname]);

  // Show loading state while checking authentication
  if (isLoading) {
    return (
      <div style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        height: '100vh',
        flexDirection: 'column',
        gap: '1rem'
      }}>
        <div style={{
          width: '40px',
          height: '40px',
          border: '4px solid #f3f3f3',
          borderTop: '4px solid #3498db',
          borderRadius: '50%',
          animation: 'spin 1s linear infinite'
        }}></div>
        <p>Verifying authentication...</p>
        <style>{`
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `}</style>
      </div>
    );
  }

  // Show session expired message
  if (sessionExpired) {
    return (
      <div style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        height: '100vh',
        flexDirection: 'column',
        gap: '1rem',
        textAlign: 'center',
        padding: '2rem'
      }}>
        <h2 style={{ color: '#e74c3c' }}>Session Expired</h2>
        <p>Your session has expired for security reasons. Please log in again.</p>
        <button 
          onClick={() => window.location.href = '/login'}
          style={{
            padding: '0.75rem 1.5rem',
            backgroundColor: '#3498db',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer'
          }}
        >
          Go to Login
        </button>
      </div>
    );
  }

  // Redirect to login if not authenticated
  if (!auth) {
    return <Navigate to={fallbackPath} replace />;
  }

  // Check role-based access if required roles are specified
  if (requiredRoles.length > 0) {
    const userRoles = Array.isArray(auth.roles) 
      ? auth.roles.map(role => String(role).toLowerCase()) 
      : [];
    
    const hasRequiredRole = requiredRoles.some(role => 
      userRoles.includes(role.toLowerCase())
    );

    if (!hasRequiredRole) {
      // Redirect based on user's actual role
      if (userRoles.includes('admin')) {
        return <Navigate to="/admin" replace />;
      } else if (userRoles.includes('supplier')) {
        return <Navigate to="/supplier" replace />;
      } else if (userRoles.includes('customer')) {
        return <Navigate to="/dashboard" replace />;
      } else {
        return <Navigate to="/login" replace />;
      }
    }
  }

  // Render the protected component
  return children;
};

export default ProtectedRoute;