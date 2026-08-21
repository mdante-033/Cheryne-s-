<?php
declare(strict_types=1);

use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;

$reports = $reports ?? ['orders' => [], 'statuses' => [], 'top_items' => []];
?>
<section class="admin-shell container">
    <div class="admin-topbar"><div><p class="eyebrow">Performance</p><h1 class="admin-topbar__heading">Reports</h1></div><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin')) ?>">Back to dashboard</a></div>
    <div class="metric-grid"><div class="metric"><span>Orders, last 30 days</span><strong><?= e($reports['orders']['count'] ?? 0) ?></strong></div><div class="metric"><span>Revenue, last 30 days</span><strong><?= e(money($reports['orders']['revenue'] ?? 0)) ?></strong></div></div>
    <div class="admin-two-col"><section class="admin-panel"><h2>Order status</h2><div class="table-responsive"><table class="table"><thead><tr><th>Status</th><th>Orders</th></tr></thead><tbody><?php foreach ($reports['statuses'] as $row): ?><tr><td><?= e(ucfirst($row['status'])) ?></td><td><?= e($row['count']) ?></td></tr><?php endforeach; ?></tbody></table></div></section><section class="admin-panel"><h2>Top menu items</h2><div class="table-responsive"><table class="table"><thead><tr><th>Item</th><th>Quantity</th><th>Revenue</th></tr></thead><tbody><?php foreach ($reports['top_items'] as $row): ?><tr><td><?= e($row['item_name']) ?></td><td><?= e($row['quantity']) ?></td><td><?= e(money($row['revenue'])) ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
</section>
