<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from "@/helpers/pms_axios";
import { useUsersStore } from "@/stores/user";
import { showAlert, showConfirm } from "@/helpers/essential";
import VueApexCharts from "vue3-apexcharts";

const userstore = useUsersStore();
const reports = ref([]);
const loading = ref(true);

watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });
const generating = ref(false);

// Chart Data (Mock for HR Dashboard)
const chartSeries = ref([{
    name: 'Average Rating',
    data: [4.2, 3.8, 4.5, 3.9, 4.1]
}]);

const chartOptions = ref({
    chart: { type: 'bar', height: 350, toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, horizontal: true, } },
    dataLabels: { enabled: true },
    xaxis: { categories: ['IT', 'HR', 'Sales', 'Finance', 'Operations'] },
    colors: ['#7c3aed']
});

const newReport = ref({
  title: "",
  type: "",
  department_id: "",
  date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString()
    .substr(0, 10),
  date_to: new Date().toISOString().substr(0, 10),
  notes: "",
});

const stats = computed(() => {
  return {
    total: reports.value.length,
    thisMonth: reports.value.filter((r) => {
      const date = new Date(r.created_at);
      const now = new Date();
      return (
        date.getMonth() === now.getMonth() &&
        date.getFullYear() === now.getFullYear()
      );
    }).length,
    pending: 0, // Mock data based on reference
    shared: 0, // Mock data based on reference
  };
});

const fetchReports = async () => {
  loading.value = true;
  try {
    const response = await axios.get("pms/reports");
    if (response.data.status === "success") {
      reports.value = response.data.data;
    }
  } catch (error) {
    console.error("Error fetching reports:", error);
  } finally {
    loading.value = false;
  }
};

const generateReport = async () => {
  if (
    !newReport.value.title ||
    !newReport.value.type ||
    !newReport.value.date_from ||
    !newReport.value.date_to
  ) {
    showAlert(
      "Missing Information",
      "Please fill in all required fields.",
      "warning"
    );
    return;
  }
  generating.value = true;
  try {
    const response = await axios.post("pms/reports", newReport.value);
    if (response.data.status === "success") {
      showAlert("Success", "Report generated successfully!", "success");
      fetchReports();
      newReport.value.title = "";
      newReport.value.notes = "";
      newReport.value.type = "";
    }
  } catch (error) {
    console.error("Error generating report:", error);
  } finally {
    generating.value = false;
  }
};

const deleteReport = async (id) => {
  const result = await showConfirm(
    "Delete Report",
    "Are you sure you want to delete this report?",
    "warning",
    "Yes, Delete"
  );
  if (!result.isConfirmed) return;

  try {
    await axios.delete(`pms/reports/${id}`);
    fetchReports();
    showAlert("Deleted", "Report has been deleted.", "success");
  } catch (error) {
    console.error("Error deleting report:", error);
  }
};

const useTemplate = (template) => {
  const today = new Date();
  switch (template) {
    case "monthly":
      newReport.value.type = "performance_summary";
      newReport.value.title = `Monthly Performance Summary - ${today.toLocaleDateString(
        "en-US",
        { month: "long", year: "numeric" }
      )}`;
      break;
    case "quarterly":
      newReport.value.type = "department_overview";
      const quarter = Math.ceil((today.getMonth() + 1) / 3);
      newReport.value.title = `Q${quarter} ${today.getFullYear()} Department Review`;
      break;
    case "annual":
      newReport.value.type = "performance_summary";
      newReport.value.title = `Annual Performance Report ${today.getFullYear()}`;
      break;
    case "comparison":
      newReport.value.type = "rating_distribution";
      newReport.value.title = `Year-over-Year Performance Comparison`;
      break;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return "N/A";
  return new Date(dateStr).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

const formatType = (type) => {
  const types = {
    performance_summary: "Performance Summary",
    department_overview: "Department Overview",
    training_needs: "Training Needs",
    goal_completion: "Goal Completion",
    rating_distribution: "Rating Distribution",
    appraisal_summary: "Appraisal Summary", // Backward comp
    department_ranking: "Department Ranking", // Backward comp
  };
  return types[type] || type.replace("_", " ");
};

onMounted(() => {
  fetchReports();
});
</script>

<template>
  <div class="reports-container min-h-screen">
    <!-- Compact Premium Page Header -->
    <div class="relative overflow-hidden bg-[#1A237E] rounded-2xl mb-6 border border-white/5 shadow-xl animate-slide-up">
        <!-- Abstract Background Glows -->
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-indigo-600/20 blur-[100px] rounded-full animate-pulse"></div>
        <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-purple-600/10 blur-[80px] rounded-full"></div>
        
        <div class="relative px-6 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 rotate-3 group-hover:rotate-0 transition-transform duration-500">
                    <i class="pi pi-chart-bar text-xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight uppercase leading-none">PMS Performance Audit</h1>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="text-indigo-200/60 text-[9px] font-bold uppercase tracking-widest">Global Review Validation Matrix</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="px-4 py-2 bg-white/5 backdrop-blur-md rounded-xl border border-white/10 flex flex-col items-center">
                    <span class="text-[9px] font-bold text-indigo-300 uppercase tracking-widest leading-none mb-1">Total Audits</span>
                    <span class="text-lg font-black text-white leading-none">{{ stats.total }}</span>
                </div>
                <div class="px-4 py-2 bg-emerald-500/10 backdrop-blur-md rounded-xl border border-emerald-500/20 flex flex-col items-center">
                    <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-widest leading-none mb-1">HR Verified</span>
                    <span class="text-lg font-black text-white leading-none">{{ stats.verified || 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Section (Premium KPI Cards) -->
    <div class="mb-8 animate-slide-up" style="animation-delay: 100ms">
        <div class="flex items-center justify-between mb-4 px-1">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-black text-gray-800 text-[10px] uppercase tracking-wider">Managerial Evaluation Index</h3>
            </div>
            <span class="text-[9px] font-bold text-gray-400 uppercase bg-gray-100 px-2 py-1 rounded-md">Archive Protocol: Active</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
            <!-- IT Card -->
            <div class="group relative bg-white rounded-2xl border border-gray-100 p-6 hover:border-indigo-200 transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/5">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="pi pi-desktop text-4xl text-indigo-600"></i>
                </div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Information Tech</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-gray-900 leading-none">4.2</span>
                    <span class="text-[10px] font-bold text-emerald-500">/ 5.0</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 font-bold text-[10px] text-emerald-600">
                        <i class="pi pi-arrow-up-right"></i>
                        <span>+5.2%</span>
                    </div>
                    <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full" style="width: 84%"></div>
                    </div>
                </div>
            </div>

            <!-- HR Card -->
            <div class="group relative bg-white rounded-2xl border border-gray-100 p-6 hover:border-purple-200 transition-all duration-300 hover:shadow-xl hover:shadow-purple-500/5">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="pi pi-users text-4xl text-purple-600"></i>
                </div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Human Capital</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-gray-900 leading-none">3.8</span>
                    <span class="text-[10px] font-bold text-orange-400">/ 5.0</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 font-bold text-[10px] text-gray-400">
                        <i class="pi pi-minus"></i>
                        <span>STABLE</span>
                    </div>
                    <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full" style="width: 76%"></div>
                    </div>
                </div>
            </div>

            <!-- Sales Card -->
            <div class="group relative bg-white rounded-2xl border border-gray-100 p-6 hover:border-emerald-200 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-500/5">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="pi pi-chart-line text-4xl text-emerald-600"></i>
                </div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Revenue Growth</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-gray-900 leading-none">4.5</span>
                    <span class="text-[10px] font-bold text-emerald-500">/ 5.0</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 font-bold text-[10px] text-emerald-600">
                        <i class="pi pi-arrow-up-right"></i>
                        <span>+12.8%</span>
                    </div>
                    <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: 90%"></div>
                    </div>
                </div>
            </div>

            <!-- Finance Card -->
            <div class="group relative bg-white rounded-2xl border border-gray-100 p-6 hover:border-blue-200 transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/5">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="pi pi-wallet text-4xl text-blue-600"></i>
                </div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Fiscal Admin</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-gray-900 leading-none">3.9</span>
                    <span class="text-[10px] font-bold text-blue-400">/ 5.0</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 font-bold text-[10px] text-blue-600">
                        <i class="pi pi-arrow-up-right"></i>
                        <span>+2.4%</span>
                    </div>
                    <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full" style="width: 78%"></div>
                    </div>
                </div>
            </div>

            <!-- Operations Card -->
            <div class="group relative bg-white rounded-2xl border border-gray-100 p-6 hover:border-orange-200 transition-all duration-300 hover:shadow-xl hover:shadow-orange-500/5">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="pi pi-cog text-4xl text-orange-600"></i>
                </div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Logistics Ops</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-gray-900 leading-none">4.1</span>
                    <span class="text-[10px] font-bold text-orange-400">/ 5.0</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 font-bold text-[10px] text-rose-600">
                        <i class="pi pi-arrow-down-right"></i>
                        <span>-1.2%</span>
                    </div>
                    <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-500 rounded-full" style="width: 82%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-slide-up" style="animation-delay: 200ms">
      <!-- Main Content Area: Evaluation Governance -->
      <div class="lg:col-span-2 space-y-8">
        <!-- Audit Initialization -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden group">
          <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
            <div>
                <h3 class="font-black text-gray-800 text-base uppercase tracking-wider">Audit Initialization</h3>
                <p class="text-[10px] font-bold text-gray-400 mt-0.5">Generate performance reports from Line Manager evaluations</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-indigo-500 shadow-sm">
                <i class="pi pi-bolt"></i>
            </div>
          </div>
          
          <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-8">
              <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Analytical Framework</label>
                <select
                  v-model="newReport.type"
                  class="w-full !bg-gray-50 !border-gray-100 !rounded-xl !py-3 !px-4 !text-sm !font-bold focus:!bg-white focus:!ring-2 focus:!ring-indigo-500/10 transition-all outline-none"
                >
                  <option value="" disabled selected>Select analysis type</option>
                  <option value="performance_summary">Performance Summary</option>
                  <option value="department_overview">Department Overview</option>
                  <option value="training_needs">Training Needs Analysis</option>
                  <option value="goal_completion">Goal Completion Report</option>
                  <option value="rating_distribution">Rating Distribution</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Target Department</label>
                <select
                  v-model="newReport.department_id"
                  class="w-full !bg-gray-50 !border-gray-100 !rounded-xl !py-3 !px-4 !text-sm !font-bold focus:!bg-white focus:!ring-2 focus:!ring-indigo-500/10 transition-all outline-none"
                >
                  <option value="">All Business Units</option>
                  <option value="engineering">Engineering</option>
                  <option value="marketing">Marketing</option>
                  <option value="design">Design</option>
                  <option value="hr">Human Resources</option>
                  <option value="finance">Finance Control</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Timeline Initiation</label>
                <input
                  type="date"
                  v-model="newReport.date_from"
                  class="w-full !bg-gray-50 !border-gray-100 !rounded-xl !py-3 !px-4 !text-sm !font-bold focus:!bg-white focus:!ring-2 focus:!ring-indigo-500/10 transition-all outline-none"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Timeline Conclusion</label>
                <input
                  type="date"
                  v-model="newReport.date_to"
                  class="w-full !bg-gray-50 !border-gray-100 !rounded-xl !py-3 !px-4 !text-sm !font-bold focus:!bg-white focus:!ring-2 focus:!ring-indigo-500/10 transition-all outline-none"
                />
              </div>
            </div>

            <div class="space-y-2 mb-6">
              <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Evaluation Cycle Title</label>
              <input
                type="text"
                v-model="newReport.title"
                class="w-full !bg-gray-50 !border-gray-100 !rounded-xl !py-3 !px-4 !text-sm !font-bold focus:!bg-white focus:!ring-2 focus:!ring-indigo-500/10 transition-all outline-none"
                placeholder="Ex: Q3 Performance Validation Matrix"
              />
            </div>

            <div class="space-y-2 mb-10">
              <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Executive Annotations</label>
              <textarea
                v-model="newReport.notes"
                class="w-full !bg-gray-50 !border-gray-100 !rounded-xl !py-3 !px-4 !text-sm !font-bold focus:!bg-white focus:!ring-2 focus:!ring-indigo-500/10 transition-all outline-none resize-none"
                rows="3"
                placeholder="Add specific instructions for this validation audit..."
              ></textarea>
            </div>

            <div class="flex items-center gap-4">
              <button
                @click="generateReport"
                :disabled="generating"
                class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-indigo-600/20 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3 text-xs"
              >
                <i :class="generating ? 'pi pi-spin pi-spinner' : 'pi pi-check-square'"></i>
                {{ generating ? 'Analyzing...' : 'Execute Validation Audit' }}
              </button>
              <button
                class="px-8 py-4 bg-white border border-gray-100 text-gray-600 font-black uppercase tracking-widest rounded-2xl hover:bg-gray-50 transition-all active:scale-95 text-[10px]"
              >
                <i class="pi pi-eye mr-2"></i> Preview
              </button>
            </div>
          </div>
        </div>

        <!-- Review Validation Log (Table) -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="font-black text-gray-800 text-base uppercase tracking-wider">Audit Validation Log</h3>
                <p class="text-[10px] font-bold text-gray-400 mt-0.5">Historical Line Manager evaluations reviewed by HR</p>
            </div>
            <button class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-indigo-500 transition-colors shadow-sm">
              <i class="pi pi-filter"></i>
            </button>
          </div>

          <div v-if="reports.length === 0 && !loading" class="py-20 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-4 text-gray-200">
                <i class="pi pi-folder-open text-4xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-400 tracking-tight">No intelligence assets synthesized yet.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/30">
                  <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Subject / Role</th>
                  <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Review Status</th>
                  <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">HR Verified At</th>
                  <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr
                  v-for="report in reports"
                  :key="report.id"
                  class="group hover:bg-indigo-50/30 transition-colors"
                >
                  <td class="px-8 py-5">
                    <div class="flex flex-col">
                        <span class="text-sm font-black text-gray-800 leading-tight group-hover:text-indigo-600 transition-colors">{{ report.title }}</span>
                        <span class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-tighter">ID: #{{ report.id }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-5">
                    <span
                      class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                      :class="{
                        'bg-indigo-100 text-indigo-700': report.type.includes('performance'),
                        'bg-blue-100 text-blue-700': report.type.includes('department'),
                        'bg-emerald-100 text-emerald-700': report.type.includes('goal'),
                        'bg-orange-100 text-orange-700': report.type.includes('rating') || report.type.includes('training')
                      }"
                    >
                      {{ formatType(report.type) }}
                    </span>
                  </td>
                  <td class="px-6 py-5">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-black text-gray-700">{{ formatDate(report.created_at) }}</span>
                        <span class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">at {{ new Date(report.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                  </td>
                  <td class="px-8 py-5 text-right">
                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button class="w-8 h-8 rounded-lg bg-white border border-gray-100 shadow-sm flex items-center justify-center text-gray-400 hover:text-indigo-500 hover:border-indigo-100 transition-all">
                        <i class="pi pi-external-link text-[10px]"></i>
                      </button>
                      <button class="w-8 h-8 rounded-lg bg-white border border-gray-100 shadow-sm flex items-center justify-center text-gray-400 hover:text-emerald-500 hover:border-emerald-100 transition-all">
                        <i class="pi pi-download text-[10px]"></i>
                      </button>
                      <button @click="deleteReport(report.id)" class="w-8 h-8 rounded-lg bg-white border border-gray-100 shadow-sm flex items-center justify-center text-gray-400 hover:text-rose-500 hover:border-rose-100 transition-all">
                        <i class="pi pi-trash text-[10px]"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Selection Sidebar -->
      <div class="space-y-8 animate-slide-up" style="animation-delay: 300ms">
        <!-- Intelligence Summary Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-8 py-6 border-b border-gray-50 flex items-center gap-3 bg-gray-50/50">
            <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center text-white">
                <i class="pi pi-database text-xs"></i>
            </div>
            <h3 class="font-black text-gray-800 text-sm uppercase tracking-wider">Repository Stats</h3>
          </div>
          <div class="p-8 space-y-6">
            <div class="flex justify-between items-end">
              <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Synthetic Assets</span>
              <span class="text-2xl font-black text-gray-900 leading-none">{{ stats.total }}</span>
            </div>
            <div class="w-full h-2 bg-gray-50 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full" :style="{ width: (stats.total > 0 ? '100%' : '0%') }"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Monthly</span>
                    <span class="text-xl font-black text-gray-800">{{ stats.thisMonth }}</span>
                </div>
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Critical</span>
                    <span class="text-xl font-black text-gray-800">0</span>
                </div>
            </div>
          </div>
        </div>

        <!-- Rapid Prototyping (Templates) -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-8 py-6 border-b border-gray-50 flex items-center gap-3 bg-gray-50/50">
            <div class="w-8 h-8 rounded-lg bg-purple-500 flex items-center justify-center text-white">
                <i class="pi pi-clone text-xs"></i>
            </div>
            <h3 class="font-black text-gray-800 text-sm uppercase tracking-wider">Preset Blueprints</h3>
          </div>
          <div class="p-8 space-y-3">
            <button
              @click="useTemplate('monthly')"
              class="group w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:bg-indigo-50/30 transition-all duration-300"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gray-50 group-hover:bg-white flex items-center justify-center text-gray-400 group-hover:text-indigo-500 transition-colors">
                    <i class="pi pi-calendar text-xs"></i>
                </div>
                <span class="text-[11px] font-black text-gray-600 uppercase tracking-wider">Monthly Cycle</span>
              </div>
              <i class="pi pi-chevron-right text-[10px] text-gray-300 group-hover:translate-x-1 transition-transform"></i>
            </button>

            <button
              @click="useTemplate('quarterly')"
              class="group w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:bg-indigo-50/30 transition-all duration-300"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gray-50 group-hover:bg-white flex items-center justify-center text-gray-400 group-hover:text-indigo-500 transition-colors">
                    <i class="pi pi-calendar-plus text-xs"></i>
                </div>
                <span class="text-[11px] font-black text-gray-600 uppercase tracking-wider">Quarterly Audit</span>
              </div>
              <i class="pi pi-chevron-right text-[10px] text-gray-300 group-hover:translate-x-1 transition-transform"></i>
            </button>

            <button
              @click="useTemplate('annual')"
              class="group w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:bg-indigo-50/30 transition-all duration-300"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gray-50 group-hover:bg-white flex items-center justify-center text-gray-400 group-hover:text-indigo-500 transition-colors">
                    <i class="pi pi-file text-xs"></i>
                </div>
                <span class="text-[11px] font-black text-gray-600 uppercase tracking-wider">Annual Summary</span>
              </div>
              <i class="pi pi-chevron-right text-[10px] text-gray-300 group-hover:translate-x-1 transition-transform"></i>
            </button>

            <button
              @click="useTemplate('comparison')"
              class="group w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:bg-indigo-50/30 transition-all duration-300"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gray-50 group-hover:bg-white flex items-center justify-center text-gray-400 group-hover:text-indigo-500 transition-colors">
                    <i class="pi pi-chart-bar text-xs"></i>
                </div>
                <span class="text-[11px] font-black text-gray-600 uppercase tracking-wider">YoY Delta</span>
              </div>
              <i class="pi pi-chevron-right text-[10px] text-gray-300 group-hover:translate-x-1 transition-transform"></i>
            </button>
          </div>
        </div>

        <!-- System Export Protocols -->
        <div class="bg-indigo-900 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-indigo-900/20">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/5 blur-2xl rounded-full"></div>
            <h3 class="font-black text-base uppercase tracking-wider mb-2">Protocol Export</h3>
            <p class="text-[10px] text-indigo-300 font-bold mb-8 uppercase tracking-widest leading-relaxed">Select output format for decentralized sharing</p>
            
            <div class="space-y-3 relative z-10">
                <button class="w-full flex items-center justify-center gap-3 py-4 bg-white text-indigo-900 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-indigo-50 transition-colors active:scale-95">
                    <i class="pi pi-file-pdf"></i>
                    PDF Distribution
                </button>
                <button class="w-full flex items-center justify-center gap-3 py-4 bg-white/10 text-white border border-white/20 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-white/20 transition-colors active:scale-95">
                    <i class="pi pi-file-excel"></i>
                    Excel Dataset
                </button>
            </div>
        </div>
    </div>
  </div>
</div>
</template>

<style scoped></style>
