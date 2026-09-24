<?php
require_once __DIR__ . '/../../config/app.php';
requirePermission('settings', 'view');

$pageTitle = 'الإعدادات';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
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
            <?= csrfField() ?>
            <?php foreach ($grouped as $group => $items): ?>
            <div class="table-container mb-4">
                <h6 class="mb-3">
                    <?= $group === 'general' ? 'إعدادات عامة' : ($group === 'backup' ? 'إعدادات النسخ الاحتياطي' : ($group === 'notification' ? 'إعدادات الإشعارات' : ($group === 'messaging' ? 'إعدادات الرسائل (واتساب/SMS)' : $group))) ?>
                </h6>
                <div class="row g-3">
                    <?php foreach ($items as $item):
                        $key = $item['setting_key'];
                        $labels = [
                            'school_name'       => 'اسم المدرسة',
                            'school_address'    => 'عنوان المدرسة',
                            'school_phone'      => 'هاتف المدرسة',
                            'school_email'      => 'البريد الإلكتروني',
                            'school_logo'       => 'شعار المدرسة (رابط)',
                            'academic_year'     => 'السنة الدراسية',
                            'currency'          => 'العملة',
                            'date_format'       => 'صيغة التاريخ',
                            'timezone'          => 'المنطقة الزمنية',
                            'language'          => 'اللغة',
                            'backup_auto'       => 'النسخ الاحتياطي التلقائي',
                            'backup_frequency'  => 'تكرار النسخ الاحتياطي',
                            'notify_email'      => 'البريد للإشعارات',
                            'whatsapp_number'   => 'رقم WhatsApp',
                            'sms_provider'      => 'مزود خدمة SMS',
                            'sms_api_key'       => 'مفتاح API لـ SMS',
                            'sms_sender_name'   => 'اسم المرسل (SMS)',
                            'sms_api_url'       => 'رابط API (اختياري)',
                            'twilio_account_sid' => 'Twilio Account SID',
                        ];
                        $label = $labels[$key] ?? $key;
                    ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars($label) ?></label>
                        <?php if ($key === 'sms_provider'): ?>
                        <select name="settings[sms_provider]" class="form-select">
                            <option value="twilio" <?= $item['setting_value'] === 'twilio' ? 'selected' : '' ?>>Twilio (عالمي)</option>
                            <option value="unifonic" <?= $item['setting_value'] === 'unifonic' ? 'selected' : '' ?>>Unifonic (الشرق الأوسط)</option>
                            <option value="4jawaly" <?= $item['setting_value'] === '4jawaly' ? 'selected' : '' ?>>4jawaly (السعودية)</option>
                        </select>
                        <?php else: ?>
                        <input type="<?= $key === 'sms_api_key' || $key === 'twilio_account_sid' ? 'password' : 'text' ?>" name="settings[<?= $key ?>]" class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>" <?= $key === 'sms_api_key' || $key === 'twilio_account_sid' ? 'autocomplete="off"' : '' ?>>
                        <?php endif; ?>
                        <?php if ($key === 'sms_api_key' || $key === 'twilio_account_sid'): ?>
                        <small class="text-muted">مشفر لحماية بياناتك</small>
                        <?php endif; ?>
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
