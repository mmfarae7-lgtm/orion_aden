<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SESSION['role'] !== 'teacher') { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$pageTitle = 'بوابة المدرس';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $code = trim($_POST['teacher_code']);
    $teacher = getRow("SELECT * FROM teachers WHERE teacher_code = ?", [$code]);
    if ($teacher) {
        $_SESSION['teacher_id'] = $teacher['id'];
        $_SESSION['teacher_name'] = $teacher['first_name'] . ' ' . $teacher['last_name'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'كود المدرس غير صحيح';
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="container d-flex align-items-center justify-content-center" style="min-height:80vh">
    <div class="card shadow" style="width:400px">
        <div class="card-body p-4 text-center">
            <i class="bi bi-person-workspace display-3 text-success"></i>
            <h4 class="mt-3">بوابة المدرس</h4>
            <p class="text-muted">أهلاً بك يا <?= htmlspecialchars($_SESSION['full_name']) ?></p>
            <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">أدخل كود المدرس الخاص بك</label>
                    <input type="text" name="teacher_code" class="form-control text-center" required autofocus placeholder="مثال: TCH001">
                </div>
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-box-arrow-in-left"></i> دخول</button>
            </form>
            <div class="mt-3"><a href="<?= BASE_URL ?>/logout.php" class="text-danger small">تسجيل خروج</a></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
