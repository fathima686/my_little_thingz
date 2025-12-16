import React from 'react';
import { Navigate } from 'react-router-dom';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';

const TutorialProtectedRoute = ({ children }) => {
  const { isTutorialAuthenticated, isLoading } = useTutorialAuth();

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
          borderTop: '4px solid #667eea',
          borderRadius: '50%',
          animation: 'spin 1s linear infinite'
        }} />
        <p>Loading tutorials...</p>
        <style>{`
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `}</style>
      </div>
    );
  }

  if (!isTutorialAuthenticated) {
    return <Navigate to="/tutorial-login" replace />;
  }

  return children;
};

export default TutorialProtectedRoute;
