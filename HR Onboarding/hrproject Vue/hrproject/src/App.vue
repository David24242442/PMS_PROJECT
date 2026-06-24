<script setup>
  import { RouterLink, RouterView } from 'vue-router'
  import { useUsersStore } from '@/stores/user';
  import LoadingOverlay from '@/components/LoadingOverlay.vue';
  
  const userstore = useUsersStore()

  let { setloguser, settoken } = userstore

  const user = JSON.parse(localStorage.getItem('hrproject_user'))
  const user_token = JSON.parse(localStorage.getItem('hrproject_user_token'))

  if(user){
      setloguser(user)
      settoken(user_token)
  }
</script>

<template>
  <LoadingOverlay />
  <RouterView />
</template>

<style scoped>

</style>
