import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth.js';

const routes = [
  { path: '/', redirect: '/events' },
  { path: '/login', component: () => import('@/pages/auth/Login.vue') },
  { path: '/events', component: () => import('@/pages/events/Index.vue') },
  { path: '/events/:id', component: () => import('@/pages/events/Show.vue') },

  // védett útvonalak
  { path: '/admin', component: () => import('@/pages/admin/Index.vue'),
    meta: { requiresAuth: true, roles: ['admin'] } },
  { path: '/organizer', component: () => import('@/pages/organizer/Index.vue'),
    meta: { requiresAuth: true, roles: ['admin','organizer'] } },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  // első oldalbetöltéskor próbáljuk visszaállítani a session-t
  if (!auth.user && to.path !== '/login') {
    try { await auth.fetchMe(); } catch {}
  }

  if (to.meta?.requiresAuth && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } };
  }

  if (to.meta?.roles?.length && auth.isAuthenticated &&
      !to.meta.roles.includes(auth.role)) {
    return { path: '/' };
  }

  return true;
});
