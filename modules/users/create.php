<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('users', 'create');

$pageTitle = 'إضافة مستخدم';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (empty($username)) $errors[] = 'اسم المستخدم مطلوب';
    if (empty($password)) $errors[] = 'كلمة المرور مطلوبة';
    if (empty($full_name)) $errors[] = 'الاسم الكامل مطلوب';

    $existing = getRow("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) $errors[] = 'اسم المستخدم موجود بالفعل';

    if ($email) {
        $existingEmail = getRow("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingEmail) $errors[] = 'البريد الإلكتروني موجود بالفعل';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        insert("INSERT INTO users (username, email, password, full_name, role, phone, is_active, created_by) VALUES (?,?,?,?,?,?,?,?)",
            [$username, $email ?: null, $hash, $full_name, $role, $phone ?: null, $is_active, $_SESSION['user_id']]);
        setFlash('success', 'تم إضافة المستخدم بنجاح');
        header('Location: list.php');
        exit;
    }
}

$roles = ['super_admin', 'admin', 'accountant', 'teacher', 'reception', 'viewer'];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">إضافة مستخدم</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <?php if (!empty($errors)): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= $e ?></div>
        <?php endforeach; endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الدور <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="">اختر الدور</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r ?>" <?= ($_POST['role'] ?? '') === $r ? 'selected' : '' ?>><?= getRoleLabel($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                <label class="form-check-label" for="isActive">مفعل</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
                </form>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
