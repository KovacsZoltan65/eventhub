<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import EventsService from '../../services/EventService.js';
import BookingsService from '../../services/BookingsService.js';
import { useAuthStore } from '../../stores/auth.js';

const route = useRoute();
const router = useRouter();
const eventId = Number(route.params.id);
const qty = ref(1);
const loading = ref(true);
const error = ref(null);
const success = ref(null);
const eventItem = ref(null);
const auth = useAuthStore();

const isAuth = computed(() => auth.isAuthenticated);

const bookingState = ref({ loading: false, success: null, err: null });

function fmt(dt) { return dt ? new Date(dt).toLocaleString() : '—' };

function goLogin() {
  router.push({ path: '/login', query: { redirect: route.fullPath } })
}

async function load() {
    loading.value = true;
    error.value = null;
    try {
        const data = await EventsService.show(route.params.id);
        // Egységesítés: snake_case kulcsokat használunk a template-ben
        eventItem.value = data;
    } catch (e) {
        error.value = 'Nem található az esemény vagy hiba történt.';
    } finally {
        loading.value = false;
    }
}

async function book()
{
    if (!isAuth.value) {
        return goLogin();
    }

    loading.value = true;
    error.value = null;
    success.value = null;
    /*
    try {
        const res = await BookingsService.create({ event_id: eventId, quantity: qty.value });
        console.log('Show res',res);
        const bookingId = res.bookingId ?? res.id ?? res.booking?.id;
        const q = res.quantity ?? res.booking?.quantity;
        success.value = `Foglalás OK (#${bookingId}, db: ${q})`;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Foglalás sikertelen.';
    } finally {
        loading.value = false;
    }
    */
    
    try {
        const res = await BookingsService.create({ event_id: eventId, quantity: qty.value });
        console.log('Show.vue res', res);
        success.value = `Foglalás OK (#${res.bookingId}, db: ${res.quantity})`;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Foglalás sikertelen.';
    } finally {
        loading.value = false;
    }
    
}

onMounted(load);
</script>

<template>
    <section class="container mx-auto p-4" style="max-width:900px">
        <button class="mb-3 px-3 py-1 border rounded" @click="router.back()">← Vissza</button>

        <div v-if="loading">Betöltés…</div>
        <div v-else-if="error" class="text-red-600">{{ error }}</div>

        <div v-else-if="eventItem" class="space-y-3">
            <h1 class="text-2xl font-semibold">{{ eventItem.title }}</h1>
            <div class="opacity-70">{{ fmt(eventItem.starts_at) }} • {{ eventItem.location }}</div>

            <div class="flex flex-wrap gap-3 text-sm">
                <span class="px-2 py-0.5 border rounded">Kategória: {{ eventItem.category || '—' }}</span>
                <span class="px-2 py-0.5 border rounded">Státusz: {{ eventItem.status }}</span>
            </div>

            <p class="mt-2 whitespace-pre-line">{{ eventItem.description }}</p>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="border rounded p-3">
                    <div class="text-sm">Kapacitás</div>
                    <div class="text-xl font-semibold">{{ eventItem.capacity }}</div>
                </div>
                <div class="border rounded p-3">
                    <div class="text-sm">Szabad hely</div>
                    <!-- a backend most már dinamikusan számolja (capacity - confirmed) és 'remaining_seats' néven adja -->
                    <div class="text-xl font-semibold">{{ eventItem.remaining_seats ?? '—' }}</div>
                </div>
            </div>

            <!-- Foglalás doboz -->
            <div class="mt-4 flex items-center gap-2">
                
                <template v-if="isAuth">
                    <input type="number" v-model.number="qty" min="1" :max="5" class="border rounded p-1 w-16" />
                    <button class="px-3 py-1 border rounded hover:bg-gray-50" :disabled="loading" @click="book">
                        {{ loading ? 'Foglalás…' : 'Foglalás' }}
                    </button>
                </template>

                <template v-else>
                    <span class="text-sm text-gray-600">Foglaláshoz be kell jelentkezned.</span>
                    <button class="px-3 py-1 border rounded hover:bg-gray-50" @click="goLogin">Login</button>
                </template>

            </div>

            <p v-if="error" class="text-red-600 text-sm mt-2">{{ error }}</p>
            <p v-if="success" class="text-green-700 text-sm mt-2">{{ success }}</p>
            
        </div>
    </section>
</template>
