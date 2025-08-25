import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';

const SessionDemo = () => {
  const { auth, logout, sessionExpired } = useAuth();
  const [sessionInfo, setSessionInfo] = useState({});

  useEffect(() => {
    const updateSessionInfo = () => {
      const sessionTimestamp = localStorage.getItem('sessionTimestamp');
      const authData = localStorage.getItem('auth');
      
      setSessionInfo({
        isAuthenticated: !!auth,
        sessionTimestamp: sessionTimestamp ? new Date(parseInt(sessionTimestamp)).toLocaleString() : 'None',
        authData: authData ? 'Present' : 'None',
        sessionExpired,
        userRoles: auth?.roles || [],
        userId: auth?.user_id || 'None'
      });
    };

    updateSessionInfo();
    const interval = setInterval(updateSessionInfo, 1000);
    
    return () => clearInterval(interval);
  }, [auth, sessionExpired]);

  const testBackNavigation = () => {
    alert('Try using the browser back button after clicking OK. The session should prevent navigation to protected pages.');
  };

  const testSessionExpiry = () => {
    // Simulate session expiry by setting an old timestamp
    const oldTimestamp = Date.now() - (31 * 60 * 1000); // 31 minutes ago
    localStorage.setItem('sessionTimestamp', oldTimestamp.toString());
    alert('Session timestamp set to 31 minutes ago. Refresh the page or wait for the next activity check.');
  };

  const clearSession = () => {
    logout();
  };

  return (
    <div style={{
      position: 'fixed',
      top: '10px',
      right: '10px',
      background: 'white',
      border: '2px solid #ccc',
      borderRadius: '8px',
      padding: '15px',
      maxWidth: '300px',
      fontSize: '12px',
      zIndex: 9999,
      boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
    }}>
      <h4 style={{ margin: '0 0 10px 0', color: '#333' }}>Session Status</h4>
      
      <div style={{ marginBottom: '10px' }}>
        <strong>Authenticated:</strong> {sessionInfo.isAuthenticated ? '✅ Yes' : '❌ No'}
      </div>
      
      <div style={{ marginBottom: '10px' }}>
        <strong>Session Expired:</strong> {sessionInfo.sessionExpired ? '⚠️ Yes' : '✅ No'}
      </div>
      
      <div style={{ marginBottom: '10px' }}>
        <strong>User ID:</strong> {sessionInfo.userId}
      </div>
      
      <div style={{ marginBottom: '10px' }}>
        <strong>Roles:</strong> {sessionInfo.userRoles?.join(', ') || 'None'}
      </div>
      
      <div style={{ marginBottom: '10px' }}>
        <strong>Last Activity:</strong> {sessionInfo.sessionTimestamp}
      </div>
      
      <div style={{ marginBottom: '10px' }}>
        <strong>Auth Data:</strong> {sessionInfo.authData}
      </div>

      <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
        <button 
          onClick={testBackNavigation}
          style={{
            padding: '5px 10px',
            fontSize: '11px',
            backgroundColor: '#007bff',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer'
          }}
        >
          Test Back Navigation
        </button>
        
        <button 
          onClick={testSessionExpiry}
          style={{
            padding: '5px 10px',
            fontSize: '11px',
            backgroundColor: '#ffc107',
            color: 'black',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer'
          }}
        >
          Test Session Expiry
        </button>
        
        <button 
          onClick={clearSession}
          style={{
            padding: '5px 10px',
            fontSize: '11px',
            backgroundColor: '#dc3545',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer'
          }}
        >
          Clear Session
        </button>
      </div>
    </div>
  );
};

export default SessionDemo;