<script setup>
    import { ref, onMounted, reactive, computed, watch } from 'vue'
    import { log, toastt } from '@/helpers/essential'
    import { positions, findposition } from '@/data/masterdata'
    import axios from '@/helpers/pms_axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()

    let users = ref([])
    let loaded = ref(false)
    let masterEmployees = ref([])
    let creatinguser = ref(false)
    let saving = ref(false)
    let user = reactive({})
    let selecteduser = ref(null)

    // Search, filters, sort, pagination
    const searchQuery = ref('')
    const selectedDepartment = ref('')
    const selectedRole = ref('')
    const selectedPosition = ref('')
    const sortBy = ref('name_asc')
    const pageSize = ref(15)
    const currentPage = ref(1)

    // Modal controls
    const showPassword = ref(false)
    const masterEmpSearchQuery = ref('')
    const isMasterEmpDropdownOpen = ref(false)

    // Administrator Privileges Check
    const isAdmin = computed(() => {
        const me = userstore.getloguser() || JSON.parse(localStorage.getItem('hrproject_user') || '{}')
        return Boolean(me && (me.admin || me.role === 'admin' || me.position_id == 4))
    })

    // User Deletion State
    const deleteModalVisible = ref(false)
    const userToDelete = ref(null)
    const deletingUser = ref(false)

    const confirmDeleteUser = (u) => {
        if (!isAdmin.value) {
            toastt('Unauthorized: Only administrators can delete users', 'error')
            return
        }
        userToDelete.value = u
        deleteModalVisible.value = true
    }

    const cancelDeleteUser = () => {
        deleteModalVisible.value = false
        userToDelete.value = null
    }

    const isDeletingSelf = computed(() => {
        if (!userToDelete.value) return false
        const me = userstore.getloguser() || JSON.parse(localStorage.getItem('hrproject_user') || '{}')
        if (!me) return false
        return (me.id && userToDelete.value.id && me.id === userToDelete.value.id) || 
               (me.username && userToDelete.value.username && me.username.toLowerCase() === userToDelete.value.username.toLowerCase())
    })

    const executeDeleteUser = async () => {
        if (!isAdmin.value) {
            toastt('Unauthorized: Administrator rights required', 'error')
            return
        }
        if (!userToDelete.value || !userToDelete.value.id) return
        if (isDeletingSelf.value) {
            toastt('You cannot delete your own active account', 'error')
            return
        }

        deletingUser.value = true
        try {
            let res
            try {
                res = await axios.delete(`users/${userToDelete.value.id}`)
            } catch (delErr) {
                res = await axios.post('deleteuser', { id: userToDelete.value.id })
            }

            if (res && res.data && res.data.status === 'error') {
                toastt(res.data.message || 'Failed to delete user', 'error')
                deletingUser.value = false
                return
            }

            const deletedId = userToDelete.value.id
            const deletedName = userToDelete.value.name || userToDelete.value.username
            users.value = users.value.filter(u => u.id !== deletedId)

            if (creatinguser.value && user.id === deletedId) {
                creatinguser.value = false
            }

            toastt(`User "${deletedName}" was successfully removed`, 'success')
            deleteModalVisible.value = false
            userToDelete.value = null
        } catch (err) {
            console.error('Error deleting user:', err)
            const errMsg = err?.response?.data?.message || 'Error removing user. Please try again.'
            toastt(errMsg, 'error')
        } finally {
            deletingUser.value = false
        }
    }
    
    // Constant definition to represent dynamic page accesses
    const categoryPages = [
        {
            name: 'Onboarding',
            icon: 'pi pi-user-plus',
            pages: [
                { label: 'Dashboard', path: '/dashboard' },
                { label: 'Onboarding', path: '/onboarding' },
                { label: 'Employees', path: '/employees' }
            ]
        },
        {
            name: 'PMS',
            icon: 'pi pi-chart-line',
            pages: [
                { label: 'PMS Dashboard', path: '/pms/dashboard' },
                { label: 'Goals', path: '/pms/goals' },
                { label: 'Appraisal', path: '/pms/appraisal' },
                { label: 'Review', path: '/pms/review' },
                { label: 'PMS Submissions', path: '/hr/submissions' }
            ]
        },
        {
            name: 'HR Admin',
            icon: 'pi pi-shield',
            pages: [
                { label: 'Manage Users', path: '/users' },
                { label: 'Line Manager Console', path: '/pms/employee-master' } 
            ]
        }
    ]

    let activeTab = ref('Onboarding')

    const changeTab = (name) => {
        activeTab.value = name;
    }

    onMounted(async () => {
        try {
            const res = await axios.get('users')
            users.value = res.data || []
            loaded.value = true
        } catch (error) {
            console.error('Error fetching users:', error)
            toastt('Failed to load users list', 'error')
        }

        // Fetch master employees for fast auto-fill
        try {
            const empRes = await axios.get('pms/get-employees')
            const rawList = empRes.data?.data || empRes.data || []
            if (Array.isArray(rawList)) {
                masterEmployees.value = rawList
            }
        } catch (empErr) {
            console.warn('Could not load master employees for user link:', empErr)
        }
    })

    const vUppercase = {
        mounted(el) {
            el.addEventListener('input', updateValue)
        },
        unmounted(el) {
            el.removeEventListener('input', updateValue)
        }
    }

    const updateValue = (el) => {
        const input = el.target
        const sourceValue = input.value
        const newValue = sourceValue.toUpperCase()

        if (sourceValue !== newValue) {
            input.value = newValue
            input.dispatchEvent(new Event('input', { bubbles: true }))
        }
    }

    // --- Computed Metrics ---
    const totalUsersCount = computed(() => users.value.length)
    const adminCount = computed(() => users.value.filter(u => Boolean(u.admin)).length)
    const managerCount = computed(() => users.value.filter(u => Boolean(u.is_manager) || u.position_id == 3).length)
    const viewerCount = computed(() => users.value.filter(u => !u.admin && !u.is_manager && u.position_id != 3 && u.position_id != 4).length)

    // Dynamic departments list
    const departmentsList = computed(() => {
        const set = new Set()
        users.value.forEach(u => {
            if (u.department && u.department.trim()) {
                set.add(u.department.trim())
            }
        })
        return Array.from(set).sort()
    })

    // --- Filtered & Sorted Users ---
    const filteredUsers = computed(() => {
        let list = [...users.value]

        // Search query across Username, Full Name, Code, Department, Email
        if (searchQuery.value && searchQuery.value.trim()) {
            const q = searchQuery.value.trim().toLowerCase()
            list = list.filter(u => {
                const username = (u.username || '').toLowerCase()
                const name = (u.name || '').toLowerCase()
                const code = (u.employee_code || '').toLowerCase()
                const dept = (u.department || '').toLowerCase()
                const email = (u.email || '').toLowerCase()
                return username.includes(q) || name.includes(q) || code.includes(q) || dept.includes(q) || email.includes(q)
            })
        }

        // Department filter
        if (selectedDepartment.value) {
            list = list.filter(u => (u.department || '').trim().toLowerCase() === selectedDepartment.value.toLowerCase())
        }

        // Role filter
        if (selectedRole.value === 'admin') {
            list = list.filter(u => Boolean(u.admin) || u.role === 'admin' || u.role === 'Admin')
        } else if (selectedRole.value === 'manager') {
            list = list.filter(u => Boolean(u.is_manager) || u.position_id == 3 || u.role === 'manager' || u.role === 'Manager')
        } else if (selectedRole.value === 'entry') {
            list = list.filter(u => u.position_id == 2)
        } else if (selectedRole.value === 'viewer') {
            list = list.filter(u => u.position_id == 1 && !u.admin && !u.is_manager)
        } else if (selectedRole.value === 'basic' || selectedRole.value === 'standard' || selectedRole.value === 'employee') {
            list = list.filter(u => !u.admin && !u.is_manager && u.position_id != 3 && u.position_id != 4)
        }

        // Position filter
        if (selectedPosition.value) {
            list = list.filter(u => String(u.position_id) === String(selectedPosition.value))
        }

        // Sorting
        list.sort((a, b) => {
            if (sortBy.value === 'name_asc') {
                return (a.name || '').localeCompare(b.name || '')
            } else if (sortBy.value === 'name_desc') {
                return (b.name || '').localeCompare(a.name || '')
            } else if (sortBy.value === 'username_asc') {
                return (a.username || '').localeCompare(b.username || '')
            } else if (sortBy.value === 'code_asc') {
                return (a.employee_code || '').localeCompare(b.employee_code || '')
            } else if (sortBy.value === 'newest') {
                return (b.id || 0) - (a.id || 0)
            }
            return 0
        })

        return list
    })

    // --- Pagination ---
    const totalPages = computed(() => {
        if (pageSize.value === -1) return 1
        return Math.ceil(filteredUsers.value.length / pageSize.value) || 1
    })

    const paginatedUsers = computed(() => {
        if (pageSize.value === -1) return filteredUsers.value
        const start = (currentPage.value - 1) * pageSize.value
        return filteredUsers.value.slice(start, start + pageSize.value)
    })

    // Reset page on filter changes
    watch([searchQuery, selectedDepartment, selectedRole, selectedPosition, pageSize, sortBy], () => {
        currentPage.value = 1
    })

    const clearFilters = () => {
        searchQuery.value = ''
        selectedDepartment.value = ''
        selectedRole.value = ''
        selectedPosition.value = ''
        sortBy.value = 'name_asc'
        currentPage.value = 1
    }

    const hasActiveFilters = computed(() => {
        return Boolean(searchQuery.value.trim() || selectedDepartment.value || selectedRole.value || selectedPosition.value)
    })

    // --- Avatar & Initials ---
    const getInitials = (name) => {
        if (!name) return 'U'
        const parts = name.trim().split(/\s+/)
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase()
        }
        return name.slice(0, 2).toUpperCase()
    }

    const getAvatarGradient = (str) => {
        const gradients = [
            'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
            'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
            'linear-gradient(135deg, #10b981 0%, #047857 100%)',
            'linear-gradient(135deg, #f59e0b 0%, #b45309 100%)',
            'linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%)',
            'linear-gradient(135deg, #ec4899 0%, #be185d 100%)',
            'linear-gradient(135deg, #06b6d4 0%, #0e7490 100%)'
        ]
        if (!str) return gradients[0]
        let hash = 0
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash)
        }
        return gradients[Math.abs(hash) % gradients.length]
    }

    // --- Quick Clipboard Action ---
    const copyToClipboard = (text, label) => {
        if (!text || text === '---') return
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text)
            toastt(`Copied ${label || 'value'} to clipboard`, 'success')
        }
    }

    // --- CSV Export ---
    const exportUsersCSV = () => {
        if (!filteredUsers.value.length) {
            toastt('No users found to export', 'error')
            return
        }

        const headers = ['Username', 'Full Name', 'Employee Code', 'Department', 'Location', 'Admin Privileges', 'Position Role', 'Reports To', 'Email']
        const rows = filteredUsers.value.map(u => [
            `"${(u.username || '').replace(/"/g, '""')}"`,
            `"${(u.name || '').replace(/"/g, '""')}"`,
            `"${(u.employee_code || '').replace(/"/g, '""')}"`,
            `"${(u.department || '').replace(/"/g, '""')}"`,
            `"${(u.location || '').replace(/"/g, '""')}"`,
            `"${u.admin ? 'Yes (Administrator)' : 'No (Basic)'}"`,
            `"${(findposition(u.position_id) || 'Employee').replace(/"/g, '""')}"`,
            `"${(u.report_to || '').replace(/"/g, '""')}"`,
            `"${(u.email || '').replace(/"/g, '""')}"`
        ])

        const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')
        const encodedUri = encodeURI(csvContent)
        const link = document.createElement('a')
        link.setAttribute('href', encodedUri)
        const dateStr = new Date().toISOString().split('T')[0]
        link.setAttribute('download', `melcom_portal_users_${dateStr}.csv`)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        toastt('Users list successfully exported to CSV', 'success')
    }

    // --- CRUD Operations ---
    const create = () => {
        saving.value = true

        axios.post('users', user, {})
            .then(res => {
                const data = res.data
                
                if (data.status === 'error') {
                    const errMsgs = Object.values(data.errors || {}).map(e => e[0]).join(', ')
                    toastt(data.message + (errMsgs ? ': ' + errMsgs : ''), 'error')
                    saving.value = false
                    return
                }

                const newUser = data.data || data
                users.value.unshift(newUser)
                saving.value = false
                creatinguser.value = false
                toastt('Portal identity created successfully', 'success')
            }).catch((error) => {
                toastt('Error creating user. Please check credentials and try again', 'error')
                saving.value = false
                log(error)
            })
    }

    const update = () => {
        saving.value = true

        axios.post('updateuser', user, {})
            .then(res => {
                const data = res.data

                if (data.status === 'error') {
                    toastt(data.message, 'error')
                    saving.value = false
                    return
                }

                saving.value = false
                const updatedUser = data.data || data
                
                // Update in local array
                if (selecteduser.value !== null && users.value[selecteduser.value]) {
                    users.value[selecteduser.value] = updatedUser
                } else {
                    const idx = users.value.findIndex(u => u.id === updatedUser.id)
                    if (idx !== -1) users.value[idx] = updatedUser
                }

                creatinguser.value = false
                selecteduser.value = null
                toastt('User identity updated successfully', 'success')
            }).catch((error) => {
                toastt('Error updating user. Please try again', 'error')
                saving.value = false
                log(error)
            })
    }

    const edit = (u, index) => {
        selecteduser.value = index
        showPassword.value = false
        masterEmpSearchQuery.value = u.name ? `${u.name}${u.employee_code ? ` (${u.employee_code})` : ''}` : ''
        isMasterEmpDropdownOpen.value = false

        const isMgr = Boolean(u.is_manager || u.position_id == 3)
        let posId = u.position_id
        if (isMgr && (!posId || posId == 1)) {
            posId = 3
        }

        // Ensure permissions array exists during edit
        Object.assign(user, { 
            ...u, 
            is_manager: isMgr,
            position_id: posId || 1,
            password: '', // Kept empty; user types only if changing
            permissions: Array.isArray(u.permissions) ? [...u.permissions] : [] 
        })
        creatinguser.value = true
    }

    const createuser = () => {
        selecteduser.value = null
        showPassword.value = false
        masterEmpSearchQuery.value = ''
        isMasterEmpDropdownOpen.value = false

        // Clear reactive object cleanly
        Object.keys(user).forEach(k => delete user[k])
        user.permissions = []
        user.admin = false
        user.is_manager = false
        user.position_id = 1 // Default Viewer
        user.password = ''
        creatinguser.value = true
    }

    const onPositionSelectChange = () => {
        if (user.position_id == 3 || user.position_id == 4) {
            user.is_manager = true
        } else if (user.position_id == 1 || user.position_id == 2 || user.position_id == 5) {
            if (!user.admin) {
                user.is_manager = false
            }
        }
    }

    const toggleManager = () => {
        user.is_manager = !user.is_manager
        if (user.is_manager) {
            if (user.position_id != 3 && user.position_id != 4) {
                user.position_id = 3
            }
        } else {
            if (user.position_id == 3) {
                user.position_id = 1
            }
        }
    }

    const onManagerCheckboxChange = () => {
        if (user.is_manager) {
            if (user.position_id != 3 && user.position_id != 4) {
                user.position_id = 3
            }
        } else {
            if (user.position_id == 3) {
                user.position_id = 1
            }
        }
    }

    // --- Master Employee Smart Search & Auto-Fill ---
    const filteredMasterEmployees = computed(() => {
        if (!masterEmployees.value || !masterEmployees.value.length) return []
        if (!masterEmpSearchQuery.value || !masterEmpSearchQuery.value.trim()) {
            return masterEmployees.value.slice(0, 20)
        }
        const q = masterEmpSearchQuery.value.trim().toLowerCase()
        return masterEmployees.value.filter(e => {
            const name = (e.name || `${e.firstname || ''} ${e.surname || ''}`).toLowerCase()
            const code = (e.employee_code || e.employeeid || '').toLowerCase()
            const dept = (e.department || e.emp_dept || '').toLowerCase()
            const loc = (e.location || e.emp_location || '').toLowerCase()
            return name.includes(q) || code.includes(q) || dept.includes(q) || loc.includes(q)
        }).slice(0, 30)
    })

    const selectMasterEmployee = (emp) => {
        if (!emp) return

        // 1. Full Name
        const fullName = (emp.name || `${emp.firstname || ''} ${emp.surname || ''}`).trim()
        user.name = fullName

        // 2. Employee Code
        const empCode = (emp.employee_code || emp.employeeid || '').trim()
        user.employee_code = empCode

        // 3. Portal Username: First Name of employee + " " + Employee Code (e.g. DAVID H664)
        const firstName = (emp.firstname || fullName.split(' ')[0] || '').trim().toUpperCase()
        const codeUpper = empCode.toUpperCase()
        if (firstName && codeUpper) {
            user.username = `${firstName} ${codeUpper}`
        } else if (codeUpper) {
            user.username = codeUpper
        } else if (firstName) {
            user.username = firstName
        }

        // 4. Email: If user has an email populate it; if not, leave blank for manual input
        const empEmail = emp.email || emp.user_email || emp.emp_email || ''
        user.email = empEmail ? empEmail.trim() : ''

        // 5. Department & Location
        user.department = emp.department || emp.emp_dept || ''
        user.location = emp.location || emp.emp_location || ''

        // 6. Reports To (Line Manager)
        user.report_to = emp.line_manager_name || emp.manager_name || ''

        // 7. Position / Role match
        const posText = (emp.position || emp.emp_position || emp.job_title || emp.joiningposition || '').toLowerCase()
        if (posText) {
            const matchedPos = positions.find(p => posText.includes(p.name.toLowerCase()) || p.name.toLowerCase().includes(posText))
            if (matchedPos) {
                user.position_id = matchedPos.id
                if (matchedPos.id == 3 || matchedPos.id == 4) {
                    user.is_manager = true
                }
            }
        }

        masterEmpSearchQuery.value = `${fullName} (${empCode})`
        isMasterEmpDropdownOpen.value = false
        toastt(`Auto-filled details for ${fullName} (${empCode})`, 'success')
    }

    // --- Password Generator ---
    const generatePassword = () => {
        const randDigits = Math.floor(1000 + Math.random() * 9000)
        const generated = `Melcom@${randDigits}`
        user.password = generated
        showPassword.value = true
        if (navigator.clipboard) {
            navigator.clipboard.writeText(generated)
            toastt(`Password generated & copied: ${generated}`, 'success')
        } else {
            toastt(`Password generated: ${generated}`, 'success')
        }
    }

    // --- Administrator Auto-Permissions Toggle ---
    const selectAllPermissions = () => {
        const allPaths = categoryPages.flatMap(cat => cat.pages.map(p => p.path))
        if (!Array.isArray(user.permissions)) {
            user.permissions = []
        }
        user.permissions = Array.from(new Set([...user.permissions, ...allPaths]))
    }

    const toggleAdmin = () => {
        user.admin = !user.admin
        if (user.admin) {
            selectAllPermissions()
            toastt('Administrator privileges enabled: All module & tab permissions selected', 'info')
        }
    }

    const onAdminCheckboxChange = () => {
        if (user.admin) {
            selectAllPermissions()
            toastt('Administrator privileges enabled: All module & tab permissions selected', 'info')
        }
    }

    const isAllModulesSelected = computed(() => {
        if (!user.permissions || !Array.isArray(user.permissions)) return false
        const allPaths = categoryPages.flatMap(cat => cat.pages.map(p => p.path))
        return allPaths.length > 0 && allPaths.every(p => user.permissions.includes(p))
    })

    const toggleAllModules = () => {
        const allPaths = categoryPages.flatMap(cat => cat.pages.map(p => p.path))
        if (!user.permissions) user.permissions = []
        if (isAllModulesSelected.value) {
            user.permissions = []
        } else {
            user.permissions = [...allPaths]
        }
    }

    // --- Permission Batch Toggles ---
    const isCategoryFullySelected = (catName) => {
        const cat = categoryPages.find(c => c.name === catName)
        if (!cat || !user.permissions) return false
        return cat.pages.every(p => user.permissions.includes(p.path))
    }

    const toggleCategoryPermissions = (catName) => {
        const cat = categoryPages.find(c => c.name === catName)
        if (!cat) return
        if (!user.permissions) user.permissions = []

        if (isCategoryFullySelected(catName)) {
            const paths = cat.pages.map(p => p.path)
            user.permissions = user.permissions.filter(p => !paths.includes(p))
        } else {
            cat.pages.forEach(p => {
                if (!user.permissions.includes(p.path)) {
                    user.permissions.push(p.path)
                }
            })
        }
    }

    const togglePermission = (path) => {
        if (!user.permissions) user.permissions = []
        const index = user.permissions.indexOf(path)
        if (index > -1) {
            user.permissions.splice(index, 1)
        } else {
            user.permissions.push(path)
        }
    }
</script>
<template>
    <div class="users-page-container">
        <!-- 1. Top Header Banner -->
        <div class="page-header-row">
            <div class="header-titles">
                <div class="flex items-center gap-3">
                    <div class="header-icon-box">
                        <i class="pi pi-users text-xl text-indigo-600"></i>
                    </div>
                    <div>
                        <h2 class="page-main-title">User Management</h2>
                        <p class="page-subtitle">Configure portal accounts, security access, administrative roles, and module permissions</p>
                    </div>
                </div>
            </div>

            <div class="header-actions">
                <button @click="exportUsersCSV" class="btn-secondary-action" title="Export current filtered users to CSV">
                    <i class="pi pi-download mr-1.5 text-xs"></i>
                    Export CSV
                </button>
                <Button @click="createuser" class="btn-primary-action">
                    <i class="pi pi-user-plus mr-2 text-sm"></i>
                    Create User
                </Button>
            </div>
        </div>

        <!-- 2. KPI Metrics Overview -->
        <div class="kpi-metrics-grid">
            <div class="metric-card">
                <div class="metric-icon-wrap bg-indigo-50 text-indigo-600">
                    <i class="pi pi-users text-lg"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-label">Total Users</span>
                    <span class="metric-val text-indigo-950">{{ totalUsersCount }}</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-wrap bg-purple-50 text-purple-600">
                    <i class="pi pi-shield text-lg"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-label">Administrators</span>
                    <span class="metric-val text-purple-950">{{ adminCount }}</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-wrap bg-emerald-50 text-emerald-600">
                    <i class="pi pi-briefcase text-lg"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-label">Line Managers</span>
                    <span class="metric-val text-emerald-950">{{ managerCount }}</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-wrap bg-blue-50 text-blue-600">
                    <i class="pi pi-id-card text-lg"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-label">Basic Employees</span>
                    <span class="metric-val text-blue-950">{{ viewerCount }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Advanced Search & Filter Bar -->
        <div class="filter-controls-card">
            <div class="filter-grid">
                <!-- Search Input: Username, Full Name, Code (Icon Removed, Compact Width) -->
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Search by Username, Full Name, or Code..."
                        class="search-input-field"
                    />
                    <button 
                        v-if="searchQuery" 
                        @click="searchQuery = ''" 
                        class="clear-search-btn"
                        title="Clear search"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- Department Filter -->
                <div class="filter-select-wrapper">
                    <select v-model="selectedDepartment" class="filter-select-field">
                        <option value="">All Departments</option>
                        <option v-for="dept in departmentsList" :key="dept" :value="dept">{{ dept }}</option>
                    </select>
                </div>

                <!-- Role Filter -->
                <div class="filter-select-wrapper">
                    <select v-model="selectedRole" class="filter-select-field">
                        <option value="">All Roles</option>
                        <option value="admin">Administrators Only</option>
                        <option value="manager">Line Managers</option>
                        <option value="basic">Basic Users</option>
                    </select>
                </div>

                <!-- Position Filter -->
                <div class="filter-select-wrapper">
                    <select v-model="selectedPosition" class="filter-select-field">
                        <option value="">All Positions</option>
                        <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div class="filter-select-wrapper">
                    <select v-model="sortBy" class="filter-select-field">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="username_asc">Username</option>
                        <option value="code_asc">Employee Code</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>

                <!-- Reset Filters Button -->
                <button 
                    v-if="hasActiveFilters" 
                    @click="clearFilters" 
                    class="reset-filters-btn"
                    title="Reset all filters"
                >
                    <i class="pi pi-filter-slash text-xs mr-1"></i>
                    Reset
                </button>

                <!-- Rows Per Page: Positioned at the Last Column of the Controls Bar -->
                <div class="rows-per-page-col">
                    <span class="rows-label">Rows per page:</span>
                    <select v-model="pageSize" class="page-size-select">
                        <option :value="10">10</option>
                        <option :value="15">15</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="-1">All</option>
                    </select>
                </div>
            </div>

            <!-- Active Results Counter -->
            <div class="filter-footer-info">
                <span class="results-count-text">
                    Showing <strong>{{ paginatedUsers.length }}</strong> of <strong>{{ filteredUsers.length }}</strong> users
                    <span v-if="hasActiveFilters" class="text-indigo-600 ml-1 font-semibold">(Filtered from {{ totalUsersCount }})</span>
                </span>
            </div>
        </div>

        <!-- 4. Enhanced Modern Table -->
        <div class="table-outer-wrapper">
            <table class="modern-users-table">
                <thead>
                    <tr>
                        <th style="width: 26%;">USER IDENTITY</th>
                        <th style="width: 12%;">CODE</th>
                        <th style="width: 18%;">DEPARTMENT / LOCATION</th>
                        <th style="width: 12%;">ROLE</th>
                        <th style="width: 12%;">POSITION</th>
                        <th style="width: 12%;">REPORTS TO</th>
                        <th style="width: 8%; text-align: center;">ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(u, ind) in paginatedUsers" :key="u.id || ind" class="user-table-row">
                        <!-- User Identity: Avatar + Name + Username -->
                        <td>
                            <div class="user-profile-cell">
                                <div 
                                    class="user-avatar" 
                                    :style="{ background: getAvatarGradient(u.name || u.username) }"
                                >
                                    {{ getInitials(u.name || u.username) }}
                                </div>
                                <div class="user-names-box">
                                    <span class="user-display-name">{{ u.name || '---' }}</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="user-username-tag">@{{ u.username }}</span>
                                        <span v-if="u.email" class="user-email-text" :title="u.email">{{ u.email }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Code -->
                        <td>
                            <span 
                                v-if="u.employee_code" 
                                class="code-mono-badge" 
                                @click="copyToClipboard(u.employee_code, 'Employee Code')"
                                title="Click to copy code"
                            >
                                {{ u.employee_code }}
                                <i class="pi pi-copy text-[10px] ml-1 opacity-60"></i>
                            </span>
                            <span v-else class="text-xs text-slate-400 font-mono">---</span>
                        </td>

                        <!-- Department & Location -->
                        <td>
                            <div class="dept-location-cell">
                                <span class="dept-text font-semibold text-slate-700">
                                    <i class="pi pi-building text-slate-400 mr-1 text-xs"></i>
                                    {{ u.department || '---' }}
                                </span>
                                <span v-if="u.location" class="location-subtext">
                                    <i class="pi pi-map-marker text-slate-400 mr-1 text-[11px]"></i>
                                    {{ u.location }}
                                </span>
                            </div>
                        </td>

                        <!-- Role / Admin Status -->
                        <td>
                            <span v-if="u.admin || u.role === 'admin' || u.role === 'Admin'" class="role-badge admin-badge">
                                <i class="pi pi-shield text-xs mr-1"></i>
                                Admin
                            </span>
                            <span v-else-if="u.is_manager || u.position_id == 3 || u.role === 'manager' || u.role === 'Manager'" class="role-badge manager-role-badge">
                                <i class="pi pi-briefcase text-xs mr-1"></i>
                                Manager
                            </span>
                            <span v-else-if="u.position_id == 4" class="role-badge hrhead-role-badge">
                                <i class="pi pi-user text-xs mr-1"></i>
                                Hr Head
                            </span>
                            <span v-else-if="u.position_id == 2" class="role-badge entry-role-badge">
                                <i class="pi pi-file-edit text-xs mr-1"></i>
                                Entry
                            </span>
                            <span v-else-if="u.position_id == 1" class="role-badge viewer-role-badge">
                                <i class="pi pi-eye text-xs mr-1"></i>
                                Viewer
                            </span>
                            <span v-else class="role-badge basic-badge">
                                Basic
                            </span>
                        </td>

                        <!-- Position -->
                        <td>
                            <span 
                                :class="[
                                    'position-pill',
                                    (u.position_id == 3 || (u.is_manager && u.position_id != 4)) ? 'pos-manager' : 
                                    u.position_id == 4 ? 'pos-hrhead' : 
                                    u.position_id == 2 ? 'pos-entry' : 
                                    u.position_id == 1 ? 'pos-viewer' : 'pos-employee'
                                ]"
                            >
                                {{ (u.position_id == 3 || (u.is_manager && u.position_id != 4)) ? 'MANAGER' : (findposition(u.position_id) || 'EMPLOYEE') }}
                            </span>
                        </td>

                        <!-- Reports To -->
                        <td>
                            <span class="text-xs text-slate-600 font-medium truncate block max-w-[130px]" :title="u.report_to">
                                {{ u.report_to || '---' }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td style="text-align: center;">
                            <div class="action-buttons-group">
                                <button
                                    class="action-edit-btn"
                                    @click="edit(u, ind)"
                                    title="Manage Identity & Permissions"
                                >
                                    <i class="pi pi-user-edit text-sm"></i>
                                </button>
                                <button
                                    v-if="isAdmin"
                                    class="action-delete-btn"
                                    @click="confirmDeleteUser(u)"
                                    title="Delete User"
                                >
                                    <i class="pi pi-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!paginatedUsers.length && loaded">
                        <td colspan="7" class="empty-state-cell">
                            <div class="empty-state-wrap">
                                <i class="pi pi-search text-3xl text-slate-300 mb-2"></i>
                                <span class="font-bold text-slate-700 text-sm">No portal users found</span>
                                <p class="text-xs text-slate-400 mt-0.5">Try refining your search terms or clearing current filters</p>
                                <button v-if="hasActiveFilters" @click="clearFilters" class="btn-clear-empty mt-3">
                                    Clear All Filters
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Loading State -->
                    <tr v-if="!loaded">
                        <td colspan="7" class="empty-state-cell">
                            <div class="empty-state-wrap">
                                <i class="pi pi-spin pi-spinner text-3xl text-indigo-500 mb-2"></i>
                                <span class="font-bold text-slate-700 text-sm">Loading user identities...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination Bar -->
            <div v-if="totalPages > 1" class="pagination-footer-row">
                <span class="text-xs text-slate-500">
                    Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages }}</strong>
                </span>

                <div class="pagination-buttons">
                    <button 
                        @click="currentPage = Math.max(1, currentPage - 1)" 
                        :disabled="currentPage === 1"
                        class="page-btn"
                        title="Previous Page"
                    >
                        <i class="pi pi-chevron-left text-xs"></i>
                    </button>

                    <button 
                        v-for="p in totalPages" 
                        :key="p" 
                        v-show="Math.abs(p - currentPage) <= 2 || p === 1 || p === totalPages"
                        @click="currentPage = p"
                        :class="['page-num-btn', { 'active': currentPage === p }]"
                    >
                        {{ p }}
                    </button>

                    <button 
                        @click="currentPage = Math.min(totalPages, currentPage + 1)" 
                        :disabled="currentPage === totalPages"
                        class="page-btn"
                        title="Next Page"
                    >
                        <i class="pi pi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Enhanced "Manage Portal Identity" Modal -->
    <div class='poppop' v-if="creatinguser">
        <div class="poppopin">
            <!-- Modal Header -->
            <div class='popheader-premium'>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white text-lg">
                        <i :class="user.id ? 'pi pi-user-edit' : 'pi pi-user-plus'"></i>
                    </div>
                    <div>
                        <h3 class="pop-title">
                            {{ user.id ? 'Manage': 'Create' }} Portal Identity
                        </h3>
                        <p class="pop-subtext">Personnel Account Credentials & Access Permissions</p>
                    </div>
                </div>

                <button class='poppopclose-btn' @click="creatinguser = false" title="Close">
                    <i class="pi pi-times text-xs"></i>
                </button>
            </div>

            <!-- Profile Summary Ribbon (When Editing Existing User) -->
            <div v-if="user.id" class="edit-profile-ribbon">
                <div class="user-avatar-large" :style="{ background: getAvatarGradient(user.name || user.username) }">
                    {{ getInitials(user.name || user.username) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 text-sm">{{ user.name || 'Staff User' }}</span>
                        <span class="user-username-tag">@{{ user.username }}</span>
                        <span v-if="user.admin" class="role-badge admin-badge !py-0.5 !px-2 text-[10px]">Admin</span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-3">
                        <span v-if="user.employee_code">Code: <strong>{{ user.employee_code }}</strong></span>
                        <span v-if="user.department">Dept: <strong>{{ user.department }}</strong></span>
                        <span v-if="user.position_id">Role: <strong>{{ findposition(user.position_id) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Modal Content Form -->
            <div class="poplist">
                <!-- Master Employee Smart Search Auto-Fill -->
                <div class="employee-linker-box mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-indigo-700 flex items-center gap-1.5">
                            <i class="pi pi-sparkles text-xs"></i>
                            Smart Search Employee Master
                        </span>
                        <span class="text-[10px] text-slate-400 font-medium">Search by Full Name or Employee Code to auto-fill</span>
                    </div>

                    <!-- Search Input & Autocomplete Dropdown -->
                    <div class="smart-search-wrapper relative">
                        <div class="relative">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            <input 
                                type="text" 
                                v-model="masterEmpSearchQuery"
                                @focus="isMasterEmpDropdownOpen = true"
                                @input="isMasterEmpDropdownOpen = true"
                                placeholder="Search by name (e.g. DAVID) or code (e.g. H664)..."
                                class="premium-input !py-2.5 !pl-9 !pr-8 !text-xs w-full font-medium"
                            />
                            <button 
                                v-if="masterEmpSearchQuery" 
                                type="button" 
                                @click="masterEmpSearchQuery = ''; isMasterEmpDropdownOpen = false" 
                                class="clear-smart-btn"
                                title="Clear search"
                            >
                                <i class="pi pi-times text-[10px]"></i>
                            </button>
                        </div>

                        <!-- Floating Results Dropdown -->
                        <div 
                            v-if="isMasterEmpDropdownOpen" 
                            class="smart-dropdown-panel"
                        >
                            <div class="dropdown-header-bar flex items-center justify-between px-3 py-2 bg-slate-50 border-b border-slate-100 text-[11px] text-slate-500 font-bold">
                                <span>{{ filteredMasterEmployees.length }} Employee(s) Found</span>
                                <button type="button" @click="isMasterEmpDropdownOpen = false" class="text-slate-400 hover:text-slate-600">
                                    <i class="pi pi-times text-xs"></i>
                                </button>
                            </div>

                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-100">
                                <div 
                                    v-for="emp in filteredMasterEmployees" 
                                    :key="emp.employee_code || emp.employeeid || emp.id"
                                    @click="selectMasterEmployee(emp)"
                                    class="smart-dropdown-item flex items-center gap-3 p-2.5 hover:bg-indigo-50/70 cursor-pointer transition-colors"
                                >
                                    <div 
                                        class="user-avatar !w-8 !h-8 !text-xs font-bold shrink-0"
                                        :style="{ background: getAvatarGradient(emp.name || emp.firstname) }"
                                    >
                                        {{ getInitials(emp.name || emp.firstname) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-800 text-xs truncate">{{ emp.name || `${emp.firstname} ${emp.surname}` }}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 font-mono text-[10px] font-bold shrink-0">
                                                #{{ emp.employee_code || emp.employeeid }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-500 mt-0.5">
                                            <span v-if="emp.department" class="truncate max-w-[130px] font-medium">{{ emp.department }}</span>
                                            <span v-if="emp.department && emp.location">&bull;</span>
                                            <span v-if="emp.location" class="truncate max-w-[110px]">{{ emp.location }}</span>
                                            <span v-if="emp.email || emp.emp_email" class="text-indigo-600 truncate ml-auto font-medium">
                                                {{ emp.email || emp.emp_email }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-if="!filteredMasterEmployees.length" class="p-4 text-center text-xs text-slate-400">
                                    <i class="pi pi-info-circle mr-1"></i>
                                    No employee matching "{{ masterEmpSearchQuery }}" found
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Status Chip -->
                    <div v-if="user.employee_code" class="mt-2.5 flex items-center justify-between px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800">
                        <div class="flex items-center gap-1.5 truncate">
                            <i class="pi pi-check-circle text-emerald-600 text-xs shrink-0"></i>
                            <span class="truncate">Linked: <strong>{{ user.name }}</strong> (<code>{{ user.employee_code }}</code>)</span>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700 shrink-0 ml-2">Username: @{{ user.username }}</span>
                    </div>
                </div>

                <!-- Group 1: Basic Identity -->
                <div class="form-section-group mb-6">
                    <h4 class="section-group-title text-indigo-600">
                        <span class="title-dot bg-indigo-600"></span>
                        Basic Identity
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="field-label">Full Name *</label>
                            <input type="text" v-model="user.name" v-uppercase required placeholder="e.g. DAVE NORVISI" class="premium-input">
                        </div>
                        <div class="space-y-1">
                            <label class="field-label">Portal Username *</label>
                            <input type="text" v-model="user.username" v-uppercase required placeholder="e.g. DAVE" class="premium-input">
                        </div>
                    </div>
                </div>

                <!-- Group 2: Credentials & Access -->
                <div class="form-section-group mb-6">
                    <h4 class="section-group-title text-purple-600">
                        <span class="title-dot bg-purple-600"></span>
                        Credentials & Security
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="field-label">Email (Optional)</label>
                            <input type="email" v-model="user.email" placeholder="user@melcomgroup.com" class="premium-input">
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="field-label">
                                    Password {{ user.id ? '(Leave blank to keep unchanged)' : '*' }}
                                </label>
                                <button type="button" @click="generatePassword" class="text-[10px] text-indigo-600 font-bold hover:underline">
                                    Generate
                                </button>
                            </div>
                            <div class="password-input-wrap">
                                <input 
                                    :type="showPassword ? 'text' : 'password'" 
                                    v-model="user.password" 
                                    :placeholder="user.id ? '(Unchanged)' : 'Enter or Generate Password'" 
                                    class="premium-input !pr-10"
                                >
                                <button 
                                    type="button" 
                                    @click="showPassword = !showPassword" 
                                    class="password-eye-btn"
                                    title="Toggle show/hide password"
                                >
                                    <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Group 3: Organizational Details -->
                <div class="form-section-group mb-6">
                    <h4 class="section-group-title text-blue-600">
                        <span class="title-dot bg-blue-600"></span>
                        Organizational Details
                    </h4>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="space-y-1">
                            <label class="field-label">Employee Code</label>
                            <input type="text" v-model="user.employee_code" placeholder="e.g. EX359 / AML008" class="premium-input">
                        </div>
                        <div class="space-y-1">
                            <label class="field-label">Position / Job Role</label>
                            <select v-model="user.position_id" @change="onPositionSelectChange" class="premium-input">
                                <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="space-y-1">
                            <label class="field-label">Department</label>
                            <input type="text" v-model="user.department" placeholder="e.g. IT HEAD / ACCOUNTS" class="premium-input">
                        </div>
                        <div class="space-y-1">
                            <label class="field-label">Location</label>
                            <input type="text" v-model="user.location" placeholder="e.g. HEAD OFFICE / ACCRA" class="premium-input">
                        </div>
                    </div>

                    <div class="space-y-1 mb-4">
                        <label class="field-label">Reports To (Line Manager)</label>
                        <select v-model="user.report_to" class="premium-input">
                            <option value="">(None / Self-Reporting)</option>
                            <option v-for="u in users" :key="u.id" :value="u.name">{{ u.name }} (@{{ u.username }})</option>
                        </select>
                    </div>

                    <!-- Administrative Privileges Switches -->
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <!-- Admin Switch -->
                        <div 
                            @click="toggleAdmin"
                            :class="['privilege-toggle-card', { 'active-privilege-admin': user.admin }]"
                        >
                            <div class="privilege-icon bg-indigo-50 text-indigo-600">
                                <i class="pi pi-shield"></i>
                            </div>
                            <div class="flex-1">
                                <span class="privilege-title">Administrator</span>
                                <span class="privilege-desc">Full access to users & configuration</span>
                            </div>
                            <input type="checkbox" v-model="user.admin" @change="onAdminCheckboxChange" class="w-4 h-4 text-indigo-600 rounded" @click.stop>
                        </div>

                        <!-- Manager Switch -->
                        <div 
                            @click="toggleManager"
                            :class="['privilege-toggle-card', { 'active-privilege-manager': user.is_manager }]"
                        >
                            <div class="privilege-icon bg-emerald-50 text-emerald-600">
                                <i class="pi pi-briefcase"></i>
                            </div>
                            <div class="flex-1">
                                <span class="privilege-title">Line Manager</span>
                                <span class="privilege-desc">Can review PMS appraisals & goals</span>
                            </div>
                            <input type="checkbox" v-model="user.is_manager" @change="onManagerCheckboxChange" class="w-4 h-4 text-emerald-600 rounded" @click.stop>
                        </div>
                    </div>
                </div>

                <!-- Group 4: Assigned Module Permissions -->
                <div class="form-section-group mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="section-group-title text-slate-600 !mb-0">
                            <span class="title-dot bg-slate-600"></span>
                            Module Access & Tab Permissions
                        </h4>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="toggleAllModules"
                                class="batch-select-btn !bg-indigo-50 !text-indigo-700 !border-indigo-200 hover:!bg-indigo-100"
                                :title="isAllModulesSelected ? 'Deselect all modules' : 'Select all modules across all tabs'"
                            >
                                <i :class="isAllModulesSelected ? 'pi pi-times mr-1' : 'pi pi-check-circle mr-1'"></i>
                                {{ isAllModulesSelected ? 'Deselect All Modules' : 'Select All Modules' }}
                            </button>
                            <button 
                                type="button" 
                                @click="toggleCategoryPermissions(activeTab)"
                                class="batch-select-btn"
                            >
                                <i :class="isCategoryFullySelected(activeTab) ? 'pi pi-times mr-1' : 'pi pi-check-circle mr-1'"></i>
                                {{ isCategoryFullySelected(activeTab) ? 'Deselect in ' + activeTab : 'Select in ' + activeTab }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tabs Header -->
                    <div class="module-tabs-header">
                        <button 
                            type="button"
                            v-for="cat in categoryPages" 
                            :key="cat.name" 
                            @click="changeTab(cat.name)"
                            :class="['module-tab-btn', { 'active': activeTab === cat.name }]"
                        >
                            <i :class="cat.icon" class="mr-1.5 text-xs"></i>
                            {{ cat.name }}
                        </button>
                    </div>

                    <!-- Tab Content Cards -->
                    <template v-for="cat in categoryPages" :key="cat.name">
                        <div v-if="activeTab === cat.name" class="mt-3">
                            <div class="grid grid-cols-3 gap-2.5">
                                <div 
                                    v-for="page in cat.pages" 
                                    :key="page.path"
                                    @click="togglePermission(page.path)"
                                    :class="['permission-card-enhanced', { 'selected': user.permissions && user.permissions.includes(page.path) }]"
                                >
                                    <div class="perm-checkbox">
                                        <i class="pi pi-check" v-show="user.permissions && user.permissions.includes(page.path)"></i>
                                    </div>
                                    <span class="perm-label">{{ page.label }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Bottom Actions -->
                <div class="modal-footer-actions">
                    <button 
                        v-if="user.id && isAdmin" 
                        type="button" 
                        @click="confirmDeleteUser(user)" 
                        class="btn-modal-delete"
                        title="Delete User"
                    >
                        <i class="pi pi-trash text-xs mr-1.5"></i>
                        Delete User
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="creatinguser = false" 
                            class="btn-discard"
                            type="button"
                        >
                            Discard
                        </button>

                        <Button
                            :disabled="saving"
                            @click="create"
                            v-if="!user.id"
                            :loading="saving"
                            class="btn-save-primary !bg-indigo-600"
                            label="Create Identity"
                        ></Button>

                        <Button
                            :disabled="saving"
                            @click="update"
                            v-if="user.id"
                            class="btn-save-primary !bg-purple-600"
                            :loading="saving"
                            label="Save Changes"
                        ></Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Delete User Confirmation Dialog Modal -->
    <div class="poppop delete-confirm-modal" v-if="deleteModalVisible">
        <div class="delete-confirm-box" @click.stop>
            <div class="delete-modal-icon-wrap">
                <i class="pi pi-exclamation-triangle text-2xl text-red-500"></i>
            </div>
            <h3 class="delete-modal-title">Delete User Account</h3>
            <p class="delete-modal-subtitle">
                Are you sure you want to permanently delete this user? Their portal credentials and permissions will be removed.
            </p>

            <div v-if="userToDelete" class="delete-user-card-preview">
                <div 
                    class="user-avatar !w-10 !h-10 !text-sm !font-bold" 
                    :style="{ background: getAvatarGradient(userToDelete.name || userToDelete.username) }"
                >
                    {{ getInitials(userToDelete.name || userToDelete.username) }}
                </div>
                <div class="text-left flex-1 min-w-0">
                    <div class="font-bold text-slate-800 text-sm truncate">{{ userToDelete.name || '---' }}</div>
                    <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                        <span class="font-semibold text-indigo-600">@{{ userToDelete.username }}</span>
                        <span v-if="userToDelete.employee_code" class="text-slate-400">#{{ userToDelete.employee_code }}</span>
                        <span v-if="userToDelete.department" class="text-slate-400">&bull; {{ userToDelete.department }}</span>
                    </div>
                </div>
            </div>

            <!-- Warning if trying to delete own account -->
            <div 
                v-if="isDeletingSelf" 
                class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold mt-3 text-left flex items-center gap-2"
            >
                <i class="pi pi-lock text-amber-600"></i>
                <span>You cannot delete your own active logged-in account.</span>
            </div>

            <!-- Warning if not administrator -->
            <div 
                v-if="!isAdmin" 
                class="p-2.5 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs font-semibold mt-3 text-left flex items-center gap-2"
            >
                <i class="pi pi-shield text-red-600"></i>
                <span>Only users with administrator privileges can delete user accounts.</span>
            </div>

            <div class="delete-modal-actions mt-5">
                <button 
                    type="button" 
                    @click="cancelDeleteUser" 
                    class="btn-delete-cancel"
                    :disabled="deletingUser"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    @click="executeDeleteUser" 
                    class="btn-delete-confirm"
                    :disabled="deletingUser || isDeletingSelf || !isAdmin"
                >
                    <i v-if="deletingUser" class="pi pi-spin pi-spinner mr-1.5"></i>
                    <i v-else class="pi pi-trash mr-1.5"></i>
                    {{ deletingUser ? 'Deleting...' : 'Yes, Delete User' }}
                </button>
            </div>
        </div>
    </div>
</template>
<style scoped>
    /* =========================================================
       PAGE WRAPPER & HEADER
       ========================================================= */
    .users-page-container {
        padding: 4px 0 30px 0;
    }

    .page-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .header-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #eef2ff;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .page-main-title {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        font-size: 0.8rem;
        color: #64748b;
        margin: 2px 0 0 0;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-primary-action {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
        color: white !important;
        border: none !important;
        padding: 10px 20px !important;
        border-radius: 10px !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25) !important;
        transition: all 0.2s ease !important;
    }
    .btn-primary-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35) !important;
    }

    .btn-secondary-action {
        background: white;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 9px 16px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .btn-secondary-action:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    /* =========================================================
       KPI METRICS BANNER
       ========================================================= */
    .kpi-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    @media (max-width: 900px) {
        .kpi-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .metric-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.2s ease;
    }
    .metric-card:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
    }

    .metric-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .metric-content {
        display: flex;
        flex-direction: column;
    }

    .metric-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .metric-val {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.15;
        margin-top: 2px;
    }

    /* =========================================================
       FILTER CONTROLS BAR
       ========================================================= */
    .filter-controls-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 14px;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .filter-grid {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .search-input-wrapper {
        position: relative;
        width: 250px;
        flex: 0 0 250px;
        max-width: 280px;
    }

    .search-input-field {
        width: 100%;
        padding: 6px 28px 6px 12px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        font-size: 0.8rem;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        height: 34px;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }
    .search-input-field:focus {
        border-color: #6366f1;
        background: white;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
    }

    .clear-search-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #e2e8f0;
        border: none;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .filter-select-wrapper {
        min-width: 130px;
    }

    .filter-select-field {
        width: 100%;
        padding: 5px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
        outline: none;
        height: 34px;
        box-sizing: border-box;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .filter-select-field:focus {
        border-color: #6366f1;
        background: white;
    }

    .reset-filters-btn {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fecaca;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 0.76rem;
        font-weight: 700;
        height: 34px;
        box-sizing: border-box;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    .reset-filters-btn:hover {
        background: #fee2e2;
    }

    /* Rows Per Page (Last Column in Controls Bar) */
    .rows-per-page-col {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        padding-left: 6px;
    }

    .rows-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }

    .filter-footer-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
        padding-top: 4px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.74rem;
        line-height: 1.2;
    }

    .results-count-text {
        color: #64748b;
    }

    .page-size-select {
        padding: 4px 8px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        font-size: 0.76rem;
        font-weight: 700;
        color: #1e293b;
        outline: none;
        cursor: pointer;
        height: 34px;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }
    .page-size-select:focus {
        border-color: #6366f1;
    }

    /* =========================================================
       MODERN USERS TABLE
       ========================================================= */
    .table-outer-wrapper {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    }

    .modern-users-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .modern-users-table thead tr {
        background: #1e293b;
        border-bottom: 2px solid #0f172a;
    }

    .modern-users-table th {
        padding: 13px 18px;
        font-size: 0.72rem;
        font-weight: 800;
        color: #f1f5f9;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .user-table-row {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.15s ease;
    }
    .user-table-row:nth-child(even) {
        background: #f8fafc;
    }
    .user-table-row:hover {
        background: #f0f4fa;
    }

    .modern-users-table td {
        padding: 13px 18px;
        font-size: 0.85rem;
        vertical-align: middle;
    }

    /* User Profile Cell */
    .user-profile-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        color: white;
        font-weight: 800;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .user-names-box {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .user-display-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.88rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-username-tag {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6366f1;
        background: #eef2ff;
        padding: 1px 6px;
        border-radius: 5px;
    }

    .user-email-text {
        font-size: 0.72rem;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 140px;
    }

    /* Code Mono Badge */
    .code-mono-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.76rem;
        font-weight: 700;
        color: #0369a1;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        padding: 3px 8px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    .code-mono-badge:hover {
        background: #e0f2fe;
        border-color: #7dd3fc;
    }

    /* Dept & Location */
    .dept-location-cell {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .dept-text {
        font-size: 0.82rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .location-subtext {
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 600;
    }

    /* Role Badges */
    .role-badge {
        font-size: 0.72rem;
        font-weight: 800;
        padding: 3px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .admin-badge {
        background: #f5f3ff;
        color: #7c3aed;
        border: 1px solid #ddd6fe;
    }

    .manager-role-badge {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .basic-badge,
    .standard-badge {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .viewer-role-badge {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .entry-role-badge {
        background: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
    }

    .hrhead-role-badge {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    /* Position Pills */
    .position-pill {
        font-size: 0.72rem;
        font-weight: 800;
        padding: 3px 10px;
        border-radius: 6px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .pos-manager {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #6ee7b7;
    }

    .pos-hrhead {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .pos-employee,
    .pos-viewer {
        background: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }

    .pos-entry {
        background: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
    }

    /* Action Buttons */
    .action-buttons-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .action-edit-btn {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #4f46e5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .action-edit-btn:hover {
        background: #4f46e5;
        color: white;
        border-color: #4f46e5;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    }

    .action-delete-btn {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        border: 1px solid #fee2e2;
        background: #fef2f2;
        color: #ef4444;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .action-delete-btn:hover {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
    }

    /* Empty & Loading States */
    .empty-state-cell {
        padding: 45px 20px !important;
        text-align: center;
    }

    .empty-state-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .btn-clear-empty {
        background: #eef2ff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
    }

    /* Pagination */
    .pagination-footer-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        background: #f8fafc;
        border-top: 1px solid #edf2f7;
    }

    .pagination-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .page-btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .page-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .page-btn:not(:disabled):hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }

    .page-num-btn {
        min-width: 30px;
        height: 30px;
        padding: 0 6px;
        border-radius: 6px;
        border: 1px solid transparent;
        background: transparent;
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .page-num-btn.active {
        background: #4f46e5;
        color: white;
        border-color: #4f46e5;
    }
    .page-num-btn:not(.active):hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* =========================================================
       MODAL STYLING (POPPOP)
       ========================================================= */
    .poppop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 500;
        background-color: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .poppopin {
        background-color: white;
        width: 100%;
        max-width: 740px;
        max-height: 92vh;
        overflow: hidden;
        border-radius: 16px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }

    .popheader-premium {
        background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
        padding: 22px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .pop-title {
        color: white;
        font-size: 1.2rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .pop-subtext {
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin: 2px 0 0 0;
    }

    .poppopclose-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .poppopclose-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
    }

    /* Edit Profile Ribbon */
    .edit-profile-ribbon {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 28px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .user-avatar-large {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        color: white;
        font-weight: 800;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    }

    .poplist {
        padding: 24px 28px;
        overflow-y: auto;
        flex: 1;
        background: white;
    }

    /* Master Employee Linker */
    .employee-linker-box {
        background: #eef2ff;
        border: 1.5px dashed #c7d2fe;
        border-radius: 12px;
        padding: 12px 16px;
    }

    .smart-search-wrapper {
        position: relative;
    }

    .clear-smart-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #e2e8f0;
        border: none;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .clear-smart-btn:hover {
        background: #cbd5e1;
        color: #1e293b;
    }

    .smart-dropdown-panel {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        z-index: 50;
        overflow: hidden;
        animation: scaleUp 0.15s ease;
    }

    .smart-dropdown-item {
        cursor: pointer;
    }

    .form-section-group {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 20px;
    }

    .section-group-title {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .title-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .field-label {
        font-size: 0.76rem;
        font-weight: 700;
        color: #475569;
        display: block;
        margin-bottom: 4px;
    }

    .premium-input {
        width: 100%;
        padding: 10px 14px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        transition: all 0.2s ease;
        outline: none;
    }
    .premium-input:focus {
        background: white;
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.08);
    }

    .password-input-wrap {
        position: relative;
    }

    .password-eye-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 4px;
    }

    /* Privilege Cards */
    .privilege-toggle-card {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .privilege-toggle-card:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }

    .active-privilege-admin {
        border-color: #6366f1 !important;
        background: #f5f3ff !important;
    }

    .active-privilege-manager {
        border-color: #10b981 !important;
        background: #ecfdf5 !important;
    }

    .privilege-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .privilege-title {
        font-size: 0.82rem;
        font-weight: 800;
        color: #0f172a;
        display: block;
    }

    .privilege-desc {
        font-size: 0.68rem;
        color: #64748b;
        font-weight: 500;
        display: block;
    }

    /* Batch Permission Select Button */
    .batch-select-btn {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .batch-select-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* Module Tabs Header */
    .module-tabs-header {
        display: flex;
        gap: 6px;
        border-bottom: 1.5px solid #e2e8f0;
        padding-bottom: 6px;
    }

    .module-tab-btn {
        padding: 6px 14px;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        font-size: 0.82rem;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: -7px;
    }
    .module-tab-btn.active {
        color: #4f46e5;
        border-bottom-color: #4f46e5;
    }

    /* Permission Cards */
    .permission-card-enhanced {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: white;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .permission-card-enhanced:hover {
        border-color: #6366f1;
        background: #f8fafc;
    }
    .permission-card-enhanced.selected {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.07);
    }

    .perm-checkbox {
        width: 18px;
        height: 18px;
        min-width: 18px;
        border-radius: 5px;
        border: 2px solid #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        transition: all 0.2s ease;
    }
    .selected .perm-checkbox {
        background: #6366f1;
        border-color: #6366f1;
        color: white;
    }
    .perm-checkbox .pi {
        font-size: 0.65rem;
        font-weight: 800;
        color: white;
    }

    .perm-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        line-height: 1.2;
    }
    .selected .perm-label {
        color: #312e81;
    }

    /* Modal Footer Actions */
    .modal-footer-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid #f1f5f9;
    }

    .btn-modal-delete {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 9px 15px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    .btn-modal-delete:hover {
        background: #fee2e2;
        border-color: #f87171;
        color: #b91c1c;
    }

    .btn-discard {
        padding: 10px 20px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: white;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-discard:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .btn-save-primary {
        padding: 10px 28px !important;
        border-radius: 10px !important;
        border: none !important;
        color: white !important;
        font-size: 0.8rem !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25) !important;
        transition: all 0.2s ease !important;
    }
    .btn-save-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35) !important;
    }

    /* =========================================================
       DELETE CONFIRMATION DIALOG MODAL
       ========================================================= */
    .delete-confirm-box {
        background: white;
        width: 100%;
        max-width: 440px;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        text-align: center;
        animation: scaleUp 0.2s ease;
        margin: auto;
    }

    .delete-modal-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #fee2e2;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px auto;
    }

    .delete-modal-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 6px 0;
    }

    .delete-modal-subtitle {
        font-size: 0.82rem;
        color: #64748b;
        line-height: 1.45;
        margin: 0 0 16px 0;
    }

    .delete-user-card-preview {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .delete-modal-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-delete-cancel {
        padding: 9px 16px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 9px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-delete-cancel:hover {
        background: #f1f5f9;
        color: #334155;
    }

    .btn-delete-confirm {
        padding: 9px 18px;
        border: none;
        background: #dc2626;
        color: white;
        border-radius: 9px;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
    }
    .btn-delete-confirm:hover:not(:disabled) {
        background: #b91c1c;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);
    }
    .btn-delete-confirm:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* =========================================================
       DARK MODE
       ========================================================= */
    :global(body.dark-mode) .metric-card,
    :global(body.dark-mode) .filter-controls-card,
    :global(body.dark-mode) .table-outer-wrapper,
    :global(body.dark-mode) .poppopin,
    :global(body.dark-mode) .poplist,
    :global(body.dark-mode) .delete-confirm-box {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }

    :global(body.dark-mode) .page-main-title,
    :global(body.dark-mode) .user-display-name,
    :global(body.dark-mode) .privilege-title,
    :global(body.dark-mode) .delete-modal-title {
        color: #f1f5f9 !important;
    }

    :global(body.dark-mode) .delete-modal-subtitle {
        color: #94a3b8 !important;
    }

    :global(body.dark-mode) .delete-user-card-preview {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }

    :global(body.dark-mode) .btn-delete-cancel {
        background-color: #334155 !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }

    :global(body.dark-mode) .action-delete-btn,
    :global(body.dark-mode) .btn-modal-delete {
        background-color: #451a1a !important;
        border-color: #7f1d1d !important;
        color: #f87171 !important;
    }

    :global(body.dark-mode) .modern-users-table thead tr,
    :global(body.dark-mode) .pagination-footer-row,
    :global(body.dark-mode) .edit-profile-ribbon {
        background-color: #1a2332 !important;
        border-color: #334155 !important;
    }

    :global(body.dark-mode) .user-table-row {
        border-color: #334155 !important;
    }
    :global(body.dark-mode) .user-table-row:hover {
        background-color: #243044 !important;
    }

    :global(body.dark-mode) .search-input-field,
    :global(body.dark-mode) .filter-select-field,
    :global(body.dark-mode) .premium-input,
    :global(body.dark-mode) .page-size-select,
    :global(body.dark-mode) .btn-secondary-action {
        background-color: #0f172a !important;
        border-color: #475569 !important;
        color: #f1f5f9 !important;
    }

    :global(body.dark-mode) .permission-card-enhanced,
    :global(body.dark-mode) .privilege-toggle-card {
        background-color: #0f172a !important;
        border-color: #475569 !important;
    }

    :global(body.dark-mode) .permission-card-enhanced.selected {
        background-color: rgba(99, 102, 241, 0.2) !important;
        border-color: #818cf8 !important;
    }

    :global(body.dark-mode) .perm-label {
        color: #cbd5e1 !important;
    }
    :global(body.dark-mode) .selected .perm-label {
        color: #c7d2fe !important;
    }

    :global(body.dark-mode) .action-edit-btn,
    :global(body.dark-mode) .page-btn,
    :global(body.dark-mode) .btn-discard {
        background-color: #1e293b !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }

    :global(body.dark-mode) .smart-dropdown-panel {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    :global(body.dark-mode) .dropdown-header-bar {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
    :global(body.dark-mode) .smart-dropdown-item:hover {
        background: rgba(99, 102, 241, 0.15) !important;
    }
</style>