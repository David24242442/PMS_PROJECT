<script setup>
    import { ref, onMounted, watch } from "vue";
    import { useRouter } from 'vue-router';
    import { useUsersStore } from '@/stores/user';
    const emit = defineEmits(['update:collapsed']);
    
    const userstore = useUsersStore();
    const { setloguser, loguser } = userstore;
    const router = useRouter();

    const isDarkMode = ref(localStorage.getItem('pms-theme') === 'dark');
    const isCollapsed = ref(localStorage.getItem('pms-sidebar-collapsed') === 'true');
    const openMenus = ref({
        performance: true,
        hrAdmin: true
    }); 

    const toggleMenu = (menu) => {
        if (!isCollapsed.value) {
            openMenus.value[menu] = !openMenus.value[menu];
        }
    };

    const toggleCollapse = () => {
        isCollapsed.value = !isCollapsed.value;
        localStorage.setItem('pms-sidebar-collapsed', isCollapsed.value.toString());
        emit('update:collapsed', isCollapsed.value);
    };

    const toggleTheme = () => {
        isDarkMode.value = !isDarkMode.value;
        const mode = isDarkMode.value ? 'dark' : 'light';
        localStorage.setItem('pms-theme', mode);
        updateThemeClass();
    };

    const updateThemeClass = () => {
        if (isDarkMode.value) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    };

    onMounted(() => {
        updateThemeClass();
        emit('update:collapsed', isCollapsed.value);
    });

    watch(isCollapsed, (newVal) => {
        // When collapsed, close all submenus
        if (newVal) {
            openMenus.value.performance = false;
            openMenus.value.hrAdmin = false;
        }
    });

    const logout = () => {
        setloguser(null);
        localStorage.removeItem('hrproject_user');
        localStorage.removeItem('hrproject_user_token');
        router.push('/');
    };
</script>

<template>
    <aside class="sidebar-container" :class="{ 'dark': isDarkMode, 'collapsed': isCollapsed }">
        <div class="sidebar-top">
            <div class="logo">
                <span class="logo-text" v-show="!isCollapsed">MELCOM <span class="pms-styled">PMS</span></span>
                <span class="logo-text-collapsed" v-show="isCollapsed">P</span>
            </div>
            <button class="collapse-btn" @click="toggleCollapse" :title="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                <span class="pi" :class="isCollapsed ? 'pi-angle-right' : 'pi-angle-left'"></span>
            </button>
        </div>

        <nav class="sidebar-menu">
            <router-link to="/app/pms/dashboard" class="menu-item" :title="isCollapsed ? 'Dashboard' : ''">
                <span class="pi pi-home"></span>
                <span class="link-name" v-show="!isCollapsed">Dashboard</span>
            </router-link>
            
            <div class="menu-item-wrapper dropdown" :class="{ 'showMenu': openMenus.performance && !isCollapsed }">
                <div class="menu-item" @click="toggleMenu('performance')" :title="isCollapsed ? 'Performance' : ''">
                    <span class="pi pi-chart-bar"></span>
                    <span class="link-name" v-show="!isCollapsed">Performance</span>
                    <span class="pi pi-chevron-down arrow" v-show="!isCollapsed"></span>
                </div>
                <div class="sub-menu" v-show="!isCollapsed">
                    <router-link to="/app/pms/goals">
                        <span class="pi pi-bullseye"></span>
                        Goals
                    </router-link>
                    <router-link to="/app/pms/appraisal">
                        <span class="pi pi-file-edit"></span>
                        My Appraisal
                    </router-link>
                    <router-link to="/app/pms/review">
                        <span class="pi pi-check-circle"></span>
                        Review
                    </router-link>
                </div>
            </div>

            <div class="menu-item-wrapper dropdown" :class="{ 'showMenu': openMenus.hrAdmin && !isCollapsed }">
                <div class="menu-item" @click="toggleMenu('hrAdmin')" :title="isCollapsed ? 'HR Admin' : ''">
                    <span class="pi pi-users"></span>
                    <span class="link-name" v-show="!isCollapsed">HR Admin</span>
                    <span class="pi pi-chevron-down arrow" v-show="!isCollapsed"></span>
                </div>
                <div class="sub-menu" v-show="!isCollapsed">
                    <router-link to="/app/hr/submissions">
                        <span class="pi pi-inbox"></span>
                        Submissions
                    </router-link>
                    <router-link to="/app/hr/reports">
                        <span class="pi pi-file"></span>
                        Reports
                    </router-link>
                    <router-link v-if="loguser?.admin" to="/app/admin/users">
                        <span class="pi pi-shield"></span>
                        PMS Admin
                    </router-link>
                    <router-link v-if="loguser?.admin" to="/users">
                        <span class="pi pi-user-plus"></span>
                        App Users
                    </router-link>
                    <router-link v-if="loguser?.admin" to="/app/admin/upload">
                        <span class="pi pi-upload"></span>
                        Upload Data
                    </router-link>
                </div>
            </div>

            <router-link to="/profile" class="menu-item" :title="isCollapsed ? 'Profile' : ''">
                <span class="pi pi-user"></span>
                <span class="link-name" v-show="!isCollapsed">Profile</span>
            </router-link>

            <a @click="logout" class="menu-item logout-link" :title="isCollapsed ? 'Logout' : ''">
                <span class="pi pi-sign-out"></span>
                <span class="link-name" v-show="!isCollapsed">Logout</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile" v-show="!isCollapsed">
                <div class="avatar">
                    <i class="pi pi-user text-xl"></i>
                </div>
                <div class="user-details">
                    <p>{{ loguser?.name }}</p>
                    <small>{{ loguser?.admin ? 'Administrator' : 'Employee' }}</small>
                </div>
            </div>
            <div class="avatar collapsed-avatar" v-show="isCollapsed" :title="loguser?.name">
                <i class="pi pi-user"></i>
            </div>
            
            <div class="theme-toggler" @click="toggleTheme" v-show="!isCollapsed">
                <span class="pi pi-sun" :class="{ active: !isDarkMode }"></span>
                <span class="pi pi-moon" :class="{ active: isDarkMode }"></span>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.sidebar-container {
    width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 50%, #6d28d9 100%);
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 24px rgba(79, 70, 229, 0.15);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar-top {
    padding: var(--spacing-lg) var(--spacing-lg) var(--spacing-md);
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 90px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.logo-text {
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    white-space: nowrap;
    letter-spacing: -0.5px;
}

.pms-styled {
    color: #ff4444;
    font-size: 1.4rem;
    font-weight: 900;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    font-family: 'Arial Black', sans-serif;
    letter-spacing: 1px;
}

.logo-text-collapsed {
    color: #ff4444;
    font-size: 1.5rem;
    font-weight: 900;
    font-family: 'Arial Black', sans-serif;
}

.sidebar-menu {
    flex: 1;
    padding: var(--spacing-md) 0;
    overflow-y: auto;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

/* Hide scrollbar by default */
.sidebar-menu::-webkit-scrollbar {
    width: 0;
    background: transparent;
}

/* Show thin scrollbar on hover */
.sidebar-menu:hover {
    scrollbar-width: thin; /* Firefox */
    scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.sidebar-menu:hover::-webkit-scrollbar {
    width: 4px;
}

.sidebar-menu:hover::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-menu:hover::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.sidebar-menu:hover::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.menu-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md) var(--spacing-lg);
    color: rgba(255, 255, 255, 0.75);
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
    border-left: 4px solid transparent;
    margin: 2px 8px;
    border-radius: var(--radius-lg);
}

.menu-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.menu-item.router-link-active {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border-left-color: #ffffff;
    font-weight: 600;
}

.menu-item .pi {
    font-size: 1.2rem;
    min-width: 24px;
}

.link-name {
    font-size: 0.95rem;
    font-weight: 500;
}

.arrow {
    margin-left: auto;
    font-size: 0.8rem !important;
    transition: transform 0.3s ease;
}

.showMenu .arrow {
    transform: rotate(180deg);
}

.sub-menu {
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: rgba(0, 0, 0, 0.15);
    margin: 0 8px;
    border-radius: var(--radius-md);
}

.showMenu .sub-menu {
    max-height: 250px;
}

.sub-menu a {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-sm) var(--spacing-lg) var(--spacing-sm) calc(var(--spacing-lg) + 28px); /* Adjusted padding for border */
    color: rgba(255, 255, 255, 0.65);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.sub-menu a:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
}

.sub-menu a.router-link-active {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
    border-left-color: #ffffff;
    font-weight: 600;
}

.sidebar-footer {
    padding: var(--spacing-lg);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.1);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.user-details p {
    font-weight: 700;
    font-size: 0.85rem;
    color: #ffffff;
}

.user-details small {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.7);
}

.theme-toggler {
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 32px;
    width: 64px;
    cursor: pointer;
    border-radius: var(--radius-md);
    padding: 0 var(--spacing-xs);
}

.theme-toggler span {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
    border-radius: var(--radius-sm);
}

.theme-toggler span.active {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

.logout-link {
    color: #fca5a5 !important;
}

.logout-link:hover {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #fecaca !important;
}

/* Collapse Button */
.collapse-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.collapse-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

/* Collapsed State */
.sidebar-container.collapsed {
    width: 72px;
}

.collapsed .sidebar-top {
    padding: var(--spacing-md);
    justify-content: center;
    flex-direction: column;
    gap: var(--spacing-sm);
    height: auto;
    padding-top: var(--spacing-lg);
}

.collapsed .logo {
    justify-content: center;
}

.collapsed .logo-img {
    width: 40px;
    height: 40px;
}

.collapsed .menu-item {
    justify-content: center;
    padding: var(--spacing-md);
    margin: 4px auto;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    border-left: none;
}

.collapsed .menu-item .pi {
    font-size: 1.3rem;
    min-width: auto;
}

.collapsed .menu-item.router-link-active {
    border-left: none;
    background: rgba(255, 255, 255, 0.2);
}

.collapsed .sidebar-footer {
    padding: var(--spacing-md);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.collapsed-avatar {
    width: 40px;
    height: 40px;
    margin: 0 auto;
}
</style>
