<script setup>
    import { ref, onMounted } from "vue";
    import axios from 'axios';
    import { useRouter } from 'vue-router';
    import { log } from '@/helpers/essential';
    import { useUsersStore } from '@/stores/user';
    import logo from '@/assets/img/melcom_logo.png'
    import logoo from '@/assets/img/melcom_logo_full.png.png'
    import bglogo from '@/assets/img/Image.jpg'

    const router = useRouter()
    const userstore = useUsersStore()

    let { setloguser, settoken } = userstore

    let username = ref('')
    let password = ref('')
    let erreur = ref(false)
    let cancelErreur = () => erreur.value = false
    let loading = ref(false)
    let showingpass = ref(false)

    const login = () =>{
        
        if(!username.value.trim() || !password.value.trim()){
            console.log('Error')
            return
        }
        
        loading.value = true

        axios.post('login', {
            'username': username.value.trim(),
            'password': password.value.trim()
        })
            .then(res => {
                const data = res.data

                if (data.result) {
                    localStorage.setItem('hrproject_user', JSON.stringify(data.user))
                    localStorage.setItem('hrproject_user_token', JSON.stringify(data.token))
                    setloguser(data.user)
                    settoken(data.token)
                    
                    loading.value = false
                    
                    // Dynamic Redirect Logic
                    const userRole = data.user.attributes ? data.user.attributes.position_id : data.user.position_id;
                    const permissions = data.user.permissions || [];
                    
                    let redirectUrl = '/profile';
                    if (userRole === 4) {
                        redirectUrl = '/pms/goals';
                    } else if (permissions.includes('/pms/goals')) {
                        redirectUrl = '/pms/goals';
                    } else if (permissions.length > 0) {
                        redirectUrl = permissions[0];
                    }
                    
                    router.push(redirectUrl)
                }
                else {
                    erreur.value = true
                    loading.value = false
                }
            })
            .catch((error) => {
                console.log(error)
                loading.value = false
                if (error.response) {
                    // console.log(error.response.data);
                    // console.log(error.response.status);
                    // console.log(error.response.headers);
                } else if (error.request) {
                    console.log(error.request);
                } else {
                    console.log('Error', error.message);
                }
            })

    }

    

</script>

<template>
    <div class="login-page">
        <!-- Left: Image Panel -->
        <div class="image-panel" :style="`background-image:url(${bglogo})`">
            <div class="image-overlay"></div>
            <div class="image-content">
                <img :src="logoo" alt="Melcom Logo" class="panel-logo">
                <div class="panel-divider"></div>
                <h2 class="panel-title">Human Resources Portal</h2>
                <p class="panel-subtitle">Performance Management System</p>
            </div>
            <div class="image-footer">
                <p>Melcom Group &copy; {{ new Date().getFullYear() }}</p>
            </div>
        </div>

        <!-- Right: Form Panel -->
        <div class="form-panel">
            <div class="form-wrapper animate-fadeIn">
                <!-- Top Brand Identity -->
                <div class="brand-header mb-12">
                    <img :src="logoo" alt="Melcom" class="brand-logo-img">
                </div>

                <form @submit.prevent="login" class="login-form">
                    <!-- Welcome Section -->
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center border border-indigo-100/50 shadow-sm">
                                <i class="pi pi-shield text-[#1A237E] text-sm"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-[#1A237E] uppercase tracking-[0.2em]">Authorized Access Only</span>
                            </div>
                        </div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-2 flex flex-col">
                            <span class="text-indigo-600 -mb-1">Please sign in.</span>
                        </h1>
                        <p class="text-sm text-slate-400 font-medium">Identify yourself to access the <span class="text-slate-600 font-bold">Performance Hub</span></p>
                    </div>
                    
                    <div class="space-y-5">
                        <!-- Username -->
                        <div class="space-y-1.5">
                            <label for="username" class="login-label text-black">Username</label>
                            <div class="relative input-group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-700">
                                    <i class="pi pi-user text-xs"></i>
                                </div>
                                <input 
                                    id="username"
                                    type="text" 
                                    v-model="username" 
                                    placeholder="Enter your username"
                                    required
                                    @keydown="cancelErreur"
                                    class="login-input"
                                >
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <label for="password" class="login-label text-black">Password</label>
                            <div class="relative input-group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-700">
                                    <i class="pi pi-lock text-xs"></i>
                                </div>
                                <input 
                                    id="password"
                                    :type="showingpass ? 'text' : 'password'" 
                                    v-model="password" 
                                    placeholder="••••••••"
                                    required 
                                    @keydown="cancelErreur"
                                    class="login-input"
                                >
                                <button 
                                    type="button"
                                    @click="showingpass = !showingpass"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-300 hover:text-gray-600 transition-colors"
                                >
                                    <i :class="showingpass ? 'pi pi-eye-slash' : 'pi pi-eye'" class="text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Error Alert -->
                        <div v-if="erreur" class="p-3.5 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3 animate-shake">
                            <div class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center text-red-500 shrink-0">
                                <i class="pi pi-exclamation-triangle text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-bold text-red-600">Invalid username or password. Please try again.</span>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                class="login-button"
                                :disabled="!password || !username || loading"
                            >
                                <div v-if="loading" class="flex items-center justify-center gap-3">
                                    <i class="pi pi-spin pi-spinner text-sm"></i>
                                    <span class="tracking-wider font-black uppercase text-xs">Verifying...</span>
                                </div>
                                <div v-else class="flex items-center justify-center gap-2">
                                    <span class="tracking-wider font-black uppercase text-xs">Sign In</span>
                                    <i class="pi pi-arrow-right text-[10px] transition-transform btn-arrow"></i>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-10 pt-6 border-t border-gray-100 text-center">
                        <p class="text-[9px] text-gray-300 font-bold uppercase tracking-normal">
                            Melcom Group &copy; {{ new Date().getFullYear() }} &bull; Secure Access
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-page {
    display: flex;
    height: 100vh;
    width: 100vw;
    overflow: hidden;
}

/* Left Image Panel */
.image-panel {
    flex: 1;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.85) 100%);
}

.image-content {
    position: relative;
    z-index: 10;
    text-align: center;
    padding: 40px;
}

.panel-logo {
    width: 200px;
    height: auto;
    margin: 0 auto 24px;
    filter: brightness(0) invert(1);
    opacity: 0.95;
}

.panel-divider {
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, #a855f7, #6366f1);
    border-radius: 10px;
    margin: 0 auto 20px;
}

.panel-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 900;
    letter-spacing: -0.02em;
    margin-bottom: 8px;
}

.panel-subtitle {
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.image-footer {
    position: absolute;
    bottom: 24px;
    left: 0;
    right: 0;
    text-align: center;
    z-index: 10;
}

.image-footer p {
    font-size: 10px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.3);
    letter-spacing: 0.1em;
}

/* Right Form Panel */
.form-panel {
    width: 480px;
    min-width: 480px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FFFFFF;
    position: relative;
}

.form-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent 10%, #e2e8f0 50%, transparent 90%);
}

.form-wrapper {
    width: 100%;
    padding: 60px;
    display: flex;
    flex-direction: column;
}

.brand-logo-img {
    height: 48px;
    width: auto;
    object-fit: contain;
}

.login-form {
    width: 100%;
}

/* Form Elements */
.login-label {
    display: block;
    font-size: 10px;
    font-weight: 900;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    margin-left: 2px;
    margin-bottom: 0 !important;
}

.login-input {
    width: 100%;
    padding: 14px 16px 14px 42px;
    background: #f8fafc;
    border: 1.5px solid #f1f5f9;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    outline: none;
}

.login-input:focus {
    background: #ffffff;
    border-color: #a855f7;
    box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.08);
}

.login-input::placeholder {
    color: #cbd5e1;
    font-weight: 500;
    font-size: 12px;
}

.input-group:focus-within .text-gray-400 {
    color: #a855f7;
}

.login-button {
    width: 100%;
    padding: 16px 28px;
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px -4px rgba(124, 58, 237, 0.25);
}

.login-button::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: 0.5s;
}

.login-button:hover::after {
    left: 100%;
}

.login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px -4px rgba(124, 58, 237, 0.35);
}

.login-button:hover .btn-arrow {
    transform: translateX(3px);
}

.login-button:active {
    transform: translateY(0);
}

.login-button:disabled {
    opacity: 0.4;
    background: #cbd5e1;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.login-button:disabled::after {
    display: none;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.animate-fadeIn {
    animation: fadeIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-shake {
    animation: shake 0.2s ease-in-out 0s 2;
}

/* Responsive */
@media (max-width: 900px) {
    .login-page {
        flex-direction: column;
    }

    .image-panel {
        flex: none;
        height: 220px;
    }

    .panel-logo {
        width: 120px;
        margin-bottom: 12px;
    }

    .panel-title {
        font-size: 1.1rem;
    }

    .panel-divider {
        margin-bottom: 12px;
    }

    .image-footer {
        display: none;
    }

    .form-panel {
        flex: 1;
        width: 100%;
        min-width: unset;
    }

    .form-panel::before {
        display: none;
    }

    .form-wrapper {
        padding: 32px 24px;
        max-width: 420px;
    }
}

@media (max-width: 480px) {
    .image-panel {
        height: 160px;
    }

    .image-content {
        padding: 20px;
    }

    .panel-logo {
        width: 100px;
    }

    .panel-title {
        font-size: 0.9rem;
    }

    .panel-subtitle {
        font-size: 9px;
    }
}
</style>
