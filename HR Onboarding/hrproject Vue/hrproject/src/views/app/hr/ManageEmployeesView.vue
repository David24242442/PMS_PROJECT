<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';

const userstore = useUsersStore();

// UI State
const loading = ref(false);
const showUploadSection = ref(false);
const uploading = ref(false);
const quickSyncing = ref(false);
const uploadProgress = ref(0);
const uploadSuccessMessage = ref('');
const uploadErrorMessage = ref('');

// Data State
const employees = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: 0,
    from: 0,
    to: 0
});

// Stats State
const stats = ref({
    total_employees: 0,
    total_locations: 0,
    categories: {},
    gender: {},
    latest_month: 'AUGUST 2026'
});

// Filters
const filterSearch = ref('');
const filterLocation = ref('');
const filterCategory = ref('');
const filterMonth = ref('');
const sortBy = ref('sr_no');
const sortOrder = ref('asc');
const perPage = ref(25);

// Filter options
const availableLocations = ref([]);
const availableCategories = ref([]);
const availableMonths = ref([]);

// Upload Form
const selectedFile = ref(null);
const uploadMonthYear = ref('AUGUST 2026');
const isDragging = ref(false);

// Load Employees with filters & pagination
const fetchEmployees = async (page = 1) => {
    loading.value = true;
    userstore.setIsLoading(true);
    try {
        const params = {
            page: page,
            per_page: perPage.value,
            search: filterSearch.value,
            location: filterLocation.value,
            category: filterCategory.value,
            month_year: filterMonth.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value
        };

        const res = await axios.get('/pms/monthly-employees', { params });
        if (res.data && res.data.status === 'success') {
            const result = res.data.data;
            employees.value = result.data || [];
            pagination.value = {
                current_page: result.current_page || 1,
                last_page: result.last_page || 1,
                per_page: result.per_page || 25,
                total: result.total || 0,
                from: result.from || 0,
                to: result.to || 0
            };

            if (res.data.filters) {
                if (res.data.filters.locations && res.data.filters.locations.length > 0) {
                    availableLocations.value = res.data.filters.locations;
                }
                if (res.data.filters.categories && res.data.filters.categories.length > 0) {
                    availableCategories.value = res.data.filters.categories;
                }
                if (res.data.filters.months && res.data.filters.months.length > 0) {
                    availableMonths.value = res.data.filters.months;
                }
            }
        }
    } catch (err) {
        console.error('Failed to load monthly employees:', err);
    } finally {
        loading.value = false;
        userstore.setIsLoading(false);
    }
};

// Load Stats
const fetchStats = async () => {
    try {
        const res = await axios.get('/pms/monthly-employees/stats', {
            params: { month_year: filterMonth.value }
        });
        if (res.data && res.data.status === 'success') {
            stats.value = res.data.data;
            if (stats.value.latest_month && !uploadMonthYear.value) {
                uploadMonthYear.value = stats.value.latest_month;
            }
        }
    } catch (err) {
        console.error('Failed to load employee stats:', err);
    }
};

// Search debounce
let searchTimer = null;
const onSearchInput = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        fetchEmployees(1);
    }, 350);
};

// Change Filter Handlers
const onFilterChange = () => {
    fetchEmployees(1);
    fetchStats();
};

const resetFilters = () => {
    filterSearch.value = '';
    filterLocation.value = '';
    filterCategory.value = '';
    filterMonth.value = '';
    perPage.value = 25;
    sortBy.value = 'sr_no';
    sortOrder.value = 'asc';
    fetchEmployees(1);
    fetchStats();
};

// Sorting
const toggleSort = (column) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    fetchEmployees(1);
};

// File Selection
const onFileSelected = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        setFile(files[0]);
    }
};

const onDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const onDropFile = (e) => {
    e.preventDefault();
    isDragging.value = false;
    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
        setFile(files[0]);
    }
};

const setFile = (file) => {
    uploadSuccessMessage.value = '';
    uploadErrorMessage.value = '';
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
        uploadErrorMessage.value = 'Please select a valid Excel (.xlsx) or CSV (.csv) file.';
        selectedFile.value = null;
        return;
    }
    selectedFile.value = file;

    // Try to extract month year from filename
    const nameMatch = file.name.match(/(january|february|march|april|may|june|july|august|september|october|november|december)\s*\d{4}/i);
    if (nameMatch) {
        uploadMonthYear.value = nameMatch[0].toUpperCase();
    }
};

// Upload Submission
const submitUpload = async () => {
    if (!selectedFile.value) {
        uploadErrorMessage.value = 'Please choose a file to upload.';
        return;
    }

    uploading.value = true;
    uploadProgress.value = 20;
    uploadSuccessMessage.value = '';
    uploadErrorMessage.value = '';

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('month_year', uploadMonthYear.value);

    try {
        uploadProgress.value = 50;
        const res = await axios.post('/pms/monthly-employees/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded * 90) / progressEvent.total);
                }
            }
        });

        uploadProgress.value = 100;
        if (res.data && res.data.status === 'success') {
            uploadSuccessMessage.value = res.data.message || 'File uploaded successfully!';
            selectedFile.value = null;
            // Refresh data and stats
            await fetchEmployees(1);
            await fetchStats();
        } else {
            uploadErrorMessage.value = res.data.message || 'Upload failed.';
        }
    } catch (err) {
        console.error('Upload error:', err);
        uploadErrorMessage.value = err.response?.data?.message || 'Server error while processing employee records.';
    } finally {
        uploading.value = false;
    }
};

// Quick Ingest from Server 20 Local Excel File
const triggerLocalSync = async () => {
    quickSyncing.value = true;
    uploadSuccessMessage.value = '';
    uploadErrorMessage.value = '';
    try {
        const res = await axios.get('/pms/sync-local-excel', {
            params: { month_year: uploadMonthYear.value }
        });
        if (res.data && res.data.status === 'success') {
            uploadSuccessMessage.value = res.data.message;
            await fetchEmployees(1);
            await fetchStats();
        } else {
            uploadErrorMessage.value = res.data.message || 'Direct server sync failed.';
        }
    } catch (err) {
        console.error('Local sync error:', err);
        uploadErrorMessage.value = err.response?.data?.message || 'Server-side file not found or sync failed. Please select file to upload.';
    } finally {
        quickSyncing.value = false;
    }
};

// Download Template
const downloadTemplate = () => {
    window.open(axios.defaults.baseURL + '/pms/monthly-employees/template', '_blank');
};

// Format Helpers
const formatNumber = (num) => {
    return (num || 0).toLocaleString();
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

onMounted(() => {
    fetchEmployees(1);
    fetchStats();
});
</script>

<template>
    <div class="manage-employees-container">
        
        <!-- Top Header Banner -->
        <div class="header-card">
            <div class="header-content">
                <div class="title-section">
                    <div class="badge-pill">
                        <i class="pi pi-database"></i> HR Payroll Ingestion
                    </div>
                    <h1>Manage Employees</h1>
                    <p class="subtitle">Authoritative company-wide monthly payroll employee directory for PMS performance tracking and line manager assignments.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary" @click="downloadTemplate" title="Download sample CSV format">
                        <i class="pi pi-download"></i> Sample Template
                    </button>
                    <button class="btn btn-secondary" @click="fetchEmployees(pagination.current_page)" title="Refresh employee records">
                        <i class="pi pi-refresh" :class="{ 'pi-spin': loading }"></i> Refresh
                    </button>
                    <button class="btn btn-primary" @click="showUploadSection = !showUploadSection">
                        <i class="pi" :class="showUploadSection ? 'pi-chevron-up' : 'pi-cloud-upload'"></i>
                        {{ showUploadSection ? 'Hide Upload' : 'Upload Employees' }}
                    </button>
                </div>
            </div>

            <!-- Stats Metric Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue">
                        <i class="pi pi-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total Employees</span>
                        <span class="stat-value">{{ formatNumber(stats.total_employees) }}</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-emerald">
                        <i class="pi pi-map-marker"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Locations / Branches</span>
                        <span class="stat-value">{{ stats.total_locations || 0 }}</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-amber">
                        <i class="pi pi-briefcase"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Contract</span>
                        <span class="stat-value">{{ formatNumber(stats.categories?.CONTRACT || 0) }}</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-indigo">
                        <i class="pi pi-shield"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Permanent</span>
                        <span class="stat-value">{{ formatNumber(stats.categories?.PERMANENT || 0) }}</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-purple">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Active Batch</span>
                        <span class="stat-value stat-month">{{ stats.latest_month || 'AUGUST 2026' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Employees Section (Collapsible) -->
        <transition name="slide-fade">
            <div v-show="showUploadSection" class="upload-section-card">
                <div class="upload-section-header">
                    <div>
                        <h2><i class="pi pi-cloud-upload text-indigo"></i> Upload Monthly Employees</h2>
                        <p class="section-desc">Upload company employee records (Excel <code>.xlsx</code> or <code>.csv</code>) to ingest directly into the <code>Monthly_Employees</code> table on Server 20.</p>
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="upload-grid">
                    <!-- Drop Zone -->
                    <div 
                        class="drop-zone"
                        :class="{ 'drop-zone-active': isDragging, 'has-file': selectedFile }"
                        @dragover="onDragOver"
                        @dragleave="onDragLeave"
                        @drop="onDropFile"
                        @click="$refs.fileInput.click()"
                    >
                        <input 
                            ref="fileInput" 
                            type="file" 
                            accept=".xlsx, .xls, .csv" 
                            class="file-input-hidden" 
                            @change="onFileSelected" 
                        />
                        
                        <div v-if="!selectedFile" class="drop-zone-prompt">
                            <div class="upload-icon-circle">
                                <i class="pi pi-file-excel"></i>
                            </div>
                            <h3>Drag & Drop Employee File Here</h3>
                            <p>Supports <strong>.xlsx</strong> (Excel) or <strong>.csv</strong></p>
                            <span class="browse-link"><i class="pi pi-search"></i> Or click to browse files</span>
                        </div>

                        <div v-else class="drop-zone-selected">
                            <div class="file-badge">
                                <i class="pi pi-file text-xl"></i>
                                <div class="file-meta">
                                    <span class="file-name">{{ selectedFile.name }}</span>
                                    <span class="file-size">{{ formatFileSize(selectedFile.size) }}</span>
                                </div>
                                <button type="button" class="btn-clear-file" @click.stop="selectedFile = null" title="Remove file">
                                    <i class="pi pi-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Configuration & Action -->
                    <div class="upload-controls-panel">
                        <div class="form-group">
                            <label for="monthYearInput"><i class="pi pi-calendar"></i> Payroll Month / Batch</label>
                            <input 
                                id="monthYearInput"
                                v-model="uploadMonthYear" 
                                type="text" 
                                class="form-control" 
                                placeholder="e.g. AUGUST 2026" 
                            />
                            <small class="form-help">Specify the payroll cycle period for these employee records.</small>
                        </div>

                        <div class="upload-info-box">
                            <h4><i class="pi pi-info-circle"></i> Required Columns in File</h4>
                            <p>The file should contain these headers (matching your payroll export):</p>
                            <div class="tags-cloud">
                                <span class="header-tag">Sr.No</span>
                                <span class="header-tag">Emp Id</span>
                                <span class="header-tag">Employee Name</span>
                                <span class="header-tag">Location</span>
                                <span class="header-tag">Designation</span>
                                <span class="header-tag">Sex</span>
                                <span class="header-tag">Category</span>
                            </div>
                        </div>

                        <div class="upload-actions-footer">
                            <button 
                                class="btn btn-primary btn-block btn-upload" 
                                :disabled="!selectedFile || uploading || quickSyncing" 
                                @click="submitUpload"
                            >
                                <i class="pi" :class="uploading ? 'pi-spin pi-spinner' : 'pi-check-circle'"></i>
                                {{ uploading ? 'Ingesting Employees...' : 'Upload & Sync to PMS' }}
                            </button>
                            <button 
                                type="button"
                                class="btn btn-secondary btn-block mt-2" 
                                :disabled="uploading || quickSyncing" 
                                @click="triggerLocalSync"
                                title="Ingest directly from AUGUST 2026 PAYROLL DATA.xlsx placed on Server 20"
                            >
                                <i class="pi" :class="quickSyncing ? 'pi-spin pi-spinner' : 'pi-bolt'"></i>
                                {{ quickSyncing ? 'Ingesting Server Excel File...' : 'One-Click Ingest from Server Excel' }}
                            </button>
                        </div>

                        <!-- Upload Progress Bar -->
                        <div v-if="uploading" class="progress-bar-container">
                            <div class="progress-bar-fill" :style="{ width: uploadProgress + '%' }"></div>
                            <span class="progress-text">{{ uploadProgress }}% Completed</span>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <div v-if="uploadSuccessMessage" class="alert alert-success">
                    <i class="pi pi-check-circle alert-icon"></i>
                    <div>
                        <strong>Upload Succeeded!</strong>
                        <p>{{ uploadSuccessMessage }}</p>
                    </div>
                </div>

                <div v-if="uploadErrorMessage" class="alert alert-danger">
                    <i class="pi pi-exclamation-triangle alert-icon"></i>
                    <div>
                        <strong>Upload Error</strong>
                        <p>{{ uploadErrorMessage }}</p>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Search, Filter & Table Card -->
        <div class="data-card">
            <div class="filter-toolbar">
                <div class="search-box">
                    <i class="pi pi-search search-icon"></i>
                    <input 
                        v-model="filterSearch" 
                        type="text" 
                        placeholder="Search by Emp ID, Name, Designation, Location..." 
                        class="search-input"
                        @input="onSearchInput"
                    />
                    <button v-if="filterSearch" class="btn-clear-search" @click="filterSearch = ''; fetchEmployees(1)">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <div class="filter-selects">
                    <!-- Location Filter -->
                    <div class="filter-item">
                        <select v-model="filterLocation" class="filter-select" @change="onFilterChange">
                            <option value="">All Locations ({{ availableLocations.length }})</option>
                            <option v-for="loc in availableLocations" :key="loc" :value="loc">
                                {{ loc }}
                            </option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-item">
                        <select v-model="filterCategory" class="filter-select" @change="onFilterChange">
                            <option value="">All Categories</option>
                            <option v-for="cat in availableCategories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                    </div>

                    <!-- Month Filter -->
                    <div class="filter-item">
                        <select v-model="filterMonth" class="filter-select" @change="onFilterChange">
                            <option value="">All Batches</option>
                            <option v-for="m in availableMonths" :key="m" :value="m">
                                {{ m }}
                            </option>
                        </select>
                    </div>

                    <!-- Per Page -->
                    <div class="filter-item per-page-item">
                        <select v-model="perPage" class="filter-select" @change="onFilterChange">
                            <option :value="15">15 per page</option>
                            <option :value="25">25 per page</option>
                            <option :value="50">50 per page</option>
                            <option :value="100">100 per page</option>
                        </select>
                    </div>

                    <button class="btn btn-outline" @click="resetFilters" title="Reset all filters">
                        <i class="pi pi-filter-slash"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Table Header Stats -->
            <div class="table-meta-bar">
                <span>Showing <strong>{{ pagination.from || 0 }}</strong> to <strong>{{ pagination.to || 0 }}</strong> of <strong>{{ formatNumber(pagination.total) }}</strong> employee records</span>
                <span v-if="filterLocation || filterCategory || filterSearch" class="filter-indicator">
                    <i class="pi pi-filter"></i> Filters Active
                </span>
            </div>

            <!-- Employees Data Table -->
            <div class="table-responsive">
                <table class="employees-table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer" @click="toggleSort('sr_no')">
                                Sr. No
                                <i class="pi" :class="sortBy === 'sr_no' ? (sortOrder === 'asc' ? 'pi-sort-amount-up' : 'pi-sort-amount-down') : 'pi-sort-alt text-muted'"></i>
                            </th>
                            <th class="cursor-pointer" @click="toggleSort('emp_id')">
                                Emp ID
                                <i class="pi" :class="sortBy === 'emp_id' ? (sortOrder === 'asc' ? 'pi-sort-amount-up' : 'pi-sort-amount-down') : 'pi-sort-alt text-muted'"></i>
                            </th>
                            <th class="cursor-pointer" @click="toggleSort('employee_name')">
                                Employee Name
                                <i class="pi" :class="sortBy === 'employee_name' ? (sortOrder === 'asc' ? 'pi-sort-amount-up' : 'pi-sort-amount-down') : 'pi-sort-alt text-muted'"></i>
                            </th>
                            <th class="cursor-pointer" @click="toggleSort('location')">
                                Location
                                <i class="pi" :class="sortBy === 'location' ? (sortOrder === 'asc' ? 'pi-sort-amount-up' : 'pi-sort-amount-down') : 'pi-sort-alt text-muted'"></i>
                            </th>
                            <th class="cursor-pointer" @click="toggleSort('designation')">
                                Designation
                                <i class="pi" :class="sortBy === 'designation' ? (sortOrder === 'asc' ? 'pi-sort-amount-up' : 'pi-sort-amount-down') : 'pi-sort-alt text-muted'"></i>
                            </th>
                            <th>Sex</th>
                            <th class="cursor-pointer" @click="toggleSort('category')">
                                Category
                                <i class="pi" :class="sortBy === 'category' ? (sortOrder === 'asc' ? 'pi-sort-amount-up' : 'pi-sort-amount-down') : 'pi-sort-alt text-muted'"></i>
                            </th>
                            <th>Batch Month</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && employees.length === 0">
                            <td colspan="9" class="text-center py-5">
                                <i class="pi pi-spin pi-spinner text-2xl text-indigo"></i>
                                <p class="mt-2 text-muted">Loading employee records...</p>
                            </td>
                        </tr>

                        <tr v-else-if="employees.length === 0">
                            <td colspan="9" class="text-center py-5 empty-state">
                                <div class="empty-icon-circle">
                                    <i class="pi pi-inbox"></i>
                                </div>
                                <h3>No Employee Records Found</h3>
                                <p class="text-muted">No records match your filters, or no records have been uploaded yet.</p>
                                <button class="btn btn-primary mt-3" @click="showUploadSection = true">
                                    <i class="pi pi-cloud-upload"></i> Upload AUGUST 2026 Payroll Data
                                </button>
                            </td>
                        </tr>

                        <tr v-for="emp in employees" :key="emp.id" class="employee-row">
                            <td class="col-sr">{{ emp.sr_no || emp.id }}</td>
                            <td class="col-empid">
                                <span class="emp-code-badge">{{ emp.emp_id }}</span>
                            </td>
                            <td class="col-name">
                                <div class="name-cell">
                                    <span class="employee-name-text">{{ emp.employee_name }}</span>
                                </div>
                            </td>
                            <td class="col-location">
                                <span class="location-badge">
                                    <i class="pi pi-map-marker text-xs"></i> {{ emp.location || 'N/A' }}
                                </span>
                            </td>
                            <td class="col-designation">
                                <span class="designation-text">{{ emp.designation || 'N/A' }}</span>
                            </td>
                            <td class="col-sex">
                                <span 
                                    class="badge" 
                                    :class="emp.sex?.toLowerCase() === 'female' ? 'badge-female' : 'badge-male'"
                                >
                                    {{ emp.sex || '—' }}
                                </span>
                            </td>
                            <td class="col-category">
                                <span 
                                    class="badge"
                                    :class="{
                                        'badge-contract': emp.category === 'CONTRACT',
                                        'badge-permanent': emp.category === 'PERMANENT',
                                        'badge-outsource': emp.category === 'OUTSOURCE'
                                    }"
                                >
                                    {{ emp.category || 'N/A' }}
                                </span>
                            </td>
                            <td class="col-month text-muted text-xs">
                                {{ emp.month_year || 'AUGUST 2026' }}
                            </td>
                            <td class="col-status">
                                <span class="badge badge-active">
                                    <i class="pi pi-check"></i> {{ emp.status || 'ACTIVE' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="pagination.last_page > 1" class="pagination-bar">
                <div class="pagination-info">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                </div>
                <div class="pagination-controls">
                    <button 
                        class="page-btn" 
                        :disabled="pagination.current_page === 1 || loading" 
                        @click="fetchEmployees(1)"
                        title="First Page"
                    >
                        <i class="pi pi-angle-double-left"></i>
                    </button>
                    <button 
                        class="page-btn" 
                        :disabled="pagination.current_page === 1 || loading" 
                        @click="fetchEmployees(pagination.current_page - 1)"
                        title="Previous Page"
                    >
                        <i class="pi pi-angle-left"></i> Prev
                    </button>

                    <span class="current-page-indicator">
                        {{ pagination.current_page }}
                    </span>

                    <button 
                        class="page-btn" 
                        :disabled="pagination.current_page === pagination.last_page || loading" 
                        @click="fetchEmployees(pagination.current_page + 1)"
                        title="Next Page"
                    >
                        Next <i class="pi pi-angle-right"></i>
                    </button>
                    <button 
                        class="page-btn" 
                        :disabled="pagination.current_page === pagination.last_page || loading" 
                        @click="fetchEmployees(pagination.last_page)"
                        title="Last Page"
                    >
                        <i class="pi pi-angle-double-right"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.manage-employees-container {
    padding: 0 4px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Header Card */
.header-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 12px;
    padding: 14px 20px;
    color: white;
    box-shadow: 0 6px 18px -4px rgba(15, 23, 42, 0.2);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(129, 140, 248, 0.3);
    color: #a5b4fc;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 2px 8px;
    border-radius: 9999px;
    margin-bottom: 4px;
}

.title-section h1 {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 2px 0;
    letter-spacing: -0.02em;
    color: #ffffff;
}

.subtitle {
    margin: 0;
    font-size: 0.82rem;
    color: #94a3b8;
    max-width: 680px;
    line-height: 1.35;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.header-actions .btn {
    padding: 7px 13px;
    font-size: 0.82rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 4px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: transform 0.2s ease, background 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.09);
}

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.icon-blue { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
.icon-emerald { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.icon-amber { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.icon-indigo { background: rgba(99, 102, 241, 0.2); color: #818cf8; }
.icon-purple { background: rgba(168, 85, 247, 0.2); color: #c084fc; }

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.68rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.stat-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
}

.stat-month {
    font-size: 0.92rem;
    letter-spacing: -0.01em;
}

/* Upload Section Card */
.upload-section-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.upload-section-header {
    margin-bottom: 20px;
}

.upload-section-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-desc {
    margin: 0;
    font-size: 0.88rem;
    color: #64748b;
}

.section-desc code {
    background: #f1f5f9;
    color: #4f46e5;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85em;
}

.upload-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 24px;
    align-items: stretch;
}

@media (max-width: 900px) {
    .upload-grid {
        grid-template-columns: 1fr;
    }
}

/* Drop Zone */
.drop-zone {
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
    border-radius: 14px;
    padding: 30px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 220px;
    text-align: center;
}

.drop-zone:hover, .drop-zone-active {
    border-color: #6366f1;
    background: #eef2ff;
}

.file-input-hidden {
    display: none;
}

.drop-zone-prompt {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.upload-icon-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 4px;
}

.drop-zone-prompt h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
}

.drop-zone-prompt p {
    margin: 0;
    font-size: 0.85rem;
    color: #64748b;
}

.browse-link {
    color: #4f46e5;
    font-weight: 600;
    font-size: 0.85rem;
    margin-top: 4px;
}

/* File selected badge */
.drop-zone-selected {
    width: 100%;
}

.file-badge {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #ffffff;
    border: 1px solid #c7d2fe;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
}

.file-badge .pi-file {
    color: #4f46e5;
}

.file-meta {
    flex: 1;
    text-align: left;
    display: flex;
    flex-direction: column;
}

.file-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e293b;
    word-break: break-all;
}

.file-size {
    font-size: 0.8rem;
    color: #64748b;
}

.btn-clear-file {
    background: #fee2e2;
    color: #ef4444;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.btn-clear-file:hover {
    background: #fca5a5;
}

/* Upload Controls */
.upload-controls-panel {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-control {
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.92rem;
    outline: none;
    transition: border-color 0.2s;
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.form-help {
    font-size: 0.75rem;
    color: #64748b;
}

.upload-info-box {
    background: #f1f5f9;
    border-radius: 10px;
    padding: 12px 16px;
}

.upload-info-box h4 {
    margin: 0 0 6px 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 6px;
}

.upload-info-box p {
    margin: 0 0 8px 0;
    font-size: 0.78rem;
    color: #64748b;
}

.tags-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.header-tag {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
}

.btn-upload {
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 700;
}

/* Progress bar */
.progress-bar-container {
    background: #e2e8f0;
    border-radius: 9999px;
    height: 18px;
    position: relative;
    overflow: hidden;
    margin-top: 8px;
}

.progress-bar-fill {
    background: linear-gradient(90deg, #6366f1, #4f46e5);
    height: 100%;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    width: 100%;
    text-align: center;
    font-size: 0.7rem;
    font-weight: 700;
    color: #1e293b;
    top: 50%;
    transform: translateY(-50%);
}

/* Alerts */
.alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 10px;
    margin-top: 16px;
    font-size: 0.88rem;
}

.alert-icon {
    font-size: 1.25rem;
    margin-top: 2px;
}

.alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert p {
    margin: 2px 0 0 0;
    font-size: 0.82rem;
}

/* Data Card */
.data-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

/* Filter Toolbar */
.filter-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 280px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-input {
    width: 100%;
    padding: 10px 36px 10px 40px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.2s;
}

.search-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.btn-clear-search {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
}

.filter-selects {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-select {
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.86rem;
    background: #ffffff;
    color: #334155;
    outline: none;
    cursor: pointer;
    font-weight: 500;
}

.filter-select:focus {
    border-color: #6366f1;
}

/* Table Meta Bar */
.table-meta-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 4px;
    font-size: 0.82rem;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 8px;
}

.filter-indicator {
    background: #e0e7ff;
    color: #4338ca;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Employees Table */
.table-responsive {
    overflow-x: auto;
}

.employees-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    text-align: left;
}

.employees-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    padding: 12px 14px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
    user-select: none;
}

.employees-table th i {
    margin-left: 4px;
    font-size: 0.75rem;
}

.employees-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}

.employee-row:hover {
    background: #f8fafc;
}

.col-sr {
    color: #94a3b8;
    font-size: 0.8rem;
    font-weight: 600;
    width: 60px;
}

.emp-code-badge {
    font-family: monospace;
    font-size: 0.85rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #1e293b;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.employee-name-text {
    font-weight: 700;
    color: #0f172a;
}

.location-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 600;
}

.designation-text {
    color: #334155;
    font-size: 0.82rem;
    font-weight: 500;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.badge-contract { background: #dbeafe; color: #1e40af; }
.badge-permanent { background: #dcfce7; color: #166534; }
.badge-outsource { background: #f3e8ff; color: #6b21a8; }
.badge-active { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.72rem; }

.badge-male { background: #f1f5f9; color: #334155; }
.badge-female { background: #fce7f3; color: #9d174d; }

/* Empty state */
.empty-state {
    padding: 60px 20px;
}

.empty-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 12px auto;
}

/* Pagination */
.pagination-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 18px;
    margin-top: 10px;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 12px;
}

.pagination-info {
    font-size: 0.84rem;
    color: #64748b;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.page-btn {
    padding: 6px 12px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}

.page-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #94a3b8;
}

.page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.current-page-indicator {
    padding: 6px 12px;
    background: #4f46e5;
    color: #ffffff;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 0.86rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #4338ca;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.12);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

.btn-outline {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
}

.btn-outline:hover {
    background: #f8fafc;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

.cursor-pointer {
    cursor: pointer;
}

/* Animations */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}
</style>
