<?php
// debug_log.php - Place in public/
$logFile = __DIR__ . '/../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    die("Log file not found at: $logFile");
}

$lines = file($logFile);
$lastLines = array_slice($lines, -100); // Get last 100 lines

echo "<h1>Last 100 Log Lines</h1>";
echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd; white-space: pre-wrap;'>";
foreach ($lastLines as $line) {
    echo htmlspecialchars($line);
}
echo "</pre>";
