<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pms_database');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection using PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // For development, show error. In production, log it instead
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to get PDO connection
function getDB() {
    global $pdo;
    return $pdo;
}
?>
