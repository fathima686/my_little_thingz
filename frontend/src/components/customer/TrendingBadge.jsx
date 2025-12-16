import React from 'react';

/**
 * Trending Badge Component
 * Displays a visual indicator for trending products
 */
const TrendingBadge = ({ product, showIcon = true }) => {
  // Check if product is trending based on simple heuristics
  // In production, this would call the ML API
  const isTrending = () => {
    // Simple heuristic - in production, this would be fetched from API
    const totalViews = product.total_views || product.views || 0;
    const avgRating = product.average_rating || product.rating || 0;
    const recentSales = product.recent_sales_count || product.sales || 0;
    const numReviews = product.number_of_reviews || product.reviews || 0;
    
    return (
      (recentSales >= 50 || totalViews >= 1000) &&
      avgRating >= 4.0 &&
      numReviews >= 15
    );
  };

  const showTrending = product?.is_trending !== false && isTrending();

  if (!showTrending) return null;

  return (
    <div className="trending-badge" style={{
      position: 'absolute',
      top: '8px',
      right: '8px',
      background: 'linear-gradient(135deg, #e11d48, #f43f5e)',
      color: '#fff',
      padding: '4px 10px',
      borderRadius: '12px',
      fontSize: '11px',
      fontWeight: '700',
      display: 'flex',
      alignItems: 'center',
      gap: '4px',
      boxShadow: '0 2px 8px rgba(225, 29, 72, 0.3)',
      zIndex: 10,
      letterSpacing: '0.5px',
      textTransform: 'uppercase'
    }}>
      {showIcon && <span>🔥</span>}
      <span>Trending</span>
    </div>
  );
};

export default TrendingBadge;




















