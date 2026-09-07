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



    const isLoading = ref(false)
    const setIsLoading = (val) => {
        isLoading.value = !!val
    }

    return { loguser, authtoken, isLoading, setloguser, getloguser, settoken, getauthtoken, setIsLoading }

})
