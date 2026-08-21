<?php
declare(strict_types=1);

use function App\Helpers\cart_count;
use function App\Helpers\config;
use function App\Helpers\current_user;
use function App\Helpers\csrf_token;
use function App\Helpers\e;
use function App\Helpers\flashes;
use function App\Helpers\is_admin;
use function App\Helpers\url;
use function App\Helpers\app_url;

// Safe config loading
$app = config('app');
$appName = $app['name'] ?? "Cheryne's Hotel";

// Safe variable fallbacks
$pageTitle = $title ?? $appName;
$metaDescription = $description ?? "Cheryne's Hotel serves authentic local foods in Nyali, Mombasa. Call 0795 879797.";
$structuredData = $structuredData ?? null;
$canonicalUrl = app_url($_SERVER['REQUEST_URI'] ?? '/');
$shareTitle = rawurlencode($pageTitle);
$shareUrl = rawurlencode($canonicalUrl);

$user = current_user();

// Safe WhatsApp link fallback
$whatsappLink = 'https://wa.me/254795879797?text=Hello%20Cheryne%27s%2C%20I%20would%20like%20to%20place%20an%20order';
if (class_exists(\App\Services\WhatsAppService::class)) {
    $whatsappLink = \App\Services\WhatsAppService::defaultOrderLink();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="keywords" content="Authentic local foods Nyali Mombasa, Best local food Nyali, Nyali restaurant, Cheryne's Hotel">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    
    <!-- FIX: Paths pointing to the public assets directory -->
    <link href="<?= e(url('/images/logo.png')) ?>" rel="icon" type="image/png">
    <link href="<?= e(url('/assets/css/bootstrap-local.css')) ?>" rel="stylesheet">
    <link href="<?= e(url('/assets/css/style.css')) ?>" rel="stylesheet">
    <?php if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin')): ?>
        <link href="<?= e(url('/assets/css/admin.css')) ?>" rel="stylesheet">
    <?php endif; ?>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('cherynes-theme');
                var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
    
    <?php if (!empty($structuredData)): ?>
        <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- FIX: Integrated your local logo asset file cleanly into the header -->
            <a class="navbar-brand brand-mark" href="<?= e(url('/')) ?>" aria-label="Cheryne's Hotel home">
                <img src="<?= e(url('/images/logo.png')) ?>" alt="Cheryne's Hotel Logo" style="height:40px; width:auto; margin-right:8px; vertical-align:middle;">
                <span>Cheryne's Hotel</span>
            </a>
            <div class="header-actions">
                <a class="btn btn-outline-reservation" href="<?= e(url('/reservations')) ?>">Reservation</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/menu')) ?>">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/reservations')) ?>">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/contact')) ?>">Contact</a></li>
                    <li class="nav-item"><a class="nav-link cart-link" href="<?= e(url('/cart')) ?>">Cart <span><?= cart_count() ?></span></a></li>
                    <li class="nav-item">
                        <button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark mode">🌙</button>
                    </li>
                    <?php if ($user): ?>
                        <?php if (is_admin()): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('/admin')) ?>">Admin</a></li>
                        <?php endif; ?>
                            <li class="nav-item">
                                <form action="<?= e(url('/auth/logout')) ?>" method="post" style="margin:0;">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="nav-link" style="background:none;border:0;cursor:pointer;font:inherit;padding:.5rem;">Logout</button>
                                </form>
                            </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/auth/login')) ?>">Login</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-dark" href="tel:0795879797" aria-label="Call Cheryne's Hotel on 0795 879797">0795 879797</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="main">
    <div class="container flash-wrap">
        <?php foreach (flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
                <!-- FIX: Allows purposeful formatting within notifications while protecting fallbacks -->
                <?= e($flash['message'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Where your separate page files load dynamically -->
    <?= $content ?? '' ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-cta">
            <div>
                <p class="footer-kicker">Come hungry, leave happy</p>
                <h2>Make your next meal local.</h2>
                <p>Order from the menu or reserve a table at <?= e($appName) ?>.</p>
            </div>
            <a class="footer-cta-button" href="<?= e(url('/menu')) ?>">Explore the menu <span aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="footer-grid">
            <div class="footer-brand">
                <a class="footer-logo" href="<?= e(url('/')) ?>" aria-label="<?= e($appName) ?> home">
                    <img src="<?= e(url('/images/logo.png')) ?>" alt="" width="48" height="48">
                    <span><?= e($appName) ?></span>
                </a>
                <p><?= e($metaDescription) ?></p>
                <p class="footer-trust"><span aria-hidden="true">&#10003;</span> Local Kenyan cuisine in Nyali</p>
            </div>

            <nav class="footer-column" aria-labelledby="footer-nav-title">
                <h2 id="footer-nav-title">Explore</h2>
                <a href="<?= e(url('/menu')) ?>">Menu</a>
                <a href="<?= e(url('/reservations')) ?>">Reservations</a>
                <a href="<?= e(url('/contact')) ?>">Contact</a>
                <a href="<?= e(url('/cart')) ?>">Your cart</a>
            </nav>

            <div class="footer-column" aria-labelledby="footer-contact-title">
                <h2 id="footer-contact-title">Contact</h2>
                <a href="tel:0795879797">0795 879797</a>
                <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">Order on WhatsApp</a>
                <span>Nyali, Mombasa, Kenya</span>
                <a href="<?= e(url('/contact')) ?>">Get directions <span aria-hidden="true">&rarr;</span></a>
            </div>

            <div class="footer-column" aria-labelledby="footer-share-title">
                <h2 id="footer-share-title">Stay connected</h2>
                <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e($shareUrl) ?>" target="_blank" rel="noopener">Share on Facebook</a>
                <a href="https://twitter.com/intent/tweet?url=<?= e($shareUrl) ?>&text=<?= e($shareTitle) ?>" target="_blank" rel="noopener">Share on X</a>
                <span class="footer-note">Fresh updates and local food moments.</span>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e($appName) ?>. All rights reserved.</span>
            <span>Secure ordering and reservations</span>
        </div>
    </div>
</footer>

<div class="floating-cta" aria-label="Quick contact actions">
    <a class="cta-whatsapp" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" aria-label="Order from Cheryne's Hotel on WhatsApp">WhatsApp</a>
    <a class="cta-call" href="tel:0795879797" aria-label="Call Cheryne's Hotel on 0795 879797">Call</a>
</div>

<!-- FIX: Javascript bindings updated to public root paths -->
<script src="<?= e(url('/assets/js/bootstrap-local.js')) ?>"></script>
<script src="<?= e(url('/assets/js/main.js')) ?>"></script>
</body>
</html>
