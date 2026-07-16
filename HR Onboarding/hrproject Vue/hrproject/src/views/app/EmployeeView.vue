<script setup>
    import { ref, reactive, computed, onMounted } from 'vue'
    import { log, calculateAge, toastt, numberToWords, aToday, aDate, imgburl} from '@/helpers/essential'
    import { useRoute, useRouter } from 'vue-router';
    import { findrelation, findmarital, findqual, finddept, findgender, findregion, findbranch, findbankaccounttype, findbank, findfamilyrelation, findcountry, findidtypes, findcompany, findconttypes, findstatus } from '@/data/masterdata'
    import axios from '@/helpers/pms_axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const router = useRouter()
    const route = useRoute()
    const { loguser } = userstore

    const empid = route.params.empid

    /* Variables declaration Start */
        let adding = ref(false) 
        let showsearchpopupvalue = ref(false)

        const today = aToday(new Date())
        
        let emplist = ref([])
        let empsearch = ref('')

        let emp = reactive({})
        let banksocial = reactive({})
        let irrguar = reactive({})
        let soccont = reactive({})
        
        let iscurwork = ref(false)
        let isunion = ref()
        let curwork = reactive({})
        let unioninfo = reactive({})
        let nominee = reactive({});
        let dlicense = reactive({});
        let wpermit = reactive({});
        
        let haschildren = ref(false)
        let childrens = ref([]);

        let econts = ref([]);
        let edus = ref([])
        let workexps = ref([])
        let refs = ref([])
        let guarantors = ref([])
        let wives = ref([]);

        let irrguarwitnesses = ref([])

        let fileName = ref()
        let file = ref([])
    /* Variables declaration End */

    const eczone = computed(() => {
        if(emp.citizenship == 'GH') return 'GH'
        else if(['NG','NE','GM','BJ','BF', 'SN', 'CV', 'GN', 'TG', 'SL','CI'].includes(emp.citizenship)) return 'EC'
        else return 'OT'
    })

    onMounted(() => {
        console.log("--- DEBUGGER --- imgburl:", imgburl);
        
        axios.post('employee',{
                data: empid
            }).then(res => {
                const data = res.data
                Object.assign(emp, data)
                Object.assign(unioninfo, data.unioninfo || {})
                Object.assign(wpermit, data.workpermit || {})
                Object.assign(dlicense, data.driverlicense || {})
                Object.assign(nominee, data.nominee || {})
                childrens.value = data.childrens

                soccont = data.soccontact || {}
                curwork = data.presentjob

                edus.value = data.educations
                childrens.value = data.childrens
                wives.value = data.wives
                econts.value = data.econs
                workexps.value = data.workexps
                guarantors.value = data.guarantos
                refs.value = data.refs

                banksocial = data.banksocial || {}
                irrguar = data.irrguarantor || {}
                irrguarwitnesses.value = data.irrguarantor?.witnesses
                

                iscurwork.value = data.presentjob ? true : false
                isunion.value = data.unioninfo ? true : false
                haschildren.value = data.childrens?.length ? true : false
                log(data)
                
            })

    })

    const getImgUrl = (path, folder) => {
        if (!path) return '';
        return path.includes('/') ? `${imgburl}${path}` : `${imgburl}${folder}/${path}`;
    }

</script>
<template>
    <div style="display: flex; flex-direction: column;gap:50px">
        <h2>{{ emp.firstname }} - {{ emp.emp_code }}</h2>

        <div>
            <div>

                <div class="group" v-if="emp.id">
                    <h3>Personal Information</h3>
                    <div>
                        <!-- <div class="one">
                            <label for="">Profile Picture:</label>
                            <img
                                v-if="emp.id"
                                :src="`${imgburl}${emp.profilepicture[0]?.path}`"
                                width="150px"
                            >

                            <div>
                                <strong>Status</strong>
                                <span>{{ findstatus(emp.status) }}</span>
                            </div>
                        </div> -->

                        <div class='double' >
                            <div>
                                <label for="">Profile Picture:</label>
                                <img
                                    v-if="emp.id && emp.profilepicture && emp.profilepicture.length && emp.profilepicture[0]?.path"
                                    :src="`http://192.168.0.20:5050/pms_backend/api/file/profilepicture/` + (emp.profilepicture[0].path.includes('/') ? emp.profilepicture[0].path.split('/').pop() : emp.profilepicture[0].path)"
                                    width="150px"
                                >
                                
                            </div>
                            <div>
                                <strong>Status:</strong>
                                <span class="ps-3">{{ findstatus(emp.status) }}</span>
                            </div>
                        </div>
                        
                        <div class="triple">
                            <div>
                                <label for="firstname">First Name:</label>
                                <span>{{ emp.firstname }}</span>
                            </div>
                            <div>
                                <label for="middlename">Middle Name:</label>
                                <span>{{ emp.middlename }}</span>
                            </div>
                            <div>
                                <label for="surname">SurName:</label>
                                <span>{{ emp.surname }}</span>
                            </div>
                            
                        </div>

                        <div class='double' >
                            <div>
                                <label for="">Citizenship:</label>
                                <span>{{ findcountry(emp.citizenship) }}</span>
                                
                            </div>
                            <div v-if="eczone == 'EC'">
                                <label for="">ID Type:</label>
                                <span>{{ findidtypes(emp.idtype) }}</span>
                                
                            </div>
                        </div>

                        <div class='double' v-if="eczone == 'GH'">
                            <div>
                                <label for="">Ghana Card No:</label>
                                <span>{{ emp.ghcardno }}</span>
                                
                            </div>
                            <div>
                                <label for="">Ghana Card Picture:</label>
                                <span style="display: inline-flex;gap:5px">
                                    <a v-for="card in emp.ghcard" :key="card.id" target="_blank" :href="`${imgburl}${card.path}`">view</a>
                                </span>
                                
                            </div>
                        </div>

                        <div class='double' v-if="eczone == 'EC' && emp.idtype ">
                            <div>
                                <label for="ghcardno">{{ findidtypes(emp.idtype) }} No :</label>
                                <span>{{ emp.ghcardno }}</span>
                            </div>
                            <div>
                                <label for="">{{ findidtypes(emp.idtype) }} Picture :</label>
                                <a v-for="card in emp.ghcard" :key="card.id" target="_blank" :href="`${imgburl}${card.path}`">view</a>
                            </div>
                        </div>

                        <div class='double' v-if="eczone == 'OT'">
                            <div>
                                <label for="">Passport No:</label>
                                <span>{{ emp.ghcardno }}</span>
                                
                            </div>
                            <div>
                                <label for="">Passport Picture:</label>
                                <span style="display: inline-flex;gap:5px">
                                    <a v-for="card in emp.ghcard" :key="card.id" target="_blank" :href="`${imgburl}${card.path}`">view</a>
                                </span>
                                
                            </div>
                        </div>

                        <div class="one">
                            <label >Residential Address/Landmark:</label>
                            <span>{{ emp.raddress }}</span>
                        </div>
                        <div class="one">
                            <label>Residential Ghana Digital Address:</label>
                            <span>{{ emp.daddress }}</span>
                        </div>
                        
                        <div class="double">
                            <div>
                                <label>Permanent Home Town/Region:</label>
                                <span>{{ emp.hometown }}</span>
                            </div>
                            <div>
                                <label>Permanent Ghana Digital Address:</label>
                                <span>{{ emp.hdaddress }}</span>
                            </div>
                        </div>

                        <div class="double">
                            <div>
                                <label>Mobile Number:</label>
                                <span>{{ emp.mobileno }}</span>
                            </div>
                            <div>
                                <label>Alternate Phone:</label>
                                <span>{{ emp.altnumber }}</span>
                            </div>
                        </div>

                        <div class="triple">
                            <div>
                                <label>Email Address:</label>
                                <span>{{ emp.email }}</span>
                            </div>

                            <div>
                                <label>Gender:</label>
                                <span>{{ findgender(emp.gender) }}</span>
                            </div>

                            <div>
                                <label>Social Security Number:</label>
                                <span>{{ emp.socialsecurityno }}</span>
                            </div>
                        </div>
                        <div class="triple">
                            <div>
                                <label>Birth Date:</label>
                                <span>{{ emp.dob }}</span>
                                
                            </div>
                            <div>
                                <label>Age: </label>
                                <span>{{ calculateAge(emp.dob) }}</span>
                            </div>
                            <div>
                                <label>Marital Status:</label>
                                <span>{{ findmarital(emp.maritalstatus) }}</span>
                                
                            </div>
                        </div>

                        <div
                            v-if="emp.maritalstatus == 2"
                            style='border:1px solid #00000033'
                            class="p-2 my-4 shadow-md border-1 border-indigo-950"
                        >

                            <h4 class='my-2'>Spouses information</h4>

                            <div v-for="(wife, index) in wives" :key="index" class="inline">
                                <div>
                                    <label>Spouse {{ (index + 1) }}'s  Name :</label>
                                    <span>{{ wife.name }}</span>
                                    
                                </div>
                                <div>
                                    <label>Occupation *:</label>
                                    <span>{{ wife.occupation }}</span>
                                    
                                </div>
                                
                            </div>

                        </div>


                        <div class='double' >
                            <div>
                                <label >Father's Name:</label>
                                <span>{{ emp.fathersname }}</span>
                            </div>
                            <div>
                                <label >Mother's Name:</label>
                                <span>{{ emp.mothersname }}</span>
                            </div>
                        </div>

                        <div v-if="emp.relativeinorg">
                            <h4>Relative in Melcom</h4>
                            <div class='double' >
                                <div>
                                    <label >Relative's Name:</label>
                                    <span>{{ emp.relative_name }}</span>
                                </div>
                                <div>
                                    <label >Relative's Type:</label>
                                    <span>{{ findrelation(emp.relative_relation) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="haschildren">
                            <h4>Children</h4>
                            <div v-for="(child, index) in childrens" :key="index" class="inline">
                                <div>
                                    <label>Child {{ (index + 1) }}'s  Name:</label>
                                    <span>{{ child.name }}</span>
                                </div>
                                <div>
                                    <label>Age:</label>
                                    <span>{{ child.age }}</span> 
                                </div>
                                
                            </div>
                        </div>

                        <div class="group">
                            <h3>Melcom Employee Position Details</h3>

                            <div class="inlinewrap">
                                

                                <div>
                                    <label>Joining Position:</label>
                                    <span>{{ emp.joiningposition }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Date of Joining:</label>
                                    <span>{{ emp.joiningdate }}</span> 
                                </div>
                                
                                <div>
                                    <label>Joining Location:</label>
                                    <span>{{ findbranch(emp.joining_branch_id) }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Joining Department:</label>
                                    <span>{{ finddept(emp.joining_dept_id) }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Joining Department:</label>
                                    <span>{{ findcompany(emp.company) }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Contract Category:</label>
                                    <span>{{ emp.contracttype ? findconttypes(emp.contracttype) : '' }}</span> 
                                    
                                </div>
  
                            </div>
                            
                        </div>

                        <div class="group" v-if="emp.contracttype == 'expat'">
                            <h3>Work Permit Details</h3>

                            <div class="inlinewrap">

                                <div>
                                    <label>Number Of Renewal:</label>
                                    <span>{{ wpermit.renewalno }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Passport Expiry Date:</label>
                                    <span>{{ wpermit.passedate }}</span> 
                                    
                                </div>
                                <div>
                                    <label>Work Permit Date:</label>
                                    <span>{{ wpermit.idate }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Work Permit Expiry Date:</label>
                                    <span>{{ wpermit.edate }}</span> 
                                    
                                </div>

                                <div>
                                    <label>Work Permit Number:</label>
                                    <span>{{ wpermit.number }}</span> 
                                    
                                </div>

                            </div>
                        </div>

                        <div class="group" v-if="emp.joiningposition == 'DRIVER'">
                            <h3>Driver's Licence Details</h3>

                            <div class="inlinewrap">
                                <div>
                                    <label>Name on the license:</label>
                                    <span>{{ dlicense.name }}</span> 
                                </div>

                                <div>
                                    <label>license#:</label>
                                    <span>{{ dlicense.licenseno }}</span> 
                                </div>
                                
                                <div>
                                    <label>Issuing Date:</label>
                                    <span>{{ dlicense.idate }}</span> 
                                </div>

                                <div>
                                    <label>Expiry Date:</label>
                                    <span>{{ dlicense.edate }}</span> 
                                </div>

                                <div>
                                    <label>Ref#*:</label>
                                    <span>{{ dlicense.ref }}</span> 
                                </div>

                                <div>
                                    <label>Renewal  Date:</label>
                                    <span>{{ dlicense.rdate }}</span> 
                                </div>

                                <div>
                                    <label for="">License Picture:</label>
                                    
                                    <a
                                        v-for="file in dlicense.files"
                                        target="_blank"
                                        :key="file.id"
                                        :href="`${imgburl}${file.path}`"
                                        v-if="dlicense.id" 
                                    >view</a>
                                </div>
                                
                            </div>
                        </div>

                        <div class="group" v-if="iscurwork">
                            <h3> Job at Melcom</h3>
                            
                            <div v-if="iscurwork">
                                <h4>Details of <!-- Present --> Job at Melcom</h4>

                                <div class="inlinewrap">
                                    <div>
                                        <label>Title:</label>
                                        <span>{{ curwork.title }}</span> 
                                        
                                    </div>
                                    <div>
                                        <label>Employee ID:</label>
                                        <span>{{ curwork.employeeid }}</span> 
                                        
                                    </div>
                                    <div>
                                        <label>Date of Joining:</label>
                                        <span>{{ curwork.doj }}</span> 
                                        
                                    </div>
                                    <div>
                                        <label>Department:</label>
                                        <span>{{ finddept(curwork.dept_id) }}</span> 
                                        
                                    </div>
                                    <div>
                                        <label>Branch:</label>
                                        <span>{{ findbranch(curwork.branch_id) }}</span> 
                                        
                                    </div>
                                    <div>
                                        <label>Region:</label>
                                        <span>{{ findregion( curwork.region_id) }}</span> 
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3>ANY OTHER INFORMATION</h3>
                            <div>
                                {{ emp.anyotherinfo }}
                            </div>
                        </div>
                            
                    </div>
                </div>
            </div>
            <div class="group" v-if="isunion">
                <h3>Union membership information</h3>
                <div >
                    <h4>Details of Membership</h4>
                    <div class="inlinewrap">
                        
                        <div>
                            <label>Union name:</label>
                            <span>{{ unioninfo.union_name }}</span> 
                            
                        </div>
                        <div>
                            <label>Union Registration date:</label>
                            <span>{{ unioninfo.union_regdate }}</span> 
                            
                        </div>
                        
                        <div>
                            <label>Union Registration Number:</label>
                            <span>{{ unioninfo.union_number }}</span> 
                            
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="group">
                    <h3>Emergency Contact Information [Relatives Only]</h3>

                    
                    <div v-for="(econt,index) in econts" :key="index">

                        
                        <h4>CONTACT NO{{ index + 1 }}</h4>
                        <div class="inlinewrap">
                            <div>
                                <label >Full Name:</label>
                                <span>{{ econt.fullname }}</span>
                                
                            </div>
                            <div>
                                <label>Relationship to you:</label>
                                <span>{{ findfamilyrelation(econt.relation) }}</span>
                                
                            </div>
                            <div>
                                <label >Work Address preferable:</label>
                                <span>{{ econt.workaddress }}</span>
                                
                            </div>
                            <div>
                                <label >Ghana Card No:</label>
                                <span>{{ econt.ghcardno }}</span>
                                
                            </div>
                            <div>
                                <label for="">Ghana Card:</label>
                                <a v-for="file in econt.files" target="_blank" :key="file.id" :href="`${imgburl}${file.path}`">view</a>
                            </div>
                            <div>
                                <label>Mobile Number:</label>
                                <span>{{ econt.mobileno }}</span>
                            </div>
                            <div>
                                <label>Alternate Phone:</label>
                                <span>{{ econt.altnumber }}</span>
                                
                            </div>

                            <div>
                                <label>Next of kin?</label> 
                                <span>{{ econt.isnextofkin ? 'Yes':'No' }}</span>
                                
                            </div>
                        </div>
                        
                    </div>

                </div>

                <div class="group">
                    <h3>Social Contact Information</h3>
                    <div>
                        <div class="inlinewrap">
                            <div>
                                <label >Church / Mosque Membership & Location:</label>
                                <span>{{ soccont.churchmemberloc }}</span>
                            </div>
                            
                            <div>
                                <label>Pastor Religious Leader:</label>
                                <span>{{ soccont.pastor }}</span>
                            </div>
                            <div>
                                <label >Duration of Membership:</label>
                                <span>{{ soccont.memduration }}</span>
                            </div>
                            <div>
                                <label>Mobile Number:</label>
                                <span>{{ soccont.mobileno }}</span>
                            </div>
                            <div>
                                <label>Ghana Card No:</label>
                                <span>{{ soccont.ghcardno }}</span>
                            </div>

                            <div>
                                <label for="">Closest Friend/Confidant:</label>
                                <span>{{ soccont.closestfriend }}</span>
                            </div>

                            <div>
                                <label for="">Contact Phone:</label>
                                <span>{{ soccont.contactsphone }}</span>
                            </div>

                            <div>
                                <label >Work Address preferable:</label>
                                
                                <span>{{ soccont.workaddress }}</span>
                            </div>

                            <div>
                                <label for="">Email Address:</label>
                                <span>{{ soccont.email }}</span>
                            </div>

                            <div>
                                <label >Digital Address:</label>
                                
                                <span>{{ soccont.digitaladdress }}</span>
                            </div>
                            
                        </div>
                    </div>

                </div>

               
            </div>
                
            <div>
                <div class="group">
                    <h3>Educational Background</h3>
                    <div>
                        <div v-for="(edu, index) in edus" :key="index" class="inline">
                            
                            <div>
                                <label for="">Qualification Type:</label>
                                <span>{{ findqual(edu.educqualtype) }}</span>
                                
                            </div>

                            <div>
                                <label>Specialization:</label>
                                <span>{{ edu.educqual }}</span>
                                
                            </div>

                            <div>
                                <label >Date Of Completion:</label>
                                <span>{{ edu.to }}</span>
                                
                            </div>

                            <div>
                                <label>Educational Qualification:</label>
                                <span style="display: inline-flex;gap:5px">
                                    <a v-for="file in edu.files" :key="file.id" target="_blank" :href="`${imgburl}${file.path}`">view</a>
                                </span>
                            </div>

                            

                        </div>
                    </div>


                    <!-- <div>
                        Are you a borrower of the Student Loan Trust Fund?
                        <span>
                            {{ emp.studentloan ? 'Yes' : 'No' }}
                            
                        </span>
                    </div> -->
                </div>

                <div class="group">
                    <h3>WORK EXPERIENCE</h3>

                    <div >
                        <div v-for="(wexp, ind) in workexps" :key="ind" class="inline">
                            <div>
                                <label >Name of Organization:</label>
                                <span>{{ wexp.orgname }}</span>
                                
                            </div>
                            
                            <div>
                                <label>Post held:</label>
                                <span>{{ wexp.postheld }}</span>
                            </div>
                            <div>
                                <label >From:</label>
                                <span>{{ wexp.from }}</span>
                            </div>

                            <div>
                                <label >To:</label>
                                <span>{{ wexp.to }}</span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="group">
                    <h3>REFERENCES</h3>

                    
                    <div v-for="(ref , ind) in refs" :key="ind" >
                        <h4>REFERENCE 1</h4>
                        <div class="inline">
                            <div>
                                <label >Name :</label>
                                <span>{{ ref.name }}</span>
                                
                            </div>
                            
                            <div>
                                <label>Company Name:</label>
                                <span>{{ ref.companyname }}</span>
                                
                            </div>

                            <div>
                                <label>Designation:</label>
                                <span>{{ ref.designation }}</span>
                                
                            </div>
                            <div>
                                <label>Contact Number:</label>
                                <span>{{ ref.contactno }}</span>
                            </div>
                            <div>
                                <label>Email:</label>
                                <span>{{ ref.email }}</span>
                            </div>
                            
                        </div>
                    </div>


                    
                </div>
                
            </div>

            <!-- <div>
                <div class="group">
                    <h3>WITNESSES (MIN 2)</h3>

                    
                    <div v-for="(g , ind) in guarantors" :key="ind" >
                        <h4>WITNESS {{ ind + 1 }}</h4>
                        <div class="inline">
                            <div>
                                <label >Name :</label>
                                <span>{{ g.name }}</span>
                            </div>
                            
                            <div>
                                <label>Residential address:</label>
                                <span>{{ g.address }}</span>
                            </div>

                            <div>
                                <label>Email:</label>
                                <span>{{ g.email }}</span>
                            </div>

                            <div>
                                <label>Mobile No:</label>
                                <span>{{ g.mobileno }}</span>
                            </div>

                            
                            
                            <div>
                                <label>ID:</label>
                                <a v-for="file in g.files" target="_blank" :key="file.id" :href="`${imgburl}${file.path}`">view</a>
                            </div>
                            <span v-if="!emp.id">
                                
                                <button @click="removeguar(ind)">-</button>
                                <button v-if="ind === guarantors.length - 1" @click="addguar">+</button>
                            </span>
                        </div>
                    </div>


                    
                </div>
            </div> -->
            <div class="group">
                    <h3>Nominee Information </h3>
                    
                <div class="inlinewrap">
                    <div>
                        <label >Name:</label>
                        <span>{{ nominee.name }}</span> 
                        
                    </div>

                    <div>
                        <label>Relationship to you:</label>
                        <span>{{ nominee.relation ? findfamilyrelation(nominee.relation) : '' }}</span> 
                    </div>
                    
                    <div>
                        <label>Mobile Number:</label>
                        <span>{{ nominee.mobile }}</span> 
                        
                    </div>
                    
                    <div>
                        <label for="">ID :</label>
                        
                        <a
                            v-for="file in nominee.files"
                            target="_blank"
                            :key="file.id"
                            :href="`${imgburl}${file.path}`"
                            v-if="nominee.id" 
                        >view</a>
                    </div>
                    

                    
                </div>
                
            </div>
            <div>
                <div class="group">
                    <h3>EMPLOYEE'S DECLARATION</h3>

                    <div>
                        <p>
                            I <strong> {{ emp.fullname }}</strong> declare that the information provided above is true. In case of false declaration, the appropriate action as per company’s policy will be taken.
                        </p>

                        <p>
                            Signature: 
                            <div v-if="emp.id">
                                <img v-if="emp.signature" :src="`${imgburl}${emp.signature.path}`" width="200" >
                            </div>
                            
                            Date: <strong> {{ aDate(emp.created_at)  }} </strong>
                        </p>

                        <p>
                            Guarantor Signature: 
                            <div v-if="emp.id">
                                <img v-if="emp.guarsignature?.path" :src="`${imgburl}${emp.guarsignature.path}`" width="200" >
                            </div>
                            
                            
                        </p>

                        
                    </div>
                    
                </div>
            </div>
        </div>

        <div>
            <h2>Bank / Social Security Fund Contribution Details</h2>

            <div v-if="banksocial.id" class="group">
                <h3>Information</h3>
                <div class='double' >
                    <div>
                        <label for="">Name of Bank:</label>
                        <span>{{ findbank(banksocial.bankname) }}</span>
                        
                    </div>
                    <div>
                        <label for="">Bank Branch:</label>
                        <span>{{ banksocial.bankbranch }}</span>
                        
                    </div>
                </div>
                <div class='double' >
                    <div>
                        <label for="">Account Name:</label>
                        <span>{{ banksocial.accountname }}</span>
                    </div>
                    <div>
                        <label for="">Account Type:</label>
                        <span>{{ findbankaccounttype(banksocial.accounttype) }}</span>
                        
                    </div>
                </div>
                <div class='double' >
                    <div>
                        <label for="">Account Number:</label>
                        <span>{{ banksocial.accountnumber }}</span>
                    </div>
                    <div>
                        <label for="">Social Security Fund Number:</label>
                        <span>{{ banksocial.socialfundnumber }}</span>
                    </div>
                </div>

                
            </div>
        </div>

        <div >

            <h2>Declaration Document Attachment of irrevocable and Witness Sign</h2>
            <div v-if="irrguar.id">
                <!-- <div class="steper">
                    Page 
                    <span class="active">{{ irrguarstep }}</span> / <span>3</span>
                </div> -->

                <div class="group">
                    <h3>Information Form</h3>

                    
                    <div class="one" v-if="irrguar.id && irrguar.profilepicture?.path">
                        <img
                            :src="`${imgburl}${irrguar.profilepicture.path}`"
                            width="150px"
                        >
                    </div>

                    <!-- <div class='one'>
                        <label>ID Card:</label>
                        
                    </div> -->

                    <div>
                        <label for="">Guarantor Form:</label>
                        <span style="display: inline-flex;gap:5px">
                            <a v-for="f in irrguar.guarforms" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </span>
                        
                    </div>

                    <div class='double' >
                        <div>
                            <label for="">Name of Guarantor:</label>
                            <span>{{ irrguar.guarname }}</span>
                            
                        </div>
                        <div>
                            <label for="">SSF No:</label>
                            <span>{{ irrguar.ssfno }}</span>
                        </div>
                    </div>
                    <div class='double' >
                        <div>
                            <label for="">Annual Income:</label>
                            <span>{{ irrguar.annualincome }}</span>
                        </div>
                        <div>
                            <label for="">Value of Landed Property:</label>
                            <span>{{ irrguar.propertyvalue }}</span>
                        </div>
                    </div>
                    <div class='double' >
                        <div>
                            <label for="">Primary Email:</label>
                            <span>{{ irrguar.primary_email }}</span>
                        </div>
                        <div>
                            <label for="">Secondary Email:</label>
                            <span>{{ irrguar.secondary_email }}</span>
                        </div>
                    </div>
                    <div class='double' >
                        <div>
                            <label for="">Occupation/Position:</label>
                            <span>{{ irrguar.occupation }}</span>
                        </div>
                        <div>
                            <label for="">Tel/Mob. No:</label>
                            <span>{{ irrguar.mobileno }}</span>
                        </div>
                    </div>

                    <div class='double' >
                        <div>
                            <label for="">Alternate Tel/Mob. No:</label>
                            <span>{{ irrguar.alternate_mobileno }}</span>
                        </div>
                        <div>
                            <label for="">Region:</label>
                            <span>{{ irrguar.region_id ? findregion(irrguar.region_id) : '' }}</span>
                        </div>
                    </div>

                    <div class='one' >
                        <label for="">Business Address:</label>
                        <span>{{ irrguar.businessaddr }}</span>
                        
                    </div>
                    <div class='one' >
                        <label for="">Residential Address:</label>
                        <span>{{ irrguar.residenceaddr }}</span>
                        
                    </div>

                    <div class='double' >
                        <div>
                            <label for="">Ghana Card Number:</label>
                            <span>{{ irrguar.ghcard }}</span>
                        </div>
                        <div>
                            <label for="">Digital Address:</label>
                            <span>{{ irrguar.digitaladdr }}</span>
                        </div>
                    </div>

                    <div class='double' >
                        <div>
                            <label>Relationship with Applicant:</label>
                            <span>{{ findrelation(irrguar.relation) }}</span>
                        </div>
                        <div>
                            <label for="">Duration of Relationship:</label>
                            <span>{{ irrguar.relationyear }}</span>
                        </div>
                    </div>

                    <div class='double' >
                        <div>
                            <label for="">Secondary Contact name:</label>
                            <span>{{ irrguar.secondary_contact_person_name }}</span>
                        </div>
                        <div>
                            <label for="">Secondary Contact Number:</label>
                            <span>{{ irrguar.secondary_contact_person_number }}</span>
                        </div>
                    </div>

                    <div>
                        The guarantor should also submit with evidence of citizenship and identity any of the following: Driver’s License /Passport/Voter’s ID card/ Employment ID Card. The guarantor must submit in person to the Head Office of the Company or any other office authorized to receive such forms with Two (2) current Passport picture.
                    </div>

                    
                </div>

                <div class="group">
                    <h3>Declaration</h3>
                        
                    <div id="declarationdiv">
                        <p>
                            I <strong>{{ irrguar.guarname }}</strong> with telephone number(s) <strong>{{ irrguar.mobileno }}</strong> <strong v-if
                            ='irrguar.alternate_mobileno'> / {{ irrguar.alternate_mobileno }}</strong>,  an employee of  <strong>{{ irrguar.company }}</strong> and presently residing at <strong>{{ irrguar.residenceaddr }}</strong> in the <strong>{{ irrguar.region_id ? findregion(irrguar.region_id) : '' }}</strong> region of the Republic of Ghana voluntarily presents myself as a guarantor.
                        </p> 
                        <p>
                            I understand and verily believe same to be true that you have employed <strong>{{ emp.firstname+' '+emp.surname }}</strong> as a <strong>{{ emp.joiningposition }}</strong> in your organization and clearly understand my obligations thereof .
                        </p>

                        <p>
                            I have known the above named person for <strong>{{ irrguar.relationyear }}</strong> years and do hereby consent to standing in as a guarantor to secure the organization against any losses that may accrue due to his/her actions, omissions, fraud, dishonesty, malfeasance or other(s) as the case may be to the tune of <strong>{{ irrguar.guaramount }}</strong>  (In words) <strong > {{ irrguar.guaramount ? numberToWords(irrguar?.guaramount) : '' }} </strong> Ghana Cedis.
                        </p>

                        <p>
                            Any claims made under this guarantee must be sent (without limitations via any reachable means) and received by me accompanied by a signed statement indicating that the abovementioned employee has failed in fulfilling his / her obligations which has ensued losses to your organization.
                        </p>
                        <p>
                            Such statement and claim shall be conclusive evidence of the amount being claimed under this guarantee and as such I and the above named employee waive all rights against the organization of any suits or actions whatsoever and howsoever.   
                        </p>

                    </div>

                    
                </div>

                <div  class="group">
                    <h3>Signature</h3>
                    <div >
                        <p>
                            <strong>Signed on this DAY OF {{ emp.id ? aDate(emp.created_at) : today }} </strong>

                            <div style="margin: 10px 0px;">
                                
                                <div v-if="irrguar.id">
                                    <img v-if="irrguar.signature" :src="`${imgburl}${irrguar.signature.path}`" width="200" >
                                </div>

                                
                                
                            </div>
                            <!-- Signature of Guarantor<br>
                            In the presence of -->
                            
                        </p>
                    </div>

                    <div class="group">
                        <h3>WITNESSES</h3>

                        <div >
                            <div v-for="(witness, ind) in irrguarwitnesses" :key="ind" >
                                <h3>Witness {{ ind + 1 }}</h3>
                                <div class="inline">
                                    <div>
                                        <label >Name:</label>
                                        <span>{{ witness.name }}</span>
                                    </div>
                                    
                                    <div>
                                        <label>Address:</label>
                                        <span>{{ witness.address }}</span>
                                    </div>
                                    <div>
                                        <label >Phone No:</label>
                                        <span>{{ witness.phoneno }}</span>
                                    </div>

                                </div>
                                
                            </div>
                        </div>

                    </div>

                </div>

            </div> 
        </div>

        <div >
            <h2> Employee checklist</h2>

            <div  id='empchecklistdiv'>
                
                <div style="border-top: 1px solid black;">
                    <strong>Profile Form Filled</strong>
                    <span>
                        Done
                    </span>
                </div>
                <div>
                    <strong>2 Passport size pictures</strong>
                    <span>{{ emp.ghcard ? 'Done' : '' }}</span>
                </div>
                <div>
                    <strong>Application Letter</strong>
                    <span>
                        <template v-if="emp?.appletters?.length">
                            
                            <a v-for="f in emp.appletters" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                            
                        </template>
                    </span>
                </div>

                <div>
                    <strong>Appointment Letter</strong>
                    <span>
                        <template v-if="emp?.appointmentletters?.length">
                            <a v-for="f in emp.appointmentletters" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>Probation Confirmation Detail</strong>
                    <span>
                        <template v-if="emp?.probationconfs?.length">
                            <a v-for="f in emp.probationconfs" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>CV</strong>
                    <span>
                        <template v-if="emp?.cv?.length">
                            <a v-for="f in emp.cv" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>Petra Trust form filled</strong>
                    <span>
                        <template v-if="emp?.petratrust?.length">
                            <a v-for="f in emp.petratrust" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>National Identification Card 2 Coloured Copies (GHANA CARD)</strong>
                    <span>{{ emp.ghcard ? 'Done' : '' }}</span>
                </div>

                <div>
                    <strong>Guarantor Forms (Guarantor ID Card, etc.)</strong>
                    <span>{{ irrguar.id ? 'Done' : '' }}</span>
                    
                </div>

                <div>
                    <strong>Bank / Social Security Fund</strong>
                    <span>{{ banksocial.id ? 'Done' : '' }}</span>
                </div>

                <div>
                    <strong>NHIS Card Copy </strong>
                    <span>
                        <template v-if="emp?.nhis?.length">
                            
                            <a v-for="f in emp.nhis" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>Birth Certificate Copy  </strong>
                    <span>
                        <template v-if="emp?.birthcert?.length">
                            <a v-for="f in emp.birthcert" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>Police Clearance form for Drivers (exclusively for drivers, security officers, Shop Managers, Sales Managers/Executives.)</strong>
                    <span>
                        <template v-if="emp?.pclearanceform?.length">
                            <a v-for="f in emp.pclearanceform" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>

                <div>
                    <strong>SSNIT Card Copy  </strong>
                    <span>
                        <template v-if="emp?.ssnit?.length">
                            <a v-for="f in emp.ssnit" :key="f.id" target="_blank" :href="`${imgburl}${f.path}`">view</a>
                        </template>
                        
                    </span>
                </div>
            </div>
        </div>

        <!-- <div>
            <div>
                <div>
                    <strong>Profile form filled</strong>
                    <span>{{ emp.id ? 'Yes' : 'No' }}</span>
                </div>
                <div>
                    <strong>2 Passport size Pictures</strong>
                    <span></span>
                </div>
                <div>
                    <strong>Apllication Letter</strong>
                    <span></span>
                </div>
                <div>
                    <strong>CV</strong>
                    <span></span>
                </div>
                <div>
                    <strong>Copies Of Applicant Certificates</strong>
                    <span></span>
                </div>
            </div>
        </div> -->
        

    </div>
</template>
<style scoped>
    

    /* #topheader{
        display: none !important;
    } */

    nav{
        display: none !important;
    }

    #empchecklistdiv {
        display: flex;
        flex-direction: column;


        & > div{
            display: flex;
            border-bottom:1px solid black;
            border-left: 1px solid black;
            border-right: 1px solid black;

            & > strong{
                flex: 1;
                padding:15px;
            }
            & > span{
                border-left: 1px solid black;
                width: 150px;
                padding:15px;
                display: inline-flex;
                gap:5px;


            }
        }
    }
    label:not(.radio){
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    .one{
        margin-bottom: 15px;
        border-bottom: 2px solid #80808040;
        padding-bottom: 10px;
    }
    .double{
        display:flex;
        gap:10px;
        margin-bottom: 15px;
    }
    .double > div{
        flex:1;
        border-bottom: 2px solid #80808040;
        padding-bottom: 10px;
        
    }
    .triple{
        display:flex;
        gap:10px;
        margin-bottom: 15px;
    }
    .triple > div{
        flex:1;
        border-bottom: 2px solid #80808040;
        padding-bottom: 10px;
    }
    .group > button, .inbutton{
        padding: 5px 20px;
        cursor: pointer;

    }
    .inline{
        display: flex;
        /* align-items: end; */
        gap:10px;
        margin-bottom: 15px;
        
    }
    .inline > div{
        flex: 1;
        border-bottom: 2px solid #80808040;
        padding-bottom: 10px;
    }
    .inline > span button{
        width: 30px;
        height: 30px;
        margin-right: 10px;
        cursor: pointer;
    }
    .inlinewrap {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: end;
    }
    .inlinewrap > div {
        width: calc(50% - 5px);
        border-bottom: 2px solid #80808040;
        padding-bottom: 10px;
    }
    .inlinewrap ~ span{
        display: block;
        margin-top: 10px;
        text-align: right;
    }
    .inlinewrap ~ span button{
        padding: 5px 10px;
        margin-right: 10px;
    }
    h3,  h5{
        margin:0;
    }
    h4{
        margin:5px 0px;
    }
    /* .group > *:not(h3){
        margin:0px 5px;
        margin-top: 10px;
    }
    .group{
        border:1px solid grey;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }
    .group > h3{
        background-color: lightgray;
        padding: 7px;
        text-align: center;
    }
    .group > div{
        padding:10px;
    } */
</style>