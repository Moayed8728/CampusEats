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
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_CURRENCY=myr
```

## Gemini Setup

The AI Food Assistant calls Gemini from the backend only. The frontend never receives or stores the Gemini API key.

Add these variables to `backend/.env`:

```env
GEMINI_API_KEY=your_gemini_api_key
GEMINI_MODEL=gemini-3-flash-preview
```

If `GEMINI_MODEL` is omitted, the backend defaults to `gemini-1.5-flash`. If Gemini is unavailable, missing, or returns invalid JSON, CampusEats automatically falls back to the existing rule-based parser and still returns recommendations.

## Stripe Sandbox Payments

CampusEats uses Stripe Sandbox payments before an order is created. The student sees a card form and a single `Pay` button. Orders are created only after Stripe confirms the card payment and the backend verifies the PaymentIntent.

Backend variables in `backend/.env`:

```env
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_CURRENCY=myr
```

Frontend variables in `frontend/.env` or your Vercel environment:

```env
VITE_STRIPE_PUBLIC_KEY=your_stripe_publishable_key
```

Do not commit `.env` files. The Stripe secret key is used only by the backend. The frontend only receives the publishable key and a PaymentIntent client secret.

Test card:

```text
4242 4242 4242 4242
Any future expiry
Any CVC
Any ZIP/postal code
```

Decline test card:

```text
4000 0000 0000 0002
Any future expiry
Any CVC
```

Payment flow:

1. Student adds items to cart.
2. Student chooses a pickup time.
3. Student enters a Stripe test card and clicks `Pay`.
4. Stripe confirms the payment in the browser.
5. The backend verifies the PaymentIntent status, amount, and currency.
6. The frontend creates the order with `payment_method=stripe` and `payment_status=paid`.
7. The order status page shows payment method, status, and reference.

For failed Stripe payments, CampusEats shows the Stripe error and does not create the order.

If `VITE_STRIPE_PUBLIC_KEY` is missing, the card form is hidden. If `STRIPE_SECRET_KEY` is missing or Stripe fails, the backend returns an error for Stripe intent creation.

## Real-Time Updates

CampusEats uses simple polling for order updates:

- Vendor dashboard refreshes `GET /api/vendor/orders` every 5 seconds.
- Student order status refreshes `GET /api/orders/{id}` every 5 seconds.

Polling is intentionally kept simple for local demos and deployment stability. This is the only order update method in the active app.

To test polling in the browser:

1. Login as `vendor@test.com` and open `/vendor/dashboard`.
2. Open DevTools > Network and filter by `vendor/orders`.
3. Confirm the request repeats roughly every 5 seconds.
4. Login as `student@test.com` and open an order status page.
5. Update that order from the vendor dashboard and confirm the student page updates on the next poll.

## Loyalty Points

Students earn loyalty points when an order is marked `collected` by the vendor.

- RM1 = 1 point
- Points are awarded once per order
- Students can view their balance and point history at `/rewards`
- Existing collected orders are backfilled when rewards are viewed
- Admins can review all student balances and recent reward activity at `/admin/rewards`

## Vendor Applications

Public registration always creates student accounts. Admin and vendor accounts cannot be created from the public registration form.

Students can request vendor access from `/vendor-application`. Admins review requests from `/admin/vendor-applications`:

- Approving an application creates the vendor profile and changes the student account role to `vendor`.
- Rejecting an application keeps the student account unchanged.
- Seeded demo accounts such as `vendor@test.com` and `admin@test.com` remain available for demos.

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
