<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('teachers', 'view');

$id = (int)($_GET['id'] ?? 0);
$teacher = getRow("SELECT * FROM teachers WHERE id = ?", [$id]);
if (!$teacher) { setFlash('error', 'المدرس غير موجود'); header('Location: list.php'); exit; }

$pageTitle = 'تفاصيل المدرس';

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تفاصيل المدرس</h5></div>
        <div>
            <?php if (hasPermission('teachers', 'edit')): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-success"><i class="bi bi-pencil"></i> تعديل</a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-outline-secondary ms-1"><i class="bi bi-arrow-right"></i> العودة</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">كود المدرس</th><td><?= htmlspecialchars($teacher['teacher_code']) ?></td></tr>
                        <tr><th>الاسم</th><td><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></td></tr>
                        <tr><th>الجنس</th><td><?= $teacher['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td></tr>
                        <tr><th>تاريخ الميلاد</th><td><?= $teacher['date_of_birth'] ?? '---' ?></td></tr>
                        <tr><th>المؤهل</th><td><?= htmlspecialchars($teacher['qualification'] ?? '---') ?></td></tr>
                        <tr><th>التخصص</th><td><?= htmlspecialchars($teacher['specialization'] ?? '---') ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">الهاتف</th><td><?= htmlspecialchars($teacher['phone'] ?? '---') ?></td></tr>
                        <tr><th>البريد</th><td><?= htmlspecialchars($teacher['email'] ?? '---') ?></td></tr>
                        <tr><th>العنوان</th><td><?= htmlspecialchars($teacher['address'] ?? '---') ?></td></tr>
                        <tr><th>تاريخ التعيين</th><td><?= $teacher['hire_date'] ?></td></tr>
                        <tr><th>الراتب</th><td><?= number_format($teacher['salary'], 0) ?></td></tr>
                        <tr><th>الحالة</th><td><span class="badge bg-<?= $teacher['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $teacher['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
