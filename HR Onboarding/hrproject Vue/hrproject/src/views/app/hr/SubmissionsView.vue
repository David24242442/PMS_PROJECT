<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, parseSmart, parseList, calculateAverageRating } from '@/helpers/essential';
import { depts, branchs } from '@/data/masterdata';

const userstore = useUsersStore();

const submissions = ref([]);
const loading = ref(true);
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });
const selectedAppraisal = ref(null);
const showActionModal = ref(false);
const actionData = ref({ status: 'approved', hr_comments: '', overall_rating: 4.0 });
const viewMode = ref('list'); // 'list' or 'protocol'

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateString;
    }
};

const getRecordId = (goal) => {
    if (!goal) return 'N/A';
    const dept = goal.user?.department || goal.department || 'NA';
    const code = goal.user?.employee_code || goal.employee_code || goal.id || '---';
    const abbr = dept.includes(' ') 
        ? dept.split(' ').map(w => w[0]).join('').toUpperCase() 
        : (dept.length >= 3 ? dept.substring(0, 3).toUpperCase() : dept.toUpperCase());
    return `${abbr}-${code}`;
};

const filterStatus = ref('all');
const filterDepartment = ref('');
const filterLocation = ref('');
const searchQuery = ref('');
const expandedManagers = ref([]);

const toggleManager = (name) => {
    if (expandedManagers.value.includes(name)) {
        expandedManagers.value = expandedManagers.value.filter(m => m !== name);
    } else {
        expandedManagers.value.push(name);
    }
};
const getCompositeRating = (appraisals) => {
    if (!appraisals.length) return '0.0';
    const total = appraisals.reduce((sum, s) => sum + parseFloat(calculateAverageRating(s)), 0);
    return (total / appraisals.length).toFixed(1);
};

const getCompositeStatus = (appraisals) => {
    const isAllApproved = appraisals.every(s => s.status === 'approved');
    const isAnyRejected = appraisals.some(s => s.status === 'rejected');
    if (isAnyRejected) return 'Rejected';
    return isAllApproved ? 'Reviewed' : 'Submitted';
};

const smartLabels = [
    { key: 'specific', label: 'Specific', short: 'S', color: 'bg-[#2E7D32] text-white' },
    { key: 'measurable', label: 'Measurable', short: 'M', color: 'bg-[#1565C0] text-white' },
    { key: 'attainable', label: 'Attainable', short: 'A', color: 'bg-[#7B1FA2] text-white' },
    { key: 'relevant', label: 'Relevant', short: 'R', color: 'bg-[#C62828] text-white' },
    { key: 'time_bound', label: 'Time-bound', short: 'T', color: 'bg-[#1A237E] text-white' }
];

const getTimeProgress = (goal) => {
    if (!goal || !goal.completion_date) return 0;
    const start = new Date(goal.submitted_at || goal.created_at || `${currentYear.value}-01-01`);
    const end = new Date(goal.completion_date);
    const today = new Date();
    if (today >= end) return 100;
    if (today <= start) return 0;
    return Math.min(100, Math.max(0, Math.round(((today - start) / (end - start)) * 100)));
};

const getSmartClass = (labelShort, appraisal) => {
    if (!appraisal) return 'bg-slate-50 text-slate-300';
    const smart = typeof appraisal.smart_criteria === 'string' ? JSON.parse(appraisal.smart_criteria) : (appraisal.smart_criteria || {});
    const item = smartLabels.find(l => l.short === labelShort);
    if (!item) return 'bg-slate-50 text-slate-300';
    return smart[item.key] ? `${item.color} shadow-sm font-black` : 'bg-slate-50 text-slate-200';
};

const currentYear = ref(new Date().getFullYear());

const stats = computed(() => {
    return {
        total: submissions.value.length,
        pending: submissions.value.filter(s => s.status === 'pending').length,
        approved: submissions.value.filter(s => s.status === 'approved').length,
        rejected: submissions.value.filter(s => s.status === 'rejected').length
    };
});

const groupedSubmissions = computed(() => {
    const groups = {};
    submissions.value.forEach(s => {
        const mgr = s.manager_name || 'Administrator';
        if (!groups[mgr]) {
            groups[mgr] = {
                manager_name: mgr,
                department: s.user?.department || 'NA',
                user: s.user, 
                appraisals: []
            };
        }
        groups[mgr].appraisals.push(s);
    });

    return Object.values(groups).map(g => {
        const matchingAppraisals = g.appraisals.filter(s => {
            const query = searchQuery.value.toLowerCase();
            const name = s.user?.name?.toLowerCase() || '';
            const code = s.user?.employee_code?.toLowerCase() || '';
            const record_id = getRecordId(s).toLowerCase();

            let matchesSearch = true;
            if (query) {
                matchesSearch = name.includes(query) || code.includes(query) || record_id.includes(query) || g.manager_name.toLowerCase().includes(query);
            }

            let matchesStatus = true;
            if (filterStatus.value !== 'all') {
                matchesStatus = s.status === filterStatus.value;
            }

            let matchesDept = true;
            if (filterDepartment.value) {
                matchesDept = (s.user?.department === filterDepartment.value) || (s.department === filterDepartment.value);
            }

            let matchesLoc = true;
            if (filterLocation.value) {
                matchesLoc = (s.user?.location === filterLocation.value) || (s.location === filterLocation.value);
            }

            return matchesSearch && matchesStatus && matchesDept && matchesLoc;
        });
        return { ...g, appraisals: matchingAppraisals };
    }).filter(g => g.appraisals.length > 0);
});

// Create flat fallback for empty states and stats counters backwards compatibility
const filteredSubmissions = computed(() => {
    return groupedSubmissions.value.flatMap(g => g.appraisals);
});

const fetchSubmissions = async () => {
    loading.value = true;
    const { setIsLoading } = userstore; // Ensure we have the store setter
    // Use the store instance directly
    userstore.setIsLoading(true);
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
        userstore.setIsLoading(false);
    }
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

const downloadCSV = () => {
    if (!selectedAppraisal.value) return;
    const item = selectedAppraisal.value;
    const rows = [
        ['Property', 'Details'],
        ['Staff Name', item.user?.name || 'N/A'],
        ['Department', item.user?.department || 'N/A'],
        ['Designation', item.user?.job_title || 'N/A'],
        ['Cycle Year', item.year],
        ['Appraisal Status', item.status],
        ['Overall Metric', item.overall_rating || '0.0']
    ];
    
    // Add Competencies
    const appraisalData = typeof item.appraisal_data === 'string' ? JSON.parse(item.appraisal_data) : item.appraisal_data;
    if (appraisalData?.competencies) {
        rows.push([], ['Competency assessment', 'Score']);
        appraisalData.competencies.forEach(c => {
            rows.push([c.title, `Self: ${c.selfRating || 0} | Mgr: ${c.managerRating || 0}`]);
        });
    }

    let csvContent = "data:text/csv;charset=utf-8," 
        + rows.map(e => e.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
        
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `${item.user?.name || 'employee'}_pms_${item.year}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadSinglePDF = (appraisal) => {
    selectedAppraisal.value = appraisal;
    viewMode.value = 'protocol';
    setTimeout(() => {
        const res = downloadPDF();
        if (res && typeof res.then === 'function') {
            res.then(() => { viewMode.value = 'list'; }).catch(() => { viewMode.value = 'list'; });
        } else {
            // Fallback for script load frame delay
            setTimeout(() => { viewMode.value = 'list'; }, 2000);
        }
    }, 800);
};

const exportSubmissionsCSV = () => {
    const data = filteredSubmissions.value;
    if (!data.length) {
        showAlert('Info', 'No data to export with current filters.', 'info');
        return;
    }

    // Determine competency headers from the first item
    const firstAppraisal = parseSmart(data[0].appraisal_data || '{}');
    const comps = firstAppraisal?.competencies || [];
    
    const headers = [
        'Employee Code', 'Employee Name', 'Department', 'Location', 'Line Manager', 
        'Year', 'Status', 'Overall Rating', 'Submitted At',
        'Goal Title', 'Goal Description', 'Goal Purposes'
    ];
    
    // Add competency headers
    comps.forEach(c => {
        headers.push(`${c.title} (Self)`, `${c.title} (Mgr)`);
    });
    
    // Add feedback headers
    headers.push('Manager Feedback: Achievement', 'Manager Feedback: Initiatives', 'Manager Feedback: Next Steps', 'Manager Feedback: Improvements', 'Manager Feedback: Commentary');

    // Add Quarterly Data Headers
    [1, 2, 3, 4].forEach(q => {
        headers.push(`Q${q} Target Measures`, `Q${q} Start Date`, `Q${q} End Date`);
    });

    const rows = data.map(s => {
        const ad = parseSmart(s.appraisal_data || '{}');
        const row = [
            s.user?.employee_code || s.employee_code || s.user_id || 'N/A',
            s.user?.name || 'N/A',
            s.user?.department || s.department || 'N/A',
            s.user?.location || 'N/A',
            s.manager_name || 'Administrator',
            s.year,
            s.status,
            calculateAverageRating(s),
            s.submitted_at || s.created_at || 'N/A',
            s.title || 'N/A',
            Array.isArray(parseList(s.description)) ? parseList(s.description).join('; ') : (s.description || 'N/A'),
            Array.isArray(parseList(s.purposes)) ? parseList(s.purposes).join('; ') : (s.purposes || 'N/A')
        ];
        
        // Competency scores
        const competencyMap = ad?.competencies || [];
        comps.forEach((headerComp, idx) => {
             const c = competencyMap[idx] || {};
             row.push(c.selfRating || 0, c.managerRating || 0);
        });
        
        // Review Summaries
        const rs = ad?.review_summary || {};
        const ach = rs.A ? (Array.isArray(rs.A) ? rs.A.map(a => a.achievement).join('; ') : rs.A) : '';
        row.push(ach);
        row.push(rs.B ? (Array.isArray(rs.B) ? rs.B.join('; ') : rs.B) : '');
        row.push(rs.C ? (Array.isArray(rs.C) ? rs.C.join('; ') : rs.C) : '');
        row.push(rs.D ? (Array.isArray(rs.D) ? rs.D.join('; ') : rs.D) : '');
        row.push(rs.E ? (Array.isArray(rs.E) ? rs.E.join('; ') : rs.E) : '');

        // Add Quarterly Data
        const quarters = parseList(s.quarterly_tracking);
        [0, 1, 2, 3].forEach(idx => {
            const q = quarters[idx] || {};
            const measures = Array.isArray(parseList(q.target_measures)) ? parseList(q.target_measures).join('; ') : (q.target_measures || 'N/A');
            row.push(measures, q.start_date || 'N/A', q.end_date || 'N/A');
        });
        
        return row;
    });

    let csvContent = "\uFEFF" + [headers, ...rows].map(e => e.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
        
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `pms_comprehensive_export_${currentYear.value}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadPDF = () => {
    let element = document.getElementById('printable-report');
    if (viewMode.value === 'protocol') {
         element = document.getElementById('protocol-report');
    }
    if (!element) return;
    
    const runDownload = () => {
        const opt = {
          margin:       8,
          filename:     `${selectedAppraisal.value.user?.name || 'employee'}_pms_${selectedAppraisal.value.year}.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        return window.html2pdf().from(element).set(opt).save();
    };

    if (window.html2pdf) {
        return runDownload();
    } else {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.onload = runDownload;
        document.head.appendChild(script);
    }
};

onMounted(() => {
    fetchSubmissions();
});
</script>

<template>
    <div class="submissions-wrapper min-h-screen">
        <div v-if="viewMode === 'list'">
            <!-- Compact Premium Page Header -->
        <div class="relative overflow-hidden bg-[#1A237E] rounded-2xl mb-6 border border-white/5 shadow-xl animate-slide-up">
            <!-- Abstract Background Glows -->
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-purple-600/20 blur-[100px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-indigo-600/10 blur-[80px] rounded-full"></div>
            
            <div class="relative px-6 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/20 rotate-3 hover:rotate-0 transition-transform duration-500">
                        <i class="pi pi-inbox text-xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-white tracking-tight uppercase leading-none">Submissions Portal</h1>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <p class="text-purple-200/60 text-[9px] font-bold uppercase tracking-widest">Executive PMS Oversight</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                   
                </div>
            </div>
        </div>

                <!-- KPI Cards Deck -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 mt-2 animate-slide-up">
            <!-- Completed Card -->
            <div class="bg-[#14532D] text-white rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between h-32 transform hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 blur-2xl rounded-full"></div>
                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-100">completed</span>
                <h3 class="text-4xl font-black tracking-tighter">{{ stats.approved }}</h3>
                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-100">achieved</span>
            </div>
            
            <!-- In Progress Card -->
            <div class="bg-[#1D4ED8] text-white rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between h-32 transform hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 blur-2xl rounded-full"></div>
                <span class="text-[9px] font-black uppercase tracking-widest text-blue-100">in progress</span>
                <h3 class="text-4xl font-black tracking-tighter">{{ stats.pending }}</h3>
                <span class="text-[9px] font-black uppercase tracking-widest text-blue-100">working on</span>
            </div>

            <!-- Not Started Card -->
            <div class="bg-[#1E293B] text-white rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between h-32 transform hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 blur-2xl rounded-full"></div>
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-300">not started</span>
                <h3 class="text-4xl font-black tracking-tighter">{{ stats.total - stats.approved - stats.pending }}</h3>
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-300">pending action</span>
            </div>
        </div>

        <!-- Compact Governance Filter Bar -->
        <div class="bg-white rounded-2xl p-4 shadow-lg shadow-slate-100/60 border border-slate-100/80 mb-8 flex flex-col md:flex-row items-center justify-between gap-4 animate-slide-up" style="animation-delay: 200ms">
            <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto flex-1">
                <!-- Search -->
                <div class="relative w-full md:max-w-sm group">
                    <input v-model="searchQuery" type="text" placeholder="Search by name, code, or manager..." 
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-100/40 focus:bg-white transition-all placeholder:text-slate-300" />
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>

                <!-- Department Filter -->
                <div class="relative w-full md:max-w-[180px]">
                    <select v-model="filterDepartment" class="w-full appearance-none pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-xs font-semibold text-slate-600 cursor-pointer focus:ring-2 focus:ring-indigo-100/40 focus:bg-white transition-all">
                        <option value="">All Departments</option>
                        <option v-for="dept in depts" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                    </select>
                    <i class="pi pi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Location Filter -->
                <div class="relative w-full md:max-w-[180px]">
                    <select v-model="filterLocation" class="w-full appearance-none pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-xs font-semibold text-slate-600 cursor-pointer focus:ring-2 focus:ring-indigo-100/40 focus:bg-white transition-all">
                        <option value="">All Locations</option>
                        <option v-for="loc in branchs" :key="loc.id" :value="loc.name">{{ loc.name }}</option>
                    </select>
                    <i class="pi pi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto self-end md:self-auto">
                <!-- Status Filters -->
                <div class="flex items-center p-1 bg-slate-50 border border-slate-200/40 rounded-xl w-full md:w-auto">
                    <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-white shadow-sm text-indigo-700 border-slate-100' : 'text-slate-400 hover:text-slate-600 border-transparent'" class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all border">Total</button>
                    <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-white shadow-sm text-amber-600 border-slate-100' : 'text-slate-400 hover:text-amber-600 border-transparent'" class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all border">Pending</button>
                    <button @click="filterStatus = 'approved'" :class="filterStatus === 'approved' ? 'bg-white shadow-sm text-emerald-600 border-slate-100' : 'text-slate-400 hover:text-emerald-600 border-transparent'" class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all border">Reviewed</button>
                </div>

                <!-- Export All Button -->
                <button @click="exportSubmissionsCSV" 
                    class="h-[46px] px-6 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-[0.15em] flex items-center gap-3 transition-all hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-600/20 active:scale-95 border-none shadow-sm cursor-pointer ml-auto md:ml-0">
                    <i class="pi pi-file-excel text-xs"></i>
                    Export CSV
                </button>
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

            <!-- Advanced Submission Matrix (Table) -->
            <div v-else class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-100 overflow-hidden animate-slide-up" style="animation-delay: 400ms">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-700 border-b border-slate-200 uppercase tracking-widest text-[9px] font-black border-b border-indigo-950">
                                <th class="px-8 py-5">Line Manager</th>
                                <th class="px-8 py-5">Department</th>
                                <th class="px-8 py-5 text-center">Counter</th>
                                <th class="px-8 py-5 text-center">Avg Rating</th>
                                <th class="px-8 py-5 text-center">Oversight Status</th>
                                <th class="px-8 py-5 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <template v-for="(item, idx) in groupedSubmissions" :key="item.manager_name">
                                <tr class="group hover:bg-slate-50 transition-all duration-300 border-b border-slate-100/60 h-20">
                                    <td class="px-8 py-4">
                                        <div class="flex items-center gap-4">
                                            <button @click="toggleManager(item.manager_name)" class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95">
                                                <i class="pi" :class="expandedManagers.includes(item.manager_name) ? 'pi-chevron-down' : 'pi-chevron-right'"></i>
                                            </button>
                                            <div>
                                                <h4 class="text-sm font-black text-slate-800 tracking-tight cursor-pointer hover:text-indigo-600 transition-colors uppercase flex items-center gap-2"
                                                    @click="toggleManager(item.manager_name)">
                                                    {{ item.manager_name }}
                                                </h4>
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Assessor</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100/80 text-slate-600 text-[10px] font-extrabold border border-slate-100 group-hover:bg-white group-hover:border-indigo-100 transition-all">
                                            <i class="pi pi-shield text-slate-400 group-hover:text-indigo-500"></i>
                                            {{ item.department }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-4 text-center">
                                        <span class="text-xs font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">{{ item.appraisals.length }} index</span>
                                    </td>
                                    <td class="px-8 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-2 h-2 rounded-full" :class="getCompositeRating(item.appraisals) >= 4 ? 'bg-emerald-500' : (getCompositeRating(item.appraisals) >= 3 ? 'bg-amber-500' : 'bg-rose-500')"></div>
                                            <span class="text-sm font-black text-slate-800">{{ getCompositeRating(item.appraisals) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 text-center">
                                        <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border inline-flex items-center gap-2 shadow-sm"
                                            :class="getCompositeStatus(item.appraisals) === 'Reviewed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : getCompositeStatus(item.appraisals) === 'Rejected' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-amber-50 text-amber-700 border-amber-100'">
                                            <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="getCompositeStatus(item.appraisals) === 'Reviewed' ? 'bg-emerald-500' : getCompositeStatus(item.appraisals) === 'Rejected' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                                            {{ getCompositeStatus(item.appraisals) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <button @click="toggleManager(item.manager_name)" class="px-4 py-2.5 bg-indigo-50 border border-indigo-100 text-indigo-700 font-extrabold rounded-xl hover:bg-indigo-600 hover:text-white transition-all text-[10px] uppercase shadow-sm active:scale-95">
                                            {{ expandedManagers.includes(item.manager_name) ? 'Hide Goals' : 'Review Goals' }}
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Expandable Row Sub Table -->
                                <tr v-if="expandedManagers.includes(item.manager_name)">
                                    <td colspan="6" class="p-0 bg-slate-50/50">
                                        <div class="px-16 py-8 border-b border-slate-100 shadow-inner">
                                            <h5 class="text-xs font-black text-indigo-900 mb-4 flex items-center gap-2 uppercase tracking-wide">
                                                <i class="pi pi-list text-indigo-600"></i> Records Under Management: {{ item.manager_name }}
                                            </h5>
                                            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                                                <table class="w-full text-left">
                                                    <thead>
                                                        <tr class="bg-white border-b border-slate-100 text-[10px] text-slate-400 font-extrabold uppercase h-12">
                                                            <th class="px-6">Goal Details</th>
                                                            <th class="px-6 text-center">Record ID</th>
                                                            <th class="px-6 text-center">Smart Score</th>
                                                            <th class="px-6 text-center">Progress</th>
                                                            <th class="px-6 text-center">Status</th>
                                                            <th class="px-6 text-right">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-50">
                                                        <tr v-for="appraisal in item.appraisals" :key="appraisal.id" class="hover:bg-slate-50 transition-all duration-200 h-16">
                                                            <td class="px-6 py-3">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                                                    <div>
                                                                        <span class="text-xs font-black text-slate-800 uppercase">{{ appraisal.employee?.name || 'Staff' }}</span>
                                                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tight mt-0.5">{{ appraisal.title || appraisal.employee?.job_title || 'YEARLY SMART GOALS SETTING' }}</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-3 text-center">
                                                                <span class="text-[10px] font-black text-slate-800">{{ getRecordId(appraisal) }}</span>
                                                            </td>
                                                            <td class="px-6 py-3 text-center">
                                                                <div class="flex items-center justify-center gap-1">
                                                                    <span v-for="label in ['S','M','A','R','T']" :key="label" 
                                                                        :class="getSmartClass(label, appraisal)"
                                                                        class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] transition-all">
                                                                        {{ label }}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-3 text-center">
                                                                <div class="flex items-center justify-center gap-2">
                                                                    <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                                        <div class="bg-indigo-600 h-1.5 rounded-full" :style="{ width: getTimeProgress(appraisal) + '%' }"></div>
                                                                    </div>
                                                                    <span class="text-[9px] font-black text-slate-700">{{ getTimeProgress(appraisal) }}%</span>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-3 text-center">
                                                                <span class="px-4 py-1.5 rounded-full text-[8px] font-black uppercase tracking-widest border border-indigo-100 bg-indigo-50/50 text-indigo-700 shadow-sm inline-flex items-center gap-1.5">
                                                                    <i class="pi pi-send text-[8px]"></i>
                                                                    {{ appraisal.status }}
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-3 text-right">
                                                                <div class="flex items-center justify-end gap-2 text-slate-400">
                                                                    <!-- View Icon -->
                                                                    <button @click="openActionModal(appraisal)" class="hover:text-indigo-600 transition-colors p-1 flex items-center gap-1.5 px-3 py-1 bg-slate-50 rounded-lg hover:bg-indigo-50 group transition-all border border-slate-100">
                                                                        <i class="pi pi-eye text-[10px] group-hover:scale-110 transition-transform"></i>
                                                                        <span class="text-[9px] font-black uppercase tracking-widest">Preview</span>
                                                                    </button>
                                                                    <!-- Download Icon Direct -->
                                                                    <button @click="downloadSinglePDF(appraisal)" class="hover:text-emerald-600 transition-colors p-1 flex items-center gap-1.5 px-3 py-1 bg-slate-50 rounded-lg hover:bg-emerald-50 group transition-all border border-slate-100">
                                                                        <i class="pi pi-download text-[10px] group-hover:scale-110 transition-transform"></i>
                                                                        <span class="text-[9px] font-black uppercase tracking-widest">PDF</span>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- End viewMode === 'list' -->

        <!-- High-End Review Matrix (Dialog) -->
        <Dialog v-model:visible="showActionModal" modal :style="{ width: '1200px', maxWidth: '98vw', border: 'none' }" :showHeader="false" class="p-0 overflow-hidden rounded-[3rem] shadow-2xl border-none" :contentStyle="{ padding: '0', backgroundColor: 'transparent' }">
            <div v-if="selectedAppraisal" class="bg-white flex flex-col h-full max-h-[90vh] overflow-hidden rounded-[2rem]">
                <!-- Action Header (Hidden on Print) -->
                <div class="px-10 py-6 border-b border-slate-100 flex justify-between items-center bg-white no-print">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i class="pi pi-shield text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-full bg-indigo-600 text-white text-[8px] font-black uppercase tracking-tighter inline-block">Appraisal Dossier</span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[8px] font-black uppercase tracking-tighter inline-block">ID: {{ getRecordId(selectedAppraisal) }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <h2 class="text-sm font-black text-slate-800 uppercase tracking-tight leading-none">Record Verification</h2>
                                <div class="flex items-center gap-1.5 px-2 py-0.5 bg-emerald-50 rounded-lg border border-emerald-100">
                                    <span class="text-[9px] font-black text-emerald-600 uppercase">Score: {{ calculateAverageRating(selectedAppraisal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="downloadSinglePDF(selectedAppraisal)" class="px-6 py-3 bg-[#1A237E] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.15em] flex items-center gap-2 transition-all hover:bg-slate-900 active:scale-95 border-none cursor-pointer shadow-lg shadow-indigo-900/10 no-print">
                            <i class="pi pi-download text-xs"></i> Download Professional PDF
                        </button>
                        <div class="w-px h-8 bg-slate-200 mx-2 no-print"></div>
                        <button @click="showActionModal = false" class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition-colors border-none cursor-pointer group no-print">
                            <i class="pi pi-times text-slate-400 group-hover:text-slate-600 group-hover:rotate-90 transition-all"></i>
                        </button>
                    </div>
                </div>

                <!-- Redesigned Dossier Scope -->
                <div v-if="viewMode === 'list'" id="printable-report" class="flex-1 overflow-y-auto p-0 bg-[#F9FAFB]">
                    
                    <!-- 1. High-Impact Profile Header -->
                    <div class="relative bg-white border-b border-slate-200 shadow-sm overflow-hidden">
                        <!-- Background Accent -->
                        <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-indigo-50/50 to-transparent"></div>
                        
                        <div class="relative px-12 py-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                            <div class="flex items-center gap-8">
                                <!-- Modern Avatar with Ring -->
                                <div class="relative group">
                                    <div class="absolute -inset-1.5 bg-gradient-to-tr from-indigo-600 to-purple-500 rounded-[2.5rem] blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                                    <div class="relative w-28 h-28 rounded-[2rem] bg-white p-1.5 shadow-xl border border-slate-100">
                                        <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(selectedAppraisal.user?.name || 'User')+'&background=1e293b&color=fff&size=256'" class="w-full h-full object-cover rounded-[1.5rem]" />
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full border-4 border-white bg-emerald-500 flex items-center justify-center shadow-lg" v-if="selectedAppraisal.status === 'approved'">
                                        <i class="pi pi-check text-white text-[10px] font-black"></i>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full border-4 border-white bg-amber-500 flex items-center justify-center shadow-lg" v-else>
                                        <i class="pi pi-clock text-white text-[10px] font-black"></i>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase leading-none">{{ selectedAppraisal.user?.name }}</h1>
                                        <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-100 rounded-full border border-slate-200">
                                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">ID: {{ getRecordId(selectedAppraisal) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="flex items-center gap-1.5 text-xs font-black text-indigo-600 uppercase tracking-wider">
                                            <i class="pi pi-briefcase text-[10px]"></i>
                                            {{ selectedAppraisal.user?.job_title || 'Employee' }}
                                        </span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                        <span class="flex items-center gap-1.5 text-xs font-bold text-slate-500 uppercase">
                                            <i class="pi pi-map-marker text-[10px]"></i>
                                            {{ selectedAppraisal.user?.location || 'Main Office' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-600 text-[9px] font-black uppercase tracking-widest border border-purple-100 flex items-center gap-1.5">
                                            <i class="pi pi-at text-[8px]"></i> Direct Message
                                        </span>
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase tracking-widest border border-indigo-100 flex items-center gap-1.5">
                                            <i class="pi pi-phone text-[8px]"></i> Call
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm text-center min-w-[140px] transform hover:scale-105 transition-transform duration-300">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 block">Manager Rating</span>
                                    <div class="flex items-baseline justify-center gap-1">
                                        <span class="text-4xl font-black text-indigo-600 tracking-tighter">{{ calculateAverageRating(selectedAppraisal) }}</span>
                                        <span class="text-xs font-bold text-slate-300">/ 5.0</span>
                                    </div>
                                </div>
                                <div class="bg-[#1A237E] p-5 rounded-3xl border border-indigo-900/10 shadow-xl text-center min-w-[140px] transform hover:scale-105 transition-transform duration-300">
                                    <span class="text-[9px] font-black text-indigo-200/60 uppercase tracking-[0.2em] mb-2 block">Stage Detail</span>
                                    <div class="text-sm font-black text-white uppercase tracking-wider">{{ selectedAppraisal.status }}</div>
                                    <div class="w-12 h-1 bg-indigo-400/30 rounded-full mx-auto mt-2 overflow-hidden">
                                        <div class="bg-indigo-400 h-full w-full" :class="{ 'w-3/4': selectedAppraisal.status === 'pending', 'w-full': selectedAppraisal.status === 'approved' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Integrated Appraisal Content -->
                    <div class="p-12 space-y-12">
                        
                        <!-- Page Context: Cycle Progression -->
                        <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-8">Dossier Evaluation Cycle</h3>
                            <div class="flex items-center w-full px-4">
                                <template v-for="(stage, sIdx) in ['Drafting', 'Self Review', 'Manager Audit', 'Final Submission']" :key="stage">
                                    <div class="flex items-center flex-1 last:flex-none">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-12 h-12 rounded-[1.25rem] flex items-center justify-center border-2 transition-all duration-700"
                                                :class="sIdx <= 2 ? 'bg-indigo-600 border-indigo-600 text-white shadow-xl shadow-indigo-100' : 'bg-white border-slate-100 text-slate-300'">
                                                <i :class="sIdx <= 2 ? 'pi pi-check text-sm' : 'pi pi-circle text-xs'" class="font-black"></i>
                                            </div>
                                            <span class="text-[9px] font-black uppercase tracking-widest whitespace-nowrap" :class="sIdx <= 2 ? 'text-indigo-600' : 'text-slate-300'">{{ stage }}</span>
                                        </div>
                                        <div v-if="sIdx < 3" class="h-1.5 flex-1 mx-6 rounded-full transition-all duration-1000" :class="sIdx < 2 ? 'bg-indigo-600' : 'bg-slate-100'"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Main Grid Structure -->
                        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                            
                            <!-- Sidebar: Metadata & Identity -->
                            <div class="xl:col-span-1 space-y-8">
                                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm group hover:border-indigo-100 transition-colors">
                                    <div class="flex items-center gap-3 mb-8">
                                        <div class="w-8 h-8 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500">
                                            <i class="pi pi-id-card text-sm"></i>
                                        </div>
                                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Metadata</h3>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        <div class="group/item">
                                            <label class="block text-[8px] font-black text-slate-300 uppercase tracking-[0.2em] mb-1.5 group-hover/item:text-indigo-400 transition-colors">Employee Code</label>
                                            <p class="text-[11px] font-extrabold text-slate-700 uppercase tracking-tight">{{ selectedAppraisal.user?.employee_code || '---' }}</p>
                                        </div>
                                        <div class="group/item">
                                            <label class="block text-[8px] font-black text-slate-300 uppercase tracking-[0.2em] mb-1.5 group-hover/item:text-indigo-400 transition-colors">Department Unit</label>
                                            <p class="text-[11px] font-extrabold text-slate-700 uppercase tracking-tight">{{ selectedAppraisal.user?.department || 'NA' }}</p>
                                        </div>
                                        <div class="group/item">
                                            <label class="block text-[8px] font-black text-slate-300 uppercase tracking-[0.2em] mb-1.5 group-hover/item:text-indigo-400 transition-colors">Cycle Year</label>
                                            <p class="text-[11px] font-extrabold text-slate-700 uppercase tracking-tight">{{ selectedAppraisal.year }} Period</p>
                                        </div>
                                        <div class="group/item border-t border-slate-50 pt-6">
                                            <label class="block text-[8px] font-black text-slate-300 uppercase tracking-[0.2em] mb-1.5 group-hover/item:text-indigo-400 transition-colors">Line Manager Oversight</label>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-[10px] font-black">
                                                    {{ (selectedAppraisal.manager_name || 'A').charAt(0) }}
                                                </div>
                                                <p class="text-[11px] font-extrabold text-slate-700 uppercase leading-none">{{ selectedAppraisal.manager_name || 'Administrator' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-indigo-900 rounded-[2.5rem] p-10 text-white shadow-xl shadow-indigo-950/20 relative overflow-hidden group">
                                     <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/5 blur-[50px] rounded-full group-hover:scale-150 transition-transform duration-1000"></div>
                                     <span class="text-[8px] font-black text-indigo-300 uppercase tracking-[0.2em] mb-4 block">Primary objective</span>
                                     <h4 class="text-sm font-black uppercase leading-snug tracking-tight">{{ selectedAppraisal.title }}</h4>
                                     <div class="mt-8 flex items-center gap-2">
                                         <span class="px-2.5 py-1 rounded-lg bg-white/10 text-[8px] font-black uppercase border border-white/5 tracking-widest">Strategic</span>
                                         <span class="px-2.5 py-1 rounded-lg bg-white/10 text-[8px] font-black uppercase border border-white/5 tracking-widest">{{ selectedAppraisal.category || 'Standard' }}</span>
                                     </div>
                                </div>
                            </div>

                            <!-- Content Area: Metrics & Performance -->
                            <div class="xl:col-span-2 space-y-8">
                                <!-- Quarterly Tracker -->
                                <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm relative overflow-hidden">
                                    <div class="flex items-center justify-between mb-10">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-500">
                                                <i class="pi pi-calendar-clock text-base"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Quarterly Execution Tracking</h3>
                                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">Audit Trail & Timeline Verification</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div v-for="(quarter, index) in parseList(selectedAppraisal.quarterly_tracking)" :key="index" class="p-6 bg-slate-50/50 rounded-[2.25rem] border border-slate-100 group hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 hover:border-indigo-100 transition-all duration-500 transform hover:-translate-y-1">
                                            <div class="flex justify-between items-center mb-6">
                                                <span class="text-[10px] font-black text-slate-900 uppercase group-hover:text-indigo-600 transition-colors">{{ quarter.quarter ? quarter.quarter.toUpperCase() : 'Q'+(index+1) }}</span>
                                                <div class="w-2 h-2 rounded-full bg-indigo-200 group-hover:bg-indigo-500 group-hover:scale-125 transition-all"></div>
                                            </div>
                                            <div class="space-y-4">
                                                <div>
                                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest block mb-2">Metrics</span>
                                                    <p v-for="(measure, mIdx) in parseList(quarter.target_measures)" :key="mIdx" class="text-[10px] font-bold text-slate-600 leading-tight mb-2 last:mb-0">
                                                        {{ measure }}
                                                    </p>
                                                </div>
                                                <div class="pt-4 border-t border-slate-100 flex flex-col gap-1.5 text-[8px] font-black text-slate-400 uppercase">
                                                    <div class="flex items-center gap-2 font-black text-slate-300 uppercase">
                                                       <span>Start: {{ formatDate(quarter.start_date) }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 font-black text-slate-500 uppercase">
                                                       <span>Due: {{ formatDate(quarter.end_date) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Competency Matrix -->
                                <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm overflow-hidden">
                                    <div class="flex items-center justify-between mb-8">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-500">
                                                <i class="pi pi-sparkles text-base"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Competency Assessment</h3>
                                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">Behavioral & Professional Unit Audit</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="overflow-hidden rounded-[2.25rem] border border-slate-100 bg-white">
                                        <table class="w-full text-left">
                                            <thead>
                                                <tr class="bg-slate-50/50 border-b border-slate-100 h-14 text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">
                                                    <th class="px-10">Competency Unit</th>
                                                    <th class="w-32 text-center px-4">Self Score</th>
                                                    <th class="w-32 text-center px-10 bg-indigo-50/30 text-indigo-600 border-l border-indigo-100/10">Mgr Score</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-50">
                                                <tr v-for="(comp, cIdx) in parseSmart(selectedAppraisal.appraisal_data)?.competencies" :key="cIdx" class="h-16 hover:bg-slate-50/30 transition-colors group">
                                                    <td class="px-10">
                                                        <span class="text-[11px] font-black text-slate-700 uppercase group-hover:text-indigo-600 transition-colors">{{ comp.title }}</span>
                                                    </td>
                                                    <td class="px-4 text-center">
                                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100/50 rounded-xl border border-slate-100 text-[10px] font-black text-slate-400">
                                                            {{ comp.selfRating || '0' }}<span class="opacity-30">/ 5</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-10 text-center bg-indigo-50/10 border-l border-indigo-100/10">
                                                        <div class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-indigo-600 rounded-xl text-[10px] font-black text-white shadow-xl shadow-indigo-100/50 ring-4 ring-white">
                                                            {{ comp.managerRating || '0' }}<span class="text-indigo-300">/ 5</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Qualitative Narratives (Full Width) -->
                        <div class="bg-white rounded-[2.5rem] p-12 border border-slate-100 shadow-sm">
                            <div class="flex items-center gap-4 mb-12">
                                <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500">
                                    <i class="pi pi-comments text-base"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Executive Feedbacks & Summaries</h3>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">Qualitative Narrative Analysis</p>
                                </div>
                            </div>

                            <div class="space-y-12">
                                <div class="space-y-8">
                                    <span class="inline-block px-5 py-2 rounded-2xl bg-orange-50 text-orange-600 text-[10px] font-black uppercase tracking-widest border border-orange-100">Achievement Narrative Report</span>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div v-for="(item, idx) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.A" :key="idx" class="p-10 bg-[#FFFBF7] rounded-[3rem] border border-orange-100/50 flex flex-col gap-8 relative overflow-hidden group hover:shadow-2xl hover:shadow-orange-100/50 transition-all duration-500">
                                            <div class="absolute -top-6 -right-6 w-24 h-24 bg-orange-100/20 rounded-full flex items-center justify-center font-black text-orange-200 text-4xl rotate-12">{{ idx+1 }}</div>
                                            <div class="relative">
                                                <label class="block text-[8px] font-black text-orange-400 uppercase tracking-[0.2em] mb-3">Target Realization</label>
                                                <p class="text-[12px] font-black text-slate-800 tracking-tight leading-relaxed uppercase">{{ item.achievement || '---' }}</p>
                                            </div>
                                            <div class="relative pt-8 border-t border-orange-100/50">
                                                <label class="block text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Qualitative Context</label>
                                                <p class="text-[12px] font-bold text-slate-500 leading-relaxed italic">"{{ item.target || 'N/A' }}"</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 pt-10 border-t border-slate-50">
                                    <div v-for="sec in ['B', 'C', 'D', 'E']" :key="sec" class="space-y-6">
                                        <div class="flex items-center gap-3 px-2">
                                           <div class="w-2 h-2 rounded-full" :class="sec === 'E' ? 'bg-indigo-500' : 'bg-slate-300'"></div>
                                           <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ sec === 'B' ? 'Initiatives Taken' : sec === 'C' ? 'Strategic Next Steps' : sec === 'D' ? 'Growth Focus Areas' : 'Executive Observations' }}</span>
                                        </div>
                                        <div class="p-10 bg-slate-50/50 rounded-[3rem] border border-slate-100 min-h-[160px] group hover:bg-white transition-colors duration-500">
                                            <p v-for="(text, ix) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.[sec]" :key="ix" class="text-[11px] font-extrabold text-slate-600 leading-relaxed mb-4 last:mb-0 uppercase tracking-tight">
                                                {{ text || 'Official response pending record entry.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </div> <!-- End Footer Authenticity Trace -->
                    </div> <!-- End printable-report (Dossier View) -->

                    <!-- FORMAL PROTOCOL REPORT (Audit View) -->
                    <div v-if="viewMode === 'protocol'" class="bg-gray-50 flex-1 overflow-y-auto p-8 md:p-12 print:p-0 print:bg-white animate-fadeIn">

                <div class="max-w-[1000px] mx-auto mb-8 flex justify-between items-center print:hidden">
                    <button @click="viewMode = 'list'" class="flex items-center gap-2 text-black hover:text-indigo-900 font-bold transition-colors border-none bg-transparent cursor-pointer">
                        <i class="pi pi-arrow-left"></i> Return to Hub
                    </button>
                    <div class="flex items-center gap-3">
                        <button @click="downloadCSV" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold flex items-center gap-2 shadow-lg hover:bg-emerald-700 transition-all text-sm border-none cursor-pointer">
                            <i class="pi pi-file-excel"></i> Download Protocol CSV
                        </button>
                        <button @click="downloadPDF" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold flex items-center gap-2 shadow-lg hover:bg-indigo-700 transition-all text-sm border-none cursor-pointer">
                            <i class="pi pi-file-pdf"></i> Download Protocol PDF
                        </button>
                    </div>
                </div>

                <div id="protocol-report" class="max-w-[1000px] mx-auto bg-white shadow-2xl rounded-none border border-slate-200 p-16 print:shadow-none print:border-none print:p-0">
                    <div class="flex justify-between items-start border-b-2 border-slate-900 pb-10 mb-12">
                        <div><h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase mb-2">Performance Management System</h1><p class="text-sm font-bold text-slate-500 uppercase tracking-[0.2em]">Official Strategic Goal Protocol & Audit Trail</p></div>
                        <div class="text-right"><div class="text-2xl font-black text-slate-900 mb-1">FY {{ selectedAppraisal.year }}</div><div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Document ID: PMS-{{ selectedAppraisal.id }}-GFL</div></div>
                    </div>


                    <!-- I. Personnel Identification (Screenshot 2 Top Identity) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border-2 border-slate-900 mb-8 rounded-xl overflow-hidden">
                        <!-- Candidate Area -->
                        <div class="border-r-2 border-slate-900">
                            <div class="bg-slate-900 px-5 py-2 flex justify-between items-center">
                                <h4 class="font-black text-[10px] text-white uppercase tracking-widest">01. Candidate Identification</h4>
                                <i class="pi pi-user text-white text-[10px]"></i>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Full Legal Name</label>
                                    <div class="text-sm font-black text-slate-800 uppercase">{{ selectedAppraisal.user?.name || '---' }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Department</label>
                                        <div class="text-[10px] font-black text-slate-700 uppercase">{{ selectedAppraisal.user?.department || 'Operations' }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Code / ID</label>
                                        <div class="text-[10px] font-black text-slate-700 uppercase">{{ selectedAppraisal.user?.employee_code || 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Manager Area -->
                        <div class="bg-slate-50/50">
                            <div class="bg-slate-800 px-5 py-2 flex justify-between items-center">
                                <h4 class="font-black text-[10px] text-white uppercase tracking-widest">02. Authorized Evaluator</h4>
                                <i class="pi pi-shield text-white text-[10px]"></i>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Line Manager Name</label>
                                    <div class="text-sm font-black text-slate-800 uppercase">{{ selectedAppraisal.manager_name || 'Administrator' }}</div>
                                </div>
                                <div>
                                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Validation Status</label>
                                    <div class="inline-flex items-center gap-2 px-2 py-1 bg-emerald-600 text-white text-[9px] font-black uppercase rounded">
                                        <i class="pi pi-verified text-[8px]"></i> Verified Record
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- II. Strategic Goal Details & SMART Side (Screenshot 2 Main setup) -->
                    <div class="mb-8">
                         <div class="bg-slate-900 px-5 py-2 rounded-t-xl">
                              <h4 class="font-black text-[10px] text-white uppercase tracking-widest">03. Strategic Objective Details</h4>
                         </div>
                         <div class="border-2 border-slate-900 border-t-0 rounded-b-xl overflow-hidden grid grid-cols-3 divide-x-2 divide-slate-900">
                             <!-- Left 2 Cols: Goal Cards -->
                             <div class="col-span-2 divide-y-2 divide-slate-900">
                            <!-- GOALS -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#E8EAF6] px-5 py-3 border-b border-[#C5CAE9]">
                                    <h4 class="font-black text-sm text-[#1A237E] uppercase tracking-wide">GOALS</h4>
                                </div>
                                <div class="p-5 space-y-4">
                                     <div>
                                         <label class="block text-[10px] font-black text-[#1A237E] uppercase mb-1">Goals Title</label>
                                         <div class="p-3 bg-[#F8FAFC] border border-slate-100 rounded-lg text-xs font-black text-slate-800">{{ selectedAppraisal.title }}</div>
                                     </div>
                                     <div class="space-y-2">
                                         <label class="block text-[10px] font-black text-[#3949AB] uppercase mb-1">Goals Description</label>
                                         <div v-for="(desc, index) in parseList(selectedAppraisal.description)" :key="index" class="flex gap-3">
                                              <div class="w-7 h-7 rounded-lg bg-[#E8EAF6] flex items-center justify-center text-[#1A237E] font-black text-xs border border-[#C5CAE9] shrink-0 mt-0.5">{{ index+1 }}</div>
                                              <div class="p-3 bg-white border border-slate-100 rounded-lg text-xs font-bold text-slate-700 flex-1">{{ desc }}</div>
                                         </div>
                                     </div>
                                </div>
                            </div>

                            <!-- PURPOSES -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#B2DFDB] px-5 py-3 border-b border-[#80CBC4]">
                                     <h4 class="font-black text-sm text-[#004D40] uppercase tracking-wide">PURPOSES</h4>
                                </div>
                                <div class="p-5 space-y-2">
                                     <div v-for="(p, index) in parseList(selectedAppraisal.purposes)" :key="index" class="flex gap-3">
                                          <div class="w-7 h-7 rounded-lg bg-[#E0F2F1] text-[#00695C] flex items-center justify-center border border-[#B2DFDB] shrink-0 mt-0.5"><i class="pi pi-check text-[10px] font-black"></i></div>
                                          <div class="p-3 bg-white border border-slate-100 rounded-lg text-xs font-semibold text-slate-700 flex-1">{{ p }}</div>
                                     </div>
                                </div>
                            </div>

                            <!-- CHALLENGES -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#FFE0B2] px-5 py-3 border-b border-[#FFCC80]">
                                     <h4 class="font-black text-sm text-[#E65100] uppercase tracking-wide">CHALLENGES</h4>
                                </div>
                                <div class="p-5 space-y-2">
                                     <div v-for="(c, index) in parseList(selectedAppraisal.challenges)" :key="index" class="flex gap-3">
                                          <div class="w-7 h-7 rounded-lg bg-[#FFF3E0] text-[#E65100] flex items-center justify-center border border-[#FFE0B2] shrink-0 mt-0.5"><i class="pi pi-exclamation-triangle text-[10px]"></i></div>
                                          <div class="p-3 bg-white border border-slate-100 rounded-lg text-xs font-semibold text-slate-700 flex-1">{{ c }}</div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right 1 Col: Smart Side Checklist -->
                        <div class="col-span-1">
                             <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden sticky top-6">
                                  <div class="bg-[#1A237E] px-4 py-3"><h4 class="font-black text-xs text-white uppercase">MY GOAL IS...</h4></div>
                                  <div class="divide-y divide-gray-100">
                                       <div v-for="criteria in smartLabels" :key="criteria.key" class="flex items-center justify-between px-4 py-3.5" :class="parseSmart(selectedAppraisal.smart_criteria)[criteria.key] ? 'bg-green-50/50' : ''">
                                            <span class="font-black text-xs text-slate-700">{{ criteria.label }}</span>
                                            <div class="flex items-center gap-3">
                                                 <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs text-white" :class="criteria.color.split(' ')[0]">{{ criteria.short }}</div>
                                                 <i class="pi" :class="parseSmart(selectedAppraisal.smart_criteria)[criteria.key] ? 'pi-check-circle text-green-600 text-sm' : 'pi-circle text-gray-200 text-sm'"></i>
                                            </div>
                                       </div>
                                  </div>
                             </div>
                        </div>
                         </div>
                    </div>

                    <!-- III. Quarterly Execution Matrix (Compact) -->
                    <div class="mb-8 border-2 border-slate-900 rounded-xl overflow-hidden">
                         <div class="bg-slate-900 px-5 py-2 flex justify-between items-center">
                              <h2 class="text-[10px] font-black text-white uppercase tracking-widest leading-none">04. Operational Execution Timeline</h2>
                              <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter italic">Quarterly Performance Audit</span>
                         </div>
                         <table class="w-full border-collapse">
                              <thead class="bg-slate-100 border-b-2 border-slate-900">
                                   <tr>
                                        <th class="p-3 text-[9px] font-black uppercase text-left border-r border-slate-200">Index</th>
                                        <th class="p-3 text-[9px] font-black uppercase text-left border-r border-slate-200">Reporting Window</th>
                                        <th class="p-3 text-[9px] font-black uppercase text-left">Strategic Target Measurement</th>
                                   </tr>
                              </thead>
                              <tbody class="divide-y border-slate-200 italic">
                                   <tr v-for="(q, index) in parseList(selectedAppraisal.quarterly_tracking)" :key="index">
                                        <td class="p-4 border-r border-slate-200 text-[10px] font-black uppercase bg-slate-50/50 w-24">Quarter {{ index+1 }}</td>
                                        <td class="p-4 border-r border-slate-200 text-[10px] font-bold text-slate-600 w-48">{{ formatDate(q.start_date) }} <br>to {{ formatDate(q.end_date) }}</td>
                                        <td class="p-4 text-xs text-slate-700">
                                             <div v-for="(m, mi) in parseList(q.target_measures)" :key="mi" class="mb-1 last:mb-0">
                                                  <span class="inline-block w-1.5 h-1.5 bg-slate-900 rounded-full mr-2"></span> {{ m }}
                                             </div>
                                        </td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>

                    <!-- IV. Competencies Replicated Style (Screenshot 3 step 3 setup) -->
                    <div v-if="selectedAppraisal.appraisal_data" class="mb-8 print:break-before-page p-0 border-2 border-slate-900 rounded-xl overflow-hidden">
                        <div class="bg-slate-900 text-white text-[10px] font-black uppercase flex items-center h-10 px-6 tracking-widest">
                             <div class="flex-1">05. Professional Competency Audit</div>
                             <div class="w-20 text-center">WEIGHT</div>
                             <div class="w-20 text-center">SELF</div>
                             <div class="w-20 text-center">MANAGER</div>
                             <div class="w-20 text-center">FINAL</div>
                        </div>
                        <div class="bg-white divide-y-2 divide-slate-900">
                             <div class="bg-slate-100 flex items-center h-8 px-6 text-[8px] font-black text-slate-400 uppercase tracking-tighter border-b-2 border-slate-900">
                                  <div class="flex-1">Detailed Competency Markers</div>
                                  <div class="w-20 text-center">WT%</div>
                                  <div class="w-20 text-center">Score (1-5)</div>
                                  <div class="w-20 text-center">Score (1-5)</div>
                                  <div class="w-20 text-center">Weighted</div>
                             </div>
                             <div v-for="(comp, cIdx) in parseSmart(selectedAppraisal.appraisal_data)?.competencies" :key="cIdx" class="flex items-center px-6 py-4 hover:bg-slate-50 transition-colors">
                                  <div class="flex-1">
                                       <span class="text-xs font-black text-slate-800">{{ cIdx+1 }}. {{ comp.title }}</span>
                                  </div>
                                  <div class="w-20 text-center text-xs font-black text-slate-400">{{ comp.weight }}%</div>
                                  <div class="w-20 text-center text-xs font-black text-slate-600">{{ comp.selfRating || '0' }} / 5</div>
                                  <div class="w-20 text-center text-sm font-black text-indigo-600">{{ comp.managerRating || '0' }} / 5</div>
                                  <div class="w-20 text-center font-black text-slate-900 bg-slate-50 text-xs py-1 rounded">
                                       {{ ((comp.managerRating || 0) * (comp.weight / 100)).toFixed(1) }}
                                  </div>
                             </div>
                        </div>
                    </div>

                    <!-- V. Qualitative Narratives & Executive Feedback -->
                    <div class="space-y-4">
                         <div class="bg-slate-900 px-5 py-2 rounded-t-xl">
                              <h4 class="font-black text-[10px] text-white uppercase tracking-widest">06. Executive Feedback & Performance Commentary</h4>
                         </div>
                         <div class="border-2 border-slate-900 border-t-0 rounded-b-xl overflow-hidden divide-y-2 divide-slate-900">
                            <!-- Section A -->
                            <div class="grid grid-cols-4 divide-x-2 divide-slate-900">
                                <div class="col-span-1 bg-slate-50 p-4">
                                     <span class="text-[9px] font-black text-slate-900 uppercase">Marker A</span>
                                     <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Goal Achievement vs. Target</p>
                                </div>
                                <div class="col-span-3 p-4 space-y-3">
                                     <div v-for="(item, idx) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.A" :key="idx" class="p-3 bg-slate-50 border border-slate-200 rounded flex gap-4">
                                         <div class="w-5 h-5 rounded bg-slate-200 text-slate-600 font-black flex items-center justify-center text-[9px] shrink-0">{{ idx+1 }}</div>
                                         <div class="grid grid-cols-2 gap-4 flex-1 text-[10px]">
                                              <div><label class="block text-[7px] font-black text-slate-400 uppercase mb-0.5">Achievement</label><p class="font-bold text-slate-800 leading-tight">{{ item.achievement || '---' }}</p></div>
                                              <div><label class="block text-[7px] font-black text-slate-400 uppercase mb-0.5">Target</label><p class="font-bold text-slate-800 leading-tight">{{ item.target || '---' }}</p></div>
                                         </div>
                                     </div>
                                </div>
                            </div>

                            <!-- Section B -->
                            <div class="grid grid-cols-4 divide-x-2 divide-slate-900">
                                <div class="col-span-1 bg-slate-50 p-4">
                                     <span class="text-[9px] font-black text-slate-900 uppercase">Marker B</span>
                                     <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Additional Initiatives</p>
                                </div>
                                <div class="col-span-3 p-4">
                                     <p v-for="(text, ix) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.B" :key="ix" class="mb-2 last:mb-0 text-[10px] font-bold text-slate-700 leading-relaxed">• {{ text || 'No response provided.' }}</p>
                                </div>
                            </div>

                            <!-- Section C -->
                            <div class="grid grid-cols-4 divide-x-2 divide-slate-900">
                                <div class="col-span-1 bg-slate-50 p-4">
                                     <span class="text-[9px] font-black text-slate-900 uppercase">Marker C</span>
                                     <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Future Planning</p>
                                </div>
                                <div class="col-span-3 p-4">
                                     <p v-for="(text, ix) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.C" :key="ix" class="mb-2 last:mb-0 text-[10px] font-bold text-slate-700 leading-relaxed">• {{ text || 'No response provided.' }}</p>
                                </div>
                            </div>

                            <!-- Section D-E consolidated Official Verdict -->
                            <div class="grid grid-cols-2 divide-x-2 divide-slate-900">
                                <div class="p-4 bg-slate-50/30">
                                     <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Area of Improvement (Assessor's Note)</label>
                                     <div class="p-4 bg-white border border-slate-200 rounded min-h-[60px] text-[10px] font-bold text-slate-800 italic">
                                          <p v-for="(text, ix) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.D" :key="ix" class="mb-1 last:mb-0">{{ text }}</p>
                                     </div>
                                </div>
                                <div class="p-4 bg-slate-50/50">
                                     <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Final Feedback (Verified Evaluator)</label>
                                     <div class="p-4 bg-slate-900 text-white rounded min-h-[60px] text-[10px] font-bold">
                                          <p v-for="(text, ix) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.E" :key="ix" class="mb-1 last:mb-0">{{ text }}</p>
                                     </div>
                                </div>
                            </div>
                         </div>
                    </div>

                    <!-- VI. Authentic Validation & Signatures -->
                    <div class="mt-16 pt-10 border-t-4 border-slate-900">
                        <div class="grid grid-cols-2 gap-16">
                            <div class="space-y-4">
                                <div class="h-20 border-b-2 border-slate-200 flex items-end pb-2">
                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-[0.2em]">Affix Official Signature Here</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-[10px] font-black text-slate-900 uppercase tracking-widest">{{ selectedAppraisal.user?.name }}</p>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase">Employee / Candidate</p>
                                    </div>
                                    <p class="text-[9px] font-black text-slate-800 uppercase tracking-tighter">Date: ____/____/2026</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="h-20 border-b-2 border-slate-200 flex items-end justify-end pb-2">
                                    <div class="w-24 h-24 border-2 border-indigo-100 rounded-full flex items-center justify-center -mb-8 mr-4 opacity-40">
                                        <span class="text-[8px] font-black text-indigo-300 uppercase text-center rotate-12">Official<br>Seal</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Management / HR Oversight</p>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase">Seal of Official Approval</p>
                                    </div>
                                    <p class="text-[9px] font-black text-slate-800 uppercase tracking-tighter">Date: ____/____/2026</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Metadata Footer -->
                        <div class="mt-12 pt-6 border-t border-slate-100 flex justify-between items-center opacity-40">
                             <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Registry Hash: {{ selectedAppraisal.id }}X92-PMS-AUTH</div>
                             <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Document Generated: {{ new Date().toLocaleString() }}</div>
                        </div>
                        </div> <!-- End Section VI -->
                    </div> <!-- End protocol-report inner -->
                </div> <!-- End Protocol View Wrapper -->
            </div> <!-- End selectedAppraisal container 617 -->
        </Dialog> <!-- End Main Action Dialog 616 -->

        <!-- Standalone Print Fallback Wrapper (For direct ViewMode changes) -->
        <div v-if="viewMode === 'protocol' && !showActionModal" class="fixed inset-0 bg-white z-[9999] overflow-y-auto">
             <div class="max-w-[1100px] mx-auto py-20">
                  <div class="bg-white shadow-2xl p-16">
                       <!-- Standalone content could go here if needed -->
                  </div>
             </div>
        </div>
    </div> <!-- End submissions-wrapper 355 -->
</template>

<style scoped>
.submissions-container {
    padding-bottom: 2rem;
}

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

/* HIGH FIDELITY PRINT OVERRIDES */
@media print {
  /* Hide every single thing by default */
  body * {
    visibility: hidden !important;
  }
  
  /* Selectively unhide only the report and its deep children */
  #printable-report, #printable-report * {
    visibility: visible !important;
  }
  
  /* Force report to master the canvas */
  #printable-report {
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: auto !important;
    background: white !important;
    z-index: 99999 !important;
    overflow: visible !important;
    padding: 0px !important; /* Let printer margins do heavy lifting */
    margin: 0 !important;
    box-shadow: none !important;
    border: none !important;
  }

  /* Specifically hide items flagged inside the report structure */
  .no-print {
    display: none !important;
    visibility: hidden !important;
  }
  
  /* Boost contrast/readability for paper output */
  .text-slate-600, .text-slate-700 {
      color: #1e293b !important; /* Force darker text */
  }
}
</style>
