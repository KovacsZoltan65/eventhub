import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// https://vite.dev/config/
export default defineConfig({
    appType: 'spa',
    plugins: [vue()],
    resolve: {
        alias: { '@': path.resolve(__dirname, './src'), }
    },
    server: {
        port: 5173,
        strictPort: true,
        proxy: {
            // csak az API és Sanctum menjen a backend felé
            '^/(api|sanctum)': {
                target: 'http://localhost:8000',
                changeOrigin: true,
            },
        },
    },
});
