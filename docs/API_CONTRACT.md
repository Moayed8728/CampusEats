# CampusEats API Contract

Base URL:

- Local: `http://localhost:8000/api`
- Production: `https://campuseats-mswg.onrender.com/api`

All protected routes require:

```http
Authorization: Bearer JWT_TOKEN
Content-Type: application/json
```

## Auth

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/auth/register` | Public | Register a student account only |
| POST | `/auth/login` | Public | Login and receive JWT |
| GET | `/auth/me` | Any user | Return current user |

Register request:

```json
{ "name": "Student Name", "email": "student@example.com", "password": "123456" }
```

Login response:

```json
{
  "token": "jwt",
  "user": { "id": "uuid", "name": "Student Demo", "email": "student@test.com", "role": "student" }
}
```

## Student Marketplace

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/vendors` | Public | List active vendors |
| GET | `/vendors/{id}/menu` | Public | List active menu items for one vendor |
| GET | `/recommendations` | Student | Rule-based recommendations |
| POST | `/ai/query` | Student | Gemini preference parsing with rule fallback |

AI request:

```json
{ "query": "I want halal spicy rice under RM10" }
```

## Orders And Payments

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/payments/create-intent` | Student | Create Stripe PaymentIntent |
| POST | `/orders` | Student | Create order after payment succeeds |
| GET | `/orders/{id}` | Owner, vendor owner, admin | View one order |
| GET | `/student/orders` | Student | View order history |
| PATCH | `/orders/{id}/status` | Vendor owner | Move order status forward |

Create intent request:

```json
{ "amount": 1050 }
```

Create order request:

```json
{
  "vendor_id": "uuid",
  "pickup_at": "2026-07-01 12:30:00",
  "items": [{ "menu_item_id": "uuid", "qty": 2 }],
  "payment_status": "paid",
  "payment_reference": "pi_...",
  "payment_method": "stripe"
}
```

Valid status transitions:

```text
placed -> preparing -> ready -> collected
```

## Vendor

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/vendor/orders` | Vendor | List own vendor orders |
| GET | `/vendor/analytics` | Vendor | Today's analytics |
| GET | `/vendor/menu` | Vendor | Read own complete menu catalog |
| POST | `/vendor/menu-items` | Vendor | Create menu item |
| PATCH | `/vendor/menu-items/{id}` | Vendor owner | Update menu item |
| DELETE | `/vendor/menu-items/{id}` | Vendor owner | Delete or hide menu item |

Menu item request:

```json
{
  "name": "Chicken Rice",
  "description": "Steamed chicken rice with soup.",
  "price": 8.5,
  "category": "Rice",
  "is_halal": true,
  "is_vegetarian": false,
  "in_stock": true
}
```

If a menu item has previous orders, delete marks it `in_stock=false` to preserve historical order records.

## Vendor Applications

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/vendor-applications/me` | Student/vendor | View current user's latest application |
| POST | `/vendor-applications` | Student | Submit vendor application |
| GET | `/admin/vendor-applications` | Admin | Review all applications |
| PATCH | `/admin/vendor-applications/{id}/approve` | Admin | Create vendor and promote user |
| PATCH | `/admin/vendor-applications/{id}/reject` | Admin | Reject application |

Application request:

```json
{
  "vendor_name": "Campus Sandwich Bar",
  "description": "Fresh sandwiches and drinks for pickup between classes.",
  "location": "Block B Lobby",
  "opening_hours": "Mon-Fri 9:00 AM - 5:00 PM"
}
```

## Reviews, Notifications, Rewards

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/vendors/{id}/reviews` | Student | Read vendor reviews |
| POST | `/reviews` | Student | Create review |
| GET | `/notifications` | Any user | List notifications |
| PATCH | `/notifications/{id}/read` | Owner | Mark notification read |
| GET | `/rewards` | Student | Loyalty balance and history |
| GET | `/admin/rewards` | Admin | Reward balances and transactions |

## Admin

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/admin/summary` | Admin | Dashboard metrics |
| GET | `/admin/users` | Admin | Read users |
| GET | `/admin/vendors` | Admin | Read vendors |
| PATCH | `/admin/vendors/{id}/toggle-active` | Admin | Activate/deactivate vendor |
| DELETE | `/admin/vendors/{id}` | Admin | Remove vendor or deactivate if it has order history |
| GET | `/admin/orders` | Admin | Read all orders |

Standard error response:

```json
{ "error": "Helpful error message" }
```

