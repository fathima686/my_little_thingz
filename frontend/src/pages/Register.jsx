import React, { useEffect, useMemo, useRef, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import logo from "../assets/logo.png";
import blue from "../assets/blue.png";
import "../styles/register.css";

// API base for XAMPP; adjust if your localhost path differs
const API_BASE = "http://localhost/my_little_thingz/backend/api";
// Use env first; fallback to provided client id so buttons render immediately
const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID;

export default function Register() {
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    role: "", // require explicit selection: 2=customer, 3=supplier
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const googleBtnRef = useRef(null);
  const [googleRole, setGoogleRole] = useState("");
  const [banner, setBanner] = useState(null); // { type: 'info'|'success'|'error', text: string }

  // Load Google Identity script and render "Sign up with Google" button centered
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
        console.warn("Google Client ID missing. Google Sign Up disabled.");
        if (googleBtnRef.current) {
          // Hide the Google button area when no client id
          googleBtnRef.current.style.display = "none";
        }
        return;
      }
      window.google.accounts.id.initialize({
        client_id: CLIENT_ID,
        callback: async ({ credential }) => {
          try {
            // Ensure role is explicitly selected before Google signup
            const chosen = googleRole || formData.role;
            if (!chosen) {
              alert("Please select your role (Customer or Supplier) before continuing with Google.");
              return;
            }
            const desired_role = parseInt(chosen, 10) === 3 ? 3 : 2;
            const res = await fetch(`${API_BASE}/auth/google.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ credential, desired_role }),
            });
            const data = await res.json();
            if (res.ok && data.status === "success") {
              // store and redirect by role
              try { localStorage.setItem("auth", JSON.stringify(data)); } catch (e) {}
              const roles = Array.isArray(data.roles) ? data.roles.map(r => String(r).toLowerCase()) : [];
              const has = (name) => roles.includes(name);
              let to = "/dashboard";
              if (has("admin")) to = "/admin";
              else if (has("supplier")) to = "/supplier";
              else if (has("customer")) to = "/dashboard";
              navigate(to);
            } else if (res.ok && data.status === "pending") {
              setBanner({ type: 'info', text: 'Your supplier account is awaiting admin approval.' });
            } else {
              setBanner({ type: 'error', text: data.message || 'Google sign-up failed' });
            }
          } catch (e) {
            setBanner({ type: 'error', text: 'Network error on Google sign-up' });
          }
        },
        ux_mode: "popup",
        auto_select: false,
      });
      if (googleBtnRef.current) {
        // Clear previous render before re-rendering
        googleBtnRef.current.innerHTML = "";
        window.google.accounts.id.renderButton(googleBtnRef.current, {
          theme: "filled_blue",
          size: "large",
          shape: "pill",
          text: "signup_with",
          logo_alignment: "left",
          width: 340,
        });
      }

    };

    init();
  }, [googleRole, formData.role]);

  // Strict validation rules
  const validators = useMemo(() => ({
    firstName: (v) =>
      !v.trim()
        ? "First name is required"
        : !/^[A-Za-z][A-Za-z\s'-]{1,29}$/.test(v)
        ? "Use 2-30 letters (letters, space, ',- allowed)"
        : null,
    lastName: (v) =>
      !v.trim()
        ? "Last name is required"
        : !/^[A-Za-z][A-Za-z\s'-]{1,29}$/.test(v)
        ? "Use 2-30 letters (letters, space, ',- allowed)"
        : null,
    email: (v) =>
      !v.trim()
        ? "Email is required"
        : !/^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(v)
        ? "Invalid email format"
        : null,
    password: (v) =>
      !v
        ? "Password is required"
        : v.length < 8
        ? "Minimum 8 characters"
        : !/[A-Z]/.test(v)
        ? "Must include an uppercase letter"
        : !/[a-z]/.test(v)
        ? "Must include a lowercase letter"
        : !/[0-9]/.test(v)
        ? "Must include a number"
        : !/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(v)
        ? "Include one special character"
        : null,
  }), []);

  const validateAll = () => {
    const next = Object.fromEntries(
      Object.entries(formData)
        .filter(([k]) => !!validators[k]) // only validate fields that have validators
        .map(([k, v]) => [k, validators[k](v)])
    );
    Object.keys(next).forEach((k) => next[k] === null && delete next[k]);
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((p) => ({ ...p, [name]: value }));
  };

  const handleBlur = (e) => {
    const { name, value } = e.target;
    const err = validators[name]?.(value) || null;
    setErrors((p) => ({ ...p, [name]: err || undefined }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateAll()) return;

    try {
      setSubmitting(true);
      if (!formData.role) {
        alert("Please select a role before creating your account.");
        return;
      }
      const res = await fetch(`${API_BASE}/auth/register.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...formData, role: parseInt(formData.role, 10) }),
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        const isSupplier = (data.role === 'supplier' || String(formData.role) === '3');
        if (isSupplier) {
          setBanner({ type: 'info', text: 'Registration submitted. Your supplier account is pending admin approval.' });
          // Stay on the same page; no redirect
        } else {
          setBanner({ type: 'success', text: 'Registration successful! Redirecting...' });
          navigate("/");
        }
      } else {
        setBanner({ type: 'error', text: data.message || 'Registration failed' });
      }
    } catch (err) {
      setBanner({ type: 'error', text: 'Network error during registration' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="auth-split">
      <aside className="visual" style={{ backgroundImage: `url(${blue})` }} aria-hidden>
        <div className="visual-overlay" />
      </aside>

      <main className="auth-side">
        <div className="auth-card glossy">
          <div className="brand-head">
            <img src={logo} alt="My Little Thingz" className="brand-logo" />
            <h1>Create account</h1>
          </div>

          {banner && (
            <div className={`banner ${banner.type}`} role="status" aria-live="polite">
              {banner.text}
            </div>
          )}

          <form className="form" onSubmit={handleSubmit} noValidate>
            <div className="row">
              <div className="field">
                <label>First name</label>
                <input
                  name="firstName"
                  type="text"
                  value={formData.firstName}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  autoComplete="given-name"
                />
                {errors.firstName && <span className="error">{errors.firstName}</span>}
              </div>

              <div className="field">
                <label>Last name</label>
                <input
                  name="lastName"
                  type="text"
                  value={formData.lastName}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  autoComplete="family-name"
                />
                {errors.lastName && <span className="error">{errors.lastName}</span>}
              </div>
            </div>

            <div className="field">
              <label>Email</label>
              <input
                name="email"
                type="email"
                value={formData.email}
                onChange={handleChange}
                onBlur={handleBlur}
                autoComplete="email"
              />
              {errors.email && <span className="error">{errors.email}</span>}
            </div>

            <div className="field">
              <label>Password</label>
              <input
                name="password"
                type="password"
                value={formData.password}
                onChange={handleChange}
                onBlur={handleBlur}
                autoComplete="new-password"
              />
              {errors.password && <span className="error">{errors.password}</span>}
              <p className="hint">8+ chars, upper, lower, number, special</p>
            </div>

            <div className="field">
              <label>Role</label>
              <select name="role" value={formData.role} onChange={handleChange}>
                <option value="" disabled>Select role</option>
                <option value="2">Customer</option>
                <option value="3">Supplier</option>
              </select>
            </div>

            <button className="btn primary glossy" type="submit" disabled={submitting}>
              {submitting ? "Creating..." : "Create account"}
            </button>

            <div className="or"><span>or</span></div>

            {/* Separate role selection for Google sign-up */}
            <div className="field">
              <label>Role for Google sign-up</label>
              <select name="googleRole" value={googleRole} onChange={(e)=>setGoogleRole(e.target.value)}>
                <option value="" disabled>Select role</option>
                <option value="2">Customer</option>
                <option value="3">Supplier</option>
              </select>
              <p className="hint">Required when using Google sign-up.</p>
            </div>

            {/* Google sign-up button rendered by GSI; fallback button shows when CLIENT_ID is missing */}
            <div className="google-wrap" ref={googleBtnRef} />
            {!CLIENT_ID && (
              <button
                type="button"
                className="btn google-fallback"
                onClick={() => alert('Google Sign Up unavailable. Set VITE_GOOGLE_CLIENT_ID in your .env and restart the dev server.')}
                aria-label="Sign up with Google"
              >
                {/* Google G logo (inline SVG) */}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" aria-hidden="true">
                  <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.65 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.06 0 5.842 1.154 7.961 3.039l5.657-5.657C33.642 6.053 29.084 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.651-.389-3.917z"/>
                  <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.339 16.108 18.839 13 24 13c3.06 0 5.842 1.154 7.961 3.039l5.657-5.657C33.642 6.053 29.084 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                  <path fill="#4CAF50" d="M24 44c5.167 0 9.86-1.977 13.409-5.192l-6.195-5.238C29.17 35.091 26.715 36 24 36c-5.205 0-9.62-3.317-11.287-7.946l-6.53 5.027C9.5 39.556 16.227 44 24 44z"/>
                  <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-1.273 3.592-4.18 6.375-7.894 7.57.003-.001 13.591 11.35 13.591 11.35C42.955 43.718 44 36.116 44 31.917c0-1.341-.138-2.651-.389-3.917z"/>
                </svg>
                Sign up with Google
              </button>
            )}

            <p className="switch">Already have an account? <Link to="/login">Sign in</Link></p>
          </form>
        </div>
      </main>
    </div>
  );
}