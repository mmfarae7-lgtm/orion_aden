<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('teachers', 'view');

$pageTitle = 'إدارة المدرسين';
$teachers = getRows("SELECT t.*, u.full_name AS creator_name FROM teachers t LEFT JOIN users u ON t.created_by = u.id ORDER BY t.created_at DESC");
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0 d-inline">إدارة المدرسين</h5>
        </div>
        <?php if (hasPermission('teachers', 'create')): ?>
        <div><a href="create.php" class="btn btn-success"><i class="bi bi-person-plus"></i> إضافة مدرس</a></div>
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
                <h6 class="mb-0">قائمة المدرسين</h6>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="بحث..." data-table="#teachersTable">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="teachersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>التخصص</th>
                            <th>الهاتف</th>
                            <th>تاريخ التعيين</th>
                            <th>الحالة</th>
                            <th>أضيف بواسطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="9" class="text-center text-muted">لا يوجد مدرسين</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $i => $t): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($t['teacher_code']) ?></td>
                                <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                                <td><?= htmlspecialchars($t['specialization'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($t['phone'] ?? '---') ?></td>
                                <td><?= $t['hire_date'] ?></td>
                                <td><span class="badge bg-<?= $t['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $t['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($t['creator_name'] ?? '---') ?></td>
                                <td>
                                    <?php if (hasPermission('teachers', 'edit')): ?>
                                    <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <a href="view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                    <?php if (hasPermission('teachers', 'delete')): ?>
                                    <a href="delete.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل أنت متأكد من الحذف؟"><i class="bi bi-trash"></i></a>
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
