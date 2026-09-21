<?php
$log_path = 'c:\\Users\\USER\\Workspaces\\htdocs\\HR Onboarding\\hr\\hr\\storage\\logs\\laravel.log';

if (file_exists($log_path)) {
    $content = file_get_contents($log_path);
    $decoded = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
    echo substr($decoded, -4000);
} else {
    echo "File not found: " . $log_path;
}
?>
