<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, showConfirm } from '@/helpers/essential';

const userstore = useUsersStore();
const { loguser } = userstore;

const goals = ref([]);
const loading = ref(true);
const saving = ref(false);
const showDetailModal = ref(false);
const selectedGoal = ref(null);
const currentYear = ref(new Date().getFullYear());
const years = range(currentYear.value, currentYear.value - 5);

// Multi-step form state
const viewMode = ref('list'); // 'list' or 'create'
const currentStep = ref(1);
const totalSteps = 2;

function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

const defaultSmartCriteria = () => ({
    specific: false,
    measurable: false,
    attainable: false,
    relevant: false,
    time_bound: false
});

const defaultQuarterlyTracking = () => ([
    { quarter: 'q1', date: '', target_measure: '', evidence: '', attachment: null },
    { quarter: 'q2', date: '', target_measure: '', evidence: '', attachment: null },
    { quarter: 'q3', date: '', target_measure: '', evidence: '', attachment: null },
    { quarter: 'q4', date: '', target_measure: '', evidence: '', attachment: null }
]);

const newGoal = ref({
    title: '',
    description: '',
    purposes: '',
    challenges: '',
    category: 'General',
    target: 100,
    due_date: '',
    completion_date: '',
    year: currentYear.value,
    user_id: loguser.id,
    candidate_name: '',
    manager_name: loguser.username,
    smart_criteria: defaultSmartCriteria(),
    quarterly_tracking: defaultQuarterlyTracking()
});

const smartLabels = [
    { key: 'specific', label: 'Specific', short: 'S', color: 'bg-purple-100 text-purple-700 border-purple-300' },
    { key: 'measurable', label: 'Measurable', short: 'M', color: 'bg-blue-100 text-blue-700 border-blue-300' },
    { key: 'attainable', label: 'Attainable', short: 'A', color: 'bg-green-100 text-green-700 border-green-300' },
    { key: 'relevant', label: 'Relevant', short: 'R', color: 'bg-yellow-100 text-yellow-700 border-yellow-300' },
    { key: 'time_bound', label: 'Time-bound', short: 'T', color: 'bg-red-100 text-red-700 border-red-300' }
];

const quarterColors = {
    q1: { header: 'bg-red-400 text-white', cell: 'bg-red-50' },
    q2: { header: 'bg-purple-500 text-white', cell: 'bg-purple-50' },
    q3: { header: 'bg-green-600 text-white', cell: 'bg-green-50' },
    q4: { header: 'bg-amber-600 text-white', cell: 'bg-amber-50' }
};

const fetchGoals = async () => {
    loading.value = true;
    try {
        const response = await axios.get('pms/goals', {
            params: { year: currentYear.value }
        });
        if (response.data.status === 'success') {
            goals.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching goals:', error);
    } finally {
        loading.value = false;
    }
};

const startCreateGoal = () => {
    resetForm();
    currentStep.value = 1;
    viewMode.value = 'create';
};

const cancelCreate = () => {
    viewMode.value = 'list';
    currentStep.value = 1;
    resetForm();
};

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const addGoal = async () => {
    saving.value = true;
    try {
    const payload = {
            ...newGoal.value,
            description: JSON.stringify(newGoal.value.description.filter(d => d.trim() !== '')),
            purposes: JSON.stringify(newGoal.value.purposes.filter(p => p.trim() !== '')),
            challenges: JSON.stringify(newGoal.value.challenges.filter(c => c.trim() !== ''))
        };
        const response = await axios.post('pms/goals', payload);
        if (response.data.status === 'success') {
            goals.value.push(response.data.data);
            viewMode.value = 'list';
            resetForm();
            showAlert('Success', 'SMART Goal created successfully!', 'success');
        }
    } catch (error) {
        console.error('Error adding goal:', error);
        showAlert('Error', 'Failed to create goal.', 'error');
    } finally {
        saving.value = false;
    }
};

const resetForm = () => {
    newGoal.value = { 
        title: '', 
        description: [''], 
        purposes: [''],
        challenges: [''],
        category: 'General', 
        target: 100, 
        due_date: '', 
        completion_date: '',
        year: currentYear.value,
        user_id: loguser.id,
        candidate_name: '',
        manager_name: loguser.name || '',
        smart_criteria: defaultSmartCriteria(),
        quarterly_tracking: defaultQuarterlyTracking()
    };
    currentStep.value = 1;
};

const updateProgress = async (goal) => {
    try {
        await axios.patch(`pms/goals/${goal.id}`, { 
            actual: goal.actual, 
            status: goal.actual >= goal.target ? 'completed' : 'in_progress' 
        });
    } catch (error) {
        console.error('Error updating goal progress:', error);
    }
};

const deleteGoal = async (id) => {
    const result = await showConfirm('Delete Goal', 'Are you sure you want to delete this goal?', 'warning', 'Yes, Delete');
    if (!result.isConfirmed) return;
    try {
        await axios.delete(`pms/goals/${id}`);
        goals.value = goals.value.filter(g => g.id !== id);
        showAlert('Deleted', 'Goal removed successfully.', 'success');
    } catch (error) {
        console.error('Error deleting goal:', error);
    }
};

const viewGoalDetail = (goal) => {
    selectedGoal.value = { 
        ...goal,
        smart_criteria: goal.smart_criteria || defaultSmartCriteria(),
        quarterly_tracking: goal.quarterly_tracking || defaultQuarterlyTracking()
    };
    showDetailModal.value = true;
};

const getSmartScore = (goal) => {
    if (!goal.smart_criteria) return 0;
    const criteria = goal.smart_criteria;
    return Object.values(criteria).filter(v => v === true).length;
};

const canProceedToStep2 = computed(() => {
    return newGoal.value.title.trim() !== '';
});

onMounted(() => {
    fetchGoals();
});

// Stats Computed
const stats = computed(() => {
    return {
        total: goals.value.length,
        completed: goals.value.filter(g => g.actual >= g.target).length,
        inProgress: goals.value.filter(g => g.actual < g.target && g.actual > 0).length,
        pending: goals.value.filter(g => g.actual === 0 || g.actual === null).length
    };
});
// Dynamic Field Helpers
const addPurpose = () => {
    newGoal.value.purposes.push('');
};
const removePurpose = (index) => {
    newGoal.value.purposes.splice(index, 1);
};
const addChallenge = () => {
    newGoal.value.challenges.push('');
};
const removeChallenge = (index) => {
    newGoal.value.challenges.splice(index, 1);
};
const addDescription = () => {
    newGoal.value.description.push('');
};
const removeDescription = (index) => {
    newGoal.value.description.splice(index, 1);
};

// Formatting Helper for View
const parseList = (jsonString) => {
    try {
        const parsed = JSON.parse(jsonString);
        if (Array.isArray(parsed)) return parsed;
        return [jsonString]; // Fallback if regular string
    } catch (e) {
        return [jsonString]; // Fallback if regular string
    }
};
// File Upload Helper
const uploading = ref({}); // Track uploading state per quarter

const handleFileUpload = async (event, qIndex) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    uploading.value[qIndex] = true;

    try {
        const response = await axios.post('pms/goals/upload-attachment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.status === 'success') {
            newGoal.value.quarterly_tracking[qIndex].attachment = {
                url: response.data.file_url,
                name: response.data.file_name
            };
            showAlert('Success', 'File uploaded successfully', 'success');
        }
    } catch (error) {
        console.error('File upload failed:', error);
        showAlert('Error', 'File upload failed. Please try again.', 'error');
    } finally {
        uploading.value[qIndex] = false;
    }
};

</script>

<template>
    <div class="h-full pb-6">
        <!-- LIST VIEW -->
        <template v-if="viewMode === 'list'">
            <!-- Page Header with Gradient -->
            <div class="mb-8 bg-white/50 backdrop-blur-sm border border-white/20 shadow-sm rounded-2xl p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-indigo-600 flex items-center gap-3 mb-2">
                            <i class="pi pi-flag text-2xl text-purple-600"></i> Yearly SMART Goals Setting
                        </h1>
                        <p class="text-sm font-medium text-gray-500">Define, track, and achieve your strategic objectives using SMART criteria.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select v-model="currentYear" @change="fetchGoals" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-purple-200 shadow-sm hover:border-purple-300 transition-all cursor-pointer">
                            <option v-for="year in years" :key="year" :value="year">FY {{ year }}</option>
                        </select>
                        <button @click="startCreateGoal" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-purple-200 transform hover:-translate-y-0.5 transition-all flex items-center gap-2">
                            <i class="pi pi-plus font-bold"></i>
                            <span>New SMART Goal</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Employee Info Header -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-8 overflow-hidden">
                <div class="bg-gray-50/50 px-6 py-3 border-b border-gray-100">
                    <h3 class="font-bold text-gray-500 text-xs uppercase tracking-wider flex items-center gap-2">
                        <i class="pi pi-id-card"></i> Employee & Manager Information
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                    <div class="p-6 border-b md:border-b-0 md:border-r border-gray-100 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center shadow-inner">
                                <i class="pi pi-user text-2xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-purple-600 uppercase font-black tracking-widest mb-0.5">Candidate</p>
                                <p class="font-bold text-gray-800 text-lg leading-tight"></p>
                                <p class="text-sm text-gray-500 font-medium">{{ loguser?.job_title || 'Job Title' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center shadow-inner">
                                <i class="pi pi-users text-2xl text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-emerald-600 uppercase font-black tracking-widest mb-0.5">Line Manager</p>
                                <p class="font-bold text-gray-800 text-lg leading-tight">{{ loguser?.name || 'Manager Name' }}</p>
                                <p class="text-sm text-gray-500 font-medium">{{ loguser?.manager_title || 'Manager Title' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Goals -->
                <div class="relative overflow-hidden rounded-2xl p-6 shadow-xl shadow-purple-100 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-purple-500/20"
                     style="background: linear-gradient(135deg, #4c1d95 0%, #312e81 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-3 mb-3 opacity-90">
                        <i class="pi pi-flag text-xl"></i>
                         <span class="text-xs font-bold uppercase tracking-wider">Total Goals</span>
                    </div>
                    <div class="text-4xl font-extrabold mb-1">{{ stats.total }}</div>
                    <div class="text-xs font-medium opacity-80">FY {{ currentYear }} Objectives</div>
                </div>

                <!-- Completed -->
                <div class="relative overflow-hidden rounded-2xl p-6 shadow-xl shadow-emerald-100 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-emerald-500/20"
                     style="background: linear-gradient(135deg, #059669 0%, #064e3b 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-3 mb-3 opacity-90">
                        <i class="pi pi-check-circle text-xl"></i>
                         <span class="text-xs font-bold uppercase tracking-wider">Completed</span>
                    </div>
                    <div class="text-4xl font-extrabold mb-1">{{ stats.completed }}</div>
                    <div class="text-xs font-medium opacity-80">Achieved</div>
                </div>

                <!-- In Progress -->
                <div class="relative overflow-hidden rounded-2xl p-6 shadow-xl shadow-blue-100 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-blue-500/20"
                     style="background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-3 mb-3 opacity-90">
                        <i class="pi pi-chart-line text-xl"></i>
                         <span class="text-xs font-bold uppercase tracking-wider">In Progress</span>
                    </div>
                    <div class="text-4xl font-extrabold mb-1">{{ stats.inProgress }}</div>
                    <div class="text-xs font-medium opacity-80">Working on</div>
                </div>

                <!-- Pending -->
                <div class="relative overflow-hidden rounded-2xl p-6 shadow-xl shadow-gray-100 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-gray-500/20"
                     style="background: linear-gradient(135deg, #475569 0%, #1e293b 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-3 mb-3 opacity-90">
                        <i class="pi pi-clock text-xl"></i>
                         <span class="text-xs font-bold uppercase tracking-wider">Not Started</span>
                    </div>
                    <div class="text-4xl font-extrabold mb-1">{{ stats.pending }}</div>
                    <div class="text-xs font-medium opacity-80">Pending Action</div>
                </div>
            </div>

            <!-- Goals Table -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-8 py-5 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                            Active SMART Goals
                            <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-700 text-xs font-bold">{{ goals.length }}</span>
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Manage and track your performance objectives.</p>
                    </div>
                </div>

                <div v-if="loading" class="p-20 flex flex-col items-center justify-center">
                    <div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 text-gray-500 font-medium animate-pulse">Loading goals...</p>
                </div>

                <div v-else-if="goals.length === 0" class="p-20 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-inbox text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">No SMART Goals Found</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">Get started by creating your first SMART goal for this year. Set clear objectives to drive your success.</p>
                    <button @click="startCreateGoal" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg shadow-purple-200 transition-all transform hover:-translate-y-1 flex items-center gap-2 mx-auto">
                        <i class="pi pi-plus"></i>
                        <span>Create Your First Goal</span>
                    </button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 uppercase font-bold text-xs border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 w-[30%]">Goal Details</th>
                                <th class="px-6 py-4 w-[15%]">SMART Score</th>
                                <th class="px-6 py-4 w-[20%]">Progress</th>
                                <th class="px-6 py-4 w-[15%]">Completion Date</th>
                                <th class="px-6 py-4 w-[20%] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="goal in goals" :key="goal.id" class="hover:bg-purple-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <strong class="text-gray-800 block mb-1 text-base">{{ goal.title }}</strong>
                                    <div class="text-gray-500 text-xs line-clamp-2">
                                        <ul v-if="parseList(goal.description).length > 1" class="list-disc list-inside">
                                             <li v-for="(item, i) in parseList(goal.description).slice(0, 2)" :key="i">{{ item }}</li>
                                        </ul>
                                        <span v-else>{{ parseList(goal.description)[0] || 'No description' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <span v-for="criteria in smartLabels" :key="criteria.key"
                                            :class="[goal.smart_criteria?.[criteria.key] ? criteria.color : 'bg-gray-100 text-gray-400 border-gray-200']"
                                            class="w-7 h-7 rounded-md flex items-center justify-center text-xs font-black border">
                                            {{ criteria.label.charAt(0) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="goal.actual >= goal.target ? 'bg-gradient-to-r from-green-400 to-green-500' : 'bg-gradient-to-r from-purple-400 to-purple-600'"
                                                :style="{ width: Math.min(100, ((goal.actual || 0) / (goal.target || 100)) * 100) + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700 w-12 text-right">{{ Math.round(((goal.actual || 0) / (goal.target || 100)) * 100) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="goal.completion_date" class="text-gray-700 text-sm font-medium">{{ new Date(goal.completion_date).toLocaleDateString() }}</span>
                                    <span v-else class="text-gray-400 text-sm italic">Not set</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="viewGoalDetail(goal)" class="action-btn action-btn-view" title="View Details">
                                            <i class="pi pi-eye"></i>
                                        </button>
                                        <button @click="deleteGoal(goal.id)" class="action-btn action-btn-delete" title="Delete">
                                            <i class="pi pi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- CREATE VIEW - Full Page Multi-Step Form -->
        <template v-if="viewMode === 'create'">
            <!-- Header with Stepper -->
            <div class="mb-6 rounded-2xl shadow-lg shadow-purple-200 text-white overflow-hidden relative"
                 style="background: linear-gradient(135deg, #6b21a8 0%, #3730a3 100%) !important;">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="pi pi-compass text-9xl"></i>
                </div>
                <div class="px-8 py-6 relative z-10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <button @click="cancelCreate" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 transition-all text-white border border-white/10">
                                <i class="pi pi-arrow-left text-xl"></i>
                            </button>
                            <div>
                                <h1 class="text-2xl font-bold flex items-center gap-2">
                                    Create SMART Goal
                                    <span class="px-2 py-0.5 rounded text-xs bg-white/20 border border-white/10 font-bold tracking-wider uppercase">FY {{ currentYear }}</span>
                                </h1>
                                <p class="text-purple-100 text-sm mt-1">Step {{ currentStep }} of {{ totalSteps }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <!-- Step Indicators -->
                            <div class="flex items-center gap-4 bg-black/20 px-6 py-3 rounded-2xl border border-white/10 backdrop-blur-sm">
                                <div class="flex items-center gap-3">
                                    <div :class="currentStep >= 1 ? 'bg-white text-purple-700 shadow-md' : 'bg-white/20 text-white'"
                                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-all border-2 border-transparent">1</div>
                                    <span class="text-white font-bold text-sm hidden md:inline" :class="currentStep >= 1 ? 'opacity-100' : 'opacity-60'">Goal Details</span>
                                </div>
                                <div class="w-12 h-0.5 bg-white/20 rounded-full"></div>
                                <div class="flex items-center gap-3">
                                    <div :class="currentStep >= 2 ? 'bg-white text-purple-700 shadow-md' : 'bg-white/20 text-white'"
                                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-all border-2 border-transparent">2</div>
                                    <span class="text-white font-bold text-sm hidden md:inline" :class="currentStep >= 2 ? 'opacity-100' : 'opacity-60'">Quarterly Tracking</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 1: Goal Details -->
            <div v-if="currentStep === 1" class="space-y-6 animate-fadeIn">
                <!-- Employee Info Mini Header -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                        <!-- Candidate Section -->
                        <div class="p-6 relative overflow-hidden group hover:bg-purple-50/30 transition-colors">
                            <div class="flex items-start justify-between relative z-10">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center shadow-inner text-purple-600">
                                        <i class="pi pi-user text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] text-purple-600 uppercase font-black tracking-widest mb-1">Candidate Name</p>
                                        <input v-model="newGoal.candidate_name" type="text" class="font-bold text-gray-800 text-lg w-full bg-transparent border-b-2 border-gray-200 hover:border-purple-400 focus:border-purple-600 focus:ring-0 px-0 py-0.5 transition-all placeholder-gray-400" placeholder="Enter Name..." />
                                        <p class="text-sm text-gray-500 mt-1 font-medium">{{ loguser?.job_title || 'Select Job Title' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Line Manager Section -->
                        <div class="p-6 relative overflow-hidden group hover:bg-emerald-50/30 transition-colors">
                             <div class="flex items-start justify-between relative z-10">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center shadow-inner text-emerald-600">
                                        <i class="pi pi-briefcase text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] text-emerald-600 uppercase font-black tracking-widest mb-1">Line Manager Name</p>
                                        <input v-model="newGoal.manager_name" type="text" class="font-bold text-gray-800 text-lg w-full bg-transparent border-b-2 border-transparent hover:border-gray-200 focus:border-emerald-500 focus:ring-0 px-0 py-0.5 transition-all placeholder-gray-300" placeholder="Enter Manager Name..." />
                                        <p class="text-sm text-gray-500 mt-1 font-medium">{{ loguser?.manager_title || 'Select Manager Title' }}</p>
                                    </div>
                                </div>
                                <div class="text-right bg-gray-50 px-3 py-1 rounded-lg border border-gray-100">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Date</p>
                                    <p class="font-bold text-gray-700 text-sm">{{ new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Form Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Column: Goals, Purposes, Challenges -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- GOALS Section -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="bg-blue-600 px-6 py-4 border-b border-blue-600 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center backdrop-blur-sm">
                                    <i class="pi pi-flag text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-sm uppercase tracking-wide">Goal Definition</h4>
                                    <p class="text-xs text-blue-100">What do you want to achieve?</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 ml-1">Goal Title</label>
                                    <input v-model="newGoal.title" type="text" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-lg font-bold text-gray-800 focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none placeholder-gray-400" placeholder="e.g. Improve Customer Satisfaction Score by 15%" />
                                </div>
                                
                                <div class="space-y-3">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 ml-1">Objectives / Key Results</label>
                                    <div v-for="(desc, index) in newGoal.description" :key="index" class="flex gap-3 group">
                                        <textarea v-model="newGoal.description[index]" rows="2" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-700 focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none resize-none placeholder-gray-400" placeholder="Describe a specific objective..."></textarea>
                                        <button @click="removeDescription(index)" v-if="newGoal.description.length > 1" class="text-gray-300 hover:text-red-500 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100" title="Remove">
                                            <i class="pi pi-trash"></i>
                                        </button>
                                    </div>
                                    <button @click="addDescription" class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center gap-2 mt-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all border border-blue-100">
                                        <i class="pi pi-plus-circle"></i> Add Another Objective
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- PURPOSES Section -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                             <div class="bg-green-600 px-6 py-4 border-b border-green-600 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center backdrop-blur-sm">
                                    <i class="pi pi-question-circle text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-sm uppercase tracking-wide">Purposes & Benefits</h4>
                                    <p class="text-xs text-green-100">Why is this relevant?</p>
                                </div>
                            </div>
                             <div class="p-6 space-y-4">
                                <div v-for="(purpose, index) in newGoal.purposes" :key="index" class="flex gap-3 group">
                                     <textarea v-model="newGoal.purposes[index]" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-500/10 transition-all outline-none resize-none placeholder-gray-400" placeholder="Explain the purpose and benefits..."></textarea>
                                     <button @click="removePurpose(index)" v-if="newGoal.purposes.length > 1" class="text-gray-300 hover:text-red-500 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100" title="Remove">
                                        <i class="pi pi-trash"></i>
                                     </button>
                                </div>
                                <button @click="addPurpose" class="text-sm font-bold text-green-600 hover:text-green-700 flex items-center gap-2 mt-2 px-4 py-2 bg-green-50 hover:bg-green-100 rounded-xl transition-all border border-green-100">
                                    <i class="pi pi-plus-circle"></i> Add Another Purpose
                                </button>
                             </div>
                        </div>

                        <!-- CHALLENGES Section -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="bg-orange-500 px-6 py-4 border-b border-orange-500 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center backdrop-blur-sm">
                                    <i class="pi pi-exclamation-triangle text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-sm uppercase tracking-wide">Potential Challenges</h4>
                                    <p class="text-xs text-orange-100">Obstacles to overcome</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div v-for="(challenge, index) in newGoal.challenges" :key="index" class="flex gap-3 group">
                                     <textarea v-model="newGoal.challenges[index]" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all outline-none resize-none placeholder-gray-400" placeholder="List potential challenge..."></textarea>
                                      <button @click="removeChallenge(index)" v-if="newGoal.challenges.length > 1" class="text-gray-300 hover:text-red-500 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100" title="Remove">
                                        <i class="pi pi-trash"></i>
                                     </button>
                                </div>
                                <button @click="addChallenge" class="text-sm font-bold text-orange-600 hover:text-orange-700 flex items-center gap-2 mt-2 px-4 py-2 bg-orange-50 hover:bg-orange-100 rounded-xl transition-all border border-orange-100">
                                    <i class="pi pi-plus-circle"></i> Add Another Challenge
                                </button>
                             </div>
                        </div>

                        <!-- Completion Date -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="bg-gray-800 px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                                 <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center backdrop-blur-sm">
                                    <i class="pi pi-calendar text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-sm uppercase tracking-wide">Classifications</h4>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide ml-1">Category</label>
                                        <div class="relative">
                                            <i class="pi pi-tag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>
                                            <select v-model="newGoal.category" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:bg-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none appearance-none transition-all">
                                                <option>General</option>
                                                <option>Technical</option>
                                                <option>Development</option>
                                                <option>Behavioral</option>
                                                <option>Leadership</option>
                                            </select>
                                            <i class="pi pi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide ml-1">Target Value</label>
                                        <div class="relative">
                                            <i class="pi pi-chart-bar absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>
                                            <input v-model="newGoal.target" type="number" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:bg-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all" />
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide ml-1">Completion Date</label>
                                         <div class="relative">
                                            <input v-model="newGoal.completion_date" type="date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:bg-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: SMART Criteria Checklist -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-6">
                            <div class="bg-gray-50/50 px-6 py-4 border-b border-gray-100">
                                <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide">My Goal Is...</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Check (✓) if criteria is met</p>
                            </div>
                            <div class="p-4 space-y-3">
                                <label v-for="criteria in smartLabels" :key="criteria.key" 
                                    class="flex items-center justify-between p-3 rounded-xl border-2 cursor-pointer transition-all hover:bg-gray-50"
                                    :class="newGoal.smart_criteria[criteria.key] ? criteria.color + ' border-current shadow-sm' : 'bg-white border-gray-100 text-gray-500 hover:border-gray-300'">
                                    <span class="font-bold text-sm" :class="newGoal.smart_criteria[criteria.key] ? '' : 'text-gray-500'">{{ criteria.label }}</span>
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-xs"
                                            :class="newGoal.smart_criteria[criteria.key] ? 'bg-white/60' : 'bg-gray-100 text-gray-400'">
                                            {{ criteria.short }}
                                        </span>
                                        <input type="checkbox" v-model="newGoal.smart_criteria[criteria.key]" class="hidden" />
                                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all"
                                             :class="newGoal.smart_criteria[criteria.key] ? 'bg-current border-transparent' : 'border-gray-300 bg-white'">
                                            <i class="pi pi-check text-[10px] text-white" v-show="newGoal.smart_criteria[criteria.key]"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Quarterly Tracking -->
            <div v-if="currentStep === 2" class="space-y-6 animate-fadeIn">
                <!-- Goal Summary -->
                <div class="relative rounded-2xl p-8 shadow-2xl shadow-purple-200 text-white overflow-hidden"
                     style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="pi pi-flag text-9xl"></i>
                    </div>
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row gap-6 justify-between items-start">
                             <div class="flex-1">
                                <h3 class="font-bold text-3xl mb-2 text-white/90">{{ newGoal.title || 'Untitled Goal' }}</h3>
                                <p class="text-purple-100 text-lg leading-relaxed mb-6">{{ newGoal.description[0] || 'No description provided' }}</p>
                                
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold text-purple-300 uppercase tracking-wider mr-2">SMART Status:</span>
                                    <span v-for="criteria in smartLabels" :key="criteria.key"
                                        :class="[newGoal.smart_criteria[criteria.key] ? 'bg-white text-purple-800' : 'bg-white/10 text-white/40']"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black transition-all">
                                        {{ criteria.short }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-white/10 rounded-xl p-4 backdrop-blur-md border border-white/10 min-w-[200px]">
                                <div class="text-center">
                                    <p class="text-xs text-purple-200 uppercase font-bold tracking-widest mb-1">Target</p>
                                    <p class="text-4xl font-black">{{ newGoal.target }}</p>
                                    <p class="text-sm font-bold mt-1 text-purple-200">{{ newGoal.completion_date ? new Date(newGoal.completion_date).toLocaleDateString() : 'No date set' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quarterly Tracking Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                                <i class="pi pi-calendar text-purple-600 bg-purple-50 p-2 rounded-lg"></i> 
                                Quarterly Milestones
                            </h4>
                            <p class="text-sm text-gray-500 mt-1 pl-10">Define targets and evidence for each quarter (Cumulative Growth)</p>
                        </div>
                    </div>
                    
                    <div class="p-8 bg-gray-50/50">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Q1 -->
                            <div v-for="(quarter, index) in newGoal.quarterly_tracking" :key="index" 
                                class="rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 bg-white group hover:-translate-y-1">
                                <div :class="quarterColors['q'+(index+1)].header" class="py-4 px-4 font-bold text-center border-b border-white/20 relative overflow-hidden">
                                     <div class="absolute inset-0 opacity-0 group-hover:opacity-20 transition-opacity bg-white"></div>
                                     <span class="text-lg tracking-widest">{{ quarter.quarter ? quarter.quarter.toUpperCase() : 'Q'+(index+1) }}</span>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                            <i class="pi pi-calendar text-gray-300"></i> Date
                                        </label>
                                        <input v-model="quarter.date" type="date" 
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 outline-none transition-all" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                            <i class="pi pi-bullseye text-gray-300"></i> Target
                                        </label>
                                        <div class="relative">
                                            <textarea v-model="quarter.target_measure" rows="3"
                                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 outline-none transition-all resize-none" 
                                                placeholder="Target..."></textarea>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                            <i class="pi pi-file text-gray-300"></i> Evidence
                                        </label>
                                        <div class="relative">
                                            <textarea v-model="quarter.evidence" rows="3"
                                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 outline-none transition-all resize-none" 
                                                placeholder="Evidence..."></textarea>
                                        </div>
                                    </div>
                                    <!-- Attachment Section -->
                                    <div class="pt-2 border-t border-gray-50">
                                        <div v-if="quarter.attachment" class="flex items-center gap-2 p-2 bg-blue-50 border border-blue-100 rounded-lg group/file">
                                            <i class="pi pi-file-pdf text-blue-500"></i>
                                            <a :href="quarter.attachment.url" target="_blank" class="text-xs text-blue-600 hover:underline truncate flex-1 font-medium">
                                                {{ quarter.attachment.name }}
                                            </a>
                                            <button @click="quarter.attachment = null" class="text-gray-400 hover:text-red-500">
                                                <i class="pi pi-times text-xs"></i>
                                            </button>
                                        </div>

                                        <div v-else class="relative">
                                            <label class="flex items-center justify-center gap-2 w-full p-2 bg-gray-50 hover:bg-purple-50 border border-dashed border-gray-300 hover:border-purple-300 rounded-lg cursor-pointer transition-all group/upload">
                                                <i class="pi pi-paperclip text-gray-400 group-hover/upload:text-purple-500"></i>
                                                <span class="text-xs text-gray-500 group-hover/upload:text-purple-600 font-medium">Attach File</span>
                                                <input type="file" @change="handleFileUpload($event, index)" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png" />
                                            </label>
                                            <div v-if="uploading[index]" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <i class="pi pi-spin pi-spinner text-purple-600"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Navigation -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <button v-if="currentStep > 1" @click="prevStep" 
                    class="px-6 py-3 rounded-xl border-2 border-gray-200 text-gray-600 font-bold hover:border-gray-800 hover:text-gray-900 transition-all duration-200 flex items-center gap-2">
                    <i class="pi pi-arrow-left"></i> 
                    Go Back
                </button>
                <button v-else @click="cancelCreate" 
                    class="px-6 py-3 rounded-xl bg-white border-2 border-gray-200 text-gray-600 font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all duration-200 flex items-center gap-2 shadow-sm">
                    Cancel Goal
                </button>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                    <button v-if="currentStep < totalSteps" @click="nextStep" :disabled="!canProceedToStep2" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold shadow-xl shadow-purple-200 hover:shadow-2xl hover:shadow-purple-300 transition-all transform hover:-translate-y-1 disabled:opacity-50 disabled:transform-none disabled:shadow-none min-w-[200px] flex items-center justify-center gap-3 text-lg">
                         <span>Next Step</span>
                         <i class="pi pi-arrow-right"></i>
                    </button>
                    <button v-else @click="addGoal" :disabled="saving" class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-bold shadow-xl shadow-emerald-200 hover:shadow-2xl hover:shadow-emerald-300 transition-all transform hover:-translate-y-1 disabled:opacity-70 disabled:transform-none disabled:shadow-none min-w-[220px] flex items-center justify-center gap-3 text-lg">
                        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                        <i v-else class="pi pi-check"></i>
                        {{ saving ? 'Saving...' : 'Complete & Save' }}
                    </button>
                </div>
                </div>
            </div>
        </template>

        <!-- Goal Detail Modal -->
        <transition name="modal">
            <div v-if="showDetailModal && selectedGoal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" @click.self="showDetailModal = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-bold text-xl text-gray-800">{{ selectedGoal.title }}</h3>
                        <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>
                    
                    <div class="p-6 overflow-y-auto max-h-[calc(85vh-120px)] space-y-5 modal-content">
                        <!-- SMART Score -->
                        <div class="flex items-center gap-2 p-4 bg-gray-50 rounded-xl">
                            <span class="text-sm font-bold text-gray-600">SMART Score:</span>
                            <div class="flex gap-1">
                                <span v-for="criteria in smartLabels" :key="criteria.key"
                                    :class="[selectedGoal.smart_criteria?.[criteria.key] ? criteria.color : 'bg-gray-100 text-gray-400 border-gray-200']"
                                    class="w-8 h-8 rounded-md flex items-center justify-center text-sm font-black border">
                                    {{ criteria.label.charAt(0) }}
                                </span>
                            </div>
                            <span class="ml-auto font-bold text-primary">{{ getSmartScore(selectedGoal) }}/5</span>
                        </div>

                        <!-- Description -->
                        <div v-if="selectedGoal.description">
                            <h4 class="font-bold text-gray-700 mb-2">Description / Objectives</h4>
                            <ul class="list-disc list-inside text-gray-600 space-y-1">
                                <li v-for="(item, i) in parseList(selectedGoal.description)" :key="i">{{ item }}</li>
                            </ul>
                        </div>

                        <!-- Purposes -->
                        <div v-if="selectedGoal.purposes" class="p-4 bg-green-50 rounded-xl border border-green-100">
                            <h4 class="font-bold text-green-800 mb-2 flex items-center gap-2">
                                <i class="pi pi-question-circle"></i> Purposes
                            </h4>
                            <ul class="list-disc list-inside text-green-700 space-y-1">
                                <li v-for="(item, i) in parseList(selectedGoal.purposes)" :key="i">{{ item }}</li>
                            </ul>
                        </div>

                        <!-- Challenges -->
                        <div v-if="selectedGoal.challenges" class="p-4 bg-orange-50 rounded-xl border border-orange-100">
                            <h4 class="font-bold text-orange-800 mb-2 flex items-center gap-2">
                                <i class="pi pi-exclamation-triangle"></i> Challenges
                            </h4>
                             <ul class="list-disc list-inside text-orange-700 space-y-1">
                                <li v-for="(item, i) in parseList(selectedGoal.challenges)" :key="i">{{ item }}</li>
                            </ul>
                        </div>

                        <!-- Quarterly Tracking -->
                        <div v-if="selectedGoal.quarterly_tracking">
                            <h4 class="font-bold text-gray-700 mb-3">Quarterly Tracking</h4>
                            <div class="grid grid-cols-4 gap-3">
                                <div v-for="(data, q) in selectedGoal.quarterly_tracking" :key="q" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <span class="text-xs font-bold uppercase text-gray-500">{{ q.toUpperCase() }}</span>
                                    <div class="mt-2 text-sm space-y-1">
                                        <p><strong>Date:</strong> {{ data.date || '-' }}</p>
                                        <p><strong>Target:</strong> {{ data.target_measure || '-' }}</p>
                                        <p><strong>Evidence:</strong> {{ data.evidence || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.form-control {
    width: 100%;
    padding: 0.65rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.form-control::placeholder {
    color: #94a3b8;
}

.modal-enter-active, .modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar styling to match theme */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f3f0ff;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #8b5cf6, #7c3aed);
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #7c3aed, #6d28d9);
}

/* Custom checkbox styling to match theme */
.smart-checkbox {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 0.375rem;
    border: 2px solid #d1d5db;
    background-color: #ffffff;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    transition: all 0.2s ease;
    position: relative;
}

.smart-checkbox:checked {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    border-color: #7c3aed;
}

.smart-checkbox:checked::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 3px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2.5px 2.5px 0;
    transform: rotate(45deg);
}

.smart-checkbox:hover {
    border-color: #8b5cf6;
}

.smart-checkbox:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
}

/* Fade In Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

/* Quarterly Table Styles */
.quarterly-table th:first-child {
    border-top-left-radius: 0.5rem;
}

.quarterly-table th:last-child {
    border-top-left-radius: 0.5rem;
}

/* Stepper Header Styles */
.stepper-header {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 50%, #a855f7 100%);
}

.back-btn {
    color: rgba(255, 255, 255, 0.8);
}

.back-btn:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
}

.step-active {
    background-color: white;
    color: #7c3aed;
}

.step-inactive {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.progress-bar {
    background: linear-gradient(90deg, #7c3aed, #9333ea);
}

/* Page Header Styles */
.page-header {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 50%, #a855f7 100%);
}

.year-select {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.625rem 1rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.year-select:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
}

.year-select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
}

.year-select option {
    background: #7c3aed;
    color: white;
}

.create-goal-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    color: #7c3aed;
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.create-goal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.15);
}

.create-goal-btn:active {
    transform: translateY(0);
}

/* Employee Header Banner */
.employee-header-banner {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

/* KPI Card Styles */
.kpi-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    background: white;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.kpi-card:hover {
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.kpi-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.kpi-content {
    flex: 1;
    min-width: 0;
}

.kpi-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.kpi-value {
    font-size: 1.875rem;
    font-weight: 900;
    line-height: 1.2;
}

.kpi-sub {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

/* KPI Card Color Variants */
.kpi-total .kpi-icon {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.kpi-total .kpi-value {
    color: #6366f1;
}

.kpi-completed .kpi-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.kpi-completed .kpi-value {
    color: #10b981;
}

.kpi-progress .kpi-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.kpi-progress .kpi-value {
    color: #f59e0b;
}

.kpi-pending .kpi-icon {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.kpi-pending .kpi-value {
    color: #6b7280;
}

/* Goal Count Badge */
.goal-count-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.875rem;
    background: linear-gradient(135deg, #7c3aed, #9333ea);
    color: white;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 2px 4px -1px rgba(124, 58, 237, 0.3);
}

/* Empty State Styles */
.empty-state-icon {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon i {
    font-size: 2.5rem;
    color: #9ca3af;
}

.create-first-goal-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #7c3aed, #9333ea);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px -2px rgba(124, 58, 237, 0.4);
}

.create-first-goal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -4px rgba(124, 58, 237, 0.5);
}

/* Action Button Styles */
.action-btn {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    background: transparent;
}

.action-btn-view {
    color: #6b7280;
}

.action-btn-view:hover {
    background: linear-gradient(135deg, #7c3aed10, #9333ea10);
    color: #7c3aed;
}

.action-btn-delete {
    color: #6b7280;
}

.action-btn-delete:hover {
    background: #fef2f2;
    color: #ef4444;
}
</style>
