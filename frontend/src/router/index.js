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
  const auth = useAuthStore()

  // Session visszaállítás első betöltéskor – csendes (nem dob hibát)
  if (!auth.user && to.path !== '/login') {
    await auth.fetchMe()
  }

  // Csak a requiresAuth route-okra dobjunk loginra
  if (to.meta?.requiresAuth && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  // Role check, ha van roles meta
  if (to.meta?.roles?.length && auth.isAuthenticated && !to.meta.roles.includes(auth.role)) {
    return { path: '/' }
  }

  return true
})
