import { apiClient, originClient } from './http';

class AuthService {
    async csrf() {
        return originClient.get('/sanctum/csrf-cookie');
    }

    async login({ email, password }) {
        await this.csrf();
        const { data } = await originClient.post('/login', { email, password });
        return data;
    }

    async me() {
        // 401-et is "sikeresnek" tekintjük (nem reject-el), így nem lesz Uncaught
        const res = await apiClient.get('/me', {
            validateStatus: s => s === 200 || s === 401
        })
        return res.status === 200 ? res.data : null
    }

    async logout() {
        return originClient.post('/logout');
    }
}

export default new AuthService();
