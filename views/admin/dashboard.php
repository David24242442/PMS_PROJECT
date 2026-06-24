<?php
/**
 * Admin Dashboard View
 * CSV Upload and Data Management
 */
?>

<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
    <p class="page-subtitle">Upload and manage employee data</p>
</div>

<!-- Upload Section -->
<div class="card mb-lg">
    <div class="card-header">
        <h3 class="card-title">Upload Data</h3>
        <div class="flex gap-sm">
            <button id="export-excel-btn" class="btn btn-success hidden" onclick="exportToExcel()">
                <span class="material-symbols-sharp">download</span>
                Export to Excel
            </button>
        </div>
    </div>
    
    <div class="file-upload-zone" id="csv-upload-zone">
        <input type="file" accept=".csv,.xlsx,.xls" style="display:none" id="csv-file-input">
        <span class="material-symbols-sharp">cloud_upload</span>
        <p>Drag and drop your CSV or Excel file here</p>
        <small>or click to browse (Supported: .csv, .xlsx, .xls)</small>
    </div>
</div>

<!-- Data Table Container -->
<div class="table-container" id="csv-table-container">
    <div class="table-header">
        <h3 class="card-title">Uploaded Data</h3>
        <div class="flex gap-sm">
            <div class="search-box" style="width: 250px;">
                <span class="material-symbols-sharp">search</span>
                <input type="text" placeholder="Search data..." id="table-search">
            </div>
        </div>
    </div>
    
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>EMP001</td>
                    <td>John Doe</td>
                    <td>Engineering</td>
                    <td>Software Developer</td>
                    <td>john.doe@company.com</td>
                    <td><span class="badge badge-success">Active</span></td>
                </tr>
                <tr>
                    <td>EMP002</td>
                    <td>Jane Smith</td>
                    <td>Marketing</td>
                    <td>Marketing Manager</td>
                    <td>jane.smith@company.com</td>
                    <td><span class="badge badge-success">Active</span></td>
                </tr>
                <tr>
                    <td>EMP003</td>
                    <td>Mike Johnson</td>
                    <td>HR</td>
                    <td>HR Specialist</td>
                    <td>mike.johnson@company.com</td>
                    <td><span class="badge badge-success">Active</span></td>
                </tr>
                <tr>
                    <td>EMP004</td>
                    <td>Sarah Williams</td>
                    <td>Finance</td>
                    <td>Accountant</td>
                    <td>sarah.williams@company.com</td>
                    <td><span class="badge badge-warning">On Leave</span></td>
                </tr>
                <tr>
                    <td>EMP005</td>
                    <td>David Brown</td>
                    <td>Engineering</td>
                    <td>Senior Developer</td>
                    <td>david.brown@company.com</td>
                    <td><span class="badge badge-success">Active</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Stats -->
<div class="kpi-grid mt-lg">
    <div class="kpi-card">
        <span class="kpi-label">
            <span class="material-symbols-sharp">group</span>
            Total Employees
        </span>
        <span class="kpi-value">156</span>
        <span class="kpi-sublabel">+12 this month</span>
    </div>
    <div class="kpi-card success">
        <span class="kpi-label">
            <span class="material-symbols-sharp">check_circle</span>
            Active Employees
        </span>
        <span class="kpi-value">148</span>
        <span class="kpi-sublabel">94.8% of total</span>
    </div>
    <div class="kpi-card warning">
        <span class="kpi-label">
            <span class="material-symbols-sharp">pending</span>
            Pending Reviews
        </span>
        <span class="kpi-value">23</span>
        <span class="kpi-sublabel">Due this week</span>
    </div>
    <div class="kpi-card info">
        <span class="kpi-label">
            <span class="material-symbols-sharp">upload_file</span>
            Last Upload
        </span>
        <span class="kpi-value">Today</span>
        <span class="kpi-sublabel">employees_data.csv</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Table search functionality
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#csv-table-container tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
