<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('students', 'view');

$id = (int)($_GET['id'] ?? 0);
$student = getRow("SELECT * FROM students WHERE id = ?", [$id]);
if (!$student) { setFlash('error', 'الطالب غير موجود'); header('Location: list.php'); exit; }

$pageTitle = 'تفاصيل الطالب';

// Enroll in course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll']) && hasPermission('students', 'edit')) {
    requireCsrf();
    $courseId = (int)$_POST['course_id'];
    $existing = getRow("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", [$id, $courseId]);
    if (!$existing) {
        insert("INSERT INTO enrollments (student_id, course_id, enrollment_date, status, created_by) VALUES (?,?,?,?,?)", [$id, $courseId, date('Y-m-d'), 'active', $_SESSION['user_id']]);
        setFlash('success', 'تم تسجيل الطالب في الدورة');
    } else {
        setFlash('error', 'الطالب مسجل بالفعل في هذه الدورة');
    }
    header('Location: view.php?id=' . $id);
    exit;
}

// Remove from course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove']) && hasPermission('students', 'edit')) {
    requireCsrf();
    $courseId = (int)$_POST['course_id'];
    delete("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?", [$id, $courseId]);
    setFlash('success', 'تم حذف الطالب من الدورة');
    header('Location: view.php?id=' . $id);
    exit;
}

$enrollments = getRows("SELECT e.*, c.course_name, c.course_code FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = ?", [$id]);
$enrolledIds = array_column($enrollments, 'course_id');
$availableCourses = [];
if (hasPermission('students', 'edit')) {
    if ($enrolledIds) {
        $placeholders = implode(',', array_fill(0, count($enrolledIds), '?'));
        $availableCourses = getRows("SELECT id, course_code, course_name FROM courses WHERE status = 'active' AND id NOT IN ($placeholders)", $enrolledIds);
    } else {
        $availableCourses = getRows("SELECT id, course_code, course_name FROM courses WHERE status = 'active'");
    }
}

$payments = getRows("SELECT fp.*, f.fee_name FROM fee_payments fp LEFT JOIN fees f ON fp.fee_id = f.id WHERE fp.student_id = ? ORDER BY fp.created_at DESC", [$id]);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تفاصيل الطالب</h5></div>
        <div>
            <?php if (hasPermission('students', 'edit')): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> تعديل</a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="table-container text-center">
                    <?php if ($student['photo']): ?>
                        <img src="<?= BASE_URL . '/' . $student['photo'] ?>" style="height:150px;width:150px;object-fit:cover;border-radius:50%;border:3px solid #dee2e6">
                    <?php else: ?>
                        <div style="height:150px;width:150px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;margin:auto;font-size:3rem;color:#aaa"><i class="bi bi-person"></i></div>
                    <?php endif; ?>
                    <h5 class="mt-3"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($student['student_code']) ?></p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="table-container">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th style="width:150px;">اسم الأب</th><td><?= htmlspecialchars($student['father_name'] ?? '---') ?></td></tr>
                                <tr><th>اسم الأم</th><td><?= htmlspecialchars($student['mother_name'] ?? '---') ?></td></tr>
                                <tr><th>الجنس</th><td><?= $student['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td></tr>
                                <tr><th>تاريخ الميلاد</th><td><?= $student['date_of_birth'] ?? '---' ?></td></tr>
                                <tr><th>الهاتف</th><td><?= htmlspecialchars($student['phone'] ?? '---') ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>البريد</th><td><?= htmlspecialchars($student['email'] ?? '---') ?></td></tr>
                                <tr><th>العنوان</th><td><?= htmlspecialchars($student['address'] ?? '---') ?></td></tr>
                                <tr><th>تاريخ التسجيل</th><td><?= $student['enrollment_date'] ?></td></tr>
                                <tr><th>الحالة</th><td><span class="badge bg-<?= $student['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $student['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td></tr>
                                <tr><th>ملاحظات</th><td><?= nl2br(htmlspecialchars($student['notes'] ?? '---')) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="table-container mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-book"></i> الدورات المسجل فيها</h6>
                        <?php if (hasPermission('students', 'edit') && !empty($availableCourses)): ?>
                        <button class="btn btn-sm btn-success" onclick="toggleEnrollForm()"><i class="bi bi-plus"></i> تسجيل في دورة</button>
                        <?php endif; ?>
                    </div>

                    <?php if (hasPermission('students', 'edit') && !empty($availableCourses)): ?>
                    <div id="enrollForm" style="display:none" class="border rounded p-3 bg-light mb-3">
                        <form method="POST" class="row g-2">
                            <?= csrfField() ?>
                            <div class="col-8">
                                <select name="course_id" class="form-select form-select-sm" required>
                                    <option value="">-- اختر دورة --</option>
                                    <?php foreach ($availableCourses as $ac): ?>
                                    <option value="<?= $ac['id'] ?>"><?= htmlspecialchars($ac['course_code'] . ' - ' . $ac['course_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" name="enroll" class="btn btn-sm btn-success w-100"><i class="bi bi-check"></i> تسجيل</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($enrollments)): ?>
                        <p class="text-muted">غير مسجل في أي دورة</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>الدورة</th><th>تاريخ التسجيل</th><th>الحالة</th><th>الدرجة</th><?php if (hasPermission('students', 'edit')): ?><th></th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['course_name']) ?> (<?= htmlspecialchars($e['course_code']) ?>)</td>
                                <td><?= $e['enrollment_date'] ?></td>
                                <td><span class="badge bg-<?= $e['status'] === 'active' ? 'success' : ($e['status'] === 'completed' ? 'info' : 'danger') ?>"><?= $e['status'] === 'active' ? 'نشط' : ($e['status'] === 'completed' ? 'مكتمل' : 'منسحب') ?></span></td>
                                <td><?= htmlspecialchars($e['grade'] ?? '---') ?></td>
                                <?php if (hasPermission('students', 'edit')): ?>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('حذف الطالب من الدورة؟')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="course_id" value="<?= $e['course_id'] ?>">
                                        <button type="submit" name="remove" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <div class="table-container mt-3">
                    <h6 class="mb-3"><i class="bi bi-cash"></i> سجل الدفعات</h6>
                    <?php if (empty($payments)): ?>
                        <p class="text-muted">لا توجد دفعات</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>الإيصال</th><th>الرسم</th><th>المبلغ</th><th>الخصم</th><th>التاريخ</th><th>طريقة الدفع</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['receipt_number']) ?></td>
                                <td><?= htmlspecialchars($p['fee_name'] ?? '---') ?></td>
                                <td><?= number_format($p['amount_paid'], 0) ?></td>
                                <td><?= number_format($p['discount'] ?? 0, 0) ?></td>
                                <td><?= $p['payment_date'] ?></td>
                                <td><?= $p['payment_method'] === 'cash' ? 'نقداً' : ($p['payment_method'] === 'card' ? 'بطاقة' : ($p['payment_method'] === 'bank_transfer' ? 'تحويل بنكي' : 'شيك')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<script>
function toggleEnrollForm() {
    const div = document.getElementById('enrollForm');
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
