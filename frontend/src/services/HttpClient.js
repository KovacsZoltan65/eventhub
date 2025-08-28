import axios from 'axios';
import { CONFIG } from '@/helpers/constants.js';

function trimSlash(s) { return s.replace(/\/+$/, ''); }

export const apiClient = axios.create({
    baseURL: trimSlash(CONFIG.BASE_URL), // pl. http://localhost:8000/api
    withCredentials: true,               // /api/me-hez kell a session cookie
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});

export default apiClient;
