import { createRouter, createWebHistory } from "vue-router";

const routes = [
    { path: "/",           redirect: '/events' },
    { path: '/events',     component: () => import('../pages/events/Index.vue') },
    { path: '/events/:id', component: () => import('@/pages/events/Show.vue') },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});