<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('import', 'view');

$pageTitle = 'استيراد بيانات';
$importResult = null;

// Database table definitions for import
$importTables = [
    'students' => [
        'label' => 'الطلاب',
        'fields' => ['student_code', 'first_name', 'last_name', 'father_name', 'mother_name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'enrollment_date', 'status', 'notes'],
        'required' => ['student_code', 'first_name', 'last_name', 'gender', 'enrollment_date'],
        'defaults' => ['status' => 'active'],
    ],
    'teachers' => [
        'label' => 'المدرسين',
        'fields' => ['teacher_code', 'first_name', 'last_name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'qualification', 'specialization', 'hire_date', 'salary', 'status', 'notes'],
        'required' => ['teacher_code', 'first_name', 'last_name', 'gender', 'hire_date'],
        'defaults' => ['status' => 'active'],
    ],
    'courses' => [
        'label' => 'الدورات',
        'fields' => ['course_code', 'course_name', 'description', 'teacher_id', 'credit_hours', 'fee', 'max_students', 'status'],
        'required' => ['course_code', 'course_name'],
        'defaults' => ['status' => 'active', 'max_students' => 30],
    ],
    'fees' => [
        'label' => 'الرسوم',
        'fields' => ['fee_name', 'amount', 'description', 'frequency'],
        'required' => ['fee_name', 'amount'],
        'defaults' => ['frequency' => 'one_time'],
    ],
];

$selectedTable = $_POST['table'] ?? $_GET['table'] ?? '';

// Template download
if (isset($_GET['action']) && $_GET['action'] === 'template' && $selectedTable && isset($importTables[$selectedTable])) {
    $t = $importTables[$selectedTable];
    $headers = $t['fields'];
    $sampleRow = [];
    foreach ($headers as $f) {
        $sampleRow[] = $t['defaults'][$f] ?? 'مثال_' . $f;
    }
    exportCSV('template_' . $selectedTable, $headers, [$sampleRow]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $selectedTable) {
    requirePermission('import', 'import');

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!isset($importTables[$selectedTable])) {
        $importResult = ['error' => 'الجدول غير موجود'];
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $importResult = ['error' => 'خطأ في رفع الملف'];
    } elseif (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
        $importResult = ['error' => 'يجب أن يكون الملف CSV أو Excel (xlsx, xls)'];
    } else {
        $tableDef = $importTables[$selectedTable];

        // Parse file
        if ($ext === 'csv') {
            $parsed = parseCSV($file['tmp_name']);
        } else {
            $parsed = parseXLSX($file['tmp_name']);
        }

        if (isset($parsed['error'])) {
            $importResult = $parsed;
        } elseif (empty($parsed) || count($parsed) < 2) {
            $importResult = ['error' => 'الملف لا يحتوي على بيانات كافية (يجب أن يحتوي على عنوان + بيانات)'];
        } else {
            $headers = $parsed[0];
            $dataRows = array_slice($parsed, 1);

            // Map headers to field names
            $headerMap = [];
            foreach ($headers as $i => $h) {
                $h = trim($h);
                // Try to match header to field name
                $matched = false;
                foreach ($tableDef['fields'] as $field) {
                    if (str_replace('_', '', $field) === str_replace([' ', '_', '-'], '', $h)) {
                        $headerMap[$i] = $field;
                        $matched = true;
                        break;
                    }
                }
                // Try direct match
                if (!$matched && in_array($h, $tableDef['fields'])) {
                    $headerMap[$i] = $h;
                    $matched = true;
                }
                if (!$matched) {
                    $headerMap[$i] = $h; // keep original
                }
            }

            $imported = 0;
            $errors = [];

            foreach ($dataRows as $ri => $row) {
                $data = $tableDef['defaults'];

                // Map values from columns
                foreach ($headerMap as $ci => $field) {
                    if (isset($row[$ci]) && $row[$ci] !== '') {
                        $data[$field] = $row[$ci];
                    }
                }

                // Check required fields
                $missing = [];
                foreach ($tableDef['required'] as $req) {
                    if (empty($data[$req])) {
                        $missing[] = $req;
                    }
                }

                if (!empty($missing)) {
                    $errors[] = 'صف ' . ($ri + 2) . ': الحقول المطلوبة مفقودة - ' . implode(', ', $missing);
                    continue;
                }

                try {
                    $placeholders = implode(',', array_fill(0, count($data), '?'));
                    $fields = implode(',', array_keys($data));
                    $values = array_values($data);
                    insert("INSERT INTO $selectedTable ($fields) VALUES ($placeholders)", $values);
                    $imported++;
                } catch (Exception $e) {
                    $errors[] = 'صف ' . ($ri + 2) . ': ' . $e->getMessage();
                }
            }

            $importResult = [
                'success' => "تم استيراد $imported سجل بنجاح",
                'errors' => $errors,
                'total' => count($dataRows),
                'imported' => $imported,
            ];
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline">استيراد بيانات</h5></div>
    </nav>
    <div class="page-content">
        <?php if ($importResult): ?>
            <?php if (isset($importResult['error'])): ?>
                <div class="alert alert-danger"><?= $importResult['error'] ?></div>
            <?php else: ?>
                <div class="alert alert-success"><?= $importResult['success'] ?></div>
                <?php if (!empty($importResult['errors'])): ?>
                    <div class="alert alert-warning">
                        <strong>الأخطاء:</strong>
                        <ul class="mb-0"><?php foreach ($importResult['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="bi bi-upload"></i> رفع ملف بيانات</h6>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">الجدول <span class="text-danger">*</span></label>
                            <select name="table" class="form-select" required>
                                <option value="">-- اختر الجدول --</option>
                                <?php foreach ($importTables as $key => $t): ?>
                                <option value="<?= $key ?>" <?= $selectedTable === $key ? 'selected' : '' ?>><?= $t['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الملف <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-cloud-upload"></i> استيراد</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="bi bi-info-circle"></i> تعليمات الاستيراد</h6>
                <ul class="mb-0">
                    <li>يدعم الملفات بصيغة <strong>CSV</strong> و <strong>Excel (xlsx, xls)</strong></li>
                    <li>الصف الأول يجب أن يحتوي على عناوين الأعمدة</li>
                    <li>تأكد من تطابق أسماء الأعمدة مع الحقول في قاعدة البيانات</li>
                    <li>الحقول المطلوبة: حسب الجدول المختار</li>
                    <li>يمكنك <a href="?table=<?= $selectedTable ?>&action=template">تحميل قالب</a> لمعرفة التنسيق الصحيح</li>
                </ul>
            </div>
        </div>

        <?php if ($selectedTable && isset($importTables[$selectedTable])): $t = $importTables[$selectedTable]; ?>
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title mb-3">حقول جدول "<?= $t['label'] ?>"</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>الحقل</th><th>مطلوب</th><th>القيمة الافتراضية</th></tr></thead>
                        <tbody>
                            <?php foreach ($t['fields'] as $f): ?>
                            <tr>
                                <td><code><?= $f ?></code></td>
                                <td><?= in_array($f, $t['required']) ? '<span class="text-danger">نعم</span>' : '<span class="text-muted">لا</span>' ?></td>
                                <td><?= $t['defaults'][$f] ?? '<span class="text-muted">---</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="?table=<?= $selectedTable ?>&action=template" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> تحميل قالب</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <footer class="footer"><span>نظام Orion Aden &copy; <?= date('Y') ?></span></footer>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
