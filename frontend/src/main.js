import { createApp } from 'vue';
import { router } from '@/router';
import App from './App.vue';
import { createPinia } from 'pinia';

import '@picocss/pico/css/pico.min.css';
import './assets/pico-tweaks.css';

createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app');