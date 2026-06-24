<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Throwable;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return jsonResponse(new Response(), ['error' => 'Bearer token is required'], 401);
        }

        try {
            $key = hash('sha256', $_ENV['JWT_SECRET'], true);
            $payload = (array) JWT::decode($matches[1], new Key($key, 'HS256'));
        } catch (Throwable $e) {
            return jsonResponse(new Response(), ['error' => 'Invalid or expired token'], 401);
        }

        return $handler->handle($request->withAttribute('auth_user', $payload));
    }
}
