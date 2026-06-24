<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useUsersStore } from '@/stores/user';
import { showAlert } from '@/helpers/essential';

const userstore = useUsersStore();
const { loguser } = userstore;

const currentYear = ref(new Date().getFullYear());
const years = range(currentYear.value, currentYear.value - 5);
const loading = ref(true);
const saving = ref(false);

function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

// Review Sections Data
const reviewData = ref({
    empCode: '',
    name: '',
    division: '',
    position: '',
    date: new Date().toISOString().split('T')[0],
    // Section A: Goal Achievement vs. Target (Table)
    goalAchievement: [{ target: '', achievement: '' }],
    // Section B: Additional Responsibility/Initiatives Taken
    initiatives: [''],
    // Section C: Next Step for Business
    nextSteps: [''],
    // Section D: Area of Improvement
    improvements: [''],
    // Section E: Feedback from Line Manager
    managerFeedback: ['']
});

// Dynamic Field Helpers
const addItem = (key) => {
    if (key === 'goalAchievement') {
        reviewData.value[key].push({ target: '', achievement: '' });
    } else {
        reviewData.value[key].push('');
    }
};

const removeItem = (key, index) => {
    if (reviewData.value[key].length > 1) {
        reviewData.value[key].splice(index, 1);
    } else {
        showAlert('Info', 'At least one item is required.', 'info');
    }
};

// Sections configuration with colors matching reference
const sections = [
    { 
        key: 'goalAchievement', 
        letter: 'A', 
        title: 'Goal Achievement vs. Target',
        headerClass: 'bg-blue-600 text-white',
        iconClass: 'text-blue-600 bg-white',
        borderColor: 'border-blue-600'
    },
    { 
        key: 'initiatives', 
        letter: 'B', 
        title: 'Additional Responsibility/Initiatives Taken',
        headerClass: 'bg-orange-500 text-white',
        iconClass: 'text-orange-600 bg-white',
        borderColor: 'border-orange-500'
    },
    { 
        key: 'nextSteps', 
        letter: 'C', 
        title: 'Next Step for Business',
        headerClass: 'bg-cyan-600 text-white',
        iconClass: 'text-cyan-600 bg-white',
        borderColor: 'border-cyan-600'
    },
    { 
        key: 'improvements', 
        letter: 'D', 
        title: 'Area of Improvement',
        headerClass: 'bg-yellow-500 text-white',
        iconClass: 'text-yellow-600 bg-white',
        borderColor: 'border-yellow-500'
    },
    { 
        key: 'managerFeedback', 
        letter: 'E', 
        title: 'Feedback from Line Manager',
        headerClass: 'bg-emerald-600 text-white',
        iconClass: 'text-emerald-600 bg-white',
        borderColor: 'border-emerald-600'
    }
];

const fetchReview = async () => {
    loading.value = true;
    try {
        // Pre-fill employee info from logged in user
        reviewData.value.empCode = loguser?.employee_code || '';
        reviewData.value.name = loguser?.name || '';
        reviewData.value.division = loguser?.department || '';
        reviewData.value.position = loguser?.job_title || '';
        
        const response = await axios.get('pms/reviews', { 
            params: { year: currentYear.value, user_id: loguser.id } 
        });
        if (response.data.status === 'success' && response.data.data) {
            reviewData.value = { ...reviewData.value, ...response.data.data };
        }
    } catch (error) {
        console.log('No existing review found');
    } finally {
        loading.value = false;
    }
};

const saveReview = async (submit = false) => {
    saving.value = true;
    try {
        const payload = {
            ...reviewData.value,
            status: submit ? 'submitted' : 'draft',
            year: currentYear.value,
            user_id: loguser.id
        };
        const response = await axios.post('pms/reviews', payload);
        if (response.data.status === 'success') {
            showAlert('Success', submit ? 'Review submitted successfully.' : 'Draft saved.', 'success');
        }
    } catch (error) {
        console.error('Error saving review:', error);
        showAlert('Error', 'Failed to save review.', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchReview();
});
</script>

<template>
    <div class="h-full">
        <!-- Page Header -->
        <div class="bg-indigo-900 border-b border-indigo-800 sticky top-0 z-10 mb-8 shadow-md">
            <div class="px-8 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center shadow-lg border border-white/20">
                            <i class="pi pi-file-check text-2xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white tracking-tight">End-Of-Year Review</h1>
                            <p class="text-indigo-200 text-sm font-medium">Executive Summary & Performance Evaluation</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 bg-indigo-800/50 rounded-lg border border-indigo-700/50 text-xs font-bold text-indigo-100 uppercase tracking-wide">
                            FY {{ currentYear }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Info Header -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8 overflow-hidden mx-8">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="pi pi-id-card text-gray-400"></i> Employee Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Emp. Code</label>
                        <input v-model="reviewData.empCode" class="form-input font-bold" placeholder="Code" />
                    </div>
                    <div class="space-y-1.5 md:col-span-1">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Name</label>
                        <input v-model="reviewData.name" class="form-input font-bold" placeholder="Full Name" />
                    </div>
                    <div class="space-y-1.5 md:col-span-1">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Division</label>
                        <input v-model="reviewData.division" class="form-input font-bold" placeholder="Division" />
                    </div>
                    <div class="space-y-1.5 md:col-span-1">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Position</label>
                        <input v-model="reviewData.position" class="form-input font-bold" placeholder="Position" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Date</label>
                        <input type="date" v-model="reviewData.date" class="form-input font-medium" />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
            <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-gray-500">Loading review data...</p>
        </div>

        <div v-else class="space-y-6">
            <!-- Review Sections -->
            <div v-for="section in sections" :key="section.key" 
                class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mx-8">
                
                <!-- Section Header -->
                <div class="px-6 py-4 flex items-center gap-4 border-b border-gray-100" :class="section.headerClass">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center font-black text-lg shadow-sm"
                        :class="[section.iconClass]">
                        {{ section.letter }}
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-white">{{ section.title }}</h3>
                    </div>
                </div>

                <!-- Section Content -->
                <div class="p-0">
                    <!-- CASE 1: Table Layout (Goal Achievement) -->
                    <div v-if="section.key === 'goalAchievement'" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-sm font-bold text-gray-600 w-1/12 text-center">#</th>
                                    <th class="px-4 py-3 text-sm font-bold text-gray-600 w-5/12 border-l border-gray-200">Target</th>
                                    <th class="px-4 py-3 text-sm font-bold text-gray-600 w-5/12 border-l border-gray-200">Achievement</th>
                                    <th class="px-4 py-3 text-sm font-bold text-gray-600 w-1/12 text-center border-l border-gray-200">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in reviewData[section.key]" :key="index" class="border-b border-gray-100 hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-center">
                                        <span class="w-6 h-6 rounded bg-gray-100 text-gray-500 font-bold text-xs flex items-center justify-center mx-auto">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 border-l border-gray-100">
                                        <textarea v-model="item.target" class="form-textarea border-0 bg-transparent focus:bg-white focus:ring-0 min-h-[50px]" placeholder="Enter target..."></textarea>
                                    </td>
                                    <td class="px-4 py-3 border-l border-gray-100">
                                        <textarea v-model="item.achievement" class="form-textarea border-0 bg-transparent focus:bg-white focus:ring-0 min-h-[50px]" placeholder="Enter achievement..."></textarea>
                                    </td>
                                    <td class="px-4 py-3 border-l border-gray-100 text-center">
                                        <button v-if="reviewData[section.key].length > 1" @click="removeItem(section.key, index)" class="text-red-400 hover:text-red-600 transition-colors bg-transparent border-0 cursor-pointer">
                                            <i class="pi pi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="px-4 py-2 bg-gray-50 border-t border-gray-200">
                                        <button @click="addItem(section.key)" class="text-sm font-bold text-blue-600 hover:text-blue-800 flex items-center gap-2 bg-transparent border-0 cursor-pointer">
                                            <i class="pi pi-plus-circle"></i> Add Row
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- CASE 2: Dynamic List (Other Sections) -->
                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="(item, index) in reviewData[section.key]" :key="index" 
                            class="flex items-start gap-4 px-4 py-3 hover:bg-gray-50 transition-colors group">
                            <span class="w-8 h-8 rounded-lg border-2 flex items-center justify-center font-bold text-gray-500 shrink-0 mt-1"
                                :class="section.borderColor">
                                {{ index + 1 }}
                            </span>
                            <div class="flex-1">
                                <textarea 
                                    v-model="reviewData[section.key][index]"
                                    class="form-textarea w-full"
                                    :placeholder="`Enter item ${index + 1}...`"
                                    rows="2"
                                ></textarea>
                            </div>
                            <button v-if="reviewData[section.key].length > 1" @click="removeItem(section.key, index)" 
                                class="mt-2 text-gray-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 bg-transparent border-0 cursor-pointer">
                                <i class="pi pi-trash"></i>
                            </button>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                            <button @click="addItem(section.key)" class="text-sm font-bold text-purple-600 hover:text-purple-800 flex items-center gap-2 bg-transparent border-0 cursor-pointer">
                                <i class="pi pi-plus-circle"></i> Add New Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mx-8 mt-8 mb-12">
                <div class="flex items-center justify-between">
                    <button @click="saveReview(false)" :disabled="saving"
                        class="group px-6 py-3 rounded-xl bg-gradient-to-r from-gray-500 to-gray-600 text-white font-semibold hover:from-gray-600 hover:to-gray-700 shadow-md shadow-gray-300 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                        <i class="pi pi-save"></i>
                        Save Draft
                    </button>

                    <button @click="saveReview(true)" :disabled="saving"
                        class="group px-8 py-3 rounded-xl bg-gradient-to-r from-purple-500 to-violet-600 text-white font-semibold hover:from-purple-600 hover:to-violet-700 shadow-lg shadow-purple-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-xl hover:shadow-purple-300 hover:-translate-y-0.5">
                        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                        <i v-else class="pi pi-check-circle"></i>
                        {{ saving ? 'Submitting...' : 'Submit Review' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 50%, #a855f7 100%);
}

/* Employee Header Banner */
.employee-header-banner {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

/* Section Headers */
.section-header {
    background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
}

.from-blue-500 { --tw-gradient-from: #3b82f6; }
.to-blue-600 { --tw-gradient-to: #2563eb; }
.from-orange-400 { --tw-gradient-from: #fb923c; }
.to-orange-500 { --tw-gradient-to: #f97316; }
.from-cyan-400 { --tw-gradient-from: #22d3ee; }
.to-cyan-500 { --tw-gradient-to: #06b6d4; }
.from-yellow-400 { --tw-gradient-from: #facc15; }
.to-yellow-500 { --tw-gradient-to: #eab308; }
.from-green-500 { --tw-gradient-from: #22c55e; }
.to-green-600 { --tw-gradient-to: #16a34a; }

/* Year Select */
.year-select {
    padding: 0.625rem 2rem 0.625rem 1rem;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
    background-color: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    cursor: pointer;
    transition: all 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
}

.year-select:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

.year-select option {
    color: #374151;
    background-color: white;
}

/* Form Inputs */
.form-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: #f8fafc;
    color: #1e293b;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #7c3aed;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    background-color: #ffffff;
    color: #1e293b;
    resize: vertical;
    min-height: 60px;
    transition: all 0.2s;
}

.form-textarea:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.form-textarea::placeholder {
    color: #94a3b8;
}

/* Animation */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
