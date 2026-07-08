<script setup>
    import { ref, onMounted, reactive } from 'vue'
    import { log, toastt} from '@/helpers/essential'
    import { positions, findposition } from '@/data/masterdata'
    import axios from '@/helpers/pms_axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()

    let users = ref([])
    let loaded = ref(false)
    let creatinguser = ref(false)
    let saving = ref(false)
    let user = reactive({})
    
    // Constant definition to represent dynamic page accesses
    const categoryPages = [
        {
            name: 'Onboarding',
            pages: [
                { label: 'Dashboard', path: '/dashboard' },
                { label: 'Onboarding', path: '/onboarding' },
                { label: 'Employees', path: '/employees' }
            ]
        },
        {
            name: 'Performance',
            pages: [
                { label: 'PMS Dashboard', path: '/pms/dashboard' },
                { label: 'Goals', path: '/pms/goals' },
                { label: 'Appraisal', path: '/pms/appraisal' },
                { label: 'Review', path: '/pms/review' }
              
            ]
        },
        {
            name: 'HR Admin',
            pages: [
                { label: 'PMS Submissions', path: '/hr/submissions' },
                { label: 'Manage Users', path: '/users' },
                { label: 'Line Manager Console', path: '/pms/employee-master' } 
            ]
        }
    ]

    let activeTab = ref('Onboarding')

    const changeTab = (name) => {
        // console.log("Changing tab to:", name);
        activeTab.value = name;
    }

    let selecteduser = ref(null) 

    onMounted(() => {

        axios.get('users')
            .then(res => {
                const data = res.data
                users.value = data
                loaded.value = true
                log(data)
            })
            .catch((error) => {
                console.log(error)
            })
    })

    const vUppercase = {
        mounted(el) {
            el.addEventListener('input', updateValue)
        },
        unmounted(el) {
            el.removeEventListener('input', updateValue)
        }
    }

    const updateValue = (el) => {
        const input = el.target
        const sourceValue = input.value
        const newValue = sourceValue.toUpperCase()

        if (sourceValue !== newValue) {
            input.value = newValue
            input.dispatchEvent(new Event('input', { bubbles: true }))
        }
    }

    const create = () => {
        saving.value = true

        axios.post('users', user, {
            
            }).then(res => {
                const data = res.data
                
                if (data.status === 'error') {
                    // Extract first error for simpler Toast displays
                    const errMsgs = Object.values(data.errors).map(e => e[0]).join(', ')
                    toastt(data.message + ': ' + errMsgs, 'error')
                    saving.value = false
                    return;
                }

                log(data)  

                users.value.push(data)
                Object.assign(user, data)
                saving.value = false
                toastt('User created successfully')
                // creatinguser.value = false // Manual-close enabled
            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                saving.value = false
                log(error)
            })

    }

    const update = () => {
        saving.value = true

        axios.post('updateuser', user, {
            
            }).then(res => {
                const data = res.data

                if (data.status === 'error') {
                    toastt(data.message, 'error')
                    saving.value = false
                    return;
                }

                log(data) 
                saving.value = false
                toastt('User updated successfully')

                users.value[selecteduser.value] = data
                selecteduser.value = null
                // creatinguser.value = false // Manual-close enabled
            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                saving.value = false
                log(error)
            })

    }

    const edit = (u,index) =>{
        selecteduser.value = index
        // Ensure permissions array exists during edit
        Object.assign(user, { ...u, permissions: u.permissions || [] })
        creatinguser.value = true
    }

    const createuser = () => {
        // Safe clear reactive object
        Object.keys(user).forEach(k => delete user[k])
        user.permissions = [] // initialize array
        creatinguser.value = true
    }

    const togglePermission = (path) => {
        if (!user.permissions) user.permissions = []
        const index = user.permissions.indexOf(path)
        if (index > -1) {
            user.permissions.splice(index, 1)
        } else {
            user.permissions.push(path)
        }
    }
</script>
<template>
    <div>
        <h2>Users List</h2>
        <Button @click="createuser" class="mb-5">Create User</Button>
        <div>
            <div style="display: flex; flex-direction: column;">
                <div id='tableheader'>
                    <span>Username</span>
                    <span>Full Name</span>
                    <span>Code</span>
                    <span>Department</span>
                    <span>Admin</span>
                    <span>Position</span>
                    <span>Actions</span>
                </div>
                <div id='tablebody'> 
                    
                    <div
                        v-for="(user, ind) in users"
                        :key="ind"
                    >
                        <span>{{ user.username }}</span>
                        <span>{{ user.name }}</span>
                        <span class="text-xs font-bold text-gray-400">{{ user.employee_code || '---' }}</span>
                        <span class="text-xs">{{ user.department || '---' }}</span>
                        <span>{{ user.admin ? 'Yes' : 'No' }}</span>
                        <span>{{ findposition(user.position_id) }}</span> 
                        <span> 
                            <Button
                                icon="pi pi-user-edit"
                                area-label="Edit user"
                                @click="edit(user, ind)"
                            ></Button>
                        </span>
                        
                    </div>

                    <div v-if="!users.length && loaded" style="justify-content: center;">
                        No result for your search
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class='poppop' v-if="creatinguser" >
        <div class="poppopin">
            <div class='popheader' style="border-radius: 12px 12px 0 0; background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); padding: 25px 30px;">
                <h3 style="background:transparent; padding:0; text-align: left; font-size: 1.2rem;"> 
                    <i class="pi pi-user-plus mr-2"></i>
                    {{ user.id ? 'Manage': 'Create' }} Portal Identity
                </h3>
                <p class="text-[rgba(255,255,255,0.7)] text-[10px] font-bold uppercase tracking-widest mt-1">Personnel Account Configuration</p>
                <button class='poppopclose' @click="creatinguser = false" style="position: absolute; top: 30px; right: 25px; background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                    <i class="pi pi-times text-xs"></i>
                </button>
            </div>

            <div class="poplist !p-8">
                <!-- Group 1: Basic Identity -->
                <div class="mb-8">
                    <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        Basic Identity
                    </h4>
                    <div class="grid grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Full Name *</label>
                            <input type="text" v-model="user.name" v-uppercase required placeholder="Enter Full Name" class="premium-input">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Portal Username *</label>
                            <input type="text" v-model="user.username" v-uppercase required placeholder="e.g. JDoe" class="premium-input">
                        </div>
                    </div>
                </div>

                <!-- Group 2: Credential & Access -->
                <div class="mb-8">
                    <h4 class="text-[10px] font-black text-purple-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        Credentials & Access
                    </h4>
                    <div class="grid grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Email (Optional)</label>
                            <input type="email" v-model="user.email" placeholder="email@company.com" class="premium-input">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Password</label>
                            <input type="password" v-model="user.password" :placeholder="user.id ? '(Unchanged)' : 'Enter Password'" class="premium-input">
                        </div>
                    </div>
                </div>

                <!-- Group 3: Organizational Details -->
                <div class="mb-8">
                    <h4 class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        Organizational Details
                    </h4>
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Employee Code</label>
                            <input type="text" v-model="user.employee_code" placeholder="e.g. EX123" class="premium-input">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Position / Job Title</label>
                            <select v-model="user.position_id" class="premium-input">
                                <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Department</label>
                            <input type="text" v-model="user.department" placeholder="e.g. Accounts" class="premium-input">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Location</label>
                            <input type="text" v-model="user.location" placeholder="e.g. HO" class="premium-input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-5 items-end">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 ml-1">Reports To</label>
                            <select v-model="user.report_to" class="premium-input">
                            <option value="">(None)</option>
                            <option v-for="u in users" :key="u.id" :value="u.name">{{ u.name }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <input type="checkbox" id='isadmin' v-model="user.admin" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <label for="isadmin" class="text-[11px] font-black text-gray-700 uppercase tracking-widest mb-0 cursor-pointer">Grant Admin Access</label>
                        </div>
                    </div>
                </div>

                <!-- Assigned Pages Card Grid - Grouped Tabs -->
                <div class="mb-6">
                    <label style="color: #64748B; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;" class="mb-2 block">Assigned Pages & Tabs</label>
                    
                    <!-- Tab Headers -->
                    <div class="tabs-header mb-3" style="display: flex; gap: 8px; border-bottom: 2px solid #E2E8F0; padding-bottom: 8px;">
                        <span 
                            v-for="cat in categoryPages" 
                            :key="cat?.name" 
                            @click="cat?.name && changeTab(cat.name)"
                            :style="{
                                padding: '8px 12px',
                                cursor: 'pointer',
                                fontSize: '0.85rem',
                                fontWeight: '600',
                                color: activeTab === cat?.name ? '#6366F1' : '#64748B',
                                borderBottom: activeTab === cat?.name ? '2px solid #6366F1' : 'none',
                                marginBottom: '-10px'
                            }"
                        >
                            {{ cat?.name }}
                        </span>
                    </div>

                    <!-- Tab Content -->
                    <template v-for="cat in categoryPages" :key="cat?.name">
                        <div v-if="activeTab === cat?.name" class="mt-3">
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px;">
                                <div 
                                    v-for="page in cat?.pages" 
                                    :key="page.path"
                                    @click="togglePermission(page.path)"
                                    :class="['permission-card', { 'selected': user.permissions && user.permissions.includes(page.path) }]"
                                >
                                    <div class="card-checkbox">
                                        <i class="pi pi-check" v-show="user.permissions && user.permissions.includes(page.path)"></i>
                                    </div>
                                    <span class="card-label">{{ page.label }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                

                <div class="flex justify-start gap-4 mt-10">
                    <Button
                        id='create'
                        :disabled="saving"
                        @click="create"
                        v-if="!user.id"
                        :loading="saving"
                        class="!px-12 !py-4 !rounded-xl !bg-indigo-600 !border-none !font-black !uppercase !tracking-widest !text-[10px]"
                        label="Create Identity"
                    ></Button>

                    <Button
                        id='create'
                        :disabled="saving"
                        @click="update"
                        v-if="user.id"
                        class="!px-12 !py-4 !rounded-xl !bg-purple-600 !border-none !font-black !uppercase !tracking-widest !text-[10px]"
                        :loading="saving"
                        label="Save Changes"
                    ></Button>
                    
                    <button @click="creatinguser = false" class="px-8 py-4 rounded-xl border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                        Discard
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
    
    .poppop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 500;
        background-color: rgba(0,0,0,0.5); /* Semi-transparent overlay */
        backdrop-filter: blur(2px);
        padding: 50px 20%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .popheader h3{
        margin:0;
        background-color: var(--primary);
        color: white;
        text-align: center;
        padding: 15px;
        border-radius: 8px 8px 0 0;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .poppopin {
        background-color: var(--surface-card);
        width: 100%;
        max-width: 700px;
        max-height: 90vh;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
    }


    .poplist {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
        background-color: var(--surface-card);
    }
    .poplist .deux:nth-child(even){
        background-color: var(--surface-ground)
    }
    .poplist .deux:hover{
        background-color: var(--primary);
        color: white;
        cursor: pointer;
    }
    .poplist .deux span{
        padding:5px;
    }

    .double{
        display:flex;
        gap: 15px;
        margin-bottom: 20px;
    }
    .double > div{
        flex:1
    }
    
    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: var(--text-color);
    }
    
    input, select {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background-color: var(--surface-ground);
        color: var(--text-color);
        transition: border-color 0.2s;
        outline: none;
    }

    input:focus, select:focus {
        border-color: var(--primary);
        background-color: var(--surface-card);
    }

    .premium-input {
        width: 100%;
        padding: 12px 16px;
        background: #F8FAFC;
        border: 1.5px solid #E2E8F0;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #1E293B;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        outline: none;
    }
    
    .premium-input:focus {
        background: white;
        border-color: #6366F1;
        box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.1);
    }

    #tableheader{
        display: flex;
        justify-content: space-between; 
        font-weight: 600; 
        padding: 16px 24px;
        background-color: #f8fafc;
        color: #64748b;
        border-bottom: 2px solid #f1f5f9;
        border-radius: 12px 12px 0 0;
    }
    #tableheader > span{
        width: 14%;
        display: inline-block;
        text-align: left;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    #tablebody{
        background-color: white; 
        flex:1;
        overflow: auto;
        border-radius: 0 0 12px 12px;
        border: 1px solid #f1f5f9;
        border-top: none;
    }
    #tablebody > div{
        display: flex;
        justify-content: space-between; 
        border-bottom: 1px solid #f1f5f9; 
        padding: 16px 24px;
        transition: all .2s ease;
        align-items: center;
    }
    #tablebody > div:last-child {
        border-bottom: none;
    }
    #tablebody > div:hover{
        background-color: #f8fafc;
        transform: scale(1.002);
    }
    #tablebody > div > span{
        width: 14%;
        display: inline-block;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
    }
    #tablebody button{
        /* padding:5px 10px; */
        cursor: pointer;
    }

    /* PREMIUM PERMISSION CARDS CSS */
    .permission-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: white;
        border: 1.5px solid #E2E8F0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }
    .permission-card:hover {
        border-color: #6366F1;
        background: #F8FAFC;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .permission-card.selected {
        border-color: #6366F1;
        background: rgba(99, 102, 241, 0.06);
        box-shadow: 0 4px 8px -2px rgba(99, 102, 241, 0.15);
    }
    .card-checkbox {
        width: 20px;
        height: 20px;
        min-width: 20px;
        border-radius: 6px;
        border: 2px solid #CBD5E1;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        background: white;
    }
    .selected .card-checkbox {
        background: #6366F1;
        border-color: #6366F1;
        color: white;
    }
    .card-checkbox .pi {
        font-size: 0.7rem;
        font-weight: 800;
        color: white;
    }
    .card-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        line-height: 1.2;
        transition: color 0.2s;
    }
    .selected .card-label {
        color: #312E81 !important; /* Indigo 900 */
    }

    /* Dark Mode */
    :global(body.dark-mode) #tableheader {
        background-color: #1a2332 !important;
        color: #94a3b8 !important;
        border-color: #334155 !important;
    }
    :global(body.dark-mode) #tablebody {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    :global(body.dark-mode) #tablebody > div {
        border-color: #334155 !important;
    }
    :global(body.dark-mode) #tablebody > div:hover {
        background-color: #253347 !important;
    }
    :global(body.dark-mode) #tablebody > div > span {
        color: #cbd5e1 !important;
    }
    :global(body.dark-mode) .premium-input {
        background: #0f172a !important;
        border-color: #475569 !important;
        color: #f1f5f9 !important;
    }
    :global(body.dark-mode) .premium-input:focus {
        background: #1a2332 !important;
    }
    :global(body.dark-mode) .permission-card {
        background: #1a2332 !important;
        border-color: #475569 !important;
    }
    :global(body.dark-mode) .permission-card:hover {
        background: #253347 !important;
    }
    :global(body.dark-mode) .permission-card.selected {
        background: rgba(99, 102, 241, 0.15) !important;
    }
    :global(body.dark-mode) .card-checkbox {
        background: #0f172a !important;
        border-color: #475569 !important;
    }
    :global(body.dark-mode) .card-label {
        color: #cbd5e1 !important;
    }
    :global(body.dark-mode) .selected .card-label {
        color: #a5b4fc !important;
    }
</style>