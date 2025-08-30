import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// https://vite.dev/config/
export default defineConfig({
    appType: 'spa',
    plugins: [vue()],
    resolve: {
        alias: { '@': path.resolve(__dirname, './src') }
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        proxy: {
            '/api':     { target: 'http://backend:8000', changeOrigin: true },
            '/login':   { target: 'http://backend:8000', changeOrigin: true },
            '/logout':  { target: 'http://backend:8000', changeOrigin: true },
            '/sanctum': { target: 'http://backend:8000', changeOrigin: true },
        },
        /*
        // PROXY a Laravel backendhez (http://localhost:8000)
        proxy: {
            // minden /api, /sanctum, /login, /logout hívás továbbmegy a backendre
            '^/(api|sanctum|login|logout)': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                // secure: false, // ha https-es backend lenne
            },
        },
        */
    },
});
