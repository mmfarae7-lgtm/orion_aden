<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getSetting('school_name', 'نظام أوريون') ?> - <?= $pageTitle ?? 'لوحة التحكم' ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/tajawal/tajawal.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/Orion.png">
</head>
<body>
<div class="spinner-wrapper">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">جاري التحميل...</span>
    </div>
</div>
