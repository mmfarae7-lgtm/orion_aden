<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('fees', 'view');

$pageTitle = 'إدارة الرسوم';
$fees = getRows("SELECT * FROM fees ORDER BY created_at DESC");
$payments = getRows("SELECT fp.*, s.first_name, s.last_name, f.fee_name FROM fee_payments fp JOIN students s ON fp.student_id = s.id JOIN fees f ON fp.fee_id = f.id ORDER BY fp.created_at DESC LIMIT 20");

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">إدارة الرسوم</h5></div>
        <?php if (hasPermission('fees', 'create')): ?>
        <div><a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> إضافة رسم</a></div>
        <?php endif; ?>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <div class="table-container mb-4">
            <h6 class="mb-3">أنواع الرسوم</h6>
            <table class="table table-hover">
                <thead><tr><th>#</th><th>اسم الرسم</th><th>المبلغ</th><th>الدورية</th><th>الوصف</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php if (empty($fees)): ?><tr><td colspan="6" class="text-center text-muted">لا توجد رسوم</td></tr>
                    <?php else: ?>
                        <?php foreach ($fees as $i => $f): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($f['fee_name']) ?></td>
                            <td><?= number_format($f['amount'], 0) ?></td>
                            <td><?= $f['frequency'] === 'one_time' ? 'مرة واحدة' : ($f['frequency'] === 'monthly' ? 'شهري' : 'سنوي') ?></td>
                            <td><?= htmlspecialchars($f['description'] ?? '---') ?></td>
                            <td>
                                <?php if (hasPermission('fees', 'edit')): ?>
                                <a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (hasPermission('fees', 'delete')): ?>
                                <a href="delete.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل أنت متأكد؟"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="mb-0">آخر المدفوعات</h6>
                <?php if (hasPermission('fees', 'create')): ?>
                <a href="payment.php" class="btn btn-outline-success btn-sm"><i class="bi bi-cash"></i> تسديد دفعة</a>
                <?php endif; ?>
            </div>
            <table class="table table-hover">
                <thead><tr><th>الإيصال</th><th>الطالب</th><th>الرسم</th><th>المبلغ</th><th>التاريخ</th><th>طريقة الدفع</th></tr></thead>
                <tbody>
                    <?php if (empty($payments)): ?><tr><td colspan="6" class="text-center text-muted">لا توجد مدفوعات</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['receipt_number']) ?></td>
                            <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                            <td><?= htmlspecialchars($p['fee_name']) ?></td>
                            <td><?= number_format($p['amount_paid'], 0) ?></td>
                            <td><?= $p['payment_date'] ?></td>
                            <td><?= $p['payment_method'] === 'cash' ? 'نقداً' : ($p['payment_method'] === 'card' ? 'بطاقة' : ($p['payment_method'] === 'bank_transfer' ? 'تحويل بنكي' : 'شيك')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
