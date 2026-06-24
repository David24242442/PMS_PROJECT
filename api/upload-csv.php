<?php
/**
 * API: Upload CSV
 * Handles CSV file uploads and stores in database
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $file = $_FILES['csv_file'];
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($extension !== 'csv') {
        throw new Exception('Only CSV files are allowed');
    }
    
    // Read CSV content
    $csvData = [];
    $headers = [];
    
    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
        $lineNumber = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if ($lineNumber === 0) {
                $headers = $row;
            } else {
                $rowData = [];
                foreach ($headers as $i => $header) {
                    $rowData[$header] = $row[$i] ?? '';
                }
                $csvData[] = $rowData;
            }
            $lineNumber++;
        }
        fclose($handle);
    }
    
    // Store in database
    $pdo = getDB();
    
    // Generate unique filename
    $filename = uniqid('csv_') . '.csv';
    $uploadPath = '../uploads/' . $filename;
    
    // Create uploads directory if not exists
    if (!is_dir('../uploads')) {
        mkdir('../uploads', 0755, true);
    }
    
    // Move uploaded file
    move_uploaded_file($file['tmp_name'], $uploadPath);
    
    // Insert upload record
    $stmt = $pdo->prepare("INSERT INTO csv_uploads (filename, original_name, uploaded_by, row_count) VALUES (?, ?, ?, ?)");
    $stmt->execute([$filename, $file['name'], 1, count($csvData)]);
    $uploadId = $pdo->lastInsertId();
    
    // Insert CSV data
    $stmt = $pdo->prepare("INSERT INTO csv_data (upload_id, row_data) VALUES (?, ?)");
    foreach ($csvData as $row) {
        $stmt->execute([$uploadId, json_encode($row)]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'data' => $csvData,
        'headers' => $headers,
        'row_count' => count($csvData)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
