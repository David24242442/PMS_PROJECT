<script setup>
    import { ref, onMounted, reactive, computed  } from 'vue'
    import { log, toastt} from '@/helpers/essential'
    import { findposition } from '@/data/masterdata'
    import axios from '@/helpers/pms_axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { loguser } = userstore


    let loaded = ref(false)
    let saving = ref(false)
    let user = reactive({})

    const showingopass = ref(false)
    const showingnpass = ref(false)
    const showingcpass = ref(false)

    let opassword = ref('')
    let npassword = ref('')
    let cpassword = ref('')

    const userInitial = computed(() => user.name?.charAt(0)?.toUpperCase() || 'U')
    const userRole = computed(() => findposition(user.position_id) || 'STANDARD')

    onMounted(() => {
        Object.assign(user, loguser)
    })

    const colorinvalid = (elem) =>{
        if(['select-one', 'text','email','date','tel', 'number', 'file', 'password'].includes(elem.type)){
            elem.style = 'border:2px solid red'
            elem.addEventListener('input', function(e){
                elem.style = 'revert'
                if(elem.nextElementSibling) elem.nextElementSibling.innerText = ''
            },{once:true})
        }
    }

    const validate = (elem) => {
        const reqinput = document.querySelectorAll("#passwordform :invalid");

        reqinput.forEach((elem)=>{
            colorinvalid(elem)
        })

        if(reqinput.length){
            toastt('Correct the errors and submit the form again', 'error')
            window.scrollTo(0,100);
            return false
        }
    }

    const save = () => {
        if(validate() === false) return

        saving.value = true

        if(npassword.value !== cpassword.value){
            toastt('New passwords do not match', 'error')
            saving.value = false
            return
        }

        const userinfo = {
            opassword: opassword.value,
            npassword: npassword.value,
            cpassword: cpassword.value,
        }

        axios.post('updatepassword', userinfo, {

            }).then(res => {

                const data = res.data

                if(data.error){
                    toastt(data.error, 'error')
                    saving.value = false
                    return
                }

                saving.value = false
                opassword.value = ''
                npassword.value = ''
                cpassword.value = ''
                toastt('Your password has been updated', 'success')

            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                saving.value = false
                log(error)
            })
    }
</script>
<template>
    <div class="profile-page">

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-header-bg"></div>
            <div class="profile-header-content">
                <div class="profile-avatar">
                    <span>{{ userInitial }}</span>
                </div>
                <div class="profile-identity">
                    <h1 class="profile-name">{{ user.name || 'User' }}</h1>
                    <p class="profile-meta">
                        <span class="profile-badge">{{ userRole }}</span>
                        <span class="profile-separator">|</span>
                        <span>{{ user.department || 'General' }}</span>
                        <span v-if="user.location" class="profile-separator">|</span>
                        <span v-if="user.location">{{ user.location }}</span>
                    </p>
                </div>
                <div class="profile-status">
                    <div class="status-dot"></div>
                    Active
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="profile-grid">

            <!-- Left: Info Cards -->
            <div class="profile-main">

                <!-- Account Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="pi pi-user"></i>
                        </div>
                        <h3>Account Information</h3>
                    </div>
                    <div class="info-card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name</label>
                                <p>{{ user.name || 'N/A' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Username</label>
                                <p>{{ user.username || 'N/A' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Email Address</label>
                                <p class="break-all">{{ user.email || 'N/A' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Access Level</label>
                                <p class="access-level">
                                    <i class="pi pi-shield"></i>
                                    {{ userRole }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Details -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon work-icon">
                            <i class="pi pi-briefcase"></i>
                        </div>
                        <h3>Work Details</h3>
                    </div>
                    <div class="info-card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Employee Code</label>
                                <p>{{ user.employee_code || 'N/A' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Department</label>
                                <p>{{ user.department || 'N/A' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Position</label>
                                <p>{{ userRole }}</p>
                            </div>
                            <div class="info-item">
                                <label>Location</label>
                                <p>{{ user.location || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Security Panel -->
            <div class="profile-sidebar">
                <div class="security-card" id="passwordform">
                    <div class="security-card-header">
                        <div class="security-icon">
                            <i class="pi pi-lock"></i>
                        </div>
                        <h3>Change Password</h3>
                    </div>

                    <div class="security-card-body">
                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="password-field">
                                <input :type="showingopass ? 'text' : 'password'" v-model="opassword" required
                                    placeholder="Enter current password" class="security-input" />
                                <i class="toggle-pass" :class="showingopass ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                    @click="showingopass = !showingopass"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <div class="password-field">
                                <input :type="showingnpass ? 'text' : 'password'" v-model="npassword" required
                                    placeholder="Minimum 6 characters" class="security-input" />
                                <i class="toggle-pass" :class="showingnpass ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                    @click="showingnpass = !showingnpass"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <div class="password-field">
                                <input :type="showingcpass ? 'text' : 'password'" v-model="cpassword" required
                                    placeholder="Repeat new password" class="security-input" />
                                <i class="toggle-pass" :class="showingcpass ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                    @click="showingcpass = !showingcpass"></i>
                            </div>
                        </div>

                        <button :disabled="saving" @click="save" class="save-btn">
                            <i class="pi" :class="saving ? 'pi-spin pi-spinner' : 'pi-check'"></i>
                            {{ saving ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.profile-page {
    padding: 24px 32px 60px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ===== HEADER ===== */
.profile-header {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 28px;
}

.profile-header-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #312e81 0%, #1e1b4b 50%, #0f172a 100%);
}

.profile-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 32px 40px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.12);
    border: 2px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.profile-avatar span {
    font-size: 32px;
    font-weight: 800;
    color: white;
}

.profile-identity {
    flex: 1;
    min-width: 0;
}

.profile-name {
    font-size: 24px;
    font-weight: 800;
    color: white;
    margin: 0 0 6px;
    line-height: 1.2;
}

.profile-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: rgba(199, 210, 254, 0.8);
    margin: 0;
    flex-wrap: wrap;
}

.profile-badge {
    background: rgba(129, 140, 248, 0.2);
    border: 1px solid rgba(129, 140, 248, 0.3);
    padding: 2px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #c7d2fe;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-separator {
    opacity: 0.3;
}

.profile-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 10px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.25);
    color: #6ee7b7;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
}

/* ===== GRID ===== */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    align-items: start;
}

.profile-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ===== INFO CARDS ===== */
.info-card {
    background: var(--surface-card, #fff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 14px;
    overflow: hidden;
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 28px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.info-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.info-card-icon.work-icon {
    background: #fef3c7;
    color: #d97706;
}

.info-card-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-color, #1f2937);
    margin: 0;
}

.info-card-body {
    padding: 28px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.info-item label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.info-item p {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-color, #1f2937);
    padding: 10px 14px;
    background: var(--surface-ground, #f8fafc);
    border-radius: 8px;
    border: 1px solid var(--border-color, #e2e8f0);
    min-height: 42px;
    display: flex;
    align-items: center;
}

.access-level {
    color: #4f46e5 !important;
    gap: 8px;
}

/* ===== SECURITY CARD ===== */
.security-card {
    background: var(--surface-card, #fff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 14px;
    overflow: hidden;
}

.security-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 28px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.security-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #fef2f2;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.security-card-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-color, #1f2937);
    margin: 0;
}

.security-card-body {
    padding: 28px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.password-field {
    position: relative;
}

.security-input {
    width: 100%;
    padding: 10px 40px 10px 14px;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    background: var(--surface-ground, #f8fafc);
    color: var(--text-color, #1f2937);
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    box-sizing: border-box;
}

.security-input:focus {
    border-color: var(--primary, #4f46e5);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.security-input::placeholder {
    color: var(--text-secondary, #9ca3af);
    font-weight: 400;
}

.toggle-pass {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary, #9ca3af);
    cursor: pointer;
    font-size: 14px;
    transition: color 0.2s;
}

.toggle-pass:hover {
    color: var(--text-color, #374151);
}

.save-btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: var(--primary, #4f46e5);
    color: white;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s, transform 0.1s;
    margin-top: 8px;
}

.save-btn:hover:not(:disabled) {
    background: var(--primary-dark, #4338ca);
}

.save-btn:active:not(:disabled) {
    transform: scale(0.98);
}

.save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ===== DARK MODE ===== */
:global(body.dark-mode) .info-card-icon {
    background: rgba(79, 70, 229, 0.15);
    color: #818cf8;
}

:global(body.dark-mode) .info-card-icon.work-icon {
    background: rgba(217, 119, 6, 0.15);
    color: #fbbf24;
}

:global(body.dark-mode) .security-icon {
    background: rgba(239, 68, 68, 0.12);
    color: #f87171;
}

:global(body.dark-mode) .access-level {
    color: #818cf8 !important;
}

:global(body.dark-mode) .profile-badge {
    background: rgba(129, 140, 248, 0.15);
    border-color: rgba(129, 140, 248, 0.25);
}

:global(body.dark-mode) .profile-status {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.2);
}

:global(body.dark-mode) .save-btn {
    background: #6366f1;
}

:global(body.dark-mode) .save-btn:hover:not(:disabled) {
    background: #818cf8;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .profile-page {
        padding: 16px;
    }
    .profile-header-content {
        flex-direction: column;
        text-align: center;
        padding: 24px;
    }
    .profile-meta {
        justify-content: center;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
