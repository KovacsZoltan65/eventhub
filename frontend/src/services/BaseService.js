import ErrorService from './ErrorService.js';
import apiClient, { apiClient as namedClient } from './HttpClient.js';

export default class BaseService
{
    constructor() {
        this.apiClient = namedClient;

        this.apiClient.interceptors.response.use(
            (res) => res,
            (err) => {
                if (err?.response?.status === 401) {
                    const url = err.config?.url || ''
                    // /me esetén ne irányítsunk (csak session probe)
                    if (url.endsWith('/me')) {
                        return Promise.reject(err)
                    }
                    // ...ide jöhetne opcionális redirect, de én azt javaslom, most ne irányíts globálisan
                    // window.location.href = '/login'
                }
                return Promise.reject(err)
            }
        );
    }

    get(url, config = {})    { return this.apiClient.get(url, config); }
    post(url, data, config={}) { return this.apiClient.post(url, data, config); }
    put(url, data, config={})  { return this.apiClient.put(url, data, config); }
    delete(url, config = {}) { return this.apiClient.delete(url, { ...config, data: config.data || {} }); }
}
