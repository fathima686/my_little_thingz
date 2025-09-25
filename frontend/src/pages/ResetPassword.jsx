import React, { useEffect, useState } from "react";
import { createKeydownHandler } from "../utils/validation";
import { Link, useLocation, useNavigate } from "react-router-dom";
import blue from "../assets/blue.png";
import logo from "../assets/logo.png";
import "../styles/login.css";

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function ResetPassword() {
  const location = useLocation();
  const navigate = useNavigate();
  const params = new URLSearchParams(location.search);
  const [email, setEmail] = useState(params.get("email") || "");
  const [token, setToken] = useState(params.get("token") || "");
  const [newPassword, setNewPassword] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // if either param missing, remain but allow manual entry
  }, [location.search]);

  const submitReset = async (e) => {
    e.preventDefault();
    if (!/^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(email)) {
      alert("Enter a valid email");
      return;
    }
    if (!token || token.length < 6) {
      alert("Invalid reset token");
      return;
    }
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
        navigate("/login");
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
            <h1>Reset password</h1>
          </div>

          <form className="form" onSubmit={submitReset}>
            <div className="field">
              <label>Email</label>
              <input type="email" value={email} onChange={(e)=>setEmail(e.target.value)} onKeyDown={createKeydownHandler(false)} required />
            </div>
            <div className="field">
              <label>Reset token</label>
              <input value={token} onChange={(e)=>setToken(e.target.value)} onKeyDown={createKeydownHandler(false)} required />
            </div>
            <div className="field">
              <label>New password</label>
              <input type="password" value={newPassword} onChange={(e)=>setNewPassword(e.target.value)} onKeyDown={createKeydownHandler(false)} required />
            </div>
            <button className="btn primary glossy" type="submit" disabled={loading}>
              {loading ? "Updating..." : "Reset password"}
            </button>
            <p className="switch"><Link to="/login">Back to login</Link></p>
          </form>
        </div>
      </main>
    </div>
  );
}