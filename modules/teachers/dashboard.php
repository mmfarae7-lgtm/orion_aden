<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SESSION['role'] !== 'teacher' || !isset($_SESSION['teacher_id'])) {
    header('Location: portal.php');
    exit;
}

$teacherId = $_SESSION['teacher_id'];
$teacher = getRow("SELECT * FROM teachers WHERE id = ?", [$teacherId]);
if (!$teacher) { unset($_SESSION['teacher_id']); header('Location: portal.php'); exit; }

$pageTitle = 'لوحة المدرس';
$courses = getRows("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') AS enrolled FROM courses c WHERE c.teacher_id = ? AND c.status = 'active'", [$teacherId]);

// Grade update inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    requireCsrf();
    $courseId = (int)$_POST['course_id'];
    $gradeFields = ['attendance_grade', 'vocabulary', 'writing', 'speaking', 'test', 'exam', 'total'];
    $studentCodes = array_keys($_POST['attendance_grade'] ?? $_POST['vocabulary'] ?? $_POST['writing'] ?? []);
    foreach ($studentCodes as $studentCode) {
        $student = getRow("SELECT id FROM students WHERE student_code = ?", [$studentCode]);
        if ($student) {
            $sets = [];
            $params = [];
            foreach ($gradeFields as $f) {
                $val = trim($_POST[$f][$studentCode] ?? '');
                $sets[] = "$f = ?";
                $params[] = $val ?: null;
            }
            $params[] = $student['id'];
            $params[] = $courseId;
            update("UPDATE enrollments SET " . implode(', ', $sets) . " WHERE student_id = ? AND course_id = ?", $params);
        }
    }
    setFlash('success', 'تم تحديث الدرجات');
    header('Location: dashboard.php');
    exit;
}

// Attendance recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attend'])) {
    requireCsrf();
    $courseId = (int)$_POST['course_id'];
    $date = $_POST['date'] ?? date('Y-m-d');
    $students = getRows("SELECT student_id FROM enrollments WHERE course_id = ? AND status = 'active'", [$courseId]);
    foreach ($students as $s) {
        $sid = $s['student_id'];
        $status = $_POST['status'][$sid] ?? 'absent';
        $notes = trim($_POST['notes'][$sid] ?? '');
        $existing = getRow("SELECT id FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?", [$sid, $courseId, $date]);
        if ($existing) {
            update("UPDATE attendance SET status=?, notes=?, recorded_by=? WHERE id=?", [$status, $notes, $_SESSION['user_id'], $existing['id']]);
        } else {
            insert("INSERT INTO attendance (student_id, course_id, attendance_date, status, notes, recorded_by) VALUES (?,?,?,?,?,?)", [$sid, $courseId, $date, $status, $notes, $_SESSION['user_id']]);
        }
    }
    setFlash('success', 'تم تسجيل الحضور');
    header('Location: dashboard.php');
    exit;
}

// Remove student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_student'])) {
    requireCsrf();
    $code = trim($_POST['student_code']);
    $courseId = (int)$_POST['course_id'];
    $student = getRow("SELECT id FROM students WHERE student_code = ?", [$code]);
    if ($student) {
        delete("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?", [$student['id'], $courseId]);
        setFlash('success', 'تم حذف الطالب من الدورة');
    }
    header('Location: dashboard.php');
    exit;
}

// Enroll student by code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
    requireCsrf();
    $courseId = (int)$_POST['course_id'];
    $code = trim($_POST['student_code']);
    $student = getRow("SELECT id FROM students WHERE student_code = ?", [$code]);
    if ($student) {
        $existing = getRow("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", [$student['id'], $courseId]);
        if (!$existing) {
            insert("INSERT INTO enrollments (student_id, course_id, enrollment_date, status, created_by) VALUES (?,?,?,?,?)", [$student['id'], $courseId, date('Y-m-d'), 'active', $_SESSION['user_id']]);
            setFlash('success', "تم إضافة الطالب $code");
        } else {
            setFlash('error', 'الطالب مسجل بالفعل');
        }
    } else {
        setFlash('error', 'كود الطالب غير موجود');
    }
    header('Location: dashboard.php');
    exit;
}

// Grade file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['grade_file'])) {
    requireCsrf();
    $courseId = (int)$_POST['course_id'];
    $file = $_FILES['grade_file'];
    $valid = validateUpload($_FILES['grade_file'], ['text/csv', 'text/plain', 'application/vnd.ms-excel']);
    if (!$valid['valid']) { setFlash('error', $valid['error']); header('Location: dashboard.php'); exit; }
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmp = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $imported = 0;
        $gradeFields = ['attendance_grade', 'vocabulary', 'writing', 'speaking', 'test', 'exam', 'total'];
        if ($ext === 'csv') {
            if (($handle = fopen($tmp, 'r')) !== false) {
                $header = fgetcsv($handle);
                $fieldMap = [];
                if ($header) {
                    foreach ($header as $i => $h) {
                        $hClean = preg_replace('/[\s\-_:]+/u', '', mb_strtolower(trim($h)));
                        $map = ['studentcode' => 'code', 'student_code' => 'code', 'code' => 'code', 'كود' => 'code',
                                'attendance' => 'attendance_grade', 'attendance_grade' => 'attendance_grade',
                                'vocabulary' => 'vocabulary', 'writing' => 'writing', 'speaking' => 'speaking',
                                'test' => 'test', 'exam' => 'exam', 'total' => 'total'];
                        $fieldMap[$i] = $map[$hClean] ?? null;
                    }
                }
                while (($row = fgetcsv($handle)) !== false) {
                    $code = '';
                    $vals = [];
                    foreach ($fieldMap as $i => $f) {
                        if ($f === 'code') $code = trim($row[$i] ?? '');
                        elseif ($f) $vals[$f] = trim($row[$i] ?? '');
                    }
                    if ($code) {
                        $student = getRow("SELECT id FROM students WHERE student_code = ?", [$code]);
                        if ($student) {
                            $sets = [];
                            $params = [];
                            foreach ($gradeFields as $f) {
                                $v = $vals[$f] ?? '';
                                $sets[] = "$f = ?";
                                $params[] = $v ?: null;
                            }
                            $params[] = $student['id'];
                            $params[] = $courseId;
                            update("UPDATE enrollments SET " . implode(', ', $sets) . " WHERE student_id = ? AND course_id = ?", $params);
                            $imported++;
                        }
                    }
                }
                fclose($handle);
            }
        }
        setFlash('success', "تم استيراد درجات $imported طالب");
    } else {
        setFlash('error', 'فشل رفع الملف');
    }
    header('Location: dashboard.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
?>
<style>.no-sidebar .main-content{margin-right:0!important;padding-right:20px!important}.student-row{cursor:pointer}</style>
<div class="main-content no-sidebar">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><h5 class="mb-0 d-inline"><i class="bi bi-person-workspace"></i> لوحة المدرس - <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></h5></div>
        <div>
            <a href="portal.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-repeat"></i> تبديل المدرس</a>
            <a href="<?= BASE_URL ?>/modules/messaging/index.php" class="btn btn-outline-success btn-sm"><i class="bi bi-chat-dots"></i> رسائل</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> خروج</a>
        </div>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?>"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>

        <?php if (empty($courses)): ?>
            <div class="alert alert-info">لا توجد دورات مسندة إليك</div>
        <?php else: ?>
            <?php foreach ($courses as $c): ?>
            <div class="table-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        <?= htmlspecialchars($c['course_name']) ?>
                        <small class="text-muted">(<?= htmlspecialchars($c['course_code']) ?>)</small>
                        <?php if ($c['room']): ?><span class="badge bg-secondary"><?= htmlspecialchars($c['room']) ?></span><?php endif; ?>
                        <?php if ($c['schedule_time']): ?><span class="badge bg-info"><?= htmlspecialchars($c['schedule_time']) ?></span><?php endif; ?>
                        <span class="badge bg-primary"><?= $c['enrolled'] ?> طالب</span>
                    </h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-warning" onclick="toggleAttendance(<?= $c['id'] ?>)"><i class="bi bi-calendar-check"></i> حضور</button>
                        <button class="btn btn-sm btn-primary" onclick="toggleGrades(<?= $c['id'] ?>)"><i class="bi bi-file-earmark-arrow-up"></i> درجات</button>
                        <button class="btn btn-sm btn-success" onclick="toggleEnroll(<?= $c['id'] ?>)"><i class="bi bi-person-plus"></i> إضافة طالب</button>
                    </div>
                </div>

                <div id="attendance_<?= $c['id'] ?>" style="display:none">
                    <?php
                    $students = getRows("SELECT s.id, s.student_code, s.first_name, s.last_name FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ? AND e.status = 'active' AND s.status = 'active'", [$c['id']]);
                    $today = date('Y-m-d');
                    ?>
                    <form method="POST" class="border p-3 rounded bg-light">
                        <?= csrfField() ?>
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <div class="mb-2"><input type="date" name="date" class="form-control form-control-sm d-inline w-auto" value="<?= $today ?>"></div>
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>#</th><th>الكود</th><th>الاسم</th><th>الحالة</th><th>ملاحظات</th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $i => $s): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($s['student_code']) ?></td>
                                    <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                    <td>
                                        <select name="status[<?= $s['id'] ?>]" class="form-select form-select-sm">
                                            <option value="present">حاضر</option>
                                            <option value="absent">غائب</option>
                                            <option value="late">متأخر</option>
                                            <option value="excused">بعذر</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="notes[<?= $s['id'] ?>]" class="form-control form-control-sm" placeholder="..."></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="attend" class="btn btn-warning btn-sm mt-2"><i class="bi bi-save"></i> حفظ</button>
                    </form>
                </div>

                <div id="grades_<?= $c['id'] ?>" style="display:none">
                    <form method="POST" enctype="multipart/form-data" class="border p-3 rounded bg-light mb-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <label class="form-label small">رفع ملف درجات (CSV - الصف الأول: عناوين الأعمدة)</label>
                        <div class="d-flex gap-2">
                            <input type="file" name="grade_file" class="form-control form-control-sm" accept=".csv" required>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-upload"></i> رفع</button>
                        </div>
                    </form>
                </div>
                <div id="enroll_<?= $c['id'] ?>" style="display:none">
                    <form method="POST" class="border p-3 rounded bg-light mb-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="enroll_student" value="1">
                        <label class="form-label small">إضافة طالب إلى الدورة</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="student_code" class="form-control form-control-sm" placeholder="أدخل كود الطالب" required>
                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-person-plus"></i> إضافة</button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive mt-2">
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>الطالب</th><th>Attendance</th><th>Vocabulary</th><th>Writing</th><th>Speaking</th><th>Test</th><th>Exam</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                            <?php $enrolled = getRows("SELECT s.student_code, s.first_name, s.last_name, e.grade, e.attendance_grade, e.vocabulary, e.writing, e.speaking, e.test, e.exam, e.total FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ? AND e.status = 'active' ORDER BY s.first_name", [$c['id']]); ?>
                            <?php foreach ($enrolled as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                                <td><input type="text" name="attendance_grade[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['attendance_grade'] ?? '') ?>"></td>
                                <td><input type="text" name="vocabulary[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['vocabulary'] ?? '') ?>"></td>
                                <td><input type="text" name="writing[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['writing'] ?? '') ?>"></td>
                                <td><input type="text" name="speaking[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['speaking'] ?? '') ?>"></td>
                                <td><input type="text" name="test[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['test'] ?? '') ?>"></td>
                                <td><input type="text" name="exam[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['exam'] ?? '') ?>"></td>
                                <td><input type="text" name="total[<?= $e['student_code'] ?>]" class="form-control form-control-sm" style="width:60px" value="<?= htmlspecialchars($e['total'] ?? '') ?>"></td>
                                <td>
                                    <button type="submit" name="remove_student" value="1" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف الطالب من الدورة؟')"><i class="bi bi-x"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                        <button type="submit" name="save_grades" class="btn btn-sm btn-primary mt-1"><i class="bi bi-save"></i> حفظ الدرجات</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script>
function toggleAttendance(id) {
    const div = document.getElementById('attendance_' + id);
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
function toggleGrades(id) {
    const div = document.getElementById('grades_' + id);
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
function toggleEnroll(id) {
    const div = document.getElementById('enroll_' + id);
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
