<?php

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;

$categories = $categories ?? [];
$items = $items ?? [];
?>
<section class="admin-shell container">
    <div class="admin-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Menu Management</h1>
        </div>
        <div class="admin-tabs">
            <a href="<?= e(url('/admin')) ?>">Dashboard</a>
            <a href="<?= e(url('/admin/orders')) ?>">Orders</a>
        </div>
    </div>

    <section class="admin-panel">
        <h2>Categories</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <form action="<?= e(url('/admin/categories/' . $category['id'] . '/update')) ?>" method="post" class="status-form">
                            <?= csrf_field() ?>
                            <td><input type="text" name="name" value="<?= e($category['name']) ?>" required></td>
                            <td><?= e($category['slug']) ?></td>
                            <td style="display:flex; gap:.5rem;">
                                <button class="btn btn-sm btn-outline-dark" type="submit">Save</button>
                        </form>
                        <form action="<?= e(url('/admin/categories/' . $category['id'] . '/delete')) ?>" method="post" onsubmit="return confirm('Delete this category?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-link text-danger" type="submit">Delete</button>
                        </form>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form action="<?= e(url('/admin/categories')) ?>" method="post" class="inline-form" style="margin-top:1rem;">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="New category name" required>
            <button class="btn btn-sm btn-primary" type="submit">Add category</button>
        </form>
    </section>

    <section class="admin-panel">
        <h2>Menu items</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Available</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <form action="<?= e(url('/admin/menu/' . $item['id'] . '/update')) ?>" method="post" class="status-form">
                            <?= csrf_field() ?>
                            <td><input type="text" name="name" value="<?= e($item['name']) ?>" required></td>
                            <td>
                                <select name="category_id">
                                    <option value="">Uncategorized</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= e($category['id']) ?>" <?= ((int) ($item['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="price" min="0" step="0.01" value="<?= e($item['price']) ?>" required></td>
                            <td><input type="checkbox" name="is_available" value="1" <?= filter_var($item['is_available'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' ?>></td>
                            <td style="display:flex; gap:.5rem;">
                                <button class="btn btn-sm btn-outline-dark" type="submit">Save</button>
                        </form>
                        <form action="<?= e(url('/admin/menu/' . $item['id'] . '/delete')) ?>" method="post" onsubmit="return confirm('Delete this item?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-link text-danger" type="submit">Delete</button>
                        </form>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-top:1.5rem;">Add menu item</h3>
        <form action="<?= e(url('/admin/menu')) ?>" method="post" class="form-panel">
            <?= csrf_field() ?>
            <label>Name <input type="text" name="name" required maxlength="120"></label>
            <label>Description <textarea name="description" maxlength="500" rows="3"></textarea></label>
            <label>Price (KSh) <input type="number" name="price" min="0" step="0.01" required></label>
            <label>Image URL <input type="url" name="image_url" required></label>
            <label>Category
                <select name="category_id">
                    <option value="">Uncategorized</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><input type="checkbox" name="is_available" value="1" checked style="width:auto;"> Available</label>
            <button class="btn btn-primary" type="submit">Add item</button>
        </form>
    </section>
</section>
