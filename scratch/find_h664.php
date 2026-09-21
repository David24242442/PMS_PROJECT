<?php
$filePath = __DIR__ . '/../AUGUST 2026 PAYROLL DATA.xlsx';
$zip = new ZipArchive();
$zip->open($filePath);

$sharedStrings = [];
$sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
$xml = simplexml_load_string($sharedStringsXml);
foreach ($xml->si as $si) {
    if (isset($si->t)) $sharedStrings[] = (string)$si->t;
    elseif (isset($si->r)) {
        $t = '';
        foreach ($si->r as $r) $t .= (string)$r->t;
        $sharedStrings[] = $t;
    } else $sharedStrings[] = '';
}

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
$sheet = simplexml_load_string($sheetXml);
$zip->close();

$matches = [];
$rNum = 0;
foreach ($sheet->sheetData->row as $r) {
    $rNum++;
    $cells = [];
    foreach ($r->c as $c) {
        $col = preg_replace('/[0-9]/', '', (string)$c['r']);
        $t = (string)$c['t'];
        $val = isset($c->v) ? (string)$c->v : '';
        if ($t === 's' && isset($sharedStrings[(int)$val])) $val = $sharedStrings[(int)$val];
        $cells[$col] = trim($val);
    }
    foreach ($cells as $k => $v) {
        if (stripos($v, 'H664') !== false || stripos($v, 'ADOM') !== false) {
            $matches[] = ['row' => $rNum, 'cell' => $k, 'val' => $v, 'all' => $cells];
        }
    }
}
print_r($matches);
