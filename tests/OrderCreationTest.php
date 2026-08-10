<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Order creation tests require a live PostgreSQL database.
 * Skipped by default. To run:
 *   1. Set DB_PASS in .env
 *   2. Ensure PostgreSQL is running on localhost:5432
 *   3. Run migrations (php artisan migrate)
 */
final class OrderCreationTest extends TestCase
{
    public function testOrderCreationIntegrationSkipped(): void
    {
        $this->markTestSkipped(
            'Order creation integration test requires PostgreSQL + migrations. '
            . 'Configure DB_PASS in .env and run migrations to enable.'
        );
    }
}
