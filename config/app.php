<?php
// Application configuration
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/permissions.php';

// Base URL (fixed from app.php location)
$basePath = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
define('BASE_URL', rtrim(substr($basePath, strlen($docRoot)), '/'));
define('BASE_PATH', $basePath);
define('ASSET_VER', '4');

// Timezone
$tz = getRow("SELECT setting_value FROM settings WHERE setting_key = 'timezone'");
date_default_timezone_set($tz ? $tz['setting_value'] : 'Asia/Baghdad');

// Language
$lang = getRow("SELECT setting_value FROM settings WHERE setting_key = 'language'");
$lang = $lang ? $lang['setting_value'] : 'ar';

// Check authentication
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['error'] = 'ليس لديك صلاحية للوصول إلى هذه الصفحة';
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    return getRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'رمز CSRF غير صالح']);
            exit;
        }
        setFlash('error', 'رمز CSRF غير صالح. يرجى إعادة تحميل الصفحة.');
        $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header('Location: ' . $referer);
        exit;
    }
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash() {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

// Log error
function appLog($message, $level = 'INFO') {
    $log = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    error_log($log, 3, __DIR__ . '/../error.log');
}

// Get setting
function getSetting($key, $default = '') {
    $row = getRow("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $row ? $row['setting_value'] : $default;
}

// ---------- Role-based Permission Helpers ----------

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], (array)$roles);
}

function getRoleLabel($role = null) {
    global $roleLabels;
    $role = $role ?? ($_SESSION['role'] ?? '');
    return $roleLabels[$role] ?? $role;
}

function hasPermission($module, $action) {
    global $rolePermissions;
    $role = $_SESSION['role'] ?? '';
    if (!isset($rolePermissions[$role])) return false;
    if (!isset($rolePermissions[$role][$module])) return false;
    return in_array($action, $rolePermissions[$role][$module]);
}

function requirePermission($module, $action) {
    if (!hasPermission($module, $action)) {
        if (!isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login.php');
        } else {
            $_SESSION['error'] = 'ليس لديك صلاحية للقيام بهذا الإجراء';
            header('Location: ' . BASE_URL . '/dashboard.php');
        }
        exit;
    }
}

function getSidebarModules() {
    global $rolePermissions;
    $role = $_SESSION['role'] ?? '';
    $modules = $rolePermissions[$role] ?? [];
    return array_keys(array_filter($modules, function($actions) {
        return !empty($actions);
    }));
}

// Upload validation
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
$allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function validateUpload($file, $allowedTypes, $maxSize = MAX_UPLOAD_SIZE) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'خطأ في رفع الملف (رمز: ' . $file['error'] . ')'];
    }
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'حجم الملف كبير جداً. الحد الأقصى: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }
    // Try finfo first, fall back to extension check
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'csv' => 'text/csv', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    }
    if (!in_array($mime, $allowedTypes)) {
        return ['valid' => false, 'error' => 'نوع الملف غير مسموح. الأنواع المسموحة: ' . implode(', ', $allowedTypes)];
    }
    return ['valid' => true];
}

// Rate limiting
function checkLoginRateLimit($identifier) {
    $window = 300; // 5 minutes
    $maxAttempts = 5;
    $now = time();
    $attempts = $_SESSION['login_attempts'][$identifier] ?? [];
    $attempts = array_filter($attempts, function($t) use ($now, $window) {
        return $t > ($now - $window);
    });
    $_SESSION['login_attempts'][$identifier] = $attempts;
    return count($attempts) < $maxAttempts;
}

function recordLoginAttempt($identifier) {
    $_SESSION['login_attempts'][$identifier][] = time();
}

function generateStudentCode() {
    $yearSuffix = date('y');
    $prefix = 'OA' . $yearSuffix;
    $last = getRow("SELECT MAX(student_code) as last_code FROM students WHERE student_code LIKE ?", [$prefix . '%']);
    if ($last && $last['last_code']) {
        $num = (int)substr($last['last_code'], 4) + 1;
    } else {
        $num = 1;
    }
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}
