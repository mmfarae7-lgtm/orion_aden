<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('fees', 'create');
$pageTitle = 'تسديد دفعة';

$students = getRows("SELECT id, student_code, first_name, last_name, phone FROM students WHERE status = 'active'");
$feesList = getRows("SELECT id, fee_name, amount FROM fees");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $receipt = 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $feeId = $_POST['fee_id'];
    $fee = getRow("SELECT amount FROM fees WHERE id = ?", [$feeId]);
    $total = $fee['amount'] ?? 0;
    $discount = (float)($_POST['discount'] ?? 0);
    $paid = (float)($_POST['amount_paid'] ?? 0);
    insert("INSERT INTO fee_payments (student_id, fee_id, amount_paid, discount, payment_date, payment_method, receipt_number, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)", [
        $_POST['student_id'], $feeId, $paid, $discount, $_POST['payment_date'],
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
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>
        <div class="row g-4">
            <div class="col-md-8">
                <div class="table-container">
                    <h6 class="mb-3"><i class="bi bi-cash"></i> تسديد دفعة جديدة</h6>
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الطالب <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select" id="studentSelect" required onchange="showStudentInfo(this)">
                                    <option value="">-- اختر الطالب --</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?= $s['id'] ?>" data-code="<?= $s['student_code'] ?>" data-name="<?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>" data-phone="<?= htmlspecialchars($s['phone'] ?? '') ?>"><?= htmlspecialchars($s['student_code'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نوع الرسم <span class="text-danger">*</span></label>
                                <select name="fee_id" class="form-select" id="feeSelect" required onchange="calcRemaining()">
                                    <option value="">-- اختر الرسم --</option>
                                    <?php foreach ($feesList as $f): ?>
                                        <option value="<?= $f['id'] ?>" data-amount="<?= $f['amount'] ?>"><?= htmlspecialchars($f['fee_name']) ?> (<?= number_format($f['amount'], 0) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Student Info -->
                        <div id="studentInfo" class="alert alert-light border mt-3 p-3" style="display:none">
                            <strong>معلومات الطالب:</strong>
                            <div class="row mt-2">
                                <div class="col-4"><small>الكود: <span id="infoCode"></span></small></div>
                                <div class="col-4"><small>الاسم: <span id="infoName"></span></small></div>
                                <div class="col-4"><small>الهاتف: <span id="infoPhone"></span></small></div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label">سعر الرسم</label>
                                <input type="text" class="form-control" id="displayAmount" readonly style="background:#f8f9fa;font-weight:bold" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">الخصم</label>
                                <input type="number" name="discount" class="form-control" id="discountInput" step="0.01" value="0" oninput="calcRemaining()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">المبلغ المدفوع <span class="text-danger">*</span></label>
                                <input type="number" name="amount_paid" class="form-control" id="paidInput" step="0.01" required oninput="calcRemaining()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">المتبقي</label>
                                <input type="text" class="form-control" id="remainingDisplay" readonly style="background:#f8f9fa;font-weight:bold;color:#dc3545" value="0">
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
                            <div class="col-md-12">
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
            <div class="col-md-4">
                <div class="table-container">
                    <h6 class="mb-3"><i class="bi bi-clock-history"></i> آخر الدفعات</h6>
                    <div id="recentPayments"><p class="text-muted small">اختر طالباً لعرض دفعاته</p></div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>

<script>
function showStudentInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        document.getElementById('studentInfo').style.display = 'block';
        document.getElementById('infoCode').textContent = opt.dataset.code;
        document.getElementById('infoName').textContent = opt.dataset.name;
        document.getElementById('infoPhone').textContent = opt.dataset.phone || '---';
        // Load recent payments
        fetch('<?= BASE_URL ?>/modules/fees/ajax_payments.php?student_id=' + opt.value)
            .then(r => r.text()).then(h => document.getElementById('recentPayments').innerHTML = h);
    } else {
        document.getElementById('studentInfo').style.display = 'none';
        document.getElementById('recentPayments').innerHTML = '<p class="text-muted small">اختر طالباً لعرض دفعاته</p>';
    }
}

function calcRemaining() {
    const feeSel = document.getElementById('feeSelect');
    const feeOpt = feeSel.options[feeSel.selectedIndex];
    const amount = feeOpt.value ? parseFloat(feeOpt.dataset.amount) : 0;
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const paid = parseFloat(document.getElementById('paidInput').value) || 0;
    const remaining = Math.max(0, amount - discount - paid);
    document.getElementById('displayAmount').value = amount.toLocaleString();
    document.getElementById('remainingDisplay').value = remaining.toLocaleString();
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
