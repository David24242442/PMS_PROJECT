<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert } from '@/helpers/essential';

const userstore = useUsersStore();
const { loguser } = userstore;

const currentYear = ref(new Date().getFullYear());
const loading = ref(true);
const saving = ref(false);
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });


const goals = ref([]);
const selectedGoal = ref(null);
const viewMode = ref('list'); // 'list' or 'review'
const currentStep = ref(1);
const totalSteps = 5;

// Helpers from GoalsView
const parseList = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    try {
        return typeof data === 'string' ? JSON.parse(data) : data;
    } catch (e) {
        return [data];
    }
};

const parseSmart = (data) => {
    if (!data) return {};
    if (typeof data === 'object' && !Array.isArray(data)) return data;
    try {
        return typeof data === 'string' ? JSON.parse(data) : data;
    } catch (e) {
        return {};
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const smartLabels = [
    { key: 'specific', label: 'Specific', short: 'S', color: 'bg-[#2E7D32] text-white', badgeColor: 'bg-[#2E7D32]' },
    { key: 'measurable', label: 'Measurable', short: 'M', color: 'bg-[#1565C0] text-white', badgeColor: 'bg-[#1565C0]' },
    { key: 'attainable', label: 'Attainable', short: 'A', color: 'bg-[#7B1FA2] text-white', badgeColor: 'bg-[#7B1FA2]' },
    { key: 'relevant', label: 'Relevant', short: 'R', color: 'bg-[#C62828] text-white', badgeColor: 'bg-[#C62828]' },
    { key: 'time_bound', label: 'Time-bound', short: 'T', color: 'bg-[#1A237E] text-white', badgeColor: 'bg-[#1A237E]' }
];

const quarterColors = {
    q1: { header: 'bg-red-400 text-white', cell: 'bg-red-50' },
    q2: { header: 'bg-purple-500 text-white', cell: 'bg-purple-50' },
    q3: { header: 'bg-green-600 text-white', cell: 'bg-green-50' },
    q4: { header: 'bg-amber-600 text-white', cell: 'bg-amber-50' }
};

const performanceRatings = [
    { value: 5, label: '5-Out Standing / Exceptional', potential: 'High Potential', potentialComment: 'Ready for the next position' },
    { value: 4, label: '4-Exceeding Expectations', potential: 'High Potential', potentialComment: 'Ready in 2 years' },
    { value: 3, label: '3-Meeting Expectations', potential: 'Good Potential', potentialComment: '' },
    { value: 2, label: '2-Partly Meeting Expectations', potential: 'Low Potential', potentialComment: 'Need to keep under observation' },
    { value: 1, label: '1-Below Expectations/ Unsatisfactory', potential: 'Below Potential', potentialComment: 'PIP' }
];

// Stats computed
const submittedCount = computed(() => goals.value.filter(g => g.status === 'submitted').length);
const completedCount = computed(() => goals.value.filter(g => g.status === 'completed').length);
const inProgressCount = computed(() => goals.value.filter(g => g.status === 'in_progress').length);
const pendingCount = computed(() => goals.value.filter(g => g.status !== 'submitted' && g.status !== 'completed').length);

// Standard 5 Performance Key Competencies (20% Weight Each)
const defaultCompetencyList = [
    {
        id: 1,
        title: 'Performance & Teamwork',
        weight: 20,
        selfRating: 0,
        managerRating: 0,
        descriptions: [
            'a) Overall performance - based on feedback from Line or Operations Managers',
            'b) Teamwork, People issues - how it has been managed, number of queries tracked as compared to last year and compared to your peers.'
        ],
        descriptionText: 'a) Overall performance - based on feedback from Line or Operations Managers\nb) Teamwork, People issues - how it has been managed, number of queries tracked as compared to last year and compared to your peers.'
    },
    {
        id: 2,
        title: 'Customer Service / Relationship Building',
        weight: 20,
        selfRating: 0,
        managerRating: 0,
        descriptions: [
            'a) Number of super saver cards sold vs number of invoices made without the use of super saver card on the invoices',
            "b) Google scores – Improvement over last year's Shop Google or overall Melcom Google score."
        ],
        descriptionText: "a) Number of super saver cards sold vs number of invoices made without the use of super saver card on the invoices\nb) Google scores – Improvement over last year's Shop Google or overall Melcom Google score."
    },
    {
        id: 3,
        title: 'Execution / Sales Results Driven',
        weight: 20,
        selfRating: 0,
        managerRating: 0,
        descriptions: [
            'a) Business Driven Metric (Set by Department with Management input)',
            'b) Loss to company % (Factors and calculations must be provided), where application and relevant'
        ],
        descriptionText: 'a) Business Driven Metric (Set by Department with Management input)\nb) Loss to company % (Factors and calculations must be provided), where application and relevant'
    },
    {
        id: 4,
        title: 'Compliance & Quality Standards',
        weight: 20,
        selfRating: 0,
        managerRating: 0,
        descriptions: [
            'a) Adherence to company policies, SOPs, safety, and regulatory compliance',
            'b) % implementation of Wooqer checklist and shop/department standards'
        ],
        descriptionText: 'a) Adherence to company policies, SOPs, safety, and regulatory compliance\nb) % implementation of Wooqer checklist and shop/department standards'
    },
    {
        id: 5,
        title: 'Continuous Improvement in workflows/processes',
        weight: 20,
        selfRating: 0,
        managerRating: 0,
        descriptions: [
            'a) Culture of adaptability and innovation among staff, such as inventory management, employee training',
            'b) Adaptability / Flexibility and operational problem solving'
        ],
        descriptionText: 'a) Culture of adaptability and innovation among staff, such as inventory management, employee training\nb) Adaptability / Flexibility and operational problem solving'
    }
];

const reviewTotalWeight = computed(() => {
    if (!selectedGoal.value?.appraisal_data?.competencies) return 100;
    return selectedGoal.value.appraisal_data.competencies.reduce((sum, c) => sum + (parseFloat(c.weight) || 0), 0);
});

const managerOverallScore = computed(() => {
    if (!selectedGoal.value?.appraisal_data?.competencies) return '0.00';
    const comps = selectedGoal.value.appraisal_data.competencies;
    const total = comps.reduce((sum, c) => {
        const rating = parseFloat(c.managerRating) || 0;
        const weight = parseFloat(c.weight) || 20;
        return sum + ((rating * weight) / 100);
    }, 0);
    return total.toFixed(2);
});

const managerOverallPercentage = computed(() => {
    const score = parseFloat(managerOverallScore.value) || 0;
    const pct = (score / 5) * 100;
    return pct % 1 === 0 ? pct.toFixed(0) : pct.toFixed(1);
});

const managerAverageScore = computed(() => managerOverallScore.value);

const getReviewWeightedScore = (comp) => {
    const rating = parseFloat(comp.managerRating) || 0;
    const weight = parseFloat(comp.weight) || 20;
    return ((rating * weight) / 100).toFixed(2);
};

const calculateAverageRating = (goal) => {
    if (!goal) return 0;
    const data = parseSmart(goal.appraisal_data);
    if (data.line_manager_rating || data.manager_rating) {
        return parseFloat(data.line_manager_rating || data.manager_rating);
    }
    const comps = data.competencies || [];
    if (!comps.length) return 0;
    const total = comps.reduce((sum, c) => sum + (parseFloat(c.managerRating) || 0), 0);
    return parseFloat((total / comps.length).toFixed(1));
};

const fetchGoals = async () => {
    loading.value = true;
    try {
        const response = await axios.get('pms/goals', { params: { year: currentYear.value } });
        if (response.data.status === 'success') {
            goals.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching goals:', error);
    } finally {
        loading.value = false;
    }
};

const startReview = (goal) => {
    const parsedData = goal.appraisal_data ? (typeof goal.appraisal_data === 'string' ? JSON.parse(goal.appraisal_data) : goal.appraisal_data) : {};
    
    // Standardize & normalize competencies to 5 items with full descriptions & weights
    let normalizedCompetencies = [];
    if (parsedData.competencies && parsedData.competencies.length > 0) {
        const rawComps = parsedData.competencies;
        if (rawComps.length === 4) {
            normalizedCompetencies = [
                {
                    ...defaultCompetencyList[0],
                    title: rawComps[0]?.title || defaultCompetencyList[0].title,
                    descriptions: rawComps[0]?.descriptions || defaultCompetencyList[0].descriptions,
                    descriptionText: rawComps[0]?.descriptions ? (Array.isArray(rawComps[0].descriptions) ? rawComps[0].descriptions.join('\n') : rawComps[0].descriptions) : defaultCompetencyList[0].descriptionText,
                    selfRating: rawComps[0]?.selfRating || 0,
                    managerRating: rawComps[0]?.managerRating || 0,
                    weight: Number(rawComps[0]?.weight) || 20
                },
                {
                    ...defaultCompetencyList[1],
                    title: rawComps[1]?.title || defaultCompetencyList[1].title,
                    descriptions: rawComps[1]?.descriptions || defaultCompetencyList[1].descriptions,
                    descriptionText: rawComps[1]?.descriptions ? (Array.isArray(rawComps[1].descriptions) ? rawComps[1].descriptions.join('\n') : rawComps[1].descriptions) : defaultCompetencyList[1].descriptionText,
                    selfRating: rawComps[1]?.selfRating || 0,
                    managerRating: rawComps[1]?.managerRating || 0,
                    weight: Number(rawComps[1]?.weight) || 20
                },
                {
                    ...defaultCompetencyList[2],
                    title: rawComps[2]?.title || defaultCompetencyList[2].title,
                    descriptions: rawComps[2]?.descriptions || defaultCompetencyList[2].descriptions,
                    descriptionText: rawComps[2]?.descriptions ? (Array.isArray(rawComps[2].descriptions) ? rawComps[2].descriptions.join('\n') : rawComps[2].descriptions) : defaultCompetencyList[2].descriptionText,
                    selfRating: rawComps[2]?.selfRating || 0,
                    managerRating: rawComps[2]?.managerRating || 0,
                    weight: Number(rawComps[2]?.weight) || 20
                },
                {
                    ...defaultCompetencyList[3], // Compliance & Quality Standards
                    weight: 20
                },
                {
                    ...defaultCompetencyList[4], // Continuous Improvement
                    title: rawComps[3]?.title || defaultCompetencyList[4].title,
                    descriptions: rawComps[3]?.descriptions || defaultCompetencyList[4].descriptions,
                    descriptionText: rawComps[3]?.descriptions ? (Array.isArray(rawComps[3].descriptions) ? rawComps[3].descriptions.join('\n') : rawComps[3].descriptions) : defaultCompetencyList[4].descriptionText,
                    selfRating: rawComps[3]?.selfRating || 0,
                    managerRating: rawComps[3]?.managerRating || 0,
                    weight: Number(rawComps[3]?.weight) || 20
                }
            ];
        } else {
            normalizedCompetencies = defaultCompetencyList.map((def, idx) => {
                let match = rawComps[idx];
                if (idx === 3 && match?.title && match.title.toLowerCase().includes('continuous') && rawComps[4]?.title?.toLowerCase().includes('continuous')) {
                    match = null; // Revert index 3 to Compliance
                }
                return {
                    ...def,
                    title: match?.title || def.title,
                    descriptions: match?.descriptions || (match?.descriptionText ? (Array.isArray(match.descriptionText) ? match.descriptionText : match.descriptionText.split('\n')) : def.descriptions),
                    descriptionText: match?.descriptionText || (match?.descriptions ? (Array.isArray(match.descriptions) ? match.descriptions.join('\n') : match.descriptions) : def.descriptionText),
                    selfRating: match?.selfRating || 0,
                    managerRating: match?.managerRating || 0,
                    weight: match?.weight !== undefined ? Number(match.weight) : def.weight
                };
            });
        }
    } else {
        normalizedCompetencies = JSON.parse(JSON.stringify(defaultCompetencyList));
    }

    selectedGoal.value = {
        ...goal,
        appraisal_data: {
            ...parsedData,
            competencies: normalizedCompetencies,
            comments: parsedData.comments || '',
            impressedMost: parsedData.impressedMost || '',
            impressedLeast: parsedData.impressedLeast || '',
            performanceRating: parsedData.performanceRating || 0,
            rating_comments: parsedData.rating_comments || {1: '', 2: '', 3: '', 4: '', 5: ''},
            candidate_signature_name: parsedData.candidate_signature_name || '',
            manager_signature_name: parsedData.manager_signature_name || loguser.name || '',
            signature_date: parsedData.signature_date || new Date().toISOString().split('T')[0]
        }
    };

    // Fallback default for Executive Summary (Sections A-E)
    if (!selectedGoal.value.appraisal_data.review_summary) {
        selectedGoal.value.appraisal_data.review_summary = {
            A: [{ achievement: '', target: '' }], // Special Structure for A
            B: [''],
            C: [''],
            D: [''],
            E: ['']
        };
    }

    // Fallback default for Authorization Sign-off fields
    if (!selectedGoal.value.appraisal_data.authorization) {
        selectedGoal.value.appraisal_data.authorization = {
            line_manager_name: '',
            line_manager_signature: '',
            line_manager_date: '',
            hod_comments: '',
            hod_name: '',
            hod_designation: '',
            hod_signature: '',
            hod_date: '',
            director_remarks: '',
            director_signature: '',
            director_date: ''
        };
    }

    currentStep.value = 1;
    viewMode.value = 'review';
};

const addSummaryRow = (section) => {
    if (!selectedGoal.value || !selectedGoal.value.appraisal_data || !selectedGoal.value.appraisal_data.review_summary) return;
    const summary = selectedGoal.value.appraisal_data.review_summary;
    
    if (section === 'A') {
        summary.A.push({ achievement: '', target: '' });
    } else {
        summary[section].push('');
    }
};

const removeSummaryRow = (section, index) => {
    if (!selectedGoal.value || !selectedGoal.value.appraisal_data || !selectedGoal.value.appraisal_data.review_summary) return;
    const summary = selectedGoal.value.appraisal_data.review_summary;
    
    if (summary[section].length > 1) {
        summary[section].splice(index, 1);
    } else {
        if (section === 'A') {
            summary.A[0] = { achievement: '', target: '' };
        } else {
            summary[section][0] = '';
        }
    }
};

const updateReview = async () => {
    saving.value = true;
    try {
        const appData = selectedGoal.value.appraisal_data || {};
        const authData = appData.authorization || {};
        const managerSig = appData.manager_signature_name || authData.line_manager_signature || authData.line_manager_name || (loguser.name || '');

        const payload = {
            ...selectedGoal.value,
            status: 'review_completed',
            overall_rating: managerOverallScore.value,
            appraisal_data: {
                ...appData,
                manager_signature_name: managerSig,
                overallPerformanceRating: managerOverallScore.value,
                overallPercentage: managerOverallPercentage.value,
                manager_rating: managerOverallScore.value
            }
        };
        const response = await axios.patch('pms/goals/' + selectedGoal.value.id, payload);
        if (response.data.status === 'success') {
            showAlert('Success', 'Goal reviewed and completed successfully.', 'success');
            viewMode.value = 'list';
            fetchGoals();
        }
    } catch (error) {
        console.error('Error updating goal:', error);
        showAlert('Error', 'Failed to approve review.', 'error');
    } finally {
        saving.value = false;
    }
};

const filterStatus = ref('all'); // Default to show all
const searchQuery = ref('');

const filteredGoals = computed(() => {
    return goals.value.filter(g => {
        // Status Filter
        if (filterStatus.value && filterStatus.value !== 'all') {
            // If they clicked a specific filter card, show goals matching that display_status
            if (g.display_status !== filterStatus.value) {
                return false;
            }
        }
        
        // Search Filter
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const name = g.candidate_name ? g.candidate_name.toLowerCase() : '';
            const title = g.title ? g.title.toLowerCase() : '';
            const dept = g.department ? g.department.toLowerCase() : '';
            return name.includes(query) || title.includes(query) || dept.includes(query);
        }
        
        return true;
    }).sort((a, b) => {
        return calculateAverageRating(b) - calculateAverageRating(a);
    });
});

onMounted(fetchGoals);

const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };
const nextStep = () => { if (currentStep.value < totalSteps) currentStep.value++; };

const downloadFile = async (url, filename) => {
    if (!url) return;
    try {
        const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://192.168.0.20:5050/pms_backend/api';

        // Convert storage paths to the backend file-serving endpoint
        let downloadUrl = url;
        if (!url.startsWith('http')) {
            // e.g. /storage/attachments/file.xlsx → /api/file/attachments/file.xlsx
            const cleaned = url.replace(/^\/?storage\//, '');
            const parts = cleaned.split('/');
            const folder = parts.slice(0, -1).join('/') || 'attachments';
            const file = parts[parts.length - 1];
            downloadUrl = `${baseUrl}/file/${folder}/${encodeURIComponent(file)}`;
        }

        const response = await axios.get(downloadUrl, { responseType: 'blob' });
        const blob = new Blob([response.data]);
        const blobUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filename || url.split('/').pop() || 'download';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
    } catch (error) {
        console.error('Download failed:', error);
    }
};

</script>

<template>
    <div class="h-full max-w-[2400px] mx-auto px-6">
        <!-- Dashboard View -->
        <div v-if="viewMode === 'list'" class="animate-slide-up mt-4">
            <!-- High-End Header -->
            <div class="relative overflow-hidden rounded-2xl bg-[#1A237E] shadow-xl border border-white/10 mb-5">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-900 via-[#1A237E] to-blue-900 opacity-10 h-12"></div>
                
                <div class="relative px-8 py-4 flex flex-col md:flex-row items-center justify-between gap-4 z-10">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="w-6 h-[2px] bg-blue-400 rounded-full"></span>
                            <span class="text-[9px] font-bold text-blue-300 uppercase tracking-[0.3em]">Review Management Hub</span>
                        </div>
                        <h1 class="text-2xl font-black text-white tracking-tight uppercase leading-none">Review</h1>
                        <p class="text-indigo-200 text-[10px] font-medium mt-1">Review and approve team goals for FY {{ currentYear }}.</p>
                    </div>
                </div>
            </div>

            <!-- Stats Analytics Widget -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- All Goals Card -->
                <div @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'border-[#1A237E] bg-indigo-50/50 shadow-indigo-900/10 ring-2 ring-[#1A237E]/30' : 'border-slate-100 bg-white'" class="p-6 rounded-3xl border shadow-xl shadow-indigo-900/5 flex items-center justify-between cursor-pointer hover:shadow-md transition-all">
                    <div>
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block mb-1">Total Submission</span>
                        <div class="text-3xl font-black text-slate-900">{{ goals.length }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <i class="pi pi-folders"></i>
                    </div>
                </div>

                <!-- Reviewed Card -->
                <div @click="filterStatus = 'appraisal_completed'" :class="filterStatus === 'appraisal_completed' ? 'border-teal-600 bg-teal-50/30 shadow-teal-900/10 ring-2 ring-teal-600/30' : 'border-slate-100 bg-white'" class="p-6 rounded-3xl border shadow-xl shadow-indigo-900/5 flex items-center justify-between cursor-pointer hover:shadow-md transition-all">
                    <div>
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block mb-1">Appraisal Meeting Completed</span>
                        <div class="text-3xl font-black text-slate-900">{{ goals.filter(g => g.display_status === 'appraisal_completed').length }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center shadow-lg shadow-teal-600/20">
                        <i class="pi pi-eye"></i>
                    </div>
                </div>

                <!-- Completed Card -->
                <div @click="filterStatus = 'review_completed'" :class="filterStatus === 'review_completed' ? 'border-sky-600 bg-sky-50/30 shadow-sky-900/10 ring-2 ring-sky-600/30' : 'border-slate-100 bg-white'" class="p-6 rounded-3xl border shadow-xl shadow-indigo-900/5 flex items-center justify-between cursor-pointer hover:shadow-md transition-all">
                    <div>
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest block mb-1">Review Completed</span>
                        <div class="text-3xl font-black text-slate-900">{{ goals.filter(g => g.display_status === 'review_completed').length }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-500 text-white flex items-center justify-center shadow-lg shadow-sky-500/20">
                        <i class="pi pi-check-circle"></i>
                    </div>
                </div>
            </div>
            <!-- Search & Filters Header -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <div class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-[#1A237E] rounded-full"></span>
                    {{ filterStatus === 'all' ? 'FULL' : filterStatus.toUpperCase() }} LIST ({{ filteredGoals.length }})
                </div>
                <div class="relative w-full md:w-96">
                    <input v-model="searchQuery" type="text" placeholder="Search Candidate Name, Title or Dept..." class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:border-[#1A237E] text-sm font-semibold text-slate-700 bg-white shadow-sm transition-all" />
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-3xl shadow-xl shadow-indigo-900/5 border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900 text-white text-[10px] uppercase font-black tracking-widest">
                                <th class="px-6 py-4 whitespace-nowrap">Candidate / Official Designation</th>
                                <th class="px-6 py-4 whitespace-nowrap">Goal Description & Target</th>
                                <th class="px-6 py-4 text-center whitespace-nowrap">Status</th>
                                <th class="px-6 py-4 text-right whitespace-nowrap">Audit Response</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="goal in filteredGoals" :key="goal.id" class="hover:bg-indigo-50/30 transition-all group">
                                <td class="px-6 py-5 align-top">
                                    <div class="font-black text-slate-800 text-sm uppercase">{{ goal.candidate_name }}</div>
                                    <div class="text-[9px] font-bold text-indigo-600 uppercase tracking-widest mt-1">{{ goal.department || 'Operations' }}</div>
                                    <div class="text-[9px] font-bold text-slate-400 mt-0.5">{{ goal.location || 'Head Office' }} - {{ goal.employee_code || 'EMP-000' }}</div>
                                </td>
                                <td class="px-6 py-5 align-top max-w-sm">
                                    <div class="font-bold text-slate-700 text-sm leading-snug mb-2">{{ goal.title }}</div>
                                    <div class="text-xs text-slate-500 font-medium line-clamp-2">{{ goal.category }} - {{ goal.target }}</div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span v-if="goal.display_status === 'goal_created'" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-slate-50 text-slate-600 border-slate-100">
                                        Goal Created
                                    </span>
                                    <span v-else-if="goal.display_status === 'appraisal_completed'" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-amber-50 text-amber-600 border-amber-100">
                                        Appraisal Done
                                    </span>
                                    <span v-else-if="goal.display_status === 'review_completed'" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-teal-50 text-teal-600 border-teal-100">
                                        Review Completed
                                    </span>
                                    <span v-else class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-slate-50 text-slate-600 border-slate-100">
                                        {{ goal.display_status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <button @click="startReview(goal)" class="px-5 py-2.5 font-black rounded-2xl text-[10px] uppercase tracking-widest flex items-center gap-2 active:scale-95 transition-all shadow-md ml-auto"
                                        :class="goal.display_status === 'review_completed' ? 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-sm' : 'bg-green-600 hover:bg-green-700 text-white shadow-green-600/20'">
                                        <i class="pi pi-verified text-xs"></i> {{ goal.display_status === 'review_completed' ? 'Completed' : 'Review' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredGoals.length === 0">
                                <td colspan="4" class="p-16 text-center">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-50 flex items-center justify-center text-slate-300 mx-auto mb-4 border border-slate-100">
                                        <i class="pi pi-inbox text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-black text-slate-400">No goals found matching criteria.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Review Step Wizard Layout -->
        <div v-else-if="viewMode === 'review'" class="animate-slide-up mt-8">
            <!-- Wizard Header -->
            <div class="bg-[#1A237E] rounded-2xl shadow-xl overflow-hidden mb-6">
                <div class="px-8 py-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4">
                        <button @click="viewMode = 'list'" class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition-all active:scale-95">
                            <i class="pi pi-arrow-left text-xs"></i>
                        </button>
                        <div>
                            <h1 class="text-2xl font-black text-white tracking-normal uppercase flex items-center gap-3">
                                <span>{{ selectedGoal.display_status === 'review_completed' ? 'MANAGER REVIEW - READ ONLY' : 'MANAGER PERFORMANCE REVIEW' }}</span>
                            </h1>
                            <p class="text-indigo-200 text-[10px] font-black mt-1 uppercase opacity-80">
                                <template v-if="currentStep === 1">Step 1: Goal Definition</template>
                                <template v-else-if="currentStep === 2">Step 2: Quarterly Progress Matrix</template>
                                <template v-else-if="currentStep === 3">Step 3: Performance Key Competencies</template>
                                <template v-else-if="currentStep === 4">Step 4: Executive Summary / End-Of-Year Review</template>
                                <template v-else-if="currentStep === 5">Step 5: Authorization &amp; Sign-Off (Final Approval)</template>
                            </p>
                        </div>
                    </div>

                    <!-- Stepper Widget (5 Clickable Steps) -->
                    <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl border border-white/10 overflow-x-auto">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer" :class="currentStep === 1 ? 'bg-white text-[#1A237E]' : 'text-white/60 hover:text-white'" @click="currentStep = 1">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] bg-[#1A237E] text-white">1</span>
                            <span class="text-xs font-bold uppercase hidden sm:inline">Definition</span>
                        </div>
                        <div class="w-3 h-[2px] bg-white/10"></div>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer" :class="currentStep === 2 ? 'bg-white text-[#1A237E]' : 'text-white/60 hover:text-white'" @click="currentStep = 2">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] bg-[#1A237E] text-white">2</span>
                            <span class="text-xs font-bold uppercase hidden sm:inline">Tracking</span>
                        </div>
                        <div class="w-3 h-[2px] bg-white/10"></div>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer" :class="currentStep === 3 ? 'bg-white text-[#1A237E]' : 'text-white/60 hover:text-white'" @click="currentStep = 3">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] bg-[#1A237E] text-white">3</span>
                            <span class="text-xs font-bold uppercase hidden sm:inline">Appraisal</span>
                        </div>
                        <div class="w-3 h-[2px] bg-white/10"></div>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer" :class="currentStep === 4 ? 'bg-white text-[#1A237E]' : 'text-white/60 hover:text-white'" @click="currentStep = 4">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] bg-[#1A237E] text-white">4</span>
                            <span class="text-xs font-bold uppercase hidden sm:inline">Summary</span>
                        </div>
                        <div class="w-3 h-[2px] bg-white/10"></div>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer" :class="currentStep === 5 ? 'bg-white text-[#1A237E]' : 'text-white/60 hover:text-white'" @click="currentStep = 5">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] bg-[#1A237E] text-white">5</span>
                            <span class="text-xs font-bold uppercase hidden sm:inline">Authorization</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Panel -->
            <div class="bg-white rounded-3xl shadow-xl shadow-indigo-900/5 p-8 border border-slate-100 mb-8">
                <!-- STEP 1: READ ONLY DEFINITIONS -->
                <div v-if="currentStep === 1" class="space-y-5">
                    <!-- Candidate & Manager Info Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Candidate Info Card -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            <div class="bg-[#E8EAF6] px-5 py-3 border-b border-[#C5CAE9]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#1A237E] text-white flex items-center justify-center">
                                        <i class="pi pi-user text-xs"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-[#1A237E]">Candidate Name</h3>
                                        <p class="text-[9px] text-[#3949AB] font-black uppercase tracking-normal opacity-70">Job Title & Signature</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5 space-y-3">
                                <input :value="selectedGoal.candidate_name" disabled class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-800 outline-none" />
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-3 py-1 bg-[#F1F5F9] rounded text-[10px] font-semibold text-gray-500 border border-gray-100">Dept: {{ selectedGoal.department || 'N/A' }}</span>
                                    <span class="px-3 py-1 bg-[#F1F5F9] rounded text-[10px] font-semibold text-gray-500 border border-gray-100">Location: {{ selectedGoal.location || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Line Manager Card -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            <div class="bg-[#305286] px-5 py-3 border-b border-[#264270]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-white/15 text-white flex items-center justify-center border border-white/10">
                                        <i class="pi pi-shield text-xs"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-white">Line Manager Name</h3>
                                        <p class="text-[9px] text-blue-200 font-black uppercase tracking-widest opacity-70">Signature & Date</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5">
                                <div class="flex items-end justify-between">
                                    <div>
                                        <p class="text-lg font-bold text-gray-800 leading-none mb-1">{{ selectedGoal.manager_name || 'Administrator' }}</p>
                                        <span class="text-[10px] font-semibold text-gray-400">Authorized Evaluator</span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-semibold text-gray-400 mb-0.5">Date</p>
                                        <p class="font-bold text-sm text-gray-700">{{ formatDate(selectedGoal.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Form Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
                        <!-- Left Column: Goals, Purposes, Challenges -->
                        <div class="lg:col-span-3 space-y-5">
                            <!-- GOALS Section -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#C5CAE9] px-5 py-3 border-b border-[#9FA8DA]">
                                    <h4 class="font-bold text-base text-[#1A237E] uppercase tracking-wide">GOALS</h4>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-[#1A237E] uppercase tracking-wide mb-2">Goals Title</label>
                                        <input :value="selectedGoal.title" type="text" disabled class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-3 px-4 text-sm font-bold text-gray-800 outline-none" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="block text-xs font-bold text-[#3949AB] uppercase tracking-wide">Goals Description</label>
                                        <div v-for="(desc, index) in parseList(selectedGoal.description)" :key="index" class="flex gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-[#E8EAF6] flex items-center justify-center text-[#1A237E] font-bold text-xs border border-[#C5CAE9] shrink-0 mt-1">{{ index + 1 }}</div>
                                            <textarea :value="desc" rows="2" disabled class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 outline-none resize-none"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PURPOSES Section -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#B2DFDB] px-5 py-3 border-b border-[#80CBC4]">
                                    <h4 class="font-bold text-base text-[#004D40] uppercase tracking-wide">PURPOSES</h4>
                                </div>
                                <div class="p-5 space-y-3">
                                    <div v-for="(purpose, index) in parseList(selectedGoal.purposes)" :key="index" class="flex gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#E0F2F1] text-[#00695C] flex items-center justify-center font-bold text-xs border border-[#B2DFDB] shrink-0 mt-1"><i class="pi pi-check text-[10px]"></i></div>
                                        <textarea :value="purpose" rows="2" disabled class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 outline-none resize-none"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- CHALLENGES Section -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#FFE0B2] px-5 py-3 border-b border-[#FFCC80]">
                                    <h4 class="font-bold text-base text-[#E65100] uppercase tracking-wide">CHALLENGES</h4>
                                </div>
                                <div class="p-5 space-y-3">
                                    <div v-for="(challenge, index) in parseList(selectedGoal.challenges)" :key="index" class="flex gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#FFF3E0] text-[#E65100] flex items-center justify-center font-bold text-xs border border-[#FFE0B2] shrink-0 mt-1"><i class="pi pi-exclamation-triangle text-[10px]"></i></div>
                                        <textarea :value="challenge" rows="2" disabled class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 outline-none resize-none"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- COMPLETION DATE Section -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#CFD8DC] px-5 py-3 border-b border-[#B0BEC5]">
                                    <h4 class="font-bold text-base text-[#37474F] uppercase tracking-wide">COMPLETION DATE</h4>
                                </div>
                                <div class="p-5">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Category</label><input :value="selectedGoal.category" disabled class="w-full bg-[#F8FAFC] border p-2 rounded-lg text-xs font-bold" /></div>
                                        <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Target</label><input :value="selectedGoal.target" disabled class="w-full bg-[#F8FAFC] border p-2 rounded-lg text-xs font-bold" /></div>
                                        <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Completion Date</label><input :value="formatDate(selectedGoal.completion_date)" disabled class="w-full bg-[#F8FAFC] border p-2 rounded-lg text-xs font-bold" /></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: SMART Criteria Checklist -->
                        <div class="lg:col-span-1">
                            <div class="bg-white overflow-hidden sticky top-6 shadow-md rounded-2xl border border-gray-200">
                                <div class="bg-[#1A237E] px-4 py-3"><h4 class="font-bold text-sm text-white uppercase">MY GOAL IS...</h4></div>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="criteria in smartLabels" :key="criteria.key" class="flex items-center justify-between px-4 py-3.5">
                                        <span class="font-semibold text-sm text-gray-700">{{ criteria.label }}</span>
                                        <div class="flex items-center gap-3">
                                            <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs" :class="criteria.color">{{ criteria.short }}</div>
                                            <input type="checkbox" :checked="parseSmart(selectedGoal.smart_criteria)[criteria.key]" disabled class="w-4 h-4 rounded text-[#1A237E]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: READ ONLY TRACKING -->
                <div v-else-if="currentStep === 2" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="(quarter, index) in parseList(selectedGoal.quarterly_tracking)" :key="index" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col h-full hover:shadow-md transition-all duration-300">
                            <div class="py-3 px-5 flex justify-between items-center" :class="index === 0 ? 'bg-[#EF5350] text-white' : index === 1 ? 'bg-[#7E57C2] text-white' : index === 2 ? 'bg-[#43A047] text-white' : 'bg-[#FF8F00] text-white'">
                                <h5 class="font-bold text-lg tracking-wide">{{ quarter.quarter ? quarter.quarter.toUpperCase() : 'Q'+(index+1) }}</h5>
                                <i class="pi pi-calendar text-white/70 text-xs"></i>
                            </div>
                            <div class="p-4 space-y-3 flex-1 flex flex-col">
                                <div class="grid grid-cols-2 gap-2">
                                    <div><label class="text-[9px] font-bold text-[#1A237E] uppercase block mb-1">Start Date</label><input type="text" :value="formatDate(quarter.start_date)" disabled class="w-full bg-[#F8FAFC] border p-2 rounded-lg text-xs font-bold" /></div>
                                    <div><label class="text-[9px] font-bold text-[#E65100] uppercase block mb-1">End Date</label><input type="text" :value="formatDate(quarter.end_date)" disabled class="w-full bg-[#FFF3E0] border p-2 rounded-lg text-xs font-bold" /></div>
                                </div>
                                <div class="relative flex-1 flex flex-col">
                                    <label class="text-[9px] font-bold text-gray-500 uppercase mb-1 block">Target Measure</label>
                                    <div class="space-y-2 mb-3">
                                        <div v-for="(measure, mIndex) in parseList(quarter.target_measures)" :key="'m'+mIndex"><textarea :value="measure" rows="2" disabled class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2 px-3 text-sm font-medium text-gray-700 outline-none resize-none"></textarea></div>
                                    </div>
                                </div>
                                <div class="flex-1 flex flex-col justify-end">
                                    <label class="text-[9px] font-bold text-gray-500 uppercase mb-1 block">Evidence</label>
                                    <div class="space-y-2 mb-3 min-h-[40px]">
                                        <div v-for="(file, fIndex) in parseList(quarter.attachments)" :key="fIndex" class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border">
                                            <div class="flex items-center gap-2 overflow-hidden flex-1"><i class="pi pi-file text-xs text-[#1A237E]"></i><p class="truncate text-[10px] font-bold text-gray-700">{{ file.file_name || file.name }}</p></div><div class="flex items-center gap-1"><button @click="downloadFile(file.path || file.url || ('/storage/' + (file.file_path || file.file_name || file.name || file)), file.name || 'file')" class="w-7 h-7 rounded-md bg-blue-600 flex items-center justify-center text-white hover:bg-blue-700 transition-all shadow-md shadow-blue-500/20 cursor-pointer" title="Download Attachment"><i class="pi pi-download text-xs"></i></button></div>
                                        </div>
                                        <div v-if="quarter.evidence && (quarter.evidence.includes('/') || quarter.evidence.includes('http'))" class="flex items-center justify-between p-2 bg-blue-50/50 rounded-lg border border-blue-100 mt-1 mb-1"><div class="flex items-center gap-2 overflow-hidden flex-1"><i class="pi pi-file-o text-xs text-[#1A237E]"></i><p class="truncate text-[10px] font-bold text-gray-700">Flat Evidence File</p></div><button @click="downloadFile(quarter.evidence.startsWith('http') ? quarter.evidence : ('/storage/' + quarter.evidence), quarter.evidence.split('/').pop() || 'evidence')" class="w-6 h-6 rounded-md bg-white border flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm cursor-pointer" title="Download Evidence"><i class="pi pi-download text-[9px]"></i></button></div><div v-if="!parseList(quarter.attachments)?.length && (!quarter.evidence || (!quarter.evidence.includes('/') && !quarter.evidence.includes('http')))" class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider py-4 text-center border-2 border-dashed border-gray-100 rounded-lg">No files uploaded</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: EDTIABLE APPRAISAL (PERFORMANCE KEY COMPETENCIES) -->
                <div v-else-if="currentStep === 3" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="font-black text-slate-800 text-sm md:text-base flex items-center gap-2">
                                    <i class="pi pi-chart-bar text-indigo-600"></i> Performance Key Competencies
                                </h4>
                                <p class="text-gray-400 text-[9px] uppercase tracking-normal font-bold">Annual performance evaluation scale (5 Key Areas &bull; Manager Review)</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5 text-xs font-bold">
                                    <span class="text-gray-400 text-[10px] uppercase">Total Weight:</span>
                                    <span class="px-2.5 py-0.5 rounded-lg border text-xs font-black text-emerald-700 bg-emerald-50 border-emerald-300 flex items-center gap-1 shadow-2xs">
                                        <i class="pi pi-check text-[10px]"></i>
                                        {{ reviewTotalWeight }}%
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 bg-teal-50/80 px-3.5 py-1.5 rounded-xl border border-teal-200 shadow-2xs">
                                    <span class="text-[9px] font-black text-teal-800 uppercase tracking-wider">Overall Manager Score:</span>
                                    <span class="text-base font-black text-teal-700">{{ managerOverallScore }}</span>
                                    <span class="text-[10px] font-bold text-teal-400">/ 5.00</span>
                                    <span class="text-xs font-black text-teal-800 bg-white border border-teal-200 px-2 py-0.5 rounded-lg shadow-2xs">
                                        ({{ managerOverallPercentage }}%)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/80 border-b border-gray-100">
                                        <th class="px-3 py-2.5 text-left text-[9px] font-black text-gray-400 uppercase tracking-normal w-10">#</th>
                                        <th class="px-3 py-2.5 text-left text-[9px] font-black text-gray-400 uppercase tracking-normal">Competency &amp; Specific Criteria</th>
                                        <th class="px-3 py-2.5 text-center text-[9px] font-black text-gray-400 uppercase tracking-normal w-24">Weight (%)</th>
                                        <th class="px-3 py-2.5 text-center text-[9px] font-black text-indigo-600 uppercase tracking-normal w-28 bg-indigo-50/40">Self Rating</th>
                                        <th class="px-3 py-2.5 text-center text-[9px] font-black text-teal-600 uppercase tracking-normal w-28 bg-teal-50/40">Manager Rating</th>
                                        <th class="px-3 py-2.5 text-center text-[9px] font-black text-gray-400 uppercase tracking-normal w-24">W. Score</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(comp, index) in selectedGoal.appraisal_data.competencies" :key="comp.id || index" class="hover:bg-indigo-50/10 transition-colors">
                                        <td class="px-3 py-3 font-black text-gray-300 text-xs align-top pt-4">0{{ index + 1 }}</td>
                                        <td class="px-3 py-2.5">
                                            <div class="font-bold text-gray-800 text-xs md:text-sm mb-1 tracking-tight">{{ comp.title }}</div>
                                            <div v-if="comp.descriptions && comp.descriptions.length" class="space-y-0.5">
                                                <p v-for="(desc, dIdx) in comp.descriptions" :key="dIdx" class="text-[11px] text-gray-500 leading-snug font-medium">{{ desc }}</p>
                                            </div>
                                            <p v-else-if="comp.descriptionText" class="text-[11px] text-gray-500 leading-snug font-medium whitespace-pre-line">{{ comp.descriptionText }}</p>
                                        </td>
                                        <td class="px-3 py-2.5 text-center align-middle">
                                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-black text-gray-700 bg-slate-100 border border-slate-200 shadow-2xs">
                                                {{ comp.weight || 20 }}%
                                            </span>
                                        </td>
                                        <td class="px-3 py-2.5 bg-indigo-50/30 text-center align-middle">
                                            <div class="inline-flex items-center justify-center w-12 py-1 rounded-lg font-black text-xs text-indigo-700 bg-white border border-indigo-100 shadow-2xs">
                                                {{ comp.selfRating && comp.selfRating > 0 ? comp.selfRating : '—' }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 bg-teal-50/30 align-middle">
                                            <div class="flex items-center justify-center">
                                                <select v-model.number="comp.managerRating" 
                                                    :disabled="selectedGoal.display_status === 'review_completed'"
                                                    class="review-manager-select shadow-2xs">
                                                    <option :value="0">—</option>
                                                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-black text-teal-700 text-sm align-middle">{{ getReviewWeightedScore(comp) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50/90 border-t border-gray-100">
                                    <tr>
                                        <td colspan="2" class="px-4 py-3 font-black text-gray-500 tracking-normal text-[11px]">ANNUAL PERFORMANCE SUMMARY</td>
                                        <td class="px-3 py-3 text-center font-black text-xs text-gray-700">{{ reviewTotalWeight }}%</td>
                                        <td colspan="2" class="px-3 py-3 text-center font-black text-gray-400 uppercase tracking-wider text-[9px]">OVERALL MANAGER SCORE</td>
                                        <td class="px-3 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span class="font-black text-teal-700 text-xl">{{ managerOverallScore }}</span>
                                                <span class="text-xs font-bold text-teal-300 ml-0.5">/ 5.00</span>
                                                <span class="text-[11px] font-black text-teal-700 bg-teal-50 border border-teal-200 px-1.5 py-0.5 rounded-md">
                                                    ({{ managerOverallPercentage }}%)
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Manager Review Comments -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block mb-2">Manager Review Comments & Feedback</span>
                        <textarea v-model="selectedGoal.appraisal_data.comments" rows="4" 
                            class="w-full bg-white border border-indigo-100 rounded-xl p-4 text-sm font-medium text-slate-700 focus:ring-indigo-500 outline-none resize-none transition-all focus:border-indigo-300" 
                            placeholder="Provide overall feedback on the employee's performance goals and execution..."></textarea>
                    </div>

                    <!-- Signature Box -->
                    <div class="grid grid-cols-2 gap-6 p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <div>
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Candidate Signature Verification</span>
                            <div class="p-4 bg-white rounded-xl border border-slate-200 text-sm font-bold text-slate-600 italic" style="font-family: 'Brush Script MT', cursive; font-size: 1.25rem">
                                {{ selectedGoal.appraisal_data.candidate_signature_name || 'Missing signature' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest block mb-2">Line Manager Signature authorization</span>
                            <input v-model="selectedGoal.appraisal_data.manager_signature_name" type="text"
                                class="w-full px-4 py-3 rounded-xl border border-indigo-200 focus:ring-indigo-500 text-sm font-black text-indigo-900 bg-white" 
                                style="font-family: 'Brush Script MT', cursive; font-size: 1.25rem" placeholder="Type full name to verify" />
                    </div>
                </div>
            </div>
            <div v-else-if="currentStep === 4" class="space-y-6">
                <div class="bg-indigo-50/50 p-6 rounded-3xl border border-indigo-100 mb-4 flex justify-between items-center">
                    <div>
                        <h4 class="text-xl font-black text-[#1A237E] flex items-center gap-2">
                            <i class="pi pi-file-edit text-lg"></i> Executive Summary / End-Of-Year Review
                        </h4>
                        <p class="text-xs text-indigo-500 font-bold mt-1">Provide narrative achievement vs target summaries and manager feedback</p>
                    </div>
                </div>

                <!-- Section A: Goal Achievement vs Target -->
                <div class="bg-white rounded-2xl border border-red-200 shadow-sm overflow-hidden">
                    <div class="bg-red-500 px-5 py-3 border-b border-red-600 flex justify-between items-center">
                        <h4 class="font-bold text-sm text-white uppercase tracking-wide">A. Goal Achievement vs. Target</h4>
                        <span class="w-6 h-6 rounded-lg bg-white/20 text-white flex items-center justify-center font-bold text-xs">A</span>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-for="(item, index) in selectedGoal.appraisal_data.review_summary.A" :key="'A'+index" class="flex gap-4 items-start bg-red-50/20 p-4 rounded-xl border border-red-50">
                            <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-extrabold text-xs shrink-0 mt-1">{{ index + 1 }}</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
                                <div>
                                    <label class="block text-[10px] font-black text-red-600 uppercase mb-1">Achievement Details</label>
                                    <textarea v-model="item.achievement" :disabled="selectedGoal.display_status === 'review_completed'" rows="2" class="w-full bg-white border border-gray-200 rounded-xl py-2 px-3 text-sm font-medium text-slate-700 focus:border-red-400 focus:ring-red-100 outline-none resize-none transition-all" placeholder="What was achieved..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-red-600 uppercase mb-1">Target Description</label>
                                    <textarea v-model="item.target" :disabled="selectedGoal.display_status === 'review_completed'" rows="2" class="w-full bg-white border border-gray-200 rounded-xl py-2 px-3 text-sm font-medium text-slate-700 focus:border-red-400 focus:ring-red-100 outline-none resize-none transition-all" placeholder="What the target was..."></textarea>
                                </div>
                            </div>
                            <button v-if="selectedGoal.display_status !== 'review_completed'" 
                                @click="removeSummaryRow('A', index)" 
                                type="button"
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-red-400 hover:text-white hover:bg-red-500 bg-red-50 border border-red-100 transition-all shadow-2xs mt-6 shrink-0 cursor-pointer" 
                                title="Remove item">
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                        </div>
                        <button v-if="selectedGoal.display_status !== 'review_completed'" 
                            @click="addSummaryRow('A')" 
                            type="button"
                            class="flex items-center gap-2 py-2.5 px-4 bg-red-50 text-red-700 border border-red-200 rounded-xl hover:bg-red-100 hover:shadow-xs transition-all text-xs font-black uppercase mt-1 cursor-pointer">
                            <i class="pi pi-plus text-[10px]"></i> Add Item
                        </button>
                    </div>
                </div>

                <!-- Section B: Additional Responsibility -->
                <div class="bg-white rounded-2xl border border-teal-200 shadow-sm overflow-hidden">
                    <div class="bg-teal-600 px-5 py-3 border-b border-teal-700 flex justify-between items-center">
                        <h4 class="font-bold text-sm text-white uppercase tracking-wide">B. Additional Responsibility/Initiatives Taken</h4>
                        <span class="w-6 h-6 rounded-lg bg-white/20 text-white flex items-center justify-center font-bold text-xs">B</span>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="(item, index) in selectedGoal.appraisal_data.review_summary.B" :key="'B'+index" class="flex gap-3 items-center">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-extrabold text-xs shrink-0">{{ index + 1 }}</div>
                            <textarea v-model="selectedGoal.appraisal_data.review_summary.B[index]" :disabled="selectedGoal.display_status === 'review_completed'" rows="2" class="flex-1 bg-gray-50 border border-gray-100 rounded-xl py-2 px-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-teal-400 focus:ring-teal-100 outline-none resize-none transition-all" placeholder="Enter details..."></textarea>
                            <button v-if="selectedGoal.display_status !== 'review_completed'" 
                                @click="removeSummaryRow('B', index)" 
                                type="button"
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-red-500 bg-slate-50 border border-slate-200 transition-all shadow-2xs shrink-0 cursor-pointer" 
                                title="Remove item">
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                        </div>
                        <button v-if="selectedGoal.display_status !== 'review_completed'" 
                            @click="addSummaryRow('B')" 
                            type="button"
                            class="flex items-center gap-2 py-2 px-4 bg-teal-50 text-teal-700 border border-teal-200 rounded-xl hover:bg-teal-100 hover:shadow-xs transition-all text-xs font-black uppercase mt-1 cursor-pointer">
                            <i class="pi pi-plus text-[10px]"></i> Add Item
                        </button>
                    </div>
                </div>

                <!-- Section C: Next Step for Business -->
                <div class="bg-white rounded-2xl border border-purple-200 shadow-sm overflow-hidden">
                    <div class="bg-purple-600 px-5 py-3 border-b border-purple-700 flex justify-between items-center">
                        <h4 class="font-bold text-sm text-white uppercase tracking-wide">C. Next Step for Business and planning</h4>
                        <span class="w-6 h-6 rounded-lg bg-white/20 text-white flex items-center justify-center font-bold text-xs">C</span>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="(item, index) in selectedGoal.appraisal_data.review_summary.C" :key="'C'+index" class="flex gap-3 items-center">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-extrabold text-xs shrink-0">{{ index + 1 }}</div>
                            <textarea v-model="selectedGoal.appraisal_data.review_summary.C[index]" :disabled="selectedGoal.display_status === 'review_completed'" rows="2" class="flex-1 bg-gray-50 border border-gray-100 rounded-xl py-2 px-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-purple-400 focus:ring-purple-100 outline-none resize-none transition-all" placeholder="Enter business alignment notes..."></textarea>
                            <button v-if="selectedGoal.display_status !== 'review_completed'" 
                                @click="removeSummaryRow('C', index)" 
                                type="button"
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-red-500 bg-slate-50 border border-slate-200 transition-all shadow-2xs shrink-0 cursor-pointer" 
                                title="Remove item">
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                        </div>
                        <button v-if="selectedGoal.display_status !== 'review_completed'" 
                            @click="addSummaryRow('C')" 
                            type="button"
                            class="flex items-center gap-2 py-2 px-4 bg-purple-50 text-purple-700 border border-purple-200 rounded-xl hover:bg-purple-100 hover:shadow-xs transition-all text-xs font-black uppercase mt-1 cursor-pointer">
                            <i class="pi pi-plus text-[10px]"></i> Add Item
                        </button>
                    </div>
                </div>

                <!-- Section D: Area of Improvement -->
                <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
                    <div class="bg-amber-600 px-5 py-3 border-b border-amber-700 flex justify-between items-center">
                        <h4 class="font-bold text-sm text-white uppercase tracking-wide">D. Area of Improvement in Daily work</h4>
                        <span class="w-6 h-6 rounded-lg bg-white/20 text-white flex items-center justify-center font-bold text-xs">D</span>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="(item, index) in selectedGoal.appraisal_data.review_summary.D" :key="'D'+index" class="flex gap-3 items-center">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-extrabold text-xs shrink-0">{{ index + 1 }}</div>
                            <textarea v-model="selectedGoal.appraisal_data.review_summary.D[index]" :disabled="selectedGoal.display_status === 'review_completed'" rows="2" class="flex-1 bg-gray-50 border border-gray-100 rounded-xl py-2 px-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-amber-400 focus:ring-amber-100 outline-none resize-none transition-all" placeholder="Enter improvement notes..."></textarea>
                            <button v-if="selectedGoal.display_status !== 'review_completed'" 
                                @click="removeSummaryRow('D', index)" 
                                type="button"
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-red-500 bg-slate-50 border border-slate-200 transition-all shadow-2xs shrink-0 cursor-pointer" 
                                title="Remove item">
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                        </div>
                        <button v-if="selectedGoal.display_status !== 'review_completed'" 
                            @click="addSummaryRow('D')" 
                            type="button"
                            class="flex items-center gap-2 py-2 px-4 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl hover:bg-amber-100 hover:shadow-xs transition-all text-xs font-black uppercase mt-1 cursor-pointer">
                            <i class="pi pi-plus text-[10px]"></i> Add Item
                        </button>
                    </div>
                </div>

                <!-- Section E: Feedback From Line Manager -->
                <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm overflow-hidden">
                    <div class="bg-emerald-600 px-5 py-3 border-b border-emerald-700 flex justify-between items-center">
                        <h4 class="font-bold text-sm text-white uppercase tracking-wide">E. Feedback From your Line Manager</h4>
                        <span class="w-6 h-6 rounded-lg bg-white/20 text-white flex items-center justify-center font-bold text-xs">E</span>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="(item, index) in selectedGoal.appraisal_data.review_summary.E" :key="'E'+index" class="flex gap-3 items-center">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-extrabold text-xs shrink-0">{{ index + 1 }}</div>
                            <textarea v-model="selectedGoal.appraisal_data.review_summary.E[index]" :disabled="selectedGoal.display_status === 'review_completed'" rows="2" class="flex-1 bg-gray-50 border border-gray-100 rounded-xl py-2 px-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-emerald-400 focus:ring-emerald-100 outline-none resize-none transition-all" placeholder="Enter final evaluator notes..."></textarea>
                            <button v-if="selectedGoal.display_status !== 'review_completed'" 
                                @click="removeSummaryRow('E', index)" 
                                type="button"
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-red-500 bg-slate-50 border border-slate-200 transition-all shadow-2xs shrink-0 cursor-pointer" 
                                title="Remove item">
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                        </div>
                        <button v-if="selectedGoal.display_status !== 'review_completed'" 
                            @click="addSummaryRow('E')" 
                            type="button"
                            class="flex items-center gap-2 py-2 px-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-100 hover:shadow-xs transition-all text-xs font-black uppercase mt-1 cursor-pointer">
                            <i class="pi pi-plus text-[10px]"></i> Add Item
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 5: AUTHORIZATION & SIGN-OFF (MANAGEMENT ENDORSEMENT & FINAL APPROVAL) -->
            <div v-else-if="currentStep === 5" class="space-y-6">
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
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Full Name</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.line_manager_name" :disabled="selectedGoal.display_status === 'review_completed'" type="text"
                                        class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/20 outline-none transition-all"
                                        placeholder="Enter line manager's name" />
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Rating</label>
                                    <select v-model="selectedGoal.appraisal_data.authorization.line_manager_rating" :disabled="selectedGoal.display_status === 'review_completed'"
                                        class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/20 outline-none transition-all">
                                        <option value="">Select...</option>
                                        <option value="5 - Outstanding">5 - Outstanding</option>
                                        <option value="4 - Exceeds Expectations">4 - Exceeds Expectations</option>
                                        <option value="3 - Meets Expectations">3 - Meets Expectations</option>
                                        <option value="2 - Needs Improvement">2 - Needs Improvement</option>
                                        <option value="1 - Unacceptable">1 - Unacceptable</option>
                                    </select>
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Signature</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.line_manager_signature" :disabled="selectedGoal.display_status === 'review_completed'" type="text"
                                        class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/20 outline-none transition-all"
                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.15rem" placeholder="Type full name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.line_manager_date" :disabled="selectedGoal.display_status === 'review_completed'" type="date"
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
                            <textarea v-model="selectedGoal.appraisal_data.authorization.hod_comments" :disabled="selectedGoal.display_status === 'review_completed'" rows="3"
                                class="w-full bg-white border border-blue-200/60 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none resize-none transition-all"
                                placeholder="HOD / Functional Head's overall assessment and comments..."></textarea>
                        </div>

                        <!-- ── 3. HOD / Functional Head's Name, Designation & Signature ── -->
                        <div class="bg-blue-50/40 rounded-2xl border border-blue-100/80 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-7 h-7 rounded-lg bg-blue-700 text-white flex items-center justify-center font-black text-[10px]">3</span>
                                <h5 class="font-black text-sm text-blue-800 uppercase tracking-wide">HOD / Functional Head's Name, Designation &amp; Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Full Name</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.hod_name" :disabled="selectedGoal.display_status === 'review_completed'" type="text"
                                        class="w-full bg-white border border-blue-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none transition-all"
                                        placeholder="HOD name" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Designation</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.hod_designation" :disabled="selectedGoal.display_status === 'review_completed'" type="text"
                                        class="w-full bg-white border border-blue-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none transition-all"
                                        placeholder="e.g. Head of Finance" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Signature</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.hod_signature" :disabled="selectedGoal.display_status === 'review_completed'" type="text"
                                        class="w-full bg-white border border-blue-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none transition-all"
                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.15rem" placeholder="Type name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.hod_date" :disabled="selectedGoal.display_status === 'review_completed'" type="date"
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
                            <textarea v-model="selectedGoal.appraisal_data.authorization.director_remarks" :disabled="selectedGoal.display_status === 'review_completed'" rows="3"
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
                                    <input v-model="selectedGoal.appraisal_data.authorization.director_signature" :disabled="selectedGoal.display_status === 'review_completed'" type="text"
                                        class="w-full bg-white border border-amber-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 outline-none transition-all"
                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.15rem" placeholder="Type director's name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                                    <input v-model="selectedGoal.appraisal_data.authorization.director_date" :disabled="selectedGoal.display_status === 'review_completed'" type="date"
                                        class="w-full bg-white border border-amber-200/60 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            </div>

            <!-- Footer Action Bar -->
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl border border-slate-100 shadow-xl shadow-indigo-900/5">
                <button @click="viewMode = 'list'" class="px-6 py-3 border border-slate-200 rounded-2xl text-xs font-black text-slate-600 uppercase tracking-normal hover:bg-slate-50 transition-all">
                    Cancel Review
                </button>
                <div class="flex gap-3">
                    <button v-if="currentStep > 1" @click="prevStep" class="px-5 py-3 border border-indigo-200 text-indigo-600 rounded-2xl text-xs font-black uppercase hover:bg-indigo-50 transition-all">
                        PREVIOUS
                    </button>
                    <button v-if="currentStep < totalSteps" @click="nextStep" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl text-xs uppercase shadow-lg shadow-indigo-300 transition-all">
                        NEXT STEP
                    </button>
                    <button v-if="currentStep === totalSteps && selectedGoal.display_status !== 'review_completed'" 
                        @click="updateReview" 
                        :disabled="saving || (!selectedGoal.appraisal_data.manager_signature_name && !selectedGoal.appraisal_data.authorization?.line_manager_signature && !selectedGoal.appraisal_data.authorization?.line_manager_name)" 
                        class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-black rounded-2xl text-xs uppercase shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2 active:scale-95">
                        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                        <i v-else class="pi pi-check-circle"></i>
                        Submit Goals Review
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Clean Manager Rating Select Dropdown - No background patterns or chevrons */
.review-manager-select {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background: #f0fdfa !important;
    background-image: none !important;
    border: 1.5px solid #5eead4 !important;
    border-radius: 8px !important;
    color: #0f766e !important;
    font-weight: 800 !important;
    font-size: 13px !important;
    width: 68px !important;
    height: 30px !important;
    text-align: center !important;
    text-align-last: center !important;
    padding: 0 !important;
    outline: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
}

.review-manager-select:hover:not(:disabled) {
    background: #ffffff !important;
    background-image: none !important;
    border-color: #14b8a6 !important;
}

.review-manager-select:focus:not(:disabled) {
    background: #ffffff !important;
    background-image: none !important;
    border-color: #0d9488 !important;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2) !important;
}

.review-manager-select:disabled {
    background: #f1f5f9 !important;
    background-image: none !important;
    border-color: #cbd5e1 !important;
    color: #64748b !important;
    cursor: not-allowed !important;
}

/* Dark Mode */
:global(body.dark-mode .review-manager-select) {
    background: #134e4a !important;
    background-image: none !important;
    border-color: #0d9488 !important;
    color: #ccfbf1 !important;
}
:global(body.dark-mode .review-manager-select:disabled) {
    background: #0f172a !important;
    background-image: none !important;
    border-color: #334155 !important;
    color: #64748b !important;
}
</style>
