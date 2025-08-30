import axios from 'axios'

export const originClient = axios.create({
    baseURL: '/',                // proxy miatt maradhat relatív
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
});

export const apiClient = axios.create({
    baseURL: '/api',             // proxy miatt relatív
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
});



/*
const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'
const API_PREFIX = import.meta.env.VITE_API_PREFIX ?? '/api'

// Sanctumhoz szükséges, hogy a sütik menjenek keresztbe:
export const originClient = axios.create({
  baseURL: API_BASE,            // pl. http://localhost:8000
  withCredentials: true,        // FONTOS!
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
});

export const apiClient = axios.create({
  baseURL: API_BASE + API_PREFIX, // pl. http://localhost:8000/api
  withCredentials: true,
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
});
*/