# 🎁 Bayesian Gift Category Classifier Implementation Guide

## Overview
This guide explains the **Rule-based Keyword Classifier** with Naive Bayes-like confidence scoring. It automatically predicts gift categories from gift names using keyword matching and priority weighting.

**No existing code was modified.** New files were added:
- `backend/services/GiftCategoryClassifier.php` - Classification engine
- `backend/api/customer/gift-classifier.php` - Public API for predictions
- `backend/api/admin/gift-classifier-manage.php` - Admin management API

---

## How It Works

### 1. **Rule-Based Classification**
The classifier analyzes gift names using predefined keyword patterns:

| Category | Keywords | Priority |
|----------|----------|----------|
| Gift box | box, hamper, case, package, set | 10 |
| boquetes | bouquet, flowers, rose, floral | 9 |
| frames | frame, photo frame, picture frame | 8 |
| poloroid | polaroid, instant photo, photo print | 8 |
| custom chocolate | chocolate, choco, candy, sweet | 7 |
| Wedding card | wedding, marriage, card, invitation | 8 |
| drawings | drawing, sketch, art, illustration | 7 |
| album | album, photo album, scrapbook | 7 |
| Greeting Card | greeting card, birthday card, card | 6 |

### 2. **Confidence Scoring**
For each gift name:
- Calculates keyword match strength (exact phrase matching > single word)
- Applies priority weights per category
- Returns confidence as 0.0 - 1.0

### 3. **Smart Hybrid Action**
Based on confidence:

| Confidence | Action | Behavior |
|------------|--------|----------|
| ≥ 0.75 (Threshold) | `auto_assign` | Automatically assign category |
| 0.50 - 0.75 | `suggest` | Show suggestions for manual review |
| < 0.50 | `manual_review` | Requires human decision |

---

## API Usage

### **1. Predict Single Gift Category**

**Endpoint:** `GET /backend/api/customer/gift-classifier.php`

```bash
curl "http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php?gift_name=Birthday%20Greeting%20Card"
```

**Response:**
```json
{
  "status": "success",
  "predictions": [
    {
      "input_name": "Birthday Greeting Card",
      "predicted_category": "Greeting Card",
      "confidence": 0.87,
      "confidence_percent": 87.0,
      "suggested_categories": {
        "Wedding card": 0.65,
        "frames": 0.42
      },
      "action": "auto_assign",
      "reason": "Matched keywords: greeting card, card, birthday card"
    }
  ]
}
```

### **2. Predict Multiple Gift Names**

```bash
curl "http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php?gift_names[]=Wedding%20Hamper&gift_names[]=Custom%20Chocolate&confidence_threshold=0.7"
```

### **3. Adjust Confidence Threshold**

```bash
curl "http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php?gift_name=Premium%20Box&confidence_threshold=0.85"
```

Lower threshold = more auto-assigns (but less accurate)  
Higher threshold = fewer auto-assigns (but higher accuracy)

---

## Admin APIs

### **1. Get Classification Statistics**

**Endpoint:** `GET /backend/api/admin/gift-classifier-manage.php?action=stats`

```bash
curl "http://localhost/my_little_thingz/backend/api/admin/gift-classifier-manage.php?action=stats"
```

**Response:**
```json
{
  "status": "success",
  "stats": {
    "total_artworks": 37,
    "categorized": 29,
    "uncategorized": 8,
    "unique_categories": 8
  },
  "categorization_rate": "78.38%"
}
```

### **2. Get Uncategorized Gifts for Classification**

```bash
curl "http://localhost/my_little_thingz/backend/api/admin/gift-classifier-manage.php?action=uncategorized&limit=10"
```

**Response includes predictions for each uncategorized gift.**

### **3. Get Available Categories**

```bash
curl "http://localhost/my_little_thingz/backend/api/admin/gift-classifier-manage.php?action=categories"
```

### **4. Apply Classification to Single Artwork**

**Endpoint:** `POST /backend/api/admin/gift-classifier-manage.php`

```bash
curl -X POST http://localhost/my_little_thingz/backend/api/admin/gift-classifier-manage.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "apply_classification",
    "artwork_id": 42,
    "category_name": "Greeting Card"
  }'
```

### **5. Bulk Apply Auto-Classifications (DRY RUN)**

```bash
curl -X POST http://localhost/my_little_thingz/backend/api/admin/gift-classifier-manage.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "apply_bulk_classification",
    "confidence_threshold": 0.8,
    "dry_run": true
  }'
```

This shows what WOULD be classified without actually modifying the database.

### **6. Bulk Apply Auto-Classifications (LIVE)**

```bash
curl -X POST http://localhost/my_little_thingz/backend/api/admin/gift-classifier-manage.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "apply_bulk_classification",
    "confidence_threshold": 0.8,
    "dry_run": false
  }'
```

---

## React Frontend Integration

### **Example 1: Use in Supplier Product Form**

```jsx
import { useState, useEffect } from 'react';

export function AddProductForm() {
  const [productName, setProductName] = useState('');
  const [suggestedCategory, setSuggestedCategory] = useState(null);
  const [confidence, setConfidence] = useState(0);

  const handleProductNameChange = async (e) => {
    const name = e.target.value;
    setProductName(name);

    if (name.length > 3) {
      const response = await fetch(
        `http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php?gift_name=${encodeURIComponent(name)}`
      );
      const data = await response.json();
      
      if (data.predictions && data.predictions[0]) {
        const pred = data.predictions[0];
        setSuggestedCategory(pred.predicted_category);
        setConfidence(pred.confidence_percent);
      }
    }
  };

  return (
    <div>
      <input 
        type="text"
        value={productName}
        onChange={handleProductNameChange}
        placeholder="Enter product name"
      />
      
      {suggestedCategory && (
        <div className="suggestion">
          <p>💡 Suggested: {suggestedCategory} ({confidence}%)</p>
        </div>
      )}
    </div>
  );
}
```

### **Example 2: Display on Gift Browse Page**

```jsx
export function GiftCard({ gift }) {
  const [classification, setClassification] = useState(null);

  useEffect(() => {
    // Get classification on mount
    fetch(`http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php?gift_name=${encodeURIComponent(gift.title)}`)
      .then(r => r.json())
      .then(data => {
        if (data.predictions) {
          setClassification(data.predictions[0]);
        }
      });
  }, [gift.title]);

  return (
    <div className="gift-card">
      <h3>{gift.title}</h3>
      {classification && (
        <div className="classification-badge">
          <span className="category">{classification.predicted_category}</span>
          <span className="confidence">{classification.confidence_percent}% match</span>
        </div>
      )}
    </div>
  );
}
```

---

## Training & Improvement

### **Train Classifier from Existing Data**

```bash
curl -X POST http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php \
  -H "Content-Type: application/json" \
  -d '{"action": "train"}'
```

This analyzes all existing categorized gifts to:
- Build frequency maps for each category
- Identify common word patterns
- Optimize keyword weights (foundation for future ML enhancement)

---

## Customization

### **Add New Categories/Keywords**

Edit `backend/services/GiftCategoryClassifier.php`:

```php
private function initializePatterns() {
    $this->categoryPatterns = [
        // ... existing ...
        'Personalized Gifts' => [
            'keywords' => ['custom', 'personalized', 'monogram', 'engraved', 'bespoke'],
            'priority' => 9,
            'weight' => 0.90
        ]
    ];
}
```

### **Adjust Confidence Threshold**

**Globally in classifier:**
```php
$prediction = $classifier->classifyGift($name, 0.85); // Higher = stricter
```

**Per API call:**
```bash
curl "...?gift_name=Box&confidence_threshold=0.9"
```

---

## Testing

### **Test Page Provided**

```bash
Open: http://localhost/my_little_thingz/backend/test_gift_classifier.html
```

This provides interactive testing UI.

### **Manual Testing**

1. **Get uncategorized gifts:**
   ```bash
   curl "http://localhost/.../api/admin/gift-classifier-manage.php?action=uncategorized"
   ```

2. **Dry run bulk classification:**
   ```bash
   curl -X POST ... -d '{"action":"apply_bulk_classification","dry_run":true}'
   ```

3. **Review results and adjust confidence threshold**

4. **Apply for real:**
   ```bash
   curl -X POST ... -d '{"action":"apply_bulk_classification","dry_run":false}'
   ```

---

## Future Enhancements

### Phase 2: True Naive Bayes (Optional)
If you want higher accuracy, implement true ML:

1. **Export training data** from the classifier analysis
2. **Train Python Naive Bayes model** using sklearn
3. **Create Python prediction service** (predict_gift_category.py)
4. **Call from PHP** via shell_exec with JSON return
5. **Fallback to rule-based** if Python service unavailable

Example Python snippet:
```python
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.naive_bayes import MultinomialNB
import joblib

# Train
vectorizer = CountVectorizer()
X = vectorizer.fit_transform(gift_names)
model = MultinomialNB()
model.fit(X, categories)

# Save
joblib.dump(model, 'gift_model.pkl')
joblib.dump(vectorizer, 'vectorizer.pkl')
```

---

## Architecture Benefits

✅ **Non-destructive** - No existing code modified  
✅ **Backward compatible** - Works with current database  
✅ **Extensible** - Easy to add more categories/keywords  
✅ **Scalable** - Can be upgraded to true ML later  
✅ **Smart hybrid** - Auto-assigns high confidence, suggests low confidence  
✅ **Explainable** - Shows why each prediction was made  

---

## Troubleshooting

### Q: Classifications not appearing?
**A:** Check database connection in API files, ensure categories table has 'active' status items.

### Q: Confidence too low?
**A:** Adjust keywords in `initializePatterns()` or lower the threshold.

### Q: Want better accuracy?
**A:** Run training API endpoint to analyze word patterns, then consider Phase 2 (Python ML).

---

## Support Files

- **Service Class:** `backend/services/GiftCategoryClassifier.php`
- **Public API:** `backend/api/customer/gift-classifier.php`
- **Admin API:** `backend/api/admin/gift-classifier-manage.php`
- **Testing:** Use provided test endpoints

---

**Status:** ✅ Ready to use. No breaking changes to existing project.