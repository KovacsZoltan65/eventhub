<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.js';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref('admin@eventhub.local');
const password = ref('Admin123!');

async function submit() {
    const ok = await auth.login(email.value, password.value);
    if (ok) {
        const go = route.query.redirect || '/events';
        router.replace(go);
    }
}
</script>

<template>
    <section class="container mx-auto p-4" style="max-width:420px">
        <h1 class="text-2xl font-semibold mb-4">Bejelentkezés</h1>

        <form @submit.prevent="submit" class="grid gap-3">
            <div>
                <label class="text-sm">Email</label>
                <input type="email" v-model="email" class="border rounded p-2 w-full" required />
            </div>
            <div>
                <label class="text-sm">Jelszó</label>
                <input type="password" v-model="password" class="border rounded p-2 w-full" required />
            </div>

            <button type="submit" class="px-4 py-2 border rounded hover:bg-gray-50" :disabled="auth.loading">
                {{ auth.loading ? 'Beléptetés…' : 'Belépés' }}
            </button>

            <div v-if="auth.error" class="text-red-600 text-sm">{{ auth.error }}</div>
        </form>
  </section>
</template>
