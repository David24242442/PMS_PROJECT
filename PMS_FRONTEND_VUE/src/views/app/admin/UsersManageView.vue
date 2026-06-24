<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { findposition, finddept, findcompany, findstatus } from '@/data/masterdata'
import { showAlert, showConfirm } from '@/helpers/essential'

const users = ref([]);
const selectedUser = ref(null);
const loading = ref(true);
const loadingUser = ref(false);
const loadingMore = ref(false);
const searchQuery = ref('');
const activeTab = ref('overview');

const page = ref(1);
const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0
});

const activityTypes = [
    { key: 'attendance', label: 'Attendance & Punctuality', options: ['Excellent', 'Good', 'Needs Improvement', 'Poor'] },
    { key: 'task_completion', label: 'Task Completion', options: ['On Time', 'Slightly Delayed', 'Significantly Delayed', 'Incomplete'] },
    { key: 'quality', label: 'Work Quality', options: ['Outstanding', 'Above Average', 'Average', 'Below Average'] },
    { key: 'teamwork', label: 'Teamwork', options: ['Excellent Collaborator', 'Good Team Player', 'Works Independently', 'Needs Improvement'] },
    { key: 'initiative', label: 'Initiative', options: ['Highly Proactive', 'Proactive', 'Reactive', 'Passive'] },
];

const checklistItems = [
    { id: 'has_app_letter', label: 'Application Letter Submitted' },
    { id: 'has_appointment_letter', label: 'Appointment Letter Signed' },
    { id: 'has_cv', label: 'CV / Resume on File' },
    { id: 'has_ghcard', label: 'Ghana Card Verified' },
    { id: 'has_ssnit', label: 'SSNIT Number Registered' },
    { id: 'has_tin', label: 'TIN / NHIS Verified' },
    { id: 'has_birthcert', label: 'Birth Certificate Submitted' },
    { id: 'has_police_clearance', label: 'Police Clearance Checked' },
];

const assessment = ref({
    checklist: [],
    ratings: {
        attendance: 0,
        task_completion: 0,
        quality: 0,
        teamwork: 0,
        initiative: 0
    },
    strengths: '',
    improvements: '',
    action_items: '',
    overall_notes: ''
});

const filteredUsers = computed(() => users.value); // Filtering is now server-side

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchUsers(1);
    }, 500); // Debounce search
};

const fetchUsers = async (pageNum = 1) => {
    if (pageNum === 1) {
        loading.value = true;
        users.value = []; // Reset on new search or refresh
    } else {
        loadingMore.value = true;
    }

    try {
        const response = await axios.post(`fetchemployees?page=${pageNum}`, {
            search: searchQuery.value
        });
        
        // Laravel paginate response: { data: [...], current_page: 1, ... }
        const newData = response.data.data;
        meta.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total
        };

        if (pageNum === 1) {
            users.value = newData;
        } else {
            users.value = [...users.value, ...newData];
        }
        
        page.value = pageNum;

    } catch (error) {
        console.error('Error fetching employees:', error);
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const loadMore = () => {
    if (page.value < meta.value.last_page) {
        fetchUsers(page.value + 1);
    }
};

const selectUser = async (user) => {
    // Basic verification to ensure user object is valid
    if (!user || !user.id) {
        console.error('Invalid user object selected');
        return;
    }

    loadingUser.value = true;
    selectedUser.value = user; // Show basic info immediately
    activeTab.value = 'overview'; // Ensure we start on the main tab
    
    // Reset assessment form
    assessment.value = {
        checklist: [],
        ratings: { attendance: 0, task_completion: 0, quality: 0, teamwork: 0, initiative: 0 },
        strengths: '',
        improvements: '',
        action_items: '',
        overall_notes: ''
    };

    try {
        const response = await axios.post('fetchemployee', { id: user.id });
        const detailedUser = response.data;
        
        // Merge detailed info into selectedUser
        selectedUser.value = { ...selectedUser.value, ...detailedUser };
        
        // Populate checklist based on fetched data
        if (detailedUser.checklist) {
             // Iterate over checklist items and verify if they are present in the response
             assessment.value.checklist = Object.keys(detailedUser.checklist).filter(key => detailedUser.checklist[key]);
        }

    } catch (error) {
        console.error('Error fetching user details:', error);
    } finally {
        loadingUser.value = false;
    }
};

const submitAssessment = async () => {
    if (!selectedUser.value) return;
    try {
        // Validation or submission logic here
        // alert('Assessment saved (Simulated) ' + selectedUser.value.name);
         const payload = {
            user_id: selectedUser.value.id,
            checklist: assessment.value.checklist,
            ...assessment.value.ratings,
            strengths: assessment.value.strengths,
            improvements: assessment.value.improvements,
            action_items: assessment.value.action_items,
            overall_notes: assessment.value.overall_notes
        };
        const response = await axios.post('pms/assessments', payload);
        if (response.data.status === 'success') {
             // alert('Assessment saved successfully!');
             showAlert('Success', 'Assessment saved successfully!', 'success');
        } else {
             showAlert('Saved', 'Assessment saved successfully!', 'success');
        }
    } catch (error) {
        console.error('Error saving assessment:', error);
    }
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <div class="h-full pb-6 flex flex-col">
        <!-- Page Header -->
        <div class="mb-4 flex-shrink-0">
            <h1 class="text-xl font-bold text-gray-800">Performance Admin</h1>
            <p class="text-xs text-gray-500 mt-1">Monitor employee feedback and assessments</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 min-h-0">
            <!-- Left Side: User Directory -->
            <div class="lg:col-span-3 flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden h-full">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-bold text-gray-700 text-sm mb-3">Employee Directory</h3>
                    <div class="relative group">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-purple-500 transition-colors"></i>
                         <input v-model="searchQuery" @input="handleSearch" type="text" placeholder="Search employees..." class="w-full pl-9 pr-3 py-2 text-sm text-gray-500 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-100 transition-all shadow-sm" />
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div v-if="loading" class="flex flex-col items-center justify-center p-8 text-gray-400">
                         <i class="pi pi-spin pi-spinner text-purple-500 text-xl mb-2"></i>
                         <span class="text-xs">Loading directory...</span>
                    </div>
                    <div v-else-if="filteredUsers.length === 0" class="p-8 text-center text-gray-400">
                        <i class="pi pi-users text-2xl mb-2 opacity-50"></i>
                         <p class="text-xs">No employees found.</p>
                    </div>
                    <div v-else class="divide-y divide-gray-50">
                        <div v-for="user in filteredUsers" :key="user.id" 
                            @click="selectUser(user)"
                            class="p-3 cursor-pointer transition-all flex items-center gap-3 border-l-[3px]"
                            :class="selectedUser?.id === user.id ? 'bg-purple-50 border-purple-600' : 'hover:bg-gray-50 border-transparent'">
                            
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gray-100 overflow-hidden flex-shrink-0 border border-gray-100 shadow-sm">
                                    <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(user.name)+'&background=random&color=fff&size=64'" class="w-full h-full object-cover" />
                                </div>
                                <div v-if="selectedUser?.id === user.id" class="absolute -right-1 -bottom-1 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                            </div>
                            
                            <div class="overflow-hidden flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate leading-tight" :class="{'text-purple-700': selectedUser?.id === user.id}">{{ user.name }}</p>
                                <p class="text-[10px] uppercase font-bold text-gray-400 truncate mt-0.5">{{ findposition(user.position_id) || user.position_id || 'Employee' }}</p>
                            </div>
                            <i v-if="selectedUser?.id === user.id" class="pi pi-chevron-right text-purple-400 text-xs"></i>
                        </div>
                        
                        <!-- Load More Button -->
                        <div v-if="page < meta.last_page" class="p-4 text-center">
                            <button @click="loadMore" :disabled="loadingMore" class="w-full py-2.5 rounded-xl bg-white border border-gray-200 shadow-sm text-xs font-bold text-gray-600 hover:text-purple-600 hover:border-purple-200 hover:bg-purple-50 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                                <span v-if="loadingMore"><i class="pi pi-spin pi-spinner"></i> Loading...</span>
                                <span v-else>Load More</span>
                                <i v-if="!loadingMore" class="pi pi-angle-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-3 border-t border-gray-100 bg-gray-50 text-center text-[10px] text-gray-400">
                    Showing {{ users.length }} of {{ meta.total }} employees
                </div>
            </div>

            <!-- Right Side: Details & Form -->
            <div class="lg:col-span-9 h-full overflow-y-auto custom-scrollbar flex flex-col rounded-xl bg-gray-50/50 border border-gray-200/60 p-1">
                <div v-if="!selectedUser" class="h-full flex flex-col items-center justify-center text-center p-12 ">
                    <div class="w-24 h-24 bg-white rounded-full shadow-sm flex items-center justify-center mb-6">
                        <i class="pi pi-user text-4xl text-purple-200"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-2">Select an Employee</h3>
                    <p class="text-sm text-gray-500 max-w-xs">Click on an employee from the directory on the left to view their profile and manage assessments.</p>
                </div>

                <div v-else class="space-y-6 p-4 animate-fade-in pb-20">
                    <!-- Clean User Header -->
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm relative group">
                        <!-- Loading Bar (Simpler CSS) -->
                        <div v-if="loadingUser" class="absolute top-0 left-0 right-0 h-1 z-30 bg-purple-100 overflow-hidden">
                            <div class="h-full bg-purple-600 w-full animate-pulse"></div>
                        </div>

                         <div class="h-24 bg-gradient-to-r from-purple-600 to-indigo-600"></div>
                         <div class="px-8 pb-6 relative">
                            <div class="flex flex-col md:flex-row gap-6 items-end -mt-10">
                                <div class="w-24 h-24 rounded-2xl bg-white p-1 shadow-lg flex-shrink-0 relative z-10">
                                    <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(selectedUser.name)+'&background=random&color=fff&size=128'" class="w-full h-full object-cover rounded-xl" />
                                    <div v-if="loadingUser" class="absolute inset-0 bg-white/80 z-20 flex items-center justify-center rounded-xl backdrop-blur-sm">
                                        <i class="pi pi-spin pi-spinner text-purple-600 text-xl"></i>
                                    </div>
                                </div>
                                
                                <div class="flex-1 w-full md:w-auto text-center md:text-left pt-2 md:pt-0 pb-1">
                                    <h2 class="text-2xl font-bold text-gray-800">{{ selectedUser.name }}</h2>
                                    <div class="flex flex-wrap gap-2 justify-center md:justify-start mt-2 items-center">
                                        <span class="px-2.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-bold rounded uppercase tracking-wider border border-gray-200">
                                            {{ findposition(selectedUser.position_id) || selectedUser.position_id }}
                                        </span>
                                        <span class="px-2.5 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded uppercase tracking-wider border border-blue-100">
                                            {{ finddept(selectedUser.department) || selectedUser.department }}
                                        </span>
                                        <span v-if="selectedUser.admin" class="px-2.5 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-bold rounded uppercase tracking-wider border border-amber-100">
                                            <i class="pi pi-shield mr-1 text-[9px]"></i>Admin
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col gap-1 items-end min-w-[120px] md:flex">
                                     <div class="text-right">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</p>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-bold border border-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            {{ findstatus(selectedUser.status) || selectedUser.status || 'Active' }}
                                        </span>
                                     </div>
                                </div>
                            </div>

                            <!-- Tabs Navigation -->
                            <!-- Tabs Navigation -->
                            <div class="flex items-center gap-2 mt-8 px-1">
                                <button 
                                    @click="activeTab = 'overview'"
                                    class="px-6 py-2.5 text-sm font-bold rounded-full transition-all duration-300 shadow-sm border"
                                    :class="activeTab === 'overview' ? 'bg-gray-900 text-white border-gray-900 shadow-lg shadow-gray-200' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:text-gray-700'">
                                    Overview
                                </button>
                                <button 
                                    @click="activeTab = 'assessment'"
                                    class="px-6 py-2.5 text-sm font-bold rounded-full transition-all duration-300 shadow-sm border"
                                    :class="activeTab === 'assessment' ? 'bg-gray-900 text-white border-gray-900 shadow-lg shadow-gray-200' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:text-gray-700'">
                                    Assessment & Feedback
                                </button>
                            </div>
                         </div>
                    </div>

                    <!-- TAB CONTENT: OVERVIEW -->
                    <div v-if="activeTab === 'overview'" class="space-y-6 animate-fade-in">
                        
                         <!-- Meta Information Card -->
                        <div class="card bg-white border border-gray-200 rounded-xl shadow-sm p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <i class="pi pi-id-card text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-base">Employee Details</h4>
                                    <p class="text-xs text-gray-500">Key employment information</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Company</p>
                                    <p class="text-sm font-semibold text-gray-700 truncate">{{ findcompany(selectedUser.employee_details?.company) || selectedUser.employee_details?.company || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Date Joined</p>
                                    <p class="text-sm font-semibold text-gray-700">{{ selectedUser.joining_date || 'N/A' }}</p>
                                </div>
                                 <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Email Address</p>
                                    <p class="text-sm font-semibold text-gray-700 truncate" :title="selectedUser.email">{{ selectedUser.email || 'No email' }}</p>
                                </div>
                                <div>
                                     <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">System Role</p>
                                     <p class="text-sm font-semibold text-gray-700">{{ selectedUser.admin ? 'Administrator' : 'Standard User' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="card bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-shield text-green-600"></i>
                                    <h4 class="font-bold text-gray-800 text-sm">Records Verification</h4>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 transition-all duration-500" :style="{width: (assessment.checklist.length / checklistItems.length * 100) + '%'}"></div>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-500">{{ Math.round(assessment.checklist.length / checklistItems.length * 100) }}%</span>
                                </div>
                            </div>
                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label v-for="item in checklistItems" :key="item.id" 
                                    class="flex items-center gap-3 p-3 rounded-lg transition-all border border-transparent"
                                    :class="assessment.checklist.includes(item.id) ? 'bg-green-50 border-green-100 text-green-800' : 'bg-gray-50 text-gray-400'">
                                    
                                    <div class="relative flex-shrink-0 flex items-center justify-center w-5 h-5 rounded border transition-all"
                                         :class="assessment.checklist.includes(item.id) ? 'bg-green-500 border-green-500' : 'border-gray-300 bg-white'">
                                        <i class="pi pi-check text-[10px] text-white" v-if="assessment.checklist.includes(item.id)"></i>
                                    </div>
                                    
                                    <span class="text-xs font-semibold select-none">{{ item.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- TAB CONTENT: ASSESSMENT -->
                    <div v-if="activeTab === 'assessment'" class="space-y-6 animate-fade-in">
                        <!-- Competencies -->
                        <div class="card bg-white border border-gray-200 rounded-xl shadow-sm h-full overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                                <i class="pi pi-chart-bar text-purple-600"></i>
                                <h4 class="font-bold text-gray-800 text-sm">Competency Assessment</h4>
                            </div>
                            <div class="p-6 space-y-6">
                                <div v-for="section in activityTypes" :key="section.key" class="bg-gray-50/30 p-4 rounded-xl border border-gray-100">
                                    <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <span class="w-1 h-4 bg-purple-500 rounded-full"></span>
                                        {{ section.label }}
                                    </p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <label v-for="(option, idx) in section.options" :key="idx" class="cursor-pointer group">
                                            <input type="radio" :name="section.key" :value="idx" v-model="assessment.ratings[section.key]" class="hidden peer" />
                                             <div class="h-full py-2.5 px-3 text-center border-2 border-gray-100 bg-white rounded-lg text-[11px] font-bold text-gray-500 peer-checked:bg-purple-600 peer-checked:text-white peer-checked:border-purple-600 peer-checked:shadow-md hover:border-gray-300 transition-all flex items-center justify-center leading-tight">
                                                {{ option }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Qualitative -->
                        <div class="card bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                                <i class="pi pi-comments text-blue-600"></i>
                                <h4 class="font-bold text-gray-800 text-sm">Qualitative Feedback</h4>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                 <div class="space-y-2">
                                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-1">Strengths & Achievements</label>
                                    <textarea v-model="assessment.strengths" class="w-full text-sm p-3 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all bg-gray-50 focus:bg-white resize-none" rows="4" placeholder="Highlight key achievements..."></textarea>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-1">Areas for Improvement</label>
                                    <textarea v-model="assessment.improvements" class="w-full text-sm p-3 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all bg-gray-50 focus:bg-white resize-none" rows="4" placeholder="Suggest areas for growth..."></textarea>
                                </div>
                                
                                 <div class="space-y-2">
                                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-1">Overall Summary</label>
                                    <textarea v-model="assessment.overall_notes" class="w-full text-sm p-3 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all bg-gray-50 focus:bg-white resize-none" rows="4" placeholder="Executive summary of performance..."></textarea>
                                </div>
                                 <div class="space-y-2">
                                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-1">Action Items</label>
                                    <textarea v-model="assessment.action_items" class="w-full text-sm p-3 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all bg-gray-50 focus:bg-white resize-none" rows="4" placeholder="Specific next steps..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                 <!-- Floating Footer -->
                <div v-if="selectedUser" class="sticky bottom-4 mx-4 mb-2 z-30">
                     <div class="bg-gray-900/90 backdrop-blur-md text-white px-6 py-4 rounded-2xl shadow-2xl flex justify-between items-center border border-white/10">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-gray-400 uppercase">Status</span>
                            <span class="font-bold text-sm">Draft - Unsaved Changes</span>
                        </div>
                        <div class="flex gap-3">
                             <button class="px-6 py-2.5 rounded-xl text-gray-300 font-bold text-xs hover:bg-white/10 transition-colors" @click="selectedUser = null">CANCEL</button>
                             <button class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-900/50 transition-all transform hover:scale-105 active:scale-95 flex items-center gap-2" @click="submitAssessment">
                                 <i class="pi pi-save"></i> SAVE ASSESSMENT
                            </button>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}

@keyframes progress {
    0% { transform: scaleX(0); }
    50% { transform: scaleX(0.5); }
    100% { transform: scaleX(1); }
}
.animate-progress {
    animation: progress 1.5s infinite linear;
}
</style>
