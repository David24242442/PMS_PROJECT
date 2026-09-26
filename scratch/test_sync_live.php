<?php

$centralPdo = new PDO("mysql:host=192.168.0.17;port=3306;dbname=hrproject;charset=utf8mb4", "misaccount", "Inv@Central@2024", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$localPdo = new PDO("mysql:host=192.168.0.20;port=3306;dbname=hrproject;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "Connected successfully to both Server 17 (Central) and Server 20 (Local)!\n";

$tables = [
    'employees',
    'bank_socials',
    'childrens',
    'driverlicenses',
    'education',
    'emergency_contacts',
    'guarantors',
    'histories',
    'irr_guar_witnesses',
    'irr_guarantees',
    'nominees',
    'present_jobs',
    'references',
    'social_contacts',
    'unions',
    'uploads',
    'wives',
    'work_experiences',
    'workpermits'
];

$totalSynced = 0;

foreach ($tables as $table) {
    $stmt = $localPdo->query("SELECT MAX(id) FROM `$table`");
    $localMaxId = (int) $stmt->fetchColumn();

    $stmt = $centralPdo->query("SELECT MAX(id) FROM `$table`");
    $centralMaxId = (int) $stmt->fetchColumn();

    if ($centralMaxId > $localMaxId) {
        $fetchStmt = $centralPdo->prepare("SELECT * FROM `$table` WHERE id > ? ORDER BY id ASC");
        $fetchStmt->execute([$localMaxId]);
        $newRows = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Table '$table': Central Max ID is $centralMaxId, Local Max ID is $localMaxId. Found " . count($newRows) . " new rows.\n";

        foreach ($newRows as $row) {
            if ($table === 'employees') {
                $fn = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['surname'] ?? ''));
                if (empty($row['full_name']) && !empty($fn)) {
                    $row['full_name'] = $fn;
                }
            }

            $columns = array_keys($row);
            $colList = implode('`, `', $columns);
            $paramList = implode(', ', array_fill(0, count($columns), '?'));

            $insertSql = "INSERT IGNORE INTO `$table` (`$colList`) VALUES ($paramList)";
            $insertStmt = $localPdo->prepare($insertSql);
            $insertStmt->execute(array_values($row));
            $totalSynced++;
        }
    } else {
        echo "Table '$table': Up to date (Max ID: $localMaxId).\n";
    }
}

$stmt = $localPdo->query("SELECT count(*) FROM employees");
$finalLocalCount = $stmt->fetchColumn();

$stmt = $centralPdo->query("SELECT count(*) FROM employees");
$finalCentralCount = $stmt->fetchColumn();

echo "\nSync Complete!\n";
echo "Total new rows synced across all tables: $totalSynced\n";
echo "Server 20 Employee Count: $finalLocalCount\n";
echo "Server 17 Employee Count: $finalCentralCount\n";
