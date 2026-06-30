<?php

use Dotenv\Dotenv;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/helpers.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

date_default_timezone_set('Asia/Kuala_Lumpur');

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

require __DIR__ . '/../routes/api.php';

$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

$app->options('/{routes:.*}', function ($request, $response) {
    return $response;
});

$app->add(function (ServerRequestInterface $request, $handler) {
    $configuredOrigins = array_filter(array_map(
        'trim',
        explode(',', (string) ($_ENV['CORS_ALLOWED_ORIGINS'] ?? $_ENV['FRONTEND_URL'] ?? ''))
    ));
    $allowedOrigins = array_values(array_unique(array_merge([
        'http://localhost:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'https://campus-eats-ashy.vercel.app',
    ], $configuredOrigins)));
    $origin = $request->getHeaderLine('Origin');
    $isVercelPreview = (bool) preg_match(
        '/^https:\/\/campus-eats-[a-z0-9-]+-moayed8728s-projects\.vercel\.app$/i',
        $origin
    );
    $allowOrigin = (in_array($origin, $allowedOrigins, true) || $isVercelPreview)
        ? $origin
        : $allowedOrigins[0];

    if ($request->getMethod() === 'OPTIONS') {
        $response = new Response();
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, OPTIONS')
        ->withHeader('Vary', 'Origin');
});

$app->run();
