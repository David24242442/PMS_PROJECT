<script setup>
    import { ref, computed, onMounted, watch } from "vue";
    import { useRouter, useRoute } from 'vue-router';
    import { useUsersStore } from '@/stores/user';
    import axios from '@/helpers/pms_axios';
    
    const userstore = useUsersStore()
    let { setloguser, loguser } = userstore

    const router = useRouter()
    const route = useRoute()

    const isManager = computed(() => {
        return !!(loguser?.admin || loguser?.is_manager || loguser?.position_id === 3 || loguser?.position_id === 4 || loguser?.designation === 'Manager');
    });

    const hasPendingAppraisals = ref(false);

    // State for Pinned Sidebar & Hover Expand/Collapse
    const isPinned = ref(localStorage.getItem('hr-nav-pinned') === 'true');
    const isHovered = ref(false);
    const isExpanded = computed(() => isPinned.value || isHovered.value);

    // Read saved open menu preferences from localStorage
    const getSavedMenus = () => {
        try {
            return JSON.parse(localStorage.getItem('hr-nav-open-menus') || '{}');
        } catch (e) {
            return {};
        }
    };
    const savedMenus = getSavedMenus();

    // State for Dropdown Menus - persist user choice
    const openMenus = ref({
        onboarding: savedMenus.onboarding ?? false,
        pms: savedMenus.pms ?? true,
        admin: savedMenus.admin ?? false
    });

    // Dark Mode State
    const isDarkMode = ref(localStorage.getItem('hr-theme') === 'dark');

    const applyTheme = (dark) => {
        if (dark) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    };

    const toggleDarkMode = () => {
        isDarkMode.value = !isDarkMode.value;
        localStorage.setItem('hr-theme', isDarkMode.value ? 'dark' : 'light');
        applyTheme(isDarkMode.value);
    };

    const emit = defineEmits(['pin-change', 'hover-change', 'nav-change']);

    // Emit nav-change to coordinate layout with AppInterface
    watch(isExpanded, (val) => {
        emit('nav-change', val);
    }, { immediate: true });

    const togglePin = () => {
        isPinned.value = !isPinned.value;
        localStorage.setItem('hr-nav-pinned', isPinned.value.toString());
        emit('pin-change', isPinned.value);
    };

    // Auto-sync open menu with active route
    const syncMenuWithRoute = (path) => {
        if (!path) return;
        if (path.startsWith('/pms')) {
            openMenus.value.pms = true;
        } else if (path.startsWith('/onboarding') || path.startsWith('/employees') || path.startsWith('/employee')) {
            openMenus.value.onboarding = true;
        } else if (path.startsWith('/users') || path.startsWith('/hr/manage-employees') || path.startsWith('/hr/sessions')) {
            openMenus.value.admin = true;
        }
    };

    watch(() => route.path, (newPath) => {
        syncMenuWithRoute(newPath);
    }, { immediate: true });

    // Persist openMenus across page refreshes
    watch(openMenus, (val) => {
        localStorage.setItem('hr-nav-open-menus', JSON.stringify(val));
    }, { deep: true });

    // Hover intent timers: avoids frantic jitter on fast cursor movements
    let hoverTimer = null;
    const onMouseEnter = () => {
        if (isPinned.value) return;
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => {
            isHovered.value = true;
            emit('hover-change', true);
        }, 50);
    };

    const onMouseLeave = () => {
        if (isPinned.value) return;
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => {
            isHovered.value = false;
            emit('hover-change', false);
        }, 140);
    };

    const toggleMenu = (menu) => {
        openMenus.value[menu] = !openMenus.value[menu];
    };

    const logout = () => {
        try {
            axios.post('activity-logs', {
                action: 'LOGOUT',
                module: 'AUTH',
                description: `User ${loguser?.name || 'User'} (${loguser?.employee_code || loguser?.username || ''}) logged out of PMS portal.`
            }).catch(() => {});
        } catch(e) {}
        setloguser(null)
        localStorage.removeItem('hrproject_user');
        localStorage.removeItem('hrproject_user_token');
        router.push('/')
    };

    onMounted(async () => {
        // Apply saved theme preference
        applyTheme(isDarkMode.value);

        // Sync initial pin state with layout
        if (isPinned.value) {
            emit('pin-change', true);
        }

        // Check for pending appraisals for navigation status
        if (loguser?.permissions?.includes('/pms/appraisal') || loguser?.position_id === 4) {
            try {
                const currentYear = new Date().getFullYear();
                const res = await axios.get('pms/goals', { params: { year: currentYear } });
                if (res && res.data && res.data.status === 'success') {
                    const goals = res.data.data;
                    hasPendingAppraisals.value = goals.some(g => ['goal_created', 'appraisal_completed', 'review_completed'].includes(g.display_status));
                }
            } catch (e) {
                console.warn('Failed to get goals for nav - not authenticated or no access', e.message);
            }
        }

        // Fetch pending online submissions count
        fetchPendingOnline();
    });

    const pendingOnlineCount = ref(0);
    const fetchPendingOnline = async () => {
        try {
            const res = await axios.get('online-onboardings', { params: { per_page: 1 } });
            if (res.data && res.data.stats) {
                pendingOnlineCount.value = res.data.stats.pending || 0;
            }
        } catch (e) {
            // silent
        }
    };

    const isOnboardingMainActive = computed(() => {
        return route.path === '/onboarding' && route.query.tab !== 'online' && route.query.tab !== '5';
    });

    const isOnlineTabActive = computed(() => {
        return route.path === '/onboarding' && (route.query.tab === 'online' || route.query.tab === '5');
    });

</script>

<template>
    <aside 
        class="sidebar-container" 
        :class="{ 
            'collapsed': !isExpanded, 
            'expanded-hover': isHovered && !isPinned, 
            'is-pinned': isPinned 
        }" 
        @mouseenter="onMouseEnter" 
        @mouseleave="onMouseLeave"
    >
        
        <!-- Sidebar Header / Logo -->
        <div class="sidebar-top">
            <div class="logo">
                <span class="logo-text" v-show="isExpanded"> HR <span class="pms-styled">PORTAL</span></span>
                <span class="logo-text-collapsed" v-show="!isExpanded">HR</span>
            </div>
            <button 
                v-show="isExpanded" 
                @click.stop="togglePin" 
                class="pin-toggle-btn" 
                :title="isPinned ? 'Unpin sidebar (auto-collapse)' : 'Pin sidebar permanently'"
            >
                <i class="pi pi-thumbtack text-xs" :class="isPinned ? 'text-amber-400 rotate-45' : 'text-white/40 hover:text-white'"></i>
            </button>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="sidebar-menu">
            
            <!-- Dashboard (Global) -->
            <router-link v-if="isManager && loguser?.permissions?.includes('/dashboard')" to="/dashboard" class="menu-item" :title="!isExpanded ? 'Dashboard' : ''">
                <div class="active-indicator"></div>
                <span class="pi pi-th-large"></span>
                <span class="link-name" v-show="isExpanded">Dashboard</span>
            </router-link>

            <!-- Onboarding Group -->
            <div v-if="isManager && (loguser?.permissions?.includes('/onboarding') || loguser?.permissions?.includes('/employees') || loguser?.permissions?.includes('/onboarding/online'))" class="menu-item-wrapper dropdown" :class="{ 'showMenu': openMenus.onboarding && isExpanded }">
                <div class="menu-item" @click="toggleMenu('onboarding')" :title="!isExpanded ? 'Onboarding' : ''">
                    <div class="active-indicator"></div>
                    <span class="pi pi-briefcase"></span>
                    <span class="link-name" v-show="isExpanded">Onboarding</span>
                    <span class="pi pi-chevron-down arrow" v-show="isExpanded"></span>
                </div>
                <div class="sub-menu" v-show="isExpanded">
                    <router-link v-if="loguser?.permissions?.includes('/onboarding')" to="/onboarding" exact>
                        <span class="pi pi-list"></span>
                        Onboarding
                    </router-link>
                    <router-link v-if="loguser?.permissions?.includes('/employees')" to="/employees">
                        <span class="pi pi-users"></span>
                        Employees
                    </router-link>
                    <router-link 
                        v-if="loguser?.permissions?.includes('/onboarding/online') || loguser?.admin || loguser?.position_id === 4" 
                        to="/onboarding/online"
                        class="flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span class="pi pi-globe"></span>
                            <span>Online Onboarding</span>
                        </div>
                        <span v-if="pendingOnlineCount > 0" class="sub-menu-badge">{{ pendingOnlineCount }}</span>
                    </router-link>
                </div>
            </div>

            <!-- PMS Group (For Employees: ONLY Goals is visible) -->
            <div v-if="isManager || loguser?.position_id === 1 || loguser?.permissions?.includes('/pms/goals')" class="menu-item-wrapper dropdown" :class="{ 'showMenu': openMenus.pms && isExpanded }">
                <div class="menu-item" @click="toggleMenu('pms')" :title="!isExpanded ? 'PMS' : ''">
                    <div class="active-indicator"></div>
                    <span class="pi pi-chart-bar"></span>
                    <span class="link-name" v-show="isExpanded">PMS</span>
                    <span class="pi pi-chevron-down arrow" v-show="isExpanded"></span>
                </div>
                <div class="sub-menu" v-show="isExpanded">
                    <router-link v-if="isManager && (loguser?.admin || loguser?.is_manager || loguser?.position_id === 3 || loguser?.position_id === 4 || loguser?.permissions?.includes('/pms/dashboard'))" to="/pms/dashboard">
                        <span class="pi pi-home"></span>
                        Dashboard
                    </router-link>
                    <router-link to="/pms/goals">
                        <span class="pi pi-bullseye"></span>
                        Goals
                    </router-link>
                    <router-link v-if="isManager && (loguser?.admin || loguser?.is_manager || loguser?.position_id === 3 || loguser?.position_id === 4 || loguser?.permissions?.includes('/pms/appraisal'))" to="/pms/appraisal">
                        <span class="pi pi-file-edit"></span>
                        Appraisal
                    </router-link>
                    <router-link v-if="isManager && (loguser?.admin || loguser?.is_manager || loguser?.position_id === 3 || loguser?.position_id === 4 || loguser?.permissions?.includes('/pms/review'))" to="/pms/review">
                        <span class="pi pi-check-circle"></span>
                        Review
                    </router-link>
                    <router-link v-if="isManager && (loguser?.admin || loguser?.permissions?.includes('/hr/submissions'))" to="/hr/submissions">
                        <span class="pi pi-inbox"></span>
                        PMS Submissions
                    </router-link>
                </div>
            </div>

            <!-- Admin Group - Manager / Admin Only -->
            <div v-if="isManager && (loguser?.admin || loguser?.position_id === 4 || ['/users', '/pms/employee-master', '/hr/manage-employees', '/hr/sessions'].some(p => loguser?.permissions?.includes(p)))" class="menu-item-wrapper dropdown" :class="{ 'showMenu': openMenus.admin && isExpanded }">
                <div class="menu-item" @click="toggleMenu('admin')" :title="!isExpanded ? 'HR Admin' : ''">
                    <div class="active-indicator"></div>
                    <span class="pi pi-users"></span>
                    <span class="link-name" v-show="isExpanded">HR Admin</span>
                    <span class="pi pi-chevron-down arrow" v-show="isExpanded"></span>
                </div>
                <div class="sub-menu" v-show="isExpanded">
                    <router-link v-if="loguser?.admin || loguser?.position_id === 4 || loguser?.permissions?.includes('/users')" to="/users">
                        <span class="pi pi-user-edit"></span>
                        Manage Users
                    </router-link>
                    <router-link v-if="loguser?.admin || loguser?.position_id === 4 || loguser?.permissions?.includes('/pms/employee-master')" to="/pms/employee-master">
                        <span class="pi pi-users"></span>
                        Line Manager Console
                    </router-link>
                    <router-link v-if="loguser?.admin || loguser?.position_id === 4 || loguser?.permissions?.includes('/hr/manage-employees')" to="/hr/manage-employees">
                        <span class="pi pi-id-card"></span>
                        Manage Employees
                    </router-link>
                    <router-link v-if="loguser?.admin || loguser?.position_id === 4 || loguser?.permissions?.includes('/hr/sessions')" to="/hr/sessions">
                        <span class="pi pi-history"></span>
                        Sessions
                    </router-link>
                </div>
            </div>

            <router-link to="/profile" class="menu-item" :title="!isExpanded ? 'Profile' : ''">
                <div class="active-indicator"></div>
                <span class="pi pi-user"></span>
                <span class="link-name" v-show="isExpanded">Profile</span>
            </router-link>

            <!-- Logout -->
            <a @click="logout" class="menu-item mt-auto" :title="!isExpanded ? 'Logout' : ''">
                <div class="active-indicator opacity-0"></div>
                <span class="pi pi-sign-out"></span>
                <span class="link-name" v-show="isExpanded">Logout</span>
            </a>

        </nav>

        <!-- Sidebar Footer (Profile) -->
        <div class="sidebar-footer">
            <div class="user-profile" v-show="isExpanded">
                <div class="avatar">
                    <i class="pi pi-user text-lg"></i>
                </div>
                <div class="user-details">
                    <p>{{ loguser?.name }}</p>
                    <small>{{ loguser?.admin ? 'Administrator' : 'Employee' }}</small>
                </div>
            </div>
            <div class="avatar collapsed-avatar" v-show="!isExpanded" :title="loguser?.name">
                <i class="pi pi-user"></i>
            </div>
            
            <!-- Dark/Light Mode Toggle -->
            <button class="theme-toggle-btn" @click="toggleDarkMode" :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                <i :class="isDarkMode ? 'pi pi-sun' : 'pi pi-moon'"></i>
                <span v-show="isExpanded" class="theme-toggle-label">{{ isDarkMode ? 'Light Mode' : 'Dark Mode' }}</span>
            </button>
        </div>
    </aside>
</template>

<style scoped>
    /* 
       VARIABLES 
       Match the PMS styling variables or hardcode them if global vars aren't available.
       Since we know PMS uses a purple gradient, we'll implement that directly.
    */
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 80px;
        --spacing-md: 10px;
        --spacing-lg: 16px;
    }

    .sidebar-container {
        width: 80px;
        height: 100vh;
        background: linear-gradient(180deg, #1A237E 0%, #121858 100%); /* Indigo 900 to Deep Navy */
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        transition: width 0.22s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.22s ease;
        will-change: width;
        color: white; 
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        overflow-x: hidden;
    }

    /* Active Indicator Pill */
    .active-indicator {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: white;
        border-radius: 0 4px 4px 0;
        opacity: 0;
        transition: all 0.2s ease;
    }

    .router-link-active > .active-indicator,
    .showMenu .menu-item > .active-indicator {
        opacity: 1;
    }

    /* Professional Icon Colors */
    .sidebar-container .pi {
        color: var(--prof-text-muted) !important;
        transition: color 0.3s ease;
    }

    .sidebar-top {
        padding: 0 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 80px;
        background: linear-gradient(to right, rgba(255, 255, 255, 0.05), transparent);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo-text {
        font-size: 1.25rem;
        font-weight: 900;
        color: white;
        white-space: nowrap;
        letter-spacing: -0.5px;
    }

    .pms-styled {
        color: #F97316; /* Orange */
        font-weight: 900;
    }

    .sidebar-menu {
        flex: 1;
        padding: 16px 0;
        overflow-y: auto;
    }

    /* Custom Scrollbar for Sidebar */
    .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar-menu::-webkit-scrollbar-track {
        background: transparent;
    }
    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }
    .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.4);
    }
    
    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.95) !important;
        text-decoration: none;
        transition: background 0.18s ease, color 0.18s ease;
        cursor: pointer;
        position: relative;
        white-space: nowrap;
        overflow: hidden;
    }

    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white !important;
    }

    .menu-item:hover .pi {
        color: white !important;
    }

    .router-link-active, .showMenu > .menu-item {
        background: rgba(255, 255, 255, 0.15) !important;
        color: white !important;
        font-weight: 600;
    }

    .router-link-active .pi, .showMenu > .menu-item .pi {
        color: white !important;
    }

    .menu-item .pi {
        font-size: 1.1rem;
        min-width: 24px;
        color: rgba(255, 255, 255, 0.85) !important;
    }

    .link-name {
        font-size: 0.95rem;
        font-weight: 500;
    }

    .arrow {
        margin-left: auto;
        font-size: 0.65rem !important;
        transition: transform 0.3s ease;
    }

    .showMenu .arrow {
        transform: rotate(180deg);
    }

    .sub-menu {
        padding: 4px 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        background: rgba(18, 24, 88, 0.6); /* Deeper Indigo */
    }

    .showMenu .sub-menu {
        max-height: 500px;
    }

    .sub-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 20px 10px 48px;
        color: rgba(255, 255, 255, 0.8) !important;
        text-decoration: none;
        font-size: 0.85rem;
        transition: background 0.18s ease, color 0.18s ease;
        white-space: nowrap;
        overflow: hidden;
    }

    .sub-menu a:hover {
        color: white !important;
        background: rgba(255, 255, 255, 0.05);
    }

    .sub-menu a.router-link-active {
        color: #818cf8 !important; /* Indigo 400 */
        font-weight: 600;
        background: rgba(99, 102, 241, 0.1);
    }

    .sub-menu-badge {
        margin-left: auto;
        padding: 2px 7px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 800;
        background: #ef4444;
        color: #ffffff !important;
        line-height: 1;
        box-shadow: 0 1px 3px rgba(239, 68, 68, 0.4);
    }

    .sidebar-footer {
        padding: 24px;
        background: rgba(18, 24, 88, 0.8);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-details p {
        font-weight: 600;
        font-size: 0.9rem;
        color: white;
        margin: 0;
    }

    .user-details small {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .logout-link {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .logout-link:hover {
        background: #FFE4E6 !important;
        color: #BE123C !important;
    }

    .collapse-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .collapse-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .collapse-btn:hover .pi {
        color: white !important;
    }

    /* Collapsed State (Default) */
    .sidebar-container.collapsed {
        width: 80px;
    }

    /* Hover Expanded State & Pinned State */
    .sidebar-container.expanded-hover,
    .sidebar-container.is-pinned {
        width: 260px;
    }

    .sidebar-container.expanded-hover:not(.is-pinned) {
        box-shadow: 12px 0 36px rgba(10, 15, 45, 0.45);
    }

    .pin-toggle-btn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pin-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .collapsed .sidebar-top {
        justify-content: center;
        padding: 0;
    }

    .collapsed .menu-item {
        justify-content: center;
        width: 48px;
        height: 48px;
        padding: 0 !important;
        margin: 4px auto;
        border-radius: 12px;
    }

    .collapsed .menu-item .pi {
        margin: 0;
        font-size: 1.25rem;
        min-width: unset;
    }

    .collapsed .sub-menu {
        display: none !important;
        max-height: 0 !important;
        opacity: 0 !important;
        visibility: hidden !important;
    }

    .collapsed .sidebar-footer {
        padding: 16px 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Theme Toggle Button */
    .theme-toggle-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 14px;
        margin-top: 12px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.9);
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .theme-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .theme-toggle-btn .pi {
        font-size: 1rem;
        color: #fbbf24 !important;
        min-width: 20px;
        text-align: center;
    }

    .theme-toggle-label {
        font-weight: 500;
        letter-spacing: 0.01em;
    }

    .collapsed .theme-toggle-btn {
        justify-content: center;
        padding: 10px;
    }


</style>