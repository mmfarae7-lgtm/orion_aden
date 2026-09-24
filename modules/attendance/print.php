<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('attendance', 'view');

$pageTitle = 'طباعة كشف الحضور';
$courseId = (int)($_GET['course_id'] ?? 0);
$startDate = $_GET['start_date'] ?? date('Y-m-d');

$course = getRow("SELECT c.*, CONCAT(t.first_name,' ',t.last_name) AS teacher_name FROM courses c LEFT JOIN teachers t ON c.teacher_id = t.id WHERE c.id = ?", [$courseId]);

$students = getRows("
    SELECT s.id, s.student_code, s.first_name, s.last_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.course_id = ? AND e.status = 'active'
    ORDER BY s.first_name
", [$courseId]);

$dayNames = ['Sat' => 'سبت', 'Sun' => 'أحد', 'Mon' => 'اثنين', 'Tue' => 'ثلاثاء', 'Wed' => 'أربعاء', 'Thu' => 'خميس', 'Fri' => 'جمعة'];

// Generate 5 weeks of daily dates (all 7 days)
$allDates = [];
$start = new DateTime($startDate);
// Move to Saturday (start of week)
$start->modify('last Saturday');
for ($w = 0; $w < 5; $w++) {
    $weekDates = [];
    for ($d = 0; $d < 7; $d++) {
        $date = clone $start;
        $date->modify("+" . ($w * 7 + $d) . " days");
        $weekDates[] = $date->format('Y-m-d');
    }
    $allDates[] = ['label' => 'الأسبوع ' . ($w + 1), 'dates' => $weekDates];
}

$printMode = isset($_GET['print']);

if ($printMode):
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف حضور - <?= htmlspecialchars($course['course_name'] ?? '') ?></title>
    <style>
        @page { size: landscape; margin: 0.7cm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Traditional Arabic', 'Times New Roman', serif; padding: 5px; font-size: 9px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h3 { margin: 0; font-size: 18px; }
        .header p { margin: 1px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 2px 1px; text-align: center; vertical-align: middle; }
        th { background: #e0e0e0; font-weight: bold; font-size: 8px; }
        .week-header { background: #f0f0f0; }
        .day-cell { width: 15px; height: 15px; border: 1.2px solid #555; border-radius: 50%; display: inline-block; margin: 0; }
        .day-group { display: flex; gap: 0; justify-content: center; }
        .day-circle { display: inline-block; width: 11px; height: 11px; border: 1px solid #555; border-radius: 50%; margin: 0; }
        .circle-row { display: flex; gap: 1px; justify-content: center; }
        .circle-row span { font-size: 5px; line-height: 1; }
        .no-print { text-align: center; margin-top: 15px; }
        .legend { margin-top: 8px; font-size: 10px; text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h3><?= htmlspecialchars(getSetting('school_name', 'Orion Aden')) ?></h3>
        <p>كشف الحضور اليومي - <?= htmlspecialchars($course['course_name'] ?? '') ?>
           | المدرس: <?= htmlspecialchars($course['teacher_name'] ?? '---') ?>
           | القاعة: <?= htmlspecialchars($course['room'] ?? '---') ?>
           | الوقت: <?= htmlspecialchars($course['schedule_time'] ?? '---') ?></p>
        <p>بداية من: <?= $startDate ?> | لمدة 5 أسابيع</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22px;">#</th>
                <th style="width:60px;">الكود</th>
                <th style="width:100px;">اسم الطالب</th>
                <?php foreach ($allDates as $week): ?>
                <th colspan="7" class="week-header" style="font-size:9px;"><?= $week['label'] ?></th>
                <?php endforeach; ?>
                <th style="width:28px;">الحضور</th>
            </tr>
            <tr>
                <th></th><th></th><th></th>
                <?php foreach ($allDates as $week): ?>
                <?php foreach ($week['dates'] as $d): ?>
                <th style="font-size:7px;padding:1px;"><?= date('d', strtotime($d)) ?><br><span style="font-weight:normal;font-size:6px;"><?= $dayNames[date('D', strtotime($d))] ?></span></th>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $i => $s): ?>
            <tr style="height:24px;">
                <td><?= $i + 1 ?></td>
                <td style="font-size:8px;"><?= htmlspecialchars($s['student_code']) ?></td>
                <td style="text-align:right;padding-right:3px;font-size:10px;"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <?php foreach ($allDates as $week): ?>
                <?php foreach ($week['dates'] as $d): ?>
                <td style="padding:0;">
                    <div class="circle-row">
                        <div class="day-circle"></div>
                    </div>
                </td>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <td style="font-size:8px;"></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="legend">
        <span style="margin:0 8px;">⬤ = حاضر</span>
        <span style="margin:0 8px;">✗ = غائب</span>
        <span style="margin:0 8px;">◐ = متأخر</span>
        <span style="margin:0 8px;">△ = بعذر</span>
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding:6px 20px;font-size:14px;cursor:pointer;">🖨️ طباعة</button>
        <a href="print.php" style="padding:6px 20px;font-size:14px;margin-right:10px;">عودة</a>
    </div>
    <script>window.print();</script>
</body>
</html>
<?php exit; endif; ?>

<?php
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';

$user = getCurrentUser();
if ($user['role'] === 'teacher') {
    $teacher = getRow("SELECT id FROM teachers WHERE email = ? OR CONCAT(first_name,' ',last_name) = ?", [$user['email'], $user['full_name']]);
    $teacherId = $teacher ? $teacher['id'] : 0;
    $courses = getRows("SELECT id, course_name, room FROM courses WHERE status = 'active' AND teacher_id = ?", [$teacherId]);
} else {
    $courses = getRows("SELECT id, course_name, room FROM courses WHERE status = 'active'");
}
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">طباعة كشف الحضور</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">الدورة <span class="text-danger">*</span></label>
                    <select name="course_id" class="form-select" required>
                        <option value="">-- اختر دورة --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $courseId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?><?= $c['room'] ? ' - ' . htmlspecialchars($c['room']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">بداية من تاريخ</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-eye"></i> عرض وطباعة</button>
                </div>
            </form>
            <p class="text-muted small mt-2">يتم إنشاء كشف حضور يومي فارغ لـ 5 أسابيع (جميع أيام الأسبوع) مع دوائر لتعليم الحضور</p>
        </div>

        <?php if ($course && $students): ?>
        <div class="table-container mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><?= htmlspecialchars($course['course_name']) ?> - <?= count($students) ?> طالب - 5 أسابيع (<?= 5*7 ?> يوم)</h6>
                <a href="print.php?course_id=<?= $courseId ?>&start_date=<?= $startDate ?>&print=1" target="_blank" class="btn btn-primary"><i class="bi bi-printer"></i> طباعة الكشف</a>
            </div>
        </div>
        <?php elseif ($courseId): ?>
        <div class="alert alert-info mt-3">لا يوجد طلاب في هذه الدورة</div>
        <?php endif; ?>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
