import axios from 'axios';
import { useUsersStore } from '@/stores/user';

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

// Create a dedicated instance for PMS Backend
const pmsApi = axios.create({
    baseURL: apiBase,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// Request Interceptor (Attach Token)
pmsApi.interceptors.request.use(config => {
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

// Response Interceptor (Handle 401)
pmsApi.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Handle Logout via Store if needed, or redirect
            // For now, just reject
            const userstore = useUsersStore();
            userstore.setloguser(null);
            localStorage.removeItem('hrproject_user');
            localStorage.removeItem('hrproject_user_token');
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);

export default pmsApi;
