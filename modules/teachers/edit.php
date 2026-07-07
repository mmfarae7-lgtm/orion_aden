<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('teachers', 'edit');

$id = (int)($_GET['id'] ?? 0);
$teacher = getRow("SELECT * FROM teachers WHERE id = ?", [$id]);
if (!$teacher) { setFlash('error', 'المدرس غير موجود'); header('Location: list.php'); exit; }

$pageTitle = 'تعديل مدرس';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update("UPDATE teachers SET teacher_code=?, first_name=?, last_name=?, gender=?, date_of_birth=?, phone=?, email=?, address=?, qualification=?, specialization=?, hire_date=?, salary=?, notes=? WHERE id=?", [
        trim($_POST['teacher_code']), trim($_POST['first_name']), trim($_POST['last_name']),
        $_POST['gender'], $_POST['date_of_birth'], trim($_POST['phone']), trim($_POST['email']),
        trim($_POST['address']), trim($_POST['qualification']), trim($_POST['specialization']),
        $_POST['hire_date'], $_POST['salary'], trim($_POST['notes']), $id
    ]);
    setFlash('success', 'تم تحديث بيانات المدرس');
    header('Location: list.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تعديل مدرس</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود المدرس</label>
                        <input type="text" name="teacher_code" class="form-control" required value="<?= htmlspecialchars($teacher['teacher_code']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم الأول</label>
                        <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($teacher['first_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم الأخير</label>
                        <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($teacher['last_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجنس</label>
                        <select name="gender" class="form-select">
                            <option value="male" <?= $teacher['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
                            <option value="female" <?= $teacher['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= $teacher['date_of_birth'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المؤهل</label>
                        <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($teacher['qualification'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التخصص</label>
                        <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($teacher['specialization'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($teacher['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التعيين</label>
                        <input type="date" name="hire_date" class="form-control" required value="<?= $teacher['hire_date'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الراتب</label>
                        <input type="number" name="salary" class="form-control" step="0.01" value="<?= $teacher['salary'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($teacher['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> حفظ التغييرات</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
