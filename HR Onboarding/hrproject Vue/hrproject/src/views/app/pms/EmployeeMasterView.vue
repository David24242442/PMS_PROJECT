<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert } from '@/helpers/essential';
import AutoComplete from 'primevue/autocomplete';
import MultiSelect from 'primevue/multiselect';

const userstore = useUsersStore();
const { loguser } = userstore;

const users = ref([]);
const masterEmployees = ref([]); // List from HR Employee Table
const filteredMasterEmployees = ref([]); // For AutoComplete (HR Database)
const filteredUserCandidates = ref([]); // For AutoComplete (Users Table)
const loading = ref(true);

watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });

const saving = ref(false);
const csvUploading = ref(false);
const csvResult = ref(null); // { matched_count, unmatched_codes, total_parsed }
// Modal States
const showEditModal = ref(false);
const showCreateModal = ref(false);
const showTeamModal = ref(false); // Modal for viewing team
const showPushFreshModal = ref(false); // Confirmation modal for pushing fresh goals
const targetManagerForPush = ref(null);
const pushingGoals = ref(false);
const editingManager = ref(null); 
const searchQuery = ref('');
const teamMembers = ref([]); // Team members for the viewing manager
const teamSearchQuery = ref(''); // Internal search for the team modal

const filteredTeamMembers = computed(() => {
    if (!teamSearchQuery.value) return teamMembers.value;
    const q = teamSearchQuery.value.toLowerCase();
    return teamMembers.value.filter(emp => 
        (emp.name && emp.name.toLowerCase().includes(q)) || 
        (emp.employee_code && emp.employee_code.toLowerCase().includes(q)) ||
        (emp.department && emp.department.toLowerCase().includes(q)) ||
        (emp.position && emp.position.toLowerCase().includes(q)) ||
        (emp.designation && emp.designation.toLowerCase().includes(q)) ||
        (emp.location && emp.location.toLowerCase().includes(q))
    );
});

const triggerPushFreshGoals = (manager) => {
    targetManagerForPush.value = manager;
    showPushFreshModal.value = true;
};

const confirmPushFreshGoals = async () => {
    if (!targetManagerForPush.value) return;
    pushingGoals.value = true;
    try {
        const response = await axios.post('pms/push-fresh-goals', {
            manager_id: targetManagerForPush.value.id,
            year: 2026
        });
        if (response.data.status === 'success') {
            showAlert('Success', response.data.message || 'Fresh Goals & Appraisal templates pushed successfully!', 'success');
            showPushFreshModal.value = false;
        }
    } catch (error) {
        console.error('Error pushing fresh goals:', error);
        showAlert('Error', error.response?.data?.message || 'Failed to push fresh goals.', 'error');
    } finally {
        pushingGoals.value = false;
    }
};

// Form Data for Manager's Team
const form = ref({
    team_members: [] 
});

// Form Data for New User
const createForm = ref({
    candidate: null, // Selected from Users Table (as requested)
    username: '',
    email: '',
    password: 'Password', // Default
    admin: false,
    position_id: 1 // Default
});

// Fetch all users for table and dropdown
const fetchUsers = async () => {
    loading.value = true;
    try {
        const response = await axios.get('pms/employee-master');
        if (response.data.status === 'success') {
            users.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        showAlert('Error', 'Failed to fetch employee list.', 'error');
    } finally {
        loading.value = false;
    }
};

// Fetch Master Employee List (for Code Search)
const fetchMasterEmployees = async () => {
    try {
        const response = await axios.get('pms/get-employees?all=1');
        if (response.data.status === 'success') {
            masterEmployees.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching master employees:', error);
    }
};

// Filtered Users (Managers View - Show only managers, hide standard identities)
const filteredUsers = computed(() => {
    // Stage 1: Only show people explicitly enabled as Managers
    let result = users.value.filter(u => u.is_manager === 1 || u.is_manager === true);

    // Stage 2: Apply Search query
    if (searchQuery.value) {
        const lowerQ = searchQuery.value.toLowerCase();
        result = result.filter(u =>
            (u.name && u.name.toLowerCase().includes(lowerQ)) ||
            (u.email && u.email.toLowerCase().includes(lowerQ)) ||
            (u.department && u.department.toLowerCase().includes(lowerQ)) ||
            (u.employee_code && u.employee_code.toLowerCase().includes(lowerQ)) ||
            (u.position && u.position.toLowerCase().includes(lowerQ)) ||
            (u.location && u.location.toLowerCase().includes(lowerQ))
        );
    }
    return result;
});

// View Team Modal
const viewTeam = (manager) => {
    editingManager.value = manager;
    teamMembers.value = masterEmployees.value.filter(emp => emp.line_manager_id === manager.id);
    showTeamModal.value = true;
};

// Transition from View -> Edit
const editTeamFromView = () => {
    csvResult.value = null;
    form.value = {
        team_members: [...teamMembers.value]
    };
    showTeamModal.value = false;
    showEditModal.value = true;
};

// Open "Manage Team" Modal
const manageTeam = (manager) => {
    editingManager.value = manager;
    csvResult.value = null;

    // Find current team members from masterEmployees who have this manager as line_manager_id
    const team = masterEmployees.value.filter(emp => emp.line_manager_id === manager.id);

    form.value = {
        team_members: team
    };

    showEditModal.value = true;
};

// Update Team (Bulk Sync)
const updateTeam = async () => {
    if (!editingManager.value) return;

    saving.value = true;
    try {
        const payload = {
            manager_id: editingManager.value.id,
            year: 2026,
            employees: form.value.team_members.map(emp => ({
                employee_code: emp.employee_code,
                name: emp.name,
                department: emp.department,
                location: emp.location,
                position: emp.position || emp.designation,
                email: emp.email
            }))
        };

        const response = await axios.post('pms/sync-team', payload);
        if (response.data.status === 'success') {
            showAlert('Success', response.data.message, 'success');
            await fetchUsers(); // Refresh the managers list
            await fetchMasterEmployees(); // Refresh the employees list to get new line_manager_ids
            // Update teamMembers so the "Associated Team Members" view refreshes without page reload
            if (editingManager.value) {
                teamMembers.value = masterEmployees.value.filter(emp => emp.line_manager_id === editingManager.value.id);
            }
        }
    } catch (error) {
        console.error('Error syncing team:', error);
        showAlert('Error', 'Failed to update team assignments.', 'error');
    } finally {
        saving.value = false;
    }
};

// Create New User / Assign Manager Role
const createUser = async () => {
    if (!createForm.value.candidate) {
        showAlert('Warning', 'Please select a candidate from your users list.', 'warning');
        return;
    }
    
    saving.value = true;
    try {
        const payload = {
            id: createForm.value.candidate.id, // CRITICAL: Required for updateuser endpoint
            name: createForm.value.candidate.name,
            username: createForm.value.username || createForm.value.candidate.username,
            email: createForm.value.email || createForm.value.candidate.email || null,
            password: createForm.value.password,
            admin: createForm.value.admin || createForm.value.candidate.admin,
            employee_code: createForm.value.candidate.employee_code,
            department: createForm.value.candidate.department,
            location: createForm.value.candidate.location,
            position_id: createForm.value.position_id,
            is_manager: 1 // Explicitly mark as manager
        };

        // Determine if we are creating a new user or promoting an existing one
        const endpoint = createForm.value.candidate.id ? 'updateuser' : 'users';
        const response = await axios.post(endpoint, payload);
        
        if (response.data.status === 'success' || response.data.id) {
            showAlert('Success', 'Manager portal enabled successfully!', 'success');
            // showCreateModal.value = false; // Manual-close enabled
            // Reset form
            createForm.value = { candidate: null, username: '', email: '', password: 'Password', admin: false, position_id: 1 };
            
            // CRITICAL: Force clear and re-fetch to ensure UI updates
            users.value = []; 
            await fetchUsers();
        }
    } catch (error) {
        console.error('Error creating user:', error);
        showAlert('Error', 'Failed to process request. Check if user data is valid.', 'error');
    } finally {
        saving.value = false;
    }
};

// Auto-fill form when candidate is selected
const onCandidateSelect = (event) => {
    const user = event.value;
    createForm.value.username = user.username;
    createForm.value.email = user.email;
    createForm.value.position_id = user.position_id;
    createForm.value.admin = user.admin;
};

// Search from Users Table for Manager Creation
const searchUsers = (event) => {
    const query = event.query.toLowerCase();
    filteredUserCandidates.value = users.value.filter(user => 
        (user.name && user.name.toLowerCase().includes(query)) || 
        (user.employee_code && user.employee_code.toLowerCase().includes(query)) ||
        (user.username && user.username.toLowerCase().includes(query))
    );
};

// Filter employees for AutoComplete (General HR)
const searchEmployees = async (event) => {
    const query = (event.query || '').trim().toLowerCase();
    let matches = masterEmployees.value.filter(emp => 
        (emp.name && emp.name.toLowerCase().includes(query)) || 
        (emp.employee_code && emp.employee_code.toLowerCase().includes(query)) ||
        (emp.location && emp.location.toLowerCase().includes(query)) ||
        (emp.position && emp.position.toLowerCase().includes(query)) ||
        (emp.designation && emp.designation.toLowerCase().includes(query)) ||
        (emp.full_string && emp.full_string.toLowerCase().includes(query))
    );

    if (matches.length === 0 && query.length >= 2) {
        try {
            const response = await axios.get('pms/get-employees', { params: { search: query } });
            if (response.data && response.data.status === 'success' && response.data.data.length > 0) {
                matches = response.data.data;
            }
        } catch (err) {
            console.error('Error dynamic searchEmployees in EmployeeMasterView:', err);
        }
    }

    filteredMasterEmployees.value = matches;
};

// CSV Upload for Bulk Team Assignment
const handleCsvUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    csvUploading.value = true;
    csvResult.value = null;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post('pms/parse-team-csv', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.status === 'success') {
            const parsed = response.data.data;
            // Merge parsed employees with existing selection (avoid duplicates)
            const existingCodes = new Set(form.value.team_members.map(m => m.employee_code));
            const newMembers = parsed.filter(emp => !existingCodes.has(emp.employee_code));
            form.value.team_members = [...form.value.team_members, ...newMembers];

            csvResult.value = {
                matched_count: response.data.matched_count,
                unmatched_codes: response.data.unmatched_codes || [],
                total_parsed: response.data.total_parsed,
                new_added: newMembers.length
            };

            showAlert('Success', `${newMembers.length} employees added from CSV (${response.data.matched_count} matched, ${(response.data.unmatched_codes || []).length} unmatched).`, 'success');
        }
    } catch (error) {
        console.error('CSV upload error:', error);
        showAlert('Error', 'Failed to parse CSV file. Please check the format.', 'error');
    } finally {
        csvUploading.value = false;
        // Reset file input so the same file can be re-uploaded
        event.target.value = '';
    }
};

const downloadCsvTemplate = () => {
    const csvContent = 'employee_code\nEX001\nEX002\nEX003';
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'team_assignment_template.csv';
    link.click();
    URL.revokeObjectURL(link.href);
};

onMounted(() => {
    fetchUsers();
    fetchMasterEmployees();
});
</script>

<template>
    <div class="h-full pb-6">
        <!-- Page Header -->
        <div class="mb-6 bg-[#1A237E] backdrop-blur-sm border border-white/20 shadow-sm rounded-2xl p-5">
            <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                <div>
                    <h1 class="text-lg font-black text-white flex items-center gap-2">
                        <i class="pi pi-users text-purple-600 text-sm"></i> Line Manager Console
                    </h1>
                    <p class="text-xs font-bold text-white">Manage line manager accounts and their associated teams.</p>
                </div>
                <div class="flex items-center gap-3">   
                    <button @click="showCreateModal = true" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-xs font-black shadow-lg  transition-all flex items-center gap-2">
                        <i class="pi pi-user-plus"></i> Create Manager User
                    </button>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                        {{ filteredUsers.length }} managers
                    </span>
                    <div class="relative w-full md:w-64">
                        <input v-model="searchQuery" type="text" placeholder="Search managers..." 
                            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-200 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Table -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden min-h-[400px]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-black text-[10px] tracking-widest border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3">Name & Email</th>
                            <th class="px-5 py-3">Department</th>
                            <th class="px-5 py-3">Reports To</th>
                            <th class="px-5 py-3">Code</th>
                            <th class="px-5 py-3">Location</th>
                            <th class="px-5 py-3 text-right">Team</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-black shrink-0"
                                        :class="user.gender === 'Male' ? 'bg-blue-50 text-blue-600' : user.gender === 'Female' ? 'bg-pink-50 text-pink-600' : 'bg-gray-100 text-gray-500'">
                                        {{ user.name ? user.name.charAt(0) : '?' }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm">{{ user.name }}</p>
                                        <p class="text-[10px] text-gray-400">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span v-if="user.department" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-bold">{{ user.department }}</span>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <td class="px-5 py-3">
                                <div v-if="user.report_to" class="flex items-center gap-1.5">
                                    <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[9px] font-bold">
                                        {{ user.report_to.charAt(0) }}
                                    </div>
                                    <span class="font-medium text-gray-700 text-xs">{{ user.report_to }}</span>
                                </div>
                                <span v-else class="text-gray-300 text-xs italic">Unassigned</span>
                            </td>
                            <td class="px-5 py-3">
                                <span v-if="user.employee_code" class="font-mono text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ user.employee_code }}</span>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ user.location || '-' }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="triggerPushFreshGoals(user)" class="px-2.5 py-1.5 rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all flex items-center gap-1.5 shadow-xs" title="Push Fresh Goals & Appraisal Dossier">
                                        <i class="pi pi-send text-xs"></i>
                                        <span class="text-[10px] font-black uppercase tracking-tight">Push Goals</span>
                                    </button>
                                    <button @click="viewTeam(user)" class="px-2.5 py-1.5 rounded-lg text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition-all flex items-center gap-1.5 shadow-xs" title="View Team">
                                        <i class="pi pi-users text-xs"></i>
                                        <span class="text-[10px] font-black uppercase tracking-tight">View Team</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- "View Team" Modal (Associated Team Members) -->
        <div v-if="showTeamModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-md overflow-y-auto">
            <div class="bg-white w-full max-w-5xl max-h-[90vh] sm:max-h-[92vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden animate-fadeIn border border-white/20 my-auto">
                <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <div>
                        <h3 class="font-black text-xl sm:text-2xl text-gray-800 flex items-center gap-3">
                            <i class="pi pi-users text-purple-600 font-black"></i> Associated Team Members
                            <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-black border border-purple-200">
                                {{ teamMembers.length }} Staff
                            </span>
                        </h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Listing employees reporting to {{ editingManager?.name }}</p>
                    </div>
                    <button @click="showTeamModal = false" class="w-9 h-9 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 sm:p-8 space-y-6 flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                    <!-- Manager Info Summary Card -->
                    <div class="p-5 bg-gradient-to-r from-purple-50 via-indigo-50/40 to-white rounded-2xl border border-purple-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                         <div class="flex items-center gap-4">
                            <div class="relative shrink-0">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#1A237E] to-purple-700 text-white shadow-sm flex items-center justify-center font-black text-xl">
                                    {{ editingManager?.name?.charAt(0) }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></div>
                            </div>
                            <div>
                                <p class="font-black text-gray-800 text-lg leading-tight">{{ editingManager?.name }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-wide bg-white px-2 py-0.5 rounded border border-purple-100">{{ editingManager?.department || 'General' }}</span>
                                    <span class="text-[10px] font-mono font-black text-indigo-600 uppercase tracking-wide bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ editingManager?.employee_code }}</span>
                                    <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">{{ teamMembers.length }} Members</span>
                                </div>
                            </div>
                         </div>
                         <div class="flex items-center gap-2.5 shrink-0">
                             <button @click="triggerPushFreshGoals(editingManager)" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md shadow-emerald-200 flex items-center gap-2">
                                <i class="pi pi-send"></i>
                                <span>Push Fresh Goals</span>
                             </button>
                             <button @click="editTeamFromView" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-purple-200 flex items-center gap-2">
                                <i class="pi pi-user-edit"></i> Manage Assignments
                             </button>
                         </div>
                    </div>

                    <!-- Search within Team -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Team Membership Roster</p>
                        <div class="relative w-full sm:w-80">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" placeholder="Filter by name, code, dept, designation..." v-model="teamSearchQuery"
                                class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-200 outline-none transition-all" />
                        </div>
                    </div>

                    <!-- Team Table -->
                    <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm bg-gray-50/30">
                        <div class="max-h-[380px] overflow-y-auto overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="bg-white text-gray-500 uppercase font-black text-[9px] tracking-widest border-b border-gray-100 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-5 py-3.5">Employee Name</th>
                                        <th class="px-5 py-3.5">Employee Code</th>
                                        <th class="px-5 py-3.5">Department</th>
                                        <th class="px-5 py-3.5">Designation</th>
                                        <th class="px-5 py-3.5">Location</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="emp in filteredTeamMembers" :key="emp.employee_code" class="hover:bg-purple-50/30 transition-colors group">
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 text-gray-500 flex items-center justify-center text-[10px] font-black group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                                    {{ emp.name?.charAt(0) || '?' }}
                                                </div>
                                                <span class="font-bold text-gray-800 text-xs">{{ emp.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <span class="font-mono text-[11px] text-indigo-600 font-black bg-indigo-50 px-2.5 py-0.5 rounded border border-indigo-100">{{ emp.employee_code }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-xs font-bold text-gray-600">{{ emp.department || '-' }}</td>
                                        <td class="px-5 py-3.5 text-xs font-bold text-gray-600">{{ emp.designation || emp.position || '-' }}</td>
                                        <td class="px-5 py-3.5 text-xs font-bold text-gray-400 italic">{{ emp.location || '-' }}</td>
                                    </tr>
                                    <tr v-if="filteredTeamMembers.length === 0">
                                        <td colspan="5" class="px-6 py-16 text-center">
                                            <div class="bg-gray-100 inline-flex p-5 rounded-full mb-3 text-gray-300">
                                                <i class="pi pi-users text-3xl"></i>
                                            </div>
                                            <p class="text-gray-400 font-black text-xs uppercase tracking-widest">
                                                {{ teamSearchQuery ? 'No members match your search' : 'No team members assigned yet' }}
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-4 border-t border-gray-100 flex justify-end bg-gray-50/80 shrink-0">
                     <button @click="showTeamModal = false" class="px-6 py-2.5 rounded-xl bg-gray-800 text-white font-black text-xs uppercase tracking-widest hover:bg-gray-900 transition-all shadow-md shadow-gray-200">Close Roster</button>
                </div>
            </div>
        </div>

        <!-- "Manage Team" Modal (Manage Associated Team) -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-md overflow-y-auto">
            <div class="bg-white w-full max-w-4xl max-h-[90vh] sm:max-h-[92vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden animate-fadeIn border border-white/20 my-auto">
                <!-- Sticky Modal Header -->
                <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <div>
                        <h3 class="font-black text-xl sm:text-2xl text-gray-800 flex items-center gap-3">
                            <i class="pi pi-users text-purple-600 font-black"></i> Manage Associated Team
                        </h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Assign employees to this manager's reporting line</p>
                    </div>
                    <button @click="showEditModal = false" class="w-9 h-9 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                
                <!-- Scrollable Modal Body -->
                <div class="p-6 sm:p-8 space-y-5 flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                    <!-- Selected Manager Executive Card -->
                    <div class="p-5 bg-gradient-to-br from-purple-50 via-indigo-50/40 to-white rounded-2xl border border-purple-100 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#1A237E] to-purple-700 text-white shadow-md flex items-center justify-center text-xl font-black shrink-0">
                                {{ editingManager?.name?.charAt(0) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-[9px] bg-purple-600 text-white font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow-xs">Selected Line Manager</span>
                                    <span class="text-[10px] bg-indigo-50 text-indigo-700 font-mono font-black px-2 py-0.5 rounded border border-indigo-100">{{ editingManager?.employee_code }}</span>
                                    <span class="text-[10px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded border border-emerald-100">{{ form.team_members.length }} Member(s) In Selection</span>
                                </div>
                                <h4 class="font-black text-xl text-gray-800 leading-tight">{{ editingManager?.name }}</h4>
                                <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-gray-500 mt-1">
                                    <span class="flex items-center gap-1"><i class="pi pi-briefcase text-purple-500"></i> {{ editingManager?.department || 'General' }}</span>
                                    <span v-if="editingManager?.email" class="flex items-center gap-1"><i class="pi pi-envelope text-indigo-500"></i> {{ editingManager?.email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Automatic Provisioning & Goals Dossier Banner -->
                    <div class="p-4 bg-gradient-to-r from-purple-50 via-indigo-50/30 to-purple-50 rounded-2xl border border-purple-200/80 shadow-xs flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                            <i class="pi pi-bolt text-sm"></i>
                        </div>
                        <div class="flex-1 text-xs">
                            <div class="flex items-center gap-2">
                                <p class="font-black text-purple-950 uppercase tracking-wide">Automatic Account Provisioning &amp; Goals Dossier</p>
                                <span class="bg-emerald-100 text-emerald-800 text-[9px] font-black px-2 py-0.5 rounded uppercase">Automated</span>
                            </div>
                            <p class="text-purple-900 font-medium mt-1 leading-relaxed">
                                When you finalize team assignments, all members automatically receive their portal credentials:
                                <span class="font-mono font-black bg-white px-2 py-0.5 rounded border border-purple-200 text-purple-900">Username: Emp ID</span> &amp;
                                <span class="font-mono font-black bg-white px-2 py-0.5 rounded border border-purple-200 text-purple-900">Password: Password</span>.
                                A fresh, clean <strong>FY 2026 SMART Goals &amp; Appraisal dossier</strong> is automatically initialized for each employee to complete self-assessment and submit for <strong>{{ editingManager?.name }}</strong> to review.
                            </p>
                        </div>
                    </div>

                    <!-- CSV Bulk Upload Section -->
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-amber-50 to-orange-50/60 rounded-2xl border border-amber-200/80 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-white shadow-xs flex items-center justify-center text-amber-600 border border-amber-200 shrink-0">
                                    <i class="pi pi-file-import text-sm font-bold"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Bulk Upload via CSV</p>
                                    <p class="text-[9px] text-gray-500 font-bold">Upload a CSV with employee codes to auto-assign in bulk</p>
                                </div>
                            </div>
                            <button @click="downloadCsvTemplate" class="inline-flex items-center gap-1.5 text-[9px] font-black text-amber-800 bg-white hover:bg-amber-100 px-3 py-1.5 rounded-lg border border-amber-200 transition-all uppercase tracking-widest shadow-xs shrink-0 self-start sm:self-auto">
                                <i class="pi pi-download text-[10px]"></i> Download Format
                            </button>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white border-2 border-dashed border-amber-300 rounded-xl cursor-pointer hover:bg-amber-50/70 hover:border-amber-400 transition-all group">
                                <i class="pi pi-upload text-amber-500 group-hover:text-amber-600 text-sm"></i>
                                <span class="text-xs font-bold text-gray-600 group-hover:text-gray-800">
                                    {{ csvUploading ? 'Processing CSV Roster...' : 'Choose CSV File with Employee Codes' }}
                                </span>
                                <input type="file" accept=".csv,.txt" @change="handleCsvUpload" class="hidden" :disabled="csvUploading">
                            </label>
                        </div>
                        <!-- CSV Result Feedback -->
                        <div v-if="csvResult" class="mt-3 p-3 bg-white rounded-xl border border-gray-100 text-xs">
                            <div class="flex flex-wrap items-center gap-4">
                                <span class="font-black text-emerald-600"><i class="pi pi-check-circle mr-1"></i>{{ csvResult.new_added }} added</span>
                                <span class="font-bold text-gray-500">{{ csvResult.matched_count }}/{{ csvResult.total_parsed }} matched</span>
                                <span v-if="csvResult.unmatched_codes.length" class="font-bold text-red-500">
                                    <i class="pi pi-exclamation-circle mr-1"></i>{{ csvResult.unmatched_codes.length }} not found
                                </span>
                            </div>
                            <div v-if="csvResult.unmatched_codes.length" class="mt-2 text-[10px] text-red-400 font-mono">
                                Unmatched: {{ csvResult.unmatched_codes.join(', ') }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 my-1">
                        <div class="flex-1 border-t border-gray-200"></div>
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">or select individually</span>
                        <div class="flex-1 border-t border-gray-200"></div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider">Assign Team Members</label>
                            <span class="text-[10px] font-black text-purple-600 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-100">
                                {{ form.team_members.length }} members selected
                            </span>
                        </div>
                        <MultiSelect 
                            v-model="form.team_members" 
                            :options="masterEmployees" 
                            optionLabel="full_string" 
                            filter 
                            filterBy="name,employee_code,full_string,department,designation,position,location"
                            placeholder="Search by Name or Employee Code..."
                            class="custom-pms-multiselect w-full"
                            panelClass="custom-pms-panel"
                            :maxSelectedLabels="3"
                            display="chip"
                            :virtualScrollerOptions="{ itemSize: 55 }"
                        >
                            <template #option="slotProps">
                                <div class="flex items-center gap-3 py-1">
                                    <div class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 text-gray-500 flex items-center justify-center text-[10px] font-black">
                                        {{ slotProps.option.name?.charAt(0) }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-black text-gray-800 text-xs">{{ slotProps.option.name }}</div>
                                        <div class="text-[10px] font-semibold text-gray-400 flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-indigo-600 font-bold">{{ slotProps.option.employee_code }}</span>
                                            <span v-if="slotProps.option.department">&bull; {{ slotProps.option.department }}</span>
                                            <span v-if="slotProps.option.designation">&bull; {{ slotProps.option.designation }}</span>
                                            <span v-if="slotProps.option.location">&bull; <span class="italic text-gray-400">{{ slotProps.option.location }}</span></span>
                                        </div>
                                    </div>
                                    <div v-if="slotProps.option.line_manager_id" class="ml-2">
                                        <span v-if="slotProps.option.line_manager_id === editingManager.id" class="text-[8px] font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full border border-emerald-100">Current Team</span>
                                        <span v-else class="text-[8px] font-black text-amber-600 bg-amber-50 px-2 py-1 rounded-full border border-amber-100">Other Manager</span>
                                    </div>
                                </div>
                            </template>
                        </MultiSelect>
                        <div class="p-3.5 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <p class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                <i class="pi pi-info-circle text-purple-500 mr-1"></i>
                                Managers can oversee multiple employees. Note: Assigning an employee who is already in another team will automatically transfer them to this manager once saved.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sticky Modal Footer -->
                <div class="px-6 sm:px-8 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/80 shrink-0">
                    <button @click="showEditModal = false; showTeamModal = true" class="px-5 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-widest hover:bg-slate-300 transition-all">Back to List</button>
                    <button @click="updateTeam" :disabled="saving" class="px-7 py-2.5 rounded-xl bg-purple-600 text-white font-black text-xs uppercase tracking-widest hover:bg-purple-700 hover:shadow-xl hover:shadow-purple-200 transition-all disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-purple-100">
                        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                        <span>{{ saving ? 'Syncing Team...' : 'Finalize Assignments' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- "Push Fresh Goals" Confirmation Modal -->
        <div v-if="showPushFreshModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-md overflow-y-auto">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-fadeIn border border-white/20 my-auto">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-black text-lg text-gray-800 flex items-center gap-2.5">
                        <i class="pi pi-send text-emerald-600"></i> Push Fresh Goals &amp; Appraisal
                    </h3>
                    <button @click="showPushFreshModal = false" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 text-xs text-emerald-900 leading-relaxed">
                        <p class="font-black text-sm text-emerald-950 mb-1">
                            Push Fresh Blank Template to {{ targetManagerForPush?.name }}'s Team?
                        </p>
                        <p class="text-emerald-800 text-[11px] mt-2">
                            &bull; All assigned team members will receive a clean blank Goals &amp; Appraisal dossier ready for their self-assessment.<br>
                            &bull; Any historical completed reviews will remain safely preserved in review history.
                        </p>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        Are you sure you want to proceed with pushing fresh templates?
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                    <button @click="showPushFreshModal = false" :disabled="pushingGoals" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-500 font-bold text-xs uppercase hover:bg-white transition-all">Cancel</button>
                    <button @click="confirmPushFreshGoals" :disabled="pushingGoals" class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-black text-xs uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-200 flex items-center gap-2 disabled:opacity-50">
                        <i v-if="pushingGoals" class="pi pi-spin pi-spinner"></i>
                        <i v-else class="pi pi-check"></i>
                        <span>{{ pushingGoals ? 'Pushing...' : 'Confirm & Push Now' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- "Create Manager Account" Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-fadeIn border border-white/20">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="font-black text-2xl text-gray-800 flex items-center gap-3">
                            <i class="pi pi-user-plus text-purple-600 font-extrabold"></i> Enable Manager Portal
                        </h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Assign manager role to an existing user identity</p>
                    </div>
                    <button @click="showCreateModal = false" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <div class="p-8">
                    <div class="space-y-6">
                        <!-- Step 1: Select Candidate -->
                        <div class="p-5 bg-purple-50 rounded-2xl border border-purple-100 shadow-sm transition-all hover:shadow-md">
                            <label class="block text-[10px] font-black text-purple-600 uppercase tracking-widest mb-3">1. Select Candidate from Portal Users</label>
                            <AutoComplete 
                                v-model="createForm.candidate" 
                                :suggestions="filteredUserCandidates" 
                                @complete="searchUsers"
                                @item-select="onCandidateSelect"
                                optionLabel="name"
                                placeholder="Search by name, code or username..."
                                class="w-full"
                                inputClass="w-full px-4 py-3 border border-purple-200 rounded-xl focus:ring-4 focus:ring-purple-100 outline-none text-sm font-bold shadow-inner"
                            >
                                <template #item="slotProps">
                                    <div class="flex items-center gap-3 py-1">
                                        <div class="w-8 h-8 rounded-lg bg-white border border-purple-100 text-purple-600 flex items-center justify-center text-[10px] font-black shadow-sm">
                                            {{ slotProps.item.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-gray-800 text-xs italic">{{ slotProps.item.name }}</p>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tight">{{ slotProps.item.employee_code }} &bull; {{ slotProps.item.department }}</p>
                                        </div>
                                    </div>
                                </template>
                            </AutoComplete>
                        </div>

                        <!-- Step 2: User Details -->
                        <div class="grid grid-cols-2 gap-4" v-if="createForm.candidate">
                            <div class="space-y-1.5 px-1">
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Portal Username</label>
                                <input v-model="createForm.username" type="text"
                                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-xs font-black text-gray-800 shadow-sm focus:ring-4 focus:ring-purple-100 outline-none" placeholder="Username" />
                            </div>
                            <div class="space-y-1.5 px-1">
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Corporate Email (Optional)</label>
                                <input v-model="createForm.email" type="email"
                                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-xs font-black text-gray-800 shadow-sm focus:ring-4 focus:ring-purple-100 outline-none" placeholder="manager@company.com" />
                            </div>
                        </div>

                        <!-- Step 3: Role & Permissions -->
                        <div class="flex items-center gap-6 p-5 bg-gray-50/80 rounded-2xl border border-gray-100 shadow-inner">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-gray-400 border border-gray-200">
                                    <i class="pi pi-shield font-extrabold text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Portal Access Level</p>
                                    <p class="text-[9px] text-gray-400 font-bold">Defines manager visibility</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 ml-auto">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="createForm.admin" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                    <span class="ml-3 text-[10px] font-black text-gray-600 uppercase tracking-widest">Administrator</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 border-t border-gray-100 flex justify-end bg-gray-50/50 gap-4">
                    <button @click="showCreateModal = false" class="px-8 py-3 rounded-xl border border-gray-200 text-gray-400 font-black text-[10px] uppercase tracking-widest hover:bg-white transition-all shadow-sm">Discard</button>
                    <button @click="createUser" 
                        :disabled="saving || !createForm.candidate"
                        class="px-10 py-3 rounded-xl bg-purple-600 text-white font-black text-[10px] uppercase tracking-widest hover:bg-purple-700 hover:shadow-xl hover:shadow-purple-200 transition-all disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-purple-100">
                        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                        <span v-if="!saving">Finalize Manager Account</span>
                        <span v-else>Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- GLOBAL STYLES TO TARGET PORTALED DROPDOWNS -->
<style>
/* Dropdown Panel Styling - CRITICAL FIX FOR BACKGROUND & TEXT COLORS */
.p-multiselect-panel,
.custom-pms-panel,
.p-multiselect-panel.custom-pms-panel {
    border-radius: 16px !important;
    overflow: hidden !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1) !important;
    background-color: #ffffff !important;
    color: #000000 !important;
}

.p-multiselect-panel .p-multiselect-items-wrapper,
.custom-pms-panel .p-multiselect-items-wrapper {
    background-color: #ffffff !important;
}

.p-multiselect-panel .p-multiselect-header,
.custom-pms-panel .p-multiselect-header {
    background: #f9fafb !important;
    padding: 1rem !important;
    border-bottom: 1px solid #f3f4f6 !important;
    color: #000000 !important;
}

.p-multiselect-panel .p-multiselect-items,
.custom-pms-panel .p-multiselect-items {
    padding: 0.5rem !important;
    background-color: #ffffff !important;
}

.p-multiselect-panel .p-multiselect-item,
.custom-pms-panel .p-multiselect-item {
    border-radius: 10px !important;
    margin-bottom: 2px !important;
    transition: all 0.2s !important;
    color: #000000 !important;
    background-color: transparent !important;
}

.p-multiselect-panel .p-multiselect-item.p-highlight,
.custom-pms-panel .p-multiselect-item.p-highlight {
    background: #f5f3ff !important;
    color: #7c3aed !important;
}

.p-multiselect-panel .p-multiselect-item:not(.p-highlight):not(.p-disabled):hover,
.custom-pms-panel .p-multiselect-item:not(.p-highlight):not(.p-disabled):hover {
    background: #f9fafb !important;
    color: #000000 !important;
}

/* Search Input inside dropdown - FORCE BLACK TEXT */
.p-multiselect-panel .p-multiselect-filter-container .p-inputtext,
.custom-pms-panel .p-multiselect-filter-container .p-inputtext {
    border-radius: 10px !important;
    font-size: 13px !important;
    padding: 0.5rem 0.75rem 0.5rem 2.5rem !important;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    color: #000000 !important;
    width: 100% !important;
}

.p-multiselect-panel .p-multiselect-filter-container .p-inputtext::placeholder {
    color: #9ca3af !important;
}

/* Clear/Search icons */
.p-multiselect-filter-container .p-multiselect-filter-icon {
    color: #9ca3af !important;
    left: 0.75rem !important;
}

/* Custom virtual scroller item height */
.p-multiselect-panel .p-virtualscroller-item {
    height: auto !important;
}
</style>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom PrimeVue MultiSelect Trigger Area */
:deep(.custom-pms-multiselect) {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 2px;
    transition: all 0.2s;
}

:deep(.custom-pms-multiselect:hover) {
    border-color: #d1d5db;
    background: #ffffff;
}

:deep(.custom-pms-multiselect.p-focus) {
    border-color: #7c3aed;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    background: #ffffff;
}

:deep(.custom-pms-multiselect .p-multiselect-label) {
    padding: 0.5rem 0.75rem;
}

:deep(.custom-pms-multiselect .p-multiselect-token) {
    background: #f3f4f6;
    color: #374151;
    border-radius: 8px;
    font-weight: 700;
    font-size: 11px;
}

/* Dark Mode */
:global(body.dark-mode) .p-multiselect-panel,
:global(body.dark-mode) .custom-pms-panel {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
:global(body.dark-mode) .p-multiselect-panel .p-multiselect-items-wrapper,
:global(body.dark-mode) .p-multiselect-panel .p-multiselect-items {
    background-color: #1e293b !important;
}
:global(body.dark-mode) .p-multiselect-panel .p-multiselect-header {
    background: #1a2332 !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
:global(body.dark-mode) .p-multiselect-panel .p-multiselect-item {
    color: #f1f5f9 !important;
}
:global(body.dark-mode) .p-multiselect-panel .p-multiselect-item:hover {
    background: #334155 !important;
}
:global(body.dark-mode) .p-multiselect-panel .p-multiselect-filter-container .p-inputtext {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #f1f5f9 !important;
}
:global(body.dark-mode) :deep(.custom-pms-multiselect) {
    background: #0f172a !important;
    border-color: #475569 !important;
}
:global(body.dark-mode) :deep(.custom-pms-multiselect .p-multiselect-token) {
    background: #334155 !important;
    color: #f1f5f9 !important;
}
</style>
