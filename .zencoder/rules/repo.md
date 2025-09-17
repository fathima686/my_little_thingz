# Repository Info: my_little_thingz

## Overview
- Frontend: React + Vite (JavaScript), located in `frontend/`
- Backend: PHP (procedural with some includes), located in `backend/`
- Database: MariaDB/MySQL, schema and seeds in `backend/database/` and `backend/schema.sql`
- Payment: Razorpay integration via `backend/api/customer/razorpay-*.php`
- Auth: Custom, with Google OAuth support via `backend/google-login.php` and `auth_providers` table

## Important Paths
- **Frontend app**: `frontend/src/` (pages, components, contexts, styles, utils)
  - Entry: `frontend/src/main.jsx`
  - Router: `frontend/src/App.jsx`
  - Cart page: `frontend/src/pages/CartPage.jsx`
- **Backend API root**: `backend/api/`
  - Customer endpoints: `backend/api/customer/`
  - Admin endpoints: `backend/api/admin/`
- **Config**: `backend/config/`
  - `database.php`, `email.php`, `razorpay.php`
- **DB Scripts**: `backend/database/`
  - `customer_tables.sql`, `migrations_razorpay.sql`, `migrate_add_razorpay_columns.php`
- **Uploads**: `backend/uploads/` (artworks, profile-images, custom-requests, supplier-products)

## Local Dev
- Environment: XAMPP on Windows (Apache + PHP + MariaDB)
- Backend base URL (dev): `http://localhost/my_little_thingz/backend/api`
- Frontend dev server: Vite (port typically 5173) or served statically via Apache

### Frontend
1. `cd frontend`
2. `npm install`
3. `npm run dev`
4. Update `frontend/.env` if needed; API base in code: `API_BASE = 'http://localhost/my_little_thingz/backend/api'`

### Backend
- Place repo at: `c:/xampp/htdocs/my_little_thingz`
- Ensure DB `my_little_thingz` exists; import `backend/schema.sql` or `backend/database/*.sql`
- Configure DB in `backend/config/database.php`
- Composer dependencies already present (vendor checked in); run `composer install` if needed.

## Data Model Highlights
- `artworks`, `categories`, `cart`, `orders`, `order_items`
- `custom_requests` and `custom_request_images` for customization flow
- Razorpay columns in `orders`: `payment_method`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`

## Cart & Checkout Flow
- Cart page: `frontend/src/pages/CartPage.jsx`
  - Fetch cart: `backend/api/customer/cart.php`
  - Place order (COD/placeholder): `backend/api/customer/checkout.php`
  - Razorpay create + verify: `backend/api/customer/razorpay-create-order.php`, `razorpay-verify.php`
- Shipping address stored as a single string in `orders.shipping_address` (frontend composes normalized fields)

## Email
- Email sender classes in `backend/includes/`
- Test script: `backend/test_email.php`
- Logs: `email_log.txt`

## Known Conventions
- PHP expects `X-User-ID` and `Authorization: Bearer <token>` headers
- Some endpoints accept `?user_id=` in query for convenience (still use headers)

## Recent Changes
- CartPage updated to use normalized address fields (name, street, city, state, pincode, phone) and compose `shipping_address`.

## Tips
- If Razorpay flow fails, verify keys in `backend/config/razorpay.php`
- If DB migrations are missing, run `backend/database/migrate_add_razorpay_columns.php` once
- Ensure Apache serves the repo at `/my_little_thingz` and `mod_rewrite` as needed