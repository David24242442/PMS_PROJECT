<?php
/**
 * API: HR Action
 * Handles HR approve/reject/request info actions
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $pdo = getDB();
    
    $submissionId = $_POST['submission_id'] ?? null;
    $actionType = $_POST['action_type'] ?? null;
    $comments = $_POST['hr_comments'] ?? '';
    $hrUserId = $_SESSION['user_id'] ?? 1; // Default to 1 for demo
    
    if (!$submissionId || !$actionType) {
        throw new Exception('Missing required fields');
    }
    
    // Determine new status based on action
    $statusMap = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'request_info' => 'under_review'
    ];
    
    $newStatus = $statusMap[$actionType] ?? 'pending';
    
    // Update appraisal
    $stmt = $pdo->prepare("
        UPDATE appraisals 
        SET status = ?, 
            hr_comments = ?,
            hr_action_by = ?,
            hr_action_date = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$newStatus, $comments, $hrUserId, $submissionId]);
    
    $message = match($actionType) {
        'approve' => 'Appraisal approved successfully',
        'reject' => 'Appraisal rejected and returned for revision',
        'request_info' => 'Information request sent to employee',
        default => 'Action completed'
    };
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
