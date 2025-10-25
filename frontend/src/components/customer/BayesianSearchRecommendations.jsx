import React, { useState } from 'react';
import { LuSearch, LuSparkles, LuTarget, LuTrendingUp } from 'react-icons/lu';

const PYTHON_ML_API = "http://localhost:5001/api/ml";

const BayesianSearchRecommendations = () => {
  const [searchKeyword, setSearchKeyword] = useState('');
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [predictionInfo, setPredictionInfo] = useState(null);

  const handleSearch = async () => {
    if (!searchKeyword.trim()) {
      setError('Please enter a search keyword');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      setRecommendations([]);
      setPredictionInfo(null);

      // Call the enhanced Bayesian search API
      const response = await fetch(`${PYTHON_ML_API}/bayesian/search-recommendations`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          keyword: searchKeyword,
          limit: 6,
          confidence_threshold: 0.6
        })
      });

      const data = await response.json();

      if (data.success) {
        setRecommendations(data.recommendations);
        setPredictionInfo({
          predicted_category: data.predicted_category,
          confidence: data.confidence_percent,
          algorithm: data.algorithm,
          search_keyword: data.search_keyword
        });
      } else {
        setError(data.error || 'Failed to get recommendations');
      }
    } catch (err) {
      console.error('Search error:', err);
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter') {
      handleSearch();
    }
  };

  const getCategoryEmoji = (category) => {
    const emojis = {
      'chocolate': '🍫',
      'bouquet': '🌸',
      'gift_box': '🎁',
      'wedding_card': '💒',
      'custom_chocolate': '✨',
      'nuts': '🥜'
    };
    return emojis[category] || '🎁';
  };

  const getCategoryColor = (category) => {
    const colors = {
      'chocolate': 'bg-amber-100 text-amber-800',
      'bouquet': 'bg-pink-100 text-pink-800',
      'gift_box': 'bg-blue-100 text-blue-800',
      'wedding_card': 'bg-purple-100 text-purple-800',
      'custom_chocolate': 'bg-yellow-100 text-yellow-800',
      'nuts': 'bg-green-100 text-green-800'
    };
    return colors[category] || 'bg-gray-100 text-gray-800';
  };

  const exampleKeywords = [
    'sweet', 'chocolate', 'flower', 'romantic', 'premium', 
    'custom', 'wedding', 'healthy', 'luxury', 'anniversary'
  ];

  return (
    <div className="bayesian-search-container" style={{
      maxWidth: '1200px',
      margin: '0 auto',
      padding: '20px',
      fontFamily: 'system-ui, -apple-system, sans-serif'
    }}>
      {/* Header */}
      <div style={{
        textAlign: 'center',
        marginBottom: '30px',
        padding: '20px',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        borderRadius: '16px',
        color: 'white'
      }}>
        <h2 style={{
          margin: '0 0 10px 0',
          fontSize: '28px',
          fontWeight: '700',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: '10px'
        }}>
          <LuSparkles /> Enhanced AI Search
        </h2>
        <p style={{
          margin: '0',
          fontSize: '16px',
          opacity: 0.9
        }}>
          Search with keywords and get intelligent category predictions
        </p>
      </div>

      {/* Search Input */}
      <div style={{
        display: 'flex',
        gap: '12px',
        marginBottom: '30px',
        alignItems: 'center'
      }}>
        <div style={{ position: 'relative', flex: 1 }}>
          <input
            type="text"
            value={searchKeyword}
            onChange={(e) => setSearchKeyword(e.target.value)}
            onKeyPress={handleKeyPress}
            placeholder="Try: sweet, romantic, premium, custom..."
            style={{
              width: '100%',
              padding: '16px 20px',
              border: '2px solid #e5e7eb',
              borderRadius: '12px',
              fontSize: '16px',
              outline: 'none',
              transition: 'all 0.2s ease',
              boxSizing: 'border-box'
            }}
            onFocus={(e) => {
              e.target.style.borderColor = '#667eea';
              e.target.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
            }}
            onBlur={(e) => {
              e.target.style.borderColor = '#e5e7eb';
              e.target.style.boxShadow = 'none';
            }}
          />
          <LuSearch style={{
            position: 'absolute',
            right: '16px',
            top: '50%',
            transform: 'translateY(-50%)',
            color: '#9ca3af',
            fontSize: '20px'
          }} />
        </div>
        <button
          onClick={handleSearch}
          disabled={loading}
          style={{
            padding: '16px 24px',
            background: loading ? '#9ca3af' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            color: 'white',
            border: 'none',
            borderRadius: '12px',
            fontSize: '16px',
            fontWeight: '600',
            cursor: loading ? 'not-allowed' : 'pointer',
            transition: 'all 0.2s ease',
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
          }}
        >
          {loading ? 'Searching...' : 'Search'}
          <LuTarget />
        </button>
      </div>

      {/* Example Keywords */}
      <div style={{ marginBottom: '30px' }}>
        <p style={{
          margin: '0 0 12px 0',
          fontSize: '14px',
          color: '#6b7280',
          fontWeight: '500'
        }}>
          Try these keywords:
        </p>
        <div style={{
          display: 'flex',
          flexWrap: 'wrap',
          gap: '8px'
        }}>
          {exampleKeywords.map((keyword) => (
            <button
              key={keyword}
              onClick={() => {
                setSearchKeyword(keyword);
                handleSearch();
              }}
              style={{
                padding: '8px 16px',
                background: '#f3f4f6',
                border: '1px solid #e5e7eb',
                borderRadius: '20px',
                fontSize: '14px',
                cursor: 'pointer',
                transition: 'all 0.2s ease',
                color: '#374151'
              }}
              onMouseEnter={(e) => {
                e.target.style.background = '#e5e7eb';
                e.target.style.borderColor = '#d1d5db';
              }}
              onMouseLeave={(e) => {
                e.target.style.background = '#f3f4f6';
                e.target.style.borderColor = '#e5e7eb';
              }}
            >
              {keyword}
            </button>
          ))}
        </div>
      </div>

      {/* Prediction Info */}
      {predictionInfo && (
        <div style={{
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          borderRadius: '12px',
          padding: '20px',
          marginBottom: '30px'
        }}>
          <h3 style={{
            margin: '0 0 16px 0',
            fontSize: '18px',
            fontWeight: '600',
            color: '#1e293b',
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
          }}>
            <LuTrendingUp /> AI Prediction Results
          </h3>
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
            gap: '16px'
          }}>
            <div>
              <p style={{ margin: '0 0 4px 0', fontSize: '14px', color: '#64748b' }}>
                Search Keyword
              </p>
              <p style={{ margin: '0', fontSize: '16px', fontWeight: '600', color: '#1e293b' }}>
                "{predictionInfo.search_keyword}"
              </p>
            </div>
            <div>
              <p style={{ margin: '0 0 4px 0', fontSize: '14px', color: '#64748b' }}>
                Predicted Category
              </p>
              <span style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                padding: '6px 12px',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: '600',
                ...getCategoryColor(predictionInfo.predicted_category)
              }}>
                {getCategoryEmoji(predictionInfo.predicted_category)}
                {predictionInfo.predicted_category.replace('_', ' ').toUpperCase()}
              </span>
            </div>
            <div>
              <p style={{ margin: '0 0 4px 0', fontSize: '14px', color: '#64748b' }}>
                Confidence
              </p>
              <p style={{ margin: '0', fontSize: '16px', fontWeight: '600', color: '#059669' }}>
                {predictionInfo.confidence.toFixed(1)}%
              </p>
            </div>
            <div>
              <p style={{ margin: '0 0 4px 0', fontSize: '14px', color: '#64748b' }}>
                Algorithm
              </p>
              <p style={{ margin: '0', fontSize: '16px', fontWeight: '600', color: '#1e293b' }}>
                {predictionInfo.algorithm}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div style={{
          background: '#fef2f2',
          border: '1px solid #fecaca',
          borderRadius: '12px',
          padding: '16px',
          marginBottom: '30px',
          color: '#dc2626'
        }}>
          <p style={{ margin: '0', fontSize: '14px', fontWeight: '500' }}>
            {error}
          </p>
        </div>
      )}

      {/* Loading State */}
      {loading && (
        <div style={{
          textAlign: 'center',
          padding: '40px',
          color: '#6b7280'
        }}>
          <div style={{
            display: 'inline-block',
            width: '40px',
            height: '40px',
            border: '4px solid #e5e7eb',
            borderTop: '4px solid #667eea',
            borderRadius: '50%',
            animation: 'spin 1s linear infinite',
            marginBottom: '16px'
          }}></div>
          <p style={{ margin: '0', fontSize: '16px' }}>
            Analyzing keyword and generating recommendations...
          </p>
        </div>
      )}

      {/* Recommendations Grid */}
      {recommendations.length > 0 && (
        <div>
          <h3 style={{
            margin: '0 0 20px 0',
            fontSize: '20px',
            fontWeight: '600',
            color: '#1e293b'
          }}>
            Recommended Products ({recommendations.length})
          </h3>
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
            gap: '20px'
          }}>
            {recommendations.map((product) => (
              <div
                key={product.id}
                style={{
                  background: 'white',
                  border: '1px solid #e5e7eb',
                  borderRadius: '16px',
                  overflow: 'hidden',
                  transition: 'all 0.2s ease',
                  cursor: 'pointer'
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'translateY(-4px)';
                  e.currentTarget.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.1)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'translateY(0)';
                  e.currentTarget.style.boxShadow = 'none';
                }}
              >
                <div style={{ padding: '20px' }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    marginBottom: '12px'
                  }}>
                    <span style={{
                      padding: '4px 8px',
                      borderRadius: '6px',
                      fontSize: '12px',
                      fontWeight: '600',
                      ...getCategoryColor(product.predicted_category)
                    }}>
                      {getCategoryEmoji(product.predicted_category)}
                      {product.predicted_category.replace('_', ' ').toUpperCase()}
                    </span>
                    <span style={{
                      padding: '4px 8px',
                      background: '#f0f9ff',
                      color: '#0369a1',
                      borderRadius: '6px',
                      fontSize: '12px',
                      fontWeight: '600'
                    }}>
                      {product.algorithm}
                    </span>
                  </div>
                  
                  <h4 style={{
                    margin: '0 0 8px 0',
                    fontSize: '16px',
                    fontWeight: '600',
                    color: '#1e293b',
                    lineHeight: '1.4'
                  }}>
                    {product.title}
                  </h4>
                  
                  <p style={{
                    margin: '0 0 12px 0',
                    fontSize: '14px',
                    color: '#64748b',
                    lineHeight: '1.5'
                  }}>
                    {product.description}
                  </p>
                  
                  <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center'
                  }}>
                    <span style={{
                      fontSize: '18px',
                      fontWeight: '700',
                      color: '#059669'
                    }}>
                      ₹{product.price.toLocaleString()}
                    </span>
                    <span style={{
                      fontSize: '12px',
                      color: '#6b7280',
                      background: '#f3f4f6',
                      padding: '4px 8px',
                      borderRadius: '4px'
                    }}>
                      {product.confidence.toFixed(1)}% match
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* CSS Animation */}
      <style>{`
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
};

export default BayesianSearchRecommendations;





