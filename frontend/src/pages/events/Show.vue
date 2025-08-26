<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EventsService from '../../services/EventService.js'
import BookingService from '../../services/BookingService.js'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref(null)
const eventItem = ref(null)

const qty = ref(1)
const bookingState = ref({ loading: false, success: null, err: null })

function fmt(dt) { return dt ? new Date(dt).toLocaleString() : '—' }

async function load() {
    loading.value = true
    error.value = null
    try {
        const data = await EventsService.show(route.params.id)
        // Egységesítés: snake_case kulcsokat használunk a template-ben
        eventItem.value = data
    } catch (e) {
        error.value = 'Nem található az esemény vagy hiba történt.'
    } finally {
        loading.value = false
    }
}

async function book() {
    bookingState.value = { loading: true, success: null, err: null }
    try {
        const payload = { event_id: eventItem.value.id, quantity: qty.value }
        const resp = await BookingService.create(payload)
        bookingState.value.success = resp
    } catch (e) {
        // egyszerű hibaüzenet
        bookingState.value.err = e?.response?.data?.message || 'A foglalás nem sikerült.'
    } finally {
        bookingState.value.loading = false
    }
}

onMounted(load)
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
            <div class="mt-6 border rounded p-4">
                <h2 class="font-semibold mb-3">Foglalás</h2>

                <div class="flex items-center gap-3">
                    <label class="text-sm">Mennyiség (max. 5):</label>
                    <input
                        type="number"
                        min="1"
                        max="5"
                        v-model.number="qty"
                        class="border rounded p-2 w-24"
                    />
                    <button
                        class="px-4 py-2 border rounded hover:bg-gray-50"
                        :disabled="bookingState.loading || qty<1 || qty>5 || eventItem.status!=='published'"
                        @click="book"
                    >
                        {{ bookingState.loading ? 'Küldés…' : 'Foglalok' }}
                    </button>
                </div>

                <div v-if="bookingState.err" class="mt-3 text-red-600">
                    {{ bookingState.err }}
                </div>

                <div v-if="bookingState.success" class="mt-3 text-green-700">
                    Sikeres foglalás!
                    <span v-if="bookingState.success.bookingId">
                        Azonosító: <b>{{ bookingState.success.bookingId }}</b>
                    </span>
                </div>

                <div class="mt-2 text-xs opacity-70">
                    Megjegyzés: be nem lépett felhasználónál a backend jogosultságot ellenőrizhet (401/403).
                </div>
            </div>
        </div>
    </section>
</template>
