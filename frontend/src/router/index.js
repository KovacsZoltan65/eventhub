import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const routes = [
    { path: '/', redirect: '/events' },
    { path: '/login', component: () => import('@/pages/auth/Login.vue') },

    // Public
    { path: '/events', name: 'events.index', component: () => import('@/pages/events/Index.vue'), meta: { requiresAuth: false } },
    { path: '/events/:id', name: 'events.show', component: () => import('@/pages/events/Show.vue'), meta: { requiresAuth: false } },

    // Admin
    //{ path: '/admin', name: 'admin.index', component: () => import('@/pages/admin/Index.vue'), meta: { requiresAuth: true, roles: ['admin'] } },
    { path: '/admin/users', name: 'admin.users', component: () => import('@/pages/admin/users/Index.vue'), meta: { requiresAuth: true, roles: ['admin'] } },
    { path: '/admin/bookings', name: 'admin.bookings', component: () => import('@/pages/admin/bookings/Index.vue'), meta: { requiresAuth: true, roles: ['admin'] }, },

    // Organizer
    { path: '/organizer', name: 'organizer.index', component: () => import('@/pages/organizer/Index.vue'), meta: { requiresAuth: true, roles: ['admin','organizer'] } },
    { path: '/organizer/events', name: 'organizer.events', component: () => import('@/pages/organizer/events/Index.vue'), meta: { requiresAuth: true, roles: ['admin','organizer'] } },

    // Saját foglalások
    { path: '/bookings', name: 'bookings.mine', component: () => import('@/pages/bookings/Index.vue'), meta: { requiresAuth: true } },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // Csendes session visszaállítás első betöltéskor
    if (!auth.user && to.path !== '/login') {
        try { await auth.fetchMe() } catch (_) {}
    }

    // 1) Auth guard
    if (to.meta?.requiresAuth && !auth.isAuthenticated) {
        return { path: '/login', query: { redirect: to.fullPath } };
    }

    // 2) Role guard (TÖMBÖK összevetése!)
    if (to.meta?.roles?.length && auth.isAuthenticated) {
        const userRoles = auth.user?.roles || [];
        const ok = to.meta.roles.some(r => userRoles.includes(r));
        if (!ok) return { path: '/' } // vagy egy 403-as oldal
    }

    return true;
})
