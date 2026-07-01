# CampusEats Deployment Guide

## Services

- Frontend: Vercel
- Backend API: Render Web Service
- Database: Aiven MySQL
- Mobile: Capacitor Android APK built from the Vite production build

## Backend On Render

Set these environment variables in Render, not in a committed file:

```env
DB_HOST=your-aiven-host
DB_NAME=defaultdb
DB_USER=your-aiven-user
DB_PASS=your-aiven-password
DB_PORT=23347
DB_SSL=true
JWT_SECRET=long-random-secret
STRIPE_SECRET_KEY=sk_test_...
STRIPE_CURRENCY=myr
GEMINI_API_KEY=optional
GEMINI_MODEL=gemini-1.5-flash
```

Recommended Render settings:

- Root directory: `backend`
- Build command: `composer install --no-dev --optimize-autoloader`
- Start command: `php -S 0.0.0.0:$PORT -t public`

Health check:

```text
https://campuseats-mswg.onrender.com/api/health
```

Expected response:

```json
{ "status": "ok", "message": "CampusEats API is running" }
```

## Database On Aiven

Import schema and seed into the deployed MySQL database:

```powershell
mysql --host=YOUR_AIVEN_HOST --port=YOUR_AIVEN_PORT --user=YOUR_AIVEN_USER --password --ssl-mode=REQUIRED defaultdb < backend/database/schema.sql
mysql --host=YOUR_AIVEN_HOST --port=YOUR_AIVEN_PORT --user=YOUR_AIVEN_USER --password --ssl-mode=REQUIRED defaultdb < backend/database/seed.sql
```

Demo accounts after seeding:

- `student@test.com` / `123456`
- `vendor@test.com` / `123456`
- `admin@test.com` / `123456`

Important: if Aiven is powered off or rebuilding, `/api/health` can still work but login and vendor/menu endpoints will fail with 500 because they need the database. Open the Aiven dashboard before the demo and confirm the service is running. A paid/basic plan prevents automatic power-offs caused by free-tier inactivity.

## Frontend On Vercel

Set these Vercel environment variables:

```env
VITE_API_URL=https://campuseats-mswg.onrender.com/api
VITE_STRIPE_PUBLIC_KEY=pk_test_...
VITE_ENABLE_SSE=false
```

Recommended Vercel settings:

- Root directory: `frontend`
- Build command: `npm run build`
- Output directory: `dist`

After changing env variables, redeploy the Vercel project.

## Local Development

Frontend development:

```powershell
cd frontend
npm install
npm run dev
```

Backend development:

```powershell
cd backend
composer install
php -S localhost:8000 -t public
```

Use `frontend/.env.development` locally:

```env
VITE_API_URL=http://localhost:8000/api
VITE_STRIPE_PUBLIC_KEY=pk_test_...
VITE_ENABLE_SSE=false
```

Use `backend/.env` locally with local or Aiven database credentials. Never commit backend secrets.

## Android APK

Build and sync:

```powershell
cd frontend
npm run build
npx cap sync android
npx cap open android
```

In Android Studio:

```text
Build > Generate Signed App Bundle / APK > APK
```

Use the generated app APK, not `app-debug-androidTest.apk`.

## Smoke Test Before Demo

1. Open backend health URL.
2. Login as `student@test.com`.
3. Open vendors and confirm multiple vendors appear.
4. Add an item to cart.
5. Pay with Stripe test card `4242 4242 4242 4242`.
6. Confirm order status page shows `payment_method=stripe` and `payment_status=paid`.
7. Login as `vendor@test.com`.
8. Open Dashboard and update order status.
9. Open Vendor Menu and add/edit/delete an item.
10. Login as `admin@test.com`.
11. Open Vendor Applications and Vendors.
12. Confirm admin can deactivate/remove vendors.

