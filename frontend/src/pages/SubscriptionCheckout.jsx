import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { LuArrowLeft } from 'react-icons/lu';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import '../styles/subscription-checkout.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const PLANS = {
  basic: {
    name: 'Basic',
    priceLabel: '₹199 / month',
    price: 199,
    features: ['Access to basic tutorials', 'Standard video quality', 'Community support', 'Mobile access'],
  },
  premium: {
    name: 'Premium',
    priceLabel: '₹499 / month',
    price: 499,
    features: ['Unlimited tutorial access', 'HD video quality', 'New content weekly', 'Priority support', 'Download videos'],
  },
  pro: {
    name: 'Pro',
    priceLabel: '₹999 / month',
    price: 999,
    features: ['Everything in Premium', '1-on-1 mentorship', 'Live workshops', 'Certificate of completion', 'Early access to new content'],
  },
};

export default function SubscriptionCheckout() {
  const navigate = useNavigate();
  const location = useLocation();
  const { tutorialAuth } = useTutorialAuth();
  const [planCode, setPlanCode] = useState('basic');
  const [email, setEmail] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('card');
  const [isAnnual, setIsAnnual] = useState(false);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Get plan from URL params or location state
    const params = new URLSearchParams(location.search);
    const plan = params.get('plan') || location.state?.plan || 'basic';
    setPlanCode(plan);
    
    // Set email from auth context
    if (tutorialAuth?.email) {
      setEmail(tutorialAuth.email);
    }
  }, [location, tutorialAuth]);

  const plan = PLANS[planCode] || PLANS.basic;
  const monthlyPrice = plan.price;
  const annualPrice = Math.round(monthlyPrice * 12 * 0.8); // 20% discount
  const displayPrice = isAnnual ? annualPrice : monthlyPrice;
  const priceLabel = isAnnual ? `₹${annualPrice} / year` : plan.priceLabel;
  const monthlyEquivalent = isAnnual ? `₹${Math.round(annualPrice / 12)} / month` : null;

  const handleSubscribe = async () => {
    if (!email || !email.trim()) {
      alert('Please enter your email address');
      return;
    }

    setLoading(true);
    
    try {
      const requestBody = {
        plan_code: planCode,
        billing_period: isAnnual ? 'yearly' : 'monthly'
      };
      
      const res = await fetch(`${API_BASE}/customer/create-subscription.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Tutorial-Email': email.trim()
        },
        body: JSON.stringify(requestBody)
      });

      if (!res.ok) {
        const errorText = await res.text();
        console.error('Subscription API error:', errorText);
        alert(`Failed to create subscription (${res.status}). Please try again.`);
        setLoading(false);
        return;
      }

      const responseText = await res.text();
      
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        alert('Invalid response from server. Please try again.');
        setLoading(false);
        return;
      }
      
      if (data.status !== 'success') {
        console.error('Subscription creation failed:', data);
        alert(data.message || 'Failed to create subscription. Please try again.');
        setLoading(false);
        return;
      }

      // If already active, just redirect
      if (data.subscription_status === 'active') {
        if (data.subscription_status === 'active') {
          alert('You already have an active ' + (data.plan_code || 'subscription') + ' plan!');
        }
        navigate('/tutorials?subscription=success');
        return;
      }

      // For paid plans, handle Razorpay checkout
      if (data.razorpay_order_id || data.razorpay_subscription_id) {
        if (data.short_url) {
          window.location.href = data.short_url;
          return;
        }
        
        // Load Razorpay script if not already loaded
        if (!window.Razorpay) {
          const script = document.createElement('script');
          script.src = 'https://checkout.razorpay.com/v1/checkout.js';
          script.onload = () => {
            openRazorpayCheckout(data);
          };
          script.onerror = () => {
            alert('Failed to load payment gateway. Please check your internet connection and try again.');
            setLoading(false);
          };
          document.body.appendChild(script);
        } else {
          openRazorpayCheckout(data);
        }
      } else {
        alert('Failed to create subscription - no payment ID received. Please try again.');
        setLoading(false);
      }
    } catch (error) {
      console.error('Subscription error:', error);
      alert('An error occurred during subscription: ' + error.message);
      setLoading(false);
    }
  };

  const openRazorpayCheckout = (paymentData) => {
    const razorpayKey = paymentData.razorpay_key || import.meta.env.VITE_RAZORPAY_KEY || 'rzp_test_RGXWGOBliVCIpU';
    
    if (!window.Razorpay) {
      alert('Payment gateway not loaded. Please refresh and try again.');
      setLoading(false);
      return;
    }
    
    // Determine if this is order-based or subscription-based payment
    const isOrderPayment = paymentData.payment_type === 'order' || paymentData.razorpay_order_id;
    const paymentId = paymentData.razorpay_order_id || paymentData.razorpay_subscription_id;
    
    const options = {
      key: razorpayKey,
      name: 'My Little Thingz',
      description: `Subscribe to ${plan.name} Plan - ${priceLabel}`,
      prefill: {
        email: email || '',
      },
      theme: {
        color: '#5a9bb8'
      },
      handler: async function(response) {
        // Handle payment verification
        try {
          let verifyEndpoint, verifyData;
          
          if (isOrderPayment) {
            // For order-based payments, use subscription order verification endpoint
            verifyEndpoint = `${API_BASE}/customer/subscription-order-verify.php`;
            verifyData = {
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_order_id: response.razorpay_order_id || paymentId,
              razorpay_signature: response.razorpay_signature,
              subscription_id: paymentData.subscription_id,
              plan_code: paymentData.plan_code
            };
          } else {
            // For subscription-based payments, use subscription verification endpoint
            verifyEndpoint = `${API_BASE}/customer/subscription-verify.php`;
            verifyData = {
              razorpay_subscription_id: response.razorpay_subscription_id || paymentId,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature
            };
          }
          
          const verifyRes = await fetch(verifyEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Tutorial-Email': email || ''
            },
            body: JSON.stringify(verifyData)
          });

          const verifyResult = await verifyRes.json();
          
          if (verifyResult.status === 'success') {
            alert('Payment successful! Welcome to ' + plan.name + ' plan!');
            navigate('/tutorials?subscription=success');
          } else {
            console.error('Payment verification failed:', verifyResult);
            alert('Payment verification failed. Please contact support with your payment ID: ' + (response.razorpay_payment_id || 'N/A'));
            setLoading(false);
          }
        } catch (error) {
          console.error('Verification error:', error);
          alert('Payment verification failed. Please contact support with your payment ID: ' + (response.razorpay_payment_id || 'N/A'));
          setLoading(false);
        }
      },
      modal: {
        ondismiss: function() {
          setLoading(false);
        }
      }
    };
    
    // Add order_id or subscription_id based on payment type
    if (isOrderPayment) {
      options.order_id = paymentId;
      options.amount = paymentData.amount;
      options.currency = paymentData.currency || 'INR';
    } else {
      options.subscription_id = paymentId;
    }
    
    try {
      const razorpay = new window.Razorpay(options);
      razorpay.open();
    } catch (error) {
      console.error('Error creating/opening Razorpay instance:', error);
      alert('Failed to open payment gateway: ' + error.message);
      setLoading(false);
    }
  };

  return (
    <div className="subscription-checkout-page">
      <div className="checkout-container">
        {/* Left Column - Subscription Details */}
        <div className="checkout-left-column">
          <button className="checkout-back-btn" onClick={() => navigate('/tutorials')}>
            <LuArrowLeft size={20} />
            <span>Back</span>
          </button>
          
          <div className="checkout-left-content">
            <h1>Subscribe to {plan.name}</h1>
            <p className="checkout-main-price">{priceLabel}</p>
            
            {/* Plan Card */}
            <div className="checkout-plan-card">
              <div className="plan-card-icon">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                  <rect width="40" height="40" rx="8" fill="#5a9bb8" opacity="0.1"/>
                  <path d="M20 10L25 15L20 20L15 15L20 10Z" fill="#5a9bb8"/>
                  <path d="M25 15L30 20L25 25L20 20L25 15Z" fill="#5a9bb8"/>
                  <path d="M15 15L20 20L15 25L10 20L15 15Z" fill="#5a9bb8"/>
                  <path d="M20 20L25 25L20 30L15 25L20 20Z" fill="#5a9bb8"/>
                </svg>
              </div>
              <h2>{plan.name}</h2>
              <p className="plan-description">
                {planCode === 'basic' 
                  ? 'Perfect for getting started with craft tutorials'
                  : planCode === 'premium' 
                  ? 'Unlock unlimited access to all craft tutorials'
                  : planCode === 'pro'
                  ? 'Everything in Premium plus mentorship and exclusive content'
                  : 'Access to craft tutorials'}
              </p>
              <p className="plan-billing">Billed monthly</p>
              <p className="plan-price-display">₹{displayPrice}</p>
            </div>

            {/* Annual Billing Toggle */}
            {planCode !== 'basic' && (
              <div className="annual-billing-toggle">
                <label className="toggle-switch">
                  <input 
                    type="checkbox" 
                    checked={isAnnual}
                    onChange={(e) => setIsAnnual(e.target.checked)}
                  />
                  <span className="toggle-slider"></span>
                </label>
                <div className="toggle-content">
                  <span className="toggle-label">Save {Math.round((monthlyPrice * 12 - annualPrice) / 100)}% with annual billing</span>
                  {isAnnual && monthlyEquivalent && (
                    <span className="toggle-price">{monthlyEquivalent}</span>
                  )}
                </div>
              </div>
            )}

            {/* Order Summary */}
            <div className="checkout-summary">
              <div className="summary-row">
                <span>Subtotal</span>
                <span>₹{displayPrice}</span>
              </div>
              <div className="summary-row">
                <span>
                  Tax
                  <span className="info-icon" title="Tax will be calculated based on your location">ℹ️</span>
                </span>
                <span className="summary-placeholder">Enter address to calculate</span>
              </div>
              <div className="summary-row summary-total">
                <span>Total due today</span>
                <span>₹{displayPrice}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Right Column - Contact & Payment */}
        <div className="checkout-right-column">
          <div className="checkout-right-content">
            {/* Contact Information */}
            <div className="checkout-section">
              <h3>Contact information</h3>
              <div className="checkout-field">
                <label>Email</label>
                <input 
                  type="email" 
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="your@email.com"
                />
              </div>
            </div>

            {/* Payment Method */}
            <div className="checkout-section">
              <h3>Payment method</h3>
              <div className="payment-methods">
                <label className="payment-method-option">
                  <input 
                    type="radio" 
                    name="paymentMethod" 
                    value="card"
                    checked={paymentMethod === 'card'}
                    onChange={(e) => setPaymentMethod(e.target.value)}
                  />
                  <div className="payment-method-content">
                    <span className="payment-method-label">Card</span>
                    <div className="payment-method-icons">
                      <span>Visa</span>
                      <span>Mastercard</span>
                      <span>Amex</span>
                      <span>JCB</span>
                      <span>Discover</span>
                    </div>
                  </div>
                </label>
                
                <label className="payment-method-option disabled">
                  <input type="radio" name="paymentMethod" value="upi" disabled />
                  <div className="payment-method-content">
                    <span className="payment-method-label">UPI / Wallet</span>
                    <span className="coming-soon">(coming soon)</span>
                  </div>
                </label>
              </div>
            </div>

            {/* Save Information */}
            <div className="checkout-save-info">
              <label className="save-info-checkbox">
                <input type="checkbox" />
                <span>Save my information for faster checkout</span>
              </label>
              <p className="save-info-note">Pay securely at My Little Thingz and everywhere Link is accepted.</p>
            </div>

            <button 
              className="checkout-subscribe-btn"
              onClick={handleSubscribe}
              disabled={loading || !email?.trim()}
            >
              {loading ? 'Processing...' : 'Subscribe'}
            </button>

            {/* Legal Text */}
            <p className="checkout-legal">
              By subscribing, you authorize My Little Thingz to charge you according to the terms until you cancel.
            </p>

            {/* Footer */}
            <div className="checkout-footer">
              <span>Powered by Razorpay</span>
              <span>•</span>
              <a href="/terms">Terms</a>
              <span>•</span>
              <a href="/privacy">Privacy</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}


