<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\url;

$suppliers = $suppliers ?? [];
?>
<section class="admin-shell container">
    <div class="admin-topbar"><div><p class="eyebrow">Operations</p><h1 class="admin-topbar__heading">Suppliers</h1></div><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin')) ?>">Back to dashboard</a></div>
    <div class="admin-two-col"><section class="admin-panel"><h2>Supplier directory</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Notes</th></tr></thead><tbody>
    <?php if (!$suppliers): ?><tr><td colspan="4">No suppliers added yet.</td></tr><?php endif; ?>
    <?php foreach ($suppliers as $supplier): ?><tr><td><?= e($supplier['name']) ?></td><td><?= e($supplier['phone'] ?? '') ?></td><td><?= e($supplier['email'] ?? '') ?></td><td><?= e($supplier['notes'] ?? '') ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
    <form class="form-panel" action="<?= e(url('/admin/suppliers')) ?>" method="post"><h2>Add supplier</h2><?= csrf_field() ?><label>Name <input name="name" maxlength="120" required></label><label>Phone <input name="phone" maxlength="30"></label><label>Email <input type="email" name="email" maxlength="160"></label><label>Notes <textarea name="notes" maxlength="500" rows="4"></textarea></label><button class="btn btn-primary" type="submit">Add supplier</button></form></div>
</section>
