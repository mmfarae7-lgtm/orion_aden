<?php
require_once __DIR__ . '/../../../config/app.php';
requireAuth();
requirePermission('student_cards', 'print');

$student_id = (int)($_GET['id'] ?? 0);
$student = getRow("
    SELECT s.*, sc.card_number, sc.issue_date, sc.expiry_date
    FROM students s
    LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.status = 'active'
    WHERE s.id = ?
", [$student_id]);

if (!$student) { die('الطالب غير موجود'); }

$schoolName = getSetting('school_name', 'Orion Aden');
$schoolAddress = getSetting('school_address', '');
$logo = BASE_URL . '/assets/img/Orion.png';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة طالب</title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #f0f0f0; }
        .card-container { width: 340px; margin: 50px auto; }
        .student-card {
            background: #ffffff;
            color: #333;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .student-card .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 200px;
            height: auto;
            pointer-events: none;
            z-index: 0;
        }
        .card-header {
            text-align: center;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 12px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        .card-header img.logo {
            height: 45px;
            width: auto;
            margin-bottom: 4px;
        }
        .card-header h5 {
            color: #4e73df;
            font-weight: 700;
            margin: 0;
            font-size: 1rem;
        }
        .card-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f0f4ff;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            border: 3px solid #4e73df;
            color: #4e73df;
            overflow: hidden;
        }
        .card-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-info { position: relative; z-index: 1; }
        .card-info table { width: 100%; }
        .card-info td { padding: 4px 0; font-size: 0.85rem; }
        .card-info td:first-child { color: #888; width: 100px; font-weight: 600; }
        .card-info td:last-child { color: #333; font-weight: 500; }
        .card-footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 0.7rem;
            color: #aaa;
            position: relative;
            z-index: 1;
        }
        @media print {
            body { background: white; }
            .card-container { margin: 20px auto; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="text-center no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary mt-3"><i class="bi bi-printer"></i> طباعة</button>
        <a href="index.php" class="btn btn-secondary mt-3">عودة</a>
    </div>
    <div class="card-container">
        <div class="student-card">
            <img src="<?= $logo ?>" alt="" class="watermark">
            <div class="card-header">
                <img src="<?= $logo ?>" alt="Logo" class="logo">
                <h5><?= htmlspecialchars($schoolName) ?></h5>
            </div>
            <div class="card-info">
                <table>
                    <tr><td>الاسم:</td><td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td></tr>
                    <tr><td>الكود:</td><td><?= htmlspecialchars($student['student_code']) ?></td></tr>
                    <tr><td>الجنس:</td><td><?= $student['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td></tr>
                    <tr><td>تاريخ الميلاد:</td><td><?= $student['date_of_birth'] ?? '---' ?></td></tr>
                    <tr><td>رقم البطاقة:</td><td><?= htmlspecialchars($student['card_number'] ?? '---') ?></td></tr>
                    <tr><td>تاريخ الإصدار:</td><td><?= $student['issue_date'] ?? date('Y-m-d') ?></td></tr>
                </table>
            </div>
            <div class="card-footer">
                <?= htmlspecialchars($schoolAddress) ?>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
