<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('users', 'delete');

$id = (int)($_GET['id'] ?? 0);
$user = getRow("SELECT * FROM users WHERE id = ?", [$id]);

if ($user) {
    if ($id == $_SESSION['user_id']) {
        setFlash('error', 'لا يمكن حذف حسابك الخاص');
    } else {
        delete("DELETE FROM users WHERE id = ?", [$id]);
        setFlash('success', 'تم حذف المستخدم بنجاح');
    }
} else {
    setFlash('error', 'المستخدم غير موجود');
}

header('Location: list.php');
exit;
