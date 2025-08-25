import React, { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import logo from "../assets/logo.png";
import blue from "../assets/blue.png";
import "../styles/login.css";

// API base for XAMPP; adjust if your localhost path differs
const API_BASE = "http://localhost/my_little_thingz/backend/api";

const DEFAULT_REDIRECT_BY_ROLE = {
  admin: "/admin", // if you add
  supplier: "/supplier", // if you add later
  customer: "/dashboard", // our new blue dashboard
};
// Use only env; avoids mismatch with your configured Google client
const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID;

export default function Login() {
  const { login, sessionExpired } = useAuth();
  const [formData, setFormData] = useState({ email: "", password: "" });
  const [showPw, setShowPw] = useState(false);
  const googleBtnRef = useRef(null);
  const [banner, setBanner] = useState(null); // { type: 'info'|'success'|'error', text: string }

  // Clear any existing session data when component mounts
  useEffect(() => {
    localStorage.removeItem('auth');
    localStorage.removeItem('sessionTimestamp');
    sessionStorage.clear();
    
    // Clear browser history
    if (window.history.replaceState) {
      window.history.replaceState(null, null, '/login');
    }
    
    // Show session expired message if applicable
    if (sessionExpired) {
      setBanner({ type: 'info', text: 'Your session has expired. Please log in again.' });
    }
  }, [sessionExpired]);

  // Load GSI and render "Sign in with Google" button centered
  useEffect(() => {
    const loadGsi = () =>
      new Promise((resolve) => {
        if (window.google?.accounts?.id) return resolve();
        const id = "google-identity";
        let s = document.getElementById(id);
        if (!s) {
          s = document.createElement("script");
          s.id = id;
          s.src = "https://accounts.google.com/gsi/client";
          s.async = true;
          s.defer = true;
          s.onload = () => resolve();
          document.body.appendChild(s);
        } else {
          s.onload = () => resolve();
        }
      });

    const init = async () => {
      await loadGsi();
      if (!CLIENT_ID) {
        console.warn("Google Client ID missing. Google Sign In disabled.");
        return;
      }
      window.google.accounts.id.initialize({
        client_id: CLIENT_ID,
        callback: async ({ credential }) => {
          try {
            const res = await fetch(`${API_BASE}/auth/google.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ credential }),
            });
            const data = await res.json();
            if (res.ok && data.status === "success") {
              // Use the new login function from AuthContext
              login(data);
            } else if (res.ok && data.status === "pending") {
              // pending supplier — show message and do not redirect
              setBanner({ type: 'info', text: 'Your supplier account is awaiting admin approval.' });
            } else {
              setBanner({ type: 'error', text: data.message || 'Google sign-in failed' });
            }
          } catch (e) {
            alert("Network error on Google sign-in");
          }
        },
        ux_mode: "popup",
        auto_select: false,
      });
      if (googleBtnRef.current) {
        window.google.accounts.id.renderButton(googleBtnRef.current, {
          theme: "outline",
          size: "large",
          shape: "pill",
          text: "signin_with",
          logo_alignment: "left",
          width: 340,
        });
      }
    };

    init();
  }, []);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!/^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(formData.email)) {
      alert("Please enter a valid email");
      return;
    }
    if (formData.password.length < 8) {
      alert("Password must be at least 8 characters");
      return;
    }

    try {
      const res = await fetch(`${API_BASE}/auth/login.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData), // no role sent from client
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        // Use the new login function from AuthContext
        login(data);
      } else if (res.ok && data.status === "pending") {
        // Pending suppliers should not redirect
        setBanner({ type: 'info', text: 'Your supplier account is awaiting admin approval. Please try again later.' });
      } else {
        setBanner({ type: 'error', text: data.message || 'Login failed' });
      }
    } catch (err) {
      alert("Network error during login");
    }
  };

  return (
    <div className="auth-split">
      <aside className="visual" style={{ backgroundImage: `url(${blue})` }} aria-hidden>
        <div className="visual-overlay" />
      </aside>

      <main className="auth-side">
        <div className="auth-card small glossy">
          <div className="brand-head">
            <img src={logo} alt="My Little Thingz" className="brand-logo" />
            <h1>Welcome back</h1>
            <p className="muted">Sign in to continue</p>
          </div>

          {banner && (
            <div className={`banner ${banner.type}`} role="status" aria-live="polite">
              {banner.text}
            </div>
          )}

          <form className="form" onSubmit={handleSubmit}>
            <div className="field">
              <label>Email</label>
              <input
                name="email"
                type="email"
                value={formData.email}
                onChange={handleChange}
                autoComplete="email"
                required
              />
            </div>

            <div className="field">
              <label>Password</label>
              <div className="pwwrap">
                <input
                  name="password"
                  type={showPw ? "text" : "password"}
                  value={formData.password}
                  onChange={handleChange}
                  autoComplete="current-password"
                  required
                />
                <button type="button" className="pwbtn" onClick={() => setShowPw((s) => !s)}>
                  {showPw ? "Hide" : "Show"}
                </button>
              </div>
            </div>

            <button className="btn primary glossy" type="submit">Sign in</button>

            <div className="or"><span>or</span></div>

            <div className="google-wrap" ref={googleBtnRef} />
            {!CLIENT_ID && (
              <button
                type="button"
                className="btn google-fallback"
                onClick={() =>
                  alert('Google Sign-In unavailable. Set VITE_GOOGLE_CLIENT_ID in frontend/.env and restart the dev server.')
                }
                aria-label="Sign in with Google"
              >
                {/* Google G logo (inline SVG) */}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" aria-hidden="true">
                  <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.65 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.06 0 5.842 1.154 7.961 3.039l5.657-5.657C33.642 6.053 29.084 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.651-.389-3.917z"/>
                  <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.339 16.108 18.839 13 24 13c3.06 0 5.842 1.154 7.961 3.039l5.657-5.657C33.642 6.053 29.084 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                  <path fill="#4CAF50" d="M24 44c5.167 0 9.86-1.977 13.409-5.192l-6.195-5.238C29.17 35.091 26.715 36 24 36c-5.205 0-9.62-3.317-11.287-7.946l-6.53 5.027C9.5 39.556 16.227 44 24 44z"/>
                  <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-1.273 3.592-4.18 6.375-7.894 7.57.003-.001 13.591 11.35 13.591 11.35C42.955 43.718 44 36.116 44 31.917c0-1.341-.138-2.651-.389-3.917z"/>
                </svg>
                Sign in with Google
              </button>
            )}

            <div className="row between" style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
              <p className="switch">New here? <Link to="/register">Create account</Link></p>
              <p className="switch"><Link to="/forgot">Forgot password?</Link></p>
            </div>
          </form>
        </div>
      </main>
    </div>
  );
}