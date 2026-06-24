<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, showConfirm } from '@/helpers/essential';
import AutoComplete from 'primevue/autocomplete';
import { finddept, findbranch } from '@/data/masterdata';

const userstore = useUsersStore();
const { loguser } = userstore;
const router = useRouter();

const goals = ref([]);
const loading = ref(true);
const saving = ref(false);
const savingDraft = ref(false);
const draftId = ref(null); // Track if editing an existing draft
const lastDraftSave = ref(null); // Timestamp of last auto-save
const showDetailModal = ref(false);
const selectedGoal = ref(null);
const currentYear = ref(new Date().getFullYear());
const years = range(currentYear.value, currentYear.value - 5);
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });

// Multi-step form state
const viewMode = ref('list'); // 'list' or 'create'
const currentStep = ref(1);
const totalSteps = 2;
const stepTransition = ref('slide-next');

// Master Employee Data for Dropdown
const masterEmployees = ref([]);
const filteredMasterEmployees = ref([]);
const selectedCandidate = ref(null); // For AutoComplete v-model

function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

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
            title: 'Performance', 
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
                "b) Google scores â€“ Improvement over last year's Shop Google or overall Melcom Google score."
            ], 
            weight: 30, 
            selfRating: 0, 
            managerRating: 0 
        },
        { 
            id: 3, 
            title: 'Execution / Sales Results Driven', 
            descriptions: [
                'a) Business Driven Metric (Set by Department with Management input)', 
                'b) Loss to company % (Factors and calculations must be provided), where application and relevant'
            ], 
            weight: 30, 
            selfRating: 0, 
            managerRating: 0 
        },
        { 
            id: 4, 
            title: 'Continuous Improvement in workflows/processes', 
            descriptions: [
                'a) Culture of adaptability and innovation among staff, such as inventory management, employee training', 
                'b) Adaptability / Flexibility',
                'c) Compliance :- % implementation of Wooqer checklist for the shop'
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
    newGoal.value.job_title = (candidate.job_title && candidate.job_title !== 'N/A')
        ? candidate.job_title
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

const cancelCreate = () => {
    clearDraft();
    viewMode.value = 'list';
    currentStep.value = 1;
    resetForm();
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
        // Check if any quarterly measures are empty
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
            user_id: parseInt(loguser.id),
            candidate_name: newGoal.value.candidate_name,
            employee_code: newGoal.value.employee_code,
            location: newGoal.value.location,
            department: newGoal.value.department,
            manager_name: loguser.name || '',
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

const getSmartScore = (goal) => {
    if (!goal.smart_criteria) return 0;
    const criteria = goal.smart_criteria;
    return Object.values(criteria).filter(v => v === true).length;
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
    window.print();
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
    
    // Define elaborate horizontal headers
    const headers = [
        'Record ID', 'Candidate Name', 'Employee Code', 'Department', 'Location', 'Year',
        'Goal Title', 'Category', 'Target %', 'Status',
        'Q1 Target', 'Q2 Target', 'Q3 Target', 'Q4 Target',
        'Strategic Objectives', 'Appraisal Rating', 'HOD Approval', 'Director Approval'
    ];

    // Flatten Objectives
    const objectives = parseList(goal.description).join('; ');

    // Map Quarterly Data
    const qData = [1, 2, 3, 4].map(idx => {
        const q = goal.quarterly_tracking?.find(qt => qt.quarter === `q${idx}`) || 
                  goal.quarterly_tracking?.[idx-1];
        return q ? parseList(q.target_measures).join('; ') : 'N/A';
    });

    // Final Data Row
    const row = [
        recordId,
        goal.candidate_name,
        goal.employee_code,
        goal.department || 'N/A',
        goal.location || 'N/A',
        goal.year,
        goal.title,
        goal.category || 'General',
        `${goal.target}%`,
        goal.status,
        ...qData,
        objectives,
        goal.appraisal_data?.performanceRating || 'Pending',
        goal.appraisal_data?.hod_signature_name || 'Pending',
        goal.appraisal_data?.director_signature_name || 'Pending'
    ];

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
          filename:     `Protocol_${selectedGoal.value.id}.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true, logging: false },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
          pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
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
    if (!data.smart_criteria) data.smart_criteria = defaultSmartCriteria();
    if (!data.quarterly_tracking) data.quarterly_tracking = defaultQuarterlyTracking();
    if (!data.appraisal_data) {
        data.appraisal_data = defaultAppraisalData();
    } else if (!data.appraisal_data.rating_comments) {
        data.appraisal_data.rating_comments = { 1: '', 2: '', 3: '', 4: '', 5: '' };
    }
    if (!Array.isArray(data.description) || !data.description.length) data.description = [''];
    if (!Array.isArray(data.purposes) || !data.purposes.length) data.purposes = [''];
    if (!Array.isArray(data.challenges) || !data.challenges.length) data.challenges = [''];

    Object.assign(newGoal.value, data);
    currentStep.value = 1;
    viewMode.value = 'create';
};

const editGoal = (goal) => {
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
});

// â”€â”€â”€ Filter State â”€â”€â”€
const filterStatus = ref('all');
const searchQuery = ref('');

// Stats Computed - uses display_status from backend
const stats = computed(() => {
    return {
        total: goals.value.length,
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

</script>

<template>
    <div class="h-full pb-6">
        <!-- LIST VIEW -->
        <template v-if="viewMode === 'list'">
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
                        <!-- <div class="px-4 py-1.5 bg-white rounded-xl border border-gray-100 text-[10px] font-black text-gray-500 uppercase tracking-widest shadow-sm">
                            FY {{ currentYear }} <i class="pi pi-chevron-down ml-2 text-[8px]"></i>
                        </div> -->
                        <button @click="startCreateGoal" class="prof-button !bg-[#334155] px-6 py-2.5 flex items-center gap-2 group border-none shadow-lg text-sm">
                            <i class="pi pi-plus font-black text-xs"></i>
                            <span class="font-black tracking-tight text-xs">NEW SMART GOAL</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Stats & Table -->
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Goals -->
                <div class="prof-card p-4 !bg-[#5830E0] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden">
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

                <!-- Goal Created -->
                <div class="prof-card p-4 !bg-[#1E40AF] text-white border-none shadow-lg !rounded-2xl relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform" @click="filterStatus = 'goal_created'">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="pi pi-file-plus text-xs"></i>
                            <span class="text-[9px] font-black uppercase tracking-normal text-blue-100">Goal Created</span>
                        </div>
                        <div class="text-2xl font-black text-white mb-0.5">{{ stats.goalCreated }}</div>
                        <div class="text-[9px] font-bold text-blue-200 uppercase tracking-normal">Awaiting Appraisal</div>
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
                        <div class="text-[9px] font-bold text-teal-200 uppercase tracking-normal">Awaiting Review</div>
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

            <!-- Goals List Section -->
            <div class="prof-card mb-6 !rounded-2xl">
                 <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/30">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                                Active SMART Goals
                                <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black border border-indigo-100">{{ filteredGoals.length }}</span>
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-normal font-bold">Manage and track your performance objectives.</p>
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
                            <button @click="filterStatus = 'goal_created'" 
                                :class="filterStatus === 'goal_created' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-gray-100 hover:text-blue-600'" 
                                class="px-4 py-2 rounded-lg text-xs font-black transition-all uppercase tracking-normal">
                                Goal Created
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
                    <h3 class="text-xl font-bold text-gray-800 mb-2">No SMART Goals Found</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">Get started by creating your first SMART goal for this year. Set clear objectives to drive your success.</p>
                    <button @click="startCreateGoal" class="px-6 py-3 bg-[#1A237E] hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg shadow-purple-200 transition-all transform hover:-translate-y-1 flex items-center gap-2 mx-auto">
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
                                    <span v-if="goal.display_status === 'draft'" class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest border border-amber-100 shadow-sm">
                                        <i class="pi pi-file-edit mr-1.5"></i> Draft
                                    </span>
                                    <span v-else-if="goal.display_status === 'goal_created'" class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-widest border border-blue-200 shadow-sm">
                                        <i class="pi pi-file-plus mr-1.5 text-xs"></i> Goal Created
                                    </span>
                                    <span v-else-if="goal.display_status === 'appraisal_completed'" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-700 text-[10px] font-black uppercase tracking-widest border border-teal-200 shadow-sm">
                                        <i class="pi pi-check-circle mr-1.5 text-xs"></i> Appraisal Meeting Completed
                                    </span>
                                    <span v-else-if="goal.display_status === 'review_completed'" class="px-3 py-1.5 rounded-xl bg-green-50 text-green-700 text-[10px] font-black uppercase tracking-widest border border-green-200 shadow-sm">
                                        <i class="pi pi-verified mr-1.5 text-xs"></i> Review Completed
                                    </span>
                                    <span v-else class="px-3 py-1.5 rounded-xl bg-gray-50 text-gray-500 text-[10px] font-black uppercase tracking-widest border border-gray-100 shadow-sm">
                                        <i class="pi pi-minus mr-1.5 text-xs"></i> Unknown
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center font-black text-[11px]">
                                    <span v-if="goal.appraisal_data?.authorization?.line_manager_rating" class="px-2 py-1 bg-slate-100 text-slate-700 rounded border border-slate-200" :title="goal.appraisal_data.authorization.line_manager_rating">
                                        {{ goal.appraisal_data.authorization.line_manager_rating.split(' ')[0] }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Button (for drafts) -->
                                        <button v-if="goal.status === 'draft'" @click="editGoal(goal)" 
                                            class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-lg border border-blue-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm">
                                            <i class="pi pi-pencil text-[9px]"></i> Edit
                                        </button>
                                        
                                        <!-- Appraise Button (for in-progress) -->
                                        <button v-if="goal.status === 'in_progress'" @click="router.push({ name: 'pms-appraisal', query: { goal_id: goal.id } })" 
                                            class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-lg border border-indigo-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm">
                                            <i class="pi pi-star-fill text-[9px]"></i> Appraise
                                        </button>
                                        
                                        <!-- Preview Button -->
                                        <button @click="selectedGoal = goal; showDetailModal = true" 
                                            class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 text-slate-700 hover:bg-slate-800 hover:text-white rounded-lg border border-slate-200 transition-all text-[10px] font-black uppercase tracking-tight shadow-sm">
                                            <i class="pi pi-eye text-[9px]"></i> View
                                        </button>

                                        <!-- Delete Button -->
                                        <button @click="deleteGoal(goal.id)" 
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
                                    <span>{{ currentStep === 1 ? 'YEARLY SMART GOALS SETTING' : 'GOAL START DATE - KEY STEPS' }}</span>
                                    <div v-if="currentStep === 2" class="flex items-center gap-2 px-3 py-1 bg-white/10 rounded-xl border border-white/20 backdrop-blur-sm">
                                        <i class="pi pi-user text-indigo-200 text-xs"></i>
                                        <span class="text-sm font-bold tracking-normal normal-case text-indigo-100">{{ newGoal.candidate_name || 'Pending Candidate' }}</span>
                                    </div>
                                </h1>
                                <p class="text-indigo-200 text-[10px] font-black mt-1 tracking-normal uppercase opacity-80">
                                    <template v-if="currentStep === 1">Step 1: GOAL DEFINITION</template>
                                    <template v-else>MEASURE (Growth Over Last Year & Quarters) — Keep a log of your progress.</template>
                                    <span class="ml-2 px-2 py-0.5 rounded bg-white/15 text-white text-[10px] font-bold border border-white/10">FY {{ currentYear }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Stepper Pills -->
                        <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl backdrop-blur-sm border border-white/10">
                            <div class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-500" :class="currentStep === 1 ? 'bg-white text-[#1A237E] shadow-lg' : 'text-white/60'">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs" :class="currentStep >= 1 ? (currentStep === 1 ? 'bg-[#1A237E] text-white' : 'bg-green-500 text-white') : 'bg-white/20'">
                                    <span v-if="currentStep > 1">âœ“</span><span v-else>1</span>
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Definition</span>
                            </div>
                            <div class="w-6 h-[2px] rounded-full" :class="currentStep >= 2 ? 'bg-white/50' : 'bg-white/10'"></div>
                            <div class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-500" :class="currentStep === 2 ? 'bg-white text-[#1A237E] shadow-lg' : 'text-white/60'">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs" :class="currentStep >= 2 ? (currentStep === 2 ? 'bg-[#1A237E] text-white' : 'bg-green-500 text-white') : 'bg-white/20'">
                                    2
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Tracking</span>
                            </div>
                        </div>
                    </div>
                </div>
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
                                    <p class="text-[9px] text-[#3949AB] font-black uppercase tracking-normal opacity-70">Job Title & Signature</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <AutoComplete
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
                                <span class="px-3 py-1 bg-[#F1F5F9] rounded text-[10px] font-semibold text-gray-500 border border-gray-100">Dept: {{ newGoal.department || 'N/A' }}</span>
                                <span class="px-3 py-1 bg-[#F1F5F9] rounded text-[10px] font-semibold text-gray-500 border border-gray-100">Loc: {{ newGoal.location || 'N/A' }}</span>
                                <span class="px-3 py-1 bg-indigo-50 rounded text-[10px] font-black text-indigo-700 border border-indigo-100">Job Title: {{ newGoal.job_title || 'N/A' }}</span>
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
                                    <input v-model="newGoal.title" type="text" 
                                        class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-3 px-4 text-sm font-semibold text-gray-800 focus:bg-white focus:border-[#1A237E] focus:ring-2 focus:ring-[#1A237E]/10 outline-none transition-all" 
                                        placeholder="E.g. Maximize operational efficiencies..." />
                                </div>
                                
                                <div class="space-y-3 pt-1">
                                    <label class="block text-xs font-bold text-[#3949AB] uppercase tracking-wide">Goals Description</label>
                                    <div v-for="(desc, index) in newGoal.description" :key="index" class="flex gap-3 group/item">
                                        <div class="w-8 h-8 rounded-lg bg-[#E8EAF6] flex items-center justify-center text-[#1A237E] font-bold text-xs border border-[#C5CAE9] shrink-0 mt-1">{{ index + 1 }}</div>
                                        <textarea v-model="newGoal.description[index]" rows="2" 
                                            class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 focus:border-[#1A237E] focus:ring-2 focus:ring-[#1A237E]/10 outline-none resize-none transition-all" 
                                            placeholder="Define a measurable performance indicator..."></textarea>
                                        <button @click="removeDescription(index)" v-if="newGoal.description.length > 1" 
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/item:opacity-100 shrink-0 mt-1" title="Remove">
                                            <i class="pi pi-times text-xs"></i>
                                        </button>
                                    </div>
                                    <button @click="addDescription" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#1A237E] bg-[#E8EAF6] hover:bg-[#C5CAE9] rounded-lg transition-all active:scale-95 border border-[#C5CAE9]">
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
                                    <textarea v-model="newGoal.purposes[index]" rows="2" 
                                        class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 focus:border-[#00695C] focus:ring-2 focus:ring-[#00695C]/10 outline-none resize-none transition-all" 
                                        placeholder="Establish the business relevance..."></textarea>
                                    <button @click="removePurpose(index)" v-if="newGoal.purposes.length > 1" 
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/item:opacity-100 shrink-0 mt-1" title="Remove">
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </div>
                                <button @click="addPurpose" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#004D40] bg-[#E0F2F1] hover:bg-[#B2DFDB] rounded-lg transition-all active:scale-95 border border-[#B2DFDB]">
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
                                    <textarea v-model="newGoal.challenges[index]" rows="2" 
                                        class="flex-1 bg-white border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-medium text-gray-700 focus:border-[#E65100] focus:ring-2 focus:ring-[#E65100]/10 outline-none resize-none transition-all" 
                                        placeholder="Anticipate potential roadblocks..."></textarea>
                                    <button @click="removeChallenge(index)" v-if="newGoal.challenges.length > 1" 
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/item:opacity-100 shrink-0 mt-1" title="Remove">
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </div>
                                <button @click="addChallenge" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#E65100] bg-[#FFF3E0] hover:bg-[#FFE0B2] rounded-lg transition-all active:scale-95 border border-[#FFE0B2]">
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
                                        <select v-model="newGoal.category" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-700 focus:border-[#37474F] focus:ring-2 focus:ring-[#37474F]/10 outline-none transition-all">
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
                                        <input v-model="newGoal.target" type="number" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-700 focus:border-[#37474F] focus:ring-2 focus:ring-[#37474F]/10 outline-none transition-all" />
                                    </div>
                                    <!-- Completion Date -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-[#37474F] uppercase tracking-wide">Date To be Completed</label>
                                        <input v-model="newGoal.completion_date" type="date" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2.5 px-3 text-sm font-semibold text-gray-700 focus:border-[#37474F] focus:ring-2 focus:ring-[#37474F]/10 outline-none transition-all" />
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
                                    <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-wider">Check (âœ“)</span>
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
                                            <input type="checkbox" v-model="newGoal.smart_criteria[criteria.key]" 
                                                class="w-5 h-5 rounded-2xl border-2 border-gray-100 text-[#1A237E] focus:ring-[#1A237E] focus:ring-2 cursor-pointer accent-[#1A237E]" />
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
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[9px] font-bold text-[#1A237E] uppercase tracking-wider mb-1 block">Start Date</label>
                                    <input type="date" v-model="quarter.start_date" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-lg py-2 px-2 text-[11px] font-semibold text-gray-700 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/10 outline-none transition-all" />
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-[#E65100] uppercase tracking-wider mb-1 block">End Date</label>
                                    <input type="date" v-model="quarter.end_date" class="w-full bg-[#FFF3E0] border border-[#FFE0B2] rounded-lg py-2 px-2 text-[11px] font-semibold text-gray-700 focus:border-[#E65100] focus:ring-1 focus:ring-[#E65100]/10 outline-none transition-all" />
                                </div>
                            </div>
                            
                            <!-- Target Measure -->
                            <div class="relative flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block">Target Measure</label>
                                    <button @click="addExecutionTarget(index)" class="flex items-center gap-1 px-2 py-1 bg-[#E8EAF6] hover:bg-[#1A237E] hover:text-white rounded-lg transition-colors text-[#1A237E]">
                                        <i class="pi pi-plus text-[8px]"></i>
                                        <span class="text-[8px] font-bold uppercase tracking-wider">Add</span>
                                    </button>
                                </div>
                                <div class="space-y-2 mb-3">
                                    <div v-for="(measure, mIndex) in quarter.target_measures" :key="'m'+mIndex" class="flex items-start gap-2 group/measure">
                                        <textarea v-model="quarter.target_measures[mIndex]" rows="2" class="flex-1 bg-[#F8FAFC] border border-gray-200 rounded-lg py-2 px-3 text-sm font-medium text-gray-700 focus:border-[#1A237E] focus:ring-1 focus:ring-[#1A237E]/10 outline-none resize-none transition-all" placeholder="Target measure..."></textarea>
                                        <button @click="removeExecutionTarget(index, mIndex)" v-if="quarter.target_measures.length > 1" class="w-7 h-7 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all opacity-0 group-hover/measure:opacity-100 shrink-0 mt-1"><i class="pi pi-times text-[10px]"></i></button>
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
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <div class="w-7 h-7 rounded-lg bg-[#E8EAF6] text-[#1A237E] flex items-center justify-center">
                                                <i class="pi pi-file text-xs"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <p class="truncate max-w-[80px] text-[10px] font-bold text-gray-700">{{ file.file_name || file.name }}</p>
                                            </div>
                                        </div>
                                        <button @click="removeAttachment(index, fIndex)" class="w-5 h-5 rounded-full flex items-center justify-center text-gray-300 hover:text-white hover:bg-red-500 transition-all">
                                            <i class="pi pi-times text-[7px]"></i>
                                        </button>
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
 
                                <input type="file" multiple @change="handleFileUpload($event, index)" class="hidden" :id="'file-upload-'+index">
                                <label :for="'file-upload-'+index" class="w-full flex items-center justify-center gap-2 py-2 bg-[#E8EAF6] hover:bg-[#1A237E] hover:text-white rounded-lg cursor-pointer transition-all text-black text-xs font-bold uppercase tracking-wider">
                                    <i class="pi pi-plus text-[10px]"></i>
                                    Add Evidence
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: HOD & Director Review Section -->
                <div class="mt-8 space-y-6">
                    <!-- HOD Section -->
                    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="bg-[#F1F5F9] px-6 py-4 border-b border-gray-200">
                            <h4 class="font-black text-sm text-[#1A237E] uppercase tracking-wider">HOD / Functional Head Review</h4>
                        </div>
                        <div class="p-0 divide-y divide-gray-100">
                            <!-- Comments Row -->
                            <div class="grid grid-cols-1 md:grid-cols-4 items-stretch">
                                <div class="bg-[#F8FAFC] p-4 flex items-center border-r border-gray-100">
                                    <label class="text-[10px] font-black text-[#64748B] uppercase tracking-widest leading-tight">HOD/Functional Head's<br>comments:</label>
                                </div>
                                <div class="md:col-span-3 p-4">
                                    <textarea v-model="newGoal.appraisal_data.hod_comments" rows="3" class="w-full bg-transparent border-none focus:ring-0 text-sm font-semibold text-gray-700 placeholder:text-gray-300 resize-none" placeholder="Provide HOD feedback here..."></textarea>
                                </div>
                            </div>
                            <!-- Signature Row -->
                            <div class="grid grid-cols-1 md:grid-cols-4 items-stretch">
                                <div class="bg-[#F8FAFC] p-4 flex items-center border-r border-gray-100">
                                    <label class="text-[10px] font-black text-[#64748B] uppercase tracking-widest leading-tight">HOD/Functional Head's<br>Name, Designation & Signature:</label>
                                </div>
                                <div class="md:col-span-2 p-4 border-r border-gray-100">
                                    <input v-model="newGoal.appraisal_data.hod_signature_name" type="text" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-800" placeholder="Enter Full Name & Designation" />
                                </div>
                                <div class="p-4 flex items-center gap-4">
                                    <label class="text-[10px] font-black text-[#64748B] uppercase tracking-widest">Date:</label>
                                    <input v-model="newGoal.appraisal_data.hod_signature_date" type="date" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-700" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Director Section -->
                    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden tracking-normal">
                        <div class="bg-[#F1F5F9] px-6 py-4 border-b border-gray-200">
                            <h4 class="font-black text-sm text-[#1A237E] uppercase tracking-wider">Director Review</h4>
                        </div>
                        <div class="p-0 divide-y divide-gray-100">
                            <!-- Remarks Row -->
                            <div class="grid grid-cols-1 md:grid-cols-4 items-stretch">
                                <div class="bg-[#F8FAFC] p-4 flex items-center border-r border-gray-100">
                                    <label class="text-[10px] font-black text-[#64748B] uppercase tracking-widest leading-tight">Director's Remarks:</label>
                                </div>
                                <div class="md:col-span-3 p-4">
                                    <textarea v-model="newGoal.appraisal_data.director_remarks" rows="3" class="w-full bg-transparent border-none focus:ring-0 text-sm font-semibold text-gray-700 placeholder:text-gray-300 resize-none" placeholder="Provide director remarks here..."></textarea>
                                </div>
                            </div>
                            <!-- Signature Row -->
                            <div class="grid grid-cols-1 md:grid-cols-4 items-stretch">
                                <div class="bg-[#F8FAFC] p-4 flex items-center border-r border-gray-100">
                                    <label class="text-[10px] font-black text-[#64748B] uppercase tracking-widest leading-tight">Director's Signature:</label>
                                </div>
                                <div class="md:col-span-2 p-4 border-r border-gray-100">
                                    <input v-model="newGoal.appraisal_data.director_signature_name" type="text" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-800" placeholder="Enter Full Name & Designation" />
                                </div>
                                <div class="p-4 flex items-center gap-4">
                                    <label class="text-[10px] font-black text-[#64748B] uppercase tracking-widest">Date:</label>
                                    <input v-model="newGoal.appraisal_data.director_signature_date" type="date" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-700" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </Transition>

            <!-- Navigation Footer -->
            <div class="mt-8 flex justify-between items-center bg-white p-6 rounded-3xl border border-gray-100 shadow-xl shadow-indigo-900/5">
                <div class="flex items-center gap-4">
                    <button v-if="currentStep > 1" @click="prevStep" 
                        class="prof-button !bg-white !text-gray-600 !border !border-gray-200 !rounded-3xl !py-4 px-10 hover:shadow-md transition-all">
                        <i class="pi pi-arrow-left mr-2 font-bold text-xs"></i> 
                        Previous Step
                    </button>
                    <button v-else @click="cancelCreate" 
                        class="prof-button !bg-white !text-gray-400 !border !border-gray-100 !rounded-3xl !py-4 px-10 hover:text-red-500 hover:shadow-sm transition-all text-xs font-black uppercase tracking-widest leading-none">
                        Cancel
                    </button>

                    <button @click="addGoal('draft')" :disabled="savingDraft"
                        class="prof-button !bg-white !text-indigo-600 !border !border-indigo-200/50 !rounded-3xl !py-4 px-10 shadow-sm hover:shadow-md transition-all">
                        <i class="pi pi-save mr-2 font-bold text-xs"></i> 
                        {{ savingDraft ? 'Saving...' : 'Save Progress' }}
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <button v-if="currentStep < totalSteps" @click="nextStep" 
                        class="prof-button !bg-indigo-600 !text-white !rounded-3xl !py-4 px-10 shadow-xl shadow-indigo-600/30 hover:scale-[1.02] active:scale-95">
                        Next Stage
                        <i class="pi pi-arrow-right ml-3 text-xs font-bold"></i>
                    </button>
                    
                    <button v-else @click="addGoal('in_progress')" :disabled="saving"
                        class="prof-button !bg-teal-600 !text-white !rounded-3xl !py-4 px-10 shadow-xl shadow-teal-600/30 hover:scale-[1.02] active:scale-95">
                        <i v-if="saving" class="pi pi-spin pi-spinner mr-3"></i>
                        <i v-else class="pi pi-check-circle mr-3 font-bold text-xs"></i>
                        {{ saving ? 'Submitting...' : 'Submit for Appraisal' }}
                    </button>
                </div>
            </div>
        </template>
        <Dialog v-model:visible="showDetailModal" :modal="true" :showHeader="false" 
            class="!p-0 overflow-hidden shadow-2xl border-none" 
            :style="{ width: '100vw', height: '100vh', maxWidth: '100vw', maxHeight: '100vh', margin: '0' }"
            :contentStyle="{ padding: '0', backgroundColor: '#F1F5F9', display: 'flex' }">
            
            <div v-if="selectedGoal" class="flex w-full h-full overflow-hidden">
                
                <!-- Document Viewport (Left/Center) -->
                <div class="flex-1 overflow-y-auto bg-slate-200 p-8 md:p-12 lg:p-20 flex flex-col items-center custom-scrollbar scroll-smooth">
                    
                    <div id="protocol-report" class="w-full max-w-[210mm] space-y-8 print:m-0 print:shadow-none print:w-full no-scrollbar">
                        
                        <!-- Page 1: Profile & Definitions -->
                        <div class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 min-h-[297mm] flex flex-col">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-12 border-b-2 border-slate-900 pb-8">
                                <div>
                                    <div class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.3em] mb-3">PMS SMART GOALS PROTOCOL</div>
                                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-tight">{{ selectedGoal.candidate_name }}</h1>
                                    <div class="flex items-center gap-3 mt-4">
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase rounded">{{ selectedGoal.employee_code || 'EMP-XXXX' }}</span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                        <span class="text-[10px] font-bold text-slate-400 capitalize">{{ selectedGoal.department || 'N/A' }} &#x2022; {{ selectedGoal.location || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fiscal Cycle</div>
                                    <div class="text-2xl font-black text-slate-900">{{ selectedGoal.year }}</div>
                                </div>
                            </div>

                            <!-- Section A: Strategic Definition -->
                            <div class="space-y-10">
                                <div>
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">01. STRATEGIC MISSION</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <h3 class="text-xs font-black text-slate-900 uppercase mb-2">Primary Goal Title</h3>
                                            <p class="text-base font-black text-indigo-700 uppercase leading-snug">{{ selectedGoal.title }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-8">
                                            <div>
                                                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-2">Category</h3>
                                                <p class="text-xs font-bold text-slate-700 uppercase">{{ selectedGoal.category || 'General' }}</p>
                                            </div>
                                            <div>
                                                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-2">Target Performance</h3>
                                                <p class="text-xs font-black text-slate-900 uppercase">{{ selectedGoal.target }}%</p>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-[10px] font-black text-slate-400 uppercase mb-3">Narrative Description & Objectives</h3>
                                            <div class="space-y-3">
                                                <div v-for="(desc, index) in parseList(selectedGoal.description)" :key="index" class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-600 leading-relaxed">
                                                    <span class="text-indigo-600 mr-2 opacity-50">{{ (index+1).toString().padStart(2, '0') }}</span> {{ desc }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">02. PURPOSE & VALUE</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-for="(p, pIdx) in parseList(selectedGoal.purposes)" :key="pIdx" class="flex gap-4 p-4 bg-white border border-slate-100 rounded-xl shadow-sm">
                                            <div class="w-2 h-2 rounded-full bg-teal-500 mt-1.5 shrink-0"></div>
                                            <p class="text-[11px] font-bold text-slate-600 leading-normal">{{ p }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 01 // Official Performance Record // RecordId: {{ getDossierId(selectedGoal) }}</p>
                            </div>
                        </div>

                        <!-- Page 2: Quarterly Progress -->
                        <div class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 min-h-[297mm] flex flex-col break-before-page">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-10 h-1 bg-indigo-600"></div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Quarterly Execution Audit</h2>
                            </div>

                            <div class="space-y-8">
                                <div v-for="(q, qIdx) in selectedGoal.quarterly_tracking" :key="qIdx" class="relative group">
                                    <div class="absolute -left-6 top-0 bottom-0 w-1 bg-slate-100 group-hover:bg-indigo-600 transition-colors"></div>
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">{{ q.quarter ? q.quarter.toUpperCase() : 'QUARTER ' + (qIdx + 1) }}</h3>
                                        <span class="text-[9px] font-black text-slate-400 uppercase">{{ formatDate(q.start_date) }} - {{ formatDate(q.end_date) }}</span>
                                    </div>
                                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
                                        <div>
                                            <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-2">Target Measures & Deliverables</label>
                                            <div class="space-y-2">
                                                <div v-for="(m, mIdx) in parseList(q.target_measures)" :key="mIdx" class="flex items-start gap-3">
                                                    <i class="pi pi-check-circle text-indigo-400 text-[10px] mt-0.5"></i>
                                                    <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ m }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 02 // Official Performance Record // RecordId: {{ getDossierId(selectedGoal) }}</p>
                            </div>
                        </div>

                        <!-- Page 3: Appraisal & Review -->
                        <div v-if="selectedGoal.appraisal_data || selectedGoal.status === 'completed' || selectedGoal.status === 'in_progress'" 
                            class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 min-h-[297mm] flex flex-col break-before-page">
                            
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-10 h-1 bg-indigo-600"></div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Appraisal Matrix & Final Review</h2>
                            </div>

                            <div class="space-y-12">
                                <!-- Competency Matrix -->
                                <div v-if="selectedGoal.appraisal_data && selectedGoal.appraisal_data.competencies">
                                    <table class="w-full text-left font-black text-xs border-collapse">
                                        <thead class="bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest">
                                            <tr>
                                                <th class="px-6 py-4 rounded-tl-xl truncate">Assessment Unit</th>
                                                <th class="px-6 py-4 text-center">Weight</th>
                                                <th class="px-6 py-4 text-center">Self</th>
                                                <th class="px-6 py-4 text-center rounded-tr-xl">Mgr</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white border-x border-b border-slate-100 rounded-b-xl overflow-hidden">
                                            <tr v-for="(comp, cIdx) in selectedGoal.appraisal_data.competencies" :key="cIdx">
                                                <td class="px-6 py-5 font-black text-slate-800 text-[11px]">{{ comp.title }}</td>
                                                <td class="px-6 py-5 text-center font-bold text-slate-400">{{ comp.weight }}%</td>
                                                <td class="px-6 py-5 text-center font-bold text-slate-400">{{ comp.selfRating || '0' }}</td>
                                                <td class="px-6 py-5 text-center font-black text-indigo-600">{{ comp.managerRating || '0' }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-indigo-50/50">
                                            <tr>
                                                <td class="px-6 py-4 font-black text-indigo-900 uppercase text-[10px]">Overall Achievement Score</td>
                                                <td colspan="2"></td>
                                                <td class="px-6 py-4 text-center text-lg font-black text-indigo-600">{{ selectedGoal.appraisal_data.performanceRating || '0.00' }}/5</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Approvals Row -->
                                <div class="grid grid-cols-2 gap-12 pt-12 border-t border-slate-100">
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4 tracking-widest">HOD / Functional Head Approval</h4>
                                            <div class="h-20 border-b-2 border-slate-900 mt-2 mb-2 italic text-slate-400 text-[10px] flex items-end pb-2">
                                                {{ selectedGoal.appraisal_data?.hod_signature_name || 'Signature pending' }}
                                            </div>
                                            <p class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ selectedGoal.appraisal_data?.hod_signature_name || 'Designee' }}</p>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(selectedGoal.appraisal_data?.hod_signature_date) || 'Date' }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4 tracking-widest">Director / Management Approval</h4>
                                            <div class="h-20 border-b-2 border-slate-900 mt-2 mb-2 italic text-slate-400 text-[10px] flex items-end pb-2">
                                                {{ selectedGoal.appraisal_data?.director_signature_name || 'Signature pending' }}
                                            </div>
                                            <p class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ selectedGoal.appraisal_data?.director_signature_name || 'Director' }}</p>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(selectedGoal.appraisal_data?.director_signature_date) || 'Date' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 03 // Official Performance Record // RecordId: {{ getDossierId(selectedGoal) }}</p>
                            </div>
                        </div>

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
                            <h2 class="text-xl font-black text-white uppercase tracking-tight mb-2">Goal Dossier</h2>
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
    /* Hide everything except the report */
    :not(#protocol-report):not(#protocol-report *) {
        visibility: hidden;
    }
    
    body, html {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
    }

    #protocol-report {
        visibility: visible !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
    }

    .doc-page {
        margin: 0 !important;
        padding: 20mm !important;
        width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        page-break-after: always !important;
        break-after: page !important;
        box-shadow: none !important;
        border: none !important;
        visibility: visible !important;
        display: flex !important;
        flex-direction: column !important;
    }

    /* Avoid breaking logic within rows */
    tr, p, div {
        page-break-inside: avoid !important;
    }
}
</style>
