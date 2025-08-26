import originClient from '@/services/OriginClient.js';
import apiClient from '@/services/HttpClient.js';

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
    const { data } = await apiClient.get('/me'); // -> /api/me
    return data;
  }

  async logout() {
    return originClient.post('/logout');
  }
}

export default new AuthService();
