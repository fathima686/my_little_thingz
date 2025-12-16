import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useGoogleLogin } from '@react-oauth/google';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { LuArrowLeft } from 'react-icons/lu';
import { FiMail, FiLock, FiEye, FiEyeOff } from 'react-icons/fi';
import collage1 from '../assets/collage1.jpeg';
import collage2 from '../assets/collage2.jpeg';
import collage3 from '../assets/collage3.jpeg';
import collage4 from '../assets/collage4.jpeg';
import collage5 from '../assets/collage5.jpeg';
import collage6_1 from '../assets/collage6 (1).jpeg';
import collage6_2 from '../assets/collage6 (2).jpeg';
import collage6_3 from '../assets/collage6 (3).jpeg';
import collage6_4 from '../assets/collage6 (4).jpeg';
import collage6_5 from '../assets/collage6 (5).jpeg';
import myLogo from '../assets/my-logo.png';
import '../styles/tutorial-login.css';

const VALIDATION_RULES = {
  email: {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: 'Please enter a valid email address'
  },
  password: {
    minLength: 1,
    message: 'Password is required'
  }
};

export default function TutorialLogin() {
  const navigate = useNavigate();
  const { tutorialLogin, tutorialGoogleLogin } = useTutorialAuth();
  const [formData, setFormData] = useState({ email: '', password: '' });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [touched, setTouched] = useState({});

  const validateEmail = (email) => {
    if (!email || !email.trim()) {
      return 'Email is required';
    }
    if (!VALIDATION_RULES.email.pattern.test(email.trim())) {
      return VALIDATION_RULES.email.message;
    }
    return '';
  };

  const validatePassword = (password) => {
    if (!password || !password.trim()) {
      return 'Password is required';
    }
    return '';
  };

  const validateForm = () => {
    const newErrors = {};
    
    const emailError = validateEmail(formData.email);
    if (emailError) newErrors.email = emailError;

    const passwordError = validatePassword(formData.password);
    if (passwordError) newErrors.password = passwordError;

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    setTouched(prev => ({
      ...prev,
      [name]: true
    }));

    // Real-time validation
    if (errors[name]) {
      const newErrors = { ...errors };
      delete newErrors[name];
      setErrors(newErrors);
    }
  };

  const handleBlur = (e) => {
    const { name, value } = e.target;
    setTouched(prev => ({
      ...prev,
      [name]: true
    }));

    // Validate on blur
    let error = '';
    if (name === 'email') {
      error = validateEmail(value);
    } else if (name === 'password') {
      error = validatePassword(value);
    }

    if (error) {
      setErrors(prev => ({ ...prev, [name]: error }));
    } else {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Mark all fields as touched
    setTouched({ email: true, password: true });

    if (!validateForm()) {
      return;
    }

    setLoading(true);
    setErrors({});

    try {
      await tutorialLogin(formData.email.trim(), formData.password);
      navigate('/tutorials', { replace: true });
    } catch (error) {
      setErrors({ submit: error.message || 'Login failed. Please check your credentials and try again.' });
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleLogin = useGoogleLogin({
    onSuccess: (codeResponse) => {
      setLoading(true);
      setErrors({});
      
      fetch('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' + codeResponse.access_token, {
        headers: { 'Authorization': `Bearer ${codeResponse.access_token}` },
      })
        .then((res) => res.json())
        .then((data) => {
          tutorialGoogleLogin(data.email, data.id, data.name);
          navigate('/tutorials', { replace: true });
        })
        .catch(() => {
          setErrors({ submit: 'Google login failed. Please try again.' });
          setLoading(false);
        });
    },
    onError: () => {
      setErrors({ submit: 'Google login failed. Please try again.' });
      setLoading(false);
    },
  });

  return (
    <div className="tutorial-login-container">
      <Link to="/" className="tutorial-login-back">
        <LuArrowLeft size={20} />
        Back to Home
      </Link>

      <div className="tutorial-login-wrapper">
        {/* Left Side - Collage Grid */}
        <div className="tutorial-login-left">
          <div className="tutorial-hero-wrapper">
            <div className="collage-grid">
              <div className="collage-item">
                <img src={collage1} alt="Craft Collage 1" />
              </div>
              <div className="collage-item">
                <img src={collage2} alt="Craft Collage 2" />
              </div>
              <div className="collage-item">
                <img src={collage3} alt="Craft Collage 3" />
              </div>
              <div className="collage-item">
                <img src={collage4} alt="Craft Collage 4" />
              </div>
              <div className="collage-item">
                <img src={collage5} alt="Craft Collage 5" />
              </div>
              <div className="collage-item">
                <img src={collage6_1} alt="Craft Collage 6" />
              </div>
              <div className="collage-item">
                <img src={collage6_2} alt="Craft Collage 7" />
              </div>
              <div className="collage-item">
                <img src={collage6_3} alt="Craft Collage 8" />
              </div>
              <div className="collage-item">
                <img src={collage6_4} alt="Craft Collage 9" />
              </div>
              <div className="collage-item">
                <img src={collage6_5} alt="Craft Collage 10" />
              </div>
            </div>
          </div>
        </div>

        {/* Right Side - Login Form */}
        <div className="tutorial-login-right">
          <div className="tutorial-login-card">
            {/* Logo */}
            <div className="tutorial-logo-container">
              <img src={myLogo} alt="My Little Thingz" className="tutorial-logo" />
            </div>

            <div className="tutorial-login-header">
              <h1>Welcome Creator</h1>
              <p>Sign in to unlock amazing craft tutorials & exclusive content</p>
            </div>

            {errors.submit && (
              <div className="tutorial-login-error">
                {errors.submit}
              </div>
            )}

            <form onSubmit={handleSubmit} className="tutorial-login-form">
              <div className="tutorial-form-group">
                <label htmlFor="email">Email Address</label>
                <div className="tutorial-input-wrapper">
                  <FiMail className="tutorial-input-icon" />
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={loading}
                    className={`${errors.email && touched.email ? 'input-error' : ''}`}
                  />
                </div>
                {errors.email && touched.email && (
                  <span className="error-message">{errors.email}</span>
                )}
              </div>

              <div className="tutorial-form-group">
                <label htmlFor="password">Password</label>
                <div className="tutorial-input-wrapper">
                  <FiLock className="tutorial-input-icon" />
                  <input
                    type={showPassword ? 'text' : 'password'}
                    id="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={loading}
                    className={`${errors.password && touched.password ? 'input-error' : ''}`}
                  />
                  <button
                    type="button"
                    className="tutorial-password-toggle"
                    onClick={() => setShowPassword(!showPassword)}
                    disabled={loading}
                    tabIndex="-1"
                  >
                    {showPassword ? <FiEyeOff size={18} /> : <FiEye size={18} />}
                  </button>
                </div>
                {errors.password && touched.password && (
                  <span className="error-message">{errors.password}</span>
                )}
              </div>

              <button 
                type="submit" 
                className="tutorial-login-btn"
                disabled={loading || !formData.email.trim() || !formData.password.trim()}
              >
                {loading ? 'Signing in...' : 'Sign In with Email'}
              </button>
            </form>

            <div className="tutorial-divider">
              <span>OR</span>
            </div>

            <button 
              type="button" 
              className="tutorial-google-btn"
              onClick={() => handleGoogleLogin()}
              disabled={loading}
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              Continue with Google
            </button>

            <div className="tutorial-login-footer">
              <p>Don't have an account? <Link to="/tutorial-register">Sign up</Link></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
