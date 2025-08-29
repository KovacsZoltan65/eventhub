// src/services/HttpClient.js
import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api',                 // <-- relatív! (Vite proxy továbbítja 8000-re)
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});

export default apiClient;
