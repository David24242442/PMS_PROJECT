<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import VueApexCharts from "vue3-apexcharts";
import { showAlert } from '@/helpers/essential';

const router = useRouter();
const userstore = useUsersStore();

const stats = ref({
    total_employees: 0,
    total_goals: 0,
    completed_goals: 0,
    pending_appraisals: 0,
    approved_appraisals: 0,
    completion_rate: 0,
    org_avg_rating: 0,
    needs_improvement_count: 0
});

const recentGoals = ref([]);
const weeklyProgress = ref({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    data: [0, 0, 0, 0, 0, 0, 0]
});

const departmentStats = ref([]);
const ratingDistribution = ref([]);
const lineManagerStats = ref([]);
const needsImprovement = ref({
    underperforming_staff: [],
    competency_gaps: [],
    lagging_departments: [],
    pending_bottlenecks: []
});
const statusDistribution = ref({});

const loading = ref(true);
const activeSection = ref('all'); // 'all', 'departments', 'ratings', 'managers', 'needs_improvement'

const showEmployeeModal = ref(false);
const selectedDashEmployee = ref(null);
const selectedChartPoint = ref(null);

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
        if (data) {
            stats.value = data.stats || {};
            recentGoals.value = data.recent_goals || [];
            
            if (data.weekly_progress) {
                weeklyProgress.value = data.weekly_progress;
            }
            if (data.top_employees) {
                topEmployeesRaw.value = data.top_employees;
            }
            if (data.department_stats) {
                departmentStats.value = data.department_stats;
            }
            if (data.rating_distribution) {
                ratingDistribution.value = data.rating_distribution;
            }
            if (data.line_manager_stats) {
                lineManagerStats.value = data.line_manager_stats;
            }
            if (data.needs_improvement) {
                needsImprovement.value = data.needs_improvement;
            }
            if (data.status_distribution) {
                statusDistribution.value = data.status_distribution;
            }
        }
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    } finally {
        loading.value = false;
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
        zoom: { enabled: false }
    },
    colors: ['#4f46e5'],
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
            formatter: (value) => value + "%"
        }
    },
    grid: {
        borderColor: '#f1f5f9', strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        theme: 'light'
    },
    markers: {
        size: 4,
        colors: ['#4f46e5'],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: { size: 8, sizeOffset: 3 }
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
        fontFamily: 'Inter, sans-serif'
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
    grid: { show: false }
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

// --- Department Ratings Column Chart ---
const deptChartOptions = computed(() => {
    const categories = departmentStats.value.map(d => d.department);
    return {
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '45%',
                distributed: false,
                dataLabels: { position: 'top' }
            }
        },
        colors: ['#4f46e5'],
        dataLabels: {
            enabled: true,
            formatter: (val) => val.toFixed(1),
            offsetY: -20,
            style: { fontSize: '11px', fontWeight: 900, colors: ['#4f46e5'] }
        },
        xaxis: {
            categories: categories,
            labels: { style: { colors: '#64748b', fontSize: '11px', fontWeight: 700 } }
        },
        yaxis: {
            max: 5,
            labels: {
                formatter: (val) => val.toFixed(1),
                style: { colors: '#94a3b8', fontSize: '11px' }
            }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: (val) => `${val.toFixed(1)} / 5.0`
            }
        }
    };
});

const deptChartSeries = computed(() => [{
    name: 'Avg. Rating',
    data: departmentStats.value.map(d => d.avg_rating)
}]);

// --- Department Completion Rate Bar Chart ---
const deptCompletionChartOptions = computed(() => {
    const categories = departmentStats.value.map(d => d.department);
    return {
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                barHeight: '55%',
                dataLabels: { position: 'right' }
            }
        },
        colors: ['#10b981'],
        dataLabels: {
            enabled: true,
            formatter: (val) => `${val}%`,
            offsetX: 25,
            style: { fontSize: '11px', fontWeight: 800, colors: ['#059669'] }
        },
        xaxis: {
            max: 100,
            labels: {
                formatter: (val) => `${val}%`,
                style: { colors: '#94a3b8', fontSize: '11px' }
            }
        },
        yaxis: {
            labels: { style: { colors: '#334155', fontSize: '11px', fontWeight: 700 } }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: (val) => `${val}% Completed`
            }
        }
    };
});

const deptCompletionSeries = computed(() => [{
    name: 'Completion Rate',
    data: departmentStats.value.map(d => d.completion_rate)
}]);

// --- Rating Distribution Donut Chart ---
const ratingDonutOptions = computed(() => {
    const labels = ratingDistribution.value.map(r => r.short || r.label);
    const colors = ratingDistribution.value.map(r => r.color || '#6366f1');
    return {
        chart: {
            type: 'donut',
            fontFamily: 'Inter, sans-serif'
        },
        labels: labels,
        colors: colors,
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontWeight: 700,
            labels: { colors: '#475569' }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        name: { show: true, fontSize: '12px', fontWeight: 700, color: '#64748b' },
                        value: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: 900,
                            color: '#1e293b',
                            formatter: (val) => val
                        },
                        total: {
                            show: true,
                            label: 'Total Goals',
                            fontSize: '11px',
                            fontWeight: 700,
                            color: '#94a3b8',
                            formatter: (w) => {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: (val, opts) => {
                const count = opts.w.globals.series[opts.seriesIndex];
                return count > 0 ? `${count}` : '';
            },
            dropShadow: { enabled: false }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: (val) => `${val} goal(s)`
            }
        }
    };
});

const ratingDonutSeries = computed(() => ratingDistribution.value.map(r => r.count));

// --- Line Manager Review Progress Chart ---
const managerChartOptions = computed(() => {
    const categories = lineManagerStats.value.map(m => m.manager_name);
    return {
        chart: {
            type: 'bar',
            height: 320,
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: '42%'
            }
        },
        colors: ['#10b981', '#f59e0b', '#cbd5e1'],
        xaxis: {
            categories: categories,
            labels: {
                style: { colors: '#64748b', fontSize: '11px', fontWeight: 700 },
                rotate: -20
            }
        },
        yaxis: {
            labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '11px',
            fontWeight: 700,
            labels: { colors: '#475569' }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        tooltip: {
            theme: 'light'
        }
    };
});

const managerChartSeries = computed(() => {
    return [
        {
            name: 'Reviewed & Completed',
            data: lineManagerStats.value.map(m => m.reviewed_goals)
        },
        {
            name: 'Pending Review',
            data: lineManagerStats.value.map(m => m.pending_reviews)
        },
        {
            name: 'In Progress / Assigned',
            data: lineManagerStats.value.map(m => Math.max(0, m.total_goals - m.reviewed_goals - m.pending_reviews))
        }
    ];
});

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
    if (appraisal.overall_rating) {
        return parseFloat(appraisal.overall_rating).toFixed(1);
    }
    const data = parseSmart(appraisal.appraisal_data || appraisal.goal_data);
    const competencies = data?.competencies || [];
    if (competencies.length === 0) return '0.0';
    const rated = competencies.filter(c => parseFloat(c.managerRating) > 0);
    if (rated.length === 0) return '0.0';
    const sum = rated.reduce((acc, c) => acc + (parseFloat(c.managerRating) || 0), 0);
    return (sum / rated.length).toFixed(1);
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

const exportData = () => {
    let csv = "Department,Total Goals,Completed Goals,Completion Rate,Average Rating,Employee Count\n";
    departmentStats.value.forEach(d => {
        csv += `"${d.department}",${d.total_goals},${d.completed_goals},"${d.completion_rate}%",${d.avg_rating},${d.employee_count}\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", `PMS_Analytics_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <div class="min-h-full pb-10 bg-slate-50/60">
        <!-- Dashboard Header -->
        <div class="dashboard-header mb-6 rounded-3xl p-6 shadow-xl text-white relative overflow-hidden"
             style="background: linear-gradient(135deg, #4338ca 0%, #1e1b4b 100%) !important;">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
                <div>
                    <div class="flex items-center gap-3 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-md bg-white/20 text-white font-black text-[10px] tracking-wider uppercase">Executive Hub</span>
                        <span class="text-xs font-bold text-indigo-200">Performance Intelligence & Analytics</span>
                    </div>
                    <h1 class="text-2xl font-black text-white flex items-center gap-2 tracking-tight">
                        PMS Dashboard Overview
                        <button 
                            v-if="!loading"
                            @click="fetchDashboardData"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all shadow-sm border border-white/10 cursor-pointer"
                            title="Refresh Data"
                        >
                            <i class="pi pi-refresh text-xs" :class="{'pi-spin': loading}"></i>
                        </button>
                    </h1>
                </div>
                
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3.5 py-2 flex items-center gap-2 text-xs text-white shadow-sm font-bold">
                        <i class="pi pi-calendar text-indigo-200"></i>
                        <span>{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3.5 py-2 flex items-center gap-2 text-xs text-white shadow-sm font-bold">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Live Sync</span>
                    </div>
                    <button @click="exportData" class="bg-white text-indigo-900 hover:bg-indigo-50 px-4 py-2 rounded-xl text-xs font-black shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="pi pi-download text-[11px] text-indigo-600 font-bold"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid (Top Metrics) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- 1. Completion Rate Card -->
            <div class="relative overflow-hidden rounded-3xl p-5 shadow-lg text-white transform hover:-translate-y-1 transition-all duration-300 border border-white/10"
                 style="background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%) !important;">
                <div class="flex justify-between items-start">
                    <span class="text-indigo-200 font-black text-[10px] uppercase tracking-wider">Overall Completion</span>
                    <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                        <i class="pi pi-check-circle text-white text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-black text-white mb-0.5">{{ stats.completion_rate }}%</div>
                    <p class="text-xs text-indigo-200 font-semibold">{{ stats.completed_goals }} of {{ stats.total_goals }} goals reviewed</p>
                </div>
            </div>

            <!-- 2. Org Average Performance Card -->
            <div class="relative overflow-hidden rounded-3xl p-5 shadow-lg text-white transform hover:-translate-y-1 transition-all duration-300 border border-white/10"
                 style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%) !important;">
                <div class="flex justify-between items-start">
                    <span class="text-teal-100 font-black text-[10px] uppercase tracking-wider">Avg. Performance Rating</span>
                    <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                        <i class="pi pi-star-fill text-white text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-black text-white mb-0.5 flex items-baseline gap-1">
                        {{ stats.org_avg_rating ? Number(stats.org_avg_rating).toFixed(1) : '0.0' }}
                        <span class="text-sm font-bold text-teal-200">/ 5.0</span>
                    </div>
                    <p class="text-xs text-teal-100 font-semibold">Across all evaluated employees</p>
                </div>
            </div>

            <!-- 3. Active Goals Card -->
            <div class="relative overflow-hidden rounded-3xl p-5 shadow-lg text-white transform hover:-translate-y-1 transition-all duration-300 border border-white/10"
                 style="background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important;">
                <div class="flex justify-between items-start">
                    <span class="text-orange-100 font-black text-[10px] uppercase tracking-wider">Active Strategic Goals</span>
                    <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                        <i class="pi pi-flag-fill text-white text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-black text-white mb-0.5">{{ stats.total_goals }}</div>
                    <p class="text-xs text-orange-100 font-semibold">{{ stats.pending_appraisals }} submissions awaiting review</p>
                </div>
            </div>

            <!-- 4. Needs Improvement / Attention Card -->
            <div class="relative overflow-hidden rounded-3xl p-5 shadow-lg text-white transform hover:-translate-y-1 transition-all duration-300 border border-white/10"
                 style="background: linear-gradient(135deg, #e11d48 0%, #9f1239 100%) !important;">
                <div class="flex justify-between items-start">
                    <span class="text-rose-100 font-black text-[10px] uppercase tracking-wider">Needs Improvement</span>
                    <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                        <i class="pi pi-exclamation-triangle text-white text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-black text-white mb-0.5">{{ stats.needs_improvement_count || 0 }}</div>
                    <p class="text-xs text-rose-100 font-semibold">Evaluations scoring under 3.0</p>
                </div>
            </div>
        </div>

        <!-- Section Navigation Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6 scrollbar-none">
            <button @click="activeSection = 'all'"
                :class="activeSection === 'all' ? 'bg-[#1A237E] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                class="px-4 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap cursor-pointer">
                All Analytics
            </button>
            <button @click="activeSection = 'departments'"
                :class="activeSection === 'departments' ? 'bg-[#1A237E] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                class="px-4 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <i class="pi pi-building text-xs"></i> By Departments
            </button>
            <button @click="activeSection = 'ratings'"
                :class="activeSection === 'ratings' ? 'bg-[#1A237E] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                class="px-4 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <i class="pi pi-chart-pie text-xs"></i> Rating Distribution
            </button>
            <button @click="activeSection = 'managers'"
                :class="activeSection === 'managers' ? 'bg-[#1A237E] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                class="px-4 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <i class="pi pi-users text-xs"></i> Line Managers
            </button>
            <button @click="activeSection = 'needs_improvement'"
                :class="activeSection === 'needs_improvement' ? 'bg-rose-700 text-white shadow-md' : 'bg-white text-rose-600 hover:bg-rose-50 border border-rose-200'"
                class="px-4 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <i class="pi pi-exclamation-circle text-xs"></i> Where Needs Improvement
                <span v-if="stats.needs_improvement_count > 0" class="px-1.5 py-0.2 bg-white text-rose-700 rounded-full text-[10px] font-black">
                    {{ stats.needs_improvement_count }}
                </span>
            </button>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 1: DEPARTMENT ANALYTICS                                 -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div v-if="['all', 'departments'].includes(activeSection)" class="mb-8 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <i class="pi pi-building text-indigo-600"></i> Department Performance & Goals
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">Comparative ratings and goal execution rates grouped by organizational department</p>
                </div>
            </div>

            <!-- Department Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Dept Rating Chart -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-black text-slate-800">Average Performance Rating by Department</h3>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Scale 1.0 - 5.0</span>
                    </div>
                    <div v-if="departmentStats.length > 0" class="h-80">
                        <VueApexCharts width="100%" height="100%" :options="deptChartOptions" :series="deptChartSeries" />
                    </div>
                    <div v-else class="h-80 flex items-center justify-center text-slate-400 text-xs font-bold">
                        No department evaluation data recorded yet.
                    </div>
                </div>

                <!-- Dept Completion Rate Chart -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-black text-slate-800">Goal Completion Rate by Department (%)</h3>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Target: 100%</span>
                    </div>
                    <div v-if="departmentStats.length > 0" class="h-80">
                        <VueApexCharts width="100%" height="100%" :options="deptCompletionChartOptions" :series="deptCompletionSeries" />
                    </div>
                    <div v-else class="h-80 flex items-center justify-center text-slate-400 text-xs font-bold">
                        No department goal data recorded yet.
                    </div>
                </div>
            </div>

            <!-- Department Breakdown Cards Table -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Department Summary Table</span>
                    <span class="text-xs font-bold text-slate-400">{{ departmentStats.length }} Departments</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100/70 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3.5">Department</th>
                                <th class="px-6 py-3.5 text-center">Employees</th>
                                <th class="px-6 py-3.5 text-center">Total Goals</th>
                                <th class="px-6 py-3.5 text-center">Reviewed</th>
                                <th class="px-6 py-3.5">Completion Rate</th>
                                <th class="px-6 py-3.5 text-center">Avg Rating</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                            <tr v-for="dept in departmentStats" :key="dept.department" class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-black text-slate-900 flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-black text-xs">
                                        {{ dept.department.substring(0, 1) }}
                                    </div>
                                    {{ dept.department }}
                                </td>
                                <td class="px-6 py-4 text-center">{{ dept.employee_count }}</td>
                                <td class="px-6 py-4 text-center">{{ dept.total_goals }}</td>
                                <td class="px-6 py-4 text-center text-emerald-700 font-bold">{{ dept.completed_goals }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-32 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" :style="{ width: dept.completion_rate + '%' }"></div>
                                        </div>
                                        <span class="font-black text-xs text-slate-800">{{ dept.completion_rate }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="dept.avg_rating > 0" class="px-2.5 py-1 rounded-lg text-xs font-black border"
                                        :class="dept.avg_rating >= 3.5 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : dept.avg_rating >= 2.5 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                                        ⭐ {{ dept.avg_rating }}
                                    </span>
                                    <span v-else class="text-slate-300 font-normal">—</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="dept.completion_rate === 100" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Completed
                                    </span>
                                    <span v-else-if="dept.completion_rate >= 50" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        On Track
                                    </span>
                                    <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                        In Progress
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 2: RATING DISTRIBUTION (PERFORMANCE BELL CURVE)        -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div v-if="['all', 'ratings'].includes(activeSection)" class="mb-8 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <i class="pi pi-chart-pie text-purple-600"></i> Rating Distribution (Performance Bell Curve)
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">Evaluation tier distribution across the 5 standard performance rating brackets</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Donut Chart -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex flex-col items-center justify-center">
                    <h3 class="text-sm font-black text-slate-800 mb-2 w-full text-left">Rating Spread</h3>
                    <div class="w-full h-72 flex items-center justify-center">
                        <VueApexCharts width="100%" height="100%" :options="ratingDonutOptions" :series="ratingDonutSeries" />
                    </div>
                </div>

                <!-- Tiers Cards Grid (2 cols) -->
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="tier in ratingDistribution" :key="tier.label"
                        class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm hover:border-indigo-300 transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: tier.color }"></span>
                                <span class="font-black text-xs text-slate-800">{{ tier.label }}</span>
                            </div>
                            <span class="text-xs font-black px-2 py-0.5 rounded-md"
                                  :style="{ backgroundColor: tier.color + '20', color: tier.color }">
                                {{ stats.total_goals > 0 ? Math.round((tier.count / stats.total_goals) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between mt-1">
                            <span class="text-2xl font-black text-slate-900">{{ tier.count }}</span>
                            <span class="text-[11px] font-semibold text-slate-400">goals / employees</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2 overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all"
                                 :style="{ width: (stats.total_goals > 0 ? Math.round((tier.count / stats.total_goals) * 100) : 0) + '%', backgroundColor: tier.color }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 3: LINE MANAGERS TRACKING & REVIEWS                     -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div v-if="['all', 'managers'].includes(activeSection)" class="mb-8 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <i class="pi pi-users text-blue-600"></i> Line Manager Review Tracking
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">Tracking review completion rates, pending submissions, and average team score per Line Manager</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Stacked Bar Chart -->
                <div class="lg:col-span-1 bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 mb-3">Review Progress by Manager</h3>
                    <div v-if="lineManagerStats.length > 0" class="h-80">
                        <VueApexCharts width="100%" height="100%" :options="managerChartOptions" :series="managerChartSeries" />
                    </div>
                    <div v-else class="h-80 flex items-center justify-center text-slate-400 text-xs font-bold">
                        No manager records found.
                    </div>
                </div>

                <!-- Managers Progress Table -->
                <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Line Manager Status Table</span>
                        <span class="text-xs font-bold text-slate-400">{{ lineManagerStats.length }} Managers</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100/70 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3">Manager Name</th>
                                    <th class="px-4 py-3 text-center">Team Size</th>
                                    <th class="px-4 py-3 text-center">Total Goals</th>
                                    <th class="px-4 py-3 text-center">Reviewed</th>
                                    <th class="px-4 py-3 text-center">Pending Review</th>
                                    <th class="px-5 py-3">Review Rate</th>
                                    <th class="px-4 py-3 text-center">Team Avg</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                                <tr v-for="mgr in lineManagerStats" :key="mgr.manager_name" class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-5 py-3.5 font-black text-slate-900 flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-black text-xs">
                                            {{ mgr.manager_name.substring(0, 1).toUpperCase() }}
                                        </div>
                                        <span>{{ mgr.manager_name }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">{{ mgr.team_size }}</td>
                                    <td class="px-4 py-3.5 text-center font-bold">{{ mgr.total_goals }}</td>
                                    <td class="px-4 py-3.5 text-center font-bold text-emerald-600">{{ mgr.reviewed_goals }}</td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span v-if="mgr.pending_reviews > 0" class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-black text-[10px]">
                                            {{ mgr.pending_reviews }}
                                        </span>
                                        <span v-else class="text-slate-300">0</span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="h-2 rounded-full transition-all duration-500"
                                                     :class="mgr.review_rate === 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                                                     :style="{ width: mgr.review_rate + '%' }"></div>
                                            </div>
                                            <span class="font-black text-[11px]">{{ mgr.review_rate }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span v-if="mgr.avg_rating > 0" class="px-2 py-0.5 rounded-md font-bold text-xs bg-slate-100 text-slate-800">
                                            ⭐ {{ mgr.avg_rating }}
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 4: WHERE NEEDS IMPROVEMENT (INTERVENTION HUB)            -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div v-if="['all', 'needs_improvement'].includes(activeSection)" class="mb-8 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-rose-700 flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle"></i> Where Needs Improvement (Intervention Hub)
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">Critical attention areas, competency skill gaps across departments, and underperforming employees</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- 1. Low Performance Watchlist (< 3.0 Rating) -->
                <div class="bg-white rounded-3xl p-5 border border-rose-200 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs">
                                <i class="pi pi-user-minus text-[10px]"></i>
                            </span>
                            <h3 class="text-sm font-black text-slate-800">Performance Watchlist (Rating &lt; 3.0)</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-black">
                            {{ needsImprovement.underperforming_staff?.length || 0 }} Staff Flagged
                        </span>
                    </div>

                    <div v-if="needsImprovement.underperforming_staff && needsImprovement.underperforming_staff.length > 0" class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                        <div v-for="staff in needsImprovement.underperforming_staff" :key="staff.id"
                             class="p-3 rounded-2xl bg-rose-50/40 border border-rose-100 flex items-center justify-between hover:bg-rose-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 font-black text-xs flex items-center justify-center">
                                    {{ staff.name.substring(0, 1) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-slate-900">{{ staff.name }}</h4>
                                    <p class="text-[10px] text-slate-500">{{ staff.employee_code }} &bull; {{ staff.department }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-black text-xs">
                                    ⭐ {{ staff.rating }} / 5.0
                                </span>
                                <p class="text-[9px] text-rose-600 font-bold mt-0.5">Needs Coaching</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-slate-400">
                        <i class="pi pi-check-circle text-2xl text-emerald-500 mb-2 block"></i>
                        <p class="text-xs font-bold text-slate-600">No underperforming evaluations detected!</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">All currently evaluated employees are scoring 3.0 or higher.</p>
                    </div>
                </div>

                <!-- 2. Organizational Competency Gaps -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">
                                <i class="pi pi-sliders-h text-[10px]"></i>
                            </span>
                            <h3 class="text-sm font-black text-slate-800">Competency Skill Gaps (Lowest First)</h3>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Training Focus</span>
                    </div>

                    <div v-if="needsImprovement.competency_gaps && needsImprovement.competency_gaps.length > 0" class="space-y-3 max-h-72 overflow-y-auto pr-1">
                        <div v-for="gap in needsImprovement.competency_gaps" :key="gap.competency" class="space-y-1">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold text-slate-800">{{ gap.competency }}</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-black text-slate-900">{{ gap.avg_score }} / 5.0</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase"
                                          :class="gap.avg_score >= 3.5 ? 'bg-emerald-50 text-emerald-700' : gap.avg_score >= 2.8 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'">
                                        {{ gap.status === 'needs_attention' ? 'Priority' : gap.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500"
                                     :class="gap.avg_score >= 3.5 ? 'bg-emerald-500' : gap.avg_score >= 2.8 ? 'bg-amber-500' : 'bg-rose-500'"
                                     :style="{ width: (gap.avg_score / 5) * 100 + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-slate-400">
                        <i class="pi pi-info-circle text-2xl text-slate-300 mb-2 block"></i>
                        <p class="text-xs font-bold text-slate-500">Awaiting evaluated competency scores</p>
                    </div>
                </div>
            </div>

            <!-- Pending Review Bottlenecks Banner -->
            <div v-if="needsImprovement.pending_bottlenecks && needsImprovement.pending_bottlenecks.length > 0"
                 class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                        <i class="pi pi-clock text-base"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-900">{{ needsImprovement.pending_bottlenecks.length }} Appraisal(s) Awaiting Manager Review</h4>
                        <p class="text-[11px] text-amber-700 font-medium">Submissions are awaiting final review and signature from assigned Line Managers.</p>
                    </div>
                </div>
                <router-link to="/pms/goals" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black transition-colors whitespace-nowrap shadow-sm">
                    View Submissions <i class="pi pi-arrow-right text-[10px] ml-1"></i>
                </router-link>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 5: PERFORMANCE TRENDS & RECENT STRATEGIC GOALS          -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div v-if="activeSection === 'all'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Main Performance Chart -->
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-slate-900 font-black text-sm">Total Weekly Activity Trend</h3>
                            <div class="flex items-center gap-3 mt-1">
                                <h2 class="text-xl font-black text-slate-900">{{ stats.completion_rate }}% Completed</h2>
                            </div>
                        </div>
                    </div>
                    <div class="w-full h-72">
                        <VueApexCharts width="100%" height="100%" :options="chartOptions" :series="series" />
                    </div>
                </div>

                <!-- Side Widgets Column -->
                <div class="space-y-4">
                    <!-- Day Activity Widget -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-slate-900 font-black text-xs">Activity by Day</h3>
                            <i class="pi pi-chart-bar text-slate-400 text-xs"></i>
                        </div>
                        <div class="h-36 flex items-center justify-center -ml-2">
                            <VueApexCharts width="100%" height="100%" :options="barChartOptions" :series="barSeries" />
                        </div>
                    </div>

                    <!-- Completion Rate Radial Widget -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
                        <div class="flex justify-between items-center mb-1">
                            <h3 class="text-slate-900 font-black text-xs">Overall Completion</h3>
                            <span class="text-[10px] text-indigo-600 font-bold cursor-pointer" @click="toggleRadialDetail">
                                {{ showRadialDetail ? 'Hide Details' : 'Details' }}
                            </span>
                        </div>
                        <div class="h-36 flex items-center justify-center relative cursor-pointer" @click="toggleRadialDetail">
                            <VueApexCharts width="100%" height="100%" :options="radialOptions" :series="radialSeries" />
                        </div>
                        <div v-if="showRadialDetail" class="mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-1.5 text-xs animate-fade-in">
                            <div class="flex justify-between font-bold text-slate-700">
                                <span>Completed Goals:</span>
                                <span class="text-emerald-700">{{ stats.completed_goals }} / {{ stats.total_goals }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-slate-700">
                                <span>Pending Reviews:</span>
                                <span class="text-amber-700">{{ stats.pending_appraisals }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Recent Strategic Goals Table & Leaderboard -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Recent Strategic Goals -->
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/40">
                        <h3 class="text-slate-900 font-black text-sm">Recent Strategic Goals</h3>
                        <router-link to="/pms/goals" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">View All</router-link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] text-slate-500 border-b border-slate-100 bg-slate-50/50 uppercase font-black tracking-wider">
                                    <th class="px-6 py-3 pl-8">ID</th>
                                    <th class="px-6 py-3">Goal / Employee</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Rating</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-xs">
                                <tr v-for="goal in recentGoals.slice(0, 5)" :key="goal.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-3.5 font-bold text-slate-400 pl-8">#{{ goal.id }}</td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0 text-slate-600">
                                                <i class="pi pi-flag text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="font-black text-slate-800 line-clamp-1 max-w-[200px]">{{ goal.title }}</p>
                                                <p class="text-[11px] text-slate-400">{{ goal.candidate_name || goal.user?.name || 'Unassigned' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span :class="['text-[10px] font-black px-2.5 py-0.5 rounded-full border uppercase tracking-tight', getStatusPill(goal.status)]">
                                            {{ goal.status?.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 font-black text-slate-700">
                                        <div class="flex items-center gap-1">
                                            <i class="pi pi-star-fill text-amber-400 text-[10px]"></i>
                                            <span>{{ calculateAverageRating(goal) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="recentGoals.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-400 font-bold">No recent goals found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Employee Leaderboard Widget -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/40">
                        <h3 class="text-slate-900 font-black text-sm">Top Performers</h3>
                        <router-link to="/pms/leaderboard" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">View All</router-link>
                    </div>
                    
                    <div class="p-4 space-y-3">
                        <div v-for="(emp, index) in topEmployees" :key="emp.id" @click="openDashEmployeeModal(emp)"
                             class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 transition-colors cursor-pointer group">
                            <div class="relative">
                                <div :class="['w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs', emp.color]">
                                    {{ emp.avatar }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-white rounded-full flex items-center justify-center shadow-sm text-[9px] font-black"
                                    :class="index === 0 ? 'text-amber-500 font-bold' : (index === 1 ? 'text-slate-400' : 'text-slate-300')">
                                    {{ index + 1 }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-black text-slate-900 truncate group-hover:text-indigo-600 transition-colors">{{ emp.name }}</h4>
                                <p class="text-[11px] text-slate-400 truncate">{{ emp.role }}</p>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs font-black text-slate-900">⭐ {{ emp.score }}</span>
                                <span class="text-[9px] text-slate-400 font-bold">Rating</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Goals Modal (Dashboard) -->
        <div v-if="showEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeDashModal">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeDashModal"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-hidden z-10 animate-scale-in">
                <div class="p-5 border-b border-gray-100 flex items-center gap-4"
                     style="background: linear-gradient(135deg, #4338ca 0%, #1e1b4b 100%) !important;">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center font-black text-white text-sm">
                        {{ selectedDashEmployee?.name?.substring(0, 1).toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <h3 class="text-white font-black text-sm">{{ selectedDashEmployee?.name }}</h3>
                        <p class="text-indigo-200 text-xs font-medium">{{ selectedDashEmployee?.role }}</p>
                    </div>
                    <div class="text-center text-white mr-2">
                        <p class="text-lg font-black">⭐ {{ selectedDashEmployee?.rating }}</p>
                        <p class="text-[10px] text-indigo-200 font-bold">Rating</p>
                    </div>
                    <button @click="closeDashModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white cursor-pointer">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto" style="max-height: calc(80vh - 90px);">
                    <h4 class="text-xs font-black text-slate-900 mb-3 uppercase tracking-wider">Assigned Strategic Objectives</h4>
                    <div v-if="!selectedDashEmployee?.goals?.length" class="py-6 text-center text-slate-400 text-xs font-bold">
                        No goal details available for this staff member.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="goal in selectedDashEmployee.goals" :key="goal.id"
                            class="border border-slate-200 rounded-2xl p-3.5 hover:border-indigo-300 transition-all">
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <p class="text-xs font-black text-slate-900 flex-1">{{ goal.title }}</p>
                                <span :class="['text-[10px] font-black px-2 py-0.5 rounded-full border whitespace-nowrap uppercase', getStatusPill(goal.status)]">
                                    {{ goal.status?.replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-500 font-semibold">
                                <span v-if="goal.rating" class="flex items-center gap-1 text-slate-700">
                                    <i class="pi pi-star-fill text-amber-400 text-[10px]"></i> {{ goal.rating }}/5.0
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-chart-bar text-[10px]"></i> Target: {{ goal.target || 'N/A' }}
                                </span>
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
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.2s ease-out; }
.animate-scale-in { animation: scale-in 0.2s ease-out; }
</style>
