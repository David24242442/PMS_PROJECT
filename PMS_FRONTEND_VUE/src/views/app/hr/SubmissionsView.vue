<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { showAlert } from '@/helpers/essential';

const submissions = ref([]);
const loading = ref(true);
const selectedAppraisal = ref(null);
const showActionModal = ref(false);
const actionData = ref({ status: 'approved', hr_comments: '', overall_rating: 4.0 });
const filterStatus = ref('all');
const filterDepartment = ref('');
const searchQuery = ref('');
const currentYear = ref(new Date().getFullYear());

const stats = computed(() => {
    return {
        total: submissions.value.length,
        pending: submissions.value.filter(s => s.status === 'pending').length,
        approved: submissions.value.filter(s => s.status === 'approved').length,
        rejected: submissions.value.filter(s => s.status === 'rejected').length
    };
});

const filteredSubmissions = computed(() => {
    return submissions.value.filter(s => {
        // Status Filter
        if (filterStatus.value !== 'all' && s.status !== filterStatus.value) return false;
        
        // Department Filter
        if (filterDepartment.value && s.user?.department !== filterDepartment.value) return false;

        // Search Filter
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const name = s.user?.name?.toLowerCase() || '';
            const empCode = s.user?.employee_code?.toLowerCase() || '';
            return name.includes(query) || empCode.includes(query);
        }

        return true;
    });
});

const fetchSubmissions = async () => {
    loading.value = true;
    try {
        const response = await axios.get('pms/appraisals/all', {
            params: { year: currentYear.value }
        });
        if (response.data.status === 'success') {
            submissions.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching submissions:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const openActionModal = (appraisal) => {
    selectedAppraisal.value = appraisal;
    actionData.value = { 
        status: appraisal.status === 'pending' ? 'approved' : appraisal.status, 
        hr_comments: appraisal.hr_comments || '',
        overall_rating: appraisal.overall_rating || 4.0
    };
    showActionModal.value = true;
};

const submitAction = async () => {
    try {
        const response = await axios.patch(`pms/appraisals/${selectedAppraisal.value.id}/review`, actionData.value);
        if (response.data.status === 'success') {
            showAlert('Success', 'Review updated successfully!', 'success');
            showActionModal.value = false;
            fetchSubmissions();
        }
    } catch (error) {
        console.error('Error submitting review:', error);
        showAlert('Error', 'Failed to update review.', 'error');
    }
};

onMounted(() => {
    fetchSubmissions();
});
</script>

<template>
    <div class="submissions-container">
        <!-- Page Header -->
        <!-- Page Header -->
        <div class="mb-xl">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-indigo-600">HR Submissions</h1>
                    <p class="text-gray-500 font-medium mt-1">Review and evaluate employee performance appraisals</p>
                </div>
                <div class="flex items-center gap-md">
                    <div class="relative">
                        <select v-model="currentYear" @change="fetchSubmissions" class="appearance-none bg-purple-50 border border-purple-100 text-purple-700 font-bold py-2.5 pl-4 pr-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer shadow-sm hover:shadow-md transition-all">
                            <option :value="2025">2025 Cycle</option>
                            <option :value="2026">2026 Cycle</option>
                        </select>
                        <i class="pi pi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-purple-400 font-bold pointer-events-none"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-purple-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-purple-500/20"
                 style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-inbox text-xl"></i>
                    <span class="text-sm font-bold uppercase tracking-wider">Total Submissions</span>
                </div>
                <div class="text-4xl font-extrabold mb-1">{{ stats.total }}</div>
                <div class="text-xs font-medium opacity-80">Records for {{ currentYear }}</div>
            </div>

            <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-orange-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-orange-500/20"
                 style="background: linear-gradient(135deg, #ea580c 0%, #991b1b 100%) !important;">
                 <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-clock text-xl"></i>
                    <span class="text-sm font-bold uppercase tracking-wider">Pending Review</span>
                </div>
                <div class="text-4xl font-extrabold mb-1">{{ stats.pending }}</div>
                <div class="text-xs font-medium opacity-80">Requiring Your Action</div>
            </div>

            <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-teal-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-teal-500/20"
                 style="background: linear-gradient(135deg, #0f766e 0%, #064e3b 100%) !important;">
                 <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-check-circle text-xl"></i>
                    <span class="text-sm font-bold uppercase tracking-wider">Approved</span>
                </div>
                <div class="text-4xl font-extrabold mb-1">{{ stats.approved }}</div>
                <div class="text-xs font-medium opacity-80">Evaluation Finalized</div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div>
            <!-- Filters Toolbar -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <h2 class="text-xl font-bold text-gray-800">Appraisal Records</h2>
                
                <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                    <!-- Search -->
                    <div class="relative w-full md:w-64">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search employee..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all text-sm font-medium" />
                    </div>

                    <!-- Department Filter -->
                     <select v-model="filterDepartment" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer hover:border-purple-300 transition-colors">
                        <option value="">All Departments</option>
                        <option value="HR">HR</option>
                        <option value="IT">IT</option>
                        <option value="Sales">Sales</option>
                        <option value="Operations">Operations</option>
                        <option value="Finance">Finance</option>
                    </select>

                    <!-- Status Filter -->
                    <div class="flex bg-white p-1 rounded-xl shadow-sm border border-gray-200">
                        <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-purple-700 text-white shadow-md hover:shadow-lg' : 'bg-gray-400 text-white hover:bg-gray-500'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all">All</button>
                        <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-orange-700 text-white shadow-md hover:shadow-lg' : 'bg-gray-400 text-white hover:bg-gray-500'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all">Pending</button>
                        <button @click="filterStatus = 'approved'" :class="filterStatus === 'approved' ? 'bg-teal-700 text-white shadow-md hover:shadow-lg' : 'bg-gray-400 text-white hover:bg-gray-500'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all">Approved</button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-4 text-gray-500 font-medium animate-pulse">Retrieving submissions...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredSubmissions.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100 dashed-border">
                  <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                    <i class="pi pi-folder-open text-4xl"></i>
                  </div>
                  <h3 class="text-xl font-bold text-gray-800">No submissions found</h3>
                  <p class="text-gray-500 mt-1">Try adjusting your filters or cycle year.</p>
            </div>

            <!-- List View (Table Layout) -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                                <th class="px-6 py-4">Employee</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Submission Date</th>
                                <th class="px-6 py-4">Rating</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in filteredSubmissions" :key="item.id" class="group hover:bg-purple-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full border border-gray-100 shadow-sm overflow-hidden flex-shrink-0">
                                            <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(item.user?.name || 'User')+'&background=7c3aed&color=fff'" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 group-hover:text-purple-700 transition-colors">{{ item.user?.name }}</h4>
                                            <p class="text-xs text-gray-500">{{ item.user?.job_title || 'Employee' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 text-gray-600 text-xs font-bold border border-gray-100">
                                        <i class="pi pi-building text-[10px] text-gray-400"></i>
                                        {{ item.user?.department || 'General' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
                                        <i class="pi pi-calendar text-gray-400"></i>
                                        {{ formatDate(item.submitted_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 font-bold text-gray-700">
                                        <i class="pi pi-star-fill text-amber-400"></i>
                                        {{ item.overall_rating || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border inline-flex items-center gap-1.5"
                                        :class="item.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (item.status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-red-50 text-red-700 border-red-100')">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="item.status === 'approved' ? 'bg-emerald-500' : (item.status === 'pending' ? 'bg-amber-500' : 'bg-red-500')"></span>
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openActionModal(item)" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all shadow-sm hover:shadow-md text-xs">
                                        <i class="pi pi-file-edit"></i>
                                        Review
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Review Dialog using PrimeVue Component -->
        <Dialog v-model:visible="showActionModal" modal :style="{ width: '850px', maxWidth: '90vw', borderRadius: '0.75rem', overflow: 'hidden' }" :showHeader="false" class="p-0 custom-modal" :contentStyle="{ padding: '0', backgroundColor: 'white' }">
            <div v-if="selectedAppraisal" class="flex flex-col md:flex-row h-full bg-white relative min-h-[500px]">
                
                <!-- Left Sidebar: Profile & Context -->
                <div class="w-full md:w-1/3 bg-slate-900 text-white p-6 flex flex-col justify-between relative overflow-hidden">
                    <!-- Background blobs for subtle effect -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-purple-600 rounded-full opacity-20 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-indigo-600 rounded-full opacity-20 blur-3xl"></div>

                    <div class="relative z-10">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Appraisal Review</h2>
                        
                        <div class="flex flex-col items-center text-center">
                            <div class="w-24 h-24 rounded-full border-4 border-slate-700 shadow-xl overflow-hidden mb-4 relative group">
                                <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(selectedAppraisal.user.name)+'&background=6366f1&color=fff'" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                            </div>
                            <h3 class="text-xl font-bold text-white mb-1">{{ selectedAppraisal.user.name }}</h3>
                            <p class="text-sm text-slate-400 font-medium mb-1">{{ selectedAppraisal.user.job_title || 'Employee' }}</p>
                            <span class="inline-block px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-bold border border-slate-700 mt-2">
                                {{ selectedAppraisal.user.department || 'General' }}
                            </span>
                        </div>

                        <div class="mt-8 space-y-4">
                            <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/50 backdrop-blur-sm">
                                <div class="text-slate-400 text-xs uppercase font-bold mb-1">Cycle Year</div>
                                <div class="text-white font-mono font-bold text-lg">{{ selectedAppraisal.year }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/50 backdrop-blur-sm">
                                <div class="text-slate-400 text-xs uppercase font-bold mb-1">Submitted On</div>
                                <div class="text-white font-medium text-sm">{{ formatDate(selectedAppraisal.submitted_at) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 mt-auto pt-8 text-center">
                        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Confidential Evaluation</p>
                    </div>
                </div>

                <!-- Right Content: Form -->
                <div class="w-full md:w-2/3 bg-white p-8 flex flex-col h-full">
                    
                    <div class="flex justify-between items-center mb-8 pb-4 border-b border-gray-100">
                        <div>
                             <h2 class="text-2xl font-bold text-gray-900">Final Determination</h2>
                             <p class="text-sm text-gray-500 mt-1">Review the appraisal and provide your final decision.</p>
                        </div>
                        <button @click="showActionModal = false" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 text-gray-400 hover:text-gray-600 flex items-center justify-center transition-colors">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <div class="space-y-8 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                        <!-- Status Selection (Segmented Control Style) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-900 uppercase tracking-wide mb-3">Decision Status</label>
                            <div class="flex bg-gray-100 p-1.5 rounded-xl">
                                <button v-for="status in ['started', 'approved', 'rejected']" :key="status"
                                    @click="actionData.status = status"
                                    class="flex-1 py-3 px-4 rounded-lg text-sm font-bold transition-all duration-200 capitalize flex items-center justify-center gap-2 text-white"
                                    :class="actionData.status === status ? 
                                        (status === 'approved' ? 'bg-emerald-600 text-white shadow-md' : 
                                        (status === 'rejected' ? 'bg-rose-600 text-white shadow-md' : 
                                        'bg-blue-600 text-white shadow-md')) : 
                                        'bg-gray-400 text-white hover:bg-gray-500 shadow-sm'">
                                    
                                    <i class="pi" :class="status === 'approved' ? 'pi-check-circle' : (status === 'rejected' ? 'pi-times-circle' : 'pi-eye')"></i>
                                    {{ status === 'started' ? 'Reviewing' : status }}
                                </button>
                            </div>
                        </div>

                        <!-- Rating Input -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                             <div>
                                <label class="block text-xs font-bold text-gray-900 uppercase tracking-wide mb-3">Performance Score</label>
                                <div class="flex items-center gap-4">
                                    <div class="relative w-full h-12 bg-gray-100 rounded-xl overflow-hidden">
                                        <div class="absolute top-0 left-0 h-full bg-indigo-600 transition-all duration-300" :style="{ width: (actionData.overall_rating / 5) * 100 + '%' }"></div>
                                        <input type="range" min="1" max="5" step="0.1" v-model="actionData.overall_rating" 
                                            class="absolute w-full h-full opacity-0 cursor-pointer" />
                                    </div>
                                    <div class="flex flex-col items-center justify-center w-16 h-12 bg-white border-2 border-indigo-100 text-indigo-700 font-extrabold text-lg rounded-xl shadow-sm">
                                        {{ actionData.overall_rating }}
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                    <span>Poor</span>
                                    <span>Meets</span>
                                    <span>Excellent</span>
                                </div>
                             </div>

                             <!-- Dynamic Feedback Label based on Score -->
                             <div class="flex items-center">
                                 <div class="flex items-start gap-3 p-3 bg-indigo-50 border border-indigo-100 rounded-xl w-full">
                                     <i class="pi pi-info-circle text-indigo-600 text-lg mt-0.5"></i>
                                     <div>
                                         <span class="text-xs font-bold text-indigo-800 uppercase block mb-0.5">Rating Guide</span>
                                         <p class="text-xs text-indigo-600 leading-relaxed font-medium">
                                             {{ actionData.overall_rating >= 4.5 ? 'Exceptional performance exceeding all expectations.' : 
                                                (actionData.overall_rating >= 3.5 ? 'Solid performance meeting most requirements.' : 
                                                'Performance needs improvement in key areas.') }}
                                         </p>
                                     </div>
                                 </div>
                             </div>
                        </div>

                        <!-- Comments -->
                        <div>
                             <label class="block text-xs font-bold text-gray-900 uppercase tracking-wide mb-2">Manager's Remarks</label>
                             <div class="relative">
                                 <textarea v-model="actionData.hr_comments" rows="4" 
                                    class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-800 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder:text-gray-400 resize-none"
                                    placeholder="Enter your formal evaluation comments here..."></textarea>
                                 <div class="absolute bottom-3 right-3 text-[10px] font-bold text-gray-400 pointer-events-none">Visible to Employee</div>
                             </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-6 mt-2 border-t border-gray-100 flex justify-end gap-3">
                        <button class="px-6 py-3 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors text-sm" @click="showActionModal = false">Cancel</button>
                        <button class="px-8 py-3 bg-slate-900 hover:bg-black text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all text-sm flex items-center gap-2" @click="submitAction">
                            <i class="pi pi-check"></i>
                            Confirm Decision
                        </button>
                    </div>

                </div>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.submissions-container {
    padding-bottom: 2rem;
}

.bg-white { background-color: var(--color-surface); }
.bg-lighter { background-color: var(--color-lighter); }
.border-light { border-color: var(--color-light); }
.text-primary { color: var(--color-primary); }
.text-secondary { color: var(--text-secondary); }
.text-warning { color: var(--color-warning); }
.bg-warning\/10 { background-color: rgba(245, 158, 11, 0.1); }

/* Slider Styling for Webkit */
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #7c3aed;
  cursor: pointer;
  margin-top: -6px; 
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
}

input[type=range]::-webkit-slider-runnable-track {
  width: 100%;
  height: 8px;
  cursor: pointer;
  background: #e2e8f0;
  border-radius: 4px;
}
</style>
