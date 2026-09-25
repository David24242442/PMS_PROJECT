<script setup>
    import { ref, computed, onMounted } from 'vue'
    import axios, { all } from 'axios';
    import { log,toastt, imgburl, showAlert} from '@/helpers/essential'
    import { hospitalityDeptsIDs, finddept, findregion, findbranch, findbankaccounttype, findbank, findfamilyrelation, findcountry, findidtypes, findcompany, findgender } from '@/data/masterdata'
    import { regionminis, regioncentral, regionsgra, regionswestern, regionashanti, regionvolta, regionnorth, regioneastern, regionbrong } from '@/data/masterdata'
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { loguser, getloguser, authtoken, getauthtoken } = userstore

    import { VuePDF, usePDF } from '@tato30/vue-pdf'
    const { pdf, pages } = usePDF('./src/assets/documents/The_Melcom_Way_DECK.pdf')

    import logo from '@/assets/img/mel_logo.png'

    import Chart from 'chart.js/auto';
    import ApexCharts from 'apexcharts';
    

    const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer

    const refreshClass = computed(() => loading.value ? 'pi pi-refresh pi-spin' : 'pi pi-refresh');
    
    let result = ref()
    let loading = ref(false)
    let loadingEmps = ref(false)
    let showEmpsPopup = ref(false)
    let emplist = ref([])

    const chartGendersRef = ref(null);
    let chartGenders = null;

    const chartCompRef = ref(null);
    let chartComp = null;

	const chartCitRef = ref(null);
    let chartCit = null;

    const chartDeptRef = ref(null);
    let chartDept = null;

    const chartMelcomDeptRef = ref(null);
    let chartMelcomDept = null;

    const chartHospitalityDeptRef = ref(null);
    let chartHospitalityDept = null;

    const chartLocRef = ref(null);
    let chartLoc = null;

    const chartRegionRef = ref(null);
    let chartRegion = null;

    const chartRegionLocRef = ref(null);
    let chartRegionLoc = null;

    const chartAgeGroupRef = ref(null);
    let chartAgeGroup = null;
    
    const chartMonthlyRef = ref(null);
    let chartMonthly = null;

    let  visibleDialog = ref(false);

    

    const hospitalitiesData = computed(() => {
        return result.value?.deptsCount?.filter((item) => hospitalityDeptsIDs.includes(item.joining_dept_id))
    })
    const melcomDeptsData = computed(() => {
        return result.value?.deptsCount?.filter((item) => hospitalityDeptsIDs.includes(item.joining_dept_id) == false)
    })

    let showingbranchs = ref(false)

    const stopshowingbranchs = ()=>{
        showingbranchs.value = false
    }

    const minisdata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionminis.includes(item.joining_branch_id))
    })
    const minisdatatotals = computed(() => minisdata.value?.reduce((a, b) => a + b.total_count, 0))

    const centraldata = computed(() => {
        return result.value?.locationCount?.filter((item) => regioncentral.includes(item.joining_branch_id))
    })
    const centraldatatotals = computed(() => centraldata.value?.reduce((a, b) => a + b.total_count, 0))

    const gradata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionsgra.includes(item.joining_branch_id))
    })
    const gradatatotals = computed(() => gradata.value?.reduce((a, b) => a + b.total_count, 0))

    const westerndata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionswestern.includes(item.joining_branch_id))
    })
    const westerndatatotals = computed(() => westerndata.value?.reduce((a, b) => a + b.total_count, 0))

    const ashantidata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionashanti.includes(item.joining_branch_id))
    })
    const ashantitotals = computed(() => ashantidata.value?.reduce((a, b) => a + b.total_count, 0))

    const voltadata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionvolta.includes(item.joining_branch_id))
    })
    const voltatotals = computed(() => voltadata.value?.reduce((a, b) => a + b.total_count, 0))

    const northdata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionnorth.includes(item.joining_branch_id))
    })
    const northdatatotals = computed(() => northdata.value?.reduce((a, b) => a + b.total_count, 0))

    const easterndata = computed(() => {
        return result.value?.locationCount?.filter((item) => regioneastern.includes(item.joining_branch_id))
    })
    const easterndatatotals = computed(() => easterndata.value?.reduce((a, b) => a + b.total_count, 0))

    const brongdata = computed(() => {
        return result.value?.locationCount?.filter((item) => regionbrong.includes(item.joining_branch_id))
    })
    const brongdatatotals = computed(() => brongdata.value?.reduce((a, b) => a + b.total_count, 0))

    const finalLocationData = computed(() => {
        return {
            'names': ["Greater Accra", /* "Mini Stores",*/  "Central", 'Western', 'Ashanti', 'Volta', 'Northern', 'Eastern', 'Brong Ahafo'] ,
            'count': [gradatatotals.value,  /* minisdatatotals.value,*/ centraldatatotals.value, westerndatatotals.value, ashantitotals.value, voltatotals.value, northdatatotals.value, easterndatatotals.value, brongdatatotals.value]
        }
    }) 

    const matchingdata = computed(() => {
        return {
            "Greater Accra": gradata.value,
            // "Mini Stores": minisdata.value,
            "Central": centraldata.value,
            "Western": westerndata.value,
            "Ashanti": ashantidata.value,
            "Volta": voltadata.value,
            "Northern": northdata.value,
            "Eastern": easterndata.value,
            "Brong Ahafo": brongdata.value
        }
    })

    const selectedregion = ref()

    const allData = computed(() => {
        return result.value?.deptsCount
    })

    onMounted(() => {
        loadchart()
    });

    const loadEmpDepts = (data)=>{
        emplist.value = []
        loadingEmps.value = true
        showEmpsPopup.value = true

        axios.post('loadEmpDepts',{
            type: data[0],
            value: data[1] 
        })
            .then(res => {
                const data = res.data
                // log(data)
                emplist.value = data
                
                loadingEmps.value = false
            })
            .catch((error) => {
                console.log(error)
                loadingEmps.value = false
            })
    }        
    

    const loadchart = () => {
        loading.value = true
        axios.get('dashboard')
            .then(res => {
                const data = res.data
                
                result.value = data
                loading.value = false
                // log(data)


                /* Countries Chart */
                
                    const cit = data.citizenshipCounts.filter((i)=> i.citizenship != '' ).map(item => findcountry(item.citizenship) || '');
                    const cittotal = data.citizenshipCounts.filter((i)=> i.citizenship != '' ).map(item => item.total_count);

                    const optionsCit = {
                        series: [{
                            name: 'Units',
                            data: cittotal
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: {
                                show: false
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = data.citizenshipCounts[index].citizenship 

                                    
                                    loadEmpDepts(['citizenship',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                        bar: {
                            distributed: true, // This makes each bar have a different color
                            borderRadius: 6,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        xaxis: {
                            categories: cit,
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: true,
                                formatter: function(val) {
                                return val;
                                }
                            }
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            yaxis: {
                                lines: {
                                show: true
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartCit = new ApexCharts(chartCitRef.value, optionsCit);
                    chartCit.render();
                /* Countries Chart */
                
                /* Monthly Chart */
                
                    const monthly = Object.keys(data.monthlyData);
                    const monthlytotal = Object.values(data.monthlyData)

                    const optionsMonthly = {
                        series: [{
                            name: 'Units',
                            data: monthlytotal
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: {
                                show: false
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = monthly[index]
                                    
                                    
                                    loadEmpDepts(['monthdata',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                        bar: {
                            distributed: true, // This makes each bar have a different color
                            borderRadius: 6,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        
                        xaxis: {
                            categories: monthly,
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: true,
                                formatter: function(val) {
                                return val;
                                }
                            }
                        },
                         grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            yaxis: {
                                lines: {
                                show: true
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartMonthly = new ApexCharts(chartMonthlyRef.value, optionsMonthly);
                    chartMonthly.render();
                /* Monthly Chart */
                
                
                /* Melcom Depts Chart */
                
                    const melcomdept = melcomDeptsData.value.map(item => finddept(item.joining_dept_id));
                    const melcomdepttotal = melcomDeptsData.value.map(item => item.total_count);

                    const optionsMelcomDept = {
                        series: [{
                            name: 'Units',
                            data: melcomdepttotal
                        }],
                        chart: {
                            type: 'bar',
                            height: 500,
                            toolbar: {
                                show: false
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = melcomDeptsData.value[index].joining_dept_id
                                    event.target.style.cursor = "pointer";
                                    
                                    loadEmpDepts(['dept',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                        bar: {
                            distributed: true, // This makes each bar have a different color
                            borderRadius: 6,
                            horizontal: true,
                            dataLabels: {
                                position: 'right'
                            }
                        }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            textAnchor: 'start',
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetX: 0,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        
                        xaxis: {
                            categories: melcomdept,
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },labels: {
                                show: false
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: true,
                                style: {
                                    fontSize: '11px'
                                },
                                formatter: function(val) {
                                    // if string is too long, trim
                                    if(val.length > 20){
                                        return val.substring(0,20)+'...'
                                    }
                                    return val;
                                }
                            }
                        },
                         grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            xaxis: {
                                lines: {
                                show: true
                                }
                            },
                             yaxis: {
                                lines: {
                                show: false
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartMelcomDept = new ApexCharts(chartMelcomDeptRef.value, optionsMelcomDept);
                    chartMelcomDept.render();

                /* Depts Chart */

                /* Hostpitality Depts Chart */
                
                    const hospitalitydept = hospitalitiesData.value.map(item => finddept(item.joining_dept_id));
                    const hospitalitydepttotal = hospitalitiesData.value.map(item => item.total_count);

                    const optionsHospitalityDept = {
                        series: [{
                            name: 'Units',
                            data: hospitalitydepttotal
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: {
                                show: false
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = hospitalitiesData.value[index].joining_dept_id
                                    event.target.style.cursor = "pointer";
                                    
                                    loadEmpDepts(['dept',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                        bar: {
                            distributed: true, // This makes each bar have a different color
                            borderRadius: 6,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        
                        xaxis: {
                            categories: hospitalitydept,
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },labels: {
                                rotate: -45,
                                trim: false,
                                hideOverlappingLabels: false,
                                // offsetY: 5,
                                style: {
                                    fontSize: '10px' // Smaller font size
                                }
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: false
                            }
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            yaxis: {
                                lines: {
                                show: true
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartHospitalityDept = new ApexCharts(chartHospitalityDeptRef.value, optionsHospitalityDept);
                    chartHospitalityDept.render();

                /* Hostpitality Depts Chart */
                
    
                /* Regions Chart */
                    
                    
                    const regions = finalLocationData.value.names;
                    const regionsTotal = finalLocationData.value.count;

                    const optionsRegions = {
                        series: [{
                            name: 'Units',
                            data: regionsTotal
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: {
                                show: false
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = regions[index]

                                    

                                    showlocation(valueKey)
                                    
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            distributed: true, // This makes each bar have a different color
                            borderRadius: 6,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        
                        xaxis: {
                            categories: regions,
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },labels: {
                                rotate: -45,
                                trim: false,
                                hideOverlappingLabels: false,
                                style: {
                                    fontSize: '10px' // Smaller font size
                                }
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: false
                            }
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            yaxis: {
                                lines: {
                                show: true
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartRegion = new ApexCharts(chartRegionRef.value, optionsRegions);
                    chartRegion.render();
                /* Regions Chart */

                /* Region Location Chart */
                
                   

                    const regionoptionsLoc = {
                        series: [{
                            name: 'Units',
                            data: []
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: {
                                show: false
                            },
                             animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800,
                                animateGradually: {
                                    enabled: true,
                                    delay: 150
                                },
                                dynamicAnimation: {
                                    enabled: true,
                                    speed: 350
                                }
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = matchingdata.value[selectedregion.value][index].joining_branch_id
                                    
                                    loadEmpDepts(['loc',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '50%',
                                distributed: true, // This makes each bar have a different color
                                borderRadius: 6,
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        
                        xaxis: {
                            categories: [],
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },labels: {
                                // rotate: -45,
                                trim: false,
                                hideOverlappingLabels: false,
                                // offsetY: 5,
                                style: {
                                    fontSize: '10px' // Smaller font size
                                }
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: false
                            }
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            yaxis: {
                                lines: {
                                show: true
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartRegionLoc = new ApexCharts(chartRegionLocRef.value, regionoptionsLoc);
                    chartRegionLoc.render();
                /* Region Location Chart */
                 
                /* Gender Chart */

                    const genders = data.genderCounts.filter((i)=> i.gender != '' ).map(item => findgender(item.gender) || '');
                    const genderstotal = data.genderCounts.filter((i)=> i.gender != '' ).map(item => item.total_count);

                    const optionsgenders = {
                        series: genderstotal,
                        chart: {
                            type: 'donut',
                            height: 350,
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = data.genderCounts[index].gender

                                    
                                    loadEmpDepts(['gender',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        labels: genders,
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        legend: {
                            show: true, 
                            position: 'bottom'
                        },
                        tooltip: {
                            enabled: true 
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            fontSize: '22px',
                                            fontWeight: 'bold',
                                            color: '#373d3f',
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: {
                           enabled: false
                        }
                    };

                    chartGenders = new ApexCharts(chartGendersRef.value, optionsgenders);
                    chartGenders.render();
                /* genders Chart */

                /* Companies Chart */
                    const comp = data.companiesCounts.map(item => findcompany(item.company));
                    const comptotal = data.companiesCounts.map(item => item.total_count);

                    const optionsComp = {
                        series: comptotal,
                        chart: {
                            type: 'donut',
                            height: 350,
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = data.companiesCounts[index].company 

                                    
                                    loadEmpDepts(['comp',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        labels: comp,
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        legend: {
                            show: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            enabled: true 
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: false
                        }
                    };

                    chartComp = new ApexCharts(chartCompRef.value, optionsComp);
                    chartComp.render();
                /* Companies Cart */
                
                /* AgeGroup Chart */

                    const agegroup = Object.keys(data.ageGroups);
                    const agegrouptotal = Object.values(data.ageGroups);

                    const optionsAgeGroup = {
                        series: [{
                            name: 'Units',
                            data: agegrouptotal
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: {
                                show: false
                            },
                            events: {
                                dataPointSelection: (event, chartContext, config) => { 
                                    const index = config.dataPointIndex
                                    const valueKey = agegroup[index] 

                                    
                                    loadEmpDepts(['agegroup',valueKey])
                                },
                                dataPointMouseEnter: function(event) {
                                    event.target.style.cursor = 'pointer'
                                },
                                dataPointMouseLeave: function(event) {
                                    event.target.style.cursor = 'default'
                                }
                            }
                        },
                        plotOptions: {
                        bar: {
                            distributed: true, // This makes each bar have a different color
                            borderRadius: 6,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                        },
                        colors: ['#7c3aed', '#db2777', '#ea580c', '#059669', '#2563eb'],
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val + '';
                            },
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        
                        xaxis: {
                            categories: agegroup,
                            position: 'bottom',
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },labels: {
                                rotate: -45,
                                trim: false,
                                hideOverlappingLabels: false,
                                offsetY: 0,
                                style: {
                                    fontSize: '10px' // Smaller font size
                                }
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            labels: {
                                show: false
                            }
                        },
                         grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            yaxis: {
                                lines: {
                                show: true
                                }
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        }
                    };

                    chartAgeGroup = new ApexCharts(chartAgeGroupRef.value, optionsAgeGroup);
                    chartAgeGroup.render();
                /* AgeGroup Chart */

            })
            .catch((error) => {
                console.log(error)
                loading.value = false
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
        const updateValue = (el) => {
            const input = el.target
            const sourceValue = input.value
            const newValue = sourceValue.toUpperCase()

            if (sourceValue !== newValue) {
                input.value = newValue
                input.dispatchEvent(new Event('input', { bubbles: true }))
            }
        }
    /* Directive Creation Stop */


    const items = [
        {
            label: 'The Melcom Way DECK',
            command: () => {
                visibleDialog.value = true
            }
        },
        
        {
            separator: true
        },
    ]

    const showlocation = (region) => {
        showingbranchs.value = true

        selectedregion.value = region

        const regionloc = matchingdata.value[region].map(item => findbranch(item.joining_branch_id));
        const regionloctotal = matchingdata.value[region]?.map(item => item.total_count);

        chartRegionLoc.updateOptions({
            xaxis: {
                categories:  regionloc
            }
        });

        chartRegionLoc.updateSeries([{
            name: 'Units',
            data: regionloctotal 
        }]);
    }
    
</script>

<template>
    <div class="h-full pb-6">

        <Dialog v-model:visible="visibleDialog" modal header="The Melcom Way DECK" :style="{ width: '50rem' }" >
            <div class="pdf-container">
                <div v-for="page in pages" :key="page">
                    <div style="padding-top: 20px;padding-bottom: 20px;margin-bottom: 20px; border-bottom: 2px solid #00000036;">
                        <VuePDF :pdf="pdf" :page="page"  fit-parent />
                        <span style="margin-top: 10px;display: block;text-align: right;padding-right: 20px;">{{ page }} / {{ pages }}</span>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- New Clean Header -->
        <div class="dashboard-header mb-8 rounded-2xl p-6 shadow-lg shadow-purple-200"
             style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                     <h1 class="text-3xl font-extrabold text-white flex items-center gap-3 mb-2">
                        Dashboard Overview
                        <button 
                            v-if="!loading"
                            @click="loadchart"
                            class="w-8 h-8 rounded-full bg-white/20 text-white hover:bg-white/30 flex items-center justify-center transition-all shadow-sm border border-white/10 cursor-pointer"
                            title="Refresh Data"
                        >
                            <i class="pi pi-refresh text-sm" :class="{'pi-spin': loading}"></i>
                        </button>
                    </h1>
                    <p class="text-sm font-medium text-purple-100">Real-time HR analytics and demographics</p>
                </div>

                <div class="header-actions flex items-center gap-3">
                     <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl shadow-sm border border-white/20">
                        <img :src="logo" class="h-6 brightness-0 invert">
                        <span class="font-bold text-white">MiMelcom</span>
                    </div>
                     <SplitButton label="Actions" :model="items" icon="pi pi-cog" class="p-button-outlined p-button-secondary bg-white text-purple-800 border-none"></SplitButton>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div v-if="result" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Employees -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-purple-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-purple-500/20"
                 style="background: linear-gradient(135deg, #6b21a8 0%, #312e81 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-users text-lg"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Total Staff</span>
                </div>
                <div class="text-3xl font-extrabold mb-1">{{ result?.totalemp }}</div>
                <div class="text-[10px] font-medium opacity-80">Active Employees</div>
            </div>

            <!-- Departments -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-blue-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-blue-500/20"
                 style="background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-building text-lg"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Depts</span>
                </div>
                <div class="text-3xl font-extrabold mb-1">{{ result?.deptsCount?.length || 0 }}</div>
                <div class="text-[10px] font-medium opacity-80">Departments</div>
            </div>

            <!-- Locations -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-orange-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-orange-500/20"
                 style="background: linear-gradient(135deg, #ea580c 0%, #991b1b 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                     <i class="pi pi-map-marker text-lg"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Locations</span>
                </div>
                <div class="text-3xl font-extrabold mb-1">{{ result?.locationCount?.length || 0 }}</div>
                <div class="text-[10px] font-medium opacity-80">Active Branches</div>
            </div>

            <!-- Companies -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-teal-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-teal-500/20"
                 style="background: linear-gradient(135deg, #0f766e 0%, #064e3b 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-briefcase text-lg"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Companies</span>
                </div>
                <div class="text-3xl font-extrabold mb-1">{{ result?.companiesCounts?.length || 0 }}</div>
                <div class="text-[10px] font-medium opacity-80">Entities</div>
            </div>

            <!-- Countries -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-pink-200 text-white transform hover:-translate-y-1 transition-transform duration-300 border border-pink-500/20"
                 style="background: linear-gradient(135deg, #be185d 0%, #881337 100%) !important;">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex items-center gap-3 mb-2 opacity-90">
                    <i class="pi pi-globe text-lg"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Countries</span>
                </div>
                <div class="text-3xl font-extrabold mb-1">{{ result?.citizenshipCounts?.length || 0 }}</div>
                <div class="text-[10px] font-medium opacity-80">Nationalities</div>
            </div>
        </div>

        <div v-if="loading && !result" class="loading-container">
           <div class="loading-content">
               <i class="pi pi-spin pi-spinner loading-spinner"></i>
               <p class="loading-text">Loading analytics...</p>
           </div>
        </div>

        <div v-if="result" class="dashboard-content">
            
            <!-- Grid Layout for Charts -->
            <div class="charts-grid-3">
                
                <!-- Genders (Pie) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Gender Distribution</h2>
                        <span class="chart-subtitle">Employees by gender</span>
                    </div>
                    <div class="chart-body">
                         <div ref="chartGendersRef" class="chart-container"></div>
                    </div>
                </div>

                 <!-- Companies (Donut) -->
                <div class="chart-card">
                     <div class="chart-header">
                        <h2 class="chart-title">Company Structure</h2>
                        <span class="chart-subtitle">Headcount by entity</span>
                    </div>
                     <div class="chart-body">
                        <div ref="chartCompRef" class="chart-container"></div>
                    </div>
                </div>

                <!-- Age Groups -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Demographics</h2>
                        <span class="chart-subtitle">Age group distribution</span>
                    </div>
                    <div class="chart-body">
                        <div ref="chartAgeGroupRef" class="chart-container"></div>
                    </div>
                </div>
            </div>

            <div class="charts-grid-2">
                <!-- Melcom Departments (Horizontal Bar) -->
                 <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Melcom Departments</h2>
                    </div>
                    <div ref="chartMelcomDeptRef"></div>
                </div>

                <!-- Hospitality Departments -->
                 <div class="chart-card">
                     <div class="chart-header">
                        <h2 class="chart-title">Hospitality Departments</h2>
                    </div>
                    <div ref="chartHospitalityDeptRef"></div>
                </div>
            </div>


            <!-- Full Width Charts -->
             <div class="chart-card chart-full">
                 <div class="chart-header">
                    <h2 class="chart-title">Monthly Onboarding</h2>
                    <span class="chart-subtitle">New joiners per month</span>
                </div>
                <div ref="chartMonthlyRef"></div>
            </div>

             <div class="chart-card chart-full">
                <div class="chart-header-with-action">
                    <div>
                        <h2 class="chart-title">Regional Distribution</h2>
                        <span class="chart-subtitle">Headcount by region and branch</span>
                    </div>
                    <div v-if="showingbranchs">
                         <button @click="stopshowingbranchs" class="back-btn">
                            <i class="pi pi-arrow-left"></i> Back to Regions
                         </button>
                    </div>
                </div>
                
                <div class="chart-slider-container">
                    <div class="chart-slide" 
                        :class="[showingbranchs ? 'slide-out' : 'slide-in']">
                         <div ref="chartRegionRef"></div>
                    </div>
                     <div class="chart-slide"
                         :class="[showingbranchs ? 'slide-in' : 'slide-out']">
                         <h3 class="region-title" v-if="showingbranchs">{{ selectedregion }} Region</h3>
                         <div ref="chartRegionLocRef"></div>
                    </div>
                </div>
            </div>

             <div class="chart-card chart-full">
                 <div class="chart-header">
                    <h2 class="chart-title">Citizenships</h2>
                </div>
                <div ref="chartCitRef"></div>
            </div>

        </div>
    </div>
    
    <!-- Employee List Popup (Re-styled) -->
    <div v-if="showEmpsPopup" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div>
                     <h3 class="text-xl font-bold text-gray-800">Employee List</h3>
                     <p class="text-sm text-gray-500">{{ emplist.length }} employee(s) found</p>
                </div>
                <button @click="showEmpsPopup = false" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-colors">
                    <i class="pi pi-times"></i>
                </button>
            </div>
            
            <div class="p-4 border-b border-gray-100 bg-white">
                <div class="relative">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" v-uppercase placeholder="Search by name or ID..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all">
                </div>
            </div>

             <div class="flex-1 overflow-y-auto p-0">
                <div v-if="loadingEmps" class="flex justify-center items-center h-40">
                    <i class="pi pi-spinner pi-spin text-purple-600 text-3xl"></i>
                </div>
                
                <table v-else class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Profile</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Name</th>
                             <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(e, index) in emplist" :key="index" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3">
                                 <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden">
                                     <img :src="e.profilepicture[0]?.path ? `${imgburl}${e.profilepicture[0]?.path}` : 'https://ui-avatars.com/api/?name='+e.firstname+'&background=random'" class="w-full h-full object-cover">
                                 </div>
                            </td>
                            <td class="px-6 py-3 font-mono text-sm text-gray-600">{{ e.employeeid }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ e.firstname }} {{ e.lastname }}</td>
                            <td class="px-6 py-3">
                                <router-link :to="`/employee/${e.id}`" target="_blank" class="text-purple-600 hover:text-purple-800 p-2 rounded-full hover:bg-purple-50 transition-colors">
                                    <i class="pi pi-eye"></i>
                                </router-link>
                            </td>
                        </tr>
                        <tr v-if="!emplist.length">
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">No employees found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                <button class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-lg transition-colors" @click="showEmpsPopup = false">Close</button>
            </div>
        </div>
    </div>
   
</template>

<style scoped>
    /* Dashboard Header */
    .dashboard-header {
        background: var(--color-surface);
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--color-light);
        margin-bottom: 1.5rem;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dashboard-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dashboard-subtitle {
        color: var(--text-muted);
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .refresh-btn {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: var(--color-lighter);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .refresh-btn:hover {
        background: var(--color-light);
        color: var(--color-primary);
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .brand-pill {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--color-lighter);
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--color-light);
    }

    .brand-logo {
        height: 1.5rem;
    }

    .brand-name {
        font-weight: 700;
        color: var(--text-primary);
    }

    /* Quick Stats Row */
    .quick-stats-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1200px) {
        .quick-stats-row {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .quick-stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .stat-card {
        background: var(--color-surface);
        border-radius: 1rem;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--color-light);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .stat-primary .stat-icon {
        background: rgba(124, 58, 237, 0.1);
        color: #7c3aed;
    }

    .stat-success .stat-icon {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .stat-warning .stat-icon {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .stat-info .stat-icon {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .stat-pink .stat-icon {
        background: rgba(236, 72, 153, 0.1);
        color: #ec4899;
    }

    .stat-content {
        display: flex;
        flex-direction: column;
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    /* Loading State */
    .loading-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 16rem;
    }

    .loading-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }

    .loading-spinner {
        color: var(--color-primary);
        font-size: 2.5rem;
    }

    .loading-text {
        color: var(--text-muted);
        font-weight: 500;
    }

    /* Dashboard Content */
    .dashboard-content {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Chart Grids */
    .charts-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    .charts-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 1200px) {
        .charts-grid-3 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .charts-grid-3,
        .charts-grid-2 {
            grid-template-columns: 1fr;
        }
    }

    /* Chart Cards */
    .chart-card {
        background: var(--color-surface);
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--color-light);
        display: flex;
        flex-direction: column;
    }

    .chart-full {
        grid-column: 1 / -1;
    }

    .chart-header {
        margin-bottom: 1rem;
    }

    .chart-header-with-action {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--color-lighter);
    }

    .chart-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .chart-subtitle {
        font-size: 0.75rem;
        color: var(--text-muted);
        display: block;
        margin-top: 0.25rem;
    }

    .chart-body {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chart-container {
        width: 100%;
    }

    /* Back Button */
    .back-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: none;
        background: transparent;
        color: var(--color-danger);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .back-btn:hover {
        background: rgba(239, 68, 68, 0.08);
    }

    /* Chart Slider */
    .chart-slider-container {
        position: relative;
        overflow: hidden;
        min-height: 400px;
    }

    .chart-slide {
        position: absolute;
        inset: 0;
        width: 100%;
        transition: all 0.5s ease-in-out;
    }

    .chart-slide.slide-in {
        transform: translateX(0);
        opacity: 1;
        pointer-events: auto;
    }

    .chart-slide.slide-out {
        transform: translateX(-100%);
        opacity: 0;
        pointer-events: none;
    }

    .chart-slide:last-child.slide-out {
        transform: translateX(100%);
    }

    .region-title {
        text-align: center;
        font-weight: 700;
        color: var(--color-primary);
        margin-bottom: 0.5rem;
    }

    /* Employee Popup Styles */
    .popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        backdrop-filter: blur(4px);
        animation: fadeIn 0.2s ease;
    }

    .popup-container {
        background: var(--color-surface);
        border-radius: 1.5rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        width: 100%;
        max-width: 56rem;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .popup-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--color-lighter);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--color-lighter);
    }

    .popup-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .popup-subtitle {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    .popup-close {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: var(--color-light);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .popup-close:hover {
        background: rgba(239, 68, 68, 0.1);
        color: var(--color-danger);
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Styling for ApexCharts tooltips to match theme */
    :deep(.apexcharts-tooltip) {
        background: var(--color-surface) !important;
        border-color: var(--color-light) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        border-radius: 0.5rem !important;
    }

    :deep(.apexcharts-tooltip-title) {
        background: var(--color-lighter) !important;
        border-bottom: 1px solid var(--color-light) !important;
        font-family: inherit !important;
    }

    :deep(.apexcharts-text) {
        font-family: inherit !important; 
        fill: var(--text-muted) !important;
    }

    .pdf-container canvas {
        display: none;
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        .dashboard-header {
            padding: 1rem;
        }

        .header-content {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        .header-actions {
            width: 100%;
            justify-content: space-between;
        }

        .brand-pill {
            display: none;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .stat-value {
            font-size: 1.25rem;
        }
    }
</style>