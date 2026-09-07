<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, showConfirm } from '@/helpers/essential';
import { toast } from 'vue3-toastify';
import AutoComplete from 'primevue/autocomplete';

const userstore = useUsersStore();
const { loguser } = userstore;

const route = useRoute();
const router = useRouter();

function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

const currentYear = ref(new Date().getFullYear());
const years = range(currentYear.value, currentYear.value - 5);
const loading = ref(true);
const saving = ref(false);
const currentStep = ref(route.query.step ? parseInt(route.query.step) : 1);
const totalSteps = 2;
const goalId = ref(route.query.goal_id || null);
const goalStatus = ref('');

const isManager = computed(() => {
    return !!(loguser?.admin || loguser?.is_manager || loguser?.position_id === 1 || loguser?.designation === 'Manager');
});
const isEmployee = computed(() => !isManager.value);

// Read-only when appraisal is submitted (completed/submitted) and review is done
const isReadOnly = computed(() => {
    return ['review_completed', 'completed', 'submitted'].includes(goalStatus.value);
});

// Master Employee Data for Dropdown (for managers starting new appraisals)
const masterEmployees = ref([]);
const filteredMasterEmployees = ref([]);
const selectedCandidate = ref(null);
watch(loading, (val) => userstore?.setIsLoading?.(val), { immediate: true });

// Performance Key Competencies (5 items, default 20% weight each = 100%)
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

const weightOptions = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80];

const isEditingWeights = ref(false);

const isWeightDisabled = computed(() => {
    if (isReadOnly.value) return true;
    return !isEditingWeights.value;
});

const onWeightChange = () => {
    if (totalWeight.value === 100) {
        isEditingWeights.value = false;
        toast.success('Total weight is 100% and now locked.', { autoClose: 2000 });
    }
};

const resetWeightsToDefault = () => {
    performanceCompetencies.value.forEach(comp => {
        comp.weight = 20;
    });
    isEditingWeights.value = false;
    toast.info('Weights reset to 20% each (Total: 100%).', { autoClose: 2000 });
};

const performanceCompetencies = ref(JSON.parse(JSON.stringify(defaultCompetencyList)));

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
    rating_comments: { 1: '', 2: '', 3: '', 4: '', 5: '' },
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

const onSelectRating = (val) => {
    if (isReadOnly.value) return;
    appraisal.value.performanceRating = val;
    if (!appraisal.value.rating_comments) {
        appraisal.value.rating_comments = { 1: '', 2: '', 3: '', 4: '', 5: '' };
    }
    appraisal.value.performanceComments = appraisal.value.rating_comments[val] || '';
};

// Computed
const totalWeight = computed(() => {
    return performanceCompetencies.value.reduce((sum, c) => sum + (parseFloat(c.weight) || 0), 0);
});

const overallPerformanceRating = computed(() => {
    const comps = performanceCompetencies.value;
    if (!comps || comps.length === 0) return (0.00).toFixed(2);
    // Weighted score sum: sum of (rating * weight) / 100
    const hasManager = comps.some(c => parseFloat(c.managerRating) > 0);
    const total = comps.reduce((sum, c) => {
        const rating = hasManager ? (parseFloat(c.managerRating) || 0) : (parseFloat(c.selfRating) || 0);
        return sum + ((rating * (parseFloat(c.weight) || 0)) / 100);
    }, 0);
    return total.toFixed(2);
});

const overallPercentage = computed(() => {
    const score = parseFloat(overallPerformanceRating.value) || 0;
    const pct = (score / 5) * 100;
    return pct % 1 === 0 ? pct.toFixed(0) : pct.toFixed(1);
});

const canProceedToStep2 = computed(() => {
    return performanceCompetencies.value.every(comp => comp.selfRating > 0) && totalWeight.value === 100;
});

// Methods
const nextStep = async () => {
    if (isReadOnly.value) {
        if (currentStep.value < totalSteps) currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    
    if (totalWeight.value !== 100) {
        showAlert('Weight Validation', `The total weight of all competencies must sum up to exactly 100%. Currently it is ${totalWeight.value}%. Please adjust the weights in the dropdowns.`, 'warning');
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
    return ((rating * (parseFloat(comp.weight) || 0)) / 100).toFixed(2);
};

const populateFromGoalAndAppraisal = (goal, appraisalData) => {
    if (goal) {
        goalId.value = goal.id;
        goalStatus.value = goal.status || '';
        appraisal.value.candidate_name = goal.candidate_name || '';
        appraisal.value.employee_code = goal.employee_code || '';
        appraisal.value.job_title = goal.job_title || goal.department || '';
        appraisal.value.department = goal.department || '';
        appraisal.value.location = goal.location || '';
        appraisal.value.manager_name = goal.manager_name || loguser.name || '';

        if (masterEmployees.value.length > 0 && !selectedCandidate.value) {
            selectedCandidate.value = masterEmployees.value.find(e => e.employee_code === goal.employee_code) || null;
        }

        if (!appraisal.value.manager_signature_name) {
            appraisal.value.manager_signature_name = appraisal.value.manager_name || loguser.name || '';
        }

        if (appraisalData) {
            const rComments = (typeof appraisalData.rating_comments === 'object' && appraisalData.rating_comments) 
                ? { ...appraisalData.rating_comments } 
                : { 1: '', 2: '', 3: '', 4: '', 5: '' };
            if (appraisalData.performanceRating && appraisalData.performanceComments && !rComments[appraisalData.performanceRating]) {
                rComments[appraisalData.performanceRating] = appraisalData.performanceComments;
            }
            appraisal.value = { 
                ...appraisal.value, 
                comments: appraisalData.comments || '',
                impressedMost: appraisalData.impressedMost || '',
                impressedLeast: appraisalData.impressedLeast || '',
                performanceRating: appraisalData.performanceRating || 0,
                performanceComments: appraisalData.performanceComments || '',
                rating_comments: rComments,
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
            
            if (appraisalData.competencies && appraisalData.competencies.length > 0) {
                const rawComps = appraisalData.competencies;
                const rawSum = rawComps.reduce((s, c) => s + (parseFloat(c.weight) || 0), 0);
                const isSum100 = Math.round(rawSum) === 100;

                performanceCompetencies.value = defaultCompetencyList.map((def, idx) => {
                    let match = rawComps.find(c => c.id === def.id) || rawComps[idx];
                    return {
                        ...def,
                        title: match?.title || def.title,
                        descriptions: match?.descriptions || def.descriptions,
                        descriptionText: match?.descriptionText || (match?.descriptions ? (Array.isArray(match.descriptions) ? match.descriptions.join('\n') : match.descriptions) : def.descriptionText),
                        selfRating: match?.selfRating || 0,
                        managerRating: match?.managerRating || 0,
                        weight: isSum100 ? (match?.weight !== undefined ? Number(match.weight) : def.weight) : 20
                    };
                });
            }
        }
    }
};

const fetchAppraisal = async () => {
    loading.value = true;
    try {
        let params = {};
        if (goalId.value) {
            params.goal_id = goalId.value;
        } else if (route.query.employee_code) {
            params.employee_code = route.query.employee_code;
            params.year = currentYear.value;
        } else if (selectedCandidate.value?.employee_code) {
            params.employee_code = selectedCandidate.value.employee_code;
            params.year = currentYear.value;
        } else {
            params.year = currentYear.value;
            params.user_id = loguser.id;
        }

        const response = await axios.get('pms/appraisals', { params });

        if (response.data.status === 'success' && response.data.data?.goal) {
            populateFromGoalAndAppraisal(response.data.data.goal, response.data.data.appraisal_data);
        } else {
            // Empty / fallback state (e.g. manager designing template or candidate hasn't submitted yet)
            goalId.value = null;
            goalStatus.value = 'draft';
            if (selectedCandidate.value) {
                appraisal.value.candidate_name = selectedCandidate.value.name;
                appraisal.value.employee_code = selectedCandidate.value.employee_code;
                appraisal.value.job_title = selectedCandidate.value.designation || selectedCandidate.value.department || '';
                appraisal.value.department = selectedCandidate.value.department || '';
                appraisal.value.location = selectedCandidate.value.location || '';
                appraisal.value.manager_name = loguser.name || '';
                appraisal.value.manager_signature_name = loguser.name || '';
            } else if (isManager.value) {
                appraisal.value.manager_name = loguser.name || '';
                appraisal.value.manager_signature_name = loguser.name || '';
            }
            
            // Fetch saved template from server tied to this Line Manager (or employee's Line Manager)
            try {
                let params = {};
                if (selectedCandidate.value?.employee_code) {
                    params.employee_code = selectedCandidate.value.employee_code;
                }
                const tplRes = await axios.get('pms/manager-template', { params });
                if (tplRes.data.status === 'success' && tplRes.data.template && Array.isArray(tplRes.data.template) && tplRes.data.template.length > 0) {
                    const rawComps = tplRes.data.template;
                    performanceCompetencies.value = defaultCompetencyList.map((def, idx) => {
                        const m = rawComps.find(c => c.id === def.id) || rawComps[idx] || def;
                        return {
                            ...def,
                            title: m.title || def.title,
                            weight: m.weight !== undefined ? Number(m.weight) : def.weight,
                            descriptions: m.descriptions || def.descriptions,
                            descriptionText: m.descriptions ? (Array.isArray(m.descriptions) ? m.descriptions.join('\n') : m.descriptions) : (m.descriptionText || def.descriptionText),
                            selfRating: 0,
                            managerRating: 0
                        };
                    });
                } else {
                    const storageKey = `pms_custom_competency_template_${loguser?.id || 'default'}`;
                    const savedTpl = localStorage.getItem(storageKey);
                    if (savedTpl) {
                        const parsed = JSON.parse(savedTpl);
                        performanceCompetencies.value = defaultCompetencyList.map((def, idx) => {
                            const m = parsed.find(c => c.id === def.id) || parsed[idx] || def;
                            return {
                                ...def,
                                title: m.title || def.title,
                                weight: m.weight !== undefined ? Number(m.weight) : def.weight,
                                descriptions: m.descriptions || def.descriptions,
                                descriptionText: m.descriptions ? (Array.isArray(m.descriptions) ? m.descriptions.join('\n') : m.descriptions) : (m.descriptionText || def.descriptionText),
                                selfRating: 0,
                                managerRating: 0
                            };
                        });
                    } else {
                        performanceCompetencies.value = JSON.parse(JSON.stringify(defaultCompetencyList));
                    }
                }
            } catch (err) {
                performanceCompetencies.value = JSON.parse(JSON.stringify(defaultCompetencyList));
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
            if (route.query.employee_code && !selectedCandidate.value) {
                selectedCandidate.value = masterEmployees.value.find(e => e.employee_code === route.query.employee_code) || null;
            }
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

const onCandidateSelect = async (event) => {
    const candidate = event.value;
    selectedCandidate.value = candidate;
    goalId.value = null;
    await fetchAppraisal();
};

const saveAppraisal = async (submit = false) => {
    if (submit) {
        if (totalWeight.value !== 100) {
            showAlert('Weight Validation', `The total weight must sum up to exactly 100% before finalizing. Currently it is ${totalWeight.value}%. Please adjust the weights in the dropdowns.`, 'warning');
            return;
        }
        const confirm = await showConfirm('Complete Assessment', 'Are you sure you want to finalize this appraisal?', 'question');
        if (!confirm.isConfirmed) return;
    }

    saving.value = true;
    try {
        const activeRating = appraisal.value.performanceRating;
        const currentComment = (appraisal.value.rating_comments && appraisal.value.rating_comments[activeRating]) 
            ? appraisal.value.rating_comments[activeRating] 
            : appraisal.value.performanceComments;

        // Build a clean appraisal_data payload (only assessment-relevant fields)
        const appraisalData = {
            comments: appraisal.value.comments,
            impressedMost: appraisal.value.impressedMost,
            impressedLeast: appraisal.value.impressedLeast,
            performanceRating: appraisal.value.performanceRating,
            performanceComments: currentComment,
            rating_comments: appraisal.value.rating_comments,
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
                descriptions: c.descriptions || (c.descriptionText ? c.descriptionText.split('\n') : [])
            })),
            overallPerformanceRating: overallPerformanceRating.value
        };

        // Cache customized template in localStorage AND backend server tied to this line manager
        if (isManager.value) {
            const storageKey = `pms_custom_competency_template_${loguser?.id || 'default'}`;
            localStorage.setItem(storageKey, JSON.stringify(appraisalData.competencies));
            try {
                await axios.post('pms/manager-template', {
                    template: appraisalData.competencies
                });
            } catch (tplErr) {
                console.error('Error persisting manager template to server:', tplErr);
            }
        }

        if (goalId.value) {
            const targetStatus = submit ? (isEmployee.value ? 'submitted' : 'completed') : 'draft';
            const payload = {
                appraisal_data: appraisalData,
                status: targetStatus
            };

            const response = await axios.patch(`pms/goals/${goalId.value}`, payload);
            if (response.data.status === 'success') {
                const successMsg = submit 
                    ? (isEmployee.value ? 'Appraisal submitted to Line Manager for review successfully!' : 'Appraisal completed successfully!')
                    : 'Appraisal progress saved successfully!';
                await showAlert('Success', successMsg, 'success');
                if (submit) {
                    router.push('/pms/goals');
                }
            }
        } else if (isManager.value) {
            toast.success('Appraisal template and competencies saved for your team successfully!', { autoClose: 3000 });
        } else {
            showAlert('Notice', 'No active goal dossier linked to save appraisal against.', 'info');
        }
    } catch (error) {
        console.error('Error saving:', error);
        showAlert('Error', 'Failed to save appraisal assessments. Please try again.', 'error');
    } finally {
        saving.value = false;
    }
};

const saveManagerTeamTemplate = async () => {
    if (totalWeight.value !== 100) {
        toast.warning(`Total weight must equal exactly 100%. Current: ${totalWeight.value}%.`, { autoClose: 3500 });
        showAlert('Weight Validation', `The total weight must equal 100% before saving template. Current: ${totalWeight.value}%.`, 'warning');
        return;
    }

    saving.value = true;
    try {
        const compsToSave = performanceCompetencies.value.map(c => ({
            id: c.id,
            title: c.title,
            weight: c.weight,
            descriptions: c.descriptions || (c.descriptionText ? c.descriptionText.split('\n') : []),
            descriptionText: c.descriptionText || (Array.isArray(c.descriptions) ? c.descriptions.join('\n') : c.descriptions)
        }));

        const storageKey = `pms_custom_competency_template_${loguser?.id || 'default'}`;
        localStorage.setItem(storageKey, JSON.stringify(compsToSave));

        await axios.post('pms/manager-template', {
            template: compsToSave
        });

        if (goalId.value) {
            await saveAppraisal(false);
        }

        toast.success('Appraisal template and competencies saved for your team!', { autoClose: 4000 });
        await showAlert('Template Saved!', 'Your appraisal template and competency criteria have been saved for your team. All assigned employees will see this template when filling their appraisal.', 'success');
    } catch (err) {
        console.error('Error saving manager template:', err);
        toast.error('Failed to save template: ' + (err.response?.data?.message || err.message), { autoClose: 4000 });
    } finally {
        saving.value = false;
    }
};

watch(() => route.query, (newQ) => {
    if (newQ.step) currentStep.value = parseInt(newQ.step);
    if (newQ.goal_id) goalId.value = newQ.goal_id;
    fetchAppraisal();
});

onMounted(async () => {
    if (route.query.step) currentStep.value = parseInt(route.query.step);
    await fetchMasterEmployees();
    await fetchAppraisal();
});
</script>

<template>
    <div class="h-full">
        <!-- Page Header with Stepper (Compact) -->
        <div class="prof-header sticky top-0 z-10 mb-4 shadow-sm">
            <div class="px-6 py-3">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50/10 flex items-center justify-center shadow-sm border border-white/20 shrink-0">
                            <i class="pi pi-file-edit text-lg text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-white tracking-normal leading-tight">Performance Assessment</h1>
                            <p class="text-indigo-200 text-[9px] font-bold uppercase tracking-wider">Step {{ currentStep }} of {{ totalSteps }}</p>
                        </div>
                    </div>

                    <!-- Manager Candidate Switcher -->
                    <div v-if="isManager" class="w-full md:w-80">
                        <AutoComplete
                            v-model="selectedCandidate"
                            :suggestions="filteredMasterEmployees"
                            @complete="searchCandidate"
                            @item-select="onCandidateSelect"
                            optionLabel="full_string"
                            placeholder="Switch candidate/employee..."
                            inputClass="!w-full !bg-white/10 !text-white !border !border-white/20 !rounded-xl !py-1.5 !px-3 !text-xs !font-bold placeholder:!text-indigo-200 focus:!bg-white focus:!text-slate-800"
                            class="w-full"
                        >
                            <template #item="slotProps">
                                <div class="flex items-center gap-2 py-1 px-1">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-black text-xs shrink-0">
                                        {{ slotProps.item.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-800 text-xs truncate">{{ slotProps.item.name }}</div>
                                        <div class="text-[10px] text-slate-400 truncate">ID: {{ slotProps.item.employee_code }} &bull; {{ slotProps.item.department }}</div>
                                    </div>
                                </div>
                            </template>
                        </AutoComplete>
                    </div>

                    <div class="flex items-center gap-4 self-end md:self-auto">
                        <!-- Step Indicators -->
                        <div class="hidden md:flex items-center gap-3">
                            <div class="flex items-center gap-2 cursor-pointer" @click="currentStep = 1">
                                <div :class="currentStep >= 1 ? 'step-active' : 'step-inactive'"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-[11px] shadow-sm border transition-all">1</div>
                                <span class="text-gray-200 font-bold text-[9px] uppercase tracking-wider">Competencies</span>
                            </div>
                            <div class="w-6 h-px bg-white/20"></div>
                            <div class="flex items-center gap-2 cursor-pointer" @click="currentStep = 2">
                                <div :class="currentStep >= 2 ? 'step-active' : 'step-inactive'"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-[11px] shadow-sm border transition-all">2</div>
                                <span class="text-gray-200 font-bold text-[9px] uppercase tracking-wider">Ratings</span>
                            </div>
                        </div>
                        <select v-model="currentYear" @change="fetchAppraisal" class="prof-input font-bold !py-1.5 !px-3 !text-xs !bg-gray-50 !rounded-lg">
                            <option v-for="year in years" :key="year" :value="year">FY {{ year }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Read-Only Banner -->
        <div v-if="isReadOnly" class="mx-4 md:mx-6 mb-4 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
            <i class="pi pi-lock text-amber-600 text-sm"></i>
            <div>
                <p class="font-bold text-amber-800 text-xs">This appraisal is locked</p>
                <p class="text-[9px] text-amber-600 font-semibold uppercase tracking-wider">The review has been completed and submitted. No further edits are allowed.</p>
            </div>
        </div>

        <fieldset :disabled="isReadOnly" :class="{ 'opacity-80': isReadOnly }" class="border-none p-0 m-0">

            <!-- STEP 1: Performance Key Competencies -->
            <div v-if="currentStep === 1" class="space-y-4 animate-fadeIn pb-6">
                <!-- 0. Employee Profile Summary (Compact) -->
                <div class="prof-card mx-4 md:mx-6 overflow-hidden bg-white shadow-md shadow-indigo-900/5 mb-4 border-t-2 border-t-indigo-600">
                    <div class="p-4 md:p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50/40 items-center">
                        <div class="space-y-2">
                            <div>
                                <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5 border-l-2 border-indigo-500 pl-1.5">Candidate Name</p>
                                <p class="text-xs font-black text-gray-800 uppercase tracking-tight">{{ appraisal.candidate_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5 border-l-2 border-slate-200 pl-1.5">Assessment Date</p>
                                <p class="text-[10px] font-bold text-slate-600 uppercase">{{ new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5 border-l-2 border-slate-200 pl-1.5">Employee ID</p>
                                <p class="text-xs font-black text-gray-700 uppercase leading-tight">{{ appraisal.employee_code || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5 border-l-2 border-slate-200 pl-1.5">Line Manager</p>
                                <p class="text-[10px] font-bold text-slate-600 uppercase">{{ appraisal.manager_name || loguser?.name || 'Manager Name' }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5 border-l-2 border-slate-200 pl-1.5">Job Description</p>
                                <p class="text-xs font-black text-gray-700 uppercase leading-tight">{{ appraisal.job_title || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5 border-l-2 border-slate-200 pl-1.5">Division / Branch</p>
                                <p class="text-[10px] font-bold text-slate-600 uppercase">{{ appraisal.department || 'N/A' }} / {{ appraisal.location || 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center items-center lg:items-end p-3.5 bg-white rounded-2xl border border-indigo-50 shadow-sm">
                            <p class="text-[8px] text-gray-400 uppercase font-black tracking-wider mb-0.5">Overall Assessment Score</p>
                            <div class="flex items-baseline justify-end gap-1.5">
                                <span class="text-3xl font-black text-indigo-600 tracking-tight">{{ overallPerformanceRating }}</span>
                                <span class="text-[10px] font-bold text-indigo-300">/ 5.00</span>
                                <span class="text-xs font-black text-indigo-600 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-lg ml-1 shadow-xs">
                                    {{ overallPercentage }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Competencies Table (Compact & 5 Items) -->
                <div class="prof-card mx-4 md:mx-6 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-black text-gray-800 text-sm md:text-base flex items-center gap-2">
                                <i class="pi pi-chart-bar text-indigo-600"></i> Performance Key Competencies
                            </h3>
                            <p class="text-gray-400 text-[9px] uppercase tracking-normal font-bold">Annual performance evaluation scale (5 Key Areas &bull; Default 20% Each)</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold">
                                <span class="text-gray-400 text-[10px] uppercase">Total Weight:</span>
                                <span :class="totalWeight === 100 ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-amber-700 bg-amber-50 border-amber-300 animate-pulse'" class="px-2.5 py-0.5 rounded-lg border text-xs font-black flex items-center gap-1 transition-all shadow-xs">
                                    <i v-if="totalWeight === 100" class="pi pi-check text-[10px]"></i>
                                    <i v-else class="pi pi-exclamation-triangle text-[10px]"></i>
                                    {{ totalWeight }}%
                                </span>
                            </div>

                            <!-- Weight adjustment controls -->
                            <div v-if="!isReadOnly" class="flex items-center gap-1.5 ml-1">
                                <button 
                                    v-if="!isEditingWeights" 
                                    type="button"
                                    @click="isEditingWeights = true" 
                                    class="px-2 py-1 rounded-lg text-[10px] font-black text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 flex items-center gap-1 transition-all cursor-pointer shadow-xs"
                                    title="Unlock dropdowns to customize weights"
                                >
                                    <i class="pi pi-sliders-h text-[9px]"></i>
                                    <span>Adjust Weights</span>
                                </button>
                                <button 
                                    v-else 
                                    type="button"
                                    @click="isEditingWeights = false" 
                                    :disabled="totalWeight !== 100"
                                    :class="totalWeight === 100 ? 'bg-emerald-600 text-white hover:bg-emerald-700 border-emerald-700 cursor-pointer' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'"
                                    class="px-2 py-1 rounded-lg text-[10px] font-black border flex items-center gap-1 transition-all shadow-xs"
                                    title="Lock weights (requires 100%)"
                                >
                                    <i class="pi pi-lock text-[9px]"></i>
                                    <span>Lock (100%)</span>
                                </button>
                                <button 
                                    type="button"
                                    @click="resetWeightsToDefault" 
                                    class="px-2 py-1 rounded-lg text-[10px] font-black text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 flex items-center gap-1 transition-all cursor-pointer shadow-xs"
                                    title="Reset all 5 competencies to default 20% each"
                                >
                                    <i class="pi pi-refresh text-[9px]"></i>
                                    <span>Reset 20%</span>
                                </button>
                                <button 
                                    v-if="isManager && !isReadOnly" 
                                    type="button"
                                    @click="saveManagerTeamTemplate" 
                                    :disabled="saving"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-black text-white bg-emerald-600 hover:bg-emerald-700 border border-emerald-600 flex items-center gap-1 transition-all cursor-pointer shadow-xs ml-1"
                                    title="Save edited competencies and weights as your team template"
                                >
                                    <i v-if="saving" class="pi pi-spin pi-spinner text-[9px]"></i>
                                    <i v-else class="pi pi-check-circle text-[9px]"></i>
                                    <span>Save Template for My Team</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Informational banner when adjusting weights -->
                    <div v-if="isEditingWeights && totalWeight !== 100" class="px-5 py-2 bg-amber-50 border-b border-amber-200 flex items-center justify-between text-xs text-amber-800">
                        <div class="flex items-center gap-2 text-[11px]">
                            <i class="pi pi-info-circle text-amber-600"></i>
                            <span>
                                <strong>Weight Adjustment Mode:</strong> Current sum is <strong>{{ totalWeight }}%</strong>.
                                <span v-if="totalWeight < 100"> Need <strong>+{{ 100 - totalWeight }}%</strong> more to reach 100%.</span>
                                <span v-else> Please reduce by <strong>-{{ totalWeight - 100 }}%</strong> to equal 100%.</span>
                                (Weights automatically lock once total reaches exactly 100%).
                            </span>
                        </div>
                        <button type="button" @click="resetWeightsToDefault" class="text-[11px] font-bold text-amber-900 underline hover:no-underline cursor-pointer">
                            Reset to 20%
                        </button>
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
                                <tr v-for="(comp, index) in performanceCompetencies" :key="comp.id" class="hover:bg-indigo-50/10 transition-colors">
                                    <td class="px-3 py-3 font-black text-gray-300 text-xs align-top pt-4">0{{ index + 1 }}</td>
                                    <td class="px-3 py-2.5">
                                        <template v-if="isManager && !isReadOnly">
                                            <input v-model="comp.title" class="w-full font-bold text-gray-800 text-xs md:text-sm mb-1 tracking-tight bg-transparent border-b border-dashed border-slate-300 hover:border-slate-400 focus:border-indigo-400 outline-none px-2 py-0.5 rounded transition-all" placeholder="Competency Title" />
                                            <textarea v-model="comp.descriptionText" 
                                                @input="comp.descriptions = comp.descriptionText.split('\n')"
                                                class="w-full text-[11px] text-gray-500 leading-snug font-medium bg-transparent border border-dashed border-slate-200 hover:border-slate-300 focus:border-indigo-200 outline-none resize-none px-2 py-1 rounded transition-all" 
                                                rows="2" 
                                                placeholder="Criteria descriptions (one line per bullet)">
                                            </textarea>
                                        </template>
                                        <template v-else>
                                            <div class="font-bold text-gray-800 text-xs md:text-sm mb-1 tracking-tight px-2 py-0.5">{{ comp.title }}</div>
                                            <div class="text-[11px] text-gray-500 leading-snug font-medium px-2 py-0.5 whitespace-pre-line">{{ comp.descriptionText || (comp.descriptions ? comp.descriptions.join('\n') : '') }}</div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2.5 text-center align-middle">
                                        <div class="flex items-center justify-center">
                                            <select 
                                                v-model.number="comp.weight" 
                                                @change="onWeightChange"
                                                :disabled="isWeightDisabled" 
                                                class="weight-select shadow-xs"
                                            >
                                                <option v-for="opt in weightOptions" :key="opt" :value="opt">{{ opt }}%</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 bg-indigo-50/30 align-middle">
                                        <select v-model.number="comp.selfRating" :disabled="isReadOnly || isManager" class="prof-input !py-1 !px-2 w-full !text-center font-black text-xs !rounded-lg" :class="{ 'opacity-60 cursor-not-allowed': isManager }">
                                            <option :value="0">—</option>
                                            <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5 bg-teal-50/30 align-middle">
                                        <select v-model.number="comp.managerRating" disabled class="prof-input !py-1 !px-2 w-full !text-center font-black text-xs !border-teal-200 opacity-50 cursor-not-allowed !rounded-lg">
                                            <option :value="0">—</option>
                                            <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-black text-indigo-600 text-sm align-middle">{{ getWeightedScore(comp) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50/90 border-t border-gray-100">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 font-black text-gray-500 tracking-normal text-[11px]">ANNUAL PERFORMANCE SUMMARY</td>
                                    <td class="px-3 py-3 text-center align-middle">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="font-black text-sm" :class="totalWeight === 100 ? 'text-emerald-600' : 'text-amber-600'">{{ totalWeight }}%</span>
                                            <span v-if="totalWeight === 100" class="text-[8px] font-bold text-emerald-600 tracking-tight uppercase">Locked (100%)</span>
                                            <span v-else class="text-[8px] font-bold text-amber-500 tracking-tight uppercase">Must be 100%</span>
                                        </div>
                                    </td>
                                    <td colspan="2" class="px-3 py-3 text-center font-black text-gray-400 uppercase tracking-wider text-[9px]">OVERALL SCORE</td>
                                    <td class="px-3 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="font-black text-indigo-600 text-xl">{{ overallPerformanceRating }}</span>
                                            <span class="text-xs font-bold text-indigo-300 ml-0.5">/ 5.00</span>
                                            <span class="text-[11px] font-black text-indigo-600 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded-md">
                                                ({{ overallPercentage }}%)
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Additional Comments Section (Compact) -->
                <div class="prof-card mx-4 md:mx-6 p-4 md:p-5 mt-4">
                    <h4 class="font-black text-gray-800 text-xs md:text-sm mb-2 flex items-center gap-2">
                        <i class="pi pi-comment text-indigo-500"></i> Additional Comments
                    </h4>
                    <textarea v-model="appraisal.comments" rows="2" class="prof-input !bg-gray-50 !text-xs !p-2.5 !rounded-xl" 
                        placeholder="Add any additional comments or notes here..."></textarea>
                </div>
            </div>
        
            <!-- STEP 2: Ratings & Feedback (Compact & Modern Layout) -->
            <div v-if="currentStep === 2" class="mx-4 md:mx-6 space-y-4 animate-fadeIn pb-6">
                
                <!-- ─── 1. Interpersonal Impressions Section (Compact) ─── -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Most Impressed -->
                    <div class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden hover:shadow-md transition-all">
                        <div class="bg-emerald-600 px-4 py-2.5 border-b border-emerald-700 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center border border-white/10">
                                <i class="pi pi-thumbs-up text-white text-xs"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-xs text-white uppercase tracking-wide">What impressed you the most?</h4>
                                <p class="text-[8px] text-emerald-100 font-bold uppercase tracking-wider opacity-80">Positive performance highlights</p>
                            </div>
                        </div>
                        <div class="p-3.5">
                            <textarea v-model="appraisal.impressedMost" rows="3" 
                                class="w-full bg-emerald-50/20 border border-emerald-100 rounded-lg py-2 px-3 text-xs font-medium text-slate-700 focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/20 outline-none resize-none transition-all" 
                                placeholder="Describe key strengths and achievements..."></textarea>
                        </div>
                    </div>

                    <!-- Least Impressed -->
                    <div class="bg-white rounded-xl border border-orange-200 shadow-sm overflow-hidden hover:shadow-md transition-all">
                        <div class="bg-orange-500 px-4 py-2.5 border-b border-orange-600 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center border border-white/10">
                                <i class="pi pi-exclamation-triangle text-white text-xs"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-xs text-white uppercase tracking-wide">What impressed you the least?</h4>
                                <p class="text-[8px] text-orange-50 font-bold uppercase tracking-wider opacity-80">Area of improvement</p>
                            </div>
                        </div>
                        <div class="p-3.5">
                            <textarea v-model="appraisal.impressedLeast" rows="3" 
                                class="w-full bg-orange-50/20 border border-orange-100 rounded-lg py-2 px-3 text-xs font-medium text-slate-700 focus:border-orange-400 focus:ring-1 focus:ring-orange-400/20 outline-none resize-none transition-all" 
                                placeholder="Describe areas needing development and improvement..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- ─── 2. Performance Rating Matrix (Compact) ─── -->
                <div class="bg-white rounded-xl border border-indigo-200 shadow-sm overflow-hidden">
                    <div class="bg-[#1A237E] px-4 py-2.5 border-b border-[#0D1559] flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center border border-white/10">
                                <i class="pi pi-star-fill text-white text-xs"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-xs text-white uppercase tracking-wide">Performance Rating Matrix</h4>
                                <p class="text-[8px] text-indigo-200 font-bold uppercase tracking-wider opacity-80">Select the most accurate rating for this period</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 bg-white/10 text-white text-[8px] font-black uppercase tracking-wider rounded border border-white/10">Scale 1-5</span>
                    </div>

                    <div class="p-4 space-y-2">
                        <div v-for="rating in performanceRatings" :key="rating.value"
                            @click="onSelectRating(rating.value)"
                            class="group rounded-xl border p-3 transition-all duration-200"
                            :class="[
                                isReadOnly ? 'cursor-default' : 'cursor-pointer',
                                appraisal.performanceRating === rating.value
                                    ? 'bg-indigo-50/70 border-indigo-300 shadow-sm ring-1 ring-indigo-200/50'
                                    : 'bg-slate-50/40 border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/20'
                            ]">
                            <div class="flex flex-col md:flex-row md:items-center gap-3">
                                <!-- Radio + Label -->
                                <div class="flex items-center gap-2.5 md:w-[260px] shrink-0">
                                    <div :class="appraisal.performanceRating === rating.value ? 'bg-indigo-600 border-indigo-600 shadow-sm' : 'bg-white border-slate-200 group-hover:border-indigo-300'"
                                        class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all shrink-0">
                                        <div v-show="appraisal.performanceRating === rating.value" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    </div>
                                    <span class="text-xs font-black tracking-tight" :class="appraisal.performanceRating === rating.value ? 'text-indigo-900' : 'text-slate-600'">{{ rating.label }}</span>
                                </div>
                                <!-- Score Badge -->
                                <div class="shrink-0">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-black text-xs transition-all"
                                        :class="appraisal.performanceRating === rating.value ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-400'">
                                        {{ rating.value }}
                                    </span>
                                </div>
                                <!-- Comments -->
                                <div class="flex-1 transition-all" :class="appraisal.performanceRating === rating.value ? 'opacity-100' : 'opacity-30 group-hover:opacity-50'">
                                    <input v-model="appraisal.rating_comments[rating.value]" 
                                        :disabled="appraisal.performanceRating !== rating.value"
                                        @input="appraisal.performanceComments = appraisal.rating_comments[rating.value]"
                                        type="text"
                                        class="w-full bg-white border rounded-lg py-1.5 px-3 text-xs font-medium text-slate-700 outline-none transition-all"
                                        :class="appraisal.performanceRating === rating.value ? 'border-indigo-200 focus:border-indigo-400' : 'border-slate-100'"
                                        :placeholder="appraisal.performanceRating === rating.value ? 'Enter specific comments for this rating...' : ''" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════ -->
                <!-- AUTHORIZATION SIGN-OFF SECTION (Compact)               -->
                <!-- ═══════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-[#1A237E] px-4 py-2.5 border-b border-[#0D1559] flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center border border-white/10">
                            <i class="pi pi-verified text-white text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-xs text-white uppercase tracking-wide">Authorization &amp; Sign-Off</h4>
                            <p class="text-[8px] text-indigo-200 font-bold uppercase tracking-wider opacity-80">Management Endorsement &amp; Final Approval</p>
                        </div>
                    </div>

                    <div class="p-4 space-y-3">

                        <!-- ── 1. Line Manager's Name & Signature ── -->
                        <div class="bg-slate-50/70 rounded-xl border border-slate-100 p-3.5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-5 h-5 rounded-md bg-[#1A237E] text-white flex items-center justify-center font-black text-[9px]">1</span>
                                <h5 class="font-black text-xs text-[#1A237E] uppercase tracking-wide">Line Manager's Name &amp; Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-wider mb-1">Signature</label>
                                    <input v-model="appraisal.manager_signature_name" type="text"
                                        class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs font-bold text-slate-800 focus:border-[#1A237E] outline-none transition-all signature-input"
                                        placeholder="Type full name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-wider mb-1">Date</label>
                                    <input v-model="appraisal.signature_date" type="date"
                                        class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs font-bold text-slate-800 focus:border-[#1A237E] outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                        <!-- ── 2. HOD / Functional Head's Comments ── -->
                        <div class="bg-blue-50/30 rounded-xl border border-blue-100/70 p-3.5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-5 h-5 rounded-md bg-blue-700 text-white flex items-center justify-center font-black text-[9px]">2</span>
                                <h5 class="font-black text-xs text-blue-800 uppercase tracking-wide">HOD / Functional Head's Comments</h5>
                            </div>
                            <textarea v-model="appraisal.hod_comments" rows="2"
                                class="w-full bg-white border border-blue-200/60 rounded-lg py-2 px-3 text-xs font-medium text-slate-700 focus:border-blue-500 outline-none resize-none transition-all"
                                placeholder="HOD / Functional Head's overall assessment and comments..."></textarea>
                        </div>

                        <!-- ── 3. HOD / Functional Head's Name, Designation & Signature ── -->
                        <div class="bg-blue-50/30 rounded-xl border border-blue-100/70 p-3.5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-5 h-5 rounded-md bg-blue-700 text-white flex items-center justify-center font-black text-[9px]">3</span>
                                <h5 class="font-black text-xs text-blue-800 uppercase tracking-wide">HOD / Functional Head's Name, Designation &amp; Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-wider mb-1">Signature</label>
                                    <input v-model="appraisal.hod_signature_name" type="text"
                                        class="w-full bg-white border border-blue-200/60 rounded-lg py-1.5 px-3 text-xs font-bold text-slate-800 focus:border-blue-500 outline-none transition-all signature-input"
                                        placeholder="Type HOD name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-wider mb-1">Date</label>
                                    <input v-model="appraisal.hod_signature_date" type="date"
                                        class="w-full bg-white border border-blue-200/60 rounded-lg py-1.5 px-3 text-xs font-bold text-slate-800 focus:border-blue-500 outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                        <!-- ── 4. Director's Remarks ── -->
                        <div class="bg-amber-50/30 rounded-xl border border-amber-100/70 p-3.5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-5 h-5 rounded-md bg-amber-700 text-white flex items-center justify-center font-black text-[9px]">4</span>
                                <h5 class="font-black text-xs text-amber-800 uppercase tracking-wide">Director's Remarks</h5>
                            </div>
                            <textarea v-model="appraisal.director_remarks" rows="2"
                                class="w-full bg-white border border-amber-200/60 rounded-lg py-2 px-3 text-xs font-medium text-slate-700 focus:border-amber-500 outline-none resize-none transition-all"
                                placeholder="Director's final remarks and endorsement notes..."></textarea>
                        </div>

                        <!-- ── 5. Director's Signature ── -->
                        <div class="bg-amber-50/30 rounded-xl border border-amber-100/70 p-3.5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-5 h-5 rounded-md bg-amber-700 text-white flex items-center justify-center font-black text-[9px]">5</span>
                                <h5 class="font-black text-xs text-amber-800 uppercase tracking-wide">Director's Signature</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-wider mb-1">Signature</label>
                                    <input v-model="appraisal.director_signature_name" type="text"
                                        class="w-full bg-white border border-amber-200/60 rounded-lg py-1.5 px-3 text-xs font-bold text-slate-800 focus:border-amber-500 outline-none transition-all signature-input"
                                        placeholder="Type director's name as signature" />
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-wider mb-1">Date</label>
                                    <input v-model="appraisal.director_signature_date" type="date"
                                        class="w-full bg-white border border-amber-200/60 rounded-lg py-1.5 px-3 text-xs font-bold text-slate-800 focus:border-amber-500 outline-none transition-all" />
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Final Candidate Acknowledgment Footer -->
                    <div class="bg-[#1A237E] px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white border border-white/20 shadow-inner">
                                <i class="pi pi-user text-sm"></i>
                            </div>
                            <div>
                                <p class="text-[9px] text-indigo-300 uppercase font-black tracking-wider">Candidate Signature</p>
                                <p class="text-[8px] text-white/40 uppercase tracking-wider">Final Employee Acknowledgment</p>
                            </div>
                        </div>
                        <div class="flex-1 max-w-md">
                            <input v-model="appraisal.candidate_signature_name" type="text" 
                                class="signature-input w-full p-0 bg-transparent border-b border-indigo-400/50 text-white text-xl focus:border-white outline-none transition-all placeholder:text-indigo-400/50" 
                                placeholder="Type Full Name to Sign" />
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] text-indigo-300 uppercase font-black tracking-wider mb-0.5">Acknowledgment Date</p>
                            <p class="text-sm text-white font-black">{{ appraisal.signature_date }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </fieldset>

        <!-- Footer Navigation (Compact & Sleek) -->
        <div class="prof-card p-3.5 md:p-4 mx-4 md:mx-6 mt-4 mb-8 bg-gray-50/70 border border-slate-200/70 shadow-sm">
            <div class="flex items-center justify-between">
                <button v-if="currentStep > 1" @click="prevStep"
                    class="prof-button !bg-white !text-gray-600 !border !border-gray-200 !rounded-xl !py-2 px-5 hover:shadow-sm transition-all text-xs font-bold">
                    <i class="pi pi-arrow-left mr-1.5 text-xs font-bold"></i>
                    Previous Step
                </button>
                <button v-else-if="!isReadOnly" @click="saveAppraisal(false)" :disabled="saving"
                    class="prof-button !bg-white !text-indigo-600 !border !border-indigo-100 !rounded-xl !py-2 px-5 shadow-sm hover:shadow transition-all text-xs font-bold">
                    <i class="pi pi-save mr-1.5 text-xs font-bold"></i>
                    Save Progress
                </button>
                <div v-else></div>

                <div class="flex items-center gap-3">
                    <button v-if="currentStep < totalSteps" @click="nextStep"
                        class="prof-button !bg-indigo-600 !text-white !rounded-xl !py-2 px-6 shadow-md shadow-indigo-600/20 hover:scale-[1.01] active:scale-95 text-xs font-bold">
                        Next Stage
                        <i class="pi pi-arrow-right ml-2 text-xs font-bold"></i>
                    </button>

                    <button v-else-if="!isReadOnly" @click="saveAppraisal(true)" :disabled="saving"
                        class="prof-button !bg-teal-600 !text-white !rounded-xl !py-2 px-6 shadow-md shadow-teal-600/20 hover:scale-[1.01] active:scale-95 text-xs font-bold">
                        <i v-if="saving" class="pi pi-spin pi-spinner mr-2"></i>
                        <i v-else class="pi pi-check-circle mr-2 font-bold"></i>
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

/* Hide number input spinners */
.no-spinners::-webkit-inner-spin-button,
.no-spinners::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.no-spinners {
    -moz-appearance: textfield;
}

/* Clean Weight Dropdown - No background patterns or arrows */
.weight-select {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background: #f8fafc !important;
    background-image: none !important;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 8px !important;
    color: #1e293b !important;
    font-weight: 800 !important;
    font-size: 12px !important;
    width: 68px !important;
    height: 30px !important;
    text-align: center !important;
    text-align-last: center !important;
    padding: 0 !important;
    outline: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
}

.weight-select:hover:not(:disabled) {
    background: #ffffff !important;
    background-image: none !important;
    border-color: #cbd5e1 !important;
}

.weight-select:focus:not(:disabled) {
    background: #ffffff !important;
    background-image: none !important;
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15) !important;
}

.weight-select:disabled {
    background: #f1f5f9 !important;
    background-image: none !important;
    border-color: #e2e8f0 !important;
    color: #334155 !important;
    cursor: not-allowed !important;
    opacity: 0.95 !important;
}

/* Dark Mode */
:global(body.dark-mode .weight-select) {
    background: #1e293b !important;
    background-image: none !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
:global(body.dark-mode .weight-select:disabled) {
    background: #0f172a !important;
    background-image: none !important;
    border-color: #1e293b !important;
    color: #94a3b8 !important;
}
:global(body.dark-mode .step-inactive) {
    background-color: #1e293b !important;
    color: #94a3b8 !important;
    border-color: #475569 !important;
}
:global(body.dark-mode .prof-input[type="date"]::-webkit-calendar-picker-indicator) {
    filter: invert(0.8) !important;
}
</style>
