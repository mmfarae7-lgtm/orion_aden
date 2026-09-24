<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('courses', 'edit');

$id = (int)($_GET['id'] ?? 0);
$course = getRow("SELECT * FROM courses WHERE id = ?", [$id]);
if (!$course) { setFlash('error', 'الدورة غير موجودة'); header('Location: list.php'); exit; }

$teachers = getRows("SELECT id, first_name, last_name FROM teachers WHERE status = 'active'");
$pageTitle = 'تعديل دورة';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    update("UPDATE courses SET course_code=?, course_name=?, description=?, teacher_id=?, credit_hours=?, fee=?, max_students=?, room=?, schedule_time=?, schedule_days=? WHERE id=?", [
        trim($_POST['course_code']), trim($_POST['course_name']), trim($_POST['description']),
        $_POST['teacher_id'] ?: null, $_POST['credit_hours'], $_POST['fee'], $_POST['max_students'],
        trim($_POST['room'] ?: ''), trim($_POST['schedule_time'] ?: ''), trim($_POST['schedule_days'] ?: ''), $id
    ]);
    setFlash('success', 'تم تحديث الدورة');
    header('Location: list.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تعديل دورة</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <form method="POST">
                <?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود الدورة</label>
                        <input type="text" name="course_code" class="form-control" required value="<?= htmlspecialchars($course['course_code']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الدورة</label>
                        <input type="text" name="course_name" class="form-control" required value="<?= htmlspecialchars($course['course_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المدرس</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">-- اختر --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $course['teacher_id'] == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القاعة</label>
                        <input type="text" name="room" class="form-control" value="<?= htmlspecialchars($course['room'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">وقت الدورة</label>
                        <input type="text" name="schedule_time" class="form-control" value="<?= htmlspecialchars($course['schedule_time'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">أيام الدورة</label>
                        <input type="text" name="schedule_days" class="form-control" value="<?= htmlspecialchars($course['schedule_days'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الساعات المعتمدة</label>
                        <input type="number" name="credit_hours" class="form-control" value="<?= $course['credit_hours'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرسوم</label>
                        <input type="number" name="fee" class="form-control" step="0.01" value="<?= $course['fee'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحد الأقصى</label>
                        <input type="number" name="max_students" class="form-control" value="<?= $course['max_students'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-info"><i class="bi bi-save"></i> حفظ التغييرات</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
