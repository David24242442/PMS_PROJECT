<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useUsersStore } from '@/stores/user';
import { useRouter } from 'vue-router';
import { showAlert } from '@/helpers/essential';

const router = useRouter();
const userstore = useUsersStore();
const fileInput = ref(null);
const dragging = ref(false);
const uploading = ref(false);
const uploadComplete = ref(false);
const uploadedData = ref([]);
const headers = ref([]);
const uploadStats = ref(null);
const error = ref(null);

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFileDrop = (e) => {
    dragging.value = false;
    const files = e.dataTransfer.files;
    if (files.length) handleUpload(files[0]);
};

const handleFileSelect = (e) => {
    const files = e.target.files;
    if (files.length) handleUpload(files[0]);
};

const handleUpload = async (file) => {
    if (!file) return;
    
    // Validate type
    const validTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!validTypes.includes(file.type) && !file.name.endsWith('.csv')) {
        error.value = "Invalid file type. Please upload a CSV file.";
        return;
    }

    uploading.value = true;
    error.value = null;
    uploadComplete.value = false;

    const formData = new FormData();
    formData.append('csv_file', file);

    try {
        // Use relative path since base URL is set or handled by proxy/interceptor
        // Typically strict API endpoint: 'http://localhost/PMS/api/upload-csv.php' if not proxied
        // Assuming axios base URL is set or we use relative path pms/api/... or similar
        // Based on previous checks, axios base might be set in main.js
        const response = await axios.post('upload-csv.php', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        const res = response.data;
        if (res.success) {
            uploadedData.value = res.data;
            headers.value = res.headers;
            uploadStats.value = {
                count: res.row_count,
                filename: file.name
            };
            uploadComplete.value = true;
        } else {
            error.value = res.message || "Upload failed.";
        }
    } catch (err) {
        console.error(err);
        // Fallback for demo if API fails (mock data)
        error.value = err.response?.data?.message || "Server error during upload.";
    } finally {
        uploading.value = false;
    }
};

const exportData = (type) => {
    if (!uploadedData.value.length) return;
    showAlert('Feature Unavailable', `Export to ${type} functionality requires external libraries (xlsx/jspdf). Please install them to enable this feature.`, 'info');
};

</script>

<template>
    <div class="upload-container animate-fade-in">
        <div class="page-header mb-xl">
            <h1 class="text-2xl font-bold text-primary">Data Management</h1>
            <p class="text-muted text-sm">Upload employee data via CSV to update the system.</p>
        </div>

        <div class="upload-grid">
            <!-- Upload Zone -->
            <div class="card upload-card" :class="{ 'upload-success': uploadComplete }">
                <div 
                    class="drop-zone"
                    :class="{ 'dragging': dragging, 'uploading': uploading }"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="handleFileDrop"
                    @click="triggerFileInput"
                >
                    <input type="file" ref="fileInput" @change="handleFileSelect" accept=".csv" class="hidden" />
                    
                    <div v-if="uploading" class="upload-status">
                        <i class="pi pi-spin pi-spinner text-4xl text-primary mb-md"></i>
                        <p class="font-bold">Processing File...</p>
                        <span class="text-xs text-muted">Please wait while we parse the data.</span>
                    </div>

                    <div v-else-if="uploadComplete" class="upload-status">
                        <div class="icon-circle bg-success-light text-success mb-md animate-bounce-in">
                            <i class="pi pi-check text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-lg mb-xs">Upload Complete!</h3>
                        <p class="text-sm text-muted mb-md">{{ uploadStats?.filename }}</p>
                        <div class="stats-badge">
                            <i class="pi pi-users mr-xs"></i>
                            <span>{{ uploadStats?.count }} Records Parsed</span>
                        </div>
                        <button class="btn btn-sm btn-outline mt-lg" @click.stop="uploadComplete = false; uploadedData = []">Upload Another</button>
                    </div>

                    <div v-else class="upload-status">
                        <div class="icon-circle bg-primary-light text-primary mb-md">
                            <i class="pi pi-cloud-upload text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-lg mb-xs">Upload CSV File</h3>
                        <p class="text-sm text-muted mb-lg">Drag & drop your file here or click to browse</p>
                        <div class="supported-formats">
                            <span class="badge badge-light">.CSV</span>
                            <span class="badge badge-light">.XLSX</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview / Instructions -->
            <div class="card preview-card" v-if="!uploadedData || uploadedData.length === 0">
                <div class="empty-state">
                    <i class="pi pi-table text-4xl text-muted opacity-20 mb-md"></i>
                    <h3 class="font-bold text-muted">No Data to Display</h3>
                    <p class="text-sm text-muted mt-xs text-center max-w-xs" v-if="uploadComplete">
                        The uploaded file processed successfully but returned no readable rows. Please check if the file is empty or formatted correctly.
                    </p>
                    <p class="text-sm text-muted mt-xs text-center max-w-xs" v-else>
                        Uploaded data preview will appear here. Ensure your CSV follows the standard employee template.
                    </p>
                    <button class="btn btn-text text-primary mt-md"><i class="pi pi-download mr-xs"></i> Download Template</button>
                </div>
            </div>

            <div class="card preview-card" v-else>
                <div class="card-header flex justify-between items-center mb-md">
                    <div class="flex items-center gap-sm">
                        <h3 class="font-bold">Data Preview</h3>
                        <span class="badge badge-primary">{{ uploadedData.length }} Rows</span>
                    </div>
                    <div class="flex gap-sm">
                        <button class="btn btn-sm btn-outline" @click="exportData('PDF')">
                            <i class="pi pi-file-pdf mr-xs"></i> PDF
                        </button>
                        <button class="btn btn-sm btn-primary" @click="exportData('Excel')">
                            <i class="pi pi-file-excel mr-xs"></i> Excel
                        </button>
                    </div>
                </div>
                
                <div class="table-container custom-scrollbar">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th v-for="(header, index) in headers" :key="index">{{ header }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, rowIndex) in uploadedData" :key="rowIndex">
                                <td v-for="(header, colIndex) in headers" :key="colIndex">
                                    {{ row[header] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div v-if="error" class="toast-error animate-slide-up">
            <i class="pi pi-exclamation-triangle mr-sm"></i>
            {{ error }}
            <button @click="error = null" class="ml-auto"><i class="pi pi-times"></i></button>
        </div>
    </div>
</template>

<style scoped>
.upload-container {
    height: 100%;
}

.upload-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: var(--spacing-xl);
    height: calc(100vh - 180px);
}

.upload-card {
    display: flex;
    flex-direction: column;
}

.drop-zone {
    flex: 1;
    border: 2px dashed var(--color-light);
    border-radius: var(--radius-lg);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--color-lighter);
    transition: all 0.3s ease;
    cursor: pointer;
    min-height: 300px;
}

.drop-zone:hover, .drop-zone.dragging {
    border-color: var(--color-primary);
    background: rgba(124, 58, 237, 0.05);
}

.drop-zone.uploading {
    cursor: wait;
    border-style: solid;
}

.upload-status {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-primary-light { background: rgba(124, 58, 237, 0.1); }
.bg-success-light { background: rgba(34, 197, 94, 0.1); }
.text-success { color: var(--color-success); }

.stats-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 16px;
    background: white;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-primary);
    box-shadow: var(--shadow-sm);
}

.preview-card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0.7;
}

.table-container {
    flex: 1;
    overflow: auto;
    border: 1px solid var(--color-light);
    border-radius: var(--radius-md);
}

.data-table th {
    background: var(--color-lighter);
    position: sticky;
    top: 0;
    z-index: 10;
}

.toast-error {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
    padding: 1rem;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    box-shadow: var(--shadow-lg);
    z-index: 1000;
    font-weight: 500;
}

@media (max-width: 1024px) {
    .upload-grid {
        grid-template-columns: 1fr;
        height: auto;
        gap: var(--spacing-lg);
    }
}
</style>
