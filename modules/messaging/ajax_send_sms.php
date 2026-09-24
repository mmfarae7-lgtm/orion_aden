<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('messaging', 'send');
requireCsrf();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$studentIds = $_POST['student_ids'] ?? [];
$message = trim($_POST['message'] ?? '');
$provider = getSetting('sms_provider', 'twilio');

if (empty($studentIds) || !is_array($studentIds)) {
    echo json_encode(['success' => false, 'error' => 'اختر طالباً واحداً على الأقل']);
    exit;
}
if (!$message) {
    echo json_encode(['success' => false, 'error' => 'اكتب الرسالة']);
    exit;
}

// Check SMS API key is configured
$apiKey = getSetting('sms_api_key', '');
if (!$apiKey && $provider !== 'twilio') {
    echo json_encode(['success' => false, 'error' => 'مفتاح API لـ SMS غير مضبوط في الإعدادات']);
    exit;
}
if ($provider === 'twilio' && (!getSetting('twilio_account_sid', '') || !$apiKey)) {
    echo json_encode(['success' => false, 'error' => 'Twilio Account SID أو Auth Token غير مضبوط في الإعدادات']);
    exit;
}

// Fetch phone numbers
$placeholders = implode(',', array_fill(0, count($studentIds), '?'));
$students = getRows(
    "SELECT id, student_code, first_name, last_name, phone FROM students WHERE id IN ($placeholders) AND phone != '' AND phone IS NOT NULL",
    $studentIds
);

if (empty($students)) {
    echo json_encode(['success' => false, 'error' => 'لا يوجد أرقام هواتف صالحة للمرسل إليهم']);
    exit;
}

require_once __DIR__ . '/../../lib/SmsSender.php';
$sender = new SmsSender($provider);
$successCount = 0;
$errors = [];

foreach ($students as $student) {
    $result = $sender->send($student['phone'], $message);
    if ($result['success']) {
        $successCount++;
        // Log to message_log
        insert("INSERT INTO message_log (student_id, method, message, status) VALUES (?, 'sms', ?, 'sent')", [
            $student['id'],
            $message,
        ]);
    } else {
        $errors[] = $student['first_name'] . ' ' . $student['last_name'] . ': ' . $result['error'];
        insert("INSERT INTO message_log (student_id, method, message, status) VALUES (?, 'sms', ?, 'failed')", [
            $student['id'],
            $message,
        ]);
    }
}

echo json_encode([
    'success' => $successCount > 0,
    'sent' => $successCount,
    'failed' => count($errors),
    'total' => count($students),
    'errors' => $errors,
]);
