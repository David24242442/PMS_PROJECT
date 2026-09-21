<?php

$filePath = __DIR__ . '/../AUGUST 2026 PAYROLL DATA.xlsx';
$zip = new ZipArchive();
if ($zip->open($filePath) !== true) die("Zip failed");

$sharedStrings = [];
$sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedStringsXml !== false) {
    $xml = simplexml_load_string($sharedStringsXml);
    if ($xml && isset($xml->si)) {
        foreach ($xml->si as $si) {
            if (isset($si->t)) $sharedStrings[] = (string)$si->t;
            elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) $text .= (string)$r->t;
                $sharedStrings[] = $text;
            } else $sharedStrings[] = '';
        }
    }
}

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
$sheet = simplexml_load_string($sheetXml);
$zip->close();

$rows = [];
$rowCount = 0;
$h664Record = null;
$corruptedEmpIds = [];

foreach ($sheet->sheetData->row as $r) {
    $rowCount++;
    if ($rowCount === 1) continue; // Skip header

    $rowCells = [];
    foreach ($r->c as $c) {
        $colLetter = preg_replace('/[0-9]/', '', (string)$c['r']);
        $type = (string)$c['t'];
        $val = isset($c->v) ? (string)$c->v : '';
        if ($type === 's' && isset($sharedStrings[(int)$val])) {
            $val = $sharedStrings[(int)$val];
        }
        $rowCells[$colLetter] = trim(str_replace("\xc2\xa0", ' ', $val));
    }

    // STRICT RELATIVE COLUMNS:
    // Column A = Sr.No
    // Column B = Emp Id
    // Column C = Employee Name
    // Column D = Location
    // Column E = Designation
    // Column F = Sex
    // Column G = Category
    $data = [
        'sr_no' => !empty($rowCells['A']) ? (int)$rowCells['A'] : null,
        'emp_id' => $rowCells['B'] ?? '',
        'employee_name' => $rowCells['C'] ?? '',
        'location' => $rowCells['D'] ?? '',
        'designation' => $rowCells['E'] ?? '',
        'sex' => $rowCells['F'] ?? '',
        'category' => $rowCells['G'] ?? ''
    ];

    if (!empty($data['emp_id'])) {
        $rows[] = $data;
    }

    if ($data['emp_id'] === 'H664') {
        $h664Record = $data;
    }

    // Check if emp_id looks like a full name with spaces
    if (strpos($data['emp_id'], ' ') !== false) {
        $corruptedEmpIds[] = $data;
    }
}

echo "TOTAL ROWS PARSED: " . count($rows) . "\n";
echo "H664 FOUND:\n";
print_r($h664Record);
echo "EMP IDs WITH SPACES (NAMES?): " . count($corruptedEmpIds) . "\n";
if (!empty($corruptedEmpIds)) {
    echo "First 3 with spaces:\n";
    print_r(array_slice($corruptedEmpIds, 0, 3));
}
