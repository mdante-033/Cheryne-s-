const CACHE_NAME = 'cherynes-v1';
const ASSETS = [
    '/',
    '/assets/css/bootstrap-local.css',
    '/assets/css/style.css',
    '/images/logo.png',
    '/assets/js/bootstrap-local.js',
    '/assets/js/main.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
});
