<?php

use function App\Helpers\e;

$mapsKey = (string) ($mapsKey ?? '');
$mapsLocation = (string) ($mapsLocation ?? 'Nyali, Mombasa, Kenya');
?>
<section class="page-head">
    <div class="container">
        <p class="eyebrow">Nyali, Mombasa</p>
        <h1>Contact Cheryne's</h1>
        <p>Call 0795 879797 for orders, reservations, and local food in Nyali.</p>
    </div>
</section>
<section class="section-band">
    <div class="container contact-grid">
        <div class="form-panel">
            <h2>Direct contact</h2>
            <p><strong>Phone:</strong> <a href="tel:0795879797" aria-label="Call Cheryne's on 0795 879797">0795 879797</a></p>
            <p><strong>Area served:</strong> Nyali, Mombasa</p>
            <p><strong>Cuisine:</strong> Local, Kenyan</p>
        </div>
        <div class="map-panel">
            <?php if ($mapsKey !== ''): ?>
                <div id="google-map" class="google-map" aria-label="Map showing Cheryne's Hotel location"></div>
                <div class="map-actions">
                    <a class="btn btn-sm btn-outline-dark" href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($mapsLocation) ?>" target="_blank" rel="noopener">Get directions</a>
                </div>
                <script>
                    window.initMap = function () {
                        var position = { lat: -4.0446, lng: 39.6731 };
                        var map = new google.maps.Map(document.getElementById('google-map'), {
                            center: position,
                            zoom: 15,
                            gestureHandling: 'greedy',
                        });
                        new google.maps.Marker({
                            position: position,
                            map: map,
                            title: "Cheryne's Hotel",
                        });
                    };
                </script>
                <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= e($mapsKey) ?>&callback=initMap"></script>
            <?php else: ?>
                <div class="empty-state">
                    <h2>Map ready</h2>
                    <p>Add GOOGLE_MAPS_API_KEY in .env to show the Google Maps location map.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
