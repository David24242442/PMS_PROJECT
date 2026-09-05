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
const currentStep = ref(1);
const totalSteps = 2;

function range(start, end) {
    const arr = [];
    for (let i = start; i >= end; i--) arr.push(i);
    return arr;
}

// Performance Key Competencies (5 items, 20% weight each)
const performanceCompetencies = ref([
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
]);

// Performance Rating Scale
const performanceRatings = [
    { value: 5, label: '5-Out Standing / Exceptional', potential: 'High Potential', potentialComment: 'Ready for the next position' },
    { value: 4, label: '4-Exceeding Expectations', potential: 'High Potential', potentialComment: 'Ready in 2 year' },
    { value: 3, label: '3-Meeting Expectations', potential: 'Good Potential', potentialComment: '' },
    { value: 2, label: '2-Partly Meeting Expectations', potential: 'Low Potential', potentialComment: 'Need to keep under observation (Potential level is good need more training)' },
    { value: 1, label: '1-Below Expectations / Unsatisfactory', potential: 'Below Potential', potentialComment: 'PIP' }
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
    candidate_name: '', // Added for header input
    candidate_signature_name: '',
    manager_signature_name: '',
    signature_date: new Date().toISOString().split('T')[0]
});

// Computed
const overallPerformanceRating = computed(() => {
    let totalWeightedScore = 0;
    performanceCompetencies.value.forEach(comp => {
        const rating = comp.managerRating || comp.selfRating || 0;
        totalWeightedScore += (rating / 5) * comp.weight;
    });
    return totalWeightedScore.toFixed(2);
});

const canProceedToStep2 = computed(() => {
    return performanceCompetencies.value.every(comp => comp.selfRating > 0);
});

// Methods
const nextStep = () => {
    if (currentStep.value < totalSteps) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const getWeightedScore = (comp) => {
    const rating = comp.managerRating || comp.selfRating || 0;
    return ((rating / 5) * comp.weight).toFixed(2);
};

const fetchAppraisal = async () => {
    loading.value = true;
    try {
        // 1. Check for existing official appraisal first
        const appResponse = await axios.get('pms/appraisals', { 
            params: { year: currentYear.value, user_id: loguser.id } 
        });

        if (appResponse.data.status === 'success') {
            const data = appResponse.data.data;
            appraisal.value = { ...appraisal.value, ...data };
            if (data.competencies_data) {
                performanceCompetencies.value = data.competencies_data;
            }
            loading.value = false;
            return; // If official appraisal exists, don't load draft
        }
    } catch (e) { /* Check draft if not found */ }

    // 2. If no official appraisal, check for draft
    try {
        const draftResponse = await axios.get('pms/drafts', {
            params: { type: `appraisal_${currentYear.value}` }
        });
        
        if (draftResponse.data.status === 'success') {
            const draftData = draftResponse.data.data.data;
            if (draftData) {
                appraisal.value = { ...appraisal.value, ...draftData };
                if (draftData.competencies_data) {
                    performanceCompetencies.value = draftData.competencies_data;
                }
                showAlert('Draft Loaded', 'Your previous session has been restored.', 'info');
            }
        }
    } catch (error) {
        console.log('No draft found');
    } finally {
        loading.value = false;
    }
};

const saveAppraisal = async (submit = false) => {
    saving.value = true;
    try {
        const payload = {
            ...appraisal.value,
            competencies_data: performanceCompetencies.value,
            overall_rating: overallPerformanceRating.value,
            achievements: appraisal.value.impressedMost, // Map 'What impressed you most' to achievements
            improvements: appraisal.value.impressedLeast, // Map 'What impressed you least' to improvements
            self_assessment: appraisal.value.comments, // Map comments to self_assessment (or general comments)
            // status field is handled by backend or not needed for draft
            year: currentYear.value,
            user_id: loguser.id
        };

        if (submit) {
            // Submit official appraisal
            payload.status = 'pending';
            const response = await axios.post('pms/appraisals', payload);
            if (response.data.status === 'success') {
                appraisal.value = { ...appraisal.value, ...response.data.data };
                // Delete draft after successful submission
                await axios.delete('pms/drafts', { params: { type: `appraisal_${currentYear.value}` } });
                showAlert('Success', 'Appraisal submitted for review.', 'success');
            }
        } else {
            // Save as draft
            const draftPayload = {
                type: `appraisal_${currentYear.value}`,
                data: payload
            };
            const response = await axios.post('pms/drafts', draftPayload);
            if (response.data.status === 'success') {
                showAlert('Success', 'Draft saved successfully.', 'success');
            }
        }
    } catch (error) {
        console.error('Error saving:', error);
        showAlert('Error', 'Action failed. Please check your inputs.', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchAppraisal();
});
</script>

<template>
    <div class="h-full">
        <!-- Page Header with Stepper -->
        <div class="stepper-header rounded-xl shadow-sm mb-6">
            <div class="px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                            <i class="pi pi-file-edit text-2xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">Yearly Performance Assessment</h1>
                            <p class="text-purple-200 text-sm">Step {{ currentStep }} of {{ totalSteps }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Step Indicators -->
                        <div class="hidden md:flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div :class="currentStep >= 1 ? 'step-active' : 'step-inactive'"
                                    class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-all">1</div>
                                <span class="text-white font-medium">Competencies</span>
                            </div>
                            <div class="w-8 h-0.5 bg-white/30"></div>
                            <div class="flex items-center gap-2">
                                <div :class="currentStep >= 2 ? 'step-active' : 'step-inactive'"
                                    class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-all">2</div>
                                <span class="text-white font-medium">Ratings & Feedback</span>
                            </div>
                        </div>
                        <select v-model="currentYear" @change="fetchAppraisal" class="year-select">
                            <option v-for="year in years" :key="year" :value="year">FY {{ year }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee & Manager Info Header -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6 overflow-hidden">
            <div class="employee-header-banner px-6 py-3">
                <h3 class="font-bold text-white text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="pi pi-id-card"></i> Employee & Manager Information
                </h3>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-5">
                <!-- Candidate Section -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Candidate Name</p>
                        <input v-model="appraisal.candidate_name" type="text" class="font-bold text-gray-800 w-full bg-transparent border-b-2 border-transparent hover:border-gray-200 focus:border-purple-500 focus:bg-purple-50/50 rounded transition-all px-2 py-1.5 -ml-2" placeholder="Enter Candidate Name" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Assessment Date</p>
                        <p class="font-medium text-gray-700">{{ new Date().toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Emp. Code</p>
                        <p class="font-medium text-gray-700">{{ loguser?.employee_code || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Line Manager</p>
                        <p class="font-bold text-gray-800">{{ loguser?.name || loguser?.username || 'Manager Name' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Job Title</p>
                        <p class="font-medium text-gray-700">{{ loguser?.job_title || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Over all final rating</p>
                        <p class="font-bold text-purple-600 text-xl">{{ overallPerformanceRating }}%</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Department Name/Location</p>
                        <p class="font-medium text-gray-700">{{ loguser?.department || 'N/A' }}</p>
                    </div>
                </div>
                
                <!-- Signatures Section -->
                <div class="grid grid-cols-2 gap-6 border-l border-gray-100 pl-6">
                    <div>
                        <p class="text-xs text-purple-500 uppercase font-semibold mb-2 flex items-center gap-1">
                            <i class="pi pi-user"></i> Candidate Signature
                        </p>
                        <input v-model="appraisal.candidate_signature_name" type="text" 
                            class="form-control font-bold text-gray-700 bg-purple-50/30 border-purple-100 focus:border-purple-300 focus:ring-4 focus:ring-purple-500/10 transition-all font-handwriting" 
                            placeholder="Type full name to sign" />
                        <div class="mt-3">
                            <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Date</p>
                            <input v-model="appraisal.signature_date" type="date" readonly 
                                class="form-control bg-gray-50 text-gray-500 cursor-not-allowed border-gray-200" />
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-green-500 uppercase font-semibold mb-2 flex items-center gap-1">
                            <i class="pi pi-briefcase"></i> Line Manager Signature
                        </p>
                        <input v-model="appraisal.manager_signature_name" type="text" 
                            class="form-control font-bold text-gray-700 bg-green-50/30 border-green-100 focus:border-green-300 focus:ring-4 focus:ring-green-500/10 transition-all font-handwriting" 
                            placeholder="Type full name to sign" />
                        <div class="mt-3">
                            <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Date</p>
                            <input v-model="appraisal.signature_date" type="date" readonly 
                                class="form-control bg-gray-50 text-gray-500 cursor-not-allowed border-gray-200" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
            <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-gray-500">Loading appraisal data...</p>
        </div>

        <div v-else>
            <!-- STEP 1: Performance Key Competencies -->
            <div v-if="currentStep === 1" class="space-y-6 animate-fadeIn">
                <!-- Performance Competencies Table -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="competency-header px-6 py-4">
                        <h3 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="pi pi-chart-bar"></i> Performance Key Competencies
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">(Rate on how well they worked during the whole year)</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase w-8">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Competency</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase w-20">Wts.</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-orange-600 uppercase w-28 bg-orange-50">Self Rating</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-blue-600 uppercase w-32 bg-blue-50">Line Manager<br>1-5 Rating<br>(5 Highest)</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase w-28">Line Manager's<br>Weighted Score</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(comp, index) in performanceCompetencies" :key="comp.id" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-4 font-bold text-gray-600">#{{ index + 1 }}</td>
                                    <td class="px-4 py-4">
                                        <p class="font-bold text-blue-600 underline mb-1">{{ comp.title }}</p>
                                        <p v-for="(desc, i) in comp.descriptions" :key="i" class="text-sm text-gray-600">
                                            {{ desc }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 text-center font-bold text-gray-700">{{ comp.weight }}%</td>
                                    <td class="px-4 py-4 bg-orange-50/50">
                                        <select v-model="comp.selfRating" class="rating-select w-32 text-gray-500 ">
                                            <option :value="0">Select</option>
                                            <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-4 bg-blue-50/50">
                                        <select v-model="comp.managerRating" class="rating-select w-full text-gray-700">
                                            <option :value="0">—</option>
                                            <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-4 text-center font-bold text-gray-700">{{ getWeightedScore(comp) }}%</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-100 border-t-2 border-gray-300">
                                <tr>
                                    <td colspan="2" class="px-4 py-4 font-bold text-gray-700">Comments:</td>
                                    <td class="px-4 py-4 text-center font-bold text-gray-800">OVERALL PERFORMANCE SCORE</td>
                                    <td colspan="2" class="px-4 py-4 text-center font-bold text-gray-800">100%</td>
                                    <td class="px-4 py-4 text-center font-bold text-purple-600 text-xl">{{ overallPerformanceRating }}%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="p-4 border-t border-gray-200">
                        <textarea v-model="appraisal.comments" rows="3" class="form-control" 
                            placeholder="Add any additional comments here..."></textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Ratings & Feedback -->
            <div v-if="currentStep === 2" class="space-y-6 animate-fadeIn">
                <!-- What Impressed You Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="bg-green-50 px-5 py-3 border-b border-green-100">
                            <h4 class="font-bold text-green-800 flex items-center gap-2">
                                <i class="pi pi-thumbs-up"></i> What impressed you the most?
                            </h4>
                        </div>
                        <div class="p-4">
                            <textarea v-model="appraisal.impressedMost" rows="4" class="form-control" 
                                placeholder="Describe the most impressive aspects of performance..."></textarea>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="bg-orange-50 px-5 py-3 border-b border-orange-100">
                            <h4 class="font-bold text-orange-800 flex items-center gap-2">
                                <i class="pi pi-exclamation-triangle"></i> What impressed you the least? (Area of improvement)
                            </h4>
                        </div>
                        <div class="p-4">
                            <textarea v-model="appraisal.impressedLeast" rows="4" class="form-control" 
                                placeholder="Identify areas that need improvement..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Performance Rating & Potential Rating Tables -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="competency-header px-6 py-4">
                        <h3 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="pi pi-star"></i> Performance & Potential Rating
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-blue-600 uppercase underline w-1/4">Performance Rating</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-blue-600 uppercase underline w-1/4">Comments</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-blue-600 uppercase underline w-1/4">Potential Rating</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-blue-600 uppercase underline w-1/4">Comments</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="rating in performanceRatings" :key="rating.value" 
                                    class="hover:bg-gray-50/50 cursor-pointer"
                                    :class="{ 'bg-purple-50': appraisal.performanceRating === rating.value }">
                                    <td class="px-4 py-3">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" v-model="appraisal.performanceRating" :value="rating.value" 
                                                class="w-4 h-4 text-purple-600">
                                            <span class="text-sm text-gray-700">{{ rating.label }}</span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input v-if="appraisal.performanceRating === rating.value" 
                                            type="text" v-model="appraisal.performanceComments" 
                                            class="form-control text-sm" placeholder="Add comments...">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rating.potential }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 italic">{{ rating.potentialComment }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>

            <!-- Footer Navigation -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mt-6">
                <div class="flex items-center justify-between">
                    <button v-if="currentStep > 1" @click="prevStep" 
                        class="group px-6 py-3 rounded-xl border-2 border-gray-200 text-white font-semibold hover:border-purple-300  hover:bg-gray-600 transition-all duration-200 flex items-center gap-2">
                        <i class="pi pi-arrow-left transform group-hover:-translate-x-1 transition-transform"></i> 
                        Previous
                    </button>
                    <button v-else @click="saveAppraisal(false)" :disabled="saving"
                        class="group px-6 py-3 rounded-xl border-2 border-gray-200 text-white font-semibold hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50 transition-all duration-200 flex items-center gap-2">
                        <i class="pi pi-save"></i>
                        Save Draft
                    </button>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full">
                            <span class="text-sm font-medium text-gray-500">Step</span>
                            <span class="w-7 h-7 rounded-full bg-purple-600 text-white text-sm font-bold flex items-center justify-center">{{ currentStep }}</span>
                            <span class="text-sm text-gray-400">of {{ totalSteps }}</span>
                        </div>
                        
                        <button v-if="currentStep < totalSteps" @click="nextStep" :disabled="!canProceedToStep2"
                            class="group px-8 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold hover:from-purple-700 hover:to-purple-800 shadow-lg shadow-purple-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-xl hover:shadow-purple-300 hover:-translate-y-0.5">
                            Next Step
                            <i class="pi pi-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </button>
                        
                        <button v-else @click="saveAppraisal(true)" :disabled="saving"
                            class="group px-8 py-3 rounded-xl text-white font-semibold shadow-lg hover:bg-gray-600 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-xl  hover:-translate-y-0.5">
                            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                            <i v-else class="pi pi-check-circle"></i>
                            {{ saving ? 'Submitting...' : 'Submit for Review' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Stepper Header */
.stepper-header {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #9333ea 100%);
}

.step-active {
    background-color: white;
    color: #7c3aed;
}

.step-inactive {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Employee Header Banner */
.employee-header-banner {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

/* Competency Header */
.competency-header {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

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

/* Rating Select */
.rating-select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-align: center;
    background-color: white;
}

.rating-select:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

/* Form Control */
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.form-control::placeholder {
    color: #94a3b8;
}

/* Animation */
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
