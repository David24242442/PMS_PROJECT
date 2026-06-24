<?php
/**
 * API: Get User Assessments
 * Retrieves assessment history for a user
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $pdo = getDB();
    
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    // Check if table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'user_assessments'")->fetchAll();
    if (empty($tables)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'No assessments found'
        ]);
        exit;
    }
    
    // Fetch assessments with assessor info
    $stmt = $pdo->prepare("
        SELECT 
            ua.*,
            CONCAT(u.first_name, ' ', u.last_name) as assessor_name
        FROM user_assessments ua
        LEFT JOIN users u ON ua.assessed_by = u.id
        WHERE ua.user_id = ?
        ORDER BY ua.assessment_date DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $assessments,
        'count' => count($assessments)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
