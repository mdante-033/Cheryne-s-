<?php

declare(strict_types=1);

use App\Config\Database;
use App\Models\Order;
use PHPUnit\Framework\TestCase;

/**
 * Order creation tests require a live PostgreSQL database.
 * Skipped automatically unless DB_DSN/DB_USER/DB_PASS are set in .env
 * and the schema from schema.sql has been applied. No migration tool
 * is used in this project - schema.sql is run directly:
 *   psql -U your_user -d cherynes -f schema.sql
 */
final class OrderCreationTest extends TestCase
{
    public function testOrderCreationIntegrationExample(): void
    {
        if (SKIP_DB_TESTS) {
            $this->markTestSkipped(
                'Order creation integration test requires a configured PostgreSQL connection. '
                . 'Set DB_DSN/DB_USER/DB_PASS in .env and apply schema.sql to enable.'
            );
        }

        $customer = [
            'name' => 'PHPUnit Test Customer',
            'phone' => '0700000000',
            'email' => 'phpunit@example.test',
            'notes' => 'Automated test order - safe to delete',
        ];

        $cart = [
            [
                'id' => null,
                'name' => 'Test Item',
                'price' => 10.00,
                'quantity' => 2,
            ],
        ];

        $order = Order::createFromCart(null, $customer, $cart, 'cash');

        try {
            $this->assertArrayHasKey('id', $order);
            $this->assertIsNumeric($order['id']);
            $this->assertSame('PHPUnit Test Customer', $order['customer_name']);
            $this->assertSame('cash', $order['payment_method']);
            $this->assertEqualsWithDelta(20.00, (float) $order['total_amount'], 0.001);
        } finally {
            // Clean up so this test never leaves data behind in a real database.
            $pdo = Database::connection();
            $pdo->prepare('DELETE FROM order_items WHERE order_id = :id')
                ->execute(['id' => $order['id']]);
            $pdo->prepare('DELETE FROM orders WHERE id = :id')
                ->execute(['id' => $order['id']]);
        }
    }
}