<?php
header('Content-Type: application/json');
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hrproject', 'root', '');
    echo json_encode(['status' => 'success', 'message' => 'Connected to database']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
