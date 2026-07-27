<?php

declare(strict_types=1);

use App\Services\PaymentService;
use PHPUnit\Framework\TestCase;

final class PaymentServiceTest extends TestCase
{
    public function testStripeSuccessUrlIncludesCheckoutSessionToken(): void
    {
        $service = new PaymentService();
        $method = new ReflectionMethod(PaymentService::class, 'buildSuccessUrl');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'https://example.com/payment/success');

        $this->assertSame('https://example.com/payment/success?session_id={CHECKOUT_SESSION_ID}', $result);
    }
}
