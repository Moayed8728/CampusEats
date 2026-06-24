<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(private array $roles)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = getAuthUser($request);

        if (!in_array($user['role'] ?? null, $this->roles, true)) {
            return jsonResponse(new Response(), ['error' => 'You do not have permission for this action'], 403);
        }

        return $handler->handle($request);
    }
}
