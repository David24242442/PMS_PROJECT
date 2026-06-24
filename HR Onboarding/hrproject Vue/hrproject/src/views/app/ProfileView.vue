<script setup>
    import { ref, onMounted, reactive  } from 'vue'
    import { log, toastt} from '@/helpers/essential'
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

    onMounted(() => {
        Object.assign(user, loguser)

    })

    const colorinvalid = (elem) =>{
        if(['select-one', 'text','email','date','tel', 'number', 'file', 'password'].includes(elem.type)){
            elem.style = 'border:2px solid red'
            // if(elem.nextElementSibling) elem.nextElementSibling.innerText = elem.validationMessage
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
                toastt('Your password has been updated', 'success')
                
            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                saving.value = false
                log(error)
            })

    
    }


</script>
<template>
    <div class="h-full pb-20 overflow-y-auto">
        <!-- Profile Hero Banner -->
        <div class="mx-8 mt-10 mb-10 animate-slide-up">
            <div class="prof-card !rounded-[40px] !bg-[#312E81] text-white p-8 px-12 relative overflow-hidden shadow-2xl shadow-indigo-200/50 group">
                <!-- Background Accents -->
                <div class="absolute -top-24 -right-24 w-[500px] h-[500px] bg-white/5 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-1000"></div>
                <div class="absolute -bottom-24 -left-24 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-10 text-center md:text-left">
                    <!-- Avatar Area -->
                    <div class="relative group/avatar">
                        <div class="w-32 h-32 rounded-[28px] bg-white/10 backdrop-blur-xl border-4 border-white/20 p-1.5 shadow-2xl transition-all duration-700 group-hover/avatar:rotate-6 group-hover/avatar:scale-105">
                            <div class="w-full h-full rounded-[20px] bg-[#EEF2FF] flex items-center justify-center text-[#5830E0] shadow-inner overflow-hidden">
                                <span class="text-4xl font-black">{{ user.name?.charAt(0) || 'U' }}</span>
                            </div>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-10 h-10 rounded-xl bg-emerald-400 text-indigo-900 flex items-center justify-center shadow-lg border-4 border-[#312E81] animate-bounce">
                             <i class="pi pi-check-circle text-lg"></i>
                        </div>
                    </div>

                    <!-- User Identity -->
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-3 mb-3 justify-center md:justify-start">
                            <span class="px-3 py-1 rounded-full bg-indigo-500 text-white text-[9px] font-black uppercase tracking-normal shadow-lg shadow-indigo-900/40 border border-white/10">Authorized Representative</span>
                            <div class="h-px w-16 bg-white/10"></div>
                        </div>
                        <h1 class="text-3xl font-black text-white tracking-normal uppercase leading-none break-words mb-3">{{ user.name || 'AUTHENTICATED USER' }}</h1>
                        <p class="text-indigo-200 text-xs font-bold tracking-normal uppercase opacity-80 flex items-center gap-2 justify-center md:justify-start">
                            <i class="pi pi-map-marker text-[#F97316]"></i>
                            MELCOM • {{ user.department || 'GENERAL' }}
                        </p>
                    </div>

                    <!-- Stats / Details -->
                    <div class="flex gap-4">
                        <div class="px-6 py-3 rounded-[24px] bg-white/5 border border-white/10 backdrop-blur-md text-center">
                            <p class="text-[9px] text-indigo-300 font-black uppercase tracking-normal mb-1">Status</p>
                            <p class="text-white font-black text-base">ACTIVE</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-8 grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Information Panel (Left Column) -->
            <div class="lg:col-span-2 space-y-10">
                <!-- Account Overview Card -->
                <div class="prof-card !rounded-[32px] !bg-white border-none shadow-xl shadow-indigo-100/20 overflow-hidden animate-slide-up" style="animation-delay: 0.1s">
                    <div class="px-12 py-8 border-b border-gray-50 flex items-center justify-between bg-[#F8FAFC]/50">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-[#5830E0] text-white flex items-center justify-center shadow-lg shadow-indigo-100">
                                <i class="pi pi-user text-xl"></i>
                            </div>
                            <h3 class="font-black text-gray-800 text-2xl tracking-normal uppercase">Personnel Profile</h3>
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-normal">MELCOM AUTHENTICATED USER</span>
                    </div>
                    
                    <div class="p-12">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <label class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.2em] ml-1">Official Name</label>
                                <div class="prof-input w-full !bg-[#F8FAFC] !border-none !rounded-2xl !py-5 !font-black !px-8 text-slate-900 shadow-inner border border-slate-100/50">
                                    {{ user.name || 'N/A' }}
                                </div>
                            </div>
                            <div class="space-y-4">
                                <label class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.2em] ml-1">User Identification</label>
                                <div class="prof-input w-full !bg-[#F8FAFC] !border-none !rounded-2xl !py-5 !font-black !px-8 text-slate-900 shadow-inner border border-slate-100/50 uppercase">
                                    {{ user.username || 'EMP001' }}
                                </div>
                            </div>
                            <div class="space-y-4">
                                <label class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.2em] ml-1">Connected Email</label>
                                <div class="prof-input w-full !bg-[#F8FAFC] !border-none !rounded-2xl !py-5 !font-black !px-8 text-slate-900 shadow-inner border border-slate-100/50 break-all">
                                    {{ user.email || 'N/A' }}
                                </div>
                            </div>
                            <div class="space-y-4">
                                <label class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.2em] ml-1">Security Access Level</label>
                                <div class="prof-input w-full !bg-indigo-50 !border-none !rounded-2xl !py-5 !font-black !px-8 text-[#1E1B4B] shadow-inner flex items-center gap-3">
                                    <i class="pi pi-shield text-indigo-600"></i>
                                    {{ user.role?.toUpperCase() || 'STANDARD AUTH' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Panel (Right Column) -->
            <div class="lg:col-span-1 space-y-10 animate-slide-up" style="animation-delay: 0.3s">
                <div class="prof-card !rounded-[32px] !bg-[#1E1B4B] text-white border-none shadow-2xl shadow-indigo-900/20 overflow-hidden group" id="passwordform">
                    <div class="px-10 py-8 border-b border-white/5 bg-white/5 flex items-center gap-5">
                        <div class="w-10 h-10 rounded-xl bg-[#F97316] text-white flex items-center justify-center shadow-lg shadow-orange-900/40 group-hover:rotate-12 transition-transform">
                            <i class="pi pi-lock text-sm"></i>
                        </div>
                        <h3 class="font-black text-white text-xl tracking-normal uppercase">Security Protocol</h3>
                    </div>

                    <div class="p-10 space-y-8">
                        <div>
                            <label class="block text-[10px]  text-indigo-200 uppercase tracking-widest mb-4 ml-1">Current Password</label>
                            <div class="relative">
                                <input :type="showingopass ? 'text' : 'password'"  v-model="opassword" required
                                    placeholder="Enter current password"
                                    class="prof-input w-full !bg-white/5 !border !border-white/10 !rounded-2xl !py-4 !font-black !px-6 text-white transition-all focus:!bg-white/10 placeholder:text-white/20">
                                <i class="absolute right-5 top-1/2 -translate-y-1/2 text-white/30 hover:text-white cursor-pointer transition-colors"
                                    :class="showingopass ? 'pi pi-eye-slash' : 'pi pi-eye' "
                                    @click="showingopass = !showingopass"></i>
                            </div>
                        </div>

                        <div class="h-px bg-white/5 mx-2"></div>

                        <div>
                            <label class="block text-[10px] text-indigo-200 uppercase tracking-widest mb-4 ml-1">New Password</label>
                            <div class="relative">
                                <input :type="showingnpass ? 'text' : 'password'"  v-model="npassword" required
                                    placeholder="Minimum 6 characters"
                                    class="prof-input w-full !bg-white/5 !border !border-white/10 !rounded-2xl !py-4 !font-black !px-6 text-white transition-all focus:!bg-white/10 placeholder:text-white/20">
                                <i class="absolute right-5 top-1/2 -translate-y-1/2 text-white/30 hover:text-white cursor-pointer transition-colors"
                                    :class="showingnpass ? 'pi pi-eye-slash' : 'pi pi-eye' "
                                    @click="showingnpass = !showingnpass"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] text-indigo-200 uppercase tracking-widest mb-4 ml-1">Confirm New Password</label>
                            <div class="relative">
                                <input :type="showingcpass ? 'text' : 'password'"  v-model="cpassword" required
                                    placeholder="Repeat new password"
                                    class="prof-input w-full !bg-white/5 !border !border-white/10 !rounded-2xl !py-4 !font-black !px-6 text-white transition-all focus:!bg-white/10 placeholder:text-white/20">
                                <i class="absolute right-5 top-1/2 -translate-y-1/2 text-white/30 hover:text-white cursor-pointer transition-colors"
                                    :class="showingcpass ? 'pi pi-eye-slash' : 'pi pi-eye' "
                                    @click="showingcpass = !showingcpass"></i>
                            </div>
                        </div>

                        <button
                            :disabled="saving"
                            @click="save"
                            class="w-full mt-4 !px-10 !py-5 rounded-[24px] bg-[#F97316] hover:bg-[#EA580C] text-white font-black text-xs uppercase tracking-normal transition-all flex items-center justify-center gap-3 shadow-xl shadow-orange-950/40 active:scale-95 border-none cursor-pointer"
                        >
                            <i class="pi" :class="saving ? 'pi-spin pi-spinner' : 'pi-check-circle'"></i>
                            {{ saving ? 'UPDATING...' : 'Update Password' }}
                        </button>
                    </div>
                </div>

                <!-- Session Info -->
                <div class="p-8 text-center text-gray-300 font-bold text-[10px] uppercase tracking-normal">
                    SECURE TERMINAL ACCESS • {{ new Date().toLocaleTimeString() }}
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
</style>
