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
    $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
    ];
    $origin = $request->getHeaderLine('Origin');
    $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : 'http://localhost:5173';

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
