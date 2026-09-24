<?php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
requirePermission('messaging', 'send');

$pageTitle = 'إرسال رسائل';

// Get courses for filtering
$courses = getRows("SELECT id, course_code, course_name FROM courses WHERE status = 'active' ORDER BY course_name");

// Get students with filters
$courseFilter = (int)($_GET['course_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$withPhone = isset($_GET['with_phone']) ? (int)$_GET['with_phone'] : 0;

$where = "WHERE s.status = 'active'";
$params = [];
if ($courseFilter > 0) {
    $where .= " AND e.course_id = ? AND e.status = 'active'";
    $params[] = $courseFilter;
}
if ($search) {
    $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_code LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($withPhone === 1) {
    $where .= " AND s.phone != '' AND s.phone IS NOT NULL";
}

$students = getRows("SELECT DISTINCT s.id, s.student_code, s.first_name, s.last_name, s.phone, s.email FROM students s LEFT JOIN enrollments e ON s.id = e.student_id $where ORDER BY s.first_name", $params);

// WhatsApp number from settings
$whatsappNumber = getSetting('whatsapp_number', '');

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div><button class="btn btn-link d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button><h5 class="mb-0 d-inline"><i class="bi bi-chat-dots"></i> إرسال رسائل</h5></div>
        <div>
            <a href="history.php" class="btn btn-outline-info btn-sm"><i class="bi bi-clock-history"></i> السجل</a>
        </div>
    </nav>
    <div class="page-content">
        <?php $flash = getFlash(); foreach ($flash as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>

        <div class="row g-4">
            <?= csrfField() ?>
            <div class="col-md-5">
                <div class="table-container">
                    <h6 class="mb-3"><i class="bi bi-funnel"></i> تصفية الطلاب</h6>
                    <form method="GET" class="row g-2">
                        <div class="col-12">
                            <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- كل الدورات --</option>
                                <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $courseFilter === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-8">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="بحث باسم أو كود..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="with_phone" value="1" id="withPhone" class="form-check-input" <?= $withPhone ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label for="withPhone" class="form-check-label small">فقط الطلاب الذين لديهم رقم هاتف</label>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-container mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-people"></i> الطلاب (<?= count($students) ?>)</h6>
                        <div class="form-check">
                            <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleAll(this)">
                            <label for="selectAll" class="form-check-label small">تحديد الكل</label>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th style="width:40px"></th><th>الكود</th><th>الاسم</th><th>الهاتف</th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><input type="checkbox" class="student-check" value="<?= $s['id'] ?>" data-code="<?= $s['student_code'] ?>" data-name="<?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>" data-phone="<?= htmlspecialchars($s['phone'] ?? '') ?>"></td>
                                    <td><?= htmlspecialchars($s['student_code']) ?></td>
                                    <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                    <td><?= htmlspecialchars($s['phone'] ?? '---') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($students)): ?>
                                <tr><td colspan="4" class="text-muted text-center">لا يوجد طلاب</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="table-container">
                    <h6 class="mb-3"><i class="bi bi-pencil-square"></i> إنشاء رسالة</h6>
                    <div class="mb-3">
                        <label class="form-label">المستلمون</label>
                        <div id="recipientList" class="border rounded p-2 bg-light" style="min-height:50px;max-height:120px;overflow-y:auto">
                            <small class="text-muted">اختر طلاباً من القائمة...</small>
                        </div>
                        <small class="text-muted" id="recipientCount">0 طالب</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نوع الإرسال</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="radio" name="sendMethod" value="whatsapp" id="mWhatsapp" class="form-check-input" checked>
                                <label for="mWhatsapp" class="form-check-label"><i class="bi bi-whatsapp text-success"></i> WhatsApp</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="sendMethod" value="email" id="mEmail" class="form-check-input">
                                <label for="mEmail" class="form-check-label"><i class="bi bi-envelope text-primary"></i> Email</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="sendMethod" value="sms" id="mSms" class="form-check-input">
                                <label for="mSms" class="form-check-label"><i class="bi bi-chat text-info"></i> SMS</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الرسالة</label>
                        <textarea id="messageText" class="form-control" rows="5" placeholder="اكتب رسالتك هنا..." oninput="updatePreview()"></textarea>
                        <small class="text-muted"><span id="charCount">0</span> حرف</small>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('حضور')">حضور</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('رسوم')">رسوم</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('موعد')">موعد</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('نتائج')">نتائج</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">معاينة</label>
                        <div id="previewBox" class="border rounded p-3 bg-light" style="min-height:60px">
                            <small class="text-muted">سيتم عرض معاينة الرسالة هنا...</small>
                        </div>
                    </div>
                    <button class="btn btn-success w-100" onclick="sendMessage()"><i class="bi bi-send"></i> إرسال</button>
                </div>

                <div class="table-container mt-3" id="quickActions">
                    <h6 class="mb-3"><i class="bi bi-lightning"></i> إجراءات سريعة</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="?with_phone=1" class="btn btn-sm btn-outline-success"><i class="bi bi-whatsapp"></i> الطلاب الذين لديهم واتساب</a>
                        <button class="btn btn-sm btn-outline-warning" onclick="selectOutstanding()"><i class="bi bi-exclamation-triangle"></i> طلاب متبقي عليهم رسوم</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer"><span>نظام أوريون لإدارة المدارس &copy; <?= date('Y') ?></span></footer>
</div>

<script>
const recipients = new Map();

function toggleAll(el) {
    document.querySelectorAll('.student-check').forEach(cb => {
        cb.checked = el.checked;
        if (el.checked) addRecipient(cb);
        else removeRecipient(cb.value);
    });
}

document.querySelectorAll('.student-check').forEach(cb => {
    cb.addEventListener('change', function() {
        if (this.checked) addRecipient(this);
        else removeRecipient(this.value);
    });
});

function addRecipient(cb) {
    const id = cb.value;
    const name = cb.dataset.name;
    const phone = cb.dataset.phone;
    const code = cb.dataset.code;
    if (!recipients.has(id)) {
        recipients.set(id, { id, name, phone, code });
        renderRecipients();
    }
}

function removeRecipient(id) {
    recipients.delete(id);
    renderRecipients();
    document.querySelector(`.student-check[value="${id}"]`).checked = false;
}

function renderRecipients() {
    const list = document.getElementById('recipientList');
    const count = document.getElementById('recipientCount');
    if (recipients.size === 0) {
        list.innerHTML = '<small class="text-muted">اختر طلاباً من القائمة...</small>';
        count.textContent = '0 طالب';
        return;
    }
    list.innerHTML = Array.from(recipients.values()).map(r =>
        `<span class="badge bg-info m-1 p-2">${r.name} <a href="#" onclick="removeRecipient(${r.id});return false" class="text-white ms-1 text-decoration-none">&times;</a></span>`
    ).join('');
    count.textContent = recipients.size + ' طالب';
}

function updatePreview() {
    const msg = document.getElementById('messageText').value;
    document.getElementById('charCount').textContent = msg.length;
    document.getElementById('previewBox').innerHTML = msg ? `<p class="mb-0">${msg.replace(/\n/g, '<br>')}</p>` : '<small class="text-muted">سيتم عرض معاينة الرسالة هنا...</small>';
}

function insertTemplate(type) {
    const tpls = {
        'حضور': 'عزيزي الطالب، نود إعلامك بموعد الحضور غداً في تمام الساعة المحددة. مع تحيات الإدارة',
        'رسوم': 'عزيزي ولي الأمر، لديك رسوم متبقية. يرجى مراجعة الإدارة للتسديد. مع الشكر',
        'موعد': 'تذكير بموعد الامتحان: يرجى الحضور في الوقت المحدد. نتمنى لكم التوفيق',
        'نتائج': 'النتائج متاحة الآن. يمكنكم الاستعلام عبر بوابة الطالب. مع التوفيق للجميع'
    };
    const ta = document.getElementById('messageText');
    ta.value += (ta.value ? '\n' : '') + (tpls[type] || '');
    updatePreview();
}

function sendMessage() {
    const method = document.querySelector('input[name="sendMethod"]:checked').value;
    const msg = document.getElementById('messageText').value.trim();
    if (recipients.size === 0) { alert('اختر طالباً واحداً على الأقل'); return; }
    if (!msg) { alert('اكتب الرسالة'); return; }

    if (method === 'whatsapp') {
        const numbers = Array.from(recipients.values())
            .map(r => r.phone.replace(/[^0-9]/g, ''))
            .filter(p => p.length >= 7);
        if (numbers.length === 0) { alert('لا يوجد أرقام هواتف صالحة للمرسل إليهم'); return; }
        // Open in bulk via wa.me (one per tab) or use group link
        numbers.forEach((num, i) => {
            setTimeout(() => {
                window.open(`https://wa.me/${num}?text=${encodeURIComponent(msg)}`, '_blank');
            }, i * 500);
        });
    } else if (method === 'email') {
        const emails = Array.from(recipients.values()).map(r => r.email).filter(e => e);
        if (emails.length === 0) { alert('لا يوجد بريد إلكتروني للمرسل إليهم'); return; }
        window.open(`mailto:${emails.join(',')}?subject=رسالة من المؤسسة&body=${encodeURIComponent(msg)}`, '_blank');
    } else {
        sendSms(msg);
    }

function sendSms(msg) {
    const ids = Array.from(recipients.keys());
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    ids.forEach(id => formData.append('student_ids[]', id));
    formData.append('message', msg);

    document.querySelector('button.btn-success').disabled = true;
    document.querySelector('button.btn-success').innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري الإرسال...';

    fetch('ajax_send_sms.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم إرسال ' + data.sent + ' رسالة بنجاح' + (data.failed > 0 ? '، فشل ' + data.failed : ''));
            if (data.errors && data.errors.length) {
                console.log('Errors:', data.errors);
            }
        } else {
            alert('فشل الإرسال: ' + (data.error || 'خطأ غير معروف'));
        }
    })
    .catch(err => {
        alert('حدث خطأ في الاتصال: ' + err.message);
    })
    .finally(() => {
        document.querySelector('button.btn-success').disabled = false;
        document.querySelector('button.btn-success').innerHTML = '<i class="bi bi-send"></i> إرسال';
    });
}
}

function selectOutstanding() {
    fetch('ajax_outstanding.php')
        .then(r => r.json())
        .then(data => {
            document.querySelectorAll('.student-check').forEach(cb => {
                if (data.includes(parseInt(cb.value))) {
                    cb.checked = true;
                    addRecipient(cb);
                }
            });
        });
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
