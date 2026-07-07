<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('fees', 'edit');

$id = (int)($_GET['id'] ?? 0);
$fee = getRow("SELECT * FROM fees WHERE id = ?", [$id]);
if (!$fee) { setFlash('error', 'الرسم غير موجود'); header('Location: list.php'); exit; }

$pageTitle = 'تعديل رسم';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update("UPDATE fees SET fee_name=?, amount=?, description=?, frequency=? WHERE id=?", [
        trim($_POST['fee_name']), $_POST['amount'], trim($_POST['description']), $_POST['frequency'], $id
    ]);
    setFlash('success', 'تم تحديث الرسم');
    header('Location: list.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar"><h5 class="mb-0">تعديل رسم</h5></nav>
    <div class="page-content">
        <div class="table-container">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم الرسم</label>
                        <input type="text" name="fee_name" class="form-control" required value="<?= htmlspecialchars($fee['fee_name']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المبلغ</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required value="<?= $fee['amount'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الدورية</label>
                        <select name="frequency" class="form-select">
                            <option value="one_time" <?= $fee['frequency'] === 'one_time' ? 'selected' : '' ?>>مرة واحدة</option>
                            <option value="monthly" <?= $fee['frequency'] === 'monthly' ? 'selected' : '' ?>>شهري</option>
                            <option value="yearly" <?= $fee['frequency'] === 'yearly' ? 'selected' : '' ?>>سنوي</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($fee['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ التغييرات</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
