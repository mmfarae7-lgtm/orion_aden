<?php
require_once __DIR__ . '/../../../config/app.php';
requireAuth();
requirePermission('student_cards', 'view');

$pageTitle = 'بطاقات الطلاب';

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo_upload']) && isset($_POST['student_id'])) {
    requireCsrf();
    requirePermission('student_cards', 'create');
    $sid = (int)$_POST['student_id'];
    $file = $_FILES['photo_upload'];
    $valid = validateUpload($_FILES['photo_upload'], $GLOBALS['allowedPhotoTypes']);
    if (!$valid['valid']) { setFlash('error', $valid['error']); header('Location: index.php'); exit; }
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            $dir = __DIR__ . '/../../../assets/uploads/students/';
            $filename = 'stu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dir . $filename);
            update("UPDATE students SET photo = ? WHERE id = ?", ['assets/uploads/students/' . $filename, $sid]);
            setFlash('success', 'تم رفع الصورة بنجاح');
        }
    }
    header('Location: index.php');
    exit;
}

$students = getRows("
    SELECT s.*, sc.card_number, sc.issue_date, sc.expiry_date, sc.status as card_status
    FROM students s
    LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.status = 'active'
    WHERE s.status = 'active'
    ORDER BY s.first_name
");

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">بطاقات الطلاب</h5></div>
        <?php if (hasPermission('student_cards', 'create')): ?>
        <div><a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> إصدار بطاقة</a></div>
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
            <table class="table table-hover">
                <thead>
                    <tr><th>#</th><th>الصورة</th><th>الكود</th><th>الاسم</th><th>رقم البطاقة</th><th>تاريخ الإصدار</th><th>تاريخ الانتهاء</th><th>الحالة</th><th>الإجراءات</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="9" class="text-center text-muted">لا يوجد طلاب</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if ($s['photo']): ?>
                                    <img src="<?= BASE_URL . '/' . $s['photo'] ?>" style="height:35px;width:35px;object-fit:cover;border-radius:50%">
                                <?php endif; ?>
                                <form method="POST" enctype="multipart/form-data" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                    <label class="btn btn-sm btn-outline-primary" style="cursor:pointer;font-size:0.7rem">
                                        <i class="bi bi-camera"></i>
                                        <input type="file" name="photo_upload" accept="image/*" onchange="this.form.submit()" style="display:none">
                                    </label>
                                </form>
                            </td>
                            <td><?= htmlspecialchars($s['student_code']) ?></td>
                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><?= htmlspecialchars($s['card_number'] ?? '---') ?></td>
                            <td><?= $s['issue_date'] ?? '---' ?></td>
                            <td><?= $s['expiry_date'] ?? '---' ?></td>
                            <td>
                                <?php if ($s['card_number']): ?>
                                    <span class="badge bg-<?= $s['card_status'] === 'active' ? 'success' : 'danger' ?>"><?= $s['card_status'] === 'active' ? 'نشطة' : 'منتهية' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">لا توجد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (hasPermission('student_cards', 'print')): ?>
                                <a href="print.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="bi bi-printer"></i></a>
                                <?php endif; ?>
                                <?php if (hasPermission('student_cards', 'create')): ?>
                                <a href="create.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-plus-circle"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
