<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, showConfirm } from '@/helpers/essential';
import { toast } from 'vue3-toastify';
import AutoComplete from 'primevue/autocomplete';
import { finddept, findbranch } from '@/data/masterdata';
import melcomLogo from '@/assets/img/melcom_logo.png';
import PrintableHardCopyDossier from '@/components/pms/PrintableHardCopyDossier.vue';

const userstore = useUsersStore();
const { loguser } = userstore;
const router = useRouter();

const isDark = ref(document.body.classList.contains('dark-mode'));
const _darkObserver = new MutationObserver(() => { isDark.value = document.body.classList.contains('dark-mode'); });
_darkObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });

const goals = ref([]);
const loading = ref(true);
const saving = ref(false);
const savingDraft = ref(false);
const draftId = ref(null); // Track if editing an existing draft
const lastDraftSave = ref(null); // Timestamp of last auto-save
const showDetailModal = ref(false);
const selectedGoal = ref(null);
function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

const currentYear = ref(new Date().getFullYear());
const years = range(currentYear.value, currentYear.value - 5);
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });

// Role definitions
const isManager = computed(() => {
    return !!(loguser?.admin || loguser?.is_manager || loguser?.position_id === 3 || loguser?.position_id === 4 || loguser?.designation === 'Manager');
});
const isEmployee = computed(() => !isManager.value);

// Multi-step form state (3 Steps: 1. Definition, 2. Tracking, 3. Appraisal)
const viewMode = ref('list'); // 'list' or 'create'
const currentStep = ref(1);
const totalSteps = 3;
const stepTransition = ref('slide-next');

// Read-only condition for submitted or reviewed goals
const isFormReadOnly = computed(() => {
    if (isManager.value) {
        // Managers can edit assigned, in_progress, and draft goals at will
        return ['review_completed', 'completed'].includes(newGoal.value.status) || newGoal.value.display_status === 'review_completed';
    }
    return ['submitted', 'review_completed', 'completed'].includes(newGoal.value.status) || newGoal.value.display_status === 'review_completed';
});

// Single employee assigned goal computed - prioritize active uncompleted goal
const employeeGoal = computed(() => {
    if (!goals.value || !goals.value.length) return null;
    return goals.value.find(g => ['assigned', 'draft', 'in_progress'].includes(g.status)) 
        || goals.value.find(g => g.year === currentYear.value) 
        || goals.value[0] 
        || null;
});

// Helper to check if employee has an active assigned goal requiring action
const pendingAssignedGoal = computed(() => {
    if (!goals.value || !goals.value.length) return null;
    return goals.value.find(g => g.status === 'assigned' || g.display_status === 'assigned') || null;
});

// Master Employee Data for Dropdown
const masterEmployees = ref([]);
const filteredMasterEmployees = ref([]);
const selectedCandidate = ref(null); // For AutoComplete v-model

const getRecordId = (goal) => {
    if (!goal) return 'N/A';
    const dept = goal.department || goal.user?.department || 'NA';
    const code = goal.employee_code || goal.user?.employee_code || goal.id || '---';
    const abbr = dept.includes(' ') 
        ? dept.split(' ').map(w => w[0]).join('').toUpperCase() 
        : (dept.length >= 3 ? dept.substring(0, 3).toUpperCase() : dept.toUpperCase());
    return `${abbr}-${code}`;
};

const getTimeProgress = (goal) => {
    if (!goal.completion_date) return 0;
    const start = new Date(goal.submitted_at || goal.created_at || `${currentYear.value}-01-01`);
    const end = new Date(goal.completion_date);
    const today = new Date();
    if (today >= end) return 100;
    if (today <= start) return 0;
    return Math.min(100, Math.max(0, Math.round(((today - start) / (end - start)) * 100)));
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Fallback if invalid date
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const defaultSmartCriteria = () => ({
    specific: false,
    measurable: false,
    attainable: false,
    relevant: false,
    time_bound: false
});

const defaultQuarterlyTracking = () => {
    const today = new Date().toISOString().split('T')[0];
    const year = currentYear.value || new Date().getFullYear();
    return [
        { quarter: 'q1', start_date: today, end_date: `${year}-03-31`, target_measures: [''], evidence: '', attachments: [] },
        { quarter: 'q2', start_date: `${year}-04-01`, end_date: `${year}-06-30`, target_measures: [''], evidence: '', attachments: [] },
        { quarter: 'q3', start_date: `${year}-07-01`, end_date: `${year}-09-30`, target_measures: [''], evidence: '', attachments: [] },
        { quarter: 'q4', start_date: `${year}-10-01`, end_date: `${year}-12-31`, target_measures: [''], evidence: '', attachments: [] }
    ];
};

const defaultAppraisalData = () => ({
    competencies: [
        { 
            id: 1, 
            title: 'Performance & Teamwork', 
            descriptions: [
                'a) Overall performance - based on feedback from Line or Operations Managers', 
                'b) Teamwork, People issues - how it has been managed, number of queries tracked as compared to last year and compared to your peers.'
            ], 
            weight: 20, 
            selfRating: 0, 
            managerRating: 0 
        },
        { 
            id: 2, 
            title: 'Customer Service / Relationship Building', 
            descriptions: [
                'a) Number of super saver cards sold vs number of invoices made without the use of super saver card on the invoices', 
                "b) Google review 4.6 / Improvement query in the weekly Shop Google vs overall Melcom Google score."
            ], 
            weight: 20, 
            selfRating: 0, 
            managerRating: 0 
        },
        { 
            id: 3, 
            title: 'Execution / Sales Results Driven', 
            descriptions: [
                'a) Business Driven Metric (Set by Department with Management input)', 
                'b) Loss to company % (Factors and calculations must be provided), where applicable and relevant'
            ], 
            weight: 20, 
            selfRating: 0, 
            managerRating: 0 
        },
        { 
            id: 4, 
            title: 'Compliance & Quality Standards', 
            descriptions: [
                'a) Adherence to company policies, SOPs, safety, and regulatory compliance', 
                'b) % implementation of Wooqer checklist and shop/department standards'
            ], 
            weight: 20, 
            selfRating: 0, 
            managerRating: 0 
        },
        { 
            id: 5, 
            title: 'Continuous Improvement in workflows/processes', 
            descriptions: [
                'a) Culture of adaptability and innovation among staff, such as inventory management, employee training', 
                'b) Adaptability / Flexibility and operational problem solving'
            ], 
            weight: 20, 
            selfRating: 0, 
            managerRating: 0 
        }
    ],
    comments: '',
    impressedMost: '',
    impressedLeast: '',
    performanceRating: 0,
    performanceComments: '', // Kept for legacy compatibility if needed, but primary is rating_comments
    rating_comments: { 1: '', 2: '', 3: '', 4: '', 5: '' },
    potentialRating: '',
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

// Performance Rating Scale (for Step 3)
const performanceRatings = [
    { value: 5, label: '5-Out Standing / Exceptional', potential: 'High Potential', potentialComment: 'Ready for the next position' },
    { value: 4, label: '4-Exceeding Expectations', potential: 'High Potential', potentialComment: 'Ready in 2 years' },
    { value: 3, label: '3-Meeting Expectations', potential: 'Good Potential', potentialComment: '' },
    { value: 2, label: '2-Partly Meeting Expectations', potential: 'Low Potential', potentialComment: 'Need to keep under observation' },
    { value: 1, label: '1-Below Expectations/ Unsatisfactory', potential: 'Below Potential', potentialComment: 'PIP' }
];

const newGoal = ref({
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
    candidate_name: '', // Display name
    employee_code: '',
    location: '',
    department: '',
    job_title: '',
    manager_name: loguser.name,
    smart_criteria: defaultSmartCriteria(),
    quarterly_tracking: defaultQuarterlyTracking(),
    appraisal_data: defaultAppraisalData()
});

// Auto-populate quarter dates if they are empty
watch(() => newGoal.value.completion_date, (newDate) => {
    if (newGoal.value.quarterly_tracking) {
        const today = new Date().toISOString().split('T')[0];
        newGoal.value.quarterly_tracking.forEach(q => {
            if (!q.start_date) {
                q.start_date = today;
            }
            if (!q.end_date && newDate) {
                q.end_date = newDate;
            }
        });
    }
}, { immediate: true });

const smartLabels = [
    { key: 'specific', label: 'Specific', short: 'S', color: 'bg-[#2E7D32] text-white border-[#2E7D32]', badgeColor: 'bg-[#2E7D32]' },
    { key: 'measurable', label: 'Measurable', short: 'M', color: 'bg-[#1565C0] text-white border-[#1565C0]', badgeColor: 'bg-[#1565C0]' },
    { key: 'attainable', label: 'Attainable', short: 'A', color: 'bg-[#7B1FA2] text-white border-[#7B1FA2]', badgeColor: 'bg-[#7B1FA2]' },
    { key: 'relevant', label: 'Relevant', short: 'R', color: 'bg-[#C62828] text-white border-[#C62828]', badgeColor: 'bg-[#C62828]' },
    { key: 'time_bound', label: 'Time-bound', short: 'T', color: 'bg-[#1A237E] text-white border-[#1A237E]', badgeColor: 'bg-[#1A237E]' }
];

const quarterColors = {
    q1: { header: 'bg-red-400 text-white', cell: 'bg-red-50' },
    q2: { header: 'bg-purple-500 text-white', cell: 'bg-purple-50' },
    q3: { header: 'bg-green-600 text-white', cell: 'bg-green-50' },
    q4: { header: 'bg-amber-600 text-white', cell: 'bg-amber-50' }
};



const fetchGoals = async () => {
    loading.value = true;
    userstore.setIsLoading(true);
    try {
        const response = await axios.get('pms/goals', {
            params: { year: currentYear.value }
        });
        if (response.data.status === 'success') {
            goals.value = response.data.data;

            // Employee sees the PMS section list with all their submitted and active goals
        }
    } catch (error) {
        console.error('Error fetching goals:', error);
    } finally {
        loading.value = false;
        userstore.setIsLoading(false);
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
    // Keep user_id as loguser.id (the creator/manager) to avoid foreign key violations 
    // since candidates are employees from onboarding and may not have user accounts.
    newGoal.value.user_id = loguser.id; 
    
    newGoal.value.candidate_name = candidate.name;
    newGoal.value.employee_code = candidate.employee_code;
    
    // Resolve Department & Location from master data if flat strings are missing/N/A
    newGoal.value.location = (candidate.location && candidate.location !== 'N/A') 
        ? candidate.location 
        : (findbranch(candidate.joining_branch_id) || 'N/A');
        
    newGoal.value.department = (candidate.department && candidate.department !== 'N/A') 
        ? candidate.department 
        : (finddept(candidate.joining_dept_id) || 'N/A');
        
    // Job Title fallback logic
    newGoal.value.job_title = (candidate.position && candidate.position !== 'N/A')
        ? candidate.position
        : newGoal.value.department;
        
    // Manager stays as logged in user (the creator)
    newGoal.value.manager_name = loguser.name;
};


const startCreateGoal = () => {
    resetForm();
    currentStep.value = 1;
    viewMode.value = 'create';
    fetchMasterEmployees();
    loadDraft(); // Restore any saved draft
};

const openEmployeeGoal = (goal) => {
    if (!goal) return;
    resumeDraft(goal);
    draftId.value = goal.id;
    newGoal.value.status = goal.status;
    newGoal.value.candidate_name = goal.candidate_name || loguser.name;
    newGoal.value.employee_code = goal.employee_code || loguser.employee_code;
    newGoal.value.job_title = goal.job_title || loguser.job_title || loguser.department || '';
    newGoal.value.department = goal.department || loguser.department || '';
    newGoal.value.location = goal.location || loguser.location || '';
    newGoal.value.manager_name = goal.manager_name || 'Line Manager';
    if (!newGoal.value.appraisal_data) {
        newGoal.value.appraisal_data = defaultAppraisalData();
    }
    // Signature is left blank for manual signing
    if (!newGoal.value.appraisal_data.candidate_signature_name) {
        newGoal.value.appraisal_data.candidate_signature_name = '';
    }
    if (!newGoal.value.appraisal_data.rating_comments || typeof newGoal.value.appraisal_data.rating_comments !== 'object') {
        newGoal.value.appraisal_data.rating_comments = { 1: '', 2: '', 3: '', 4: '', 5: '' };
    } else {
        for (let i = 1; i <= 5; i++) {
            if (!newGoal.value.appraisal_data.rating_comments[i]) newGoal.value.appraisal_data.rating_comments[i] = '';
        }
    }

    // Ensure fields and SMART checklist start clean and empty for the employee to fill in
    if (goal.status === 'assigned' || isEmployee.value) {
        if (!newGoal.value.title || newGoal.value.title.startsWith('Yearly SMART Goals FY') || goal.status === 'assigned') {
            newGoal.value.title = '';
        }
        if (!newGoal.value.description || newGoal.value.description.some(d => !d || d.includes('Define key performance indicators'))) {
            newGoal.value.description = [''];
        }
        if (!newGoal.value.purposes || newGoal.value.purposes.some(p => !p || p.includes('Establish business relevance and benefits'))) {
            newGoal.value.purposes = [''];
        }
        if (!newGoal.value.challenges || newGoal.value.challenges.some(c => !c || c.includes('Potential obstacles and mitigations'))) {
            newGoal.value.challenges = [''];
        }
        if (goal.status === 'assigned') {
            newGoal.value.smart_criteria = {
                specific: false,
                measurable: false,
                attainable: false,
                relevant: false,
                time_bound: false
            };
        }
    }

    currentStep.value = 1;
    viewMode.value = 'create';
};

const cancelCreate = () => {
    clearDraft();
    viewMode.value = 'list';
    currentStep.value = 1;
    resetForm();
};

// Appraisal Step 3 Self-Rating Calculations
const selfAppraisalOverallRating = computed(() => {
    const comps = newGoal.value.appraisal_data?.competencies || [];
    if (!comps.length) return '0.00';
    const total = comps.reduce((sum, c) => {
        const r = parseFloat(c.selfRating) || 0;
        const w = parseFloat(c.weight) || 20;
        return sum + ((r * w) / 100);
    }, 0);
    return total.toFixed(2);
});

const selfAppraisalOverallPercentage = computed(() => {
    const score = parseFloat(selfAppraisalOverallRating.value) || 0;
    const pct = (score / 5) * 100;
    return pct % 1 === 0 ? pct.toFixed(0) : pct.toFixed(1);
});

const activeSelfRatingBand = computed(() => Math.round(parseFloat(selfAppraisalOverallRating.value) || 0));

const toggleRating = (val) => {
    if (isFormReadOnly.value) return;
    if (newGoal.value.appraisal_data.performanceRating === val) {
        newGoal.value.appraisal_data.performanceRating = 0;
    } else {
        newGoal.value.appraisal_data.performanceRating = val;
    }
};

// Manager Assign Modal State
const showAssignModal = ref(false);
const assigning = ref(false);
const assignSearchEmployee = ref(null); // AutoComplete search input
const assignForm = ref({
    assign_type: 'specific', // 'specific' (single or multiple) or 'all_team'
    selected_employees: [], // array of selected employee objects
    title: '',
    year: currentYear.value,
    target: 100,
    category: 'Operational',
    due_date: '',
    weights: [20, 20, 20, 20, 20]
});

const defaultAssignCompetencies = [
    { id: 1, title: 'Performance & Teamwork', weight: 20, descriptions: ['Overall performance based on feedback from Line or Operations Managers', 'Teamwork and people management issues'] },
    { id: 2, title: 'Customer Service / Relationship Building', weight: 20, descriptions: ['Super saver cards and service quality', 'Google rating improvement and satisfaction'] },
    { id: 3, title: 'Execution / Sales Results Driven', weight: 20, descriptions: ['Business driven metric set by department', 'Loss to company % mitigation and delivery'] },
    { id: 4, title: 'Compliance & Quality Standards', weight: 20, descriptions: ['Adherence to company policies, SOPs, safety, and regulatory compliance', 'Wooqer checklist and department standards implementation'] },
    { id: 5, title: 'Continuous Improvement in workflows/processes', weight: 20, descriptions: ['Culture of adaptability and operational innovation', 'Flexibility and problem solving'] }
];

const managerCompetencies = ref([...defaultAssignCompetencies]);

const competencyNames = computed(() => {
    return managerCompetencies.value.map(c => c.title);
});

const openAssignModal = async () => {
    assignSearchEmployee.value = null;
    assignForm.value = {
        assign_type: 'specific',
        selected_employees: [],
        title: '',
        year: currentYear.value,
        target: 100,
        category: 'Operational',
        due_date: `${currentYear.value}-12-31`,
        weights: managerCompetencies.value.map(c => c.weight || 20)
    };
    fetchMasterEmployees();

    // Fetch Line Manager's saved custom template from server or local storage
    try {
        const tplRes = await axios.get('pms/manager-template');
        if (tplRes.data.status === 'success' && tplRes.data.template && Array.isArray(tplRes.data.template) && tplRes.data.template.length > 0) {
            const raw = tplRes.data.template;
            managerCompetencies.value = defaultAssignCompetencies.map((def, idx) => {
                const m = raw.find(c => c.id === def.id) || raw[idx] || def;
                return {
                    ...def,
                    title: m.title || def.title,
                    weight: m.weight !== undefined ? Number(m.weight) : def.weight,
                    descriptions: m.descriptions || def.descriptions
                };
            });
            assignForm.value.weights = managerCompetencies.value.map(c => c.weight || 20);
        } else {
            const storageKey = `pms_custom_competency_template_${loguser?.id || 'default'}`;
            const savedTpl = localStorage.getItem(storageKey);
            if (savedTpl) {
                const parsed = JSON.parse(savedTpl);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    managerCompetencies.value = defaultAssignCompetencies.map((def, idx) => {
                        const m = parsed.find(c => c.id === def.id) || parsed[idx] || def;
                        return {
                            ...def,
                            title: m.title || def.title,
                            weight: m.weight !== undefined ? Number(m.weight) : def.weight,
                            descriptions: m.descriptions || def.descriptions
                        };
                    });
                    assignForm.value.weights = managerCompetencies.value.map(c => c.weight || 20);
                }
            }
        }
    } catch (e) {
        console.log('Error loading manager template, using current competencies:', e);
    }

    showAssignModal.value = true;
};

const onAssignEmployeeSelect = (event) => {
    const candidate = event.value;
    if (!candidate) return;
    const exists = assignForm.value.selected_employees.some(e => e.employee_code === candidate.employee_code);
    if (!exists) {
        assignForm.value.selected_employees.push({
            employee_code: candidate.employee_code,
            name: candidate.name,
            department: candidate.department || finddept(candidate.joining_dept_id) || 'N/A',
            location: candidate.location || findbranch(candidate.joining_branch_id) || 'N/A',
            job_title: candidate.position || candidate.department || 'Employee',
            email: candidate.email || ''
        });
    }
    assignSearchEmployee.value = null; // Clear search input
};

const removeAssignEmployee = (index) => {
    assignForm.value.selected_employees.splice(index, 1);
};

const selectAllMasterEmployeesForAssign = () => {
    masterEmployees.value.forEach(candidate => {
        if (!candidate.employee_code) return;
        const exists = assignForm.value.selected_employees.some(e => e.employee_code === candidate.employee_code);
        if (!exists) {
            assignForm.value.selected_employees.push({
                employee_code: candidate.employee_code,
                name: candidate.name,
                department: candidate.department || finddept(candidate.joining_dept_id) || 'N/A',
                location: candidate.location || findbranch(candidate.joining_branch_id) || 'N/A',
                job_title: candidate.position || candidate.department || 'Employee',
                email: candidate.email || ''
            });
        }
    });
};

const clearAssignSelectedEmployees = () => {
    assignForm.value.selected_employees = [];
};

const assignTotalWeight = computed(() => {
    return Math.round(assignForm.value.weights.reduce((sum, w) => sum + (parseFloat(w) || 0), 0));
});

const submitAssignGoal = async () => {
    if (assignForm.value.assign_type === 'specific' && (!assignForm.value.selected_employees || assignForm.value.selected_employees.length === 0)) {
        toast.warning('Please search and select at least one employee to assign goals and appraisal.', { autoClose: 3500 });
        showAlert('Select Employee(s)', 'Please search and select at least one employee to assign goals and appraisal to.', 'warning');
        return;
    }
    if (assignTotalWeight.value !== 100) {
        toast.warning(`Total weight must equal exactly 100%. Current sum: ${assignTotalWeight.value}%.`, { autoClose: 3500 });
        showAlert('Invalid Weights', `The total weight must equal exactly 100%. Current sum: ${assignTotalWeight.value}%.`, 'warning');
        return;
    }

    assigning.value = true;
    try {
        // Build template from line manager's custom competencies and weights
        const templateComps = managerCompetencies.value.map((comp, idx) => ({
            id: comp.id || (idx + 1),
            title: comp.title,
            descriptions: comp.descriptions || (comp.descriptionText ? comp.descriptionText.split('\n') : []),
            descriptionText: comp.descriptionText || (Array.isArray(comp.descriptions) ? comp.descriptions.join('\n') : comp.descriptions),
            weight: assignForm.value.weights[idx] !== undefined ? Number(assignForm.value.weights[idx]) : (Number(comp.weight) || 20),
            selfRating: 0,
            managerRating: 0
        }));

        const customTemplate = {
            ...defaultAppraisalData(),
            competencies: templateComps,
            manager_name: loguser.name || '',
            manager_signature_name: loguser.name || ''
        };

        // Persist customized template to line manager's template in DB and scoped localStorage
        try {
            const storageKey = `pms_custom_competency_template_${loguser?.id || 'default'}`;
            localStorage.setItem(storageKey, JSON.stringify(templateComps));
            await axios.post('pms/manager-template', { template: templateComps });
        } catch (tErr) {
            console.warn('Could not auto-save manager template:', tErr);
        }

        const payload = {
            assign_type: assignForm.value.assign_type,
            year: assignForm.value.year,
            title: assignForm.value.title,
            category: assignForm.value.category,
            target: assignForm.value.target,
            due_date: assignForm.value.due_date,
            appraisal_data: customTemplate,
            employees: assignForm.value.assign_type === 'specific' ? assignForm.value.selected_employees : []
        };

        const res = await axios.post('pms/goals/assign', payload);
        if (res.data.status === 'success') {
            showAssignModal.value = false;
            filterStatus.value = 'assigned'; // Immediately switch to Assigned tab
            toast.success(res.data.message || 'Goals & appraisal templates assigned successfully!', { autoClose: 5000 });
            await showAlert(
                'Assignment Successful!',
                res.data.message || 'Goals & appraisal templates assigned successfully. Employee credentials format: Username = Firstname EmployeeCode (or EmployeeCode), Password = password.',
                'success'
            );
            await fetchGoals();
        }
    } catch (err) {
        console.error('Error assigning goals:', err);
        const msg = err.response?.data?.message || err.message || 'Failed to assign goals.';
        toast.error(msg, { autoClose: 5000 });
        showAlert('Error', msg, 'error');
    } finally {
        assigning.value = false;
    }
};

const submitGoalForReview = async () => {
    if (!newGoal.value.title || !newGoal.value.title.trim()) {
        showAlert('Title Required', 'Please provide a title for your goal in Step 1.', 'warning');
        currentStep.value = 1;
        return;
    }

    const comps = newGoal.value.appraisal_data?.competencies || [];
    const unrated = comps.filter(c => !c.selfRating || c.selfRating === 0);
    if (unrated.length > 0) {
        showAlert('Self Rating Required', `Please complete your Self Rating (1-5) for all ${comps.length} competencies in Step 3 before submitting.`, 'warning');
        return;
    }

    const confirm = await showConfirm(
        'Submit to Line Manager',
        'Are you sure you want to sign off and submit your Goals & Self-Appraisal to your Line Manager? Once submitted, your submission will be locked for review.',
        'question',
        'Yes, Sign Off & Submit'
    );
    if (!confirm.isConfirmed) return;

    if (!newGoal.value.appraisal_data.candidate_signature_name) {
        newGoal.value.appraisal_data.candidate_signature_name = newGoal.value.candidate_name || loguser.name;
    }
    if (!newGoal.value.appraisal_data.signature_date) {
        newGoal.value.appraisal_data.signature_date = new Date().toISOString().split('T')[0];
    }

    await addGoal('submitted');
};

const nextStep = async () => {
    if (currentStep.value === 1) {
        const missing = [];
        if (!newGoal.value.title || !newGoal.value.title.trim()) missing.push('Goal Title');
        if (!newGoal.value.candidate_name) missing.push('Candidate selection');

        if (missing.length > 0) {
            showAlert('Required Fields', `Please provide the following: ${missing.join(', ')}`, 'warning');
            return;
        }
    } else if (currentStep.value === 2) {
        const emptyMeasures = newGoal.value.quarterly_tracking.some(q => 
            !q.target_measures || q.target_measures.every(m => !m || !m.trim())
        );

        if (emptyMeasures) {
            const result = await showConfirm(
                'Incomplete Tracking',
                'Some quarterly target measures are empty. Proceed to appraisal anyway?',
                'warning',
                'Yes, Continue'
            );
            if (!result.isConfirmed) return;
        }
    }

    if (currentStep.value < totalSteps) {
        stepTransition.value = 'slide-next';
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        stepTransition.value = 'slide-prev';
        currentStep.value--;
    }
};



const addGoal = async (status = 'in_progress') => {
    // Validation for completion
    if (status === 'completed') {
        const rating = newGoal.value.appraisal_data.performanceRating;
        if (!rating) {
            showAlert('Rating Required', 'Please select a performance rating (1-5) before completing the appraisal.', 'warning');
            return;
        }
        
        const comment = newGoal.value.appraisal_data.rating_comments[rating];
        if (!comment || !comment.trim()) {
            showAlert('Comments Required', `Please provide justification comments for the selected rating (${rating}).`, 'warning');
            return;
        }
    }

    // Final safety check before save
    if (!newGoal.value.title || !newGoal.value.title.trim()) {
        showAlert('Title Required', 'Please provide a title for the goal.', 'warning');
        saving.value = false;
        return;
    }
    if (!newGoal.value.candidate_name) {
        showAlert('Candidate Required', 'Please select a candidate.', 'warning');
        saving.value = false;
        return;
    }

    saving.value = true;
    try {
        const payload = {
            title: newGoal.value.title.trim(),
            description: JSON.stringify(newGoal.value.description.filter(d => d.trim() !== '')),
            purposes: JSON.stringify(newGoal.value.purposes.filter(p => p.trim() !== '')),
            challenges: JSON.stringify(newGoal.value.challenges.filter(c => c.trim() !== '')),
            category: newGoal.value.category || 'General',
            target: parseFloat(newGoal.value.target) || 0,
            due_date: newGoal.value.due_date || null,
            completion_date: newGoal.value.completion_date || null,
            year: parseInt(newGoal.value.year) || new Date().getFullYear(),
            user_id: parseInt(newGoal.value.user_id || loguser.id),
            candidate_name: newGoal.value.candidate_name,
            employee_code: newGoal.value.employee_code,
            location: newGoal.value.location,
            department: newGoal.value.department,
            job_title: newGoal.value.job_title || '',
            manager_name: newGoal.value.manager_name || loguser.name || '',
            smart_criteria: newGoal.value.smart_criteria,
            quarterly_tracking: newGoal.value.quarterly_tracking,
            appraisal_data: newGoal.value.appraisal_data,
            status: status
        };
        console.log('Final Payload Sync:', payload);

        // If completing, ensure completion date is set if not already
        if (status === 'completed' && !payload.completion_date) {
            payload.completion_date = new Date().toISOString().split('T')[0];
        }

        let response;
        if (draftId.value) {
            // Update the existing draft record
            response = await axios.patch(`pms/goals/${draftId.value}`, payload);
        } else {
            response = await axios.post('pms/goals', payload);
        }

        if (response.data.status === 'success') {
            if (draftId.value) {
                // Replace the draft in the list with the updated goal
                const idx = goals.value.findIndex(g => g.id === draftId.value);
                if (idx !== -1) goals.value[idx] = response.data.data;
                else goals.value.push(response.data.data);
            } else {
                goals.value.push(response.data.data);
            }
            clearDraft();
            viewMode.value = 'list';
            resetForm();
            
            if (status === 'completed') {
                showAlert('Success', 'Goal Appraisal Completed successfully!', 'success');
            } else if (status === 'submitted') {
                showAlert('Submitted for Review', 'Your SMART goals and self-appraisal have been successfully submitted to your Line Manager for review.', 'success');
            } else {
                showAlert('Success', 'Goal saved successfully!', 'success');
            }
        }
    } catch (error) {
        console.error('Error adding goal:', error);
        const serverMsg = error.response?.data?.message || error.response?.data?.error || '';
        showAlert('Error', `Failed to save goal. ${serverMsg}`, 'error');
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
        employee_code: '',
        location: '',
        department: '',
        manager_name: loguser.name || '',
        smart_criteria: defaultSmartCriteria(),
        quarterly_tracking: defaultQuarterlyTracking(),
        appraisal_data: defaultAppraisalData()
    };
    selectedCandidate.value = null; // Reset Dropdown
    draftId.value = null;
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

const getManagerRating = (goal) => {
    const data = typeof goal.appraisal_data === 'string' ? JSON.parse(goal.appraisal_data || '{}') : (goal.appraisal_data || {});
    // First: compute from competency managerRatings (the actual review scores)
    const comps = data.competencies || [];
    const rated = comps.filter(c => parseFloat(c.managerRating) > 0);
    if (rated.length > 0) {
        const total = rated.reduce((sum, c) => sum + parseFloat(c.managerRating), 0);
        return (total / rated.length).toFixed(1);
    }
    // Fallback: overall_rating from DB
    if (goal.overall_rating) return parseFloat(goal.overall_rating).toFixed(1);
    return null;
};

const getSelfRating = (goal) => {
    const data = typeof goal.appraisal_data === 'string' ? JSON.parse(goal.appraisal_data || '{}') : (goal.appraisal_data || {});
    const comps = data.competencies || [];
    const rated = comps.filter(c => parseFloat(c.selfRating) > 0);
    if (rated.length > 0) {
        const total = rated.reduce((sum, c) => sum + parseFloat(c.selfRating), 0);
        return (total / rated.length).toFixed(1);
    }
    return null;
};

const getSmartScore = (goal) => {
    if (!goal.smart_criteria) return 0;
    const criteria = goal.smart_criteria;
    return Object.values(criteria).filter(v => v === true).length;
};

const isGoalReviewCompleted = (goal) => {
    if (!goal) return false;
    return goal.display_status === 'review_completed' || 
           goal.status === 'review_completed' || 
           (goal.status === 'completed' && goal.display_status !== 'appraisal_completed');
};

const canProceedToStep2 = computed(() => {
    const hasTitle = newGoal.value.title && String(newGoal.value.title).trim() !== '';
    const hasUser = !!newGoal.value.user_id;
    // console.log('Validation:', { title: newGoal.value.title, user_id: newGoal.value.user_id, hasTitle, hasUser });
    return hasTitle && hasUser;
});

// â”€â”€â”€ Draft Save / Load / Clear â”€â”€â”€
const DRAFT_KEY = `pms_goal_draft_${loguser.id}`;

const saveDraft = async (toServer = false) => {
    // Always save to localStorage
    const draftData = JSON.parse(JSON.stringify(newGoal.value));
    draftData._step = currentStep.value;
    draftData._draftId = draftId.value;
    localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));
    lastDraftSave.value = new Date();

    if (toServer) {
        savingDraft.value = true;
        try {
            const payload = {
                title: newGoal.value.title,
                description: JSON.stringify(newGoal.value.description.filter(d => d.trim() !== '')),
                purposes: JSON.stringify(newGoal.value.purposes.filter(p => p.trim() !== '')),
                challenges: JSON.stringify(newGoal.value.challenges.filter(c => c.trim() !== '')),
                category: newGoal.value.category || 'General',
                target: parseFloat(newGoal.value.target) || 0,
                due_date: newGoal.value.due_date || null,
                completion_date: newGoal.value.completion_date || null,
                year: parseInt(newGoal.value.year) || new Date().getFullYear(),
                user_id: parseInt(loguser.id),
                candidate_name: newGoal.value.candidate_name,
                employee_code: newGoal.value.employee_code,
                location: newGoal.value.location,
                department: newGoal.value.department,
                job_title: newGoal.value.job_title || '',
                manager_name: loguser.name || '',
                smart_criteria: newGoal.value.smart_criteria,
                quarterly_tracking: newGoal.value.quarterly_tracking,
                appraisal_data: newGoal.value.appraisal_data,
                status: 'draft'
            };
            console.log('Final Draft Payload Sync:', payload);

            let response;
            if (draftId.value) {
                response = await axios.patch(`pms/goals/${draftId.value}`, payload);
            } else {
                response = await axios.post('pms/goals', payload);
            }

            if (response.data.status === 'success') {
                draftId.value = response.data.data.id;
                // Also update localStorage with the server ID
                draftData._draftId = draftId.value;
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));

                // Update the goals list if the draft is new
                const idx = goals.value.findIndex(g => g.id === draftId.value);
                if (idx !== -1) goals.value[idx] = response.data.data;
                else goals.value.push(response.data.data);

                showAlert('Draft Saved', 'Your progress has been saved as a draft.', 'success');
            }
        } catch (error) {
            console.error('Error saving draft:', error);
            let errorMsg = 'Could not save draft to server, but local backup is saved.';
            if (error.response && error.response.data && error.response.data.errors) {
                errorMsg += ' ' + Object.values(error.response.data.errors).flat().join(' ');
            } else if (error.response && error.response.data && error.response.data.message) {
                errorMsg += ' Details: ' + error.response.data.message;
            }
            showAlert('Error', errorMsg, 'warning');
        } finally {
            savingDraft.value = false;
        }
    }
};

const triggerPrint = () => {
    const source = document.getElementById('protocol-report');
    if (!source) return;

    const printContainer = document.createElement('div');
    printContainer.id = 'print-clone';
    printContainer.innerHTML = source.innerHTML;
    document.body.appendChild(printContainer);

    const printStyle = document.createElement('style');
    printStyle.id = 'print-clone-style';
    printStyle.textContent = `
        @media print {
            body > *:not(#print-clone):not(#print-clone-style) {
                display: none !important;
            }
            #print-clone {
                display: block !important;
                position: static !important;
                width: 100% !important;
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            #print-clone .hardcopy-page {
                margin: 0 !important;
                padding: 8mm 10mm !important;
                width: 100% !important;
                min-height: auto !important;
                max-height: none !important;
                height: auto !important;
                page-break-after: always !important;
                break-after: page !important;
                box-shadow: none !important;
                border: none !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: visible !important;
            }
            #print-clone .hardcopy-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            #print-clone table {
                page-break-inside: auto !important;
            }
            #print-clone tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            #print-clone thead {
                display: table-header-group !important;
            }
            #print-clone .section-box, 
            #print-clone .quarterly-section, 
            #print-clone .feedback-split-box, 
            #print-clone .scale-guide-wrapper, 
            #print-clone .review-section-block, 
            #print-clone .auth-signoff-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            @page {
                size: A4 portrait;
                margin: 6mm 6mm;
            }
        }
        @media screen {
            #print-clone { display: none !important; }
        }
    `;
    document.head.appendChild(printStyle);

    setTimeout(() => {
        window.print();
        setTimeout(() => {
            if (document.body.contains(printContainer)) document.body.removeChild(printContainer);
            if (document.head.contains(printStyle)) document.head.removeChild(printStyle);
        }, 500);
    }, 300);
};

const getDossierId = (goal) => {
    if (!goal) return 'PMS-000';
    const deptPrefix = goal.department ? goal.department.substring(0, 3).toUpperCase() : 'PMS';
    return `${deptPrefix}-${goal.employee_code || goal.id}`;
};

const downloadCSV = () => {
    const headers = ['ID', 'Candidate', 'Code', 'Title', 'Status', 'Target', 'Actual', 'Department', 'Location', 'Year'];
    const rows = filteredGoals.value.map(g => [
        `"${g.id}"`,
        `"${g.candidate_name}"`,
        `"${g.employee_code}"`,
        `"${g.title.replace(/"/g, '""')}"`,
        `"${g.status}"`,
        g.target,
        g.actual || 0,
        `"${g.department || ''}"`,
        `"${g.location || ''}"`,
        g.year
    ]);
    const csvContent = "\ufeff" + headers.join(",") + "\n" + rows.map(r => r.join(",")).join("\n");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `SMART_Goals_${currentYear.value}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadSingleCSV = (goal) => {
    const recordId = getDossierId(goal);
    const ad = typeof goal.appraisal_data === 'string' ? JSON.parse(goal.appraisal_data || '{}') : (goal.appraisal_data || {});
    const comps = ad.competencies || [];

    // Build comprehensive horizontal headers
    const headers = [
        'Record ID', 'Candidate Name', 'Employee Code', 'Department', 'Location', 'Year',
        'Goal Title', 'Category', 'Target %', 'Status', 'Manager Name',
        'Description/Objectives', 'Purposes', 'Challenges',
        'SMART-Specific', 'SMART-Measurable', 'SMART-Attainable', 'SMART-Relevant', 'SMART-TimeBound'
    ];

    // Quarterly headers
    [1, 2, 3, 4].forEach(q => {
        headers.push(`Q${q} Start Date`, `Q${q} End Date`, `Q${q} Target Measures`, `Q${q} Evidence`);
    });

    // Competency headers
    comps.forEach(c => {
        headers.push(`${c.title} (Weight)`, `${c.title} (Self)`, `${c.title} (Mgr)`);
    });

    // Appraisal fields
    headers.push(
        'Overall Manager Rating', 'Performance Rating', 'Potential Rating',
        'What Impressed Most', 'What Impressed Least', 'General Comments',
        'HOD Comments', 'Director Remarks',
        'Employee Signature', 'Manager Signature', 'Signature Date',
        'HOD Signature', 'HOD Signature Date', 'Director Signature', 'Director Signature Date'
    );

    // Build data row
    const smart = goal.smart_criteria || {};
    const row = [
        recordId, goal.candidate_name, goal.employee_code,
        goal.department || 'N/A', goal.location || 'N/A', goal.year,
        goal.title, goal.category || 'General', `${goal.target}%`, goal.status,
        goal.manager_name || 'N/A',
        parseList(goal.description).join('; '),
        parseList(goal.purposes).join('; '),
        parseList(goal.challenges).join('; '),
        smart.specific ? 'Yes' : 'No', smart.measurable ? 'Yes' : 'No',
        smart.attainable ? 'Yes' : 'No', smart.relevant ? 'Yes' : 'No',
        smart.time_bound ? 'Yes' : 'No'
    ];

    // Quarterly data
    [1, 2, 3, 4].forEach(idx => {
        const q = goal.quarterly_tracking?.find(qt => qt.quarter === `q${idx}`) || goal.quarterly_tracking?.[idx-1] || {};
        row.push(q.start_date || 'N/A', q.end_date || 'N/A',
            parseList(q.target_measures).filter(m => m).join('; ') || 'N/A',
            q.evidence || 'N/A');
    });

    // Competency data
    comps.forEach(c => {
        row.push(`${c.weight}%`, c.selfRating || 0, c.managerRating || 0);
    });

    // Appraisal data
    row.push(
        getManagerRating(goal) || 'N/A', ad.performanceRating || 'N/A', ad.potentialRating || 'N/A',
        ad.impressedMost || '', ad.impressedLeast || '', ad.comments || '',
        ad.hod_comments || '', ad.director_remarks || '',
        ad.candidate_signature_name || '', ad.manager_signature_name || '', ad.signature_date || '',
        ad.hod_signature_name || '', ad.hod_signature_date || '', ad.director_signature_name || '', ad.director_signature_date || ''
    );

    const csvContent = "\ufeff" + headers.join(",") + "\n" + row.map(v => `"${(v+'').replace(/"/g, '""')}"`).join(",");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.style.display = 'none';
    link.setAttribute("href", url);
    link.setAttribute("download", `Goal_Report_${recordId}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadPDF = () => {
    const element = document.getElementById('protocol-report');
    if (!element) return;
    
    const runDownload = () => {
        const opt = {
          margin:       0,
          filename:     `PMS_Appraisal_${selectedGoal.value.candidate_name || selectedGoal.value.employee_code || selectedGoal.value.id}_${selectedGoal.value.year || 'Record'}.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true, logging: false },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
          pagebreak:    { mode: ['css', 'legacy'] }
        };
        window.html2pdf().from(element).set(opt).save();
    };

    if (window.html2pdf) {
        runDownload();
    } else {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.onload = runDownload;
        document.head.appendChild(script);
    }
};

const loadDraft = () => {
    const saved = localStorage.getItem(DRAFT_KEY);
    if (saved) {
        try {
            const draftData = JSON.parse(saved);
            const step = draftData._step || 1;
            const savedDraftId = draftData._draftId || null;
            delete draftData._step;
            delete draftData._draftId;

            // Restore arrays if they came back as strings
            if (typeof draftData.description === 'string') draftData.description = JSON.parse(draftData.description);
            if (typeof draftData.purposes === 'string') draftData.purposes = JSON.parse(draftData.purposes);
            if (typeof draftData.challenges === 'string') draftData.challenges = JSON.parse(draftData.challenges);

            // Ensure defaults for missing fields
            if (!draftData.smart_criteria) draftData.smart_criteria = defaultSmartCriteria();
            if (!draftData.quarterly_tracking) draftData.quarterly_tracking = defaultQuarterlyTracking();
            if (!draftData.appraisal_data) {
                draftData.appraisal_data = defaultAppraisalData();
            } else if (!draftData.appraisal_data.rating_comments) {
                // Safeguard for old drafts that don't have per-rating comments
                draftData.appraisal_data.rating_comments = { 1: '', 2: '', 3: '', 4: '', 5: '' };
            }
            if (!Array.isArray(draftData.description)) draftData.description = [''];
            if (!Array.isArray(draftData.purposes)) draftData.purposes = [''];
            if (!Array.isArray(draftData.challenges)) draftData.challenges = [''];

            Object.assign(newGoal.value, draftData);
            currentStep.value = step;
            draftId.value = savedDraftId;

            // Enforce calculated calendar boundaries for drafts
            if (newGoal.value.quarterly_tracking) {
                const year = currentYear.value || new Date().getFullYear();
                const today = new Date().toISOString().split('T')[0];
                newGoal.value.quarterly_tracking.forEach(q => {
                    const quarterKey = q.quarter ? q.quarter.toLowerCase() : '';
                    if (quarterKey === 'q1') { if (!q.start_date) q.start_date = today; q.end_date = `${year}-03-31`; }
                    else if (quarterKey === 'q2') { q.start_date = `${year}-04-01`; q.end_date = `${year}-06-30`; }
                    else if (quarterKey === 'q3') { q.start_date = `${year}-07-01`; q.end_date = `${year}-09-30`; }
                    else if (quarterKey === 'q4') { q.start_date = `${year}-10-01`; q.end_date = `${year}-12-31`; }
                });
            }

            // Restore the AutoComplete selection if candidate data exists
            if (draftData.candidate_name && draftData.employee_code) {
                selectedCandidate.value = {
                    name: draftData.candidate_name,
                    employee_code: draftData.employee_code,
                    job_title: draftData.job_title,
                    full_string: draftData.employee_code + ' - ' + draftData.candidate_name
                };
            }

            showAlert('Draft Restored', 'Your previous unsaved progress has been restored.', 'info');
        } catch (e) {
            console.error('Failed to load draft:', e);
            localStorage.removeItem(DRAFT_KEY);
        }
    }
};

const clearDraft = () => {
    localStorage.removeItem(DRAFT_KEY);
    draftId.value = null;
    lastDraftSave.value = null;
};

const resumeDraft = (goal) => {
    // Resume editing a server-saved draft from the goals list
    fetchMasterEmployees();
    const data = { ...goal };
    draftId.value = goal.id;

    // Parse JSON fields back to arrays
    if (typeof data.description === 'string') {
        try { data.description = JSON.parse(data.description); } catch { data.description = [data.description]; }
    }
    if (typeof data.purposes === 'string') {
        try { data.purposes = JSON.parse(data.purposes); } catch { data.purposes = [data.purposes]; }
    }
    if (typeof data.challenges === 'string') {
        try { data.challenges = JSON.parse(data.challenges); } catch { data.challenges = [data.challenges]; }
    }
    if (typeof data.smart_criteria === 'string') {
        try { data.smart_criteria = JSON.parse(data.smart_criteria); } catch {}
    }
    if (typeof data.quarterly_tracking === 'string') {
        try { data.quarterly_tracking = JSON.parse(data.quarterly_tracking); } catch {}
    }
    if (typeof data.appraisal_data === 'string') {
        try { data.appraisal_data = JSON.parse(data.appraisal_data); } catch {}
    }
    if (!data.smart_criteria) data.smart_criteria = defaultSmartCriteria();
    if (!data.quarterly_tracking) data.quarterly_tracking = defaultQuarterlyTracking();
    if (!data.appraisal_data) {
        data.appraisal_data = defaultAppraisalData();
    } else {
        if (typeof data.appraisal_data.rating_comments === 'string') {
            try { data.appraisal_data.rating_comments = JSON.parse(data.appraisal_data.rating_comments); } catch {}
        }
        if (!data.appraisal_data.rating_comments || typeof data.appraisal_data.rating_comments !== 'object') {
            data.appraisal_data.rating_comments = { 1: '', 2: '', 3: '', 4: '', 5: '' };
        } else {
            for (let i = 1; i <= 5; i++) {
                if (!data.appraisal_data.rating_comments[i]) data.appraisal_data.rating_comments[i] = '';
            }
        }
    }
    if (!Array.isArray(data.description) || !data.description.length) data.description = [''];
    if (!Array.isArray(data.purposes) || !data.purposes.length) data.purposes = [''];
    if (!Array.isArray(data.challenges) || !data.challenges.length) data.challenges = [''];

    Object.assign(newGoal.value, data);
    currentStep.value = 1;
    viewMode.value = 'create';
};

const editGoal = (goal) => {
    if (isEmployee.value) {
        openEmployeeGoal(goal);
        return;
    }
    // Similar to resumeDraft but for persisted goals
    resumeDraft(goal);
    // Ensure we update the existing ID
    draftId.value = goal.id;
    
    // Set AutoComplete selection properly so it shows up in the UI
    if (goal.candidate_name && goal.employee_code) {
        selectedCandidate.value = {
            name: goal.candidate_name,
            employee_code: goal.employee_code,
            job_title: goal.job_title,
            full_string: goal.employee_code + ' - ' + goal.candidate_name
        };
    }
};

// Missing Fields Computation for Preview
const missingFields = computed(() => {
    if (!selectedGoal.value) return [];
    const missing = [];
    const g = selectedGoal.value;

    // Check SMART
    const smart = g.smart_criteria || {};
    if (!smart.specific) missing.push('Specific (S) criteria not met');
    if (!smart.measurable) missing.push('Measurable (M) criteria not met');
    if (!smart.attainable) missing.push('Attainable (A) criteria not met');
    if (!smart.relevant) missing.push('Relevant (R) criteria not met');
    if (!smart.time_bound) missing.push('Time-bound (T) criteria not met');

    // Check Quarterly Tracking
    const tracking = g.quarterly_tracking || [];
    tracking.forEach(q => {
        if (!q.target_measure) missing.push(`${q.quarter.toUpperCase()} Target Measure missing`);
        // Evidence check: simplistic check if evidence field is empty
        if (!q.evidence) missing.push(`${q.quarter.toUpperCase()} Evidence missing`);
    });
    
    // Check Appraisal - only if we care about it in this view
    if (g.appraisal_data && g.appraisal_data.performanceRating === null) {
        // Only flag if appraisal started but incomplete? 
        // For now, let's strictly check goal fields as requested ("not completed fields")
    }

    return missing;
});

// Auto-save to localStorage whenever the form changes
watch(newGoal, () => {
    if (viewMode.value === 'create') {
        saveDraft(false); // localStorage only, no server call
    }
}, { deep: true });

onMounted(() => {
    fetchGoals();
    fetchMasterEmployees();
});

// â”€â”€â”€ Filter State â”€â”€â”€
const filterStatus = ref('all');
const searchQuery = ref('');

// Stats Computed - uses display_status from backend
const stats = computed(() => {
    return {
        total: goals.value.length,
        assigned: goals.value.filter(g => g.display_status === 'assigned').length,
        submitted: goals.value.filter(g => g.display_status === 'submitted').length,
        goalCreated: goals.value.filter(g => g.display_status === 'goal_created').length,
        appraisalCompleted: goals.value.filter(g => g.display_status === 'appraisal_completed').length,
        reviewCompleted: goals.value.filter(g => g.display_status === 'review_completed').length,
        draft: goals.value.filter(g => g.display_status === 'draft').length
    };
});

// Filtered Goals Computed - uses display_status
const filteredGoals = computed(() => {
    return goals.value.filter(g => {
        // Status Filter by display_status
        if (filterStatus.value !== 'all') {
            if (filterStatus.value === 'assigned' && g.display_status !== 'assigned') return false;
            if (filterStatus.value === 'submitted') {
                if (isEmployee.value) {
                    const isSub = ['submitted', 'appraisal_completed', 'review_completed', 'completed'].includes(g.display_status) ||
                                  ['submitted', 'appraisal_completed', 'review_completed', 'completed'].includes(g.status);
                    if (!isSub) return false;
                } else {
                    if (g.display_status !== 'submitted') return false;
                }
            }
            if (filterStatus.value === 'goal_created' && g.display_status !== 'goal_created') return false;
            if (filterStatus.value === 'appraisal_completed' && g.display_status !== 'appraisal_completed') return false;
            if (filterStatus.value === 'review_completed' && g.display_status !== 'review_completed') return false;
            if (filterStatus.value === 'draft' && g.display_status !== 'draft') return false;
        }

        // Search Filter
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const title = (g.title || '').toLowerCase();
            const candidateName = (g.candidate_name || '').toLowerCase();
            const empCode = (g.employee_code || '').toLowerCase();
            const dept = (g.department || '').toLowerCase();
            return title.includes(query) || candidateName.includes(query) || empCode.includes(query) || dept.includes(query);
        }

        return true;
    });
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

const addExecutionTarget = (qIndex) => {
    newGoal.value.quarterly_tracking[qIndex].target_measures.push('');
};
const removeExecutionTarget = (qIndex, mIndex) => {
    newGoal.value.quarterly_tracking[qIndex].target_measures.splice(mIndex, 1);
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
const uploadProgress = ref({}); // Track numeric progress (0-100) per quarter

const handleFileUpload = async (event, qIndex) => {
    const files = event.target.files;
    if (!files.length) return;

    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    uploading.value[qIndex] = true;
    uploadProgress.value[qIndex] = 0;

    try {
        const response = await axios.post('pms/goals/upload-attachment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    uploadProgress.value[qIndex] = percent;
                }
            }
        });

        if (response.data.status === 'success') {
            if (!newGoal.value.quarterly_tracking[qIndex].attachments) {
                newGoal.value.quarterly_tracking[qIndex].attachments = [];
            }
            // Append new files to existing list
            newGoal.value.quarterly_tracking[qIndex].attachments.push(...response.data.files);
            showAlert('Success', 'Files uploaded successfully', 'success');
        }
    } catch (error) {
        console.error('File upload failed:', error);
        let errorMsg = 'File upload failed. Please try again.';
        if (error.response && error.response.data && error.response.data.errors) {
            const errors = error.response.data.errors;
            errorMsg = Object.values(errors).flat().join(' ');
        } else if (error.response && error.response.data && error.response.data.message) {
            errorMsg = error.response.data.message;
        }
        showAlert('Error', errorMsg, 'error');
    } finally {
        uploading.value[qIndex] = false;
        // Reset input
        if (event && event.target) {
            event.target.value = '';
        }
    }
};

const removeAttachment = (qIndex, fileIndex) => {
    newGoal.value.quarterly_tracking[qIndex].attachments.splice(fileIndex, 1);
};

const resolveAttachmentUrl = (file) => {
    if (!file) return '';
    const url = file.path || file.url || ('/storage/' + (file.file_path || file.file_name || file.name || file));
    const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://192.168.0.20:5050/pms_backend/api';
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    const cleaned = url.replace(/^\/?storage\//, '');
    const parts = cleaned.split('/');
    const folder = parts.slice(0, -1).join('/') || 'attachments';
    const fileName = parts[parts.length - 1];
    return `${baseUrl}/file/${folder}/${encodeURIComponent(fileName)}`;
};

const viewAttachment = (file) => {
    const url = resolveAttachmentUrl(file);
    if (url) window.open(url, '_blank');
};

const downloadAttachment = (file) => {
    let url = resolveAttachmentUrl(file);
    if (!url) return;
    url += (url.includes('?') ? '&' : '?') + 'download=1';
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', file.name || file.file_name || 'attachment');
    link.setAttribute('target', '_blank');
    document.body.appendChild(link);
    link.click();
    setTimeout(() => {
        if (document.body.contains(link)) document.body.removeChild(link);
    }, 2000);
};

</script>

<template>
    <div class="h-full pb-6">
        <!-- LIST VIEW -->
        <template v-if="viewMode === 'list'">
            <!-- TOP BLINKING ALERT FOR EMPLOYEES: NEW GOAL & APPRAISAL ASSIGNED -->
            <div v-if="isEmployee && pendingAssignedGoal && (pendingAssignedGoal.status === 'assigned' || pendingAssignedGoal.display_status === 'assigned')" class="mb-6 p-5 bg-gradient-to-r from-red-600 via-rose-600 to-indigo-950 rounded-2xl text-white shadow-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-2 border-red-400 relative overflow-hidden animate-pulse">
                <div class="flex items-center gap-3.5 z-10">
                    <span class="relative flex h-5 w-5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-90"></span>
                        <span class="relative inline-flex rounded-full h-5 w-5 bg-amber-400 text-slate-900 items-center justify-center text-[10px] font-black shadow-sm">
                            <i class="pi pi-bell text-[10px]"></i>
                        </span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <span class="px-2.5 py-0.5 bg-amber-400 text-slate-900 rounded-md text-[10px] font-black uppercase tracking-widest shadow-xs">
                                ACTION REQUIRED
                            </span>
                            <span class="text-xs font-black uppercase tracking-wider text-rose-100">
                                NEW GOAL &amp; PERFORMANCE APPRAISAL ASSIGNED!
                            </span>
                        </div>
                        <p class="text-xs font-medium text-white/95 leading-snug">
                            Your Line Manager <strong>({{ pendingAssignedGoal.manager_name || 'Line Manager' }})</strong> has assigned your performance goals and appraisal template for FY {{ pendingAssignedGoal.year || currentYear }}. Please click below to fill your SMART objectives, quarterly tracking, and self-appraisal.
                        </p>
                    </div>
                </div>
                <button @click="openEmployeeGoal(pendingAssignedGoal)" class="px-5 py-2.5 bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider shadow-lg flex items-center gap-2 transition-all flex-shrink-0 cursor-pointer z-10">
                    <i class="pi pi-pencil text-xs"></i>
                    <span>Fill Goals &amp; Self-Appraisal</span>
                </button>
            </div>

            <!-- Page Header -->
            <div class="prof-card mb-6 bg-[#1A237E] !rounded-2xl">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-5 px-6">
                    <div class="flex items-center gap-4">
                        <div class="text-blue-200">
                            <i class="pi pi-flag-fill text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white text-[9px] font-black uppercase tracking-normal mb-0.5">Performance Management System</p>
                            <h1 class="text-lg font-black text-white tracking-tight">Define and track your strategic objectives using SMART criteria.</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <template v-if="isManager">
                            <button @click="openAssignModal" class="prof-button !bg-emerald-600 hover:!bg-emerald-700 px-5 py-2.5 flex items-center gap-2 group border-none shadow-lg text-sm text-white font-bold transition-all">
                                <i class="pi pi-user-plus font-black text-xs"></i>
                                <span class="font-black tracking-tight text-xs uppercase">Assign Goal & Appraisal</span>
                            </button>
                            <button @click="router.push({ path: '/pms/appraisal', query: { step: 1 } })" class="prof-button !bg-indigo-700/80 hover:!bg-indigo-600 border border-indigo-400/40 px-4 py-2.5 flex items-center gap-2 group shadow-lg text-xs text-white font-bold transition-all" title="Edit Appraisal Template & Key Competencies (Step 1)">
                                <i class="pi pi-file-edit font-black text-xs"></i>
                                <span class="font-black tracking-tight uppercase">Edit Appraisal Template (Step 1)</span>
                            </button>
                            <button @click="startCreateGoal" class="prof-button !bg-[#334155] hover:!bg-slate-700 px-5 py-2.5 flex items-center gap-2 group border-none shadow-lg text-sm text-white font-bold transition-all">
                                <i class="pi pi-plus font-black text-xs"></i>
                                <span class="font-black tracking-tight text-xs uppercase">NEW SMART GOAL</span>
                            </button>
                        </template>
                        <template v-else>
                            <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-xs font-black text-white flex items-center gap-2">
                                <i class="pi pi-id-card text-blue-200"></i>
                                <span>{{ loguser.name }} ({{ loguser.employee_code || 'Employee' }})</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>



            <!-- Dashboard Stats & Table (Manager only) -->
            <div v-if="isManager" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <!-- Total Goals -->
                <div class="prof-card p-4 !bg-[#5830E0] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform" @click="filterStatus = 'all'">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="pi pi-flag-fill text-xs"></i>
                            <span class="text-[9px] font-black uppercase tracking-normal text-indigo-100">Total Goals</span>
                        </div>
                        <div class="text-2xl font-black text-white mb-0.5">{{ stats.total }}</div>
                        <div class="text-[9px] font-bold text-indigo-200 uppercase tracking-normal">Objectives</div>
                    </div>
                </div>

                <!-- Assigned -->
                <div class="prof-card p-4 !bg-[#3949AB] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform" @click="filterStatus = 'assigned'">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="pi pi-bell text-xs"></i>
                            <span class="text-[9px] font-black uppercase tracking-normal text-indigo-200">Assigned</span>
                        </div>
                        <div class="text-2xl font-black text-white mb-0.5">{{ stats.assigned }}</div>
                        <div class="text-[9px] font-bold text-indigo-200 uppercase tracking-normal">Awaiting Employee</div>
                    </div>
                </div>

                <!-- Submitted -->
                <div class="prof-card p-4 !bg-[#D97706] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform" @click="filterStatus = 'submitted'">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="pi pi-clock text-xs"></i>
                            <span class="text-[9px] font-black uppercase tracking-normal text-amber-200">Submitted</span>
                        </div>
                        <div class="text-2xl font-black text-white mb-0.5">{{ stats.submitted }}</div>
                        <div class="text-[9px] font-bold text-amber-200 uppercase tracking-normal">Awaiting Review</div>
                    </div>
                </div>

                <!-- Appraisal Completed -->
                <div class="prof-card p-4 !bg-[#0D7377] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform" @click="filterStatus = 'appraisal_completed'">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="pi pi-check-circle text-xs"></i>
                            <span class="text-[9px] font-black uppercase tracking-normal text-teal-200">Appraisal Done</span>
                        </div>
                        <div class="text-2xl font-black text-white mb-0.5">{{ stats.appraisalCompleted }}</div>
                        <div class="text-[9px] font-bold text-teal-200 uppercase tracking-normal">Meeting Done</div>
                    </div>
                </div>

                <!-- Review Completed -->
                <div class="prof-card p-4 !bg-[#14532d] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform" @click="filterStatus = 'review_completed'">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="pi pi-verified text-xs"></i>
                            <span class="text-[9px] font-black uppercase tracking-normal text-green-200">Review Done</span>
                        </div>
                        <div class="text-2xl font-black text-white mb-0.5">{{ stats.reviewCompleted }}</div>
                        <div class="text-[9px] font-bold text-green-300 uppercase tracking-normal">Fully Completed</div>
                    </div>
                </div>
            </div>



            <!-- Goals List Section (Visible to both Manager and Employee) -->
            <div class="prof-card mb-6 !rounded-2xl">
                 <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/30">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                                <span>{{ isManager ? 'Active SMART Goals' : 'My Performance Goals & Appraisals' }}</span>
                                <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black border border-indigo-100">{{ filteredGoals.length }}</span>
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-normal font-bold">
                                {{ isManager ? 'Manage and track your team\'s performance objectives.' : 'Track your objectives, fill self-evaluations, and view/print your performance dossiers.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Filters Toolbar -->
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6 mt-6">
                        <!-- Status Filter Tabs -->
                        <div class="flex bg-gray-200 p-1.5 rounded-xl border border-gray-100 flex-wrap">
                            <button @click="filterStatus = 'all'" 
                                :class="filterStatus === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-indigo-600'" 
                                class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                All
                            </button>
                            <button @click="filterStatus = 'assigned'" 
                                :class="filterStatus === 'assigned' ? 'bg-indigo-700 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-indigo-700'" 
                                class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                Assigned
                            </button>
                            <button @click="filterStatus = 'submitted'" 
                                :class="filterStatus === 'submitted' ? 'bg-amber-600 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-amber-600'" 
                                class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                Submitted
                            </button>
                            <template v-if="isManager">
                                <button @click="filterStatus = 'goal_created'" 
                                    :class="filterStatus === 'goal_created' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-blue-600'" 
                                    class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                    In Progress
                                </button>
                                <button @click="filterStatus = 'appraisal_completed'" 
                                    :class="filterStatus === 'appraisal_completed' ? 'bg-teal-600 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-teal-600'" 
                                    class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                    Appraisal Done
                                </button>
                                <button @click="filterStatus = 'review_completed'" 
                                    :class="filterStatus === 'review_completed' ? 'bg-green-700 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-green-700'" 
                                    class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                    Review Done
                                </button>
                                <button @click="filterStatus = 'draft'" 
                                    :class="filterStatus === 'draft' ? 'bg-gray-600 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-gray-800'" 
                                    class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                    Drafts
                                </button>
                            </template>
                        </div>

                        <!-- Actions & Search Box -->
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <button @click="downloadCSV" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl shadow-md text-xs flex items-center justify-center gap-2 transition-all active:scale-95">
                                <i class="pi pi-file-excel text-sm"></i> Export CSV
                            </button>
                            <div class="relative w-full md:w-80 border-gray-100">
                                <input v-model="searchQuery" type="text" placeholder="Search goals, name, code..."
                                    class="prof-input pl-12 w-full !bg-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!loading && goals.length === 0" class="p-20 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-inbox text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ isManager ? 'No SMART Goals Found' : 'No Performance Goals Assigned Yet' }}</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">
                        {{ isManager ? 'Get started by creating or assigning your first SMART goal for this year. Set clear objectives to drive success.' : 'Your line manager has not yet assigned a goal for this cycle. Once assigned, it will appear here.' }}
                    </p>
                    <button v-if="isManager" @click="startCreateGoal" class="px-6 py-3 bg-[#1A237E] hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg shadow-purple-200 transition-all transform hover:-translate-y-1 flex items-center gap-2 mx-auto cursor-pointer">
                        <i class="pi pi-plus text-white"></i>
                        <span class="text-lg text-white">Create Your First Goal</span>
                    </button>
                </div>

                <!-- No results for current filter/search -->
                <div v-else-if="filteredGoals.length === 0" class="p-16 text-center">
                    <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-filter-slash text-2xl text-purple-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-1">No matching goals</h3>
                    <p class="text-gray-500 text-sm mb-4">Try adjusting your filters or search query.</p>
                    <button @click="filterStatus = 'all'; searchQuery = ''" class="px-5 py-2 bg-purple-100 text-purple-700 font-bold rounded-lg hover:bg-purple-200 transition-all text-sm">
                        <i class="pi pi-filter-slash mr-1"></i> Clear Filters
                    </button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 uppercase font-bold text-xs border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 w-[25%]">Goal Details</th>
                                <th class="px-6 py-4 w-[15%]">Record ID</th>
                                <th class="px-6 py-4 w-[12%]">SMART Score</th>
                                <th class="px-6 py-4 w-[15%]">Progress</th>
                                <th class="px-6 py-4 w-[13%]">Status</th>
                                <th class="px-6 py-4 w-[10%] text-center">Rating</th>
                                <th class="px-6 py-4 w-[10%] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(goal, idx) in filteredGoals" :key="goal.id" class="hover:bg-indigo-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-2 h-2 rounded-full" :class="[goal.display_status === 'review_completed' ? 'bg-green-500' : goal.display_status === 'appraisal_completed' ? 'bg-teal-500' : goal.display_status === 'goal_created' ? 'bg-blue-500 pulse-subtle' : 'bg-amber-500']"></div>
                                        <strong class="text-gray-800 text-base font-black">{{ goal.candidate_name || 'N/A' }}</strong>
                                        <span v-if="goal.display_status === 'draft'" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-600 rounded-lg border border-amber-100">Draft</span>
                                    </div>
                                    <div class="text-indigo-900 text-[11px] font-extrabold pl-4 mb-0.5">{{ goal.title }}</div>
                                    <div class="text-gray-400 text-[10px] font-bold line-clamp-1 pl-4">
                                        {{ parseList(goal.description)[0] || 'No description provided' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[11px] font-black text-slate-800 tracking-tight">{{ getRecordId(goal) }}</span>
                                </td>
                                 <td class="px-6 py-4">
                                     <div class="flex items-center gap-1.5">
                                        <span v-for="criteria in smartLabels" :key="criteria.key"
                                            :class="[goal.smart_criteria?.[criteria.key] ? criteria.color : 'bg-gray-50 text-gray-200 border-gray-100']"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-black border transition-all">
                                            {{ criteria.short }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden border border-gray-100 shadow-inner">
                                            <div class="h-full rounded-full transition-all duration-1000 ease-out relative"
                                                :class="getTimeProgress(goal) === 100 ? 'bg-gradient-to-r from-teal-400 to-emerald-500' : 'bg-gradient-to-r from-indigo-400 via-indigo-500 to-indigo-600'"
                                                :style="{ width: getTimeProgress(goal) + '%' }">
                                                <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                            </div>
                                        </div>
                                        <span class="text-[11px] font-black text-gray-700 w-12 text-right">{{ getTimeProgress(goal) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="goal.display_status === 'draft'" class="px-3 py-1.5 rounded-xl bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-widest border border-slate-200 shadow-sm">
                                        <i class="pi pi-file-edit mr-1.5"></i> Draft
                                    </span>
                                    <span v-else-if="goal.display_status === 'assigned'" class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-widest border border-indigo-200 shadow-sm">
                                        <i class="pi pi-bell mr-1.5 text-xs"></i> Assigned
                                    </span>
                                    <span v-else-if="goal.display_status === 'submitted'" class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 text-[10px] font-black uppercase tracking-widest border border-amber-200 shadow-sm">
                                        <i class="pi pi-clock mr-1.5 text-xs"></i> Submitted (Awaiting Review)
                                    </span>
                                    <span v-else-if="goal.display_status === 'goal_created'" class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-widest border border-blue-200 shadow-sm">
                                        <i class="pi pi-file-plus mr-1.5 text-xs"></i> In Progress
                                    </span>
                                    <span v-else-if="goal.display_status === 'appraisal_completed'" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-700 text-[10px] font-black uppercase tracking-widest border border-teal-200 shadow-sm">
                                        <i class="pi pi-check-circle mr-1.5 text-xs"></i> Appraisal Done
                                    </span>
                                    <span v-else-if="goal.display_status === 'review_completed'" class="px-3 py-1.5 rounded-xl bg-green-50 text-green-700 text-[10px] font-black uppercase tracking-widest border border-green-200 shadow-sm">
                                        <i class="pi pi-verified mr-1.5 text-xs"></i> Review Completed
                                    </span>
                                    <span v-else class="px-3 py-1.5 rounded-xl bg-gray-50 text-gray-500 text-[10px] font-black uppercase tracking-widest border border-gray-100 shadow-sm">
                                        <i class="pi pi-minus mr-1.5 text-xs"></i> {{ goal.display_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center font-black text-[11px]">
                                    <span v-if="getManagerRating(goal)" class="px-2 py-1 bg-slate-100 text-slate-700 rounded border border-slate-200" :title="'Manager avg: ' + getManagerRating(goal) + '/5'">
                                        {{ getManagerRating(goal) }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Fill / Edit Button (Hidden if review is completed) -->
                                        <button v-if="!isGoalReviewCompleted(goal) && (isManager || goal.status === 'assigned' || goal.status === 'draft' || goal.status === 'in_progress')" @click="editGoal(goal)" 
                                            class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-lg border border-indigo-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm cursor-pointer"
                                            :title="isManager ? 'Edit SMART Goal' : (goal.status === 'assigned' ? 'Fill Goals & Self-Appraisal' : 'Edit')">
                                            <i class="pi pi-pencil text-[9px]"></i> {{ isManager ? 'Edit Goal' : (goal.status === 'assigned' ? 'Fill Goals & Appraisal' : 'Edit') }}
                                        </button>

                                        <!-- Edit Appraisal Step 1 Button (Manager - Hidden if review is completed) -->
                                        <button v-if="isManager && !isGoalReviewCompleted(goal)" @click="router.push({ path: '/pms/appraisal', query: { goal_id: goal.id, employee_code: goal.candidate_code || goal.employee_code, step: 1 } })" 
                                            class="flex items-center gap-1.5 px-3 py-1.5 bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white rounded-lg border border-teal-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm cursor-pointer"
                                            title="Edit Appraisal Template & Key Competencies (Step 1)">
                                            <i class="pi pi-file-edit text-[9px]"></i> Edit Appraisal (Step 1)
                                        </button>

                                        <!-- Review Button (for managers) -->
                                        <button v-if="goal.status === 'submitted' && isManager" @click="router.push({ path: '/pms/review' })" 
                                            class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white rounded-lg border border-amber-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm cursor-pointer">
                                            <i class="pi pi-verified text-[9px]"></i> Review
                                        </button>
                                        
                                        <!-- Appraise Button (for in-progress) -->
                                        <button v-if="goal.status === 'in_progress'" @click="router.push({ name: 'pms-appraisal', query: { goal_id: goal.id } })" 
                                            class="flex items-center gap-2 px-3 py-1.5 bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white rounded-lg border border-teal-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm cursor-pointer">
                                            <i class="pi pi-star-fill text-[9px]"></i> Appraise
                                        </button>
                                        
                                        <!-- View Dossier Button (Available for all goals) -->
                                        <button @click="viewGoalDetail(goal)" 
                                            class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-800 hover:bg-[#1A237E] hover:text-white rounded-lg border border-slate-300 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm cursor-pointer"
                                            title="View and Print Official Performance Dossier">
                                            <i class="pi pi-print text-[10px]"></i> View Dossier
                                        </button>

                                        <!-- Delete Button (Manager only) -->
                                        <button v-if="isManager" @click="deleteGoal(goal.id)" 
                                            class="flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white rounded-lg border border-red-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm">
                                            <i class="pi pi-trash text-[9px]"></i> Delete
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
            <!-- Dashboard Header - Bold Title -->
            <div class="mb-6">
                <div class="bg-[#1A237E] rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-8 py-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-4">
                            <button @click="cancelCreate" class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition-all border border-white/10 active:scale-95">
                                <i class="pi pi-arrow-left font-black text-xs"></i>
                            </button>
                            <div>
                                <h1 class="text-2xl font-black text-white tracking-normal uppercase flex items-center gap-3">
                                    <span>{{ currentStep === 1 ? 'YEARLY SMART GOALS SETTING' : currentStep === 2 ? 'QUARTERLY TARGET TRACKING & EVIDENCE' : 'ANNUAL PERFORMANCE APPRAISAL (SELF-EVALUATION)' }}</span>
                                    <div v-if="currentStep >= 2" class="flex items-center gap-2 px-3 py-1 bg-white/10 rounded-xl border border-white/20 backdrop-blur-sm">
                                        <i class="pi pi-user text-indigo-200 text-xs"></i>
                                        <span class="text-sm font-bold tracking-normal normal-case text-indigo-100">{{ newGoal.candidate_name || 'Pending Candidate' }}</span>
                                    </div>
                                </h1>
                                <p class="text-indigo-200 text-[10px] font-black mt-1 tracking-normal uppercase opacity-80">
                                    <template v-if="currentStep === 1">Step 1 of 3: GOAL DEFINITION & STRATEGIC OBJECTIVES</template>
                                    <template v-else-if="currentStep === 2">Step 2 of 3: MEASURE (Growth Over Last Year & Quarters) — Keep a log of your progress.</template>
                                    <template v-else>Step 3 of 3: PERFORMANCE KEY COMPETENCIES & SIGN-OFF</template>
                                    <span class="ml-2 px-2 py-0.5 rounded bg-white/15 text-white text-[10px] font-bold border border-white/10">FY {{ currentYear }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Stepper Pills (3 Steps) -->
                        <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl backdrop-blur-sm border border-white/10">
                            <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all duration-500 cursor-pointer" @click="currentStep = 1" :class="currentStep === 1 ? 'bg-white text-[#1A237E] shadow-lg' : 'text-white/60'">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs" :class="currentStep >= 1 ? (currentStep === 1 ? 'bg-[#1A237E] text-white' : 'bg-green-500 text-white') : 'bg-white/20'">
                                    <span v-if="currentStep > 1">✓</span><span v-else>1</span>
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Definition</span>
                            </div>
                            <div class="w-5 h-[2px] rounded-full" :class="currentStep >= 2 ? 'bg-white/50' : 'bg-white/10'"></div>
                            <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all duration-500 cursor-pointer" @click="currentStep = 2" :class="currentStep === 2 ? 'bg-white text-[#1A237E] shadow-lg' : 'text-white/60'">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs" :class="currentStep >= 2 ? (currentStep === 2 ? 'bg-[#1A237E] text-white' : 'bg-green-500 text-white') : 'bg-white/20'">
                                    <span v-if="currentStep > 2">✓</span><span v-else>2</span>
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Tracking</span>
                            </div>
                            <div class="w-5 h-[2px] rounded-full" :class="currentStep >= 3 ? 'bg-white/50' : 'bg-white/10'"></div>
                            <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all duration-500 cursor-pointer" @click="currentStep = 3" :class="currentStep === 3 ? 'bg-white text-[#1A237E] shadow-lg' : 'text-white/60'">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs" :class="currentStep === 3 ? 'bg-[#1A237E] text-white' : 'bg-white/20'">
                                    3
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Appraisal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Read-Only Banner when Submitted or Reviewed -->
            <div v-if="isFormReadOnly" class="mb-5 p-4 rounded-2xl border flex items-center justify-between gap-4"
                :class="newGoal.status === 'review_completed' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800'">
                <div class="flex items-center gap-3">
                    <i :class="newGoal.status === 'review_completed' ? 'pi pi-check-circle text-emerald-600' : 'pi pi-lock text-amber-600'" class="text-xl"></i>
                    <div>
                        <h4 class="font-black text-sm uppercase tracking-wide">
                            {{ newGoal.status === 'review_completed' ? 'Review Completed & Finalized' : 'Submission Locked Under Manager Review' }}
                        </h4>
                        <p class="text-xs font-semibold opacity-90">
                            {{ newGoal.status === 'review_completed' 
                                ? 'Your Line Manager has completed and approved this performance appraisal.' 
                                : 'Your goals and self-appraisal have been signed off and submitted. Editing is locked while awaiting review.' }}
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-white border shadow-xs">
                    Status: {{ newGoal.status }}
                </span>
            </div>

            <Transition :name="stepTransition" mode="out-in">
                <!-- STEP 1: Goal Details -->
                <div v-if="currentStep === 1" :key="1" class="space-y-5">
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
                                    <p class="text-[9px] text-[#3949AB] font-black uppercase tracking-normal opacity-70">Job Title & Identification</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <!-- Locked for Employee: Pre-filled with Candidate Name & ID (NO search bar) -->
                            <div v-if="isEmployee" class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-base shadow-sm">
                                    {{ (newGoal.candidate_name || loguser.name || 'E').charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-base font-black text-slate-800 leading-tight uppercase">{{ newGoal.candidate_name || loguser.name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded uppercase">ID: {{ newGoal.employee_code || loguser.employee_code || 'N/A' }}</span>
                                        <span class="text-[10px] font-bold text-slate-500"><i class="pi pi-lock text-[8px] mr-1"></i>Pre-filled & Locked</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Searchable AutoComplete for Manager -->
                            <AutoComplete
                                v-else
                                v-model="selectedCandidate"
                                :suggestions="filteredMasterEmployees"
                                @complete="searchCandidate"
                                @item-select="onCandidateSelect"
                                optionLabel="full_string"
                                placeholder="Type staff name or ID..."
                                inputClass="!w-full !bg-[#F8FAFC] !border !border-gray-200 !rounded-lg !py-2.5 !px-3 !text-sm !font-semibold transition-all focus:!bg-white focus:!border-[#1A237E]"
                                class="w-full"
                            >
                                <template #item="slotProps">
                                    <div class="flex items-center gap-3 py-2 px-1">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-black text-xs border border-indigo-100 shadow-sm">
                                            {{ slotProps.item.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-0.5">
                                                <div class="font-black text-slate-800 text-sm truncate">{{ slotProps.item.name }}</div>
                                                <div class="text-[9px] font-black px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded uppercase">ID: {{ slotProps.item.employee_code }}</div>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400">
                                                <span class="flex items-center gap-1">
                                                    <i class="pi pi-building text-[8px]"></i>
                                                    {{ slotProps.item.department && slotProps.item.department !== 'N/A' ? slotProps.item.department : (finddept(slotProps.item.joining_dept_id) || 'No Dept') }}
                                                </span>
                                                <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                                <span class="flex items-center gap-1">
                                                    <i class="pi pi-map-marker text-[8px]"></i>
                                                    {{ slotProps.item.location && slotProps.item.location !== 'N/A' ? slotProps.item.location : (findbranch(slotProps.item.joining_branch_id) || 'No Loc') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </AutoComplete>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-[#F1F5F9] rounded text-[10px] font-semibold text-gray-500 border border-gray-100">Joining Department: {{ newGoal.department || 'N/A' }}</span>
                                <span class="px-3 py-1 bg-[#F1F5F9] rounded text-[10px] font-semibold text-gray-500 border border-gray-100">Joining Location: {{ newGoal.location || 'N/A' }}</span>
                                <span class="px-3 py-1 bg-indigo-50 rounded text-[10px] font-black text-indigo-700 border border-indigo-100">Joining Position: {{ newGoal.job_title || 'N/A' }}</span>
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
                                    <p class="text-lg font-bold text-gray-800 leading-none mb-1">{{ newGoal.manager_name }}</p>
                                    <span class="text-[10px] font-semibold text-gray-400">Authorized Evaluator</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-semibold text-gray-400 mb-0.5">Date</p>
                                    <p class="font-bold text-sm text-gray-700">{{ new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
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
                                <p class="text-xs text-[#283593] font-medium italic mt-0.5">Be specific and concise. Include the measure and time frame.</p>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="relative">
                                    <label class="block text-xs font-bold text-[#1A237E] uppercase tracking-wide mb-2">Goals Title</label>
                                    <input v-model="newGoal.title" :disabled="isFormReadOnly" type="text" 
                                        class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-3 px-4 text-sm font-semibold text-gray-800 focus:bg-white focus:border-[#1A237E] focus:ring-2 focus:ring-[#1A237E]/10 outline-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" 
                                        placeholder="E.g. Maximize operational efficiencies..." />
                                </div>
                                
                                <div class="space-y-3 pt-1">
                                    <label class="block text-xs font-bold text-[#3949AB] uppercase tracking-wide">Goals Description</label>
                                    <div v-for="(desc, index) in newGoal.description" :key="index" class="flex gap-3 group/item">
                                        <div class="w-8 h-8 rounded-lg bg-[#E8EAF6] flex items-center justify-center text-[#1A237E] font-bold text-xs border border-[#C5CAE9] shrink-0 mt-1">{{ index + 1 }}</div>
                                        <textarea v-model="newGoal.description[index]" :disabled="isFormReadOnly" rows="2" 
                                            class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 focus:border-[#1A237E] focus:ring-2 focus:ring-[#1A237E]/10 outline-none resize-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" 
                                            placeholder="Define a measurable performance indicator..."></textarea>
                                        <button @click="removeDescription(index)" v-if="!isFormReadOnly && newGoal.description.length > 1" 
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/item:opacity-100 shrink-0 mt-1" title="Remove">
                                            <i class="pi pi-times text-xs"></i>
                                        </button>
                                    </div>
                                    <button v-if="!isFormReadOnly" @click="addDescription" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#1A237E] bg-[#E8EAF6] hover:bg-[#C5CAE9] rounded-lg transition-all active:scale-95 border border-[#C5CAE9]">
                                        <i class="pi pi-plus text-[10px]"></i> Add Goals Description
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- PURPOSES Section -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="bg-[#B2DFDB] px-5 py-3 border-b border-[#80CBC4]">
                                <h4 class="font-bold text-base text-[#004D40] uppercase tracking-wide">PURPOSES</h4>
                                <p class="text-xs text-[#00695C] font-medium italic mt-0.5">Why is the goal relevant? What are the benefits?</p>
                            </div>
                            <div class="p-5 space-y-3">
                                <div v-for="(purpose, index) in newGoal.purposes" :key="index" class="flex gap-3 group/item">
                                    <div class="w-8 h-8 rounded-lg bg-[#E0F2F1] text-[#00695C] flex items-center justify-center font-bold text-xs border border-[#B2DFDB] shrink-0 mt-1">
                                        <i class="pi pi-check text-[10px]"></i>
                                    </div>
                                    <textarea v-model="newGoal.purposes[index]" :disabled="isFormReadOnly" rows="2" 
                                        class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 focus:border-[#00695C] focus:ring-2 focus:ring-[#00695C]/10 outline-none resize-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" 
                                        placeholder="Establish the business relevance..."></textarea>
                                    <button @click="removePurpose(index)" v-if="!isFormReadOnly && newGoal.purposes.length > 1" 
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/item:opacity-100 shrink-0 mt-1" title="Remove">
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </div>
                                <button v-if="!isFormReadOnly" @click="addPurpose" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#004D40] bg-[#E0F2F1] hover:bg-[#B2DFDB] rounded-lg transition-all active:scale-95 border border-[#B2DFDB]">
                                    <i class="pi pi-plus text-[10px]"></i> Add Purpose
                                </button>
                            </div>
                        </div>

                        <!-- CHALLENGES Section -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="bg-[#FFE0B2] px-5 py-3 border-b border-[#FFCC80]">
                                <h4 class="font-bold text-base text-[#E65100] uppercase tracking-wide">CHALLENGES</h4>
                                <p class="text-xs text-[#EF6C00] font-medium italic mt-0.5">What are the challenges to overcome? What resources and skills are needed?</p>
                            </div>
                            <div class="p-5 space-y-3">
                                <div v-for="(challenge, index) in newGoal.challenges" :key="index" class="flex gap-3 group/item">
                                    <div class="w-8 h-8 rounded-lg bg-[#FFF3E0] text-[#E65100] flex items-center justify-center font-bold text-xs border border-[#FFE0B2] shrink-0 mt-1">
                                        <i class="pi pi-exclamation-triangle text-[10px]"></i>
                                    </div>
                                    <textarea v-model="newGoal.challenges[index]" :disabled="isFormReadOnly" rows="2" 
                                        class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 focus:border-[#E65100] focus:ring-2 focus:ring-[#E65100]/10 outline-none resize-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" 
                                        placeholder="Anticipate potential roadblocks..."></textarea>
                                    <button @click="removeChallenge(index)" v-if="!isFormReadOnly && newGoal.challenges.length > 1" 
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/item:opacity-100 shrink-0 mt-1" title="Remove">
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </div>
                                <button v-if="!isFormReadOnly" @click="addChallenge" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#E65100] bg-[#FFF3E0] hover:bg-[#FFE0B2] rounded-lg transition-all active:scale-95 border border-[#FFE0B2]">
                                    <i class="pi pi-plus text-[10px]"></i> Add Challenge
                                </button>
                            </div>
                        </div>

                        <!-- COMPLETION DATE Section -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="bg-[#CFD8DC] px-5 py-3 border-b border-[#B0BEC5]">
                                <h4 class="font-bold text-base text-[#37474F] uppercase tracking-wide">COMPLETION DATE</h4>
                                <p class="text-xs text-[#546E7A] font-medium italic mt-0.5">Classification, target and timeline</p>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Category -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-[#37474F] uppercase tracking-wide">Category</label>
                                        <select v-model="newGoal.category" :disabled="isFormReadOnly" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-700 focus:border-[#37474F] focus:ring-2 focus:ring-[#37474F]/10 outline-none transition-all disabled:opacity-75 disabled:cursor-not-allowed">
                                            <option>General</option>
                                            <option>Technical</option>
                                            <option>Development</option>
                                            <option>Behavioral</option>
                                            <option>Leadership</option>
                                        </select>
                                    </div>
                                    <!-- Target Value -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-[#37474F] uppercase tracking-wide">Target Performance</label>
                                        <input v-model="newGoal.target" :disabled="isFormReadOnly" type="number" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-700 focus:border-[#37474F] focus:ring-2 focus:ring-[#37474F]/10 outline-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" />
                                    </div>
                                    <!-- Completion Date -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-[#37474F] uppercase tracking-wide">Date To be Completed</label>
                                        <input v-model="newGoal.completion_date" :disabled="isFormReadOnly" type="date" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-700 focus:border-[#37474F] focus:ring-2 focus:ring-[#37474F]/10 outline-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: SMART Criteria Checklist -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden sticky top-6 shadow-md rounded-2xl border border-gray-200">
                            <!-- Table Header -->
                            <div class="bg-[#1A237E] px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-sm text-white uppercase tracking-wide">MY GOAL IS...</h4>
                                    <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-wider">Check (✓)</span>
                                </div>
                            </div>

                            <!-- Criteria Rows -->
                            <div class="divide-y divide-gray-100">
                                <div v-for="(criteria, cIndex) in smartLabels" :key="criteria.key" 
                                    class="flex items-center justify-between px-4 py-3.5 transition-all duration-200"
                                    :class="newGoal.smart_criteria[criteria.key] ? 'bg-green-50/50' : 'hover:bg-gray-50'">
                                    
                                    <!-- Criteria Name -->
                                    <span class="font-semibold text-sm text-gray-700">{{ criteria.label }}</span>
                                    
                                    <!-- Colored Letter Badge + Checkbox -->
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shrink-0"
                                            :class="criteria.badgeColor + ' text-white'">
                                            {{ criteria.short }}
                                        </div>
                                        <label class="relative cursor-pointer">
                                            <input type="checkbox" v-model="newGoal.smart_criteria[criteria.key]" :disabled="isFormReadOnly" 
                                                class="w-5 h-5 rounded-2xl border-2 border-gray-100 text-[#1A237E] focus:ring-[#1A237E] focus:ring-2 cursor-pointer accent-[#1A237E] disabled:cursor-not-allowed" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Score Display -->
                            <div class="border-t-2 border-gray-200 bg-[#F5F5F5] px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <!-- Circular Score -->
                                    <div class="relative w-12 h-12 shrink-0">
                                        <svg class="w-12 h-12 -rotate-90" viewBox="0 0 48 48">
                                            <circle cx="24" cy="24" r="20" fill="none" stroke-width="3" class="stroke-gray-200" />
                                            <circle cx="24" cy="24" r="20" fill="none" stroke-width="3" stroke-linecap="round"
                                                class="transition-all duration-1000"
                                                :class="getSmartScore(newGoal) === 5 ? 'stroke-[#2E7D32]' : getSmartScore(newGoal) >= 3 ? 'stroke-[#1565C0]' : 'stroke-gray-400'"
                                                :stroke-dasharray="125.6"
                                                :stroke-dashoffset="125.6 - (125.6 * getSmartScore(newGoal) / 5)" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-sm font-black" :class="getSmartScore(newGoal) === 5 ? 'text-[#2E7D32]' : getSmartScore(newGoal) >= 3 ? 'text-[#1565C0]' : 'text-gray-400'">
                                                {{ getSmartScore(newGoal) }}/5
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Score Details -->
                                    <div class="flex-1">
                                        <div class="text-[10px] font-bold uppercase tracking-wide mb-1"
                                            :class="getSmartScore(newGoal) === 5 ? 'text-[#2E7D32]' : getSmartScore(newGoal) >= 3 ? 'text-[#1565C0]' : 'text-gray-500'">
                                            {{ getSmartScore(newGoal) === 5 ? 'ALL CRITERIA MET' : getSmartScore(newGoal) >= 3 ? 'GOOD PROGRESS' : 'NEEDS WORK' }}
                                        </div>
                                            <div v-for="n in 5" :key="n" class="h-2 flex-1 rounded-full bg-gray-200"
                                                :class="n <= getSmartScore(newGoal) 
                                                    ? (getSmartScore(newGoal) === 5 ? 'bg-[#2E7D32]' : 'bg-[#1565C0]') 
                                                    : 'bg-gray-200'">
                                            </div>
                                        <p class="text-[9px] text-gray-500 font-semibold mt-1 uppercase tracking-wider">{{ getSmartScore(newGoal) }} of 5 criteria met</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Quarterly Tracking with Multiple Uploads -->
            <div v-else-if="currentStep === 2" :key="2" class="space-y-5">

                <!-- Removed Start & End Date Summary block as requested -->

                <!-- Quarterly Tracking Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                     <div v-for="(quarter, index) in newGoal.quarterly_tracking" :key="index" 
                        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col h-full hover:shadow-md transition-all duration-300">
                        <!-- Quarter Header - Colored -->
                        <div class="py-3 px-5 flex justify-between items-center"
                            :class="index === 0 ? 'bg-[#1A237E] text-white' : index === 1 ? 'bg-[#1A237E] text-white' : index === 2 ? 'bg-[#1A237E] text-white' : 'bg-[#1A237E] text-white'">
                            <h5 class="font-bold text-lg tracking-wide">
                                {{ quarter.quarter ? quarter.quarter.toUpperCase() : 'Q'+(index+1) }}
                            </h5>
                            <i class="pi pi-calendar text-white/70 text-xs"></i>
                        </div>
                        <div class="p-4 space-y-3 flex-1 flex flex-col">
                            <!-- Date Fields (Editable but Auto-populated) -->
                            <!-- Date Fields (Editable but Auto-populated) -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[9px] font-bold text-[#1A237E] uppercase tracking-wider mb-1 block">Start Date</label>
                                    <input type="date" v-model="quarter.start_date" :disabled="isFormReadOnly" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2 px-2 text-[11px] font-semibold text-gray-700 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/10 outline-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" />
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-[#E65100] uppercase tracking-wider mb-1 block">End Date</label>
                                    <input type="date" v-model="quarter.end_date" :disabled="isFormReadOnly" class="w-full bg-[#FFF3E0] border border-[#FFE0B2] rounded-lg py-2 px-2 text-[11px] font-semibold text-gray-700 focus:border-[#E65100] focus:ring-1 focus:ring-[#E65100]/10 outline-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" />
                                </div>
                            </div>
                            
                            <!-- Target Measure -->
                            <div class="relative flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block">Target Measure</label>
                                    <button v-if="!isFormReadOnly" @click="addExecutionTarget(index)" class="flex items-center gap-1 px-2 py-1 bg-[#E8EAF6] hover:bg-[#1A237E] hover:text-white rounded-lg transition-colors text-[#1A237E]">
                                        <i class="pi pi-plus text-[8px]"></i>
                                        <span class="text-[8px] font-bold uppercase tracking-wider">Add</span>
                                    </button>
                                </div>
                                <div class="space-y-2 mb-3">
                                    <div v-for="(measure, mIndex) in quarter.target_measures" :key="'m'+mIndex" class="flex items-start gap-2 group/measure">
                                        <textarea v-model="quarter.target_measures[mIndex]" :disabled="isFormReadOnly" rows="2" class="flex-1 bg-[#F8FAFC] border border-gray-200 rounded-lg py-2 px-3 text-sm font-medium text-gray-700 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/10 outline-none resize-none transition-all disabled:opacity-75 disabled:cursor-not-allowed" placeholder="Target measure..."></textarea>
                                        <button @click="removeExecutionTarget(index, mIndex)" v-if="!isFormReadOnly && quarter.target_measures.length > 1" class="w-7 h-7 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/measure:opacity-100 shrink-0 mt-1"><i class="pi pi-times text-[10px]"></i></button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Evidence -->
                            <div class="flex-1 flex flex-col justify-end">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Evidence</label>
                                    <span class="text-[9px] font-bold text-[#1A237E] bg-[#E8EAF6] px-2 py-0.5 rounded-full">{{ quarter.attachments?.length || 0 }} FILES</span>
                                </div>
                                
                                <div class="space-y-2 mb-3 min-h-[40px]">
                                    <div v-for="(file, fIndex) in quarter.attachments" :key="fIndex" class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-100 group/file hover:bg-white hover:shadow-sm transition-all">
                                        <div class="flex items-center gap-2 overflow-hidden flex-1 cursor-pointer" @click="viewAttachment(file)" title="Click to view file">
                                            <div class="w-7 h-7 rounded-lg bg-[#E8EAF6] text-[#1A237E] flex items-center justify-center flex-shrink-0">
                                                <i class="pi pi-file text-xs"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <p class="truncate max-w-[130px] text-[10px] font-bold text-gray-700 hover:text-blue-600 transition-colors">{{ file.file_name || file.name }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="viewAttachment(file)" class="w-6 h-6 rounded-md bg-white border border-gray-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all cursor-pointer" title="Preview File">
                                                <i class="pi pi-eye text-[9px]"></i>
                                            </button>
                                            <button type="button" @click="downloadAttachment(file)" class="w-6 h-6 rounded-md bg-white border border-gray-200 flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all cursor-pointer" title="Download File">
                                                <i class="pi pi-download text-[9px]"></i>
                                            </button>
                                            <button v-if="!isFormReadOnly" type="button" @click="removeAttachment(index, fIndex)" class="w-6 h-6 rounded-md bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-white hover:bg-red-500 hover:border-red-500 transition-all cursor-pointer" title="Remove File">
                                                <i class="pi pi-times text-[8px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="uploading[index]" class="mt-4 p-3 bg-indigo-50/50 rounded-xl border border-indigo-100/50">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">Uploading Evidence...</span>
                                            <span class="text-[9px] font-black text-indigo-600">{{ uploadProgress[index] || 0 }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-indigo-100 rounded-full overflow-hidden shadow-inner">
                                            <div class="h-full bg-indigo-600 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(79,70,229,0.4)]"
                                                :style="{ width: (uploadProgress[index] || 0) + '%' }">
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="!quarter.attachments?.length && !uploading[index]" class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider py-4 text-center border-2 border-dashed border-gray-100 rounded-lg flex flex-col items-center gap-1 bg-gray-50/20">
                                        <i class="pi pi-cloud-upload text-sm opacity-30 text-[#1A237E]"></i>
                                        No files yet
                                    </div>
                                </div>
 
                                <template v-if="!isFormReadOnly">
                                    <input type="file" multiple @change="handleFileUpload($event, index)" class="hidden" :id="'file-upload-'+index">
                                    <label :for="'file-upload-'+index" class="w-full flex items-center justify-center gap-2 py-2 bg-[#E8EAF6] hover:bg-[#1A237E] hover:text-white rounded-lg cursor-pointer transition-all text-black text-xs font-bold uppercase tracking-wider">
                                        <i class="pi pi-plus text-[10px]"></i>
                                        Add Evidence
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- STEP 3: Performance Key Competencies (Self-Rating ONLY) -->
            <div v-else-if="currentStep === 3" :key="3" class="space-y-6">
                <!-- Competencies Table Card -->
                <div class="bg-white rounded-3xl border border-gray-200 shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/80 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <i class="pi pi-chart-bar text-[#1A237E]"></i> Performance Key Competencies &amp; Self-Rating
                            </h3>
                            <p class="text-gray-400 text-[10px] uppercase tracking-normal font-bold">
                                Annual performance evaluation (5 Key Areas &bull; Self-Evaluation Mode)
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white shadow-xs flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase text-slate-400">Total Weight:</span>
                                <span class="text-xs font-black text-emerald-700">100%</span>
                            </div>
                            <div class="px-3.5 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50/80 shadow-xs flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase text-indigo-700">Self Score:</span>
                                <span class="text-base font-black text-indigo-700">{{ selfAppraisalOverallRating }}</span>
                                <span class="text-[10px] font-bold text-indigo-300">/ 5.00</span>
                                <span class="text-xs font-black text-indigo-800 bg-white border border-indigo-200 px-2 py-0.5 rounded-md">
                                    {{ selfAppraisalOverallPercentage }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50/90 border-b border-gray-100">
                                    <th class="px-4 py-3 text-left text-[9px] font-black text-gray-400 uppercase tracking-normal w-12">#</th>
                                    <th class="px-4 py-3 text-left text-[9px] font-black text-gray-400 uppercase tracking-normal">Competency &amp; Specific Criteria</th>
                                    <th class="px-4 py-3 text-center text-[9px] font-black text-gray-400 uppercase tracking-normal w-24">Weight (%)</th>
                                    <th class="px-4 py-3 text-center text-[9px] font-black text-indigo-600 uppercase tracking-normal w-32 bg-indigo-50/50">Self Rating</th>
                                    <th class="px-4 py-3 text-center text-[9px] font-black text-slate-400 uppercase tracking-normal w-32 bg-slate-50/60">Manager Rating</th>
                                    <th class="px-4 py-3 text-center text-[9px] font-black text-gray-400 uppercase tracking-normal w-24">W. Score</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(comp, index) in newGoal.appraisal_data.competencies" :key="comp.id || index" class="hover:bg-indigo-50/10 transition-colors">
                                    <td class="px-4 py-3.5 font-black text-gray-300 text-xs align-top pt-4">0{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-black text-slate-800 text-sm mb-1 tracking-tight">{{ comp.title }}</div>
                                        <div v-if="comp.descriptions && comp.descriptions.length" class="space-y-0.5">
                                            <p v-for="(desc, dIdx) in comp.descriptions" :key="dIdx" class="text-[11px] text-gray-500 leading-snug font-medium">{{ desc }}</p>
                                        </div>
                                        <p v-else-if="comp.descriptionText" class="text-[11px] text-gray-500 leading-snug font-medium whitespace-pre-line">{{ comp.descriptionText }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center align-middle">
                                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-black text-gray-700 bg-slate-100 border border-slate-200">
                                            {{ comp.weight || 20 }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 bg-indigo-50/30 align-middle">
                                        <!-- SELF RATING: Enabled for employee (pure numbers) -->
                                        <select v-model.number="comp.selfRating" :disabled="isFormReadOnly" class="prof-input !py-1.5 !px-2 w-full !text-center font-black text-xs !rounded-lg border-indigo-200 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600/20">
                                            <option :value="0">— Select —</option>
                                            <option :value="1">1</option>
                                            <option :value="2">2</option>
                                            <option :value="3">3</option>
                                            <option :value="4">4</option>
                                            <option :value="5">5</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 bg-slate-50/60 align-middle text-center">
                                        <!-- MANAGER RATING: Locked showing '--' for employee -->
                                        <div class="flex items-center justify-center">
                                            <div class="py-1.5 px-4 bg-slate-100/90 rounded-lg border border-slate-200 text-slate-400 font-black text-xs tracking-widest cursor-not-allowed select-none" title="Manager Rating is provided by your Line Manager during review">
                                                <span v-if="comp.managerRating && comp.managerRating > 0 && isManager">{{ comp.managerRating }}</span>
                                                <span v-else>--</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-black text-indigo-700 text-sm align-middle">
                                        {{ ((parseFloat(comp.selfRating || 0) * parseFloat(comp.weight || 20)) / 100).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50/90 border-t border-gray-100">
                                <tr>
                                    <td colspan="2" class="px-6 py-4 font-black text-gray-600 tracking-normal text-xs uppercase">
                                        Self-Assessment Evaluation Summary
                                    </td>
                                    <td class="px-4 py-4 text-center font-black text-xs text-emerald-700">100%</td>
                                    <td colspan="2" class="px-4 py-4 text-center font-black text-gray-400 uppercase tracking-wider text-[9px]">
                                        TOTAL WEIGHTED SELF SCORE
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="font-black text-indigo-700 text-xl">{{ selfAppraisalOverallRating }}</span>
                                            <span class="text-xs font-bold text-indigo-300">/ 5.00</span>
                                            <span class="text-[11px] font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded-md">
                                                {{ selfAppraisalOverallPercentage }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Self Feedback & Comments Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="p-5 bg-white rounded-3xl border border-gray-200 shadow-sm space-y-2">
                        <label class="text-[10px] font-black text-[#1A237E] uppercase tracking-wider block">What Impressed Most / Key Achievements</label>
                        <textarea v-model="newGoal.appraisal_data.impressedMost" :disabled="isFormReadOnly" rows="3"
                            class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl p-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-[#1A237E] outline-none resize-none transition-all"
                            placeholder="Detail projects, initiatives or milestones achieved successfully..."></textarea>
                    </div>
                    <div class="p-5 bg-white rounded-3xl border border-gray-200 shadow-sm space-y-2">
                        <label class="text-[10px] font-black text-[#1A237E] uppercase tracking-wider block">What Impressed Least / Challenges Overcome</label>
                        <textarea v-model="newGoal.appraisal_data.impressedLeast" :disabled="isFormReadOnly" rows="3"
                            class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl p-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-[#1A237E] outline-none resize-none transition-all"
                            placeholder="Detail obstacles encountered and lessons learned..."></textarea>
                    </div>
                </div>

                <!-- Yearly Performance Assessment: 1-5 Performance Rating & Potential Reference Guide Table -->
                <div class="p-6 bg-white rounded-3xl border border-gray-200 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#1A237E]"></span>
                                <h4 class="text-xs font-black text-[#1A237E] uppercase tracking-wider">Yearly Performance Assessment</h4>
                            </div>
                            <p class="text-[11px] text-gray-500 font-medium mt-0.5">Select your performance rating checkbox and leave your assessment comments in the comments column.</p>
                        </div>
                        <div v-if="newGoal.appraisal_data.performanceRating" class="flex items-center gap-2 self-start sm:self-auto px-3 py-1 bg-blue-50 border border-blue-200 rounded-xl">
                            <span class="text-[10px] font-bold text-blue-700 uppercase">Selected Rating:</span>
                            <span class="text-xs font-black text-blue-900">{{ newGoal.appraisal_data.performanceRating }} / 5</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border-2 border-slate-700 shadow-xs">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b-2 border-slate-700">
                                    <th class="px-4 py-3.5 font-black text-slate-900 text-center w-[30%] border-r-2 border-slate-700 text-xs tracking-tight">
                                        1-5 Rating (5 Highest)
                                    </th>
                                    <th class="px-4 py-3.5 text-center w-[20%] border-r-2 border-slate-700">
                                        <span class="text-blue-700 italic underline font-black text-xs tracking-tight">Performance Rating</span>
                                    </th>
                                    <th class="px-4 py-3.5 text-center w-[50%]">
                                        <span class="text-blue-700 italic underline font-black text-xs tracking-tight">Comments</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-300">
                                <tr v-for="rate in performanceRatings" :key="rate.value" 
                                    :class="newGoal.appraisal_data.performanceRating === rate.value ? 'bg-blue-50/70 font-semibold' : 'hover:bg-slate-50/50'"
                                    class="transition-colors border-b border-slate-300 last:border-b-0">
                                    <td class="px-4 py-3 text-slate-900 font-bold border-r border-slate-400 align-middle">
                                        {{ rate.label }}
                                    </td>
                                    <td class="px-4 py-3 text-center border-r border-slate-400 align-middle">
                                        <label class="inline-flex items-center justify-center gap-2 cursor-pointer select-none">
                                            <input 
                                                type="checkbox" 
                                                :checked="newGoal.appraisal_data.performanceRating === rate.value" 
                                                @change="toggleRating(rate.value)" 
                                                :disabled="isFormReadOnly" 
                                                class="w-4 h-4 text-blue-600 rounded cursor-pointer accent-blue-600"
                                            />
                                            <span class="font-black text-sm text-slate-800">{{ rate.value }}</span>
                                        </label>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <textarea 
                                            v-model="newGoal.appraisal_data.rating_comments[rate.value]" 
                                            :disabled="isFormReadOnly" 
                                            rows="2" 
                                            class="w-full bg-white border border-slate-300 rounded-lg p-2 text-xs font-medium text-slate-800 focus:bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600/30 outline-none resize-none transition-all placeholder:text-slate-400 placeholder:italic" 
                                            :placeholder="rate.value === 5 ? 'e.g. All issues reported are always resolved without a delay' : 'Enter comments for rating ' + rate.value + '...'"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- General Comments & Sign-off Box -->
                <div class="p-6 bg-white rounded-3xl border border-gray-200 shadow-sm space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-[#1A237E] uppercase tracking-wider block mb-1">Employee Self-Comments &amp; Development Goals</label>
                        <textarea v-model="newGoal.appraisal_data.comments" :disabled="isFormReadOnly" rows="3"
                            class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl p-3 text-sm font-medium text-slate-700 focus:bg-white focus:border-[#1A237E] outline-none resize-none transition-all"
                            placeholder="Any personal comments, skill areas for growth, or requests for training..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-gray-100">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Candidate Sign-Off</label>
                            <input v-model="newGoal.appraisal_data.candidate_signature_name" :disabled="isFormReadOnly" type="text"
                                class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl py-2.5 px-3 text-sm font-bold text-gray-800 focus:bg-white focus:border-[#1A237E] outline-none"
                                placeholder="Enter Full Name" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Date</label>
                            <input v-model="newGoal.appraisal_data.signature_date" :disabled="isFormReadOnly" type="date"
                                class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl py-2.5 px-3 text-sm font-bold text-gray-700 focus:bg-white focus:border-[#1A237E] outline-none" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Assigned Line Manager</label>
                            <div class="py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-black text-slate-700">
                                {{ newGoal.manager_name || 'Line Manager' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </Transition>

            <!-- Navigation Footer -->
            <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-xl shadow-indigo-900/5">
                <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-start">
                    <button v-if="currentStep > 1" @click="prevStep" 
                        class="prof-button !bg-white !text-gray-600 !border !border-gray-200 !rounded-3xl !py-3.5 px-8 hover:shadow-md transition-all">
                        <i class="pi pi-arrow-left mr-2 font-bold text-xs"></i> 
                        Previous Step
                    </button>
                    <button v-else @click="cancelCreate" 
                        class="prof-button !bg-white !text-gray-400 !border !border-gray-100 !rounded-3xl !py-3.5 px-8 hover:text-red-500 hover:shadow-sm transition-all text-xs font-black uppercase tracking-widest leading-none">
                        Back to List
                    </button>

                    <button v-if="!isFormReadOnly" @click="addGoal('draft')" :disabled="savingDraft"
                        class="prof-button !bg-white !text-indigo-600 !border !border-indigo-200/50 !rounded-3xl !py-3.5 px-8 shadow-sm hover:shadow-md transition-all">
                        <i class="pi pi-save mr-2 font-bold text-xs"></i> 
                        {{ savingDraft ? 'Saving...' : 'Save Draft' }}
                    </button>
                </div>

                <div class="flex items-center gap-4 w-full sm:w-auto justify-end">
                    <button v-if="currentStep < totalSteps" @click="nextStep" 
                        class="prof-button !bg-indigo-600 !text-white !rounded-3xl !py-3.5 px-8 shadow-xl shadow-indigo-600/30 hover:scale-[1.02] active:scale-95">
                        Next Stage
                        <i class="pi pi-arrow-right ml-3 text-xs font-bold"></i>
                    </button>
                    
                    <!-- Employee Final Submission Button in Step 3 -->
                    <template v-else-if="isEmployee">
                        <button v-if="!isFormReadOnly" @click="submitGoalForReview" :disabled="saving"
                            class="prof-button !bg-emerald-600 hover:!bg-emerald-700 !text-white !rounded-3xl !py-3.5 px-8 shadow-xl shadow-emerald-600/30 hover:scale-[1.02] active:scale-95">
                            <i v-if="saving" class="pi pi-spin pi-spinner mr-3"></i>
                            <i v-else class="pi pi-send mr-3 font-bold text-xs"></i>
                            {{ saving ? 'Submitting...' : 'Sign Off & Submit to Line Manager' }}
                        </button>
                        <div v-else class="px-6 py-3 bg-slate-100 text-slate-500 rounded-3xl font-black text-xs uppercase tracking-wider border border-slate-200">
                            <i class="pi pi-lock mr-2"></i> Submission Locked
                        </div>
                    </template>

                    <!-- Manager Submission Button -->
                    <button v-else-if="!isFormReadOnly" @click="addGoal('in_progress')" :disabled="saving"
                        class="prof-button !bg-teal-600 !text-white !rounded-3xl !py-3.5 px-8 shadow-xl shadow-teal-600/30 hover:scale-[1.02] active:scale-95">
                        <i v-if="saving" class="pi pi-spin pi-spinner mr-3"></i>
                        <i v-else class="pi pi-check-circle mr-3 font-bold text-xs"></i>
                        {{ saving ? 'Submitting...' : 'Save & Publish Goal' }}
                    </button>
                </div>
            </div>
        </template>

        <!-- ASSIGN GOAL & APPRAISAL MODAL (FOR MANAGERS) -->
        <Dialog v-model:visible="showAssignModal" :modal="true" :showHeader="false"
            class="!rounded-3xl !overflow-hidden !border-none !shadow-2xl"
            :style="{ width: '92vw', maxWidth: '750px', height: '88vh', maxHeight: '88vh', display: 'flex', flexDirection: 'column' }"
            :contentStyle="{ padding: '0', borderRadius: '1.5rem', overflow: 'hidden', display: 'flex', flexDirection: 'column', height: '100%', maxHeight: '100%', flex: '1 1 auto', minHeight: '0' }">
            <div class="bg-white rounded-3xl overflow-hidden flex flex-col h-full w-full flex-1 min-h-0">
                <!-- Modal Header (Fixed / Shrink 0) -->
                <div class="bg-[#1A237E] p-5 md:p-6 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white">
                            <i class="pi pi-user-plus text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight">Assign Goals &amp; Appraisal Template</h3>
                            <p class="text-xs text-indigo-200 font-medium">Provision portal access and set competency evaluation weights.</p>
                        </div>
                    </div>
                    <button @click="showAssignModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all cursor-pointer">
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- Modal Body (Scrollable / Flex 1) -->
                <div class="p-5 md:p-6 space-y-5 flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                    <!-- Assignment Target Toggle -->
                    <div>
                        <label class="text-xs font-black text-slate-700 uppercase tracking-wide block mb-2">Assignment Scope</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3.5 rounded-2xl border-2 cursor-pointer transition-all"
                                :class="assignForm.assign_type === 'specific' ? 'border-[#1A237E] bg-indigo-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" v-model="assignForm.assign_type" value="specific" class="accent-[#1A237E] w-4 h-4" />
                                <div>
                                    <p class="text-xs font-black text-slate-800 uppercase">Selected Employees</p>
                                    <p class="text-[10px] text-slate-500 font-medium">Select one or multiple team members</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3.5 rounded-2xl border-2 cursor-pointer transition-all"
                                :class="assignForm.assign_type === 'all_team' ? 'border-[#1A237E] bg-indigo-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" v-model="assignForm.assign_type" value="all_team" class="accent-[#1A237E] w-4 h-4" />
                                <div>
                                    <p class="text-xs font-black text-slate-800 uppercase">All Team Members</p>
                                    <p class="text-[10px] text-slate-500 font-medium">Bulk assign to entire team</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Selected Employees (Single or Multiple) -->
                    <div v-if="assignForm.assign_type === 'specific'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-black text-slate-700 uppercase tracking-wide">
                                Select Employee(s)
                                <span v-if="assignForm.selected_employees.length > 0" class="ml-1.5 px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 text-[10px] font-black">
                                    {{ assignForm.selected_employees.length }} selected
                                </span>
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllMasterEmployeesForAssign" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer">
                                    + Add All ({{ masterEmployees.length }})
                                </button>
                                <span class="text-slate-300">&bull;</span>
                                <button type="button" v-if="assignForm.selected_employees.length > 0" @click="clearAssignSelectedEmployees" class="text-[10px] font-black text-red-500 hover:text-red-700 hover:underline cursor-pointer">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- AutoComplete Search to Add -->
                        <AutoComplete
                            v-model="assignSearchEmployee"
                            :suggestions="filteredMasterEmployees"
                            @complete="searchCandidate"
                            @item-select="onAssignEmployeeSelect"
                            optionLabel="full_string"
                            placeholder="Type employee name or ID to add..."
                            inputClass="!w-full !bg-[#F8FAFC] !border !border-gray-200 !rounded-xl !py-2.5 !px-3.5 !text-sm !font-semibold transition-all focus:!bg-white focus:!border-[#1A237E]"
                            class="w-full"
                        >
                            <template #item="slotProps">
                                <div class="flex items-center gap-3 py-1.5 px-1">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-black text-xs shrink-0">
                                        {{ slotProps.item.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-800 text-xs truncate">{{ slotProps.item.name }}</div>
                                        <div class="text-[10px] font-medium text-slate-400 truncate">ID: {{ slotProps.item.employee_code }} &bull; {{ slotProps.item.department }}</div>
                                    </div>
                                    <span v-if="assignForm.selected_employees.some(e => e.employee_code === slotProps.item.employee_code)" class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                        Added
                                    </span>
                                </div>
                            </template>
                        </AutoComplete>

                        <!-- Selected Employees Badges / List -->
                        <div v-if="assignForm.selected_employees.length > 0" class="space-y-2 max-h-36 overflow-y-auto custom-scrollbar p-1">
                            <div v-for="(emp, idx) in assignForm.selected_employees" :key="emp.employee_code"
                                class="p-2.5 bg-indigo-50/70 rounded-xl border border-indigo-100 flex items-center justify-between gap-3 transition-all hover:bg-indigo-50">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-[11px] shrink-0">
                                        {{ emp.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-black text-indigo-950 text-xs truncate">{{ emp.name }}</div>
                                        <div class="text-[10px] font-semibold text-indigo-600 truncate">
                                            ID: {{ emp.employee_code }} &bull; {{ emp.department }}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="removeAssignEmployee(idx)" 
                                    class="w-6 h-6 rounded-lg bg-red-100/80 hover:bg-red-200 text-red-600 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Remove">
                                    <i class="pi pi-times text-[10px]"></i>
                                </button>
                            </div>
                        </div>

                        <div v-else class="p-3.5 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-center">
                            <p class="text-xs text-slate-400 font-medium">Search and select one or multiple employees above to assign.</p>
                        </div>
                    </div>

                    <!-- All Team Notice -->
                    <div v-else class="p-4 bg-blue-50/80 rounded-2xl border border-blue-200 flex items-start gap-3">
                        <i class="pi pi-users text-blue-600 mt-0.5"></i>
                        <div class="text-xs text-blue-800">
                            <p class="font-bold">Team-wide Bulk Assignment:</p>
                            <p class="text-[11px] text-blue-600 mt-0.5">
                                A goal and appraisal dossier will be created for all active personnel reporting to you.
                                User accounts will be automatically provisioned with: <strong>Username: Firstname EmployeeCode</strong>, <strong>Password: Password</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- Goal Meta Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-600 uppercase tracking-wider block mb-1">Goal Title</label>
                            <input v-model="assignForm.title" type="text"
                                class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl py-2 px-3 text-xs font-bold text-slate-800 focus:bg-white focus:border-[#1A237E] outline-none" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-600 uppercase tracking-wider block mb-1">Fiscal Year &amp; Target</label>
                            <div class="grid grid-cols-2 gap-2">
                                <select v-model="assignForm.year" class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl py-2 px-2 text-xs font-bold text-slate-800 focus:bg-white outline-none">
                                    <option v-for="y in years" :key="y" :value="y">FY {{ y }}</option>
                                </select>
                                <input v-model="assignForm.target" type="number" placeholder="Target %"
                                    class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl py-2 px-2 text-xs font-bold text-slate-800 focus:bg-white outline-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Competency Weights Customization -->
                    <div class="space-y-3 pt-2 border-t border-slate-100">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="text-xs font-black text-slate-800 uppercase tracking-wide">Key Competency Weights &amp; Template</label>
                                <p class="text-[10px] text-slate-400 font-medium">Customize percentage weights (Total must equal 100%)</p>
                            </div>
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <button type="button" @click="showAssignModal = false; router.push('/pms/appraisal')"
                                    class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-all flex items-center gap-1.5 cursor-pointer shadow-xs">
                                    <i class="pi pi-file-edit text-[9px]"></i>
                                    <span>Edit Appraisal Template (Step 1)</span>
                                </button>
                                <div class="px-2.5 py-1 rounded-xl text-xs font-black border"
                                    :class="assignTotalWeight === 100 ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-amber-50 text-amber-700 border-amber-300 animate-pulse'">
                                    Total: {{ assignTotalWeight }}%
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div v-for="(name, idx) in competencyNames" :key="idx"
                                class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="flex items-center gap-2 flex-1 pr-3">
                                    <span class="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px]">{{ idx + 1 }}</span>
                                    <span class="text-xs font-bold text-slate-700 truncate">{{ name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <select v-model.number="assignForm.weights[idx]"
                                        class="bg-white border border-slate-200 rounded-lg py-1 px-2 text-xs font-black text-slate-800 outline-none">
                                        <option v-for="w in [5, 10, 15, 20, 25, 30, 35, 40, 50, 60]" :key="w" :value="w">{{ w }}%</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credential Instructions Note -->
                    <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200 text-[11px] text-amber-800 flex items-center gap-2">
                        <i class="pi pi-key text-amber-600"></i>
                        <span>
                            <strong>Employee Credentials:</strong> Assigned employees can log into the portal using 
                            <code>[FirstName] [EmployeeCode]</code> (e.g. <code>BERNARD SBP8637</code>) or their Employee Code (e.g. <code>SBP8637</code>) with password <code>password</code> (case-insensitive).
                        </span>
                    </div>
                </div>

                <!-- Modal Footer (Fixed / Sticky / Never Hidden) -->
                <div class="p-4 md:p-5 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <button @click="showAssignModal = false"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-black text-slate-500 hover:bg-slate-100 transition-all uppercase tracking-wider cursor-pointer">
                        Cancel
                    </button>
                    <button @click="submitAssignGoal" :disabled="assigning"
                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2 cursor-pointer">
                        <i v-if="assigning" class="pi pi-spin pi-spinner"></i>
                        <i v-else class="pi pi-check"></i>
                        <span>{{ assigning ? 'Assigning & Provisioning...' : 'Assign & Provide Access' }}</span>
                    </button>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="showDetailModal" :modal="true" :showHeader="false" 
            class="!p-0 overflow-hidden shadow-2xl border-none" 
            :style="{ width: '100vw', height: '100vh', maxWidth: '100vw', maxHeight: '100vh', margin: '0' }"
            :contentStyle="{ padding: '0', backgroundColor: isDark ? '#0f172a' : '#F1F5F9', display: 'flex' }">
            
            <div v-if="selectedGoal" class="flex w-full h-full overflow-hidden">
                
                <!-- Document Viewport (Left/Center) -->
                <div class="flex-1 overflow-y-auto bg-slate-200 p-8 md:p-12 lg:p-20 flex flex-col items-center custom-scrollbar scroll-smooth">
                    
                    <div id="protocol-report" class="w-full max-w-[210mm] print:m-0 print:shadow-none print:w-full no-scrollbar">
                        <PrintableHardCopyDossier :goal="selectedGoal" :employees="masterEmployees" :showReviewPage="isManager || isGoalReviewCompleted(selectedGoal)" />
                    </div>
                </div>

                <!-- Right Sidebar (Controls) -->
                <div class="w-[380px] bg-slate-900 flex flex-col no-print shrink-0 border-l border-white/5">
                    
                    <!-- Close Button -->
                    <div class="p-4 flex justify-end">
                        <button @click="showDetailModal = false" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 text-white flex items-center justify-center transition-all cursor-pointer border-none">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <div class="p-10 flex-1 overflow-y-auto no-scrollbar">
                        
                        <!-- Visual Header -->
                        <div class="mb-10 text-center">
                            <div class="inline-flex w-24 h-24 rounded-3xl bg-red-500/10 text-red-500 items-center justify-center mb-6 shadow-2xl shadow-red-500/20 border border-red-500/20">
                                <i class="pi pi-file-pdf text-4xl"></i>
                            </div>
                            <h2 class="text-xl font-black text-white uppercase tracking-tight mb-2">{{ isManager ? 'Goal, Appraisal & Review Dossier' : 'Goal & Appraisal Dossier' }}</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ getDossierId(selectedGoal) }} // VERIFIED DOCUMENT</p>
                        </div>

                        <!-- Brief Description -->
                        <div class="mb-10 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-1 h-4 bg-indigo-500"></div>
                                <h3 class="text-[10px] font-black text-white uppercase tracking-widest">Quick Brief</h3>
                            </div>
                            <div class="p-5 bg-white/5 rounded-2xl border border-white/5 text-[11px] font-medium text-slate-400 leading-relaxed italic">
                                "{{ selectedGoal.title }}: A strategic goal focused on {{ selectedGoal.category.toLowerCase() }} optimization for the {{ selectedGoal.year }} PMS cycle."
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="grid grid-cols-2 gap-4 mb-10">
                            <div class="p-4 bg-white/5 rounded-xl border border-white/5">
                                <span class="text-[8px] font-black text-slate-500 uppercase block mb-1">Status</span>
                                <span class="text-[10px] font-black text-indigo-400 uppercase">{{ selectedGoal.status }}</span>
                            </div>
                            <div class="p-4 bg-white/5 rounded-xl border border-white/5">
                                <span class="text-[8px] font-black text-slate-500 uppercase block mb-1">Target</span>
                                <span class="text-[10px] font-black text-white uppercase">{{ selectedGoal.target }}%</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <button @click="downloadPDF" 
                                class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-xl shadow-indigo-600/20 border-none cursor-pointer">
                                <i class="pi pi-download"></i> Download PDF
                            </button>
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="downloadSingleCSV(selectedGoal)" 
                                    class="py-4 bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 rounded-2xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer">
                                    <i class="pi pi-file-excel"></i> CSV
                                </button>
                                <button @click="triggerPrint" 
                                    class="py-4 bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 rounded-2xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer">
                                    <i class="pi pi-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="p-10 border-t border-white/5">
                        <div class="flex items-center gap-4">
                            <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(selectedGoal.candidate_name)+'&background=fff&color=1e293b&size=64'" class="w-10 h-10 rounded-xl" />
                            <div>
                                <p class="text-[10px] font-black text-white uppercase tracking-tight">{{ selectedGoal.candidate_name }}</p>
                                <p class="text-[8px] font-bold text-slate-500 uppercase uppercase">{{ selectedGoal.employee_code }}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.watermark-logo {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 280px;
    height: auto;
    opacity: 0.04;
    pointer-events: none;
    z-index: 0;
    user-select: none;
}
.doc-page > *:not(.watermark-logo) {
    position: relative;
    z-index: 1;
}
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
.animate-pulse-subtle {
    animation: pulse-subtle 2s infinite ease-in-out;
}
.font-segoe {
    font-family: 'Inter', sans-serif;
}

@media print {
    /* Hide sidebar, navigation, and non-report elements */
    .no-print,
    nav, header, footer, aside,
    .p-dialog-mask::before {
        display: none !important;
    }

    body, html {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        overflow: visible !important;
    }

    /* Make all ancestors of the report visible and unstyled */
    .p-dialog-mask,
    .p-dialog,
    .p-dialog-content {
        position: static !important;
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
        display: block !important;
    }

    #protocol-report {
        position: static !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
        overflow: visible !important;
    }

    /* Hide the scrollable wrapper's chrome, make it flow */
    #protocol-report > * {
        visibility: visible !important;
    }

    .doc-page {
        margin: 0 !important;
        padding: 10mm !important;
        width: 100% !important;
        min-height: auto !important;
        max-height: none !important;
        height: auto !important;
        page-break-after: always !important;
        break-after: page !important;
        box-shadow: none !important;
        border: none !important;
        display: block !important;
        overflow: visible !important;
        border-radius: 0 !important;
    }

    .doc-page:last-child {
        page-break-after: avoid !important;
    }

    /* Avoid breaking inside rows/blocks */
    table { page-break-inside: auto !important; }
    tr { page-break-inside: avoid !important; break-inside: avoid !important; }
    thead { display: table-header-group !important; }
    .space-y-4 > *, .space-y-6 > *, .space-y-8 > *, .space-y-10 > * {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    .grid {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    .mt-auto { margin-top: 10mm !important; }

    @page {
        size: A4 portrait;
        margin: 8mm 5mm;
    }
}
</style>
