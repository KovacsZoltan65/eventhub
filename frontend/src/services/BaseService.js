import ErrorService from './ErrorService.js';
import apiClient, { apiClient as namedClient } from './HttpClient.js';

export default class BaseService {
  constructor() {
    this.apiClient = namedClient;

    this.apiClient.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error?.response) {
          try {
            ErrorService.logClientError(error, {
              category: 'api_error',
              data: {
                url: error.config?.url,
                method: error.config?.method,
                status: error.response?.status,
                response: error.response?.data,
              },
            });
          } catch {}
        }
        return Promise.reject(error);
      }
    );
  }

  get(url, config = {})    { return this.apiClient.get(url, config); }
  post(url, data, config={}) { return this.apiClient.post(url, data, config); }
  put(url, data, config={})  { return this.apiClient.put(url, data, config); }
  delete(url, config = {}) { return this.apiClient.delete(url, { ...config, data: config.data || {} }); }
}
