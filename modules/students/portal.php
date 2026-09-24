<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SESSION['role'] !== 'viewer') { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$pageTitle = 'بوابة الطالب';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $code = trim($_POST['student_code']);
    $student = getRow("SELECT * FROM students WHERE student_code = ?", [$code]);
    if ($student) {
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
        $_SESSION['student_code'] = $student['student_code'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'كود الطالب غير صحيح';
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="container d-flex align-items-center justify-content-center" style="min-height:80vh">
    <div class="card shadow" style="width:400px">
        <div class="card-body p-4 text-center">
            <i class="bi bi-person-vcard display-3 text-info"></i>
            <h4 class="mt-3">بوابة الطالب</h4>
            <p class="text-muted">أهلاً بك يا <?= htmlspecialchars($_SESSION['full_name']) ?></p>
            <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">أدخل كود الطالب الخاص بك</label>
                    <input type="text" name="student_code" class="form-control text-center" required autofocus placeholder="أدخل الكود الخاص بك" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-info w-100"><i class="bi bi-box-arrow-in-left"></i> دخول</button>
            </form>
            <div class="mt-3"><a href="<?= BASE_URL ?>/logout.php" class="text-danger small">تسجيل خروج</a></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
