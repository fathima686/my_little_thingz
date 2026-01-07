import React, { useState, useEffect } from 'react';
import { LuX, LuCheck, LuCrown, LuStar, LuZap, LuVideo, LuUpload, LuAward, LuUsers, LuClock } from 'react-icons/lu';
import '../styles/subscription-plans.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const SubscriptionPlansModal = ({ isOpen, onClose, userEmail, onSubscriptionUpdate }) => {
  const [loading, setLoading] = useState(false);
  const [currentPlan, setCurrentPlan] = useState('basic');
  const [subscriptionStatus, setSubscriptionStatus] = useState(null);

  useEffect(() => {
    if (isOpen && userEmail) {
      fetchSubscriptionStatus();
    }
  }, [isOpen, userEmail]);

  const fetchSubscriptionStatus = async () => {
    try {
      const response = await fetch(`${API_BASE}/customer/subscription-status.php`, {
        headers: {
          'X-Tutorial-Email': userEmail
        }
      });
      const data = await response.json();
      if (data.status === 'success') {
        setCurrentPlan(data.plan_code || 'basic');
        setSubscriptionStatus(data);
      }
    } catch (error) {
      console.error('Error fetching subscription status:', error);
    }
  };

  const handleUpgrade = async (planCode) => {
    if (planCode === currentPlan) return;
    
    setLoading(true);
    try {
      if (planCode === 'pro') {
        // Redirect to Razorpay payment for Pro plan
        const response = await fetch(`${API_BASE}/customer/create-subscription.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Tutorial-Email': userEmail
          },
          body: JSON.stringify({
            plan_code: 'pro',
            email: userEmail
          })
        });
        
        const data = await response.json();
        if (data.status === 'success' && data.payment_link) {
          // Redirect to Razorpay payment
          window.open(data.payment_link, '_blank');
          onClose();
        } else {
          alert('Error creating subscription: ' + (data.message || 'Unknown error'));
        }
      } else {
        // For Basic and Premium, update directly
        const response = await fetch(`${API_BASE}/customer/upgrade-subscription.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Tutorial-Email': userEmail
          },
          body: JSON.stringify({
            plan_code: planCode,
            email: userEmail
          })
        });
        
        const data = await response.json();
        if (data.status === 'success') {
          setCurrentPlan(planCode);
          onSubscriptionUpdate && onSubscriptionUpdate(planCode);
          alert('Subscription updated successfully!');
        } else {
          alert('Error updating subscription: ' + (data.message || 'Unknown error'));
        }
      }
    } catch (error) {
      console.error('Error upgrading subscription:', error);
      alert('Error upgrading subscription. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const plans = [
    {
      code: 'basic',
      name: 'Basic',
      price: 0,
      duration: 'Free Forever',
      color: '#6B7280',
      icon: LuVideo,
      features: [
        'Limited video access (preview only)',
        'Basic video quality',
        'Community support',
        'Access to free tutorials'
      ],
      limitations: [
        'Cannot watch complete videos',
        'No live classes access',
        'No practice uploads',
        'No certificates'
      ]
    },
    {
      code: 'premium',
      name: 'Premium',
      price: 499,
      duration: 'per month',
      color: '#3B82F6',
      icon: LuStar,
      popular: true,
      features: [
        'Full video access (watch complete videos)',
        'HD video quality',
        'Download videos for offline viewing',
        'Priority support',
        'Weekly new content',
        'Access to all tutorials'
      ],
      limitations: [
        'No live classes access',
        'No practice uploads',
        'No certificates',
        'No 1-on-1 mentorship'
      ]
    },
    {
      code: 'pro',
      name: 'Pro',
      price: 999,
      duration: 'per month',
      color: '#10B981',
      icon: LuCrown,
      premium: true,
      features: [
        'Everything in Premium',
        'Access to live classes (Google Meet links)',
        'Upload practice images',
        'Progress tracking with certificates',
        '1-on-1 mentorship sessions',
        'Early access to new content',
        'Certificate generation on 100% completion'
      ],
      proFeatures: [
        'Live workshops access',
        'Practice work uploads',
        'Progress tracking',
        'Certificate generation',
        'Mentorship sessions'
      ]
    }
  ];

  if (!isOpen) return null;

  return (
    <div className="subscription-modal-overlay">
      <div className="subscription-modal">
        <div className="subscription-modal-header">
          <h2>Choose Your Learning Plan</h2>
          <button className="close-button" onClick={onClose}>
            <LuX size={24} />
          </button>
        </div>

        <div className="subscription-plans-grid">
          {plans.map((plan) => {
            const Icon = plan.icon;
            const isCurrentPlan = currentPlan === plan.code;
            const canUpgrade = plan.code !== currentPlan;
            
            return (
              <div 
                key={plan.code} 
                className={`subscription-plan-card ${isCurrentPlan ? 'current-plan' : ''} ${plan.popular ? 'popular' : ''} ${plan.premium ? 'premium' : ''}`}
                style={{ '--plan-color': plan.color }}
              >
                {plan.popular && <div className="plan-badge">Most Popular</div>}
                {plan.premium && <div className="plan-badge premium-badge">Best Value</div>}
                {isCurrentPlan && <div className="current-plan-badge">Current Plan</div>}
                
                <div className="plan-header">
                  <div className="plan-icon">
                    <Icon size={32} />
                  </div>
                  <h3>{plan.name}</h3>
                  <div className="plan-price">
                    {plan.price === 0 ? (
                      <span className="price-free">Free</span>
                    ) : (
                      <>
                        <span className="price-currency">₹</span>
                        <span className="price-amount">{plan.price}</span>
                        <span className="price-duration">/{plan.duration}</span>
                      </>
                    )}
                  </div>
                </div>

                <div className="plan-features">
                  <h4>Features Included:</h4>
                  <ul className="features-list">
                    {plan.features.map((feature, index) => (
                      <li key={index} className="feature-item">
                        <LuCheck size={16} className="feature-check" />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>

                  {plan.code === 'pro' && (
                    <div className="pro-features-highlight">
                      <h4>Pro Exclusive Features:</h4>
                      <ul className="pro-features-list">
                        {plan.proFeatures.map((feature, index) => (
                          <li key={index} className="pro-feature-item">
                            <LuZap size={16} className="pro-feature-icon" />
                            <span>{feature}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}

                  {plan.limitations && (
                    <div className="plan-limitations">
                      <h4>Limitations:</h4>
                      <ul className="limitations-list">
                        {plan.limitations.map((limitation, index) => (
                          <li key={index} className="limitation-item">
                            <span>{limitation}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>

                <div className="plan-action">
                  {isCurrentPlan ? (
                    <button className="plan-button current" disabled>
                      Current Plan
                    </button>
                  ) : (
                    <button 
                      className={`plan-button ${plan.code === 'pro' ? 'pro-button' : 'upgrade-button'}`}
                      onClick={() => handleUpgrade(plan.code)}
                      disabled={loading}
                    >
                      {loading ? 'Processing...' : 
                       plan.code === 'pro' ? 'Upgrade to Pro' : 
                       plan.price === 0 ? 'Downgrade to Basic' : 'Upgrade to Premium'}
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>

        <div className="subscription-modal-footer">
          <div className="payment-info">
            <p><strong>Secure Payment:</strong> All payments are processed securely through Razorpay</p>
            <p><strong>Cancel Anytime:</strong> You can cancel or change your subscription at any time</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SubscriptionPlansModal;