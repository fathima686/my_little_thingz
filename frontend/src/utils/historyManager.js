// Browser history management utilities for session security

export class HistoryManager {
  static clearHistory() {
    // Clear browser history to prevent back navigation
    if (window.history && window.history.pushState) {
      // Replace current entry with login page
      window.history.replaceState(null, null, '/login');
      
      // Push multiple entries to prevent back navigation
      for (let i = 0; i < 10; i++) {
        window.history.pushState(null, null, '/login');
      }
    }
  }

  static preventBackNavigation() {
    // Prevent back button functionality
    const preventBack = () => {
      window.history.pushState(null, null, window.location.pathname);
    };

    window.addEventListener('popstate', preventBack);
    
    // Initial push to prevent immediate back navigation
    window.history.pushState(null, null, window.location.pathname);

    return () => {
      window.removeEventListener('popstate', preventBack);
    };
  }

  static disableForwardNavigation() {
    // Disable forward navigation by clearing forward history
    if (window.history && window.history.go) {
      // Go forward to clear any forward history
      try {
        window.history.go(1);
        setTimeout(() => {
          window.history.go(-1);
        }, 100);
      } catch (e) {
        // Ignore errors
      }
    }
  }

  static setupSecureNavigation(currentPath) {
    // Set up secure navigation for protected routes
    const protectedRoutes = ['/dashboard', '/admin', '/supplier'];
    const isProtected = protectedRoutes.some(route => currentPath.startsWith(route));

    if (isProtected) {
      // Prevent caching
      this.preventCaching();
      
      // Set up history manipulation
      this.preventBackNavigation();
      
      // Disable right-click and dev tools (optional)
      this.disableDevTools();
    }
  }

  static preventCaching() {
    // Add meta tags to prevent caching
    const addMetaTag = (httpEquiv, content) => {
      const existing = document.querySelector(`meta[http-equiv="${httpEquiv}"]`);
      if (existing) {
        existing.content = content;
      } else {
        const meta = document.createElement('meta');
        meta.httpEquiv = httpEquiv;
        meta.content = content;
        document.head.appendChild(meta);
      }
    };

    addMetaTag('Cache-Control', 'no-cache, no-store, must-revalidate');
    addMetaTag('Pragma', 'no-cache');
    addMetaTag('Expires', '0');
  }

  static disableDevTools() {
    // Disable common developer tools shortcuts
    const disableKeys = (e) => {
      // F12
      if (e.keyCode === 123) {
        e.preventDefault();
        return false;
      }
      
      // Ctrl+Shift+I
      if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
        e.preventDefault();
        return false;
      }
      
      // Ctrl+U (View Source)
      if (e.ctrlKey && e.keyCode === 85) {
        e.preventDefault();
        return false;
      }
      
      // Ctrl+Shift+C (Inspect Element)
      if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
        e.preventDefault();
        return false;
      }
    };

    const disableRightClick = (e) => {
      e.preventDefault();
      return false;
    };

    document.addEventListener('keydown', disableKeys);
    document.addEventListener('contextmenu', disableRightClick);

    return () => {
      document.removeEventListener('keydown', disableKeys);
      document.removeEventListener('contextmenu', disableRightClick);
    };
  }

  static clearBrowserData() {
    // Clear all browser storage
    try {
      localStorage.clear();
      sessionStorage.clear();
      
      // Clear cookies (if any)
      document.cookie.split(";").forEach((c) => {
        const eqPos = c.indexOf("=");
        const name = eqPos > -1 ? c.substr(0, eqPos) : c;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
      });
      
      // Clear IndexedDB (if used)
      if (window.indexedDB) {
        indexedDB.databases().then(databases => {
          databases.forEach(db => {
            indexedDB.deleteDatabase(db.name);
          });
        }).catch(() => {
          // Ignore errors
        });
      }
    } catch (error) {
      console.warn('Error clearing browser data:', error);
    }
  }

  static setupSessionTimeout(timeoutMs = 30 * 60 * 1000, onTimeout) {
    let timeoutId;
    let lastActivity = Date.now();

    const resetTimeout = () => {
      clearTimeout(timeoutId);
      lastActivity = Date.now();
      
      timeoutId = setTimeout(() => {
        if (Date.now() - lastActivity >= timeoutMs) {
          onTimeout();
        }
      }, timeoutMs);
    };

    // Track user activity
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    events.forEach(event => {
      document.addEventListener(event, resetTimeout, true);
    });

    // Initial timeout setup
    resetTimeout();

    return () => {
      clearTimeout(timeoutId);
      events.forEach(event => {
        document.removeEventListener(event, resetTimeout, true);
      });
    };
  }

  static handlePageVisibility(onHidden, onVisible) {
    const handleVisibilityChange = () => {
      if (document.hidden) {
        onHidden();
      } else {
        onVisible();
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }
}