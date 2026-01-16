# Free Local Image Classification Service

## Overview

This service provides **free, local image classification** using pre-trained MobileNetV2 from TensorFlow. No paid APIs, no billing, completely free.

## Features

- ✅ **100% Free**: No API keys, no billing, no costs
- ✅ **Local Processing**: All processing happens on your server
- ✅ **Pre-trained Model**: MobileNetV2 trained on ImageNet (1000 classes)
- ✅ **Fast**: ~100-200ms per image on modern hardware
- ✅ **REST API**: Flask-based API for easy integration
- ✅ **CLI Support**: Can be used from command line
- ✅ **Batch Processing**: Classify multiple images at once

## Requirements

- Python 3.8 or higher
- ~500MB disk space (for TensorFlow and model)
- 2GB RAM minimum (4GB recommended)

## Quick Start

### 1. Setup (One-time)

```bash
cd python_ml_service
setup.bat
```

This will:
- Create Python virtual environment
- Install TensorFlow and dependencies
- Download MobileNetV2 model (~14MB)

**Note**: First setup takes 5-10 minutes to download dependencies.

### 2. Start Service

```bash
start-service.bat
```

Service will run on `http://localhost:5000`

### 3. Test

```bash
test-classifier.bat
```

## Usage

### REST API

#### Health Check
```bash
GET http://localhost:5000/health
```

Response:
```json
{
  "status": "healthy",
  "service": "image_classification",
  "model": "MobileNetV2",
  "version": "1.0.0"
}
```

#### Classify Image (JSON)
```bash
POST http://localhost:5000/classify
Content-Type: application/json

{
  "image_path": "/path/to/image.jpg"
}
```

Response:
```json
{
  "success": true,
  "ai_enabled": true,
  "possibly_unrelated": false,
  "labels": [
    {"name": "embroidery", "confidence": 0.85},
    {"name": "textile", "confidence": 0.72}
  ],
  "confidence": 0.85,
  "warning_message": null,
  "model": "MobileNetV2",
  "model_type": "local_free"
}
```

#### Classify Image (File Upload)
```bash
POST http://localhost:5000/classify
Content-Type: multipart/form-data

image: <file>
```

#### Batch Classification
```bash
POST http://localhost:5000/classify/batch
Content-Type: application/json

{
  "image_paths": [
    "/path/to/image1.jpg",
    "/path/to/image2.jpg"
  ]
}
```

### CLI Usage

```bash
# Activate virtual environment
venv\Scripts\activate.bat

# Classify image
python image_classifier.py path/to/image.jpg
```

Output:
```json
{
  "success": true,
  "ai_enabled": true,
  "possibly_unrelated": false,
  "labels": [...],
  "confidence": 0.85,
  "warning_message": null
}
```

## Integration with PHP

### Example PHP Code

```php
<?php
function classifyImage($imagePath) {
    $url = 'http://localhost:5000/classify';
    
    $data = json_encode(['image_path' => $imagePath]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return [
        'success' => false,
        'error_code' => 'SERVICE_ERROR',
        'error_message' => 'Classification service unavailable'
    ];
}

// Usage
$result = classifyImage('/path/to/uploaded/image.jpg');

if ($result['success'] && $result['possibly_unrelated']) {
    echo "Warning: " . $result['warning_message'];
}
?>
```

## Unrelated Content Detection

The classifier checks for these categories (confidence ≥ 80%):

- **People**: person, face, portrait, man, woman, child
- **Landscapes**: landscape, scenery, nature, outdoor, mountain
- **Animals**: animal, pet, dog, cat, bird, horse
- **Food**: food, meal, dish, restaurant, pizza
- **Vehicles**: car, automobile, truck, bus
- **Buildings**: building, architecture, city, house

If detected, sets `possibly_unrelated: true` and provides warning message.

## Performance

- **Startup**: ~2-3 seconds (model loading)
- **Classification**: ~100-200ms per image
- **Memory**: ~500MB (model in memory)
- **Disk**: ~500MB (TensorFlow + model)

## Troubleshooting

### Service won't start

1. Check Python version: `python --version` (need 3.8+)
2. Check virtual environment: `venv\Scripts\activate.bat`
3. Reinstall dependencies: `pip install -r requirements.txt`

### Classification fails

1. Check image file exists
2. Check image format (JPEG, PNG supported)
3. Check image not corrupted
4. Check service is running: `http://localhost:5000/health`

### Slow performance

1. First classification is slower (model loading)
2. Subsequent classifications are faster
3. Consider upgrading hardware (CPU/RAM)

## Model Information

- **Model**: MobileNetV2
- **Training**: ImageNet (1.4M images, 1000 classes)
- **Size**: ~14MB
- **Input**: 224x224 RGB images
- **Output**: 1000 class probabilities
- **License**: Apache 2.0 (free for commercial use)

## Advantages Over Google Vision API

| Feature | MobileNetV2 (Local) | Google Vision API |
|---------|---------------------|-------------------|
| Cost | Free | Requires billing |
| Privacy | Local processing | Sends to Google |
| Speed | 100-200ms | 500-2000ms |
| Offline | Works offline | Requires internet |
| Limits | None | Quota limits |
| Setup | One-time install | API key + billing |

## Security

- ✅ All processing is local (no data sent externally)
- ✅ No API keys required
- ✅ No external dependencies at runtime
- ✅ Input validation on all endpoints
- ✅ File size limits (10MB max)

## Maintenance

### Update Dependencies

```bash
venv\Scripts\activate.bat
pip install --upgrade tensorflow flask pillow numpy
```

### Check Service Status

```bash
curl http://localhost:5000/health
```

### View Logs

Service logs are printed to console. Redirect to file:

```bash
python flask_api.py > service.log 2>&1
```

## Production Deployment

### Run as Background Service

Use a process manager like:
- **Windows**: NSSM (Non-Sucking Service Manager)
- **Linux**: systemd or supervisor

### Example systemd service (Linux)

```ini
[Unit]
Description=Image Classification Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/python_ml_service
Environment="PATH=/path/to/python_ml_service/venv/bin"
ExecStart=/path/to/python_ml_service/venv/bin/python flask_api.py
Restart=always

[Install]
WantedBy=multi-user.target
```

### Performance Tuning

For production, consider:
- Use Gunicorn instead of Flask dev server
- Enable TensorFlow optimizations
- Use GPU if available (requires tensorflow-gpu)
- Cache predictions for identical images

## Support

For issues:
1. Check service is running: `http://localhost:5000/health`
2. Check logs for errors
3. Verify Python version and dependencies
4. Test with simple image first

## License

- Service code: MIT License
- TensorFlow: Apache 2.0
- MobileNetV2: Apache 2.0

All components are free for commercial use.
