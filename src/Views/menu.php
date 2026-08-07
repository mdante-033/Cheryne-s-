<?php

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;
?>
<section class="page-head">
    <div class="container">
        <p class="eyebrow">Authentic local foods Nyali Mombasa</p>
        <h1>Cheryne's Menu</h1>
        <p>Browse local dishes, filter by availability, and order directly.</p>
    </div>
</section>

<section class="section-band">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('/menu')) ?>" data-menu-filter>
            <label>
                <span>Category</span>
                <select name="category_id">
                    <option value="">All</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Availability</span>
                <select name="available">
                    <option value="">All</option>
                    <option value="1" <?= ($filters['available'] ?? '') === '1' ? 'selected' : '' ?>>Available</option>
                    <option value="0" <?= ($filters['available'] ?? '') === '0' ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </label>
            <label>
                <span>Min price</span>
                <input type="number" name="min_price" min="0" step="1" value="<?= e($filters['min_price'] ?? '') ?>">
            </label>
            <label>
                <span>Max price</span>
                <input type="number" name="max_price" min="0" step="1" value="<?= e($filters['max_price'] ?? '') ?>">
            </label>
            <label class="filter-search">
                <span>Search</span>
                <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>">
            </label>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>
    </div>

    <div class="container menu-grid" id="menu-results">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <h2>No menu items found</h2>
                <p>Try adjusting your filters, or call 0795 879797 to ask about today's menu.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <article class="menu-card">
                <a href="<?= e(url('/menu/' . $item['id'])) ?>">
                    <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                </a>
                <div class="menu-card-body">
                    <span><?= e($item['category_name'] ?? 'Menu') ?></span>
                    <h2><a href="<?= e(url('/menu/' . $item['id'])) ?>"><?= e($item['name']) ?></a></h2>
                    <p><?= e($item['description']) ?></p>
                    <div class="menu-card-actions">
                        <strong><?= e(money($item['price'])) ?></strong>
                        <?php if (filter_var($item['is_available'], FILTER_VALIDATE_BOOLEAN)): ?>
                            <form action="<?= e(url('/cart/add')) ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button class="btn btn-sm btn-primary" type="submit">Add</button>
                            </form>
                        <?php else: ?>
                            <span class="status-pill">Unavailable</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
