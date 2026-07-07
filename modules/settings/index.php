<?php
require_once __DIR__ . '/../../config/app.php';
requirePermission('settings', 'view');

$pageTitle = 'الإعدادات';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('settings', 'edit');
    foreach ($_POST['settings'] as $key => $value) {
        $existing = getRow("SELECT id FROM settings WHERE setting_key = ?", [$key]);
        if ($existing) {
            update("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        } else {
            insert("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)", [$key, $value]);
        }
    }
    setFlash('success', 'تم حفظ الإعدادات بنجاح');
    header('Location: index.php');
    exit;
}

$settings = getRows("SELECT * FROM settings ORDER BY group_name, setting_key");
$grouped = [];
foreach ($settings as $s) {
    $grouped[$s['group_name']][] = $s;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar"><h5 class="mb-0">الإعدادات</h5></nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <form method="POST">
            <?php foreach ($grouped as $group => $items): ?>
            <div class="table-container mb-4">
                <h6 class="mb-3">
                    <?= $group === 'general' ? 'إعدادات عامة' : ($group === 'backup' ? 'إعدادات النسخ الاحتياطي' : ($group === 'notification' ? 'إعدادات الإشعارات' : $group)) ?>
                </h6>
                <div class="row g-3">
                    <?php foreach ($items as $item): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars($item['setting_key']) ?></label>
                        <input type="text" name="settings[<?= $item['setting_key'] ?>]" class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ الإعدادات</button>
        </form>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
