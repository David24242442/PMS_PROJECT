<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from '@/helpers/pms_axios'
import Swal from 'sweetalert2'
import { useUsersStore } from '@/stores/user'

const userstore = useUsersStore()
const { loguser } = userstore

// State
const loading = ref(false)
const logs = ref([])
const totalLogs = ref(0)
const currentPage = ref(1)
const perPage = ref(20)
const lastPage = ref(1)

const stats = reactive({
  total_logs: 0,
  today_logins: 0,
  today_activities: 0,
  unique_users_today: 0,
  data_changes_today: 0
})

// Filters
const filters = reactive({
  search: '',
  action: 'ALL',
  module: 'ALL',
  date_from: '',
  date_to: ''
})

// Modals
const showClearModal = ref(false)
const clearType = ref('date_range') // 'date_range', 'older_than', 'all'
const clearDateFrom = ref('')
const clearDateTo = ref('')
const clearOlderThan = ref('')
const isClearing = ref(false)

const showDetailsModal = ref(false)
const selectedLog = ref(null)

// Action options
const actionOptions = [
  { value: 'ALL', label: 'All Actions' },
  { value: 'LOGIN', label: 'Login' },
  { value: 'LOGOUT', label: 'Logout' },
  { value: 'CREATE', label: 'Create' },
  { value: 'UPDATE', label: 'Update' },
  { value: 'DELETE', label: 'Delete' },
  { value: 'APPROVE', label: 'Approve' },
  { value: 'REJECT', label: 'Reject' },
  { value: 'SUBMIT', label: 'Submit' },
  { value: 'SYNC', label: 'Sync' }
]

// Module options
const moduleOptions = [
  { value: 'ALL', label: 'All Modules' },
  { value: 'AUTH', label: 'Authentication' },
  { value: 'ONBOARDING', label: 'Onboarding' },
  { value: 'PMS_GOALS', label: 'PMS Goals' },
  { value: 'PMS_APPRAISAL', label: 'PMS Appraisal' },
  { value: 'PMS_REVIEW', label: 'PMS Review' },
  { value: 'EMPLOYEE_MASTER', label: 'Line Manager Console' },
  { value: 'USERS', label: 'User Management' }
]

// Fetch Activity Logs
const fetchLogs = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: filters.search || undefined,
      action: filters.action !== 'ALL' ? filters.action : undefined,
      module: filters.module !== 'ALL' ? filters.module : undefined,
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined
    }

    const res = await axios.get('activity-logs', { params })
    if (res.data && res.data.status === 'success') {
      const paginated = res.data.data
      logs.value = paginated.data || []
      totalLogs.value = paginated.total || 0
      currentPage.value = paginated.current_page || 1
      lastPage.value = paginated.last_page || 1

      if (res.data.stats) {
        Object.assign(stats, res.data.stats)
      }
    }
  } catch (error) {
    console.error('Failed to fetch activity logs:', error)
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  filters.search = ''
  filters.action = 'ALL'
  filters.module = 'ALL'
  filters.date_from = ''
  filters.date_to = ''
  fetchLogs(1)
}

// Clear Logs Action
const handleClearLogs = async () => {
  if (clearType.value === 'date_range' && (!clearDateFrom.value || !clearDateTo.value)) {
    Swal.fire({
      icon: 'warning',
      title: 'Date Range Required',
      text: 'Please select both start and end dates.',
      confirmButtonColor: '#1A237E'
    })
    return
  }

  if (clearType.value === 'older_than' && !clearOlderThan.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Cutoff Date Required',
      text: 'Please choose a cutoff date.',
      confirmButtonColor: '#1A237E'
    })
    return
  }

  const confirmMsg =
    clearType.value === 'all'
      ? 'Are you sure you want to permanently clear ALL activity logs? This action cannot be undone!'
      : clearType.value === 'older_than'
      ? `Permanently delete all logs older than ${clearOlderThan.value}?`
      : `Permanently delete all logs between ${clearDateFrom.value} and ${clearDateTo.value}?`

  const result = await Swal.fire({
    title: 'Confirm Log Deletion',
    text: confirmMsg,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Delete Logs'
  })

  if (!result.isConfirmed) return

  isClearing.value = true
  try {
    const payload = {
      type: clearType.value,
      date_from: clearDateFrom.value || undefined,
      date_to: clearDateTo.value || undefined,
      older_than_date: clearOlderThan.value || undefined
    }

    const res = await axios.post('activity-logs/clear', payload)
    if (res.data && res.data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Logs Cleared',
        text: res.data.message || 'Activity logs cleared successfully.',
        confirmButtonColor: '#1A237E'
      })
      showClearModal.value = false
      fetchLogs(1)
    }
  } catch (error) {
    console.error('Clear logs error:', error)
    Swal.fire({
      icon: 'error',
      title: 'Failed to Clear Logs',
      text: error.response?.data?.message || 'Server error occurred.',
      confirmButtonColor: '#1A237E'
    })
  } finally {
    isClearing.value = false
  }
}

// Helpers
const getActionClass = (action) => {
  const act = (action || '').toUpperCase()
  if (act === 'LOGIN') return 'bg-blue-50 text-blue-700 border-blue-200'
  if (act === 'LOGOUT') return 'bg-slate-100 text-slate-700 border-slate-300'
  if (act === 'CREATE' || act === 'APPROVE') return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  if (act === 'UPDATE' || act === 'SYNC') return 'bg-indigo-50 text-indigo-700 border-indigo-200'
  if (act === 'DELETE' || act === 'REJECT') return 'bg-rose-50 text-rose-700 border-rose-200'
  if (act === 'SUBMIT') return 'bg-amber-50 text-amber-700 border-amber-200'
  return 'bg-slate-50 text-slate-700 border-slate-200'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const openDetails = (log) => {
  selectedLog.value = log
  showDetailsModal.value = true
}

const ensureCurrentSessionLogged = async () => {
  try {
    if (loguser && (loguser.name || loguser.username)) {
      const empId = loguser.employee_code || loguser.username || ('USR-' + (loguser.id || '1'))
      await axios.post('activity-logs', {
        action: 'LOGIN',
        module: 'AUTH',
        description: `User ${loguser.name || loguser.username} (${empId}) active session verified on PMS.`,
        details: {
          username: loguser.username,
          employee_code: loguser.employee_code,
          department: loguser.department,
          source: 'HR Portal Sessions View'
        }
      })
    }
  } catch (e) {
    console.warn('Session beacon error:', e.message)
  }
}

const recordAndRefresh = async () => {
  if (loguser && (loguser.name || loguser.username)) {
    await ensureCurrentSessionLogged()
  }
  await fetchLogs(currentPage.value)
}

onMounted(async () => {
  // Set default clear cutoff date to 30 days ago
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  clearOlderThan.value = thirtyDaysAgo.toISOString().split('T')[0]

  // Proactively record session if current user is logged in
  if (loguser && (loguser.name || loguser.username)) {
    await ensureCurrentSessionLogged()
  }

  await fetchLogs(1)
})
</script>

<template>
  <div class="pms-sessions-container pb-12">
    <!-- Top Header Banner with High-Contrast Royal Navy Theme -->
    <div 
      class="pms-sessions-header-card p-6 sm:p-8 rounded-3xl shadow-xl mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6"
      style="background: linear-gradient(135deg, #1A237E 0%, #0D47A1 50%, #1565C0 100%) !important; color: #ffffff !important;"
    >
      <div class="flex items-center gap-4">
        <div 
          class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0"
          style="background: rgba(255, 255, 255, 0.18) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #80D8FF !important;"
        >
          <i class="pi pi-history"></i>
        </div>
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span 
              class="text-[11px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded-full"
              style="background: rgba(255, 255, 255, 0.2) !important; color: #80D8FF !important; border: 1px solid rgba(255, 255, 255, 0.3) !important;"
            >
              HR Administration &amp; Security
            </span>
          </div>
          <h1 
            class="text-2xl sm:text-3xl font-black tracking-tight m-0"
            style="color: #ffffff !important; text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;"
          >
            User Sessions &amp; Activity Logs
          </h1>
          <p 
            class="text-sm mt-1.5 mb-0 font-medium"
            style="color: #E0E7FF !important;"
          >
            Audit trail tracking user logins, employee creations, edits, approvals, and system-wide activities.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3 shrink-0">
        <button
          type="button"
          @click="recordAndRefresh"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm transition-all cursor-pointer shadow-sm hover:scale-[1.02]"
          style="background: rgba(255, 255, 255, 0.2) !important; color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35) !important;"
        >
          <i class="pi pi-refresh" :class="{ 'animate-spin': loading }"></i>
          <span>Refresh</span>
        </button>

        <button
          type="button"
          @click="showClearModal = true"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-md transition-all cursor-pointer hover:scale-[1.02]"
        >
          <i class="pi pi-trash"></i>
          <span>Delete / Clear Logs</span>
        </button>
      </div>
    </div>

    <!-- Summary Stats Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Activity Logs</div>
          <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.total_logs.toLocaleString() }}</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xl">
          <i class="pi pi-database"></i>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Today's Logins</div>
          <div class="text-2xl font-black text-blue-700 tracking-tight">{{ stats.today_logins.toLocaleString() }}</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 text-xl">
          <i class="pi pi-sign-in"></i>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Active Users Today</div>
          <div class="text-2xl font-black text-emerald-700 tracking-tight">{{ stats.unique_users_today.toLocaleString() }}</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 text-xl">
          <i class="pi pi-users"></i>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Data Changes Today</div>
          <div class="text-2xl font-black text-amber-700 tracking-tight">{{ stats.data_changes_today.toLocaleString() }}</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 text-xl">
          <i class="pi pi-file-edit"></i>
        </div>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs mb-6">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
        <!-- Search -->
        <div class="md:col-span-4 relative">
          <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input
            v-model="filters.search"
            @keyup.enter="fetchLogs(1)"
            type="text"
            placeholder="Search by user, employee ID, IP, or details..."
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1A237E] focus:ring-2 focus:ring-[#1A237E]/10"
          />
        </div>

        <!-- Action Filter -->
        <div class="md:col-span-2">
          <select
            v-model="filters.action"
            @change="fetchLogs(1)"
            class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:border-[#1A237E]"
          >
            <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- Module Filter -->
        <div class="md:col-span-2">
          <select
            v-model="filters.module"
            @change="fetchLogs(1)"
            class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:outline-none focus:border-[#1A237E]"
          >
            <option v-for="m in moduleOptions" :key="m.value" :value="m.value">
              {{ m.label }}
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div class="md:col-span-2">
          <input
            v-model="filters.date_from"
            @change="fetchLogs(1)"
            type="date"
            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#1A237E]"
            title="From Date"
          />
        </div>
        <div class="md:col-span-2 flex items-center gap-2">
          <input
            v-model="filters.date_to"
            @change="fetchLogs(1)"
            type="date"
            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#1A237E]"
            title="To Date"
          />
          <button
            type="button"
            @click="resetFilters"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition-all cursor-pointer"
            title="Reset Filters"
          >
            <i class="pi pi-filter-slash text-sm"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[#1A237E] text-white text-[11px] font-black uppercase tracking-wider">
              <th class="py-3.5 px-4">User</th>
              <th class="py-3.5 px-3">Action</th>
              <th class="py-3.5 px-3">Module</th>
              <th class="py-3.5 px-4">Activity Description</th>
              <th class="py-3.5 px-3">IP Address</th>
              <th class="py-3.5 px-4 text-right">Timestamp</th>
              <th class="py-3.5 px-3 text-center">Info</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
            <tr v-if="loading">
              <td colspan="7" class="py-12 text-center text-slate-500">
                <i class="pi pi-spin pi-spinner text-2xl text-[#1A237E] mb-2 block"></i>
                Loading activity logs...
              </td>
            </tr>
            <tr v-else-if="logs.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-500">
                <i class="pi pi-info-circle text-2xl text-slate-400 mb-2 block"></i>
                No activity logs found matching the filter criteria.
              </td>
            </tr>
            <tr
              v-else
              v-for="log in logs"
              :key="log.id"
              class="hover:bg-slate-50/80 transition-colors"
            >
              <!-- User -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-slate-100 text-[#1A237E] font-black text-xs flex items-center justify-center border border-slate-200 shrink-0">
                    {{ (log.user_name || 'U').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-extrabold text-slate-900 leading-snug">
                      {{ log.user_name || 'System / Guest' }}
                    </div>
                    <div v-if="log.employee_code" class="text-xs font-bold text-slate-400">
                      ID: {{ log.employee_code }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Action -->
              <td class="py-3.5 px-3">
                <span
                  class="inline-block px-2.5 py-1 rounded-lg text-xs font-extrabold border"
                  :class="getActionClass(log.action)"
                >
                  {{ log.action }}
                </span>
              </td>

              <!-- Module -->
              <td class="py-3.5 px-3">
                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">
                  {{ log.module }}
                </span>
              </td>

              <!-- Description -->
              <td class="py-3.5 px-4 max-w-md">
                <div class="text-sm font-semibold text-slate-800 line-clamp-2">
                  {{ log.description }}
                </div>
              </td>

              <!-- IP Address -->
              <td class="py-3.5 px-3 font-mono text-xs text-slate-500">
                {{ log.ip_address || '127.0.0.1' }}
              </td>

              <!-- Timestamp -->
              <td class="py-3.5 px-4 text-right text-xs font-bold text-slate-600 whitespace-nowrap">
                {{ formatDate(log.created_at) }}
              </td>

              <!-- Details button -->
              <td class="py-3.5 px-3 text-center">
                <button
                  type="button"
                  @click="openDetails(log)"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-[#1A237E] hover:bg-slate-100 transition-all cursor-pointer"
                  title="View Log Details"
                >
                  <i class="pi pi-eye text-sm"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-xs font-bold text-slate-500">
          Showing page {{ currentPage }} of {{ lastPage }} ({{ totalLogs.toLocaleString() }} total records)
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="fetchLogs(currentPage - 1)"
            :disabled="currentPage <= 1 || loading"
            class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-bold text-xs text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-all cursor-pointer"
          >
            Previous
          </button>

          <span class="text-xs font-extrabold text-slate-700 px-2">
            Page {{ currentPage }}
          </span>

          <button
            type="button"
            @click="fetchLogs(currentPage + 1)"
            :disabled="currentPage >= lastPage || loading"
            class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-bold text-xs text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-all cursor-pointer"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Clear / Delete Logs Modal -->
    <div
      v-if="showClearModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-fade-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
              <i class="pi pi-trash"></i>
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900 m-0">Delete Activity Logs</h3>
              <p class="text-xs font-semibold text-slate-500 m-0">Filter and clean up historic session entries</p>
            </div>
          </div>
          <button
            type="button"
            @click="showClearModal = false"
            class="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
          >
            <i class="pi pi-times text-lg"></i>
          </button>
        </div>

        <!-- Radio Options -->
        <div class="space-y-4 mb-6">
          <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:bg-slate-50/80 cursor-pointer transition-all">
            <input
              type="radio"
              v-model="clearType"
              value="date_range"
              class="mt-1 text-[#1A237E] focus:ring-[#1A237E]"
            />
            <div class="flex-1">
              <div class="font-bold text-sm text-slate-800">Delete Logs by Date Range</div>
              <div class="text-xs text-slate-500 mb-2">Delete logs recorded between two specific dates</div>
              <div v-if="clearType === 'date_range'" class="grid grid-cols-2 gap-2 mt-2">
                <div>
                  <label class="block text-[11px] font-bold text-slate-600 mb-1">Start Date</label>
                  <input
                    v-model="clearDateFrom"
                    type="date"
                    class="w-full px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300"
                  />
                </div>
                <div>
                  <label class="block text-[11px] font-bold text-slate-600 mb-1">End Date</label>
                  <input
                    v-model="clearDateTo"
                    type="date"
                    class="w-full px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300"
                  />
                </div>
              </div>
            </div>
          </label>

          <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:bg-slate-50/80 cursor-pointer transition-all">
            <input
              type="radio"
              v-model="clearType"
              value="older_than"
              class="mt-1 text-[#1A237E] focus:ring-[#1A237E]"
            />
            <div class="flex-1">
              <div class="font-bold text-sm text-slate-800">Delete Logs Older Than Date</div>
              <div class="text-xs text-slate-500 mb-2">Purge historic records prior to a cutoff date</div>
              <div v-if="clearType === 'older_than'" class="mt-2">
                <input
                  v-model="clearOlderThan"
                  type="date"
                  class="w-full px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300"
                />
              </div>
            </div>
          </label>

          <label class="flex items-start gap-3 p-3.5 rounded-xl border border-rose-200 bg-rose-50/40 hover:bg-rose-50 cursor-pointer transition-all">
            <input
              type="radio"
              v-model="clearType"
              value="all"
              class="mt-1 text-rose-600 focus:ring-rose-500"
            />
            <div>
              <div class="font-bold text-sm text-rose-700">Clear ALL Activity Logs</div>
              <div class="text-xs text-rose-600/80">Wipes all audit logs completely from the database</div>
            </div>
          </label>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="showClearModal = false"
            class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-100 transition-all cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="handleClearLogs"
            :disabled="isClearing"
            class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 transition-all shadow-md cursor-pointer disabled:opacity-50"
          >
            <i class="pi pi-trash mr-1.5" :class="{ 'animate-spin': isClearing }"></i>
            <span>{{ isClearing ? 'Deleting...' : 'Confirm & Delete' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div
      v-if="showDetailsModal && selectedLog"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 animate-fade-in max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4 shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#1A237E] flex items-center justify-center text-lg">
              <i class="pi pi-file"></i>
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900 m-0">Log Entry #{{ selectedLog.id }}</h3>
              <p class="text-xs font-semibold text-slate-500 m-0">{{ formatDate(selectedLog.created_at) }}</p>
            </div>
          </div>
          <button
            type="button"
            @click="showDetailsModal = false"
            class="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
          >
            <i class="pi pi-times text-lg"></i>
          </button>
        </div>

        <div class="space-y-3 overflow-y-auto pr-1 flex-1 text-sm">
          <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
            <div class="text-[11px] font-bold text-slate-400 uppercase">User</div>
            <div class="font-extrabold text-slate-800 text-sm">
              {{ selectedLog.user_name || 'System' }}
              <span v-if="selectedLog.employee_code" class="text-xs text-slate-500 font-semibold">
                (ID: {{ selectedLog.employee_code }})
              </span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <div class="text-[11px] font-bold text-slate-400 uppercase">Action</div>
              <div class="font-extrabold text-slate-800">{{ selectedLog.action }}</div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <div class="text-[11px] font-bold text-slate-400 uppercase">Module</div>
              <div class="font-extrabold text-slate-800">{{ selectedLog.module }}</div>
            </div>
          </div>

          <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Description</div>
            <div class="font-medium text-slate-800 mt-0.5">{{ selectedLog.description }}</div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <div class="text-[11px] font-bold text-slate-400 uppercase">IP Address</div>
              <div class="font-mono text-xs font-bold text-slate-700">{{ selectedLog.ip_address || '127.0.0.1' }}</div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <div class="text-[11px] font-bold text-slate-400 uppercase">Browser / Agent</div>
              <div class="text-xs text-slate-600 truncate" :title="selectedLog.user_agent">
                {{ selectedLog.user_agent || '-' }}
              </div>
            </div>
          </div>

          <div v-if="selectedLog.details" class="bg-slate-900 text-slate-200 p-3.5 rounded-xl font-mono text-xs overflow-x-auto">
            <div class="text-[10px] text-cyan-400 font-bold uppercase mb-1">Payload Details:</div>
            <pre class="m-0 whitespace-pre-wrap">{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 mt-4 text-right shrink-0">
          <button
            type="button"
            @click="showDetailsModal = false"
            class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-[#1A237E] hover:bg-indigo-900 transition-all cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pms-sessions-header-card {
  background: linear-gradient(135deg, #1A237E 0%, #0D47A1 50%, #1565C0 100%) !important;
  color: #ffffff !important;
}
.pms-sessions-header-card h1 {
  color: #ffffff !important;
}
.pms-sessions-header-card p {
  color: #E0E7FF !important;
}
.animate-fade-in {
  animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.97); }
  to { opacity: 1; transform: scale(1); }
}
</style>
