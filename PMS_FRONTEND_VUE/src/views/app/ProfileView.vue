<script setup>
    import { ref, onMounted, reactive } from 'vue'
    import { log, toastt} from '@/helpers/essential'
    import axios from 'axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { authtoken, loguser } = userstore

    const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer
    
    let loaded = ref(false)
    let saving = ref(false)
    let user = reactive({})

    const showingopass = ref(false)
    const showingnpass = ref(false)
    const showingcpass = ref(false)

    let opassword = ref('')
    let npassword = ref('')
    let cpassword = ref('')

    onMounted(() => {
        Object.assign(user, loguser)
    })

    const save = () => {
        if (!opassword.value || !npassword.value || !cpassword.value) {
             toastt('Please fill all password fields', 'error')
             return
        }

        if(npassword.value !== cpassword.value){
            toastt('New passwords do not match', 'error')
            return
        }

        saving.value = true

        const userinfo = {
            opassword: opassword.value,
            npassword: npassword.value,
            cpassword: cpassword.value,
        }

        axios.post('updatepassword', userinfo)
            .then(res => {
                const data = res.data
                if(data.error){
                    toastt(data.error, 'error')
                    saving.value = false
                    return
                }
                saving.value = false
                toastt('Your password has been updated', 'success')
                // Reset fields
                opassword.value = ''
                npassword.value = ''
                cpassword.value = ''
            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                saving.value = false
                log(error)
            })
    }
</script>

<template>
    <div class="h-full">
        <!-- Enhanced Page Header with Gradient -->
        <div class="page-header rounded-xl shadow-lg mb-6">
            <div class="px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                            <i class="pi pi-user text-2xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">My Profile</h1>
                            <p class="text-purple-200 text-sm">Manage your account settings and preferences</p>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-2 text-white/80 text-sm">
                        <i class="pi pi-shield"></i>
                        <span>Account Security</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Left Column: User Info Card -->
            <div class="profile-sidebar">
                <!-- Profile Card with Gradient Banner -->
                <div class="profile-card bg-white rounded-xl shadow-md overflow-hidden">
                    <!-- Gradient Header Banner -->
                    <div class="profile-banner h-24"></div>
                    
                    <!-- Avatar & Basic Info -->
                    <div class="px-6 pb-6 -mt-12 relative">
                        <div class="avatar-container">
                            <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(user.name || 'User')+'&background=7c3aed&color=fff&size=200'" alt="Avatar" class="avatar-img" />
                            <div class="avatar-status"></div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <h2 class="text-xl font-bold text-gray-800">{{ user.name }}</h2>
                            <p class="text-purple-600 font-medium text-sm">{{ user.job_title || 'Employee' }}</p>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-3 mt-5">
                            <div class="stat-card bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-4 text-center border border-purple-100">
                                <i class="pi pi-building text-purple-500 text-lg mb-2"></i>
                                <p class="text-xs text-gray-500 uppercase font-semibold">Department</p>
                                <p class="text-sm font-bold text-gray-800 mt-1 truncate">{{ user.department || 'N/A' }}</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 text-center border border-blue-100">
                                <i class="pi pi-id-card text-blue-500 text-lg mb-2"></i>
                                <p class="text-xs text-gray-500 uppercase font-semibold">Employee ID</p>
                                <p class="text-sm font-bold text-gray-800 mt-1">{{ user.employee_id || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Card -->
                <div class="bg-white rounded-xl shadow-md p-5 mt-5">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                            <i class="pi pi-address-book text-white text-sm"></i>
                        </div>
                        <h3 class="font-bold text-gray-800">Contact Information</h3>
                    </div>
                    
                    <div class="space-y-4 mt-4">
                        <!-- Email -->
                        <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center">
                                <i class="pi pi-envelope text-purple-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 uppercase font-semibold">Email Address</p>
                                <p class="text-sm font-bold text-gray-800 truncate">{{ user.email }}</p>
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-100 to-emerald-100 flex items-center justify-center">
                                <i class="pi pi-phone text-green-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 uppercase font-semibold">Phone Number</p>
                                <p class="text-sm font-bold text-gray-800">{{ user.phone || 'Not set' }}</p>
                            </div>
                        </div>
                        
                        <!-- Joined Date -->
                        <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center">
                                <i class="pi pi-calendar text-amber-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 uppercase font-semibold">Member Since</p>
                                <p class="text-sm font-bold text-gray-800">{{ user.created_at ? new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Security Settings -->
            <div class="profile-content">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <!-- Card Header with Icon -->
                    <div class="security-header px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shadow-red-200">
                                <i class="pi pi-lock text-xl text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Security Settings</h3>
                                <p class="text-gray-500 text-sm">Update your password to keep your account secure</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="p-6">
                        <form @submit.prevent="save">
                            <!-- Current Password -->
                            <div class="mb-6">
                                <label class="form-label">Current Password</label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                        <i class="pi pi-key text-gray-400"></i>
                                    </div>
                                    <input 
                                        :type="showingopass ? 'text' : 'password'" 
                                        v-model="opassword" 
                                        class="form-input pl-10 pr-10" 
                                        required 
                                        placeholder="Enter your current password"
                                    >
                                    <button 
                                        type="button"
                                        @click="showingopass = !showingopass"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-purple-600 transition-colors"
                                    >
                                        <i :class="showingopass ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                                <div>
                                    <label class="form-label">New Password</label>
                                    <div class="relative">
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                            <i class="pi pi-lock text-gray-400"></i>
                                        </div>
                                        <input 
                                            :type="showingnpass ? 'text' : 'password'" 
                                            v-model="npassword" 
                                            class="form-input pl-10 pr-10" 
                                            required 
                                            placeholder="Enter new password"
                                        >
                                        <button 
                                            type="button"
                                            @click="showingnpass = !showingnpass"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-purple-600 transition-colors"
                                        >
                                            <i :class="showingnpass ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="relative">
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                            <i class="pi pi-check-circle text-gray-400"></i>
                                        </div>
                                        <input 
                                            :type="showingcpass ? 'text' : 'password'" 
                                            v-model="cpassword" 
                                            class="form-input pl-10 pr-10" 
                                            required 
                                            placeholder="Confirm new password"
                                        >
                                        <button 
                                            type="button"
                                            @click="showingcpass = !showingcpass"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-purple-600 transition-colors"
                                        >
                                            <i :class="showingcpass ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Requirements Hint -->
                            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 mb-6 border border-purple-100">
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-info-circle text-purple-500 mt-0.5"></i>
                                    <div class="text-sm text-gray-600">
                                        <p class="font-semibold text-gray-800 mb-1">Password Requirements</p>
                                        <ul class="space-y-1 text-xs">
                                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> At least 8 characters long</li>
                                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> Include uppercase and lowercase letters</li>
                                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> Include at least one number</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center gap-4">
                                <button 
                                    type="submit"
                                    :disabled="saving"
                                    class="px-8 py-3 rounded-xl bg-gradient-to-r from-purple-500 to-violet-600 text-white font-semibold hover:from-purple-600 hover:to-violet-700 shadow-lg shadow-purple-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-xl hover:-translate-y-0.5"
                                >
                                    <i v-if="saving" class="pi pi-spin pi-spinner"></i>
                                    <i v-else class="pi pi-shield"></i>
                                    {{ saving ? 'Updating...' : 'Update Password' }}
                                </button>
                                <p v-if="saving" class="text-sm text-gray-500 animate-pulse">Processing secure update...</p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Additional Info Card -->
                <div class="bg-white rounded-xl shadow-md p-5 mt-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                            <i class="pi pi-info-circle text-white text-sm"></i>
                        </div>
                        <h3 class="font-bold text-gray-800">Account Information</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase font-semibold">Account Status</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="text-sm font-bold text-green-600">Active</span>
                            </div>
                        </div>
                        <div class="p-4 rounded-lg bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase font-semibold">Role</p>
                            <p class="text-sm font-bold text-gray-800 mt-2">{{ user.admin ? 'Administrator' : 'Employee' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Page Header Gradient */
.page-header {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 50%, #a855f7 100%);
}

/* Profile Grid Layout */
.profile-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

/* Profile Banner */
.profile-banner {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 50%, #c084fc 100%);
}

/* Avatar Container */
.avatar-container {
    width: 100px;
    height: 100px;
    margin: 0 auto;
    position: relative;
}

.avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 4px 20px rgba(124, 58, 237, 0.3);
    object-fit: cover;
}

.avatar-status {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border: 3px solid white;
}

/* Form Styles */
.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.875rem 2.75rem 0.875rem 2.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.9rem;
    background-color: #f9fafb;
    color: #1e293b;
    transition: all 0.2s;
    height: 48px;
}

.form-input:focus {
    outline: none;
    border-color: #7c3aed;
    background-color: white;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
}

.form-input::placeholder {
    color: #9ca3af;
}

/* Animations */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Input Icon Positioning */
.relative {
    position: relative;
}

.absolute {
    position: absolute;
}

.left-3 {
    left: 0.875rem;
}

.right-3 {
    right: 0.875rem;
}

.top-1\/2 {
    top: 50%;
}

.-translate-y-1\/2 {
    transform: translateY(-50%);
}

.pl-10 {
    padding-left: 2.75rem !important;
}

.pr-10 {
    padding-right: 2.75rem !important;
}
</style>
