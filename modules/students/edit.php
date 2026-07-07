<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('students', 'edit');

$id = (int)($_GET['id'] ?? 0);
$student = getRow("SELECT * FROM students WHERE id = ?", [$id]);
if (!$student) { setFlash('error', 'الطالب غير موجود'); header('Location: list.php'); exit; }

$pageTitle = 'تعديل طالب';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE students SET student_code=?, first_name=?, last_name=?, father_name=?, mother_name=?, gender=?, date_of_birth=?, phone=?, email=?, address=?, enrollment_date=?, notes=? WHERE id=?";
    $params = [
        trim($_POST['student_code']), trim($_POST['first_name']), trim($_POST['last_name']),
        trim($_POST['father_name']), trim($_POST['mother_name']), $_POST['gender'],
        $_POST['date_of_birth'], trim($_POST['phone']), trim($_POST['email']),
        trim($_POST['address']), $_POST['enrollment_date'], trim($_POST['notes']), $id
    ];
    update($sql, $params);
    setFlash('success', 'تم تحديث بيانات الطالب');
    header('Location: list.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0 d-inline">تعديل طالب</h5>
        </div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود الطالب</label>
                        <input type="text" name="student_code" class="form-control" value="<?= htmlspecialchars($student['student_code']) ?>" readonly style="background:#f8f9fa;cursor:not-allowed;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم الأول</label>
                        <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($student['first_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم الأخير</label>
                        <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($student['last_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الأب</label>
                        <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($student['father_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الأم</label>
                        <input type="text" name="mother_name" class="form-control" value="<?= htmlspecialchars($student['mother_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجنس</label>
                        <select name="gender" class="form-select" required>
                            <option value="male" <?= $student['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
                            <option value="female" <?= $student['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= $student['date_of_birth'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ التسجيل</label>
                        <input type="date" name="enrollment_date" class="form-control" required value="<?= $student['enrollment_date'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($student['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ التغييرات</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
