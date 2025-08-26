import axios from 'axios';
import { CONFIG } from '@/helpers/constants.js';

function originFromBase(baseUrl) {
  try {
    const u = new URL(baseUrl, window.location.origin);
    return `${u.protocol}//${u.hostname}${u.port ? ':' + u.port : ''}`;
  } catch {
    return 'http://localhost:8000';
  }
}

export const originBase = originFromBase(CONFIG.BASE_URL);

function getCookie(name) {
  const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return m ? decodeURIComponent(m[2]) : null;
}

export const originClient = axios.create({
  baseURL: originBase, // pl. http://localhost:8000
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
});

// Mindig tegyük fel a XSRF headert a cookie-ból (biztos, ami biztos)
originClient.interceptors.request.use((config) => {
  const token = getCookie('XSRF-TOKEN');
  if (token) config.headers['X-XSRF-TOKEN'] = token;
  return config;
});

export default originClient;
