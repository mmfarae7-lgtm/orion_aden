<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('users', 'edit');

$id = (int)($_GET['id'] ?? 0);
$user = getRow("SELECT * FROM users WHERE id = ?", [$id]);
if (!$user) { setFlash('error', 'المستخدم غير موجود'); header('Location: list.php'); exit; }

if ($id == $_SESSION['user_id']) {
    setFlash('error', 'لا يمكن تعديل حسابك من هنا');
    header('Location: list.php');
    exit;
}

$pageTitle = 'تعديل مستخدم';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];

    $errors = [];
    if (empty($full_name)) $errors[] = 'الاسم الكامل مطلوب';

    if ($email && $email !== $user['email']) {
        $existingEmail = getRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
        if ($existingEmail) $errors[] = 'البريد الإلكتروني موجود بالفعل';
    }

    if (empty($errors)) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            update("UPDATE users SET email=?, full_name=?, role=?, phone=?, is_active=?, password=? WHERE id=?",
                [$email ?: null, $full_name, $role, $phone ?: null, $is_active, $hash, $id]);
        } else {
            update("UPDATE users SET email=?, full_name=?, role=?, phone=?, is_active=? WHERE id=?",
                [$email ?: null, $full_name, $role, $phone ?: null, $is_active, $id]);
        }
        setFlash('success', 'تم تحديث المستخدم بنجاح');
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
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">تعديل مستخدم</h5></div>
        <div><a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a></div>
    </nav>
    <div class="page-content">
        <?php if (!empty($errors)): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= $e ?></div>
        <?php endforeach; endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم المستخدم</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">كلمة المرور (اتركها فارغة إذا لم ترد التغيير)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($user['full_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الدور <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= getRoleLabel($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $user['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">مفعل</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ التغييرات</button>
                </form>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
