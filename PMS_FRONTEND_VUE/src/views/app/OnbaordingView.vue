<script setup>
    import Vue3Signature from "vue3-signature"
    import { ref, reactive, computed, onMounted , watch} from 'vue'
    import {onBeforeRouteLeave } from 'vue-router'
    import { log, calculateAge, toastt, numberToWords, aToday, afDate, aDate, imgburl} from '@/helpers/essential'   
    import { mstatus,  bankaccounttype, relations, depts, regions, branchs, qualtypes, banks, familyrelation, countries, conttypes, idtypes, findidtypes, findcompany, genders, companies, findregion, findconttypes, findrecordstatus} from '@/data/masterdata'
    import axios from 'axios';


    import { useConfirm } from 'primevue/useconfirm';
    const confirm = useConfirm();
    
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { loguser, getloguser, authtoken, getauthtoken } = userstore

    const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer


    /* Date Computation Start */
        // Get today's date
        let mindob = new Date();

        // Add 80 years to today's date
        const future80Date = new Date();
        future80Date.setFullYear(mindob.getFullYear() - 80);
        const formatted80Date = future80Date.toISOString().split('T')[0];

        // Add 16 years to today's date
        const future16Date = new Date();
        future16Date.setFullYear(mindob.getFullYear() - 18);
        const formatted16Date = future16Date.toISOString().split('T')[0];
    /* Date Computation End */
    

    /* Variables declaration Start */
        let adding = ref(false)
        let saving = ref(false) 
        let showsearchpopupvalue = ref(false)

        let changedvalue = ref(false)

        const today = aToday(new Date())
        
        let emplist = ref([])
        let empsearch = ref('')

        let emp = reactive({})
        let banksocial = reactive({})
        let irrguar = reactive({})
        let soccont = reactive({})
        
        let iscurwork = ref()
        let isunion = ref()
        // let isdriver = ref()
        let curwork = reactive({})
        let unioninfo = reactive({})
        let nominee = reactive({});
        let dlicense = reactive({});
        let wpermit = reactive({});
        
        let haschildren = ref()
        let childrens = ref([]);

        let wives = ref([]);

        let econts = ref([]);
        let edus = ref([])
        let workexps = ref([])
        let refs = ref([])
        let guarantors = ref([])
        

        let irrguarwitnesses = ref([])

        let fileName = ref()
        let file = ref([])

        let agreetotems = ref(false)
        let otprequested = ref(false)

        let empinfohistories = ref([])
        let bankinfohistories = ref([])
        let irrguarinfohistories = ref([])
    /* Variables declaration End */

    onMounted(() => {
        const inps = document.querySelectorAll("input, select, textarea");
        inps.forEach((elem)=>{
            elem.addEventListener('change', function(e){
                if(!changedvalue.value) changedvalue.value = true
            },{once:true})
        })
    })
    
    onBeforeRouteLeave ((to, from , next) => {

        if(changedvalue.value){
            confirmPosition(next, next)
            return
        }else{
            next()
        }
         
    })


    /* Signature code  Start*/
        const state = reactive({
            count: 0,
            option: {
                penColor: "rgb(0, 0, 0)",
                backgroundColor: "white"
            },
            disabled: false
        })

        const loadsignature = (elem, event) =>{
            const fil = event.files[0]
            const reader = new FileReader();

            reader.onloadend = function() {
                const URIData = reader.result;
                elem.value.fromDataURL(URIData)
            };
            const tic = reader.readAsDataURL(fil);
        }
        
        const signature1 = ref(null)
        emp.signature = ref('')
        let employeesigning = ref(false)

        const signature2 = ref(null)
        irrguar.signature = ref('')
        let irrguarsigning = ref(false)

        const signature3 = ref(null)
        emp.guarsignature = ref('')
        let employeeguarsigning = ref(false)

        const savesignature1 = (t) => {
            if(signature1.value.isEmpty()){
                toastt('Please append a signature first', 'error')
                return
            }else{
                emp.signature = signature1.value.save(t)
                employeesigning.value = false
            }
        }
        const loadsignature1 = (event) =>{
            loadsignature(signature1,event)
        }
        const clearsignature1 = () => {
            signature1.value.clear()
        }
        const cancelsignature1 = () => {
            signature1.value.clear()
            employeesigning.value = false
        }

        const savesignature3 = (t) => {
            if(signature3.value.isEmpty()){
                toastt('Please append a signature first', 'error')
                return
            }else{
                emp.guarsignature = signature3.value.save(t)
                employeeguarsigning.value = false
            }
        }
        const loadsignature3 = (event) =>{
            loadsignature(signature3,event)
        }
        const clearsignature3 = () => {
            signature3.value.clear()
        }
        const cancelsignature3 = () => {
            signature3.value.clear()
            employeeguarsigning.value = false
        }

        const savesignature2 = (t) => {
            if(signature2.value.isEmpty()){
                toastt('Please append a signature first', 'error')
                return
            }else{
                irrguar.signature = signature2.value.save(t)
                irrguarsigning.value = false
            }
        }
        const loadsignature2 = (event) =>{
            loadsignature(signature2,event)
        }
        const clearsignature2 = () => {
            signature2.value.clear()
        }
        const cancelsignature2 = () => {
            signature2.value.clear()
            irrguarsigning.value = false
        }

        
    /* Signature code  Start*/
        

    /* Computed Properties declaration Start */
        const age = computed(() => emp.dob ? calculateAge(emp.dob) : emp.dob )
        const childmaxdob = computed(() => emp.dob ? age.value - 10 : emp.dob  )
        const eczone = computed(() => {
            if(emp.citizenship == 'GH') return 'GH'
            else if(['NG','NE','GM','BJ','BF', 'SN', 'CV', 'GN', 'TG', 'SL','CI'].includes(emp.citizenship)) return 'EC'
            else return 'OT'
        })
        const ssrequired = computed(() => emp.citizenship == 'GH' ? true : false )

        const isnotoptional = computed(() => {
            if([4,5,6,7,8,9,10].includes(emp.company)) return false
            else return true
        })
        const isnomineenotoptional = computed(() => {
            if(nominee.name?.length || nominee.relation || nominee.mobile?.length) return true
            else return false
        })
        const isplcnotrequired = computed(() => {
            if(['DRIVER','SECURITY','SECURITY OFFICER','SHOP MANAGER','SALES MANAGER','SALES EXECUTIVE'].includes(emp.joiningposition)) return false
            else return true
        })
        const guarantorrequired = computed(() => {
            if(['AUDITOR', 'CASHIER'].includes(emp.joiningposition)) return true
            else return false
        })
    /* Computed Properties declaration End */


    watch(() => empsearch.value, (newv, oldv) => {
        
        if(newv.length >2){
            axios.post('searchemp',{
                data: newv 
            })
                .then(res => {
                    const data = res.data
                    emplist.value = data

                    log(data)
                })
                .catch((error) => {
                    console.log(error)
                })

        }else{
            emplist.value = []
        }
    })

    /* Options Code Start */
        const addchild = () => {
            childrens.value.push({
                'name': '',
                'age': null
            })
        }
        const removechild = (index) => {
            childrens.value.splice(index, 1)
        }
        const addwife = () => {
            wives.value.push({
                'name': '',
                'occupation': ''
            })
        }
        const removewife = (index) => {
            wives.value.splice(index, 1)
        }
        const addecont = () => {
            econts.value.push({
                'fullname': '',
                'relation':'',
                'workaddress': '',
                'ghcardno':'',
                'files':[],
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
                'email': '',
                'mobileno': '',
                'files': [],
            })
        }
        const removeguar = (index) => {
            guarantors.value.splice(index, 1)
        }

        const addirrguarwitness = () => {
            
            irrguarwitnesses.value.push({
                'name': '',
                'address': '',
                'phoneno': '',
            })
        }
        const removeirrguarwitness = (index) => {
            irrguarwitnesses.value.splice(index, 1)
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
                const tic = reader.readAsDataURL(fil);
            }
        }

        let newprofilepicture = []
        const changeprofileimage = (event) => {
            
            newprofilepicture = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    newprofilepicture.push(base64String)  
                };
                const tic = reader.readAsDataURL(fil);
            }
        }

        const addghcardimage = (event) => {
            
            emp.ghcardfile = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    emp.ghcardfile.push(base64String)  
                };
                const tic = reader.readAsDataURL(fil);
            }
        }
        const addnomineedfile = (event) => {
            
            nominee.files = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    nominee.files.push(base64String)  
                };
                const tic = reader.readAsDataURL(fil);
            }
        }
        const addlicencsefile = (event) => {
            
            dlicense.files = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    dlicense.files.push(base64String)  
                };
                const tic = reader.readAsDataURL(fil);
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
                const tic = reader.readAsDataURL(fil);
                
            }
        }
        const addguarfiles = (event, index) => {
            
            guarantors.value[index].files = [];
            
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    guarantors.value[index].files.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }
        const addecontfiles = (event, index) => {
            
            econts.value[index].files = [];
            
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    econts.value[index].files.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }
        const addguaridimage = (event) => {
            
            irrguar.idcard = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    irrguar.idcard.push(base64String)  
                };
                const tic = reader.readAsDataURL(fil);
            }
        }

        const addguarform = (event) => {
            
            irrguar.guarform = [];

            for(const fil of event.target.files){
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    irrguar.guarform.push(base64String)  
                };
                const tic = reader.readAsDataURL(fil);
            }
        }

        const addguarprofileimage = (event) => {
            
            const fil = event.target.files[0]
            irrguar.profileimage = null;

            const reader = new FileReader();

            reader.onloadend = function() {
                const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                irrguar.profileimage = base64String  
            };
            const tic = reader.readAsDataURL(fil);

        }

        let appletters = ref([])
        const addappletter = (event, index) => {
            appletters.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    appletters.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let appointmentletters = ref([])
        const addappointmentletter = (event, index) => {
            appointmentletters.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    appointmentletters.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let probationconfs = ref([])
        const addprobationconf = (event, index) => {
            probationconfs.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    probationconfs.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let cv = ref([])
        const addcv = (event, index) => {
            cv.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    cv.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let petratrust = ref([])
        const addpetratrust = (event, index) => {
            petratrust.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    petratrust.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let nhis = ref([])
        const addnhis = (event, index) => {
            nhis.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    nhis.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let birthcert = ref([])
        const addbirthcert = (event, index) => {
            birthcert.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    birthcert.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let pclearanceform = ref([])
        const addpclearanceform = (event, index) => {
            pclearanceform.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    pclearanceform.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }

        let ssnit = ref([])
        const addssnit = (event, index) => {
            ssnit.value = []
            for(const fil of event.target.files){

                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64String = reader.result.replace('data:', '').replace(/^.+,/, '');
                    ssnit.value.push(base64String)
                };
                const tic = reader.readAsDataURL(fil);
                
            }
        }
        
        
    /* File methods variable End */

    /* Validation code Start */
        const checkduplicate = (arraytocheck, firstprop, secprops) =>{
            return  arraytocheck.some((item, index) => 
                arraytocheck.findIndex(f => f[firstprop] === item[firstprop] || f[secprops] === item[secprops]) !== index
            );
        }
        const colorinvalid = (elem) =>{
            if(['radio'].includes(elem.type)){
                elem.parentElement.parentElement.style = 'border:2px solid red'
                elem.addEventListener('input', function(e){
                    elem.parentElement.parentElement.style = 'revert'
                },{once:true})
            }else if(['select-one', 'text','email','date','tel', 'number', 'file'].includes(elem.type)){
                elem.style = 'border:2px solid red'
                if(elem.nextElementSibling) elem.nextElementSibling.innerText = elem.validationMessage
                elem.addEventListener('input', function(e){
                    elem.style = 'revert'
                    if(elem.nextElementSibling) elem.nextElementSibling.innerText = ''
                },{once:true})
            }
        }
        const valEmpPersInfo1 = () => {
            const reqinput = document.querySelectorAll("#perinfo1 :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                perinfostep.value = 1
                window.scrollTo(0,100);
                return false
            }
            
        }
        const valEmpPersInfo2 = () => {
            const reqinput = document.querySelectorAll("#perinfo2 :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                perinfostep.value = 2
                window.scrollTo(0,100);
                return false;
            }
            if(isnotoptional.value && econts.value.length == 0){
                toastt('You need at least one emergency contact', 'error')
                perinfostep.value = 2
                window.scrollTo(0,100);
                return false;
            }

            if(checkduplicate(econts.value, 'fullname','mobileno')){
                toastt('You cannot duplicate emergency contacts', 'error')
                perinfostep.value = 2
                return false;
            }
            
            
        }
        const valEmpPersInfo3 = () => { 
            const reqinput = document.querySelectorAll("#perinfo3 :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                perinfostep.value = 3
                
                return false;
            }
            if(isnotoptional.value && edus.value.length == 0){
                toastt('You need at least one educational background', 'error')
                perinfostep.value = 3
                window.scrollTo(0,100);
                return false;
            }
            if(isnotoptional.value && refs.value.length == 0){
                toastt('You need at least one reference', 'error')
                perinfostep.value = 3
                return false;
            }
            
        }
        const valEmpPersInfo4 = () => {
            if(!emp.signature){
                toastt('Ensure the signature is appended and saved', 'error')
                perinfostep.value = 4
                return false
            } 

            if(!emp.guarsignature){
                toastt('Ensure the guarrantor signature is appended and saved', 'error')
                perinfostep.value = 4
                return false
            } 
        }
        const valsocial = () => {
            const reqinput = document.querySelectorAll("#socialcontact :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                return false
            }
            
        }
        const valIrrGuar1 = () => {
            const reqinput = document.querySelectorAll("#irrguar1 :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                irrguarstep.value = 1
                window.scrollTo(0,100);
                return false
            }
            
        }
        const valIrrGuar2 = () => {
            const reqinput = document.querySelectorAll("#irrguar2 :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                irrguarstep.value = 2
                window.scrollTo(0,100);
                return false
            }
            
        }
        const valIrrGuar3 = () => {
            const reqinput = document.querySelectorAll("#irrguar3 :invalid");
            
            reqinput.forEach((elem)=>{
                colorinvalid(elem)
            })
            if(reqinput.length){
                toastt('Correct the errors and submit the form again', 'error')
                irrguarstep.value = 3
                window.scrollTo(0,200);
                return false
            }

            if(!irrguar.signature){
                toastt('Ensure the signature is appended and saved', 'error')
                irrguarstep.value = 3
                return false
            } 
            if(irrguarwitnesses.value.length < 2){
                toastt('There must be at least 2 witnesses', 'error')
                irrguarstep.value = 3
                return false;
            }
            if(checkduplicate(irrguarwitnesses.value, 'name','phoneno')){
                toastt('You cannot duplicate witnesses', 'error')
                perinfostep.value = 2
                return false;
            }
        }

        
    /* Validation code End */

    /* Functional method Start */
        const submit = () =>{

            if(valEmpPersInfo1() === false) return
            if(valEmpPersInfo2() === false) return
            if(valEmpPersInfo3() === false) return
            if(valEmpPersInfo4() === false) return
            saving.value = true

            const empdetails = {
                workexps: workexps,
                emp: emp,
                soccont: soccont,
                iscurwork: iscurwork.value,
                isunion: isunion.value,
                curwork: curwork,
                unioninfo: unioninfo,
                wpermit: wpermit,
                dlicense: dlicense,
                nominee: nominee,
                childrens: childrens.value,
                wives: wives.value,
                econts: econts.value,
                edus: edus.value,
                workexps: workexps.value,
                refs: refs.value,
                guarantors: guarantors.value
            }

            axios.post('addemp', empdetails, {
                
                }).then(res => {
                    
                    const data = res.data
                    
                    if(data.success == false){

                        for (const [key, value] of Object.entries(data.errors)) {
                            if(key != 'signature'){
                                const elmt= document.querySelector(`[name=${key}]`)
                                elmt.nextElementSibling.innerText = value[0]
                                elmt.addEventListener('input', function(e){
                                    elmt.nextElementSibling.innerText = ''
                                },{once:true})
                                perinfostep.value = 1
                                window.scrollTo(0,100);
                            }else{
                                toastt('The signature is required', 'error')
                            }
                        }
                        toastt('Correct the errors and submit the form again', 'error')

                        saving.value = false
                    }else{

                        saving.value = false

                        Object.assign(emp, data)
                        Object.assign(soccont, data.soccontact || {})
                        Object.assign(curwork, data.curwork || {})
                        Object.assign(unioninfo, data.unioninfo || {})
                        Object.assign(wpermit, data.workpermit || {})
                        Object.assign(dlicense, data.driverlicense || {})
                        Object.assign(nominee, data.nominee || {})
                        
                        edus.value = data.educations
                        childrens.value = data.childrens
                        wives.value = data.wives
                        econts.value = data.econs
                        workexps.value = data.workexps
                        guarantors.value = data.guarantos
                        refs.value = data.refs
                        iscurwork.value = data.presentjob ? true : false
                        haschildren.value = data.childrens?.length ? true : false
                        toastt('Employee successfully added')
                        
                    }     
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    saving.value = false
                    log(error)
                })

        }
        const updateemployee = () =>{

            if(valEmpPersInfo1() === false) return
            if(valEmpPersInfo2() === false) return
            if(valEmpPersInfo3() === false) return
            if(valEmpPersInfo4() === false) return
            // if(valEmpPersInfo5() === false) return

            saving.value = true

            const empdetails = {
                workexps: workexps,
                emp: emp,
                soccont: soccont,
                iscurwork: iscurwork.value,
                isunion: isunion.value,
                curwork: curwork,
                unioninfo: unioninfo,
                wpermit: wpermit,
                dlicense: dlicense,
                nominee: nominee,
                childrens: childrens.value,
                wives: wives.value,
                econts: econts.value,
                edus: edus.value,
                workexps: workexps.value,
                refs: refs.value
                // guarantors: guarantors.value
            }

            axios.post('updateemp', empdetails, {
                
                }).then(res => {
                    
                    const data = res.data
                    log(data)

                    if(data.success == false){

                        for (const [key, value] of Object.entries(data.errors)) {
                            if(key != 'signature'){
                                const elmt= document.querySelector(`[name=${key}]`)
                                elmt.nextElementSibling.innerText = value[0]
                                elmt.addEventListener('input', function(e){
                                    elmt.nextElementSibling.innerText = ''
                                },{once:true})
                                perinfostep.value = 1
                                window.scrollTo(0,100);
                            }else{
                                toastt('The signature is required', 'error')
                            }
                        }
                        toastt('Correct the errors and submit the form again', 'error')

                        
                    }else{
                    
                        Object.assign(emp, data)
                        Object.assign(soccont, data.soccontact || {})
                        Object.assign(curwork, data.curwork || {})
                        Object.assign(unioninfo, data.unioninfo || {})
                        Object.assign(unioninfo, data.unioninfo || {})
                        Object.assign(wpermit, data.workpermit || {})
                        Object.assign(dlicense, data.driverlicense || {})
                        Object.assign(nominee, data.nominee || {})
                        
                        edus.value = data.educations
                        childrens.value = data.childrens
                        wives.value = data.wives
                        econts.value = data.econs
                        workexps.value = data.workexps
                        guarantors.value = data.guarantos
                        refs.value = data.refs
                        iscurwork.value = data.presentjob ? true : false
                        haschildren.value = data.childrens?.length ? true : false

                        
                        toastt('Employee successfully updated')
                    }
                    saving.value = false
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    saving.value = false
                    log(error)
                })

        }
        const submitbanksocial = () => {

            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            if(valsocial() === false) return

            saving.value = true
            const empdetails = {
                banksocial: banksocial,
                emp: emp.id,
            }
            axios.post('addbanksocial', empdetails, {
                }).then(res => {
                    
                    const data = res.data
                    Object.assign(banksocial, data)
                    toastt('Bank details successfully added')
                    log(data)
                    saving.value = false
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const editbanksocial = () => {

            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            if(valsocial() === false) return

            saving.value = true
            const empdetails = {
                banksocial: banksocial,
                emp: emp.id,
            }
            axios.post('updatebanksocial', empdetails, {
                }).then(res => {
                    
                    const data = res.data
                    toastt('Bank details successfully updated')
                    log(data)
                    saving.value = false
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const submitirrguarantee = () => {

            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                // return
            }
            
            if(valIrrGuar1() === false) return
            if(valIrrGuar2() === false) return
            if(valIrrGuar3() === false) return
            
            saving.value = true

            const empdetails = {
                irrguar: irrguar,
                emp: emp.id,
                irrguarwitnesses: irrguarwitnesses.value
            }

            axios.post('addirrguarantee', empdetails, {
                }).then(res => {
                    
                    const data = res.data
                    log(data)
                    Object.assign(irrguar, data)
                    toastt('Irrevocable Guarantee successfully added')
                    saving.value = false
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    log(error)
                    saving.value = false
                })
        }
        const updateirrguarantee = () => {

            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            if(valIrrGuar1() === false) return
            if(valIrrGuar2() === false) return
            if(valIrrGuar3() === false) return

            saving.value = true

            const empdetails = {
                irrguar: irrguar,
                emp: emp.id,
                irrguarwitnesses: irrguarwitnesses.value
            }

            axios.post('updateirrguarantee', empdetails, {
                }).then(res => {
                    
                    const data = res.data

                    // Object.assign(irrguar, data)
                    irrguarwitnesses.value = data.witnesses
                    toastt('Irrevocable Guarantee successfully updated')

                    saving.value = false
                    log(data)
                    
                }).catch((error) => {
                    toastt('Error. Please Try again', 'error')
                    log(error)
                    saving.value = false
                })
        }


        const uploadappletter = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: appletters.value,
                emp: emp.id,
            }

            axios.post('uploadappletter', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.appletters = data.appletters
                    saving.value = false

                    toastt('The application letter is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadappointmentletter = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: appointmentletters.value,
                emp: emp.id,
            }

            axios.post('uploadappointmentletter', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.appointmentletters = data.appointmentletters
                    saving.value = false

                    toastt('The appointment letter is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadprobationconf = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: probationconfs.value,
                emp: emp.id,
            }

            axios.post('uploadprobationconf', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.probationconfs = data.probationconfs
                    saving.value = false

                    toastt('The probation confirmation detail is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadcv = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: cv.value,
                emp: emp.id,
            }

            axios.post('uploadcv', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.cv = data.cv
                    saving.value = false

                    toastt('The CV is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadpetratrust = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: petratrust.value,
                emp: emp.id,
            }

            axios.post('uploadpetratrust', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.petratrust = data.petratrust
                    saving.value = false

                    toastt('The Petra Trust is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadnhis = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: nhis.value,
                emp: emp.id,
            }

            axios.post('uploadnhis', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.nhis = data.nhis
                    saving.value = false

                    log(data)

                    toastt('The NHIS is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadbirthcert = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: birthcert.value,
                emp: emp.id,
            }

            axios.post('uploadbirthcert', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.birthcert = data.birthcert
                    saving.value = false

                    log(data)

                    toastt('The Birth certificate is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadpclearanceform = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: pclearanceform.value,
                emp: emp.id,
            }

            axios.post('uploadpclearanceform', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.pclearanceform = data.pclearanceform
                    saving.value = false

                    log(data)

                    toastt('The Police Clearance Form is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const uploadssnit = () =>{
            saving.value = true
            
            if(!emp.id){
                toastt('The employee must be created before this', 'error')
                return
            }

            const data = {
                files: ssnit.value,
                emp: emp.id,
            }

            axios.post('uploadssnit', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.ssnit = data.ssnit
                    saving.value = false

                    log(data)

                    toastt('The SSNIT is uploaded')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
    /* Functional method End */

    /* Control flow Start */

        const submitempinfo = () => {
            saving.value = true

            const data = {
                record: emp.id
            }

            axios.post('submitempinfo', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.recordstatus = 1
                    saving.value = false
                    toastt('Your record is sumitted')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const verifyempinfo = () => {
            saving.value = true

            const data = {
                record: emp.id
            }

            axios.post('verifyempinfo', data, {
                }).then(res => {
                    
                    const data = res.data
                    emp.recordstatus = 2
                    saving.value = false
                    toastt('The record is verified')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const approveempinfo = () => {
            saving.value = true

            const data = {
                record: emp.id
            }

            axios.post('approveempinfo', data, {
                }).then(res => {
                    const data = res.data
                    emp.recordstatus = 3
                    saving.value = false
                    toastt('The record is approved')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const moveempinfotorecheck = () => {
            saving.value = true

            const data = {
                record: emp.id
            }

            axios.post('moveempinfotorecheck', data, {
                }).then(res => {
                    const data = res.data
                    emp.recordstatus = 1
                    saving.value = false
                    toastt('The record has been moved back to recheck status')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const moveempinfotodraft = () => {
            saving.value = true

            const data = {
                record: emp.id
            }

            axios.post('moveempinfotodraft', data, {
                }).then(res => {
                    const data = res.data
                    emp.recordstatus = 0
                    saving.value = false
                    toastt('The record has been moved back to draft status')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }


        const submitbankinfo = () => {
            saving.value = true

            const data = {
                record: banksocial.id
            }

            axios.post('submitbankinfo', data, {
                }).then(res => {
                    
                    const data = res.data
                    banksocial.recordstatus = 1
                    saving.value = false
                    toastt('Your record is sumitted')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const verifybankinfo = () => {
            saving.value = true

            const data = {
                record: banksocial.id
            }

            axios.post('verifybankinfo', data, {
                }).then(res => {
                    
                    const data = res.data
                    banksocial.recordstatus = 2
                    saving.value = false
                    toastt('The record is verified')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const approvebankinfo = () => {
            saving.value = true

            const data = {
                record: banksocial.id
            }

            axios.post('approvebankinfo', data, {
                }).then(res => {
                    const data = res.data
                    banksocial.recordstatus = 3
                    saving.value = false
                    toastt('The record is approved')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const movebankinfotorecheck = () => {
            saving.value = true

            const data = {
                record: banksocial.id
            }

            axios.post('movebankinfotorecheck', data, {
                }).then(res => {
                    const data = res.data
                    banksocial.recordstatus = 1
                    saving.value = false
                    toastt('The record has been moved back to recheck status')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const movebankinfotodraft = () => {
            saving.value = true

            const data = {
                record: banksocial.id
            }

            axios.post('movebankinfotodraft', data, {
                }).then(res => {
                    const data = res.data
                    banksocial.recordstatus = 0
                    saving.value = false
                    toastt('The record has been moved back to draft status')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }


        const ssubmitirrguarantee = () => {
            saving.value = true

            const data = {
                record: irrguar.id
            }

            axios.post('ssubmitirrguarantee', data, {
                }).then(res => {
                    
                    const data = res.data
                    irrguar.recordstatus = 1
                    saving.value = false
                    toastt('Your record is sumitted')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const verifyirrguarantee = () => {
            saving.value = true

            const data = {
                record: irrguar.id
            }

            axios.post('verifyirrguarantee', data, {
                }).then(res => {
                    
                    const data = res.data
                    irrguar.recordstatus = 2
                    saving.value = false
                    toastt('The record is verified')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const approveirrguarantee = () => {
            saving.value = true

            const data = {
                record: irrguar.id
            }

            axios.post('approveirrguarantee', data, {
                }).then(res => {
                    const data = res.data
                    irrguar.recordstatus = 3
                    saving.value = false
                    toastt('The record is approved')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const moveirrguaranteetorecheck = () => {
            saving.value = true

            const data = {
                record: irrguar.id
            }

            axios.post('moveirrguaranteetorecheck', data, {
                }).then(res => {
                    const data = res.data
                    irrguar.recordstatus = 1
                    saving.value = false
                    toastt('The record has been moved back to recheck status')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }
        const moveirrguaranteetodraft = () => {
            saving.value = true

            const data = {
                record: irrguar.id
            }

            axios.post('moveirrguaranteetodraft', data, {
                }).then(res => {
                    const data = res.data
                    irrguar.recordstatus = 0
                    saving.value = false
                    toastt('The record has been moved back to draft status')
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }


    /* Control Flow End */


    /*  */
        
        const loadempinfohistories = () => {
            saving.value = true

            empinfohistories.value = []

            const data = {
                record: emp.id
            }

            axios.post('loadempinfohistories', data, {
                }).then(res => {
                    
                    const data = res.data
                    empinfohistories.value = data
                    saving.value = false
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const loadbankinfohistories = () => {
            saving.value = true

            bankinfohistories.value = []

            const data = {
                record: banksocial.id
            }

            axios.post('loadbankinfohistories', data, {
                }).then(res => {
                    
                    const data = res.data
                    bankinfohistories.value = data
                    saving.value = false
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        const loadirrguarinfohistories = () => {
            saving.value = true

            irrguarinfohistories.value = []

            const data = {
                record: irrguar.id
            }

            axios.post('loadirrguarinfohistories', data, {
                }).then(res => {
                    
                    const data = res.data
                    irrguarinfohistories.value = data
                    saving.value = false
                    
                }).catch((error) => {
                    toastt(error.response.data.message, 'error')
                    saving.value = false
                    log(error)
                })
        }

        
    
    /*  */

    /* Tabs and Steps code Start */
        let maintab = ref(1)
        const changetab = (index) => {
            maintab.value = index
        }
        let perinfostep = ref(1)
        const perinfostepnext = () => {
            // const fname = `valEmpPersInfo${perinfostep.value}()`
            // if(eval(fname) === false) return
            
            perinfostep.value ++
        }
        const perinfostepprev = () => {
            perinfostep.value --
        }

        let irrguarstep = ref(1)
        const irrguarstepnext = () => {
            irrguarstep.value ++
        }
        const irrguarstepprev = () => {
            irrguarstep.value --
        }
    /* Tabs and Steps code End */

    const showsearchpopup = () => {
        showsearchpopupvalue.value = true
    }
    const handleempselect = (id) =>{

        axios.post('fetchemp',{
            data: id 
        })
            .then(res => {
                const data = res.data
                log(data)
                bankinfohistories.value = []

                Object.assign(emp, data)
                Object.assign(soccont, data.soccontact || {})
                Object.assign(curwork, data.curwork || {})
                Object.assign(unioninfo, data.unioninfo || {})
                Object.assign(wpermit, data.workpermit || {})
                Object.assign(dlicense, data.driverlicense || {})
                Object.assign(nominee, data.nominee || {})
                
                edus.value = data.educations
                childrens.value = data.childrens
                wives.value = data.wives
                econts.value = data.econs
                workexps.value = data.workexps
                guarantors.value = data.guarantos
                refs.value = data.refs

                Object.assign(banksocial, data.banksocial || {})
                if(data.irrguarantor){
                    Object.assign(irrguar, data.irrguarantor)
                    irrguarwitnesses.value = data.irrguarantor.witnesses
                }else{
                    Object.assign(irrguar, {})
                    irrguarwitnesses.value = []
                }

                iscurwork.value = data.presentjob ? true : false
                isunion.value = data.unioninfo ? true : false
                haschildren.value = data.childrens?.length ? true : false

                showsearchpopupvalue.value = false
            })
            .catch((error) => {
                console.log(error)
            })

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
    
    // const checkdriver = (e) =>{
        
    // }
    
    const confirmPosition = (acceptAction, rejectAction) => {
        confirm.require({
            group: 'positioned',
            message: 'Are you sure you want to leave this page?',
            header: 'Confirmation',
            icon: 'pi pi-info-circle',
            position: 'top',
            rejectProps: {
                label: 'Cancel',
                severity: 'dark',
                text: true
            },
            acceptProps: {
                label: 'Leave',
                severity: 'danger',
                text: true
            },
            accept: () => {
                acceptAction()
            },
            reject: () => {
                rejectAction(false)
            }
        });
    }

    
    const newProfileInput = ref(null);
    const changingProfile = ref(false)
    const savingNewProfile = ref(false)
    const saveprofileimage = () => {
        savingNewProfile.value = true
        axios.post('changeprofilepicture', {newpicture:newprofilepicture, empid:emp.id}, {
            }).then(res => {
                const data = res.data
                
                emp.profilepicture = data.profilepicture
                changingProfile.value = false
                savingNewProfile.value = false

                newProfileInput.value.value = null
                newprofilepicture = []

            })
    }
    

</script>
<template>

    <div>
        <h2>Employee Onboarding</h2>

        <div class="m-5">
            <Button icon='pi pi-search' label="Search for employee to continue" @click="showsearchpopup"></Button>
            
        </div>
        

        <div class="tabs">
            <button
                @click="changetab(1)"
                :class="maintab == 1 ? 'activetab' : '' "
            >Employee Information Form</button>

            <button
                @click="changetab(2)"
                :class="maintab == 2 ? 'activetab' : '' "
                :disabled="!emp.id || !isnotoptional"
            >Bank / Social Security Fund</button>

            <button
                @click="changetab(3)"
                :class="maintab == 3 ? 'activetab' : '' "
                :disabled="!emp.id || (!isnotoptional && !guarantorrequired)"
            >Irrevocable Continuing Guarantee</button>

            <button
                @click="changetab(4)"
                :class="maintab == 4 ? 'activetab' : '' "
                :disabled="!emp.id"
            >Checklist</button>
        </div>

        <div id="form">
            
            <div id="mainform" v-show="maintab == 1">
                <h2>
                    Employee Information Form 
                    <span v-if="emp.id" class='text-mred-500'>( {{ emp.emp_code }} )</span> 
                </h2>

                <div>
                    <div class="steper">
                        Page 
                        <span class="active">{{ perinfostep }}</span> / <span>4</span>
                    </div>
                    
                    <div id='perinfo1' v-show="perinfostep == 1">

                        <div class="group">
                            <h3>Melcom Employee Position Details</h3>

                            <div class="inlinewrap">
                                
                                

                                <div>
                                    <label>Joining Company *:</label>
                                    <select  v-model="emp.company" required>
                                        <option></option>
                                        {{ companies }}
                                        <option
                                            v-for="comp in companies"
                                            :key="comp.id"
                                            :value="comp.id"
                                        >
                                            {{  comp.name }}
                                        </option>
                                    </select>
                                    <span class="error"></span>
                                </div>
                                
                                <div>
                                    <label>Contract Category *:</label>
                                    <select  v-model="emp.contracttype" required>
                                        <option></option>
                                        <option
                                            v-for="ct in conttypes"
                                            :key="ct.code"
                                            :value="ct.code"
                                        >
                                            {{  ct.name }}
                                        </option>
                                    </select>
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Joining Location *:</label>
                                    <select  v-model="emp.joining_branch_id"  required>
                                        <option></option>
                                        <option
                                            v-for="branch in branchs"
                                            :key="branch.id"
                                            :value="branch.id"
                                        >
                                            {{  branch.name }}
                                        </option>
                                    </select>
                                    <span class="error"></span>
                                </div>
                                <div>
                                    <label>Joining Department *:</label>
                                    <select  v-model="emp.joining_dept_id" required>
                                        <option></option>
                                        <option
                                            v-for="dept in depts"
                                            :key="dept.id"
                                            :value="dept.id"
                                        >
                                            {{  dept.name }}
                                        </option>
                                    </select>
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Joining Position  *:</label>
                                    <input type="text" v-model="emp.joiningposition" v-uppercase  required>
                                    <span class="error"></span>
                                </div>
                                
                                <div>
                                    <label>Date of Joining *:</label>
                                    <input type="date" v-model="emp.joiningdate"  required min="1986-01-01">
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                        </div>

                        <div class="group">
                            <h3>Personal Information</h3>

                            <div>
                                <div class="one" v-if="!emp.id">
                                    <label for="">Profile Picture:</label>
                                    <input type="file" multiple @change="addprofileimage($event)" accept="image/*" required>
                                    <span class="error"></span>
                                </div>
                                <div class="one flex align-items-center" v-if="emp.id">
                                    <img
                                        :src="`${imgburl}${emp.profilepicture[0].path}`"
                                        width="150px"
                                        
                                    >
                                    
                                    <div class="ms-10 flex flex-column items-center" v-if="loguser.position_id == 2 && emp.recordstatus == 0">
                                        <Button
                                            label="Change Profile Picture"
                                            class="p-button-info self-center"
                                            @click="changingProfile = !changingProfile"
                                            v-if="!changingProfile"
                                        ></Button>
                                        

                                        <div v-if="changingProfile">
                                            <input
                                                type="file"
                                                ref="newProfileInput"
                                                multiple
                                                @change="changeprofileimage($event)"
                                                accept="image/*"
                                                required
                                            >

                                            <div class="mt-3">
                                                <Button label="Cancel" class="p-button-info" @click="changingProfile = !changingProfile"></Button>

                                                <Button
                                                    label="Save"
                                                    class="p-button-success ml-2"
                                                    @click="saveprofileimage"
                                                    :loading="savingNewProfile"
                                                    :disabled="savingNewProfile"
                                                ></Button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                <div class="triple">
                                    <div>
                                        <label for="firstname">First Name *:</label>
                                        <input type="text" id='firstname' name='firstname'  v-model="emp.firstname" v-uppercase maxlength="250" required >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label for="middlename">Middle Name:</label>
                                        <input type="text" id='middlename' name='middlename'  v-model="emp.middlename" v-uppercase maxlength="250" >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label for="surname">SurName *:</label>
                                        <input type="text" id='surname' name='surname'  v-model="emp.surname" v-uppercase maxlength="250" required >
                                        <span class="error"></span>
                                    </div>
                                    
                                </div>

                                <div class='double'>
                                    <div>
                                        <label>Citizenship *: </label>

                                        
                                        
                                        <select  v-model="emp.citizenship"  required>
                                            <option></option>
                                            <option
                                                v-for="c in countries"
                                                :key="c.code"
                                                :value="c.code"
                                            >
                                                {{  c.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>

                                    <div v-if="eczone == 'EC'">
                                        <label>ID Type *:</label>
                                        
                                        <select v-model="emp.idtype"  required>
                                            <option></option>
                                            <option
                                                v-for="t in idtypes"
                                                :key="t.id"
                                                :value="t.id"
                                            >
                                                {{  t.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <div class='double' v-if="eczone == 'GH'">
                                    <div>
                                        <label for="ghcardno">Ghana Card No *:</label>
                                        <input
                                            type="text"
                                            id="ghcardno"
                                            name="ghcardno"
                                            v-model="emp.ghcardno"
                                            v-uppercase
                                            required
                                            pattern="[A-Za-z]{3}-\d{9}-\d" 
                                            placeholder="GHA-000000000-0"
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label for="">Ghana Card Picture *:</label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="addghcardimage($event)"
                                            accept="image/*"
                                            required
                                            v-if="!emp.id"
                                        >

                                        <span style="display: inline-flex;gap:5px">
                                            <a
                                                v-for="card in emp.ghcard"
                                                :key="card.id"
                                                target="_blank"
                                                :href="`${imgburl}${card.path}`"
                                                v-if="emp.id"
                                            >view</a>
                                        </span>
                                        <span class="error"></span>
                                    </div>
                                </div>

                                <div class='double' v-if="eczone == 'EC' && emp.idtype ">
                                    <div>
                                        <label for="ghcardno">{{ emp.idtype ? findidtypes(emp.idtype) : '' }} No *:</label>
                                        <input
                                            type="text"
                                            id="ghcardno"
                                            name="ghcardno"
                                            v-model="emp.ghcardno"
                                            v-uppercase
                                            required
                                            placeholder=""
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label for="">{{ emp.idtype ? findidtypes(emp.idtype) : '' }} Picture *:</label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="addghcardimage($event)"
                                            accept="image/*"
                                            required
                                            v-if="!emp.id"
                                        >

                                        <span style="display: inline-flex;gap:5px">
                                            <a
                                                v-for="card in emp.ghcard"
                                                :key="card.id"
                                                target="_blank"
                                                :href="`${imgburl}${card.path}`"
                                                v-if="emp.id"
                                            >view</a>
                                        </span>
                                        <span class="error"></span>
                                    </div>
                                </div>

                                <div class='double' v-if="eczone == 'OT'">
                                    <div>
                                        <label for="ghcardno">Passport No *:</label>
                                        <input
                                            type="text"
                                            id="ghcardno"
                                            name="ghcardno"
                                            v-model="emp.ghcardno"
                                            v-uppercase
                                            required
                                            placeholder=""
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label for="">Passport Picture *:</label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="addghcardimage($event)"
                                            accept="image/*"
                                            required
                                            v-if="!emp.id"
                                        >

                                        <span style="display: inline-flex;gap:5px">
                                            <a
                                                v-for="card in emp.ghcard"
                                                :key="card.id"
                                                target="_blank"
                                                :href="`${imgburl}${card.path}`"
                                                v-if="emp.id"
                                            >view</a>
                                        </span>
                                        <span class="error"></span>
                                    </div>
                                </div>

                                <div class="one">
                                    <label for="raddress">Residential Address/Landmark *:</label>
                                    <input
                                        type="text"
                                        id="raddress"
                                        name='raddress'
                                        minlength="6"
                                        v-model="emp.raddress"
                                        
                                        v-uppercase
                                        required
                                    >
                                    
                                </div>
                                <div class="double">
                                    <div>
                                        <label for="daddress">Residential Ghana Digital Address *:</label>
                                        <input
                                            type="text"
                                            id="daddress"
                                            name='daddress'
                                            v-model="emp.daddress"
                                            v-uppercase
                                            minlength="6"
                                            required
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label for="employeeid">Employee ID *:</label>
                                        <input
                                            type="text"
                                            id='employeeid'
                                            maxlength="10"
                                            name="employeeid"
                                            v-model="emp.employeeid"
                                            v-uppercase
                                            required
                                        >
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <div class="double">
                                    <div>
                                        <label for="hometown">Permanent Home Town/Region {{ isnotoptional ? '*' :'' }}:</label>
                                        <input
                                            type="text"
                                            id="hometown"
                                            name="hometown"
                                            v-model="emp.hometown"
                                            minlength="6"
                                            v-uppercase
                                            :required="isnotoptional"
                                        >
                                    </div>
                                    <div>
                                        <label for="hdaddress">Permanent Ghana Digital Address {{ isnotoptional ? '*' :'' }}:</label>
                                        <input
                                            type="text"
                                            name="hdaddress"
                                            v-model="emp.hdaddress"
                                            minlength="6"
                                            v-uppercase
                                            :required="isnotoptional"
                                        >
                                    </div>
                                </div>

                                <div class="double">
                                    <div>
                                        <label for="mobileno">Mobile Number *:</label>
                                        <input
                                            type="text"
                                            name="mobileno"
                                            id="mobileno"
                                            maxlength="13"
                                            minlength="8"
                                            v-model="emp.mobileno"
                                            v-uppercase
                                            required
                                            v-number
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Alternate Phone:</label>
                                        <input
                                            type="tel"
                                            v-model="emp.altnumber"
                                            v-uppercase
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                        >
                                        <span class="error"></span>
                                    </div>
                                </div>

                                <div class="triple">
                                    <div>
                                        <label for="email">Email Address {{ isnotoptional ? '*' :'' }}:</label>
                                        <input type="email"  id="email" name="email" v-model="emp.email" v-uppercase :required="isnotoptional">
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label>Gender *:</label>
                                        
                                        <select  v-model="emp.gender"  required>
                                            <option></option>
                                            <option
                                                v-for="g in genders"
                                                :key="g.id"
                                                :value="g.id"
                                            >
                                                {{  g.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label for="socialsecurityno">Social Security Number {{ ssrequired ? '*' :'' }}:</label>
                                        <input
                                            type="text"
                                            id="socialsecurityno"
                                            maxlength="13"
                                            minlength="13"
                                            name="socialsecurityno"
                                            v-model="emp.socialsecurityno"
                                            v-uppercase
                                            :required="ssrequired"
                                        >
                                        <span class="error"></span>
                                    </div>
                                </div>
                                <div class="triple">
                                    <div>
                                        <label>Date of Birth  *:</label>
                                        <input type="date"  v-model="emp.dob" required :max="formatted16Date" :min="formatted80Date">
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Age: </label>
                                        <input type="text" :value="age" disabled>
                                    </div>
                                    <div>
                                        <label>Marital Status *:</label>
                                        
                                        <select  v-model="emp.maritalstatus"  required>
                                            <option></option>
                                            <option
                                                v-for="ms in mstatus"
                                                :key="ms.id"
                                                :value="ms.id"
                                            >
                                                {{  ms.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                </div>

                                <div
                                    v-if="emp.maritalstatus == 2"
                                    style='border:1px solid #00000033'
                                    class="p-2 my-4 shadow-md border-1 border-indigo-950"
                                >

                                    <h4 class='my-2'>Spouses information</h4>
                                    <Button
                                        class="inbutton !py-2"
                                        v-if="!wives.length"
                                        @click="addwife"

                                    >Add spouse</Button>

                                    <div v-for="(wife, index) in wives" :key="index" class="inline">
                                        <div>
                                            <label>Spouse {{ (index + 1) }}'s  Name *:</label>
                                            <input type="text" v-model="wife.name" v-uppercase  required>
                                            <span class="error"></span>
                                        </div>
                                        <div>
                                            <label>Occupation *:</label>
                                            <input type="text" v-model="wife.occupation" v-uppercase required >
                                            <span class="error"></span>
                                        </div>
                                        <span>
                                            <Button
                                                @click="removewife(index)"
                                                v-if="!wife.id"
                                                icon="pi pi-minus"
                                            ></Button>
                                            <Button
                                                v-if="index === wives.length - 1"
                                                @click="addwife"
                                                icon="pi pi-plus"
                                            ></Button>
                                        </span>
                                    </div>

                                </div>

                                <!-- <div class='double' v-if="">
                                    <div>
                                        <label>Spouse's Name *:</label>
                                        <input type="text"  v-model="emp.spousename" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Occupation *:</label>
                                        <input type="text"  v-model="emp.spouseoccupation" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                </div> -->

                                <div class='double' >
                                    <div>
                                        <label >Father's Name *:</label>
                                        <input type="text"  v-model="emp.fathersname" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label >Mother's Name *:</label>
                                        <input type="text"  v-model="emp.mothersname" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                

                            </div>

                            <h4>
                                Do you know anyone in Melcom? *

                                <span class="radiobuttoncontainer">
                                    <label class="radio-button-container" >No
                                        <input type="radio" name='relativeinorg' required v-model="emp.relativeinorg" :value="0" >
                                        <span class="checkmark"></span>
                                    </label>
                                    <label class="radio-button-container">Yes
                                        <input type="radio" name='relativeinorg' required v-model="emp.relativeinorg" :value="1" >
                                        <span class="checkmark"></span>
                                    </label>
                                </span>

                                
                            </h4>

                            <div class='double' v-if="emp.relativeinorg">
                                <div>
                                    <label >Name *:</label>
                                    <input type="text"  v-model="emp.relative_name" v-uppercase required>
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label> Type of relation *:</label>
                                    <select  v-model="emp.relative_relation"  required>
                                        <option></option>
                                        <option
                                            v-for="relation in relations"
                                            :key="relation.id"
                                            :value="relation.id"
                                        >
                                            {{  relation.name }}
                                        </option>
                                    </select>
                                    <span class="error"></span>
                                </div>
                            </div>


                            <h4>
                                Do you have children?  *

                                <span class="radiobuttoncontainer">
                                    <label class="radio-button-container" >No
                                        <input type="radio" name='haschildren' required v-model="haschildren" :value="false" >
                                        <span class="checkmark"></span>
                                    </label>
                                    <label class="radio-button-container">Yes
                                        <input type="radio" name='haschildren' required v-model="haschildren" :value="true" >
                                        <span class="checkmark"></span>
                                    </label>
                                </span>

                                <!-- <span>
                                    <input type="radio" name='haschildren' required v-model="haschildren" :value="false">No
                                    <input type="radio" name='haschildren' required v-model="haschildren" :value="true">Yes
                                </span> -->
                            </h4>

                            <div v-if="haschildren">

                                <Button
                                    class="inbutton !py-2"
                                    v-if="!childrens.length"
                                    @click="addchild"

                                >Add child</Button>
                                
                                <div v-for="(child, index) in childrens" :key="index" class="inline">
                                    <div>
                                        <label>Child {{ (index + 1) }}'s  Name *:</label>
                                        <input type="text" v-model="child.name" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Age *:</label>
                                        <input type="number" v-model="child.age"  required :max="childmaxdob">
                                        <span class="error"></span>
                                    </div>
                                    <span>
                                        <Button
                                            @click="removechild(index)"
                                            v-if="!child.id"
                                            icon="pi pi-minus"
                                        ></Button>
                                        <Button
                                            v-if="index === childrens.length - 1"
                                            @click="addchild"
                                            icon="pi pi-plus"
                                        ></Button>
                                    </span>
                                </div>
                                
                            </div>

                        </div>                       

                        <div class="group" v-if="emp.contracttype == 'expat'">
                            <h3>Work Permit Details</h3>

                            <div class="inlinewrap">

                                <div>
                                    <label>Number Of Renewal *:</label>
                                    <input type="number" v-model="wpermit.renewalno" v-uppercase  required>
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Passport Expiry Date *:</label>
                                    <input type="date" v-model="wpermit.passedate"  required >
                                    <span class="error"></span>
                                </div>
                                <div>
                                    <label>Work Permit Date *:</label>
                                    <input type="date" v-model="wpermit.idate"  required >
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Work Permit Expiry Date *:</label>
                                    <input type="date" v-model="wpermit.edate"  required >
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Work Permit Number *:</label>
                                    <input type="text" v-model="wpermit.number" v-uppercase  required>
                                    <span class="error"></span>
                                </div>

                            </div>
                        </div>

                        <div class="group" v-if="emp.joiningposition == 'DRIVER'">
                            <h3>Driver's Licence Details</h3>

                            <div class="inlinewrap">
                                <div>
                                    <label>Name on the license  *:</label>
                                    <input type="text" v-model="dlicense.name" v-uppercase  required>
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>license#  *:</label>
                                    <input type="text" v-model="dlicense.licenseno" v-uppercase  required>
                                    <span class="error"></span>
                                </div>
                                
                                <div>
                                    <label>Issuing Date *:</label>
                                    <input type="date" v-model="dlicense.idate"  required >
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Expiry Date *:</label>
                                    <input type="date" v-model="dlicense.edate"  required >
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Ref#  *:</label>
                                    <input type="text" v-model="dlicense.ref" v-uppercase  required>
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Renewal  Date *:</label>
                                    <input type="date" v-model="dlicense.rdate"  required >
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label for="">License Picture:</label>
                                    <input
                                        type="file"
                                        multiple
                                        @change="addlicencsefile($event, index)"
                                        accept="image/*"
                                        v-if="!dlicense.id"
                                    >
                                    <a
                                        v-for="file in dlicense.files"
                                        target="_blank"
                                        :key="file.id"
                                        :href="`${imgburl}${file.path}`"
                                        v-if="dlicense.id" 
                                    >view</a>
                                    <span class="error"></span>
                                </div>
                                
                            </div>
                        </div>


                        <div class="group">
                            <h3><!-- Present  -->Job at Melcom</h3>
                            <h4>
                                Have you ever worked with Melcom? *
                                <span class="radiobuttoncontainer">
                                    <label class="radio-button-container" >No
                                        <input type="radio" name='iscurwork' required v-model="iscurwork" :value="false" >
                                        <span class="checkmark"></span>
                                    </label>
                                    <label class="radio-button-container">Yes
                                        <input type="radio" name='iscurwork' v-model="iscurwork" :value="true" >
                                        <span class="checkmark"></span>
                                    </label>
                                </span>
                            </h4>

                            <div v-if="iscurwork">
                                <h4>Details of <!-- Present --> Job at Melcom</h4>
                                <div class="inlinewrap">
                                    <div>
                                        <label>Title  *:</label>
                                        <input type="text" v-model="curwork.title" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Employee ID *:</label>
                                        <input type="text" v-model="curwork.employeeid" maxlength="10" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Date of Joining *:</label>
                                        <input type="date" v-model="curwork.doj"  required min="1986-01-01">
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Department *:</label>
                                        <select  v-model="curwork.dept_id" required>
                                            <option></option>
                                            <option
                                                v-for="dept in depts"
                                                :key="dept.id"
                                                :value="dept.id"
                                            >
                                                {{  dept.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Branch *:</label>
                                        <select  v-model="curwork.branch_id"  required>
                                            <option></option>
                                            <option
                                                v-for="branch in branchs"
                                                :key="branch.id"
                                                :value="branch.id"
                                            >
                                                {{  branch.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Region *:</label>
                                        <select  v-model="curwork.region_id"  required>
                                            <option></option>
                                            <option
                                                v-for="region in regions"
                                                :key="region.id"
                                                :value="region.id"
                                            >
                                                {{  region.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="group">
                            <h3>Union membership information</h3>
                            <h4>
                                Are you Union Member? *
                                <span class="radiobuttoncontainer">
                                    <label class="radio-button-container" >No
                                        <input type="radio" name='isunion' required v-model="isunion" :value="false" >
                                        <span class="checkmark"></span>
                                    </label>
                                    <label class="radio-button-container">Yes
                                        <input type="radio" name='isunion' v-model="isunion" :value="true" >
                                        <span class="checkmark"></span>
                                    </label>
                                </span>
                            </h4>

                            <div v-if="isunion">
                                <h4>Details of Membership</h4>
                                <div class="inlinewrap">
                                    <div>
                                        <label>Union name  *:</label>
                                        <input type="text" v-model="unioninfo.union_name" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Union Registration date *:</label>
                                        <input type="date" v-model="unioninfo.union_regdate"  v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    
                                    <div>
                                        <label>Union Registration Number *:</label>
                                        <input type="text" v-model="unioninfo.union_number" maxlength="250" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3>ANY OTHER INFORMATION</h3>
                            <textarea  v-model="emp.anyotherinfo" rows="5"  v-uppercase></textarea>
                        </div>

                        <div class="stepsbutton">
                            <button disabled>prev</button>

                            <!-- <Button @click="perinfostepnext" label="Next" severity="info" raised ></Button> -->
                            <button @click="perinfostepnext">Next</button>
                        </div>
                    </div>

                    <div id='perinfo2'  v-show="perinfostep == 2">
                        <div class="group">
                            <h3>Emergency Contact Information [Relatives Only]</h3>

                            <Button
                                @click="addecont"
                                v-if="!econts.length"
                                class="!py-2"
                            >Add</Button>

                            <div v-for="(econt,index) in econts" :key="index">

                                
                                <h4>CONTACT NO{{ index + 1 }}</h4>
                                <div class="inlinewrap">
                                    <div>
                                        <label >Full Name *:</label>
                                        <input type="text" v-model="econt.fullname" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Relationship to you *:</label>
                                        <select  v-model="econt.relation" required>
                                            <option></option>
                                            <option
                                                v-for="relation in familyrelation"
                                                :key="relation.id"
                                                :value="relation.id"
                                            >
                                                {{  relation.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label >Work Address preferable:</label>
                                        <input type="text" v-model="econt.workaddress" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label >Ghana Card No :</label>
                                        <input
                                            type="text"
                                            v-model="econt.ghcardno"
                                            v-uppercase
                                            pattern="[A-Za-z]{3}-\d{9}-\d" 
                                            placeholder="GHA-000000000-0"
                                        >
                                        <span class="error"></span>
                                    </div>
                                    
                                    <div>
                                        <label for="">Ghana Card :</label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="addecontfiles($event, index)"
                                            accept="image/*"
                                            v-if="!econt.id"
                                        >
                                        <a
                                            v-for="file in econt.files"
                                            target="_blank"
                                            :key="file.id"
                                            :href="`${imgburl}${file.path}`"
                                            v-if="econt.id" 
                                        >view</a>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Mobile Number *:</label>
                                        <input
                                            type="tel"
                                            v-model="econt.mobileno"
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                            v-uppercase
                                            required
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Alternate Phone:</label>
                                        <input
                                            type="tel"
                                            v-model="econt.altnumber"
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                            v-uppercase
                                        >
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        Next of kin?  *

                                        <span class="radiobuttoncontainer">
                                            <label class="radio-button-container" >No
                                                <input type="radio" name='isnextofkin' v-model="econt.isnextofkin" value="0"  required >
                                                <span class="checkmark"></span>
                                            </label>
                                            <label class="radio-button-container">Yes
                                                <input type="radio" name='isnextofkin' v-model="econt.isnextofkin" value="1" required >
                                                <span class="checkmark"></span>
                                            </label>
                                        </span>
                                        
                                    </div>
                                </div>
                                <span >
                                    
                                    <Button
                                        v-if="!econt.id"
                                        @click="removeecont(index)"
                                        class="!py-2"
                                    >Remove</Button>
                                    <Button
                                        v-if="index === econts.length - 1"
                                        @click="addecont"
                                        class="!py-2 !px-5"
                                    >Add</Button>
                                </span>
                            </div>


                        </div>
                    

                        <div class="group">
                            <h3>Social Contact Information</h3>
                            <div>
                                <div class="inlinewrap">
                                    <div>
                                        <label >Church / Mosque Membership & Location:</label>
                                        <input type="text" v-model="soccont.churchmemberloc"  v-uppercase>
                                    </div>
                                    
                                    <div>
                                        <label>Pastor Religious Leader:</label>
                                        <input type="text" v-model="soccont.pastor"  v-uppercase>
                                    </div>
                                    <div>
                                        <label >Duration of Membership:</label>
                                        <input type="number" v-model="soccont.memduration"  v-uppercase>
                                    </div>
                                    <div>
                                        <label>Mobile Number:</label>
                                        <input
                                            type="tel"
                                            v-model="soccont.mobileno"
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                            v-uppercase
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Ghana Card No:</label>
                                        <input
                                            type="text"
                                            v-model="soccont.ghcardno"
                                            v-uppercase
                                            pattern="[A-Za-z]{3}-\d{9}-\d" 
                                            placeholder="GHA-000000000-0"
                                        >
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label for="">Closest Friend/Confidant:</label>
                                        <input type="text" v-model="soccont.closestfriend"  v-uppercase>
                                    </div>

                                    <div>
                                        <label for="">Contacts Phone:</label>
                                        <input
                                            type="tel"
                                            v-model="soccont.contactsphone"
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                            v-uppercase
                                        >
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label for="">Email Address:</label>
                                        <input type="email" v-model="soccont.email" v-uppercase>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label for="">Digital Address:</label>
                                        <input type="text" v-model="soccont.digitaladdress" v-uppercase>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label >Work Address preferable:</label>
                                        <input type="text" v-model="soccont.workaddress"  v-uppercase>
                                    </div>
                                    
                                </div>
                            </div>


                            
                        </div>

                        
                        <div class="stepsbutton">
                            <button @click="perinfostepprev">prev</button>
                            <button @click="perinfostepnext">Next</button>
                        </div>
                    </div>

                    <div id='perinfo3' v-show="perinfostep == 3">
                        <div class="group">
                            <h3>Educational Background</h3>

                            <Button
                                @click="addedu"
                                v-if="!edus.length"
                                class="!py-2"
                            >Add</Button>

                            <div>
                                <div v-for="(edu, index) in edus" :key="index" class="inline">
                                    
                                    <div>
                                        <label for="">Qualification Type *:</label>
                                        <select  v-model="edu.educqualtype" required>
                                            <option></option>
                                            <option
                                                v-for="q in qualtypes"
                                                :key="q.id"
                                                :value="q.id"
                                            >
                                                {{  q.name }}
                                            </option>
                                        </select>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label>Specialization *:</label>
                                        <input type="text" v-model="edu.educqual"  v-uppercase required>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label >Date Of Completion:</label>
                                        <input type="date" v-model="edu.to" :min="emp.dob" max="2222-05-26">
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label>Educational Qualification:</label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="addedusfile($event, index)"
                                            accept="image/*"
                                            v-if="!edu.id"
                                        >

                                        <a
                                            v-for="file in edu.files"
                                            :key="file.id"
                                            target="_blank"
                                            :href="`${imgburl}${file.path}`"
                                            v-if="edu.id"
                                        >view</a>
                                        <span class="error"></span>
                                    </div>

                                    <span >
                                        <Button
                                            @click="removeedu(index)"
                                            v-if="!edu.id"
                                            icon="pi pi-minus"
                                        ></Button>
                                        <Button
                                            @click="addedu"
                                            v-if="index === edus.length - 1"
                                            icon="pi pi-plus"
                                        ></Button>
                                        
                                    </span>

                                </div>
                            </div>


                            <!-- <div>
                                Are you a borrower of the Student Loan Trust Fund?
                                <span class="radiobuttoncontainer">
                                    <label class="radio-button-container" >No
                                        <input type="radio" name="studentloan" v-model="emp.studentloan" value="0" required >
                                        <span class="checkmark"></span>
                                    </label>
                                    <label class="radio-button-container">Yes
                                        <input type="radio" name="studentloan" v-model="emp.studentloan" value="1" required >
                                        <span class="checkmark"></span>
                                    </label>
                                </span>
                                
                            </div> -->

                            
                        </div>

                        <div class="group">
                            <h3>WORK EXPERIENCE</h3>

                            <Button
                                @click="addworkexp"
                                v-if="!workexps.length"
                                class="!py-2"
                            >Add</Button>
                            <div >
                                <div v-for="(wexp, ind) in workexps" :key="ind" class="inline">
                                    <div>
                                        <label >Name of Organization *:</label>
                                        <input type="text" v-model="wexp.orgname" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    
                                    <div>
                                        <label>Position held *:</label>
                                        <input type="text" v-model="wexp.postheld" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label >From *:</label>
                                        <input type="date" v-model="wexp.from" required max="2222-05-26">
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label >To:</label>
                                        <input type="date" v-model="wexp.to" max="2222-05-26">
                                    </div>

                                    <span >
                                        
                                        <Button
                                            @click="removeworkexp(ind)"
                                            v-if="!wexp.id"
                                            icon="pi pi-minus"
                                        ></Button>
                                        <Button
                                            v-if="ind === workexps.length - 1"
                                            @click="addworkexp"
                                            icon="pi pi-plus"
                                        ></Button>
                                    </span>
                                </div>
                            </div>

                        </div>

                        <div class="group">
                            <h3>REFERENCES</h3>

                            <Button
                                @click="addref"
                                v-if="!refs.length"
                                class="!py-2"
                            >Add</Button>

                            <div v-for="(ref , ind) in refs" :key="ind" >
                                <h4>REFERENCE 1</h4>
                                <div class="inline">
                                    <div>
                                        <label >Name *:</label>
                                        <input type="text" v-model="ref.name" v-uppercase required>
                                        <span class="error"></span>
                                    </div>
                                    
                                    <div>
                                        <label>Company Name *:</label>
                                        <input type="text" v-model="ref.companyname" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label>Designation *:</label>
                                        <input type="text" v-model="ref.designation" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Contact Number *:</label>
                                        <input
                                            type="tel"
                                            v-model="ref.contactno"
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                            required
                                        >
                                        <span class="error"></span>
                                    </div>
                                    <div>
                                        <label>Email:</label>
                                        <input type="email" v-model="ref.email" v-uppercase>
                                    </div>
                                    <span >
                                        
                                        <Button
                                            @click="removeref(ind)"
                                            v-if="!ref.id"
                                            icon="pi pi-minus"
                                        ></Button>
                                        <Button
                                            v-if="ind === refs.length - 1"
                                            @click="addref"
                                            icon="pi pi-plus"
                                        ></Button>
                                    </span>
                                </div>
                            </div>


                            
                        </div>

                        <div class="group">
                            <h3>Nominee Information <span class="font-normal">(Next of Kin)</span> </h3>

                            <div class="inlinewrap">
                                <div>
                                    <label >Name {{ isnomineenotoptional ? '*' :'' }}:</label>
                                    <input type="text" v-model="nominee.name" v-uppercase :required="isnomineenotoptional">
                                    <span class="error"></span>
                                </div>

                                <div>
                                    <label>Relationship to you {{ isnomineenotoptional ? '*' :'' }}:</label>
                                    <select  v-model="nominee.relation" :required="isnomineenotoptional">
                                        <option></option>
                                        <option
                                            v-for="relation in familyrelation"
                                            :key="relation.id"
                                            :value="relation.id"
                                        >
                                            {{  relation.name }}
                                        </option>
                                    </select>
                                    <span class="error"></span>
                                </div>
                                
                                <div>
                                    <label>Mobile Number {{ isnomineenotoptional ? '*' :'' }}:</label>
                                    <input
                                        type="tel"
                                        v-model="nominee.mobile"
                                        maxlength="13"
                                        minlength="8"
                                        v-number
                                        v-uppercase
                                        :required="isnomineenotoptional"
                                    >
                                    <span class="error"></span>
                                </div>
                                
                                <div>
                                    <label for="">ID:</label>
                                    <input
                                        type="file"
                                        multiple
                                        @change="addnomineedfile($event, index)"
                                        accept="image/*"
                                        v-if="!nominee.id"
                                    >
                                    <a
                                        v-for="file in nominee.files"
                                        target="_blank"
                                        :key="file.id"
                                        :href="`${imgburl}${file.path}`"
                                        v-if="nominee.id" 
                                    >view</a>
                                    <span class="error"></span>
                                </div>
                                

                                
                            </div>
                            
                        </div>

                        <div class="stepsbutton">
                            <button @click="perinfostepprev">prev</button>
                            <button @click="perinfostepnext">Next</button>
                        </div>
                    </div>

                    <!-- <div id='perinfo4' v-show="perinfostep == 4">
                        <div class="group">
                            <h3>WITNESSES (MIN 2)</h3>

                            <Button @click="addguar" v-if="!guarantors.length" class="!py-2">Add</Button>

                            <div v-for="(g , ind) in guarantors" :key="ind" >
                                <h4>WITNESS {{ ind + 1 }}</h4>
                                <div class="inline">
                                    <div>
                                        <label >Name *:</label>
                                        <input type="text" v-model="g.name" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>
                                    
                                    <div>
                                        <label>Residential address *:</label>
                                        <input type="text" v-model="g.address" v-uppercase  required>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label for="email">Email Address :</label>
                                        <input type="email"v-model="g.email" v-uppercase>
                                        <span class="error"></span>
                                    </div>

                                    <div>
                                        <label>Mobile No *:</label>
                                        <input
                                            type="tel"
                                            v-model="g.mobileno"
                                            maxlength="13"
                                            minlength="8"
                                            v-number
                                            v-uppercase
                                            required
                                        >
                                        <span class="error"></span>
                                    </div>
                                    
                                    <div>
                                        <label>ID *:</label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="addguarfiles($event, ind)"
                                            accept="image/*"
                                            required
                                            v-if="!g.id"
                                        >
                                        <a
                                            v-for="file in g.files"
                                            target="_blank"
                                            :key="file.id"
                                            :href="`${imgburl}${file.path}`"
                                            v-if="g.id"
                                        >view</a>
                                        <span class="error"></span>
                                    </div>
                                    <span >
                                        
                                        <Button
                                            @click="removeguar(ind)"
                                            v-if="!g.id"
                                            icon="pi pi-minus"
                                        ></Button>
                                        <Button
                                            v-if="ind === guarantors.length - 1"
                                            @click="addguar"
                                            icon="pi pi-plus"
                                        ></Button>
                                    </span>
                                </div>
                            </div>


                            
                        </div>

                        <div class="stepsbutton">
                            <button @click="perinfostepprev">prev</button>
                            <button @click="perinfostepnext">Next</button>
                        </div>
                    </div> -->

                    <div id='perinfo4' v-show="perinfostep == 4">
                        <div class="group">
                            <h3>EMPLOYEE'S DECLARATION</h3>

                            <div>
                                <p>
                                    I <strong> {{ emp.fullname }}</strong> declare that the information provided above is true. In case of false declaration, the appropriate action as per company’s policy will be taken.
                                </p>
                                <p>
                                    Signature: 
                                    <Button
                                        v-if='!emp.id'
                                        @click="employeesigning = true"
                                        :label=" emp.signature? 'Change Signature':'Sign' "
                                        icon="pi pi-pencil"
                                    > </Button> 


                                    <div v-if="employeesigning" >
                                        
                                        <Vue3Signature
                                            style="border: 1px solid var(--primary);"
                                            ref="signature1"
                                            :sigOption="state.option"
                                            :w="'400px'"
                                            :h="'300px'"
                                            class="my-2"
                                        ></Vue3Signature>

                                        <div class="w-[400px] flex justify-center">
                                            <Button @click="savesignature1('image/jpeg')" severity="danger">Save</Button>
                                            <Button @click="clearsignature1">Clear</Button>
                                            <Button @click="cancelsignature1" severity="secondary">Cancel</Button>

                                            <FileUpload mode="basic" customUpload auto class="p-button-info ms-3" accept="image/*" @select="loadsignature1($event)" chooseLabel="Upload" />
                                        </div>
                                        
                                    </div> 
                                    <div v-if="!emp.id && emp.signature">
                                        <img :src="emp.signature" width="200" >
                                    </div>
                                    <div v-if="emp.id">
                                        <img :src="`${imgburl}${emp.signature.path}`" width="200" >
                                    </div>
                                    <br><br>
                                    Date: <strong> {{ emp.id ? aDate(emp.created_at) : today }} </strong>
                                </p>
                                
                            </div>

                            <!-- <div>
                                <span>
                                    <input type="checkbox" name="agreetotems" id='agreetotems' v-model="agreetotems">
                                    <label for="agreetotems">I, {{ emp.fullname }}, agree to the terms and conditions</label>
                                </span>
                                

                                <div v-if="agreetotems && !otprequested">
                                    <button>Get OPT</button>
                                </div>
                            </div> -->


                            <div>
                                
                                <p>
                                    Guarantor Signature: 
                                    <Button
                                        v-if='!emp.id || !emp.guarsignature'
                                        @click="employeeguarsigning = true"
                                        :label=" emp.guarsignature? 'Change Signature':'Sign' "
                                        icon="pi pi-pencil"
                                    > </Button> 


                                    <div v-if="employeeguarsigning" >
                                        
                                        <Vue3Signature
                                            style="border: 1px solid var(--primary);"
                                            ref="signature3"
                                            :sigOption="state.option"
                                            :w="'400px'"
                                            :h="'300px'"
                                            class="my-2"
                                        ></Vue3Signature>

                                        <div class="w-[400px] flex justify-center">
                                            <Button @click="savesignature3('image/jpeg')" severity="danger">Save</Button>
                                            <Button @click="clearsignature3">Clear</Button>
                                            <Button @click="cancelsignature3" severity="secondary">Cancel</Button>
                                            
                                            <FileUpload mode="basic" customUpload auto class="p-button-info ms-3" accept="image/*" @select="loadsignature3($event)" chooseLabel="Upload" />
                                        </div>
                                        
                                    </div> 
                                    <div v-if="!emp.id && emp.guarsignature">
                                        <img :src="emp.guarsignature" width="200" >
                                    </div>
                                    <div v-if="emp.id && emp.guarsignature">
                                        <img :src="`${imgburl}${emp.guarsignature?.path}`" width="200" >
                                    </div>
                                    
                                </p>
                                
                            </div>
                            
                        </div>

                        <div class="stepsbutton">
                            <button @click="perinfostepprev">prev</button>
                            <button disabled>Next</button>
                        </div>
                    </div>
                    
                    
                    <div v-if="!emp.id && loguser.position_id == 2">
                        
                        <Button
                            label="Save"
                            @click="submit"
                            :loading="saving"
                            :disabled="saving || emp.id"
                            class='!py-3 !px-12 mt-5'
                        ></Button>

                    </div>

                    <div v-if="emp.id">

                        <div v-if="loguser.position_id == 2 && emp.recordstatus == 0">

                            <Button
                                @click="updateemployee"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5'
                                label="Update"
                                
                            ></Button>

                            <Button
                                @click="submitempinfo"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Submit"
                                v-if="emp.recordstatus == 0"
                                severity="danger"
                            ></Button>

                        </div>

                        <div v-else-if="loguser.position_id == 3 && emp.recordstatus == 1">

                            <Button
                                @click="updateemployee"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 '
                                label="Update"
                            ></Button>

                            <Button
                                @click="verifyempinfo"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Verify"
                                severity="danger"
                            ></Button>

                            <Button
                                @click="moveempinfotodraft"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Move to Draft"
                                severity="info"
                            ></Button> 

                        </div>

                        <div v-else-if="loguser.position_id == 4">

                            <span v-if="emp.recordstatus == 2">

                                <Button
                                    @click="updateemployee"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5'
                                    label="Update"
                                ></Button> 

                                <Button
                                    @click="approveempinfo"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5 ms-3 me-3'
                                    label="Approve"
                                    severity="danger"
                                ></Button>
                                
                            </span>

                            <span v-if="emp.recordstatus == 3 || emp.recordstatus == 2">

                                <Button
                                    @click="moveempinfotorecheck"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5'
                                    label="Move to recheck"
                                    severity="info"
                                ></Button> 

                            </span>


                        </div>
                        
                        <!-- <Button
                            label="Update"
                            :loading="saving"
                            class='!py-3 !px-12 mt-5'
                            @click="updateemployee"
                            :disabled="saving"
                        ></Button> -->
                         
                    </div>

                    <div v-if="emp.id" class="mt-3">
                        Current status: <strong>{{ findrecordstatus(emp.recordstatus) }}</strong>
                    </div>

                    <div v-if="emp.id && emp.lasthistory.length">

                        <div class='flex justify-between items-baseline my-5'>
                            <h3>Ativity log</h3>

                            <span>
                                <span class="pe-3">
                                    <b>Total</b>: 
                                    {{ empinfohistories.length ? empinfohistories.length : emp.lasthistory.length }} 
                                </span>

                                <Button
                                    @click="loadempinfohistories"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-2 !px-10 mt-5'
                                    label="Load all"
                                    v-if="emp.lasthistory.length == 10 && !empinfohistories.length"
                                ></Button>

                            </span>
                        </div>

                        <div class="card" v-if="!empinfohistories.length">
                            <DataTable :value="emp.lasthistory" tableStyle="min-width: 50rem">
                                <Column field="created_at" header="Date & time">
                                    <template #body="slotProps">
                                        {{ afDate(slotProps.data.created_at) }}
                                    </template>
                                </Column>
                                <Column field="details" header="Details"></Column>
                                
                                <Column field="recordstatus" header="Status">
                                    <template #body="slotProps">
                                        {{ findrecordstatus(slotProps.data.recordstatus) }}
                                    </template>
                                </Column>
                                <Column field="creator.name" header="User"></Column>
                            </DataTable>
                        </div>

                        <div class="card" v-if="empinfohistories.length">
                            
                            <DataTable :value="empinfohistories" tableStyle="min-width: 50rem">
                                <Column field="created_at" header="Date & time">
                                    <template #body="slotProps">
                                        {{ afDate(slotProps.data.created_at) }}
                                    </template>
                                </Column>
                                <Column field="details" header="Details"></Column>
                                
                                <Column field="recordstatus" header="Status">
                                    <template #body="slotProps">
                                        {{ findrecordstatus(slotProps.data.recordstatus) }}
                                    </template>
                                </Column>
                                <Column field="creator.name" header="User"></Column>
                            </DataTable>
                        </div>
                    </div>

                </div>

            </div>

            <div id="banksocial" v-show="maintab == 2">
                <h2>Bank / Social Security Fund Contribution Details</h2>

                <div id='socialcontact' class='group'>
                    
                        <h3>Information</h3>

                    <div class='double' >
                        <div>
                            <label for="bankname">Name of Bank *:</label>
                            <select  v-model="banksocial.bankname" name="bankname" required>
                                <option></option>
                                <option
                                    v-for="bat in banks"
                                    :key="bat.id"
                                    :value="bat.id"
                                >
                                    {{  bat.name }}
                                </option>
                            </select>
                            <span class="error"></span>
                        </div>
                        <div>
                            <label for="bankbranch">Bank Branch *:</label>
                            <input type="text" v-model="banksocial.bankbranch" name="bankbranch" required  v-uppercase>
                            <span class="error"></span>
                        </div>
                    </div>
                    <div class='double' >
                        <div>
                            <label for="accountname">Account Name *:</label>
                            <input type="text" v-model="banksocial.accountname" name="accountname" required v-uppercase>
                            <span class="error"></span>
                        </div>
                        <div>
                            <label for="accounttype">Account Type *:</label>
                            <select  v-model="banksocial.accounttype" name="accounttype" required>
                                <option></option>
                                <option
                                    v-for="bat in bankaccounttype"
                                    :key="bat.id"
                                    :value="bat.id"
                                >
                                    {{  bat.name }}
                                </option>
                            </select>
                            <span class="error"></span>
                        </div>
                    </div>
                    <div class='double' >
                        <div>
                            <label for="accountnumber">Account Number *:</label>
                            <input
                                type="text"
                                v-model="banksocial.accountnumber"
                                name="accountnumber"
                                required
                                v-uppercase
                                maxlength="16"
                                minlength="8"
                                v-number
                            >
                            <span class="error"></span>
                        </div>
                        <div>
                            <label for="socialfundnumber">Social Security Fund Number {{ ssrequired ? '*' : ''}} :</label>
                            <input
                                type="tel"
                                v-model="banksocial.socialfundnumber"
                                :required="ssrequired"
                                name='socialfundnumber'
                                maxlength="13"
                                minlength="13"
                            >
                            <span class="error"></span>
                        </div>
                    </div>
                    
                    <div v-if="!banksocial.id && loguser.position_id == 2">
                        <Button
                            @click="submitbanksocial"
                            :disabled="saving"
                            :loading="saving"
                            class='!py-3 !px-12 mt-5 '
                            label="Save"
                        ></Button>
                    </div>
                    <div v-if="banksocial.id">

                        <div v-if="loguser.position_id == 2 && banksocial.recordstatus == 0">

                            <Button
                                @click="editbanksocial"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5'
                                label="Update"
                                
                            ></Button>

                            <Button
                                @click="submitbankinfo"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Submit"
                                severity="danger"
                                v-if="banksocial.recordstatus == 0"
                            ></Button>

                        </div>
                    
                        <div v-else-if="loguser.position_id == 3 && banksocial.recordstatus == 1">

                            <Button
                                @click="editbanksocial"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 '
                                label="Update"
                            ></Button>

                            <Button
                                @click="verifybankinfo"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Verify"
                                severity="danger"
                            ></Button>

                            <Button
                                @click="movebankinfotodraft"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Move to Draft"
                                severity="info"
                            ></Button> 

                        </div>

                        <div v-else-if="loguser.position_id == 4">

                            <span v-if="banksocial.recordstatus == 2">

                                <Button
                                    @click="editbanksocial"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5'
                                    label="Update"
                                ></Button> 

                                <Button
                                    @click="approvebankinfo"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5 ms-3 me-3'
                                    label="Approve"
                                    severity="danger"
                                ></Button>
                                
                            </span>

                            <span v-if="banksocial.recordstatus == 3 || banksocial.recordstatus == 2">

                                <Button
                                    @click="movebankinfotorecheck"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5'
                                    label="Move to recheck"
                                    severity="info"
                                ></Button> 

                            </span>
                            

                        </div>

                    </div>


                    <div v-if="banksocial.id">
                        Current status: <strong>{{ findrecordstatus(banksocial.recordstatus) }} </strong>
                    </div>

                    <div v-if="banksocial.id && banksocial.lasthistory.length">
                        
                        <div class='flex justify-between items-baseline mb-5'>

                            <h3>Ativity log</h3>

                            <span>
                                <span class="pe-3">
                                    <b>Total</b>: 
                                    {{ bankinfohistories.length ? bankinfohistories.length : banksocial.lasthistory.length }} 
                                </span>

                                <Button
                                    @click="loadbankinfohistories"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-2 !px-10 mt-5'
                                    label="Load all"
                                    v-if="banksocial.lasthistory.length == 10 && !bankinfohistories.length"
                                ></Button>

                            </span>
                        </div>
                        
                        
                        <div class="card" v-if="!bankinfohistories.length">
                            <DataTable :value="banksocial.lasthistory" tableStyle="min-width: 50rem">
                                <Column field="created_at" header="Date & time">
                                    <template #body="slotProps">
                                        {{ afDate(slotProps.data.created_at) }}
                                    </template>
                                </Column>
                                <Column field="details" header="Details"></Column>
                                
                                <Column field="recordstatus" header="Status">
                                    <template #body="slotProps">
                                        {{ findrecordstatus(slotProps.data.recordstatus) }}
                                    </template>
                                </Column>
                                <Column field="creator.name" header="User"></Column>
                            </DataTable>
                        </div>
                        
                        
                        <div class="card" v-if="bankinfohistories.length">
                            
                            <DataTable :value="bankinfohistories" tableStyle="min-width: 50rem">
                                <Column field="created_at" header="Date & time">
                                    <template #body="slotProps">
                                        {{ afDate(slotProps.data.created_at) }}
                                    </template>
                                </Column>
                                <Column field="details" header="Details"></Column>
                                
                                <Column field="recordstatus" header="Status">
                                    <template #body="slotProps">
                                        {{ findrecordstatus(slotProps.data.recordstatus) }}
                                    </template>
                                </Column>
                                <Column field="creator.name" header="User"></Column>
                            </DataTable>
                        </div>

                    </div>
                </div>
            </div>

            <div id="irrguarantor" v-show="maintab == 3">
                <h2> Declaration Document Attachment of irrevocable and Witness Sign</h2>
                <div >
                    <div class="steper">
                        Page 
                        <span class="active">{{ irrguarstep }}</span> / <span>3</span>
                    </div>
                    <div id="irrguar1" v-show="irrguarstep == 1" class="group">
                        <h3 >Information Form</h3>

                        <div class='one' v-if="!irrguar.id">
                            <label>Profile Picture:</label>
                            <input type="file" multiple required @change="addguarprofileimage($event)" accept="image/*" >
                            <span class="error"></span>
                        </div>
                        <div class="one" v-if="irrguar.id">
                            <img
                                :src="`${imgburl}${irrguar.profilepicture.path}`"
                                width="150px"
                            >
                        </div>

                        <div class='one' v-if="!irrguar.id">
                            <label>Guarantor form to be uploaded:</label>
                            <input type="file" multiple required @change="addguarform($event)" accept="image/*" >
                            <span class="error"></span>
                        </div>
                        <div class='one' v-if="irrguar.id">
                            <!-- <label>Guarantor form to be uploaded:</label>
                            <input type="file" multiple required @change="addguarform($event)" accept="image/*" >
                            <span class="error"></span> -->

                            <span style="display: inline-flex;gap:5px">
                                <a
                                    v-for="card in irrguar.guarforms"
                                    :key="card.id"
                                    target="_blank"
                                    :href="`${imgburl}${card.path}`"
                                    v-if="emp.id"
                                >view</a>
                            </span>
                        </div>

                        <div class='one' v-if="!irrguar.id">
                            <label>ID Card:</label>
                            <input type="file" multiple required @change="addguaridimage($event)" accept="image/*" >
                            <span class="error"></span>
                        </div>

                        <div class='double' >
                            <div>
                                <label for="">Name of Guarantor *:</label>
                                <input type="text" name="guarname" required v-model="irrguar.guarname"  v-uppercase>
                                <span class="error"></span>
                            </div>
                            <div>
                                <label for="">SSF No *:</label>
                                <input type="text" name="ssfno" required v-model="irrguar.ssfno"  v-uppercase>
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class='double' >
                            <div>
                                <label for="">Annual Income *:</label>
                                <input
                                    type="text"
                                    name="annualincome"
                                    maxlength="10"
                                    required
                                    v-model="irrguar.annualincome"
                                    v-uppercase
                                    minlength="4"
                                    v-number
                                >
                                <span class="error"></span>
                            </div>
                            <div>
                                <label for="">Value of Landed Property *:</label>
                                <input
                                    type="text"
                                    name="propertyvalue"
                                    maxlength="10"
                                    required
                                    v-model="irrguar.propertyvalue"
                                    v-uppercase
                                    minlength="4"
                                    v-number
                                >
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class='double' >
                            <div>
                                <label>Primary Email:</label>
                                <input type="email"  id="email" name="email" v-model="irrguar.primary_email" v-uppercase>
                                <span class="error"></span>
                            </div>
                            <div>
                                <label>Secondary Email:</label>
                                <input type="email"  id="email" name="email" v-model="irrguar.secondary_email" v-uppercase>
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class='double' >
                            <div>
                                <label for="">Occupation/Position *:</label>
                                <input type="text" name="occupation" maxlength="150" required v-model="irrguar.occupation"  v-uppercase>
                                <span class="error"></span>
                            </div>
                            <div>
                                <label for="">Tel/Mob. No *:</label>
                                <input
                                    type="tel"
                                    name="mobileno"
                                    maxlength="13"
                                    minlength="8"
                                    v-number
                                    required
                                    v-model="irrguar.mobileno"
                                    v-uppercase
                                >
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class="double">
                            <div>
                                <label for="">Alternate Tel/Mob. No:</label>
                                <input
                                    type="tel"
                                    name="alternateno"
                                    maxlength="13"
                                    minlength="8"
                                    v-number
                                    v-model="irrguar.alternate_mobileno"
                                    v-uppercase
                                >
                                <span class="error"></span>
                            </div>
                            <div>
                                <label>Region *:</label>
                                <select  v-model="irrguar.region_id"  required>
                                    <option></option>
                                    <option
                                        v-for="region in regions"
                                        :key="region.id"
                                        :value="region.id"
                                    >
                                        {{  region.name }}
                                    </option>
                                </select>
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class='one' >
                            <label for="">Business Address *:</label>
                            <input type="text" name="businessaddr" required v-model="irrguar.businessaddr"  v-uppercase>
                            <span class="error"></span>
                        </div>
                        <div class='one' >
                            <label for="">Residential Address *:</label>
                            <input type="text" name="residenceaddr" required v-model="irrguar.residenceaddr"  v-uppercase>
                            <span class="error"></span>
                        </div>

                        <div class='double' >
                            <div>
                                <label for="">Ghana Card Number *:</label>
                                <input
                                    type="text"
                                    name="ghcard"
                                    required
                                    v-model="irrguar.ghcard"
                                    v-uppercase
                                    pattern="[A-Za-z]{3}-\d{9}-\d" 
                                    placeholder="GHA-000000000-0"
                                >
                                <span class="error"></span>
                            </div>
                            <div>
                                <label for="">Ghana Digital Address *:</label>
                                <input type="text" name="digitaladdr" required v-model="irrguar.digitaladdr"  v-uppercase>
                                <span class="error"></span>
                            </div>
                        </div>

                        <div class='double' >
                            <div>
                                <label>Relationship with Applicant *:</label>
                                <select name="relation" required v-model="irrguar.relation">
                                    <option></option>
                                    <option
                                        v-for="relation in relations"
                                        :key="relation.id"
                                        :value="relation.id"
                                    >
                                        {{  relation.name }}
                                    </option>
                                </select>
                                <span class="error"></span>
                            </div>
                            <div>
                                <label for="">Duration of Relationship *:</label>
                                <input
                                    type="number"
                                    name="relationyear"
                                    required
                                    v-model="irrguar.relationyear"
                                    min="3"
                                    v-uppercase
                                    placeholder="Three (3) years minmum"
                                >
                                <span class="error"></span>
                            </div>
                        </div>

                        

                        <div class='double' >
                            <div>
                                <label>Secondary Contact name:</label>
                                <input type="text" name="secocntactname"  v-model="irrguar.secondary_contact_person_name"  v-uppercase>
                                <span class="error"></span>
                            </div>
                            <div>
                                <label for="">Secondary Contact Number:</label>
                                <input
                                    type="text"
                                    name="telno"
                                    id="telno"
                                    maxlength="13"
                                    minlength="8"
                                    v-model="irrguar.secondary_contact_person_number"
                                    v-uppercase
                                    v-number
                                >
                                <span class="error"></span>
                            </div>
                        </div>

                        

                        <div>
                            The guarantor should also submit with evidence of citizenship and identity any of the following: Driver’s License /Passport/Voter’s ID card/ Employment ID Card. The guarantor must submit in person to the Head Office of the Company or any other office authorized to receive such forms with Two (2) current Passport picture.
                        </div>

                        <div class="stepsbutton">
                            <button disabled>prev</button>
                            <button @click="irrguarstepnext">Next</button>
                        </div>
                    </div>

                    <div id="irrguar2" v-show="irrguarstep == 2"  class="group">
                        <h3>Declaration</h3>
                            
                        <div id="declarationdiv">
                            <p>
                                I <strong>{{ irrguar.guarname }}</strong> with telephone number(s) <strong>{{ irrguar.mobileno }}</strong> <strong v-if
                                ='irrguar.alternate_mobileno'> / {{ irrguar.alternate_mobileno }}</strong>,  an employee of <input type="text" v-model="irrguar.company" required v-uppercase> and presently residing at <strong>{{ irrguar.residenceaddr }}</strong> in the <strong>{{ irrguar.region_id ? findregion(irrguar.region_id) : '' }}</strong> region of the Republic of Ghana voluntarily presents myself as a guarantor. 
                            </p> 
                            <p>
                                I understand and verily believe same to be true that you have employed <strong>{{ emp.firstname+' '+emp.surname }}</strong> as a <strong>{{ emp.joiningposition }}</strong> in your organization and clearly understand my obligations thereof .
                            </p>

                            <p>
                                I have known the above named person for <strong>{{ irrguar.relationyear }}</strong> years and do hereby consent to standing in as a guarantor to secure the organization against any losses that may accrue due to his/her actions, omissions, fraud, dishonesty, malfeasance or other(s) as the case may be to the tune of <input type="text" maxlength="10" v-number required v-model.number="irrguar.guaramount"  v-uppercase> (In words) <strong > {{ irrguar.guaramount ? numberToWords(irrguar?.guaramount) : '' }} </strong> Ghana Cedis.
                            </p>

                            <p>
                                Any claims made under this guarantee must be sent (without limitations via any reachable means) and received by me accompanied by a signed statement indicating that the abovementioned employee has failed in fulfilling his / her obligations which has ensued losses to your organization.
                            </p>
                            <p>
                                Such statement and claim shall be conclusive evidence of the amount being claimed under this guarantee and as such I and the above named employee waive all rights against the organization of any suits or actions whatsoever and howsoever.   
                            </p>

                        </div>

                        <div class="stepsbutton">
                            <button @click="irrguarstepprev">prev</button>
                            <button @click="irrguarstepnext">Next</button>
                        </div>
                    </div>

                    <div id="irrguar3" v-show="irrguarstep == 3"  class="group">
                        <h3>Signature</h3>
                        <div>
                            <p>
                                <strong>Signed on this DAY OF {{ emp.id ? aDate(emp.created_at) : today }} </strong>

                                <div style="margin: 10px 0px;">
                                    <Button
                                        icon="pi pi-pencil"
                                        v-if='!irrguar.id'
                                        @click="irrguarsigning = true"
                                        :label="irrguar.signature? 'Change Signature':'Sign' "
                                    >   
                                    </Button> 

                                    <div v-if="irrguarsigning">
                                        
                                        <Vue3Signature
                                            style="border: 1px solid var(--primary);"
                                            ref="signature2"
                                            :sigOption="state.option"
                                            :w="'400px'"
                                            :h="'300px'"
                                            class="my-2"
                                        ></Vue3Signature>

                                        <div class="w-[400px] flex justify-center">
                                            <Button @click="savesignature2('image/jpeg')" severity="danger">Save</Button>
                                            <Button @click="clearsignature2">Clear</Button>
                                            <Button @click="cancelsignature2" severity="secondary">Cancel</Button>
                                            

                                            <FileUpload mode="basic" customUpload auto class="p-button-info ms-3" accept="image/*" @select="loadsignature2($event)" chooseLabel="Upload" />
                                        </div>
                                    </div> 
                                    <div v-if="!irrguar.id && irrguar.signature">
                                        <img :src="`${irrguar.signature}`" width="200" >
                                    </div>
                                    <div v-if="irrguar.id">
                                        <img :src="`${imgburl}${irrguar.signature.path}`" width="200" >
                                    </div>
                                </div>
                                <!-- Signature of Guarantor<br>
                                In the presence of -->
                                
                            </p>
                        </div>

                        <div class="group">
                            <h3>WITNESSES (MIN2)</h3>

                            <Button
                                @click="addirrguarwitness"
                                v-if="!irrguarwitnesses.length"
                                class="!py-2"
                            >Add Witness</Button>
                            <div >
                                <div v-for="(witness, ind) in irrguarwitnesses" :key="ind" >
                                    <h3>Witness {{ ind + 1 }}</h3>
                                    <div class="inline">
                                        <div>
                                            <label >Name *:</label>
                                            <input type="text" v-model="witness.name" required  v-uppercase>
                                            <span class="error"></span>
                                        </div>
                                        
                                        <div>
                                            <label>Residential address *:</label>
                                            <input type="text" v-model="witness.address" required  v-uppercase>
                                            <span class="error"></span>
                                        </div>
                                        <div>
                                            <label >Phone No *:</label>
                                            <input
                                                type="text"
                                                v-model="witness.phoneno"
                                                maxlength="13"
                                                minlength="8"
                                                v-number
                                                required
                                                v-uppercase
                                            >
                                            <span class="error"></span>
                                        </div>

                                        <span >
                                                
                                            <Button
                                                @click="removeirrguarwitness(ind)"
                                                v-if="!witness.id"
                                                icon="pi pi-minus"
                                            ></Button> 
                                            <Button
                                                v-if="ind === irrguarwitnesses.length - 1"
                                                @click="addirrguarwitness"
                                                icon="pi pi-plus"
                                            ></Button>
                                        </span>
                                    </div>
                                    
                                </div>
                            </div>

                        </div>

                        <div class="stepsbutton">
                            <button @click="irrguarstepprev">prev</button>
                            <button disabled>Next</button>
                        </div>
                    </div>
                    
                    <div v-if="!irrguar.id && loguser.position_id == 2">
                        <Button
                            @click="submitirrguarantee"
                            :disabled="saving"
                            :loading="saving"
                            class='!py-3 !px-12 mt-5'
                            label="Save"
                        ></Button>
                    </div>
                    <div v-if="irrguar.id">

                        <div v-if="loguser.position_id == 2 && irrguar.recordstatus == 0">

                            <Button
                                @click="updateirrguarantee"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5'
                                label="Update"
                                
                            ></Button>

                            <Button
                                @click="ssubmitirrguarantee"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Submit"
                                v-if="irrguar.recordstatus == 0"
                                severity="danger"
                            ></Button>

                        </div>

                        <div v-else-if="loguser.position_id == 3 && irrguar.recordstatus == 1">

                            <Button
                                @click="updateirrguarantee"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 '
                                label="Update"
                            ></Button>

                            <Button
                                @click="verifyirrguarantee"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Verify"
                                severity="danger"
                            ></Button>

                            <Button
                                @click="moveirrguaranteetodraft"
                                :disabled="saving"
                                :loading="saving"
                                class='!py-3 !px-12 mt-5 ms-3'
                                label="Move to Draft"
                                severity="info"
                            ></Button> 

                        </div>

                        <div v-else-if="loguser.position_id == 4">

                            <span v-if="irrguar.recordstatus == 2">

                                <Button
                                    @click="updateirrguarantee"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5'
                                    label="Update"
                                ></Button> 

                                <Button
                                    @click="approveirrguarantee"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5 ms-3 me-3'
                                    label="Approve"
                                    severity="danger"
                                ></Button>
                                
                            </span>

                            <span v-if="irrguar.recordstatus == 3 || irrguar.recordstatus == 2">

                                <Button
                                    @click="moveirrguaranteetorecheck"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-3 !px-12 mt-5'
                                    label="Move to recheck"
                                    severity="info"
                                ></Button> 

                            </span>

                        </div>
                        
                    </div>

                    <div v-if="irrguar.id" class="mt-3">
                        Current status: <strong>{{ findrecordstatus(irrguar.recordstatus) }}</strong>
                    </div>

                    <div v-if="irrguar.id && irrguar.lasthistory.length">
                        
                        <div class='flex justify-between items-baseline my-5'>

                            <h3>Ativity log</h3>

                            <span>
                                <span class="pe-3">
                                    <b>Total</b>: 
                                    {{ irrguarinfohistories.length ? irrguarinfohistories.length : irrguar.lasthistory.length }} 
                                </span>

                                <Button
                                    @click="loadirrguarinfohistories"
                                    :disabled="saving"
                                    :loading="saving"
                                    class='!py-2 !px-10 mt-5'
                                    label="Load all"
                                    v-if="irrguar.lasthistory.length == 10 && !irrguarinfohistories.length"
                                ></Button>

                            </span>
                        </div>

                        <div class="card" v-if="!irrguarinfohistories.length">
                            <DataTable :value="irrguar.lasthistory" tableStyle="min-width: 50rem">
                                <Column field="created_at" header="Date & time">
                                    <template #body="slotProps">
                                        {{ afDate(slotProps.data.created_at) }}
                                    </template>
                                </Column>
                                <Column field="details" header="Details"></Column>
                                
                                <Column field="recordstatus" header="Status">
                                    <template #body="slotProps">
                                        {{ findrecordstatus(slotProps.data.recordstatus) }}
                                    </template>
                                </Column>
                                <Column field="creator.name" header="User"></Column>
                            </DataTable>
                        </div>
                        
                        
                        <div class="card" v-if="irrguarinfohistories.length">
                            
                            <DataTable :value="irrguarinfohistories" tableStyle="min-width: 50rem">
                                <Column field="created_at" header="Date & time">
                                    <template #body="slotProps">
                                        {{ afDate(slotProps.data.created_at) }}
                                    </template>
                                </Column>
                                <Column field="details" header="Details"></Column>
                                
                                <Column field="recordstatus" header="Status">
                                    <template #body="slotProps">
                                        {{ findrecordstatus(slotProps.data.recordstatus) }}
                                    </template>
                                </Column>
                                <Column field="creator.name" header="User"></Column>
                            </DataTable>
                        </div>


                    </div>

                </div>
            </div> 
            
            <div id="empchecklist" v-show="maintab == 4">
                <h2> Employee checklist</h2>

                <div id='empchecklistdiv'>
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
                                Done
                            </template>
                            <template v-else="emp?.appletters">
                                <input type="file" multiple @change="addappletter($event)" accept="image/*" required>
                                <Button
                                    @click="uploadappletter"
                                    :disabled="saving || !appletters.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>Appointment Letter</strong>
                        <span>
                            <template v-if="emp?.appointmentletters?.length">
                                Done
                            </template>
                            <template v-else="emp?.appointmentletters">
                                <input type="file" multiple @change="addappointmentletter($event)" accept="image/*" required>
                                <Button
                                    @click="uploadappointmentletter"
                                    :disabled="saving || !appointmentletters.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>Probation Confirmation Detail</strong>
                        <span>
                            <template v-if="emp?.probationconfs?.length">
                                Done
                            </template>
                            <template v-else="emp?.probationconfs">
                                <input type="file" multiple @change="addprobationconf($event)" accept="image/*" required>
                                <Button
                                    @click="uploadprobationconf"
                                    :disabled="saving || !probationconfs.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>CV</strong>
                        <span>
                            <template v-if="emp?.cv?.length">
                                Done
                            </template>
                            <template v-else="emp?.cv">
                                <input type="file" multiple @change="addcv($event)" accept="image/*" required>
                                <Button
                                    @click="uploadcv"
                                    :disabled="saving || !cv.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>Petra Trust form filled</strong>
                        <span v-if="!isnotoptional">
                            NA
                        </span>
                        <span v-else>
                            <template v-if="emp?.petratrust?.length">
                                Done
                            </template>
                            <template v-else="emp?.petratrust">
                                <input type="file" multiple @change="addpetratrust($event)" accept="image/*" required>
                                <Button
                                    @click="uploadpetratrust"
                                    :disabled="saving || !petratrust.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>National Identification Card 2 Coloured Copies (GHANA CARD)</strong>
                        <span>{{ emp.ghcard ? 'Done' : '' }}</span>
                    </div>

                    <div>
                        <strong>Guarantor Forms (Guarantor ID Card, etc.)  </strong>
                        <span v-if="!isnotoptional && !guarantorrequired">
                            NA
                        </span>
                        <span v-else>
                            <template v-if="irrguar.id">
                                Done
                            </template>
                            <template v-else="">
                                <Button
                                    @click="changetab(3)"
                                    label="Fill Now"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>Bank / Social Security Fund</strong>
                        <span v-if="!isnotoptional">
                            NA
                        </span>
                        <span v-else>
                            <template v-if="banksocial.id">
                                Done
                            </template>
                            <template v-else="">
                                <Button
                                    @click="changetab(2)"
                                    label="Fill Now"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>NHIS Card Copy </strong>
                        <span>
                            <template v-if="emp?.nhis?.length">
                                Done
                            </template>
                            <template v-else="emp?.nhis">
                                <input type="file" multiple @change="addnhis($event)" accept="image/*" required>
                                <Button
                                    @click="uploadnhis"
                                    :disabled="saving || !nhis.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>Birth Certificate Copy  </strong>
                        <span v-if="!isnotoptional">
                            NA
                        </span>
                        <span v-else>
                            <template v-if="emp?.birthcert?.length">
                                Done
                            </template>
                            <template v-else="emp?.birthcert">
                                <input type="file" multiple @change="addbirthcert($event)" accept="image/*" required>
                                <Button
                                    @click="uploadbirthcert"
                                    :disabled="saving || !birthcert.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>Police Clearance form for Drivers (exclusively for drivers, security officers, Shop Managers, Sales Managers/Executives.)</strong>
                        <span v-if="isplcnotrequired">
                            NA
                        </span>
                        <span v-else>
                            <template v-if="emp?.pclearanceform?.length">
                                Done
                            </template>
                            <template v-else="emp?.pclearanceform">
                                <input type="file" multiple @change="addpclearanceform($event)" accept="image/*" required>
                                <Button
                                    @click="uploadpclearanceform"
                                    :disabled="saving || !pclearanceform.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>

                    <div>
                        <strong>SSNIT Card Copy  </strong>
                        <span>
                            <template v-if="emp?.ssnit?.length">
                                Done
                            </template>
                            <template v-else="emp?.ssnit">
                                <input type="file" multiple @change="addssnit($event)" accept="image/*" required>
                                <Button
                                    @click="uploadssnit"
                                    :disabled="saving || !ssnit.length"
                                    :loading="saving"
                                    label="Upload"
                                ></Button>
                            </template>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class='poppop' v-if="showsearchpopupvalue">
        <button class='poppopclose' @click="showsearchpopupvalue = false"> Close </button>
        <div class="poppopin">
            <div class='popheader'>
                <h3>List of Employees</h3>
                <input class="popsearch" type="text" v-model="empsearch" placeholder="Type the employee name or ID" v-uppercase>
                
                <div class="deux" >
                    <span>Employee ID</span>
                    <span>Employee Code</span>
                    <span>Employee Name</span>
                </div>
            </div>

            <div class="poplist">
                <!-- @click="handleorderselect(p.SaleOrderNO)" -->
                <div
                    v-for="(e, index) in emplist"
                    :key="index"
                    @click="handleempselect(e.id)"
                    class="deux"
                >
                    
                    <span>{{ e.employeeid }} </span>
                    <span>{{ e.emp_code }}</span>
                    <span>{{ e.firstname }}</span>
                    
                </div>

                <div v-if="!emplist.length" style="padding:10px; text-align: center;">
                    No result for your search
                </div>
            </div>
        </div>
    </div>

    <ConfirmDialog group="positioned"></ConfirmDialog>

</template>
<style scoped>

    /* #form {
        
    } */
    .error{
        color:red;
    }
    
    .steper{
        display: flex;
        justify-content: right;
        padding: 15px;
        align-items: center;
        gap: 10px;

        & span{
            display: inline-block;
            padding: 5px 10px;
            border: 1px solid var(--accent);
            width: 30px;
            height: 30px;
            border-radius: 30px;

            &.active{
                background-color: var(--accent);
                color: white;
            }
        }
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
                width: 300px;
                display: inline-block;
                padding:15px;
                display: inline-flex;
            }
        }
    }

    
    .tabs{
        display: flex;
        justify-content: center;
        gap:10px;
    }
    .tabs button{
        padding:7px;
    }
    .activetab{
        background-color: var(--accent);
        color:white;
        border:2px solid var(--accent);
    }
    .stepsbutton{
        
        margin-top: 30px;
        display: flex;
        justify-content: center;
        gap: 10px;

        & button{
            padding:7px 15px;
        }
    }

    
    #declarationdiv{
        
        line-height: 1.8;
    }
    #declarationdiv input{
        width: unset;
        padding: 3px;
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

    .poppop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 500;
        background-color: #00000061;
        padding: 50px 20%;
    }
    .deux{
        display: flex;
    }
    .deux > span{
        width: 50%;
        display: inline-block;
    }

    .popheader{
        height: 133px;
        background-color: #00000017;
    }
    .popheader h3{
        margin:0;
        background-color: var(--deepcolor);
        text-align: center;
        padding:5px;
    }
    .popheader input{
        padding:7px;
        width: calc(100% - 20px);
        margin-top: 5px;
        margin: 10px 10px;
    }
    .popheader .deux{
        margin-top: 5px;
        background-color: var(--accent);
        color: white;

    }
    .popheader .deux span{
        background-color: var(--deepcolor);
        border-right: 2px solid var(--bglightcolor);
        /* text-align: center; */
        padding:10px;
    }

    .poppopin {
        background-color: white;
        height: calc(100% - 50px);
    }

    .poppopclose {
        padding: 10px;
        cursor: pointer;
    }

    .poplist {
        padding: 10px;
        overflow: auto;
        max-height: calc( 100% - 133px);
    }
    .poplist .deux{
        border-bottom: 1px solid #00000021;
    }
    .poplist .deux:nth-child(even){
        background-color: var(--bglightcolor)
    }
    .poplist .deux:hover{
        background-color: #38318524;
        cursor: pointer;
    }
    .poplist .deux span{
        padding:10px;
    }

    

    

</style>