<?php

$filePath = __DIR__ . '/../AUGUST 2026 PAYROLL DATA.xlsx';
if (!file_exists($filePath)) {
    die("File not found: " . $filePath);
}

$zip = new ZipArchive();
if ($zip->open($filePath) !== true) {
    die("Failed to open zip");
}

$sharedStrings = [];
$sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedStringsXml !== false) {
    $xml = simplexml_load_string($sharedStringsXml);
    if ($xml && isset($xml->si)) {
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $sharedStrings[] = (string)$si->t;
            } elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
                $sharedStrings[] = $text;
            } else {
                $sharedStrings[] = '';
            }
        }
    }
}

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
$sheet = simplexml_load_string($sheetXml);
$zip->close();

$headerMap = [];
$rowCount = 0;
$h664Found = null;
$sampleRows = [];

foreach ($sheet->sheetData->row as $r) {
    $rowCount++;
    $rowCells = [];

    foreach ($r->c as $c) {
        $cellRef = (string)$c['r'];
        $colLetter = preg_replace('/[0-9]/', '', $cellRef);
        $type = (string)$c['t'];
        $val = isset($c->v) ? (string)$c->v : '';

        if ($type === 's' && isset($sharedStrings[(int)$val])) {
            $val = $sharedStrings[(int)$val];
        }
        $val = trim(str_replace("\xc2\xa0", ' ', $val));
        $rowCells[$colLetter] = $val;
    }

    if ($rowCount === 1) {
        echo "ROW 1 HEADERS:\n";
        print_r($rowCells);
        foreach ($rowCells as $col => $headerName) {
            $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $headerName)));
            if (strpos($norm, 'sr') !== false || $norm === 'no' || $norm === 'sno') {
                $headerMap[$col] = 'sr_no';
            } elseif (strpos($norm, 'name') !== false) {
                $headerMap[$col] = 'employee_name';
            } elseif (strpos($norm, 'id') !== false || strpos($norm, 'code') !== false || $norm === 'empid' || $norm === 'employeeid' || $norm === 'emp') {
                $headerMap[$col] = 'emp_id';
            } elseif (strpos($norm, 'loc') !== false || strpos($norm, 'branch') !== false) {
                $headerMap[$col] = 'location';
            } elseif (strpos($norm, 'desig') !== false || strpos($norm, 'title') !== false || strpos($norm, 'pos') !== false) {
                $headerMap[$col] = 'designation';
            } elseif (strpos($norm, 'sex') !== false || strpos($norm, 'gender') !== false) {
                $headerMap[$col] = 'sex';
            } elseif (strpos($norm, 'cat') !== false || strpos($norm, 'type') !== false) {
                $headerMap[$col] = 'category';
            }
        }
        echo "HEADER MAP:\n";
        print_r($headerMap);
    } else {
        $data = [
            'sr_no' => null,
            'emp_id' => '',
            'employee_name' => '',
            'location' => '',
            'designation' => '',
            'sex' => '',
            'category' => ''
        ];

        foreach ($rowCells as $col => $val) {
            if (isset($headerMap[$col])) {
                $field = $headerMap[$col];
                $data[$field] = $val;
            }
        }

        if (empty($data['emp_id']) && isset($rowCells['B'])) $data['emp_id'] = $rowCells['B'];
        if (empty($data['employee_name']) && isset($rowCells['C'])) $data['employee_name'] = $rowCells['C'];
        if (empty($data['location']) && isset($rowCells['D'])) $data['location'] = $rowCells['D'];
        if (empty($data['designation']) && isset($rowCells['E'])) $data['designation'] = $rowCells['E'];
        if (empty($data['sex']) && isset($rowCells['F'])) $data['sex'] = $rowCells['F'];
        if (empty($data['category']) && isset($rowCells['G'])) $data['category'] = $rowCells['G'];

        if ($data['emp_id'] === 'H664' || strpos($data['employee_name'], 'YEBOAH') !== false) {
            $h664Found = ['row' => $rowCount, 'raw' => $rowCells, 'parsed' => $data];
        }

        if ($rowCount <= 10) {
            $sampleRows[] = ['row' => $rowCount, 'raw' => $rowCells, 'parsed' => $data];
        }
    }
}

echo "\nTOTAL ROWS SCANNED: " . ($rowCount - 1) . "\n";
echo "H664 CHECK:\n";
print_r($h664Found);
echo "\nFIRST 3 SAMPLE ROWS:\n";
print_r(array_slice($sampleRows, 0, 3));
