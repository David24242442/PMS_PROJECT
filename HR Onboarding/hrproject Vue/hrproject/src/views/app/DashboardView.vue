<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/helpers/pms_axios';
import { log,imgburl } from '@/helpers/essential'
import { hospitalityDeptsIDs, finddept, findbranch, findcountry, findcompany, findgender } from '@/data/masterdata'
import { regionminis, regioncentral, regionsgra, regionswestern, regionashanti, regionvolta, regionnorth, regioneastern, regionbrong } from '@/data/masterdata'
import { useUsersStore } from '@/stores/user';

const userstore = useUsersStore()
const { loguser } = userstore

// State
const loading = ref(false)
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true })
const result = ref(null)
const loadingEmps = ref(false)
const showEmpsPopup = ref(false)
const emplist = ref([])
const visibleDialog = ref(false)

// Chart References (not needed for VueApexCharts but keeping for potential direct access)
// ...

// Computed Data for Charts
const hospitalitiesData = computed(() => result.value?.deptsCount?.filter((item) => hospitalityDeptsIDs.includes(item.joining_dept_id)) || [])
const melcomDeptsData = computed(() => result.value?.deptsCount?.filter((item) => !hospitalityDeptsIDs.includes(item.joining_dept_id)) || [])

// Region Data Logic
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

// Chart Options & Series
// 1. Melcom Departments
const seriesMelcom = computed(() => [{ name: 'Employees', data: melcomDeptsData.value.map(item => item.total_count) }])
const optionsMelcom = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, events: { dataPointSelection: (e, c, config) => loadEmpDepts(['dept', melcomDeptsData.value[config.dataPointIndex].joining_dept_id]) } },
    plotOptions: { bar: { borderRadius: 4, distributed: true, horizontal: true } }, // Horizontal for readability
    xaxis: { categories: melcomDeptsData.value.map(item => finddept(item.joining_dept_id)) },
    colors: ['#4f46e5', '#0ea5e9', '#8b5cf6', '#f43f5e', '#10b981'],
    legend: { show: false }
}))

// 2. Hospitality Departments
const seriesHospitality = computed(() => [{ name: 'Employees', data: hospitalitiesData.value.map(item => item.total_count) }])
const optionsHospitality = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, events: { dataPointSelection: (e, c, config) => loadEmpDepts(['dept', hospitalitiesData.value[config.dataPointIndex].joining_dept_id]) } },
    plotOptions: { bar: { borderRadius: 4, distributed: true, columnWidth: '50%' } },
    xaxis: { categories: hospitalitiesData.value.map(item => finddept(item.joining_dept_id)) },
    colors: ['#f59e0b', '#d97706', '#b45309'],
    legend: { show: false }
}))

// 3. Regions
const seriesRegions = computed(() => [{ name: 'Employees', data: finalLocationData.value.count }])
const optionsRegions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } }, // Keeping simple click logic for now, drilldown removed for simplicity in v1 refactor
    plotOptions: { bar: { borderRadius: 4, distributed: true } },
    xaxis: { categories: finalLocationData.value.names },
    colors: ['#3b82f6', '#2563eb', '#1d4ed8', '#1e40af', '#1e3a8a'],
    legend: { show: false }
}))

// 4. Gender (Donut/Pie)
const seriesGender = computed(() => result.value?.genderCounts?.filter(i => i.gender).map(item => item.total_count) || [])
const optionsGender = computed(() => ({
    labels: result.value?.genderCounts?.filter(i => i.gender).map(item => findgender(item.gender)) || [],
    chart: { type: 'donut', events: { dataPointSelection: (e, c, config) => loadEmpDepts(['gender', result.value.genderCounts[config.dataPointIndex].gender]) } },
    colors: ['#ec4899', '#3b82f6'], // Pink/Blue
    legend: { position: 'bottom' }
}))

// 5. Age Groups
const seriesAge = computed(() => [{ name: 'Employees', data: Object.values(result.value?.ageGroups || {}) }])
const optionsAge = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, events: { dataPointSelection: (e, c, config) => loadEmpDepts(['agegroup', Object.keys(result.value.ageGroups)[config.dataPointIndex]]) } },
    xaxis: { categories: Object.keys(result.value?.ageGroups || {}) },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '40%' } },
    colors: ['#14b8a6']
}))

// 6. Monthly Trend
const seriesMonthly = computed(() => [{ name: 'Joined', data: Object.values(result.value?.monthlyData || {}) }])
const optionsMonthly = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, events: { dataPointSelection: (e, c, config) => loadEmpDepts(['monthdata', Object.keys(result.value.monthlyData)[config.dataPointIndex]]) } },
    xaxis: { categories: Object.keys(result.value?.monthlyData || {}) },
    stroke: { curve: 'smooth' },
    colors: ['#8b5cf6'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.9, stops: [0, 90, 100] } }
}))

// Methods
const loadchart = () => {
    loading.value = true
    axios.get('dashboard')
        .then(res => {
            result.value = res.data
            loading.value = false
        })
        .catch(err => {
            console.error(err)
            loading.value = false
        })
}

const loadEmpDepts = (data) => {
    emplist.value = []
    loadingEmps.value = true
    showEmpsPopup.value = true

    axios.post('loadEmpDepts', { type: data[0], value: data[1] })
        .then(res => {
            emplist.value = res.data
            loadingEmps.value = false
        })
        .catch(err => {
            console.error(err)
            loadingEmps.value = false
        })
}

const vUppercase = {
    mounted(el) { el.addEventListener('input', (e) => { e.target.value = e.target.value.toUpperCase() }) }
}

onMounted(() => {
    loadchart()
})
</script>

<template>
    <div class="min-h-full pb-8 bg-gray-50/50 dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header mb-8 rounded-2xl p-6 shadow-lg shadow-purple-200"
             style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                 <div>
                     <h1 class="text-3xl font-extrabold text-white flex items-center gap-3 mb-2">
                        HR Dashboard
                        <button 
                             v-if="!loading"
                            @click="loadchart"
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
                         <span class="font-bold">Overview</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div v-if="result" class="space-y-6">
            
            <!-- Summary Cards Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Total Employees -->
                <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-purple-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-purple-500/20"
                     style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                    <div class="flex justify-between items-start z-10 relative">
                        <span class="text-purple-200 font-bold text-sm uppercase tracking-normal">Total Employees</span>
                        <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                            <i class="pi pi-users text-white"></i>
                        </div>
                    </div>
                    <div class="z-10 relative mt-2">
                        <div class="flex items-end gap-3 mb-1">
                            <span class="text-4xl font-extrabold text-white">{{ result?.totalemp }}</span>
                        </div>
                        <p class="text-[11px] text-purple-200 font-medium">Active Staff</p>
                    </div>
                </div>

                <!-- Total Departments -->
                <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-pink-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-pink-500/20"
                     style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                     <div class="flex justify-between items-start relative z-10">
                        <span class="text-pink-100 font-bold text-sm uppercase tracking-wider">Departments</span>
                        <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                            <i class="pi pi-building text-white"></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-2">
                        <div class="flex items-end gap-3 mb-1">
                             <span class="text-4xl font-extrabold text-white">{{ result?.deptsCount?.length || 0 }}</span>
                        </div>
                        <p class="text-[11px] text-pink-100 font-medium">Across Organization</p>
                    </div>
                </div>

                <!-- Total Branches -->
                  <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-orange-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-orange-500/20"
                     style="background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                     <div class="flex justify-between items-start relative z-10">
                        <span class="text-orange-100 font-bold text-sm uppercase tracking-wider">Branches</span>
                        <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                             <i class="pi pi-map-marker text-white"></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-2">
                        <div class="flex items-end gap-3 mb-1">
                             <span class="text-4xl font-extrabold text-white">{{ result?.locationCount?.length || 0 }}</span>
                        </div>
                        <p class="text-[11px] text-orange-100 font-medium">Regional Locations</p>
                    </div>
                </div>

                <!-- Total Companies -->
                 <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg shadow-teal-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-teal-500/20"
                     style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%) !important;">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                     <div class="flex justify-between items-start relative z-10">
                        <span class="text-teal-100 font-bold text-sm uppercase tracking-wider">Companies</span>
                         <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                            <i class="pi pi-briefcase text-white"></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-2">
                        <div class="flex items-end gap-3 mb-1">
                             <span class="text-4xl font-extrabold text-white">{{ result?.companiesCounts?.length || 0 }}</span>
                        </div>
                        <p class="text-[11px] text-teal-100 font-medium">Subsidiaries</p>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Monthly Trend (Full Width on mobile, half on large) -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 lg:col-span-2">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Monthly Joining Trend</h3>
                    <apexchart type="area" height="300" :options="optionsMonthly" :series="seriesMonthly"></apexchart>
                </div>

                <!-- Melcom Departments -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Melcom Departments</h3>
                    <apexchart type="bar" height="350" :options="optionsMelcom" :series="seriesMelcom"></apexchart>
                </div>

                <!-- Regions -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Employees by Region</h3>
                    <apexchart type="bar" height="350" :options="optionsRegions" :series="seriesRegions"></apexchart>
                </div>

                <!-- Gender & Age Group -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Gender Distribution</h3>
                    <apexchart type="donut" height="300" :options="optionsGender" :series="seriesGender"></apexchart>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Age Groups</h3>
                    <apexchart type="bar" height="300" :options="optionsAge" :series="seriesAge"></apexchart>
                </div>

                 <!-- Hospitality Departments -->
                 <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 lg:col-span-2">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Hospitality Departments</h3>
                    <apexchart type="bar" height="300" :options="optionsHospitality" :series="seriesHospitality"></apexchart>
                 </div>

            </div>

        </div>
    </div>

    <!-- Employee List Popup -->
    <Dialog v-model:visible="showEmpsPopup" modal header="Employee List" :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-500">{{ emplist.length }} employees found</span>
                <InputText placeholder="Search..." class="p-inputtext-sm" />
            </div>

            <div v-if="loadingEmps" class="flex justify-center p-8">
                <i class="pi pi-spin pi-spinner text-2xl"></i>
            </div>
            
            <div v-else class="space-y-2 max-h-[60vh] overflow-y-auto">
                <div v-for="(e, index) in emplist" :key="index" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex items-center gap-3">
                        <Avatar :image="e.profilepicture[0]?.path ? `${imgburl}${e.profilepicture[0]?.path}` : null" :label="!e.profilepicture[0]?.path ? e.firstname[0] : null" shape="circle" size="large" />
                        <div>
                            <p class="font-bold text-gray-800">{{ e.firstname }} {{ e.lastname }}</p>
                            <p class="text-xs text-gray-500">{{ e.emp_code }} | {{ e.employeeid }}</p>
                        </div>
                    </div>
                    <router-link :to="`/employee/${e.id}`" target="_blank">
                        <Button icon="pi pi-eye" text rounded severity="secondary" />
                    </router-link>
                </div>
                <div v-if="emplist.length === 0" class="text-center text-gray-500 p-4">
                    No employees found.
                </div>
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.dashboard-container {
    padding: 20px;
    max-width: 1600px;
    margin: 0 auto;
}
</style>