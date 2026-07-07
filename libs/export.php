<?php
// Export library for Excel (XLSX) and PDF generation

function exportXLSX($filename, $headers, $rows) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:r="urn:schemas-microsoft-com:office:excel">
<ss:Worksheet ss:Name="Sheet1">
<ss:Table>';

    $xml .= '<ss:Row>';
    foreach ($headers as $h) {
        $xml .= '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($h) . '</ss:Data></ss:Cell>';
    }
    $xml .= '</ss:Row>';

    foreach ($rows as $row) {
        $xml .= '<ss:Row>';
        foreach ($row as $cell) {
            $type = is_numeric($cell) ? 'Number' : 'String';
            $xml .= '<ss:Cell><ss:Data ss:Type="' . $type . '">' . htmlspecialchars((string)$cell) . '</ss:Data></ss:Cell>';
        }
        $xml .= '</ss:Row>';
    }

    $xml .= '</ss:Table></ss:Worksheet></ss:Workbook>';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    echo $xml;
    exit;
}

function exportCSV($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

function exportPDF($filename, $html) {
    $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<title>' . htmlspecialchars($filename) . '</title>
<style>
    body { font-family: "DejaVu Sans", "Tajawal", sans-serif; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 12px; }
    th { background: #4e73df; color: white; }
    tr:nth-child(even) { background: #f8f9fc; }
    h2 { text-align: center; margin-bottom: 20px; }
    .header { text-align: center; margin-bottom: 20px; }
    .header img { height: 60px; }
    .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #666; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="header">
    <img src="' . BASE_URL . '/assets/img/Orion.png" alt="Logo" style="height:50px;">
    <h2>' . getSetting('school_name', 'Orion Aden') . '</h2>
</div>
' . $html . '
<div class="footer">تم الإنشاء في: ' . date('Y-m-d H:i:s') . '</div>
</body>
</html>';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '.html"');
    echo $html;
    exit;
}

function parseXLSX($filepath) {
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return ['error' => 'لا يمكن فتح الملف'];
    }

    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheet) {
        // Try older format
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheet) {
            // Try the Office 2003 XML format (which we export)
            $zip->close();
            return parseXMLSS($filepath);
        }
    }

    $sharedStrings = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
    $strings = [];
    if ($sharedStrings) {
        foreach ($sharedStrings->si as $si) {
            $strings[] = (string)$si->t;
        }
    }
    $zip->close();

    $xml = simplexml_load_string($sheet);
    $ns = $xml->getNamespaces(true);
    $data = [];
    $rows = $xml->sheetData->row;

    foreach ($rows as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $type = (string)$cell['t'];
            $value = (string)$cell->v;
            if ($type === 's' && isset($strings[(int)$value])) {
                $rowData[] = $strings[(int)$value];
            } else {
                $rowData[] = $value;
            }
        }
        $data[] = $rowData;
    }

    return $data;
}

function parseXMLSS($filepath) {
    // Parse Office 2003 XML format
    $xml = simplexml_load_file($filepath);
    $ns = $xml->getNamespaces(true);
    $ss = $ns['ss'] ?? 'urn:schemas-microsoft-com:office:spreadsheet';

    $data = [];
    if (isset($xml->Worksheet->Table->Row)) {
        foreach ($xml->Worksheet->Table->Row as $row) {
            $rowData = [];
            if (isset($row->Cell)) {
                foreach ($row->Cell as $cell) {
                    $val = '';
                    if (isset($cell->Data)) {
                        $val = (string)$cell->Data;
                    }
                    $rowData[] = $val;
                }
            }
            $data[] = $rowData;
        }
    }
    return $data;
}

function parseCSV($filepath) {
    $rows = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows;
}
