import { defineStore } from 'pinia';
import AuthService from '../services/AuthService';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (s) => !!s.user,
        role: (s) => s.user?.role ?? 'guest',
    },
    actions: {
        async fetchMe() {
        this.loading = true; this.error = null;
        try {
            const user = await AuthService.me();
            this.user = user;
        } catch (e) {
            this.user = null;
        } finally {
            this.loading = false;
        }
    },
    async login(email, password) {
      this.loading = true; this.error = null;
      try {
        await AuthService.login({ email, password });
        const user = await AuthService.me();
        this.user = user;
        return true;
      } catch (e) {
        this.error = e?.response?.data?.message || 'Sikertelen bejelentkezés.';
        return false;
      } finally {
        this.loading = false;
      }
    },
    async logout() {
      this.loading = true;
      try {
        await AuthService.logout();
      } finally {
        this.user = null;
        this.loading = false;
      }
    },
  },
});
