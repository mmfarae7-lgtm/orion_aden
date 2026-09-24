<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('messaging', 'send');

$pageTitle = 'سجل الرسائل';

// Get message logs if table exists
$logs = [];
try {
    $logs = getRows("SELECT ml.*, s.first_name, s.last_name FROM message_log ml LEFT JOIN students s ON ml.student_id = s.id ORDER BY ml.created_at DESC LIMIT 100");
} catch (Exception $e) {
    // Table might not exist yet
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline"><i class="bi bi-clock-history"></i> سجل الرسائل</h5></div>
        <div>
            <a href="index.php" class="btn btn-outline-info btn-sm"><i class="bi bi-send"></i> إرسال رسالة</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <?php if (empty($logs)): ?>
                <p class="text-muted">لا توجد رسائل مرسلة بعد</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>التاريخ</th><th>الطالب</th><th>الرسالة</th><th>الوسيلة</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= $l['created_at'] ?></td>
                            <td><?= htmlspecialchars(($l['first_name'] ?? '') . ' ' . ($l['last_name'] ?? '')) ?></td>
                            <td><small><?= htmlspecialchars(mb_substr($l['message'] ?? '', 0, 50)) ?>...</small></td>
                            <td><span class="badge bg-<?= $l['method'] === 'whatsapp' ? 'success' : 'primary' ?>"><?= $l['method'] ?></span></td>
                            <td><span class="badge bg-<?= ($l['status'] ?? 'sent') === 'sent' ? 'success' : 'danger' ?>"><?= ($l['status'] ?? 'sent') === 'sent' ? 'مرسلة' : 'فشل' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
