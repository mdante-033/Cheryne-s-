<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\url;

$items = $items ?? [];
?>
<section class="admin-shell container">
    <div class="admin-topbar"><div><p class="eyebrow">Operations</p><h1 class="admin-topbar__heading">Inventory</h1></div><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin')) ?>">Back to dashboard</a></div>
    <section class="admin-panel"><p class="text-muted">Update stock and reorder levels for each menu item. Items at or below their reorder level are highlighted.</p>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Item</th><th>Supplier</th><th>Stock</th><th>Reorder level</th><th>Availability</th><th></th></tr></thead><tbody>
        <?php if (!$items): ?><tr><td colspan="6">No menu items found.</td></tr><?php endif; ?>
        <?php foreach ($items as $item): $low = (int) $item['stock_quantity'] <= (int) $item['reorder_level']; ?>
            <tr class="<?= $low ? 'inventory-low' : '' ?>"><td><?= e($item['name']) ?></td><td><?= e($item['supplier_name']) ?></td>
                <td colspan="3"><form class="status-form" action="<?= e(url('/admin/inventory/' . $item['id'] . '/update')) ?>" method="post"><?= csrf_field() ?><label class="visually-hidden" for="stock-<?= e($item['id']) ?>">Stock quantity</label><input id="stock-<?= e($item['id']) ?>" type="number" name="stock_quantity" min="0" value="<?= e($item['stock_quantity']) ?>"><label class="visually-hidden" for="reorder-<?= e($item['id']) ?>">Reorder level</label><input id="reorder-<?= e($item['id']) ?>" type="number" name="reorder_level" min="0" value="<?= e($item['reorder_level']) ?>"><span><?= filter_var($item['is_available'], FILTER_VALIDATE_BOOLEAN) ? 'Available' : 'Hidden' ?></span><button class="btn btn-sm btn-outline-dark" type="submit">Save</button></form></td>
            </tr>
        <?php endforeach; ?></tbody></table></div>
    </section>
</section>
