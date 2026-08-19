<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Payment
{
    public static function create(array $data): array
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO payments (order_id, provider, provider_reference, amount, currency, status, raw_response)
             VALUES (:order_id, :provider, :provider_reference, :amount, :currency, :status, :raw_response)
             RETURNING *'
        );
        $stmt->execute([
            'order_id' => $data['order_id'],
            'provider' => $data['provider'],
            'provider_reference' => $data['provider_reference'] ?? null,
            'amount' => (float) $data['amount'],
            'currency' => $data['currency'] ?? 'KES',
            'status' => $data['status'] ?? 'pending',
            'raw_response' => json_encode($data['raw_response'] ?? [], JSON_THROW_ON_ERROR),
        ]);

        return $stmt->fetch();
    }

    public static function markPaid(int $orderId, string $providerReference): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE payments SET status = 'paid', provider_reference = :reference WHERE order_id = :order_id"
        );
        return $stmt->execute(['order_id' => $orderId, 'reference' => $providerReference]);
    }

    public static function setProviderReference(int $orderId, string $providerReference): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE payments SET provider_reference = :reference WHERE order_id = :order_id AND provider = 'mpesa'"
        );
        return $stmt->execute(['order_id' => $orderId, 'reference' => $providerReference]);
    }

    public static function findOrderIdByProviderReference(string $providerReference): ?int
    {
        $stmt = Database::connection()->prepare(
            'SELECT order_id FROM payments WHERE provider = \'mpesa\' AND provider_reference = :reference LIMIT 1'
        );
        $stmt->execute(['reference' => $providerReference]);
        $orderId = $stmt->fetchColumn();

        return $orderId === false ? null : (int) $orderId;
    }
}
