<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../libs/export.php';
requireAuth();
requirePermission('reports', 'view');

$pageTitle = 'التقارير';

$stats = [
    'students' => getRow("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM students"),
    'teachers' => getRow("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM teachers"),
    'courses' => getRow("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM courses"),
    'payments' => getRow("SELECT COUNT(*) as total, SUM(amount_paid) as total_amount FROM fee_payments"),
];

$report = $_GET['report'] ?? '';
$format = $_GET['format'] ?? ''; // excel, pdf, html

// Report data generation
$reportTitle = '';
$reportHeaders = [];
$reportRows = [];

if ($report) {
    switch ($report) {
        case 'students_all':
            $reportTitle = 'كشف بجميع الطلاب';
            $data = getRows("SELECT student_code, first_name, last_name, gender, phone, email, enrollment_date, status FROM students ORDER BY created_at DESC");
            $reportHeaders = ['الكود', 'الاسم', 'الجنس', 'الهاتف', 'البريد', 'تاريخ التسجيل', 'الحالة'];
            foreach ($data as $d) {
                $reportRows[] = [$d['student_code'], $d['first_name'] . ' ' . $d['last_name'], $d['gender'] === 'male' ? 'ذكر' : 'أنثى', $d['phone'] ?? '---', $d['email'] ?? '---', $d['enrollment_date'], $d['status']];
            }
            break;

        case 'students_active':
            $reportTitle = 'الطلاب النشطين';
            $data = getRows("SELECT student_code, first_name, last_name, gender, phone, enrollment_date FROM students WHERE status='active' ORDER BY first_name");
            $reportHeaders = ['الكود', 'الاسم', 'الجنس', 'الهاتف', 'تاريخ التسجيل'];
            foreach ($data as $d) {
                $reportRows[] = [$d['student_code'], $d['first_name'] . ' ' . $d['last_name'], $d['gender'] === 'male' ? 'ذكر' : 'أنثى', $d['phone'] ?? '---', $d['enrollment_date']];
            }
            break;

        case 'students_inactive':
            $reportTitle = 'الطلاب غير النشطين';
            $data = getRows("SELECT student_code, first_name, last_name, gender, phone, status FROM students WHERE status!='active' ORDER BY first_name");
            $reportHeaders = ['الكود', 'الاسم', 'الجنس', 'الهاتف', 'الحالة'];
            foreach ($data as $d) {
                $reportRows[] = [$d['student_code'], $d['first_name'] . ' ' . $d['last_name'], $d['gender'] === 'male' ? 'ذكر' : 'أنثى', $d['phone'] ?? '---', $d['status']];
            }
            break;

        case 'payments_summary':
            $reportTitle = 'ملخص المدفوعات';
            $data = getRows("SELECT fp.receipt_number, s.first_name, s.last_name, f.fee_name, fp.amount_paid, fp.payment_date, fp.payment_method FROM fee_payments fp JOIN students s ON fp.student_id = s.id JOIN fees f ON fp.fee_id = f.id ORDER BY fp.created_at DESC");
            $reportHeaders = ['الإيصال', 'الطالب', 'الرسم', 'المبلغ', 'التاريخ', 'طريقة الدفع'];
            foreach ($data as $d) {
                $reportRows[] = [$d['receipt_number'], $d['first_name'] . ' ' . $d['last_name'], $d['fee_name'], number_format($d['amount_paid'], 0), $d['payment_date'], $d['payment_method']];
            }
            break;

        case 'payments_by_student':
            $reportTitle = 'المدفوعات حسب الطالب';
            $data = getRows("SELECT s.student_code, s.first_name, s.last_name, COUNT(fp.id) as payments_count, COALESCE(SUM(fp.amount_paid),0) as total_paid FROM students s LEFT JOIN fee_payments fp ON s.id = fp.student_id GROUP BY s.id ORDER BY total_paid DESC");
            $reportHeaders = ['الكود', 'الطالب', 'عدد الدفعات', 'الإجمالي'];
            foreach ($data as $d) {
                $reportRows[] = [$d['student_code'], $d['first_name'] . ' ' . $d['last_name'], $d['payments_count'], number_format($d['total_paid'], 0)];
            }
            break;

        case 'outstanding':
            $reportTitle = 'الرسوم غير المسددة';
            $data = getRows("SELECT s.student_code, s.first_name, s.last_name, COALESCE(SUM(fp.amount_paid),0) as paid FROM students s LEFT JOIN fee_payments fp ON s.id = fp.student_id GROUP BY s.id HAVING paid = 0 ORDER BY s.first_name");
            $reportHeaders = ['الكود', 'الطالب', 'المدفوع'];
            foreach ($data as $d) {
                $reportRows[] = [$d['student_code'], $d['first_name'] . ' ' . $d['last_name'], number_format($d['paid'], 0)];
            }
            break;

        case 'attendance_summary':
            $reportTitle = 'ملخص الحضور';
            $data = getRows("SELECT s.student_code, s.first_name, s.last_name, COUNT(*) as total, SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent, SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late FROM attendance a JOIN students s ON a.student_id = s.id GROUP BY s.id ORDER BY s.first_name");
            $reportHeaders = ['الكود', 'الطالب', 'إجمالي', 'حاضر', 'غائب', 'متأخر'];
            foreach ($data as $d) {
                $reportRows[] = [$d['student_code'], $d['first_name'] . ' ' . $d['last_name'], $d['total'], $d['present'], $d['absent'], $d['late']];
            }
            break;

        default:
            $reportTitle = 'تقرير: ' . $report;
            $reportHeaders = ['لا توجد بيانات'];
            $reportRows = [];
    }

    // Export handling
    if ($format === 'excel') {
        exportXLSX($reportTitle, $reportHeaders, $reportRows);
    }
    if ($format === 'pdf') {
        $html = '<table><thead><tr>';
        foreach ($reportHeaders as $h) $html .= '<th>' . htmlspecialchars($h) . '</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($reportRows as $row) {
            $html .= '<tr>';
            foreach ($row as $c) $html .= '<td>' . htmlspecialchars($c) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        exportPDF($reportTitle, $html);
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <h5 class="mb-0">التقارير</h5>
        <img src="<?= BASE_URL ?>/assets/img/Orion.png?v=<?= ASSET_VER ?>" alt="Logo" style="height:60px;width:auto;">
    </nav>
    <div class="page-content">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body text-center">
                        <div class="stat-number text-primary"><?= $stats['students']['total'] ?? 0 ?></div>
                        <div class="stat-label">إجمالي الطلاب</div>
                        <small class="text-success"><?= $stats['students']['active'] ?? 0 ?> نشط</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body text-center">
                        <div class="stat-number text-success"><?= $stats['teachers']['total'] ?? 0 ?></div>
                        <div class="stat-label">إجمالي المدرسين</div>
                        <small class="text-success"><?= $stats['teachers']['active'] ?? 0 ?> نشط</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-info">
                    <div class="card-body text-center">
                        <div class="stat-number text-info"><?= $stats['courses']['total'] ?? 0 ?></div>
                        <div class="stat-label">إجمالي الدورات</div>
                        <small class="text-success"><?= $stats['courses']['active'] ?? 0 ?> نشطة</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body text-center">
                        <div class="stat-number text-warning"><?= number_format($stats['payments']['total_amount'] ?? 0, 0) ?></div>
                        <div class="stat-label">إجمالي المدفوعات</div>
                        <small><?= ($stats['payments']['total'] ?? 0) ?> عملية</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="bi bi-people"></i> تقارير الطلاب</h6>
                        <div class="d-flex flex-column gap-2 mt-3">
                            <a href="?report=students_all" class="btn btn-outline-primary btn-sm">كشف بجميع الطلاب</a>
                            <a href="?report=students_active" class="btn btn-outline-primary btn-sm">الطلاب النشطين</a>
                            <a href="?report=students_inactive" class="btn btn-outline-primary btn-sm">الطلاب غير النشطين</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="bi bi-cash-coin"></i> تقارير مالية</h6>
                        <div class="d-flex flex-column gap-2 mt-3">
                            <a href="?report=payments_summary" class="btn btn-outline-success btn-sm">ملخص المدفوعات</a>
                            <a href="?report=payments_by_student" class="btn btn-outline-success btn-sm">المدفوعات حسب الطالب</a>
                            <a href="?report=outstanding" class="btn btn-outline-success btn-sm">الرسوم غير المسددة</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="bi bi-calendar-check"></i> تقارير الحضور</h6>
                        <div class="d-flex flex-column gap-2 mt-3">
                            <a href="?report=attendance_summary" class="btn btn-outline-warning btn-sm">ملخص الحضور</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($report && !empty($reportRows)): ?>
        <div class="table-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="bi bi-file-text"></i> <?= $reportTitle ?></h6>
                <div class="btn-group">
                    <a href="?report=<?= $report ?>&format=excel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    <a href="?report=<?= $report ?>&format=pdf" class="btn btn-danger btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-primary">
                        <tr><?php foreach ($reportHeaders as $h): ?><th><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $row): ?>
                        <tr><?php foreach ($row as $c): ?><td><?= htmlspecialchars($c) ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif ($report && empty($reportRows)): ?>
        <div class="alert alert-info mt-4">لا توجد بيانات لهذا التقرير</div>
        <?php endif; ?>
    </div>
    <footer class="footer"><span>Orion Aden &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
