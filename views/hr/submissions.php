<?php
/**
 * HR Submissions View
 * Review and manage submitted appraisals
 */

// Sample submissions data (in production, fetch from database)
$submissions = [
    [
        'id' => 1,
        'employee_name' => 'John Doe',
        'department' => 'Engineering',
        'position' => 'Software Developer',
        'submitted_at' => '2024-12-15 10:30:00',
        'status' => 'pending',
        'overall_rating' => 4.2
    ],
    [
        'id' => 2,
        'employee_name' => 'Jane Smith',
        'department' => 'Marketing',
        'position' => 'Marketing Manager',
        'submitted_at' => '2024-12-14 14:45:00',
        'status' => 'approved',
        'overall_rating' => 4.5
    ],
    [
        'id' => 3,
        'employee_name' => 'Mike Johnson',
        'department' => 'Design',
        'position' => 'UI/UX Designer',
        'submitted_at' => '2024-12-13 09:15:00',
        'status' => 'rejected',
        'overall_rating' => 3.1
    ],
    [
        'id' => 4,
        'employee_name' => 'Sarah Williams',
        'department' => 'HR',
        'position' => 'HR Specialist',
        'submitted_at' => '2024-12-12 16:00:00',
        'status' => 'under_review',
        'overall_rating' => 3.8
    ],
];
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">HR Submissions</h1>
            <p class="page-subtitle">Review and manage employee appraisals</p>
        </div>
        <div class="flex gap-sm">
            <select class="form-control" id="status-filter" style="width: 150px;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="under_review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
            <select class="form-control" id="department-filter" style="width: 150px;">
                <option value="">All Departments</option>
                <option value="Engineering">Engineering</option>
                <option value="Marketing">Marketing</option>
                <option value="Design">Design</option>
                <option value="HR">HR</option>
            </select>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">
            <span class="material-symbols-sharp">inbox</span>
            Total Submissions
        </span>
        <span class="kpi-value"><?php echo count($submissions); ?></span>
        <span class="kpi-sublabel">This review cycle</span>
    </div>
    <div class="kpi-card warning">
        <span class="kpi-label">
            <span class="material-symbols-sharp">pending</span>
            Pending Review
        </span>
        <span class="kpi-value"><?php echo count(array_filter($submissions, fn($s) => $s['status'] === 'pending')); ?></span>
        <span class="kpi-sublabel">Awaiting action</span>
    </div>
    <div class="kpi-card success">
        <span class="kpi-label">
            <span class="material-symbols-sharp">check_circle</span>
            Approved
        </span>
        <span class="kpi-value"><?php echo count(array_filter($submissions, fn($s) => $s['status'] === 'approved')); ?></span>
        <span class="kpi-sublabel">Completed</span>
    </div>
    <div class="kpi-card danger">
        <span class="kpi-label">
            <span class="material-symbols-sharp">cancel</span>
            Rejected
        </span>
        <span class="kpi-value"><?php echo count(array_filter($submissions, fn($s) => $s['status'] === 'rejected')); ?></span>
        <span class="kpi-sublabel">Returned for revision</span>
    </div>
</div>

<!-- Submissions Table -->
<div class="table-container">
    <div class="table-header">
        <h3 class="card-title">Appraisal Submissions</h3>
        <div class="flex gap-sm">
            <div class="search-box" style="width: 250px;">
                <span class="material-symbols-sharp">search</span>
                <input type="text" placeholder="Search employee..." id="employee-search">
            </div>
            <button class="btn btn-outline btn-sm">
                <span class="material-symbols-sharp">download</span>
                Export
            </button>
        </div>
    </div>
    
    <div class="table-scroll" style="max-height: 600px;">
        <table class="data-table" id="submissions-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Submitted</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                <tr data-id="<?php echo $submission['id']; ?>" 
                    data-department="<?php echo htmlspecialchars($submission['department']); ?>"
                    data-status="<?php echo $submission['status']; ?>">
                    <td>
                        <div class="flex items-center gap-sm">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($submission['employee_name']); ?>&background=7c3aed&color=fff" 
                                 alt="<?php echo htmlspecialchars($submission['employee_name']); ?>" 
                                 class="avatar-sm">
                            <strong><?php echo htmlspecialchars($submission['employee_name']); ?></strong>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($submission['department']); ?></td>
                    <td><?php echo htmlspecialchars($submission['position']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($submission['submitted_at'])); ?></td>
                    <td>
                        <div class="flex items-center gap-sm">
                            <span class="material-symbols-sharp" style="color: var(--color-warning); font-size: 1rem;">star</span>
                            <strong><?php echo number_format($submission['overall_rating'], 1); ?></strong>/5
                        </div>
                    </td>
                    <td>
                        <?php
                        $statusConfig = [
                            'pending' => ['class' => 'badge-warning', 'label' => 'Pending'],
                            'under_review' => ['class' => 'badge-info', 'label' => 'Under Review'],
                            'approved' => ['class' => 'badge-success', 'label' => 'Approved'],
                            'rejected' => ['class' => 'badge-danger', 'label' => 'Rejected'],
                        ];
                        $config = $statusConfig[$submission['status']] ?? ['class' => 'badge-primary', 'label' => 'Unknown'];
                        ?>
                        <span class="badge <?php echo $config['class']; ?>"><?php echo $config['label']; ?></span>
                    </td>
                    <td>
                        <div class="flex gap-sm">
                            <button class="btn btn-outline btn-sm" onclick="viewSubmission(<?php echo $submission['id']; ?>)" title="View Details">
                                <span class="material-symbols-sharp">visibility</span>
                            </button>
                            <?php if ($submission['status'] === 'pending' || $submission['status'] === 'under_review'): ?>
                            <button class="btn btn-success btn-sm" onclick="approveSubmission(<?php echo $submission['id']; ?>)" title="Approve">
                                <span class="material-symbols-sharp">check</span>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="rejectSubmission(<?php echo $submission['id']; ?>)" title="Reject">
                                <span class="material-symbols-sharp">close</span>
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="requestInfo(<?php echo $submission['id']; ?>)" title="Request Info">
                                <span class="material-symbols-sharp">help</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Action Modal Template -->
<div id="action-modal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 500px;">
        <div class="flex justify-between items-center mb-lg">
            <h3 id="modal-title">Take Action</h3>
            <button class="btn btn-outline btn-sm" onclick="closeActionModal()">
                <span class="material-symbols-sharp">close</span>
            </button>
        </div>
        <form id="action-form">
            <input type="hidden" name="submission_id" id="action-submission-id">
            <input type="hidden" name="action_type" id="action-type">
            
            <div class="form-group">
                <label class="form-label">Comments</label>
                <textarea class="form-control" name="hr_comments" rows="4" 
                          placeholder="Add your comments or feedback..." required></textarea>
            </div>
            
            <div class="flex gap-md justify-end">
                <button type="button" class="btn btn-outline" onclick="closeActionModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="action-submit-btn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load localStorage submissions
    loadLocalSubmissions();
    
    // Search functionality
    document.getElementById('employee-search').addEventListener('input', filterTable);
    document.getElementById('status-filter').addEventListener('change', filterTable);
    document.getElementById('department-filter').addEventListener('change', filterTable);
});

function loadLocalSubmissions() {
    const stored = localStorage.getItem('hr_submissions');
    if (stored) {
        const submissions = JSON.parse(stored);
        const tbody = document.querySelector('#submissions-table tbody');
        
        submissions.forEach(sub => {
            const row = document.createElement('tr');
            row.dataset.id = 'local-' + Date.now();
            row.dataset.department = 'Employee';
            row.dataset.status = 'pending';
            
            row.innerHTML = `
                <td>
                    <div class="flex items-center gap-sm">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(sub.employee_name || 'User')}&background=10b981&color=fff" 
                             alt="${sub.employee_name || 'User'}" 
                             class="avatar-sm">
                        <strong>${sub.employee_name || 'User'}</strong>
                    </div>
                </td>
                <td>Self Submission</td>
                <td>Employee</td>
                <td>${new Date(sub.submitted_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                <td>
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-sharp" style="color: var(--color-warning); font-size: 1rem;">star</span>
                        <strong>--</strong>/5
                    </div>
                </td>
                <td><span class="badge badge-warning">Pending</span></td>
                <td>
                    <div class="flex gap-sm">
                        <button class="btn btn-outline btn-sm" onclick="viewLocalSubmission('${row.dataset.id}')" title="View Details">
                            <span class="material-symbols-sharp">visibility</span>
                        </button>
                        <button class="btn btn-success btn-sm" onclick="approveSubmission('${row.dataset.id}')" title="Approve">
                            <span class="material-symbols-sharp">check</span>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="rejectSubmission('${row.dataset.id}')" title="Reject">
                            <span class="material-symbols-sharp">close</span>
                        </button>
                    </div>
                </td>
            `;
            
            tbody.insertBefore(row, tbody.firstChild);
        });
    }
}

function filterTable() {
    const search = document.getElementById('employee-search').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const department = document.getElementById('department-filter').value;
    
    const rows = document.querySelectorAll('#submissions-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const rowStatus = row.dataset.status;
        const rowDept = row.dataset.department;
        
        const matchSearch = text.includes(search);
        const matchStatus = !status || rowStatus === status;
        const matchDept = !department || rowDept === department;
        
        row.style.display = (matchSearch && matchStatus && matchDept) ? '' : 'none';
    });
}

function viewSubmission(id) {
    Modal.open(`
        <h3>Appraisal Details</h3>
        <p class="text-muted mb-lg">Full appraisal review for employee #${id}</p>
        
        <div class="mb-lg">
            <h4>Performance Summary</h4>
            <p>This section would display the full appraisal details including goals, ratings, and self-assessment.</p>
        </div>
        
        <div class="flex gap-md justify-end">
            <button class="btn btn-outline" onclick="Modal.close()">Close</button>
            <button class="btn btn-primary" onclick="Modal.close(); approveSubmission(${id})">Approve</button>
        </div>
    `);
}

function viewLocalSubmission(id) {
    Toast.info('Viewing locally submitted appraisal');
}

function approveSubmission(id) {
    openActionModal(id, 'approve', 'Approve Appraisal', 'btn-success');
}

function rejectSubmission(id) {
    openActionModal(id, 'reject', 'Reject Appraisal', 'btn-danger');
}

function requestInfo(id) {
    openActionModal(id, 'request_info', 'Request Additional Information', 'btn-warning');
}

function openActionModal(id, action, title, btnClass) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('action-submission-id').value = id;
    document.getElementById('action-type').value = action;
    
    const submitBtn = document.getElementById('action-submit-btn');
    submitBtn.className = 'btn ' + btnClass;
    submitBtn.textContent = action === 'approve' ? 'Approve' : (action === 'reject' ? 'Reject' : 'Send Request');
    
    document.getElementById('action-modal').classList.remove('hidden');
}

function closeActionModal() {
    document.getElementById('action-modal').classList.add('hidden');
    document.getElementById('action-form').reset();
}

document.getElementById('action-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('action-submission-id').value;
    const action = document.getElementById('action-type').value;
    const comments = this.querySelector('[name="hr_comments"]').value;
    
    // Update row status
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        const statusCell = row.querySelector('td:nth-child(6)');
        const actionsCell = row.querySelector('td:nth-child(7)');
        
        if (action === 'approve') {
            statusCell.innerHTML = '<span class="badge badge-success">Approved</span>';
            row.dataset.status = 'approved';
            Toast.success('Appraisal approved successfully');
        } else if (action === 'reject') {
            statusCell.innerHTML = '<span class="badge badge-danger">Rejected</span>';
            row.dataset.status = 'rejected';
            Toast.success('Appraisal rejected and returned for revision');
        } else {
            statusCell.innerHTML = '<span class="badge badge-info">Under Review</span>';
            row.dataset.status = 'under_review';
            Toast.success('Information request sent to employee');
        }
        
        // Remove action buttons for completed items
        if (action === 'approve' || action === 'reject') {
            actionsCell.innerHTML = `
                <button class="btn btn-outline btn-sm" onclick="viewSubmission(${id})" title="View Details">
                    <span class="material-symbols-sharp">visibility</span>
                </button>
            `;
        }
    }
    
    closeActionModal();
});
</script>
