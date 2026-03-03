# 🎨 Craft Image Validation System - Setup Guide

## Overview

This guide will help you set up the AI-assisted practice image validation module for your skill-based e-learning platform. The system integrates seamlessly with your existing infrastructure without disrupting current user flows.

## 🚀 Features

- **MobileNet-based craft category classification** (7 categories)
- **Category mismatch detection** (image vs selected tutorial)
- **AI-generated image detection** via metadata analysis
- **Perceptual hashing** for duplicate detection (enhanced existing system)
- **Explainable confidence scores** for admin review
- **Admin dashboard** with comprehensive validation insights
- **Seamless integration** with existing practice upload flow

## 📋 Prerequisites

- PHP 7.4+ with GD extension enabled
- Python 3.8+ 
- MySQL/MariaDB database
- Existing e-learning platform (already set up)
- ~2GB RAM for AI services
- ~1GB disk space for models and dependencies

## 🛠️ Installation Steps

### Step 1: Set Up Craft Classifier Service

1. **Navigate to the ML service directory:**
   ```bash
   cd python_ml_service
   ```

2. **Run the setup script:**
   ```bash
   setup-craft-classifier.bat
   ```
   
   This will:
   - Create a Python virtual environment
   - Install TensorFlow, Flask, and dependencies
   - Download MobileNetV2 model (~14MB)
   - Test the classifier setup

3. **Start the craft classifier service:**
   ```bash
   start-craft-classifier.bat
   ```
   
   The service will run on `http://localhost:5001`

### Step 2: Configure Environment Variables

1. **Update your `.env` file** (in `backend/` directory):
   ```env
   # Existing variables...
   LOCAL_CLASSIFIER_URL=http://localhost:5000
   CRAFT_CLASSIFIER_URL=http://localhost:5001
   ```

2. **Ensure both AI services are running:**
   - Local classifier (existing): `http://localhost:5000`
   - Craft classifier (new): `http://localhost:5001`

### Step 3: Database Setup

The system will automatically create required tables on first use:

- `craft_image_validation` - Stores craft-specific validation results
- `admin_actions_log` - Logs admin decisions for audit trail

**Manual setup (optional):**
```sql
-- Run this if you want to create tables manually
CREATE TABLE IF NOT EXISTS `craft_image_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `predicted_category` varchar(50) DEFAULT NULL,
  `prediction_confidence` decimal(5,4) DEFAULT 0.0000,
  `category_matches` tinyint(1) DEFAULT 0,
  `ai_generated_detected` tinyint(1) DEFAULT 0,
  `ai_generator` varchar(50) DEFAULT NULL,
  `ai_confidence` enum('unknown', 'suspicious', 'high') DEFAULT 'unknown',
  `validation_status` enum('approved', 'flagged', 'rejected') DEFAULT 'approved',
  `rejection_reason` text DEFAULT NULL,
  `flag_reason` text DEFAULT NULL,
  `all_predictions` json DEFAULT NULL,
  `ai_evidence` json DEFAULT NULL,
  `metadata_analysis` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image_validation` (`image_id`, `image_type`),
  KEY `idx_validation_status` (`validation_status`),
  KEY `idx_predicted_category` (`predicted_category`),
  KEY `idx_ai_generated` (`ai_generated_detected`),
  KEY `idx_user_tutorial` (`user_id`, `tutorial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 4: Update Practice Upload Endpoint

**Replace the existing practice upload endpoint** with the enhanced version:

1. **Backup your current file:**
   ```bash
   cp backend/api/pro/practice-upload-v2.php backend/api/pro/practice-upload-v2-backup.php
   ```

2. **Use the new enhanced endpoint:**
   - The new file is: `backend/api/pro/practice-upload-craft-validation.php`
   - Update your frontend to call this endpoint instead
   - Or rename it to replace the existing endpoint

### Step 5: Set Up Admin Dashboard

1. **Access the new admin dashboard:**
   ```
   http://your-domain/frontend/admin/craft-validation-dashboard.html
   ```

2. **Add to your existing admin navigation** (optional):
   ```html
   <a href="craft-validation-dashboard.html">🎨 Craft Validation</a>
   ```

## 🎯 Supported Craft Categories

The system classifies images into these 7 categories:

1. **Candle Making** - Candles, wax work, wick crafting
2. **Clay Modeling** - Pottery, ceramics, sculpture
3. **Gift Making** - Gift boxes, wrapping, packaging
4. **Hand Embroidery** - Needlework, stitching, fabric art
5. **Jewelry Making** - Beadwork, wire work, accessories
6. **Mehandi Art** - Henna designs, body art patterns
7. **Resin Art** - Epoxy work, casting, transparent crafts

## 🔧 Configuration Options

### Confidence Thresholds

You can adjust validation sensitivity in `CraftImageValidationService.php`:

```php
// Confidence thresholds
private const HIGH_CONFIDENCE_THRESHOLD = 0.80;  // 80% confidence
private const LOW_CONFIDENCE_THRESHOLD = 0.40;   // 40% confidence
private const CATEGORY_MISMATCH_THRESHOLD = 0.70; // 70% for mismatch
```

### AI Generator Detection

The system detects these AI generators in image metadata:

- Stable Diffusion
- Midjourney
- DALL-E
- Adobe Firefly
- Leonardo.ai
- Runway ML
- And more...

## 📊 Validation Rules

### Automatic Rejection
- **AI-generated images** with high confidence
- **Clearly unrelated content** (selfies, nature, animals)
- **High-confidence category mismatches**

### Flagged for Review
- **Suspicious AI patterns** detected
- **Medium confidence category mismatches**
- **Low confidence classifications**
- **Processing errors**

### Automatic Approval
- **Category matches** selected tutorial
- **High confidence craft classification**
- **No AI generation detected**
- **Passes similarity checks**

## 🎛️ Admin Dashboard Features

### Statistics Overview
- Pending submissions count
- Flagged images count
- Rejected images count
- Daily approval metrics

### Detailed Image Analysis
- **Craft classification results** with confidence scores
- **Category match analysis** (predicted vs selected)
- **AI generation detection** with evidence
- **Similarity analysis** (existing system)
- **Metadata inspection** for technical details

### Admin Actions
- **Approve** submissions with optional notes
- **Reject** submissions with required feedback
- **View detailed validation** reasoning
- **Audit trail** of all decisions

## 🔍 Testing the System

### 1. Test Craft Classifier Service

```bash
# Activate virtual environment
cd python_ml_service
craft_venv\Scripts\activate.bat

# Test with an image
python craft_classifier.py path\to\craft\image.jpg
```

Expected output:
```json
{
  "success": true,
  "predicted_category": "hand_embroidery",
  "confidence": 0.85,
  "is_craft_related": true,
  "explanation": "High confidence prediction: Hand Embroidery; Classification using fine-tuned craft model"
}
```

### 2. Test API Endpoints

**Health check:**
```bash
curl http://localhost:5001/health
```

**Classify image:**
```bash
curl -X POST http://localhost:5001/classify-craft \
  -H "Content-Type: application/json" \
  -d '{"image_path": "/path/to/image.jpg"}'
```

### 3. Test Practice Upload Flow

1. Upload a practice image through your frontend
2. Check the response for validation results
3. Verify admin dashboard shows the submission
4. Test approval/rejection workflow

## 🚨 Troubleshooting

### Common Issues

**1. Craft Classifier Service Won't Start**
```bash
# Check Python installation
python --version

# Reinstall dependencies
cd python_ml_service
rmdir /s craft_venv
setup-craft-classifier.bat
```

**2. "GD Extension Not Available" Error**
- Enable GD extension in `php.ini`
- Uncomment: `extension=gd`
- Restart Apache/Nginx

**3. Database Connection Errors**
- Verify database credentials in `.env`
- Ensure MySQL/MariaDB is running
- Check table creation permissions

**4. Images Not Loading in Admin Dashboard**
- Verify upload directory permissions
- Check image file paths in database
- Ensure web server can serve uploaded files

### Performance Optimization

**1. Model Loading Time**
- First classification takes 2-3 seconds (model loading)
- Subsequent classifications are faster (~200ms)
- Keep the service running for best performance

**2. Memory Usage**
- Craft classifier: ~500MB RAM
- Local classifier: ~500MB RAM
- Total AI services: ~1GB RAM

**3. Concurrent Requests**
- Services handle multiple requests
- Consider load balancing for high traffic
- Monitor response times under load

## 🔒 Security Considerations

### Input Validation
- File type restrictions enforced
- File size limits (5MB default)
- Path traversal protection
- SQL injection prevention

### AI Service Security
- Services run on localhost only
- No external API calls for classification
- All processing is local and private
- Input sanitization on all endpoints

### Admin Access
- Implement proper authentication for admin dashboard
- Log all admin actions for audit trail
- Restrict access to validation endpoints

## 📈 Monitoring & Maintenance

### Log Files
- Check `error_log` for PHP errors
- Monitor AI service console output
- Review admin action logs

### Performance Metrics
- Classification response times
- Validation accuracy rates
- Admin review queue length
- User satisfaction scores

### Regular Maintenance
- Update AI models periodically
- Clean up old validation data
- Monitor disk space usage
- Review and adjust thresholds

## 🎓 Training Custom Models

For better accuracy, you can train a custom MobileNet model:

1. **Collect training data** for each craft category
2. **Fine-tune MobileNetV2** using TensorFlow
3. **Save model** as `backend/ai/model/craft_image_classifier.keras`
4. **Restart craft classifier service**

The system will automatically use the fine-tuned model if available.

## 📞 Support

If you encounter issues:

1. **Check the logs** for error messages
2. **Verify all services** are running
3. **Test with sample images** to isolate issues
4. **Review configuration** settings
5. **Consult troubleshooting** section above

## 🎉 Success Indicators

Your system is working correctly when:

- ✅ Both AI services respond to health checks
- ✅ Practice uploads show validation results
- ✅ Admin dashboard displays submissions
- ✅ Approval/rejection workflow functions
- ✅ Learning progress updates correctly
- ✅ No errors in server logs

The craft validation system is now ready to enhance your e-learning platform with intelligent image validation while maintaining a smooth user experience!