/**
 * PMS - Performance Management System
 * Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initToasts();
    initModals();
    initFileUpload();
    initCharts();
});

// ============================================
// Toast Notifications
// ============================================
const ToastManager = {
    container: null,

    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = {
            success: 'check_circle',
            danger: 'error',
            warning: 'warning',
            info: 'info'
        };

        toast.innerHTML = `
            <span class="material-symbols-sharp">${icons[type] || 'info'}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <span class="material-symbols-sharp">close</span>
            </button>
        `;

        this.container.appendChild(toast);

        // Auto remove after duration
        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'danger'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

function initToasts() {
    ToastManager.init();
    window.Toast = ToastManager;
}

// ============================================
// Modal Management
// ============================================
const ModalManager = {
    overlay: null,
    content: null,

    init() {
        this.overlay = document.getElementById('modal-overlay');
        this.content = document.getElementById('modal-content');

        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });

            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !this.overlay.classList.contains('hidden')) {
                    this.close();
                }
            });
        }
    },

    open(content) {
        if (this.content && this.overlay) {
            this.content.innerHTML = content;
            this.overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    },

    close() {
        if (this.overlay) {
            this.overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
};

function initModals() {
    ModalManager.init();
    window.Modal = ModalManager;
}

// ============================================
// File Upload with CSV Parsing
// ============================================
function initFileUpload() {
    const uploadZones = document.querySelectorAll('.file-upload-zone');

    uploadZones.forEach(zone => {
        const input = zone.querySelector('input[type="file"]') || createHiddenFileInput(zone);

        // Click to upload
        zone.addEventListener('click', () => input.click());

        // Drag and drop
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('dragover');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0], zone);
            }
        });

        // File input change
        input.addEventListener('change', () => {
            if (input.files.length > 0) {
                handleFileUpload(input.files[0], zone);
            }
        });
    });
}

function createHiddenFileInput(zone) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv,.xlsx,.xls';
    input.style.display = 'none';
    zone.appendChild(input);
    return input;
}

function handleFileUpload(file, zone) {
    const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

    // Check file extension as fallback
    const extension = file.name.split('.').pop().toLowerCase();
    const isCSV = extension === 'csv';
    const isExcel = ['xls', 'xlsx'].includes(extension);

    if (!isCSV && !isExcel) {
        Toast.error('Please upload a CSV or Excel file');
        return;
    }

    Toast.info(`Processing ${file.name}...`);

    if (isCSV && typeof Papa !== 'undefined') {
        Papa.parse(file, {
            header: true,
            complete: function (results) {
                if (results.data && results.data.length > 0) {
                    displayCSVData(results.data, results.meta.fields);
                    Toast.success(`Loaded ${results.data.length} rows from ${file.name}`);
                }
            },
            error: function (error) {
                Toast.error('Error parsing CSV: ' + error.message);
            }
        });
    } else if (isExcel && typeof XLSX !== 'undefined') {
        const reader = new FileReader();
        reader.onload = function (e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

            if (jsonData.length > 0) {
                const headers = jsonData[0];
                const rows = jsonData.slice(1).map(row => {
                    const obj = {};
                    headers.forEach((header, i) => {
                        obj[header] = row[i] || '';
                    });
                    return obj;
                });
                displayCSVData(rows, headers);
                Toast.success(`Loaded ${rows.length} rows from ${file.name}`);
            }
        };
        reader.readAsArrayBuffer(file);
    } else {
        // Fallback: send to server
        uploadFileToServer(file);
    }
}

function displayCSVData(data, headers) {
    const tableContainer = document.getElementById('csv-table-container');
    if (!tableContainer) return;

    // Store data globally for export
    window.csvData = { data, headers };

    let tableHTML = `
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        ${headers.map(h => `<th>${escapeHtml(h)}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
    `;

    data.forEach(row => {
        tableHTML += '<tr>';
        headers.forEach(header => {
            tableHTML += `<td>${escapeHtml(row[header] || '')}</td>`;
        });
        tableHTML += '</tr>';
    });

    tableHTML += '</tbody></table></div>';
    tableContainer.innerHTML = tableHTML;

    // Show export button
    const exportBtn = document.getElementById('export-excel-btn');
    if (exportBtn) {
        exportBtn.classList.remove('hidden');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function uploadFileToServer(file) {
    const formData = new FormData();
    formData.append('csv_file', file);

    fetch('index.php?page=api-upload-csv', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success(data.message);
                if (data.data) {
                    displayCSVData(data.data, Object.keys(data.data[0] || {}));
                }
            } else {
                Toast.error(data.message || 'Upload failed');
            }
        })
        .catch(error => {
            Toast.error('Upload error: ' + error.message);
        });
}

// ============================================
// Excel Export
// ============================================
function exportToExcel() {
    if (!window.csvData || !window.csvData.data) {
        Toast.warning('No data to export');
        return;
    }

    if (typeof XLSX === 'undefined') {
        Toast.error('Excel export library not loaded');
        return;
    }

    const { data, headers } = window.csvData;

    // Create workbook and worksheet
    const ws = XLSX.utils.json_to_sheet(data, { header: headers });
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Data');

    // Generate filename with date
    const date = new Date().toISOString().split('T')[0];
    const filename = `pms_export_${date}.xlsx`;

    // Download
    XLSX.writeFile(wb, filename);
    Toast.success('Excel file downloaded successfully');
}

// Make exportToExcel globally available
window.exportToExcel = exportToExcel;

// ============================================
// Charts Initialization
// ============================================
function initCharts() {
    initWeeklyStatusChart();
}

function initWeeklyStatusChart() {
    const chartCanvas = document.getElementById('weekly-chart');
    if (!chartCanvas || typeof Chart === 'undefined') return;

    const ctx = chartCanvas.getContext('2d');

    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(124, 58, 237, 0.3)');
    gradient.addColorStop(1, 'rgba(124, 58, 237, 0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Completion Rate',
                data: [65, 72, 68, 75, 82, 78, 85],
                borderColor: '#7c3aed',
                backgroundColor: gradient,
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#7c3aed',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    cornerRadius: 8,
                    padding: 12
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748b'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        color: '#64748b',
                        callback: function (value) {
                            return value + '%';
                        }
                    },
                    min: 0,
                    max: 100
                }
            }
        }
    });
}

// ============================================
// Form Utilities
// ============================================
function validateForm(form) {
    const required = form.querySelectorAll('[required]');
    let isValid = true;

    required.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');

            // Remove error class on input
            field.addEventListener('input', () => {
                field.classList.remove('error');
            }, { once: true });
        }
    });

    return isValid;
}

function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};

    formData.forEach((value, key) => {
        if (data[key]) {
            // Handle multiple values (checkboxes, multi-select)
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    });

    return data;
}

// Make utilities globally available
window.validateForm = validateForm;
window.serializeForm = serializeForm;

// ============================================
// Loading States
// ============================================
function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
    element.dataset.originalText = element.innerHTML;
    element.innerHTML = '<span class="spinner"></span> Loading...';
}

function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
    element.innerHTML = element.dataset.originalText || 'Submit';
}

window.showLoading = showLoading;
window.hideLoading = hideLoading;
