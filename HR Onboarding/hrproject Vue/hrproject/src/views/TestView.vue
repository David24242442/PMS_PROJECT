<script setup>
    import { ref, reactive, computed, onMounted ,onUnmounted, watch} from 'vue'
	import axios from 'axios';
	import { log,toastt} from '@/helpers/essential'
    import { finddept, findregion, findbranch, findbankaccounttype, findbank, findfamilyrelation, findcountry, findidtypes, findcompany, findgender, genders } from '@/data/masterdata'
    import { useUsersStore } from '@/stores/user';
    const userstore = useUsersStore()
    const { loguser, getloguser, authtoken, getauthtoken } = userstore
    
	const bearer = `Bearer ${authtoken}`;
    axios.defaults.headers.common['Authorization'] = bearer

    import ApexCharts from 'apexcharts';

    // Chart container reference
    const chartGendersRef = ref(null);
    let chartGenders = null;

	const chartCompRef = ref(null);
    let chartComp = null;

	const chartCitRef = ref(null);
    let chartCit = null;

	let result = ref()

    // let genderPieData = ref()
    // let citPieData = ref()
    // let compPieData = ref()
    // const documentStyle = getComputedStyle(document.body);

    // Chart data
    const series = [44, 55, 13, 33, 22];
    const labels = ['Category A', 'Category B', 'Category C', 'Category D', 'Category E'];

	// const categories = ['Category A', 'Category B', 'Category C', 'Category D', 'Category E'];
	// const values = [44, 55, 13, 33, 22];

    onMounted(() => {
    //   // Chart options
    //   const options = {
    //     series: series,
    //     chart: {
    //       type: 'bar',
    //       height: 400
    //     },
    //     labels: labels,
    //     colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'],
    //     legend: {
    //       show: false // Hide the legend
    //     },
    //     responsive: [{
    //       breakpoint: 480,
    //       options: {
    //         chart: {
    //           width: 300
    //         }
    //       }
    //     }],
    //     tooltip: {
    //       enabled: false // Disable tooltips
    //     },
    //     plotOptions: {
    //       pie: {
    //         dataLabels: {
    //           offset: -10
    //         }
    //       }
    //     },
    //     title: {
    //       text: '',
    //       align: 'center',
    //       style: {
    //         fontSize: '18px'
    //       }
    //     },
    //     dataLabels: {
    //       formatter: function(val, opts) {
    //         // Get the actual value from the series data
    //         const seriesIndex = opts.seriesIndex;
    //         const absoluteValue = opts.w.config.series[seriesIndex];
    //         return opts.w.config.labels[seriesIndex] + ': ' + absoluteValue + '';
    //       }
    //     }
    //   };

		// const options = {
		// 	series: [{
		// 		name: 'Units',
		// 		data: values
		// 	}],
		// 	chart: {
		// 		type: 'bar',
		// 		height: 400,
		// 		toolbar: {
		// 			show: false
		// 		}
		// 	},
		// 	plotOptions: {
		// 	bar: {
		// 		distributed: true, // This makes each bar have a different color
		// 		borderRadius: 3,
		// 		dataLabels: {
		// 			position: 'top'
		// 		}
		// 	}
		// 	},
		// 	colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'],
		// 	dataLabels: {
		// 		enabled: true,
		// 		formatter: function(val) {
		// 			return val + '';
		// 		},
		// 		offsetY: -20,
		// 		style: {
		// 			fontSize: '12px',
		// 			colors: ["#304758"]
		// 		}
		// 	},
		// 	xaxis: {
		// 		categories: categories,
		// 		position: 'bottom',
		// 		axisBorder: {
		// 			show: false
		// 		},
		// 		axisTicks: {
		// 			show: false
		// 		}
		// 	},
		// 	yaxis: {
		// 		axisBorder: {
		// 			show: false
		// 		},
		// 		axisTicks: {
		// 			show: false
		// 		},
		// 		labels: {
		// 			show: true,
		// 			formatter: function(val) {
		// 			return val;
		// 			}
		// 		}
		// 	},
		// 	grid: {
		// 		yaxis: {
		// 			lines: {
		// 			show: false
		// 			}
		// 		}
		// 	},
		// 	tooltip: {
		// 		enabled: false
		// 	},
		// 	legend: {
		// 		show: false
		// 	}
		// };

    //   // Initialize chart
		// chart = new ApexCharts(chartRef.value, options);
		// chart.render();

		axios.get('dashboard')
            .then(res => {
                const data = res.data
                
                // result.value = data

				const cit = data.citizenshipCounts.filter((i)=> i.citizenship != '' ).map(item => findcountry(item.citizenship) || '');
                const cittotal = data.citizenshipCounts.filter((i)=> i.citizenship != '' ).map(item => item.total_count);

				const optionsCit = {
					series: [{
						name: 'Units',
						data: cittotal
					}],
					chart: {
						type: 'bar',
						height: 400,
						toolbar: {
							show: false
						}
					},
					plotOptions: {
					bar: {
						distributed: true, // This makes each bar have a different color
						borderRadius: 3,
						dataLabels: {
							position: 'top'
						}
					}
					},
					colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'],
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

				chartCit = new ApexCharts(chartCitRef.value, optionsCit);
				chartCit.render();

				const genders = data.genderCounts.filter((i)=> i.gender != '' ).map(item => findgender(item.gender) || '');
                const genderstotal = data.genderCounts.filter((i)=> i.gender != '' ).map(item => item.total_count);

				const optionsgenders = {
					series: genderstotal,
					chart: {
						type: 'pie',
						height: 400
					},
					labels: genders,
					colors: ['#d2691e', '#00f'],
					legend: {
						show: false // Hide the legend
					},
					responsive: [{
						breakpoint: 480,
						options: {
						chart: {
							width: 300
						}
						}
					}],
					tooltip: {
						enabled: false // Disable tooltips
					},
					plotOptions: {
						pie: {
						dataLabels: {
							offset: -10
						}
						}
					},
					title: {
						text: '',
						align: 'center',
						style: {
						fontSize: '18px'
						}
					},
					dataLabels: {
						formatter: function(val, opts) {
						// Get the actual value from the series data
						const seriesIndex = opts.seriesIndex;
						const absoluteValue = opts.w.config.series[seriesIndex];
						return opts.w.config.labels[seriesIndex] + ': ' + absoluteValue + '';
						}
					}
				};

				chartGenders = new ApexCharts(chartGendersRef.value, optionsgenders);
				chartGenders.render();

				
				const comp = data.companiesCounts.map(item => findcompany(item.company));
                const comptotal = data.companiesCounts.map(item => item.total_count);

				const optionsComp = {
					series: comptotal,
					chart: {
						type: 'pie',
						height: 400
					},
					labels: comp,
					colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'],
					legend: {
						show: false // Hide the legend
					},
					responsive: [{
						breakpoint: 480,
						options: {
						chart: {
							width: 300
						}
						}
					}],
					tooltip: {
						enabled: false // Disable tooltips
					},
					plotOptions: {
						pie: {
						dataLabels: {
							offset: -15
						}
						}
					},
					title: {
						text: '',
						align: 'center',
						style: {
						fontSize: '18px'
						}
					},
					dataLabels: {
						formatter: function(val, opts) {
						// Get the actual value from the series data
						const seriesIndex = opts.seriesIndex;
						const absoluteValue = opts.w.config.series[seriesIndex];
						return opts.w.config.labels[seriesIndex] + ': ' + absoluteValue + '';
						}
					}
				};

				chartComp = new ApexCharts(chartCompRef.value, optionsComp);
				chartComp.render();

			})
            .catch((error) => {
                console.log(error)
            })
    });

    // Clean up chart instance when component is unmounted
    // onUnmounted(() => {
    //   if (chart) {
    //     chart.destroy();
    //   }
    // });

    let selectedValue = ref()

    
</script>

<template>
    

	<div>

		<div class="chart-container">
			<h2 class="chart-title">Sales Distribution by Category</h2>
			<div ref="chartCitRef"></div>
		</div>
		
		<div class="chart-container">
			<h2 class="chart-title">Sales Distribution by Category</h2>
			<div ref="chartGendersRef"></div>
		</div>

		<div class="chart-container">
			<h2 class="chart-title">Sales Distribution by Category</h2>
			<div ref="chartCompRef"></div>
		</div>

	</div>

    <!-- 
      <Select 
      
        v-model="selectedValue" 
        :options="countries"
        optionLabel="name"
        optionValue="code"
        checkmark
        filter
        showClear 
        class="m-5"
      ></Select>
    <p>Selected ID: {{ selectedValue }}</p> -->


</template>

<style scoped>
	.chart-container {
	max-width: 800px;
	width: 100%;
	margin: 0 auto;
	padding: 20px;
	}
	.chart-title {
	text-align: center;
	margin-bottom: 20px;
	font-size: 20px;
	font-weight: bold;
	}
</style>