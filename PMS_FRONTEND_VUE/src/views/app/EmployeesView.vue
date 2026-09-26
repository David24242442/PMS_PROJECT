<script setup>
    import { ref, reactive, computed, onMounted, watch } from 'vue'
    import { log, calculateAge, toastt } from '@/helpers/essential'
    import { mstatus, bankaccounttype, relations, depts, regions, branchs, qualtypes, banks, familyrelation, countries, conttypes, idtypes, findidtypes, findcompany, genders, companies, findregion, findconttypes, findgender, findmarital, findbranch, finddept, findcountry, statuses, findstatus } from '@/data/masterdata'
    
    import { useRouter } from 'vue-router'
    import axios from '@/helpers/pms_axios'
    import { useUsersStore } from '@/stores/user'

    const userstore = useUsersStore()
    const router = useRouter()
    const { loguser } = userstore

    const emps = ref([])
    let searchdata = reactive({
        employeeinfo: '',
        createdfrom: '',
        createdto: '',
        filters: []
    })
    let loaded = ref(false)
    let loading = ref(true)

    // ── Status Filter Tab ──
    const activeStatusTab = ref('')
    const onStatusTabClick = (statusId) => {
        activeStatusTab.value = statusId
        columnFilters.status = statusId
    }

    // Keep activeStatusTab in sync if columnFilters.status is changed directly
    watch(() => columnFilters.status, (newVal) => {
        activeStatusTab.value = newVal || ''
    })

    // ── Sorting ──
    const sortBy = ref('newest')

    const sortOptions = [
        { value: 'newest', label: 'Newest First (Created Date)' },
        { value: 'oldest', label: 'Oldest First (Created Date)' },
        { value: 'name_asc', label: 'First Name (A to Z)' },
        { value: 'name_desc', label: 'First Name (Z to A)' },
        { value: 'surname_asc', label: 'Surname (A to Z)' },
        { value: 'surname_desc', label: 'Surname (Z to A)' },
        { value: 'empid_asc', label: 'Employee ID (Ascending)' },
        { value: 'empid_desc', label: 'Employee ID (Descending)' },
        { value: 'email_asc', label: 'Email (A to Z)' },
        { value: 'email_desc', label: 'Email (Z to A)' },
        { value: 'status_asc', label: 'Status (Ascending)' },
        { value: 'status_desc', label: 'Status (Descending)' },
        { value: 'joining_desc', label: 'Date of Joining (Newest First)' },
        { value: 'joining_asc', label: 'Date of Joining (Oldest First)' },
    ]

    const onSortChange = () => {
        current_page.value = 1
        fetchData()
    }

    const toggleSort = (column) => {
        switch (column) {
            case 'employeeid':
                sortBy.value = sortBy.value === 'empid_asc' ? 'empid_desc' : 'empid_asc'
                break
            case 'firstname':
                sortBy.value = sortBy.value === 'name_asc' ? 'name_desc' : 'name_asc'
                break
            case 'surname':
                sortBy.value = sortBy.value === 'surname_asc' ? 'surname_desc' : 'surname_asc'
                break
            case 'email':
                sortBy.value = sortBy.value === 'email_asc' ? 'email_desc' : 'email_asc'
                break
            case 'status':
                sortBy.value = sortBy.value === 'status_asc' ? 'status_desc' : 'status_asc'
                break
            case 'created_at':
                sortBy.value = sortBy.value === 'newest' ? 'oldest' : 'newest'
                break
            case 'joiningdate':
                sortBy.value = sortBy.value === 'joining_desc' ? 'joining_asc' : 'joining_desc'
                break
        }
        current_page.value = 1
        fetchData()
    }

    const getSortIcon = (column) => {
        switch (column) {
            case 'employeeid':
                if (sortBy.value === 'empid_asc') return 'pi pi-sort-amount-up-alt text-indigo-600'
                if (sortBy.value === 'empid_desc') return 'pi pi-sort-amount-down text-indigo-600'
                return 'pi pi-sort-alt text-slate-400 group-hover:text-slate-600'
            case 'firstname':
                if (sortBy.value === 'name_asc') return 'pi pi-sort-alpha-down text-indigo-600'
                if (sortBy.value === 'name_desc') return 'pi pi-sort-alpha-up-alt text-indigo-600'
                return 'pi pi-sort-alt text-slate-400 group-hover:text-slate-600'
            case 'surname':
                if (sortBy.value === 'surname_asc') return 'pi pi-sort-alpha-down text-indigo-600'
                if (sortBy.value === 'surname_desc') return 'pi pi-sort-alpha-up-alt text-indigo-600'
                return 'pi pi-sort-alt text-slate-400 group-hover:text-slate-600'
            case 'email':
                if (sortBy.value === 'email_asc') return 'pi pi-sort-alpha-down text-indigo-600'
                if (sortBy.value === 'email_desc') return 'pi pi-sort-alpha-up-alt text-indigo-600'
                return 'pi pi-sort-alt text-slate-400 group-hover:text-slate-600'
            case 'status':
                if (sortBy.value === 'status_asc') return 'pi pi-sort-amount-up-alt text-indigo-600'
                if (sortBy.value === 'status_desc') return 'pi pi-sort-amount-down text-indigo-600'
                return 'pi pi-sort-alt text-slate-400 group-hover:text-slate-600'
            default:
                return 'pi pi-sort-alt text-slate-400 group-hover:text-slate-600'
        }
    }

    // ── Dynamic Column Picker with LocalStorage Persistence ──
    const showColumnPicker = ref(false)
    const STORAGE_COLUMNS_KEY = 'hr_employees_visible_columns_v3'
    const DEFAULT_COLUMN_KEYS = ['employeeid', 'firstname', 'surname', 'email', 'mobileno', 'status', '_creator']

    // All available columns
    const allColumns = [
        // Standard / Default Columns (toggleable - user can uncheck or replace)
        { key: 'employeeid', label: 'Employee ID', group: 'Standard Columns' },
        { key: 'firstname', label: 'First Name', group: 'Standard Columns' },
        { key: 'surname', label: 'Surname', group: 'Standard Columns' },
        { key: 'email', label: 'Email', group: 'Standard Columns' },
        { key: 'mobileno', label: 'Mobile No', group: 'Standard Columns' },
        { key: 'status', label: 'Status', group: 'Standard Columns', formatter: (v) => findstatus(v) },
        { key: '_creator', label: 'Created By', group: 'Standard Columns' },
        // Position Details
        { key: 'company', label: 'Joining Company', group: 'Position Details', formatter: (v) => findcompany(v) },
        { key: 'contracttype', label: 'Contract Category', group: 'Position Details', formatter: (v) => findconttypes(v) },
        { key: 'joining_branch_id', label: 'Joining Location', group: 'Position Details', formatter: (v) => findbranch(v) },
        { key: 'joining_dept_id', label: 'Joining Department', group: 'Position Details', formatter: (v) => finddept(v) },
        { key: 'joiningposition', label: 'Joining Position', group: 'Position Details' },
        { key: 'joiningdate', label: 'Date of Joining', group: 'Position Details' },
        // Personal Info
        { key: 'middlename', label: 'Middle Name', group: 'Personal Info' },
        { key: 'citizenship', label: 'Citizenship', group: 'Personal Info', formatter: (v) => findcountry(v) },
        { key: 'ghcardno', label: 'GH Card / ID No', group: 'Personal Info' },
        { key: 'daddress', label: 'Residential Address', group: 'Personal Info' },
        { key: 'hdaddress', label: 'Permanent Address', group: 'Personal Info' },
        { key: 'hometown', label: 'Hometown', group: 'Personal Info' },
        { key: 'altnumber', label: 'Alternate Phone', group: 'Personal Info' },
        { key: 'gender', label: 'Gender', group: 'Personal Info', formatter: (v) => findgender(v) },
        { key: 'dob', label: 'Date of Birth', group: 'Personal Info' },
        { key: 'socialsecurityno', label: 'Social Security No', group: 'Personal Info' },
        { key: 'maritalstatus', label: 'Marital Status', group: 'Personal Info', formatter: (v) => findmarital(v) },
        { key: 'fathersname', label: "Father's Name", group: 'Personal Info' },
        { key: 'mothersname', label: "Mother's Name", group: 'Personal Info' },
        { key: 'anyotherinfo', label: 'Other Information', group: 'Personal Info' },
    ]

    // Read initial columns from localStorage or default
    const loadSavedColumns = () => {
        try {
            const raw = localStorage.getItem(STORAGE_COLUMNS_KEY)
            if (raw) {
                const parsed = JSON.parse(raw)
                if (Array.isArray(parsed) && parsed.length > 0) {
                    const valid = parsed.filter(k => allColumns.some(c => c.key === k))
                    if (valid.length > 0) return valid
                }
            }
        } catch (e) {
            console.error('Error loading visible columns from localStorage:', e)
        }
        return [...DEFAULT_COLUMN_KEYS]
    }

    const visibleColumns = ref(loadSavedColumns())
    const pendingSelection = ref([])

    const isColVisible = (key) => visibleColumns.value.includes(key)

    // Dynamic columns are any visible columns beyond the standard 7
    const dynamicColumns = computed(() => {
        return visibleColumns.value.filter(k => !DEFAULT_COLUMN_KEYS.includes(k))
    })

    const openColumnPicker = () => {
        pendingSelection.value = [...visibleColumns.value]
        showColumnPicker.value = true
    }

    const togglePendingCol = (key) => {
        if (pendingSelection.value.includes(key)) {
            if (pendingSelection.value.length <= 1) {
                toastt('At least one column must remain visible', 'warning')
                return
            }
            pendingSelection.value = pendingSelection.value.filter(k => k !== key)
        } else {
            pendingSelection.value.push(key)
        }
    }

    const applyColumns = () => {
        if (!pendingSelection.value.length) {
            toastt('Please select at least one column', 'warning')
            return
        }
        visibleColumns.value = [...pendingSelection.value]
        try {
            localStorage.setItem(STORAGE_COLUMNS_KEY, JSON.stringify(visibleColumns.value))
        } catch (e) {
            console.error('Error saving columns to localStorage:', e)
        }
        showColumnPicker.value = false
        toastt('Column preferences saved', 'success')
    }

    const resetDefaultColumns = () => {
        pendingSelection.value = [...DEFAULT_COLUMN_KEYS]
    }

    const removeColumn = (key) => {
        if (visibleColumns.value.length <= 1) {
            toastt('At least one column must remain visible', 'warning')
            return
        }
        visibleColumns.value = visibleColumns.value.filter(k => k !== key)
        try {
            localStorage.setItem(STORAGE_COLUMNS_KEY, JSON.stringify(visibleColumns.value))
        } catch (e) {
            console.error('Error saving columns to localStorage:', e)
        }
    }

    const groupedAllColumns = computed(() => {
        const groups = {}
        allColumns.forEach(col => {
            if (!groups[col.group]) groups[col.group] = []
            groups[col.group].push(col)
        })
        return groups
    })

    const getColumnDef = (key) => allColumns.find(c => c.key === key)

    const getExtraValue = (emp, key) => {
        const col = getColumnDef(key)
        const val = emp[key]
        if (val == null || val === '') return '—'
        return col?.formatter ? col.formatter(val) : val
    }

    // ── Column Filters (server-side with debounce) ──
    const columnFilters = reactive({
        employeeid: '',
        firstname: '',
        surname: '',
        email: '',
        mobileno: '',
        status: '',
        creator: ''
    })

    const hasColumnFilters = computed(() => {
        return Object.values(columnFilters).some(v => v !== '' && v !== null)
    })

    const clearColumnFilters = () => {
        Object.keys(columnFilters).forEach(k => columnFilters[k] = '')
        activeStatusTab.value = ''
    }

    // Debounce timer for column filters
    let columnFilterTimer = null
    watch(() => ({ ...columnFilters }), () => {
        clearTimeout(columnFilterTimer)
        columnFilterTimer = setTimeout(() => {
            const serverFilters = []
            const filterMap = {
                employeeid: 'employeeid',
                firstname: 'firstname',
                surname: 'surname',
                email: 'email',
                mobileno: 'mobileno',
                status: 'status',
                creator: 'creator'
            }
            for (const [key, attr] of Object.entries(filterMap)) {
                if (columnFilters[key] !== '' && columnFilters[key] !== null) {
                    serverFilters.push({
                        cond: 'a',
                        attr: attr,
                        op: key === 'status' ? 'is' : 'cont',
                        val: columnFilters[key]
                    })
                }
            }
            searchdata.columnFilters = serverFilters
            current_page.value = 1
            fetchData()
        }, 400)
    }, { deep: true })

    // Quick global search with debounced typing
    let globalSearchTimer = null
    const onGlobalSearchInput = () => {
        clearTimeout(globalSearchTimer)
        globalSearchTimer = setTimeout(() => {
            current_page.value = 1
            fetchData()
        }, 450)
    }

    const clearGlobalSearch = () => {
        searchdata.employeeinfo = ''
        current_page.value = 1
        fetchData()
    }

    const filteredEmps = computed(() => emps.value)

    let current_page = ref(1)
    let last_page = ref(1)
    let isnext = ref(false)
    let isprev = ref(false)
    let per_page = ref(50)
    let totalcount = ref(0)

    onMounted(() => {
        fetchData()
    })

    const viewemp = (id) => {
        router.push({ name: 'employee', params: { empid: id } })
    }

    const colorinvalid = (elem) => {
        elem.style = 'border:2px solid red'
        if (elem.nextElementSibling) elem.nextElementSibling.innerText = elem.validationMessage
        elem.addEventListener('input', function(e) {
            elem.style = 'revert'
            if (elem.nextElementSibling) elem.nextElementSibling.innerText = ''
        }, { once: true })
    }

    const filter = () => {
        const reqinput = document.querySelectorAll("#advfilter :invalid");
        reqinput.forEach((elem) => {
            colorinvalid(elem)
        })

        if (reqinput.length) {
            toastt('Highlighted fields cannot be empty', 'error')
            return false
        }

        current_page.value = 1
        last_page.value = 1
        fetchData()
    }

    const resetDateFilters = () => {
        searchdata.createdfrom = ''
        searchdata.createdto = ''
        current_page.value = 1
        fetchData()
    }

    const selectchange = () => {
        current_page.value = 1
        fetchData()
    }

    const fetchData = () => {
        loading.value = true

        const payload = { ...searchdata }
        const allFilters = [
            ...(searchdata.filters || []),
            ...(searchdata.columnFilters || [])
        ]
        payload.filters = allFilters
        payload.sort_by = sortBy.value

        axios.post(`fetchemployees?page=${current_page.value}&per_page=${per_page.value}`, payload)
            .then(res => {
                const data = res.data
                emps.value = data.data || []
                current_page.value = data.current_page || 1
                isnext.value = !!data.next_page_url
                isprev.value = !!data.prev_page_url
                last_page.value = data.last_page || 1
                totalcount.value = data.total || 0

                loaded.value = true
                loading.value = false
            })
            .catch((error) => {
                console.error(error)
                loading.value = false
            })
    }

    const nextpage = () => {
        if (isnext.value && !loading.value) {
            current_page.value++
            fetchData()
        }
    }

    const prevpage = () => {
        if (isprev.value && !loading.value) {
            current_page.value--
            fetchData()
        }
    }

    const goToPage = (page) => {
        if (page === '...' || page === current_page.value || page < 1 || (last_page.value && page > last_page.value)) return
        current_page.value = page
        fetchData()
    }

    // Modern pagination pages list
    const visiblePages = computed(() => {
        const total = last_page.value || 1
        const current = current_page.value || 1
        const pages = []

        if (total <= 7) {
            for (let i = 1; i <= total; i++) pages.push(i)
        } else {
            pages.push(1)
            if (current > 3) pages.push('...')

            const start = Math.max(2, current - 1)
            const end = Math.min(total - 1, current + 1)

            for (let i = start; i <= end; i++) {
                if (!pages.includes(i)) pages.push(i)
            }

            if (current < total - 2) pages.push('...')
            if (!pages.includes(total)) pages.push(total)
        }
        return pages
    })

    /* Directives */
    const vUppercase = {
        mounted(el) {
            el.addEventListener('input', updateValue)
        },
        unmounted(el) {
            el.removeEventListener('input', updateValue)
        }
    }

    const vNumber = {
        mounted(el) {
            el.addEventListener('input', updateValueNumber)
        },
        unmounted(el) {
            el.removeEventListener('input', updateValueNumber)
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

    const updateValueNumber = (el) => {
        const input = el.target
        const sourceValue = input.value
        const newValue = sourceValue.replace(/([^+0-9]+)/gi, '')
        if (sourceValue !== newValue) {
            input.value = newValue
            input.dispatchEvent(new Event('input', { bubbles: true }))
        }
    }

    const fil = {
        firstname: { type: 'text', inp: 'inp' },
        surname: { type: 'text', inp: 'inp' },
        creator: { type: 'text', inp: 'inp' },
        joiningposition: { type: 'text', inp: 'inp' },
        dob: { type: 'date', inp: 'inp' },
        joiningdate: { type: 'date', inp: 'inp' },
        joining_branch_id: { type: 'text', inp: 'sel', list: branchs, id: 'id' },
        company: { type: 'text', inp: 'sel', list: companies, id: 'id' },
        contracttype: { type: 'text', inp: 'sel', list: conttypes, id: 'code' },
        gender: { type: 'text', inp: 'sel', list: genders, id: 'id' },
        citizenship: { type: 'text', inp: 'sel', list: countries, id: 'code' },
        joining_dept_id: { type: 'text', inp: 'sel', list: depts, id: 'id' },
    }

    const showAdvFilters = ref(false)
    const toggleAdvFilters = () => {
        showAdvFilters.value = !showAdvFilters.value
        if (showAdvFilters.value && !searchdata.filters.length) {
            addfilter()
        }
    }

    const addfilter = () => {
        showAdvFilters.value = true
        searchdata.filters.push({
            'cond': 'a',
            'attr': 'firstname',
            'op': 'cont',
            'val': '',
        })
    }

    const removefilter = (index) => {
        searchdata.filters.splice(index, 1)
        if (!searchdata.filters.length) {
            current_page.value = 1
            fetchData()
        }
    }

    const clearAllAdvFilters = () => {
        searchdata.filters = []
        showAdvFilters.value = false
        current_page.value = 1
        fetchData()
    }

    const attrchange = (index) => {
        searchdata.filters[index].val = null
    }

    // ── CSV Export ──
    const exportToCsv = (data, filename = 'export.csv') => {
        try {
            if (!Array.isArray(data) || !data.length) {
                throw new Error('Input must be a non-empty array')
            }
            const headers = Object.keys(data[0])
            const csvRows = [
                headers.join(','),
                ...data.map(row => {
                    return headers.map(header => {
                        const cell = row[header]?.toString() ?? ''
                        if (cell.includes(',') || cell.includes('\n') || cell.includes('"')) {
                            return `"${cell.replace(/"/g, '""')}"`
                        }
                        return cell
                    }).join(',')
                })
            ]
            const csvContent = csvRows.join('\n')
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' })
            
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, filename)
            } else {
                const link = document.createElement('a')
                if (link.download !== undefined) {
                    const url = URL.createObjectURL(blob)
                    link.setAttribute('href', url)
                    link.setAttribute('download', filename)
                    link.style.visibility = 'hidden'
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
                    URL.revokeObjectURL(url)
                }
            }
            return true
        } catch (error) {
            console.error('Error exporting CSV:', error)
            throw error
        }
    }

    let exportingChecklist = ref(false)
    const exportChecklist = () => {
        exportingChecklist.value = true
        axios.post(`fetchemployeesforexport`, searchdata)
            .then(res => {
                const data = res.data
                const finaldata = data.map((i) => {
                    const isoptional = [4, 5, 6, 7, 8, 9, 10].includes(i.company)
                    const isplcnotrequired = ['DRIVER', 'SECURITY', 'SECURITY OFFICER', 'SHOP MANAGER', 'SALES MANAGER', 'SALES EXECUTIVE'].includes(i.joiningposition)
                    const guarantorrequired = ['AUDITOR', 'CASHIER'].includes(i.joiningposition) || ![4, 5, 6, 7, 8, 9, 10].includes(i.company)

                    return {
                        'Staff ID': i.employeeid,
                        'Fullname': i.firstname + ' ' + i.surname,
                        'Status': findstatus(i.status),
                        'Joining Company': findcompany(i.company),
                        'Joining Position': i.joiningposition,
                        'Profile Form': '✓',
                        '2 Passport size pictures': i.ghcard ? '✓' : 'X',
                        'Application Letter': i.appletters?.length ? '✓' : 'X',
                        'Appointment Letter': i.appointmentletters?.length ? '✓' : 'X',
                        'Probation Confirmation': i.probationconfs?.length ? '✓' : 'X',
                        'CV': i.cv?.length ? '✓' : 'X',
                        'Petra Trust': isoptional ? 'NA' : (i.petratrust?.length ? '✓' : 'X'),
                        'NIC 2 Coloured Copies - GH CARD': i.ghcard ? '✓' : 'X',
                        'Guarantor Forms': guarantorrequired ? (i.irrguarantor ? '✓' : 'X') : 'NA',
                        'Bank / Social Security Fund': isoptional ? 'NA' : (i.banksocial ? '✓' : 'X'),
                        'NHIS Card': i.nhis?.length ? '✓' : 'X',
                        'Birth Certificate': isoptional ? 'NA' : (i.birthcert?.length ? '✓' : 'X'),
                        'Police Clearance Form': isplcnotrequired ? 'NA' : (i.pclearanceform?.length ? '✓' : 'X'),
                        'SSNIT': i.ssnit?.length ? '✓' : 'X'
                    }
                })
                exportToCsv(finaldata, 'Checklist Export.csv')
                exportingChecklist.value = false
            })
            .catch((error) => {
                console.error(error)
                exportingChecklist.value = false
            })
    }

    let exportingDump = ref(false)
    const exportDump = () => {
        exportingDump.value = true
        const payload = { ...searchdata, sort_by: sortBy.value }
        axios.post(`fetchemployeesdumpforexport`, payload)
            .then(res => {
                const data = res.data
                const finaldata = data.map((i) => ({
                    'Staff ID': i.employeeid,
                    'Fullname': i.firstname + ' ' + (i.middlename ? i.middlename + ' ' : '') + i.surname,
                    'Citizenship': findcountry(i.citizenship),
                    'ID Type': i.citizenship == 'GH' ? 'GHANA CARD' : findidtypes(i.idtype),
                    'GH Card / ID CARD / PP No': i.ghcardno,
                    'Residential Ghana DA': i.daddress,
                    'Permanent Ghana DA': i.hdaddress,
                    'Status': findstatus(i.status),
                    'Email': i.email,
                    'Mobile No': i.mobileno,
                    'Alternate Phone': i.altnumber,
                    'Date of Birth': i.dob,
                    'Gender': findgender(i.gender),
                    'Social Security Number': i.socialsecurityno,
                    'Marital Status': findmarital(i.maritalstatus),
                    'Father Name': i.fathersname,
                    'Mother Name': i.mothersname,
                    'Joining Position': i.joiningposition,
                    'Joining Location': findbranch(i.joining_branch_id),
                    'Joining Company': findcompany(i.company),
                    'Contract Category': findconttypes(i.contracttype),
                    'Joining Department': finddept(i.joining_dept_id),
                    'Date of Joining': i.joiningdate,
                    'OTHER INFORMATION': i.anyotherinfo
                }))
                exportToCsv(finaldata, 'Employees_Export.csv')
                exportingDump.value = false
            })
            .catch((error) => {
                console.error(error)
                exportingDump.value = false
            })
    }

    const changestatus = (id, event) => {
        const selectedValue = event.target.value
        const selectedText = event.target.options[event.target.selectedIndex].text

        const data = {
            empid: id,
            status: selectedValue,
            statusname: selectedText
        }

        axios.post('updateempstatus', data)
            .then(res => {
                toastt('Status successfully updated')
            })
            .catch((error) => {
                toastt('Error updating status. Please try again', 'error')
                console.error(error)
            })
    }

    // ── Helper: Avatar Initials & Pastel Color ──
    const getAvatarInitials = (emp) => {
        const first = emp.firstname ? emp.firstname.trim().charAt(0) : ''
        const last = emp.surname ? emp.surname.trim().charAt(0) : ''
        return (first + last).toUpperCase() || 'EM'
    }

    const avatarColors = [
        'bg-indigo-50 text-indigo-700 border-indigo-200/80',
        'bg-sky-50 text-sky-700 border-sky-200/80',
        'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'bg-amber-50 text-amber-700 border-amber-200/80',
        'bg-purple-50 text-purple-700 border-purple-200/80',
        'bg-rose-50 text-rose-700 border-rose-200/80',
        'bg-teal-50 text-teal-700 border-teal-200/80',
        'bg-blue-50 text-blue-700 border-blue-200/80',
    ]

    const getAvatarClass = (emp) => {
        const str = (emp.firstname || '') + (emp.surname || '') + (emp.employeeid || '')
        let hash = 0
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash)
        }
        return avatarColors[Math.abs(hash) % avatarColors.length]
    }

    // ── Helper: Status Pill Styles ──
    const getStatusStyle = (statusId) => {
        switch (Number(statusId)) {
            case 1:
                return {
                    pill: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    dot: 'bg-emerald-500',
                    name: 'Active'
                }
            case 2:
                return {
                    pill: 'bg-amber-50 text-amber-700 border-amber-200',
                    dot: 'bg-amber-500',
                    name: 'Resigned'
                }
            case 3:
                return {
                    pill: 'bg-orange-50 text-orange-700 border-orange-200',
                    dot: 'bg-orange-500',
                    name: 'Absconded'
                }
            case 4:
                return {
                    pill: 'bg-rose-50 text-rose-700 border-rose-200',
                    dot: 'bg-rose-500',
                    name: 'Terminated'
                }
            case 5:
                return {
                    pill: 'bg-purple-50 text-purple-700 border-purple-200',
                    dot: 'bg-purple-500',
                    name: 'Dismissed'
                }
            case 6:
                return {
                    pill: 'bg-slate-100 text-slate-700 border-slate-200',
                    dot: 'bg-slate-400',
                    name: 'Deceased'
                }
            default:
                return {
                    pill: 'bg-slate-50 text-slate-700 border-slate-200',
                    dot: 'bg-slate-400',
                    name: findstatus(statusId) || 'Unknown'
                }
        }
    }
</script>

<template>
    <div class="employees-page-wrapper">
        <!-- ── Modern Header Section ── -->
        <div class="header-section">
            <div class="header-left">
                <div class="title-row">
                    <h1 class="page-title">Employees</h1>
                    <span class="count-badge" v-if="loaded">
                        {{ totalcount.toLocaleString() }} total
                    </span>
                    <span class="count-badge-loading" v-else>
                        <i class="pi pi-spin pi-spinner text-xs"></i>
                    </span>
                </div>
                <p class="page-subtitle">Search, filter, and manage staff records and onboarding profiles</p>
            </div>

            <div class="header-actions">
                <!-- Customize Columns Button -->
                <div class="relative inline-block">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click.stop="openColumnPicker()"
                        title="Customize table columns"
                    >
                        <i class="pi pi-sliders-h text-xs"></i>
                        <span>Columns</span>
                        <span class="action-pill">
                            {{ visibleColumns.length }}
                        </span>
                    </button>

                    <!-- Column Picker Dropdown Modal -->
                    <div v-if="showColumnPicker" class="col-picker-backdrop" @click="showColumnPicker = false"></div>
                    <div v-if="showColumnPicker" class="col-picker-dropdown" @click.stop>
                        <div class="col-picker-header">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-table text-indigo-600"></i>
                                <span>Customize Columns</span>
                            </div>
                            <button @click="showColumnPicker = false" class="col-picker-close">&times;</button>
                        </div>
                        <div class="col-picker-body">
                            <!-- All column groups with interactive checkboxes -->
                            <template v-for="(cols, group) in groupedAllColumns" :key="group">
                                <div class="col-picker-group">{{ group }}</div>
                                <label
                                    v-for="col in cols"
                                    :key="col.key"
                                    class="col-picker-checkbox"
                                    @click.prevent="togglePendingCol(col.key)"
                                >
                                    <input type="checkbox" :checked="pendingSelection.includes(col.key)" />
                                    <span>{{ col.label }}</span>
                                </label>
                            </template>
                        </div>
                        <div class="col-picker-footer">
                            <button type="button" class="btn-ghost text-xs" @click="resetDefaultColumns()">Reset</button>
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn-ghost" @click="showColumnPicker = false">Cancel</button>
                                <button type="button" class="btn-primary" @click="applyColumns()">Apply Changes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export CSV Button -->
                <button
                    type="button"
                    class="btn-secondary"
                    :disabled="exportingDump"
                    @click="exportDump"
                >
                    <i v-if="exportingDump" class="pi pi-spin pi-spinner text-xs"></i>
                    <i v-else class="pi pi-download text-xs"></i>
                    <span>Export</span>
                </button>

                <!-- Export Checklist Button (Position 4 only) -->
                <button
                    v-if="loguser.position_id == 4"
                    type="button"
                    class="btn-secondary text-amber-700 border-amber-200 hover:bg-amber-50"
                    :disabled="exportingChecklist"
                    @click="exportChecklist"
                >
                    <i v-if="exportingChecklist" class="pi pi-spin pi-spinner text-xs"></i>
                    <i v-else class="pi pi-file-check text-xs"></i>
                    <span>Checklist</span>
                </button>
            </div>
        </div>

        <!-- ── Filter & Status Card ── -->
        <div class="filter-card">
            <!-- Status Tabs -->
            <div class="status-tabs-row">
                <button
                    type="button"
                    class="status-tab"
                    :class="{ 'active': activeStatusTab === '' }"
                    @click="onStatusTabClick('')"
                >
                    All
                </button>
                <button
                    v-for="s in statuses"
                    :key="s.id"
                    type="button"
                    class="status-tab"
                    :class="{ 'active': String(activeStatusTab) === String(s.id) }"
                    @click="onStatusTabClick(s.id)"
                >
                    <span class="status-tab-dot" :class="getStatusStyle(s.id).dot"></span>
                    {{ s.name }}
                </button>
            </div>

            <!-- Main Filter Bar -->
            <form @submit.prevent="filter" class="filter-controls-row">
                <!-- Search Input with Icon -->
                <div class="search-input-wrapper">
                    <i class="pi pi-search search-icon"></i>
                    <input
                        type="text"
                        v-model="searchdata.employeeinfo"
                        placeholder="Search by name, ID, mobile, position..."
                        class="search-input"
                        @input="onGlobalSearchInput"
                        v-uppercase
                    />
                    <button
                        v-if="searchdata.employeeinfo"
                        type="button"
                        class="search-clear-btn"
                        @click="clearGlobalSearch"
                        title="Clear search"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- Date Range Filters -->
                <div class="date-range-group">
                    <div class="date-field">
                        <label class="date-label">Created From</label>
                        <div class="date-input-box">
                            <i class="pi pi-calendar text-slate-400 text-xs"></i>
                            <input type="date" v-model="searchdata.createdfrom" class="date-raw-input" />
                        </div>
                    </div>

                    <div class="date-field">
                        <label class="date-label">To</label>
                        <div class="date-input-box">
                            <i class="pi pi-calendar text-slate-400 text-xs"></i>
                            <input type="date" v-model="searchdata.createdto" class="date-raw-input" />
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="loading"
                    >
                        <i v-if="loading" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-filter text-xs"></i>
                        <span>Filter</span>
                    </button>

                    <button
                        v-if="searchdata.createdfrom || searchdata.createdto"
                        type="button"
                        class="btn-ghost"
                        @click="resetDateFilters"
                        title="Reset date range"
                    >
                        Reset Dates
                    </button>
                </div>

                <!-- Advanced Filters Toggle -->
                <div class="adv-filter-toggle-wrap">
                    <button
                        type="button"
                        class="btn-toggle-adv"
                        :class="{ 'active': showAdvFilters || searchdata.filters.length }"
                        @click="toggleAdvFilters"
                    >
                        <i class="pi pi-sliders-v text-xs"></i>
                        <span>Advanced Filters</span>
                        <span v-if="searchdata.filters.length" class="badge-count">
                            {{ searchdata.filters.length }}
                        </span>
                    </button>
                </div>
            </form>

            <!-- ── Expandable Advanced Filters Drawer ── -->
            <transition name="fade">
                <div v-if="showAdvFilters" id="advfilter" class="adv-filters-panel">
                    <div class="adv-panel-header">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-filter text-indigo-600"></i>
                            <span class="font-semibold text-xs uppercase tracking-wider text-slate-700">Advanced Filter Conditions</span>
                        </div>
                        <button
                            v-if="searchdata.filters.length"
                            type="button"
                            class="text-xs text-rose-600 hover:text-rose-800 font-medium"
                            @click="clearAllAdvFilters"
                        >
                            Clear All
                        </button>
                    </div>

                    <div class="adv-conditions-list">
                        <div
                            v-for="(filter, index) in searchdata.filters"
                            :key="index"
                            class="adv-condition-row"
                        >
                            <!-- Condition -->
                            <div class="adv-field-col condition-select-col">
                                <label class="adv-field-label">Condition</label>
                                <select v-model="filter.cond" class="adv-select" required>
                                    <option value="a">AND</option>
                                    <option value="o">OR</option>
                                </select>
                            </div>

                            <!-- Attribute -->
                            <div class="adv-field-col">
                                <label class="adv-field-label">Field</label>
                                <select v-model="filter.attr" @change="attrchange(index)" class="adv-select" required>
                                    <option value="firstname">First Name</option>
                                    <option value="surname">Surname</option>
                                    <option value="citizenship">Citizenship</option>
                                    <option value="joiningdate">Date Of Joining</option>
                                    <option value="joining_branch_id">Location</option>
                                    <option value="company">Company</option>
                                    <option value="contracttype">Contract Category</option>
                                    <option value="gender">Gender</option>
                                    <option value="joining_dept_id">Department</option>
                                    <option value="joiningposition">Position</option>
                                    <option value="creator">Creator</option>
                                </select>
                            </div>

                            <!-- Operator -->
                            <div class="adv-field-col">
                                <label class="adv-field-label">Operator</label>
                                <select v-model="filter.op" class="adv-select">
                                    <option value="is">Is</option>
                                    <option value="isnot">Is Not</option>
                                    <option value="gt">Greater Than</option>
                                    <option value="gtq">Equal or Greater</option>
                                    <option value="lt">Less Than</option>
                                    <option value="ltq">Equal or Less</option>
                                    <option value="cont">Contains</option>
                                </select>
                            </div>

                            <!-- Value Input -->
                            <div class="adv-field-col value-col">
                                <label class="adv-field-label" v-if="fil[filter.attr]">Value</label>
                                <select
                                    v-if="fil[filter.attr]?.inp == 'sel'"
                                    v-model="filter.val"
                                    class="adv-select"
                                    required
                                >
                                    <option value="">Select an option...</option>
                                    <option
                                        v-for="l in fil[filter.attr]?.list"
                                        :key="l[fil[filter.attr]?.id]"
                                        :value="l[fil[filter.attr]?.id]"
                                    >
                                        {{ l.name }}
                                    </option>
                                </select>
                                <input
                                    v-if="fil[filter.attr]?.inp == 'inp'"
                                    :type="fil[filter.attr]?.type"
                                    v-model="filter.val"
                                    class="adv-input"
                                    placeholder="Enter value..."
                                    v-uppercase
                                    required
                                />
                            </div>

                            <!-- Remove Condition Button -->
                            <div class="adv-action-col">
                                <button
                                    type="button"
                                    class="btn-remove-condition"
                                    @click="removefilter(index)"
                                    title="Remove this condition"
                                >
                                    <i class="pi pi-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="adv-panel-footer">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="addfilter"
                        >
                            <i class="pi pi-plus text-xs"></i>
                            <span>Add Condition</span>
                        </button>

                        <button
                            type="button"
                            class="btn-primary"
                            :disabled="loading"
                            @click="filter"
                        >
                            <i v-if="loading" class="pi pi-spin pi-spinner text-xs"></i>
                            <i v-else class="pi pi-check text-xs"></i>
                            <span>Apply Advanced Filters</span>
                        </button>
                    </div>
                </div>
            </transition>
        </div>

        <!-- ── Main Data Table Card ── -->
        <div class="table-card">
            <!-- Table Toolbar (Above Header) -->
            <div class="table-top-bar">
                <div class="table-top-left">
                    <span class="showing-text" v-if="loaded">
                        Showing <strong>{{ totalcount === 0 ? 0 : (current_page - 1) * per_page + 1 }}</strong> to <strong>{{ Math.min(current_page * per_page, totalcount) }}</strong> of <strong>{{ totalcount.toLocaleString() }}</strong> employees
                    </span>
                    <span v-if="hasColumnFilters" class="filter-indicator-badge">
                        Filtered Results
                        <button @click="clearColumnFilters" class="clear-mini-btn" title="Clear all column filters">
                            <i class="pi pi-times"></i>
                        </button>
                    </span>
                </div>

                <div class="table-top-right">
                    <!-- Sort Dropdown -->
                    <div class="sort-selector-wrap">
                        <i class="pi pi-sort-alt text-slate-400 text-xs"></i>
                        <span class="text-xs font-medium text-slate-500">Sort by:</span>
                        <select v-model="sortBy" @change="onSortChange" class="sort-dropdown">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Scrollable Table Section -->
            <div id="divtoscroll" class="table-scroll-container">
                <div class="table-grid-wrap">
                    <!-- Table Header -->
                    <div id="tableheader" class="modern-table-header">
                        <!-- Employee ID -->
                        <span v-if="isColVisible('employeeid')" class="th-cell sortable-th th-empid" @click="toggleSort('employeeid')" title="Sort by Employee ID">
                            <span>Employee ID</span>
                            <i :class="getSortIcon('employeeid')"></i>
                        </span>

                        <!-- First Name -->
                        <span v-if="isColVisible('firstname')" class="th-cell sortable-th th-firstname" @click="toggleSort('firstname')" title="Sort by First Name">
                            <span>First Name</span>
                            <i :class="getSortIcon('firstname')"></i>
                        </span>

                        <!-- Surname -->
                        <span v-if="isColVisible('surname')" class="th-cell sortable-th th-surname" @click="toggleSort('surname')" title="Sort by Surname">
                            <span>Surname</span>
                            <i :class="getSortIcon('surname')"></i>
                        </span>

                        <!-- Email -->
                        <span v-if="isColVisible('email')" class="th-cell sortable-th th-email" @click="toggleSort('email')" title="Sort by Email">
                            <span>Email</span>
                            <i :class="getSortIcon('email')"></i>
                        </span>

                        <!-- Mobile No -->
                        <span v-if="isColVisible('mobileno')" class="th-cell th-mobile">
                            <span>Mobile No</span>
                        </span>

                        <!-- Status -->
                        <span v-if="isColVisible('status')" class="th-cell sortable-th th-status" @click="toggleSort('status')" title="Sort by Status">
                            <span>Status</span>
                            <i :class="getSortIcon('status')"></i>
                        </span>

                        <!-- Created By -->
                        <span v-if="isColVisible('_creator')" class="th-cell th-creator">
                            <span>Created By</span>
                        </span>

                        <!-- Dynamic Extra Columns Header -->
                        <span
                            v-for="key in dynamicColumns"
                            :key="'h-' + key"
                            class="th-cell th-extra extra-col-header"
                        >
                            <span>{{ getColumnDef(key)?.label }}</span>
                            <button class="remove-col-btn" @click.stop="removeColumn(key)" title="Remove column">&times;</button>
                        </span>

                        <!-- Actions Header -->
                        <span class="th-cell th-actions">
                            <span>Actions</span>
                        </span>

                        <!-- Admin Status Change Header (Position 2) -->
                        <span v-if="loguser.position_id == 2" class="th-cell th-admin-status">
                            <span>Quick Status</span>
                        </span>
                    </div>

                    <!-- Column Filters Sub-Row -->
                    <div id="tablefilterrow" class="modern-table-filter-row">
                        <!-- Employee ID Filter -->
                        <span v-if="isColVisible('employeeid')" class="tf-cell th-empid">
                            <input
                                type="text"
                                v-model="columnFilters.employeeid"
                                placeholder="Filter ID..."
                                class="col-filter-input"
                            />
                        </span>

                        <!-- First Name Filter -->
                        <span v-if="isColVisible('firstname')" class="tf-cell th-firstname">
                            <input
                                type="text"
                                v-model="columnFilters.firstname"
                                placeholder="Filter first name..."
                                class="col-filter-input"
                            />
                        </span>

                        <!-- Surname Filter -->
                        <span v-if="isColVisible('surname')" class="tf-cell th-surname">
                            <input
                                type="text"
                                v-model="columnFilters.surname"
                                placeholder="Filter surname..."
                                class="col-filter-input"
                            />
                        </span>

                        <!-- Email Filter -->
                        <span v-if="isColVisible('email')" class="tf-cell th-email">
                            <input
                                type="text"
                                v-model="columnFilters.email"
                                placeholder="Filter email..."
                                class="col-filter-input"
                            />
                        </span>

                        <!-- Mobile Filter -->
                        <span v-if="isColVisible('mobileno')" class="tf-cell th-mobile">
                            <input
                                type="text"
                                v-model="columnFilters.mobileno"
                                placeholder="Filter mobile..."
                                class="col-filter-input"
                            />
                        </span>

                        <!-- Status Filter -->
                        <span v-if="isColVisible('status')" class="tf-cell th-status">
                            <select v-model="columnFilters.status" class="col-filter-select">
                                <option value="">All Statuses</option>
                                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </span>

                        <!-- Creator Filter -->
                        <span v-if="isColVisible('_creator')" class="tf-cell th-creator">
                            <input
                                type="text"
                                v-model="columnFilters.creator"
                                placeholder="Filter creator..."
                                class="col-filter-input"
                            />
                        </span>

                        <!-- Extra Dynamic Columns Filter (Blanks) -->
                        <span v-for="key in dynamicColumns" :key="'f-' + key" class="tf-cell th-extra"></span>

                        <!-- Clear Filters Button Cell -->
                        <span class="tf-cell th-actions">
                            <button
                                v-if="hasColumnFilters"
                                @click="clearColumnFilters"
                                class="btn-clear-col-filters"
                                title="Clear all column filters"
                            >
                                <i class="pi pi-filter-slash text-xs"></i>
                                <span>Clear</span>
                            </button>
                        </span>

                        <span v-if="loguser.position_id == 2" class="tf-cell th-admin-status"></span>
                    </div>

                    <!-- Table Body -->
                    <div id="tablebody" class="modern-table-body">
                        <!-- Loading State -->
                        <div v-if="loading && !emps.length" class="table-status-message">
                            <i class="pi pi-spin pi-spinner text-2xl text-indigo-600 mb-2"></i>
                            <span class="text-sm font-medium text-slate-500">Loading employees...</span>
                        </div>

                        <!-- Data Rows -->
                        <div
                            v-for="(emp, ind) in filteredEmps"
                            :key="emp.id || ind"
                            class="modern-table-row"
                        >
                            <!-- Employee ID Badge -->
                            <span v-if="isColVisible('employeeid')" class="td-cell th-empid">
                                <span class="empid-badge">
                                    #{{ emp.employeeid || emp.emp_code || '—' }}
                                </span>
                            </span>

                            <!-- First Name + Avatar -->
                            <span v-if="isColVisible('firstname')" class="td-cell th-firstname">
                                <div class="employee-identity-group">
                                    <div class="employee-avatar" :class="getAvatarClass(emp)">
                                        {{ getAvatarInitials(emp) }}
                                    </div>
                                    <div class="employee-details">
                                        <span class="employee-full-name">
                                            {{ emp.firstname }}
                                        </span>
                                        <span class="employee-meta-sub">
                                            {{ emp.joiningposition || 'Staff' }}
                                        </span>
                                    </div>
                                </div>
                            </span>

                            <!-- Surname -->
                            <span v-if="isColVisible('surname')" class="td-cell th-surname">
                                <span class="font-medium text-slate-800">
                                    {{ emp.surname || '—' }}
                                </span>
                            </span>

                            <!-- Email -->
                            <span v-if="isColVisible('email')" class="td-cell th-email">
                                <span class="cell-text-truncated" :title="emp.email">
                                    {{ emp.email || '—' }}
                                </span>
                            </span>

                            <!-- Mobile No -->
                            <span v-if="isColVisible('mobileno')" class="td-cell th-mobile">
                                <span class="font-mono text-xs text-slate-600">
                                    {{ emp.mobileno || '—' }}
                                </span>
                            </span>

                            <!-- Status Pill -->
                            <span v-if="isColVisible('status')" class="td-cell th-status">
                                <span class="status-pill" :class="getStatusStyle(emp.status).pill">
                                    <span class="status-pill-dot" :class="getStatusStyle(emp.status).dot"></span>
                                    <span>{{ getStatusStyle(emp.status).name }}</span>
                                </span>
                            </span>

                            <!-- Created By -->
                            <span v-if="isColVisible('_creator')" class="td-cell th-creator">
                                <span class="cell-text-muted">
                                    {{ emp.creator?.name || '—' }}
                                </span>
                            </span>

                            <!-- Dynamic Extra Columns -->
                            <span
                                v-for="key in dynamicColumns"
                                :key="'b-' + key + '-' + ind"
                                class="td-cell th-extra"
                            >
                                <span class="cell-text-regular">
                                    {{ getExtraValue(emp, key) }}
                                </span>
                            </span>

                            <!-- View Action Button -->
                            <span class="td-cell th-actions">
                                <router-link
                                    target="_blank"
                                    :to="`/employee/${emp.id}`"
                                    class="btn-view-profile"
                                >
                                    <i class="pi pi-external-link text-[10px]"></i>
                                    <span>View</span>
                                </router-link>
                            </span>

                            <!-- Admin Quick Status Change (Position 2) -->
                            <span v-if="loguser.position_id == 2" class="td-cell th-admin-status">
                                <select
                                    v-model="emp.status"
                                    class="admin-status-select"
                                    @change="changestatus(emp.id, $event)"
                                >
                                    <option v-for="s in statuses" :key="s.id" :value="s.id">
                                        {{ s.name }}
                                    </option>
                                </select>
                            </span>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!filteredEmps.length && loaded" class="table-empty-state">
                            <div class="empty-icon-wrap">
                                <i class="pi pi-search text-2xl text-slate-400"></i>
                            </div>
                            <h3 class="empty-title">No employees found</h3>
                            <p class="empty-desc">
                                {{ hasColumnFilters || searchdata.employeeinfo || searchdata.createdfrom ? 'No records match your active filter criteria. Try adjusting or clearing your filters.' : 'There are currently no employee records in the system.' }}
                            </p>
                            <button
                                v-if="hasColumnFilters || searchdata.employeeinfo || searchdata.createdfrom || searchdata.filters.length"
                                type="button"
                                class="btn-secondary mt-3"
                                @click="clearColumnFilters(); clearGlobalSearch(); resetDateFilters(); clearAllAdvFilters();"
                            >
                                <i class="pi pi-filter-slash text-xs"></i>
                                <span>Reset All Filters</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Modern Pagination Bar ── -->
            <div class="modern-pagination-bar">
                <div class="pagination-left">
                    <span class="text-xs text-slate-500 font-medium">Items per page:</span>
                    <select v-model="per_page" @change="selectchange" class="per-page-select">
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>

                <div class="pagination-right">
                    <!-- Previous Button -->
                    <button
                        type="button"
                        class="page-nav-btn"
                        :disabled="!isprev || loading"
                        @click="prevpage()"
                        title="Previous page"
                    >
                        <i class="pi pi-chevron-left text-xs"></i>
                    </button>

                    <!-- Page Number Pills -->
                    <div class="page-numbers-group">
                        <template v-for="(p, index) in visiblePages" :key="index">
                            <button
                                v-if="p !== '...'"
                                type="button"
                                class="page-num-btn"
                                :class="{ 'active': p === current_page }"
                                @click="goToPage(p)"
                            >
                                {{ p }}
                            </button>
                            <span v-else class="page-ellipsis">...</span>
                        </template>
                    </div>

                    <!-- Next Button -->
                    <button
                        type="button"
                        class="page-nav-btn"
                        :disabled="!isnext || loading"
                        @click="nextpage()"
                        title="Next page"
                    >
                        <i class="pi pi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* ── Main Layout Wrapper ── */
    .employees-page-wrapper {
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* ── Modern Header Section ── */
    .header-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 0.25rem 0.25rem 0.5rem;
    }
    .header-left {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .title-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-color, #0f172a);
        margin: 0;
        letter-spacing: -0.025em;
    }
    .count-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        background-color: #eef2ff;
        color: #4f46e5;
        border: 1px solid #e0e7ff;
    }
    .count-badge-loading {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        background-color: #f1f5f9;
        color: #64748b;
    }
    .page-subtitle {
        font-size: 0.85rem;
        color: var(--text-secondary, #64748b);
        margin: 0;
    }
    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    /* ── Buttons ── */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 0.625rem;
        background-color: #4f46e5;
        color: #ffffff;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 1px 2px rgba(79, 70, 229, 0.2);
    }
    .btn-primary:hover:not(:disabled) {
        background-color: #4338ca;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.35);
    }
    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 0.625rem;
        background-color: var(--surface-card, #ffffff);
        color: var(--text-color, #334155);
        border: 1px solid var(--border-color, #e2e8f0);
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .btn-secondary:hover:not(:disabled) {
        background-color: var(--surface-ground, #f8fafc);
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .btn-secondary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-ghost {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.55rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        border-radius: 0.625rem;
        background: transparent;
        color: var(--text-secondary, #64748b);
        border: none;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-ghost:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: var(--text-color, #0f172a);
    }

    .action-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 9999px;
        background-color: #eef2ff;
        color: #4f46e5;
    }

    /* ── Filter Card ── */
    .filter-card {
        background: var(--surface-card, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* ── Status Tabs ── */
    .status-tabs-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
    }
    .status-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 500;
        border-radius: 9999px;
        background: transparent;
        color: var(--text-secondary, #64748b);
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease-in-out;
    }
    .status-tab:hover {
        background-color: var(--surface-ground, #f8fafc);
        color: var(--text-color, #0f172a);
    }
    .status-tab.active {
        background-color: #0f172a;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    .status-tab-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    /* ── Filter Controls Row ── */
    .filter-controls-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.875rem;
    }

    /* Search Bar */
    .search-input-wrapper {
        position: relative;
        flex: 1 1 260px;
        min-width: 240px;
        max-width: 420px;
    }
    .search-icon {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.8125rem;
        color: #94a3b8;
        pointer-events: none;
    }
    .search-input {
        width: 100%;
        padding: 0.55rem 2.2rem 0.55rem 2.4rem;
        font-size: 0.8125rem;
        border-radius: 0.625rem;
        border: 1px solid var(--border-color, #e2e8f0);
        background-color: var(--surface-ground, #f8fafc);
        color: var(--text-color, #0f172a);
        outline: none;
        transition: all 0.15s ease;
    }
    .search-input:focus {
        background-color: var(--surface-card, #ffffff);
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }
    .search-input::placeholder {
        color: #94a3b8;
    }
    .search-clear-btn {
        position: absolute;
        right: 0.65rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.2rem;
        display: flex;
        align-items: center;
    }
    .search-clear-btn:hover {
        color: #475569;
    }

    /* Date Range Group */
    .date-range-group {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        flex-wrap: wrap;
    }
    .date-field {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .date-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-secondary, #64748b);
    }
    .date-input-box {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.65rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color, #e2e8f0);
        background: var(--surface-ground, #f8fafc);
    }
    .date-raw-input {
        border: none;
        background: transparent;
        font-size: 0.775rem;
        color: var(--text-color, #0f172a);
        outline: none;
        font-family: inherit;
        cursor: pointer;
    }

    /* Advanced Filter Toggle */
    .btn-toggle-adv {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 0.625rem;
        background-color: var(--surface-ground, #f8fafc);
        color: var(--text-secondary, #64748b);
        border: 1px solid var(--border-color, #e2e8f0);
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .btn-toggle-adv:hover,
    .btn-toggle-adv.active {
        background-color: #eef2ff;
        border-color: #c7d2fe;
        color: #4f46e5;
    }
    .badge-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        font-size: 0.6875rem;
        font-weight: 700;
        border-radius: 9999px;
        background-color: #4f46e5;
        color: #ffffff;
    }

    /* ── Expandable Advanced Filters Panel ── */
    .adv-filters-panel {
        background: var(--surface-ground, #f8fafc);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
    }
    .adv-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
    }
    .adv-conditions-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .adv-condition-row {
        display: flex;
        align-items: flex-end;
        gap: 0.625rem;
        flex-wrap: wrap;
        background: var(--surface-card, #ffffff);
        padding: 0.625rem 0.75rem;
        border-radius: 0.625rem;
        border: 1px solid var(--border-color, #e2e8f0);
    }
    .adv-field-col {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        flex: 1 1 140px;
    }
    .condition-select-col {
        flex: 0 0 90px;
        max-width: 90px;
    }
    .value-col {
        flex: 2 1 200px;
    }
    .adv-field-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-secondary, #64748b);
    }
    .adv-select, .adv-input {
        width: 100%;
        padding: 0.45rem 0.65rem;
        font-size: 0.8125rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color, #cbd5e1);
        background: var(--surface-card, #ffffff);
        color: var(--text-color, #0f172a);
        outline: none;
        transition: border-color 0.15s;
    }
    .adv-select:focus, .adv-input:focus {
        border-color: #4f46e5;
    }
    .adv-action-col {
        display: flex;
        align-items: center;
        padding-bottom: 2px;
    }
    .btn-remove-condition {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        border: 1px solid #fee2e2;
        background: #fef2f2;
        color: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-remove-condition:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    .adv-panel-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.625rem;
        padding-top: 0.5rem;
    }

    /* ── Table Card ── */
    .table-card {
        background: var(--surface-card, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Table Top Toolbar */
    .table-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
    }
    .table-top-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .showing-text {
        font-size: 0.8125rem;
        color: var(--text-secondary, #64748b);
    }
    .showing-text strong {
        color: var(--text-color, #0f172a);
        font-weight: 600;
    }
    .filter-indicator-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.5rem;
        font-size: 0.7125rem;
        font-weight: 600;
        border-radius: 9999px;
        background: #eef2ff;
        color: #4f46e5;
    }
    .clear-mini-btn {
        background: none;
        border: none;
        color: #4f46e5;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
    }
    .sort-selector-wrap {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }
    .sort-dropdown {
        padding: 0.35rem 0.65rem;
        font-size: 0.8125rem;
        font-weight: 500;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color, #cbd5e1);
        background: var(--surface-card, #ffffff);
        color: var(--text-color, #0f172a);
        outline: none;
        cursor: pointer;
    }
    .sort-dropdown:focus {
        border-color: #4f46e5;
    }

    /* ── Table Grid & Header ── */
    .table-scroll-container {
        overflow-x: auto;
        width: 100%;
    }
    .table-grid-wrap {
        min-width: 1000px;
        display: flex;
        flex-direction: column;
    }

    .modern-table-header {
        display: flex;
        align-items: center;
        background-color: var(--surface-ground, #f8fafc);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        padding: 0.625rem 1rem;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary, #64748b);
        user-select: none;
    }
    .th-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0 0.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sortable-th {
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .sortable-th:hover {
        color: #4f46e5;
    }

    /* Fixed Column Widths for Consistent SaaS Table */
    .th-empid { flex: 0 0 115px; width: 115px; }
    .th-empname { flex: 1 1 220px; min-width: 190px; }
    .th-firstname { flex: 1 1 170px; min-width: 140px; }
    .th-surname { flex: 1 1 130px; min-width: 110px; }
    .th-email { flex: 1 1 180px; min-width: 160px; }
    .th-mobile { flex: 0 0 125px; width: 125px; }
    .th-status { flex: 0 0 115px; width: 115px; }
    .th-creator { flex: 0 0 125px; width: 125px; }
    .th-extra { flex: 0 0 145px; width: 145px; }
    .th-actions { flex: 0 0 95px; width: 95px; justify-content: flex-end; }
    .th-admin-status { flex: 0 0 130px; width: 130px; }

    /* ── Table Filter Row (Under Header) ── */
    .modern-table-filter-row {
        display: flex;
        align-items: center;
        background-color: var(--surface-ground, #fafafa);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        padding: 0.45rem 1rem;
    }
    .tf-cell {
        display: inline-flex;
        align-items: center;
        padding: 0 0.5rem;
    }
    .col-filter-input, .col-filter-select {
        width: 100%;
        padding: 0.35rem 0.55rem;
        font-size: 0.75rem;
        border-radius: 0.45rem;
        border: 1px solid var(--border-color, #e2e8f0);
        background-color: var(--surface-card, #ffffff);
        color: var(--text-color, #0f172a);
        outline: none;
        transition: all 0.15s;
    }
    .col-filter-input:focus, .col-filter-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
    }
    .col-filter-input::placeholder {
        color: #94a3b8;
    }
    .btn-clear-col-filters {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.3rem 0.5rem;
        font-size: 0.725rem;
        font-weight: 600;
        border-radius: 0.4rem;
        border: 1px solid #fee2e2;
        background: #fef2f2;
        color: #ef4444;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-clear-col-filters:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    /* ── Table Body ── */
    .modern-table-body {
        display: flex;
        flex-direction: column;
        background: var(--surface-card, #ffffff);
    }
    .modern-table-row {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
        transition: background-color 0.15s ease;
    }
    .modern-table-row:last-child {
        border-bottom: none;
    }
    .modern-table-row:hover {
        background-color: var(--surface-ground, #f8fafc);
    }
    .td-cell {
        display: inline-flex;
        align-items: center;
        padding: 0 0.5rem;
        font-size: 0.8125rem;
        color: var(--text-color, #334155);
    }

    /* Employee ID Badge */
    .empid-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        border-radius: 0.375rem;
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
    }

    /* Employee Identity (Avatar + Name) */
    .employee-identity-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    .employee-avatar {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        border: 1px solid;
    }
    .employee-details {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .employee-full-name {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-color, #0f172a);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .employee-meta-sub {
        font-size: 0.6875rem;
        color: var(--text-secondary, #94a3b8);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Status Pill */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.6rem;
        font-size: 0.725rem;
        font-weight: 600;
        border-radius: 9999px;
        border: 1px solid;
        white-space: nowrap;
    }
    .status-pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    /* Cells Typography */
    .cell-text-truncated {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.8125rem;
        color: var(--text-color, #334155);
    }
    .cell-text-muted {
        font-size: 0.75rem;
        color: var(--text-secondary, #64748b);
    }
    .cell-text-regular {
        font-size: 0.8125rem;
        color: var(--text-color, #334155);
    }

    /* View Button */
    .btn-view-profile {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 0.5rem;
        background: #eef2ff;
        color: #4f46e5;
        border: 1px solid #e0e7ff;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-view-profile:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 1px 3px rgba(79, 70, 229, 0.25);
    }

    /* Admin Status Select */
    .admin-status-select {
        padding: 0.3rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 0.4rem;
        border: 1px solid var(--border-color, #cbd5e1);
        background: var(--surface-card, #ffffff);
        color: var(--text-color, #0f172a);
        outline: none;
        cursor: pointer;
    }

    /* Extra Column Header */
    .extra-col-header {
        position: relative;
        padding-right: 1.5rem !important;
    }
    .remove-col-btn {
        position: absolute;
        right: 0.25rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1;
        opacity: 0.6;
        transition: opacity 0.15s;
    }
    .remove-col-btn:hover {
        opacity: 1;
    }

    /* Table Status Message & Empty State */
    .table-status-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
    }
    .table-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
    }
    .empty-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background-color: var(--surface-ground, #f1f5f9);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .empty-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-color, #0f172a);
        margin: 0 0 0.35rem 0;
    }
    .empty-desc {
        font-size: 0.8125rem;
        color: var(--text-secondary, #64748b);
        max-width: 380px;
        margin: 0;
    }

    /* ── Modern Pagination Bar ── */
    .modern-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 0.875rem 1.25rem;
        border-top: 1px solid var(--border-color, #f1f5f9);
        background: var(--surface-card, #ffffff);
    }
    .pagination-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .per-page-select {
        padding: 0.3rem 0.5rem;
        font-size: 0.775rem;
        font-weight: 600;
        border-radius: 0.45rem;
        border: 1px solid var(--border-color, #e2e8f0);
        background: var(--surface-card, #ffffff);
        color: var(--text-color, #0f172a);
        outline: none;
        cursor: pointer;
    }
    .pagination-right {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    .page-nav-btn {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color, #e2e8f0);
        background: var(--surface-card, #ffffff);
        color: var(--text-color, #334155);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .page-nav-btn:hover:not(:disabled) {
        background: var(--surface-ground, #f8fafc);
        border-color: #cbd5e1;
    }
    .page-nav-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .page-numbers-group {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .page-num-btn {
        min-width: 32px;
        height: 32px;
        padding: 0 0.4rem;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        background: transparent;
        color: var(--text-color, #334155);
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .page-num-btn:hover:not(.active) {
        background: var(--surface-ground, #f8fafc);
    }
    .page-num-btn.active {
        background: #4f46e5;
        color: #ffffff;
        box-shadow: 0 1px 2px rgba(79, 70, 229, 0.25);
    }
    .page-ellipsis {
        padding: 0 0.25rem;
        color: #94a3b8;
        font-size: 0.8125rem;
    }

    /* ── Dynamic Column Picker Dropdown ── */
    .col-picker-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 99;
    }
    .col-picker-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 8px;
        width: 280px;
        background: var(--surface-card, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 0.875rem;
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.06);
        z-index: 100;
        overflow: hidden;
        animation: dropdownFade 0.15s ease-out;
    }
    @keyframes dropdownFade {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .col-picker-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        font-weight: 700;
        font-size: 0.8125rem;
        color: var(--text-color, #0f172a);
    }
    .col-picker-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #94a3b8;
        padding: 0 4px;
        line-height: 1;
    }
    .col-picker-close:hover {
        color: #0f172a;
    }
    .col-picker-body {
        max-height: 380px;
        overflow-y: auto;
        padding: 0.625rem;
    }
    .col-picker-group {
        padding: 0.5rem 0.625rem 0.25rem;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #4f46e5;
    }
    .col-picker-checkbox {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.45rem 0.625rem;
        border-radius: 0.5rem;
        cursor: pointer;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-color, #334155);
        transition: background 0.15s ease;
        user-select: none;
    }
    .col-picker-checkbox:hover {
        background: #f1f5f9;
    }
    .col-picker-checkbox input[type="checkbox"] {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    .col-picker-footer {
        padding: 0.625rem 1rem;
        border-top: 1px solid var(--border-color, #e2e8f0);
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        background: var(--surface-ground, #f8fafc);
    }

    /* ── Transitions ── */
    .fade-enter-active, .fade-leave-active {
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .fade-enter-from, .fade-leave-to {
        opacity: 0;
        transform: translateY(-8px);
    }

    /* ── Dark Mode Overrides ── */
    :global(body.dark-mode) .page-title {
        color: #f8fafc;
    }
    .dark-mode .btn-secondary,
    :global(body.dark-mode) .btn-secondary {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }
    :global(body.dark-mode) .filter-card,
    :global(body.dark-mode) .table-card {
        background: #1e293b;
        border-color: #334155;
    }
    :global(body.dark-mode) .modern-table-header {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
    :global(body.dark-mode) .modern-table-filter-row {
        background: #1e293b;
        border-color: #334155;
    }
    :global(body.dark-mode) .modern-table-row:hover {
        background-color: #0f172a;
    }
    :global(body.dark-mode) .empid-badge {
        background: #0f172a;
        color: #cbd5e1;
        border-color: #334155;
    }
    :global(body.dark-mode) .modern-pagination-bar {
        background: #1e293b;
        border-color: #334155;
    }
    :global(body.dark-mode) .search-input {
        background-color: #0f172a;
        border-color: #334155;
        color: #f8fafc;
    }
    :global(body.dark-mode) .search-input:focus {
        background-color: #1e293b;
    }
    :global(body.dark-mode) .status-tab:not(.active) {
        color: #94a3b8;
    }
    :global(body.dark-mode) .status-tab.active {
        background-color: #4f46e5;
    }
</style>