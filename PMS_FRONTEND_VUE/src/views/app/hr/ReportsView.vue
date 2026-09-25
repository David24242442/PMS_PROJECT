<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { showAlert, showConfirm } from "@/helpers/essential";
import VueApexCharts from "vue3-apexcharts";

const reports = ref([]);
const loading = ref(true);
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
  <div class="h-full pb-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-700">HR Reports</h1>
      <p class="text-gray-500 text-sm mt-1">
        Generate and manage performance reports
      </p>
    </div>

    <!-- Analytics Section (Replaced Chart with Cards) -->
    <div class="mb-8 p-1">
        <h3 class="font-bold text-gray-700 text-lg mb-4 pl-1">Department Performance Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
             <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col items-center text-center transform hover:-translate-y-1 transition-transform duration-300">
                <div class="w-12 h-12 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-lg mb-3">
                    <i class="pi pi-desktop"></i>
                </div>
                <h4 class="font-bold text-gray-800">IT</h4>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">4.2</div>
                <div class="text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded-full mt-2">
                    <i class="pi pi-arrow-up text-[10px]"></i> 5% vs last month
                </div>
            </div>

             <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col items-center text-center transform hover:-translate-y-1 transition-transform duration-300">
                <div class="w-12 h-12 rounded-full bg-pink-50 text-pink-600 flex items-center justify-center font-bold text-lg mb-3">
                    <i class="pi pi-users"></i>
                </div>
                <h4 class="font-bold text-gray-800">HR</h4>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">3.8</div>
                 <div class="text-xs text-orange-600 font-bold bg-orange-50 px-2 py-1 rounded-full mt-2">
                    <i class="pi pi-minus text-[10px]"></i> Stable
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col items-center text-center transform hover:-translate-y-1 transition-transform duration-300">
                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg mb-3">
                    <i class="pi pi-chart-line"></i>
                </div>
                <h4 class="font-bold text-gray-800">Sales</h4>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">4.5</div>
                 <div class="text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded-full mt-2">
                    <i class="pi pi-arrow-up text-[10px]"></i> 12% vs last month
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col items-center text-center transform hover:-translate-y-1 transition-transform duration-300">
                <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center font-bold text-lg mb-3">
                    <i class="pi pi-wallet"></i>
                </div>
                <h4 class="font-bold text-gray-800">Finance</h4>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">3.9</div>
                 <div class="text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded-full mt-2">
                    <i class="pi pi-arrow-up text-[10px]"></i> 2% vs last month
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col items-center text-center transform hover:-translate-y-1 transition-transform duration-300">
                <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-lg mb-3">
                    <i class="pi pi-cog"></i>
                </div>
                <h4 class="font-bold text-gray-800">Operations</h4>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">4.1</div>
                 <div class="text-xs text-red-600 font-bold bg-red-50 px-2 py-1 rounded-full mt-2">
                    <i class="pi pi-arrow-down text-[10px]"></i> 1% vs last month
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content Area (2 cols) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Report Generator Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 text-lg">Generate New Report</h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
              <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-600"
                  >Report Type</label
                >
                <select
                  v-model="newReport.type"
                  class="w-full form-input bg-white rounded-lg border-gray-300 text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 text-sm py-2"
                >
                  <option value="" disabled selected>Select report type</option>
                  <option value="performance_summary">
                    Performance Summary
                  </option>
                  <option value="department_overview">
                    Department Overview
                  </option>
                  <option value="training_needs">
                    Training Needs Analysis
                  </option>
                  <option value="goal_completion">
                    Goal Completion Report
                  </option>
                  <option value="rating_distribution">
                    Rating Distribution
                  </option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-600"
                  >Department</label
                >
                <select
                  v-model="newReport.department_id"
                  class="w-full form-input bg-white rounded-lg border-gray-300 text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 text-sm py-2"
                >
                  <option value="">All Departments</option>
                  <option value="engineering">Engineering</option>
                  <option value="marketing">Marketing</option>
                  <option value="design">Design</option>
                  <option value="hr">HR</option>
                  <option value="finance">Finance</option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-600"
                  >Date From</label
                >
                <input
                  type="date"
                  v-model="newReport.date_from"
                  class="w-full form-input bg-white rounded-lg border-gray-300 text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 text-sm py-2"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-600"
                  >Date To</label
                >
                <input
                  type="date"
                  v-model="newReport.date_to"
                  class="w-full form-input bg-white rounded-lg border-gray-300 text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 text-sm py-2"
                />
              </div>
            </div>

            <div class="space-y-1 mb-4">
              <label class="block text-sm font-medium text-gray-600"
                >Report Title</label
              >
              <input
                type="text"
                v-model="newReport.title"
                class="w-full form-input bg-white rounded-lg border-gray-300 text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 text-sm py-2"
                placeholder="Enter a descriptive title for this report"
              />
            </div>

            <div class="space-y-1 mb-6">
              <label class="block text-sm font-medium text-gray-600"
                >Additional Notes</label
              >
              <textarea
                v-model="newReport.notes"
                class="w-full form-input bg-white rounded-lg border-gray-300 text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 text-sm py-2 resize-none"
                rows="3"
                placeholder="Add any additional context or notes for this report..."
              ></textarea>
            </div>

            <div class="flex gap-3">
              <button
                @click="generateReport"
                :disabled="generating"
                class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2 text-sm disabled:opacity-70"
              >
                <i
                  class="pi mr-1"
                  :class="generating ? 'pi-spin pi-spinner' : 'pi-file'"
                ></i>
                Generate Report
              </button>
              <button
                class="px-5 py-2 bg-white border border-gray-300 text-gray-600 font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2 text-sm"
              >
                <i class="pi pi-eye mr-1"></i> Preview
              </button>
            </div>
          </div>
        </div>

        <!-- Recent Reports Table -->
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
        >
          <div
            class="px-6 py-4 border-b border-gray-100 flex justify-between items-center"
          >
            <h3 class="font-bold text-gray-700 text-lg">Recent Reports</h3>
            <button
              class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 flex items-center gap-2"
            >
              <i class="pi pi-filter"></i> Filter
            </button>
          </div>

          <div
            v-if="loading && reports.length === 0"
            class="flex justify-center p-8"
          >
            <i class="pi pi-spin pi-spinner text-purple-600 text-2xl"></i>
          </div>

          <div
            v-else-if="reports.length === 0"
            class="p-8 text-center text-gray-500 text-sm"
          >
            No reports found. Generate one to see it here.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="px-6 py-3 text-xs font-semibold text-gray-600">
                    Report Title
                  </th>
                  <th class="px-6 py-3 text-xs font-semibold text-gray-600">
                    Type
                  </th>
                  <th class="px-6 py-3 text-xs font-semibold text-gray-600">
                    Department
                  </th>
                  <th class="px-6 py-3 text-xs font-semibold text-gray-600">
                    Created
                  </th>
                  <th class="px-6 py-3 text-xs font-semibold text-gray-600">
                    Created By
                  </th>
                  <th class="px-6 py-3 text-xs font-semibold text-gray-600">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="report in reports"
                  :key="report.id"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-6 py-4 text-sm font-medium text-gray-800">
                    {{ report.title }}
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="px-2 py-1 rounded text-xs font-medium"
                      :class="{
                        'bg-purple-100 text-purple-700':
                          report.type.includes('performance'),
                        'bg-blue-100 text-blue-700':
                          report.type.includes('department'),
                        'bg-green-100 text-green-700':
                          report.type.includes('goal'),
                        'bg-orange-100 text-orange-700':
                          report.type.includes('rating') ||
                          report.type.includes('training'),
                      }"
                    >
                      {{ formatType(report.type) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    All Departments
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    {{ formatDate(report.created_at) }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">Current User</td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                      <button
                        class="p-1.5 border border-gray-300 rounded text-gray-500 hover:bg-gray-50 hover:text-purple-600 transition-colors"
                        title="View"
                      >
                        <i class="pi pi-eye"></i>
                      </button>
                      <button
                        class="p-1.5 border border-gray-300 rounded text-gray-500 hover:bg-gray-50 hover:text-blue-600 transition-colors"
                        title="Download"
                      >
                        <i class="pi pi-download"></i>
                      </button>
                      <button
                        @click="deleteReport(report.id)"
                        class="p-1.5 border border-gray-300 rounded text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors"
                        title="Delete"
                      >
                        <i class="pi pi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 text-lg">Report Summary</h3>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-500">Total Reports</span>
              <strong class="text-gray-800">{{ stats.total }}</strong>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-500">This Month</span>
              <strong class="text-gray-800">{{ stats.thisMonth }}</strong>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-500">Pending Reviews</span>
              <strong class="text-gray-800">{{ stats.pending }}</strong>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-500">Shared Reports</span>
              <strong class="text-gray-800">{{ stats.shared }}</strong>
            </div>
          </div>
        </div>

        <!-- Report Templates -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 text-lg">Quick Templates</h3>
          </div>
          <div class="p-6 space-y-3">
            <button
              @click="useTemplate('monthly')"
              class="w-full flex items-center gap-3 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-purple-600 transition-colors text-left"
            >
              <i class="pi pi-calendar"></i> Monthly Summary
            </button>
            <button
              @click="useTemplate('quarterly')"
              class="w-full flex items-center gap-3 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-purple-600 transition-colors text-left"
            >
              <i class="pi pi-calendar-plus"></i> Quarterly Review
            </button>
            <button
              @click="useTemplate('annual')"
              class="w-full flex items-center gap-3 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-purple-600 transition-colors text-left"
            >
              <i class="pi pi-file"></i> Annual Report
            </button>
            <button
              @click="useTemplate('comparison')"
              class="w-full flex items-center gap-3 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-purple-600 transition-colors text-left"
            >
              <i class="pi pi-chart-bar"></i> YoY Comparison
            </button>
          </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 text-lg">Export Options</h3>
          </div>
          <div class="p-6 space-y-3">
            <button
              class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-sm transition-colors text-sm"
            >
              <i class="pi pi-file-pdf"></i> Export as PDF
            </button>
            <button
              class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors text-sm"
            >
              <i class="pi pi-file-excel"></i> Export as Excel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
