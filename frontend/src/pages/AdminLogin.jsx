import React, { useState } from "react";
import { useNavigate } from "react-router-dom";

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function AdminLogin() {
  const [formData, setFormData] = useState({ email: "", password: "" });
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/auth/login.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
      const data = await res.json();
      if (res.ok && data.status === "success") {
        const roles = Array.isArray(data.roles) ? data.roles.map((r) => String(r).toLowerCase()) : [];
        if (!roles.includes("admin")) {
          alert("You are not an admin.");
          setLoading(false);
          return;
        }
        try { localStorage.setItem("auth", JSON.stringify(data)); } catch {}
        navigate("/admin");
      } else {
        alert(data.message || "Admin login failed");
      }
    } catch (err) {
      alert("Network error during admin login");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ display: "grid", placeItems: "center", minHeight: "80vh", padding: 24 }}>
      <form onSubmit={handleSubmit} style={{ width: 360, border: "1px solid #eee", borderRadius: 12, padding: 24 }}>
        <h1 style={{ marginBottom: 16 }}>Admin Login</h1>
        <div style={{ display: "grid", gap: 12 }}>
          <label>
            <div>Email</div>
            <input name="email" type="email" value={formData.email} onChange={handleChange} required />
          </label>
          <label>
            <div>Password</div>
            <input name="password" type="password" value={formData.password} onChange={handleChange} required />
          </label>
          <button disabled={loading} type="submit">{loading ? "Signing in..." : "Sign in"}</button>
        </div>
      </form>
    </div>
  );
}