<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getSetting('school_name', 'نظام أوريون') ?> - <?= $pageTitle ?? 'لوحة التحكم' ?></title>
    <!-- PWA -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon-192.png?v=<?= ASSET_VER ?>">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/icon-192.png?v=<?= ASSET_VER ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Orion">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0d6efd">
    <link id="manifestLink" rel="manifest">
    <script>
    (function() {
        var origin = window.location.origin + '/';
        var manifest = {
            "name": "Orion - نظام إدارة المعاهد",
            "short_name": "Orion",
            "description": "نظام أوريون لإدارة المدارس والمعاهد",
            "start_url": origin + "login.php",
            "scope": origin,
            "display": "standalone",
            "orientation": "any",
            "background_color": "#ffffff",
            "theme_color": "#0d6efd",
            "dir": "rtl",
            "lang": "ar",
            "icons": [
                {"src": origin + "assets/img/icon-192.png?v=<?= ASSET_VER ?>", "sizes": "192x192", "type": "image/png"},
                {"src": origin + "assets/img/icon-512.png?v=<?= ASSET_VER ?>", "sizes": "512x512", "type": "image/png"},
                {"src": origin + "assets/img/icon-192.png?v=<?= ASSET_VER ?>", "sizes": "192x192", "type": "image/png", "purpose": "maskable"},
                {"src": origin + "assets/img/icon-512.png?v=<?= ASSET_VER ?>", "sizes": "512x512", "type": "image/png", "purpose": "maskable"}
            ]
        };
        try {
            var blob = new Blob([JSON.stringify(manifest)], {type: 'application/manifest+json'});
            document.getElementById('manifestLink').href = URL.createObjectURL(blob);
        } catch(e) {}
    })();
    </script>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= BASE_URL ?>/service-worker.js?v=6').catch(() => {});
        });
    }
    </script>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/tajawal/tajawal.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/Orion.png?v=<?= ASSET_VER ?>">
</head>
<body>
<div class="spinner-wrapper">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">جاري التحميل...</span>
    </div>
</div>
