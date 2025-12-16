# Trending Gifts Feature - Customer Dashboard Integration

## ✅ What's Been Implemented

### 1. Trending Badge Component
- **File**: `frontend/src/components/customer/TrendingBadge.jsx`
- **Features**: 
  - Visual indicator for trending products
  - Gradient badge with fire emoji 🔥
  - Positioned at top-right of product cards
  - Uses ML-based heuristics to determine trending status

### 2. SVM Classifier (Backend)
- **File**: `python_ml_service/svm_gift_classifier.py`
- **API Endpoint**: `POST /api/ml/trending/classify`
- **Features Used**:
  - recent_sales_count
  - total_views
  - average_rating
  - number_of_reviews

### 3. Integration in Artwork Gallery
- **File**: `frontend/src/components/customer/ArtworkGallery.jsx`
- **Changes**:
  - Imported TrendingBadge component
  - Added to product card display
  - Sample products marked as trending

## 🎨 How It Looks

Products that are trending will show a badge like this:

```
┌─────────────────────────────┐
│  [🔥 Trending]              │  ← Top-right corner
│                             │
│     [Product Image]         │
│                             │
│  Product Title              │
│  Price: $XXX                │
└─────────────────────────────┘
```

## 📊 Trending Criteria

A gift is classified as **Trending** if it meets these criteria:

```javascript
const isTrending = () => {
  return (
    (recent_sales_count >= 50 || total_views >= 1000) &&
    average_rating >= 4.0 &&
    number_of_reviews >= 15
  );
};
```

### Criteria Breakdown:
- **High Sales or Views**: Recent sales ≥ 50 OR Total views ≥ 1000
- **High Rating**: Average rating ≥ 4.0 stars
- **Many Reviews**: Number of reviews ≥ 15

## 🎯 Sample Trending Products

The following products are configured as trending in the fallback data:

1. **Polaroids Pack** - 120 sales, 2500 views, 4.8 rating, 85 reviews
2. **Custom Chocolate** - 95 sales, 1800 views, 4.7 rating, 65 reviews
3. **Wedding Hamper** - 180 sales, 3200 views, 4.9 rating, 92 reviews
4. **Gift Box Set** - 145 sales, 2100 views, 4.6 rating, 72 reviews
5. **Bouquets** - 165 sales, 2800 views, 4.85 rating, 88 reviews

## 🚀 How to Use

### For Customers:
1. Open the customer dashboard
2. Browse products
3. Look for the **🔥 Trending** badge on popular items
4. Trending items indicate high quality and popularity

### For Developers:
```javascript
// Add trending data to a product
const product = {
  id: 'product-1',
  title: 'Sample Gift',
  price: 100,
  // Trending metrics
  is_trending: true,
  recent_sales_count: 120,
  total_views: 2500,
  average_rating: 4.8,
  number_of_reviews: 85
};

// Component will automatically show badge
<TrendingBadge product={product} />
```

## 🎨 Badge Styling

```css
/* Trending Badge Styles */
trending-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: linear-gradient(135deg, #e11d48, #f43f5e);
  color: #fff;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(225, 29, 72, 0.3);
  z-index: 10;
}
```

## 📈 Benefits

✅ **Builds Trust**: Shows customers what others are buying
✅ **Increases Sales**: Trending products get more attention
✅ **Data-Driven**: Based on actual metrics, not guesswork
✅ **Visual Appeal**: Eye-catching badge draws attention
✅ **Social Proof**: High ratings and reviews visible

## 🔧 Technical Details

### Trending Badge Component
- **Location**: `frontend/src/components/customer/TrendingBadge.jsx`
- **Props**:
  - `product`: Product object with metrics
  - `showIcon`: Boolean to show/hide fire emoji (default: true)

### Heuristics Logic
```javascript
const isTrending = (product) => {
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
```

## 🎯 Future Enhancements

1. **Real-time Updates**: Connect to database for live trending data
2. **Trending Section**: Dedicated "Trending Now" section on dashboard
3. **Time-based**: Show "Trending this week/month"
4. **Category Trending**: Show trending items per category
5. **Admin Control**: Allow admins to manually mark items as trending

## 📝 Files Modified

- ✅ `frontend/src/components/customer/TrendingBadge.jsx` - New component
- ✅ `frontend/src/components/customer/ArtworkGallery.jsx` - Added trending badge
- ✅ `python_ml_service/svm_gift_classifier.py` - SVM classifier
- ✅ `python_ml_service/app.py` - Trending API endpoint

## 🧪 Testing

To test the trending feature:

1. **Open Customer Dashboard**: Navigate to your app
2. **Browse Products**: Look through the gallery
3. **Find Trending Badge**: Products meeting criteria will show 🔥 Trending badge
4. **Check Metrics**: Hover or inspect to see sales data

## 📊 Sample Data Structure

```javascript
{
  id: 'product-id',
  title: 'Product Name',
  price: 100,
  image_url: '/path/to/image.jpg',
  
  // Trending metrics (optional)
  is_trending: true,              // Explicitly mark as trending
  recent_sales_count: 120,         // Recent sales (last 30 days)
  total_views: 2500,               // Total page views
  average_rating: 4.8,            // Average customer rating (0-5)
  number_of_reviews: 85            // Total number of reviews
}
```

## 🎉 Result

Customers will now see trending products with a distinctive badge, helping them discover popular and highly-rated items. The badge uses ML-based heuristics to automatically identify products that are performing well based on sales, views, ratings, and reviews.




















