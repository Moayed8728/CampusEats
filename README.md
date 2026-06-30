# CampusEats

CampusEats is a campus food ordering web app. Students can browse vendors, order food for pickup, track order status, receive notifications, use smart meal recommendations, and submit reviews. Vendors manage live orders and analytics. Admins can inspect users, vendors, and orders.

## Tech Stack

- Frontend: Vue 3, Vue Router, Pinia, Axios, Vite
- Backend: PHP Slim 4, PDO, JWT
- Database: MySQL

## Setup

1. Install frontend dependencies:

```powershell
cd frontend
npm install
```

2. Install backend dependencies:

```powershell
cd backend
composer install
```

3. Configure backend environment in `backend/.env`:

```env
DB_HOST=localhost
DB_NAME=campuseats
DB_USER=root
DB_PASS=root
DB_PORT=3306
JWT_SECRET=campuseats_secret_key
GEMINI_API_KEY=your_gemini_api_key
GEMINI_MODEL=gemini-3-flash-preview
```

## Gemini Setup

The AI Food Assistant calls Gemini from the backend only. The frontend never receives or stores the Gemini API key.

Add these variables to `backend/.env`:

```env
GEMINI_API_KEY=your_gemini_api_key
GEMINI_MODEL=gemini-3-flash-preview
```

If `GEMINI_MODEL` is omitted, the backend defaults to `gemini-1.5-flash`. If Gemini is unavailable, missing, or returns invalid JSON, CampusEats automatically falls back to the existing rule-based parser and still returns recommendations.

## Real-Time Updates

CampusEats uses Server-Sent Events for lightweight real-time updates:

- Vendor dashboard connects to `GET /api/vendor/orders/stream`
- Student order status connects to `GET /api/student/orders/{id}/stream`

The stream sends:

- `orders_update` for vendor order lists
- `order_status_update` for one student order

Browsers do not allow custom `Authorization` headers on `EventSource`, so the frontend passes the current JWT as a stream query parameter. Normal API calls still use the `Authorization: Bearer ...` header.

Polling remains as a fallback. If the SSE connection fails, the vendor dashboard and order status page keep refreshing every 5 seconds.

For local/demo stability, SSE is controlled by `frontend/.env`:

```env
VITE_ENABLE_SSE=true
```

- Production builds use SSE as the primary real-time connection. Polling starts only if the stream fails.
- In local development, `VITE_ENABLE_SSE=false` forces polling only.

If `VITE_ENABLE_SSE` is missing, the frontend treats SSE as enabled. Restart the Vite dev server or redeploy Vercel after changing this flag.

To test SSE in the browser:

1. Login as `vendor@test.com` and open `/vendor/dashboard`.
2. Open DevTools > Network and filter by `stream`.
3. Confirm there is one `/api/vendor/orders/stream?...` request with type `eventstream`.
4. In another session, place or update an order and watch the dashboard update.
5. Login as `student@test.com` and open an order status page.
6. Confirm there is one `/api/student/orders/{id}/stream?...` eventstream request.
7. Update that order from the vendor dashboard and confirm the student status changes live.

To test with curl, login first to get a JWT, then call:

```powershell
curl.exe -N "http://localhost:8000/api/vendor/orders/stream?token=YOUR_VENDOR_JWT"
curl.exe -N "http://localhost:8000/api/student/orders/ORDER_ID/stream?token=YOUR_STUDENT_JWT"
```

## Database Import

From the project root:

```powershell
php -r "`$pdo=new PDO('mysql:host=localhost;port=3306;charset=utf8mb4','root','root',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); `$pdo->exec(file_get_contents('backend/database/schema.sql')); `$pdo->exec(file_get_contents('backend/database/seed.sql')); echo 'imported', PHP_EOL;"
```

## Demo Accounts

- Student: `student@test.com` / `123456`
- Vendor: `vendor@test.com` / `123456`
- Admin: `admin@test.com` / `123456`

## Run Locally

Backend:

```powershell
cd backend
php -S localhost:8000 -t public
```

Frontend:

```powershell
cd frontend
npm run dev
```

Default URLs:

- Backend API: `http://localhost:8000/api`
- Frontend: `http://localhost:5173`

## Main Demo Flow

1. Login as `student@test.com`.
2. Browse vendors and open a menu.
3. Add an item to cart and place an order.
4. View order status and order history.
5. Use Recommendations and AI Assistant to add suggested meals to cart.
6. Login as `vendor@test.com`.
7. View dashboard, update order status, and confirm dashboard auto-refresh.
8. Login as `student@test.com` again and view notifications.
9. Open a collected order and submit a review.
10. Login as `admin@test.com`.
11. View admin dashboard, users, vendors, and orders.
