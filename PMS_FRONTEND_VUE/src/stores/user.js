import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useUsersStore = defineStore('user', () => {

    const loguser = ref({})
    const authtoken = ref('')


    const setloguser = (user) => {
        loguser.value = user
    }
    const getloguser = () => loguser.value

    const settoken =(token) =>{
        authtoken.value = token
    }
    const getauthtoken = () => authtoken.value

    // const getuserperms = () => JSON.parse(localStorage.getItem('agenda_user_permissions')) || []

    // const hasperm = (permtocheck) => {
    //     return getuserperms().find((perm) => perm.name == permtocheck)
    // }



    let loadingSafetyTimer = null
    const isLoading = ref(false)
    const setIsLoading = (val) => {
        isLoading.value = !!val
        if (loadingSafetyTimer) {
            clearTimeout(loadingSafetyTimer)
            loadingSafetyTimer = null
        }
        if (val) {
            // Safety fallback: Never allow the loading overlay to freeze the interface for > 10 seconds
            loadingSafetyTimer = setTimeout(() => {
                if (isLoading.value) {
                    console.warn('[PMS Safety Guard] Loading overlay auto-dismissed after 10s timeout.')
                    isLoading.value = false
                }
            }, 10000)
        }
    }

    return { loguser,  authtoken,  isLoading, setloguser, getloguser, settoken, getauthtoken, setIsLoading, /* getuserperms, hasperm */}

})
