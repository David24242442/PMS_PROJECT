<script setup>
import { useUsersStore } from '@/stores/user';
import logo from '@/assets/img/melcom_logo_full.png.png'; // Using the full logo identified in audit

const userStore = useUsersStore();
</script>

<template>
  <Transition name="fade">
    <div v-if="userStore.isLoading" class="loading-overlay">
      <div class="loading-content">
        <div class="logo-container animate-logo-pulse">
          <img :src="logo" alt="Melcom Logo" class="loading-logo">
        </div>
        <div class="loading-text-container">
          <span class="loading-text">Please wait...</span>
          <div class="loading-bar">
            <div class="loading-bar-progress"></div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.logo-container {
  width: 180px;
  height: auto;
}

.loading-logo {
  width: 100%;
  height: auto;
  filter: drop-shadow(0 4px 12px rgba(88, 48, 224, 0.1));
}

.loading-text-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.loading-text {
  font-size: 11px;
  font-weight: 800;
  color: #5830E0;
  text-transform: uppercase;
  letter-spacing: 2px;
}

.loading-bar {
  width: 120px;
  height: 2px;
  background: rgba(88, 48, 224, 0.1);
  border-radius: 10px;
  overflow: hidden;
  position: relative;
}

.loading-bar-progress {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, #5830E0, transparent);
  animation: loadingBar 1.5s infinite linear;
}

@keyframes loadingBar {
  0% { left: -100%; }
  100% { left: 100%; }
}

/* Vue Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Dark Mode */
:global(body.dark-mode) .loading-overlay {
  background: rgba(15, 23, 42, 0.75) !important;
}
:global(body.dark-mode) .loading-text {
  color: #818cf8 !important;
}
:global(body.dark-mode) .loading-bar {
  background: rgba(129, 140, 248, 0.15) !important;
}
:global(body.dark-mode) .loading-bar-progress {
  background: linear-gradient(90deg, transparent, #818cf8, transparent) !important;
}
</style>
