<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('courses', 'view');

$pageTitle = 'إدارة الدورات';
$courses = getRows("
    SELECT c.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name
    FROM courses c
    LEFT JOIN teachers t ON c.teacher_id = t.id
    ORDER BY c.created_at DESC
");

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">إدارة الدورات</h5></div>
        <?php if (hasPermission('courses', 'create')): ?>
        <div><a href="create.php" class="btn btn-info"><i class="bi bi-book-plus"></i> إضافة دورة</a></div>
        <?php endif; ?>
        <div><a href="<?= BASE_URL ?>/modules/reports/index.php?report=students_all&format=excel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i></a></div>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <div class="table-container">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="mb-0">قائمة الدورات</h6>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="بحث..." data-table="#coursesTable">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="coursesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الكود</th>
                            <th>اسم الدورة</th>
                            <th>المدرس</th>
                            <th>الرسوم</th>
                            <th>الحد الأقصى</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="8" class="text-center text-muted">لا توجد دورات</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $i => $c): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($c['course_code']) ?></td>
                                <td><?= htmlspecialchars($c['course_name']) ?></td>
                                <td><?= htmlspecialchars($c['teacher_name'] ?? '---') ?></td>
                                <td><?= number_format($c['fee'], 0) ?></td>
                                <td><?= $c['max_students'] ?></td>
                                <td><span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $c['status'] === 'active' ? 'نشطة' : 'غير نشطة' ?></span></td>
                                <td>
                                    <?php if (hasPermission('courses', 'edit')): ?>
                                    <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <a href="view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                    <?php if (hasPermission('courses', 'delete')): ?>
                                    <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل أنت متأكد من الحذف؟"><i class="bi bi-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
