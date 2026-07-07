<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$module = $_GET['module'] ?? '';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/assets/img/Orion.png" alt="Logo" style="height:45px;width:auto;margin-bottom:8px;">
        <h4><?= getSetting('school_name', 'أوريون') ?></h4>
        <small class="text-white-50">نظام إدارة المعاهد</small>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> لوحة التحكم
            </a>
        </li>

        <?php if (hasPermission('students', 'view') || hasPermission('teachers', 'view') || hasPermission('courses', 'view') || hasPermission('student_cards', 'view')): ?>
        <li class="nav-section">الإدارة</li>
        <?php endif; ?>

        <?php if (hasPermission('students', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/students/list.php" class="nav-link <?= $module === 'students' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> الطلاب
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('teachers', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/teachers/list.php" class="nav-link <?= $module === 'teachers' ? 'active' : '' ?>">
                <i class="bi bi-person-workspace"></i> المدرسين
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('courses', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/courses/list.php" class="nav-link <?= $module === 'courses' ? 'active' : '' ?>">
                <i class="bi bi-book"></i> الدورات
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('fees', 'view')): ?>
        <li class="nav-section">المالية</li>
        <?php endif; ?>
        <?php if (hasPermission('fees', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/fees/list.php" class="nav-link <?= $module === 'fees' ? 'active' : '' ?>">
                <i class="bi bi-cash-coin"></i> الرسوم
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('attendance', 'view')): ?>
        <li class="nav-section">المتابعة</li>
        <?php endif; ?>
        <?php if (hasPermission('attendance', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/attendance/list.php" class="nav-link <?= $module === 'attendance' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> الحضور
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('reports', 'view') || hasPermission('student_cards', 'view')): ?>
        <li class="nav-section">التقارير</li>
        <?php endif; ?>
        <?php if (hasPermission('reports', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/reports/index.php" class="nav-link <?= $module === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-file-bar-graph"></i> التقارير
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('student_cards', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/students/cards/index.php" class="nav-link <?= $module === 'cards' ? 'active' : '' ?>">
                <i class="bi bi-credit-card-2-front"></i> بطاقات الطلاب
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('import', 'view') || hasPermission('backup', 'view') || hasPermission('settings', 'view') || hasPermission('users', 'view')): ?>
        <li class="nav-section">النظام</li>
        <?php endif; ?>
        <?php if (hasPermission('import', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/import/index.php" class="nav-link <?= $module === 'import' ? 'active' : '' ?>">
                <i class="bi bi-upload"></i> استيراد بيانات
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('users', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/users/list.php" class="nav-link <?= $module === 'users' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock"></i> المستخدمين
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('backup', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/backup/index.php" class="nav-link <?= $module === 'backup' ? 'active' : '' ?>">
                <i class="bi bi-cloud-arrow-down"></i> النسخ الاحتياطي
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('settings', 'view')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/settings/index.php" class="nav-link <?= $module === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear"></i> الإعدادات
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-section">الحساب</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
            </a>
        </li>
    </ul>
</div>
<!-- End Sidebar -->
