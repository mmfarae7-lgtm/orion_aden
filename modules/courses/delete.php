<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('courses', 'delete');

$id = (int)($_GET['id'] ?? 0);
$course = getRow("SELECT * FROM courses WHERE id = ?", [$id]);

if ($course) {
    delete("DELETE FROM courses WHERE id = ?", [$id]);
    setFlash('success', 'تم حذف الدورة بنجاح');
} else {
    setFlash('error', 'الدورة غير موجودة');
}

header('Location: list.php');
exit;
