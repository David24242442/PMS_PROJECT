<?php
/**
 * PMS Dashboard View
 * Main dashboard matching the reference design
 */

// Sample data - in production, fetch from database
$kpiData = [
    ['label' => 'Completion rate', 'value' => '76%', 'sublabel' => '6/9 respondents', 'class' => ''],
    ['label' => 'Happiness', 'value' => '4/5', 'sublabel' => 'The average satisfaction score', 'class' => 'success'],
    ['label' => 'Accomplishments', 'value' => '18', 'sublabel' => 'This week', 'class' => 'info'],
    ['label' => 'New plans', 'value' => '14', 'sublabel' => '16 users', 'class' => 'warning'],
    ['label' => 'Reported problems', 'value' => '7', 'sublabel' => '3 users', 'class' => 'danger'],
];

$teamMembers = [
    ['name' => 'Johnny Bravo', 'role' => 'Marketing', 'status' => 'success', 'avatar' => 'https://ui-avatars.com/api/?name=JB&background=7c3aed&color=fff'],
    ['name' => 'Michael Johnstone', 'role' => 'Developer', 'status' => 'success', 'avatar' => 'https://ui-avatars.com/api/?name=MJ&background=10b981&color=fff'],
    ['name' => 'Michelle Guerrero', 'role' => 'Designer', 'status' => 'warning', 'avatar' => 'https://ui-avatars.com/api/?name=MG&background=f59e0b&color=fff'],
    ['name' => 'Johnny Bravo', 'role' => 'Sales', 'status' => 'danger', 'avatar' => 'https://ui-avatars.com/api/?name=JB&background=ef4444&color=fff'],
    ['name' => 'Michael Johnstone', 'role' => 'Support', 'status' => 'success', 'avatar' => 'https://ui-avatars.com/api/?name=MJ&background=3b82f6&color=fff'],
];

$todoItems = [
    ['title' => 'Complete your assessment', 'meta' => '3 days remaining', 'icon' => 'assignment', 'class' => ''],
    ['title' => 'Give feedback for 3 colleagues', 'meta' => '5 days remaining', 'icon' => 'group', 'class' => 'warning'],
    ['title' => "Don't forget your next one-on-one", 'meta' => '7 days remaining', 'icon' => 'calendar_today', 'class' => 'info'],
    ['title' => 'Setup next assessment period', 'meta' => '15 days remaining', 'icon' => 'settings', 'class' => 'success'],
];

$goals = [
    ['title' => 'Finalize and launch the new product', 'status' => 'Completed', 'progress' => 100, 'days' => 37, 'class' => 'completed'],
    ['title' => 'Gather 50k investment', 'status' => 'Exceeded', 'progress' => 100, 'days' => 37, 'class' => 'exceeded'],
    ['title' => 'Finish development of the new app', 'status' => 'Exceeded', 'progress' => 85, 'days' => 37, 'class' => 'exceeded'],
    ['title' => 'Onboard all new employees by the new year', 'status' => 'Completed', 'progress' => 100, 'days' => 37, 'class' => 'completed'],
    ['title' => 'Finalize and launch the new product', 'status' => 'Exceeded', 'progress' => 90, 'days' => 37, 'class' => 'exceeded'],
];
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back! Here's your performance overview.</p>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <?php foreach ($kpiData as $kpi): ?>
    <div class="kpi-card <?php echo $kpi['class']; ?>">
        <span class="kpi-label">
            <span class="material-symbols-sharp">trending_up</span>
            <?php echo htmlspecialchars($kpi['label']); ?>
        </span>
        <span class="kpi-value"><?php echo htmlspecialchars($kpi['value']); ?></span>
        <span class="kpi-sublabel"><?php echo htmlspecialchars($kpi['sublabel']); ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div class="dashboard-grid">
    <div class="dashboard-main">
        <!-- Weekly Status Section -->
        <div class="weekly-status">
            <div class="weekly-status-header">
                <div class="weekly-status-title">
                    <h3>Weekly Status for 17 - 24 March</h3>
                    <div class="rating-stars">
                        <span class="material-symbols-sharp">star</span>
                        <span class="material-symbols-sharp">star</span>
                        <span class="material-symbols-sharp">star</span>
                        <span class="material-symbols-sharp">star_half</span>
                    </div>
                </div>
                <button class="btn btn-outline btn-sm">
                    <span class="material-symbols-sharp">visibility</span>
                    See full details
                </button>
            </div>
            
            <div class="weekly-status-content">
                <div class="completion-circle-container">
                    <div class="completion-circle">
                        <svg viewBox="0 0 100 100">
                            <circle class="bg" cx="50" cy="50" r="40"/>
                            <circle class="progress" cx="50" cy="50" r="40" 
                                    stroke-dasharray="251.2" 
                                    stroke-dashoffset="60"/>
                        </svg>
                        <div class="completion-value">
                            <span class="percentage">76%</span>
                            <span class="label">Completion Rate</span>
                        </div>
                    </div>
                    
                    <div class="avatar-stack">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <img src="https://ui-avatars.com/api/?name=U<?php echo $i; ?>&background=random&size=32" 
                             alt="User" onerror="this.src='https://ui-avatars.com/api/?name=U&background=7c3aed&color=fff'">
                        <?php endfor; ?>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-value">71%</div>
                            <div class="stat-label">Progressed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">14%</div>
                            <div class="stat-label">On track</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">8%</div>
                            <div class="stat-label">At risk</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">14%</div>
                            <div class="stat-label">Off track</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">8%</div>
                            <div class="stat-label">Exceeded</div>
                        </div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="weekly-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Team Members -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Team Overview</h3>
                <button class="btn btn-outline btn-sm">View All</button>
            </div>
            <div class="team-members-list">
                <?php foreach ($teamMembers as $member): ?>
                <div class="team-member-item">
                    <img src="<?php echo htmlspecialchars($member['avatar']); ?>" 
                         alt="<?php echo htmlspecialchars($member['name']); ?>" 
                         class="avatar-sm">
                    <div class="team-member-info">
                        <div class="team-member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                        <div class="team-member-role"><?php echo htmlspecialchars($member['role']); ?></div>
                    </div>
                    <div class="status-indicator <?php echo $member['status']; ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Goals Progress -->
        <div class="goals-progress">
            <div class="goals-header">
                <h3 class="goals-title">Goals progress</h3>
                <div class="goals-filters">
                    <button class="filter-btn active">Company</button>
                    <button class="filter-btn">Team</button>
                    <button class="filter-btn">Mine</button>
                </div>
                <div class="goals-stats">
                    <div class="goal-stat">
                        <span class="goal-stat-value">71%</span>
                        <span class="goal-stat-label">On track</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-stat-value">12%</span>
                        <span class="goal-stat-label">Exceeded</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-stat-value">8%</span>
                        <span class="goal-stat-label">Off track</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-stat-value">6%</span>
                        <span class="goal-stat-label">At Risk</span>
                    </div>
                </div>
            </div>
            
            <div class="goals-grid">
                <?php foreach ($goals as $goal): ?>
                <div class="goal-card">
                    <div class="goal-card-header">
                        <div>
                            <h4 class="goal-title"><?php echo htmlspecialchars($goal['title']); ?></h4>
                            <span class="goal-status <?php echo strtolower(str_replace(' ', '-', $goal['class'])); ?>">
                                <?php echo htmlspecialchars($goal['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="goal-progress-bar">
                        <div class="goal-progress-fill <?php echo $goal['progress'] >= 100 ? 'primary' : ($goal['progress'] >= 70 ? '' : 'warning'); ?>" 
                             style="width: <?php echo $goal['progress']; ?>%"></div>
                    </div>
                    <div class="goal-meta">
                        <span class="material-symbols-sharp">schedule</span>
                        <?php echo $goal['days']; ?> days remaining
                        <div class="goal-assignees">
                            <img src="https://ui-avatars.com/api/?name=A&background=7c3aed&color=fff&size=24" alt="Assignee">
                            <img src="https://ui-avatars.com/api/?name=B&background=10b981&color=fff&size=24" alt="Assignee">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="dashboard-sidebar">
        <!-- My Todo List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My todo list</h3>
            </div>
            <div class="todo-list">
                <?php foreach ($todoItems as $todo): ?>
                <div class="todo-item">
                    <div class="todo-icon <?php echo $todo['class']; ?>">
                        <span class="material-symbols-sharp"><?php echo $todo['icon']; ?></span>
                    </div>
                    <div class="todo-content">
                        <div class="todo-title"><?php echo htmlspecialchars($todo['title']); ?></div>
                        <div class="todo-meta"><?php echo htmlspecialchars($todo['meta']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-outline btn-sm mt-md" style="width: 100%;">
                See full list
            </button>
        </div>
    </div>
</div>
