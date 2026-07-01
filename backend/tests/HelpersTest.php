<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

final class HelpersTest extends TestCase
{
    public function testValidateRequiredFieldsDetectsMissingAndEmptyFields(): void
    {
        $data = [
            'name' => 'Ali',
            'email' => '',      // empty string counts as missing
            'password' => null, // null counts as missing
            // 'role' is not present at all
        ];

        $missing = validateRequiredFields($data, ['name', 'email', 'password', 'role']);

        $this->assertSame(['email', 'password', 'role'], $missing);
    }

    public function testValidateRequiredFieldsReturnsEmptyArrayWhenAllFieldsPresent(): void
    {
        $data = [
            'name' => 'Ali',
            'email' => 'ali@test.com',
            'password' => '123456',
        ];

        $missing = validateRequiredFields($data, ['name', 'email', 'password']);

        $this->assertSame([], $missing);
    }

    public function testUuidGeneratesRfc4122Version4Format(): void
    {
        $id = uuid();

        // 8-4-4-4-12 hex groups, version nibble "4", variant nibble one of 8/9/a/b
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id
        );

        // sanity check: two calls should not collide
        $this->assertNotSame($id, uuid());
    }

    public function testJsonResponseWritesBodyAndSetsStatusAndContentType(): void
    {
        $response = jsonResponse(new Response(), ['ok' => true, 'points' => 5], 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(['ok' => true, 'points' => 5], $body);
    }
}