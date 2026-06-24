<?php
// ============================================
   //Performance Management System
// ===============================================

// 1. Critical Initialization
require_once 'config/database.php';
session_start();

// 2. Define base path for includes
define('BASE_PATH', __DIR__ . '/');

// 3. Get the requested page
$page = $_GET['page'] ?? 'homepage';

// 4. Define valid routes and their view files
$routes = [
    // Public pages
    'homepage' => 'views/homepage.php',
    'login' => 'views/login.php',
    'signup' => 'views/signup.php',
    
    // Admin pages
    'admin' => 'views/admin/dashboard.php',
    'admin-dashboard' => 'views/admin/dashboard.php',
    'admin-line-manager' => 'views/admin/line-manager.php',
    'admin-users' => 'views/admin/users.php',
    
    // PMS pages
    'pms-dashboard' => 'views/pms/dashboard.php',
    'pms-appraisal' => 'views/pms/appraisal.php',
    
    // HR pages
    'hr-submissions' => 'views/hr/submissions.php',
    'hr-reports' => 'views/hr/reports.php',
    
    // API endpoints
    'api-upload-csv' => 'api/upload-csv.php',
    'api-export-excel' => 'api/export-excel.php',
    'api-submit-appraisal' => 'api/submit-appraisal.php',
    'api-hr-action' => 'api/hr-action.php',
    'api-save-assessment' => 'api/save-assessment.php',
    'api-get-assessments' => 'api/get-assessments.php',
];

// 5. Set the content file based on route
if (array_key_exists($page, $routes)) {
    $content = $routes[$page];
} else {
    $content = 'views/404.php';
}

// 6. Check if it's an API request (no header/footer needed)
$isApi = strpos($page, 'api-') === 0;

// 7. Assemble the Page Layout
if (!$isApi) {
    include 'components/header.php';
}

if (file_exists($content)) {
    include $content;
} else {
    echo "<div class='error-page'><h1>404 - Page Not Found</h1><p>The page you're looking for doesn't exist.</p></div>";
}

if (!$isApi) {
    include 'components/footer.php';
}
?>
