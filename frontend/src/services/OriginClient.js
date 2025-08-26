/** A meglévő HttpClient.js az API-ra mutat. Kell egy „origin” kliens, ami a gyökérre (http://localhost:8000) megy: */

import axios from 'axios';
import { CONFIG } from '@/helpers/constants.js';

function extractOrigin(baseUrl)
{
    try {
        const u = new URL(baseUrl, window.location.origin);
        return `${u.protocol}//${u.hostname}${u.port ? ':' + u.port : ''}`;
    } catch {
        // ha relatív volt (pl. '/api'), akkor fallback a böngésző originre
        return window.location.origin;
    }
}

export const originBase = extractOrigin(CONFIG.BASE_URL);

export const originClient = axios.create({
    baseURL: originBase,
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});