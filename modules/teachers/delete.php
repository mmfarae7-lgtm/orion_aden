<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('teachers', 'delete');

$id = (int)($_GET['id'] ?? 0);
$teacher = getRow("SELECT * FROM teachers WHERE id = ?", [$id]);

if ($teacher) {
    delete("DELETE FROM teachers WHERE id = ?", [$id]);
    setFlash('success', 'تم حذف المدرس بنجاح');
} else {
    setFlash('error', 'المدرس غير موجود');
}

header('Location: list.php');
exit;
