<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('fees', 'create');
$pageTitle = 'تسديد دفعة';

$students = getRows("SELECT id, student_code, first_name, last_name FROM students WHERE status = 'active'");
$fees = getRows("SELECT id, fee_name, amount FROM fees");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receipt = 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    insert("INSERT INTO fee_payments (student_id, fee_id, amount_paid, payment_date, payment_method, receipt_number, notes, created_by) VALUES (?,?,?,?,?,?,?,?)", [
        $_POST['student_id'], $_POST['fee_id'], $_POST['amount_paid'], $_POST['payment_date'],
        $_POST['payment_method'], $receipt, trim($_POST['notes']), $_SESSION['user_id']
    ]);
    setFlash('success', 'تم تسجيل الدفعة بنجاح - رقم الإيصال: ' . $receipt);
    header('Location: list.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تسديد دفعة</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الطالب <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['student_code'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نوع الرسم <span class="text-danger">*</span></label>
                        <select name="fee_id" class="form-select" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($fees as $f): ?>
                                <option value="<?= $f['id'] ?>" data-amount="<?= $f['amount'] ?>"><?= htmlspecialchars($f['fee_name']) ?> (<?= number_format($f['amount'], 0) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ المدفوع <span class="text-danger">*</span></label>
                        <input type="number" name="amount_paid" class="form-control" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الدفع</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">نقداً</option>
                            <option value="card">بطاقة</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="cheque">شيك</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-success"><i class="bi bi-cash"></i> تسديد</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
