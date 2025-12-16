# 🔍 How KNN Works in Your Project for Similar Products

## 🎯 Overview

Your KNN (K-Nearest Neighbors) algorithm finds **similar products** by comparing **4 different features** and calculating a similarity score.

---

## 📊 How It Works - Step by Step

### **Step 1: Get Target Product**
```
User views: "Custom Chocolate Box" (ID: 123)
   ↓
KNN extracts features:
   - Category: "custom chocolate"
   - Price: ₹1,500
   - Title: "Custom Chocolate Box"
   - Description: "Delicious handmade chocolates"
```

### **Step 2: Compare With All Other Products**
```
Compare with every product in database (up to 200 products)
   ↓
Calculate similarity score for each product
```

### **Step 3: Calculate Similarity Score**

KNN uses **weighted similarity** with 4 factors:

| Feature | Weight | How It's Calculated |
|---------|--------|---------------------|
| **Category** | **40%** | Exact match = full points |
| **Price** | **30%** | Price difference percentage |
| **Title** | **20%** | Jaccard similarity (shared words) |
| **Description** | **10%** | Jaccard similarity (shared words) |

**Total Similarity = Category Score + Price Score + Title Score + Description Score**

### **Step 4: Filter and Sort**
```
Keep only products with similarity ≥ 0.3 (threshold)
   ↓
Sort by similarity (highest first)
   ↓
Return top 8 products
```

---

## 🧮 Similarity Calculation Examples

### **Example 1: Very Similar Products**

**Target:** "Custom Chocolate Box" (₹1,500, Category: custom chocolate)

**Candidate:** "Premium Chocolate Hamper" (₹1,600, Category: custom chocolate)

**Calculation:**
```
Category Match: Yes (same category) → 40% × 1.0 = 0.40

Price Similarity: 
   Price difference = |1500 - 1600| = 100
   Max price = 1600
   Similarity = 1 - (100/1600) = 0.9375
   Score = 30% × 0.9375 = 0.28

Title Similarity:
   "chocolate" appears in both
   Similarity = shared words / total unique words
   Score = 20% × ~0.5 = 0.10

Description Similarity:
   Some shared words about chocolate
   Score = 10% × ~0.3 = 0.03

TOTAL SIMILARITY = 0.40 + 0.28 + 0.10 + 0.03 = 0.81 (81%) ✅
```

**Result:** Very similar, will be recommended! ⭐

---

### **Example 2: Different Products**

**Target:** "Custom Chocolate Box" (₹1,500, Category: custom chocolate)

**Candidate:** "Photo Frame" (₹800, Category: frames)

**Calculation:**
```
Category Match: No (different category) → 0.00

Price Similarity: 
   Price difference = |1500 - 800| = 700
   Max price = 1500
   Similarity = 1 - (700/1500) = 0.533
   Score = 30% × 0.533 = 0.16

Title Similarity:
   No shared words
   Score = 20% × 0.0 = 0.00

Description Similarity:
   No shared words
   Score = 10% × 0.0 = 0.00

TOTAL SIMILARITY = 0.00 + 0.16 + 0.00 + 0.00 = 0.16 (16%) ❌
```

**Result:** Not similar enough, won't be recommended (below 30% threshold)

---

## 🎨 Price Tier Classification

Your KNN also groups products by price:

| Price Range | Tier | What It Means |
|-------------|------|---------------|
| < ₹500 | `budget` | Budget-friendly |
| ₹500 - ₹999 | `mid` | Mid-range |
| ₹1000 - ₹1999 | `premium` | Premium |
| ≥ ₹2000 | `luxury` | Luxury |

---

## 📝 Jaccard Similarity (Text Matching)

How similar are two product titles?

**Formula:** `Shared Words / Total Unique Words`

**Example:**

**Title 1:** "Custom Chocolate Box"  
**Title 2:** "Chocolate Gift Box"

```
Shared words: ["chocolate", "box"]
Total unique words: ["custom", "chocolate", "box", "gift"]

Similarity = 2 / 4 = 0.5 (50% similar)
```

---

## 🔌 How to Use KNN API

### **Method 1: Find Similar Products**

**Endpoint:** `GET /backend/api/customer/knn_recommendations.php`

**Parameters:**
- `product_id` - Product to find similarities for
- `limit` - Number of results (default: 8)
- `k` - Number of neighbors (default: 5)
- `similarity_threshold` - Minimum similarity (default: 0.3)

**Example:**
```
GET /backend/api/customer/knn_recommendations.php?product_id=123&limit=5
```

**Response:**
```json
{
  "status": "success",
  "algorithm": "KNN Product Similarity",
  "recommendations": [
    {
      "id": 45,
      "title": "Premium Chocolate Hamper",
      "price": 1600,
      "similarity_score": 0.81,
      "recommendation_type": "similar_products",
      "category_name": "custom chocolate"
    },
    {
      "id": 67,
      "title": "Deluxe Chocolate Collection",
      "price": 1400,
      "similarity_score": 0.75,
      "recommendation_type": "similar_products"
    }
  ],
  "count": 5
}
```

---

### **Method 2: User-Based Recommendations**

**Endpoint:** `GET /backend/api/customer/knn_recommendations.php`

**Parameters:**
- `user_id` - User ID for personalized recommendations
- `type=user` - Use collaborative filtering

**Example:**
```
GET /backend/api/customer/knn_recommendations.php?user_id=456&type=user&limit=10
```

**How It Works:**
1. Find users with similar purchase history
2. See what products they liked
3. Recommend products the user hasn't seen yet

---

## ⚙️ Configuration

### **Adjust Similarity Weights**

Edit `backend/services/KNNRecommendationEngine.php` line 178-183:

```php
$weights = [
    'category' => 0.5,    // Increase to emphasize category
    'price' => 0.25,       // Decrease to reduce price importance
    'title' => 0.20,
    'description' => 0.05
];
```

### **Adjust Similarity Threshold**

Default: 0.3 (30% minimum similarity)

To get more results (lower quality):
```
?similarity_threshold=0.2  // 20% minimum
```

To get fewer results (higher quality):
```
?similarity_threshold=0.5  // 50% minimum
```

### **Adjust K Value**

Default: 5 nearest neighbors

To consider more neighbors:
```
?k=10  // Consider 10 nearest neighbors
```

To consider fewer neighbors:
```
?k=3   // Consider 3 nearest neighbors
```

---

## 🎯 Use Cases in Your Project

### **1. Product Detail Page**
```
User viewing: "Wedding Card Collection"
   ↓
Show: "Similar Products You Might Like"
   ↓
KNN finds: Other wedding cards, greeting cards, invitation sets
```

### **2. Shopping Cart**
```
User has: "Birthday Gift Box" in cart
   ↓
Suggest: Related products at checkout
   ↓
KNN finds: Other birthday items, party supplies, greeting cards
```

### **3. User Dashboard**
```
User logged in: ID 456
   ↓
Show: "Recommended For You"
   ↓
KNN finds: Products similar to what they've purchased/browsed
```

---

## 🧪 Test KNN

### **Test in Browser:**
```
http://localhost/my_little_thingz/backend/ml_algorithms_dashboard.html
```

### **Test with cURL:**
```bash
curl "http://localhost/my_little_thingz/backend/api/customer/knn_recommendations.php?product_id=1&limit=5"
```

### **Test with JavaScript:**
```javascript
fetch('/my_little_thingz/backend/api/customer/knn_recommendations.php?product_id=123&limit=8')
  .then(response => response.json())
  .then(data => {
    console.log('Similar products:', data.recommendations);
    console.log('Algorithm:', data.algorithm);
  });
```

---

## 🎉 Real-World Example

**Scenario:** Customer browsing "Custom Photo Frame"

**What KNN Does:**
1. ✅ Finds other photo frames (category match = 40% similarity)
2. ✅ Finds frames in similar price range (price match = 30% similarity)
3. ✅ Finds products with "photo", "frame", "picture" keywords (title match)
4. ✅ Filters out completely unrelated products
5. ✅ Returns top 8 most similar frames

**Result:**
```
Recommended Products:
1. "Elegant Photo Frame" (similarity: 85%)
2. "Premium Picture Frame" (similarity: 80%)
3. "Custom Memory Frame" (similarity: 75%)
4. "Bespoke Photo Display" (similarity: 70%)
...
```

---

## 📊 Performance

- **Speed:** < 100ms per request
- **Database Queries:** 2-3 queries
- **Products Compared:** Up to 200 at a time
- **Threshold:** 30% minimum similarity
- **Default Results:** Top 8 products

---

## 🔍 Debugging

### **Why am I getting no results?**

**Solution:** Lower the similarity threshold
```
?similarity_threshold=0.2
```

### **Why are results not accurate?**

**Solutions:**
1. Increase category weight (most important)
2. Adjust price tiers in your database
3. Ensure product titles/descriptions are descriptive

### **Why is it slow?**

**Solutions:**
1. Database indexes on `category_id`, `price`
2. Reduce `limit` parameter
3. Optimize artwork table queries

---

## 📚 Key Files

| File | Purpose |
|------|---------|
| `backend/services/KNNRecommendationEngine.php` | Main KNN algorithm |
| `backend/api/customer/knn_recommendations.php` | API endpoint |
| `backend/ml_algorithms_dashboard.html` | Test interface |

---

## 🎯 Summary

**KNN in your project finds similar products by:**
1. ✅ Comparing 4 features (category, price, title, description)
2. ✅ Using weighted scoring (category matters most - 40%)
3. ✅ Filtering by similarity threshold (30% minimum)
4. ✅ Returning top K most similar products (default: 8)
5. ✅ Supporting both product-based and user-based recommendations

**Result:** Smart "Customers Also Bought" and "Similar Products" recommendations! 🚀



















