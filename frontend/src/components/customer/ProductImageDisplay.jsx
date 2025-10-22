import React, { useState, useEffect } from 'react';
import { LuImage, LuAlertCircle, LuLoader2 } from 'react-icons/lu';
import '../../styles/product-image-display.css';

const ProductImageDisplay = ({
  artworkId = null,
  imageUrl = null,
  title = "Product",
  price = 0,
  category = "",
  description = "",
  showDetails = true,
  showPricing = true,
  className = ""
}) => {
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageError, setImageError] = useState(false);
  const [artwork, setArtwork] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (artworkId && !imageUrl) {
      fetchArtworkDetails();
    } else if (imageUrl) {
      setArtwork({
        image_url: imageUrl,
        title: title,
        price: price,
        category_name: category,
        description: description
      });
    }
  }, [artworkId, imageUrl, title, price, category, description]);

  const fetchArtworkDetails = async () => {
    try {
      setLoading(true);
      const response = await fetch(`http://localhost/my_little_thingz/backend/api/customer/artwork_details.php?id=${artworkId}`);
      const data = await response.json();
      
      if (data.status === 'success' && data.artwork) {
        setArtwork(data.artwork);
      }
    } catch (err) {
      console.error('Error fetching artwork details:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleImageLoad = () => {
    setImageLoaded(true);
    setImageError(false);
  };

  const handleImageError = () => {
    setImageError(true);
    setImageLoaded(false);
  };

  const getPlaceholderImage = () => {
    return 'data:image/svg+xml;base64,' + btoa(`
      <svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#f3f4f6"/>
        <g transform="translate(150, 150)">
          <circle cx="0" cy="0" r="40" fill="#d1d5db"/>
          <path d="M-20,-20 L20,20 M20,-20 L-20,20" stroke="#9ca3af" stroke-width="3" stroke-linecap="round"/>
        </g>
        <text x="150" y="200" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#6b7280">No Image Available</text>
      </svg>
    `);
  };

  if (loading) {
    return (
      <div className={`product-image-container ${className}`}>
        <div className="image-loading">
          <LuLoader2 className="loading-spinner" />
          <p>Loading product image...</p>
        </div>
      </div>
    );
  }

  if (!artwork) {
    return (
      <div className={`product-image-container ${className}`}>
        <div className="image-error">
          <LuAlertCircle className="error-icon" />
          <p>Product not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className={`product-image-container ${className}`}>
      <div className="product-image-wrapper">
        <div className="image-container">
          {!imageLoaded && !imageError && (
            <div className="image-loading-overlay">
              <LuLoader2 className="loading-spinner" />
              <span>Loading image...</span>
            </div>
          )}
          
          <img
            src={imageError ? getPlaceholderImage() : artwork.image_url}
            alt={artwork.title}
            className={`product-image ${imageLoaded ? 'loaded' : ''} ${imageError ? 'error' : ''}`}
            onLoad={handleImageLoad}
            onError={handleImageError}
          />
          
          {imageError && (
            <div className="image-error-overlay">
              <LuImage className="error-icon" />
              <span>Image not available</span>
            </div>
          )}

          {artwork.has_offer && (
            <div className="offer-badge">
              {artwork.offer_percent ? `${artwork.offer_percent}% OFF` : 'OFFER'}
            </div>
          )}
        </div>

        {showDetails && (
          <div className="product-details">
            <h3 className="product-title">{artwork.title}</h3>
            {artwork.description && (
              <p className="product-description">{artwork.description}</p>
            )}
            
            {showPricing && (
              <div className="product-pricing">
                {artwork.effective_price && artwork.effective_price < artwork.price ? (
                  <div className="price-container">
                    <span className="original-price">₹{artwork.price}</span>
                    <span className="offer-price">₹{artwork.effective_price}</span>
                  </div>
                ) : (
                  <span className="price">₹{artwork.price}</span>
                )}
                {artwork.category_name && (
                  <span className="category">• {artwork.category_name}</span>
                )}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default ProductImageDisplay;








