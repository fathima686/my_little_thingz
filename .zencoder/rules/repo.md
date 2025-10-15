# Repository Guidelines

## Project Overview
- **Name**: My Little Thingz
- **Purpose**: Full-stack application for managing personalized gift selling platform.
- **Backend Stack**: PHP (custom MVC-style structure), Composer dependencies.
- **Frontend Stack**: React (Vite), React Router, Context API, Tailwind-like custom CSS.

## Key Paths
1. **Frontend**: `frontend/`
   - Entry: `frontend/src/main.jsx`
   - Pages: `frontend/src/pages/`
   - Components: `frontend/src/components/`
2. **Backend**: `backend/`
   - APIs: `backend/api/`
   - Config: `backend/config/`
   - Database schema & migrations: `backend/database/`

## Environment
- **Local Base URL**: `http://localhost/my_little_thingz`
- **Frontend Dev Server**: Vite default (check `package.json` scripts).
- **Backend Endpoints**: PHP scripts under `backend/api`. Ensure Apache + PHP are configured via XAMPP.

## Data Notes
- Offers stored via migrations in `backend/database/migrations_offers.sql` & `...offers_promos.sql`.
- Uploaded assets stored in `backend/uploads/` split by category.

## Common Tasks
1. **Install Frontend Dependencies**: `npm install` inside `frontend/`.
2. **Run Frontend Dev Server**: `npm run dev` (uses Vite).
3. **Composer Install**: `composer install` inside `backend/` when dependencies change.
4. **Database Setup**: Import `.sql` files from `backend/database/` into MySQL.

## Coding Standards
- **Frontend**: Prefer functional components with hooks, keep styles in `src/styles` or component-level CSS modules.
- **Backend**: Organize code by controllers/models; follow existing naming conventions and sanitized inputs.

## Testing
- Manual testing via browser/Thunder Client. No automated tests currently configured.

## Special Notes
- Offer logic likely tied to `backend/api/customer/offers-promos.php` & admin endpoints under `backend/api/admin/`.
- Ensure cross-origin requests match local hostnames.

---
Keep this file updated with any important workflow changes.