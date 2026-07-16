<script setup>
    import { ref, reactive, computed, onMounted, watch } from 'vue'
    import { log, calculateAge, toastt} from '@/helpers/essential'
    import { mstatus,  bankaccounttype, relations, depts, regions, branchs, qualtypes, banks, familyrelation, countries, conttypes, idtypes, findidtypes, findcompany, genders, companies, findregion, findconttypes, findgender, findmarital, findbranch, finddept, findcountry, statuses, findstatus} from '@/data/masterdata'
    
    import { useRouter } from 'vue-router';
    import axios from '@/helpers/pms_axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const router = useRouter()
    const { loguser } = userstore

    const emps = ref([])
    let searchdata = reactive({filters:[]})
    let loaded = ref(false)
    let loading = ref(true)

    // ── Dynamic Column Picker ──
    const showColumnPicker = ref(false)

    // All columns: default (always-on) + optional (onboarding fields)
    const allColumns = [
        // Default columns (checked & locked)
        { key: 'employeeid', label: 'Employee ID', group: 'Default', default: true },
        { key: 'firstname', label: 'First Name', group: 'Default', default: true },
        { key: 'surname', label: 'Surname', group: 'Default', default: true },
        { key: 'email', label: 'Email', group: 'Default', default: true },
        { key: 'mobileno', label: 'Mobile No', group: 'Default', default: true },
        { key: 'status', label: 'Status', group: 'Default', default: true, formatter: (v) => findstatus(v) },
        { key: '_creator', label: 'Created By', group: 'Default', default: true },
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

    // Which optional column keys are currently visible
    const extraColumns = ref([])

    // Temp selection state while picker is open
    const pendingSelection = ref([])

    const openColumnPicker = () => {
        pendingSelection.value = [...extraColumns.value]
        showColumnPicker.value = true
    }

    const togglePendingCol = (key) => {
        if (pendingSelection.value.includes(key)) {
            pendingSelection.value = pendingSelection.value.filter(k => k !== key)
        } else {
            pendingSelection.value.push(key)
        }
    }

    const applyColumns = () => {
        extraColumns.value = [...pendingSelection.value]
        showColumnPicker.value = false
    }

    const optionalColumns = allColumns.filter(c => !c.default)

    const groupedOptionalColumns = computed(() => {
        const groups = {}
        optionalColumns.forEach(col => {
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

    // Column filters (server-side with debounce)
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
        return Object.values(columnFilters).some(v => v !== '')
    })

    const clearColumnFilters = () => {
        Object.keys(columnFilters).forEach(k => columnFilters[k] = '')
    }

    // Debounce timer for column filters
    let columnFilterTimer = null
    watch(() => ({ ...columnFilters }), () => {
        clearTimeout(columnFilterTimer)
        columnFilterTimer = setTimeout(() => {
            // Build server-side filters from column filters
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
            // Merge column filters into searchdata.filters (preserve any existing advanced filters)
            searchdata.columnFilters = serverFilters
            current_page.value = 1
            fetchData()
        }, 400)
    }, { deep: true })

    // filteredEmps now just returns emps directly (filtering is server-side)
    const filteredEmps = computed(() => emps.value)

    let current_page = ref(1)
    let last_page = ref(null)
    let isnext = ref(false)
    let isprev = ref(false)
    let per_page = ref(50)
    let totalcount = ref(0)

    onMounted(() => {
        fetchData()
    })
    const viewemp = (id) => {
        // toastt(`${id}`)
        router.push({ name: 'employee', params: { empid: id } })
    }
    
    const colorinvalid = (elem) =>{
        
        elem.style = 'border:2px solid red'
        if(elem.nextElementSibling) elem.nextElementSibling.innerText = elem.validationMessage
        elem.addEventListener('input', function(e){
            elem.style = 'revert'
            if(elem.nextElementSibling) elem.nextElementSibling.innerText = ''
        },{once:true})
        
    }

    const filter = () => {

        const reqinput = document.querySelectorAll("#advfilter :invalid");
            
        reqinput.forEach((elem)=>{
            colorinvalid(elem)
        })

        if(reqinput.length){
            toastt('Highlighted fields cannot be empty', 'error')
            return false
        }

        current_page.value = 1
        last_page.value = 1

        fetchData()
        
    }

    const selectchange = (e) => {
        current_page.value = 1
        last_page.value = 1

        fetchData()
    }

    const fetchData = () => {
        loading.value = true

        // Merge advanced filters and column filters
        const payload = { ...searchdata }
        const allFilters = [
            ...(searchdata.filters || []),
            ...(searchdata.columnFilters || [])
        ]
        payload.filters = allFilters

        axios.post(`fetchemployees?page=${current_page.value}&per_page=${per_page.value}`, payload)
            .then(res => {
                const data = res.data
                emps.value = data.data
                current_page.value = data.current_page
                isnext.value = data.next_page_url
                isprev.value = data.prev_page_url
                last_page.value = data.last_page
                totalcount.value = data.total

                loaded.value = true
                loading.value = false
                log(data)
            })
            .catch((error) => {
                console.log(error)
            })
    }

    const nextpage = () => {
        current_page.value++
        fetchData()
    }

    const prevpage = () => {
        current_page.value--
        fetchData()
    }

    /* Directive Creation Start */
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
            const newValue = sourceValue.replace(/([^+0-9]+)/gi, '');

            if (sourceValue !== newValue) {
                input.value = newValue
                input.dispatchEvent(new Event('input', { bubbles: true }))
            }
        }
    /* Directive Creation Stop */

    const fil = {
        firstname:{
            type:'text',
            inp: 'inp'
        },
        surname:{
            type:'text',
            inp: 'inp'
        },
        creator:{
            type:'text',
            inp: 'inp'
        },
        joiningposition:{
            type:'text',
            inp: 'inp'
        },
        dob:{
            type:'date',
            inp: 'inp'
        },
        joiningdate:{
            type:'date',
            inp: 'inp'
        },
        joining_branch_id:{
            type:'text',
            inp: 'sel',
            list: branchs,
            id: 'id'
        },
        company:{
            type:'text',
            inp: 'sel',
            list: companies,
            id: 'id'
        },
        contracttype:{
            type:'text',
            inp: 'sel',
            list: conttypes,
            id: 'code'
        },
        gender:{
            type:'text',
            inp: 'sel',
            list: genders,
            id: 'id'
        },
        citizenship:{
            type:'text',
            inp: 'sel',
            list: countries,
            id: 'code'
        },
        joining_dept_id:{
            type:'text',
            inp: 'sel',
            list: depts,
            id: 'id'
        },
    }
    const addfilter = () => {
        searchdata.filters.push({
            'cond': '',
            'attr': '',
            'op': 'is',
            'val': null,
        })
    }
    const removefilter = (index) => {
        searchdata.filters.splice(index, 1)
    }
    const attrchange = (index) => {
        searchdata.filters[index].val = null
    }

    const  exportToCsv = (data, filename = 'export.csv') =>{
        try {
            // Validate input
            if (!Array.isArray(data) || !data.length) {
                throw new Error('Input must be a non-empty array');
            }

            // Get headers from the first object's keys
            const headers = Object.keys(data[0]);
            
            // Create CSV rows
            const csvRows = [
                // Add headers row
                headers.join(','),
                // Add data rows
                ...data.map(row => {
                    return headers.map(header => {
                        // Handle special characters and ensure proper CSV formatting
                        const cell = row[header]?.toString() ?? '';
                        // Escape quotes and wrap in quotes if contains comma or newline
                        if (cell.includes(',') || cell.includes('\n') || cell.includes('"')) {
                            return `"${cell.replace(/"/g, '""')}"`;
                        }
                        return cell;
                    }).join(',');
                })
            ];

            // Create blob and download link
            const csvContent = csvRows.join('\n');
            const blob = new Blob(["\uFEFF" +csvContent], { type: 'text/csv;charset=utf-8;' });
            
            if (navigator.msSaveBlob) { // IE 10+
                navigator.msSaveBlob(blob, filename);
            } else {
            const link = document.createElement('a');
            if (link.download !== undefined) {
                // Create URL for blob
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
            }
            return true;
        } catch (error) {
            console.error('Error exporting CSV:', error);
            throw error;
        }
    }

    let exportingChecklist = ref(false)
    const exportChecklist = () =>{

        exportingChecklist.value = true

        axios.post(`fetchemployeesforexport`,searchdata)
            .then(res => {
                const data = res.data
                
                const finaldata = data.map((i) => {
                    
                    const isoptional = computed(() => {
                        if([4,5,6,7,8,9,10].includes(i.company)) return true
                        else return false
                    })
                    const isplcnotrequired = computed(() => {
                        if(['DRIVER','SECURITY','SECURITY OFFICER','SHOP MANAGER','SALES MANAGER','SALES EXECUTIVE'].includes(i.joiningposition)) return false
                        else return true
                    })
                    const guarantorrequired = computed(() => {
                        if(['AUDITOR', 'CASHIER'].includes(i.joiningposition) || ![4,5,6,7,8,9,10].includes(i.company)) return true
                        else return false
                    })

                    return {
                        'Staff ID': i.employeeid,
                        'Fullname': i.firstname+' '+i.surname,
                        'Status': findstatus(i.status),
                        'Joining Company' : findcompany(i.company),
                        'Joining Position' : i.joiningposition,
                        'Profile Form': '✓',
                        '2 Passport size pictures': i.ghcard ? '✓' : 'X',
                        'Application Letter': i.appletters?.length ? '✓' : 'X',
                        'Appointment Letter': i.appointmentletters?.length ? '✓' : 'X',
                        'Probation Confirmation': i.probationconfs?.length ? '✓' : 'X',
                        'CV': i.cv?.length ? '✓' : 'X',
                        'Petra Trust': isoptional.value ? 'NA' : (i.petratrust?.length ? '✓' : 'X'),
                        'NIC 2 Coloured Copies - GH CARD': i.ghcard ? '✓' : 'X',
                        'Guarantor Forms': guarantorrequired.value ? (i.irrguarantor ? '✓' : 'X') : 'NA',
                        'Bank / Social Security Fund': isoptional.value ? 'NA' : (i.banksocial ? '✓' : 'X'),
                        'NHIS Card': i.nhis?.length ? '✓' : 'X',
                        'Birth Certificate': isoptional.value ? 'NA' : (i.birthcert?.length ? '✓' : 'X'),
                        'Police Clearance Form': isplcnotrequired.value ? 'NA' : (i.pclearanceform?.length ? '✓' : 'X'),
                        'SSNIT': i.ssnit?.length ? '✓' : 'X'
                    }
                });
                
                exportToCsv(finaldata, 'Checklist Export.csv')
                exportingChecklist.value = false
            })
            .catch((error) => {
                console.log(error)
                exportingChecklist.value = false
            })
    }

    let exportingDump = ref(false)
    const exportDump = () =>{

        exportingDump.value = true

        axios.post(`fetchemployeesdumpforexport`,searchdata)
            .then(res => {
                const data = res.data
                
                const finaldata = data.map((i) => ({
                    'Staff ID': i.employeeid,
                    'Fullname': i.firstname+' '+i.middlename+' '+i.surname,
                    'Citizenship': findcountry(i.citizenship),
                    'ID Type': i.citizenship == 'GH' ? 'GHANA CARD' : findidtypes(i.idtype),
                    'GH Card / ID CARD / PP No' : i.ghcardno,
                    'Residential Ghana DA': i.daddress,
                    'Permanent Ghana DA': i.hdaddress,
                    'Status': findstatus(i.status),
                    'Email' : i.email,
                    'Mobile No' : i.mobileno,
                    'Alternate Phone': i.altnumber,
                    'Date of Birth': i.dob,
                    'Gender': findgender(i.gender),
                    'Social Security Number' : i.socialsecurityno,
                    'Marital Status': findmarital(i.maritalstatus),
                    'Father Name': i.fathersname,
                    'Mother Name': i.mothersname,
                    'Joining Position' : i.joiningposition,
                    'Joining Location' : findbranch(i.joining_branch_id),
                    'Joining Company' : findcompany(i.company),
                    'Contract Category': findconttypes(i.contracttype),
                    'Joining Department': finddept(i.joining_dept_id),
                    'Date of Joining': i.joiningdate,
                    'OTHER INFORMATION': i.anyotherinfo

                }))
                
                exportToCsv(finaldata, 'Export.csv')
                exportingDump.value = false
            })
            .catch((error) => {
                console.log(error)
                exportingDump.value = false
            })  
    }

    const changestatus = (id, $event) => {
        
        const selectedValue = event.target.value
        const selectedText = event.target.options[event.target.selectedIndex].text

        const data = {
            empid: id,
            status: selectedValue,
            statusname: selectedText
        }

        axios.post('updateempstatus', data, {
            }).then(res => {
                
                const data = res.data

                toastt('Status successfully updated')

                log(data)
                
            }).catch((error) => {
                toastt('Error. Please Try again', 'error')
                log(error)
                
            })
    }


</script>
<template>
    <div >
        
        <h2>Employees list</h2>
        
        <div>
            <form @submit.prevent="filter" >

                <div id='filter' >
                    <div>
                        <label for="">Employee Info</label>
                        <input type="text" v-model="searchdata.employeeinfo" v-uppercase>
                    </div>

                    <div style="text-align: center;">
                        <label for="">Creation Date</label>
                        <span>
                            <input type="date" v-model="searchdata.createdfrom" style="margin-right: 10px;">
                            <input type="date" v-model="searchdata.createdto">
                        </span>
                    </div>

                    <span v-if="!searchdata.filters.length" class="filterspan">
                        <Button :loading="loading" label="Filter" @click="filter" :disabled="loading"></Button>
                        <Button
                            class="!bg-green-900 !border-0"
                            @click="addfilter"
                        >Add Advanced Filters</Button>
                    </span>
                        
                </div>
                
                <div id='advfilter' class="bg-gray-100 my-5 p-5" v-if="searchdata.filters.length">
                    
                    <div v-for="(filter, index) in searchdata.filters" :key="index" class="inline">
                        <div>
                            <label>Condition*:</label>
                            <select v-model="filter.cond" required>
                                <option value="a">AND</option>
                                <option value="o">OR</option>
                            </select>
                        </div>
                        <div>
                            <label>Attribute *:</label>
                            <select v-model="filter.attr" @change="attrchange(index)" required>
                                <option value="firstname" >Firstname</option>
                                <option value="surname">Surname</option>
                                <option value="citizenship">Citizenship</option>
                                <option value="joiningdate">Date Of Joining</option>
                                <option value="joining_branch_id">Location</option>
                                <option value="company">Company</option>
                                <option value="contracttype">Contract Category</option>
                                <option value="gender">Gender</option>
                                <option value="citizenship">Citizenship</option>
                                <option value="joining_dept_id">Department</option>
                                <option value="joiningposition">Position</option>
                                <option value="creator">Creator</option>
                            </select>
                        </div>

                        <div>
                            <label>Operator *:</label>
                            <select v-model="filter.op">
                                <option value='is'>Is</option>
                                <option value="isnot">Is Not</option>
                                <option value="gt">Greater Than</option>
                                <option value="gtq">Equal or Greater Than</option>
                                <option value="lt">Less than</option>
                                <option value="ltq">Equal or Less than</option>
                                <option value="cont">Contains</option>
                            </select>
                        </div>

                        <div >
                            <label v-if="fil[filter.attr]">Value *:</label>
                            <select v-if="fil[filter.attr]?.inp == 'sel'" v-model="filter.val" required>
                                <option value=""></option>
                                <option
                                    v-for="l in fil[filter.attr]?.list"
                                    :key="l[fil[filter.attr]?.id]"
                                    :value="l[fil[filter.attr]?.id]"
                                >
                                    {{  l.name }}
                                </option>
                            </select>
                            <input v-if="fil[filter.attr]?.inp == 'inp'" :type="fil[filter.attr]?.type" v-model="filter.val" v-uppercase required>
                        </div>
                        
                        <span>
                            <Button
                                style="width: 34px; height: 34px;"
                                class="!bg-red-600 !border-0"
                                @click="removefilter(index)"
                                icon="pi pi-minus"
                            ></Button>
                            
                        </span>
                    </div>

                    <span class="filterspan">
                        <Button
                            class="!bg-green-900 !border-0"
                            @click="addfilter"
                        >Add Condition</Button>
                        <Button :loading="loading" label="Filter" @click="filter" :disabled="loading"></Button>
                    </span>
                    
                </div>
                
            </form>
        </div>

        <div id='divtoscroll'>
            <div style="display: flex; flex-direction: column;">

                <div class="my-4">
                    <strong >Total count:</strong> {{ totalcount }}
                    <span v-if="hasColumnFilters" style="margin-left: 10px; color: var(--primary); font-size: 0.875rem;">
                        (Showing {{ filteredEmps.length }} of {{ emps.length }} on page)
                    </span>

                    <Button
                        severity="info"
                        :loading="exportingDump"
                        label="Export"
                        @click="exportDump"
                        :disabled="exportingDump"
                        class="ms-3"
                        >
                    </Button>

                    <Button
                        severity="warn"
                        :loading="exportingChecklist"
                        label="Export Checklist"
                        @click="exportChecklist"
                        :disabled="exportingChecklist"
                        class="ms-3"
                        v-if="loguser.position_id == 4"
                        >
                    </Button>

                    
                    
                </div>

                <div id='tableheader' >
                    <span>Employee ID</span>
                    <span>First Name</span>
                    <span>Surname</span>
                    <span>Email</span>
                    <span>Mobile No</span>
                    <span>Status</span>
                    <span>Created By</span>
                    <span v-for="key in extraColumns" :key="'h-'+key" class="extra-col-header">
                        {{ getColumnDef(key)?.label }}
                        <button class="remove-col-btn" @click="removeColumn(key)" title="Remove column">&times;</button>
                    </span>
                    <span class="actions-col-header">
                        Actions
                        <div class="add-col-wrapper">
                            <button class="add-col-btn" @click.stop="openColumnPicker()" title="Add/Remove columns">
                                <i class="pi pi-plus"></i>
                            </button>
                            <div v-if="showColumnPicker" class="col-picker-backdrop" @click="showColumnPicker = false"></div>
                            <div v-if="showColumnPicker" class="col-picker-dropdown" @click.stop>
                                <div class="col-picker-header">
                                    <span>Select Columns</span>
                                    <button @click="showColumnPicker = false" class="col-picker-close">&times;</button>
                                </div>
                                <div class="col-picker-body">
                                    <!-- Default columns (always checked, disabled) -->
                                    <div class="col-picker-group">Default Columns</div>
                                    <label v-for="col in allColumns.filter(c => c.default)" :key="col.key" class="col-picker-checkbox">
                                        <input type="checkbox" checked disabled />
                                        <span>{{ col.label }}</span>
                                    </label>
                                    <!-- Optional columns grouped -->
                                    <template v-for="(cols, group) in groupedOptionalColumns" :key="group">
                                        <div class="col-picker-group">{{ group }}</div>
                                        <label v-for="col in cols" :key="col.key" class="col-picker-checkbox" @click.prevent="togglePendingCol(col.key)">
                                            <input type="checkbox" :checked="pendingSelection.includes(col.key)" />
                                            <span>{{ col.label }}</span>
                                        </label>
                                    </template>
                                </div>
                                <div class="col-picker-footer">
                                    <button class="col-picker-apply" @click="applyColumns()">Apply</button>
                                </div>
                            </div>
                        </div>
                    </span>
                    <span v-if="loguser.position_id == 2"></span>
                </div>

                <!-- Column Filter Row -->
                <div id='tablefilterrow' >
                    <span>
                        <input type="text" v-model="columnFilters.employeeid" placeholder="Filter..." class="col-filter-input" />
                    </span>
                    <span>
                        <input type="text" v-model="columnFilters.firstname" placeholder="Filter..." class="col-filter-input" />
                    </span>
                    <span>
                        <input type="text" v-model="columnFilters.surname" placeholder="Filter..." class="col-filter-input" />
                    </span>
                    <span>
                        <input type="text" v-model="columnFilters.email" placeholder="Filter..." class="col-filter-input" />
                    </span>
                    <span>
                        <input type="text" v-model="columnFilters.mobileno" placeholder="Filter..." class="col-filter-input" />
                    </span>
                    <span>
                        <select v-model="columnFilters.status" class="col-filter-input">
                            <option value="">All</option>
                            <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </span>
                    <span>
                        <input type="text" v-model="columnFilters.creator" placeholder="Filter..." class="col-filter-input" />
                    </span>
                    <span v-for="key in extraColumns" :key="'f-'+key"></span>
                    <span>
                        <button v-if="hasColumnFilters" @click="clearColumnFilters" class="col-filter-clear" title="Clear all filters">
                            <i class="pi pi-filter-slash"></i>
                        </button>
                    </span>
                    <span v-if="loguser.position_id == 2"></span>
                </div>

                <div id='tablebody' >

                    <div
                        v-for="(emp, ind) in filteredEmps"
                        :key="ind"
                    >
                        <span>{{ emp.employeeid || emp.emp_code }}</span>
                        <span>{{ emp.firstname }}</span>
                        <span>{{ emp.surname }}</span>
                        <span>{{ emp.email }}</span>
                        <span>{{ emp.mobileno }}</span>
                        <span>{{ findstatus(emp.status)  }}</span>
                        <span>{{ emp.creator?.name || '—' }}</span>
                        <span v-for="key in extraColumns" :key="'b-'+key+'-'+ind">{{ getExtraValue(emp, key) }}</span>
                        <span class="view-col">
                            <router-link target="_blank" :to="`/employee/${emp.id}`" class="view-btn">
                                View
                            </router-link>
                        </span>
                        <span v-if="loguser.position_id == 2">
                            <select
                                v-model="emp.status"
                                required
                                @change="changestatus(emp.id, $event)"
                            >
                                <option
                                    v-for="s in statuses"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{  s.name }}
                                </option>
                            </select>
                        </span>
                        
                    </div>

                    <div v-if="!filteredEmps.length && loaded" style="justify-content: center;">
                        {{ hasColumnFilters ? 'No matches for column filters' : 'No result for your search' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="paginationcontrols">


            
            <select v-model="per_page" @change="selectchange">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>

            <div id='pagecontrol'>

                <button :disabled="!isprev || loading"  @click="prevpage()">Prev</button>

                <span v-if="!loading">{{ current_page }} / {{ last_page }}</span>
                <i v-if="loading" class="pi pi-spin pi-spinner" style="font-size: 1.2rem;padding: 7px 16px;"></i>

                <button :disabled="!isnext || loading" @click="nextpage()">Next</button>
            </div>
        </div>
        
    </div>
</template>
<style scoped>

    .paginationcontrols{
        display: flex;
        justify-content: end;
        gap: 15px;
        margin-top:20px;

        & select{
            width: 60px ;
            background-color: var(--surface-card);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
    }
    #pagecontrol{
        display: flex;
        justify-content: end;
        gap: 5px;
        
        & button{
            padding: 7px 12px;
            cursor: pointer;
            background-color: var(--surface-card);
            color: var(--primary);
            border: 1px solid var(--primary);
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s;

            &:disabled{
                background-color: var(--surface-ground);
                color: var(--text-secondary);
                cursor: not-allowed;
                border-color: var(--border-color);
            }
            &:hover:not(:disabled){
                background-color: var(--primary);
                color: white;
            }
        }
        & span{
            padding: 7px 10px;
            color: var(--text-secondary);
        }
        
        
    }

    .filterspan{
        display: flex;
        gap:10px
    }

    #filter {
        display: flex;
        gap:10px;
        margin-bottom: 20px;
        align-items: end;
        flex-wrap: wrap;

        & label:not(.radio){
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        & input, select{
            padding:8px 12px;
            width: 200px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: var(--surface-card);
            color: var(--text-color);
        }

        & button{
            height: 35px;
            /* padding:10px; button component handles padding */
        }
    }


    #tableheader{
        display: flex;
        font-weight: 600;
        padding: 12px 16px;
        background-color: var(--surface-card);
        color: var(--text-secondary);
        border-bottom: 2px solid var(--border-color);
        border-radius: 8px 8px 0 0;
        align-items: center;
    }
    #tableheader > span{
        flex: 1 1 0;
        min-width: 0;
        display: inline-block;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    #tableheader > span.actions-col-header {
        overflow: visible;
    }

    /* Column Filter Row */
    #tablefilterrow {
        display: flex;
        padding: 8px 16px;
        background-color: var(--surface-ground, #f8fafc);
        border-bottom: 1px solid var(--border-color);
        align-items: center;
    }
    #tablefilterrow > span {
        flex: 1 1 0;
        min-width: 0;
        display: inline-flex;
        align-items: center;
        padding-right: 8px;
    }
    .col-filter-input {
        width: 100% !important;
        padding: 5px 8px !important;
        font-size: 0.8rem !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 4px !important;
        background-color: var(--surface-card) !important;
        color: var(--text-color) !important;
        outline: none;
        transition: border-color 0.2s;
    }
    .col-filter-input:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
    }
    .col-filter-input::placeholder {
        color: var(--text-secondary, #9ca3af);
        font-size: 0.75rem;
    }
    .col-filter-clear {
        background: none;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 4px 8px;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .col-filter-clear:hover {
        color: #ef4444;
        border-color: #ef4444;
        background-color: rgba(239, 68, 68, 0.05);
    }

    #tablebody{
        background-color: var(--surface-card); 
        flex:1;
        overflow: auto;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    #tablebody > div{
        display: flex;
        border-bottom:1px solid var(--border-color);
        padding: 12px 16px;
        transition: background-color .1s;
        align-items: center;
    }
    #tablebody > div:last-child {
        border-bottom: none;
    }
    #tablebody > div:hover{
        background-color: var(--surface-ground);
    }
    #tablebody > div > span{
        flex: 1 1 0;
        min-width: 0;
        display: inline-block;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.95rem;
        color: var(--text-color);
    }
    #tablebody button{
        /* padding:5px 10px; handled by component */
        cursor: pointer;
    }

    /* View button */
    .view-col {
        display: flex !important;
        justify-content: flex-end;
        padding-right: 8px;
    }
    .view-btn {
        display: inline-block;
        padding: 5px 18px;
        background: #ecfdf5;
        color: #059669;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        border: 1px solid #a7f3d0;
    }
    .view-btn:hover {
        background: #d1fae5;
        color: #047857;
        box-shadow: 0 1px 4px rgba(5, 150, 105, 0.15);
    }

    /* ── Dynamic Column Picker ── */
    .actions-col-header {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        position: relative;
        flex: 0 0 auto !important;
        width: auto !important;
        white-space: nowrap;
    }
    .add-col-wrapper {
        position: relative;
    }
    .add-col-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 2px dashed var(--primary, #4f46e5);
        background: rgba(79, 70, 229, 0.06);
        color: var(--primary, #4f46e5);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 0.8rem;
    }
    .add-col-btn:hover {
        background: var(--primary, #4f46e5);
        color: white;
        border-style: solid;
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }
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
        width: 260px;
        background: var(--surface-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 12px 36px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
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
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-color, #1e293b);
    }
    .col-picker-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: var(--text-secondary, #94a3b8);
        padding: 0 4px;
        line-height: 1;
    }
    .col-picker-close:hover {
        color: var(--text-color, #1e293b);
    }
    .col-picker-body {
        max-height: 380px;
        overflow-y: auto;
        padding: 8px;
    }
    .col-picker-group {
        padding: 8px 10px 4px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--primary, #4f46e5);
        margin-top: 4px;
    }
    .col-picker-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--text-color, #334155);
        transition: background 0.15s ease;
        user-select: none;
    }
    .col-picker-checkbox:hover {
        background: rgba(79, 70, 229, 0.08);
    }
    .col-picker-checkbox input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 17px;
        min-width: 17px;
        height: 17px;
        border: 2px solid #cbd5e1;
        border-radius: 4px;
        cursor: pointer;
        flex-shrink: 0;
        position: relative;
        background: #fff;
        transition: all 0.15s ease;
        margin-right: 4px;
    }
    .col-picker-checkbox input[type="checkbox"]:checked {
        background: var(--primary, #4f46e5);
        border-color: var(--primary, #4f46e5);
    }
    .col-picker-checkbox input[type="checkbox"]:checked::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 1px;
        width: 5px;
        height: 9px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }
    .col-picker-checkbox input[type="checkbox"]:disabled {
        cursor: default;
        opacity: 0.7;
    }
    .col-picker-footer {
        padding: 10px 14px;
        border-top: 1px solid var(--border-color, #e2e8f0);
        display: flex;
        justify-content: flex-end;
    }
    .col-picker-apply {
        padding: 8px 28px;
        background: var(--primary, #4f46e5);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .col-picker-apply:hover {
        background: #4338ca;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
    }

    /* Extra column header with remove button */
    .extra-col-header {
        position: relative;
        padding-right: 20px !important;
        font-size: 0.75rem !important;
    }
    .remove-col-btn {
        position: absolute;
        top: 0;
        right: 2px;
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1;
        opacity: 0.5;
        transition: opacity 0.15s;
        padding: 0 2px;
    }
    .remove-col-btn:hover {
        opacity: 1;
    }

    /* Horizontal scroll when too many columns */
    #divtoscroll > div {
        overflow-x: auto;
    }
</style>