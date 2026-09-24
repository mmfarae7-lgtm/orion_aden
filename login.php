<?php
require_once __DIR__ . '/config/app.php';

// Force no caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect if already logged in
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkLoginRateLimit($ip)) {
        $error = 'محاولات كثيرة جداً. يرجى الانتظار 5 دقائق.';
    } elseif (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $user = getRow("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1", [$username, $username]);

        if ($user && password_verify($password, $user['password'])) {
            unset($_SESSION['login_attempts'][$ip]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Update last login
            update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            if ($user['role'] === 'teacher') {
                header('Location: modules/teachers/portal.php');
            } elseif ($user['role'] === 'viewer') {
                header('Location: modules/students/portal.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            recordLoginAttempt($ip);
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>تسجيل الدخول - <?= getSetting('school_name', 'نظام أوريون') ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/tajawal/tajawal.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="<?= BASE_URL ?>/assets/img/Orion.png?v=<?= ASSET_VER ?>" alt="Logo" class="login-logo mb-3" style="height:130px;width:auto;">
                    <h3><?= getSetting('school_name', 'Orion Aden') ?></h3>
                    <p class="text-muted">نظام إدارة المعاهد</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" autocomplete="off">
                            <?= csrfField() ?>
                            <div class="mb-3">
                                <label class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" placeholder="أدخل اسم المستخدم" required autofocus autocomplete="off">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">كلمة المرور</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="أدخل كلمة المرور" required autocomplete="new-password">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-box-arrow-in-left"></i> تسجيل الدخول
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <small class="text-muted">جميع الحقوق محفوظة &copy; <?= date('Y') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').value = '';
            document.querySelector('input[name="password"]').value = '';
        });
        // Force fresh SW & clear old cache
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= BASE_URL ?>/service-worker.js?v=6').catch(() => {});
        }
    </script>
</body>
</html>
