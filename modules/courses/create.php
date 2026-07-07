<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('courses', 'create');

$pageTitle = 'إضافة دورة';
$teachers = getRows("SELECT id, first_name, last_name FROM teachers WHERE status = 'active'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        insert("INSERT INTO courses (course_code, course_name, description, teacher_id, credit_hours, fee, max_students) VALUES (?,?,?,?,?,?,?)", [
            trim($_POST['course_code']), trim($_POST['course_name']), trim($_POST['description']),
            $_POST['teacher_id'] ?: null, $_POST['credit_hours'], $_POST['fee'], $_POST['max_students']
        ]);
        setFlash('success', 'تم إضافة الدورة بنجاح');
        header('Location: list.php');
        exit;
    } catch (Exception $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">إضافة دورة</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود الدورة <span class="text-danger">*</span></label>
                        <input type="text" name="course_code" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الدورة <span class="text-danger">*</span></label>
                        <input type="text" name="course_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المدرس</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">-- اختر --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الساعات المعتمدة</label>
                        <input type="number" name="credit_hours" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرسوم</label>
                        <input type="number" name="fee" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحد الأقصى للطلاب</label>
                        <input type="number" name="max_students" class="form-control" value="30">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-info"><i class="bi bi-save"></i> حفظ</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
