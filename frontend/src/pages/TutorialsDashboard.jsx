import React, { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { LuPlay, LuLock, LuCheck, LuArrowLeft, LuLogOut, LuHeart, LuBell, LuUser, LuCrown, LuArrowRight, LuBookOpen, LuTrendingUp, LuClock, LuStar, LuSparkles, LuZap, LuInfinity, LuShield, LuVideo, LuPlus, LuUpload, LuAward } from 'react-icons/lu';
import logo from '../assets/logo.png';
import LiveSessionsList from '../components/live-teaching/LiveSessionsList';
import CreateLiveSessionModal from '../components/live-teaching/CreateLiveSessionModal';
import NotificationDropdown from '../components/NotificationDropdown';
import ProfileDropdown from '../components/ProfileDropdown';
import SubscriptionPlansModal from '../components/SubscriptionPlansModal';
import ProgressTracker from '../components/ProgressTracker';
import PracticeUpload from '../components/PracticeUpload';
import handEmbroideryImg from '../assets/hand embroary.jpeg';
import resinArtImg from '../assets/resin.jpeg';
import giftMakingImg from '../assets/gift making.jpeg';
import mehandiImg from '../assets/mehandi.jpeg';
import candleMakingImg from '../assets/candle making.jpeg';
import jewelryMakingImg from '../assets/jewelary making.jpeg';
import clayModelingImg from '../assets/clay modeling.jpeg';
import '../styles/tutorials.css';
import '../styles/subscription-plans.css';
import '../styles/progress-tracker.css';
import '../styles/practice-upload.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';
const UPLOADS_BASE = 'http://localhost/my_little_thingz/backend';

// Helper function to get full thumbnail URL
const getThumbnailUrl = (thumbnailUrl) => {
  if (!thumbnailUrl) return null;
  // If it's already a full URL, return as is
  if (thumbnailUrl.startsWith('http://') || thumbnailUrl.startsWith('https://')) {
    return thumbnailUrl;
  }
  // If it's a relative path, convert to full URL
  return `${UPLOADS_BASE}/${thumbnailUrl}`;
};

// Learning Stats Banner Component
const LearningStatsBanner = ({ stats, subscriptionStatus }) => {
  const isPro = subscriptionStatus?.plan_code === 'pro' || subscriptionStatus?.plan_code === 'premium';
  
  return (
    <div className="stats-banner">
      <div className="stats-card">
        <div className="stats-icon">
          <LuBookOpen size={24} />
        </div>
        <div className="stats-content">
          <div className="stats-number">{stats.purchasedCount}</div>
          <div className="stats-label">
            {isPro ? 'Watched Tutorials' : 'Tutorials Purchased'}
          </div>
        </div>
      </div>
      <div className="stats-card">
        <div className="stats-icon">
          <LuCheck size={24} />
        </div>
        <div className="stats-content">
          <div className="stats-number">{stats.completedCount}</div>
          <div className="stats-label">Completed</div>
        </div>
      </div>
      <div className="stats-card">
        <div className="stats-icon">
          <LuClock size={24} />
        </div>
        <div className="stats-content">
          <div className="stats-number">{stats.totalHours}</div>
          <div className="stats-label">Hours Learned</div>
        </div>
      </div>
    </div>
  );
};

// Featured Section Component
const FeaturedSection = ({ tutorials, purchases, handleWatchTutorial, handlePurchase, hasAccessToTutorial }) => (
  <section className="featured-section">
    <div className="section-header">
      <div className="section-title">
        <LuSparkles size={24} />
        <h2>Featured Tutorials</h2>
      </div>
      <p>Handpicked premium courses to get you started</p>
    </div>
    <div className="featured-grid">
      {tutorials.slice(0, 3).map((tutorial) => {
        const hasAccess = hasAccessToTutorial(tutorial);
        const isFree = tutorial.is_free || tutorial.price === 0;
        return (
          <div key={tutorial.id} className="featured-card">
            <div className="featured-badge">Featured</div>
            <div className="featured-thumbnail">
              {tutorial.thumbnail_url ? (
                <img src={getThumbnailUrl(tutorial.thumbnail_url)} alt={tutorial.title} />
              ) : (
                <div className="featured-placeholder"></div>
              )}
              <div className="featured-overlay">
                {hasAccess ? (
                  <button className="featured-play-btn" onClick={() => handleWatchTutorial(tutorial)}>
                    <LuPlay size={28} />
                  </button>
                ) : (
                  <button className="featured-purchase-btn" onClick={() => handlePurchase(tutorial)}>
                    Purchase
                  </button>
                )}
              </div>
            </div>
            <div className="featured-content">
              <h3>{tutorial.title}</h3>
              <div className="featured-meta">
                <span>{tutorial.duration || 'N/A'} min</span>
                {!isFree && <span className="featured-price">₹{tutorial.price}</span>}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  </section>
);

// Popular Section Component
const PopularSection = ({ tutorials, purchases, favorites, toggleFavorite, handleWatchTutorial, handlePurchase, hasAccessToTutorial }) => (
  <section className="popular-section">
    <div className="section-header">
      <div className="section-title">
        <LuTrendingUp size={24} />
        <h2>Popular This Week</h2>
      </div>
      <p>Most watched tutorials by our community</p>
    </div>
    <div className="popular-grid">
      {tutorials.slice(0, 6).map((tutorial) => {
        const hasAccess = hasAccessToTutorial(tutorial);
        const isFree = tutorial.is_free || tutorial.price === 0;
        const isFavorite = favorites.has(tutorial.id);
        return (
          <div key={tutorial.id} className="popular-card">
            <div className="popular-thumbnail">
              {tutorial.thumbnail_url ? (
                <img src={getThumbnailUrl(tutorial.thumbnail_url)} alt={tutorial.title} />
              ) : (
                <div className="popular-placeholder"></div>
              )}
              <button
                className={`popular-favorite ${isFavorite ? 'active' : ''}`}
                onClick={(e) => {
                  e.stopPropagation();
                  toggleFavorite(tutorial.id);
                }}
              >
                <LuHeart size={16} />
              </button>
              <div className="popular-overlay">
                {hasAccess ? (
                  <button className="popular-play" onClick={() => handleWatchTutorial(tutorial)}>
                    <LuPlay size={20} />
                  </button>
                ) : (
                  <div className="popular-locked">
                    <LuLock size={20} />
                  </div>
                )}
              </div>
            </div>
            <div className="popular-content">
              <h4>{tutorial.title}</h4>
              <div className="popular-footer">
                {isFree ? (
                  <span className="popular-free">Free</span>
                ) : (
                  <span className="popular-price">₹{tutorial.price}</span>
                )}
                <div className="popular-rating">
                  <LuStar size={14} />
                  <span>4.8</span>
                </div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  </section>
);

// Subscription Benefits Section
const SubscriptionBenefitsSection = () => (
  <section className="benefits-section">
    <div className="benefits-container">
      <div className="benefits-header">
        <h2>Why Choose Premium?</h2>
        <p>Unlock unlimited access to all craft tutorials</p>
      </div>
      <div className="benefits-grid">
        <div className="benefit-card">
          <div className="benefit-icon">
            <LuInfinity size={32} />
          </div>
          <h3>Unlimited Access</h3>
          <p>Watch all tutorials without restrictions</p>
        </div>
        <div className="benefit-card">
          <div className="benefit-icon">
            <LuZap size={32} />
          </div>
          <h3>New Content Weekly</h3>
          <p>Fresh tutorials added every week</p>
        </div>
        <div className="benefit-card">
          <div className="benefit-icon">
            <LuShield size={32} />
          </div>
          <h3>HD Quality Videos</h3>
          <p>Premium video quality for better learning</p>
        </div>
        <div className="benefit-card">
          <div className="benefit-icon">
            <LuSparkles size={32} />
          </div>
          <h3>Exclusive Content</h3>
          <p>Access to premium-only tutorials</p>
        </div>
      </div>
    </div>
  </section>
);

// Subscription Plans Section
const SubscriptionPlansSection = ({ subscriptionPlan, setSubscriptionPlan, subscriptionStatus, onUpgradeClick }) => {
  const handleSubscriptionUpgrade = (plan) => {
    // Check if user already has this plan and it's active
    const currentPlan = subscriptionStatus?.plan_code || subscriptionPlan;
    const isActive = subscriptionStatus?.subscription_status === 'active' && subscriptionStatus?.is_active;
    
    if (currentPlan === plan && isActive) {
      // User already has this active plan - show message instead of trying to upgrade
      alert(`You already have an active ${plan.charAt(0).toUpperCase() + plan.slice(1)} subscription!`);
      return;
    }
    
    // Proceed with upgrade/change
    onUpgradeClick(plan);
  };

  // Helper function to determine button text and state
  const getButtonConfig = (planCode) => {
    const currentPlan = subscriptionStatus?.plan_code || subscriptionPlan;
    const isActive = subscriptionStatus?.subscription_status === 'active' && subscriptionStatus?.is_active;
    
    if (currentPlan === planCode && isActive) {
      return {
        text: 'Current Plan',
        disabled: true,
        className: 'plan-btn current'
      };
    } else if (currentPlan === planCode && !isActive) {
      return {
        text: 'Reactivate Plan',
        disabled: false,
        className: 'plan-btn reactivate'
      };
    } else {
      const actionText = {
        'basic': 'Select Basic',
        'premium': 'Upgrade Now',
        'pro': 'Upgrade to Pro'
      };
      return {
        text: actionText[planCode] || 'Select Plan',
        disabled: false,
        className: planCode === 'premium' ? 'plan-btn primary' : 'plan-btn'
      };
    }
  };

  return (
  <div className="subscription-plans-view">
    <div className="plans-header">
      <h1>Choose Your Plan</h1>
      <p>Select the perfect plan for your craft learning journey</p>
      {subscriptionStatus && (
        <div className="current-subscription-info">
          <p>Current: <strong>{subscriptionStatus.plan_code?.charAt(0).toUpperCase() + subscriptionStatus.plan_code?.slice(1)} Plan</strong> 
          ({subscriptionStatus.subscription_status === 'active' ? 'Active' : 'Inactive'})</p>
        </div>
      )}
    </div>
    <div className="plans-grid">
      <div className={`plan-card ${subscriptionPlan === 'basic' ? 'selected' : ''}`}>
        <div className="plan-header">
          <h3>Basic</h3>
          <div className="plan-price">
            <span className="price-amount">₹199</span>
            <span className="price-period">/month</span>
          </div>
        </div>
        <ul className="plan-features">
          <li><LuCheck size={18} /> Access to free tutorials</li>
          <li><LuCheck size={18} /> Individual tutorial purchases</li>
          <li><LuCheck size={18} /> Standard video quality</li>
          <li><LuCheck size={18} /> Community support</li>
          <li><LuCheck size={18} /> Mobile access</li>
        </ul>
        {(() => {
          const config = getButtonConfig('basic');
          return (
            <button 
              className={config.className}
              onClick={() => handleSubscriptionUpgrade('basic')}
              disabled={config.disabled}
            >
              {config.text}
            </button>
          );
        })()}
      </div>

      <div className={`plan-card featured ${subscriptionPlan === 'premium' ? 'selected' : ''}`}>
        <div className="plan-badge">Most Popular</div>
        <div className="plan-header">
          <h3>Premium</h3>
          <div className="plan-price">
            <span className="price-amount">₹499</span>
            <span className="price-period">/month</span>
          </div>
        </div>
        <ul className="plan-features">
          <li><LuCheck size={18} /> Access to ALL tutorials</li>
          <li><LuCheck size={18} /> HD video quality</li>
          <li><LuCheck size={18} /> Weekly new content</li>
          <li><LuCheck size={18} /> Priority support</li>
          <li><LuCheck size={18} /> Download videos</li>
        </ul>
        {(() => {
          const config = getButtonConfig('premium');
          return (
            <button 
              className={config.className}
              onClick={() => handleSubscriptionUpgrade('premium')}
              disabled={config.disabled}
            >
              {config.text}
            </button>
          );
        })()}
      </div>

      <div className={`plan-card ${subscriptionPlan === 'pro' ? 'selected' : ''}`}>
        <div className="plan-header">
          <h3>Pro</h3>
          <div className="plan-price">
            <span className="price-amount">₹999</span>
            <span className="price-period">/month</span>
          </div>
        </div>
        <ul className="plan-features">
          <li><LuCheck size={18} /> Access to ALL tutorials</li>
          <li><LuCheck size={18} /> HD video quality</li>
          <li><LuCheck size={18} /> Weekly new content</li>
          <li><LuCheck size={18} /> Priority support</li>
          <li><LuCheck size={18} /> Download videos</li>
          <li><LuCheck size={18} /> Live workshops</li>
          <li><LuCheck size={18} /> 1-on-1 mentorship</li>
          <li><LuCheck size={18} /> Certificate of completion</li>
          <li><LuCheck size={18} /> Early access to new content</li>
        </ul>
        {(() => {
          const config = getButtonConfig('pro');
          return (
            <button 
              className={config.className}
              onClick={() => handleSubscriptionUpgrade('pro')}
              disabled={config.disabled}
            >
              {config.text}
            </button>
          );
        })()}
      </div>
    </div>
  </div>
  );
};

// My Learning Section
const MyLearningSection = ({ tutorials, purchases, favorites, toggleFavorite, handleWatchTutorial, getLearningStats, getPurchasedTutorials, subscriptionStatus }) => {
  const stats = getLearningStats();
  const purchasedTutorials = getPurchasedTutorials();
  const favoriteTutorials = tutorials.filter(t => favorites.has(t.id));
  const isPro = subscriptionStatus?.subscription?.plan_code === 'pro' || subscriptionStatus?.plan_code === 'pro';

  return (
    <div className="my-learning-view">
      <div className="learning-header">
        <h1>My Learning Dashboard</h1>
        <p>Track your progress and continue your craft journey</p>
      </div>

      <LearningStatsBanner stats={stats} subscriptionStatus={subscriptionStatus} />

      {purchasedTutorials.length > 0 && (
        <section className="learning-section">
          <div className="section-header">
            <div className="section-title">
              <LuBookOpen size={24} />
              <h2>{isPro ? 'My Watched Videos' : 'My Purchased Tutorials'}</h2>
            </div>
          </div>
          <div className="learning-grid">
            {purchasedTutorials.map((tutorial) => {
              const progress = Math.floor(Math.random() * 100);
              return (
                <div key={tutorial.id} className="learning-card">
                  <div className="learning-thumbnail">
                    {tutorial.thumbnail_url ? (
                      <img src={getThumbnailUrl(tutorial.thumbnail_url)} alt={tutorial.title} />
                    ) : (
                      <div className="learning-placeholder"></div>
                    )}
                    <div className="learning-overlay">
                      <button className="learning-play" onClick={() => handleWatchTutorial(tutorial)}>
                        <LuPlay size={24} />
                      </button>
                    </div>
                    <div className="learning-progress">
                      <div className="progress-bar" style={{ width: `${progress}%` }}></div>
                    </div>
                  </div>
                  <div className="learning-content">
                    <h3>{tutorial.title}</h3>
                    <div className="learning-meta">
                      <span>{progress}% Complete</span>
                      <span>{tutorial.duration || 'N/A'} min</span>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      )}

      {favoriteTutorials.length > 0 && (
        <section className="learning-section">
          <div className="section-header">
            <div className="section-title">
              <LuHeart size={24} />
              <h2>My Favorites</h2>
            </div>
          </div>
          <div className="learning-grid">
            {favoriteTutorials.map((tutorial) => (
              <div key={tutorial.id} className="learning-card">
                <div className="learning-thumbnail">
                  {tutorial.thumbnail_url ? (
                    <img src={getThumbnailUrl(tutorial.thumbnail_url)} alt={tutorial.title} />
                  ) : (
                    <div className="learning-placeholder"></div>
                  )}
                  <button
                    className="learning-favorite active"
                    onClick={() => toggleFavorite(tutorial.id)}
                  >
                    <LuHeart size={18} />
                  </button>
                </div>
                <div className="learning-content">
                  <h3>{tutorial.title}</h3>
                  <div className="learning-meta">
                    <span>{tutorial.duration || 'N/A'} min</span>
                    {!tutorial.is_free && <span>₹{tutorial.price}</span>}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      )}
    </div>
  );
};

const TUTORIAL_CATEGORIES = [
  {
    id: 'hand-embroidery',
    name: 'Hand Embroidery',
    image: handEmbroideryImg,
    color: '#FFB6C1'
  },
  {
    id: 'resin-art',
    name: 'Resin Art',
    image: resinArtImg,
    color: '#B0E0E6'
  },
  {
    id: 'gift-making',
    name: 'Gift Making',
    image: giftMakingImg,
    color: '#FFDAB9'
  },
  {
    id: 'mylanchi-mehandi',
    name: 'Mylanchi / Mehandi Art',
    image: mehandiImg,
    color: '#E6E6FA'
  },
  {
    id: 'candle-making',
    name: 'Candle Making',
    image: candleMakingImg,
    color: '#F0E68C'
  },
  {
    id: 'jewelry-making',
    name: 'Jewelry Making',
    image: jewelryMakingImg,
    color: '#FFC0CB'
  },
  {
    id: 'clay-modeling',
    name: 'Clay Modeling',
    image: clayModelingImg,
    color: '#DDA0DD'
  }
];

const normalizeCategory = (value) =>
  (value || '')
    .toString()
    .toLowerCase()
    .trim()
    .replace(/\s+/g, ' ');

// Keyword helpers to place tutorials into the right category even if the
// stored category string is missing or slightly different.
const CATEGORY_KEYWORDS = {
  'Hand Embroidery': ['embroidery', 'hand embroidery', 'cap embroidery', 'hoop embroidery'],
  'Resin Art': ['resin'],
  'Gift Making': ['gift'],
  'Mylanchi / Mehandi Art': ['mehndi', 'mehandi', 'henna', 'mylanchi'],
  'Candle Making': ['candle'],
  'Jewelry Making': ['jewel', 'jewelry', 'jewellery'],
  'Clay Modeling': ['clay', 'pottery'],
  Stitching: ['stitch', 'sew', 'needlework']
};

const matchesCategory = (tutorial, categoryName) => {
  const normalizedCategoryName = normalizeCategory(categoryName);
  const normalizedTutorialCategory = normalizeCategory(tutorial.category);

  // Direct category match (exact match)
  if (normalizedTutorialCategory && normalizedTutorialCategory === normalizedCategoryName) {
    return true;
  }

  // Partial category match (for cases like "Mylanchi / Mehandi Art" vs "Mehandi Art")
  if (normalizedTutorialCategory && normalizedCategoryName) {
    if (normalizedTutorialCategory.includes(normalizedCategoryName) || 
        normalizedCategoryName.includes(normalizedTutorialCategory)) {
      return true;
    }
  }

  // Heuristic match based on title/keywords when category is missing or different
  const keywords = CATEGORY_KEYWORDS[categoryName] || [];
  const title = normalizeCategory(tutorial.title);
  const description = normalizeCategory(tutorial.description || '');
  
  return keywords.some((kw) => {
    const normalizedKeyword = normalizeCategory(kw);
    return title.includes(normalizedKeyword) || description.includes(normalizedKeyword);
  });
};

export default function TutorialsDashboard() {
  const { tutorialAuth, tutorialLogout } = useTutorialAuth();
  const navigate = useNavigate();
  const [tutorials, setTutorials] = useState([]);
  const [loading, setLoading] = useState(true);
  const [purchases, setPurchases] = useState(new Set());
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [selectedTutorial, setSelectedTutorial] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [favorites, setFavorites] = useState(new Set());
  const [viewMode, setViewMode] = useState('categories'); // 'categories' or 'lessons'
  const [activeNavSection, setActiveNavSection] = useState('home'); // 'home', 'my-learning', 'subscription', 'live-classes'
  const [subscriptionPlan, setSubscriptionPlan] = useState('basic'); // 'basic', 'premium', 'pro'
  const [subscriptionStatus, setSubscriptionStatus] = useState(null);
  const [profileStats, setProfileStats] = useState(null);
  const [isTeacher, setIsTeacher] = useState(false);
  const [showCreateSessionModal, setShowCreateSessionModal] = useState(false);
  const [editingSession, setEditingSession] = useState(null);

  useEffect(() => {
    fetchTutorials();
    fetchUserPurchases();
    fetchSubscriptionStatus();
    fetchProfileStats();
    checkTeacherRole();
  }, [tutorialAuth?.tutorial_session_id]);

  const checkTeacherRole = () => {
    // Check if user has teacher or admin role
    if (!tutorialAuth?.roles) {
      setIsTeacher(false);
      return;
    }
    
    const roles = Array.isArray(tutorialAuth.roles) 
      ? tutorialAuth.roles.map(r => String(r).toLowerCase())
      : [];
    
    const hasTeacherRole = roles.includes('teacher') || roles.includes('admin');
    setIsTeacher(hasTeacherRole);
  };

  const allCategories = useMemo(() => {
    const merged = [...TUTORIAL_CATEGORIES];
    const existing = new Set(merged.map((c) => normalizeCategory(c.name)));

    tutorials.forEach((t) => {
      const catName = t.category || 'General';
      const norm = normalizeCategory(catName);
      // Skip invalid category names (empty, just numbers, or "0")
      if (norm && norm !== '0' && norm !== 'general' && !/^\d+$/.test(norm) && !existing.has(norm)) {
        existing.add(norm);
        merged.push({
          id: norm || 'general',
          name: catName,
          image: getThumbnailUrl(t.thumbnail_url) || candleMakingImg,
          color: '#E8F0FF'
        });
      }
    });

    // Filter out categories with invalid names
    return merged.filter(cat => {
      const normName = normalizeCategory(cat.name);
      return normName && normName !== '0' && normName !== 'general' && !/^\d+$/.test(normName);
    });
  }, [tutorials]);

  const fetchTutorials = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/tutorials.php`);
      const data = await res.json();
      
      if (data.status === 'success' && Array.isArray(data.tutorials)) {
        setTutorials(data.tutorials);
      }
    } catch (error) {
      console.error('Failed to fetch tutorials:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchUserPurchases = async () => {
    try {
      const res = await fetch(`${API_BASE}/customer/tutorial-purchases.php?email=${tutorialAuth?.email}`, {
        headers: {
          'X-Tutorials-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      if (data.status === 'success' && Array.isArray(data.purchases)) {
        const purchaseIds = new Set(data.purchases.map(p => p.tutorial_id));
        setPurchases(purchaseIds);
      }
    } catch (error) {
      console.error('Failed to fetch purchases:', error);
    }
  };

  const fetchSubscriptionStatus = async () => {
    try {
      // FIXED: Use profile API instead of subscription-status API
      const res = await fetch(`${API_BASE}/customer/profile.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      const data = await res.json();
      
      console.log('Subscription status response:', data);
      
      if (data.status === 'success') {
        setSubscriptionStatus(data);
        
        // FIXED: Get subscription plan from the correct path
        const currentPlan = data.subscription?.plan_code || data.plan_code || 'basic';
        setSubscriptionPlan(currentPlan);
        
        console.log('Subscription status set:', {
          plan_code: currentPlan,
          subscription_data: data.subscription,
          feature_access: data.feature_access,
          can_access_live_workshops: data.feature_access?.access_levels?.can_access_live_workshops,
          frontend_plan_state: currentPlan
        });
      } else {
        console.log('Subscription status error:', data);
        // Fallback to basic if API fails
        setSubscriptionPlan('basic');
      }
    } catch (error) {
      console.error('Failed to fetch subscription status:', error);
      // Fallback to basic if network fails
      setSubscriptionPlan('basic');
    }
  };

  const fetchProfileStats = async () => {
    if (!tutorialAuth?.email) return;
    
    try {
      const res = await fetch(`${API_BASE}/customer/profile.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth.email
        }
      });
      const data = await res.json();
      
      if (data.status === 'success' && data.stats) {
        setProfileStats(data.stats);
      }
    } catch (error) {
      console.error('Failed to fetch profile stats:', error);
    }
  };

  const openSubscriptionCheckout = (plan) => {
    // Navigate to checkout page
    navigate(`/tutorials/subscribe?plan=${plan}`);
  };

  const handleLogout = () => {
    tutorialLogout();
    navigate('/tutorial-login', { replace: true });
  };

  const handleCategoryClick = (category) => {
    setSelectedCategory(category);
    setViewMode('lessons');
  };

  const handleBackToCategories = () => {
    setSelectedCategory(null);
    setViewMode('categories');
  };

  const handlePurchase = (tutorial) => {
    setSelectedTutorial(tutorial);
    setShowPaymentModal(true);
  };

  // Helper function to check if user has access to a tutorial
  const hasAccessToTutorial = (tutorial) => {
    // Free tutorials are always accessible (Basic plan feature)
    if (tutorial.is_free || tutorial.price === 0) {
      console.log(`Tutorial ${tutorial.id} (${tutorial.title}) - Access: FREE`);
      return true;
    }
    
    // Check if individually purchased
    if (purchases.has(tutorial.id)) {
      console.log(`Tutorial ${tutorial.id} (${tutorial.title}) - Access: PURCHASED`);
      return true;
    }
    
    // Use feature access control - unlimited tutorials require Premium or Pro
    if (subscriptionStatus?.feature_access) {
      const canAccessUnlimited = subscriptionStatus.feature_access.access_levels?.can_access_unlimited_tutorials;
      
      // FIXED: Check multiple possible locations for plan_code and is_active
      const planCode = subscriptionStatus.subscription?.plan_code || subscriptionStatus.plan_code;
      const isActive = subscriptionStatus.subscription?.is_active || subscriptionStatus.is_active;
      const subscriptionStatusValue = subscriptionStatus.subscription?.subscription_status || subscriptionStatus.subscription_status;
      
      if (canAccessUnlimited && 
          (planCode === 'premium' || planCode === 'pro') &&
          (isActive || subscriptionStatusValue === 'pending' || subscriptionStatusValue === 'authenticated')) {
        console.log(`Tutorial ${tutorial.id} (${tutorial.title}) - Access: SUBSCRIPTION (${planCode}, status: ${subscriptionStatusValue}, active: ${isActive})`);
        return true;
      }
    }
    
    console.log(`Tutorial ${tutorial.id} (${tutorial.title}) - Access: DENIED`, {
      is_free: tutorial.is_free,
      price: tutorial.price,
      purchased: purchases.has(tutorial.id),
      subscription_active: subscriptionStatus?.subscription?.is_active || subscriptionStatus?.is_active,
      plan_code: subscriptionStatus?.subscription?.plan_code || subscriptionStatus?.plan_code,
      can_access_unlimited: subscriptionStatus?.feature_access?.access_levels?.can_access_unlimited_tutorials
    });
    return false;
  };

  const handleWatchTutorial = (tutorial) => {
    if (hasAccessToTutorial(tutorial)) {
      navigate(`/tutorial/${tutorial.id}`, { state: { tutorial } });
    }
  };

  // Helper function to check if user can access live workshops (Pro only)
  const canAccessLiveWorkshops = () => {
    // Primary check: feature_access from profile API
    const hasFeatureAccess = subscriptionStatus?.feature_access?.access_levels?.can_access_live_workshops;
    
    // Backup check: subscription plan
    const isProPlan = subscriptionStatus?.subscription?.plan_code === 'pro' || subscriptionPlan === 'pro';
    
    // Additional backup: is_pro_user flag
    const isProUser = subscriptionStatus?.stats?.is_pro_user;
    
    console.log('Live workshops access check:', {
      hasFeatureAccess,
      isProPlan,
      isProUser,
      subscriptionPlan,
      subscriptionStatus: subscriptionStatus?.subscription
    });
    
    return hasFeatureAccess || isProPlan || isProUser || false;
  };

  const toggleFavorite = (tutorialId) => {
    setFavorites(prev => {
      const newFavorites = new Set(prev);
      if (newFavorites.has(tutorialId)) {
        newFavorites.delete(tutorialId);
      } else {
        newFavorites.add(tutorialId);
      }
      return newFavorites;
    });
  };

  const handlePayment = async (method, tutorialId = null) => {
    if (method === 'subscription' && !tutorialId) {
      // Handle subscription upgrade - get plan from subscriptionPlan state
      const planCode = subscriptionPlan;
      
      if (planCode === 'basic') {
        alert('You are already on the basic plan. Please select Premium or Pro to upgrade.');
        return;
      }

      try {
        // Create subscription
        const res = await fetch(`${API_BASE}/customer/create-subscription.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Tutorial-Email': tutorialAuth?.email || ''
          },
          body: JSON.stringify({
            plan_code: planCode
          })
        });

        if (!res.ok) {
          const errorText = await res.text();
          console.error('Subscription API error:', errorText);
          alert('Failed to create subscription. Please check console for details.');
          return;
        }

        const data = await res.json();
        
        if (data.status !== 'success') {
          console.error('Subscription creation failed:', data);
          alert(data.message || 'Failed to create subscription. Please try again.');
          return;
        }

        // If basic plan or immediately active, just update status
        if (planCode === 'basic' || data.subscription_status === 'active') {
            fetchSubscriptionStatus();
            alert('Subscription activated successfully!');
            return;
        }

        // For paid plans, open Razorpay checkout
        if (data.razorpay_subscription_id) {
          // If short_url is available, redirect to it (preferred method for subscriptions)
          if (data.short_url) {
            window.location.href = data.short_url;
            return;
          }
          
          // Otherwise, try to use checkout.js (may not work for all subscription types)
          // Load Razorpay script if not already loaded
          if (!window.Razorpay) {
            const script = document.createElement('script');
            script.src = 'https://checkout.razorpay.com/v1/checkout.js';
            script.onload = () => openRazorpaySubscriptionCheckout(data, planCode);
            script.onerror = () => {
              // Fallback: redirect to Razorpay dashboard or show error
              alert('Failed to load Razorpay. Please try again or contact support.');
            };
            document.body.appendChild(script);
          } else {
            openRazorpaySubscriptionCheckout(data, planCode);
          }
        } else {
          alert('Failed to create subscription. Please try again.');
        }
      } catch (error) {
        console.error('Subscription error:', error);
        alert('An error occurred during subscription: ' + error.message);
      }
      return;
    }

    if (!selectedTutorial && !tutorialId) return;

    const tutorialToPurchase = tutorialId ? tutorials.find(t => t.id === tutorialId) : selectedTutorial;

    try {
      // Step 1: Create Razorpay order via backend
      const res = await fetch(`${API_BASE}/customer/purchase-tutorial.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Tutorial-Email': tutorialAuth?.email || ''
        },
        body: JSON.stringify({
          tutorial_id: tutorialToPurchase.id,
          payment_method: method
        })
      });

      const data = await res.json();
      
      if (data.status !== 'success') {
        alert(data.message || 'Purchase failed. Please try again.');
        return;
      }

      // Step 2: If free tutorial, just update state
      if (tutorialToPurchase.is_free || tutorialToPurchase.price === 0) {
        setPurchases(new Set([...purchases, tutorialToPurchase.id]));
        setShowPaymentModal(false);
        setSelectedTutorial(null);
        fetchUserPurchases(); // Refresh purchases
        return;
      }

      // Step 3: Handle subscription payment for tutorials
      if (method === 'subscription') {
        if (data.subscription_active) {
          // User has active subscription, grant access
          setPurchases(new Set([...purchases, tutorialToPurchase.id]));
          setShowPaymentModal(false);
          setSelectedTutorial(null);
          fetchUserPurchases();
          alert('Tutorial unlocked with your subscription!');
          return;
        } else if (data.requires_subscription) {
          // User needs to subscribe first
          setShowPaymentModal(false);
          setSelectedTutorial(null);
          setActiveNavSection('subscription');
          alert('Please subscribe to access this tutorial. Redirecting to subscription page...');
          return;
        }
      }

      // Step 4: Open Razorpay checkout for paid tutorials
      if (method === 'razorpay' && data.razorpay_order_id) {
        // Load Razorpay script if not already loaded
        if (!window.Razorpay) {
          const script = document.createElement('script');
          script.src = 'https://checkout.razorpay.com/v1/checkout.js';
          script.onload = () => openRazorpayCheckout(data, tutorialToPurchase);
          script.onerror = () => alert('Failed to load Razorpay checkout. Please check your internet connection.');
          document.body.appendChild(script);
        } else {
          openRazorpayCheckout(data, tutorialToPurchase);
        }
      }
    } catch (error) {
      console.error('Payment error:', error);
      alert('An error occurred during payment processing: ' + error.message);
    }
  };

  const openRazorpayCheckout = (orderData, tutorial) => {
    const razorpayKey = import.meta.env.VITE_RAZORPAY_KEY || 'rzp_test_RGXWGOBliVCIpU';
    
    console.log('Using Razorpay Key:', razorpayKey); // Debug log
    console.log('Order Data:', orderData); // Debug log
    
    const options = {
      key: razorpayKey,
      amount: orderData.amount, // Amount in paise
      currency: orderData.currency || 'INR',
      name: 'My Little Thingz',
      description: `Purchase: ${tutorial.title}`,
      order_id: orderData.razorpay_order_id,
      prefill: {
        email: tutorialAuth?.email || '',
      },
      theme: {
        color: '#667eea'
      },
      handler: async function (response) {
        // Step 4: Verify payment with backend
        try {
          const verifyRes = await fetch(`${API_BASE}/customer/tutorial-razorpay-verify.php`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Tutorial-Email': tutorialAuth?.email || '',
              'X-User-ID': tutorialAuth?.user_id || ''
            },
            body: JSON.stringify({
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_order_id: response.razorpay_order_id,
              razorpay_signature: response.razorpay_signature,
              tutorial_id: tutorial.id
            })
          });

          const verifyData = await verifyRes.json();
          
          if (verifyData.status === 'success') {
            // Payment successful - update purchases
            setPurchases(new Set([...purchases, tutorial.id]));
            setShowPaymentModal(false);
            setSelectedTutorial(null);
            fetchUserPurchases(); // Refresh purchases
            alert('Payment successful! Tutorial unlocked.');
          } else {
            alert('Payment verification failed: ' + (verifyData.message || 'Please contact support.'));
          }
        } catch (error) {
          console.error('Verification error:', error);
          alert('Payment verification failed. Please contact support.');
        }
      },
      modal: {
        ondismiss: function() {
          // User closed the checkout without payment
          console.log('Payment cancelled');
        }
      }
    };

    const rzp = new window.Razorpay(options);
    rzp.open();
  };

  const openRazorpaySubscriptionCheckout = (subscriptionData, planCode) => {
    const razorpayKey = import.meta.env.VITE_RAZORPAY_KEY || 'rzp_test_RGXWGOBliVCIpU';
    
    const options = {
      key: razorpayKey,
      subscription_id: subscriptionData.razorpay_subscription_id,
      name: 'My Little Thingz',
      description: `Subscribe to ${planCode.charAt(0).toUpperCase() + planCode.slice(1)} Plan - ₹${subscriptionData.amount / 100}/month`,
      prefill: {
        email: tutorialAuth?.email || '',
      },
      theme: {
        color: '#667eea'
      },
      handler: async function (response) {
        // Verify subscription payment with backend
        try {
          const verifyRes = await fetch(`${API_BASE}/customer/subscription-verify.php`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Tutorial-Email': tutorialAuth?.email || ''
            },
            body: JSON.stringify({
              razorpay_subscription_id: response.razorpay_subscription_id || subscriptionData.razorpay_subscription_id,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature
            })
          });

          const verifyData = await verifyRes.json();
          
          if (verifyData.status === 'success') {
            // Subscription activated - refresh status
            fetchSubscriptionStatus();
            alert('Subscription activated successfully! Welcome to ' + planCode.charAt(0).toUpperCase() + planCode.slice(1) + '!');
          } else {
            alert('Subscription verification failed: ' + (verifyData.message || 'Please contact support.'));
          }
        } catch (error) {
          console.error('Verification error:', error);
          alert('Subscription verification failed. Please contact support.');
        }
      },
      modal: {
        ondismiss: function() {
          console.log('Subscription cancelled');
        }
      }
    };

    try {
      const rzp = new window.Razorpay(options);
      rzp.open();
    } catch (error) {
      console.error('Razorpay subscription checkout error:', error);
      alert('Failed to open payment window. Please try again.');
    }
  };

  const getCategoryTutorials = () => {
    if (!selectedCategory) return [];
    return tutorials.filter((t) => matchesCategory(t, selectedCategory.name));
  };

  // Get tutorials for a specific category name
  const getTutorialsByCategory = (categoryName) => {
    return tutorials.filter((t) => matchesCategory(t, categoryName));
  };

  const getPurchasedTutorials = () => {
    return tutorials.filter(t => purchases.has(t.id) || t.is_free);
  };

  const getPopularTutorials = () => {
    // Return first 6 tutorials as popular (you can implement actual popularity logic)
    return tutorials.slice(0, 6);
  };

  const getFeaturedTutorials = () => {
    // Return tutorials marked as featured or first 3
    return tutorials.slice(0, 3);
  };

  const getLearningStats = () => {
    const isPro = subscriptionStatus?.plan_code === 'pro' || subscriptionStatus?.plan_code === 'premium';
    
    if (isPro && profileStats) {
      // Use real learning data for Pro users
      return {
        purchasedCount: profileStats.completed_tutorials + (profileStats.in_progress_tutorials || 0), // Total watched
        completedCount: profileStats.completed_tutorials,
        totalHours: profileStats.learning_hours || 0
      };
    } else {
      // Use purchase-based data for Basic users
      const purchasedCount = purchases.size;
      const completedCount = Math.floor(purchasedCount * 0.6); // Mock data
      const totalHours = Math.floor(purchasedCount * 2.5); // Mock data
      return { purchasedCount, completedCount, totalHours };
    }
  };

  if (loading) {
    return (
      <div className="tutorials-container">
        <div className="loading-spinner">Loading tutorials...</div>
      </div>
    );
  }

  return (
    <div className="tutorials-container">
      {/* Top Navigation Bar */}
      <nav className="tutorials-nav">
        <div className="tutorials-nav-wrapper">
          <div className="tutorials-nav-left">
            <Link to="/" className="nav-logo">
              <img src={logo} alt="My Little Thingz" />
            </Link>
            {viewMode === 'lessons' && (
              <button className="back-to-categories" onClick={handleBackToCategories}>
                <LuArrowLeft size={20} />
                <span>Back to Categories</span>
              </button>
            )}
            </div>
            
          <div className="tutorials-nav-center">
            <button 
              className={`nav-link ${activeNavSection === 'home' ? 'active' : ''}`}
              onClick={() => setActiveNavSection('home')}
            >
              <LuBookOpen size={18} />
              <span>Home</span>
            </button>
            <button 
              className={`nav-link ${activeNavSection === 'my-learning' ? 'active' : ''}`}
              onClick={() => setActiveNavSection('my-learning')}
            >
              <LuPlay size={18} />
              <span>My Learning</span>
            </button>
            <button 
              className={`nav-link subscription-nav-link ${activeNavSection === 'subscription' ? 'active' : ''}`}
              onClick={() => setActiveNavSection('subscription')}
            >
              <LuCrown size={18} />
              <span>Subscription</span>
            </button>
            <button 
              className={`nav-link ${activeNavSection === 'live-classes' ? 'active' : ''}`}
              onClick={() => setActiveNavSection('live-classes')}
            >
              <LuVideo size={18} />
              <span>Live Classes</span>
            </button>
            {subscriptionStatus?.plan_code === 'pro' && (
              <Link 
                to="/pro-dashboard"
                className="nav-link pro-dashboard-link"
              >
                <LuTrendingUp size={18} />
                <span>My Progress</span>
              </Link>
            )}
            </div>

          <div className="tutorials-nav-right">
            <NotificationDropdown />
            <ProfileDropdown />
          </div>
              </div>
      </nav>

      {/* Main Content */}
      <main className="tutorials-main">
        {activeNavSection === 'live-classes' ? (
          <div className="live-classes-view">
            {!canAccessLiveWorkshops() ? (
              <div className="access-denied-section">
                <div className="access-denied-card">
                  <div className="access-denied-icon">
                    <LuLock size={48} />
                  </div>
                  <h2>Live Workshops - Pro Feature</h2>
                  <p>Live workshops and mentorship sessions are available exclusively for Pro subscribers.</p>
                  <div className="current-plan-info">
                    <span>Current Plan: <strong>{subscriptionStatus?.subscription?.plan_code || subscriptionPlan || 'Basic'}</strong></span>
                  </div>
                  <button 
                    className="upgrade-btn"
                    onClick={() => setActiveNavSection('subscription')}
                  >
                    <LuCrown size={18} />
                    Upgrade to Pro
                  </button>
                </div>
              </div>
            ) : (
              <>
                {isTeacher && (
                  <div className="teacher-actions" style={{ marginBottom: '20px', display: 'flex', justifyContent: 'flex-end' }}>
                    <button 
                      className="btn-create-session"
                      onClick={() => {
                        setEditingSession(null);
                        setShowCreateSessionModal(true);
                      }}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        padding: '12px 20px',
                        background: '#667eea',
                        color: 'white',
                        border: 'none',
                        borderRadius: '8px',
                        fontWeight: 500,
                        cursor: 'pointer',
                        transition: 'all 0.2s'
                      }}
                    >
                      <LuPlus size={18} />
                      Create Live Session
                    </button>
                  </div>
                )}
                <LiveSessionsList 
                  auth={tutorialAuth}
                  isTeacher={isTeacher}
                />
              </>
            )}
          </div>
        ) : activeNavSection === 'subscription' ? (
          <SubscriptionPlansSection 
            subscriptionPlan={subscriptionPlan}
            setSubscriptionPlan={setSubscriptionPlan}
            subscriptionStatus={subscriptionStatus}
            onUpgradeClick={openSubscriptionCheckout}
          />
        ) : activeNavSection === 'my-learning' ? (
          <MyLearningSection 
            tutorials={tutorials}
            purchases={purchases}
            favorites={favorites}
            toggleFavorite={toggleFavorite}
            handleWatchTutorial={handleWatchTutorial}
            getLearningStats={getLearningStats}
            getPurchasedTutorials={getPurchasedTutorials}
            subscriptionStatus={subscriptionStatus}
          />
        ) : viewMode === 'categories' ? (
          <div className="categories-view">
            {/* Learning Stats Banner */}
            <LearningStatsBanner stats={getLearningStats()} subscriptionStatus={subscriptionStatus} />

            {/* Categories Header */}
            <div className="categories-header">
              <h1>Choose Your Craft Journey</h1>
              <p>Explore our premium tutorials and master the art of handmade creations</p>
              </div>

            {/* Categories Grid */}
            <div className="categories-grid">
              {allCategories.map((category, index) => {
                const categoryTutorials = getTutorialsByCategory(category.name);
                const tutorialCount = categoryTutorials.length;
                
                return (
                  <div
                    key={category.id}
                    className="category-card"
                    style={{ '--category-color': category.color }}
                    onClick={() => handleCategoryClick(category)}
                  >
                    <div className="category-image-wrapper">
                      <img src={category.image} alt={category.name} />
                      <div className="category-overlay">
                        {tutorialCount > 0 && (
                          <div className="category-tutorials-preview">
                            <div className="category-tutorial-count">
                              <LuPlay size={16} />
                              <span>{tutorialCount} {tutorialCount === 1 ? 'Tutorial' : 'Tutorials'}</span>
                            </div>
                            <div className="category-tutorials-thumbnails">
                              {categoryTutorials.slice(0, 3).map((tutorial, idx) => (
                                <div key={tutorial.id} className="category-tutorial-mini">
                                  {tutorial.thumbnail_url ? (
                                    <img src={getThumbnailUrl(tutorial.thumbnail_url)} alt={tutorial.title} />
                                  ) : (
                                    <div className="category-tutorial-mini-placeholder"></div>
                                  )}
                                </div>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="category-content">
                      <div className="category-content-header">
                        <h3>{category.name}</h3>
                        <div className="category-arrow">
                          <LuArrowRight size={20} />
                        </div>
                      </div>
                      <div className="category-meta">
                        {tutorialCount > 0 && (
                          <span className="category-tutorial-count-badge">{tutorialCount} {tutorialCount === 1 ? 'Video' : 'Videos'}</span>
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Popular Tutorials */}
            {getPopularTutorials().length > 0 && (
              <PopularSection 
                tutorials={getPopularTutorials()}
                purchases={purchases}
                favorites={favorites}
                toggleFavorite={toggleFavorite}
                handleWatchTutorial={handleWatchTutorial}
                handlePurchase={handlePurchase}
                hasAccessToTutorial={hasAccessToTutorial}
              />
            )}

            {/* Subscription Benefits */}
            <SubscriptionBenefitsSection />
          </div>
        ) : (
          <div className="lessons-view">
            <div className="lessons-header">
              <div className="lessons-header-content">
                <h1>{selectedCategory?.name}</h1>
                <p>Master the techniques and create beautiful handmade pieces</p>
              </div>
            </div>

            <div className="lessons-grid">
              {getCategoryTutorials().length === 0 ? (
                <div className="no-lessons">
                  <p>No tutorials available for this category yet. Check back soon!</p>
                </div>
              ) : (
                getCategoryTutorials().map((tutorial) => {
            const hasAccess = hasAccessToTutorial(tutorial);
            const isFree = tutorial.is_free || tutorial.price === 0;
                  const isFavorite = favorites.has(tutorial.id);
                  const progress = Math.floor(Math.random() * 100); // Mock progress

            return (
                    <div key={tutorial.id} className="lesson-card">
                      <div className="lesson-thumbnail">
                        {tutorial.thumbnail_url ? (
                    <img src={getThumbnailUrl(tutorial.thumbnail_url)} alt={tutorial.title} />
                        ) : (
                          <div className="lesson-placeholder"></div>
                  )}
                        <div className="lesson-overlay">
                    {hasAccess ? (
                      <button 
                              className="play-button-overlay"
                        onClick={() => handleWatchTutorial(tutorial)}
                      >
                              <LuPlay size={32} />
                      </button>
                    ) : (
                            <div className="locked-overlay">
                              <LuLock size={32} />
                      </div>
                    )}
                  </div>
                        {progress > 0 && (
                          <div className="progress-indicator">
                            <div className="progress-bar" style={{ width: `${progress}%` }}></div>
                          </div>
                        )}
                        <button
                          className={`favorite-btn ${isFavorite ? 'active' : ''}`}
                          onClick={(e) => {
                            e.stopPropagation();
                            toggleFavorite(tutorial.id);
                          }}
                        >
                          <LuHeart size={18} />
                        </button>
                </div>

                      <div className="lesson-content">
                        <h3>{tutorial.title}</h3>
                        <p className="lesson-description">{tutorial.description}</p>
                        
                        <div className="lesson-meta">
                          <span className="lesson-duration">
                      {tutorial.duration || 'N/A'} min
                    </span>
                          <span className="lesson-level">
                      {tutorial.difficulty_level || 'Beginner'}
                    </span>
                  </div>

                        <div className="lesson-footer">
                    {hasAccess ? (
                      <button 
                              className="watch-btn"
                        onClick={() => handleWatchTutorial(tutorial)}
                      >
                        <LuPlay size={16} />
                              <span>Watch Now</span>
                      </button>
                    ) : (
                      <div className="purchase-section">
                              <span className="lesson-price">₹{tutorial.price}</span>
                        <button 
                                className="purchase-btn"
                          onClick={() => handlePurchase(tutorial)}
                        >
                          Purchase
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>
          </div>
        )}
      </main>

      {/* Payment Modal */}
      {showPaymentModal && selectedTutorial && (
        <div className="payment-modal-overlay" onClick={() => setShowPaymentModal(false)}>
          <div className="payment-modal" onClick={(e) => e.stopPropagation()}>
            <div className="payment-modal-header">
              <h2>Purchase Tutorial</h2>
              <button 
                className="close-btn"
                onClick={() => setShowPaymentModal(false)}
              >
                ×
              </button>
            </div>

            <div className="payment-modal-content">
              <div className="tutorial-summary">
                <img 
                  src={getThumbnailUrl(selectedTutorial.thumbnail_url)} 
                  alt={selectedTutorial.title}
                  className="summary-thumbnail"
                />
                <div className="summary-info">
                  <h3>{selectedTutorial.title}</h3>
                  <p className="summary-price">₹{selectedTutorial.price}</p>
                </div>
              </div>

              <div className="payment-options">
                <h4>Choose Payment Method</h4>
                <button 
                  className="payment-option"
                  onClick={() => handlePayment('razorpay')}
                >
                  <span>💳</span>
                  <span>Pay with Razorpay</span>
                </button>
                <button 
                  className="payment-option"
                  onClick={() => handlePayment('subscription')}
                >
                  <span>📅</span>
                  <span>Subscribe Monthly</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Create/Edit Live Session Modal */}
      {showCreateSessionModal && (
        <CreateLiveSessionModal
          isOpen={showCreateSessionModal}
          onClose={() => {
            setShowCreateSessionModal(false);
            setEditingSession(null);
          }}
          onSuccess={() => {
            // Refresh sessions list if needed
            window.location.reload(); // Simple refresh, or you could use state management
          }}
          session={editingSession}
          auth={tutorialAuth}
        />
      )}

    </div>
  );
}
