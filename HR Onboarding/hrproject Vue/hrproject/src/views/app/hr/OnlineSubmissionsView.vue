<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '@/helpers/pms_axios'
import { toastt, numberToWords, aDate } from '@/helpers/essential'
import Swal from 'sweetalert2'
import { useUsersStore } from '@/stores/user'

const userstore = useUsersStore()
const { loguser } = userstore

// Submissions state
const onlineSubmissions = ref([])
const onlineSubmissionsStats = reactive({
    total: 0,
    pending: 0,
    approved: 0,
    rejected: 0
})
const onlineCurrentPage = ref(1)
const onlineLastPage = ref(1)
const onlineTotal = ref(0)
const onlinePerPage = ref(15)
const isOnlineLoading = ref(false)

// Filters
const onlineFilterStatus = ref('all')
const onlineSearch = ref('')

// Modal Review State
const showOnlineReviewModal = ref(false)
const selectedOnlineSubmission = ref(null)
const onlineReviewStep = ref(1)
const isApprovingOnline = ref(false)
const isRejectingOnline = ref(false)

// Fetch Submissions
const fetchOnlineSubmissions = async (page = 1) => {
    isOnlineLoading.value = true
    onlineCurrentPage.value = page
    try {
        const params = {
            page: onlineCurrentPage.value,
            per_page: onlinePerPage.value,
            status: onlineFilterStatus.value !== 'all' ? onlineFilterStatus.value : undefined,
            search: onlineSearch.value ? onlineSearch.value.trim() : undefined
        }
        const res = await axios.get('online-onboardings', { params })
        if (res.data && res.data.status === 'success') {
            const p = res.data.data
            onlineSubmissions.value = p.data || []
            onlineTotal.value = p.total || 0
            onlineCurrentPage.value = p.current_page || 1
            onlineLastPage.value = p.last_page || 1

            if (res.data.stats) {
                Object.assign(onlineSubmissionsStats, res.data.stats)
            }
        }
    } catch (e) {
        console.error('Failed to load online onboarding submissions:', e)
    } finally {
        isOnlineLoading.value = false
    }
}

const resetOnlineFilters = () => {
    onlineSearch.value = ''
    onlineFilterStatus.value = 'all'
    fetchOnlineSubmissions(1)
}

// Review Dossier Modal
const openOnlineReview = (sub) => {
    selectedOnlineSubmission.value = sub
    onlineReviewStep.value = 1
    showOnlineReviewModal.value = true
}

const closeOnlineReview = () => {
    showOnlineReviewModal.value = false
    selectedOnlineSubmission.value = null
}

// Copy Public Link
const copyOnlineOnboardingLink = () => {
    const url = `${window.location.origin}/#/onboard-online`
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            toastt('Candidate Onboarding URL copied! Share with candidates: ' + url, 'success')
        }).catch(() => {
            prompt('Copy Online Onboarding URL:', url)
        })
    } else {
        prompt('Copy Online Onboarding URL:', url)
    }
}

// Approve & Sync
const approveAndSyncOnline = async (sub) => {
    if (!sub || !sub.id) return

    const confirmRes = await Swal.fire({
        title: 'Approve & Sync Candidate?',
        html: `You are about to approve <strong>${sub.candidate_name}</strong> and sync all personal, bank, and guarantee records to the <strong>Server 20 Live Employee Database</strong>.<br><br><span class="text-xs text-slate-500">Record will be marked as Created by you (${loguser?.name || 'Authorized User'}).</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1A237E',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="pi pi-check mr-1"></i> Approve & Sync to Server 20',
        cancelButtonText: 'Cancel'
    })

    if (!confirmRes.isConfirmed) return

    isApprovingOnline.value = true
    try {
        const res = await axios.post(`online-onboardings/${sub.id}/approve-and-sync`)
        if (res.data && res.data.status === 'success') {
            await Swal.fire({
                title: 'Successfully Approved & Synced!',
                html: `Candidate <strong>${sub.candidate_name}</strong> is now registered on Server 20.<br><br><strong>Employee Code:</strong> <span class="text-indigo-700 font-mono font-bold">${res.data.emp_code}</span><br><strong>Created By:</strong> ${res.data.created_by}`,
                icon: 'success',
                confirmButtonColor: '#1A237E'
            })
            closeOnlineReview()
            fetchOnlineSubmissions(onlineCurrentPage.value)
        } else {
            Swal.fire('Approval Notice', res.data.message || 'Action completed.', 'info')
        }
    } catch (e) {
        const msg = e.response && e.response.data && e.response.data.message ? e.response.data.message : e.message
        Swal.fire('Approval Error', msg, 'error')
    } finally {
        isApprovingOnline.value = false
    }
}

// Reject
const rejectOnline = async (sub) => {
    if (!sub || !sub.id) return

    const { value: reason } = await Swal.fire({
        title: 'Reject Online Application?',
        text: `Please enter the reason for rejecting ${sub.candidate_name}'s application:`,
        input: 'textarea',
        inputPlaceholder: 'e.g. Incomplete credentials, invalid Ghana Card image, incorrect branch details...',
        inputAttributes: {
            'aria-label': 'Rejection reason'
        },
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Confirm Rejection',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'You must provide a rejection reason!'
            }
        }
    })

    if (reason) {
        isRejectingOnline.value = true
        try {
            const res = await axios.post(`online-onboardings/${sub.id}/reject`, { reason })
            if (res.data && res.data.status === 'success') {
                toastt('Online onboarding application rejected', 'success')
                closeOnlineReview()
                fetchOnlineSubmissions(onlineCurrentPage.value)
            }
        } catch (e) {
            const msg = e.response && e.response.data && e.response.data.message ? e.response.data.message : e.message
            Swal.fire('Error', msg, 'error')
        } finally {
            isRejectingOnline.value = false
        }
    }
}

onMounted(() => {
    fetchOnlineSubmissions(1)
})
</script>

<template>
    <div class="online-submissions-container pb-12">
        <!-- Ultra-Professional High-Contrast Royal Navy Header Banner -->
        <div 
            class="online-header-card p-6 sm:p-8 rounded-3xl shadow-xl mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6"
            style="background: linear-gradient(135deg, #1A237E 0%, #0D47A1 50%, #1565C0 100%) !important; color: #ffffff !important;"
        >
            <div class="flex items-center gap-4">
                <div 
                    class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0"
                    style="background: rgba(255, 255, 255, 0.18) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #80D8FF !important;"
                >
                    <i class="pi pi-globe"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span 
                            class="text-[11px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded-full"
                            style="background: rgba(255, 255, 255, 0.2) !important; color: #80D8FF !important; border: 1px solid rgba(255, 255, 255, 0.3) !important;"
                        >
                            HR Talent Acquisition &amp; Self-Onboarding
                        </span>
                        <span 
                            v-if="onlineSubmissionsStats.pending > 0" 
                            class="text-[11px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-400 text-slate-900 shadow-sm animate-pulse"
                        >
                            {{ onlineSubmissionsStats.pending }} Pending
                        </span>
                    </div>
                    <h1 
                        class="text-2xl sm:text-3xl font-black tracking-tight m-0"
                        style="color: #ffffff !important; text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;"
                    >
                        Online Onboarding Submissions
                    </h1>
                    <p 
                        class="text-sm mt-1.5 mb-0 font-medium"
                        style="color: #E0E7FF !important;"
                    >
                        Review self-onboarding applications submitted by candidates online, inspect credentials, and approve &amp; sync to Server 20.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <button
                    type="button"
                    @click="fetchOnlineSubmissions(onlineCurrentPage)"
                    :disabled="isOnlineLoading"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm transition-all cursor-pointer shadow-sm hover:scale-[1.02]"
                    style="background: rgba(255, 255, 255, 0.18) !important; color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35) !important;"
                >
                    <i class="pi pi-refresh" :class="{ 'animate-spin': isOnlineLoading }"></i>
                    <span>Refresh</span>
                </button>

                <button
                    type="button"
                    @click="copyOnlineOnboardingLink"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-sm shadow-md transition-all cursor-pointer hover:scale-[1.02]"
                    title="Copy candidate registration URL to share via WhatsApp or Email"
                >
                    <i class="pi pi-share-alt"></i>
                    <span>Share Candidate Link</span>
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Submissions</div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">{{ onlineSubmissionsStats.total.toLocaleString() }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 text-xl font-bold">
                    <i class="pi pi-folder"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pending Review</div>
                    <div class="text-2xl font-black text-amber-600 tracking-tight">{{ onlineSubmissionsStats.pending.toLocaleString() }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 text-xl font-bold">
                    <i class="pi pi-clock"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Approved &amp; Synced</div>
                    <div class="text-2xl font-black text-emerald-600 tracking-tight">{{ onlineSubmissionsStats.approved.toLocaleString() }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 text-xl font-bold">
                    <i class="pi pi-check-circle"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Rejected</div>
                    <div class="text-2xl font-black text-rose-600 tracking-tight">{{ onlineSubmissionsStats.rejected.toLocaleString() }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 text-xl font-bold">
                    <i class="pi pi-times-circle"></i>
                </div>
            </div>
        </div>

        <!-- Search & Filter Toolbar -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs mb-6">
            <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="relative w-full md:w-96">
                    <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input
                        v-model="onlineSearch"
                        @keyup.enter="fetchOnlineSubmissions(1)"
                        type="text"
                        placeholder="Search candidate name, ref #, Ghana card, or mobile..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1A237E] focus:ring-2 focus:ring-[#1A237E]/10"
                    />
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <select
                        v-model="onlineFilterStatus"
                        @change="fetchOnlineSubmissions(1)"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:border-[#1A237E] bg-white cursor-pointer"
                    >
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved &amp; Synced</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <button
                        type="button"
                        @click="resetOnlineFilters"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-sm font-bold transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <i class="pi pi-filter-slash text-xs"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Submissions Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr style="background: #1A237E !important; color: #ffffff !important;">
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">REF #</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">CANDIDATE</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">GHANA CARD</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">MOBILE NO</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">POSITION</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">APPLIED DATE</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px] text-center">STATUS</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px]">CREATED / APPROVED BY</th>
                            <th class="py-3.5 px-4 font-black text-white uppercase tracking-wider text-[11px] text-right">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="isOnlineLoading">
                            <td colspan="9" class="py-12 text-center text-slate-500">
                                <i class="pi pi-spin pi-spinner text-3xl text-indigo-700 mb-2 block"></i>
                                Loading submissions from Server 20...
                            </td>
                        </tr>
                        <tr v-else-if="!onlineSubmissions.length">
                            <td colspan="9" class="py-16 text-center text-slate-500">
                                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="pi pi-inbox text-2xl"></i>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 m-0">No Online Submissions Found</h3>
                                <p class="text-xs text-slate-400 mt-1 mb-4">No candidates have submitted an onboarding form matching your filter criteria.</p>
                                <button
                                    type="button"
                                    @click="copyOnlineOnboardingLink"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-100 transition-all text-xs"
                                >
                                    <i class="pi pi-share-alt"></i> Share Candidate Onboarding URL
                                </button>
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="sub in onlineSubmissions"
                            :key="sub.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <!-- Ref # -->
                            <td class="py-3.5 px-4 font-mono font-bold text-indigo-900 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-md bg-indigo-50 border border-indigo-100">
                                    {{ sub.reference_number }}
                                </span>
                            </td>

                            <!-- Candidate -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#1A237E] to-indigo-600 text-white font-black flex items-center justify-center text-xs shadow-xs shrink-0">
                                        {{ (sub.candidate_name || 'C').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-slate-900 leading-tight">
                                            {{ sub.candidate_name }}
                                        </div>
                                        <div v-if="sub.email" class="text-[11px] text-slate-400">
                                            {{ sub.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Ghana Card -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700 whitespace-nowrap">
                                {{ sub.ghcardno || '-' }}
                            </td>

                            <!-- Mobile -->
                            <td class="py-3.5 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                {{ sub.mobileno || '-' }}
                            </td>

                            <!-- Position -->
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                {{ sub.position || 'Employee' }}
                            </td>

                            <!-- Date -->
                            <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                {{ new Date(sub.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span
                                    v-if="sub.status === 'pending'"
                                    class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    Pending Review
                                </span>
                                <span
                                    v-else-if="sub.status === 'approved'"
                                    class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    Approved &amp; Synced
                                </span>
                                <span
                                    v-else-if="sub.status === 'rejected'"
                                    class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-50 text-rose-700 border border-rose-200"
                                >
                                    Rejected
                                </span>
                            </td>

                            <!-- Created By -->
                            <td class="py-3.5 px-4 font-semibold text-slate-700 text-xs">
                                <span v-if="sub.created_by" class="text-indigo-800 font-bold">
                                    {{ sub.created_by }}
                                </span>
                                <span v-else class="text-slate-400 italic">
                                    Not approved yet
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="openOnlineReview(sub)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#1A237E] hover:bg-indigo-900 text-white font-bold text-xs shadow-xs transition-all cursor-pointer"
                                >
                                    <i class="pi pi-eye text-xs"></i>
                                    <span>Review Dossier</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
                <div>
                    Showing page <strong class="text-slate-800 font-bold">{{ onlineCurrentPage }}</strong> of <strong class="text-slate-800 font-bold">{{ onlineLastPage }}</strong> ({{ onlineTotal }} total applications)
                </div>
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        :disabled="onlineCurrentPage <= 1"
                        @click="fetchOnlineSubmissions(onlineCurrentPage - 1)"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 font-bold bg-white text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100"
                    >
                        Previous
                    </button>
                    <span class="px-2 font-bold text-slate-700">Page {{ onlineCurrentPage }}</span>
                    <button
                        type="button"
                        :disabled="onlineCurrentPage >= onlineLastPage"
                        @click="fetchOnlineSubmissions(onlineCurrentPage + 1)"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 font-bold bg-white text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Dossier Review Modal (Comprehensive 6-Stage Viewer) -->
        <div 
            v-if="showOnlineReviewModal && selectedOnlineSubmission"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
        >
            <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[92vh]">
                <!-- Modal Top Header -->
                <div class="px-6 py-4 bg-[#1A237E] text-white flex items-center justify-between shadow-md shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white border border-white/20">
                            <i class="pi pi-id-card text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white tracking-tight m-0">
                                    {{ selectedOnlineSubmission.candidate_name }}
                                </h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-white/20 text-cyan-200">
                                    {{ selectedOnlineSubmission.reference_number }}
                                </span>
                            </div>
                            <p class="text-xs text-indigo-200 m-0 mt-0.5">
                                Position: <strong>{{ selectedOnlineSubmission.position || 'Employee' }}</strong> &bull; Applied: {{ new Date(selectedOnlineSubmission.created_at).toLocaleDateString('en-GB') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeOnlineReview"
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-all cursor-pointer border border-white/20"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- 6-Stage Sub-stepper Navigation -->
                <div class="bg-slate-100 px-6 py-2.5 border-b border-slate-200 flex items-center gap-2 overflow-x-auto shrink-0">
                    <button
                        v-for="step in [
                            { id: 1, label: '1. Personal & Position' },
                            { id: 2, label: '2. Contacts & Family' },
                            { id: 3, label: '3. Education & Work' },
                            { id: 4, label: '4. References & Nominee' },
                            { id: 5, label: '5. Bank, SSNIT & Signatures' },
                            { id: 6, label: '6. Guarantee & Checklist' }
                        ]"
                        :key="step.id"
                        type="button"
                        @click="onlineReviewStep = step.id"
                        :class="onlineReviewStep === step.id ? 'bg-[#1A237E] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer border border-slate-200"
                    >
                        {{ step.label }}
                    </button>
                </div>

                <!-- Modal Body with Scrollable Step Content -->
                <div class="p-6 overflow-y-auto flex-1 text-slate-800 space-y-6">
                    <!-- STEP 1: Personal & Position -->
                    <div v-show="onlineReviewStep === 1" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Candidate Photo Card -->
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center flex flex-col items-center justify-center">
                                <div class="w-32 h-32 rounded-2xl bg-white border border-slate-200 overflow-hidden shadow-sm flex items-center justify-center mb-3">
                                    <img 
                                        v-if="selectedOnlineSubmission.submission_data?.emp?.profilepicture"
                                        :src="selectedOnlineSubmission.submission_data.emp.profilepicture"
                                        alt="Candidate Profile"
                                        class="w-full h-full object-cover"
                                    />
                                    <i v-else class="pi pi-user text-5xl text-slate-300"></i>
                                </div>
                                <div class="text-sm font-black text-slate-900">{{ selectedOnlineSubmission.candidate_name }}</div>
                                <div class="text-xs text-indigo-700 font-bold mt-0.5">{{ selectedOnlineSubmission.position }}</div>
                                <div class="text-[11px] text-slate-400 mt-1">Ref: {{ selectedOnlineSubmission.reference_number }}</div>
                            </div>

                            <!-- Position Details Card -->
                            <div class="md:col-span-2 bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3">
                                <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                    Position &amp; Registration Details
                                </h4>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div><span class="text-slate-400 font-medium">Position:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.position || '-' }}</strong></div>
                                    <div><span class="text-slate-400 font-medium">Joining Date:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.joiningdate || '-' }}</strong></div>
                                    <div><span class="text-slate-400 font-medium">Contract Type:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.contracttype || '-' }}</strong></div>
                                    <div><span class="text-slate-400 font-medium">Company ID:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.joining_company_id || '-' }}</strong></div>
                                    <div><span class="text-slate-400 font-medium">Department ID:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.joining_dept_id || '-' }}</strong></div>
                                    <div><span class="text-slate-400 font-medium">Branch ID:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.joining_branch_id || '-' }}</strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Data Fields -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Personal Information
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div><span class="text-slate-400">First Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.firstname || '-' }}</strong></div>
                                <div><span class="text-slate-400">Surname:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.surname || '-' }}</strong></div>
                                <div><span class="text-slate-400">Other Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.othername || '-' }}</strong></div>
                                <div><span class="text-slate-400">Ghana Card:</span> <strong class="block text-indigo-700 font-mono">{{ selectedOnlineSubmission.ghcardno || '-' }}</strong></div>

                                <div><span class="text-slate-400">Date of Birth:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.dob || '-' }}</strong></div>
                                <div><span class="text-slate-400">Gender:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.gender === 'M' ? 'Male' : (selectedOnlineSubmission.submission_data?.emp?.gender === 'F' ? 'Female' : '-') }}</strong></div>
                                <div><span class="text-slate-400">Marital Status:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.maritalstatus || '-' }}</strong></div>
                                <div><span class="text-slate-400">Religion:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.religion || '-' }}</strong></div>

                                <div><span class="text-slate-400">Hometown:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.hometown || '-' }}</strong></div>
                                <div><span class="text-slate-400">Place of Birth:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.placeofbirth || '-' }}</strong></div>
                                <div><span class="text-slate-400">Citizenship:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.citizenship || 'Ghanaian' }}</strong></div>
                                <div><span class="text-slate-400">Mobile No:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.mobileno || '-' }}</strong></div>

                                <div class="col-span-2"><span class="text-slate-400">Residential Address:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.resaddress || '-' }}</strong></div>
                                <div class="col-span-2"><span class="text-slate-400">Digital / GPS Address:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.digitaladdress || '-' }}</strong></div>

                                <div><span class="text-slate-400">Father's Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.fathersname || '-' }}</strong></div>
                                <div><span class="text-slate-400">Mother's Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.mothersname || '-' }}</strong></div>
                                <div><span class="text-slate-400">SSNIT No:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.socialsecurityno || '-' }}</strong></div>
                                <div><span class="text-slate-400">TIN No:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.tin_no || '-' }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Contacts & Family -->
                    <div v-show="onlineReviewStep === 2" class="space-y-6">
                        <!-- Emergency Contacts -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Emergency Contacts
                            </h4>
                            <div v-if="selectedOnlineSubmission.submission_data?.econts?.length" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                            <th class="py-2 px-3">Name</th>
                                            <th class="py-2 px-3">Relationship</th>
                                            <th class="py-2 px-3">Phone</th>
                                            <th class="py-2 px-3">Address</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(ec, i) in selectedOnlineSubmission.submission_data.econts" :key="i">
                                            <td class="py-2 px-3 font-bold text-slate-800">{{ ec.name || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ ec.relation || '-' }}</td>
                                            <td class="py-2 px-3 font-mono text-slate-700">{{ ec.phoneno || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ ec.address || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-slate-400 text-xs italic py-2">No emergency contacts provided.</div>
                        </div>

                        <!-- Relative in Melcom -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-2">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Relative Working with Melcom Group
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div><span class="text-slate-400">Relative Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.relativename || 'None' }}</strong></div>
                                <div><span class="text-slate-400">Relationship:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.relativerelation || '-' }}</strong></div>
                                <div><span class="text-slate-400">Branch:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.relativebranch || '-' }}</strong></div>
                                <div><span class="text-slate-400">Position:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.emp?.relativeposition || '-' }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Education & Work Experience -->
                    <div v-show="onlineReviewStep === 3" class="space-y-6">
                        <!-- Education -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Educational Background
                            </h4>
                            <div v-if="selectedOnlineSubmission.submission_data?.edus?.length" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                            <th class="py-2 px-3">Institution</th>
                                            <th class="py-2 px-3">Qualification</th>
                                            <th class="py-2 px-3">From</th>
                                            <th class="py-2 px-3">To</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(edu, i) in selectedOnlineSubmission.submission_data.edus" :key="i">
                                            <td class="py-2 px-3 font-bold text-slate-800">{{ edu.schoolname || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ edu.qualtype || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ edu.fromdate || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ edu.todate || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-slate-400 text-xs italic py-2">No educational history entered.</div>
                        </div>

                        <!-- Previous Work Experience -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Previous Work Experience
                            </h4>
                            <div v-if="selectedOnlineSubmission.submission_data?.exps?.length" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                            <th class="py-2 px-3">Employer / Company</th>
                                            <th class="py-2 px-3">Job Title / Position</th>
                                            <th class="py-2 px-3">From</th>
                                            <th class="py-2 px-3">To</th>
                                            <th class="py-2 px-3">Reason for Leaving</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(exp, i) in selectedOnlineSubmission.submission_data.exps" :key="i">
                                            <td class="py-2 px-3 font-bold text-slate-800">{{ exp.companyname || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ exp.position || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ exp.fromdate || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ exp.todate || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ exp.reasonforleaving || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-slate-400 text-xs italic py-2">No previous work experience provided.</div>
                        </div>
                    </div>

                    <!-- STEP 4: References & Nominee -->
                    <div v-show="onlineReviewStep === 4" class="space-y-6">
                        <!-- References -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Professional &amp; Personal References
                            </h4>
                            <div v-if="selectedOnlineSubmission.submission_data?.refs?.length" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                            <th class="py-2 px-3">Referee Name</th>
                                            <th class="py-2 px-3">Occupation / Company</th>
                                            <th class="py-2 px-3">Phone</th>
                                            <th class="py-2 px-3">Address</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(rf, i) in selectedOnlineSubmission.submission_data.refs" :key="i">
                                            <td class="py-2 px-3 font-bold text-slate-800">{{ rf.name || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ rf.occupation || '-' }}</td>
                                            <td class="py-2 px-3 font-mono text-slate-700">{{ rf.phoneno || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ rf.address || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-slate-400 text-xs italic py-2">No references provided.</div>
                        </div>

                        <!-- Nominee -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-2">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Nominee / Beneficiary Details
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div><span class="text-slate-400">Nominee Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.nominee?.name || '-' }}</strong></div>
                                <div><span class="text-slate-400">Relationship:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.nominee?.relation || '-' }}</strong></div>
                                <div><span class="text-slate-400">Phone:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.nominee?.phoneno || '-' }}</strong></div>
                                <div><span class="text-slate-400">Address:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.nominee?.address || '-' }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: Bank, SSNIT & Digital Signatures -->
                    <div v-show="onlineReviewStep === 5" class="space-y-6">
                        <!-- Bank & Social Security Details -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Bank &amp; Social Security Details
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                <div><span class="text-slate-400">Account Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.banksocial?.accountname || '-' }}</strong></div>
                                <div><span class="text-slate-400">Account Number:</span> <strong class="block text-slate-800 font-mono">{{ selectedOnlineSubmission.submission_data?.banksocial?.accountnumber || '-' }}</strong></div>
                                <div><span class="text-slate-400">Bank Branch:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.banksocial?.bankbranch || '-' }}</strong></div>
                                <div><span class="text-slate-400">SSNIT Number:</span> <strong class="block text-slate-800 font-mono">{{ selectedOnlineSubmission.submission_data?.banksocial?.socialfundnumber || selectedOnlineSubmission.submission_data?.emp?.socialsecurityno || '-' }}</strong></div>
                                <div><span class="text-slate-400">Petra Trust Tier 2:</span> <strong class="block text-slate-800 font-mono">{{ selectedOnlineSubmission.submission_data?.banksocial?.petratrustnumber || '-' }}</strong></div>
                            </div>
                        </div>

                        <!-- Candidate & Guarantor Dual Signatures -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 text-center">
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Employee Digital Signature</h4>
                                <div class="h-32 bg-white rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden p-2 shadow-inner">
                                    <img 
                                        v-if="selectedOnlineSubmission.submission_data?.emp?.signature"
                                        :src="selectedOnlineSubmission.submission_data.emp.signature"
                                        alt="Employee Signature"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                    <span v-else class="text-xs text-slate-400 italic">No signature captured</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-2 font-medium">Signed by {{ selectedOnlineSubmission.candidate_name }}</div>
                            </div>

                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 text-center">
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Guarantor Digital Signature</h4>
                                <div class="h-32 bg-white rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden p-2 shadow-inner">
                                    <img 
                                        v-if="selectedOnlineSubmission.submission_data?.irrguar?.signature"
                                        :src="selectedOnlineSubmission.submission_data.irrguar.signature"
                                        alt="Guarantor Signature"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                    <span v-else class="text-xs text-slate-400 italic">No guarantor signature captured</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-2 font-medium">Signed by {{ selectedOnlineSubmission.submission_data?.irrguar?.guarname || 'Guarantor' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 6: Irrevocable Guarantee & Compliance Checklist -->
                    <div v-show="onlineReviewStep === 6" class="space-y-6">
                        <!-- Irrevocable Guarantee Summary -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Irrevocable Continuing Guarantee Record
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div><span class="text-slate-400">Guarantor Name:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.irrguar?.guarname || '-' }}</strong></div>
                                <div><span class="text-slate-400">Occupation:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.irrguar?.occupation || '-' }}</strong></div>
                                <div><span class="text-slate-400">Mobile Phone:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.irrguar?.mobileno || '-' }}</strong></div>
                                <div><span class="text-slate-400">Indemnity Amount:</span> <strong class="block text-indigo-700 font-extrabold">GHS {{ Number(selectedOnlineSubmission.submission_data?.irrguar?.amount || 5000).toLocaleString() }}</strong></div>
                                <div><span class="text-slate-400">Years Known:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.irrguar?.yearsknown || '3' }} years</strong></div>
                                <div><span class="text-slate-400">Relationship:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.irrguar?.relation || '-' }}</strong></div>
                                <div><span class="text-slate-400">SSNIT No:</span> <strong class="block text-slate-800 font-mono">{{ selectedOnlineSubmission.submission_data?.irrguar?.ssfno || '-' }}</strong></div>
                                <div><span class="text-slate-400">Email:</span> <strong class="block text-slate-800">{{ selectedOnlineSubmission.submission_data?.irrguar?.primary_email || '-' }}</strong></div>
                            </div>
                        </div>

                        <!-- Guarantee Witnesses -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Guarantee Witnesses
                            </h4>
                            <div v-if="selectedOnlineSubmission.submission_data?.witnesses?.length" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                            <th class="py-2 px-3">Witness Name</th>
                                            <th class="py-2 px-3">Address</th>
                                            <th class="py-2 px-3">Phone</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(wit, i) in selectedOnlineSubmission.submission_data.witnesses" :key="i">
                                            <td class="py-2 px-3 font-bold text-slate-800">{{ wit.name || '-' }}</td>
                                            <td class="py-2 px-3 text-slate-600">{{ wit.address || '-' }}</td>
                                            <td class="py-2 px-3 font-mono text-slate-700">{{ wit.phoneno || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-slate-400 text-xs italic py-2">No witnesses entered.</div>
                        </div>

                        <!-- Uploaded Compliance Documents -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1A237E] m-0 pb-2 border-b border-slate-200">
                                Uploaded Documents &amp; Verification Files
                            </h4>
                            <div v-if="selectedOnlineSubmission.submission_data?.documents" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div 
                                    v-for="(docBase64, docKey) in selectedOnlineSubmission.submission_data.documents"
                                    :key="docKey"
                                    class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-col items-center text-center"
                                >
                                    <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 mb-2">
                                        <i class="pi pi-file"></i>
                                    </div>
                                    <span class="text-xs font-bold text-slate-800 uppercase tracking-tight">{{ docKey }}</span>
                                    <a 
                                        :href="docBase64"
                                        target="_blank"
                                        download
                                        class="mt-2 text-[11px] font-extrabold text-indigo-600 hover:text-indigo-800 flex items-center gap-1"
                                    >
                                        <i class="pi pi-download text-[10px]"></i> View / Download
                                    </a>
                                </div>
                            </div>
                            <div v-else class="text-slate-400 text-xs italic py-2">No uploaded verification files attached.</div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions (Last Step Controls) -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            v-if="onlineReviewStep > 1"
                            @click="onlineReviewStep--"
                            class="px-4 py-2 rounded-xl border border-slate-300 font-bold bg-white text-slate-700 hover:bg-slate-100 text-xs transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            <i class="pi pi-chevron-left text-[10px]"></i> Previous Step
                        </button>
                        <button
                            type="button"
                            v-if="onlineReviewStep < 6"
                            @click="onlineReviewStep++"
                            class="px-4 py-2 rounded-xl bg-indigo-50 text-indigo-800 font-bold hover:bg-indigo-100 text-xs transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            Next Step <i class="pi pi-chevron-right text-[10px]"></i>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="closeOnlineReview"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-100 text-xs transition-all cursor-pointer"
                        >
                            Close
                        </button>

                        <template v-if="selectedOnlineSubmission.status === 'pending'">
                            <button
                                type="button"
                                @click="rejectOnline(selectedOnlineSubmission)"
                                :disabled="isRejectingOnline || isApprovingOnline"
                                class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 font-bold text-xs transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <i class="pi pi-times"></i>
                                <span>Reject</span>
                            </button>

                            <button
                                type="button"
                                @click="approveAndSyncOnline(selectedOnlineSubmission)"
                                :disabled="isApprovingOnline || isRejectingOnline"
                                class="px-5 py-2.5 rounded-xl bg-[#1A237E] hover:bg-indigo-900 text-white font-extrabold text-xs shadow-md transition-all cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <i class="pi pi-check" :class="{ 'animate-spin': isApprovingOnline }"></i>
                                <span>{{ isApprovingOnline ? 'Syncing to Server 20...' : 'Approve & Sync to Server 20' }}</span>
                            </button>
                        </template>

                        <div v-else-if="selectedOnlineSubmission.status === 'approved'" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-xs flex items-center gap-1.5">
                            <i class="pi pi-check-circle text-emerald-600"></i>
                            <span>Approved &amp; Synced (Employee ID: {{ selectedOnlineSubmission.synced_employee_id }})</span>
                        </div>

                        <div v-else-if="selectedOnlineSubmission.status === 'rejected'" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 font-bold text-xs flex items-center gap-1.5">
                            <i class="pi pi-times-circle text-rose-600"></i>
                            <span>Rejected: {{ selectedOnlineSubmission.rejection_reason || 'Incomplete submission' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.online-header-card {
    background: linear-gradient(135deg, #1A237E 0%, #0D47A1 50%, #1565C0 100%) !important;
    color: #ffffff !important;
}
.online-header-card h1 {
    color: #ffffff !important;
}
.online-header-card p {
    color: #E0E7FF !important;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
</style>
