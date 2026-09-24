<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('messaging', 'send');

header('Content-Type: application/json');

// Students with outstanding fee balances
$students = getRows("
    SELECT DISTINCT s.id FROM students s
    JOIN fee_payments fp ON s.id = fp.student_id
    JOIN fees f ON fp.fee_id = f.id
    WHERE (f.amount - COALESCE(fp.discount, 0) - COALESCE(fp.amount_paid, 0)) > 0
    AND s.status = 'active'
");

$ids = array_map(function($s) { return (int)$s['id']; }, $students);
echo json_encode($ids);
