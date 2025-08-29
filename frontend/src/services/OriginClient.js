// src/services/OriginClient.js
import axios from 'axios';

const originClient = axios.create({
  baseURL: '/',                    // <-- NE backend origin! Maradjon a saját (5173) origin.
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
});

// (opcionális) interceptor maradhat, de itt már látni fogja a sütit:
originClient.interceptors.request.use((config) => {
  // axios magától is beteszi; redundáns, de nem árt
  return config;
});

export default originClient;
