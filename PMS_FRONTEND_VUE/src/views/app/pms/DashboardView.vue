<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useUsersStore } from '@/stores/user';
import VueApexCharts from "vue3-apexcharts";
import { showAlert } from '@/helpers/essential';

const userstore = useUsersStore();
const { authtoken } = userstore;

const stats = ref({
    total_employees: 0,
    total_goals: 0,
    completed_goals: 0,
    pending_appraisals: 0,
    approved_appraisals: 0,
    completion_rate: 0
});

const recentGoals = ref([]);
const weeklyProgress = ref({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    data: [65, 59, 80, 81, 56, 55, 40]
});

const loading = ref(true);

const fetchDashboardData = async () => {
    try {
        const response = await axios.get('pms/dashboard');
        const { data } = response.data;
        stats.value = data.stats;
        recentGoals.value = data.recent_goals;
        loading.value = false;
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboardData();
});

// --- Main Area Chart (Performance Trends) ---
const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
        zoom: { enabled: false }
    },
    colors: ['#3b82f6'], // Blue line like reference
    stroke: { 
        curve: 'smooth', 
        width: 3 
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: weeklyProgress.value.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { 
            style: { colors: '#94a3b8', fontSize: '12px' } 
        }
    },
    yaxis: { 
        show: true,
        labels: {
            style: { colors: '#94a3b8', fontSize: '12px' },
             formatter: (value) => { return value + "%" }
        }
    },
    grid: { 
        borderColor: '#f1f5f9',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },   
        yaxis: { lines: { show: true } },  
    },
    tooltip: { 
        theme: 'light',
        y: { formatter: function (val) { return val + "%" } }
    },
    markers: { size: 0, hover: { size: 6 } }
}));

const series = computed(() => [{
    name: 'Performance',
    data: weeklyProgress.value.data
}]);

// --- Side Bar Chart (Activity) ---
const barChartOptions = {
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    plotOptions: {
        bar: { borderRadius: 6, columnWidth: '40%', distributed: false }
    },
    dataLabels: { enabled: false },
    colors: ['#3b82f6'],
    xaxis: {
        categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
    },
    yaxis: { show: false },
    grid: { show: false },
    tooltip: { theme: 'light' }
};

const barSeries = [{
    name: 'Activity',
    data: [40, 70, 50, 90, 60, 80, 50] // Mock activity data
}];

// --- Radial Bar Chart (Completion) ---
const radialOptions = computed(() => ({
    chart: { type: 'radialBar', fontFamily: 'Inter, sans-serif' },
    plotOptions: {
        radialBar: {
            hollow: { size: '65%' },
            track: { background: '#f1f5f9', strokeWidth: '100%' },
            dataLabels: {
                show: true,
                name: { show: false },
                value: {
                    offsetY: 8,
                    fontSize: '24px',
                    fontWeight: 700,
                    color: '#1e293b',
                    formatter: function (val) { return val + "%" }
                }
            }
        }
    },
    colors: ['#10b981'], // Green like reference
    stroke: { lineCap: 'round' }
}));

const radialSeries = computed(() => [stats.value.completion_rate || 0]);

// Mock Top Employees for Leaderboard
const topEmployees = ref([
    { id: 1, name: 'Sarah Jenkins', role: 'Senior Developer', score: 98, avatar: 'SJ', color: 'bg-purple-100 text-purple-600' },
    { id: 2, name: 'Michael Chen', role: 'Product Manager', score: 95, avatar: 'MC', color: 'bg-blue-100 text-blue-600' },
    { id: 3, name: 'Emma Wilson', role: 'UX Designer', score: 92, avatar: 'EW', color: 'bg-pink-100 text-pink-600' },
    { id: 4, name: 'James Rod', role: 'Sales Lead', score: 89, avatar: 'JR', color: 'bg-orange-100 text-orange-600' },
]);

</script>

<template>
    <div class="min-h-full pb-8 bg-gray-50/50">
        <!-- Dashboard Header -->
        <div class="dashboard-header mb-8 rounded-2xl p-6 shadow-lg shadow-purple-200"
             style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                 <div>
                     <h1 class="text-3xl font-extrabold text-white flex items-center gap-3 mb-2">
                        Dashboard Overview
                        <button 
                             v-if="!loading"
                            @click="fetchDashboardData"
                            class="w-8 h-8 rounded-full bg-white/20 text-white hover:bg-white/30 flex items-center justify-center transition-all shadow-sm border border-white/10 cursor-pointer"
                            title="Refresh Data"
                        >
                            <i class="pi pi-refresh text-sm" :class="{'pi-spin': loading}"></i>
                        </button>
                    </h1>
                    <p class="text-sm font-medium text-purple-100">Real-time HR analytics and demographics</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-4 py-2 flex items-center gap-2 text-sm text-white shadow-sm cursor-pointer hover:bg-white/20 transition-colors">
                        <i class="pi pi-calendar text-purple-200"></i>
                         <span class="font-bold">Jan 1, 2026 - Feb 1, 2026</span>
                        <i class="pi pi-chevron-down text-xs ml-1 text-purple-200"></i>
                    </div>
                     <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-4 py-2 flex items-center gap-2 text-sm text-white shadow-sm cursor-pointer hover:bg-white/20 transition-colors">
                        <span class="font-bold">Last 30 days</span>
                        <i class="pi pi-chevron-down text-xs ml-1 text-purple-200"></i>
                    </div>
                    <button class="bg-white text-purple-800 hover:bg-purple-50 px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-black/10 transition-colors flex items-center gap-2">
                        <i class="pi pi-download text-xs text-purple-600"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Completion Card -->
             <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-purple-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-purple-500/20"
                 style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex justify-between items-start z-10 relative">
                    <span class="text-purple-200 font-bold text-sm uppercase tracking-wider">Completion Rate</span>
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                        <i class="pi pi-chart-pie text-white"></i>
                    </div>
                </div>
                <div class="z-10 relative mt-2">
                    <div class="flex items-end gap-3 mb-1">
                        <span class="text-4xl font-extrabold text-white">{{ stats.completion_rate }}%</span>
                        <span class="text-[11px] font-bold text-emerald-400 bg-emerald-500/20 border border-emerald-500/30 px-1.5 py-0.5 rounded flex items-center gap-0.5 mb-1.5">
                            <i class="pi pi-arrow-up text-[9px]"></i> 12.5%
                        </span>
                    </div>
                    <p class="text-[11px] text-purple-200 font-medium">vs. last period</p>
                </div>
            </div>

            <!-- Happiness Card -->
             <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-pink-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-pink-500/20"
                 style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                 <div class="flex justify-between items-start relative z-10">
                    <span class="text-pink-100 font-bold text-sm uppercase tracking-wider">Happiness Score</span>
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                        <i class="pi pi-heart text-white"></i>
                    </div>
                </div>
                <div class="relative z-10 mt-2">
                    <div class="flex items-end gap-3 mb-1">
                         <span class="text-4xl font-extrabold text-white">4.2</span>
                         <span class="text-[11px] font-bold text-emerald-300 bg-emerald-400/20 border border-emerald-400/30 px-1.5 py-0.5 rounded flex items-center gap-0.5 mb-1.5">
                            <i class="pi pi-arrow-up text-[9px]"></i> 8.4%
                        </span>
                    </div>
                    <p class="text-[11px] text-pink-100 font-medium">vs. last period</p>
                </div>
            </div>

            <!-- Active Goals Card -->
              <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-orange-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-orange-500/20"
                 style="background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                 <div class="flex justify-between items-start relative z-10">
                    <span class="text-orange-100 font-bold text-sm uppercase tracking-wider">Active Goals</span>
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                         <i class="pi pi-flag text-white"></i>
                    </div>
                </div>
                <div class="relative z-10 mt-2">
                    <div class="flex items-end gap-3 mb-1">
                         <span class="text-4xl font-extrabold text-white">{{ stats.total_goals }}</span>
                         <span class="text-[11px] font-bold text-red-200 bg-red-400/20 border border-red-400/30 px-1.5 py-0.5 rounded flex items-center gap-0.5 mb-1.5">
                            <i class="pi pi-arrow-down text-[9px]"></i> 2.1%
                        </span>
                    </div>
                    <p class="text-[11px] text-orange-100 font-medium">vs. last period</p>
                </div>
            </div>

            <!-- Completed Goals Card -->
             <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-teal-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-teal-500/20"
                 style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                 <div class="flex justify-between items-start relative z-10">
                    <span class="text-teal-100 font-bold text-sm uppercase tracking-wider">Goals Completed</span>
                     <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                        <i class="pi pi-check-circle text-white"></i>
                    </div>
                </div>
                <div class="relative z-10 mt-2">
                    <div class="flex items-end gap-3 mb-1">
                         <span class="text-4xl font-extrabold text-white">{{ stats.completed_goals }}</span>
                         <span class="text-[11px] font-bold text-emerald-200 bg-emerald-400/20 border border-emerald-400/30 px-1.5 py-0.5 rounded flex items-center gap-0.5 mb-1.5">
                            <i class="pi pi-arrow-up text-[9px]"></i> 4.4%
                        </span>
                    </div>
                    <p class="text-[11px] text-teal-100 font-medium">vs. last period</p>
                </div>
            </div>
        </div>

        <!-- Main Chart & Side Widgets -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Main Performance Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-gray-900 font-bold text-lg">Total Performance</h3>
                        <div class="flex items-center gap-4 mt-2">
                             <h2 class="text-3xl font-bold text-gray-900">{{ stats.completion_rate }}%</h2>
                             <span class="text-xs font-bold text-green-700 bg-green-50 border border-green-100 px-2 py-1 rounded-full flex items-center gap-1">
                                <i class="pi pi-arrow-up text-[10px]"></i> 24.4% vs last period
                             </span>
                        </div>
                    </div>
                </div>
                <div class="w-full h-80">
                     <VueApexCharts width="100%" height="100%" :options="chartOptions" :series="series" />
                </div>
            </div>

            <!-- Side Widgets Column -->
            <div class="space-y-6">
                 <!-- Day Activity Widget -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                     <div class="flex justify-between items-center mb-4">
                        <h3 class="text-gray-900 font-bold text-sm">Most Active Days</h3>
                        <i class="pi pi-ellipsis-h text-gray-400 cursor-pointer hover:text-gray-600"></i>
                    </div>
                    <div class="h-48 flex items-center justify-center -ml-2">
                        <VueApexCharts width="100%" height="100%" :options="barChartOptions" :series="barSeries" />
                    </div>
                </div>

                <!-- Repeat Customer (Completion Rate) Widget -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                     <div class="flex justify-between items-center mb-2">
                        <h3 class="text-gray-900 font-bold text-sm">Completion Rate</h3>
                        <i class="pi pi-ellipsis-h text-gray-400 cursor-pointer hover:text-gray-600"></i>
                    </div>
                     <div class="h-48 flex items-center justify-center relative">
                         <VueApexCharts width="100%" height="100%" :options="radialOptions" :series="radialSeries" />
                         <div class="absolute bottom-4 text-center">
                             <p class="text-[10px] text-gray-500 font-medium">On track for 80% target</p>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Table & AI Assistant -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Best Performing Products Table (Mapped to Recent Goals) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                    <h3 class="text-gray-900 font-bold text-lg">Recent Strategic Goals</h3>
                    <i class="pi pi-ellipsis-h text-gray-400 cursor-pointer hover:text-gray-600"></i>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-gray-100 bg-gray-50/50">
                                <th class="px-6 py-4 font-bold uppercase tracking-wider pl-8">ID</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider">Goal / Employee</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider">Progress</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider">Rating</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="goal in recentGoals.slice(0, 5)" :key="goal.id" class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 text-xs font-bold text-gray-400 pl-8">#{{ goal.id }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 text-gray-500 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                            <i class="pi pi-flag"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800 line-clamp-1 max-w-[180px]">{{ goal.title }}</p>
                                            <p class="text-xs text-gray-500">{{ goal.user?.name || 'Unassigned' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-gray-600">
                                    {{ Math.round((goal.actual / goal.target) * 100) }}% <span class="text-gray-400 font-normal">completed</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded border"
                                        :class="goal.actual >= goal.target ? 'text-green-700 bg-green-50 border-green-200' : 'text-orange-700 bg-orange-50 border-orange-200'">
                                        {{ goal.actual >= goal.target ? 'Done' : 'Active' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center text-xs font-bold text-gray-600">
                                        <i class="pi pi-star-fill text-yellow-400 mr-1"></i>
                                        {{ (Math.random() * (5.0 - 4.0) + 4.0).toFixed(1) }} <span class="text-gray-300 ml-1 font-normal">/5.0</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="recentGoals.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">No recent goals found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Employee Leaderboard Widget -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                    <h3 class="text-gray-900 font-bold text-sm">Employee Leaderboard</h3>
                    <router-link to="/app/pms/appraisal" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</router-link>
                </div>
                
                <div class="p-4 space-y-4">
                    <div v-for="(emp, index) in topEmployees" :key="emp.id" class="flex items-center gap-4 p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer group">
                        <div class="relative">
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs', emp.color]">
                                {{ emp.avatar }}
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-100 text-[10px] font-bold"
                                :class="index === 0 ? 'text-yellow-500' : (index === 1 ? 'text-gray-400' : (index === 2 ? 'text-orange-400' : 'text-gray-300'))">
                                {{ index + 1 }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ emp.name }}</h4>
                            <p class="text-xs text-gray-500">{{ emp.role }}</p>
                        </div>
                        <div class="text-right">
                            <span class="block text-sm font-bold text-gray-900">{{ emp.score }}</span>
                            <span class="text-[10px] text-gray-400">Score</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <button class="w-full py-2 text-center text-xs font-bold text-gray-600 hover:text-blue-600 transition-colors flex items-center justify-center gap-2">
                        <span>See complete ranking</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Custom font inter import if not global */
/* @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap'); */

/* .font-inter {
    font-family: 'Inter', sans-serif;
} */
</style>
