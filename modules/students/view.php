<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('students', 'view');

$id = (int)($_GET['id'] ?? 0);
$student = getRow("SELECT * FROM students WHERE id = ?", [$id]);
if (!$student) { setFlash('error', 'الطالب غير موجود'); header('Location: list.php'); exit; }

$pageTitle = 'تفاصيل الطالب';

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0 d-inline">تفاصيل الطالب</h5>
        </div>
        <div>
            <?php if (hasPermission('students', 'edit')): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> تعديل</a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">كود الطالب</th><td><?= htmlspecialchars($student['student_code']) ?></td></tr>
                        <tr><th>الاسم</th><td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td></tr>
                        <tr><th>اسم الأب</th><td><?= htmlspecialchars($student['father_name'] ?? '---') ?></td></tr>
                        <tr><th>اسم الأم</th><td><?= htmlspecialchars($student['mother_name'] ?? '---') ?></td></tr>
                        <tr><th>الجنس</th><td><?= $student['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td></tr>
                        <tr><th>تاريخ الميلاد</th><td><?= $student['date_of_birth'] ?? '---' ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">الهاتف</th><td><?= htmlspecialchars($student['phone'] ?? '---') ?></td></tr>
                        <tr><th>البريد</th><td><?= htmlspecialchars($student['email'] ?? '---') ?></td></tr>
                        <tr><th>العنوان</th><td><?= htmlspecialchars($student['address'] ?? '---') ?></td></tr>
                        <tr><th>تاريخ التسجيل</th><td><?= $student['enrollment_date'] ?></td></tr>
                        <tr><th>الحالة</th><td><span class="badge bg-<?= $student['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $student['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td></tr>
                        <tr><th>ملاحظات</th><td><?= nl2br(htmlspecialchars($student['notes'] ?? '---')) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
