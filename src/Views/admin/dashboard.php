<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;

/** @var array<string,mixed> $stats */
$stats = $stats ?? [
    'orders_30_days' => 0,
    'revenue_30_days' => 0.0,
    'upcoming_reservations' => 0,
];

/** @var array<int, array<string,mixed>> $orders */
$orders = $orders ?? [];

/** @var array<int, array<string,mixed>> $reservations */
$reservations = $reservations ?? [];
?>
<section class="admin-shell container">
    <div class="admin-layout">
        <aside class="admin-sidebar" aria-label="Admin navigation">
            <div class="admin-sidebar__brand">
                <div class="brand-icon" aria-hidden="true">A</div>
                <div>
                    <div class="admin-sidebar__title">Admin Dashboard</div>
                    <div class="admin-sidebar__subtitle">Restaurant management</div>
                </div>
            </div>

            <nav class="admin-sidebar__nav">
                <a class="admin-sidebar__link" href="<?= e(url('/admin')) ?>">Overview</a>
                <a class="admin-sidebar__link" href="<?= e(url('/admin/menu')) ?>">Products</a>
                <a class="admin-sidebar__link" href="<?= e(url('/admin/orders')) ?>">Orders</a>
                <a class="admin-sidebar__link" href="<?= e(url('/admin/reservations')) ?>">Reservations</a>
                <a class="admin-sidebar__link" href="<?= e(url('/admin/users')) ?>">Customers</a>
                <span class="admin-sidebar__link" style="opacity:.5;cursor:not-allowed;" aria-disabled="true">Inventory</span>
                <span class="admin-sidebar__link" style="opacity:.5;cursor:not-allowed;" aria-disabled="true">Suppliers</span>
                <span class="admin-sidebar__link" style="opacity:.5;cursor:not-allowed;" aria-disabled="true">Reports</span>
                <span class="admin-sidebar__link" style="opacity:.5;cursor:not-allowed;" aria-disabled="true">Settings</span>
            </nav>

            <div class="admin-sidebar__footer">
                <form action="<?= e(url('/auth/logout')) ?>" method="post" style="margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-light w-100">Logout</button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar" role="banner">
                <div>
                    <p class="eyebrow">Admin</p>
                    <h1 class="admin-topbar__heading">Admin Dashboard</h1>
                </div>
                <div>
                    <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin/menu')) ?>">Manage Products</a>
                </div>
            </header>

            <div class="metric-grid" aria-label="Admin summary">
                <div class="metric">
                    <span>Orders (30 days)</span>
                    <strong><?= e($stats['orders_30_days'] ?? 0) ?></strong>
                </div>
                <div class="metric">
                    <span>Revenue (30 days)</span>
                    <strong><?= e(money($stats['revenue_30_days'] ?? 0)) ?></strong>
                </div>
                <div class="metric">
                    <span>Upcoming Reservations</span>
                    <strong><?= e($stats['upcoming_reservations'] ?? 0) ?></strong>
                </div>
            </div>

            <div class="admin-two-col">
                <section class="admin-panel" aria-labelledby="recent-orders">
                    <h2 id="recent-orders">Recent Orders</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No recent orders</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= e($order['id'] ?? '—') ?></td>
                                    <td><?= e($order['customer_name'] ?? 'Guest') ?></td>
                                    <td><span class="status-pill" data-status="<?= e($order['status'] ?? 'pending') ?>"><?= e($order['status'] ?? 'pending') ?></span></td>
                                    <td><?= e(money($order['total_amount'] ?? 0)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-panel__actions">
                        <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/orders')) ?>">View all Orders</a>
                    </div>
                </section>

                <section class="admin-panel" aria-labelledby="upcoming-reservations">
                    <h2 id="upcoming-reservations">Upcoming Reservations</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Date / Time</th>
                                    <th scope="col">Guests</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($reservations)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No upcoming reservations</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?= e($reservation['name'] ?? '—') ?></td>
                                    <td>
                                        <?php 
                                            // Formats raw 2026-06-23 to a friendly layout like 23 Jun 2026
                                            $rawDate = $reservation['reservation_date'] ?? null;
                                            echo e($rawDate ? date('d M Y', strtotime((string)$rawDate)) : '—');
                                        ?>
                                        <small class="text-muted" style="display:block;">
                                            <?= e(substr((string) ($reservation['reservation_time'] ?? ''), 0, 5)) ?>
                                        </small>
                                    </td>
                                    <td><?= e($reservation['guests'] ?? 0) ?></td>
                                    <td><span class="status-pill" data-status="<?= e($reservation['status'] ?? 'pending') ?>"><?= e($reservation['status'] ?? 'pending') ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-panel__actions">
                        <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/reservations')) ?>">Manage Reservations</a>
                    </div>
                </section>
            </div>

            <section class="admin-panel" aria-labelledby="quick-crud">
                <h2 id="quick-crud">Quick Modules</h2>
                <div class="admin-module-grid" role="list">
                    <div class="admin-module" role="listitem">
                        <h3>Products</h3>
                        <p class="mb-2">Create, read, update, and delete menu items.</p>
                        <div class="admin-module__actions">
                            <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/menu')) ?>">Go to Products</a>
                        </div>
                    </div>
                    <div class="admin-module" role="listitem">
                        <h3>Orders</h3>
                        <p class="mb-2">Review ongoing orders, edit statuses, and track fulfillment.</p>
                        <div class="admin-module__actions">
                            <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/orders')) ?>">Go to Orders</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
