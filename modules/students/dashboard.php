<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SESSION['role'] !== 'viewer' || !isset($_SESSION['student_id'])) {
    header('Location: portal.php');
    exit;
}

$studentId = $_SESSION['student_id'];
$student = getRow("SELECT * FROM students WHERE id = ?", [$studentId]);
if (!$student) { unset($_SESSION['student_id']); header('Location: portal.php'); exit; }

$pageTitle = 'ملف الطالب';

// Attendance
$attendance = getRows("SELECT a.*, c.course_name FROM attendance a JOIN courses c ON a.course_id = c.id WHERE a.student_id = ? ORDER BY a.attendance_date DESC LIMIT 50", [$studentId]);

// Enrollments with grades
$enrollments = getRows("SELECT e.id, e.enrollment_date, e.status, e.attendance_grade, e.vocabulary, e.writing, e.speaking, e.test, e.exam, e.total, c.course_name, c.course_code FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? AND e.status != 'dropped'", [$studentId]);

include __DIR__ . '/../../includes/header.php';
?>
<style>.no-sidebar .main-content{margin-right:0!important;padding-right:20px!important}</style>
<div class="main-content no-sidebar">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><h5 class="mb-0 d-inline"><i class="bi bi-person-vcard"></i> ملف الطالب</h5></div>
        <div>
            <a href="portal.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-repeat"></i> طالب آخر</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> خروج</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="table-container text-center">
                    <?php if ($student['photo']): ?>
                        <img src="<?= BASE_URL . '/' . $student['photo'] ?>" style="height:120px;width:120px;object-fit:cover;border-radius:50%;border:3px solid #dee2e6">
                    <?php else: ?>
                        <div style="height:120px;width:120px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;margin:auto;font-size:3rem;color:#aaa"><i class="bi bi-person"></i></div>
                    <?php endif; ?>
                    <h5 class="mt-2"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($student['student_code']) ?></p>
                </div>
                <div class="table-container mt-3">
                    <table class="table table-sm">
                        <tr><th>الهاتف</th><td><?= htmlspecialchars($student['phone'] ?? '---') ?></td></tr>
                        <tr><th>البريد</th><td><?= htmlspecialchars($student['email'] ?? '---') ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-8">
                <div class="table-container">
                    <h6 class="mb-3"><i class="bi bi-book"></i> الدورات المسجل فيها</h6>
                    <?php if (empty($enrollments)): ?>
                        <p class="text-muted">غير مسجل في أي دورة</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>الدورة</th><th>تاريخ التسجيل</th><th>الحالة</th><th>Attendance</th><th>Vocab</th><th>Writing</th><th>Speaking</th><th>Test</th><th>Exam</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['course_name']) ?> (<?= htmlspecialchars($e['course_code']) ?>)</td>
                                <td><?= $e['enrollment_date'] ?></td>
                                <td><span class="badge bg-<?= $e['status'] === 'active' ? 'success' : 'info' ?>"><?= $e['status'] === 'active' ? 'نشط' : 'مكتمل' ?></span></td>
                                <td><?= htmlspecialchars($e['attendance_grade'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($e['vocabulary'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($e['writing'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($e['speaking'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($e['test'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($e['exam'] ?? '---') ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($e['total'] ?? '---') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <div class="table-container mt-3">
                    <h6 class="mb-3"><i class="bi bi-calendar-check"></i> سجل الحضور</h6>
                    <?php if (empty($attendance)): ?>
                        <p class="text-muted">لا توجد سجلات حضور</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>التاريخ</th><th>الدورة</th><th>الحالة</th></tr></thead>
                        <tbody>
                            <?php foreach ($attendance as $a): ?>
                            <tr>
                                <td><?= $a['attendance_date'] ?></td>
                                <td><?= htmlspecialchars($a['course_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $a['status'] === 'present' ? 'success' : ($a['status'] === 'absent' ? 'danger' : ($a['status'] === 'late' ? 'warning' : 'info')) ?>">
                                        <?= $a['status'] === 'present' ? 'حاضر' : ($a['status'] === 'absent' ? 'غائب' : ($a['status'] === 'late' ? 'متأخر' : 'بعذر')) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
