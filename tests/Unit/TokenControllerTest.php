<?php

namespace Tests\Unit;

use App\Http\Controllers\OAuth\TokenController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TokenControllerTest extends TestCase
{
    public function test_access_token_jti_can_be_read_from_jwt_payload(): void
    {
        $controller = new TokenController;
        $method = new ReflectionMethod($controller, 'tokenIdFromJwt');
        $method->setAccessible(true);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode(['jti' => 'token-id-123']));
        $jwt = "{$header}.{$payload}.signature";

        $this->assertSame('token-id-123', $method->invoke($controller, $jwt));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
