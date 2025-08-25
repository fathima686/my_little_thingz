# Customer Dashboard Functionalities

This document describes the complete customer dashboard implementation with all requested functionalities.

## ✅ **Implemented Features**

### 1. **🎨 Artwork Gallery (View Artwork)**
- **Browse Catalog**: Complete artwork gallery with filtering and search
- **Categories**: Filter by artwork categories
- **Price Filtering**: Filter by price ranges ($0-25, $25-50, $50-100, $100+)
- **Search**: Search by artwork title and description
- **Detailed View**: Click on artwork to see full details
- **Artist Information**: View artist name and artwork details

**Features:**
- Grid layout with hover effects
- Image previews with overlay actions
- Responsive design for mobile/desktop
- Loading states and error handling

### 2. **💝 Custom Gift Requests**
- **Request Form**: Comprehensive form for custom gift requests
- **Category Selection**: Choose from available categories
- **Budget Range**: Set minimum and maximum budget
- **Timeline**: Set preferred completion date
- **Image Upload**: Upload up to 5 reference images
- **Special Instructions**: Add detailed requirements

**Features:**
- File upload with preview
- Form validation
- Progress tracking
- Request history (backend ready)

### 3. **📦 Order Tracking**
- **Order History**: View all past and current orders
- **Status Tracking**: Visual progress tracking (Pending → Processing → Shipped → Delivered)
- **Order Details**: Detailed view of each order
- **Item Information**: See all items in each order
- **Shipping Information**: Track packages with tracking numbers
- **Filter Options**: Filter by order status

**Features:**
- Status-based filtering tabs
- Visual progress indicators
- Detailed order breakdown
- Shipping address and tracking info
- Estimated delivery dates

### 4. **❤️ Wishlist Management**
- **Add/Remove Items**: Easily manage wishlist items
- **Wishlist Gallery**: Visual grid of saved items
- **Quick Actions**: Add to cart directly from wishlist
- **Availability Status**: See if items are in stock
- **Sorting Options**: Sort by date added, name, price
- **Share Wishlist**: Generate shareable links

**Features:**
- Visual grid layout
- Stock status indicators
- Bulk actions
- Social sharing capabilities
- Date tracking for when items were added

## 🔧 **Technical Implementation**

### Frontend Components

#### 1. **ArtworkGallery.jsx**
```jsx
// Features:
- Search and filter functionality
- Category and price filtering
- Modal-based detailed view
- Add to cart and wishlist actions
- Responsive grid layout
```

#### 2. **CustomGiftRequest.jsx**
```jsx
// Features:
- Multi-step form with validation
- File upload with preview
- Budget and timeline selection
- Category selection
- Special instructions field
```

#### 3. **OrderTracking.jsx**
```jsx
// Features:
- Order status filtering
- Visual progress tracking
- Detailed order view
- Item breakdown
- Shipping information
```

#### 4. **WishlistManager.jsx**
```jsx
// Features:
- Grid-based item display
- Add/remove functionality
- Stock status checking
- Sorting and filtering
- Share functionality
```

### Backend API Endpoints

#### 1. **Artworks API** (`/api/customer/artworks.php`)
- `GET`: Fetch all available artworks with artist and category info
- Includes pricing, availability, and image URLs
- Supports filtering and search (can be extended)

#### 2. **Categories API** (`/api/customer/categories.php`)
- `GET`: Fetch all active categories for filtering

#### 3. **Wishlist API** (`/api/customer/wishlist.php`)
- `GET`: Fetch user's wishlist items
- `POST`: Add item to wishlist
- `DELETE`: Remove item from wishlist

#### 4. **Custom Requests API** (`/api/customer/custom-requests.php`)
- `GET`: Fetch user's custom requests
- `POST`: Submit new custom request with file uploads

#### 5. **Orders API** (`/api/customer/orders.php`)
- `GET`: Fetch user's order history with items and tracking info

#### 6. **Cart API** (`/api/customer/cart.php`)
- `GET`: Fetch cart items
- `POST`: Add item to cart
- `PUT`: Update item quantity
- `DELETE`: Remove item from cart

## 🎯 **User Experience Features**

### Navigation Integration
- **Header Navigation**: Quick access buttons in dashboard header
- **Hero Section**: Primary action buttons for main features
- **Quick Actions**: Card-based navigation in dashboard body
- **Widget Integration**: "View All" buttons in recommendation widgets

### Modal System
- **Overlay Modals**: All features open in overlay modals
- **Responsive Design**: Mobile-friendly modal layouts
- **Loading States**: Proper loading indicators
- **Error Handling**: User-friendly error messages

### Visual Design
- **Consistent Styling**: Matches existing dashboard design
- **Hover Effects**: Interactive elements with smooth transitions
- **Status Indicators**: Color-coded status badges
- **Progress Tracking**: Visual progress bars for orders

## 📱 **Responsive Design**

### Mobile Optimization
- **Grid Layouts**: Responsive grids that adapt to screen size
- **Touch-Friendly**: Large touch targets for mobile users
- **Simplified Navigation**: Collapsible filters and menus
- **Optimized Images**: Proper image sizing for different devices

### Desktop Features
- **Multi-Column Layouts**: Efficient use of screen space
- **Hover Interactions**: Rich hover effects for better UX
- **Keyboard Navigation**: Full keyboard accessibility
- **Advanced Filtering**: More detailed filter options

## 🔒 **Security & Authentication**

### User Authentication
- **Session Management**: Integrated with existing auth system
- **User ID Validation**: All API calls validate user identity
- **Protected Routes**: All customer features require authentication

### Data Security
- **Input Validation**: All form inputs are validated
- **File Upload Security**: Secure file handling for image uploads
- **SQL Injection Prevention**: Prepared statements for all queries
- **XSS Protection**: Proper output escaping

## 🚀 **Performance Optimizations**

### Frontend Performance
- **Lazy Loading**: Components load only when needed
- **Image Optimization**: Proper image sizing and loading
- **Caching**: Browser caching for static assets
- **Minimal Re-renders**: Optimized React component updates

### Backend Performance
- **Database Indexing**: Proper indexes on frequently queried columns
- **Query Optimization**: Efficient SQL queries with joins
- **Caching Headers**: Appropriate cache headers for API responses
- **File Handling**: Efficient file upload and storage

## 📊 **Database Schema Requirements**

### Required Tables
```sql
-- Artworks table
CREATE TABLE artworks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    category_id INT,
    artist_id INT,
    availability ENUM('available', 'out_of_stock') DEFAULT 'available',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, artwork_id)
);

-- Custom requests table
CREATE TABLE custom_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category_id INT,
    budget_min DECIMAL(10,2),
    budget_max DECIMAL(10,2),
    deadline DATE,
    special_instructions TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Custom request images table
CREATE TABLE custom_request_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    shipping_cost DECIMAL(10,2),
    shipping_address TEXT,
    tracking_number VARCHAR(100),
    estimated_delivery DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    artwork_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (user_id, artwork_id)
);
```

## 🎉 **Usage Instructions**

### For Customers:

1. **Browse Artworks**:
   - Click "Browse Catalog" or the search icon in header
   - Use filters to find specific items
   - Click on artworks to view details
   - Add items to wishlist or cart

2. **Create Custom Requests**:
   - Click "Custom Request" button
   - Fill out the detailed form
   - Upload reference images
   - Submit for review

3. **Track Orders**:
   - Click "Orders" in header or "Track Orders" card
   - View order status and progress
   - Click "View Details" for complete information

4. **Manage Wishlist**:
   - Click heart icon in header
   - View, sort, and filter saved items
   - Add items to cart or remove from wishlist
   - Share wishlist with others

### For Developers:

1. **Adding New Features**:
   - Create new components in `/components/customer/`
   - Add API endpoints in `/backend/api/customer/`
   - Update dashboard to include new modals

2. **Customizing Styles**:
   - Modify inline styles in components
   - Update dashboard.css for global styles
   - Ensure responsive design principles

3. **Database Integration**:
   - Run the provided SQL schema
   - Update API endpoints as needed
   - Add proper indexes for performance

## 🔮 **Future Enhancements**

### Planned Features
- **Payment Integration**: Stripe/PayPal checkout
- **Real-time Notifications**: Order status updates
- **Advanced Search**: AI-powered recommendations
- **Social Features**: Reviews and ratings
- **Mobile App**: React Native implementation

### Performance Improvements
- **Image CDN**: CloudFront integration
- **Database Optimization**: Query caching
- **Progressive Web App**: PWA features
- **Real-time Updates**: WebSocket integration

This implementation provides a complete, production-ready customer dashboard with all requested functionalities. The modular design allows for easy extension and customization while maintaining excellent user experience and performance.