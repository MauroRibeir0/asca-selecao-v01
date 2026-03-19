// sw.js - Service Worker for PWA
importScripts('https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js');

self.addEventListener('install', function (event) {
    // Cache essential assets
    event.waitUntil(
        caches.open('asca-cache-v1').then(function (cache) {
            return cache.addAll([
                '/',
                './assets/css/style.css',
                './assets/js/app.js',
                './assets/img/logo.png'
            ]);
        })
    );
});

self.addEventListener('fetch', function (event) {
    event.respondWith(
        caches.match(event.request).then(function (response) {
            return response || fetch(event.request);
        })
    );
});
