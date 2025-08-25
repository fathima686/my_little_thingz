// Session cleanup utilities

export class SessionCleanup {
  static cleanupOnLogin() {
    // Clear all existing session data when user navigates to login
    try {
      localStorage.removeItem('auth');
      localStorage.removeItem('sessionTimestamp');
      sessionStorage.clear();
      
      // Clear any other app-specific storage
      const keysToRemove = [];
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && (key.startsWith('user_') || key.startsWith('session_') || key.startsWith('auth_'))) {
          keysToRemove.push(key);
        }
      }
      keysToRemove.forEach(key => localStorage.removeItem(key));
      
    } catch (error) {
      console.warn('Error during session cleanup:', error);
    }
  }

  static cleanupOnLogout() {
    // Comprehensive cleanup on logout
    this.cleanupOnLogin();
    
    // Clear browser history
    this.clearBrowserHistory();
    
    // Prevent caching
    this.preventPageCaching();
  }

  static clearBrowserHistory() {
    try {
      // Replace current history entry
      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, '/login');
        
        // Push multiple login entries to prevent back navigation
        for (let i = 0; i < 5; i++) {
          window.history.pushState(null, null, '/login');
        }
      }
    } catch (error) {
      console.warn('Error clearing browser history:', error);
    }
  }

  static preventPageCaching() {
    try {
      // Set cache control headers via meta tags
      const metaTags = [
        { httpEquiv: 'Cache-Control', content: 'no-cache, no-store, must-revalidate' },
        { httpEquiv: 'Pragma', content: 'no-cache' },
        { httpEquiv: 'Expires', content: '0' }
      ];

      metaTags.forEach(({ httpEquiv, content }) => {
        let meta = document.querySelector(`meta[http-equiv="${httpEquiv}"]`);
        if (!meta) {
          meta = document.createElement('meta');
          meta.httpEquiv = httpEquiv;
          document.head.appendChild(meta);
        }
        meta.content = content;
      });
    } catch (error) {
      console.warn('Error setting cache control:', error);
    }
  }

  static setupBeforeUnloadHandler() {
    // Clean up when user closes tab or navigates away
    const handleBeforeUnload = (event) => {
      const currentPath = window.location.pathname;
      const authPages = ['/login', '/register', '/auth', '/forgot'];
      
      // If leaving from login page or closing browser, clean up
      if (authPages.includes(currentPath)) {
        this.cleanupOnLogin();
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    
    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }

  static setupPopstateHandler(isAuthenticated, navigate) {
    // Handle browser back/forward navigation
    const handlePopstate = (event) => {
      const currentPath = window.location.pathname;
      const protectedRoutes = ['/dashboard', '/admin', '/supplier'];
      const authRoutes = ['/login', '/register', '/auth', '/forgot'];
      
      // If trying to access protected route without auth
      if (!isAuthenticated && protectedRoutes.some(route => currentPath.startsWith(route))) {
        event.preventDefault();
        this.cleanupOnLogin();
        navigate('/login', { replace: true });
        return;
      }
      
      // If authenticated user tries to go back to auth pages
      if (isAuthenticated && authRoutes.includes(currentPath)) {
        event.preventDefault();
        // Redirect to appropriate dashboard based on user role
        navigate('/dashboard', { replace: true });
        return;
      }
    };

    window.addEventListener('popstate', handlePopstate);
    
    return () => {
      window.removeEventListener('popstate', handlePopstate);
    };
  }

  static disableBackButton() {
    // Disable back button for current page
    const disableBack = () => {
      window.history.pushState(null, null, window.location.pathname);
    };

    // Initial push
    window.history.pushState(null, null, window.location.pathname);
    
    // Listen for popstate
    window.addEventListener('popstate', disableBack);
    
    return () => {
      window.removeEventListener('popstate', disableBack);
    };
  }

  static createSecurityHeaders() {
    // Add security-related meta tags
    const securityHeaders = [
      { name: 'referrer', content: 'no-referrer' },
      // X-Content-Type-Options and X-Frame-Options must be sent via HTTP headers, not meta tags
      // Keep only safe meta-based hints here
    ];

    securityHeaders.forEach(({ name, httpEquiv, content }) => {
      const meta = document.createElement('meta');
      if (name) meta.name = name;
      if (httpEquiv) meta.httpEquiv = httpEquiv;
      if (content) meta.content = content;
      document.head.appendChild(meta);
    });
  }

  static monitorDevTools() {
    // Monitor for developer tools opening (basic detection)
    let devtools = { open: false };
    
    const threshold = 160;
    
    setInterval(() => {
      if (window.outerHeight - window.innerHeight > threshold || 
          window.outerWidth - window.innerWidth > threshold) {
        if (!devtools.open) {
          devtools.open = true;
          console.warn('Developer tools detected');
          // Optionally logout user or show warning
        }
      } else {
        devtools.open = false;
      }
    }, 500);
  }

  static setupInactivityTimer(timeoutMs, onTimeout) {
    let inactivityTimer;
    let lastActivity = Date.now();

    const resetTimer = () => {
      clearTimeout(inactivityTimer);
      lastActivity = Date.now();
      
      inactivityTimer = setTimeout(() => {
        const now = Date.now();
        if (now - lastActivity >= timeoutMs) {
          onTimeout();
        }
      }, timeoutMs);
    };

    // Events that indicate user activity
    const activityEvents = [
      'mousedown', 'mousemove', 'keypress', 'scroll', 
      'touchstart', 'click', 'keydown', 'keyup'
    ];

    activityEvents.forEach(event => {
      document.addEventListener(event, resetTimer, true);
    });

    // Start the timer
    resetTimer();

    return () => {
      clearTimeout(inactivityTimer);
      activityEvents.forEach(event => {
        document.removeEventListener(event, resetTimer, true);
      });
    };
  }
}