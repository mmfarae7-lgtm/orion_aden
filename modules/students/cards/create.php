<?php
require_once __DIR__ . '/../../../config/app.php';
requireAuth();
requirePermission('student_cards', 'create');

$pageTitle = 'إصدار بطاقة طالب';
$students = getRows("SELECT id, student_code, first_name, last_name FROM students WHERE status = 'active' ORDER BY first_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = trim($_POST['card_number']);
    $student_id = $_POST['student_id'];
    $expiry = $_POST['expiry_date'];

    try {
        insert("INSERT INTO student_cards (student_id, card_number, issue_date, expiry_date) VALUES (?,?,?,?)", [
            $student_id, $card_number, date('Y-m-d'), $expiry
        ]);
        setFlash('success', 'تم إصدار البطاقة بنجاح');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">إصدار بطاقة</h5></div>
        <div><a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الطالب <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($_GET['student_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['student_code'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم البطاقة <span class="text-danger">*</span></label>
                        <input type="text" name="card_number" class="form-control" required value="CRD-<?= date('Y') ?>-<?= strtoupper(substr(uniqid(), -6)) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الانتهاء</label>
                        <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+4 years')) ?>">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> إصدار</button>
                    <a href="index.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
