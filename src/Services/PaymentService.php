<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Stripe\Exception\ApiErrorException;
use function App\Helpers\config;

final class PaymentService
{
    private const DEFAULT_CURRENCY = 'kes';
    private const DEFAULT_RESTAURANT_NAME = "Cheryne's";
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MS = 100;

    /**
     * Create a Stripe checkout session
     *
     * @param array $order Order information
     * @param array $items Order items
     * @param string $successUrl Redirect URL after successful payment
     * @param string $cancelUrl Redirect URL after payment cancellation
     * @return string Checkout session URL
     * @throws RuntimeException When Stripe API call fails
     */
    public function createStripeCheckoutSession(
        array $order, 
        array $items, 
        string $successUrl, 
        string $cancelUrl
    ): string {
        // Validate required parameters
        $this->validateParameters($order, $items, $successUrl, $cancelUrl);
        
        // Get Stripe configuration
        $payments = config('payments');
        $secret = (string) ($payments['stripe']['secret'] ?? '');
        
        // If Stripe secret is not configured or Stripe client class doesn't exist, return demo mode URL
        if ($secret === '' || !class_exists(\Stripe\StripeClient::class)) {
            return $this->createDemoUrl($successUrl);
        }
        
        // Create Stripe client
        $stripe = new \Stripe\StripeClient($secret);
        
        // Prepare line items
        $lineItems = $this->prepareLineItems($items);
        
        // Create checkout session with retry mechanism
        $session = $this->createCheckoutSessionWithRetry(
            $stripe,
            $order,
            $lineItems,
            $this->buildSuccessUrl($successUrl),
            $cancelUrl
        );
        
        // Validate returned session URL
        if (empty($session->url)) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }
        
        return (string) $session->url;
    }
    
    /**
     * Validate required parameters
     */
    private function validateParameters(
        array $order, 
        array $items, 
        string $successUrl, 
        string $cancelUrl
    ): void {
        if (empty($order['id'])) {
            throw new RuntimeException('Order ID is required.');
        }
        
        if (empty($items)) {
            throw new RuntimeException('Order items cannot be empty.');
        }
        
        if (empty($successUrl) || !filter_var($successUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Valid success URL is required.');
        }
        
        if (empty($cancelUrl) || !filter_var($cancelUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Valid cancel URL is required.');
        }
    }
    
    /**
     * Create demo mode URL
     */
    private function createDemoUrl(string $successUrl): string
    {
        return $successUrl . (str_contains($successUrl, '?') ? '&' : '?') . 'demo=1';
    }

    private function buildSuccessUrl(string $successUrl): string
    {
        return $successUrl . (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';
    }
    
    /**
     * Prepare Stripe line items
     */
    private function prepareLineItems(array $items): array
    {
        $lineItems = [];
        
        foreach ($items as $item) {
            // Validate item data
            if (empty($item['quantity']) || empty($item['price']) || empty($item['name'])) {
                throw new RuntimeException('Invalid item data. Quantity, price and name are required.');
            }
            
            $lineItems[] = [
                'quantity' => (int) $item['quantity'],
                'price_data' => [
                    'currency' => self::DEFAULT_CURRENCY,
                    'unit_amount' => (int) round(((float) $item['price']) * 100),
                    'product_data' => [
                        'name' => (string) $item['name'],
                    ],
                ],
            ];
        }
        
        return $lineItems;
    }
    
    /**
     * Create checkout session with retry mechanism
     */
    private function createCheckoutSessionWithRetry(
        \Stripe\StripeClient $stripe,
        array $order,
        array $lineItems,
        string $successUrl,
        string $cancelUrl
    ) {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < self::MAX_RETRY_ATTEMPTS) {
            try {
                return $this->createCheckoutSession($stripe, $order, $lineItems, $successUrl, $cancelUrl);
            } catch (ApiErrorException $e) {
                $lastException = $e;
                $attempt++;
                
                if ($e->getHttpStatus() !== 429) {
                    throw $e;
                }
                
                usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            }
        }
        
        throw new RuntimeException(
            'Failed to create Stripe checkout session after ' . self::MAX_RETRY_ATTEMPTS . ' attempts.',
            0,
            $lastException
        );
    }

    private function createCheckoutSession(
        \Stripe\StripeClient $stripe,
        array $order,
        array $lineItems,
        string $successUrl,
        string $cancelUrl
    ) {
        return $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'metadata' => [
                'order_id' => (string) $order['id'],
                'restaurant' => self::DEFAULT_RESTAURANT_NAME,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }
}
