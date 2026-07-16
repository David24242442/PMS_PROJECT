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
            <!-- Decorative background elements -->
            <div class="form-bg-pattern"></div>
            <div class="form-bg-glow"></div>

            <div class="form-wrapper animate-fadeIn">
                <!-- Top Brand Identity -->
                <div class="brand-header">
                    <img :src="logoo" alt="Melcom" class="brand-logo-img">
                </div>

                <form @submit.prevent="login" class="login-form">
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <div class="welcome-badge">
                            <i class="pi pi-shield"></i>
                            <span>Secure Portal</span>
                        </div>
                        <h1 class="welcome-title">Welcome back</h1>
                        <p class="welcome-sub">Sign in to continue to your <span>Performance Hub</span></p>
                    </div>

                    <div class="fields-wrapper">
                        <!-- Username -->
                        <div class="field-group">
                            <label for="username" class="login-label">Username</label>
                            <div class="input-shell">
                                <i class="pi pi-user field-icon"></i>
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
                        <div class="field-group">
                            <label for="password" class="login-label">Password</label>
                            <div class="input-shell">
                                <i class="pi pi-lock field-icon"></i>
                                <input
                                    id="password"
                                    :type="showingpass ? 'text' : 'password'"
                                    v-model="password"
                                    placeholder="Enter your password"
                                    required
                                    @keydown="cancelErreur"
                                    class="login-input"
                                >
                                <button
                                    type="button"
                                    @click="showingpass = !showingpass"
                                    class="pass-toggle"
                                >
                                    <i :class="showingpass ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Error Alert -->
                        <div v-if="erreur" class="error-alert animate-shake">
                            <i class="pi pi-exclamation-circle"></i>
                            <span>Invalid credentials. Please check your username and password.</span>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="login-button"
                            :disabled="!password || !username || loading"
                        >
                            <div v-if="loading" class="btn-content">
                                <i class="pi pi-spin pi-spinner"></i>
                                <span>Authenticating...</span>
                            </div>
                            <div v-else class="btn-content">
                                <span>Sign In</span>
                                <i class="pi pi-arrow-right btn-arrow"></i>
                            </div>
                        </button>
                    </div>
                </form>

                <!-- Footer -->
                <div class="login-footer">
                    <div class="footer-line"></div>
                    <p>Melcom Group &copy; {{ new Date().getFullYear() }}</p>
                </div>
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

/* ══════════════════════════════════════════════════════════════
   RIGHT FORM PANEL
   ══════════════════════════════════════════════════════════════ */
.form-panel {
    width: 500px;
    min-width: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(165deg, #f8faff 0%, #f0f4ff 40%, #faf5ff 100%);
    position: relative;
    overflow: hidden;
}

/* Subtle dot pattern */
.form-bg-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 1px 1px, rgba(99, 102, 241, 0.04) 1px, transparent 0);
    background-size: 28px 28px;
    pointer-events: none;
}

/* Soft color glow */
.form-bg-glow {
    position: absolute;
    top: -120px;
    right: -80px;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.08) 0%, transparent 70%);
    pointer-events: none;
}

.form-wrapper {
    width: 100%;
    padding: 56px 52px;
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 1;
}

/* Brand */
.brand-header {
    margin-bottom: 40px;
}

.brand-logo-img {
    height: 42px;
    width: auto;
    object-fit: contain;
}

.login-form {
    width: 100%;
}

/* Welcome */
.welcome-section {
    margin-bottom: 32px;
}

.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px 5px 8px;
    background: linear-gradient(135deg, #eef2ff, #f5f3ff);
    border: 1px solid rgba(129, 140, 248, 0.2);
    border-radius: 20px;
    margin-bottom: 16px;
}

.welcome-badge i {
    font-size: 10px;
    color: #6366f1;
}

.welcome-badge span {
    font-size: 10px;
    font-weight: 800;
    color: #4f46e5;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.welcome-title {
    font-size: 28px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.03em;
    line-height: 1.1;
    margin-bottom: 8px;
}

.welcome-sub {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 500;
    line-height: 1.5;
}

.welcome-sub span {
    color: #475569;
    font-weight: 700;
}

/* Fields */
.fields-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.login-label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-left: 2px;
}

.input-shell {
    position: relative;
}

.field-icon {
    position: absolute;
    top: 50%;
    left: 16px;
    transform: translateY(-50%);
    font-size: 13px;
    color: #a5b4c8;
    transition: color 0.2s ease;
    pointer-events: none;
}

.input-shell:focus-within .field-icon {
    color: #6366f1;
}

.login-input {
    width: 100%;
    padding: 15px 16px 15px 44px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
    transition: all 0.25s ease;
    outline: none;
}

.login-input:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 1px 3px rgba(0,0,0,0.04);
}

.login-input::placeholder {
    color: #c1cdd9;
    font-weight: 500;
    font-size: 13px;
}

.pass-toggle {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #c1cdd9;
    transition: color 0.2s ease;
    font-size: 13px;
}

.pass-toggle:hover {
    color: #6366f1;
}

/* Error */
.error-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #fff5f5, #fef2f2);
    border: 1px solid #fecaca;
    border-radius: 12px;
}

.error-alert i {
    font-size: 14px;
    color: #ef4444;
    flex-shrink: 0;
}

.error-alert span {
    font-size: 12px;
    font-weight: 600;
    color: #dc2626;
    line-height: 1.4;
}

/* Button */
.login-button {
    width: 100%;
    padding: 15px 28px;
    margin-top: 4px;
    background: linear-gradient(135deg, #4f46e5 0%, #6d28d9 50%, #7c3aed 100%);
    background-size: 200% 200%;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 16px -2px rgba(79, 70, 229, 0.3), 0 2px 4px rgba(0,0,0,0.06);
}

.login-button:hover {
    background-position: 100% 0;
    transform: translateY(-1px);
    box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.4), 0 4px 8px rgba(0,0,0,0.08);
}

.login-button:active {
    transform: translateY(0);
}

.btn-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-content span {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.btn-content i {
    font-size: 12px;
}

.btn-arrow {
    transition: transform 0.3s ease;
}

.login-button:hover .btn-arrow {
    transform: translateX(4px);
}

.login-button:disabled {
    opacity: 0.35;
    background: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* Footer */
.login-footer {
    margin-top: 36px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.footer-line {
    width: 40px;
    height: 2px;
    background: linear-gradient(90deg, #c7d2fe, #ddd6fe);
    border-radius: 1px;
}

.login-footer p {
    font-size: 10px;
    font-weight: 700;
    color: #c1cdd9;
    letter-spacing: 0.06em;
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

    .form-bg-glow { display: none; }

    .form-wrapper {
        padding: 32px 28px;
        max-width: 440px;
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

    .form-wrapper {
        padding: 28px 20px;
    }

    .welcome-title {
        font-size: 24px;
    }
}

/* Dark Mode Login */
:global(body.dark-mode) .form-panel {
    background: linear-gradient(165deg, #0f172a 0%, #1a1f3d 40%, #1e1b3a 100%) !important;
}
:global(body.dark-mode) .form-bg-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(99, 102, 241, 0.06) 1px, transparent 0) !important;
}
:global(body.dark-mode) .form-bg-glow {
    background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%) !important;
}
:global(body.dark-mode) .welcome-badge {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1)) !important;
    border-color: rgba(129, 140, 248, 0.2) !important;
}
:global(body.dark-mode) .welcome-title {
    color: #f1f5f9 !important;
}
:global(body.dark-mode) .welcome-sub {
    color: #64748b !important;
}
:global(body.dark-mode) .welcome-sub span {
    color: #94a3b8 !important;
}
:global(body.dark-mode) .login-label {
    color: #94a3b8 !important;
}
:global(body.dark-mode) .login-input {
    background: rgba(15, 23, 42, 0.6) !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
:global(body.dark-mode) .login-input:focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
}
:global(body.dark-mode) .login-input::placeholder {
    color: #475569 !important;
}
:global(body.dark-mode) .field-icon {
    color: #475569 !important;
}
:global(body.dark-mode) .input-shell:focus-within .field-icon {
    color: #818cf8 !important;
}
:global(body.dark-mode) .pass-toggle {
    color: #475569 !important;
}
:global(body.dark-mode) .footer-line {
    background: linear-gradient(90deg, #334155, #3b3566) !important;
}
:global(body.dark-mode) .login-footer p {
    color: #475569 !important;
}
</style>
