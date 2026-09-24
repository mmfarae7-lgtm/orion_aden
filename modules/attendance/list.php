<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('attendance', 'view');

$pageTitle = 'إدارة الحضور';
$user = getCurrentUser();

if ($user['role'] === 'teacher') {
    $teacher = getRow("SELECT id FROM teachers WHERE email = ? OR CONCAT(first_name,' ',last_name) = ?", [$user['email'], $user['full_name']]);
    $teacherId = $teacher ? $teacher['id'] : 0;
    $courses = getRows("SELECT id, course_name, room, schedule_time FROM courses WHERE status = 'active' AND teacher_id = ?", [$teacherId]);
} else {
    $courses = getRows("SELECT id, course_name, room, schedule_time FROM courses WHERE status = 'active'");
}

$selectedCourse = $_GET['course_id'] ?? 0;
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$attendance = [];

$courseInfo = null;
if ($selectedCourse) {
    $courseInfo = getRow("SELECT c.*, CONCAT(t.first_name,' ',t.last_name) AS teacher_name FROM courses c LEFT JOIN teachers t ON c.teacher_id = t.id WHERE c.id = ?", [$selectedCourse]);
    $attendance = getRows("
        SELECT a.*, s.student_code, s.first_name, s.last_name
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.course_id = ? AND a.attendance_date = ?
        ORDER BY s.first_name
    ", [$selectedCourse, $selectedDate]);
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">الحضور</h5></div>
        <?php if (hasPermission('attendance', 'create')): ?>
        <div><a href="record.php" class="btn btn-warning"><i class="bi bi-calendar-plus"></i> تسجيل الحضور</a>
        <a href="print.php" class="btn btn-outline-primary"><i class="bi bi-printer"></i> طباعة</a></div>
        <?php endif; ?>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <div class="table-container mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الدورة</label>
                    <select name="course_id" class="form-select">
                        <option value="">-- اختر دورة --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCourse == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_name']) ?>
                                <?php if ($c['room']): ?> - <?= htmlspecialchars($c['room']) ?><?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">التاريخ</label>
                    <input type="date" name="date" class="form-control" value="<?= $selectedDate ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> عرض</button>
                </div>
            </form>
            <?php if ($courseInfo): ?>
            <div class="alert alert-light border mt-2 p-2 small">
                <strong><?= htmlspecialchars($courseInfo['course_name']) ?></strong>
                <?php if ($courseInfo['room']): ?> | قاعة: <?= htmlspecialchars($courseInfo['room']) ?><?php endif; ?>
                <?php if ($courseInfo['teacher_name']): ?> | مدرس: <?= htmlspecialchars($courseInfo['teacher_name']) ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($selectedCourse): ?>
        <div class="table-container">
            <h6 class="mb-3">سجل الحضور - <?= $selectedDate ?></h6>
            <table class="table table-hover">
                <thead><tr><th>الكود</th><th>الطالب</th><th>الحالة</th><th>ملاحظات</th></tr></thead>
                <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="4" class="text-center text-muted">لا توجد سجلات حضور لهذا اليوم</td></tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['student_code']) ?></td>
                            <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $a['status'] === 'present' ? 'success' : ($a['status'] === 'absent' ? 'danger' : ($a['status'] === 'late' ? 'warning' : 'info')) ?>">
                                    <?= $a['status'] === 'present' ? 'حاضر' : ($a['status'] === 'absent' ? 'غائب' : ($a['status'] === 'late' ? 'متأخر' : 'بعذر')) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
                $sumPresent = $sumAbsent = $sumLate = $sumExcused = 0;
                foreach ($attendance as $a) {
                    if ($a['status'] === 'present') $sumPresent++;
                    elseif ($a['status'] === 'absent') $sumAbsent++;
                    elseif ($a['status'] === 'late') $sumLate++;
                    elseif ($a['status'] === 'excused') $sumExcused++;
                }
            ?>
            <div class="mt-2">
                <span class="badge bg-success">حاضر: <?= $sumPresent ?></span>
                <span class="badge bg-danger">غائب: <?= $sumAbsent ?></span>
                <span class="badge bg-warning">متأخر: <?= $sumLate ?></span>
                <span class="badge bg-info">بعذر: <?= $sumExcused ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
