<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('attendance', 'create');

$pageTitle = 'تسجيل الحضور';
$user = getCurrentUser();

// If user is a teacher, restrict to their courses
if ($user['role'] === 'teacher') {
    $teacher = getRow("SELECT id FROM teachers WHERE email = ? OR CONCAT(first_name,' ',last_name) = ?", [$user['email'], $user['full_name']]);
    $teacherId = $teacher ? $teacher['id'] : 0;
    $courses = getRows("SELECT id, course_name, room, schedule_time FROM courses WHERE status = 'active' AND teacher_id = ?", [$teacherId]);
} else {
    $courses = getRows("SELECT id, course_name, room, schedule_time FROM courses WHERE status = 'active'");
}

$students = [];
$selectedCourse = $_POST['course_id'] ?? $_GET['course_id'] ?? 0;
$selectedDate = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');

// Load course info
$courseInfo = null;
if ($selectedCourse) {
    $courseInfo = getRow("SELECT c.*, CONCAT(t.first_name,' ',t.last_name) AS teacher_name FROM courses c LEFT JOIN teachers t ON c.teacher_id = t.id WHERE c.id = ?", [$selectedCourse]);
    $students = getRows("
        SELECT s.id, s.student_code, s.first_name, s.last_name
        FROM students s
        JOIN enrollments e ON s.id = e.student_id
        WHERE e.course_id = ? AND e.status = 'active' AND s.status = 'active'
        ORDER BY s.first_name
    ", [$selectedCourse]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    requireCsrf();
    $course_id = $_POST['course_id'];
    $date = $_POST['date'];
    $user_id = $_SESSION['user_id'];

    foreach ($_POST['attendance'] as $student_id => $status) {
        $notes = trim($_POST['notes'][$student_id] ?? '');
        $existing = getRow("SELECT id FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?", [$student_id, $course_id, $date]);
        if ($existing) {
            update("UPDATE attendance SET status=?, notes=?, recorded_by=? WHERE id=?", [$status, $notes, $user_id, $existing['id']]);
        } else {
            insert("INSERT INTO attendance (student_id, course_id, attendance_date, status, notes, recorded_by) VALUES (?,?,?,?,?,?)", [$student_id, $course_id, $date, $status, $notes, $user_id]);
        }
    }
    setFlash('success', 'تم تسجيل الحضور بنجاح');
    header('Location: list.php?course_id=' . $course_id . '&date=' . $date);
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تسجيل الحضور</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الدورة <span class="text-danger">*</span></label>
                    <select name="course_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- اختر دورة --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCourse == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_name']) ?>
                                <?php if ($c['room']): ?> - <?= htmlspecialchars($c['room']) ?><?php endif; ?>
                                <?php if ($c['schedule_time']): ?> (<?= htmlspecialchars($c['schedule_time']) ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" required value="<?= $selectedDate ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning"><i class="bi bi-eye"></i> عرض الطلاب</button>
                </div>
            </form>
            <?php if ($courseInfo): ?>
            <div class="alert alert-light border mt-2 p-2 small">
                <strong><?= htmlspecialchars($courseInfo['course_name']) ?></strong>
                <?php if ($courseInfo['room']): ?> | قاعة: <?= htmlspecialchars($courseInfo['room']) ?><?php endif; ?>
                <?php if ($courseInfo['schedule_time']): ?> | الوقت: <?= htmlspecialchars($courseInfo['schedule_time']) ?><?php endif; ?>
                <?php if ($courseInfo['teacher_name']): ?> | مدرس: <?= htmlspecialchars($courseInfo['teacher_name']) ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($students)): ?>
        <div class="table-container">
            <h6 class="mb-3">تسجيل حضور الطلاب - <?= count($students) ?> طالب</h6>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="course_id" value="<?= $selectedCourse ?>">
                <input type="hidden" name="date" value="<?= $selectedDate ?>">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>#</th><th>الكود</th><th>الاسم</th><th>الحالة</th><th>ملاحظات</th></tr></thead>
                        <tbody>
                            <?php foreach ($students as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($s['student_code']) ?></td>
                                <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" id="present_<?= $s['id'] ?>" value="present" checked>
                                        <label class="btn btn-outline-success" for="present_<?= $s['id'] ?>">حاضر</label>
                                        <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" id="absent_<?= $s['id'] ?>" value="absent">
                                        <label class="btn btn-outline-danger" for="absent_<?= $s['id'] ?>">غائب</label>
                                        <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" id="late_<?= $s['id'] ?>" value="late">
                                        <label class="btn btn-outline-warning" for="late_<?= $s['id'] ?>">متأخر</label>
                                        <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" id="excused_<?= $s['id'] ?>" value="excused">
                                        <label class="btn btn-outline-info" for="excused_<?= $s['id'] ?>">بعذر</label>
                                    </div>
                                </td>
                                <td><input type="text" name="notes[<?= $s['id'] ?>]" class="form-control form-control-sm" placeholder="ملاحظة"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-warning mt-3"><i class="bi bi-save"></i> حفظ الحضور</button>
            </form>
        </div>
        <?php elseif ($selectedCourse): ?>
            <div class="alert alert-info">لا يوجد طلاب مسجلين في هذه الدورة</div>
        <?php endif; ?>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
