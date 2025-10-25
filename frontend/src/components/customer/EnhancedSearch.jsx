import React, { useState, useEffect } from 'react';
import { LuSearch, LuSparkles, LuTarget, LuTrendingUp, LuBrain, LuX } from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

const EnhancedSearch = ({ onSearchResults, onClose }) => {
  const [searchKeyword, setSearchKeyword] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [mlInsights, setMlInsights] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [searchSuggestions, setSearchSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);

  useEffect(() => {
    if (searchKeyword.length >= 2) {
      fetchSearchSuggestions(searchKeyword);
    } else {
      setSearchSuggestions([]);
      setShowSuggestions(false);
    }
  }, [searchKeyword]);

  const fetchSearchSuggestions = async (term) => {
    try {
      const response = await fetch(`${API_BASE}/customer/enhanced-search.php?action=suggestions&term=${encodeURIComponent(term)}`);
      const data = await response.json();
      
      if (data.status === 'success') {
        setSearchSuggestions(data.data.suggestions || []);
        setShowSuggestions(true);
      }
    } catch (error) {
      console.error('Error fetching suggestions:', error);
    }
  };

  const handleSearch = async () => {
    if (!searchKeyword.trim()) {
      setError('Please enter a search keyword');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      setSearchResults([]);
      setMlInsights(null);

      // Call enhanced search API
      const response = await fetch(`${API_BASE}/customer/enhanced-search.php?action=search&term=${encodeURIComponent(searchKeyword)}&limit=20`);
      const data = await response.json();

      if (data.status === 'success') {
        setSearchResults(data.data.artworks || []);
        setMlInsights(data.data.ml_insights);
        
        // Pass results to parent component
        if (onSearchResults) {
          onSearchResults(data.data.artworks || [], data.data.ml_insights);
        }
      } else {
        setError(data.message || 'Search failed');
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

  const handleSuggestionClick = (suggestion) => {
    setSearchKeyword(suggestion);
    setShowSuggestions(false);
  };

  const getCategoryEmoji = (category) => {
    const emojis = {
      'sweet': '🍫',
      'wedding': '💒',
      'birthday': '🎂',
      'baby': '👶',
      'valentine': '💕',
      'house': '🏠',
      'farewell': '👋',
      'chocolate': '🍫',
      'bouquet': '🌹',
      'gift_box': '🎁',
      'wedding_card': '💒',
      'custom_chocolate': '🍫✨',
      'nuts': '🥜'
    };
    return emojis[category] || '🎁';
  };

  const getConfidenceColor = (confidence) => {
    if (confidence >= 80) return '#10b981'; // green
    if (confidence >= 60) return '#f59e0b'; // yellow
    return '#ef4444'; // red
  };

  return (
    <div className="enhanced-search-container">
      <div className="search-header">
        <h3>🔍 Enhanced AI Search</h3>
        <p>Powered by Bayesian Machine Learning</p>
        {onClose && (
          <button className="btn-close" onClick={onClose}>
            <LuX />
          </button>
        )}
      </div>

      <div className="search-input-container">
        <div className="search-box enhanced">
          <LuSearch className="search-icon" />
          <input
            type="text"
            placeholder="Try: 'sweet', 'wedding', 'birthday', 'baby', 'valentine'..."
            value={searchKeyword}
            onChange={(e) => setSearchKeyword(e.target.value)}
            onKeyPress={handleKeyPress}
            onFocus={() => setShowSuggestions(searchSuggestions.length > 0)}
            onBlur={() => setTimeout(() => setShowSuggestions(false), 200)}
          />
          <button 
            className="search-btn" 
            onClick={handleSearch}
            disabled={loading}
          >
            {loading ? '🔍' : <LuSearch />}
          </button>
        </div>

        {showSuggestions && searchSuggestions.length > 0 && (
          <div className="search-suggestions enhanced">
            {searchSuggestions.map((suggestion, index) => (
              <div
                key={index}
                className="suggestion-item enhanced"
                onClick={() => handleSuggestionClick(suggestion)}
              >
                <LuTarget className="suggestion-icon" />
                {suggestion}
              </div>
            ))}
          </div>
        )}
      </div>

      {mlInsights && (
        <div className="ml-insights">
          <div className="insight-header">
            <LuBrain className="brain-icon" />
            <span>AI Analysis</span>
          </div>
          <div className="insight-content">
            <div className="prediction">
              <span className="label">Predicted Category:</span>
              <span className="category">
                {getCategoryEmoji(mlInsights.predicted_category)} 
                {mlInsights.predicted_category.replace('_', ' ').toUpperCase()}
              </span>
            </div>
            <div className="confidence">
              <span className="label">Confidence:</span>
              <span 
                className="confidence-value"
                style={{ color: getConfidenceColor(mlInsights.confidence_percent) }}
              >
                {mlInsights.confidence_percent?.toFixed(1)}%
              </span>
            </div>
            <div className="algorithm">
              <span className="label">Algorithm:</span>
              <span className="algorithm-name">{mlInsights.algorithm}</span>
            </div>
            {mlInsights.suggestions && mlInsights.suggestions.length > 0 && (
              <div className="suggestions">
                <span className="label">AI Suggestions:</span>
                <div className="suggestion-tags">
                  {mlInsights.suggestions.slice(0, 4).map((suggestion, index) => (
                    <span key={index} className="suggestion-tag">
                      {suggestion}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {error && (
        <div className="error-message">
          <span>❌ {error}</span>
        </div>
      )}

      {searchResults.length > 0 && (
        <div className="search-results">
          <div className="results-header">
            <h4>🎯 Search Results ({searchResults.length})</h4>
            {mlInsights && (
              <div className="ml-badge">
                <LuSparkles />
                AI Enhanced
              </div>
            )}
          </div>
          
          {/* Display results grouped by category */}
          {searchResults.category_groups && Object.keys(searchResults.category_groups).length > 0 ? (
            <div className="category-grouped-results">
              {Object.entries(searchResults.category_groups).map(([categoryName, products]) => (
                <div key={categoryName} className="category-group">
                  <div className="category-header">
                    <h5>{getCategoryEmoji(categoryName)} {categoryName.replace('_', ' ').toUpperCase()}</h5>
                    <span className="category-count">{products.length} products</span>
                  </div>
                  <div className="category-products">
                    {products.map((artwork) => (
                      <div key={artwork.id} className="result-item">
                        <div className="result-image">
                          <img 
                            src={artwork.image_url || '/api/placeholder/200/200'} 
                            alt={artwork.title}
                            onError={(e) => {
                              e.target.src = '/api/placeholder/200/200';
                            }}
                          />
                        </div>
                        <div className="result-info">
                          <h6>{artwork.title}</h6>
                          <p className="result-description">{artwork.description}</p>
                          <div className="result-meta">
                            <span className="result-category">
                              {getCategoryEmoji(artwork.category_name)} {artwork.category_name}
                            </span>
                            <span className="result-price">₹{artwork.price}</span>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            /* Fallback to regular grid display */
            <div className="results-grid">
              {searchResults.map((artwork) => (
                <div key={artwork.id} className="result-item">
                  <div className="result-image">
                    <img 
                      src={artwork.image_url || '/api/placeholder/200/200'} 
                      alt={artwork.title}
                      onError={(e) => {
                        e.target.src = '/api/placeholder/200/200';
                      }}
                    />
                  </div>
                  <div className="result-info">
                    <h5>{artwork.title}</h5>
                    <p className="result-description">{artwork.description}</p>
                    <div className="result-meta">
                      <span className="result-category">
                        {getCategoryEmoji(artwork.category_name)} {artwork.category_name}
                      </span>
                      <span className="result-price">₹{artwork.price}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {searchResults.length === 0 && !loading && searchKeyword && (
        <div className="no-results">
          <div className="no-results-icon">🔍</div>
          <h4>No results found for "{searchKeyword}"</h4>
          <p>Try different keywords or check your spelling</p>
          <div className="suggestions">
            <p>Popular searches:</p>
            <div className="popular-searches">
              {['sweet', 'wedding', 'birthday', 'baby', 'valentine', 'house'].map(term => (
                <button 
                  key={term}
                  className="popular-search-btn"
                  onClick={() => setSearchKeyword(term)}
                >
                  {term}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      <style jsx>{`
        .enhanced-search-container {
          padding: 20px;
          background: #f8fafc;
          border-radius: 12px;
          margin: 20px 0;
        }

        .search-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 20px;
        }

        .search-header h3 {
          margin: 0;
          color: #1e293b;
          font-size: 1.5rem;
        }

        .search-header p {
          margin: 0;
          color: #64748b;
          font-size: 0.9rem;
        }

        .search-input-container {
          position: relative;
          margin-bottom: 20px;
        }

        .search-box.enhanced {
          display: flex;
          align-items: center;
          background: white;
          border: 2px solid #e2e8f0;
          border-radius: 8px;
          padding: 12px 16px;
          transition: all 0.3s ease;
        }

        .search-box.enhanced:focus-within {
          border-color: #3b82f6;
          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box.enhanced input {
          flex: 1;
          border: none;
          outline: none;
          font-size: 1rem;
          padding: 0 12px;
        }

        .search-btn {
          background: #3b82f6;
          color: white;
          border: none;
          padding: 8px 12px;
          border-radius: 6px;
          cursor: pointer;
          transition: background 0.3s ease;
        }

        .search-btn:hover {
          background: #2563eb;
        }

        .search-btn:disabled {
          background: #94a3b8;
          cursor: not-allowed;
        }

        .search-suggestions.enhanced {
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          background: white;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
          z-index: 10;
          max-height: 200px;
          overflow-y: auto;
        }

        .suggestion-item.enhanced {
          display: flex;
          align-items: center;
          padding: 12px 16px;
          cursor: pointer;
          transition: background 0.2s ease;
        }

        .suggestion-item.enhanced:hover {
          background: #f1f5f9;
        }

        .suggestion-icon {
          margin-right: 8px;
          color: #64748b;
        }

        .ml-insights {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 16px;
          border-radius: 8px;
          margin-bottom: 20px;
        }

        .insight-header {
          display: flex;
          align-items: center;
          margin-bottom: 12px;
        }

        .brain-icon {
          margin-right: 8px;
          font-size: 1.2rem;
        }

        .insight-content {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 12px;
        }

        .prediction, .confidence, .algorithm {
          display: flex;
          flex-direction: column;
          gap: 4px;
        }

        .label {
          font-size: 0.8rem;
          opacity: 0.8;
        }

        .category, .confidence-value, .algorithm-name {
          font-weight: 600;
          font-size: 1rem;
        }

        .suggestions {
          grid-column: 1 / -1;
          margin-top: 8px;
        }

        .suggestion-tags {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
          margin-top: 6px;
        }

        .suggestion-tag {
          background: rgba(255, 255, 255, 0.2);
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 0.8rem;
          font-weight: 500;
          border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .search-results {
          margin-top: 20px;
        }

        .results-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 16px;
        }

        .results-header h4 {
          margin: 0;
          color: #1e293b;
        }

        .ml-badge {
          display: flex;
          align-items: center;
          background: #10b981;
          color: white;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 0.8rem;
        }

        .results-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
          gap: 16px;
        }

        .category-grouped-results {
          display: flex;
          flex-direction: column;
          gap: 24px;
        }

        .category-group {
          background: white;
          border: 1px solid #e2e8f0;
          border-radius: 12px;
          padding: 16px;
        }

        .category-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 16px;
          padding-bottom: 12px;
          border-bottom: 2px solid #f1f5f9;
        }

        .category-header h5 {
          margin: 0;
          color: #1e293b;
          font-size: 1.1rem;
          font-weight: 600;
        }

        .category-count {
          background: #3b82f6;
          color: white;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 0.8rem;
          font-weight: 500;
        }

        .category-products {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
          gap: 12px;
        }

        .category-products .result-item {
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          overflow: hidden;
          transition: all 0.2s ease;
        }

        .category-products .result-item:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          border-color: #3b82f6;
        }

        .result-item {
          background: white;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          overflow: hidden;
          transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .result-item:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .result-image {
          width: 100%;
          height: 150px;
          overflow: hidden;
        }

        .result-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        .result-info {
          padding: 12px;
        }

        .result-info h5 {
          margin: 0 0 8px 0;
          color: #1e293b;
          font-size: 1rem;
        }

        .result-description {
          margin: 0 0 8px 0;
          color: #64748b;
          font-size: 0.9rem;
          line-height: 1.4;
        }

        .result-meta {
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .result-category {
          font-size: 0.8rem;
          color: #64748b;
        }

        .result-price {
          font-weight: 600;
          color: #059669;
        }

        .no-results {
          text-align: center;
          padding: 40px 20px;
        }

        .no-results-icon {
          font-size: 3rem;
          margin-bottom: 16px;
        }

        .no-results h4 {
          margin: 0 0 8px 0;
          color: #1e293b;
        }

        .no-results p {
          margin: 0 0 20px 0;
          color: #64748b;
        }

        .popular-searches {
          display: flex;
          flex-wrap: wrap;
          gap: 8px;
          justify-content: center;
        }

        .popular-search-btn {
          background: #f1f5f9;
          border: 1px solid #e2e8f0;
          padding: 6px 12px;
          border-radius: 6px;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .popular-search-btn:hover {
          background: #3b82f6;
          color: white;
          border-color: #3b82f6;
        }

        .error-message {
          background: #fef2f2;
          color: #dc2626;
          padding: 12px;
          border-radius: 6px;
          margin: 16px 0;
        }
      `}</style>
    </div>
  );
};

export default EnhancedSearch;
