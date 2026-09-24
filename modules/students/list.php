<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('students', 'view');

$pageTitle = 'إدارة الطلاب';

// Search
$search = trim($_GET['search'] ?? '');
$searchField = $_GET['field'] ?? 'all';
$baseQuery = "FROM students s LEFT JOIN users u ON s.created_by = u.id";
if ($search) {
    if ($searchField === 'code') {
        $students = getRows("SELECT s.*, u.full_name AS creator_name $baseQuery WHERE s.student_code LIKE ? ORDER BY s.created_at DESC", ["%$search%"]);
    } elseif ($searchField === 'name') {
        $students = getRows("SELECT s.*, u.full_name AS creator_name $baseQuery WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name,' ',s.last_name) LIKE ? ORDER BY s.created_at DESC", ["%$search%", "%$search%", "%$search%"]);
    } elseif ($searchField === 'phone') {
        $students = getRows("SELECT s.*, u.full_name AS creator_name $baseQuery WHERE s.phone LIKE ? ORDER BY s.created_at DESC", ["%$search%"]);
    } else {
        $students = getRows("SELECT s.*, u.full_name AS creator_name $baseQuery WHERE s.student_code LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.phone LIKE ? ORDER BY s.created_at DESC", ["%$search%", "%$search%", "%$search%", "%$search%"]);
    }
} else {
    $students = getRows("SELECT s.*, u.full_name AS creator_name $baseQuery ORDER BY s.created_at DESC");
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h5 class="mb-0 d-inline">إدارة الطلاب</h5>
        </div>
        <div>
            <?php if (hasPermission('students', 'create')): ?>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> إضافة طالب</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/modules/reports/index.php?report=students_all&format=excel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i></a>
        </div>
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
                <h6 class="mb-0">قائمة الطلاب</h6>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="field" class="form-select form-select-sm" style="width:auto">
                        <option value="all" <?= $searchField === 'all' ? 'selected' : '' ?>>الكل</option>
                        <option value="code" <?= $searchField === 'code' ? 'selected' : '' ?>>الكود</option>
                        <option value="name" <?= $searchField === 'name' ? 'selected' : '' ?>>الاسم</option>
                        <option value="phone" <?= $searchField === 'phone' ? 'selected' : '' ?>>الهاتف</option>
                    </select>
                    <div class="input-group input-group-sm" style="width:250px">
                        <input type="text" name="search" class="form-control" placeholder="بحث..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                        <?php if ($search): ?>
                        <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="studentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصورة</th>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>الجنس</th>
                            <th>الهاتف</th>
                            <th>تاريخ التسجيل</th>
                            <th>الحالة</th>
                            <th>أضيف بواسطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>                            <td colspan="10" class="text-center text-muted">لا يوجد طلاب</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $i => $s): ?>
                                    <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if ($s['photo']): ?>
                                        <img src="<?= BASE_URL . '/' . $s['photo'] ?>" style="height:30px;width:30px;object-fit:cover;border-radius:50%">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($s['student_code']) ?></td>
                                <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                <td><?= $s['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                                <td><?= $s['enrollment_date'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $s['status'] === 'active' ? 'success' : ($s['status'] === 'inactive' ? 'secondary' : ($s['status'] === 'graduated' ? 'info' : 'danger')) ?>">
                                        <?= $s['status'] === 'active' ? 'نشط' : ($s['status'] === 'inactive' ? 'غير نشط' : ($s['status'] === 'graduated' ? 'متخرج' : 'موقوف')) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($s['creator_name'] ?? '---') ?></td>
                                <td>
                                    <?php if (hasPermission('students', 'edit')): ?>
                                    <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                    <?php if (hasPermission('students', 'delete')): ?>
                                    <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل أنت متأكد من حذف هذا الطالب؟"><i class="bi bi-trash"></i></a>
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
    <footer class="footer">
        <span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span>
    </footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
