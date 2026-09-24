<?php

function exportCSV($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

function exportXLSX($title, $headers, $rows) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $title . '.xls"');
    echo '<table><thead><tr>';
    foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $c) echo '<td>' . htmlspecialchars($c) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    exit;
}

function parseCSV($path) {
    $rows = [];
    if (($handle = fopen($path, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows;
}

function parseXLSX($path) {
    $rows = [];
    if (($handle = fopen($path, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows ?: [['error' => 'تعذر قراءة الملف. تأكد من أنه بصيغة CSV أو Excel مدعومة']];
}

function exportPDF($title, $html) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<html dir="rtl"><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
    echo '<style>body{font-family:DejaVu Sans,sans-serif;padding:20px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #000;padding:6px;text-align:right}th{background:#f0f0f0}</style>';
    echo '</head><body>';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    echo $html;
    echo '<p style="text-align:center;margin-top:30px;color:#666;font-size:12px">Orion Aden Institute - ' . date('Y-m-d') . '</p>';
    echo '</body></html>';
    exit;
}
