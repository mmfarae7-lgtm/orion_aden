<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('users', 'view');

$pageTitle = 'إدارة المستخدمين';
$users = getRows("SELECT * FROM users ORDER BY created_at DESC");

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0 d-inline">إدارة المستخدمين</h5>
        </div>
        <?php if (hasPermission('users', 'create')): ?>
        <div><a href="create.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> إضافة مستخدم</a></div>
        <?php endif; ?>
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
                <h6 class="mb-0">قائمة المستخدمين</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المستخدم</th>
                            <th>الاسم الكامل</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th>آخر دخول</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="8" class="text-center text-muted">لا يوجد مستخدمين</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $u): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['full_name']) ?></td>
                                <td><?= htmlspecialchars($u['email'] ?? '---') ?></td>
                                <td><span class="badge bg-<?= $u['role'] === 'super_admin' ? 'dark' : ($u['role'] === 'admin' ? 'primary' : ($u['role'] === 'accountant' ? 'success' : ($u['role'] === 'teacher' ? 'info' : ($u['role'] === 'reception' ? 'warning' : 'secondary')))) ?>"><?= getRoleLabel($u['role']) ?></span></td>
                                <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'نشط' : 'معطل' ?></span></td>
                                <td><?= $u['last_login'] ?? '---' ?></td>
                                <td>
                                    <?php if (hasPermission('users', 'edit') && $_SESSION['user_id'] != $u['id']): ?>
                                    <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('users', 'delete') && $_SESSION['user_id'] != $u['id']): ?>
                                    <a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل أنت متأكد من حذف هذا المستخدم؟"><i class="bi bi-trash"></i></a>
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
