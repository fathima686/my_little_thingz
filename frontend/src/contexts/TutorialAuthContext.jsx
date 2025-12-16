import React, { createContext, useContext, useCallback, useState, useEffect } from 'react';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const TutorialAuthContext = createContext();

export const useTutorialAuth = () => {
  const context = useContext(TutorialAuthContext);
  if (!context) {
    throw new Error('useTutorialAuth must be used within a TutorialAuthProvider');
  }
  return context;
};

export const TutorialAuthProvider = ({ children }) => {
  const [tutorialAuth, setTutorialAuth] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const loadTutorialAuth = () => {
      try {
        const storedAuth = localStorage.getItem('tutorial_auth');
        if (storedAuth) {
          const auth = JSON.parse(storedAuth);
          setTutorialAuth(auth);
        }
      } catch (error) {
        console.error('Error loading tutorial auth:', error);
        localStorage.removeItem('tutorial_auth');
      } finally {
        setIsLoading(false);
      }
    };

    loadTutorialAuth();
  }, []);

  const tutorialLogin = useCallback(async (email, password) => {
    const trimmedEmail = (email || '').trim();

    const response = await fetch(`${API_BASE}/auth/login.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ email: trimmedEmail, password })
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.status !== 'success') {
      throw new Error(data.message || 'Login failed. Please check your credentials.');
    }

    const tutorialAuthData = {
      email: trimmedEmail,
      user_id: data.user_id ?? null,
      roles: data.roles ?? [],
      tutorial_session_id: Date.now().toString(),
      login_time: new Date().toISOString(),
      loginMethod: 'email'
    };
    
    localStorage.setItem('tutorial_auth', JSON.stringify(tutorialAuthData));
    setTutorialAuth(tutorialAuthData);
    return true;
  }, []);

  const tutorialGoogleLogin = useCallback((email, googleId, name) => {
    const tutorialAuthData = {
      email,
      googleId,
      name,
      tutorial_session_id: Date.now().toString(),
      login_time: new Date().toISOString(),
      loginMethod: 'google'
    };
    
    localStorage.setItem('tutorial_auth', JSON.stringify(tutorialAuthData));
    setTutorialAuth(tutorialAuthData);
    return true;
  }, []);

  const tutorialLogout = useCallback(() => {
    localStorage.removeItem('tutorial_auth');
    setTutorialAuth(null);
  }, []);

  const isTutorialAuthenticated = !!tutorialAuth;

  const value = {
    tutorialAuth,
    isLoading,
    tutorialLogin,
    tutorialGoogleLogin,
    tutorialLogout,
    isTutorialAuthenticated
  };

  return (
    <TutorialAuthContext.Provider value={value}>
      {children}
    </TutorialAuthContext.Provider>
  );
};
