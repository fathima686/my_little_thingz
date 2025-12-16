import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useGoogleLogin } from '@react-oauth/google';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { LuArrowLeft, LuCheck } from 'react-icons/lu';
import { FiMail, FiLock, FiUser, FiCheck, FiX, FiEye, FiEyeOff } from 'react-icons/fi';
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
import '../styles/tutorial-register.css';

const VALIDATION_RULES = {
  email: {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: 'Please enter a valid email address'
  },
  password: {
    minLength: 8,
    hasUpperCase: /[A-Z]/,
    hasLowerCase: /[a-z]/,
    hasNumber: /[0-9]/,
    hasSpecial: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/,
    message: 'Password must be at least 8 characters with uppercase, lowercase, number, and special character'
  },
  firstName: {
    minLength: 2,
    pattern: /^[a-zA-Z\s]+$/,
    message: 'First name must be at least 2 characters and contain only letters'
  },
  lastName: {
    minLength: 2,
    pattern: /^[a-zA-Z\s]+$/,
    message: 'Last name must be at least 2 characters and contain only letters'
  }
};

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function TutorialRegister() {
  const navigate = useNavigate();
  const { tutorialGoogleLogin } = useTutorialAuth();
  const [formData, setFormData] = useState({ firstName: '', lastName: '', email: '', password: '', confirmPassword: '' });
  const [errors, setErrors] = useState({});
  const [passwordStrength, setPasswordStrength] = useState(0);
  const [loading, setLoading] = useState(false);
  const [touched, setTouched] = useState({});
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const validateEmail = (email) => {
    return VALIDATION_RULES.email.pattern.test(email);
  };

  const validatePassword = (password) => {
    if (password.length < VALIDATION_RULES.password.minLength) return false;
    if (!VALIDATION_RULES.password.hasUpperCase.test(password)) return false;
    if (!VALIDATION_RULES.password.hasLowerCase.test(password)) return false;
    if (!VALIDATION_RULES.password.hasNumber.test(password)) return false;
    if (!VALIDATION_RULES.password.hasSpecial.test(password)) return false;
    return true;
  };

  const getPasswordStrength = (password) => {
    let strength = 0;
    if (password.length >= VALIDATION_RULES.password.minLength) strength++;
    if (VALIDATION_RULES.password.hasUpperCase.test(password)) strength++;
    if (VALIDATION_RULES.password.hasLowerCase.test(password)) strength++;
    if (VALIDATION_RULES.password.hasNumber.test(password)) strength++;
    if (VALIDATION_RULES.password.hasSpecial.test(password)) strength++;
    return strength;
  };

  const validateFirstName = (firstName) => {
    return firstName.length >= VALIDATION_RULES.firstName.minLength && VALIDATION_RULES.firstName.pattern.test(firstName);
  };

  const validateLastName = (lastName) => {
    return lastName.length >= VALIDATION_RULES.lastName.minLength && VALIDATION_RULES.lastName.pattern.test(lastName);
  };

  const validateForm = () => {
    const newErrors = {};

    // First name validation
    if (!formData.firstName || !formData.firstName.trim()) {
      newErrors.firstName = 'First name is required';
    } else if (!validateFirstName(formData.firstName.trim())) {
      newErrors.firstName = VALIDATION_RULES.firstName.message;
    }

    // Last name validation
    if (!formData.lastName || !formData.lastName.trim()) {
      newErrors.lastName = 'Last name is required';
    } else if (!validateLastName(formData.lastName.trim())) {
      newErrors.lastName = VALIDATION_RULES.lastName.message;
    }

    // Email validation
    if (!formData.email || !formData.email.trim()) {
      newErrors.email = 'Email is required';
    } else if (!validateEmail(formData.email.trim())) {
      newErrors.email = VALIDATION_RULES.email.message;
    }

    // Password validation
    if (!formData.password || !formData.password.trim()) {
      newErrors.password = 'Password is required';
    } else if (!validatePassword(formData.password)) {
      newErrors.password = VALIDATION_RULES.password.message;
    }

    // Confirm password validation
    if (!formData.confirmPassword || !formData.confirmPassword.trim()) {
      newErrors.confirmPassword = 'Please confirm your password';
    } else if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Passwords do not match';
    }

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

    if (name === 'password') {
      setPasswordStrength(getPasswordStrength(value));
    }

    // Real-time validation
    if (errors[name]) {
      const newErrors = { ...errors };
      delete newErrors[name];
      setErrors(newErrors);
    }

    // Validate confirm password when password changes
    if (name === 'password' && formData.confirmPassword) {
      if (value !== formData.confirmPassword) {
        setErrors(prev => ({ ...prev, confirmPassword: 'Passwords do not match' }));
      } else {
        setErrors(prev => {
          const newErrors = { ...prev };
          delete newErrors.confirmPassword;
          return newErrors;
        });
      }
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
    if (name === 'firstName') {
      if (!value || !value.trim()) {
        error = 'First name is required';
      } else if (!validateFirstName(value.trim())) {
        error = VALIDATION_RULES.firstName.message;
      }
    } else if (name === 'lastName') {
      if (!value || !value.trim()) {
        error = 'Last name is required';
      } else if (!validateLastName(value.trim())) {
        error = VALIDATION_RULES.lastName.message;
      }
    } else if (name === 'email') {
      error = validateEmail(value) ? '' : VALIDATION_RULES.email.message;
      if (!value || !value.trim()) {
        error = 'Email is required';
      }
    } else if (name === 'password') {
      if (!value || !value.trim()) {
        error = 'Password is required';
      } else if (!validatePassword(value)) {
        error = VALIDATION_RULES.password.message;
      }
    } else if (name === 'confirmPassword') {
      if (!value || !value.trim()) {
        error = 'Please confirm your password';
      } else if (value !== formData.password) {
        error = 'Passwords do not match';
      }
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

    if (!validateForm()) {
      return;
    }

    setLoading(true);
    setSuccessMessage('');
    setErrors({});

    try {
      const response = await fetch(`${API_BASE}/auth/register.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          firstName: formData.firstName.trim(),
          lastName: formData.lastName.trim(),
          email: formData.email.trim(),
          password: formData.password,
          role: 2 // customer role
        })
      });

      const data = await response.json();

      if (data.status === 'success' || response.ok) {
        setSuccessMessage('Registration successful! Please login to continue.');
        setFormData({ firstName: '', lastName: '', email: '', password: '', confirmPassword: '' });
        
        // Redirect to login page after 2 seconds
        setTimeout(() => {
          navigate('/tutorial-login', { replace: true });
        }, 2000);
      } else {
        setErrors({ submit: data.message || 'Registration failed. Please try again.' });
        setLoading(false);
      }
    } catch (error) {
      console.error('Registration error:', error);
      setErrors({ submit: 'Registration failed. Please try again.' });
      setLoading(false);
    }
  };

  const handleGoogleLogin = useGoogleLogin({
    onSuccess: (codeResponse) => {
      setLoading(true);

      fetch('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' + codeResponse.access_token, {
        headers: { 'Authorization': `Bearer ${codeResponse.access_token}` },
      })
        .then((res) => res.json())
        .then((data) => {
          // For Google registration, also register first then login
          tutorialGoogleLogin(data.email, data.id, data.name);
          navigate('/tutorials', { replace: true });
        })
        .catch(() => {
          setErrors({ submit: 'Google registration failed. Please try again.' });
          setLoading(false);
        });
    },
    onError: () => {
      setErrors({ submit: 'Google registration failed. Please try again.' });
      setLoading(false);
    },
  });

  const passwordStrengthLabel = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong', 'Excellent'];
  const passwordStrengthColor = ['#d32f2f', '#f57c00', '#f9a825', '#4caf50', '#388e3c', '#1b5e20'];

  return (
    <div className="tutorial-register-container">
      <Link to="/" className="tutorial-register-back">
        <LuArrowLeft size={20} />
        Back to Home
      </Link>

      <div className="tutorial-register-wrapper">
        {/* Left Side - Collage Grid */}
        <div className="tutorial-register-left">
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

        {/* Right Side - Register Form */}
        <div className="tutorial-register-right">
          <div className="tutorial-register-card">
            {/* Logo */}
            <div className="tutorial-logo-container">
              <img src={myLogo} alt="My Little Thingz" className="tutorial-logo" />
            </div>

            <div className="tutorial-register-header">
              <h1>Join The Creator Community</h1>
              <p>Sign up to start your handmade craft journey with us</p>
            </div>

            {successMessage && (
              <div className="tutorial-register-success">
                <LuCheck size={20} />
                <span>{successMessage}</span>
              </div>
            )}

            {errors.submit && (
              <div className="tutorial-register-error">
                {errors.submit}
              </div>
            )}

            <form onSubmit={handleSubmit} className="tutorial-register-form">
            {/* First Name Field */}
            <div className="tutorial-form-group">
              <label htmlFor="firstName">First Name</label>
              <div className="tutorial-input-wrapper">
                <FiUser className="tutorial-input-icon" />
                <input
                  type="text"
                  id="firstName"
                  name="firstName"
                  value={formData.firstName}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  disabled={loading}
                  className={`${errors.firstName && touched.firstName ? 'input-error' : ''}`}
                />
              </div>
              {errors.firstName && touched.firstName && (
                <span className="error-message">{errors.firstName}</span>
              )}
            </div>

            {/* Last Name Field */}
            <div className="tutorial-form-group">
              <label htmlFor="lastName">Last Name</label>
              <div className="tutorial-input-wrapper">
                <FiUser className="tutorial-input-icon" />
                <input
                  type="text"
                  id="lastName"
                  name="lastName"
                  value={formData.lastName}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  disabled={loading}
                  className={`${errors.lastName && touched.lastName ? 'input-error' : ''}`}
                />
              </div>
              {errors.lastName && touched.lastName && (
                <span className="error-message">{errors.lastName}</span>
              )}
            </div>

            {/* Email Field */}
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

            {/* Password Field */}
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

              {/* Password Strength Indicator */}
              {formData.password && (
                <div className="password-strength">
                  <div className="strength-bar">
                    <div
                      className="strength-fill"
                      style={{
                        width: `${(passwordStrength / 5) * 100}%`,
                        backgroundColor: passwordStrengthColor[passwordStrength - 1]
                      }}
                    />
                  </div>
                  <span className="strength-text" style={{ color: passwordStrengthColor[passwordStrength - 1] }}>
                    {passwordStrengthLabel[passwordStrength - 1]}
                  </span>
                </div>
              )}

              {/* Password Requirements */}
              {formData.password && (
                <div className="password-requirements">
                  <div className={`requirement ${formData.password.length >= VALIDATION_RULES.password.minLength ? 'met' : ''}`}>
                    {formData.password.length >= VALIDATION_RULES.password.minLength ? <FiCheck size={14} /> : <FiX size={14} />}
                    <span>At least 8 characters</span>
                  </div>
                  <div className={`requirement ${VALIDATION_RULES.password.hasUpperCase.test(formData.password) ? 'met' : ''}`}>
                    {VALIDATION_RULES.password.hasUpperCase.test(formData.password) ? <FiCheck size={14} /> : <FiX size={14} />}
                    <span>One uppercase letter</span>
                  </div>
                  <div className={`requirement ${VALIDATION_RULES.password.hasLowerCase.test(formData.password) ? 'met' : ''}`}>
                    {VALIDATION_RULES.password.hasLowerCase.test(formData.password) ? <FiCheck size={14} /> : <FiX size={14} />}
                    <span>One lowercase letter</span>
                  </div>
                  <div className={`requirement ${VALIDATION_RULES.password.hasNumber.test(formData.password) ? 'met' : ''}`}>
                    {VALIDATION_RULES.password.hasNumber.test(formData.password) ? <FiCheck size={14} /> : <FiX size={14} />}
                    <span>One number</span>
                  </div>
                  <div className={`requirement ${VALIDATION_RULES.password.hasSpecial.test(formData.password) ? 'met' : ''}`}>
                    {VALIDATION_RULES.password.hasSpecial.test(formData.password) ? <FiCheck size={14} /> : <FiX size={14} />}
                    <span>One special character</span>
                  </div>
                </div>
              )}

              {errors.password && touched.password && (
                <span className="error-message">{errors.password}</span>
              )}
            </div>

            {/* Confirm Password Field */}
            <div className="tutorial-form-group">
              <label htmlFor="confirmPassword">Confirm Password</label>
              <div className="tutorial-input-wrapper">
                <FiLock className="tutorial-input-icon" />
                <input
                  type={showConfirmPassword ? 'text' : 'password'}
                  id="confirmPassword"
                  name="confirmPassword"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  placeholder="Confirm password"
                  disabled={loading}
                  className={`${errors.confirmPassword && touched.confirmPassword ? 'input-error' : ''}`}
                />
                <button
                  type="button"
                  className="tutorial-password-toggle"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  disabled={loading}
                  tabIndex="-1"
                >
                  {showConfirmPassword ? <FiEyeOff size={18} /> : <FiEye size={18} />}
                </button>
              </div>
              {errors.confirmPassword && touched.confirmPassword && (
                <span className="error-message">{errors.confirmPassword}</span>
              )}
            </div>

            <button
              type="submit"
              className="tutorial-register-btn"
              disabled={loading || !formData.firstName.trim() || !formData.lastName.trim() || !formData.email.trim() || !formData.password.trim() || !formData.confirmPassword.trim() || Object.keys(errors).length > 0}
            >
              {loading ? 'Creating Account...' : 'Create Account'}
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
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
            </svg>
            Sign up with Google
          </button>

            <div className="tutorial-register-footer">
              <p>Already have an account? <Link to="/tutorial-login">Sign in</Link></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
