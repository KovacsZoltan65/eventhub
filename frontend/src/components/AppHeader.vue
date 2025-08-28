<script setup>
import { computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const auth = useAuthStore()
const hasRole = (r) => auth.user?.roles?.includes(r)

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


                <!-- ESEMÉNYEK -->
                <RouterLink to="/events" class="hover:underline">Események</RouterLink>

                <!-- SZERVEZŐ ESEMÉNYEI -->
                <RouterLink
                    v-if="hasRole('organizer') || hasRole('admin')"
                    to="/organizer/events"
                    class="btn btn-eh"
                >
                    Szervező / Eseményeim
                </RouterLink>

                <!-- FELHASZNÁLÓK (role: admin) -->
                <RouterLink 
                    v-if="hasRole('admin')" 
                    to="/admin/users"
                >
                    Admin / Felhasználók
                </RouterLink>

                <!-- FOGLALÁSAIM -->
                <RouterLink v-if="auth.isAuthenticated" to="/bookings" class="px-3 py-1 border rounded hover:bg-gray-50">Foglalásaim</RouterLink>
                <RouterLink v-if="hasRole('admin')" to="/admin/bookings">Admin / Foglalások</RouterLink>

                <span class="ml-auto">
                    <span>Felhasználó: <strong>{{ displayName }}</strong></span>
                    <RouterLink class="btn btn-eh" v-if="!auth.isAuthenticated" to="/login">Login</RouterLink>
                    <button class="btn btn-eh booking-btn" v-else @click="auth.logout()">Logout</button>
                </span>
            </nav>

            <!--<div class="ms-auto flex items-center gap-3 text-sm">
                <span>Felhasználó: <strong>{{ displayName }}</strong></span>

                <RouterLink v-if="!isAuth" to="/login" class="px-3 py-1 border rounded hover:bg-gray-50" >
                    Login
                </RouterLink>

                <button v-else class="px-3 py-1 border rounded hover:bg-gray-50" @click="doLogout" >
                    Logout
                </button>
            </div>-->
    </div>
  </header>
</template>
