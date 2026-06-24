<?php
/**
 * Admin Users View
 * Manage users and track their performance activities
 */

// Fetch users from database
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, employee_id, first_name, last_name, email, department, job_title, role, created_at FROM users ORDER BY first_name ASC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

// Sample activity types for tracking
$activityTypes = [
    'attendance' => ['label' => 'Attendance & Punctuality', 'options' => ['Excellent', 'Good', 'Needs Improvement', 'Poor']],
    'task_completion' => ['label' => 'Task Completion', 'options' => ['On Time', 'Slightly Delayed', 'Significantly Delayed', 'Incomplete']],
    'quality' => ['label' => 'Work Quality', 'options' => ['Outstanding', 'Above Average', 'Average', 'Below Average']],
    'teamwork' => ['label' => 'Teamwork', 'options' => ['Excellent Collaborator', 'Good Team Player', 'Works Independently', 'Needs Improvement']],
    'initiative' => ['label' => 'Initiative', 'options' => ['Highly Proactive', 'Proactive', 'Reactive', 'Passive']],
];

$checklistItems = [
    'goals_set' => 'Goals have been set for this period',
    'training_completed' => 'Completed required training',
    'feedback_given' => 'Received performance feedback',
    'one_on_one' => 'One-on-one meeting conducted',
    'documentation_updated' => 'Documentation is up to date',
    'kpis_reviewed' => 'KPIs have been reviewed',
    'development_plan' => 'Development plan in place',
    'recognition_given' => 'Recognition given for achievements',
];

// Get selected user if any
$selectedUserId = $_GET['user_id'] ?? null;
$selectedUser = null;

if ($selectedUserId && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$selectedUserId]);
    $selectedUser = $stmt->fetch();
}
?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <p class="page-subtitle">Track and evaluate employee performance and activities</p>
</div>

<div class="dashboard-grid" style="grid-template-columns: 350px 1fr;">
    <!-- Users List Panel -->
    <div class="card" style="height: fit-content; max-height: 80vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title">Users</h3>
            <span class="badge badge-primary"><?php echo count($users); ?></span>
        </div>
        
        <div class="search-box mb-md" style="width: 100%;">
            <span class="material-symbols-sharp">search</span>
            <input type="text" placeholder="Search users..." id="user-search">
        </div>
        
        <div class="user-list" id="user-list">
            <?php if (empty($users)): ?>
            <div class="text-center text-muted" style="padding: 2rem;">
                <span class="material-symbols-sharp" style="font-size: 3rem; display: block; margin-bottom: 1rem;">group_off</span>
                <p>No users found in database.</p>
                <small>Run schema.sql to create sample users.</small>
            </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <a href="index.php?page=admin-users&user_id=<?php echo $user['id']; ?>" 
                   class="user-list-item <?php echo $selectedUserId == $user['id'] ? 'active' : ''; ?>"
                   data-name="<?php echo htmlspecialchars(strtolower($user['first_name'] . ' ' . $user['last_name'])); ?>">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . '+' . $user['last_name']); ?>&background=7c3aed&color=fff&size=40" 
                         alt="<?php echo htmlspecialchars($user['first_name']); ?>" 
                         class="avatar-sm">
                    <div class="user-list-info">
                        <div class="user-list-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        <div class="user-list-role"><?php echo htmlspecialchars($user['job_title'] ?? $user['role']); ?></div>
                    </div>
                    <span class="material-symbols-sharp" style="color: var(--text-muted);">chevron_right</span>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- User Details & Activity Form -->
    <div class="user-details-panel">
        <?php if ($selectedUser): ?>
        <div class="card mb-lg">
            <div class="card-header">
                <div class="flex items-center gap-md">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($selectedUser['first_name'] . '+' . $selectedUser['last_name']); ?>&background=7c3aed&color=fff&size=60" 
                         alt="<?php echo htmlspecialchars($selectedUser['first_name']); ?>" 
                         class="avatar-lg">
                    <div>
                        <h3 class="card-title" style="margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?>
                        </h3>
                        <span class="text-muted"><?php echo htmlspecialchars($selectedUser['job_title'] ?? 'Employee'); ?></span>
                        <span class="badge badge-<?php echo match($selectedUser['role']) { 'admin' => 'danger', 'hr' => 'warning', 'line_manager' => 'info', default => 'primary' }; ?>" style="margin-left: 0.5rem;">
                            <?php echo ucfirst($selectedUser['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="flex gap-sm">
                    <button class="btn btn-outline btn-sm" onclick="window.print()">
                        <span class="material-symbols-sharp">print</span>
                    </button>
                    <button class="btn btn-outline btn-sm">
                        <span class="material-symbols-sharp">mail</span>
                    </button>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-light);">
                <div>
                    <span class="text-muted" style="font-size: 0.8rem;">Employee ID</span>
                    <div class="font-bold"><?php echo htmlspecialchars($selectedUser['employee_id']); ?></div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem;">Department</span>
                    <div class="font-bold"><?php echo htmlspecialchars($selectedUser['department'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem;">Email</span>
                    <div class="font-bold"><?php echo htmlspecialchars($selectedUser['email']); ?></div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem;">Joined</span>
                    <div class="font-bold"><?php echo date('M d, Y', strtotime($selectedUser['created_at'])); ?></div>
                </div>
            </div>
            
            <!-- View History Button -->
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-light);">
                <button type="button" class="btn btn-outline" onclick="toggleHistory()" id="history-toggle-btn">
                    <span class="material-symbols-sharp">history</span>
                    View Assessment History
                </button>
            </div>
        </div>
        
        <!-- Assessment History Section -->
        <div id="assessment-history" class="card mb-lg" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 0.5rem;">history</span>
                    Assessment History
                </h3>
                <button class="btn btn-outline btn-sm" onclick="toggleHistory()">
                    <span class="material-symbols-sharp">close</span>
                </button>
            </div>
            <div id="history-content" class="history-content">
                <div class="text-center text-muted" style="padding: 2rem;">
                    <span class="material-symbols-sharp" style="font-size: 2rem;">hourglass_empty</span>
                    <p>Loading assessment history...</p>
                </div>
            </div>
        </div>
        
        <!-- Performance Activity Form -->
        <form id="activity-form" class="activity-form">
            <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
            
            <!-- Activity Checklist -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 0.5rem;">checklist</span>
                        Activity Checklist
                    </h3>
                </div>
                
                <div class="checklist-grid">
                    <?php foreach ($checklistItems as $key => $label): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="checklist[]" value="<?php echo $key; ?>">
                        <span class="checkbox-box">
                            <span class="material-symbols-sharp">check</span>
                        </span>
                        <span class="checkbox-label"><?php echo $label; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Performance Ratings -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 0.5rem;">stars</span>
                        Performance Assessment
                    </h3>
                </div>
                
                <div class="rating-sections">
                    <?php foreach ($activityTypes as $key => $activity): ?>
                    <div class="rating-section">
                        <label class="form-label"><?php echo $activity['label']; ?></label>
                        <div class="radio-group">
                            <?php foreach ($activity['options'] as $idx => $option): ?>
                            <label class="radio-item">
                                <input type="radio" name="<?php echo $key; ?>" value="<?php echo $idx; ?>">
                                <span class="radio-box"></span>
                                <span class="radio-label"><?php echo $option; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 0.5rem;">comment</span>
                        Performance Comments
                    </h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Strengths & Achievements</label>
                    <textarea class="form-control" name="strengths" rows="3" 
                              placeholder="What has this employee done well?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Areas for Improvement</label>
                    <textarea class="form-control" name="improvements" rows="3" 
                              placeholder="What areas need development?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Action Items / Next Steps</label>
                    <textarea class="form-control" name="action_items" rows="3" 
                              placeholder="What actions should be taken?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Overall Notes</label>
                    <textarea class="form-control" name="overall_notes" rows="4" 
                              placeholder="Additional comments about this employee's performance..."></textarea>
                </div>
            </div>
            
            <!-- Submit Section -->
            <div class="card">
                <div class="flex justify-between items-center">
                    <div class="text-muted" style="font-size: 0.85rem;">
                        <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">info</span>
                        Assessment for <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?>
                    </div>
                    <div class="flex gap-md">
                        <button type="button" class="btn btn-outline" onclick="resetForm()">
                            <span class="material-symbols-sharp">restart_alt</span>
                            Reset Form
                        </button>
                        <button type="button" class="btn btn-outline" onclick="saveDraft()">
                            <span class="material-symbols-sharp">save</span>
                            Save Draft
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-sharp">check_circle</span>
                            Submit Assessment
                        </button>
                    </div>
                </div>
            </div>
        </form>
        
        <?php else: ?>
        <!-- No User Selected -->
        <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; text-align: center;">
            <span class="material-symbols-sharp" style="font-size: 5rem; color: var(--color-primary); opacity: 0.5; margin-bottom: 1rem;">person_search</span>
            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Select a User</h3>
            <p class="text-muted" style="max-width: 300px;">
                Click on a user from the list to view their details and track their performance activities.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* User List Styles */
.user-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--radius-md);
    transition: var(--transition-fast);
    cursor: pointer;
}

.user-list-item:hover {
    background: var(--color-lighter);
}

.user-list-item.active {
    background: rgba(124, 58, 237, 0.1);
    border-left: 3px solid var(--color-primary);
}

.user-list-info {
    flex: 1;
}

.user-list-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.user-list-role {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* Checklist Grid */
.checklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 0.75rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--color-lighter);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: var(--transition-fast);
}

.checkbox-item:hover {
    background: rgba(124, 58, 237, 0.08);
}

.checkbox-item input {
    display: none;
}

.checkbox-box {
    width: 24px;
    height: 24px;
    border: 2px solid var(--color-light);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-fast);
}

.checkbox-box span {
    font-size: 1rem;
    color: transparent;
    transition: var(--transition-fast);
}

.checkbox-item input:checked + .checkbox-box {
    background: var(--color-success);
    border-color: var(--color-success);
}

.checkbox-item input:checked + .checkbox-box span {
    color: white;
}

.checkbox-label {
    flex: 1;
    font-size: 0.9rem;
}

/* Rating Sections */
.rating-sections {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.rating-section {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--color-light);
}

.rating-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.radio-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--color-lighter);
    border-radius: var(--radius-full);
    cursor: pointer;
    transition: var(--transition-fast);
    border: 2px solid transparent;
}

.radio-item:hover {
    background: rgba(124, 58, 237, 0.08);
}

.radio-item input {
    display: none;
}

.radio-box {
    width: 16px;
    height: 16px;
    border: 2px solid var(--color-muted);
    border-radius: 50%;
    position: relative;
    transition: var(--transition-fast);
}

.radio-box::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    width: 8px;
    height: 8px;
    background: var(--color-primary);
    border-radius: 50%;
    transition: var(--transition-fast);
}

.radio-item input:checked + .radio-box {
    border-color: var(--color-primary);
}

.radio-item input:checked + .radio-box::after {
    transform: translate(-50%, -50%) scale(1);
}

.radio-item input:checked ~ .radio-label {
    color: var(--color-primary);
    font-weight: 500;
}

.radio-label {
    font-size: 0.85rem;
}

/* History Section Styles */
.history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-item {
    background: var(--color-lighter);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.history-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: rgba(124, 58, 237, 0.05);
    border-bottom: 1px solid var(--color-light);
}

.history-item-content {
    padding: 1rem;
}

.history-section {
    margin-bottom: 0.75rem;
}

.history-section:last-child {
    margin-bottom: 0;
}

.history-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.history-tags .badge {
    font-size: 0.75rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User search
    const searchInput = document.getElementById('user-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const items = document.querySelectorAll('.user-list-item');
            
            items.forEach(item => {
                const name = item.dataset.name || '';
                item.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Load saved draft
    const userId = document.querySelector('input[name="user_id"]')?.value;
    if (userId) {
        loadDraft(userId);
    }
    
    // Form submission
    const form = document.getElementById('activity-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAssessment();
        });
    }
});

function loadDraft(userId) {
    const saved = localStorage.getItem('user_assessment_' + userId);
    if (saved) {
        const data = JSON.parse(saved);
        const form = document.getElementById('activity-form');
        
        // Restore checkboxes
        if (data.checklist) {
            data.checklist.forEach(value => {
                const checkbox = form.querySelector(`input[name="checklist[]"][value="${value}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        // Restore radio buttons
        Object.keys(data).forEach(key => {
            if (key !== 'checklist') {
                const radio = form.querySelector(`input[name="${key}"][value="${data[key]}"]`);
                if (radio) radio.checked = true;
                
                const textarea = form.querySelector(`textarea[name="${key}"]`);
                if (textarea) textarea.value = data[key];
            }
        });
        
        Toast.info('Draft loaded from previous session');
    }
}

function saveDraft() {
    const form = document.getElementById('activity-form');
    const userId = form.querySelector('input[name="user_id"]').value;
    const data = {};
    
    // Get checkboxes
    data.checklist = [];
    form.querySelectorAll('input[name="checklist[]"]:checked').forEach(cb => {
        data.checklist.push(cb.value);
    });
    
    // Get radio buttons
    form.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        data[radio.name] = radio.value;
    });
    
    // Get textareas
    form.querySelectorAll('textarea').forEach(textarea => {
        data[textarea.name] = textarea.value;
    });
    
    localStorage.setItem('user_assessment_' + userId, JSON.stringify(data));
    Toast.success('Draft saved successfully');
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? This will clear all entries.')) {
        const form = document.getElementById('activity-form');
        form.reset();
        Toast.info('Form has been reset');
    }
}

function submitAssessment() {
    const form = document.getElementById('activity-form');
    const userId = form.querySelector('input[name="user_id"]').value;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Collect form data
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        if (data[key]) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    });
    
    // Show loading state
    showLoading(submitBtn);
    
    // Send to API
    fetch('index.php?page=api-save-assessment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        hideLoading(submitBtn);
        
        if (result.success) {
            Toast.success('Assessment submitted successfully!');
            localStorage.removeItem('user_assessment_' + userId);
            form.reset();
        } else {
            // Fallback to localStorage if API fails
            const assessments = JSON.parse(localStorage.getItem('user_assessments') || '[]');
            data.submitted_at = new Date().toISOString();
            assessments.push(data);
            localStorage.setItem('user_assessments', JSON.stringify(assessments));
            localStorage.removeItem('user_assessment_' + userId);
            
            Toast.success('Assessment saved locally!');
            form.reset();
        }
    })
    .catch(error => {
        hideLoading(submitBtn);
        
        // Fallback to localStorage
        const assessments = JSON.parse(localStorage.getItem('user_assessments') || '[]');
        data.submitted_at = new Date().toISOString();
        assessments.push(data);
        localStorage.setItem('user_assessments', JSON.stringify(assessments));
        localStorage.removeItem('user_assessment_' + userId);
        
        Toast.success('Assessment saved locally!');
        form.reset();
    });
}

// Assessment History Functions
let historyVisible = false;

function toggleHistory() {
    const historySection = document.getElementById('assessment-history');
    const toggleBtn = document.getElementById('history-toggle-btn');
    
    historyVisible = !historyVisible;
    
    if (historyVisible) {
        historySection.style.display = 'block';
        toggleBtn.innerHTML = '<span class="material-symbols-sharp">visibility_off</span> Hide History';
        loadAssessmentHistory();
    } else {
        historySection.style.display = 'none';
        toggleBtn.innerHTML = '<span class="material-symbols-sharp">history</span> View Assessment History';
    }
}

function loadAssessmentHistory() {
    const userId = document.querySelector('input[name="user_id"]')?.value;
    const historyContent = document.getElementById('history-content');
    
    if (!userId) return;
    
    // First try to load from API
    fetch(`index.php?page=api-get-assessments&user_id=${userId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data && result.data.length > 0) {
                displayHistory(result.data);
            } else {
                // Fallback to localStorage
                loadLocalHistory(userId);
            }
        })
        .catch(() => {
            loadLocalHistory(userId);
        });
}

function loadLocalHistory(userId) {
    const historyContent = document.getElementById('history-content');
    const allAssessments = JSON.parse(localStorage.getItem('user_assessments') || '[]');
    const userAssessments = allAssessments.filter(a => a.user_id == userId);
    
    if (userAssessments.length === 0) {
        historyContent.innerHTML = `
            <div class="text-center text-muted" style="padding: 2rem;">
                <span class="material-symbols-sharp" style="font-size: 2rem;">folder_open</span>
                <p>No previous assessments found for this user.</p>
            </div>
        `;
        return;
    }
    
    displayHistory(userAssessments.map(a => ({
        assessment_date: a.submitted_at,
        assessor_name: 'Admin',
        checklist_items: Array.isArray(a.checklist) ? a.checklist.join(',') : '',
        strengths: a.strengths || '',
        overall_notes: a.overall_notes || '',
        attendance_rating: a.attendance,
        task_completion_rating: a.task_completion,
        quality_rating: a.quality
    })));
}

function displayHistory(assessments) {
    const historyContent = document.getElementById('history-content');
    
    const ratingLabels = {
        attendance: ['Excellent', 'Good', 'Needs Improvement', 'Poor'],
        task_completion: ['On Time', 'Slightly Delayed', 'Significantly Delayed', 'Incomplete'],
        quality: ['Outstanding', 'Above Average', 'Average', 'Below Average']
    };
    
    let html = '<div class="history-list">';
    
    assessments.forEach((assessment, idx) => {
        const date = new Date(assessment.assessment_date).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        
        const checklist = assessment.checklist_items ? assessment.checklist_items.split(',').filter(c => c) : [];
        
        html += `
            <div class="history-item">
                <div class="history-item-header">
                    <div>
                        <strong>Assessment #${assessments.length - idx}</strong>
                        <span class="text-muted" style="margin-left: 0.5rem;">${date}</span>
                    </div>
                    <span class="badge badge-info">${assessment.assessor_name || 'Admin'}</span>
                </div>
                <div class="history-item-content">
                    ${checklist.length > 0 ? `
                        <div class="history-section">
                            <strong>Checklist Completed:</strong>
                            <div class="history-tags">
                                ${checklist.map(c => `<span class="badge badge-success">${c.replace(/_/g, ' ')}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                    ${assessment.attendance_rating !== null && assessment.attendance_rating !== undefined ? `
                        <div class="history-section">
                            <strong>Attendance:</strong> ${ratingLabels.attendance[assessment.attendance_rating] || 'N/A'}
                        </div>
                    ` : ''}
                    ${assessment.task_completion_rating !== null && assessment.task_completion_rating !== undefined ? `
                        <div class="history-section">
                            <strong>Task Completion:</strong> ${ratingLabels.task_completion[assessment.task_completion_rating] || 'N/A'}
                        </div>
                    ` : ''}
                    ${assessment.strengths ? `
                        <div class="history-section">
                            <strong>Strengths:</strong> ${assessment.strengths}
                        </div>
                    ` : ''}
                    ${assessment.overall_notes ? `
                        <div class="history-section">
                            <strong>Notes:</strong> ${assessment.overall_notes}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    historyContent.innerHTML = html;
}
</script>
