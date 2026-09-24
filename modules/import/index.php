<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../libs/export.php';
requireAuth();
requirePermission('import', 'view');

$pageTitle = 'استيراد بيانات';
$importResult = null;

// Database table definitions for import
$importTables = [
    'students' => [
        'label' => 'الطلاب',
        'fields' => ['student_code', 'first_name', 'last_name', 'father_name', 'mother_name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'enrollment_date', 'status', 'notes'],
        'required' => ['student_code', 'first_name', 'last_name', 'gender'],
        'defaults' => ['status' => 'active', 'enrollment_date' => date('Y-m-d'), 'created_by' => $_SESSION['user_id']],
    ],
    'teachers' => [
        'label' => 'المدرسين',
        'fields' => ['teacher_code', 'first_name', 'last_name', 'gender', 'phone', 'email', 'address', 'qualification', 'specialization', 'salary', 'hire_date', 'status'],
        'required' => ['teacher_code', 'first_name', 'last_name', 'hire_date'],
        'defaults' => ['status' => 'active', 'created_by' => $_SESSION['user_id']], => [
        'label' => 'الدورات',
        'fields' => ['course_code', 'course_name', 'description', 'teacher_id', 'room', 'schedule_time', 'fee', 'max_students', 'status'],
        'required' => ['course_code', 'course_name'],
        'defaults' => ['status' => 'active', 'max_students' => 30, 'created_by' => $_SESSION['user_id']],
    ],
    'fees' => [
        'label' => 'الرسوم',
        'fields' => ['fee_name', 'amount', 'description', 'frequency'],
        'required' => ['fee_name', 'amount'],
        'defaults' => ['frequency' => 'one_time', 'created_by' => $_SESSION['user_id']],
    ],
    'enrollments' => [
        'label' => 'نتائج الطلاب',
        'fields' => ['student_id', 'course_id', 'attendance_grade', 'vocabulary', 'writing', 'speaking', 'test', 'exam', 'total', 'status'],
        'required' => ['student_id', 'course_id'],
        'defaults' => ['status' => 'active', 'created_by' => $_SESSION['user_id']],
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
    requireCsrf();
    requirePermission('import', 'import');

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $valid = validateUpload($_FILES['file'], ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    if (!$valid['valid']) { setFlash('error', $valid['error']); header('Location: index.php'); exit; }

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

            // Arabic header name mapping (normalize both keys and lookup)
            $arabicFieldMap = [];
            $addMap = function($names, $field) use (&$arabicFieldMap) {
                foreach ((array)$names as $n) {
                    $key = preg_replace('/[\s\-_:]+/u', '', mb_strtolower(trim($n)));
                    $arabicFieldMap[$key] = $field;
                }
            };
            $addMap(['رمز الطالب','كود الطالب','student code','student_code','code','STU'], 'student_code');
            $addMap(['الاسم الأول','الاسم','first name','first_name','الاسم الاول'], 'first_name');
            $addMap(['اسم الأب','الأب','father name','father_name','اسم الاب','الاب'], 'father_name');
            $addMap(['اسم الأم','الأم','mother name','mother_name','اسم الام','الام'], 'mother_name');
            $addMap(['الاسم الأخير','العائلة','اللقب','last name','last_name','الاسم الاخير'], 'last_name');
            $addMap(['الاسم بالإنجليزي','name en','name_en','english name','الاسم الانجليزي'], 'name_en');
            $addMap(['الاسم بالعربي','arabic name','name_ar','الاسم العربي','الاسم بالعربية','الاسم الكامل','full name','اسم الطالب','طالب'], 'name_ar');
            $addMap(['الاسم الأوسط','middle name','middle_name','الاسم الاوسط'], 'middle_name');
            $addMap(['الرقم','رقم','number','id','student id','الترقيم','الكود','code','student_code'], 'student_code');
            $addMap(['رقم الجوال','الجوال','mobile','رقم الموبايل','phone number'], 'phone');
            $addMap(['سنة الميلاد','year of birth','years of birth','birth year','سنة الولادة'], '_birth_year');
            $addMap(['الجنس','النوع','gender'], 'gender');
            $addMap(['تاريخ الميلاد','الميلاد','date of birth','date_of_birth','تاريخ الميلاد'], 'date_of_birth');
            $addMap(['الجنسية','nationality'], 'nationality');
            $addMap(['العنوان','address'], 'address');
            $addMap(['الهاتف','رقم الهاتف','جوال','phone','telephone','mobile'], 'phone');
            $addMap(['البريد','الايميل','email','e-mail','البريد الالكتروني'], 'email');
            $addMap(['تاريخ التسجيل','enrollment date','تسجيل','enrollment_date','تاريخ القيد'], 'enrollment_date');
            $addMap(['الحالة','status'], 'status');
            $addMap(['ملاحظات','notes'], 'notes');
            $addMap(['رقم الطالب','student id','student_id','معرف الطالب','id student'], 'student_id');
            $addMap(['رقم الدورة','course id','course_id','معرف الدورة','id course'], 'course_id');
            $addMap(['الحضور','attendance','attendance grade','attendance_grade','درجة الحضور'], 'attendance_grade');
            $addMap(['المفردات','vocabulary','مفردات'], 'vocabulary');
            $addMap(['الكتابة','writing','كتابة'], 'writing');
            $addMap(['المحادثة','speaking','speaking','محادثة'], 'speaking');
            $addMap(['الاختبار','test','اختبار'], 'test');
            $addMap(['الامتحان','exam','امتحان','final','نهائي'], 'exam');
            $addMap(['المجموع','total','المجموع الكلي'], 'total');

            // Map headers to field names
            $headerMap = [];
            foreach ($headers as $i => $h) {
                $h = trim((string)$h);
                if ($h === '') { $headerMap[$i] = null; continue; }
                $matched = false;

                // 1. Try Arabic/common name mapping with normalized keys
                $hKey = preg_replace('/[\s\-_:]+/u', '', mb_strtolower($h));
                if (isset($arabicFieldMap[$hKey])) {
                    $headerMap[$i] = $arabicFieldMap[$hKey];
                    $matched = true;
                }

                // 2. Try fuzzy match (strip underscores, spaces)
                if (!$matched) {
                    foreach ($tableDef['fields'] as $field) {
                        $fieldClean = str_replace('_', '', mb_strtolower($field));
                        $headerClean = str_replace([' ', '_', '-'], '', mb_strtolower($h));
                        if ($fieldClean === $headerClean) {
                            $headerMap[$i] = $field;
                            $matched = true;
                            break;
                        }
                    }
                }

                // 3. If still no match, skip this column
                if (!$matched) {
                    $headerMap[$i] = null;
                }
            }

            // Log matched headers for debugging
            $matchedHeaders = array_filter($headerMap, fn($v) => $v !== null);
            $skippedHeaders = [];
            $mappedInfo = [];
            foreach ($headers as $i => $h) {
                if ($headerMap[$i] === null) {
                    $skippedHeaders[] = $h;
                } else {
                    $mappedInfo[] = "$h → {$headerMap[$i]}";
                }
            }

            $imported = 0;
            $errors = [];

            foreach ($dataRows as $ri => $row) {
                $data = $tableDef['defaults'];

                // Map values from columns (skip unmatched columns marked as null)
                foreach ($headerMap as $ci => $field) {
                    if ($field !== null && isset($row[$ci]) && $row[$ci] !== '') {
                        $data[$field] = trim($row[$ci]);
                    }
                }

                // Convert Arabic/alternate values to English
                if (!empty($data['gender'])) {
                    $g = trim($data['gender']);
                    if (in_array($g, ['ذكر', 'male', 'M', 'male'])) $data['gender'] = 'male';
                    elseif (in_array($g, ['أنثى', 'female', 'F', 'female', 'انثى'])) $data['gender'] = 'female';
                }
                if (!empty($data['status'])) {
                    $s = trim($data['status']);
                    if (in_array($s, ['نشط', 'active', 'Active', 'مفعل'])) $data['status'] = 'active';
                    elseif (in_array($s, ['غير نشط', 'inactive', 'Inactive', 'معطل', 'غير مفعل'])) $data['status'] = 'inactive';
                    elseif (in_array($s, ['متخرج', 'graduated', 'Graduated', 'خريج'])) $data['status'] = 'graduated';
                    elseif (in_array($s, ['منقطع', 'suspended', 'معلق', 'موقوف'])) $data['status'] = 'suspended';
                }
                // Ensure valid status
                if (empty($data['status'])) {
                    $data['status'] = 'active';
                }

                // If first_name is empty but name_ar exists, use it as first_name
                if (empty($data['first_name']) && !empty($data['name_ar'])) {
                    $data['first_name'] = $data['name_ar'];
                }

                // Extract last_name from first_name if last_name is missing
                if (empty($data['last_name']) && !empty($data['first_name'])) {
                    $parts = preg_split('/\s+/', trim($data['first_name']));
                    if (count($parts) > 1) {
                        $data['last_name'] = array_pop($parts);
                        $data['first_name'] = implode(' ', $parts);
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

            $info = 'الأعمدة المتطابقة: ' . implode(', ', $mappedInfo);
            if (!empty($skippedHeaders)) {
                $info .= ' | تم التخطي: ' . implode(', ', $skippedHeaders);
            }
            $importResult = [
                'success' => "تم استيراد $imported سجل بنجاح",
                'info' => $info,
                'errors' => $errors,
                'total' => count($dataRows),
                'imported' => $imported,
            ];
        }
    }

    // Redirect after POST to prevent resubmit
    $_SESSION['import_result'] = $importResult;
    header('Location: ' . BASE_URL . '/modules/import/index.php?table=' . urlencode($selectedTable));
    exit;
}

$importResult = $_SESSION['import_result'] ?? null;
unset($_SESSION['import_result']);

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
                <?php if (!empty($importResult['info'])): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($importResult['info']) ?></div>
                <?php endif; ?>
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
                    <?= csrfField() ?>
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
