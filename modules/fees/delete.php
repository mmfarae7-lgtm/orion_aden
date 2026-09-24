<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('fees', 'delete');

$id = (int)($_GET['id'] ?? 0);
$fee = getRow("SELECT * FROM fees WHERE id = ?", [$id]);

if ($fee) {
    delete("DELETE FROM fees WHERE id = ?", [$id]);
    setFlash('success', 'تم حذف الرسم بنجاح');
} else {
    setFlash('error', 'الرسم غير موجود');
}

header('Location: list.php');
exit;
