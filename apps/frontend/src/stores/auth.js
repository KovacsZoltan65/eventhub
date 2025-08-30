import { defineStore } from 'pinia';
import AuthService from '@/services/AuthService.js';

export const useAuthStore = defineStore('auth', {
    state: () => ({ user: null, loading: false, error: null }),

    // getter-ek: olvashatunk a state-ből
    getters: {
        /**
         * Igaz, ha van bejelentkezett felhasználó.
         * @returns {boolean}
         */
        isAuthenticated: s => !!s.user,

        /**
         * A bejelentkezett felhasználó szerepei (tömb).
         * @returns {string[]}
         */
        roles: s => s.user?.roles ?? [],
    },

    // action-ok: írhatunk a state-be
    actions: {
        /**
         * Lekéri a bejelentkezett felhasználó adatait.
         * @returns {Promise<void>}
         */
        async fetchMe() {
            this.loading = true; this.error = null
            try {
                const me = await AuthService.me()
                this.user = me // lehet null is – ez oké
            } finally {
                this.loading = false
            }
        },

        /**
         * Bejelentkezik a megadott e-mail címmel és jelszóval.
         * @param {string} email - a bejelentkezni akaró felhasználó e-mail címe
         * @param {string} password - a bejelentkezni akaró felhasználó jelszava
         * @returns {Promise<boolean>} - igaz, ha sikeres volt a bejelentkezés
         */
        async login(email, password) {
            this.loading = true; this.error = null
            try {
                await AuthService.login({ email, password })
                this.user = await AuthService.me()
                return true
            } catch (e) {
                this.error = e?.response?.data?.message || 'Sikertelen bejelentkezés.'
                return false
            } finally {
                this.loading = false
            }
        },

        /**
         * Kijelentkezik.
         * @returns {Promise<void>}
         */
        async logout() {
            this.loading = true
            try { await AuthService.logout() } finally { this.user = null; this.loading = false }
        },
    },
});
