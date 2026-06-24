<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Performance Management System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <!-- Papa Parse for CSV -->
    <script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="style/style.css?v=2.0">
    <link rel="stylesheet" href="style/slider.css?v=2.0">
    <link rel="stylesheet" href="style/dashboard.css?v=2.0">
    
    <!-- Main JavaScript -->
    <script src="assets/js/slider.js" defer></script>
    <script src="assets/js/main.js" defer></script>
</head>
<body>
    <div class="app-container">
        <?php include 'components/slider.php'; ?>
        <main class="main-content">
            <div class="top-bar">
                <div class="search-box">
                    <span class="material-symbols-sharp">search</span>
                    <input type="text" placeholder="Quick search for a team, a member or a goal">
                </div>
                <div class="top-bar-right">
                    <button class="icon-btn notification-btn">
                        <span class="material-symbols-sharp">notifications</span>
                        <span class="badge">0</span>
                    </button>
                    <div class="user-menu">
                        <img src="assets/images/user-avatar.png" alt="User" class="avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=User&background=7c3aed&color=fff'">
                        <div class="user-menu-info">
                            <span class="user-name">Bill Macintosh</span>
                            <span class="user-role">Manager</span>
                        </div>
                        <span class="material-symbols-sharp">expand_more</span>
                    </div>
                </div>
            </div>
            <div class="content-wrapper">
