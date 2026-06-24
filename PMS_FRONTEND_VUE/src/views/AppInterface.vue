<script setup>
    import { ref } from 'vue';
    import Nav from '@/components/Nav.vue'
    import { RouterView } from 'vue-router'
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { loguser } = userstore;
    
    const isSidebarCollapsed = ref(localStorage.getItem('pms-sidebar-collapsed') === 'true');
</script>

<template>
    <div class="app-container">
        <!-- Sidebar -->
        <Nav @update:collapsed="(val) => isSidebarCollapsed = val"></Nav>

        <!-- Main Content Area -->
        <main class="main-content" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <div class="search-box">
                        <i class="pi pi-search"></i>
                        <input type="text" placeholder="Search team or goals...">
                    </div>
                </div>

                <div class="top-bar-right">
                    <button class="icon-btn">
                        <i class="pi pi-bell"></i>
                        <span class="badge">4</span>
                    </button>
                    <div class="user-menu">
                        <div class="avatar-sm">
                            <i class="pi pi-user"></i>
                        </div>
                        <div class="user-menu-info">
                            <span class="user-name">{{ loguser?.name }}</span>
                            <span class="user-role">{{ loguser?.admin ? 'Manager' : 'Employee' }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content-wrapper">
                <RouterView></RouterView>
            </div>
        </main>
    </div>
</template>

<style scoped>
.app-container {
    display: flex;
    min-height: 100vh;
    background-color: var(--color-background);
}

.main-content {
    flex: 1;
    margin-left: var(--sidebar-width);
    display: flex;
    flex-direction: column;
    min-width: 0;
    transition: margin-left 0.3s ease;
}

.main-content.sidebar-collapsed {
    margin-left: 72px;
}

.top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 var(--spacing-xl);
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--color-light);
    height: var(--topbar-height);
    position: sticky;
    top: 0;
    z-index: 100;
}

.search-box {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    background-color: var(--color-lighter);
    padding: 0.6rem 1.2rem;
    border-radius: var(--radius-full);
    width: 400px;
    border: 1.5px solid var(--color-light);
    transition: all 0.2s ease;
}

.search-box:focus-within {
    border-color: var(--color-primary);
    background: white;
}

.search-box i {
    color: var(--text-muted);
}

.search-box input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 0.9rem;
    color: var(--text-primary);
}

.top-bar-right {
    display: flex;
    align-items: center;
    gap: var(--spacing-xl);
}

.icon-btn {
    position: relative;
    background: var(--color-lighter);
    border: none;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-full);
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.icon-btn:hover {
    background-color: var(--color-light);
    color: var(--color-primary);
}

.badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: var(--color-danger);
    color: white;
    font-size: 0.65rem;
    padding: 2px 5px;
    border-radius: var(--radius-full);
    font-weight: 700;
    border: 2px solid white;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: 0.4rem 0.8rem;
    border-radius: var(--radius-lg);
    background-color: var(--color-lighter);
    border: 1px solid var(--color-light);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-full);
    background-color: var(--color-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.user-name {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--text-primary);
}

.user-role {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-muted);
}

.content-wrapper {
    padding: var(--spacing-xl);
    max-width: 1600px;
    margin: 0 auto;
    width: 100%;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: var(--spacing-md);
    }
}
</style>
