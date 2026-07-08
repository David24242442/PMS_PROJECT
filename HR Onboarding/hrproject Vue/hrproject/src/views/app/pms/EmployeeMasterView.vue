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
// Modal States
const showEditModal = ref(false);
const showCreateModal = ref(false);
const showTeamModal = ref(false); // New modal for viewing team
const editingManager = ref(null); 
const searchQuery = ref('');
const teamMembers = ref([]); // Team members for the viewing manager
const teamSearchQuery = ref(''); // Internal search for the team modal

const filteredTeamMembers = computed(() => {
    if (!teamSearchQuery.value) return teamMembers.value;
    const q = teamSearchQuery.value.toLowerCase();
    return teamMembers.value.filter(emp => 
        (emp.name && emp.name.toLowerCase().includes(q)) || 
        (emp.employee_code && emp.employee_code.toLowerCase().includes(q))
    );
});

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
        const response = await axios.get('pms/get-employees');
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
            u.name.toLowerCase().includes(lowerQ) || 
            u.email.toLowerCase().includes(lowerQ) ||
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
    form.value = {
        team_members: [...teamMembers.value]
    };
    showTeamModal.value = false;
    showEditModal.value = true;
};

// Open "Manage Team" Modal
const manageTeam = (manager) => {
    editingManager.value = manager;
    
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
            employees: form.value.team_members.map(emp => ({
                employee_code: emp.employee_code,
                department: emp.department,
                location: emp.location
            }))
        };

        const response = await axios.post('pms/sync-team', payload);
        if (response.data.status === 'success') {
            showAlert('Success', response.data.message, 'success');
            await fetchUsers(); // Refresh the managers list
            await fetchMasterEmployees(); // Refresh the employees list to get new line_manager_ids
            // showEditModal.value = false; // Manual-close enabled
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
const searchEmployees = (event) => {
    const query = event.query.toLowerCase();
    filteredMasterEmployees.value = masterEmployees.value.filter(emp => 
        (emp.name && emp.name.toLowerCase().includes(query)) || 
        (emp.employee_code && emp.employee_code.toLowerCase().includes(query))
    );
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
                                <button @click="viewTeam(user)" class="p-1.5 rounded-lg text-black hover:bg-purple-300 transition-colors flex items-center gap-2 group" title="View Team">
                                    <i class="pi pi-users text-sm"></i>
                                    <span class="text-[10px] text-black uppercase tracking-tighter">View Team</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- "View Team" Modal -->
        <div v-if="showTeamModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden animate-fadeIn border border-white/20">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 backdrop-blur-sm">
                    <div>
                        <h3 class="font-black text-2xl text-gray-800 flex items-center gap-3">
                            <i class="pi pi-users text-purple-600 font-black"></i> Associated Team Members
                            <span class="text-xs bg-purple-100 text-purple-600 px-3 py-1 rounded-full font-black border border-purple-200">
                                {{ teamMembers.length }} Staff
                            </span>
                        </h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Listing employees reporting to {{ editingManager?.name }}</p>
                    </div>
                    <button @click="showTeamModal = false" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <div class="p-8">
                    <!-- Manager Info Summary -->
                    <div class="mb-8 p-6 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl border border-purple-100 flex items-center justify-between shadow-sm">
                         <div class="flex items-center gap-5">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center text-purple-600 font-black text-2xl border border-purple-200">
                                    {{ editingManager?.name?.charAt(0) }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full"></div>
                            </div>
                            <div>
                                <p class="font-black text-gray-800 text-lg leading-tight">{{ editingManager?.name }}</p>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-wide bg-white/50 px-2 py-0.5 rounded border border-purple-100">{{ editingManager?.department }}</span>
                                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-wide bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ editingManager?.employee_code }}</span>
                                </div>
                            </div>
                         </div>
                         <button @click="editTeamFromView" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-purple-200 flex items-center gap-2">
                            <i class="pi pi-user-edit"></i> Manage Team Assignments
                         </button>
                    </div>

                    <!-- Search within Team -->
                    <div class="mb-4 flex justify-between items-center">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Team Membership Roster</p>
                        <div class="relative w-64">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" placeholder="Filter team list..." v-model="teamSearchQuery"
                                class="w-full pl-9 pr-4 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-purple-200 outline-none transition-all" />
                        </div>
                    </div>

                    <!-- Team Table -->
                    <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm bg-gray-50/30">
                        <div class="max-h-[350px] overflow-y-auto overflow-x-hidden">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="bg-white text-gray-400 uppercase font-black text-[9px] tracking-widest border-b border-gray-100 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-4">Employee Name</th>
                                        <th class="px-6 py-4">Employee Code</th>
                                        <th class="px-6 py-4">Department</th>
                                        <th class="px-6 py-4">Position</th>
                                        <th class="px-6 py-4">Location</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="emp in filteredTeamMembers" :key="emp.employee_code" class="hover:bg-purple-50/30 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 text-gray-400 flex items-center justify-center text-[10px] font-black group-hover:bg-white group-hover:text-purple-600 transition-colors">
                                                    {{ emp.name?.charAt(0) || '?' }}
                                                </div>
                                                <span class="font-bold text-gray-700">{{ emp.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-[11px] text-indigo-500 font-black bg-indigo-50/50 px-2 py-0.5 rounded border border-indigo-100">{{ emp.employee_code }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-gray-500">{{ emp.department || '-' }}</td>
                                        <td class="px-6 py-4 text-xs font-bold text-gray-600">{{ emp.position || '-' }}</td>
                                        <td class="px-6 py-4 text-xs font-bold text-gray-400 italic">{{ emp.location || '-' }}</td>
                                    </tr>
                                    <tr v-if="filteredTeamMembers.length === 0">
                                        <td colspan="5" class="px-6 py-20 text-center">
                                            <div class="bg-gray-100 inline-flex p-6 rounded-full mb-4 text-gray-300">
                                                <i class="pi pi-users text-4xl"></i>
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

                <div class="px-8 py-6 border-t border-gray-100 flex justify-end bg-gray-50/50 gap-4">
                     <button @click="showTeamModal = false" class="px-8 py-2.5 rounded-xl bg-gray-800 text-white font-black text-xs uppercase tracking-widest hover:bg-gray-900 transition-all shadow-lg shadow-gray-200">Close Roster</button>
                </div>
            </div>
        </div>

        <!-- "Manage Team" Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden animate-fadeIn border border-white/20">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 backdrop-blur-sm">
                    <div>
                        <h3 class="font-black text-2xl text-gray-800 flex items-center gap-3">
                            <i class="pi pi-users text-purple-600"></i> Manage Associated Team
                        </h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Assign employees to this manager's reporting line</p>
                    </div>
                    <button @click="showEditModal = false" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-100 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
                
                <div class="p-8 space-y-8">
                    <!-- Selected Manager Card -->
                    <div class="p-6 bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl border border-purple-100/50 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center gap-6">
                            <div class="w-20 h-20 rounded-2xl bg-white shadow-sm flex items-center justify-center group overflow-hidden relative">
                                <div class="absolute inset-0 bg-purple-600 scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-500 opacity-5"></div>
                                <span class="font-black text-3xl text-purple-600 relative z-10">{{ editingManager.name.charAt(0) }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] bg-purple-600 text-white font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">Selected Manager</span>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-700 font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">{{ editingManager.employee_code }}</span>
                                </div>
                                <h4 class="font-black text-2xl text-gray-800 leading-none mb-2">{{ editingManager.name }}</h4>
                                <div class="flex items-center gap-4 text-xs font-bold text-gray-500">
                                    <span class="flex items-center gap-1.5"><i class="pi pi-briefcase text-purple-400"></i> {{ editingManager.department || 'No Department' }}</span>
                                    <span class="flex items-center gap-1.5"><i class="pi pi-envelope text-indigo-400"></i> {{ editingManager.email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-black text-gray-700 uppercase tracking-wide">Assign Team Members</label>
                                <span class="text-[10px] font-black text-purple-500 bg-purple-50 px-2 py-1 rounded-lg border border-purple-100">
                                    {{ form.team_members.length }} members selected
                                </span>
                            </div>
                            <MultiSelect 
                                v-model="form.team_members" 
                                :options="masterEmployees" 
                                optionLabel="full_string" 
                                filter 
                                filterBy="name,employee_code,full_string"
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
                                            {{ slotProps.option.name.charAt(0) }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-black text-gray-800 text-sm italic">{{ slotProps.option.name }}</div>
                                            <div class="text-[10px] font-semibold text-gray-400 flex items-center gap-2">
                                                <span>{{ slotProps.option.employee_code }}</span>
                                                <span v-if="slotProps.option.department">&bull; {{ slotProps.option.department }}</span>
                                            </div>
                                        </div>
                                        <div v-if="slotProps.option.line_manager_id" class="ml-2">
                                            <span v-if="slotProps.option.line_manager_id === editingManager.id" class="text-[8px] font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full border border-emerald-100">Current Team</span>
                                            <span v-else class="text-[8px] font-black text-amber-600 bg-amber-50 px-2 py-1 rounded-full border border-amber-100">Other Manager</span>
                                        </div>
                                    </div>
                                </template>
                            </MultiSelect>
                            <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                <p class="text-[11px] text-gray-500 font-medium leading-relaxed italic">
                                    <i class="pi pi-info-circle text-purple-500 mr-1"></i>
                                    Managers can oversee multiple employees. Note: Assigning an employee who is already in another team will automatically transfer them to this manager once saved.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 border-t border-gray-100 flex justify-end gap-4 bg-gray-50/50">
                    <button @click="showEditModal = false; showTeamModal = true" class="px-6 py-2.5 rounded-xl bg-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest hover:bg-slate-300 hover:text-slate-800 transition-all">Back to List</button>
                    <button @click="updateTeam" :disabled="saving" class="px-8 py-2.5 rounded-xl bg-purple-600 text-white font-black text-xs uppercase tracking-widest hover:bg-purple-700 hover:shadow-xl hover:shadow-purple-200 transition-all disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-purple-100">
                        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                        <span>{{ saving ? 'Syncing Team...' : 'Finalize Assignments' }}</span>
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
