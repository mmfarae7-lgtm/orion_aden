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

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
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
