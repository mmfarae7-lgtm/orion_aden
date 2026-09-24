<?php
require_once __DIR__ . '/../../config/app.php';
$sid = (int)($_GET['student_id'] ?? 0);
if ($sid) {
    $pays = getRows("SELECT receipt_number, amount_paid, discount, payment_date, payment_method FROM fee_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 5", [$sid]);
    if ($pays): ?>
        <table class="table table-sm small">
            <thead><tr><th>الإيصال</th><th>المبلغ</th><th>الخصم</th><th>التاريخ</th></tr></thead>
            <tbody>
                <?php foreach ($pays as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['receipt_number']) ?></td>
                    <td><?= number_format($p['amount_paid'], 0) ?></td>
                    <td><?= number_format($p['discount'] ?? 0, 0) ?></td>
                    <td><?= $p['payment_date'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted small">لا توجد دفعات سابقة</p>
    <?php endif;
}
