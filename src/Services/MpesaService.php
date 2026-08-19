<?php
declare(strict_types=1);

namespace App\Services;

use function App\Helpers\config;

final class MpesaService
{
    public function initiateStkPush(string $phone, float $amount, string $accountReference, string $description): array
    {
        $settings = (array) (config('payments')['mpesa'] ?? []);
        $required = ['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'callback_url'];
        foreach ($required as $key) {
            if (empty($settings[$key])) {
                return [
                    'ok' => false,
                    'message' => 'M-Pesa is not configured. Please choose another payment method or contact us.',
                    'reference' => null,
                ];
            }
        }

        $phoneNumber = $this->normalizePhone($phone);
        if ($phoneNumber === null || $amount <= 0) {
            return [
                'ok' => false,
                'message' => 'Please provide a valid Kenyan phone number and order amount.',
                'reference' => null,
            ];
        }

        try {
            $accessToken = $this->requestAccessToken(
                (string) $settings['consumer_key'],
                (string) $settings['consumer_secret'],
                (bool) ($settings['sandbox'] ?? true)
            );
            $timestamp = date('YmdHis');
            $shortcode = (string) $settings['shortcode'];
            $password = base64_encode($shortcode . (string) $settings['passkey'] . $timestamp);
            $response = $this->requestJson(
                'POST',
                $this->baseUrl((bool) ($settings['sandbox'] ?? true)) . '/mpesa/stkpush/v1/processrequest',
                [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
                [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => (string) ($settings['transaction_type'] ?? 'CustomerPayBillOnline'),
                    'Amount' => (int) round($amount),
                    'PartyA' => $phoneNumber,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phoneNumber,
                    'CallBackURL' => (string) $settings['callback_url'],
                    'AccountReference' => substr($accountReference, 0, 12),
                    'TransactionDesc' => substr($description, 0, 13),
                ]
            );

            if (($response['ResponseCode'] ?? '') !== '0') {
                return [
                    'ok' => false,
                    'message' => (string) ($response['ResponseDescription'] ?? 'M-Pesa could not start the payment request.'),
                    'reference' => null,
                    'response' => $response,
                ];
            }

            return [
                'ok' => true,
                'message' => 'M-Pesa payment prompt sent to your phone. Enter your PIN to complete it.',
                'reference' => (string) ($response['CheckoutRequestID'] ?? $response['MerchantRequestID'] ?? ''),
                'response' => $response,
            ];
        } catch (\Throwable) {
            return [
                'ok' => false,
                'message' => 'M-Pesa is temporarily unavailable. Please try again or choose another payment method.',
                'reference' => null,
            ];
        }
    }

    private function requestAccessToken(string $consumerKey, string $consumerSecret, bool $sandbox): string
    {
        $response = $this->requestJson(
            'GET',
            $this->baseUrl($sandbox) . '/oauth/v1/generate?grant_type=client_credentials',
            ['Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)],
            null
        );

        $token = (string) ($response['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('M-Pesa access token was not returned.');
        }

        return $token;
    }

    private function requestJson(string $method, string $url, array $headers, ?array $body): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new \RuntimeException('Could not initialise the M-Pesa request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $rawResponse = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($rawResponse === false || $error !== '') {
            throw new \RuntimeException('M-Pesa request failed.');
        }

        $response = json_decode($rawResponse, true);
        if (!is_array($response) || $httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('M-Pesa returned an invalid response.');
        }

        return $response;
    }

    private function baseUrl(bool $sandbox): string
    {
        $configuredUrl = trim((string) (config('payments')['mpesa']['base_url'] ?? ''));
        if ($configuredUrl !== '') {
            return rtrim($configuredUrl, '/');
        }

        return $sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254' . $digits;
        }

        return preg_match('/^254[17]\d{8}$/', $digits) === 1 ? $digits : null;
    }
}
