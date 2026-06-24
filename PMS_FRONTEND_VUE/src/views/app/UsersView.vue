<script setup>
    import { ref, onMounted, reactive } from 'vue'
    import { log, toastt} from '@/helpers/essential'
    import { positions, findposition } from '@/data/masterdata'
    import axios from 'axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const {authtoken } = userstore

    const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer

    let users = ref([])
    let loaded = ref(false)
    let creatinguser = ref(false)
    let saving = ref(false)
    let user = reactive({})

    let selecteduser = ref(null) 

    onMounted(() => {

        axios.post('fetchusers')
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

        axios.post('adduser', user, {
            
            }).then(res => {
                
                const data = res.data
                
                log(data)  

                users.value.push(data)
                Object.assign(user, data)
                saving.value = false
                toastt('User created successfully')
                creatinguser.value = false
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
                log(data) 
                saving.value = false
                toastt('User updated successfully')

                users.value[selecteduser.value] = data
                selecteduser.value = null
                creatinguser.value = false
            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                saving.value = false
                log(error)
            })

    }

    const edit = (u,index) =>{
        selecteduser.value = index
        Object.assign(user, u)
        creatinguser.value = true
    }

    const createuser = () => {
        user = {}
        creatinguser.value =true
    }
</script>
<template>
    <div class="h-full">
        <!-- Page Header -->
        <div class="mb-lg">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                    <p class="text-gray-500 mt-1">Manage system access and privileges</p>
                </div>
                <button @click="createuser" class="btn btn-primary px-4 py-2 rounded-lg shadow-sm flex items-center gap-2">
                    <i class="pi pi-plus"></i> Add New User
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
             <div class="card-header bg-white border-b border-gray-100 p-lg flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">System Users</h3>
                <span class="text-sm text-gray-500">Total: {{ users.length }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs">
                        <tr>
                            <th class="px-6 py-4">User Identity</th>
                            <th class="px-6 py-4">Email Address</th>
                            <th class="px-6 py-4 text-center">Role</th>
                            <th class="px-6 py-4">Position</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(user, ind) in users" :key="ind" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gray-100 overflow-hidden border border-gray-200">
                                        <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(user.name)+'&background=random&color=fff'" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500">@{{ user.username }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-medium">{{ user.email }}</td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="user.admin" class="px-2 py-1 bg-purple-50 text-purple-700 text-xs font-bold rounded border border-purple-100">Admin</span>
                                <span v-else class="px-2 py-1 bg-gray-50 text-gray-600 text-xs font-bold rounded border border-gray-200">User</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="block text-gray-700 font-medium">{{ findposition(user.position_id) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="edit(user, ind)" class="text-blue-600 hover:bg-blue-50 p-2 rounded-lg transition-colors text-xs font-bold uppercase">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!users.length && loaded">
                            <td colspan="5" class="p-10 text-center text-gray-400">
                                No users found in the system.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit User Modal -->
        <transition name="modal">
            <div v-if="creatinguser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click.self="creatinguser = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white">
                        <h3 class="font-bold text-lg text-gray-800">{{ user.id ? 'Edit User details' : 'Add New User' }}</h3>
                        <button @click="creatinguser = false" class="text-gray-400 hover:text-gray-600">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-gray-700">Full Name</label>
                            <input type="text" v-model="user.name" class="form-control" placeholder="John Doe" v-uppercase required>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-gray-700">Username</label>
                            <input type="text" v-model="user.username" class="form-control" placeholder="jdoe" v-uppercase required>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-gray-700">Email Address</label>
                            <input type="email" v-model="user.email" class="form-control" placeholder="john@example.com" v-uppercase required>
                        </div>
                        
                         <div class="space-y-1">
                            <label class="block text-sm font-bold text-gray-700">{{ user.id ? 'New Password (Optional)' : 'Password' }}</label>
                            <input type="password" v-model="user.password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-gray-700">Position</label>
                            <select v-model="user.position_id" class="form-control bg-white" required>
                                <option value="" disabled selected>Select Position</option>
                                <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div class="pt-2">
                             <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" v-model="user.admin" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary">
                                <div>
                                    <span class="block font-bold text-gray-700 text-sm">Administrator Access</span>
                                    <span class="block text-xs text-gray-500">Grant full system privileges to this user</span>
                                </div>
                            </label>
                        </div>

                    </div>

                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                        <button @click="creatinguser = false" class="px-4 py-2 rounded-lg text-gray-600 font-medium hover:bg-gray-200 transition-colors">Cancel</button>
                        <button v-if="!user.id" @click="create" :disabled="saving" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:bg-primary-dark shadow-sm flex items-center gap-2 disabled:opacity-50">
                            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                            {{ saving ? 'Creating...' : 'Create User' }}
                        </button>
                        <button v-else @click="update" :disabled="saving" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:bg-primary-dark shadow-sm flex items-center gap-2 disabled:opacity-50">
                             <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                            {{ saving ? 'Saving...' : 'Update User' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Standardize Inputs */
.form-control {
    width: 100%;
    padding: 0.6rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.form-control::placeholder {
    color: #cbd5e1;
}
</style>
