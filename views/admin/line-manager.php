<?php
/**
 * Line Manager View
 * Team performance oversight and management
 */

// Sample team data
$teamMembers = [
    [
        'id' => 1,
        'name' => 'John Doe',
        'role' => 'Software Developer',
        'department' => 'Engineering',
        'avatar' => 'https://ui-avatars.com/api/?name=JD&background=7c3aed&color=fff',
        'goals_completed' => 8,
        'goals_total' => 10,
        'performance' => 85,
        'last_check_in' => '2 days ago',
        'status' => 'on_track'
    ],
    [
        'id' => 2,
        'name' => 'Jane Smith',
        'role' => 'UX Designer',
        'department' => 'Design',
        'avatar' => 'https://ui-avatars.com/api/?name=JS&background=10b981&color=fff',
        'goals_completed' => 6,
        'goals_total' => 8,
        'performance' => 92,
        'last_check_in' => '1 day ago',
        'status' => 'exceeding'
    ],
    [
        'id' => 3,
        'name' => 'Mike Johnson',
        'role' => 'QA Engineer',
        'department' => 'Engineering',
        'avatar' => 'https://ui-avatars.com/api/?name=MJ&background=f59e0b&color=fff',
        'goals_completed' => 4,
        'goals_total' => 10,
        'performance' => 55,
        'last_check_in' => '5 days ago',
        'status' => 'at_risk'
    ],
    [
        'id' => 4,
        'name' => 'Sarah Williams',
        'role' => 'Product Manager',
        'department' => 'Product',
        'avatar' => 'https://ui-avatars.com/api/?name=SW&background=3b82f6&color=fff',
        'goals_completed' => 7,
        'goals_total' => 9,
        'performance' => 78,
        'last_check_in' => '3 days ago',
        'status' => 'on_track'
    ],
];

$upcomingMeetings = [
    ['name' => 'John Doe', 'date' => 'Tomorrow, 10:00 AM', 'type' => 'One-on-One'],
    ['name' => 'Team Standup', 'date' => 'Today, 9:00 AM', 'type' => 'Team Meeting'],
    ['name' => 'Jane Smith', 'date' => 'Dec 20, 2:00 PM', 'type' => 'Performance Review'],
];
?>

<div class="page-header">
    <h1 class="page-title">Line Manager</h1>
    <p class="page-subtitle">Manage your team's performance and development</p>
</div>

<!-- Team Overview Stats -->
<div class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">
            <span class="material-symbols-sharp">group</span>
            Team Size
        </span>
        <span class="kpi-value"><?php echo count($teamMembers); ?></span>
        <span class="kpi-sublabel">Direct reports</span>
    </div>
    <div class="kpi-card success">
        <span class="kpi-label">
            <span class="material-symbols-sharp">trending_up</span>
            Avg Performance
        </span>
        <span class="kpi-value">77%</span>
        <span class="kpi-sublabel">+5% from last month</span>
    </div>
    <div class="kpi-card warning">
        <span class="kpi-label">
            <span class="material-symbols-sharp">flag</span>
            Goals On Track
        </span>
        <span class="kpi-value">25/37</span>
        <span class="kpi-sublabel">68% completion rate</span>
    </div>
    <div class="kpi-card info">
        <span class="kpi-label">
            <span class="material-symbols-sharp">event</span>
            Upcoming Meetings
        </span>
        <span class="kpi-value"><?php echo count($upcomingMeetings); ?></span>
        <span class="kpi-sublabel">This week</span>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-main">
        <!-- Team Members Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="card-title">Team Members</h3>
                <div class="flex gap-sm">
                    <button class="btn btn-outline btn-sm">
                        <span class="material-symbols-sharp">filter_list</span>
                        Filter
                    </button>
                    <button class="btn btn-primary btn-sm">
                        <span class="material-symbols-sharp">add</span>
                        Add Goal
                    </button>
                </div>
            </div>
            
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Goals Progress</th>
                            <th>Performance</th>
                            <th>Last Check-in</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMembers as $member): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-sm">
                                    <img src="<?php echo htmlspecialchars($member['avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                         class="avatar-sm">
                                    <div>
                                        <div class="font-bold"><?php echo htmlspecialchars($member['name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($member['department']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($member['role']); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="progress" style="width: 100px;">
                                        <div class="progress-bar <?php echo $member['goals_completed'] / $member['goals_total'] >= 0.7 ? 'success' : 'warning'; ?>" 
                                             style="width: <?php echo ($member['goals_completed'] / $member['goals_total']) * 100; ?>%"></div>
                                    </div>
                                    <span style="font-size: 0.85rem;"><?php echo $member['goals_completed']; ?>/<?php echo $member['goals_total']; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="font-bold"><?php echo $member['performance']; ?>%</span>
                            </td>
                            <td><?php echo htmlspecialchars($member['last_check_in']); ?></td>
                            <td>
                                <?php
                                $statusClass = match($member['status']) {
                                    'exceeding' => 'badge-success',
                                    'on_track' => 'badge-primary',
                                    'at_risk' => 'badge-danger',
                                    default => 'badge-warning'
                                };
                                $statusText = match($member['status']) {
                                    'exceeding' => 'Exceeding',
                                    'on_track' => 'On Track',
                                    'at_risk' => 'At Risk',
                                    default => 'Needs Attention'
                                };
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <div class="flex gap-sm">
                                    <button class="btn btn-outline btn-sm" title="View Details">
                                        <span class="material-symbols-sharp">visibility</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Schedule Meeting">
                                        <span class="material-symbols-sharp">event</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Add Feedback">
                                        <span class="material-symbols-sharp">chat</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Goal Tracking -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Team Goals</h3>
                <button class="btn btn-outline btn-sm">View All</button>
            </div>
            <div class="goals-grid">
                <div class="goal-card">
                    <h4 class="goal-title">Q4 Product Launch</h4>
                    <span class="goal-status completed">Completed</span>
                    <div class="goal-progress-bar">
                        <div class="goal-progress-fill" style="width: 100%"></div>
                    </div>
                    <div class="goal-meta">
                        <span>Team Goal</span>
                        <div class="goal-assignees">
                            <img src="https://ui-avatars.com/api/?name=JD&background=7c3aed&color=fff&size=24" alt="">
                            <img src="https://ui-avatars.com/api/?name=JS&background=10b981&color=fff&size=24" alt="">
                            <img src="https://ui-avatars.com/api/?name=MJ&background=f59e0b&color=fff&size=24" alt="">
                        </div>
                    </div>
                </div>
                <div class="goal-card">
                    <h4 class="goal-title">Reduce Bug Count by 30%</h4>
                    <span class="goal-status in-progress">In Progress</span>
                    <div class="goal-progress-bar">
                        <div class="goal-progress-fill warning" style="width: 65%"></div>
                    </div>
                    <div class="goal-meta">
                        <span>Engineering Goal</span>
                        <div class="goal-assignees">
                            <img src="https://ui-avatars.com/api/?name=MJ&background=7c3aed&color=fff&size=24" alt="">
                            <img src="https://ui-avatars.com/api/?name=JD&background=3b82f6&color=fff&size=24" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-sidebar">
        <!-- Upcoming Meetings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upcoming Meetings</h3>
            </div>
            <div class="todo-list">
                <?php foreach ($upcomingMeetings as $meeting): ?>
                <div class="todo-item">
                    <div class="todo-icon info">
                        <span class="material-symbols-sharp">event</span>
                    </div>
                    <div class="todo-content">
                        <div class="todo-title"><?php echo htmlspecialchars($meeting['name']); ?></div>
                        <div class="todo-meta"><?php echo htmlspecialchars($meeting['date']); ?></div>
                        <span class="badge badge-info" style="margin-top: 4px;"><?php echo htmlspecialchars($meeting['type']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary btn-sm mt-md" style="width: 100%;">
                <span class="material-symbols-sharp">add</span>
                Schedule Meeting
            </button>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="flex flex-col gap-sm">
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;">
                    <span class="material-symbols-sharp">rate_review</span>
                    Start Performance Review
                </button>
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;">
                    <span class="material-symbols-sharp">flag</span>
                    Set Team Goal
                </button>
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;">
                    <span class="material-symbols-sharp">chat</span>
                    Send Team Feedback
                </button>
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;">
                    <span class="material-symbols-sharp">summarize</span>
                    Generate Report
                </button>
            </div>
        </div>
    </div>
</div>
