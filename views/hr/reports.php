<?php
/**
 * HR Reports View
 * Generate and manage performance reports
 */
?>

<div class="page-header">
    <h1 class="page-title">HR Reports</h1>
    <p class="page-subtitle">Generate and manage performance reports</p>
</div>

<div class="dashboard-grid">
    <div class="dashboard-main">
        <!-- Report Generator -->
        <div class="card mb-lg">
            <div class="card-header">
                <h3 class="card-title">Generate New Report</h3>
            </div>
            
            <form id="report-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Report Type</label>
                        <select class="form-control" name="report_type" required>
                            <option value="">Select report type</option>
                            <option value="performance_summary">Performance Summary</option>
                            <option value="department_overview">Department Overview</option>
                            <option value="training_needs">Training Needs Analysis</option>
                            <option value="goal_completion">Goal Completion Report</option>
                            <option value="rating_distribution">Rating Distribution</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="department">
                            <option value="">All Departments</option>
                            <option value="engineering">Engineering</option>
                            <option value="marketing">Marketing</option>
                            <option value="design">Design</option>
                            <option value="hr">HR</option>
                            <option value="finance">Finance</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Report Title</label>
                    <input type="text" class="form-control" name="report_title" 
                           placeholder="Enter a descriptive title for this report" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea class="form-control" name="notes" rows="3" 
                              placeholder="Add any additional context or notes for this report..."></textarea>
                </div>
                
                <div class="flex gap-md">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-sharp">description</span>
                        Generate Report
                    </button>
                    <button type="button" class="btn btn-outline" id="preview-btn">
                        <span class="material-symbols-sharp">visibility</span>
                        Preview
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Recent Reports -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="card-title">Recent Reports</h3>
                <button class="btn btn-outline btn-sm">
                    <span class="material-symbols-sharp">filter_list</span>
                    Filter
                </button>
            </div>
            
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Report Title</th>
                            <th>Type</th>
                            <th>Department</th>
                            <th>Created</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reports-table-body">
                        <tr>
                            <td><strong>Q4 Performance Summary</strong></td>
                            <td><span class="badge badge-primary">Performance Summary</span></td>
                            <td>All Departments</td>
                            <td>Dec 15, 2024</td>
                            <td>Sarah Johnson</td>
                            <td>
                                <div class="flex gap-sm">
                                    <button class="btn btn-outline btn-sm" title="View">
                                        <span class="material-symbols-sharp">visibility</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Download">
                                        <span class="material-symbols-sharp">download</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Share">
                                        <span class="material-symbols-sharp">share</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Engineering Team Review</strong></td>
                            <td><span class="badge badge-info">Department Overview</span></td>
                            <td>Engineering</td>
                            <td>Dec 10, 2024</td>
                            <td>Sarah Johnson</td>
                            <td>
                                <div class="flex gap-sm">
                                    <button class="btn btn-outline btn-sm" title="View">
                                        <span class="material-symbols-sharp">visibility</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Download">
                                        <span class="material-symbols-sharp">download</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Share">
                                        <span class="material-symbols-sharp">share</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>2024 Training Recommendations</strong></td>
                            <td><span class="badge badge-success">Training Needs</span></td>
                            <td>All Departments</td>
                            <td>Dec 5, 2024</td>
                            <td>Mike Wilson</td>
                            <td>
                                <div class="flex gap-sm">
                                    <button class="btn btn-outline btn-sm" title="View">
                                        <span class="material-symbols-sharp">visibility</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Download">
                                        <span class="material-symbols-sharp">download</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" title="Share">
                                        <span class="material-symbols-sharp">share</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="dashboard-sidebar">
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Report Summary</h3>
            </div>
            <div class="flex flex-col gap-md">
                <div class="flex justify-between items-center">
                    <span class="text-muted">Total Reports</span>
                    <strong>24</strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-muted">This Month</span>
                    <strong>8</strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-muted">Pending Reviews</span>
                    <strong>3</strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-muted">Shared Reports</span>
                    <strong>12</strong>
                </div>
            </div>
        </div>
        
        <!-- Report Templates -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Templates</h3>
            </div>
            <div class="flex flex-col gap-sm">
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;" onclick="useTemplate('monthly')">
                    <span class="material-symbols-sharp">calendar_month</span>
                    Monthly Summary
                </button>
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;" onclick="useTemplate('quarterly')">
                    <span class="material-symbols-sharp">date_range</span>
                    Quarterly Review
                </button>
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;" onclick="useTemplate('annual')">
                    <span class="material-symbols-sharp">event</span>
                    Annual Report
                </button>
                <button class="btn btn-outline" style="width: 100%; justify-content: flex-start;" onclick="useTemplate('comparison')">
                    <span class="material-symbols-sharp">compare</span>
                    YoY Comparison
                </button>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Export Options</h3>
            </div>
            <div class="flex flex-col gap-sm">
                <button class="btn btn-primary" style="width: 100%;">
                    <span class="material-symbols-sharp">picture_as_pdf</span>
                    Export as PDF
                </button>
                <button class="btn btn-success" style="width: 100%;">
                    <span class="material-symbols-sharp">table_chart</span>
                    Export as Excel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date();
    const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.querySelector('[name="date_from"]').valueAsDate = firstOfMonth;
    document.querySelector('[name="date_to"]').valueAsDate = today;
    
    // Form submission
    document.getElementById('report-form').addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
    
    // Preview button
    document.getElementById('preview-btn').addEventListener('click', function() {
        previewReport();
    });
});

function generateReport() {
    const form = document.getElementById('report-form');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => data[key] = value);
    
    Toast.info('Generating report...');
    
    // Simulate report generation
    setTimeout(() => {
        // Add to table
        const tbody = document.getElementById('reports-table-body');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><strong>${data.report_title}</strong></td>
            <td><span class="badge badge-primary">${formatReportType(data.report_type)}</span></td>
            <td>${data.department || 'All Departments'}</td>
            <td>${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
            <td>Current User</td>
            <td>
                <div class="flex gap-sm">
                    <button class="btn btn-outline btn-sm" title="View">
                        <span class="material-symbols-sharp">visibility</span>
                    </button>
                    <button class="btn btn-outline btn-sm" title="Download">
                        <span class="material-symbols-sharp">download</span>
                    </button>
                    <button class="btn btn-outline btn-sm" title="Share">
                        <span class="material-symbols-sharp">share</span>
                    </button>
                </div>
            </td>
        `;
        tbody.insertBefore(newRow, tbody.firstChild);
        
        Toast.success('Report generated successfully!');
        form.reset();
        
        // Reset dates
        const today = new Date();
        const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        document.querySelector('[name="date_from"]').valueAsDate = firstOfMonth;
        document.querySelector('[name="date_to"]').valueAsDate = today;
    }, 1500);
}

function formatReportType(type) {
    const types = {
        'performance_summary': 'Performance Summary',
        'department_overview': 'Department Overview',
        'training_needs': 'Training Needs',
        'goal_completion': 'Goal Completion',
        'rating_distribution': 'Rating Distribution'
    };
    return types[type] || type;
}

function previewReport() {
    const form = document.getElementById('report-form');
    const reportType = form.querySelector('[name="report_type"]').value;
    
    if (!reportType) {
        Toast.warning('Please select a report type first');
        return;
    }
    
    Modal.open(`
        <h3>Report Preview</h3>
        <p class="text-muted mb-lg">Preview of ${formatReportType(reportType)}</p>
        
        <div style="background: var(--color-lighter); padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem;">Sample Data</h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div>
                    <span class="text-muted">Total Employees</span>
                    <div class="font-bold" style="font-size: 1.5rem;">156</div>
                </div>
                <div>
                    <span class="text-muted">Avg Rating</span>
                    <div class="font-bold" style="font-size: 1.5rem;">4.2</div>
                </div>
                <div>
                    <span class="text-muted">Goals Met</span>
                    <div class="font-bold" style="font-size: 1.5rem;">78%</div>
                </div>
                <div>
                    <span class="text-muted">Training Hours</span>
                    <div class="font-bold" style="font-size: 1.5rem;">1,240</div>
                </div>
            </div>
        </div>
        
        <div class="flex gap-md justify-end">
            <button class="btn btn-outline" onclick="Modal.close()">Close</button>
            <button class="btn btn-primary" onclick="Modal.close(); generateReport();">Generate Full Report</button>
        </div>
    `);
}

function useTemplate(template) {
    const form = document.getElementById('report-form');
    const today = new Date();
    
    switch(template) {
        case 'monthly':
            form.querySelector('[name="report_type"]').value = 'performance_summary';
            form.querySelector('[name="report_title"]').value = `Monthly Performance Summary - ${today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}`;
            break;
        case 'quarterly':
            form.querySelector('[name="report_type"]').value = 'department_overview';
            const quarter = Math.ceil((today.getMonth() + 1) / 3);
            form.querySelector('[name="report_title"]').value = `Q${quarter} ${today.getFullYear()} Department Review`;
            break;
        case 'annual':
            form.querySelector('[name="report_type"]').value = 'performance_summary';
            form.querySelector('[name="report_title"]').value = `Annual Performance Report ${today.getFullYear()}`;
            break;
        case 'comparison':
            form.querySelector('[name="report_type"]').value = 'rating_distribution';
            form.querySelector('[name="report_title"]').value = `Year-over-Year Performance Comparison`;
            break;
    }
    
    Toast.info(`${template.charAt(0).toUpperCase() + template.slice(1)} template applied`);
}
</script>
