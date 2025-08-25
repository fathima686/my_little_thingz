import React, { useEffect, useRef, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import logo from "../assets/logo.png";
import "../styles/auth.css";

// API base for XAMPP; adjust if your localhost path differs
const API_BASE = "http://localhost/my_little_thingz/backend/api";
const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID || "";

export default function Auth() {
  const [mode, setMode] = useState("login"); // 'login' | 'register'
  const navigate = useNavigate();
  const googleBtnRef = useRef(null);
  const [showPw, setShowPw] = useState(false);

  // Shared form data for both modes
  const [form, setForm] = useState({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
  });
  const [busy, setBusy] = useState(false);
  const [desiredRole, setDesiredRole] = useState(null); // 2 = Customer, 3 = Supplier

  // Load Google Identity Services and render a button
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
        if (googleBtnRef.current) googleBtnRef.current.style.display = "none";
        return;
      }
      window.google.accounts.id.initialize({
        client_id: CLIENT_ID,
        callback: async ({ credential }) => {
          try {
            // If on register tab, require role before Google
            if (mode === "register") {
              if (!desiredRole) {
                alert("Please select your role (Customer or Supplier) before continuing with Google.");
                return;
              }
            }
            const res = await fetch(`${API_BASE}/auth/google.php`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(mode === "register" ? { credential, desired_role: desiredRole } : { credential }),
            });
            const data = await res.json();
            if (res.ok && data.status === "success") {
              alert("Signed in with Google!");
              navigate("/");
            } else {
              alert(data.message || "Google sign-in failed");
            }
          } catch (e) {
            alert("Network error on Google sign-in");
          }
        },
        ux_mode: "popup",
        auto_select: false,
      });
      if (googleBtnRef.current) {
        googleBtnRef.current.innerHTML = "";
        window.google.accounts.id.renderButton(googleBtnRef.current, {
          theme: mode === "login" ? "outline" : "filled_blue",
          size: "large",
          shape: "pill",
          text: mode === "login" ? "signin_with" : "signup_with",
          logo_alignment: "left",
          width: 340,
        });
      }
    };

    init();
  }, [mode]);

  const onChange = (e) => setForm((p) => ({ ...p, [e.target.name]: e.target.value }));

  const validate = () => {
    if (!/^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(form.email)) {
      alert("Please enter a valid email");
      return false;
    }
    if (form.password.length < 8) {
      alert("Password must be at least 8 characters");
      return false;
    }
    if (mode === "register") {
      if (!/^([A-Za-z][A-Za-z\s'-]{1,29})$/.test(form.firstName || "")) {
        alert("Please enter a valid first name (2-30 letters)");
        return false;
      }
      if (!/^([A-Za-z][A-Za-z\s'-]{1,29})$/.test(form.lastName || "")) {
        alert("Please enter a valid last name (2-30 letters)");
        return false;
      }
      if (desiredRole == null) {
        alert("Please select a role (Customer or Supplier)");
        return false;
      }
    }
    return true;
  };

  const submit = async (e) => {
    e.preventDefault();
    if (!validate()) return;
    setBusy(true);
    try {
      if (mode === "login") {
        const res = await fetch(`${API_BASE}/auth/login.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email: form.email, password: form.password }),
        });
        const data = await res.json();
        if (res.ok && data.status === "success") {
          alert("Logged in!");
          navigate("/");
        } else {
          alert(data.message || "Login failed");
        }
      } else {
        const res = await fetch(`${API_BASE}/auth/register.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            firstName: form.firstName,
            lastName: form.lastName,
            email: form.email,
            password: form.password,
            role: desiredRole,
          }),
        });
        const data = await res.json();
        if (res.ok && data.status === "success") {
          alert("Registration successful! Please sign in.");
          setMode("login");
        } else {
          alert(data.message || "Registration failed");
        }
      }
    } catch (err) {
      alert("Network error. Try again.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="auth-split-page">
      {/* Left visual panel with your image. We crop toward the left and overlay a gradient to hide the watermark. */}
      <aside className="auth-visual" aria-hidden>
        <div className="visual-overlay" />
        <div className="brand-mark">
          <img src={logo} alt="My Little Thingz" />
          <span>My Little Thingz</span>
        </div>
      </aside>

      {/* Right interactive panel */}
      <main className="auth-panel">
        <div className="glass-card">
          <div className="tabs">
            <button
              className={`tab ${mode === "login" ? "active" : ""}`}
              onClick={() => setMode("login")}
              type="button"
            >
              Sign in
            </button>
            <button
              className={`tab ${mode === "register" ? "active" : ""}`}
              onClick={() => setMode("register")}
              type="button"
            >
              Create account
            </button>
          </div>

          {mode === "login" ? (
            <form className="form" onSubmit={submit}>
              <div className="field">
                <label>Email</label>
                <input name="email" type="email" autoComplete="email" value={form.email} onChange={onChange} required />
              </div>
              <div className="field">
                <label>Password</label>
                <div className="pwwrap">
                  <input
                    name="password"
                    type={showPw ? "text" : "password"}
                    autoComplete="current-password"
                    value={form.password}
                    onChange={onChange}
                    required
                  />
                  <button type="button" className="pwbtn" onClick={() => setShowPw((s) => !s)}>
                    {showPw ? "Hide" : "Show"}
                  </button>
                </div>
              </div>
              <button className="btn primary glossy" type="submit" disabled={busy}>
                {busy ? "Signing in..." : "Sign in"}
              </button>

              <div className="or"><span>or</span></div>
              <div className="google-wrap" ref={googleBtnRef} />
              {!CLIENT_ID && (
                <button type="button" className="btn google-fallback" onClick={() => alert("Google Sign-In unavailable. Set VITE_GOOGLE_CLIENT_ID in your .env and restart the dev server.")}>Sign in with Google</button>
              )}

              <p className="switch">New here? <Link to="/register">Create account</Link></p>
            </form>
          ) : (
            <form className="form" onSubmit={submit}>
              <div className="row">
                <div className="field">
                  <label>First name</label>
                  <input name="firstName" type="text" value={form.firstName} onChange={onChange} autoComplete="given-name" required />
                </div>
                <div className="field">
                  <label>Last name</label>
                  <input name="lastName" type="text" value={form.lastName} onChange={onChange} autoComplete="family-name" required />
                </div>
              </div>
              <div className="field">
                <label>Email</label>
                <input name="email" type="email" value={form.email} onChange={onChange} autoComplete="email" required />
              </div>
              <div className="field">
                <label>Password</label>
                <input name="password" type="password" value={form.password} onChange={onChange} autoComplete="new-password" required />
                <p className="hint">8+ chars, upper, lower, number, special</p>
              </div>

              <div className="field">
                <label>Role</label>
                <select value={desiredRole ?? ""} onChange={(e)=>setDesiredRole(e.target.value ? parseInt(e.target.value,10) : null)} required>
                  <option value="" disabled>Select role</option>
                  <option value={2}>Customer</option>
                  <option value={3}>Supplier</option>
                </select>
                <p className="hint">Supplier accounts may require admin approval.</p>
              </div>

              <button className="btn primary glossy" type="submit" disabled={busy}>
                {busy ? "Creating..." : "Create account"}
              </button>

              <div className="or"><span>or</span></div>
              <div className="google-wrap" ref={googleBtnRef} />
              {!CLIENT_ID && (
                <button type="button" className="btn google-fallback" onClick={() => alert("Google Sign-Up unavailable. Set VITE_GOOGLE_CLIENT_ID in your .env and restart the dev server.")}>Sign up with Google</button>
              )}

              <p className="switch">Already have an account? <Link to="/login">Sign in</Link></p>
            </form>
          )}
        </div>
      </main>
    </div>
  );
}