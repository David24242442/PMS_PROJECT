<script setup>

    import { ref, reactive, computed, onMounted } from 'vue'
    import { log, calculateAge, toastt} from '@/helpers/essential'
    import axios from 'axios';
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { loguser, getloguser, authtoken, getauthtoken } = userstore

    const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer

    onMounted(() => {

    })

    /* Master data declaration Start*/
        const mstatus = [{id:1,name:'Single'},{id:2,name:'Married'},{id:3,name:'Divorced'},{id:4,name:'Not disclosed'},{id:5,name:'Others'}]
        const bankaccounttype = [{id:1,name:'Savings'},{id:2,name:'Current'}]
        const relations = [{id:1,name:'Friend'},{id:2,name:'Family'}]
        const depts = [{id:1,name:'HR'},{id:2,name:'ACCOUNTS'},{id:3,name:'DIGITAL COMMERCE'}]
        const regions = [{id:1,name:'Greater Accra', code:'GA'},{id:2,name:'Volta Region', code:'VR'}]
        const branchs = [{id:1,name:'Head  Office', code:'HO'},{id:2,name:'Melcom Plus Kaneshie', code:'LFS'},{id:3,name:'Melcom Achimota', code:'ACH'}]

        const qualtypes = [{id:1,name:'Schooling'},{id:2,name:'Diploma/HND/Undergraduate'},{id:3,name:'Graduate'}, {id:4,name:'Post graduate'}, {id:5,name:'PHD'}]
    /* Master data declaration End */
    
    /* Variables declaration Start */
        let adding = ref(false) 
        let emp = reactive({})
        let banksocial = reactive({})
        let irrguar = reactive({})
        let soccont = reactive({})
        

        let iscurwork = ref(false)
        let curwork = reactive({})
        
        let haschildren = ref(false)
        let childrens = ref([]);

        let econts = ref([]);
        let edus = ref([])
        let workexps = ref([])
        let refs = ref([])
        let guarantors = ref([])

        let fileName = ref()
        let file = ref([])

    /* Variables declaration End */

    /* Computed Properties declaration Start */
        const age = computed(() => emp.dob ? calculateAge(emp.dob) : '' )
    /* Computed Properties declaration End */

    /* Options Code Start */
        const addchild = () => {
            childrens.value.push({
                'name': '',
                'age': 0
            })
        }
        const removechild = (index) => {
            childrens.value.splice(index, 1)
        }

        const addecont = () => {
            econts.value.push({
                'fullname': '',
                'relation':'',
                'workaddress': '',
                'ghcardno':'',
                'ghcardfile':'',
                'mobileno':'',
                'altnumber':'',
                'isnextofkin': false,

            })
        }
        const removeecont = (index) => {
            econts.value.splice(index, 1)
        }
        const addedu = () =>{
            edus.value.push({
                'files': [],
                'educqual': '',
                'to':null,
                'educqualtype': null,
            })
        }
        const removeedu = (index) => {
            edus.value.splice(index, 1)
        }
        const addworkexp = () => {
            workexps.value.push({
                'orgname': '',
                'postheld': '',
                'from':null,
                'to':null,
            })
        }
        const removeworkexp = (index) => {
            workexps.value.splice(index, 1)
        }
        const addref = () => {
            
            refs.value.push({
                'name': '',
                'companyname': '',
                'designation': '',
                'contactno': '',
                'email': '',
            })
        }
        const removeref = (index) => {
            refs.value.splice(index, 1)
        }
        const addguar = () => {
            
            guarantors.value.push({
                'name': '',
                'address': '',
                'mobileno': '',
                'files':[]
            })
        }
        const removeguar = (index) => {
            guarantors.value.splice(index, 1)
        }
    /* Option Code End */

    /* File methods variable Start */
        const addprofileimage = (event) => {
            
            emp.profilepicture = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    emp.profilepicture.push(base64String)  
                };
                
            }
        }
        const addedusfile = (event, index) => {
            
            edus.value[index].files = [];
            for(const fil of event.target.files){
                
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    edus.value[index].files.push(base64String)
    
                };
                
            }
        }
    /* File methods variable End */


    /* Functional method Start */
        const submit = () =>{
            
            // if(!emp.fullname){
            //     toastt('Please Check the fullname', 'error')
            //     return
            // }
            
            // if(emp.relativeinorg){
            //     if(!emp.relative_name || !emp.relative_relation){
            //         toastt("Check your relative's info",'error')
            //         return
            //     }  
            // }

            const empdetails = {
                workexps: workexps,
                emp: emp,
                soccont: soccont,
                iscurwork: iscurwork.value,
                curwork: curwork,
                childrens: childrens.value,
                econts: econts.value,
                edus: edus.value,
                workexps: workexps.value,
                refs: refs.value
            }

            
            
            axios.post('addemp', empdetails, {
                // headers: { 'Content-Type': 'multipart/form-data' },
                }).then(res => {
                    
                    const data = res.data
                    toastt('Employee successfully added')
                    log(data)
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    log(error)
                })

        }
        const submitbanksocial = () => {
            const empdetails = {
                banksocial: banksocial,
                emp: emp.id || 1,
            }
            axios.post('addbanksocial', empdetails, {
                }).then(res => {
                    
                    const data = res.data
                    toastt('Employee successfully added')
                    log(data)
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    log(error)
                })
        }

        const submitirrguarantee = () => {
            
            const empdetails = {
                irrguar: irrguar,
                emp: emp.id || 1,
            }
            axios.post('addirrguarantee', empdetails, {
                }).then(res => {
                    
                    const data = res.data
                    toastt('Irrevocable Guarantee successfully added')
                    log(data)
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    log(error)
                })
        }
    /* Functional method End */



</script>
<template>


    <div id="form">
        <h1>Employee Onboarding</h1>
        <div id="mainform">
            <h2>Employee Information Form</h2>
            <div class="active">
                <div>
                    <button @click="submit" id="submit">Save</button>
                </div>

                <div class="group">
                    <h3>Personal Information</h3>
                    <div>
                        <div class="one">
                            <label for="">Profile Picture:</label>
                            <input type="file" multiple @change="addprofileimage($event)" >
                        </div>
                        <div class="one">
                            <label for="fullname">Full Name:</label>
                            <input type="text" v-model="emp.fullname">
                        </div>
                        <div class='double' >
                            <div>
                                <label for="">Ghana Card No:</label>
                                <input type="text" v-model="emp.ghcardno" >
                            </div>
                            <div>
                                <label for="">Ghana Card Picture:</label>
                                <input type="file" >
                            </div>
                        </div>
                        <div class="one">
                            <label >Residentital Address/Landamark:</label>
                            <input type="text"  v-model="emp.raddress" >
                        </div>
                        <div class="double">
                            <div>
                                <label>Residentital Ghana Digital Address:</label>
                                <input type="text"  v-model="emp.daddress" >
                            </div>
                            <div>
                                <label>Employee ID:</label>
                                <input type="text"  v-model="emp.employeeid" >
                            </div>
                        </div>
                        
                        <div class="double">
                            <div>
                                <label>Permanent Home Town/Region:</label>
                                <input type="text"  v-model="emp.hometown" >
                            </div>
                            <div>
                                <label>Permanent Ghana Digital Address:</label>
                                <input type="text"  v-model="emp.hdaddress" >
                            </div>
                        </div>

                        <div class="double">
                            <div>
                                <label>Mobile Number:</label>
                                <input type="text"  v-model="emp.mobileno" >
                            </div>
                            <div>
                                <label>Alternate Phone:</label>
                                <input type="text"  v-model="emp.altnumber" >
                            </div>
                        </div>

                        <div class="double">
                            <div>
                                <label>Email Address:</label>
                                <input type="text"  v-model="emp.email" >
                            </div>

                            <div>
                                <label>Social Security Number:</label>
                                <input type="text"  v-model="emp.socialsecurityno" >
                            </div>
                        </div>
                        <div class="triple">
                            <div>
                                <label>Birth Date:</label>
                                <input type="date"  v-model="emp.dob" >
                            </div>
                            <div>
                                <label>Age: </label>
                                <input type="text" :value="age" disabled>
                            </div>
                            <div>
                                <label>Marital Status:</label>
                                
                                <select  v-model="emp.maritalstatus">
                                    <option></option>
                                    <option
                                        v-for="ms in mstatus"
                                        :key="ms.id"
                                        :value="ms.id"
                                    >
                                        {{  ms.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class='double' v-if="emp.maritalstatus == 2">
                            <div>
                                <label>Spouse's Name:</label>
                                <input type="text"  v-model="emp.spousename" >
                            </div>
                            <div>
                                <label>Occupation:</label>
                                <input type="text"  v-model="emp.spouseoccupation" >
                            </div>
                        </div>

                        <div class='double' >
                            <div>
                                <label >Father's Name:</label>
                                <input type="text"  v-model="emp.fathersname" >
                            </div>
                            <div>
                                <label >Mother's Name:</label>
                                <input type="text"  v-model="emp.mothersname" >
                            </div>
                        </div>
                        

                    </div>

                    <h4>
                        Do you have a relative or family member in Melcom ?
                        <span>
                            <input type="radio" v-model="emp.relativeinorg" :value="false">No
                            <input type="radio" v-model="emp.relativeinorg" :value="true">Yes
                        </span>
                    </h4>

                    <div class='double' v-if="emp.relativeinorg">
                        <div>
                            <label >Name:</label>
                            <input type="text"  v-model="emp.relative_name" >
                        </div>

                        <div>
                            <label> Type of relation:</label>
                            <select  v-model="emp.relative_relation">
                                <option></option>
                                <option
                                    v-for="relation in relations"
                                    :key="relation.id"
                                    :value="relation.id"
                                >
                                    {{  relation.name }}
                                </option>
                            </select>
                        </div>
                    </div>


                    <h4>
                        Do you have a children?
                        <span>
                            <input type="radio" v-model="haschildren" :value="false">No
                            <input type="radio" v-model="haschildren" :value="true">Yes
                        </span>
                    </h4>

                    <div v-if="haschildren">

                        <button class="inbutton" v-if="!childrens.length" @click="addchild">Add child</button>
                        
                        <div v-for="(child, index) in childrens" :key="index" class="inline">
                            <div>
                                <label>Child {{ (index + 1) }}'s  Name:</label>
                                <input type="text" v-model="child.name" >
                            </div>
                            <div>
                                <label>Age:</label>
                                <input type="number" v-model="child.age">
                            </div>
                            <span>
                                <button @click="removechild(index)">-</button>
                                <button v-if="index === childrens.length - 1"  @click="addchild">+</button>
                                
                            </span>
                        </div>
                        
                    </div>
                </div>

                
                <div class="group">
                    <h3>Present Job at Melcom</h3>
                    <h4>
                        Are you currenlty working at Melcom?
                        <span>
                            <input type="radio" v-model="iscurwork" :value="false">No
                            <input type="radio" v-model="iscurwork" :value="true">Yes
                        </span>
                    </h4>

                    <div v-if="iscurwork">
                        <h4>Details of Present Job at Melcom</h4>
                        <div class="inlinewrap">
                            <div>
                                <label>Title:</label>
                                <input type="text" v-model="curwork.title">
                            </div>
                            <div>
                                <label>Employee ID:</label>
                                <input type="text" v-model="curwork.employeeid">
                            </div>
                            <div>
                                <label>Date of Joining:</label>
                                <input type="date" v-model="curwork.doj">
                            </div>
                            <div>
                                <label>Department:</label>
                                <select  v-model="curwork.dept_id">
                                    <option></option>
                                    <option
                                        v-for="dept in depts"
                                        :key="dept.id"
                                        :value="dept.id"
                                    >
                                        {{  dept.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label>Branch:</label>
                                <select  v-model="curwork.branch_id">
                                    <option></option>
                                    <option
                                        v-for="branch in branchs"
                                        :key="branch.id"
                                        :value="branch.id"
                                    >
                                        {{  branch.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label>Region:</label>
                                <select  v-model="curwork.region_id">
                                    <option></option>
                                    <option
                                        v-for="region in regions"
                                        :key="region.id"
                                        :value="region.id"
                                    >
                                        {{  region.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group">
                    <h3>Emergency Contact Information [Relatives Only]</h3>

                    <button @click="addecont" v-if="!econts.length">Add</button>

                    <div v-for="(econt,index) in econts" :key="index">
                        <h4>CONTACT NO{{ index + 1 }}</h4>
                        <div class="inlinewrap">
                            <div>
                                <label >Full Name:</label>
                                <input type="text" v-model="econt.fullname">
                            </div>
                            <div>
                                <label>Relationship to you:</label>
                                <select  v-model="econt.relation">
                                    <option></option>
                                    <option
                                        v-for="relation in relations"
                                        :key="relation.id"
                                        :value="relation.id"
                                    >
                                        {{  relation.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label >Work Address preferable:</label>
                                <input type="text" v-model="econt.workaddress">
                            </div>
                            <div>
                                <label >Ghana Card No:</label>
                                <input type="text" v-model="econt.ghcardno">
                            </div>
                            
                            <div>
                                <label for="">Ghana Card:</label>
                                <input type="file" >
                            </div>
                            <div>
                                <label>Mobile Number:</label>
                                <input type="tel" v-model="econt.mobileno">
                            </div>
                            <div>
                                <label>Alternate Phone:</label>
                                <input type="tel" v-model="econt.altnumber">
                            </div>

                            <div>
                                Next of kin? 
                                <input type="radio" v-model="econt.isnextofkin" :value="false">No
                                <input type="radio" v-model="econt.isnextofkin" :value="true">Yes
                            </div>
                        </div>
                        <span>
                            
                            <button v-if="index === econts.length - 1" @click="removeecont(index)">Remove</button>
                            <button @click="addecont">Add</button>
                        </span>
                    </div>


                </div>

                <div class="group">
                    <h3>Social Contact Information</h3>
                    <div>
                        <div class="inlinewrap">
                            <div>
                                <label >Church / Mosque Membership & Location:</label>
                                <input type="text" v-model="soccont.churchmemberloc" >
                            </div>
                            
                            <div>
                                <label>Pastor Religious Leader:</label>
                                <input type="text" v-model="soccont.pastor" >
                            </div>
                            <div>
                                <label >Duration of Membership:</label>
                                <input type="number" v-model="soccont.memduration" >
                            </div>
                            <div>
                                <label>Mobile Number:</label>
                                <input type="tel" v-model="soccont.mobileno" >
                            </div>
                            <div>
                                <label>Ghana Card No:</label>
                                <input type="text" v-model="soccont.ghcardno" >
                            </div>

                            <div>
                                <label for="">Closest Friend/Confident:</label>
                                <input type="text" v-model="soccont.closestfriend" >
                            </div>

                            <div>
                                <label for="">Contacts Phone:</label>
                                <input type="tel" v-model="soccont.contactsphone" >
                            </div>

                            <div>
                                <label >Work Address preferable:</label>
                                <input type="text" v-model="soccont.workaddress" >
                            </div>
                            
                        </div>
                    </div>


                    
                </div>

                <div class="group">
                    <h3>Educational Background</h3>

                    <button @click="addedu" v-if="!edus.length">Add</button>

                    <div>
                        <div v-for="(edu, index) in edus" :key="index" class="inline">
                            
                            <div>
                                <label for="">Qualification Type:</label>
                                <select  v-model="edu.educqualtype">
                                    <option></option>
                                    <option
                                        v-for="q in qualtypes"
                                        :key="q.id"
                                        :value="q.id"
                                    >
                                        {{  q.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label>Specialization:</label>
                                <input type="text" v-model="edu.educqual">
                            </div>

                            <div>
                                <label >Date Of Completion:</label>
                                <input type="date" v-model="edu.to">
                            </div>

                            <div>
                                <label>Eductional Qualification:</label>
                                <input type="file" multiple @change="addedusfile($event, index)">
                            </div>

                            <span>
                                <button @click="removeedu(index)">-</button>
                                <button @click="addedu" v-if="index === edus.length - 1">+</button>
                                
                            </span>

                        </div>
                    </div>


                    <div>
                        Are you a borrower of the Student Loan Trust Fund?
                        <span>
                            <input type="radio" v-model="emp.studentloan" :value="false" > No
                            <input type="radio" v-model="emp.studentloan" :value="true"> Yes
                        </span>
                    </div>
                </div>

                <div class="group">
                    <h3>WORK EXPERIENCE</h3>

                    <button @click="addworkexp" v-if="!workexps.length">Add</button>
                    <div >
                        <div v-for="(wexp, ind) in workexps" :key="ind" class="inline">
                            <div>
                                <label >Name of Organization:</label>
                                <input type="text" v-model="wexp.orgname">
                            </div>
                            
                            <div>
                                <label>Post held:</label>
                                <input type="text" v-model="wexp.postheld">
                            </div>
                            <div>
                                <label >From:</label>
                                <input type="date" v-model="wexp.from">
                            </div>

                            <div>
                                <label >To:</label>
                                <input type="date" v-model="wexp.to">
                            </div>

                            <span>
                                
                                <button @click="removeworkexp(ind)">-</button>
                                <button v-if="ind === workexps.length - 1" @click="addworkexp">+</button>
                            </span>
                        </div>
                    </div>

                </div>

                <div class="group">
                    <h3>REFERENCES</h3>

                    <button @click="addref" v-if="!refs.length">Add</button>

                    <div v-for="(ref , ind) in refs" :key="ind" >
                        <h4>REFERENCE 1</h4>
                        <div class="inline">
                            <div>
                                <label >Name :</label>
                                <input type="text" v-model="ref.name">
                            </div>
                            
                            <div>
                                <label>Company Name:</label>
                                <input type="text" v-model="ref.companyname">
                            </div>

                            <div>
                                <label>Designation:</label>
                                <input type="text" v-model="ref.designation">
                            </div>
                            <div>
                                <label>Contact Number:</label>
                                <input type="tel" v-model="ref.contactno">
                            </div>
                            <div>
                                <label>Email:</label>
                                <input type="email" v-model="ref.email">
                            </div>
                            <span>
                                
                                <button @click="removeref(ind)">-</button>
                                <button v-if="ind === refs.length - 1" @click="addref">+</button>
                            </span>
                        </div>
                    </div>


                    
                </div>

                <div class="group">
                    <h3>GUARANTORS (MIN 2)</h3>

                    <button @click="addguar" v-if="!guarantors.length">Add</button>

                    <div v-for="(g , ind) in guarantors" :key="ind" >
                        <h4>GUARANTOR {{ ind + 1 }}</h4>
                        <div class="inline">
                            <div>
                                <label >Name :</label>
                                <input type="text" v-model="g.name">
                            </div>
                            
                            <div>
                                <label>Address:</label>
                                <input type="text" v-model="g.address">
                            </div>

                            <div>
                                <label>mobileno:</label>
                                <input type="text" v-model="g.mobileno">
                            </div>
                            
                            <div>
                                <label>ID:</label>
                                <input type="file" >
                            </div>
                            <span>
                                
                                <button @click="removeguar(ind)">-</button>
                                <button v-if="ind === guarantors.length - 1" @click="addguar">+</button>
                            </span>
                        </div>
                    </div>


                    
                </div>

                <div>
                    <h3>ANY OTHER INFORMATION</h3>
                    <textarea  v-model="emp.anyotherinfo" rows="5"></textarea>
                </div>

                <div>
                    <button @click="submit" id="submit">Save</button>
                </div>
            </div>
        </div>
        <div id="banksocial" >
            <h2>Bank / Social Security Fund Contribution Details</h2>
            <div class="active">
                <div class='double' >
                    <div>
                        <label for="">Name of Bank:</label>
                        <input type="text" v-model="banksocial.bankname" >
                    </div>
                    <div>
                        <label for="">Bank Branch:</label>
                        <input type="text" v-model="banksocial.bankbranch" >
                    </div>
                </div>
                <div class='double' >
                    <div>
                        <label for="">Account Name:</label>
                        <input type="text" v-model="banksocial.accountname" >
                    </div>
                    <div>
                        <label for="">Account Type:</label>
                        <select  v-model="banksocial.accounttype">
                            <option></option>
                            <option
                                v-for="bat in bankaccounttype"
                                :key="bat.id"
                                :value="bat.id"
                            >
                                {{  bat.name }}
                            </option>
                        </select>
                        
                    </div>
                </div>
                <div class='double' >
                    <div>
                        <label for="">Account Number:</label>
                        <input type="text" v-model="banksocial.accountnumber" >
                    </div>
                    <div>
                        <label for="">Social Security Fund Number:</label>
                        <input type="tel" v-model="banksocial.socialfundnumber"  maxlength="13">
                    </div>
                </div>

                <div>
                    <button @click="submitbanksocial" id="submit">Save</button>
                </div>
            </div>
        </div>
        <div id="irrguarantor" >
            <h2>IRREVOCABLE CONTINUING GUARANTEE</h2>
            <div class="active">

                <div class='double' >
                    <div>
                        <label for="">Name of Guarantor:</label>
                        <input type="text" v-model="irrguar.guarname" >
                    </div>
                    <div>
                        <label for="">SSF No:</label>
                        <input type="text" v-model="irrguar.ssfno" >
                    </div>
                </div>
                <div class='double' >
                    <div>
                        <label for="">Annual Income:</label>
                        <input type="text" v-model="irrguar.annualincome" >
                    </div>
                    <div>
                        <label for="">Value of Landed Property:</label>
                        <input type="text" v-model="irrguar.propertyvalue" >
                    </div>
                </div>
                <div class='double' >
                    <div>
                        <label for="">Occupation/Position:</label>
                        <input type="text" v-model="irrguar.occupation" >
                    </div>
                    <div>
                        <label for="">Tel/Mob. No:</label>
                        <input type="text" v-model="irrguar.mobileno" >
                    </div>
                </div>

                <div class='one' >
                    <label for="">Business Address:</label>
                    <input type="text" v-model="irrguar.businessaddr" >
                    
                </div>
                <div class='one' >
                    <label for="">Residential Address:</label>
                    <input type="text" v-model="irrguar.residenceaddr" >
                    
                </div>

                <div class='double' >
                    <div>
                        <label for="">Ghana Card Number:</label>
                        <input type="text" v-model="irrguar.ghcard" >
                    </div>
                    <div>
                        <label for="">Digital Address:</label>
                        <input type="text" v-model="irrguar.digitaladdr" >
                    </div>
                </div>

                <div class='double' >
                    <div>
                        <label>Relationship with Applicant:</label>
                        <select  v-model="irrguar.relation">
                            <option></option>
                            <option
                                v-for="relation in relations"
                                :key="relation.id"
                                :value="relation.id"
                            >
                                {{  relation.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="">Duration of Relationship:</label>
                        <input type="number" v-model="irrguar.relationyear" >
                    </div>
                </div>

                <div>
                    The guarantor should also submit with evidence of citizenship and identity any of the following: Driver’s License /Passport/Voter’s ID card/ Employment ID Card. The guarantor must submit in person to the Head Office of the Company or any other office authorized to receive such forms with Two (2) current Passport picture.
                </div>

                <div>

                </div>

                
                <h4>DECLARATION</h4>
                    
                <div id="declarationdiv">
                    <p>
                        I, <strong>{{ irrguar.guarname }}</strong> presently residing at <strong>{{ irrguar.residenceaddr }}</strong>, Ghana, and an employee of <input type="text" v-model="irrguar.company"> and can be contacted on Tel <strong>{{ irrguar.mobileno }}</strong>. I understand that you have appointed <strong>{{ emp.fullname }}</strong> in your organization as <input type="text" v-model="irrguar.empposition">. I personally know him for past <strong>{{ irrguar.relationyear }}</strong>. years. I do hereby confirm that I stand as guarantor to secure the company against losses that may arise due to his act or omission, fraud, dishonesty or misfeasance for Ghana Cedi <input type="text" v-model="irrguar.guaramount"> (In words) <input type="text" v-model="irrguar.guaramountletters">
                    </p>
                    <p>
                        Any claim made under this guarantee must be received by me in writing accompanied by your signed statement that <strong>{{ emp.fullname }}</strong>.has failed to fulfill his obligations and has resulted loss to you and such statement and claim shall be conclusive evidence that the amount claimed is due to you under this Guarantee.
                    </p>
                    <p>
                        I have sought legal advice and understand the implication of this irrevocable continuing guarantee. Even though I am not an employee of the company I will be jointly and severally liable for loss and or damage whatsoever incurred by the company as a result of act, omission or misfeasance of Mr./Miss/Mrs <strong>{{ emp.fullname }}</strong>
                    </p>
                    <p>
                        I am the owner of H/No <input type="text" v-model="irrguar.hno"> situated at <input type="text" v-model="irrguar.hnolocation"> and I am prepared to deposit copies of document in original in respect of such property as proof of ownership and to secure the monetary loss, if any, incurred by Mr./Miss/Mrs <strong>{{ emp.fullname }}</strong>.<br>
                        This Irrevocable Continuing Guarantee shall remain valid so long as Mr./Miss/Mrs.<strong>{{ emp.fullname }}</strong> is working with the Company and shall expire after 90 days from the date he leaves/ terminated from the company or his final settlement is made to the satisfaction of the Company and after 90 days, this Guarantee shall become null and void whether returned to me for cancellation or not and any claim or statement received after expiry shall be ineffective.
                    </p>
                </div>

                <div>
                    <button @click="submitirrguarantee" id="submit">Save</button>
                </div>
            </div>
        </div>  
    </div>
</template>
<style scoped>

    /* #form {
        
    } */
    input{
        text-transform: uppercase;
    }
    #form > div > div:not(.active){
        display: none;
    }

    label:not(.radio){
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    input:not([type=radio]), select, textarea{
        padding:5px;
        width: 100%;
    }
    #declarationdiv{
        
        line-height: 1.5;
    }
    #declarationdiv input{
        width: unset;
        padding: 3px;
    }
    .one{
        
        margin-bottom: 15px;
    }
    .double{
        display:flex;
        gap:10px;
        margin-bottom: 15px;
    }
    .double > div{
        flex:1
    }
    .triple{
        display:flex;
        gap:10px;
        margin-bottom: 15px;
    }
    .triple > div{
        flex:1
    }
    .group > button, .inbutton{
        padding: 5px 20px;
        cursor: pointer;

    }
    .inline{
        display: flex;
        align-items: end;
        gap:10px;
        margin-bottom: 15px;
    }
    .inline > div{
        flex: 1;

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

    #submit{
        padding:10px 40px;
        margin-top: 30px;
        background-color: black;
        color:white;
        cursor: pointer;
        border-radius: 5px;
        transition: .5s;
    }
    #submit:hover{
        background-color: white;
        color: black;
        border: 2px solid black;
    }
    h3,  h5{
        margin:0;
    }
    h4{
        margin:5px 0px;
    }
    .group > *:not(h3){
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
    }
    

</style>