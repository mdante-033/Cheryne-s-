<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;

use function App\Helpers\config;
use function App\Helpers\flash;
use function App\Helpers\json_response;
use function App\Helpers\redirect;
use function App\Helpers\view;

final class PaymentController
{
    public function success(): void
    {
        $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
        $sessionId = trim((string) (filter_input(INPUT_GET, 'session_id', FILTER_DEFAULT) ?? ''));

        if ($orderId !== false && $orderId !== null && $orderId > 0 && $sessionId !== '') {
            if ($this->verifyStripePayment((int) $orderId, $sessionId)) {
                Payment::markPaid((int) $orderId, 'STRIPE-' . $sessionId);
                Order::updateStatus((int) $orderId, 'confirmed');
            }
        }

        view('payment-success', [
            'title' => "Payment Received - Cheryne's",
            'description' => "Your Cheryne's order is confirmed.",
            'orderId' => $orderId,
        ]);
    }

    public function cancel(): void
    {
        flash('warning', 'Payment was cancelled. You can place the order again when ready.');
        redirect('/cart');
    }

    public function webhookMpesa(): void
    {
        $payload = file_get_contents('php://input') ?: '';
        $decoded = json_decode($payload, true);
        $signature = trim((string) ($_SERVER['HTTP_X_MPESA_SIGNATURE'] ?? $_POST['signature'] ?? ''));
        $settings = (array) (config('payments')['mpesa'] ?? []);
        $passkey = (string) ($settings['passkey'] ?? '');
        $shortcode = (string) ($settings['shortcode'] ?? '');

        if ($signature === '' || $passkey === '' || $shortcode === '') {
            http_response_code(400);
            json_response(['ok' => false, 'error' => 'Missing MPesa webhook verification data.']);
        }

        $expected = hash_hmac('sha256', $payload . '|' . $shortcode . '|' . $passkey, $passkey);
        if (!hash_equals($expected, $signature)) {
            http_response_code(400);
            json_response(['ok' => false, 'error' => 'Invalid MPesa signature.']);
        }

        $orderId = null;
        if (is_array($decoded)) {
            $orderId = (int) ($decoded['order_id'] ?? 0);
            if ($orderId <= 0 && isset($decoded['Body']['stkCallback']['CallbackMetadata']['Item'])) {
                foreach ($decoded['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
                    if (($item['Name'] ?? '') === 'AccountReference') {
                        $accountReference = (string) ($item['Value'] ?? '');
                        if (preg_match('/\b(\d+)\b/', $accountReference, $matches) === 1) {
                            $orderId = (int) $matches[1];
                            break;
                        }
                    }
                }
            }
        }

        if ($orderId > 0) {
            Payment::markPaid($orderId, 'MPESA-' . strtoupper(bin2hex(random_bytes(4))));
            Order::updateStatus($orderId, 'confirmed');
        }

        json_response(['ok' => true]);
    }

    private function verifyStripePayment(int $orderId, string $sessionId): bool
    {
        $settings = (array) (config('payments')['stripe'] ?? []);
        $secret = (string) ($settings['secret'] ?? '');

        if ($secret === '' || !class_exists(\Stripe\StripeClient::class)) {
            return false;
        }

        try {
            $stripe = new \Stripe\StripeClient($secret);
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            $metadata = $session->metadata ?? null;
            $sessionOrderId = is_object($metadata) ? (string) ($metadata->order_id ?? '') : (string) ($metadata['order_id'] ?? '');

            return isset($session->payment_status) && $session->payment_status === 'paid' && $sessionOrderId === (string) $orderId;
        } catch (\Throwable) {
            return false;
        }
    }
}