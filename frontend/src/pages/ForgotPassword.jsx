import React, { useState } from "react";
import { createKeydownHandler } from "../utils/validation";
import { Link } from "react-router-dom";
import blue from "../assets/blue.png";
import logo from "../assets/logo.png";
import "../styles/login.css";
import { useNotify } from "../contexts/Notify.jsx";

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [token, setToken] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [step, setStep] = useState(1); // 1=request, 2=enter otp, then verify -> show password
  const [otpVerified, setOtpVerified] = useState(false);
  const [loading, setLoading] = useState(false);
  const notify = useNotify();

  const requestReset = async (e) => {
    e.preventDefault();
    if (!/^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(email)) {
      notify('Enter a valid email', 'warning');
      return;
    }
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/auth/forgot_request.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        notify('If an account exists for this email, a reset code has been sent. Please check your inbox.', 'info');
        setStep(2);
      } else {
        notify(data.message || 'Request failed', 'error');
      }
    } catch (e) {
      notify('Network error', 'error');
    } finally {
      setLoading(false);
    }
  };

  const verifyOtp = async (e) => {
    e.preventDefault();
    if (!/^[0-9]{6}$/.test(token)) {
      notify('Enter the 6-digit code from your email', 'warning');
      return;
    }
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/auth/forgot_verify_otp.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, otp: token }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setOtpVerified(true);
        notify('Code verified. Please set a new password.', 'success');
      } else {
        notify(data.message || 'Invalid or expired code', 'error');
      }
    } catch (e) {
      notify('Network error', 'error');
    } finally {
      setLoading(false);
    }
  };

  const submitReset = async (e) => {
    e.preventDefault();
    if (!otpVerified) {
      notify('Please verify the code first', 'warning');
      return;
    }
    if (newPassword.length < 8) {
      notify('Password must be at least 8 characters', 'warning');
      return;
    }
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/auth/forgot_reset.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token, newPassword }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        notify('Password updated. You can login now.', 'success');
        setTimeout(() => window.location.assign('/login'), 600);
      } else {
        notify(data.message || 'Reset failed', 'error');
      }
    } catch (e) {
      notify('Network error', 'error');
    } finally {
      setLoading(false);
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
            <h1>Forgot password</h1>
          </div>

          {step === 1 ? (
            <form className="form" onSubmit={requestReset}>
              <div className="field">
                <label>Email</label>
                <input type="email" value={email} onChange={(e)=>setEmail(e.target.value)} onKeyDown={createKeydownHandler(false)} required />
              </div>
              <button className="btn primary glossy" type="submit" disabled={loading}>
                {loading ? "Requesting..." : "Send reset code"}
              </button>
              <p className="switch"><Link to="/login">Back to login</Link></p>
            </form>
          ) : (
            <form className="form" onSubmit={otpVerified ? submitReset : verifyOtp}>
              <div className="field">
                <label>Reset code (sent to your email)</label>
                <input
                  value={token}
                  onChange={(e)=> setToken(e.target.value.replace(/\D/g, '').slice(0, 6))}
                  onKeyDown={createKeydownHandler(false)}
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  pattern="^[0-9]{6}$"
                  title="Enter the 6-digit code"
                  maxLength={6}
                  required
                />
              </div>
              {otpVerified && (
                <div className="field">
                  <label>New password</label>
                  <input type="password" value={newPassword} onChange={(e)=>setNewPassword(e.target.value)} onKeyDown={createKeydownHandler(false)} required />
                </div>
              )}
              <button className="btn primary glossy" type="submit" disabled={loading}>
                {loading ? (otpVerified ? "Updating..." : "Verifying...") : (otpVerified ? "Reset password" : "Verify code")}
              </button>
              <p className="switch"><Link to="/login">Back to login</Link></p>
            </form>
          )}
        </div>
      </main>
    </div>
  );
}