<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import VueApexCharts from "vue3-apexcharts";
import { showAlert } from '@/helpers/essential';

const router = useRouter();

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
const showEmployeeModal = ref(false);
const selectedDashEmployee = ref(null);
const selectedChartPoint = ref(null); // for showing clicked chart data inline

const openDashEmployeeModal = (emp) => {
    selectedDashEmployee.value = {
        name: emp.name,
        role: emp.role,
        rating: emp.rating,
        goals: emp.goals || [],
    };
    showEmployeeModal.value = true;
};

const closeDashModal = () => {
    showEmployeeModal.value = false;
    selectedDashEmployee.value = null;
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

const fetchDashboardData = async () => {
    userstore.setIsLoading(true);
    loading.value = true;
    try {
        const response = await axios.get('pms/dashboard');
        const { data } = response.data;
        stats.value = data.stats;
        recentGoals.value = data.recent_goals;
        
        if (data.weekly_progress) {
            weeklyProgress.value = data.weekly_progress;
        }
        
        if (data.top_employees) {
            topEmployeesRaw.value = data.top_employees;
        }

        loading.value = false;
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
        loading.value = false;
    } finally {
        userstore.setIsLoading(false);
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
        zoom: { enabled: false },
        events: {
            dataPointSelection: function(event, chartContext, config) {
                const idx = config.dataPointIndex;
                const val = config.w.config.series[0].data[idx];
                const day = config.w.globals.categoryLabels[idx] || config.w.globals.labels[idx] || '';
                selectedChartPoint.value = {
                    chart: 'performance',
                    day: day,
                    value: val,
                    label: val + '% performance',
                    detail: `${val} goals created on ${day}`
                };
            }
        }
    },
    colors: ['#3b82f6'],
    stroke: { curve: 'smooth', width: 3 },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] }
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: weeklyProgress.value.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#94a3b8', fontSize: '12px' } }
    },
    yaxis: {
        show: true,
        labels: {
            style: { colors: '#94a3b8', fontSize: '12px' },
            formatter: (value) => { return value + "%" }
        }
    },
    grid: {
        borderColor: '#f1f5f9', strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        theme: 'light',
        custom: function({ series, seriesIndex, dataPointIndex, w }) {
            const val = series[seriesIndex][dataPointIndex];
            const day = w.globals.categoryLabels[dataPointIndex] || w.globals.labels[dataPointIndex] || '';
            return `<div style="padding: 10px 14px; font-family: Inter, sans-serif; border-radius: 8px;">
                <p style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">${day}</p>
                <p style="font-size: 18px; font-weight: 900; color: #1e293b; margin-bottom: 2px;">${val}%</p>
                <p style="font-size: 10px; color: #64748b;">Performance score</p>
                <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #f1f5f9; font-size: 10px; color: #3b82f6; font-weight: 600;">
                    Click to pin details
                </div>
            </div>`;
        }
    },
    markers: {
        size: 4,
        colors: ['#3b82f6'],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: { size: 8, sizeOffset: 3 },
        discrete: []
    },
    states: {
        active: { filter: { type: 'none' } },
        hover: { filter: { type: 'lighten', value: 0.1 } }
    }
}));

const series = computed(() => [{
    name: 'Performance',
    data: weeklyProgress.value.data
}]);

// --- Side Bar Chart (Activity) ---
const barChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
        events: {
            dataPointSelection: function(event, chartContext, config) {
                const idx = config.dataPointIndex;
                const val = config.w.config.series[0].data[idx];
                const day = config.w.globals.categoryLabels[idx] || config.w.globals.labels[idx] || '';
                selectedChartPoint.value = {
                    chart: 'activity',
                    day: day,
                    value: val,
                    label: val + ' goals created',
                    detail: `Activity on ${day}: ${val} goal(s) were created`
                };
            }
        }
    },
    plotOptions: {
        bar: { borderRadius: 6, columnWidth: '40%', distributed: false }
    },
    dataLabels: { enabled: false },
    colors: ['#3b82f6'],
    xaxis: {
        categories: weeklyProgress.value.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
    },
    yaxis: { show: false },
    grid: { show: false },
    tooltip: {
        theme: 'light',
        custom: function({ series, seriesIndex, dataPointIndex, w }) {
            const val = series[seriesIndex][dataPointIndex];
            const day = w.globals.categoryLabels[dataPointIndex] || w.globals.labels[dataPointIndex] || '';
            return `<div style="padding: 10px 14px; font-family: Inter, sans-serif; border-radius: 8px;">
                <p style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">${day}</p>
                <p style="font-size: 16px; font-weight: 900; color: #1e293b;">${val} goals</p>
                <p style="font-size: 10px; color: #64748b;">Goals created on this day</p>
                <div style="margin-top: 4px; font-size: 10px; color: #3b82f6; font-weight: 600;">Click to pin details</div>
            </div>`;
        }
    },
    states: {
        active: {
            allowMultipleDataPointsSelection: false,
            filter: { type: 'darken', value: 0.3 }
        }
    }
}));

const barSeries = computed(() => [{
    name: 'Goals Created',
    data: weeklyProgress.value.data
}]);

// --- Radial Bar Chart (Completion) ---
const showRadialDetail = ref(false);

const toggleRadialDetail = () => {
    showRadialDetail.value = !showRadialDetail.value;
};

const radialOptions = computed(() => ({
    chart: {
        type: 'radialBar',
        fontFamily: 'Inter, sans-serif',
        events: {
            click: function() {
                toggleRadialDetail();
            }
        }
    },
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
    colors: ['#10b981'],
    stroke: { lineCap: 'round' }
}));

const radialSeries = computed(() => [stats.value.completion_rate || 0]);

// Top Employees for Leaderboard — from API response
const topEmployeesRaw = ref([]);
const topEmployees = computed(() => {
    const avatarColors = [
        'bg-indigo-100 text-indigo-700',
        'bg-pink-100 text-pink-700',
        'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-700',
        'bg-purple-100 text-purple-700',
    ];
    return topEmployeesRaw.value.map((emp, i) => ({
        id: emp.user_id,
        name: emp.name,
        role: emp.job_title || emp.department || 'Staff Member',
        rating: emp.avg_rating,
        score: emp.avg_rating,
        avatar: emp.name?.substring(0, 1).toUpperCase() || '?',
        color: avatarColors[i % avatarColors.length],
        goals: emp.goals || [],
    }));
});

const parseSmart = (data) => {
    if (!data) return {};
    if (typeof data === 'object' && !Array.isArray(data)) return data;
    try {
        return typeof data === 'string' ? JSON.parse(data) : data;
    } catch (e) {
        return {};
    }
};

const calculateAverageRating = (appraisal) => {
    if (!appraisal) return '0.0';
    // Fallback for flat dashboard models referencing ratings directly
    if (appraisal.line_manager_rating || appraisal.manager_rating) {
        return parseFloat(appraisal.line_manager_rating || appraisal.manager_rating).toFixed(1);
    }
    const data = parseSmart(appraisal.appraisal_data || appraisal.goal_data);
    const competencies = data?.competencies || [];
    if (competencies.length === 0) return appraisal.overall_rating || '0.0';
    const sum = competencies.reduce((acc, c) => acc + (parseFloat(c.managerRating) || 0), 0);
    return (sum / competencies.length).toFixed(1);
};

const getTimeProgress = (goal) => {
    if (!goal || !goal.completion_date) return 0;
    const start = new Date(goal.submitted_at || goal.created_at || `2024-01-01`);
    const end = new Date(goal.completion_date);
    const today = new Date();
    if (today >= end) return 100;
    if (today <= start) return 0;
    return Math.min(100, Math.max(0, Math.round(((today - start) / (end - start)) * 100)));
};

</script>

<template>
    <div class="min-h-full pb-6 bg-gray-50/50">
        <!-- Dashboard Header -->
        <div class="dashboard-header mb-6 rounded-2xl p-5 shadow-lg shadow-purple-200 animate-slide-up"
             style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                 <div>
                     <h1 class="text-xl font-black text-white flex items-center gap-2 mb-1">
                        Dashboard Overview
                        <button 
                             v-if="!loading"
                            @click="fetchDashboardData"
                            class="w-7 h-7 rounded-full bg-white/20 text-white hover:bg-white/30 flex items-center justify-center transition-all shadow-sm border border-white/10 cursor-pointer"
                            title="Refresh Data"
                        >
                            <i class="pi pi-refresh text-xs" :class="{'pi-spin': loading}"></i>
                        </button>
                    </h1>
                    <p class="text-xs font-black text-purple-100 uppercase tracking-normal opacity-80">Real-time HR analytics and demographics</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3 py-1.5 flex items-center gap-2 text-xs text-white shadow-sm">
                        <i class="pi pi-calendar text-purple-200 text-xs"></i>
                         <span class="font-bold">{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
                    </div>
                     <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3 py-1.5 flex items-center gap-2 text-xs text-white shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="font-bold">Live</span>
                    </div>
                    <button class="bg-white text-purple-800 hover:bg-purple-50 px-4 py-1.5 rounded-xl text-xs font-bold shadow-md shadow-black/10 transition-colors flex items-center gap-1.5">
                        <i class="pi pi-download text-[10px] text-purple-600"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Completion Card -->
             <div class="relative overflow-hidden rounded-2xl p-5 shadow-md text-white transform hover:-translate-y-0.5 transition-transform duration-300 border border-purple-500/20 animate-scale-in"
                 style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex justify-between items-start z-10 relative">
                    <span class="text-purple-200 font-black text-[10px] uppercase tracking-normal">Completion Rate</span>
                    <div class="bg-white/20 p-1.5 rounded-lg backdrop-blur-sm">
                        <i class="pi pi-chart-pie text-white text-xs"></i>
                    </div>
                </div>
                <div class="z-10 relative mt-1.5">
                    <div class="flex items-end gap-2 mb-0.5">
                        <span class="text-2xl font-black text-white">{{ stats.completion_rate }}%</span>
                    </div>
                    <p class="text-[10px] text-purple-200 font-bold">{{ stats.completed_goals }} of {{ stats.total_goals }} goals</p>
                </div>
            </div>

            <!-- Happiness Card -->
             <div class="relative overflow-hidden rounded-2xl p-5 shadow-md text-white transform hover:-translate-y-0.5 transition-transform duration-300 border border-pink-500/20 animate-scale-in"
                 style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%) !important; animation-delay: 0.05s;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                 <div class="flex justify-between items-start relative z-10">
                    <span class="text-pink-100 font-black text-[10px] uppercase tracking-normal">Happiness Score</span>
                    <div class="bg-white/20 p-1.5 rounded-lg backdrop-blur-sm">
                        <i class="pi pi-heart text-white text-xs"></i>
                    </div>
                </div>
                <div class="relative z-10 mt-1.5">
                    <div class="flex items-end gap-2 mb-0.5">
                         <span class="text-2xl font-black text-white">{{ topEmployeesRaw.length > 0 ? (topEmployeesRaw.reduce((s, e) => s + e.avg_rating, 0) / topEmployeesRaw.length).toFixed(1) : '0.0' }}</span>
                    </div>
                    <p class="text-[10px] text-pink-100 font-bold">avg. employee rating</p>
                </div>
            </div>

            <!-- Active Goals Card -->
              <div class="relative overflow-hidden rounded-2xl p-5 shadow-md text-white transform hover:-translate-y-0.5 transition-transform duration-300 border border-orange-500/20 animate-scale-in"
                 style="background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important; animation-delay: 0.1s;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                 <div class="flex justify-between items-start relative z-10">
                    <span class="text-orange-100 font-black text-[10px] uppercase tracking-normal">Active Goals</span>
                    <div class="bg-white/20 p-1.5 rounded-lg backdrop-blur-sm">
                         <i class="pi pi-flag text-white text-xs"></i>
                    </div>
                </div>
                <div class="relative z-10 mt-1.5">
                    <div class="flex items-end gap-2 mb-0.5">
                         <span class="text-2xl font-black text-white">{{ stats.total_goals }}</span>
                    </div>
                    <p class="text-[10px] text-orange-100 font-bold">{{ stats.pending_appraisals }} pending appraisals</p>
                </div>
            </div>

            <!-- Completed Goals Card -->
             <div class="relative overflow-hidden rounded-2xl p-5 shadow-md text-white transform hover:-translate-y-0.5 transition-transform duration-300 border border-teal-500/20 animate-scale-in"
                 style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%) !important; animation-delay: 0.15s;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                 <div class="flex justify-between items-start relative z-10">
                    <span class="text-teal-100 font-black text-[10px] uppercase tracking-normal">Goals Completed</span>
                     <div class="bg-white/20 p-1.5 rounded-lg backdrop-blur-sm">
                        <i class="pi pi-check-circle text-white text-xs"></i>
                    </div>
                </div>
                <div class="relative z-10 mt-1.5">
                    <div class="flex items-end gap-2 mb-0.5">
                         <span class="text-2xl font-black text-white">{{ stats.completed_goals }}</span>
                    </div>
                    <p class="text-[10px] text-teal-100 font-bold">{{ stats.approved_appraisals }} approved</p>
                </div>
            </div>
        </div>

        <!-- Main Chart & Side Widgets -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            
            <!-- Main Performance Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-gray-900 font-black text-sm">Total Performance</h3>
                        <div class="flex items-center gap-3 mt-1">
                             <h2 class="text-xl font-black text-gray-900">{{ stats.completion_rate }}%</h2>
                             <span class="text-[10px] font-bold text-green-700 bg-green-50 border border-green-100 px-1.5 py-0.5 rounded-full flex items-center gap-1">
                                <i class="pi pi-arrow-up text-[8px]"></i> 24.4% vs last period
                             </span>
                        </div>
                    </div>
                </div>
                <div class="w-full h-72">
                     <VueApexCharts width="100%" height="100%" :options="chartOptions" :series="series" />
                </div>
                <!-- Pinned chart detail card -->
                <div v-if="selectedChartPoint && selectedChartPoint.chart === 'performance'"
                     class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="pi pi-chart-line text-blue-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-blue-900">{{ selectedChartPoint.day }}: {{ selectedChartPoint.label }}</p>
                            <p class="text-xs text-blue-600">{{ selectedChartPoint.detail }}</p>
                        </div>
                    </div>
                    <button @click="selectedChartPoint = null" class="text-blue-400 hover:text-blue-600 p-1">
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Side Widgets Column -->
            <div class="space-y-4">
                 <!-- Day Activity Widget -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                     <div class="flex justify-between items-center mb-3">
                        <h3 class="text-gray-900 font-black text-xs">Most Active Days</h3>
                        <i class="pi pi-ellipsis-h text-gray-400 cursor-pointer hover:text-gray-600 text-xs"></i>
                    </div>
                    <div class="h-40 flex items-center justify-center -ml-2">
                        <VueApexCharts width="100%" height="100%" :options="barChartOptions" :series="barSeries" />
                    </div>
                    <!-- Pinned activity detail -->
                    <div v-if="selectedChartPoint && selectedChartPoint.chart === 'activity'"
                         class="mt-2 p-2.5 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between animate-fade-in">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-blue-600 text-xs"></i>
                            <div>
                                <p class="text-xs font-bold text-blue-900">{{ selectedChartPoint.day }}: {{ selectedChartPoint.label }}</p>
                            </div>
                        </div>
                        <button @click="selectedChartPoint = null" class="text-blue-400 hover:text-blue-600 p-1">
                            <i class="pi pi-times text-[10px]"></i>
                        </button>
                    </div>
                </div>

                <!-- Completion Rate Widget -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                     <div class="flex justify-between items-center mb-1">
                        <h3 class="text-gray-900 font-black text-xs">Completion Rate</h3>
                        <span class="text-[10px] text-gray-400 cursor-pointer">Click chart for details</span>
                    </div>
                     <div class="h-40 flex items-center justify-center relative cursor-pointer" @click="toggleRadialDetail">
                         <VueApexCharts width="100%" height="100%" :options="radialOptions" :series="radialSeries" />
                         <div v-if="!showRadialDetail" class="absolute bottom-2 text-center">
                             <p class="text-[9px] text-gray-500 font-bold">On track for 80% target</p>
                         </div>
                    </div>
                    <!-- Expanded detail on click -->
                    <div v-if="showRadialDetail" class="mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-emerald-800">Completion Breakdown</span>
                            <button @click.stop="showRadialDetail = false" class="text-emerald-400 hover:text-emerald-600">
                                <i class="pi pi-times text-[10px]"></i>
                            </button>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Completion Rate</span>
                                <span class="font-bold text-emerald-700">{{ stats.completion_rate }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Completed Goals</span>
                                <span class="font-bold text-gray-900">{{ stats.completed_goals }} / {{ stats.total_goals }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pending</span>
                                <span class="font-bold text-orange-600">{{ stats.total_goals - stats.completed_goals }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Approved Appraisals</span>
                                <span class="font-bold text-blue-600">{{ stats.approved_appraisals }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Table & AI Assistant -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- Best Performing Products Table (Mapped to Recent Goals) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                    <h3 class="text-gray-900 font-black text-sm">Recent Strategic Goals</h3>
                    <i class="pi pi-ellipsis-h text-gray-400 cursor-pointer hover:text-gray-600 text-xs"></i>
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
                                            <p class="text-xs text-gray-500">{{ goal.candidate_name || goal.employee_name || goal.employee?.name || goal.user?.name || 'Unassigned' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 text-xs font-bold text-gray-600">
                                        <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-1.5 rounded-full" :style="{ width: getTimeProgress(goal) + '%' }"></div>
                                        </div>
                                        <span>{{ getTimeProgress(goal) }}%</span>
                                    </div>
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
                                        {{ calculateAverageRating(goal) }} <span class="text-gray-300 ml-1 font-normal">/5.0</span>
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
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                    <h3 class="text-gray-900 font-black text-xs">Employee Leaderboard</h3>
                    <router-link to="/pms/leaderboard" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</router-link>
                </div>
                
                <div class="p-4 space-y-4">
                    <div v-for="(emp, index) in topEmployees" :key="emp.id" @click="openDashEmployeeModal(emp)" class="flex items-center gap-4 p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer group">
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
                            <span class="text-[10px] text-gray-400">Rating</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <router-link to="/pms/leaderboard" class="w-full py-2 text-center text-xs font-bold transition-colors flex items-center justify-center gap-2 hover:opacity-80">
                        <span class="text-indigo-600">See complete ranking</span>
                        <i class="pi pi-arrow-right text-[10px] text-indigo-600"></i>
                    </router-link>
                </div>
            </div>

        </div>

        <!-- Employee Goals Modal (Dashboard) -->
        <div v-if="showEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeDashModal">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeDashModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-hidden z-10" style="animation: scale-in 0.2s ease-out;">
                <div class="p-5 border-b border-gray-100 flex items-center gap-4"
                     style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center font-bold text-white">
                        {{ selectedDashEmployee?.name?.substring(0, 1).toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <h3 class="text-white font-black text-sm">{{ selectedDashEmployee?.name }}</h3>
                        <p class="text-purple-200 text-xs">{{ selectedDashEmployee?.role }}</p>
                    </div>
                    <div class="text-center text-white mr-2">
                        <p class="text-lg font-black">{{ selectedDashEmployee?.rating }}</p>
                        <p class="text-[10px] text-purple-200">Rating</p>
                    </div>
                    <button @click="closeDashModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto" style="max-height: calc(80vh - 90px);">
                    <h4 class="text-xs font-black text-gray-900 mb-3">Goals Details</h4>
                    <div v-if="!selectedDashEmployee?.goals?.length" class="py-6 text-center text-gray-400 text-sm">
                        No goal details available.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="goal in selectedDashEmployee.goals" :key="goal.id"
                            class="border border-gray-100 rounded-xl p-3 hover:border-indigo-200 transition-all">
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <p class="text-sm font-bold text-gray-900 flex-1">{{ goal.title }}</p>
                                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded border whitespace-nowrap', getStatusPill(goal.status)]">
                                    {{ goal.status?.replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span v-if="goal.rating" class="flex items-center gap-1">
                                    <i class="pi pi-star-fill text-yellow-400 text-[10px]"></i> {{ goal.rating }}/5
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-chart-bar text-[10px]"></i> {{ goal.actual || 0 }}/{{ goal.target }}
                                </span>
                                <span v-if="goal.category" class="flex items-center gap-1">
                                    <i class="pi pi-tag text-[10px]"></i> {{ goal.category }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 text-center">
                        <router-link to="/pms/leaderboard" @click="closeDashModal" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                            View full leaderboard <i class="pi pi-arrow-right text-[10px] ml-1"></i>
                        </router-link>
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
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.2s ease-out; }
</style>
