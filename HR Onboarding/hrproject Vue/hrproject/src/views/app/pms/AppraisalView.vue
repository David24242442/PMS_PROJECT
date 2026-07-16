<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, showConfirm } from '@/helpers/essential';
import AutoComplete from 'primevue/autocomplete';

const userstore = useUsersStore();
const { loguser } = userstore;

const currentYear = ref(new Date().getFullYear());
const years = range(currentYear.value, currentYear.value - 5);
const loading = ref(true);
const saving = ref(false);
const currentStep = ref(1);
const totalSteps = 2;
const route = useRoute();
const router = useRouter();
const goalId = ref(route.query.goal_id || null);
const goalStatus = ref('');

// Read-only when appraisal is submitted (completed) and review is done
const isReadOnly = computed(() => {
    return ['review_completed', 'completed'].includes(goalStatus.value);
});

// Master Employee Data for Dropdown (for managers starting new appraisals)
const masterEmployees = ref([]);
const filteredMasterEmployees = ref([]);
const selectedCandidate = ref(null);
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });

function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

// Performance Key Competencies (matching reference image)
const performanceCompetencies = ref([
    { id: 1, title: 'Quality of Work', weight: 15, selfRating: 0, managerRating: 0, descriptions: ['Accuracy, thoroughness and reliability of work produced', 'Neatness and appearance of work'], descriptionText: 'Accuracy, thoroughness and reliability of work produced\nNeatness and appearance of work' },
    { id: 2, title: 'Quantity of Work', weight: 15, selfRating: 0, managerRating: 0, descriptions: ['Amount of work produced', 'Promptness in completing assignments'], descriptionText: 'Amount of work produced\nPromptness in completing assignments' },
    { id: 3, title: 'Job Knowledge', weight: 15, selfRating: 0, managerRating: 0, descriptions: ['Understanding of job duties', 'Technical skills and competence'], descriptionText: 'Understanding of job duties\nTechnical skills and competence' },
    { id: 4, title: 'Dependability', weight: 15, selfRating: 0, managerRating: 0, descriptions: ['Attendance and punctuality', 'Reliability and responsibility'], descriptionText: 'Attendance and punctuality\nReliability and responsibility' },
    { id: 5, title: 'Cooperation', weight: 10, selfRating: 0, managerRating: 0, descriptions: ['Ability to work with others', 'Attitude towards supervision'], descriptionText: 'Ability to work with others\nAttitude towards supervision' },
    { id: 6, title: 'Adaptability', weight: 10, selfRating: 0, managerRating: 0, descriptions: ['Ability to learn new tasks', 'Versatility'], descriptionText: 'Ability to learn new tasks\nVersatility' },
    { id: 7, title: 'Initiative', weight: 10, selfRating: 0, managerRating: 0, descriptions: ['Self-starting ability', 'Resourcefulness'], descriptionText: 'Self-starting ability\nResourcefulness' },
    { id: 8, title: 'Judgment', weight: 10, selfRating: 0, managerRating: 0, descriptions: ['Ability to make sound decisions', 'Common sense'], descriptionText: 'Ability to make sound decisions\nCommon sense' }
]);

// Performance Rating Scale
const performanceRatings = [
    { value: 5, label: '5-Out Standing / Exceptional' },
    { value: 4, label: '4-Exceeding Expectations' },
    { value: 3, label: '3-Meeting Expectations' },
    { value: 2, label: '2-Partly Meeting Expectations' },
    { value: 1, label: '1-Below Expectations/ Unsatisfactory' }
];

const appraisal = ref({
    status: 'draft',
    comments: '',
    impressedMost: '',
    impressedLeast: '',
    performanceRating: 0,
    potentialRating: '',
    performanceComments: '',
    potentialComments: '',
    candidate_name: '',
    employee_code: '', // Sync from goal
    job_title: '',     // Sync from goal
    department: '',   // Sync from goal
    location: '',     // Sync from goal
    manager_name: '', // Sync from goal
    candidate_signature_name: '',
    manager_signature_name: '',
    signature_date: new Date().toISOString().split('T')[0],
    hod_comments: '',
    hod_signature_name: '',
    hod_signature_date: '',
    director_remarks: '',
    director_signature_name: '',
    director_signature_date: ''
});

// Computed
const overallPerformanceRating = computed(() => {
    const comps = performanceCompetencies.value;
    if (!comps || comps.length === 0) return (0.00).toFixed(2);
    // Use managerRating if available, otherwise selfRating
    const hasManager = comps.some(c => parseFloat(c.managerRating) > 0);
    const total = comps.reduce((sum, c) => {
        const rating = hasManager ? (parseFloat(c.managerRating) || 0) : (parseFloat(c.selfRating) || 0);
        return sum + rating;
    }, 0);
    return comps.length > 0 ? (total / comps.length).toFixed(2) : (0.00).toFixed(2);
});

const canProceedToStep2 = computed(() => {
    return performanceCompetencies.value.every(comp => comp.selfRating > 0);
});

// Methods
const nextStep = async () => {
    if (isReadOnly.value) {
        if (currentStep.value < totalSteps) currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    const isCompleted = canProceedToStep2.value;
    const message = isCompleted
        ? 'I have completed the rating, do I still want to continue?'
        : 'Self Rating Not Completed. Do you still want to Continue?';

    const confirm = await showConfirm('Step Confirmation', message, 'question');
    if (confirm.isConfirmed) {
        if (currentStep.value < totalSteps) currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const getWeightedScore = (comp) => {
    const hasManager = parseFloat(comp.managerRating) > 0;
    const rating = hasManager ? (parseFloat(comp.managerRating) || 0) : (parseFloat(comp.selfRating) || 0);
    return ((rating * (comp.weight || 0)) / 100).toFixed(2);
};

const fetchAppraisal = async () => {
    loading.value = true;
    try {
        const params = goalId.value ? { goal_id: goalId.value } : { year: currentYear.value, user_id: loguser.id };
        const response = await axios.get('pms/appraisals', { params });

        if (response.data.status === 'success') {
            const result = response.data.data;
            const goal = result.goal;
            const appraisalData = result.appraisal_data;
            
            if (goal) {
                goalId.value = goal.id;
                goalStatus.value = goal.status || '';
                // 1. Populate Profile Info from the Goal record (Always present)
                appraisal.value.candidate_name = goal.candidate_name || '';
                appraisal.value.employee_code = goal.employee_code || '';
                appraisal.value.job_title = goal.job_title || goal.department || '';
                appraisal.value.department = goal.department || '';
                appraisal.value.location = goal.location || '';
                appraisal.value.manager_name = goal.manager_name || '';
                
                // If it's a new appraisal for this goal, we might also want to set some defaults
                if (!appraisal.value.manager_signature_name) {
                    appraisal.value.manager_signature_name = appraisal.value.manager_name || loguser.name || '';
                }

                // 2. Populate Appraisal Ratings/Comments from appraisal_data if it exists
                if (appraisalData) {
                    appraisal.value = { 
                        ...appraisal.value, 
                        comments: appraisalData.comments || '',
                        impressedMost: appraisalData.impressedMost || '',
                        impressedLeast: appraisalData.impressedLeast || '',
                        performanceRating: appraisalData.performanceRating || 0,
                        performanceComments: appraisalData.performanceComments || '',
                        potentialRating: appraisalData.potentialRating || '',
                        candidate_signature_name: appraisalData.candidate_signature_name || '',
                        manager_signature_name: appraisalData.manager_signature_name || appraisal.value.manager_name || loguser.name || '',
                        signature_date: appraisalData.signature_date || new Date().toISOString().split('T')[0],
                        hod_comments: appraisalData.hod_comments || '',
                        hod_signature_name: appraisalData.hod_signature_name || '',
                        hod_signature_date: appraisalData.hod_signature_date || '',
                        director_remarks: appraisalData.director_remarks || '',
                        director_signature_name: appraisalData.director_signature_name || '',
                        director_signature_date: appraisalData.director_signature_date || ''
                    };
                    
                    if (appraisalData.competencies) {
                        performanceCompetencies.value = appraisalData.competencies.map(c => ({
                            ...c,
                            descriptionText: c.descriptions ? c.descriptions.join('\n') : ''
                        }));
                    }
                }
            }
        }
    } catch (e) {
        console.log('Error fetching appraisal:', e);
    } finally {
        loading.value = false;
    }
};

const fetchMasterEmployees = async () => {
    try {
        const response = await axios.get('pms/get-employees');
        if (response.data.status === 'success') {
            masterEmployees.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching master employees:', error);
    }
};

const searchCandidate = (event) => {
    const query = event.query.toLowerCase();
    filteredMasterEmployees.value = masterEmployees.value.filter(emp => 
        (emp.employee_code && emp.employee_code.toLowerCase().includes(query)) || 
        emp.name.toLowerCase().includes(query)
    );
};

const onCandidateSelect = (event) => {
    const candidate = event.value;
    appraisal.value.candidate_name = candidate.name;
    // Potentially load existing goal for this user/year if it exists
};

const saveAppraisal = async (submit = false) => {
    if (submit) {
        const confirm = await showConfirm('Complete Assessment', 'Are you sure you want to finalize this appraisal?', 'question');
        if (!confirm.isConfirmed) return;
    }

    saving.value = true;
    try {
        // Build a clean appraisal_data payload (only assessment-relevant fields)
        const appraisalData = {
            comments: appraisal.value.comments,
            impressedMost: appraisal.value.impressedMost,
            impressedLeast: appraisal.value.impressedLeast,
            performanceRating: appraisal.value.performanceRating,
            performanceComments: appraisal.value.performanceComments,
            potentialRating: appraisal.value.potentialRating,
            potentialComments: appraisal.value.potentialComments,
            candidate_signature_name: appraisal.value.candidate_signature_name,
            manager_signature_name: appraisal.value.manager_signature_name,
            signature_date: appraisal.value.signature_date,
            hod_comments: appraisal.value.hod_comments,
            hod_signature_name: appraisal.value.hod_signature_name,
            hod_signature_date: appraisal.value.hod_signature_date,
            director_remarks: appraisal.value.director_remarks,
            director_signature_name: appraisal.value.director_signature_name,
            director_signature_date: appraisal.value.director_signature_date,
            competencies: performanceCompetencies.value.map(c => ({
                id: c.id,
                title: c.title,
                weight: c.weight,
                selfRating: c.selfRating,
                managerRating: c.managerRating,
                descriptions: c.descriptions
            })),
            overallPerformanceRating: overallPerformanceRating.value
        };

        const payload = {
            appraisal_data: appraisalData,
            status: submit ? 'completed' : 'draft'
        };

        // Correct usage: appraisals are stored in goals table via appraisal_data column
        const response = await axios.patch(`pms/goals/${goalId.value}`, payload);
        if (response.data.status === 'success') {
            const alertResult = await showAlert('Success', submit ? 'Appraisal completed successfully!' : 'Draft saved successfully!', 'success');
            if (submit) {
                // Use Vue Router instead of hard redirect to prevent connection reset
                router.push('/pms/goals');
            }
        }
    } catch (error) {
        console.error('Error saving:', error);
        showAlert('Error', 'Failed to save appraisal assessments. Please try again.', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchAppraisal();
    fetchMasterEmployees();
});
</script>

<template>
    <div class="h-full">
        <!-- Page Header with Stepper -->
        <div class="prof-header sticky top-0 z-10 mb-8 shadow-sm">
            <div class="px-8 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50/10 flex items-center justify-center shadow-sm border border-white/20">
                            <i class="pi pi-file-edit text-2xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-white tracking-normal">Performance Assessment</h1>
                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-normal mt-1">Step {{ currentStep }} of {{ totalSteps }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <!-- Step Indicators -->
                        <div class="hidden md:flex items-center gap-4">
                            <div class="flex items-center gap-3">
                                <div :class="currentStep >= 1 ? 'step-active' : 'step-inactive'"
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs shadow-sm border transition-all">1</div>
                                <span class="text-gray-500 font-bold text-[10px] uppercase tracking-widest">Competencies</span>
                            </div>
                            <div class="w-8 h-px bg-gray-200"></div>
                            <div class="flex items-center gap-3">
                                <div :class="currentStep >= 2 ? 'step-active' : 'step-inactive'"
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs shadow-sm border transition-all">2</div>
                                <span class="text-gray-500 font-bold text-[10px] uppercase tracking-widest">Ratings</span>
                            </div>
                        </div>
                        <select v-model="currentYear" @change="fetchAppraisal" class="prof-input font-bold !py-2 !px-4 !bg-gray-50">
                            <option v-for="year in years" :key="year" :value="year">FY {{ year }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

            <!-- Read-Only Banner -->
            <div v-if="isReadOnly" class="mx-8 mb-6 bg-amber-50 border border-amber-200 rounded-2xl px-6 py-4 flex items-center gap-3">
                <i class="pi pi-lock text-amber-600 text-lg"></i>
                <div>
                    <p class="font-black text-amber-800 text-sm">This appraisal is locked</p>
                    <p class="text-[10px] text-amber-600 font-bold uppercase tracking-wider">The review has been completed and submitted. No further edits are allowed.</p>
                </div>
            </div>

            <fieldset :disabled="isReadOnly" :class="{ 'opacity-80': isReadOnly }" class="border-none p-0 m-0">

            <!-- STEP 1: Performance Key Competencies -->
            <div v-if="currentStep === 1" class="space-y-6 animate-fadeIn pb-16">
                <!-- 0. Employee Profile Summary (Moved from Step 2) -->
                <div class="prof-card mx-8 overflow-hidden bg-white shadow-xl shadow-indigo-900/5 mb-8 border-t-4 border-t-indigo-600">
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-12 gap-y-8 bg-slate-50/30">
                        <div class="space-y-4">
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1 border-l-2 border-indigo-500 pl-2">Candidate Name</p>
                            <p class="text-[13px] font-black text-gray-800 uppercase tracking-tight">{{ appraisal.candidate_name }}</p>
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mt-4 mb-1 border-l-2 border-slate-200 pl-2">Assessment Date</p>
                            <p class="text-[11px] font-black text-slate-600 uppercase">{{ new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                        </div>
                        <div class="space-y-4">
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1 border-l-2 border-slate-200 pl-2">Employee ID</p>
                            <p class="text-[13px] font-black text-gray-700 uppercase leading-tight">{{ appraisal.employee_code || 'N/A' }}</p>
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mt-4 mb-1 border-l-2 border-slate-200 pl-2">Line Manager</p>
                            <p class="text-[11px] font-black text-slate-600 uppercase">{{ appraisal.manager_name || loguser?.name || 'Manager Name' }}</p>
                        </div>
                        <div class="space-y-4">
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1 border-l-2 border-slate-200 pl-2">Job Description</p>
                            <p class="text-[13px] font-black text-gray-700 uppercase leading-tight">{{ appraisal.job_title || 'N/A' }}</p>
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mt-4 mb-1 border-l-2 border-slate-200 pl-2">Division / Branch</p>
                            <p class="text-[11px] font-black text-slate-600 uppercase">{{ appraisal.department }} / {{ appraisal.location }}</p>
                        </div>
                        <div class="flex flex-col justify-center items-center lg:items-end p-6 bg-white rounded-3xl border border-indigo-50 shadow-inner">
                            <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1">Overall Assessment Score</p>
                            <div class="flex items-baseline justify-end gap-1">
                                <span class="text-5xl font-black text-indigo-600 tracking-tighter">{{ overallPerformanceRating }}</span>
                                <span class="text-[12px] font-bold text-indigo-200">/ 5.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Performance Competencies Table -->
                <div class="prof-card mx-8 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-black text-gray-800 text-xl flex items-center gap-3">
                            <i class="pi pi-chart-bar text-indigo-600"></i> Performance Key Competencies
                        </h3>
                        <p class="text-gray-400 text-[10px] mt-1 uppercase tracking-normal font-black">Annual performance evaluation scale</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-normal w-12">#</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-normal">Competency</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-normal w-24">Weight</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-indigo-600 uppercase tracking-normal w-32 bg-indigo-50/50">Self Rating</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-teal-600 uppercase tracking-normal w-40 bg-teal-50/50">Manager Rating</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-normal w-28">W. Score</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(comp, index) in performanceCompetencies" :key="comp.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-6 font-black text-gray-300">0{{ index + 1 }}</td>
                                    <td class="px-6 py-6">
                                        <input v-model="comp.title" class="w-full font-black text-gray-800 text-lg mb-2 tracking-normal bg-transparent border-b border-transparent focus:border-indigo-300 outline-none px-3" placeholder="Competency Title" />
                                        <textarea v-model="comp.descriptionText" 
                                            @input="comp.descriptions = comp.descriptionText.split('\n')"
                                            class="w-full text-xs text-gray-500 leading-relaxed font-bold bg-transparent border-transparent focus:border-indigo-100 outline-none resize-none px-3 py-2" 
                                            rows="3" 
                                            placeholder="Competency descriptions (one per line)">
                                        </textarea>
                                    </td>
                                    <td class="px-6 py-6 text-center font-black text-gray-600">{{ comp.weight }}%</td>
                                    <td class="px-6 py-6 bg-indigo-50/50">
                                        <select v-model="comp.selfRating" class="prof-input !py-2 !px-3 w-full !text-center font-black">
                                            <option :value="0">—</option>
                                            <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-6 bg-teal-50/50">
                                        <div class="flex items-center justify-center">
                                            <select v-model="comp.managerRating" disabled class="prof-input !py-2 !px-3 w-full !text-center font-black !border-teal-200 opacity-50 cursor-not-allowed">
                                                <option :value="0">—</option>
                                                <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 text-center font-black text-indigo-600 text-lg">{{ getWeightedScore(comp) }}%</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t border-gray-100">
                                <tr>
                                    <td colspan="2" class="px-6 py-8 font-black text-gray-400 tracking-normal text-xs">ANNUAL PERFORMANCE SUMMARY</td>
                                    <td colspan="2" class="px-6 py-8 text-center font-black text-gray-400 uppercase tracking-normal text-[10px]">OVERALL SCORE</td>
                                    <td class="px-6 py-8 text-center font-black text-gray-400">100%</td>
                                    <td colspan="2" class="px-6 py-8 text-center">
                                        <span class="font-black text-indigo-600 text-4xl">{{ overallPerformanceRating }}</span>
                                        <span class="text-xl font-bold text-indigo-300 ml-1">/ 5.00</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                    <!-- Comments Section -->
                    <div class="prof-card mx-8 p-8 mt-6">
                        <h4 class="font-black text-gray-800 text-lg mb-4 flex items-center gap-3">
                            <i class="pi pi-comment text-gray-300"></i> Additional Comments
                        </h4>
                        <textarea v-model="appraisal.comments" rows="4" class="prof-input !bg-gray-50" 
                            placeholder="Add any additional comments here..."></textarea>
                    </div>
                </div>
            
            <!-- STEP 2: Ratings & Feedback (Modern Professional Layout) -->
            <div v-if="currentStep === 2" class="mx-8 space-y-8 animate-fadeIn pb-16">
                
                <!-- ─── 1. Interpersonal Impressions Section ─── -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Most Impressed -->
                    <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm overflow-hidden hover:shadow-md transition-all">
                        <div class="bg-emerald-600 px-6 py-4 border-b border-emerald-700 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center border border-white/10">
                                <i class="pi pi-thumbs-up text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-sm text-white uppercase tracking-wide">What impressed you the most?</h4>
                                <p class="text-[9px] text-emerald-100 font-bold uppercase tracking-widest opacity-80">Positive performance highlights</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <textarea v-model="appraisal.impressedMost" rows="5" 
                                class="w-full bg-emerald-50/30 border border-emerald-100 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/20 outline-none resize-none transition-all" 
                                placeholder="Describe the key strengths and achievements that stood out..."></textarea>
                        </div>
                    </div>

                    <!-- Least Impressed -->
                    <div class="bg-white rounded-2xl border border-orange-200 shadow-sm overflow-hidden hover:shadow-md transition-all">
                        <div class="bg-orange-500 px-6 py-4 border-b border-orange-600 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center border border-white/10">
                                <i class="pi pi-exclamation-triangle text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-sm text-white uppercase tracking-wide">What impressed you the least?</h4>
                                <p class="text-[9px] text-orange-50 font-bold uppercase tracking-widest opacity-80">Area of improvement</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <textarea v-model="appraisal.impressedLeast" rows="5" 
                                class="w-full bg-orange-50/30 border border-orange-100 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 focus:border-orange-400 focus:ring-1 focus:ring-orange-400/20 outline-none resize-none transition-all" 
                                placeholder="Describe areas needing development and improvement..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- ─── 2. Performance Rating Matrix ─── -->
                <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm overflow-hidden">
                    <div class="bg-[#1A237E] px-6 py-4 border-b border-[#0D1559] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center border border-white/10">
                                <i class="pi pi-star-fill text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-base text-white uppercase tracking-wide">Performance Rating Matrix</h4>
                                <p class="text-[9px] text-indigo-200 font-bold uppercase tracking-widest opacity-80">Select the most accurate rating for this period</p>
                            </div>
                        </div>
                        <span class="px-4 py-1.5 bg-white/10 text-white text-[9px] font-black uppercase tracking-widest rounded-lg border border-white/10">Scale 1-5</span>
                    </div>

                    <div class="p-6 space-y-3">
                        <div v-for="rating in performanceRatings" :key="rating.value"
                            @click="!isReadOnly && (appraisal.performanceRating = rating.value)"
                            class="group rounded-2xl border p-5 transition-all duration-300"
                            :class="[
                                isReadOnly ? 'cursor-default' : 'cursor-pointer',
                                appraisal.performanceRating === rating.value
                                    ? 'bg-indigo-50/70 border-indigo-300 shadow-md shadow-indigo-100/50 ring-2 ring-indigo-200/50'
                                    : 'bg-slate-50/40 border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/20'
                            ]">
                            <div class="flex flex-col md:flex-row md:items-center gap-4">
                                <!-- Radio + Label -->
                                <div class="flex items-center gap-3 md:w-[280px] shrink-0">
                                    <div :class="appraisal.performanceRating === rating.value ? 'bg-indigo-600 border-indigo-600 shadow-lg shadow-indigo-600/30' : 'bg-white border-slate-200 group-hover:border-indigo-300'"
                                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all shrink-0">
                                        <div v-show="appraisal.performanceRating === rating.value" class="w-2 h-2 rounded-full bg-white"></div>
                                    </div>
                                    <span class="text-sm font-black tracking-tight" :class="appraisal.performanceRating === rating.value ? 'text-indigo-900' : 'text-slate-600'">{{ rating.label }}</span>
                                </div>
                                <!-- Score Badge -->
                                <div class="shrink-0">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl font-black text-base transition-all"
                                        :class="appraisal.performanceRating === rating.value ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-100 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-400'">
                                        {{ rating.value }}
                                    </span>
                                </div>
                                <!-- Comments -->
                                <div class="flex-1 transition-all" :class="appraisal.performanceRating === rating.value ? 'opacity-100' : 'opacity-30 group-hover:opacity-50'">
                                    <label class="block text-[9px] font-black uppercase tracking-wider mb-1" 
                                        :class="appraisal.performanceRating === rating.value ? 'text-indigo-500' : 'text-slate-400'">Comments</label>
                                    <textarea v-model="appraisal.performanceComments" 
                                        :disabled="appraisal.performanceRating !== rating.value"
                                        rows="2" 
                                        class="w-full bg-white border rounded-xl py-2.5 px-4 text-sm font-medium text-slate-700 outline-none resize-none transition-all"
                                        :class="appraisal.performanceRating === rating.value ? 'border-indigo-200 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400/20' : 'border-slate-100'"
                                        placeholder="Enter specific comments for this rating..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════ -->
                <!-- AUTHORIZATION SIGN-OFF SECTION                        -->
                <!-- ═══════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-[#1A237E] px-6 py-4 border-b border-[#0D1559] flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center border border-white/10">
                            <i class="pi pi-verified text-white text-sm"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-base text-white uppercase tracking-wide">Authorization &amp; Sign-Off</h4>
                            <p class="text-[9px] text-indigo-200 font-bold uppercase tracking-widest opacity-80">Management Endorsement &amp; Final Approval</p>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">

                        <!-- ── 1. Line Manager's Name & Signature ── -->
                        <div class="bg-slate-50/70 rounded-2xl border border-slate-100 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-7 h-7 rounded-lg bg-[#1A237E] text-white flex items-center justify-center font-black text-[10px]">1</span>
                                <h5 class="font-black text-sm text-[#1A237E] uppercase tracking-wide">Line Manager's Name &amp; Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Signature</label>
                                    <input v-model="appraisal.manager_signature_name" type="text"
                                        class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/20 outline-none transition-all"
                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.15rem" placeholder="Type full name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                                    <input v-model="appraisal.signature_date" type="date"
                                        class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/20 outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                        <!-- ── 2. HOD / Functional Head's Comments ── -->
                        <div class="bg-blue-50/40 rounded-2xl border border-blue-100/80 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-7 h-7 rounded-lg bg-blue-700 text-white flex items-center justify-center font-black text-[10px]">2</span>
                                <h5 class="font-black text-sm text-blue-800 uppercase tracking-wide">HOD / Functional Head's Comments</h5>
                            </div>
                            <textarea v-model="appraisal.hod_comments" rows="3"
                                class="w-full bg-white border border-blue-200/60 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none resize-none transition-all"
                                placeholder="HOD / Functional Head's overall assessment and comments..."></textarea>
                        </div>

                        <!-- ── 3. HOD / Functional Head's Name, Designation & Signature ── -->
                        <div class="bg-blue-50/40 rounded-2xl border border-blue-100/80 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-7 h-7 rounded-lg bg-blue-700 text-white flex items-center justify-center font-black text-[10px]">3</span>
                                <h5 class="font-black text-sm text-blue-800 uppercase tracking-wide">HOD / Functional Head's Name, Designation &amp; Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Signature</label>
                                    <input v-model="appraisal.hod_signature_name" type="text"
                                        class="w-full bg-white border border-blue-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none transition-all"
                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.15rem" placeholder="Type HOD name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                                    <input v-model="appraisal.hod_signature_date" type="date"
                                        class="w-full bg-white border border-blue-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                        <!-- ── 4. Director's Remarks ── -->
                        <div class="bg-amber-50/40 rounded-2xl border border-amber-100/80 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-7 h-7 rounded-lg bg-amber-700 text-white flex items-center justify-center font-black text-[10px]">4</span>
                                <h5 class="font-black text-sm text-amber-800 uppercase tracking-wide">Director's Remarks</h5>
                            </div>
                            <textarea v-model="appraisal.director_remarks" rows="3"
                                class="w-full bg-white border border-amber-200/60 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 outline-none resize-none transition-all"
                                placeholder="Director's final remarks and endorsement notes..."></textarea>
                        </div>

                        <!-- ── 5. Director's Signature ── -->
                        <div class="bg-amber-50/40 rounded-2xl border border-amber-100/80 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-7 h-7 rounded-lg bg-amber-700 text-white flex items-center justify-center font-black text-[10px]">5</span>
                                <h5 class="font-black text-sm text-amber-800 uppercase tracking-wide">Director's Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Signature</label>
                                    <input v-model="appraisal.director_signature_name" type="text"
                                        class="w-full bg-white border border-amber-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 outline-none transition-all"
                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.15rem" placeholder="Type director's name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                                    <input v-model="appraisal.director_signature_date" type="date"
                                        class="w-full bg-white border border-amber-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Final Candidate Acknowledgment Footer -->
                    <div class="bg-[#1A237E] px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-8">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-white border border-white/20 shadow-inner">
                                <i class="pi pi-user text-xl"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-indigo-300 uppercase font-black tracking-widest">Candidate Signature</p>
                                <p class="text-[9px] text-white/40 uppercase mt-0.5 tracking-wider">Final Employee Acknowledgment Protocol</p>
                            </div>
                        </div>
                        <div class="flex-1 max-w-xl">
                            <input v-model="appraisal.candidate_signature_name" type="text" 
                                class="signature-input w-full p-0 bg-transparent border-b-2 border-indigo-400/50 text-white text-4xl focus:border-white outline-none transition-all placeholder:text-indigo-800" 
                                placeholder="Type Full Name to Sign" />
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] text-indigo-300 uppercase font-black tracking-widest mb-1">Acknowledgment Date</p>
                            <p class="text-2xl text-white font-black">{{ appraisal.signature_date }}</p>
                        </div>
                    </div>
                </div>
            </div>

            </fieldset>

            <!-- Footer Navigation -->
            <div class="prof-card p-10 mx-8 mt-12 mb-20 bg-gray-50/50">
                <div class="flex items-center justify-between">
                    <button v-if="currentStep > 1" @click="prevStep"
                        class="prof-button !bg-white !text-gray-600 !border !border-gray-200 !rounded-3xl !py-4 px-10 hover:shadow-md transition-all">
                        <i class="pi pi-arrow-left mr-2 font-bold text-xs"></i>
                        Previous Step
                    </button>
                    <button v-else-if="!isReadOnly" @click="saveAppraisal(false)" :disabled="saving"
                        class="prof-button !bg-white !text-indigo-600 !border !border-indigo-100 !rounded-3xl !py-4 px-10 shadow-sm hover:shadow-md transition-all">
                        <i class="pi pi-save mr-2 font-bold text-xs"></i>
                        Save Progress
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-4">
                        <button v-if="currentStep < totalSteps" @click="nextStep"
                            class="prof-button !bg-indigo-600 !text-white !rounded-3xl !py-4 px-10 shadow-xl shadow-indigo-600/30 hover:scale-[1.02] active:scale-95">
                            Next Stage
                            <i class="pi pi-arrow-right ml-3 text-xs font-bold"></i>
                        </button>

                        <button v-else-if="!isReadOnly" @click="saveAppraisal(true)" :disabled="saving"
                            class="prof-button !bg-teal-600 !text-white !rounded-3xl !py-4 px-10 shadow-xl shadow-teal-600/30 hover:scale-[1.02] active:scale-95">
                            <i v-if="saving" class="pi pi-spin pi-spinner mr-3"></i>
                            <i v-else class="pi pi-check-circle mr-3 font-bold"></i>
                            {{ saving ? 'Submitting...' : 'Complete Appraisal' }}
                        </button>
                    </div>
                </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Homemade+Apple&display=swap');

/* Stepper Styles */
.step-active {
    background-color: var(--prof-primary);
    color: white;
    border-color: var(--prof-primary);
}

.step-inactive {
    background-color: white;
    color: var(--prof-text-muted);
    border-color: var(--prof-border);
}

/* Year Select */
.prof-input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
}

/* Animation */
.animate-spin {
    animation: spin 1s linear infinite;
}

.signature-input {
    font-family: 'Dancing Script', cursive, 'Homemade Apple', serif;
    letter-spacing: -1px;
}

/* Global Heading Style */
.prof-header {
    background-color: #1A237E !important;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Dark Mode */
:global(body.dark-mode) .step-inactive {
    background-color: #1e293b !important;
    color: #94a3b8 !important;
    border-color: #475569 !important;
}
:global(body.dark-mode) .prof-input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.8) !important;
}
</style>
