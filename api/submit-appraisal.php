<?php
/**
 * API: Submit Appraisal
 * Handles appraisal form submission
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $pdo = getDB();
    
    // Get form data
    $userId = $_SESSION['user_id'] ?? 1; // Default to 1 for demo
    $year = $_POST['appraisal_year'] ?? date('Y');
    
    // Check if appraisal already exists for this user and year
    $stmt = $pdo->prepare("SELECT id FROM appraisals WHERE user_id = ? AND appraisal_year = ?");
    $stmt->execute([$userId, $year]);
    $existing = $stmt->fetch();
    
    $data = [
        'user_id' => $userId,
        'appraisal_year' => $year,
        'status' => $_POST['status'] ?? 'submitted',
        'quality_rating' => $_POST['quality_rating'] ?? null,
        'productivity_rating' => $_POST['productivity_rating'] ?? null,
        'teamwork_rating' => $_POST['teamwork_rating'] ?? null,
        'communication_rating' => $_POST['communication_rating'] ?? null,
        'problem_solving_rating' => $_POST['problem_solving_rating'] ?? null,
        'initiative_rating' => $_POST['initiative_rating'] ?? null,
        'reliability_rating' => $_POST['reliability_rating'] ?? null,
        'self_assessment' => $_POST['self_assessment'] ?? '',
        'achievements' => $_POST['achievements'] ?? '',
        'areas_for_improvement' => $_POST['areas_for_improvement'] ?? '',
        'development_plan' => $_POST['development_plan'] ?? '',
        'goals_next_year' => $_POST['goals_next_year'] ?? '',
        'submitted_at' => date('Y-m-d H:i:s'),
    ];
    
    if ($existing) {
        // Update existing
        $sql = "UPDATE appraisals SET 
                status = :status,
                quality_rating = :quality_rating,
                productivity_rating = :productivity_rating,
                teamwork_rating = :teamwork_rating,
                communication_rating = :communication_rating,
                problem_solving_rating = :problem_solving_rating,
                initiative_rating = :initiative_rating,
                reliability_rating = :reliability_rating,
                self_assessment = :self_assessment,
                achievements = :achievements,
                areas_for_improvement = :areas_for_improvement,
                development_plan = :development_plan,
                goals_next_year = :goals_next_year,
                submitted_at = :submitted_at
                WHERE user_id = :user_id AND appraisal_year = :appraisal_year";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $appraisalId = $existing['id'];
    } else {
        // Insert new
        $sql = "INSERT INTO appraisals (
                    user_id, appraisal_year, status,
                    quality_rating, productivity_rating, teamwork_rating,
                    communication_rating, problem_solving_rating, initiative_rating,
                    reliability_rating, self_assessment, achievements,
                    areas_for_improvement, development_plan, goals_next_year, submitted_at
                ) VALUES (
                    :user_id, :appraisal_year, :status,
                    :quality_rating, :productivity_rating, :teamwork_rating,
                    :communication_rating, :problem_solving_rating, :initiative_rating,
                    :reliability_rating, :self_assessment, :achievements,
                    :areas_for_improvement, :development_plan, :goals_next_year, :submitted_at
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $appraisalId = $pdo->lastInsertId();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Appraisal submitted successfully',
        'appraisal_id' => $appraisalId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
