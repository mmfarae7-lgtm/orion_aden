<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('students', 'create');

$autoCode = generateStudentCode();
$pageTitle = 'إضافة طالب';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'student_code' => trim($_POST['student_code']) ?: $autoCode,
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'father_name' => trim($_POST['father_name']),
        'mother_name' => trim($_POST['mother_name']),
        'gender' => $_POST['gender'],
        'date_of_birth' => $_POST['date_of_birth'],
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'address' => trim($_POST['address']),
        'enrollment_date' => $_POST['enrollment_date'],
        'notes' => trim($_POST['notes']),
    ];

    try {
        insert("INSERT INTO students (student_code, first_name, last_name, father_name, mother_name, gender, date_of_birth, phone, email, address, enrollment_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($data));
        setFlash('success', 'تم إضافة الطالب بنجاح');
        header('Location: list.php');
        exit;
    } catch (Exception $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-link d-md-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h5 class="mb-0 d-inline">إضافة طالب</h5>
        </div>
        <div>
            <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> العودة</a>
        </div>
    </nav>
    <div class="page-content">
        <div class="table-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود الطالب</label>
                        <input type="text" name="student_code" class="form-control" value="<?= $autoCode ?>" readonly style="background:#f8f9fa;cursor:not-allowed;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم الأول <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم الأخير <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الأب</label>
                        <input type="text" name="father_name" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الأم</label>
                        <input type="text" name="mother_name" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجنس <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ التسجيل <span class="text-danger">*</span></label>
                        <input type="date" name="enrollment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
                    <a href="list.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
    <footer class="footer">
        <span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span>
    </footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
