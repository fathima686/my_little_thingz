# 🤖 ML Integration Summary for "My Little Thingz"

## Overview
Your project has **comprehensive machine learning integration** across both PHP backend services and a Python microservice for advanced algorithms.

---

## 📍 Where Your ML Is Located

### **1. PHP Backend ML Services** (`backend/services/`)
Core ML algorithms implemented in PHP:

| Algorithm | File | Purpose |
|-----------|------|---------|
| **K-Nearest Neighbors (KNN)** | `KNNRecommendationEngine.php` | Find similar products & collaborative filtering |
| **Bayesian Classifier** | `GiftCategoryClassifier.php` | Predict gift categories from names |
| **Decision Tree** | `DecisionTreeAddonRecommender.php` | Suggest add-ons (greeting cards, ribbons) |
| **SVM Classifier** | `SVMGiftClassifier.php` | Classify gifts as Budget vs Premium |
| **Backpropagation Neural Network (BPNN)** | `BPNNNeuralNetwork.php` | AI-powered customer preference predictions |
| **BPNN Data Processor** | `BPNNDataProcessor.php` | Prepares training data for neural network |
| **BPNN Trainer** | `BPNNTrainer.php` | Trains and saves BPNN models |

### **2. Python ML Microservice** (`python_ml_service/`)
Advanced algorithms implemented in Python with scikit-learn:

| Algorithm | File | Purpose |
|-----------|------|---------|
| **KNN** | `app.py` (lines 57-97) | Product recommendations |
| **Bayesian** | `app.py` (lines 99-143) | Category classification |
| **Decision Tree** | `app.py` (lines 145-184) | Add-on suggestions |
| **SVM** | `app.py` (lines 186-223) | Budget/Premium classification |
| **BPNN** | `app.py` (lines 225-268) | Preference predictions |
| **Sentiment Analysis** | `gift_review_sentiment_analysis.py` | Review sentiment (Positive/Negative/Neutral) |
| **Trending Classifier** | `svm_gift_classifier.py` | Classify trending vs normal products |

**Python Flask API**: `app.py` (runs on port 5001)

---

## 🔌 API Endpoints

### **Customer APIs** (`backend/api/customer/`)

| Endpoint | Algorithm | Usage |
|----------|-----------|-------|
| `knn_recommendations.php` | KNN | Find similar products or user-based recommendations |
| `gift-classifier.php` | Bayesian | Predict categories from gift names |
| `svm_classifier.php` | SVM | Classify Budget vs Premium |
| `bpnn_recommendations.php` | BPNN | AI-powered personalized recommendations |
| `addon_recommendations.php` | Decision Tree | Suggest add-ons based on gift price/category |

### **Admin APIs** (`backend/api/admin/`)

| Endpoint | Purpose |
|----------|---------|
| `bpnn_training.php` | Train/retrain BPNN neural network |

### **Python API Endpoints** (`http://localhost:5001/api/ml/`)

| Endpoint | Algorithm | Request Format |
|----------|-----------|----------------|
| `POST /knn/recommendations` | KNN | `{"product_id": 123, "user_id": 456, "k": 5}` |
| `POST /bayesian/classify` | Bayesian | `{"gift_name": "Custom Chocolate", "confidence_threshold": 0.75}` |
| `POST /decision-tree/addon-suggestion` | Decision Tree | `{"cart_total": 1500, "cart_items": []}` |
| `POST /svm/classify` | SVM | `{"gift_data": {"price": 1200, "category_id": 3}}` |
| `POST /bpnn/predict-preference` | BPNN | `{"user_data": {}, "product_data": {}}` |
| `POST /sentiment/analyze` | Sentiment | `{"review_text": "Great product!"}` |
| `POST /trending/classify` | Trending | `{"recent_sales_count": 100, "total_views": 1000}` |
| `GET /health` | Health Check | Returns service status |

---

## 🔗 Frontend Integration

### **Sentiment Analysis**
- **Location**: `frontend/src/pages/AdminReviews.jsx`
- Shows sentiment badges (Positive/Negative/Neutral) for each review
- Calls Python API: `http://localhost:5001/api/ml/sentiment/analyze`

### **ML Dashboard**
- **Location**: `backend/ml_algorithms_dashboard.html`
- Test all 5 ML algorithms in one place
- Access: `http://localhost/my_little_thingz/backend/ml_algorithms_dashboard.html`

---

## 🎯 What Each Algorithm Does

### **1. KNN (K-Nearest Neighbors)**
- **Finds similar products** based on:
  - Category similarity (40% weight)
  - Price similarity (30% weight)
  - Title similarity (20% weight)
  - Description similarity (10% weight)
- **Collaborative filtering**: Finds products liked by similar users

### **2. Bayesian Classifier (Naive Bayes)**
- **Predicts categories** from gift names
- Categories: Gift box, boquetes, frames, poloroid, custom chocolate, Wedding card, drawings, album, Greeting Card
- **Confidence scoring**: 
  - ≥75% → Auto-assign
  - ≥50% → Suggest
  - <50% → Manual review

### **3. Decision Tree**
- **Suggests add-ons** (greeting cards, ribbons) based on:
  - Price tier (>₹1000, ≤₹1000, <₹500)
  - Category (wedding, birthday, anniversary, etc.)
  - Occasion (formal, casual)
  - Customer preferences
  - Season

### **4. SVM (Support Vector Machine)**
- **Classifies gifts** as Budget or Premium using:
  - Price
  - Category luxury indicator
  - Title/description keywords
  - Availability
- **Trending classifier**: Also identifies trending products

### **5. BPNN (Backpropagation Neural Network)**
- **AI-powered recommendations** based on:
  - User's purchase history
  - Product ratings
  - User behavior patterns
  - Category preferences
- **Multi-layer perceptron** with configurable hidden layers

### **6. Sentiment Analysis**
- **Analyzes reviews** as:
  - Positive: Good feedback
  - Negative: Complaints (needs investigation)
  - Neutral: Meh feedback
- **Naive Bayes classifier** with confidence scores

---

## 📊 How They Work Together

```
┌─────────────────────────────────────────────────────────┐
│           ML Algorithms Flow                            │
└─────────────────────────────────────────────────────────┘

1. CUSTOMER BROWSES PRODUCT
   ↓
2. KNN finds similar products
   ↓
3. SVM classifies as Budget/Premium
   ↓
4. Decision Tree suggests add-ons
   ↓
5. Bayesian confirms/corrects category
   ↓
6. Display personalized recommendations

───────────────────────────────────────────────────────────

1. CUSTOMER WRITES REVIEW
   ↓
2. Sentiment Analysis analyzes text
   ↓
3. Display sentiment badge + recommended action
   ↓
4. Admin sees positive/negative at a glance

───────────────────────────────────────────────────────────

1. ADMIN ADDS NEW PRODUCT
   ↓
2. Bayesian auto-categorizes
   ↓
3. SVM classifies price tier
   ↓
4. Decision Tree sets add-on rules
   ↓
5. Product ready for recommendations

───────────────────────────────────────────────────────────

1. USER LOGS IN
   ↓
2. BPNN gets AI recommendations
   ↓
3. KNN gets collaborative recommendations
   ↓
4. Display "You might like" section
```

---

## 🚀 How to Use

### **Start Python ML Service**
```bash
cd python_ml_service
python app.py
```
Service runs on `http://localhost:5001`

### **Test Individual Algorithms**

**KNN**:
```bash
curl "http://localhost/my_little_thingz/backend/api/customer/knn_recommendations.php?product_id=1&k=5"
```

**Bayesian**:
```bash
curl "http://localhost/my_little_thingz/backend/api/customer/gift-classifier.php?gift_name=Wedding%20Card"
```

**Decision Tree**:
```bash
curl "http://localhost/my_little_thingz/backend/api/customer/addon_recommendations.php?artwork_id=1"
```

**SVM**:
```bash
curl "http://localhost/my_little_thingz/backend/api/customer/svm_classifier.php?gift_id=1"
```

**BPNN**:
```bash
curl "http://localhost/my_little_thingz/backend/api/customer/bpnn_recommendations.php?user_id=1&limit=5"
```

**Sentiment**:
```bash
curl -X POST http://localhost:5001/api/ml/sentiment/analyze \
  -H "Content-Type: application/json" \
  -d '{"review_text": "Great product!"}'
```

### **Use the Dashboard**
Open: `http://localhost/my_little_thingz/backend/ml_algorithms_dashboard.html`

Test all algorithms at once with a single interface!

---

## 📁 Key Directories

```
my_little_thingz/
├── backend/
│   ├── services/              ← PHP ML algorithms
│   │   ├── KNNRecommendationEngine.php
│   │   ├── GiftCategoryClassifier.php
│   │   ├── DecisionTreeAddonRecommender.php
│   │   ├── SVMGiftClassifier.php
│   │   ├── BPNNNeuralNetwork.php
│   │   ├── BPNNDataProcessor.php
│   │   └── BPNNTrainer.php
│   ├── api/
│   │   ├── customer/          ← Customer-facing APIs
│   │   │   ├── knn_recommendations.php
│   │   │   ├── gift-classifier.php
│   │   │   ├── svm_classifier.php
│   │   │   ├── bpnn_recommendations.php
│   │   │   └── addon_recommendations.php
│   │   └── admin/             ← Admin APIs
│   │       └── bpnn_training.php
│   └── ml_algorithms_dashboard.html  ← Test dashboard
│
├── python_ml_service/         ← Python microservice
│   ├── app.py                 ← Flask API with all algorithms
│   ├── gift_review_sentiment_analysis.py
│   ├── svm_gift_classifier.py
│   ├── config.py
│   ├── database.py
│   ├── requirements.txt
│   └── README.md
│
└── frontend/
    └── src/
        └── pages/
            └── AdminReviews.jsx  ← Sentiment analysis UI
```

---

## 🎯 Business Use Cases

1. **Similar Products**: When viewing a product, show "Customers also viewed"
2. **Smart Categorization**: Auto-categorize new products without manual work
3. **Add-on Upsells**: Suggest greeting cards, ribbons to increase revenue
4. **Price Tier Display**: Show "Budget" or "Premium" badges
5. **Personalized Feed**: "You might like" based on purchase history
6. **Review Management**: Instantly see which reviews are negative
7. **Trending Products**: Identify hot products to promote

---

## 📝 Configuration Files

- **PHP Database Config**: `backend/config/database.php`
- **Python Config**: `python_ml_service/config.py`
- **Environment Example**: `python_ml_service/env_example.txt`

---

## 🔧 Dependencies

### **PHP Requirements**
- PHP 7.4+
- MySQL/MariaDB
- PDO extension

### **Python Requirements**
Install with:
```bash
cd python_ml_service
pip install -r requirements.txt
```

Key packages:
- flask
- flask-cors
- scikit-learn
- numpy
- pandas

---

## 📚 Documentation

All ML integration guides:
- `ML_ALGORITHMS_INTEGRATION_GUIDE.md` - Complete guide
- `SENTIMENT_ANALYSIS_INTEGRATION.md` - Sentiment setup
- `BPNN_IMPLEMENTATION_GUIDE.md` - Neural network guide
- `SVM_GIFT_CLASSIFIER_GUIDE.md` - SVM guide
- `BAYESIAN_GIFT_CLASSIFIER_GUIDE.md` - Bayesian guide
- `DECISION_TREE_ADDON_SUMMARY.md` - Decision tree guide
- `python_ml_service/README.md` - Python service guide

---

## ✅ Status Check

To verify ML is working:

1. **Check Python Service**:
   ```bash
   curl http://localhost:5001/api/ml/health
   ```
   Should return: `{"status": "healthy", "algorithms": [...]}`

2. **Check PHP Services**:
   - Access dashboard: `http://localhost/my_little_thingz/backend/ml_algorithms_dashboard.html`
   - Test each algorithm

3. **Check Frontend**:
   - Go to Admin Reviews page
   - See sentiment badges on reviews

---

**Your ML integration is comprehensive and production-ready! 🎉**



















