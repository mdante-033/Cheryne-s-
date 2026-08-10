<?php

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;

/** @var array<int, array<string,mixed>> $orders */
$orders = $orders ?? [];

/** @var array<int, array<string,mixed>> $reservations */
$reservations = $reservations ?? [];
?>
<section class="admin-shell container">
    <div class="admin-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Orders & Reservations</h1>
        </div>
        <div class="admin-tabs">
            <a href="<?= e(url('/admin')) ?>">Dashboard</a>
            <a href="<?= e(url('/admin/menu')) ?>">Menu</a>
        </div>
    </div>

    <section class="admin-panel">
        <h2>Orders</h2>
        <?php if (empty($orders)): ?>
            <div class="empty-state" style="text-align: center; padding: 2rem;">
                <p>No orders yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>ID</th><th>Customer</th><th>Phone</th><th>Payment</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= e($order['id']) ?></td>
                            <td><?= e($order['customer_name']) ?></td>
                            <td><?= e($order['phone']) ?></td>
                            <td><?= e($order['payment_method']) ?></td>
                            <td><?= e(money($order['total_amount'])) ?></td>
                            <td>
                                <form action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>" method="post" class="status-form">
                                    <?= csrf_field() ?>
                                    <select name="status">
                                        <?php foreach (['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-dark" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <h2>Reservations</h2>
        <?php if (empty($reservations)): ?>
            <div class="empty-state" style="text-align: center; padding: 2rem;">
                <p>No reservations yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Name</th><th>Phone</th><th>Date</th><th>Guests</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?= e($reservation['name']) ?></td>
                            <td><?= e($reservation['phone']) ?></td>
                            <td><?= e($reservation['reservation_date']) ?> <?= e(substr((string) $reservation['reservation_time'], 0, 5)) ?></td>
                            <td><?= e($reservation['guests']) ?></td>
                            <td>
                                <form action="<?= e(url('/admin/reservations/' . $reservation['id'] . '/status')) ?>" method="post" class="status-form">
                                    <?= csrf_field() ?>
                                    <select name="status">
                                        <?php foreach (['pending', 'confirmed', 'cancelled', 'completed'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $reservation['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-dark" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>
