<?php

use App\Database\Database;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\GeminiService;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\StripeClient;

$fetchOrder = static function (PDO $db, string $orderId): ?array {
    $statement = $db->prepare(
        'SELECT o.id, o.customer_id, o.vendor_id, o.status, o.pickup_at, o.total,
                o.payment_status, o.payment_reference, o.payment_method,
                v.name AS vendor_name, u.name AS customer_name
         FROM orders o
         JOIN vendors v ON v.id = o.vendor_id
         JOIN users u ON u.id = o.customer_id
         WHERE o.id = ?'
    );
    $statement->execute([$orderId]);
    $order = $statement->fetch();

    if (!$order) {
        return null;
    }

    $items = $db->prepare(
        'SELECT m.name, oi.qty, oi.unit_price
         FROM order_items oi
         JOIN menu_items m ON m.id = oi.menu_item_id
         WHERE oi.order_id = ?
         ORDER BY m.name'
    );
    $items->execute([$orderId]);

    $order['total'] = (float) $order['total'];
    $order['items'] = array_map(static function (array $item): array {
        $item['qty'] = (int) $item['qty'];
        $item['unit_price'] = (float) $item['unit_price'];
        return $item;
    }, $items->fetchAll());

    return $order;
};

$findOwnedVendor = static function (PDO $db, string $userId): ?array {
    $statement = $db->prepare('SELECT id, name FROM vendors WHERE owner_id = ?');
    $statement->execute([$userId]);
    return $statement->fetch() ?: null;
};

$fetchVendorApplications = static function (PDO $db, ?string $applicationId = null): array {
    $sql = 'SELECT va.id, va.user_id, u.name AS applicant_name, u.email AS applicant_email,
                   va.vendor_name, va.description, va.location, va.opening_hours,
                   va.status, va.created_at
            FROM vendor_applications va
            JOIN users u ON u.id = va.user_id';
    $params = [];

    if ($applicationId !== null) {
        $sql .= ' WHERE va.id = ?';
        $params[] = $applicationId;
    }

    $sql .= ' ORDER BY va.created_at DESC';
    $statement = $db->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
};

$fetchVendorOrders = static function (PDO $db, string $vendorId) use ($fetchOrder): array {
    $statement = $db->prepare('SELECT id FROM orders WHERE vendor_id = ? ORDER BY created_at DESC');
    $statement->execute([$vendorId]);
    $orders = [];
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $orderId) {
        $order = $fetchOrder($db, $orderId);
        unset($order['customer_id'], $order['vendor_id'], $order['vendor_name']);
        $orders[] = $order;
    }

    return $orders;
};

$createNotification = static function (PDO $db, string $userId, string $message): void {
    $statement = $db->prepare(
        'INSERT INTO notifications (id, user_id, message, is_read) VALUES (?, ?, ?, 0)'
    );
    $statement->execute([uuid(), $userId, $message]);
};

$awardLoyaltyPoints = static function (PDO $db, array $order): int {
    $points = max(0, (int) floor((float) $order['total']));
    if ($points === 0) {
        return 0;
    }

    $statement = $db->prepare(
        'INSERT IGNORE INTO loyalty_transactions (id, student_id, order_id, points, description)
         VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        uuid(),
        $order['customer_id'],
        $order['id'],
        $points,
        sprintf('Earned from order #%s', strtoupper(substr((string) $order['id'], 0, 8))),
    ]);

    return $statement->rowCount() > 0 ? $points : 0;
};

$syncLoyaltyPoints = static function (PDO $db, ?string $studentId = null): int {
    $sql = "INSERT IGNORE INTO loyalty_transactions (id, student_id, order_id, points, description, created_at)
            SELECT UUID(), o.customer_id, o.id, FLOOR(o.total),
                   CONCAT('Earned from order #', UPPER(LEFT(o.id, 8))),
                   COALESCE(o.updated_at, o.created_at)
            FROM orders o
            JOIN users u ON u.id = o.customer_id AND u.role = 'student'
            WHERE o.status = 'collected' AND o.total >= 1";
    $params = [];

    if ($studentId !== null) {
        $sql .= ' AND o.customer_id = ?';
        $params[] = $studentId;
    }

    $statement = $db->prepare($sql);
    $statement->execute($params);

    return $statement->rowCount();
};

$parseBoolean = static function ($value): ?bool {
    if ($value === null || $value === '') {
        return null;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
};

$normalizeCategory = static function (?string $category): ?string {
    $category = strtolower(trim((string) $category));
    $map = [
        'rice' => 'Rice',
        'nasi' => 'Rice',
        'noodle' => 'Noodles',
        'noodles' => 'Noodles',
        'mee' => 'Noodles',
        'drink' => 'Drinks',
        'drinks' => 'Drinks',
        'milo' => 'Drinks',
        'tea' => 'Drinks',
        'snack' => 'Snacks',
        'snacks' => 'Snacks',
        'wrap' => 'Snacks',
    ];

    return $map[$category] ?? ($category === '' ? null : ucfirst($category));
};

$findRecommendations = static function (PDO $db, array $filters): array {
    $conditions = ['v.is_active = 1', 'm.in_stock = 1'];
    $whereParams = [];
    $selectParams = [];

    if (($filters['budget'] ?? null) !== null) {
        $conditions[] = 'm.price <= ?';
        $whereParams[] = (float) $filters['budget'];
    }
    if (($filters['category'] ?? null) !== null) {
        $conditions[] = 'LOWER(m.category) = LOWER(?)';
        $whereParams[] = (string) $filters['category'];
    }
    if (($filters['halal'] ?? null) === true) {
        $conditions[] = 'm.is_halal = 1';
    }
    if (($filters['vegetarian'] ?? null) === true) {
        $conditions[] = 'm.is_vegetarian = 1';
    }
    $keywordTerms = [];
    if (($filters['keyword'] ?? null) !== null && trim((string) $filters['keyword']) !== '') {
        $keywordTerms = preg_split('/\s+/', strtolower(trim((string) $filters['keyword']))) ?: [];
        $keywordTerms = array_values(array_unique(array_filter($keywordTerms)));
        foreach ($keywordTerms as $term) {
            $keyword = '%' . $term . '%';
            $conditions[] = '(LOWER(m.name) LIKE ? OR LOWER(m.description) LIKE ?)';
            $whereParams[] = $keyword;
            $whereParams[] = $keyword;
            $selectParams[] = $keyword;
            $selectParams[] = $keyword;
        }
    }

    $preferenceScore = '0';
    if (($filters['halal'] ?? null) === true && ($filters['vegetarian'] ?? null) === true) {
        $preferenceScore = 'm.is_halal + m.is_vegetarian';
    } elseif (($filters['halal'] ?? null) === true) {
        $preferenceScore = 'm.is_halal';
    } elseif (($filters['vegetarian'] ?? null) === true) {
        $preferenceScore = 'm.is_vegetarian';
    }
    $relevanceScore = $keywordTerms
        ? implode(' + ', array_fill(0, count($keywordTerms), '(CASE WHEN LOWER(m.name) LIKE ? THEN 2 WHEN LOWER(m.description) LIKE ? THEN 1 ELSE 0 END)'))
        : '0';

    $sql = sprintf(
        "SELECT m.id, m.vendor_id, v.name AS vendor_name, m.name, m.description, m.price,
                m.category, NULL AS image_url, m.is_halal, m.is_vegetarian, m.in_stock,
                (%s) AS preference_score,
                %s AS relevance_score
         FROM menu_items m
         JOIN vendors v ON v.id = m.vendor_id
         WHERE %s
         ORDER BY relevance_score DESC, preference_score DESC, m.price ASC, m.name ASC
         LIMIT 20",
        $preferenceScore,
        $relevanceScore,
        implode(' AND ', $conditions)
    );

    $statement = $db->prepare($sql);
    $statement->execute([...$selectParams, ...$whereParams]);

    return array_map(static function (array $item): array {
        unset($item['preference_score'], $item['relevance_score']);
        $item['price'] = (float) $item['price'];
        $item['is_halal'] = (bool) $item['is_halal'];
        $item['is_vegetarian'] = (bool) $item['is_vegetarian'];
        $item['in_stock'] = (bool) $item['in_stock'];
        return $item;
    }, $statement->fetchAll());
};

$parseAiQuery = static function (string $query) use ($normalizeCategory): array {
    // TODO: Replace simple parser with Gemini API interpretation after the rule-based flow is stable.
    $lower = strtolower($query);
    $parsed = [
        'budget' => null,
        'category' => null,
        'halal' => str_contains($lower, 'halal'),
        'vegetarian' => (bool) preg_match('/\b(vegetarian|veggie|veg)\b/', $lower),
        'keyword' => null,
    ];

    if (preg_match('/(?:under|below|less than)\s*(?:rm)?\s*(\d+(?:\.\d{1,2})?)/i', $query, $matches)) {
        $parsed['budget'] = (float) $matches[1];
    } elseif (preg_match('/rm\s*(\d+(?:\.\d{1,2})?)/i', $query, $matches)) {
        $parsed['budget'] = (float) $matches[1];
    }

    foreach (['rice', 'nasi', 'noodles', 'noodle', 'drinks', 'drink', 'snacks', 'snack'] as $categoryWord) {
        if (str_contains($lower, $categoryWord)) {
            $parsed['category'] = $normalizeCategory($categoryWord);
            break;
        }
    }

    $keywordWords = [];
    foreach (['chicken', 'nasi', 'milo', 'tea', 'fried', 'spicy', 'coffee', 'burger', 'soup', 'rice', 'noodles', 'noodle'] as $word) {
        if (str_contains($lower, $word)) {
            $keywordWords[] = $word;
        }
    }
    $parsed['keyword'] = $keywordWords ? implode(' ', array_unique($keywordWords)) : trim($query);

    return $parsed;
};

$looksLikeWords = static function (string $value): bool {
    return (bool) preg_match('/[a-z0-9]/i', $value)
        && (bool) preg_match('/[a-z]{2,}/i', $value)
        && !preg_match('/^[^a-z0-9]+$/i', $value);
};

$isRealisticOpeningHours = static function (string $value): bool {
    $value = trim($value);
    if (strlen($value) < 7 || strlen($value) > 120) {
        return false;
    }
    if (!preg_match('/\b(am|pm)\b/i', $value) || !str_contains($value, '-')) {
        return false;
    }
    if (!preg_match('/(?:mon|tue|wed|thu|fri|sat|sun|monday|tuesday|wednesday|thursday|friday|saturday|sunday|daily|\d{1,2}(?::\d{2})?\s*(?:am|pm))/i', $value)) {
        return false;
    }

    preg_match_all('/(?<!\d)(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $value, $matches, PREG_SET_ORDER);
    if (count($matches) < 2) {
        return false;
    }
    foreach ($matches as $match) {
        $hour = (int) $match[1];
        $minute = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : 0;
        if ($hour < 1 || $hour > 12 || $minute < 0 || $minute > 59) {
            return false;
        }
    }

    return true;
};

$validateVendorApplicationData = static function (array $data) use ($looksLikeWords, $isRealisticOpeningHours): array {
    $clean = [
        'vendor_name' => trim((string) ($data['vendor_name'] ?? '')),
        'description' => trim((string) ($data['description'] ?? '')),
        'location' => trim((string) ($data['location'] ?? '')),
        'opening_hours' => trim((string) ($data['opening_hours'] ?? '')),
    ];
    $errors = [];

    if (strlen($clean['vendor_name']) < 3 || strlen($clean['vendor_name']) > 160 || !$looksLikeWords($clean['vendor_name'])) {
        $errors['vendor_name'] = 'Vendor name must be 3-160 characters and include readable text.';
    }
    if (strlen($clean['description']) < 20 || strlen($clean['description']) > 500) {
        $errors['description'] = 'Description must be 20-500 characters.';
    }
    if (strlen($clean['location']) < 3 || strlen($clean['location']) > 160 || !$looksLikeWords($clean['location'])) {
        $errors['location'] = 'Location must be 3-160 characters and include readable text.';
    }
    if (!$isRealisticOpeningHours($clean['opening_hours'])) {
        $errors['opening_hours'] = 'Opening hours must look like 9:00 AM - 5:00 PM or Mon-Fri 9:00 AM - 5:00 PM.';
    }

    if ($errors) {
        throw new InvalidArgumentException(json_encode($errors));
    }

    return $clean;
};

$normalizePaymentData = static function (array $data): array {
    $paymentStatus = strtolower(trim((string) ($data['payment_status'] ?? 'mock_paid')));
    $paymentMethod = strtolower(trim((string) ($data['payment_method'] ?? 'mock')));
    $paymentReference = trim((string) ($data['payment_reference'] ?? ''));

    if (!in_array($paymentStatus, ['paid', 'mock_paid'], true)) {
        throw new InvalidArgumentException('Payment must be completed before placing the order.');
    }
    if (!in_array($paymentMethod, ['stripe', 'mock'], true)) {
        throw new InvalidArgumentException('Unsupported payment method.');
    }
    if ($paymentStatus === 'paid' && $paymentMethod !== 'stripe') {
        throw new InvalidArgumentException('Stripe payments must use payment_method stripe.');
    }
    if ($paymentMethod === 'stripe' && ($paymentStatus !== 'paid' || !str_starts_with($paymentReference, 'pi_'))) {
        throw new InvalidArgumentException('Stripe payments require payment_status paid and a pi_ payment reference.');
    }
    if ($paymentStatus === 'mock_paid' && $paymentMethod !== 'mock') {
        throw new InvalidArgumentException('Mock payments must use payment_method mock.');
    }
    if ($paymentReference === '') {
        $paymentReference = $paymentMethod === 'mock' ? 'mock_' . bin2hex(random_bytes(8)) : null;
    }

    return [
        'payment_status' => $paymentStatus,
        'payment_method' => $paymentMethod,
        'payment_reference' => $paymentReference,
    ];
};

$verifyStripePayment = static function (array $payment, float $total): void {
    if ($payment['payment_method'] !== 'stripe') {
        return;
    }

    $secretKey = trim((string) ($_ENV['STRIPE_SECRET_KEY'] ?? ''));
    if ($secretKey === '') {
        throw new RuntimeException('Stripe is not configured.');
    }

    $stripe = new StripeClient($secretKey);
    $intent = $stripe->paymentIntents->retrieve($payment['payment_reference']);
    $expectedAmount = (int) round($total * 100);
    $expectedCurrency = strtolower(trim((string) ($_ENV['STRIPE_CURRENCY'] ?? 'myr'))) ?: 'myr';

    if ($intent->status !== 'succeeded') {
        throw new RuntimeException('Stripe payment has not succeeded.');
    }
    if ((int) $intent->amount !== $expectedAmount) {
        throw new RuntimeException('Stripe payment amount does not match the order total.');
    }
    if (strtolower((string) $intent->currency) !== $expectedCurrency) {
        throw new RuntimeException('Stripe payment currency does not match.');
    }
};

$app->get('/api/health', function (ServerRequestInterface $request, ResponseInterface $response) {
    return jsonResponse($response, [
        'status' => 'ok',
        'message' => 'CampusEats API is running',
    ]);
});

$app->post('/api/auth/register', function (ServerRequestInterface $request, ResponseInterface $response) {
    $data = (array) $request->getParsedBody();
    $missing = validateRequiredFields($data, ['name', 'email', 'password']);

    if ($missing) {
        return jsonResponse($response, ['error' => 'Missing required fields', 'fields' => $missing], 400);
    }

    $email = strtolower(trim((string) $data['email']));
    $role = 'student';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return jsonResponse($response, ['error' => 'A valid email is required'], 400);
    }
    if (strlen((string) $data['password']) < 6) {
        return jsonResponse($response, ['error' => 'Password must be at least 6 characters'], 400);
    }

    $db = Database::connect();
    $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        return jsonResponse($response, ['error' => 'Email is already registered'], 400);
    }

    $user = [
        'id' => uuid(),
        'name' => trim((string) $data['name']),
        'email' => $email,
        'role' => $role,
    ];
    $statement = $db->prepare(
        'INSERT INTO users (id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $user['id'],
        $user['name'],
        $user['email'],
        password_hash((string) $data['password'], PASSWORD_DEFAULT),
        $user['role'],
    ]);

    return jsonResponse($response, ['user' => $user], 201);
});

$app->post('/api/auth/login', function (ServerRequestInterface $request, ResponseInterface $response) {
    $data = (array) $request->getParsedBody();
    $missing = validateRequiredFields($data, ['email', 'password']);

    if ($missing) {
        return jsonResponse($response, ['error' => 'Missing required fields', 'fields' => $missing], 400);
    }

    $db = Database::connect();
    $statement = $db->prepare(
        'SELECT id, name, email, password_hash, role FROM users WHERE email = ?'
    );
    $statement->execute([strtolower(trim((string) $data['email']))]);
    $user = $statement->fetch();

    if (!$user || !password_verify((string) $data['password'], $user['password_hash'])) {
        return jsonResponse($response, ['error' => 'Invalid email or password'], 401);
    }

    unset($user['password_hash']);
    $now = time();
    $token = JWT::encode([
        'sub' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'iat' => $now,
        'exp' => $now + 86400,
    ], hash('sha256', $_ENV['JWT_SECRET'], true), 'HS256');

    return jsonResponse($response, ['token' => $token, 'user' => $user]);
});

$app->get('/api/auth/me', function (ServerRequestInterface $request, ResponseInterface $response) {
    $auth = getAuthUser($request);
    $db = Database::connect();
    $statement = $db->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $statement->execute([$auth['sub']]);
    $user = $statement->fetch();

    return $user
        ? jsonResponse($response, ['user' => $user])
        : jsonResponse($response, ['error' => 'User not found'], 404);
})->add(new JwtMiddleware());

$app->get('/api/vendors', function (ServerRequestInterface $request, ResponseInterface $response) {
    $db = Database::connect();
    $vendors = $db->query(
        'SELECT id, name, description FROM vendors WHERE is_active = 1 ORDER BY name'
    )->fetchAll();

    return jsonResponse($response, ['vendors' => $vendors]);
});

$app->get('/api/vendors/{id}/menu', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) {
    $db = Database::connect();
    $vendor = $db->prepare('SELECT id, name FROM vendors WHERE id = ? AND is_active = 1');
    $vendor->execute([$args['id']]);
    $vendorData = $vendor->fetch();

    if (!$vendorData) {
        return jsonResponse($response, ['error' => 'Vendor not found'], 404);
    }

    $statement = $db->prepare(
        'SELECT id, vendor_id, name, description, price, category, is_halal, is_vegetarian, in_stock
         FROM menu_items WHERE vendor_id = ? AND in_stock = 1 ORDER BY name'
    );
    $statement->execute([$args['id']]);
    $items = array_map(static function (array $item): array {
        $item['price'] = (float) $item['price'];
        $item['is_halal'] = (bool) $item['is_halal'];
        $item['is_vegetarian'] = (bool) $item['is_vegetarian'];
        $item['in_stock'] = (bool) $item['in_stock'];
        return $item;
    }, $statement->fetchAll());

    return jsonResponse($response, ['vendor' => $vendorData, 'menu_items' => $items]);
});

$app->get('/api/recommendations', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($findRecommendations, $normalizeCategory, $parseBoolean) {
    $query = $request->getQueryParams();
    $budget = null;
    if (($query['budget'] ?? '') !== '') {
        $budget = filter_var($query['budget'], FILTER_VALIDATE_FLOAT);
        if ($budget === false || $budget < 0) {
            return jsonResponse($response, ['error' => 'budget must be a valid number'], 400);
        }
    }

    $filters = [
        'budget' => $budget,
        'category' => $normalizeCategory($query['category'] ?? null),
        'halal' => $parseBoolean($query['halal'] ?? null),
        'vegetarian' => $parseBoolean($query['vegetarian'] ?? null),
        'keyword' => trim((string) ($query['keyword'] ?? '')) ?: null,
    ];

    $db = Database::connect();
    return jsonResponse($response, [
        'recommendations' => $findRecommendations($db, $filters),
    ]);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->post('/api/ai/query', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($findRecommendations, $parseAiQuery) {
    $data = (array) $request->getParsedBody();
    if (validateRequiredFields($data, ['query'])) {
        return jsonResponse($response, ['error' => 'query is required'], 400);
    }

    $query = trim((string) $data['query']);
    $source = 'gemini';
    try {
        $parsed = (new GeminiService())->parseFoodPreferences($query);
    } catch (Throwable $e) {
        $source = 'rule_based_fallback';
        $parsed = $parseAiQuery($query);
    }
    $db = Database::connect();

    return jsonResponse($response, [
        'source' => $source,
        'parsed' => $parsed,
        'recommendations' => $findRecommendations($db, $parsed),
    ]);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->get('/api/vendors/{id}/reviews', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) {
    $db = Database::connect();
    $vendor = $db->prepare('SELECT id FROM vendors WHERE id = ?');
    $vendor->execute([$args['id']]);
    if (!$vendor->fetch()) {
        return jsonResponse($response, ['error' => 'Vendor not found'], 404);
    }

    $statement = $db->prepare(
        'SELECT r.id, u.name AS student_name, r.rating, r.comment, r.created_at
         FROM reviews r
         JOIN users u ON u.id = r.student_id
         WHERE r.vendor_id = ?
         ORDER BY r.created_at DESC'
    );
    $statement->execute([$args['id']]);
    $reviews = array_map(static function (array $review): array {
        $review['rating'] = (int) $review['rating'];
        return $review;
    }, $statement->fetchAll());

    return jsonResponse($response, ['reviews' => $reviews]);
});

$app->post('/api/reviews', function (ServerRequestInterface $request, ResponseInterface $response) {
    $data = (array) $request->getParsedBody();
    $missing = validateRequiredFields($data, ['vendor_id', 'rating']);

    if ($missing) {
        return jsonResponse($response, ['error' => 'Missing required fields', 'fields' => $missing], 400);
    }

    $rating = filter_var($data['rating'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5],
    ]);
    if ($rating === false) {
        return jsonResponse($response, ['error' => 'Rating must be between 1 and 5'], 400);
    }

    $comment = trim((string) ($data['comment'] ?? ''));
    $db = Database::connect();
    $vendor = $db->prepare('SELECT id FROM vendors WHERE id = ?');
    $vendor->execute([(string) $data['vendor_id']]);
    if (!$vendor->fetch()) {
        return jsonResponse($response, ['error' => 'Vendor not found'], 404);
    }

    $auth = getAuthUser($request);
    $statement = $db->prepare(
        'INSERT INTO reviews (id, student_id, vendor_id, rating, comment) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        uuid(),
        $auth['sub'],
        (string) $data['vendor_id'],
        $rating,
        $comment === '' ? null : $comment,
    ]);

    return jsonResponse($response, ['message' => 'Review submitted'], 201);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->post('/api/payments/create-intent', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) {
    $secretKey = trim((string) ($_ENV['STRIPE_SECRET_KEY'] ?? ''));
    if ($secretKey === '') {
        return jsonResponse($response, ['error' => 'Stripe is not configured. Please use Mock Pay.'], 503);
    }

    $data = (array) $request->getParsedBody();
    $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if ($amount === false) {
        return jsonResponse($response, ['error' => 'amount must be a positive integer in sen'], 400);
    }

    try {
        $stripe = new StripeClient($secretKey);
        $intent = $stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => strtolower(trim((string) ($_ENV['STRIPE_CURRENCY'] ?? 'myr'))) ?: 'myr',
            'payment_method_types' => ['card'],
            'metadata' => [
                'student_id' => getAuthUser($request)['sub'],
                'app' => 'CampusEats',
            ],
        ]);
    } catch (Throwable $e) {
        return jsonResponse($response, ['error' => 'Unable to create Stripe payment. Please use Mock Pay.'], 502);
    }

    return jsonResponse($response, [
        'client_secret' => $intent->client_secret,
        'payment_intent_id' => $intent->id,
    ]);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->post('/api/payments/mock-success', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) {
    return jsonResponse($response, [
        'payment_status' => 'mock_paid',
        'payment_reference' => 'mock_' . bin2hex(random_bytes(8)),
        'payment_method' => 'mock',
    ]);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->post('/api/orders', function (ServerRequestInterface $request, ResponseInterface $response) use ($normalizePaymentData, $verifyStripePayment) {
    $data = (array) $request->getParsedBody();
    $missing = validateRequiredFields($data, ['vendor_id', 'pickup_at', 'items']);

    if ($missing) {
        return jsonResponse($response, ['error' => 'Missing required fields', 'fields' => $missing], 400);
    }
    if (!is_array($data['items']) || count($data['items']) === 0) {
        return jsonResponse($response, ['error' => 'Items must be a non-empty array'], 400);
    }

    try {
        $pickupAt = (new DateTimeImmutable((string) $data['pickup_at']))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return jsonResponse($response, ['error' => 'pickup_at must be a valid date and time'], 400);
    }
    try {
        $payment = $normalizePaymentData($data);
    } catch (InvalidArgumentException $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 400);
    }

    $requested = [];
    foreach ($data['items'] as $item) {
        if (!is_array($item) || validateRequiredFields($item, ['menu_item_id', 'qty'])) {
            return jsonResponse($response, ['error' => 'Each item needs menu_item_id and qty'], 400);
        }
        $qty = filter_var($item['qty'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($qty === false) {
            return jsonResponse($response, ['error' => 'Item quantities must be positive integers'], 400);
        }
        $menuId = (string) $item['menu_item_id'];
        $requested[$menuId] = ($requested[$menuId] ?? 0) + $qty;
    }

    $db = Database::connect();
    $vendor = $db->prepare('SELECT id FROM vendors WHERE id = ? AND is_active = 1');
    $vendor->execute([(string) $data['vendor_id']]);
    if (!$vendor->fetch()) {
        return jsonResponse($response, ['error' => 'Vendor not found'], 404);
    }

    $menuStatement = $db->prepare(
        'SELECT id, price FROM menu_items WHERE id = ? AND vendor_id = ? AND in_stock = 1'
    );
    $validatedItems = [];
    $total = 0.0;
    foreach ($requested as $menuId => $qty) {
        $menuStatement->execute([$menuId, (string) $data['vendor_id']]);
        $menuItem = $menuStatement->fetch();
        if (!$menuItem) {
            return jsonResponse(
                $response,
                ['error' => 'A menu item is unavailable or does not belong to this vendor', 'menu_item_id' => $menuId],
                400
            );
        }
        $price = (float) $menuItem['price'];
        $total += $price * $qty;
        $validatedItems[] = ['id' => $menuId, 'qty' => $qty, 'price' => $price];
    }

    try {
        $verifyStripePayment($payment, $total);
    } catch (Throwable $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 400);
    }

    $orderId = uuid();
    $auth = getAuthUser($request);

    try {
        $db->beginTransaction();
        $order = $db->prepare(
            'INSERT INTO orders
                (id, customer_id, vendor_id, pickup_at, total, payment_status, payment_reference, payment_method, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $order->execute([
            $orderId,
            $auth['sub'],
            $data['vendor_id'],
            $pickupAt,
            $total,
            $payment['payment_status'],
            $payment['payment_reference'],
            $payment['payment_method'],
            'placed',
        ]);

        $orderItem = $db->prepare(
            'INSERT INTO order_items (id, order_id, menu_item_id, qty, unit_price)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($validatedItems as $item) {
            $orderItem->execute([uuid(), $orderId, $item['id'], $item['qty'], $item['price']]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return jsonResponse($response, [
        'order_id' => $orderId,
        'status' => 'placed',
        'payment_status' => $payment['payment_status'],
        'payment_reference' => $payment['payment_reference'],
        'payment_method' => $payment['payment_method'],
        'total' => round($total, 2),
    ], 201);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->get('/api/orders/{id}', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) use ($fetchOrder, $findOwnedVendor) {
    $db = Database::connect();
    $order = $fetchOrder($db, $args['id']);
    if (!$order) {
        return jsonResponse($response, ['error' => 'Order not found'], 404);
    }

    $auth = getAuthUser($request);
    $allowed = $auth['role'] === 'admin' || $order['customer_id'] === $auth['sub'];
    if ($auth['role'] === 'vendor') {
        $vendor = $findOwnedVendor($db, $auth['sub']);
        $allowed = $vendor && $order['vendor_id'] === $vendor['id'];
    }
    if (!$allowed) {
        return jsonResponse($response, ['error' => 'You cannot view this order'], 403);
    }

    unset($order['customer_id']);
    return jsonResponse($response, ['order' => $order]);
})->add(new JwtMiddleware());

$app->get('/api/student/orders', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($fetchOrder) {
    $db = Database::connect();
    $auth = getAuthUser($request);

    $statement = $db->prepare('SELECT id FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
    $statement->execute([$auth['sub']]);

    $orders = [];
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $orderId) {
        $order = $fetchOrder($db, $orderId);
        unset($order['customer_id'], $order['vendor_id'], $order['customer_name'], $order['items']);
        $orders[] = $order;
    }

    return jsonResponse($response, ['orders' => $orders]);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->get('/api/rewards', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($syncLoyaltyPoints) {
    $db = Database::connect();
    $auth = getAuthUser($request);
    $syncLoyaltyPoints($db, $auth['sub']);

    $balance = $db->prepare(
        'SELECT COALESCE(SUM(points), 0) FROM loyalty_transactions WHERE student_id = ?'
    );
    $balance->execute([$auth['sub']]);

    $transactions = $db->prepare(
        'SELECT lt.id, lt.order_id, lt.points, lt.description, lt.created_at,
                o.total, v.name AS vendor_name
         FROM loyalty_transactions lt
         JOIN orders o ON o.id = lt.order_id
         JOIN vendors v ON v.id = o.vendor_id
         WHERE lt.student_id = ?
         ORDER BY lt.created_at DESC'
    );
    $transactions->execute([$auth['sub']]);

    $collectedOrders = $db->prepare(
        "SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status = 'collected'"
    );
    $collectedOrders->execute([$auth['sub']]);

    $history = array_map(static function (array $transaction): array {
        $transaction['points'] = (int) $transaction['points'];
        $transaction['total'] = (float) $transaction['total'];
        return $transaction;
    }, $transactions->fetchAll());
    $pointsBalance = (int) $balance->fetchColumn();
    $milestones = [25, 50, 100, 200];
    $nextMilestone = null;
    foreach ($milestones as $milestone) {
        if ($pointsBalance < $milestone) {
            $nextMilestone = $milestone;
            break;
        }
    }
    if ($nextMilestone === null) {
        $nextMilestone = $pointsBalance + 50;
    }

    return jsonResponse($response, [
        'balance' => $pointsBalance,
        'rate' => 'RM1 = 1 point',
        'collected_orders' => (int) $collectedOrders->fetchColumn(),
        'next_milestone' => $nextMilestone,
        'points_to_next_milestone' => max(0, $nextMilestone - $pointsBalance),
        'transactions' => $history,
    ]);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->get('/api/vendor-applications/me', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($fetchVendorApplications) {
    $db = Database::connect();
    $auth = getAuthUser($request);
    $statement = $db->prepare(
        'SELECT id FROM vendor_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
    );
    $statement->execute([$auth['sub']]);
    $applicationId = $statement->fetchColumn();

    return jsonResponse($response, [
        'application' => $applicationId ? ($fetchVendorApplications($db, $applicationId)[0] ?? null) : null,
        'role' => $auth['role'] ?? null,
    ]);
})->add(new JwtMiddleware());

$app->post('/api/vendor-applications', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($validateVendorApplicationData) {
    $data = (array) $request->getParsedBody();
    try {
        $clean = $validateVendorApplicationData($data);
    } catch (InvalidArgumentException $e) {
        return jsonResponse($response, [
            'error' => 'Please check your vendor application details.',
            'fields' => json_decode($e->getMessage(), true) ?: [],
        ], 400);
    }

    $db = Database::connect();
    $auth = getAuthUser($request);
    $pending = $db->prepare(
        "SELECT id FROM vendor_applications WHERE user_id = ? AND status = 'pending' LIMIT 1"
    );
    $pending->execute([$auth['sub']]);
    if ($pending->fetch()) {
        return jsonResponse($response, ['error' => 'You already have a pending vendor application'], 400);
    }

    $existingVendor = $db->prepare('SELECT id FROM vendors WHERE owner_id = ?');
    $existingVendor->execute([$auth['sub']]);
    if ($existingVendor->fetch()) {
        return jsonResponse($response, ['error' => 'Your account already owns a vendor profile'], 400);
    }

    $application = [
        'id' => uuid(),
        'user_id' => $auth['sub'],
        'vendor_name' => $clean['vendor_name'],
        'description' => $clean['description'],
        'location' => $clean['location'],
        'opening_hours' => $clean['opening_hours'],
        'status' => 'pending',
    ];

    $statement = $db->prepare(
        'INSERT INTO vendor_applications
            (id, user_id, vendor_name, description, location, opening_hours, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $application['id'],
        $application['user_id'],
        $application['vendor_name'],
        $application['description'],
        $application['location'],
        $application['opening_hours'],
        $application['status'],
    ]);

    return jsonResponse($response, ['application' => $application], 201);
})->add(new RoleMiddleware(['student']))->add(new JwtMiddleware());

$app->get('/api/vendor/orders', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($fetchVendorOrders, $findOwnedVendor) {
    $db = Database::connect();
    $vendor = $findOwnedVendor($db, getAuthUser($request)['sub']);
    if (!$vendor) {
        return jsonResponse($response, ['error' => 'Vendor account not found'], 404);
    }

    return jsonResponse($response, ['orders' => $fetchVendorOrders($db, $vendor['id'])]);
})->add(new RoleMiddleware(['vendor']))->add(new JwtMiddleware());

$app->patch('/api/orders/{id}/status', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) use ($fetchOrder, $findOwnedVendor, $createNotification, $awardLoyaltyPoints) {
    $data = (array) $request->getParsedBody();
    if (validateRequiredFields($data, ['status'])) {
        return jsonResponse($response, ['error' => 'status is required'], 400);
    }

    $nextStatus = strtolower(trim((string) $data['status']));
    $transitions = [
        'placed' => 'preparing',
        'preparing' => 'ready',
        'ready' => 'collected',
    ];
    if (!in_array($nextStatus, array_values($transitions), true)) {
        return jsonResponse($response, ['error' => 'Invalid order status'], 400);
    }

    $db = Database::connect();
    $vendor = $findOwnedVendor($db, getAuthUser($request)['sub']);
    if (!$vendor) {
        return jsonResponse($response, ['error' => 'Vendor account not found'], 404);
    }

    try {
        $db->beginTransaction();
        $statement = $db->prepare('SELECT id, status, vendor_id, customer_id, total FROM orders WHERE id = ? FOR UPDATE');
        $statement->execute([$args['id']]);
        $order = $statement->fetch();

        if (!$order) {
            $db->rollBack();
            return jsonResponse($response, ['error' => 'Order not found'], 404);
        }
        if ($order['vendor_id'] !== $vendor['id']) {
            $db->rollBack();
            return jsonResponse($response, ['error' => 'This order belongs to another vendor'], 403);
        }
        if (($transitions[$order['status']] ?? null) !== $nextStatus) {
            $db->rollBack();
            return jsonResponse($response, [
                'error' => sprintf('Order cannot move from %s to %s', $order['status'], $nextStatus),
            ], 400);
        }

        $update = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $update->execute([$nextStatus, $args['id']]);
        if ($nextStatus === 'ready') {
            $createNotification($db, $order['customer_id'], 'Your order is ready for pickup.');
        } elseif ($nextStatus === 'preparing') {
            $createNotification($db, $order['customer_id'], 'Your order is being prepared.');
        } elseif ($nextStatus === 'collected') {
            $points = $awardLoyaltyPoints($db, $order);
            if ($points > 0) {
                $createNotification($db, $order['customer_id'], "You earned {$points} loyalty points.");
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    $updated = $fetchOrder($db, $args['id']);
    unset($updated['customer_id'], $updated['vendor_id']);
    return jsonResponse($response, ['order' => $updated]);
})->add(new RoleMiddleware(['vendor']))->add(new JwtMiddleware());

$app->get('/api/notifications', function (ServerRequestInterface $request, ResponseInterface $response) {
    $db = Database::connect();
    $auth = getAuthUser($request);
    $statement = $db->prepare(
        'SELECT id, message, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC'
    );
    $statement->execute([$auth['sub']]);
    $notifications = array_map(static function (array $notification): array {
        $notification['is_read'] = (bool) $notification['is_read'];
        return $notification;
    }, $statement->fetchAll());

    return jsonResponse($response, ['notifications' => $notifications]);
})->add(new JwtMiddleware());

$app->patch('/api/notifications/{id}/read', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) {
    $db = Database::connect();
    $auth = getAuthUser($request);
    $statement = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $statement->execute([$args['id'], $auth['sub']]);

    if ($statement->rowCount() === 0) {
        return jsonResponse($response, ['error' => 'Notification not found'], 404);
    }

    return jsonResponse($response, ['message' => 'Notification marked as read']);
})->add(new JwtMiddleware());

$app->get('/api/vendor/analytics', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($findOwnedVendor) {
    $db = Database::connect();
    $vendor = $findOwnedVendor($db, getAuthUser($request)['sub']);
    if (!$vendor) {
        return jsonResponse($response, ['error' => 'Vendor account not found'], 404);
    }

    $summary = $db->prepare(
        "SELECT COUNT(*) AS orders_today,
                COALESCE(SUM(total), 0) AS revenue_today,
                COALESCE(SUM(status <> 'collected'), 0) AS pending_orders
         FROM orders WHERE vendor_id = ? AND DATE(created_at) = CURDATE()"
    );
    $summary->execute([$vendor['id']]);
    $totals = $summary->fetch();

    $statuses = ['placed' => 0, 'preparing' => 0, 'ready' => 0, 'collected' => 0];
    $statusStatement = $db->prepare(
        'SELECT status, COUNT(*) AS total FROM orders
         WHERE vendor_id = ? AND DATE(created_at) = CURDATE() GROUP BY status'
    );
    $statusStatement->execute([$vendor['id']]);
    foreach ($statusStatement->fetchAll() as $row) {
        $statuses[$row['status']] = (int) $row['total'];
    }

    $top = $db->prepare(
        'SELECT m.name, SUM(oi.qty) AS quantity
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         JOIN menu_items m ON m.id = oi.menu_item_id
         WHERE o.vendor_id = ? AND DATE(o.created_at) = CURDATE()
         GROUP BY m.id, m.name ORDER BY quantity DESC, m.name LIMIT 1'
    );
    $top->execute([$vendor['id']]);
    $topItem = $top->fetch();

    $peak = $db->prepare(
        'SELECT HOUR(created_at) AS hour, COUNT(*) AS total FROM orders
         WHERE vendor_id = ? AND DATE(created_at) = CURDATE()
         GROUP BY HOUR(created_at) ORDER BY total DESC, hour LIMIT 1'
    );
    $peak->execute([$vendor['id']]);
    $peakRow = $peak->fetch();
    $peakHour = null;
    if ($peakRow) {
        $start = DateTimeImmutable::createFromFormat('!H', str_pad((string) $peakRow['hour'], 2, '0', STR_PAD_LEFT));
        $peakHour = $start->format('g:i A') . ' - ' . $start->modify('+1 hour')->format('g:i A');
    }

    return jsonResponse($response, ['analytics' => [
        'orders_today' => (int) $totals['orders_today'],
        'revenue_today' => (float) $totals['revenue_today'],
        'pending_orders' => (int) $totals['pending_orders'],
        'top_item' => $topItem['name'] ?? null,
        'peak_hour' => $peakHour,
        'status_summary' => $statuses,
    ]]);
})->add(new RoleMiddleware(['vendor']))->add(new JwtMiddleware());

$app->get('/api/admin/summary', function (ServerRequestInterface $request, ResponseInterface $response) {
    $db = Database::connect();
    $summary = [
        'total_users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'total_vendors' => (int) $db->query('SELECT COUNT(*) FROM vendors')->fetchColumn(),
        'total_orders' => (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'total_revenue' => (float) $db->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn(),
        'total_loyalty_points' => (int) $db->query('SELECT COALESCE(SUM(points), 0) FROM loyalty_transactions')->fetchColumn(),
    ];

    return jsonResponse($response, ['summary' => $summary]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->get('/api/admin/rewards', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($syncLoyaltyPoints) {
    $db = Database::connect();
    $syncLoyaltyPoints($db);

    $summary = [
        'total_points' => (int) $db->query('SELECT COALESCE(SUM(points), 0) FROM loyalty_transactions')->fetchColumn(),
        'rewarded_orders' => (int) $db->query('SELECT COUNT(*) FROM loyalty_transactions')->fetchColumn(),
        'students_with_points' => (int) $db->query('SELECT COUNT(DISTINCT student_id) FROM loyalty_transactions')->fetchColumn(),
    ];

    $balances = $db->query(
        "SELECT u.id, u.name, u.email,
                COALESCE(SUM(lt.points), 0) AS points,
                COUNT(lt.id) AS rewarded_orders
         FROM users u
         LEFT JOIN loyalty_transactions lt ON lt.student_id = u.id
         WHERE u.role = 'student'
         GROUP BY u.id, u.name, u.email
         ORDER BY points DESC, u.name"
    )->fetchAll();
    $balances = array_map(static function (array $balance): array {
        $balance['points'] = (int) $balance['points'];
        $balance['rewarded_orders'] = (int) $balance['rewarded_orders'];
        return $balance;
    }, $balances);

    $recent = $db->query(
        'SELECT lt.id, lt.order_id, lt.points, lt.description, lt.created_at,
                u.name AS student_name, u.email AS student_email, v.name AS vendor_name
         FROM loyalty_transactions lt
         JOIN users u ON u.id = lt.student_id
         JOIN orders o ON o.id = lt.order_id
         JOIN vendors v ON v.id = o.vendor_id
         ORDER BY lt.created_at DESC
         LIMIT 20'
    )->fetchAll();
    $recent = array_map(static function (array $transaction): array {
        $transaction['points'] = (int) $transaction['points'];
        return $transaction;
    }, $recent);

    return jsonResponse($response, [
        'summary' => $summary,
        'balances' => $balances,
        'recent_transactions' => $recent,
    ]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->get('/api/admin/users', function (ServerRequestInterface $request, ResponseInterface $response) {
    $db = Database::connect();
    $users = $db->query(
        'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC, name'
    )->fetchAll();

    return jsonResponse($response, ['users' => $users]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->get('/api/admin/vendors', function (ServerRequestInterface $request, ResponseInterface $response) {
    $db = Database::connect();
    $vendors = $db->query(
        "SELECT v.id, v.name,
                COALESCE(v.location, 'Food Court A') AS location,
                COALESCE(v.opening_hours, '9AM - 5PM') AS opening_hours,
                v.is_active,
                u.name AS owner_name
         FROM vendors v
         JOIN users u ON u.id = v.owner_id
         ORDER BY v.name"
    )->fetchAll();
    $vendors = array_map(static function (array $vendor): array {
        $vendor['is_active'] = (bool) $vendor['is_active'];
        return $vendor;
    }, $vendors);

    return jsonResponse($response, ['vendors' => $vendors]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->get('/api/admin/vendor-applications', function (
    ServerRequestInterface $request,
    ResponseInterface $response
) use ($fetchVendorApplications) {
    $db = Database::connect();

    return jsonResponse($response, [
        'applications' => $fetchVendorApplications($db),
    ]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->patch('/api/admin/vendor-applications/{id}/approve', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) use ($fetchVendorApplications) {
    $db = Database::connect();

    try {
        $db->beginTransaction();
        $statement = $db->prepare(
            "SELECT va.*, u.role
             FROM vendor_applications va
             JOIN users u ON u.id = va.user_id
             WHERE va.id = ? FOR UPDATE"
        );
        $statement->execute([$args['id']]);
        $application = $statement->fetch();

        if (!$application) {
            $db->rollBack();
            return jsonResponse($response, ['error' => 'Vendor application not found'], 404);
        }
        if ($application['status'] !== 'pending') {
            $db->rollBack();
            return jsonResponse($response, ['error' => 'Only pending applications can be approved'], 400);
        }

        $existingVendor = $db->prepare('SELECT id FROM vendors WHERE owner_id = ?');
        $existingVendor->execute([$application['user_id']]);
        if ($existingVendor->fetch()) {
            $db->rollBack();
            return jsonResponse($response, ['error' => 'Applicant already owns a vendor profile'], 400);
        }

        $vendorId = uuid();
        $vendor = $db->prepare(
            'INSERT INTO vendors (id, owner_id, name, description, location, opening_hours, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $vendor->execute([
            $vendorId,
            $application['user_id'],
            $application['vendor_name'],
            $application['description'],
            $application['location'],
            $application['opening_hours'],
        ]);

        $db->prepare("UPDATE users SET role = 'vendor' WHERE id = ?")->execute([$application['user_id']]);
        $db->prepare("UPDATE vendor_applications SET status = 'approved' WHERE id = ?")->execute([$args['id']]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return jsonResponse($response, [
        'message' => 'Vendor application approved',
        'application' => $fetchVendorApplications($db, $args['id'])[0] ?? null,
    ]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->patch('/api/admin/vendor-applications/{id}/reject', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) use ($fetchVendorApplications) {
    $db = Database::connect();
    $statement = $db->prepare("UPDATE vendor_applications SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $statement->execute([$args['id']]);

    if ($statement->rowCount() === 0) {
        return jsonResponse($response, ['error' => 'Pending vendor application not found'], 404);
    }

    return jsonResponse($response, [
        'message' => 'Vendor application rejected',
        'application' => $fetchVendorApplications($db, $args['id'])[0] ?? null,
    ]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->patch('/api/admin/vendors/{id}/toggle-active', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
) {
    $db = Database::connect();
    $statement = $db->prepare('SELECT id, is_active FROM vendors WHERE id = ?');
    $statement->execute([$args['id']]);
    $vendor = $statement->fetch();
    if (!$vendor) {
        return jsonResponse($response, ['error' => 'Vendor not found'], 404);
    }

    $nextActive = (int) !$vendor['is_active'];
    $update = $db->prepare('UPDATE vendors SET is_active = ? WHERE id = ?');
    $update->execute([$nextActive, $args['id']]);

    return jsonResponse($response, ['vendor' => [
        'id' => $args['id'],
        'is_active' => (bool) $nextActive,
    ]]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

$app->get('/api/admin/orders', function (ServerRequestInterface $request, ResponseInterface $response) {
    $db = Database::connect();
    $statement = $db->query(
        'SELECT o.id, u.name AS customer_name, v.name AS vendor_name,
                o.status, o.total, o.pickup_at, o.created_at
         FROM orders o
         JOIN users u ON u.id = o.customer_id
         JOIN vendors v ON v.id = o.vendor_id
         ORDER BY o.created_at DESC'
    );
    $orders = array_map(static function (array $order): array {
        $order['total'] = (float) $order['total'];
        return $order;
    }, $statement->fetchAll());

    return jsonResponse($response, ['orders' => $orders]);
})->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());
