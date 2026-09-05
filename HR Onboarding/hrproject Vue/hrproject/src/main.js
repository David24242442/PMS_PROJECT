import './assets/main.css'
import 'vue3-toastify/dist/index.css';
import './index.css'
import 'nprogress/nprogress.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import axios from 'axios';

import PrimeVue from 'primevue/config';
import Nora from '@primevue/themes/nora';
import { definePreset } from '@primevue/themes';
import 'primeicons/primeicons.css'
import ConfirmationService from 'primevue/confirmationservice'

const baseurl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/';

axios.defaults.baseURL = baseurl;
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';



const app = createApp(App)

import VueApexCharts from "vue3-apexcharts";
app.use(VueApexCharts);

app.use(createPinia())

import { useUsersStore } from '@/stores/user';


axios.interceptors.response.use(function (response) {
    return response
    }, function (error) {
        if (error.response && error.response.status === 401) {

            const userstore = useUsersStore()
            let { setloguser } = userstore
            setloguser(null)
            localStorage.removeItem('hrproject_user');
            localStorage.removeItem('hrproject_user_token');
            
            router.push('/')
        }
    })

app.use(router)

const MyPreset = definePreset(Nora, {
    primitive: {
        mcolor:{50: "#eaedf5", 100: "#d6dbeb", 200: "#adb7d7", 300: "#8392c4", 400: "#5a6eb0", 500: "#314a9c", 600: "#273b7d", 700: "#1d2c5e", 800: "#141e3e", 900: "#0a0f1f", 950: "#0b0a1b"},
        mcolor1: {50: "#ebeaf3", 100: "#d7d6e7", 200: "#afadce", 300: "#8883b6", 400: "#605a9d", 500: "#383185", 600: "#2d276a", 700: "#221d50", 800: "#161435", 900: "#0b0a1b", 950: "#0b0a1b"},
        mcoloro: {
          50: "#fdeeec",100: "#fbddda",200: "#f6bbb5",300: "#f29a8f",400: "#ed786a",500: "#e95645",600: "#ba4537",700: "#8c3429",800: "#5d221c",900: "#2f110e",950: "#2f110e"
        }
    },
    semantic: {
        primary: {
            50: '{mcolor.50}',
            100: '{mcolor.100}',
            200: '{mcolor.200}',
            300: '{mcolor.300}',
            400: '{mcolor.400}',
            500: '{mcolor.500}',
            600: '{mcolor.600}',
            700: '{mcolor.700}',
            800: '{mcolor.800}',
            900: '{mcolor.900}',
            950: '{mcolor.950}'
        },
        colorScheme: {
            dark: {
                surface: {
                    0: '#ffffff',
                    50: '#1a2332',
                    100: '#1e293b',
                    200: '#334155',
                    300: '#475569',
                    400: '#64748b',
                    500: '#94a3b8',
                    600: '#cbd5e1',
                    700: '#e2e8f0',
                    800: '#f1f5f9',
                    900: '#f8fafc',
                    950: '#ffffff'
                },
                primary: {
                    color: '#818cf8',
                    inverseColor: '#1e1b4b',
                    hoverColor: '#a5b4fc',
                    activeColor: '#6366f1'
                },
                highlight: {
                    background: 'rgba(129, 140, 248, 0.16)',
                    focusBackground: 'rgba(129, 140, 248, 0.24)',
                    color: '#c7d2fe',
                    focusColor: '#e0e7ff'
                }
            }
        }
    }
});

app.use(PrimeVue, {
    theme: {
        preset: MyPreset,
        options: {
            darkModeSelector: '.dark-mode',
        }
    }
});
app.use(ConfirmationService)

app.mount('#app')
