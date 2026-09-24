<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('courses', 'view');

$id = (int)($_GET['id'] ?? 0);
$course = getRow("SELECT c.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name FROM courses c LEFT JOIN teachers t ON c.teacher_id = t.id WHERE c.id = ?", [$id]);
if (!$course) { setFlash('error', 'الدورة غير موجودة'); header('Location: list.php'); exit; }

$pageTitle = 'تفاصيل الدورة';

// Enroll student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll']) && hasPermission('courses', 'edit')) {
    requireCsrf();
    $studentId = (int)$_POST['student_id'];
    $existing = getRow("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", [$studentId, $id]);
    if (!$existing) {
        insert("INSERT INTO enrollments (student_id, course_id, enrollment_date, status, created_by) VALUES (?,?,?,?,?)", [$studentId, $id, date('Y-m-d'), 'active', $_SESSION['user_id']]);
        setFlash('success', 'تم تسجيل الطالب في الدورة');
    } else {
        setFlash('error', 'الطالب مسجل بالفعل');
    }
    header('Location: view.php?id=' . $id);
    exit;
}

// Remove student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove']) && hasPermission('courses', 'edit')) {
    requireCsrf();
    $studentId = (int)$_POST['student_id'];
    delete("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?", [$studentId, $id]);
    setFlash('success', 'تم حذف الطالب من الدورة');
    header('Location: view.php?id=' . $id);
    exit;
}

// Handle grade update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades']) && hasPermission('courses', 'edit')) {
    requireCsrf();
    $gradeFields = ['attendance_grade', 'vocabulary', 'writing', 'speaking', 'test', 'exam', 'total'];
    $studentIds = array_keys($_POST['attendance_grade'] ?? $_POST['vocabulary'] ?? []);
    foreach ($studentIds as $studentId) {
        $sets = [];
        $params = [];
        foreach ($gradeFields as $f) {
            $val = trim($_POST[$f][$studentId] ?? '');
            $sets[] = "$f = ?";
            $params[] = $val ?: null;
        }
        $params[] = (int)$studentId;
        $params[] = $id;
        update("UPDATE enrollments SET " . implode(', ', $sets) . " WHERE student_id = ? AND course_id = ?", $params);
    }
    setFlash('success', 'تم تحديث الدرجات');
    header('Location: view.php?id=' . $id);
    exit;
}

$enrollments = getRows("SELECT s.id, s.student_code, s.first_name, s.last_name, e.enrollment_date, e.status, e.grade, e.attendance_grade, e.vocabulary, e.writing, e.speaking, e.test, e.exam, e.total FROM enrollments e JOIN students s ON e.student_id = s.id WHERE e.course_id = ? ORDER BY s.first_name", [$id]);

// All non-enrolled students (for quick add)
$enrolledIds = array_column($enrollments, 'id');
if ($enrolledIds) {
    $placeholders = implode(',', array_fill(0, count($enrolledIds), '?'));
    $availableStudents = getRows("SELECT id, student_code, first_name, last_name, phone FROM students WHERE status = 'active' AND id NOT IN ($placeholders) ORDER BY first_name", $enrolledIds);
} else {
    $availableStudents = getRows("SELECT id, student_code, first_name, last_name, phone FROM students WHERE status = 'active' ORDER BY first_name");
}

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
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>

        <div class="table-container mb-4">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">كود الدورة</th><td><?= htmlspecialchars($course['course_code']) ?></td></tr>
                        <tr><th>اسم الدورة</th><td><?= htmlspecialchars($course['course_name']) ?></td></tr>
                        <tr><th>المدرس</th><td><?= htmlspecialchars($course['teacher_name'] ?? '---') ?></td></tr>
                        <tr><th>القاعة</th><td><?= htmlspecialchars($course['room'] ?? '---') ?></td></tr>
                        <tr><th>الوقت</th><td><?= htmlspecialchars($course['schedule_time'] ?? '---') ?></td></tr>
                        <tr><th>الأيام</th><td><?= htmlspecialchars($course['schedule_days'] ?? '---') ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width:150px;">الرسوم</th><td><?= number_format($course['fee'], 0) ?></td></tr>
                        <tr><th>الساعات المعتمدة</th><td><?= $course['credit_hours'] ?></td></tr>
                        <tr><th>الحد الأقصى</th><td><?= $course['max_students'] ?></td></tr>
                        <tr><th>المسجلون</th><td><?= count($enrollments) ?></td></tr>
                        <tr><th>الحالة</th><td><span class="badge bg-<?= $course['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $course['status'] === 'active' ? 'نشطة' : 'غير نشطة' ?></span></td></tr>
                        <tr><th>الوصف</th><td><?= nl2br(htmlspecialchars($course['description'] ?? '---')) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="bi bi-people"></i> الطلاب المسجلين <?= count($enrollments) ? '(' . count($enrollments) . ')' : '' ?></h6>
            </div>

            <?php if (hasPermission('courses', 'edit') && !empty($availableStudents)): ?>
            <div class="border rounded p-3 bg-light mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">الطلاب المتاحون (<?= count($availableStudents) ?>)</small>
                </div>
                <input type="text" id="filterAvailable" class="form-control form-control-sm mb-2" placeholder="ابحث بالاسم أو الكود..." oninput="filterAvailable()">
                <div class="table-responsive" style="max-height:250px; overflow-y:auto;">
                <table class="table table-sm table-bordered mb-0" id="availableTable">
                    <thead><tr><th>الكود</th><th>الاسم</th><th>الهاتف</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($availableStudents as $sr): ?>
                        <tr>
                            <td><?= htmlspecialchars($sr['student_code']) ?></td>
                            <td><?= htmlspecialchars($sr['first_name'] . ' ' . $sr['last_name']) ?></td>
                            <td><?= htmlspecialchars($sr['phone'] ?? '') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="student_id" value="<?= $sr['id'] ?>">
                                    <button type="submit" name="enroll" class="btn btn-sm btn-success"><i class="bi bi-plus"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <script>
            function filterAvailable() {
                const q = document.getElementById('filterAvailable').value.trim().toLowerCase();
                const rows = document.querySelectorAll('#availableTable tbody tr');
                rows.forEach(r => {
                    const text = r.cells[0].textContent.toLowerCase() + ' ' + r.cells[1].textContent.toLowerCase();
                    r.style.display = text.includes(q) ? '' : 'none';
                });
            }
            </script>
            <?php elseif (hasPermission('courses', 'edit') && empty($availableStudents)): ?>
                <p class="text-muted small">جميع الطلاب مسجلين في هذه الدورة</p>
            <?php endif; ?>

            <?php if (empty($enrollments)): ?>
                <p class="text-muted">لا يوجد طلاب مسجلين</p>
            <?php else: ?>
            <form method="POST">
                <?= csrfField() ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>الكود</th><th>الاسم</th><th>التسجيل</th><th>الحالة</th><th>Att</th><th>Voc</th><th>Wri</th><th>Spk</th><th>Test</th><th>Exam</th><th>Total</th><?php if (hasPermission('courses', 'edit')): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php foreach ($enrollments as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['student_code']) ?></td>
                            <td><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                            <td><?= $e['enrollment_date'] ?></td>
                            <td>
                                <span class="badge bg-<?= $e['status'] === 'active' ? 'success' : ($e['status'] === 'completed' ? 'info' : 'danger') ?>">
                                    <?= $e['status'] === 'active' ? 'نشط' : ($e['status'] === 'completed' ? 'مكتمل' : 'منسحب') ?>
                                </span>
                            </td>
                            <td><input type="text" name="attendance_grade[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['attendance_grade'] ?? '') ?>"></td>
                            <td><input type="text" name="vocabulary[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['vocabulary'] ?? '') ?>"></td>
                            <td><input type="text" name="writing[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['writing'] ?? '') ?>"></td>
                            <td><input type="text" name="speaking[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['speaking'] ?? '') ?>"></td>
                            <td><input type="text" name="test[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['test'] ?? '') ?>"></td>
                            <td><input type="text" name="exam[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['exam'] ?? '') ?>"></td>
                            <td><input type="text" name="total[<?= $e['id'] ?>]" class="form-control form-control-sm" style="width:50px" value="<?= htmlspecialchars($e['total'] ?? '') ?>"></td>
                            <?php if (hasPermission('courses', 'edit')): ?>
                            <td style="width:50px">
                                <form method="POST" style="display:inline" onsubmit="return confirm('حذف الطالب من الدورة؟')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="student_id" value="<?= $e['id'] ?>">
                                    <button type="submit" name="remove" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (hasPermission('courses', 'edit')): ?>
            <button type="submit" name="save_grades" class="btn btn-primary mt-2"><i class="bi bi-save"></i> حفظ الدرجات</button>
            <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
