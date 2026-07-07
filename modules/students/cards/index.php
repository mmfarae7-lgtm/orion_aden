<?php
require_once __DIR__ . '/../../../config/app.php';
requireAuth();
requirePermission('student_cards', 'view');

$pageTitle = 'بطاقات الطلاب';

$students = getRows("
    SELECT s.*, sc.card_number, sc.issue_date, sc.expiry_date, sc.status as card_status
    FROM students s
    LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.status = 'active'
    WHERE s.status = 'active'
    ORDER BY s.first_name
");

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">بطاقات الطلاب</h5></div>
        <?php if (hasPermission('student_cards', 'create')): ?>
        <div><a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> إصدار بطاقة</a></div>
        <?php endif; ?>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr><th>#</th><th>الكود</th><th>الاسم</th><th>رقم البطاقة</th><th>تاريخ الإصدار</th><th>تاريخ الانتهاء</th><th>الحالة</th><th>الإجراءات</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="8" class="text-center text-muted">لا يوجد طلاب</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($s['student_code']) ?></td>
                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><?= htmlspecialchars($s['card_number'] ?? '---') ?></td>
                            <td><?= $s['issue_date'] ?? '---' ?></td>
                            <td><?= $s['expiry_date'] ?? '---' ?></td>
                            <td>
                                <?php if ($s['card_number']): ?>
                                    <span class="badge bg-<?= $s['card_status'] === 'active' ? 'success' : 'danger' ?>"><?= $s['card_status'] === 'active' ? 'نشطة' : 'منتهية' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">لا توجد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (hasPermission('student_cards', 'print')): ?>
                                <a href="print.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="bi bi-printer"></i></a>
                                <?php endif; ?>
                                <?php if (hasPermission('student_cards', 'create')): ?>
                                <a href="create.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-plus-circle"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
