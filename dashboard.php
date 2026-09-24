<?php
require_once __DIR__ . '/config/app.php';
requireAuth();

$pageTitle = 'لوحة التحكم';
$user = getCurrentUser();

// Statistics
$totalStudents = getRow("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 0;
$totalTeachers = getRow("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'")['count'] ?? 0;
$totalCourses = getRow("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'] ?? 0;
$totalUsers = getRow("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0;

// Recent students
$recentStudents = getRows("SELECT id, student_code, first_name, last_name, enrollment_date FROM students ORDER BY created_at DESC LIMIT 5");

// Recent payments
$recentPayments = getRows("
    SELECT fp.id, fp.receipt_number, fp.amount_paid, fp.payment_date, s.first_name, s.last_name, f.fee_name, f.amount,
           (COALESCE(f.amount,0) - COALESCE(fp.amount_paid,0)) AS remaining
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    LEFT JOIN fees f ON fp.fee_id = f.id
    ORDER BY fp.created_at DESC LIMIT 5
");

// Today's attendance
$todayAttendance = getRow("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
    FROM attendance WHERE attendance_date = CURDATE()
");

// Students with outstanding fee balances (notification)
$outstandingStudents = getRows("
    SELECT s.id, s.student_code, s.first_name, s.last_name, s.phone,
           SUM(fp.amount_paid) AS total_paid,
           0 AS total_discount,
           f.amount AS fee_amount,
           f.fee_name,
           (COALESCE(f.amount, 0) - SUM(fp.amount_paid)) AS remaining
    FROM students s
    JOIN fee_payments fp ON fp.student_id = s.id
    JOIN fees f ON fp.fee_id = f.id
    GROUP BY s.id, fp.fee_id
    HAVING remaining > 0
    ORDER BY remaining DESC
");
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <!-- Topbar -->
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h5 class="mb-0 d-inline">لوحة التحكم</h5>
        </div>
        <div class="d-flex align-items-center">
            <span class="ms-2">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['full_name']) ?>
                <small class="text-muted">(<?= getRoleLabel() ?>)</small>
            </span>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="page-content">
        <!-- Flash Messages -->
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($outstandingStudents) && hasPermission('fees', 'view')): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <strong><i class="bi bi-exclamation-triangle"></i> رسوم متبقية!</strong>
            يوجد <strong><?= count($outstandingStudents) ?></strong> طالب لديهم رسوم غير مسددة بالكامل.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <?php if (hasPermission('students', 'view')): ?>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-primary border-start border-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">الطلاب</div>
                                <div class="stat-number text-primary"><?= $totalStudents ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (hasPermission('teachers', 'view')): ?>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-success border-start border-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">المدرسين</div>
                                <div class="stat-number text-success"><?= $totalTeachers ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-person-workspace stat-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (hasPermission('courses', 'view')): ?>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-info border-start border-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">الدورات</div>
                                <div class="stat-number text-info"><?= $totalCourses ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-book stat-icon text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (hasPermission('users', 'view')): ?>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-warning border-start border-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">المستخدمين</div>
                                <div class="stat-number text-warning"><?= $totalUsers ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people-fill stat-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Today's Attendance Summary -->
        <div class="row g-4 mb-4">
            <?php if (hasPermission('attendance', 'view')): ?>
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-calendar-check"></i> الحضور اليوم</h6>
                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <div class="stat-number text-success"><?= $todayAttendance['present'] ?? 0 ?></div>
                                <div class="stat-label">حاضر</div>
                            </div>
                            <div class="col-4">
                                <div class="stat-number text-danger"><?= $todayAttendance['absent'] ?? 0 ?></div>
                                <div class="stat-label">غائب</div>
                            </div>
                            <div class="col-4">
                                <div class="stat-number text-warning"><?= $todayAttendance['late'] ?? 0 ?></div>
                                <div class="stat-label">متأخر</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-lightning"></i> إجراءات سريعة</h6>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php if (hasPermission('students', 'create')): ?>
                            <a href="<?= BASE_URL ?>/modules/students/create.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-person-plus"></i> إضافة طالب
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('teachers', 'create')): ?>
                            <a href="<?= BASE_URL ?>/modules/teachers/create.php" class="btn btn-success btn-sm">
                                <i class="bi bi-person-plus"></i> إضافة مدرس
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('courses', 'create')): ?>
                            <a href="<?= BASE_URL ?>/modules/courses/create.php" class="btn btn-info btn-sm">
                                <i class="bi bi-book-plus"></i> إضافة دورة
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('attendance', 'create')): ?>
                            <a href="<?= BASE_URL ?>/modules/attendance/list.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-calendar-plus"></i> تسجيل الحضور
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($outstandingStudents) && hasPermission('fees', 'view')): ?>
        <div class="table-container mb-4">
            <h6 class="mb-3"><i class="bi bi-exclamation-triangle text-warning"></i> الطلاب المتأخرين في السداد</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>الكود</th><th>الطالب</th><th>الرسم</th><th>سعر الرسم</th><th>المدفوع</th><th>الخصم</th><th>المتبقي</th></tr></thead>
                    <tbody>
                        <?php foreach ($outstandingStudents as $o): ?>
                        <tr>
                            <td><?= htmlspecialchars($o['student_code']) ?></td>
                            <td><a href="<?= BASE_URL ?>/modules/students/view.php?id=<?= $o['id'] ?>"><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></a></td>
                            <td><?= htmlspecialchars($o['fee_name']) ?></td>
                            <td><?= number_format($o['fee_amount'], 0) ?></td>
                            <td><?= number_format($o['total_paid'], 0) ?></td>
                            <td><?= number_format($o['total_discount'], 0) ?></td>
                            <td class="fw-bold text-danger"><?= number_format($o['remaining'], 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (hasPermission('students', 'view')): ?>
            <!-- Recent Students -->
            <div class="col-md-6">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-people"></i> أحدث الطلاب</h6>
                        <a href="<?= BASE_URL ?>/modules/students/list.php" class="btn btn-outline-primary btn-sm">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>الاسم</th>
                                    <th>تاريخ التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentStudents)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">لا يوجد طلاب</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentStudents as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['student_code']) ?></td>
                                        <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                        <td><?= $s['enrollment_date'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('fees', 'view')): ?>
            <!-- Recent Payments -->
            <div class="col-md-6">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-cash-coin"></i> آخر المدفوعات</h6>
                        <a href="<?= BASE_URL ?>/modules/fees/list.php" class="btn btn-outline-primary btn-sm">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الإيصال</th>
                                    <th>الطالب</th>
                                    <th>الرسم</th>
                                    <th>المدفوع</th>
                                    <th>المتبقي</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentPayments)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">لا توجد مدفوعات</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentPayments as $p): $rem = (float)$p['remaining']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['receipt_number']) ?></td>
                                        <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                        <td><?= htmlspecialchars($p['fee_name'] ?? '---') ?></td>
                                        <td><?= number_format($p['amount_paid'], 0) ?></td>
                                        <td class="fw-bold <?= $rem > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($rem, 0) ?></td>
                                        <td><?= $p['payment_date'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span>
    </footer>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
