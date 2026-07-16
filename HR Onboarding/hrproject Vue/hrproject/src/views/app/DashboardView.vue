<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/helpers/pms_axios';
import { log, imgburl } from '@/helpers/essential'
import { hospitalityDeptsIDs, finddept, findbranch, findcountry, findcompany, findgender } from '@/data/masterdata'
import { regionminis, regioncentral, regionsgra, regionswestern, regionashanti, regionvolta, regionnorth, regioneastern, regionbrong } from '@/data/masterdata'
import { useUsersStore } from '@/stores/user';

const userstore = useUsersStore()
const { loguser } = userstore

const loading = ref(false)
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true })
const result = ref(null)
const loadingEmps = ref(false)
const showEmpsPopup = ref(false)
const emplist = ref([])
const empPopupTitle = ref('Employee List')
const empSearch = ref('')

// Computed Data
const hospitalitiesData = computed(() => result.value?.deptsCount?.filter((item) => hospitalityDeptsIDs.includes(item.joining_dept_id)) || [])
const melcomDeptsData = computed(() => result.value?.deptsCount?.filter((item) => !hospitalityDeptsIDs.includes(item.joining_dept_id)) || [])

// Region aggregates
const gradatatotals = computed(() => result.value?.locationCount?.filter((item) => regionsgra.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const centraldatatotals = computed(() => result.value?.locationCount?.filter((item) => regioncentral.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const westerndatatotals = computed(() => result.value?.locationCount?.filter((item) => regionswestern.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const ashantitotals = computed(() => result.value?.locationCount?.filter((item) => regionashanti.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const voltatotals = computed(() => result.value?.locationCount?.filter((item) => regionvolta.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const northdatatotals = computed(() => result.value?.locationCount?.filter((item) => regionnorth.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const easterndatatotals = computed(() => result.value?.locationCount?.filter((item) => regioneastern.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)
const brongdatatotals = computed(() => result.value?.locationCount?.filter((item) => regionbrong.includes(item.joining_branch_id)).reduce((a, b) => a + b.total_count, 0) || 0)

const finalLocationData = computed(() => ({
    'names': ["Greater Accra", "Central", 'Western', 'Ashanti', 'Volta', 'Northern', 'Eastern', 'Brong Ahafo'],
    'count': [gradatatotals.value, centraldatatotals.value, westerndatatotals.value, ashantitotals.value, voltatotals.value, northdatatotals.value, easterndatatotals.value, brongdatatotals.value]
}))

const totalRegionEmployees = computed(() => finalLocationData.value.count.reduce((a, b) => a + b, 0))

const filteredEmpList = computed(() => {
    if (!empSearch.value) return emplist.value
    const s = empSearch.value.toLowerCase()
    return emplist.value.filter(e =>
        (e.firstname + ' ' + e.lastname).toLowerCase().includes(s) ||
        (e.emp_code || '').toLowerCase().includes(s) ||
        (e.employeeid || '').toLowerCase().includes(s)
    )
})

// Shared tooltip style
const tooltipTheme = { theme: 'dark', style: { fontSize: '11px', fontFamily: 'Inter, sans-serif' } }

// Chart configs
const seriesMelcom = computed(() => [{
    data: melcomDeptsData.value.map(item => ({
        x: finddept(item.joining_dept_id),
        y: item.total_count
    }))
}])
const optionsMelcom = computed(() => ({
    chart: { type: 'treemap', toolbar: { show: false }, fontFamily: 'Inter, sans-serif',
        events: { dataPointSelection: (e, c, config) => {
            const idx = config.dataPointIndex;
            if (melcomDeptsData.value[idx]) {
                empPopupTitle.value = finddept(melcomDeptsData.value[idx].joining_dept_id);
                loadEmpDepts(['dept', melcomDeptsData.value[idx].joining_dept_id])
            }
        } } },
    plotOptions: { treemap: { distributed: true, enableShades: false } },
    dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 700 },
        formatter: (text, op) => [text, op.value] },
    colors: ['#6366f1', '#8b5cf6', '#a78bfa', '#818cf8', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95', '#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#22c55e', '#84cc16', '#eab308', '#f59e0b', '#f97316', '#ef4444', '#ec4899'],
    legend: { show: false },
    tooltip: tooltipTheme
}))

const seriesHospitality = computed(() => [{ name: 'Employees', data: hospitalitiesData.value.map(item => item.total_count) }])
const optionsHospitality = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif',
        events: { dataPointSelection: (e, c, config) => { empPopupTitle.value = finddept(hospitalitiesData.value[config.dataPointIndex].joining_dept_id); loadEmpDepts(['dept', hospitalitiesData.value[config.dataPointIndex].joining_dept_id]) } } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] } },
    xaxis: { categories: hospitalitiesData.value.map(item => finddept(item.joining_dept_id)), labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#64748b' }, rotate: -45, rotateAlways: hospitalitiesData.value.length > 4 } },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
    colors: ['#f59e0b', '#f97316', '#ef4444', '#ec4899', '#d946ef', '#8b5cf6'],
    legend: { show: false },
    tooltip: tooltipTheme
}))

const seriesRegions = computed(() => [{ name: 'Employees', data: finalLocationData.value.count }])
const optionsRegions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] } },
    xaxis: { categories: finalLocationData.value.names, labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#64748b' }, rotate: -35, rotateAlways: true } },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
    colors: ['#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#22c55e', '#84cc16', '#eab308', '#f97316'],
    legend: { show: false },
    tooltip: tooltipTheme
}))

const seriesGender = computed(() => result.value?.genderCounts?.filter(i => i.gender).map(item => item.total_count) || [])
const optionsGender = computed(() => ({
    labels: result.value?.genderCounts?.filter(i => i.gender).map(item => findgender(item.gender)) || [],
    chart: { type: 'donut', fontFamily: 'Inter, sans-serif',
        events: { dataPointSelection: (e, c, config) => { empPopupTitle.value = findgender(result.value.genderCounts.filter(i => i.gender)[config.dataPointIndex].gender); loadEmpDepts(['gender', result.value.genderCounts.filter(i => i.gender)[config.dataPointIndex].gender]) } } },
    colors: ['#ec4899', '#3b82f6'],
    plotOptions: { pie: { donut: { size: '72%', labels: { show: true, name: { fontSize: '13px', fontWeight: 700, color: '#334155' }, value: { fontSize: '22px', fontWeight: 800, color: '#0f172a' }, total: { show: true, label: 'Total', fontSize: '12px', fontWeight: 600, color: '#94a3b8' } } } } },
    dataLabels: { enabled: false },
    legend: { position: 'bottom', fontSize: '12px', fontWeight: 600, labels: { colors: '#64748b' }, markers: { width: 10, height: 10, radius: 3 } },
    stroke: { width: 3, colors: ['#fff'] },
    tooltip: tooltipTheme
}))

const seriesAge = computed(() => [{ name: 'Employees', data: Object.values(result.value?.ageGroups || {}) }])
const optionsAge = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif',
        events: { dataPointSelection: (e, c, config) => { empPopupTitle.value = Object.keys(result.value.ageGroups)[config.dataPointIndex]; loadEmpDepts(['agegroup', Object.keys(result.value.ageGroups)[config.dataPointIndex]]) } } },
    xaxis: { categories: Object.keys(result.value?.ageGroups || {}), labels: { style: { fontSize: '11px', fontWeight: 600, colors: '#64748b' } } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] } },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
    colors: ['#14b8a6'],
    tooltip: tooltipTheme
}))

const seriesMonthly = computed(() => [{ name: 'New Joiners', data: Object.values(result.value?.monthlyData || {}) }])
const optionsMonthly = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, fontFamily: 'Inter, sans-serif', sparkline: { enabled: false },
        events: { dataPointSelection: (e, c, config) => { empPopupTitle.value = Object.keys(result.value.monthlyData)[config.dataPointIndex]; loadEmpDepts(['monthdata', Object.keys(result.value.monthlyData)[config.dataPointIndex]]) } } },
    xaxis: { categories: Object.keys(result.value?.monthlyData || {}), labels: { style: { fontSize: '11px', fontWeight: 600, colors: '#64748b' } } },
    yaxis: { labels: { style: { fontSize: '11px', fontWeight: 600, colors: '#94a3b8' } } },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#6366f1'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 95, 100] } },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3, padding: { left: 10, right: 10 } },
    dataLabels: { enabled: false },
    markers: { size: 4, colors: ['#6366f1'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
    tooltip: tooltipTheme
}))

// Methods
const loadchart = () => {
    loading.value = true
    axios.get('dashboard')
        .then(res => { result.value = res.data; loading.value = false })
        .catch(err => { console.error(err); loading.value = false })
}

const loadEmpDepts = (data) => {
    emplist.value = []
    empSearch.value = ''
    loadingEmps.value = true
    showEmpsPopup.value = true
    axios.post('loadEmpDepts', { type: data[0], value: data[1] })
        .then(res => { emplist.value = res.data; loadingEmps.value = false })
        .catch(err => { console.error(err); loadingEmps.value = false })
}

onMounted(() => { loadchart() })
</script>

<template>
    <div class="dash-root">

        <!-- Hero Header -->
        <div class="dash-hero">
            <div class="dash-hero-bg"></div>
            <div class="dash-hero-content">
                <div>
                    <p class="text-[11px] font-black text-indigo-300 uppercase tracking-[0.25em] mb-2">Melcom Group</p>
                    <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                        HR Dashboard
                        <button v-if="!loading" @click="loadchart"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white/70 hover:text-white flex items-center justify-center transition-all cursor-pointer border border-white/10"
                            title="Refresh">
                            <i class="pi pi-refresh text-xs" :class="{'pi-spin': loading}"></i>
                        </button>
                    </h1>
                    <p class="text-sm text-indigo-200/80 font-medium mt-1">Real-time workforce analytics and onboarding insights</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hero-pill">
                        <i class="pi pi-calendar text-indigo-300 text-xs"></i>
                        <span>{{ new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    </div>
                    <div class="hero-pill">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Live</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div v-if="result" class="dash-body">

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card stat-card--indigo">
                    <div class="flex items-center justify-between mb-3">
                        <span class="stat-label">Total Employees</span>
                        <div class="stat-icon"><i class="pi pi-users"></i></div>
                    </div>
                    <div class="stat-value">{{ result.totalemp || 0 }}</div>
                    <p class="stat-sub">Active staff</p>
                </div>
                <div class="stat-card stat-card--pink">
                    <div class="flex items-center justify-between mb-3">
                        <span class="stat-label">Departments</span>
                        <div class="stat-icon"><i class="pi pi-building"></i></div>
                    </div>
                    <div class="stat-value">{{ result.deptsCount ? result.deptsCount.length : 0 }}</div>
                    <p class="stat-sub">Across organization</p>
                </div>
                <div class="stat-card stat-card--amber">
                    <div class="flex items-center justify-between mb-3">
                        <span class="stat-label">Branches</span>
                        <div class="stat-icon"><i class="pi pi-map-marker"></i></div>
                    </div>
                    <div class="stat-value">{{ result.locationCount ? result.locationCount.length : 0 }}</div>
                    <p class="stat-sub">Regional locations</p>
                </div>
                <div class="stat-card stat-card--teal">
                    <div class="flex items-center justify-between mb-3">
                        <span class="stat-label">Companies</span>
                        <div class="stat-icon"><i class="pi pi-briefcase"></i></div>
                    </div>
                    <div class="stat-value">{{ result.companiesCounts ? result.companiesCounts.length : 0 }}</div>
                    <p class="stat-sub">Subsidiaries</p>
                </div>
            </div>

            <!-- Monthly Trend — Full Width -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Monthly Joining Trend</h3>
                        <p class="chart-subtitle">New employee onboarding over time</p>
                    </div>
                    <div class="chart-badge chart-badge--indigo">
                        <i class="pi pi-chart-line text-[10px]"></i> Trend
                    </div>
                </div>
                <apexchart type="area" height="280" :options="optionsMonthly" :series="seriesMonthly"></apexchart>
            </div>

            <!-- Two-Col: Departments + Regions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">Melcom Departments</h3>
                            <p class="chart-subtitle">Staff distribution by department</p>
                        </div>
                        <div class="chart-badge chart-badge--violet">
                            <i class="pi pi-building text-[10px]"></i> {{ melcomDeptsData.length }}
                        </div>
                    </div>
                    <apexchart type="treemap" height="380" :options="optionsMelcom" :series="seriesMelcom"></apexchart>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">Employees by Region</h3>
                            <p class="chart-subtitle">Geographic distribution across Ghana</p>
                        </div>
                        <div class="chart-badge chart-badge--cyan">
                            <i class="pi pi-map text-[10px]"></i> {{ totalRegionEmployees }}
                        </div>
                    </div>
                    <apexchart type="bar" height="340" :options="optionsRegions" :series="seriesRegions"></apexchart>
                </div>
            </div>

            <!-- Two-Col: Gender + Age -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">Gender Distribution</h3>
                            <p class="chart-subtitle">Workforce diversity breakdown</p>
                        </div>
                        <div class="chart-badge chart-badge--pink">
                            <i class="pi pi-users text-[10px]"></i> Split
                        </div>
                    </div>
                    <apexchart type="donut" height="300" :options="optionsGender" :series="seriesGender"></apexchart>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">Age Groups</h3>
                            <p class="chart-subtitle">Employee age demographics</p>
                        </div>
                        <div class="chart-badge chart-badge--teal">
                            <i class="pi pi-chart-bar text-[10px]"></i> Range
                        </div>
                    </div>
                    <apexchart type="bar" height="300" :options="optionsAge" :series="seriesAge"></apexchart>
                </div>
            </div>

            <!-- Hospitality — Full Width -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Hospitality Departments</h3>
                        <p class="chart-subtitle">Staff count across hospitality divisions</p>
                    </div>
                    <div class="chart-badge chart-badge--amber">
                        <i class="pi pi-star text-[10px]"></i> {{ hospitalitiesData.length }}
                    </div>
                </div>
                <apexchart type="bar" height="280" :options="optionsHospitality" :series="seriesHospitality"></apexchart>
            </div>
        </div>

        <!-- Loading State -->
        <div v-else-if="loading" class="flex items-center justify-center py-32">
            <div class="text-center">
                <i class="pi pi-spin pi-spinner text-4xl text-indigo-400 mb-4"></i>
                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Loading dashboard...</p>
            </div>
        </div>
    </div>

    <!-- Employee Drill-down Dialog -->
    <Dialog v-model:visible="showEmpsPopup" modal :header="empPopupTitle" :style="{ width: '48rem' }" :breakpoints="{ '1199px': '75vw', '575px': '95vw' }"
        :contentStyle="{ padding: '0', background: '#ffffff', color: '#1e293b' }"
        class="emp-dialog-light">
        <div class="p-5">
            <!-- Search + Count -->
            <div class="flex items-center justify-between mb-4 gap-3">
                <div class="relative flex-1">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input v-model="empSearch" type="text" placeholder="Search by name or code..."
                        class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
                </div>
                <span class="text-xs font-black text-slate-400 uppercase tracking-wider whitespace-nowrap">{{ filteredEmpList.length }} found</span>
            </div>

            <div v-if="loadingEmps" class="flex justify-center py-12">
                <i class="pi pi-spin pi-spinner text-3xl text-indigo-400"></i>
            </div>

            <div v-else class="space-y-1.5 max-h-[60vh] overflow-y-auto pr-1">
                <div v-for="(e, index) in filteredEmpList" :key="index"
                    class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-colors group border border-transparent hover:border-slate-100">
                    <div class="flex items-center gap-3">
                        <Avatar :image="e.profilepicture[0]?.path ? `${imgburl}${e.profilepicture[0]?.path}` : null"
                            :label="!e.profilepicture[0]?.path ? (e.firstname?.[0] || '?') : null"
                            shape="circle" size="large"
                            class="shrink-0" />
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ e.firstname }} {{ e.lastname }}</p>
                            <p class="text-[11px] text-slate-400 font-medium">{{ e.emp_code }} &middot; {{ e.employeeid }}</p>
                        </div>
                    </div>
                    <router-link :to="`/employee/${e.id}`" target="_blank"
                        class="px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold uppercase tracking-wider hover:bg-indigo-100 transition-colors opacity-0 group-hover:opacity-100">
                        View
                    </router-link>
                </div>
                <div v-if="filteredEmpList.length === 0" class="text-center py-10">
                    <i class="pi pi-inbox text-3xl text-slate-200 mb-2"></i>
                    <p class="text-sm font-bold text-slate-400">No employees found</p>
                </div>
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.dash-root {
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 20px 40px;
}

/* Hero */
.dash-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 24px;
    padding: 32px 36px;
}
.dash-hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
    z-index: 0;
}
.dash-hero-bg::after {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%);
    border-radius: 50%;
}
.dash-hero-bg::before {
    content: '';
    position: absolute;
    bottom: -30%;
    left: 10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(168,85,247,0.2) 0%, transparent 70%);
    border-radius: 50%;
}
.dash-hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
}
.hero-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    border-radius: 10px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 700;
    color: rgba(255,255,255,0.8);
}

/* Body */
.dash-body {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Stat Cards */
.stat-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 20px 22px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px -12px rgba(0,0,0,0.12);
}
.stat-card--indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
.stat-card--pink::before { background: linear-gradient(90deg, #ec4899, #f472b6); }
.stat-card--amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.stat-card--teal::before { background: linear-gradient(90deg, #14b8a6, #2dd4bf); }

.stat-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
}
.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.stat-card--indigo .stat-icon { background: #eef2ff; color: #6366f1; }
.stat-card--pink .stat-icon { background: #fce7f3; color: #ec4899; }
.stat-card--amber .stat-icon { background: #fef3c7; color: #f59e0b; }
.stat-card--teal .stat-icon { background: #ccfbf1; color: #14b8a6; }

/* Chart Cards */
.chart-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 24px;
    transition: box-shadow 0.2s ease;
}
.chart-card:hover {
    box-shadow: 0 4px 20px -8px rgba(0,0,0,0.06);
}
.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}
.chart-title {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.01em;
}
.chart-subtitle {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 2px;
}
.chart-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}
.chart-badge--indigo { background: #eef2ff; color: #6366f1; }
.chart-badge--violet { background: #f5f3ff; color: #7c3aed; }
.chart-badge--cyan { background: #ecfeff; color: #06b6d4; }
.chart-badge--pink { background: #fce7f3; color: #ec4899; }
.chart-badge--teal { background: #ccfbf1; color: #14b8a6; }
.chart-badge--amber { background: #fef3c7; color: #f59e0b; }

/* Stat value */
.stat-value {
    font-size: 1.875rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 4px;
}
.stat-sub {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* Force light background on employee dialog */
:global(.emp-dialog-light) {
    background: #ffffff !important;
    color: #1e293b !important;
}
:global(.emp-dialog-light .p-dialog-header) {
    background: #ffffff !important;
    color: #0f172a !important;
    border-bottom: 1px solid #f1f5f9 !important;
    padding: 20px 24px !important;
}
:global(.emp-dialog-light .p-dialog-header .p-dialog-title) {
    font-weight: 800 !important;
    font-size: 18px !important;
    color: #0f172a !important;
}
:global(.emp-dialog-light .p-dialog-header .p-dialog-header-icon) {
    color: #94a3b8 !important;
}
:global(.emp-dialog-light .p-dialog-header .p-dialog-header-icon:hover) {
    background: #f1f5f9 !important;
    color: #334155 !important;
}
:global(.emp-dialog-light .p-dialog-content) {
    background: #ffffff !important;
    color: #1e293b !important;
}

/* Dark mode */
:global(body.dark-mode) .dash-root { color: #e2e8f0; }
:global(body.dark-mode) .stat-card,
:global(body.dark-mode) .chart-card { background: #1e293b; border-color: #334155; }
:global(body.dark-mode) .stat-card:hover { box-shadow: 0 8px 30px -12px rgba(0,0,0,0.4); }
:global(body.dark-mode) .stat-label { color: #64748b; }
:global(body.dark-mode) .stat-value { color: #f1f5f9; }
:global(body.dark-mode) .chart-title { color: #f1f5f9; }
:global(body.dark-mode) .chart-subtitle { color: #64748b; }
:global(body.dark-mode) .stat-card--indigo .stat-icon { background: rgba(99,102,241,0.15); }
:global(body.dark-mode) .stat-card--pink .stat-icon { background: rgba(236,72,153,0.15); }
:global(body.dark-mode) .stat-card--amber .stat-icon { background: rgba(245,158,11,0.15); }
:global(body.dark-mode) .stat-card--teal .stat-icon { background: rgba(20,184,166,0.15); }
</style>
