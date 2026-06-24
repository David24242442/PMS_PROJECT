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
                    router.push('/dashboard')
                }
                else {
                    erreur.value = true
                    loading.value = false
                }
            })
            .catch((error) => {
                console.log(error)
                loading.value = false
            })

    }

    

</script>

<template>
    <div class="login-wrapper" :style="`background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url(${bglogo})`">
        <div class="login-container animate-fade-in">
            <div class="login-card">
                <div class="login-brand">
                    <img :src="logoo" alt="MELCOM Logo" class="login-logo">
                </div>
                
                <div class="login-header text-center">
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter relative bottom-4">PMS</h2>
                    <p class="text-white/60 text-xs font-bold uppercase tracking-widest mt-2">Enter credentials to proceed</p>
                </div>

                <form @submit.prevent="login" class="login-form">
                    <div class="form-group">
                        <label class="login-label">Employee ID / Username</label>
                        <div class="login-input-wrap">
                            <i class="pi pi-user text-primary/50"></i>
                            <input type="text" v-model="username" placeholder="Enter your username" class="login-input" required>
                        </div>
                    </div>

                    <div class="form-group mt-sm">
                        <label class="login-label">Access Password</label>
                        <div class="login-input-wrap">
                            <i class="pi pi-lock text-primary/50"></i>
                            <input :type="showingpass ? 'text' : 'password'" v-model="password" @keydown="cancelErreur" placeholder="••••••••" class="login-input" required>
                            <i :class="showingpass ? 'pi pi-eye-slash' : 'pi pi-eye'" class="login-eye" @click="showingpass = !showingpass"></i>
                        </div>
                    </div>

                    <div v-if="erreur" class="login-error animate-pulse">
                        <i class="pi pi-exclamation-circle mr-2"></i>
                        Invalid credentials. Please verify and retry.
                    </div>

                    <div class="login-actions mt-xl">
                        <button type="submit" class="login-btn" :disabled="!password || !username || loading">
                            <i v-if="loading" class="pi pi-spin pi-spinner mr-2"></i>
                            <span v-else>Initialize Session</span>
                        </button>
                    </div>

                    <div class="login-footer mt-xl">
                        <p class="text-[10px] text-white/40 uppercase tracking-widest font-black">&copy; 2025 MELCOM Performance Management System</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-wrapper {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    overflow: hidden;
}

.login-container {
    width: 100%;
    max-width: 450px;
    padding: var(--spacing-md);
}

.login-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 40px;
    padding: 60px 40px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    text-align: center;
}

.login-brand {
    margin-bottom: var(--spacing-xl);
}

.login-logo {
    width: 200px;
    filter: drop-shadow(0 0 10px rgba(0,0,0,0.5));
}

.login-header {
    margin-bottom: var(--spacing-xl);
}

.login-form {
    text-align: left;
}

.login-label {
    display: block;
    font-size: 10px;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 8px;
    margin-left: 4px;
}

.login-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.login-input-wrap i:first-child {
    position: absolute;
    left: 20px;
}

.login-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 16px 20px 16px 50px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.login-input::placeholder {
    color: rgba(255, 255, 255, 0.2);
}

.login-input:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    outline: none;
}

.login-eye {
    position: absolute;
    right: 20px;
    color: rgba(255, 255, 255, 0.3);
    cursor: pointer;
    transition: color 0.2s;
}

.login-eye:hover {
    color: white;
}

.login-error {
    margin-top: var(--spacing-md);
    padding: 12px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 12px;
    color: #f87171;
    font-size: 0.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
}

.login-btn {
    width: 100%;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 20px;
    padding: 18px;
    font-size: 0.9rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 20px -5px rgba(124, 58, 237, 0.3);
}

.login-btn:hover:not(:disabled) {
    background: var(--color-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.4);
}

.login-btn:active:not(:disabled) {
    transform: translateY(0);
}

.login-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    filter: grayscale(1);
}

.login-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: var(--spacing-xl);
}

.animate-fade-in {
    animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .7; }
}
</style>
