<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('attendance', 'view');

$pageTitle = 'إدارة الحضور';
$courses = getRows("SELECT id, course_name FROM courses WHERE status = 'active'");

$selectedCourse = $_GET['course_id'] ?? 0;
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$attendance = [];

if ($selectedCourse) {
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
        <div><a href="record.php" class="btn btn-warning"><i class="bi bi-calendar-plus"></i> تسجيل الحضور</a></div>
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
                            <option value="<?= $c['id'] ?>" <?= $selectedCourse == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">التاريخ</label>
                    <input type="date" name="date" class="form-control" value="<?= $selectedDate ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> عرض</button>
                </div>
            </form>
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
        </div>
        <?php endif; ?>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
