<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;

use function App\Helpers\app_url;
use function App\Helpers\config;
use function App\Helpers\env;
use function App\Helpers\view;

final class HomeController
{
    public function index(): void
    {
        $items = [];
        try {
            $items = array_slice(MenuItem::all(['available' => true]), 0, 6);
        } catch (\Throwable) {
            $items = [];
        }

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => "Cheryne's",
            'telephone' => '0795 879797',
            'url' => app_url('/'),
            'menu' => app_url('/menu'),
            'areaServed' => 'Nyali, Mombasa',
            'servesCuisine' => ['Local', 'Kenyan'],
            'priceRange' => 'KSh',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Nyali',
                'addressRegion' => 'Mombasa',
                'addressCountry' => 'KE',
            ],
        ];

        view('home', [
            'title' => "Cheryne's — Authentic local foods in Nyali, Mombasa",
            'description' => "Cheryne's serves authentic local foods in Nyali, Mombasa. Order Kenyan meals, reserve a table, or call 0795 879797.",
            'items' => $items,
            'structuredData' => $structuredData,
        ]);
    }

    public function contact(): void
    {
        $mapsKey = (string) env('GOOGLE_MAPS_API_KEY', '');
        view('contact', [
            'title' => "Contact Cheryne's",
            'description' => "Call Cheryne's on 0795 879797 or find us in Nyali, Mombasa.",
            'mapsKey' => $mapsKey,
            'mapsLocation' => 'Nyali, Mombasa, Kenya',
        ]);
    }

    public function sitemap(): never
    {
        $urls = ['/', '/menu', '/reservations', '/contact'];
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($urls as $url) {
            echo '  <url><loc>' . htmlspecialchars(app_url($url), ENT_XML1) . '</loc><changefreq>weekly</changefreq></url>' . PHP_EOL;
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): never
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nSitemap: " . app_url('/sitemap.xml') . "\n";
        exit;
    }
}
