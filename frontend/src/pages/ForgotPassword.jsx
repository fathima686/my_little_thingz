import React, { useState } from "react";
import { Link } from "react-router-dom";
import blue from "../assets/blue.png";
import logo from "../assets/logo.png";
import "../styles/login.css";

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [token, setToken] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [step, setStep] = useState(1); // 1=request, 2=reset
  const [loading, setLoading] = useState(false);

  const requestReset = async (e) => {
    e.preventDefault();
    if (!/^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(email)) {
      alert("Enter a valid email");
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
        alert("Reset token generated. Check server log/response.");
        // For demo we return token directly in response
        if (data.token) setToken(data.token);
        setStep(2);
      } else {
        alert(data.message || "Request failed");
      }
    } catch (e) {
      alert("Network error");
    } finally {
      setLoading(false);
    }
  };

  const submitReset = async (e) => {
    e.preventDefault();
    if (newPassword.length < 8) {
      alert("Password must be at least 8 characters");
      return;
    }
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/auth/forgot_reset.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, token, newPassword }),
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        alert("Password updated. You can login now.");
        window.location.assign("/login");
      } else {
        alert(data.message || "Reset failed");
      }
    } catch (e) {
      alert("Network error");
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
                <input type="email" value={email} onChange={(e)=>setEmail(e.target.value)} required />
              </div>
              <button className="btn primary glossy" type="submit" disabled={loading}>
                {loading ? "Requesting..." : "Send reset token"}
              </button>
              <p className="switch"><Link to="/login">Back to login</Link></p>
            </form>
          ) : (
            <form className="form" onSubmit={submitReset}>
              <div className="field">
                <label>Reset token</label>
                <input value={token} onChange={(e)=>setToken(e.target.value)} required />
              </div>
              <div className="field">
                <label>New password</label>
                <input type="password" value={newPassword} onChange={(e)=>setNewPassword(e.target.value)} required />
              </div>
              <button className="btn primary glossy" type="submit" disabled={loading}>
                {loading ? "Updating..." : "Reset password"}
              </button>
              <p className="switch"><Link to="/login">Back to login</Link></p>
            </form>
          )}
        </div>
      </main>
    </div>
  );
}