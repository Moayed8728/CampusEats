<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
{
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));

    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json');
}

function uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20)
    );
}

function getAuthUser(ServerRequestInterface $request): array
{
    return (array) $request->getAttribute('auth_user', []);
}

function validateRequiredFields(array $data, array $fields): array
{
    return array_values(array_filter($fields, static function (string $field) use ($data): bool {
        return !array_key_exists($field, $data)
            || $data[$field] === null
            || (is_string($data[$field]) && trim($data[$field]) === '');
    }));
}
