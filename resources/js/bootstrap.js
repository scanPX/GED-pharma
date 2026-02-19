import axios from 'axios';

/**
 * Configure Axios for GED Pharma API
 */

// Create axios instance
const api = axios.create({
    baseURL: '/api/ged',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Request interceptor - Add auth token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('ged_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor - Handle errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        // Handle 401 Unauthorized - redirect to login
        if (error.response?.status === 401) {
            localStorage.removeItem('ged_token');
            localStorage.removeItem('ged_user');
            // Only redirect if not already on login page
            if (!window.location.pathname.includes('/auth/login')) {
                window.location.href = '/auth/login';
            }
        }
        
        // Handle 403 Forbidden
        if (error.response?.status === 403) {
            console.error('Access denied:', error.response.data.message);
        }
        
        // Handle 422 Validation errors
        if (error.response?.status === 422) {
            console.warn('Validation error:', error.response.data.errors);
        }
        
        // Handle 500 Server errors
        if (error.response?.status >= 500) {
            console.error('Server error:', error.response.data.message);
        }
        
        return Promise.reject(error);
    }
);

// Export for use in stores and components
window.axios = axios;
export { api };
export default api;
