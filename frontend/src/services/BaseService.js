import ErrorService from "./ErrorService";
import { apiClient } from "../services/HttpClient.js";

class BaseService
{
    constructor()
    {
        this.apiClient = apiClient;

        this.apiClient.interceptors.response.use(
            (response) => response,
            (error) => {
                if(error.response) {
                    ErrorService.logClientError(error, {
                        category: "api_error",
                        data: {
                            method: error.config.method,
                            url: error.config.url,
                            params: error.config.params,
                            data: error.config.data,
                        }
                    });
                }

                return Promise.reject(error);
            },
        );
    }

    handleError(error)
    {
        const status = error?.response?.status;

        if (status === 422) {
            console.warn("Validációs hiba:", error.response.data.errors);
            return Promise.reject(error.response.data.errors);
        }

        if (status === 401) {
            console.warn("Nincs jogosultság (401).");
        }

        if (status === 500) {
            console.error("Szerverhiba (500).");
        }

        return Promise.reject(error);
    }

    get(url, config = {})
    {
        return this.apiClient.get(url, config);
    }

    post(url, data, config = {})
    {
        return this.apiClient.post(url, data, config);
    }

    put(url, data, config = {})
    {
        return this.apiClient.put(url, data, config);
    }

    delete(url, config = {})
    {
        return this.apiClient.delete(url, {
            ...config,
            data: config.data || {},
        });
    }
}

export default BaseService;