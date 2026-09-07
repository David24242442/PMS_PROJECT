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

// Determine API Base URL dynamically to support port 5050, localhost, and relative paths
let apiBase = import.meta.env.VITE_API_BASE_URL;
if (typeof window !== 'undefined' && window.location.origin) {
    const origin = window.location.origin;
    if (window.location.port === '5050' || window.location.hostname === '192.168.0.20' || window.location.hostname === 'localhost') {
        apiBase = `${origin}/pms_backend/api/`;
    }
}
if (!apiBase || apiBase === '/api/' || apiBase === '/') {
    apiBase = (typeof window !== 'undefined' && window.location.origin)
        ? `${window.location.origin}/pms_backend/api/`
        : 'http://192.168.0.20:5050/pms_backend/api/';
}
if (!apiBase.endsWith('/')) apiBase += '/';
axios.defaults.baseURL = apiBase;
const imgburl = '/storage/';
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use(config => {
    const token = localStorage.getItem('hrproject_user_token');
    if (token) {
        try {
            config.headers.Authorization = `Bearer ${JSON.parse(token)}`;
        } catch (e) {
            config.headers.Authorization = `Bearer ${token}`;
        }
    }
    return config;
});



const app = createApp(App)

import VueApexCharts from "vue3-apexcharts";
app.use(createPinia())
app.use(VueApexCharts);

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
    return Promise.reject(error);
})

app.use(router)

const MyPreset = definePreset(Nora, {
    primitive: {
        mcolor: { 50: "#eaedf5", 100: "#d6dbeb", 200: "#adb7d7", 300: "#8392c4", 400: "#5a6eb0", 500: "#314a9c", 600: "#273b7d", 700: "#1d2c5e", 800: "#141e3e", 900: "#0a0f1f", 950: "#0b0a1b" },
        mcolor1: { 50: "#ebeaf3", 100: "#d7d6e7", 200: "#afadce", 300: "#8883b6", 400: "#605a9d", 500: "#383185", 600: "#2d276a", 700: "#221d50", 800: "#161435", 900: "#0b0a1b", 950: "#0b0a1b" },
        mcoloro: {
            50: "#fdeeec", 100: "#fbddda", 200: "#f6bbb5", 300: "#f29a8f", 400: "#ed786a", 500: "#e95645", 600: "#ba4537", 700: "#8c3429", 800: "#5d221c", 900: "#2f110e", 950: "#2f110e"
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
        }
    }
});

app.use(PrimeVue, {
    theme: {
        preset: MyPreset
    }
});
app.use(ConfirmationService)

app.mount('#app')
