<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function App\Helpers\config;
use function App\Helpers\csrf_token;
use function App\Helpers\start_secure_session;
use function App\Helpers\verify_csrf;

final class CsrfTest extends TestCase
{
    public function testCsrfTokenVerifies(): void
    {
        start_secure_session();
        $token = csrf_token();

        $this->assertNotSame('', $token);
        $this->assertTrue(verify_csrf($token));
        $this->assertFalse(verify_csrf('wrong-token'));
    }

    public function testSessionConfigUsesTheSharedSecureFlagKey(): void
    {
        putenv('SESSION_SECURE');
        unset($_ENV['SESSION_SECURE'], $_SERVER['SESSION_SECURE']);

        $sessionConfig = config('session');

        $this->assertArrayHasKey('secure', $sessionConfig);
        $this->assertFalse($sessionConfig['secure']);
    }
}
