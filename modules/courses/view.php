<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('courses', 'view');

$id = (int)($_GET['id'] ?? 0);
$course = getRow("SELECT c.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name FROM courses c LEFT JOIN teachers t ON c.teacher_id = t.id WHERE c.id = ?", [$id]);
if (!$course) { setFlash('error', 'الدورة غير موجودة'); header('Location: list.php'); exit; }

$pageTitle = 'تفاصيل الدورة';
$enrollments = getRows("SELECT s.id, s.student_code, s.first_name, s.last_name, e.enrollment_date, e.status FROM enrollments e JOIN students s ON e.student_id = s.id WHERE e.course_id = ?", [$id]);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تفاصيل الدورة</h5></div>
        <div>
            <?php if (hasPermission('courses', 'edit')): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-info"><i class="bi bi-pencil"></i> تعديل</a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-outline-secondary ms-1"><i class="bi bi-arrow-right"></i> العودة</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="table-container mb-4">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">كود الدورة</th><td><?= htmlspecialchars($course['course_code']) ?></td></tr>
                        <tr><th>اسم الدورة</th><td><?= htmlspecialchars($course['course_name']) ?></td></tr>
                        <tr><th>المدرس</th><td><?= htmlspecialchars($course['teacher_name'] ?? '---') ?></td></tr>
                        <tr><th>الرسوم</th><td><?= number_format($course['fee'], 0) ?></td></tr>
                        <tr><th>الوصف</th><td><?= nl2br(htmlspecialchars($course['description'] ?? '---')) ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">الساعات المعتمدة</th><td><?= $course['credit_hours'] ?></td></tr>
                        <tr><th>الحد الأقصى</th><td><?= $course['max_students'] ?></td></tr>
                        <tr><th>المسجلون</th><td><?= count($enrollments) ?></td></tr>
                        <tr><th>الحالة</th><td><span class="badge bg-<?= $course['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $course['status'] === 'active' ? 'نشطة' : 'غير نشطة' ?></span></td></tr>
                        <tr><th>تاريخ الإنشاء</th><td><?= $course['created_at'] ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="table-container">
            <h6 class="mb-3">الطلاب المسجلين</h6>
            <table class="table table-hover">
                <thead><tr><th>الكود</th><th>الاسم</th><th>تاريخ التسجيل</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr><td colspan="4" class="text-center text-muted">لا يوجد طلاب مسجلين</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['student_code']) ?></td>
                            <td><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                            <td><?= $e['enrollment_date'] ?></td>
                            <td><span class="badge bg-<?= $e['status'] === 'active' ? 'success' : ($e['status'] === 'completed' ? 'info' : 'danger') ?>"><?= $e['status'] === 'active' ? 'نشط' : ($e['status'] === 'completed' ? 'مكتمل' : 'منسحب') ?></span></td>
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
