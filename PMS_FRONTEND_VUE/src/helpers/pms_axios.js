import axios from 'axios';
import { useUsersStore } from '@/stores/user';

// Create a dedicated instance for PMS Backend
const pmsApi = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api', // Use environment variable or fallback to local
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
