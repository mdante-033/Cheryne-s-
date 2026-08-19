<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;

/** @var array<int, array<string, mixed>> $items */
$items = $items ?? [];

// Safe fallback if WhatsAppService class hasn't been created yet
$whatsappLink = 'https://wa.me/254795879797?text=Hello%20Cheryne%27s%2C%20I%20would%20like%20to%20place%20an%20order';
if (class_exists(\App\Services\WhatsAppService::class)) {
    $whatsappLink = \App\Services\WhatsAppService::defaultOrderLink();
}
?>
<!-- ===== PAGE INTRO ===== -->
<section class="py-4 bg-white border-bottom text-center">
    <div class="container">
        <p class="eyebrow">Nyali Restaurant</p>
        <h1>Authentic local foods in Nyali, Mombasa</h1>
    </div>
</section>

<!-- ===== HERO ===== -->
<section class="hero hero--home hero--minimal" aria-label="Restaurant hero" style="background-image: url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1800&q=80');">
    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="container hero-frame">
        <div class="hero-welcome-box">
            <p class="hero-eyebrow">Welcome to</p>
            <h1>Cheryne's Hotel</h1>
        </div>
    </div>
</section>

<section class="hero-cta-bar">
    <div class="container hero-cta-inner">
        <p class="hero-subtitle">Fresh Kenyan meals, warm service, easy ordering, and table reservations from Cheryne's.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('/reservations')) ?>">Reserve a Table</a>
            <a class="btn btn-outline-dark" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">Order via WhatsApp</a>
            <a class="btn btn-outline-dark" href="tel:0795879797" aria-label="Call Nyali Restaurant on 0795 879797">Call 0795 879797</a>
        </div>
    </div>
</section>

<!-- ===== HIGHLIGHTS / MENU ===== -->
<section class="section-band" aria-labelledby="highlights">
    <div class="container value-props" role="list">
        <div class="value-prop" role="listitem">
            <div class="value-icon" aria-hidden="true">✓</div>
            <div>
                <strong>Fresh Kenyan meals</strong>
                <p class="mb-0">Daily prep, bold local flavor.</p>
            </div>
        </div>
        <div class="value-prop" role="listitem">
            <div class="value-icon" aria-hidden="true">⏱</div>
            <div>
                <strong>Fast WhatsApp ordering</strong>
                <p class="mb-0">Order in minutes, get updates.</p>
            </div>
        </div>
        <div class="value-prop" role="listitem">
            <div class="value-icon" aria-hidden="true">📍</div>
            <div>
                <strong>Nyali, Mombasa</strong>
                <p class="mb-0">Convenient for locals and visitors.</p>
            </div>
        </div>
    </div>

    <div class="container section-heading" id="highlights">
        <p class="eyebrow">Menu highlights</p>
        <h2>Today at Cheryne's</h2>
    </div>

    <div class="container menu-grid" role="list">
        <?php if (empty($items)): ?>
            <div class="empty-state" role="listitem">
                <h3>Menu coming online</h3>
                <p>Call 0795 879797 or use WhatsApp to order while the menu database is being set up.</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                    $img = $item['image_url'] ?? '';
                    $name = $item['name'] ?? 'Unnamed Item';
                    $itemId = (int) ($item['id'] ?? 0);
                ?>
                <article class="menu-card" role="listitem">
                    <?php if ($img !== ''): ?>
                        <img src="<?= e($img) ?>" alt="<?= e($name) ?>" loading="lazy" width="400" height="300">
                    <?php else: ?>
                        <div style="width:100%;height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;">
                            No image
                        </div>
                    <?php endif; ?>
                    <div class="menu-card-body">
                        <span><?= e($item['category_name'] ?? 'Menu') ?></span>
                        <h3><?= e($name) ?></h3>
                        <p><?= e($item['description'] ?? '') ?></p>
                        <div class="menu-card-actions">
                            <strong><?= e(money($item['price'] ?? 0)) ?></strong>
                            <form action="<?= e(url('/cart/add')) ?>" method="post" class="add-to-cart-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_id" value="<?= e($itemId) ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button class="btn btn-sm btn-primary" type="submit">Add</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="section-band" aria-labelledby="testimonials">
    <div class="container section-heading">
        <p class="eyebrow">Testimonials</p>
        <h2 id="testimonials">What customers say</h2>
    </div>

    <div class="container testimonial-grid">
        <figure class="testimonial">
            <blockquote>&ldquo;The food is always fresh and the flavors are spot on. Booking is easy.&rdquo;</blockquote>
            <figcaption>&mdash; Nyali customer</figcaption>
        </figure>
        <figure class="testimonial">
            <blockquote>&ldquo;Quick WhatsApp ordering, polite service, and meals arrive hot.&rdquo;</blockquote>
            <figcaption>&mdash; Mombasa visitor</figcaption>
        </figure>
        <figure class="testimonial">
            <blockquote>&ldquo;A true local spot. Great portion sizes and consistent quality.&rdquo;</blockquote>
            <figcaption>&mdash; Regular diner</figcaption>
        </figure>
    </div>
</section>

<!-- ===== CONTACT STRIP ===== -->
<section class="contact-strip contact-strip--home" aria-labelledby="contact">
    <div class="container contact-strip-inner">
        <div>
            <p class="eyebrow">Nyali, Mombasa</p>
            <h2 id="contact">Reserve a table or order ahead</h2>
            <p class="contact-copy mb-0">For bookings, WhatsApp, or quick calls, we're ready to serve you.</p>
        </div>
        <div class="strip-actions">
            <a class="btn btn-primary" href="<?= e(url('/reservations')) ?>">Reserve</a>
            <a class="btn btn-outline-dark" href="<?= e(url('/menu')) ?>">Open menu</a>
        </div>
    </div>
</section>

<!-- ===== CONTACT METHODS ===== -->
<section class="section-band" aria-labelledby="contact-methods">
    <div class="container section-heading" style="margin-bottom:1rem;">
        <p class="eyebrow">Contact</p>
        <h2 id="contact-methods">Get in touch</h2>
    </div>

    <div class="container contact-grid" role="list">
        <div class="contact-card" role="listitem">
            <h3>Call us</h3>
            <p class="mb-0">0795 879797</p>
            <a class="btn btn-sm btn-primary" href="tel:0795879797">Call now</a>
        </div>
        <div class="contact-card" role="listitem">
            <h3>WhatsApp order</h3>
            <p class="mb-0">Fast ordering with updates.</p>
            <a class="btn btn-sm btn-primary" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
        </div>
        <div class="contact-card" role="listitem">
            <h3>Visit</h3>
            <p class="mb-0">Nyali, Mombasa</p>
            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/contact')) ?>">Contact details</a>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const button = form.querySelector('button');
            const originalText = button.textContent;
            button.textContent = 'Adding...';
            button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && data.ok === true) {
                    button.textContent = 'Added!';
                } else {
                    throw new Error(data.error || data.message || 'Failed to add item');
                }
            } catch (error) {
                console.error('Error:', error);
                button.textContent = 'Error';
            } finally {
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 2000);
            }
        });
    });
</script>
