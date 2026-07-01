<?php

namespace App\Middleware;

use App\Database\Database;
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
            $statement = Database::connect()->prepare('SELECT name, email, role FROM users WHERE id = ?');
            $statement->execute([$payload['sub'] ?? '']);
            $user = $statement->fetch();
            if (!$user) {
                return jsonResponse(new Response(), ['error' => 'User not found'], 401);
            }
            $payload['name'] = $user['name'];
            $payload['email'] = $user['email'];
            $payload['role'] = $user['role'];
        } catch (Throwable $e) {
            return jsonResponse(new Response(), ['error' => 'Invalid or expired token'], 401);
        }

        return $handler->handle($request->withAttribute('auth_user', $payload));
    }
}
