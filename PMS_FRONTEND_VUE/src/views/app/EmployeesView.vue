<script setup>
    import { ref, reactive, computed, onMounted } from 'vue'
    import { log, calculateAge, toastt} from '@/helpers/essential'
    import { mstatus,  bankaccounttype, relations, depts, regions, branchs, qualtypes, banks, familyrelation, countries, conttypes, idtypes, findidtypes, findcompany, genders, companies, findregion, findconttypes, findgender, findmarital, findbranch, finddept, findcountry, statuses, findstatus} from '@/data/masterdata'
    
    import { useRouter } from 'vue-router';
    import axios from 'axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const router = useRouter()
    const { loguser, getloguser, authtoken, getauthtoken } = userstore
    
    const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer

    const emps = ref([])
    let searchdata = reactive({filters:[]})
    let loaded = ref(false)
    let loading = ref(true)

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
        router.push({ name: 'employee', params: { empid: id } })
    }
    
    const colorinvalid = (elem) =>{
        elem.classList.add('border-red-500')
        if(elem.nextElementSibling) elem.nextElementSibling.innerText = elem.validationMessage
        elem.addEventListener('input', function(e){
            elem.classList.remove('border-red-500')
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
        axios.post(`fetchemployees?page=${current_page.value}&per_page=${per_page.value}`,searchdata)
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
                loading.value = false
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
        firstname:{ type:'text', inp: 'inp' },
        surname:{ type:'text', inp: 'inp' },
        creator:{ type:'text', inp: 'inp' },
        joiningposition:{ type:'text', inp: 'inp' },
        dob:{ type:'date', inp: 'inp' },
        joiningdate:{ type:'date', inp: 'inp' },
        joining_branch_id:{ type:'text', inp: 'sel', list: branchs, id: 'id' },
        company:{ type:'text', inp: 'sel', list: companies, id: 'id' },
        contracttype:{ type:'text', inp: 'sel', list: conttypes, id: 'code' },
        gender:{ type:'text', inp: 'sel', list: genders, id: 'id' },
        citizenship:{ type:'text', inp: 'sel', list: countries, id: 'code' },
        joining_dept_id:{ type:'text', inp: 'sel', list: depts, id: 'id' },
    }
    const addfilter = () => {
        searchdata.filters.push({
            'cond': 'a',
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
            if (!Array.isArray(data) || !data.length) throw new Error('Input must be a non-empty array');
            const headers = Object.keys(data[0]);
            const csvRows = [
                headers.join(','),
                ...data.map(row => {
                    return headers.map(header => {
                        const cell = row[header]?.toString() ?? '';
                        if (cell.includes(',') || cell.includes('\n') || cell.includes('"')) {
                            return `"${cell.replace(/"/g, '""')}"`;
                        }
                        return cell;
                    }).join(',');
                })
            ];
            const csvContent = csvRows.join('\n');
            const blob = new Blob(["\uFEFF" +csvContent], { type: 'text/csv;charset=utf-8;' });
            
            if (navigator.msSaveBlob) { 
                navigator.msSaveBlob(blob, filename);
            } else {
            const link = document.createElement('a');
            if (link.download !== undefined) {
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

    const changestatus = (id, event) => {
        
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
    <div class="h-full">
        <!-- Page Header -->
        <div class="mb-lg">
            <h1 class="text-2xl font-bold text-gray-800">Employee Management</h1>
            <p class="text-gray-500 mt-1 mb-md">Search and manage employee records</p>
        </div>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form @submit.prevent="filter">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 items-end">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Search Employee</label>
                        <input type="text" v-model="searchdata.employeeinfo" v-uppercase class="form-control" placeholder="Name or Staff ID">
                    </div>

                     <div class="col-span-2">
                         <label class="block text-sm font-bold text-gray-700 mb-2">Joining Date Range</label>
                         <div class="flex gap-4 items-center">
                            <input type="date" v-model="searchdata.createdfrom" class="form-control">
                            <span class="text-gray-400">to</span>
                            <input type="date" v-model="searchdata.createdto" class="form-control">
                         </div>
                    </div>
                </div>
                
                 <!-- Action Buttons & Badges -->
                <div class="flex justify-between items-center bg-gray-50 rounded-lg p-3 border border-gray-100">
                    <div class="flex gap-2">
                         <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg text-sm font-medium shadow-sm flex items-center gap-2" :disabled="loading">
                             <i v-if="loading" class="pi pi-spin pi-spinner"></i>
                             <span v-else>Filter Records</span>
                         </button>
                         <button type="button" @click="addfilter" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                            <i class="pi pi-sort-amount-down mr-1"></i> Advanced Filters
                         </button>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="exportDump" :disabled="exportingDump" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium shadow-sm hover:bg-green-700 transition-colors flex items-center gap-2">
                            <i v-if="exportingDump" class="pi pi-spin pi-spinner"></i>
                            <i v-else class="pi pi-file-excel"></i>
                            Export Data
                        </button>
                         <button v-if="loguser.position_id == 4" type="button" @click="exportChecklist" :disabled="exportingChecklist" class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm font-medium shadow-sm hover:bg-orange-600 transition-colors flex items-center gap-2">
                            <i v-if="exportingChecklist" class="pi pi-spin pi-spinner"></i>
                            <i v-else class="pi pi-check-circle"></i>
                            Checklist
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters Section -->
                <div id='advfilter' class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-100" v-if="searchdata.filters.length">
                    <h4 class="text-sm font-bold text-gray-700 mb-3 border-b border-gray-200 pb-2">Advanced Filtering</h4>
                    
                    <div v-for="(filter, index) in searchdata.filters" :key="index" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-3 items-end">
                        <div>
                             <label class="block text-xs font-bold text-gray-500 uppercase">Logic</label>
                            <select v-model="filter.cond" required class="form-control text-sm">
                                <option value="a">AND</option>
                                <option value="o">OR</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Field</label>
                            <select v-model="filter.attr" @change="attrchange(index)" required class="form-control text-sm">
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
                            <label class="block text-xs font-bold text-gray-500 uppercase">Operator</label>
                             <select v-model="filter.op" class="form-control text-sm">
                                <option value='is'>Is</option>
                                <option value="isnot">Is Not</option>
                                <option value="gt">Greater Than</option>
                                <option value="gtq">Equal or Greater Than</option>
                                <option value="lt">Less than</option>
                                <option value="ltq">Equal or Less than</option>
                                <option value="cont">Contains</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase" v-if="fil[filter.attr]">Value</label>
                            <div v-if="fil[filter.attr]">
                                 <select v-if="fil[filter.attr]?.inp == 'sel'" v-model="filter.val" required class="form-control text-sm">
                                    <option value=""></option>
                                    <option v-for="l in fil[filter.attr]?.list" :key="l[fil[filter.attr]?.id]" :value="l[fil[filter.attr]?.id]">
                                        {{  l.name }}
                                    </option>
                                </select>
                                <input v-if="fil[filter.attr]?.inp == 'inp'" :type="fil[filter.attr]?.type" v-model="filter.val" v-uppercase required class="form-control text-sm">
                            </div>
                        </div>
                        <div>
                            <button type="button" @click="removefilter(index)" class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition-colors" title="Remove Condition">
                                <i class="pi pi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Employee Table -->
        <div class="card bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
             <div class="card-header bg-white border-b border-gray-100 p-4 flex justify-between items-center">
                <span class="font-bold text-gray-800">Results: {{ totalcount }} employees</span>
            </div>
            
             <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs">
                        <tr>
                            <th class="px-6 py-4">Employee ID</th>
                            <th class="px-6 py-4">First Name</th>
                            <th class="px-6 py-4">Surname</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Created By</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                            <th v-if="loguser.position_id == 2" class="px-6 py-4">Admin Status</th>
                        </tr>
                    </thead>
                     <tbody class="divide-y divide-gray-100">
                        <tr v-for="(emp, ind) in emps" :key="ind" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-700">{{ emp.employeeid }}</td>
                            <td class="px-6 py-4">{{ emp.firstname }}</td>
                            <td class="px-6 py-4">{{ emp.surname }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ emp.email }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-bold uppercase">{{ findstatus(emp.status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ emp.creator.name }}</td>
                            <td class="px-6 py-4 text-right">
                                <router-link :to="`/employee/${emp.id}`" target="_blank" class="text-primary hover:text-primary-dark font-medium text-xs uppercase px-3 py-1 bg-purple-50 rounded hover:bg-purple-100 transition-colors">
                                    View Profile
                                </router-link>
                            </td>
                            <td v-if="loguser.position_id == 2" class="px-6 py-4">
                                <select v-model="emp.status" required @change="changestatus(emp.id, $event)" class="form-control text-xs py-1">
                                    <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </td>
                        </tr>
                        <tr v-if="!emps.length && loaded">
                            <td colspan="8" class="p-10 text-center text-gray-400">
                                <i class="pi pi-search text-2xl mb-2 block"></i>
                                No employees found matching your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
             </div>
             
             <!-- Pagination -->
             <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
                 <div class="flex items-center gap-2">
                     <span class="text-sm text-gray-500">Rows per page:</span>
                     <select v-model="per_page" @change="selectchange" class="form-control !w-auto !py-1 text-sm">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                 </div>
                 
                 <div class="flex items-center gap-4">
                     <button @click="prevpage()" :disabled="!isprev || loading" class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 disabled:opacity-50 text-sm font-medium">
                         Previous
                     </button>
                     <span class="text-sm text-gray-600 font-medium">Page {{ current_page }} of {{ last_page }}</span>
                     <button @click="nextpage()" :disabled="!isnext || loading" class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 disabled:opacity-50 text-sm font-medium">
                         Next
                     </button>
                 </div>
             </div>
        </div>
    </div>
</template>

<style scoped>
/* Scoped styles can be minimal now as we rely on global/bootstrap-like classes */
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #1f2937;
    background-color: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
.form-control:focus {
    border-color: var(--color-primary);
    outline: 0;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}
</style>