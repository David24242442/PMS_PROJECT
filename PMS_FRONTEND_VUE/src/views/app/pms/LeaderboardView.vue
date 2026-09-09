<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';

const userstore = useUsersStore();
const loading = ref(true);
const employees = ref([]);
const activeTab = ref('rating'); // 'rating' | 'completion'
const selectedEmployee = ref(null);
const showModal = ref(false);
const searchQuery = ref('');

const fetchLeaderboard = async () => {
    userstore.setIsLoading(true);
    loading.value = true;
    try {
        const response = await axios.get('pms/leaderboard');
        employees.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching leaderboard:', error);
    } finally {
        loading.value = false;
        userstore.setIsLoading(false);
    }
};

onMounted(() => fetchLeaderboard());

const filteredEmployees = computed(() => {
    let list = [...employees.value];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(e => e.name.toLowerCase().includes(q) || (e.department || '').toLowerCase().includes(q));
    }
    if (activeTab.value === 'rating') {
        list.sort((a, b) => b.avg_rating - a.avg_rating);
    } else {
        list.sort((a, b) => b.completion_pct - a.completion_pct);
    }
    return list;
});

const openEmployeeModal = (emp) => {
    selectedEmployee.value = emp;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedEmployee.value = null;
};

const getRankBadge = (index) => {
    if (index === 0) return { icon: 'pi-trophy', bg: 'bg-yellow-100 text-yellow-700 border-yellow-300', ring: 'ring-yellow-400' };
    if (index === 1) return { icon: 'pi-trophy', bg: 'bg-gray-100 text-gray-500 border-gray-300', ring: 'ring-gray-400' };
    if (index === 2) return { icon: 'pi-trophy', bg: 'bg-orange-100 text-orange-600 border-orange-300', ring: 'ring-orange-400' };
    return { icon: 'pi-user', bg: 'bg-slate-50 text-slate-400 border-slate-200', ring: 'ring-slate-300' };
};

const getStatusPill = (status) => {
    const map = {
        'completed': 'bg-green-50 text-green-700 border-green-200',
        'approved': 'bg-blue-50 text-blue-700 border-blue-200',
        'review_completed': 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'submitted': 'bg-amber-50 text-amber-700 border-amber-200',
        'in_progress': 'bg-orange-50 text-orange-700 border-orange-200',
        'draft': 'bg-gray-50 text-gray-500 border-gray-200',
    };
    return map[status] || 'bg-gray-50 text-gray-500 border-gray-200';
};

const getAvatarColor = (index) => {
    const colors = [
        'bg-indigo-100 text-indigo-700',
        'bg-pink-100 text-pink-700',
        'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-700',
        'bg-purple-100 text-purple-700',
        'bg-cyan-100 text-cyan-700',
        'bg-rose-100 text-rose-700',
        'bg-teal-100 text-teal-700',
    ];
    return colors[index % colors.length];
};

const getRatingStars = (rating) => {
    const full = Math.floor(rating);
    const half = rating - full >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;
    return { full, half, empty };
};
</script>

<template>
    <div class="min-h-full pb-6 bg-gray-50/50">
        <!-- Header -->
        <div class="mb-6 rounded-2xl p-5 shadow-lg shadow-purple-200"
             style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                <div>
                    <h1 class="text-xl font-black text-white flex items-center gap-2 mb-1">
                        <i class="pi pi-trophy text-yellow-300"></i>
                        Employee Leaderboard
                    </h1>
                    <p class="text-xs font-bold text-purple-100 uppercase tracking-normal opacity-80">
                        Performance rankings for {{ new Date().getFullYear() }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-purple-300 text-xs"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search employees..."
                            class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-white/30 w-48" />
                    </div>
                    <router-link to="/pms/dashboard"
                        class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3 py-1.5 text-xs text-white font-bold hover:bg-white/20 transition-colors flex items-center gap-1.5">
                        <i class="pi pi-arrow-left text-[10px]"></i> Dashboard
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                        <i class="pi pi-users text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Total Ranked</p>
                        <p class="text-xl font-black text-gray-900">{{ employees.length }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                        <i class="pi pi-star-fill text-amber-500"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Avg Rating</p>
                        <p class="text-xl font-black text-gray-900">
                            {{ employees.length ? (employees.reduce((s, e) => s + e.avg_rating, 0) / employees.length).toFixed(1) : '0.0' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                        <i class="pi pi-check-circle text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Avg Completion</p>
                        <p class="text-xl font-black text-gray-900">
                            {{ employees.length ? (employees.reduce((s, e) => s + e.completion_pct, 0) / employees.length).toFixed(0) : '0' }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Switcher -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center gap-2">
                <button @click="activeTab = 'rating'"
                    :class="['px-4 py-2 rounded-xl text-xs font-bold transition-all', activeTab === 'rating' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                    <i class="pi pi-star-fill mr-1.5 text-[10px]"></i> By Rating
                </button>
                <button @click="activeTab = 'completion'"
                    :class="['px-4 py-2 rounded-xl text-xs font-bold transition-all', activeTab === 'completion' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                    <i class="pi pi-chart-bar mr-1.5 text-[10px]"></i> By Goal Completion
                </button>
                <div class="flex-1"></div>
                <span class="text-[10px] text-gray-400 font-bold">{{ filteredEmployees.length }} employees</span>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="p-12 flex items-center justify-center">
                <i class="pi pi-spin pi-spinner text-2xl text-indigo-400"></i>
            </div>

            <!-- Empty -->
            <div v-else-if="filteredEmployees.length === 0" class="p-12 text-center">
                <i class="pi pi-users text-4xl text-gray-300 mb-3"></i>
                <p class="text-sm text-gray-400">No employees found</p>
            </div>

            <!-- Leaderboard Table -->
            <div v-else class="divide-y divide-gray-50">
                <div v-for="(emp, index) in filteredEmployees" :key="emp.user_id"
                    @click="openEmployeeModal(emp)"
                    class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition-colors cursor-pointer group">

                    <!-- Rank -->
                    <div class="w-8 text-center flex-shrink-0">
                        <div v-if="index < 3" :class="['w-7 h-7 rounded-full flex items-center justify-center border text-[10px] font-black', getRankBadge(index).bg]">
                            <i :class="['pi text-[10px]', getRankBadge(index).icon]"></i>
                        </div>
                        <span v-else class="text-sm font-bold text-gray-400">{{ index + 1 }}</span>
                    </div>

                    <!-- Avatar -->
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0', getAvatarColor(index)]">
                        {{ emp.name.substring(0, 1).toUpperCase() }}
                    </div>

                    <!-- Name & Dept -->
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">{{ emp.name }}</h4>
                        <p class="text-xs text-gray-500 truncate">{{ emp.department || emp.job_title || 'Staff' }}</p>
                    </div>

                    <!-- Goals Count -->
                    <div class="hidden md:block text-center px-4">
                        <p class="text-sm font-bold text-gray-800">{{ emp.completed_goals }}/{{ emp.total_goals }}</p>
                        <p class="text-[10px] text-gray-400">Goals</p>
                    </div>

                    <!-- Completion Bar -->
                    <div class="hidden md:block w-32">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all duration-500"
                                    :class="emp.completion_pct >= 80 ? 'bg-green-500' : emp.completion_pct >= 50 ? 'bg-amber-500' : 'bg-red-400'"
                                    :style="{ width: emp.completion_pct + '%' }"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-600 w-10 text-right">{{ emp.completion_pct }}%</span>
                        </div>
                    </div>

                    <!-- Rating -->
                    <div class="text-right flex-shrink-0 w-20">
                        <div class="flex items-center justify-end gap-1">
                            <i class="pi pi-star-fill text-yellow-400 text-xs"></i>
                            <span class="text-sm font-bold text-gray-900">{{ emp.avg_rating }}</span>
                            <span class="text-[10px] text-gray-400">/5</span>
                        </div>
                    </div>

                    <!-- Chevron -->
                    <i class="pi pi-chevron-right text-gray-300 group-hover:text-indigo-400 text-xs transition-colors flex-shrink-0"></i>
                </div>
            </div>
        </div>

        <!-- Employee Goals Detail Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden z-10 animate-scale-in">
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-100 flex items-center gap-4"
                     style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                    <div :class="['w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg', 'bg-white/20 text-white']">
                        {{ selectedEmployee?.name?.substring(0, 1).toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <h3 class="text-white font-black text-base">{{ selectedEmployee?.name }}</h3>
                        <p class="text-purple-200 text-xs">{{ selectedEmployee?.department || selectedEmployee?.job_title || 'Staff' }}</p>
                    </div>
                    <div class="flex items-center gap-4 text-white">
                        <div class="text-center">
                            <p class="text-lg font-black">{{ selectedEmployee?.avg_rating }}</p>
                            <p class="text-[10px] text-purple-200">Rating</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-black">{{ selectedEmployee?.completion_pct }}%</p>
                            <p class="text-[10px] text-purple-200">Complete</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto" style="max-height: calc(85vh - 100px);">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-black text-gray-900">Goals Summary</h4>
                        <span class="text-[10px] font-bold text-gray-400">{{ selectedEmployee?.total_goals }} total goals</span>
                    </div>

                    <div v-if="!selectedEmployee?.goals?.length" class="py-8 text-center text-gray-400 text-sm">
                        No goals found for this employee.
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="goal in selectedEmployee.goals" :key="goal.id"
                            class="border border-gray-100 rounded-xl p-4 hover:border-indigo-200 hover:bg-indigo-50/20 transition-all">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900">{{ goal.title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ goal.category }}</p>
                                </div>
                                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded border whitespace-nowrap', getStatusPill(goal.status)]">
                                    {{ goal.status?.replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span v-if="goal.due_date" class="flex items-center gap-1">
                                    <i class="pi pi-calendar text-[10px]"></i> Due: {{ goal.due_date }}
                                </span>
                                <span v-if="goal.rating" class="flex items-center gap-1">
                                    <i class="pi pi-star-fill text-yellow-400 text-[10px]"></i> {{ goal.rating }}/5
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-chart-bar text-[10px]"></i> {{ goal.actual || 0 }}/{{ goal.target }} progress
                                </span>
                            </div>
                            <!-- Progress bar -->
                            <div class="mt-2 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all"
                                    :class="(goal.actual || 0) >= goal.target ? 'bg-green-500' : 'bg-indigo-500'"
                                    :style="{ width: Math.min(100, goal.target > 0 ? ((goal.actual || 0) / goal.target) * 100 : 0) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scale-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.animate-scale-in { animation: scale-in 0.2s ease-out; }

/* Dark mode */
body.dark-mode .bg-white { background: var(--surface-card) !important; }
body.dark-mode .border-gray-200,
body.dark-mode .border-gray-100 { border-color: var(--border-color) !important; }
body.dark-mode .text-gray-900 { color: var(--text-color) !important; }
body.dark-mode .text-gray-500,
body.dark-mode .text-gray-400 { color: var(--text-secondary) !important; }
body.dark-mode .bg-gray-50\/50,
body.dark-mode .bg-gray-50\/80 { background: var(--surface-ground) !important; }
body.dark-mode .hover\:bg-gray-50\/80:hover { background: var(--surface-ground) !important; }
body.dark-mode .bg-gray-100 { background: color-mix(in srgb, var(--surface-card) 80%, white 5%) !important; }
body.dark-mode .divide-gray-50 > :not(:last-child) { border-color: var(--border-color) !important; }
</style>
