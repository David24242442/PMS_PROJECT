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

// Clean Re-Ingest All 5,920 Employees (Purges corrupted records and strictly loads columns)
const cleanReingesting = ref(false);
const triggerCleanReingest = async () => {
    if (!confirm('This will wipe any old/corrupted records in Monthly_Employees and cleanly load all 5,920 employees with strict relative column indexing (Emp ID strictly from Column B) from AUGUST 2026 PAYROLL DATA.xlsx on Server 20. Proceed?')) {
        return;
    }
    cleanReingesting.value = true;
    uploadSuccessMessage.value = '';
    uploadErrorMessage.value = '';
    try {
        const res = await axios.get('/pms/clean-reingest', {
            params: { month_year: uploadMonthYear.value || 'AUGUST 2026' }
        });
        if (res.data && res.data.status === 'success') {
            uploadSuccessMessage.value = res.data.message;
            await fetchEmployees(1);
            await fetchStats();
        } else {
            uploadErrorMessage.value = res.data.message || 'Clean re-ingest failed.';
        }
    } catch (err) {
        console.error('Clean reingest error:', err);
        uploadErrorMessage.value = err.response?.data?.message || 'Server error during clean re-ingest. Make sure AUGUST 2026 PAYROLL DATA.xlsx is placed on the server.';
    } finally {
        cleanReingesting.value = false;
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

// ── Reconciliation with Onboarding State & Methods ──
const showReconciliationModal = ref(false);
const reconcileLoading = ref(false);
const reconcileExporting = ref(false);
const reconcileMonthYear = ref('AUGUST 2026');
const reconcileStatusFilter = ref('all');
const reconcileSearch = ref('');
const reconcileLocation = ref('');
const reconcilePage = ref(1);
const reconcilePerPage = ref(50);
const reconcileStats = ref({
    total_payroll: 0,
    active: 0,
    not_onboarded: 0,
    resigned: 0,
    absconded: 0,
    terminated: 0,
    dismissed: 0,
    deceased: 0,
    stop: 0,
    unknown: 0,
    total_discrepancies: 0
});
const reconcileEmployees = ref([]);
const reconcilePagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
    from: 0,
    to: 0
});
const reconcileLocations = ref([]);
const reconcileMonths = ref([]);

const openReconciliationModal = () => {
    showReconciliationModal.value = true;
    if (uploadMonthYear.value) {
        reconcileMonthYear.value = uploadMonthYear.value;
    }
    fetchReconciliation(1);
};

const closeReconciliationModal = () => {
    showReconciliationModal.value = false;
};

const fetchReconciliation = async (page = 1) => {
    reconcileLoading.value = true;
    reconcilePage.value = page;
    try {
        const params = {
            page: page,
            per_page: reconcilePerPage.value,
            month_year: reconcileMonthYear.value,
            status_filter: reconcileStatusFilter.value,
            search: reconcileSearch.value,
            location: reconcileLocation.value
        };

        const res = await axios.get('/pms/monthly-employees/reconcile', { params });
        if (res.data && res.data.status === 'success') {
            reconcileStats.value = res.data.stats || reconcileStats.value;
            const dataObj = res.data.data;
            reconcileEmployees.value = dataObj.data || [];
            reconcilePagination.value = {
                current_page: dataObj.current_page || 1,
                last_page: dataObj.last_page || 1,
                per_page: dataObj.per_page || 50,
                total: dataObj.total || 0,
                from: dataObj.from || 0,
                to: dataObj.to || 0
            };
            if (res.data.filters) {
                if (res.data.filters.locations) reconcileLocations.value = res.data.filters.locations;
                if (res.data.filters.months) reconcileMonths.value = res.data.filters.months;
            }
        }
    } catch (err) {
        console.error('Failed to fetch reconciliation data:', err);
    } finally {
        reconcileLoading.value = false;
    }
};

let reconcileSearchTimer = null;
const onReconcileSearchInput = () => {
    clearTimeout(reconcileSearchTimer);
    reconcileSearchTimer = setTimeout(() => {
        fetchReconciliation(1);
    }, 350);
};

const setReconcileStatusFilter = (status) => {
    reconcileStatusFilter.value = status;
    fetchReconciliation(1);
};

const exportReconciliation = () => {
    reconcileExporting.value = true;
    const url = `${axios.defaults.baseURL}/pms/monthly-employees/reconcile-export?month_year=${encodeURIComponent(reconcileMonthYear.value)}&status_filter=${encodeURIComponent(reconcileStatusFilter.value)}&search=${encodeURIComponent(reconcileSearch.value)}&location=${encodeURIComponent(reconcileLocation.value)}`;
    window.open(url, '_blank');
    setTimeout(() => {
        reconcileExporting.value = false;
    }, 1500);
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
                    <button class="btn btn-reconcile" @click="openReconciliationModal" title="Compare Payroll Upload against Onboarding Records">
                        <i class="pi pi-arrows-h"></i> Compare with Onboarding
                    </button>
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
                                :disabled="!selectedFile || uploading || quickSyncing || cleanReingesting" 
                                @click="submitUpload"
                            >
                                <i class="pi" :class="uploading ? 'pi-spin pi-spinner' : 'pi-check-circle'"></i>
                                {{ uploading ? 'Ingesting Employees...' : 'Upload & Sync to PMS' }}
                            </button>
                            <button 
                                type="button"
                                class="btn btn-secondary btn-block mt-2" 
                                :disabled="uploading || quickSyncing || cleanReingesting" 
                                @click="triggerLocalSync"
                                title="Ingest directly from AUGUST 2026 PAYROLL DATA.xlsx placed on Server 20"
                            >
                                <i class="pi" :class="quickSyncing ? 'pi-spin pi-spinner' : 'pi-bolt'"></i>
                                {{ quickSyncing ? 'Ingesting Server Excel File...' : 'One-Click Ingest from Server Excel' }}
                            </button>
                            <button 
                                type="button"
                                class="btn btn-block mt-2" 
                                :disabled="uploading || quickSyncing || cleanReingesting" 
                                @click="triggerCleanReingest"
                                title="Purge corrupted rows and freshly load all 5,920 employees with strict relative column indexing"
                                style="background: #e11d48; color: #fff; font-weight: 700; border: none; padding: 9px 14px; border-radius: 8px; font-size: 0.85rem;"
                            >
                                <i class="pi" :class="cleanReingesting ? 'pi-spin pi-spinner' : 'pi-sync'"></i>
                                {{ cleanReingesting ? 'Wiping & Ingesting 5,920 Employees...' : 'Clean Reset & Ingest All 5,920 Employees' }}
                            </button>
                            <button 
                                type="button"
                                class="btn btn-block mt-2 btn-reconcile-card"
                                :disabled="uploading || quickSyncing || cleanReingesting"
                                @click="openReconciliationModal"
                                title="Cross-reference all payroll employees against Onboarding status"
                            >
                                <i class="pi pi-shield-check"></i> Audit & Compare Payroll vs Onboarding Status
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
                            <th>Onboarding Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && employees.length === 0">
                            <td colspan="10" class="text-center py-5">
                                <i class="pi pi-spin pi-spinner text-2xl text-indigo"></i>
                                <p class="mt-2 text-muted">Loading employee records...</p>
                            </td>
                        </tr>

                        <tr v-else-if="employees.length === 0">
                            <td colspan="10" class="text-center py-5 empty-state">
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
                            <td class="col-onboarding-status">
                                <span 
                                    v-if="emp.onboarding_status" 
                                    class="badge"
                                    :class="{
                                        'badge-active': emp.onboarding_status.code === 'ACTIVE',
                                        'badge-not-onboarded': emp.onboarding_status.code === 'NOT_ONBOARDED',
                                        'badge-resigned': emp.onboarding_status.code === 'RESIGNED',
                                        'badge-terminated': emp.onboarding_status.code === 'TERMINATED',
                                        'badge-absconded': emp.onboarding_status.code === 'ABSCONDED',
                                        'badge-dismissed': emp.onboarding_status.code === 'DISMISSED',
                                        'badge-deceased': emp.onboarding_status.code === 'DECEASED',
                                        'badge-stop': emp.onboarding_status.code === 'STOP',
                                    }"
                                    :title="emp.onboarding_status.audit_flag"
                                >
                                    <i class="pi" :class="emp.onboarding_status.code === 'ACTIVE' ? 'pi-check-circle' : (emp.onboarding_status.code === 'NOT_ONBOARDED' ? 'pi-exclamation-circle' : 'pi-exclamation-triangle')"></i>
                                    {{ emp.onboarding_status.label }}
                                </span>
                                <span v-else class="text-muted text-xs">—</span>
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

        <!-- ── Reconciliation & Audit Modal ── -->
        <transition name="fade">
            <div v-if="showReconciliationModal" class="reconcile-modal-overlay" @click.self="closeReconciliationModal">
                <div class="reconcile-modal-container">
                    <!-- Modal Header -->
                    <div class="reconcile-modal-header">
                        <div class="reconcile-title-group">
                            <div class="reconcile-badge">
                                <i class="pi pi-shield-check"></i> HR AUDIT & RECONCILIATION
                            </div>
                            <h2>Payroll vs. Onboarding Reconciliation</h2>
                            <p class="reconcile-subtitle">
                                Cross-referencing authoritative <strong>Payroll Uploads</strong> (Source of Truth for Employees) against <strong>Onboarding Dossiers</strong> (Source of Truth for Status).
                            </p>
                        </div>
                        <div class="reconcile-header-actions">
                            <div class="reconcile-batch-select-wrap">
                                <label><i class="pi pi-calendar"></i> Batch:</label>
                                <select v-model="reconcileMonthYear" class="reconcile-batch-select" @change="fetchReconciliation(1)">
                                    <option v-for="m in reconcileMonths" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </div>

                            <button 
                                type="button" 
                                class="btn btn-secondary btn-export-audit"
                                :disabled="reconcileExporting || reconcileLoading"
                                @click="exportReconciliation"
                            >
                                <i class="pi" :class="reconcileExporting ? 'pi-spin pi-spinner' : 'pi-download'"></i>
                                Export Audit CSV
                            </button>

                            <button type="button" class="btn-close-modal" @click="closeReconciliationModal" title="Close modal">
                                &times;
                            </button>
                        </div>
                    </div>

                    <!-- Executive KPI Banner -->
                    <div class="reconcile-stats-row">
                        <div class="reconcile-stat-card total-card" @click="setReconcileStatusFilter('all')" :class="{ 'active-card': reconcileStatusFilter === 'all' }">
                            <div class="stat-top">
                                <span class="stat-num">{{ formatNumber(reconcileStats.total_payroll) }}</span>
                                <i class="pi pi-users stat-icon"></i>
                            </div>
                            <span class="stat-name">Total on Payroll</span>
                            <span class="stat-sub">Source of Truth Records</span>
                        </div>

                        <div class="reconcile-stat-card active-card-stat" @click="setReconcileStatusFilter('active')" :class="{ 'active-card': reconcileStatusFilter === 'active' }">
                            <div class="stat-top">
                                <span class="stat-num text-emerald">{{ formatNumber(reconcileStats.active) }}</span>
                                <i class="pi pi-check-circle stat-icon text-emerald"></i>
                            </div>
                            <span class="stat-name">Active & Verified</span>
                            <span class="stat-sub">Active in Onboarding</span>
                        </div>

                        <div class="reconcile-stat-card warning-card" @click="setReconcileStatusFilter('not_onboarded')" :class="{ 'active-card': reconcileStatusFilter === 'not_onboarded' }">
                            <div class="stat-top">
                                <span class="stat-num text-amber">{{ formatNumber(reconcileStats.not_onboarded) }}</span>
                                <i class="pi pi-exclamation-circle stat-icon text-amber"></i>
                            </div>
                            <span class="stat-name">Not Onboarded</span>
                            <span class="stat-sub">On Payroll, Missing in HR</span>
                        </div>

                        <div class="reconcile-stat-card danger-card" @click="setReconcileStatusFilter('discrepancies')" :class="{ 'active-card': reconcileStatusFilter === 'discrepancies' }">
                            <div class="stat-top">
                                <span class="stat-num text-rose">{{ formatNumber(reconcileStats.total_discrepancies) }}</span>
                                <i class="pi pi-shield stat-icon text-rose"></i>
                            </div>
                            <span class="stat-name">Audit Discrepancies</span>
                            <span class="stat-sub">Inactive or Unregistered</span>
                        </div>
                    </div>

                    <!-- Status Tabs Row -->
                    <div class="reconcile-tabs-row">
                        <button 
                            type="button" 
                            class="reconcile-tab" 
                            :class="{ 'active': reconcileStatusFilter === 'all' }"
                            @click="setReconcileStatusFilter('all')"
                        >
                            All Payroll ({{ formatNumber(reconcileStats.total_payroll) }})
                        </button>
                        <button 
                            type="button" 
                            class="reconcile-tab tab-not-onboarded" 
                            :class="{ 'active': reconcileStatusFilter === 'not_onboarded' }"
                            @click="setReconcileStatusFilter('not_onboarded')"
                        >
                            <i class="pi pi-exclamation-circle"></i>
                            Not Onboarded ({{ formatNumber(reconcileStats.not_onboarded) }})
                        </button>
                        <button 
                            type="button" 
                            class="reconcile-tab tab-active" 
                            :class="{ 'active': reconcileStatusFilter === 'active' }"
                            @click="setReconcileStatusFilter('active')"
                        >
                            <i class="pi pi-check-circle"></i>
                            Active ({{ formatNumber(reconcileStats.active) }})
                        </button>
                        <button 
                            v-if="reconcileStats.resigned > 0"
                            type="button" 
                            class="reconcile-tab tab-resigned" 
                            :class="{ 'active': reconcileStatusFilter === 'resigned' }"
                            @click="setReconcileStatusFilter('resigned')"
                        >
                            <i class="pi pi-sign-out"></i>
                            Resigned on Payroll ({{ formatNumber(reconcileStats.resigned) }})
                        </button>
                        <button 
                            v-if="reconcileStats.terminated > 0"
                            type="button" 
                            class="reconcile-tab tab-terminated" 
                            :class="{ 'active': reconcileStatusFilter === 'terminated' }"
                            @click="setReconcileStatusFilter('terminated')"
                        >
                            <i class="pi pi-ban"></i>
                            Terminated on Payroll ({{ formatNumber(reconcileStats.terminated) }})
                        </button>
                        <button 
                            v-if="reconcileStats.absconded > 0"
                            type="button" 
                            class="reconcile-tab tab-absconded" 
                            :class="{ 'active': reconcileStatusFilter === 'absconded' }"
                            @click="setReconcileStatusFilter('absconded')"
                        >
                            <i class="pi pi-directions"></i>
                            Absconded ({{ formatNumber(reconcileStats.absconded) }})
                        </button>
                        <button 
                            v-if="reconcileStats.dismissed > 0"
                            type="button" 
                            class="reconcile-tab tab-dismissed" 
                            :class="{ 'active': reconcileStatusFilter === 'dismissed' }"
                            @click="setReconcileStatusFilter('dismissed')"
                        >
                            <i class="pi pi-times-circle"></i>
                            Dismissed ({{ formatNumber(reconcileStats.dismissed) }})
                        </button>
                        <button 
                            v-if="reconcileStats.deceased > 0"
                            type="button" 
                            class="reconcile-tab tab-deceased" 
                            :class="{ 'active': reconcileStatusFilter === 'deceased' }"
                            @click="setReconcileStatusFilter('deceased')"
                        >
                            <i class="pi pi-heart"></i>
                            Deceased ({{ formatNumber(reconcileStats.deceased) }})
                        </button>
                        <button 
                            v-if="reconcileStats.stop > 0"
                            type="button" 
                            class="reconcile-tab tab-stop" 
                            :class="{ 'active': reconcileStatusFilter === 'stop' }"
                            @click="setReconcileStatusFilter('stop')"
                        >
                            <i class="pi pi-pause"></i>
                            Stop / Hold ({{ formatNumber(reconcileStats.stop) }})
                        </button>
                    </div>

                    <!-- Search & Filters Toolbar -->
                    <div class="reconcile-toolbar">
                        <div class="reconcile-search-box">
                            <i class="pi pi-search"></i>
                            <input 
                                type="text" 
                                v-model="reconcileSearch" 
                                placeholder="Search by Emp ID, Name, Location, Designation..." 
                                class="reconcile-search-input"
                                @input="onReconcileSearchInput"
                            />
                            <button v-if="reconcileSearch" class="btn-clear-search" @click="reconcileSearch = ''; fetchReconciliation(1)">
                                <i class="pi pi-times"></i>
                            </button>
                        </div>

                        <div class="reconcile-filter-items">
                            <select v-model="reconcileLocation" class="reconcile-select" @change="fetchReconciliation(1)">
                                <option value="">All Locations ({{ reconcileLocations.length }})</option>
                                <option v-for="loc in reconcileLocations" :key="loc" :value="loc">{{ loc }}</option>
                            </select>

                            <select v-model="reconcilePerPage" class="reconcile-select" @change="fetchReconciliation(1)">
                                <option :value="25">25 per page</option>
                                <option :value="50">50 per page</option>
                                <option :value="100">100 per page</option>
                            </select>

                            <button 
                                type="button" 
                                class="btn btn-outline" 
                                @click="reconcileSearch = ''; reconcileLocation = ''; reconcileStatusFilter = 'all'; fetchReconciliation(1)"
                            >
                                <i class="pi pi-filter-slash"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Reconciliation Table -->
                    <div class="reconcile-table-wrapper">
                        <table class="reconcile-table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Emp ID</th>
                                    <th>Payroll Employee Name</th>
                                    <th>Location / Branch</th>
                                    <th>Designation</th>
                                    <th>Onboarding Status</th>
                                    <th>Audit Recommendation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="reconcileLoading">
                                    <td colspan="8" class="text-center py-5">
                                        <i class="pi pi-spin pi-spinner text-2xl text-indigo"></i>
                                        <p class="mt-2 text-muted">Auditing and cross-referencing records...</p>
                                    </td>
                                </tr>

                                <tr v-else-if="reconcileEmployees.length === 0">
                                    <td colspan="8" class="text-center py-5">
                                        <i class="pi pi-check-circle text-3xl text-emerald mb-2"></i>
                                        <h3>No Records in This View</h3>
                                        <p class="text-muted">No employees match the selected status or search filter.</p>
                                    </td>
                                </tr>

                                <tr v-for="emp in reconcileEmployees" :key="emp.monthly_id" class="reconcile-row" :class="{ 'row-discrepancy': emp.onboarding_status.is_discrepancy }">
                                    <td class="col-sr">{{ emp.sr_no || '—' }}</td>
                                    <td class="col-empid">
                                        <span class="emp-code-badge">#{{ emp.emp_id }}</span>
                                    </td>
                                    <td class="col-name">
                                        <div class="name-box">
                                            <span class="payroll-name">{{ emp.employee_name }}</span>
                                            <span v-if="emp.onboarding_name && emp.onboarding_name !== emp.employee_name" class="onboarding-subname">
                                                HR: {{ emp.onboarding_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="col-location">
                                        <span class="location-badge">
                                            <i class="pi pi-map-marker text-xs"></i> {{ emp.location || '—' }}
                                        </span>
                                    </td>
                                    <td class="col-designation">
                                        <span class="designation-text">{{ emp.designation || '—' }}</span>
                                    </td>
                                    <td class="col-onboarding-status">
                                        <span 
                                            class="badge" 
                                            :class="{
                                                'badge-active': emp.onboarding_status.code === 'ACTIVE',
                                                'badge-not-onboarded': emp.onboarding_status.code === 'NOT_ONBOARDED',
                                                'badge-resigned': emp.onboarding_status.code === 'RESIGNED',
                                                'badge-terminated': emp.onboarding_status.code === 'TERMINATED',
                                                'badge-absconded': emp.onboarding_status.code === 'ABSCONDED',
                                                'badge-dismissed': emp.onboarding_status.code === 'DISMISSED',
                                                'badge-deceased': emp.onboarding_status.code === 'DECEASED',
                                                'badge-stop': emp.onboarding_status.code === 'STOP',
                                            }"
                                        >
                                            <i class="pi" :class="emp.onboarding_status.code === 'ACTIVE' ? 'pi-check-circle' : (emp.onboarding_status.code === 'NOT_ONBOARDED' ? 'pi-exclamation-circle' : 'pi-exclamation-triangle')"></i>
                                            {{ emp.onboarding_status.label }}
                                        </span>
                                    </td>
                                    <td class="col-audit-flag">
                                        <span 
                                            class="audit-pill"
                                            :class="{
                                                'audit-compliant': !emp.onboarding_status.is_discrepancy,
                                                'audit-warning': emp.onboarding_status.code === 'NOT_ONBOARDED',
                                                'audit-danger': emp.onboarding_status.is_discrepancy && emp.onboarding_status.code !== 'NOT_ONBOARDED'
                                            }"
                                        >
                                            {{ emp.onboarding_status.audit_flag }}
                                        </span>
                                    </td>
                                    <td class="col-actions">
                                        <a 
                                            v-if="emp.onboarding_id" 
                                            :href="'/employee/' + emp.onboarding_id" 
                                            target="_blank" 
                                            class="btn-view-profile"
                                            title="Open full onboarding dossier"
                                        >
                                            <i class="pi pi-external-link"></i> Profile
                                        </a>
                                        <span v-else class="text-xs text-muted font-italic">
                                            Not Registered
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Pagination Bar -->
                    <div class="reconcile-pagination-bar">
                        <div class="reconcile-page-info">
                            Showing <strong>{{ reconcilePagination.from || 0 }}</strong> to <strong>{{ reconcilePagination.to || 0 }}</strong> of <strong>{{ formatNumber(reconcilePagination.total) }}</strong> records
                        </div>
                        <div class="reconcile-page-controls">
                            <button 
                                class="page-btn" 
                                :disabled="reconcilePagination.current_page === 1 || reconcileLoading"
                                @click="fetchReconciliation(reconcilePagination.current_page - 1)"
                            >
                                <i class="pi pi-chevron-left"></i> Prev
                            </button>
                            <span class="current-page-indicator">
                                {{ reconcilePagination.current_page }} / {{ reconcilePagination.last_page || 1 }}
                            </span>
                            <button 
                                class="page-btn" 
                                :disabled="reconcilePagination.current_page === reconcilePagination.last_page || reconcileLoading"
                                @click="fetchReconciliation(reconcilePagination.current_page + 1)"
                            >
                                Next <i class="pi pi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        </div>
    </div>
</template>

<style scoped>
.manage-employees-container {
    padding: 0 4px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Header Card */
.header-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 10px;
    padding: 10px 16px;
    color: white;
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.2);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(129, 140, 248, 0.3);
    color: #a5b4fc;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 1px 6px;
    border-radius: 9999px;
    margin-bottom: 2px;
}

.title-section h1 {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
    color: #ffffff;
}

.subtitle {
    margin: 2px 0 0 0;
    font-size: 0.78rem;
    color: #94a3b8;
    max-width: 680px;
    line-height: 1.25;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.header-actions .btn {
    padding: 5px 11px;
    font-size: 0.78rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 8px;
    margin-top: 2px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 5px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s ease, background 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.09);
}

.stat-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
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
    font-size: 0.62rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.stat-value {
    font-size: 1.0rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
}

.stat-month {
    font-size: 0.82rem;
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

/* ── Reconciliation Feature Styles ── */
.btn-reconcile {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    font-weight: 700;
    padding: 7px 15px;
    border-radius: 8px;
    font-size: 0.8125rem;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-reconcile:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.45);
}

.btn-reconcile-card {
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    color: #ffffff;
    font-weight: 700;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.15s ease;
}
.btn-reconcile-card:hover {
    background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
}

/* Modal Overlay */
.reconcile-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}

.reconcile-modal-container {
    width: 100%;
    max-width: 1280px;
    max-height: 92vh;
    background: var(--surface-card, #ffffff);
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--border-color, #e2e8f0);
}

/* Modal Header */
.reconcile-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    background: #0f172a;
    color: #ffffff;
    border-bottom: 1px solid #1e293b;
    flex-wrap: wrap;
    gap: 1rem;
}
.reconcile-title-group h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0.25rem 0 0.15rem 0;
    color: #ffffff;
}
.reconcile-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    border-radius: 9999px;
    background: rgba(99, 102, 241, 0.2);
    color: #a5b4fc;
    border: 1px solid rgba(165, 180, 252, 0.3);
}
.reconcile-subtitle {
    font-size: 0.8125rem;
    color: #94a3b8;
    margin: 0;
}
.reconcile-subtitle strong {
    color: #e2e8f0;
}
.reconcile-header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.reconcile-batch-select-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.8125rem;
}
.reconcile-batch-select-wrap label {
    color: #cbd5e1;
    font-size: 0.75rem;
}
.reconcile-batch-select {
    background: transparent;
    border: none;
    color: #ffffff;
    font-weight: 600;
    outline: none;
    cursor: pointer;
}
.reconcile-batch-select option {
    background: #0f172a;
    color: #ffffff;
}
.btn-export-audit {
    background: #4f46e5;
    color: #ffffff;
    border: none;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.8125rem;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-export-audit:hover {
    background: #4338ca;
}
.btn-close-modal {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    padding: 0 4px;
}
.btn-close-modal:hover {
    color: #ffffff;
}

/* Executive KPI Banner */
.reconcile-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: var(--surface-ground, #f8fafc);
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}
.reconcile-stat-card {
    background: var(--surface-card, #ffffff);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    border: 1px solid var(--border-color, #e2e8f0);
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
}
.reconcile-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.reconcile-stat-card.active-card {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
}
.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}
.stat-num {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-color, #0f172a);
}
.stat-icon {
    font-size: 1.25rem;
    color: #94a3b8;
}
.stat-name {
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--text-color, #1e293b);
}
.stat-sub {
    font-size: 0.6875rem;
    color: var(--text-secondary, #64748b);
}
.text-emerald { color: #059669 !important; }
.text-amber { color: #d97706 !important; }
.text-rose { color: #e11d48 !important; }

/* Status Tabs Row */
.reconcile-tabs-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem 0.5rem;
    overflow-x: auto;
    border-bottom: 1px solid var(--border-color, #f1f5f9);
}
.reconcile-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 0.775rem;
    font-weight: 600;
    border-radius: 9999px;
    background: transparent;
    border: 1px solid transparent;
    color: var(--text-secondary, #64748b);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.reconcile-tab:hover {
    background: var(--surface-ground, #f1f5f9);
    color: var(--text-color, #0f172a);
}
.reconcile-tab.active {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.reconcile-tab.tab-not-onboarded.active {
    background: #d97706;
}
.reconcile-tab.tab-active.active {
    background: #059669;
}
.reconcile-tab.tab-resigned.active,
.reconcile-tab.tab-terminated.active,
.reconcile-tab.tab-stop.active {
    background: #e11d48;
}

/* Toolbar */
.reconcile-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}
.reconcile-search-box {
    position: relative;
    flex: 1 1 300px;
    max-width: 480px;
}
.reconcile-search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.8125rem;
}
.reconcile-search-input {
    width: 100%;
    padding: 6px 28px 6px 30px;
    font-size: 0.8125rem;
    border-radius: 8px;
    border: 1px solid var(--border-color, #e2e8f0);
    background: var(--surface-ground, #f8fafc);
    color: var(--text-color, #0f172a);
    outline: none;
}
.reconcile-search-input:focus {
    border-color: #4f46e5;
    background: var(--surface-card, #ffffff);
}
.reconcile-filter-items {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.reconcile-select {
    padding: 6px 10px;
    font-size: 0.8125rem;
    border-radius: 8px;
    border: 1px solid var(--border-color, #e2e8f0);
    background: var(--surface-card, #ffffff);
    color: var(--text-color, #0f172a);
    outline: none;
    cursor: pointer;
}

/* Reconcile Table */
.reconcile-table-wrapper {
    flex: 1;
    overflow-y: auto;
    overflow-x: auto;
    max-height: 52vh;
}
.reconcile-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}
.reconcile-table th {
    position: sticky;
    top: 0;
    background: var(--surface-ground, #f8fafc);
    color: var(--text-secondary, #64748b);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.6875rem;
    letter-spacing: 0.05em;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    text-align: left;
    z-index: 10;
}
.reconcile-row {
    border-bottom: 1px solid var(--border-color, #f1f5f9);
    transition: background 0.1s;
}
.reconcile-row:hover {
    background: var(--surface-ground, #f8fafc);
}
.reconcile-row.row-discrepancy {
    background: rgba(254, 242, 242, 0.3);
}
.reconcile-table td {
    padding: 10px 14px;
    vertical-align: middle;
}
.name-box {
    display: flex;
    flex-direction: column;
}
.payroll-name {
    font-weight: 600;
    color: var(--text-color, #0f172a);
}
.onboarding-subname {
    font-size: 0.7rem;
    color: #4f46e5;
}

/* Badges & Pills */
.badge-not-onboarded {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fcd34d !important;
}
.badge-resigned {
    background: #ffedd5 !important;
    color: #c2410c !important;
    border: 1px solid #fdba74 !important;
}
.badge-terminated {
    background: #ffe4e6 !important;
    color: #e11d48 !important;
    border: 1px solid #fda4af !important;
}
.badge-absconded {
    background: #fae8ff !important;
    color: #a21caf !important;
    border: 1px solid #f0abfc !important;
}
.badge-dismissed {
    background: #f3e8ff !important;
    color: #7e22ce !important;
    border: 1px solid #d8b4fe !important;
}
.badge-deceased {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
}
.badge-stop {
    background: #fee2e2 !important;
    color: #dc2626 !important;
    border: 1px solid #fca5a5 !important;
}

.audit-pill {
    display: inline-flex;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.725rem;
    font-weight: 700;
}
.audit-compliant {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.audit-warning {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}
.audit-danger {
    background: #fef2f2;
    color: #e11d48;
    border: 1px solid #fecdd3;
}

.btn-view-profile {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    background: #eef2ff;
    color: #4f46e5;
    font-weight: 600;
    font-size: 0.75rem;
    text-decoration: none;
    border: 1px solid #e0e7ff;
    transition: all 0.15s;
}
.btn-view-profile:hover {
    background: #4f46e5;
    color: #ffffff;
}

/* Pagination Bar */
.reconcile-pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.5rem;
    background: var(--surface-card, #ffffff);
    border-top: 1px solid var(--border-color, #e2e8f0);
}
.reconcile-page-info {
    font-size: 0.8125rem;
    color: var(--text-secondary, #64748b);
}
.reconcile-page-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Dark Mode Overrides for Modal */
:global(body.dark-mode) .reconcile-modal-container {
    background: #1e293b;
    border-color: #334155;
}
:global(body.dark-mode) .reconcile-stats-row {
    background: #0f172a;
    border-color: #334155;
}
:global(body.dark-mode) .reconcile-stat-card {
    background: #1e293b;
    border-color: #334155;
}
:global(body.dark-mode) .reconcile-search-input,
:global(body.dark-mode) .reconcile-select {
    background: #0f172a;
    border-color: #334155;
    color: #ffffff;
}
:global(body.dark-mode) .reconcile-table th {
    background: #0f172a;
    border-color: #334155;
}
:global(body.dark-mode) .reconcile-row:hover {
    background: #0f172a;
}
:global(body.dark-mode) .reconcile-pagination-bar {
    background: #1e293b;
    border-color: #334155;
}
</style>
