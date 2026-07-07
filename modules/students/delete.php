<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('students', 'delete');

$id = (int)($_GET['id'] ?? 0);
$student = getRow("SELECT * FROM students WHERE id = ?", [$id]);

if ($student) {
    delete("DELETE FROM students WHERE id = ?", [$id]);
    setFlash('success', 'تم حذف الطالب بنجاح');
} else {
    setFlash('error', 'الطالب غير موجود');
}

header('Location: list.php');
exit;
