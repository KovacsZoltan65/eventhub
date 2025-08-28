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

//const bookingState = ref({ loading: false, success: null, err: null });

/**
 * Formáz egy dátumot ember által olvasható formára.
 * @param {string|number|Date} dt dátum (ISO 8601 string, timestamp, vagy Date-objektum)
 * @returns {string} Formázott dátum, vagy `'—'` ha a dátum nincs megadva
 */
const fmt = (dt) => {
    return dt ? new Date(dt).toLocaleString() : '—';
};

/**
 * A bejelentkezés oldalra navigál a jelenlegi oldal URL-jével, mint redirect cél
 */
const goLogin = () => {
  router.push({ path: '/login', query: { redirect: route.fullPath } })
}

/**
 * Betölti az eseményt a megadott ID-val.
 * Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-ra.
 */
const load = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const data = await EventsService.show(route.params.id);
        
        eventItem.value = data;
    } catch (e) {
        error.value = 'Nem található az esemény vagy hiba történt.';
    } finally {
        loading.value = false;
    }
}

/**
 * Foglalja le az eseményt a megadott mennyiségben.
 * Ha nem vagy bejelentkezve, akkor a bejelentkezés oldalra navigál.
 * A foglalás eredményéről a `success`-en keresztül kapunk visszajelzést.
 * Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-ra.
 */
const book = async () => {

    // Ha nincs bejelentkezve, akkor a bejelentkezés oldalra navigálunk
    if (!isAuth.value) {
        return goLogin();
    }

    // Foglalás indítás előtt nulla állapotot állítunk be
    loading.value = true;
    error.value = null;
    success.value = null;

    try {
        // Foglalás létrehozása a megadott eseményhez és mennyiségben
        // A foglalás eredményéről a res objektumon keresztül kapunk visszajelzést
        const res = await BookingsService.create({ 
            event_id: eventId, // az esemény ID-ja
            quantity: qty.value, // a foglalni kívánt mennyiség
        });
        
        // A foglalás sikeres, megjelenítjük a foglalás eredményét
        // A success változóban tároljuk a sikeresség üzenetét
        success.value = `Foglalás OK (#${res.bookingId}, db: ${res.quantity})`;

    } catch (e) {
        // A foglalás nem sikerült, hiba történt.
        // A hibaüzenetet beállítjuk a `error` változóban
        // A hibaüzenet egy része a szerver által visszaadott JSON-ban
        // található `message` kulcs alatt, ha van ilyen.
        error.value = e?.response?.data?.message || 'Foglalás sikertelen.';
    } finally {
        loading.value = false;
    }
    
}

onMounted(load);

</script>

<template>
    <section class="container mx-auto p-4" style="max-width:900px">
        <button class="mb-3 px-3 py-1 border rounded btn btn-eh" @click="router.back()">← Vissza</button>

        <div v-if="loading">Betöltés…</div>
        <div v-else-if="error" class="text-red-600">{{ error }}</div>

        <div v-else-if="eventItem" class="space-y-3">
            <h1 class="text-2xl font-semibold">{{ eventItem.title }}</h1>
            <div class="opacity-70">{{ fmt(eventItem.starts_at) }} • {{ eventItem.location }}</div>

            <div class="space-y-2 text-sm">
                <div>
                    <strong>Kategória: </strong>
                    <span class="ml-1">{{ eventItem.category || '—' }}</span>
                </div>
                <div>
                    <strong>Státusz: </strong>
                    <span class="ml-1">{{ eventItem.status }}</span>
                </div>
            </div>


            <p class="mt-2 whitespace-pre-line">{{ eventItem.description }}</p>

            <!-- Aktuális állapot -->
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="border rounded p-3">
                    <div class="text-sm"><strong>Kapacitás</strong></div>
                    <div class="text-xl font-semibold">{{ eventItem.capacity }}</div>
                </div>
                <div class="border rounded p-3">
                    <div class="text-sm"><strong>Szabad hely</strong></div>
                    <!-- a backend most már dinamikusan számolja (capacity - confirmed) és 'remaining_seats' néven adja -->
                    <div class="text-xl font-semibold">{{ eventItem.remaining_seats ?? '—' }}</div>
                </div>
            </div>

            <!-- Foglalás doboz -->
            <div class="mt-4 flex items-center gap-2">
                
                <template v-if="isAuth">
                    <input 
                        type="number" 
                        v-model.number="qty" 
                        min="1" :max="5" 
                        class="booking-input"
                    />

                    <button 
                        class="btn btn-eh booking-btn" 
                        :disabled="loading" 
                        @click="book"
                    >
                        {{ loading ? 'Foglalás…' : 'Foglalás' }}
                    </button>

                </template>

                <template v-else>
                    <span class="text-sm text-gray-600">Foglaláshoz be kell jelentkezned.</span>
                    <button class="btn btn-eh booking-btn" @click="goLogin">Login</button>
                </template>

            </div>

            <p v-if="error" class="text-red-600 text-sm mt-2">{{ error }}</p>
            <p v-if="success" class="text-green-700 text-sm mt-2">{{ success }}</p>
            
        </div>
    </section>
</template>
