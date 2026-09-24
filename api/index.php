<?php
/**
 * Orion API - للاستخدام من تطبيق الجوال أو سطح المكتب
 * 
 * الاستخدام:
 *   POST /api/index.php?action=login
 *   GET  /api/index.php?action=student_info&token=...
 *   GET  /api/index.php?action=student_grades&token=...
 *   GET  /api/index.php?action=student_attendance&token=...
 *   GET  /api/index.php?action=teacher_courses&token=...
 *   GET  /api/index.php?action=teacher_students&token=...&course_id=...
 *   POST /api/index.php?action=teacher_attendance&token=...
 */

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse($msg, $code = 400) {
    jsonResponse(['success' => false, 'error' => $msg], $code);
}

function auth($token) {
    $parts = explode(':', base64_decode($token));
    if (count($parts) !== 2) return null;
    $user = getRow("SELECT id, role FROM users WHERE id = ? AND is_active = 1", [(int)$parts[0]]);
    if (!$user) return null;
    // Simple token check: base64(user_id:timestamp)
    return $user;
}

switch ($action) {

    case 'login':
        if ($method !== 'POST') errorResponse('POST required');
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if (!$username || !$password) errorResponse('Username and password required');

        $user = getRow("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1", [$username, $username]);
        if (!$user || !password_verify($password, $user['password'])) {
            errorResponse('Invalid credentials', 401);
        }

        $token = base64_encode($user['id'] . ':' . time());
        jsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ]);
        break;

    case 'student_info':
        $user = auth($_GET['token'] ?? '');
        if (!$user || $user['role'] !== 'viewer') errorResponse('Unauthorized', 401);
        $code = trim($_GET['student_code'] ?? $_SESSION['student_code'] ?? '');
        if (!$code) errorResponse('Student code required');
        $student = getRow("SELECT id, student_code, first_name, last_name, phone, email, photo FROM students WHERE student_code = ?", [$code]);
        if (!$student) errorResponse('Student not found', 404);
        jsonResponse(['success' => true, 'student' => $student]);
        break;

    case 'student_grades':
        $user = auth($_GET['token'] ?? '');
        if (!$user || $user['role'] !== 'viewer') errorResponse('Unauthorized', 401);
        $code = trim($_GET['student_code'] ?? '');
        if (!$code) errorResponse('Student code required');
        $student = getRow("SELECT id FROM students WHERE student_code = ?", [$code]);
        if (!$student) errorResponse('Student not found', 404);
        $enrollments = getRows("SELECT c.course_name, c.course_code, e.grade, e.status, e.enrollment_date FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? AND e.status != 'dropped'", [$student['id']]);
        jsonResponse(['success' => true, 'grades' => $enrollments]);
        break;

    case 'student_attendance':
        $user = auth($_GET['token'] ?? '');
        if (!$user || $user['role'] !== 'viewer') errorResponse('Unauthorized', 401);
        $code = trim($_GET['student_code'] ?? '');
        if (!$code) errorResponse('Student code required');
        $student = getRow("SELECT id FROM students WHERE student_code = ?", [$code]);
        if (!$student) errorResponse('Student not found', 404);
        $attendance = getRows("SELECT a.attendance_date, a.status, c.course_name FROM attendance a JOIN courses c ON a.course_id = c.id WHERE a.student_id = ? ORDER BY a.attendance_date DESC LIMIT 50", [$student['id']]);
        jsonResponse(['success' => true, 'attendance' => $attendance]);
        break;

    case 'teacher_courses':
        $user = auth($_GET['token'] ?? '');
        if (!$user || $user['role'] !== 'teacher') errorResponse('Unauthorized', 401);
        $teacherCode = trim($_GET['teacher_code'] ?? '');
        if (!$teacherCode) errorResponse('Teacher code required');
        $teacher = getRow("SELECT id FROM teachers WHERE teacher_code = ?", [$teacherCode]);
        if (!$teacher) errorResponse('Teacher not found', 404);
        $courses = getRows("SELECT c.id, c.course_code, c.course_name, c.room, c.schedule_time, c.schedule_days, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') AS enrolled FROM courses c WHERE c.teacher_id = ? AND c.status = 'active'", [$teacher['id']]);
        jsonResponse(['success' => true, 'courses' => $courses]);
        break;

    case 'teacher_students':
        $user = auth($_GET['token'] ?? '');
        if (!$user || $user['role'] !== 'teacher') errorResponse('Unauthorized', 401);
        $courseId = (int)($_GET['course_id'] ?? 0);
        if (!$courseId) errorResponse('Course ID required');
        $students = getRows("SELECT s.student_code, s.first_name, s.last_name, e.grade FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ? AND e.status = 'active' ORDER BY s.first_name", [$courseId]);
        jsonResponse(['success' => true, 'students' => $students]);
        break;

    case 'teacher_attendance':
        if ($method !== 'POST') errorResponse('POST required');
        $user = auth($_POST['token'] ?? '');
        if (!$user || $user['role'] !== 'teacher') errorResponse('Unauthorized', 401);
        $courseId = (int)($_POST['course_id'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $statuses = $_POST['status'] ?? [];
        if (!$courseId || empty($statuses)) errorResponse('Course ID and statuses required');

        foreach ($statuses as $studentCode => $status) {
            $student = getRow("SELECT id FROM students WHERE student_code = ?", [$studentCode]);
            if ($student) {
                $existing = getRow("SELECT id FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?", [$student['id'], $courseId, $date]);
                if ($existing) {
                    update("UPDATE attendance SET status=?, recorded_by=? WHERE id=?", [$status, $user['id'], $existing['id']]);
                } else {
                    insert("INSERT INTO attendance (student_id, course_id, attendance_date, status, recorded_by) VALUES (?,?,?,?,?)", [$student['id'], $courseId, $date, $status, $user['id']]);
                }
            }
        }
        jsonResponse(['success' => true, 'message' => 'Attendance saved']);
        break;

    default:
        jsonResponse([
            'success' => true,
            'message' => 'Orion API v1.0',
            'endpoints' => [
                'POST login' => '?action=login',
                'GET student_info' => '?action=student_info&token=...&student_code=...',
                'GET student_grades' => '?action=student_grades&token=...&student_code=...',
                'GET student_attendance' => '?action=student_attendance&token=...&student_code=...',
                'GET teacher_courses' => '?action=teacher_courses&token=...&teacher_code=...',
                'GET teacher_students' => '?action=teacher_students&token=...&course_id=...',
                'POST teacher_attendance' => '?action=teacher_attendance',
            ]
        ]);
}
