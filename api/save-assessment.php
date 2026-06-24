<?php
/**
 * API: Save User Assessment
 * Saves admin assessment of a user to the database
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $pdo = getDB();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $userId = $input['user_id'] ?? null;
    $assessedBy = $_SESSION['user_id'] ?? 1; // Default to admin
    
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    // Prepare assessment data
    $checklist = is_array($input['checklist'] ?? []) ? implode(',', $input['checklist']) : '';
    
    $data = [
        'user_id' => $userId,
        'assessed_by' => $assessedBy,
        'checklist_items' => $checklist,
        'attendance_rating' => $input['attendance'] ?? null,
        'task_completion_rating' => $input['task_completion'] ?? null,
        'quality_rating' => $input['quality'] ?? null,
        'teamwork_rating' => $input['teamwork'] ?? null,
        'initiative_rating' => $input['initiative'] ?? null,
        'strengths' => $input['strengths'] ?? '',
        'improvements' => $input['improvements'] ?? '',
        'action_items' => $input['action_items'] ?? '',
        'overall_notes' => $input['overall_notes'] ?? '',
        'assessment_date' => date('Y-m-d H:i:s'),
    ];
    
    // Create assessments table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            assessed_by INT NOT NULL,
            checklist_items TEXT,
            attendance_rating TINYINT,
            task_completion_rating TINYINT,
            quality_rating TINYINT,
            teamwork_rating TINYINT,
            initiative_rating TINYINT,
            strengths TEXT,
            improvements TEXT,
            action_items TEXT,
            overall_notes TEXT,
            assessment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Insert assessment
    $sql = "INSERT INTO user_assessments (
                user_id, assessed_by, checklist_items,
                attendance_rating, task_completion_rating, quality_rating,
                teamwork_rating, initiative_rating,
                strengths, improvements, action_items, overall_notes, assessment_date
            ) VALUES (
                :user_id, :assessed_by, :checklist_items,
                :attendance_rating, :task_completion_rating, :quality_rating,
                :teamwork_rating, :initiative_rating,
                :strengths, :improvements, :action_items, :overall_notes, :assessment_date
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $assessmentId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Assessment saved successfully',
        'assessment_id' => $assessmentId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
