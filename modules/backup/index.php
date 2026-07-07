<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('backup', 'view');

$pageTitle = 'النسخ الاحتياطي';
$backups = getRows("SELECT b.*, u.full_name FROM backups b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC");

if (isset($_GET['action']) && $_GET['action'] === 'create') {
    requirePermission('backup', 'create');
    $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
    $filepath = __DIR__ . '/../../backups/' . $filename;

    if (!is_dir(__DIR__ . '/../../backups')) {
        mkdir(__DIR__ . '/../../backups', 0777, true);
    }

    $cmd = sprintf('"C:\\xampp\\mysql\\bin\\mysqldump" --user=%s --password=%s --host=%s %s > "%s"',
        DB_USER, DB_PASS, DB_HOST, DB_NAME, $filepath);

    $output = null;
    $resultCode = null;
    exec($cmd, $output, $resultCode);

    if ($resultCode === 0) {
        $fileSize = filesize($filepath);
        insert("INSERT INTO backups (file_name, file_path, file_size, type, created_by) VALUES (?,?,?,?,?)",
            [$filename, $filepath, $fileSize, 'manual', $_SESSION['user_id']]);
        setFlash('success', 'تم إنشاء النسخة الاحتياطية بنجاح');
    } else {
        setFlash('error', 'فشل إنشاء النسخة الاحتياطية');
    }
    header('Location: index.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    requirePermission('backup', 'download');
    $backup = getRow("SELECT * FROM backups WHERE id = ?", [(int)$_GET['id']]);
    if ($backup && file_exists($backup['file_path'])) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $backup['file_name'] . '"');
        header('Content-Length: ' . $backup['file_size']);
        readfile($backup['file_path']);
        exit;
    }
    setFlash('error', 'الملف غير موجود');
    header('Location: index.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    requirePermission('backup', 'delete');
    $backup = getRow("SELECT * FROM backups WHERE id = ?", [(int)$_GET['id']]);
    if ($backup) {
        if (file_exists($backup['file_path'])) unlink($backup['file_path']);
        delete("DELETE FROM backups WHERE id = ?", [$backup['id']]);
        setFlash('success', 'تم حذف النسخة الاحتياطية');
    }
    header('Location: index.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">النسخ الاحتياطي</h5></div>
        <?php if (hasPermission('backup', 'create')): ?>
        <div><a href="?action=create" class="btn btn-primary" onclick="return confirm('هل تريد إنشاء نسخة احتياطية الآن؟')"><i class="bi bi-cloud-arrow-down"></i> إنشاء نسخة</a></div>
        <?php endif; ?>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <div class="table-container">
            <h6 class="mb-3">النسخ الاحتياطية</h6>
            <table class="table table-hover">
                <thead><tr><th>#</th><th>اسم الملف</th><th>الحجم</th><th>النوع</th><th>المنشئ</th><th>التاريخ</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr><td colspan="7" class="text-center text-muted">لا توجد نسخ احتياطية</td></tr>
                    <?php else: ?>
                        <?php foreach ($backups as $i => $b): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($b['file_name']) ?></td>
                            <td><?= $b['file_size'] > 1048576 ? round($b['file_size'] / 1048576, 2) . ' MB' : round($b['file_size'] / 1024, 2) . ' KB' ?></td>
                            <td><?= $b['type'] === 'manual' ? 'يدوي' : 'تلقائي' ?></td>
                            <td><?= htmlspecialchars($b['full_name'] ?? '---') ?></td>
                            <td><?= $b['created_at'] ?></td>
                            <td>
                                <?php if (hasPermission('backup', 'download')): ?>
                                <a href="?action=download&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></a>
                                <?php endif; ?>
                                <?php if (hasPermission('backup', 'delete')): ?>
                                <a href="?action=delete&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل أنت متأكد؟"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
