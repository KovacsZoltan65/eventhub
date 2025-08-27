<script setup>
import { computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const auth = useAuthStore()

const isAuth = computed(() => auth.isAuthenticated)
const displayName = computed(() => isAuth.value ? (auth.user?.name ?? 'Ismeretlen') : 'Guard')

async function doLogout() {
    await auth.logout()
    router.replace('/login')
}
</script>

<template>
    <header class="border-b bg-white">
        <div class="container mx-auto px-4 py-3 flex items-center gap-6">
            <RouterLink to="/events" class="font-semibold">EventHub</RouterLink>

            <nav class="flex items-center gap-4 text-sm">
                <RouterLink 
                    to="/events" 
                    class="hover:underline"
                >
                    Események
                </RouterLink>

                <RouterLink v-if="auth.isAuthenticated" to="/bookings" class="px-3 py-1 border rounded hover:bg-gray-50">
                    Foglalásaim
                </RouterLink>

            </nav>

            <div class="ms-auto flex items-center gap-3 text-sm">
                <span>Felhasználó: <strong>{{ displayName }}</strong></span>

                <RouterLink
                    v-if="!isAuth"
                    to="/login"
                    class="px-3 py-1 border rounded hover:bg-gray-50"
                >
                    Login
                </RouterLink>

                <button
                    v-else
                    class="px-3 py-1 border rounded hover:bg-gray-50"
                    @click="doLogout"
                >
                    Logout
                </button>
            </div>
    </div>
  </header>
</template>
