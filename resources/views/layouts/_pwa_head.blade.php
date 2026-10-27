{{-- PWA: installable app metadata (shared by app and auth layouts) --}}
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#009ef7">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="الدعم الفني">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (err) {
                console.warn('Service worker registration failed', err);
            });
        });
    }
</script>
