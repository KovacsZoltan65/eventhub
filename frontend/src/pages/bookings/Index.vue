<script setup>
import { ref, onMounted, watch } from 'vue';
import BookingsService from '@/services/BookingsService.js';

const rows = ref([]);
const loading = ref(false);
const error = ref(null);

const status = ref(''); // '', 'pending', 'confirmed', 'cancelled'
const perPage = ref(10);
const page = ref(1);
const meta = ref(null);

const success = ref(null)
const actionLoadingId = ref(null)

function canCancel(b) {
    if (!b || b.status === 'cancelled') return false;
    // jövőbeli esemény?
    const dt = b.event?.starts_at ? new Date(b.event.starts_at) : null;
    return dt ? dt.getTime() > Date.now() : true;
}

async function cancelBooking(b) {
    if (!b) return;
    if (!canCancel(b)) return;
    if (!confirm(`Biztosan lemondod a #${b.id} foglalást (${b.event?.title ?? ''})?`)) return;

    success.value = null;
    error.value = null;
    actionLoadingId.value = b.id;

    try {
        const updated = await BookingsService.cancel(b.id);
        // frissítsük a sor adatait a kapott válasszal
        const idx = rows.value.findIndex(r => r.id === b.id);
        if (idx !== -1) rows.value[idx] = { ...rows.value[idx], ...updated }
        success.value = `Foglalás lemondva (#${b.id}).`;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Lemondás sikertelen.';
    } finally {
        actionLoadingId.value = null;
    }
}

function formatDate(iso) {
    if (!iso) return '-';
    try {
        return new Intl.DateTimeFormat('hu-HU', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

async function fetchData() {
    loading.value = true
    error.value = null
    try {
        const res = await BookingsService.listMine({
            status: status.value || '',
            page: page.value,
            perPage: perPage.value,
            field: 'created_at',
            order: 'desc',
        });
        rows.value = res.data || [];
        meta.value = res.meta || null;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Nem sikerült betölteni a foglalásokat.';
    } finally {
        loading.value = false;
    }
}

function toPage(p) {
    if (!meta.value) return;
    if (p < 1 || p > meta.value.last_page) return;
    page.value = p;
}

watch([status, perPage], () => {
    page.value = 1;
    fetchData();
})

onMounted(fetchData);
</script>

<template>
    <div class="max-w-5xl mx-auto p-4">
        <h1 class="text-2xl font-semibold mb-4">Saját foglalásaim</h1>

        <div class="mb-4 flex gap-3 items-center">
            <label class="text-sm">
                Státusz:
                <select v-model="status" class="border rounded p-1 ml-1">
                    <option value="">(mind)</option>
                    <option value="pending">Függőben</option>
                    <option value="confirmed">Megerősített</option>
                    <option value="cancelled">Törölt</option>
                </select>
            </label>

            <label class="text-sm">
                Sor/oldal:
                <select v-model.number="perPage" class="border rounded p-1 ml-1">
                    <option :value="10">10</option>
                    <option :value="20">20</option>
                    <option :value="50">50</option>
                </select>
            </label>
        </div>

        <div v-if="loading" class="p-4 border rounded bg-white shadow-sm">Betöltés…</div>
        <div v-if="success" class="mb-3 p-3 rounded border border-green-300 bg-green-50 text-green-700">
            {{ success }}
        </div>
        <div v-if="error" class="mb-3 p-3 rounded border border-red-300 bg-red-50 text-red-700">
            {{ error }}
        </div>

        <div v-else class="border rounded bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2">Azonosító</th>
                    <th class="text-left px-3 py-2">Esemény</th>
                    <th class="text-left px-3 py-2">Időpont</th>
                    <th class="text-left px-3 py-2">Helyszín</th>
                    <th class="text-left px-3 py-2">Db</th>
                    <th class="text-left px-3 py-2">Státusz</th>
                    <th class="text-left px-3 py-2">Foglalva</th>
                    <th class="text-left px-3 py-2">Művelet</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="rows.length === 0">
                    <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                        Nincs foglalás.
                    </td>
                </tr>
                <tr v-for="b in rows" :key="b.id" class="border-t">
                    <td class="px-3 py-2">#{{ b.id }}</td>
                    <td class="px-3 py-2">{{ b.event?.title ?? '—' }}</td>
                    <td class="px-3 py-2">{{ formatDate(b.event?.starts_at) }}</td>
                    <td class="px-3 py-2">{{ b.event?.location ?? '—' }}</td>
                    <td class="px-3 py-2">{{ b.quantity }}</td>
                    <td class="px-3 py-2">
                        <span
                            :class="{
                            'text-amber-700': b.status === 'pending',
                            'text-green-700': b.status === 'confirmed',
                            'text-gray-500 line-through': b.status === 'cancelled',
                            }"
                        >
                            {{ b.status }}
                        </span>
                    </td>
                    <td class="px-3 py-2">{{ formatDate(b.created_at) }}</td>
                    <td class="px-3 py-2">
                        <button
                            class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
                            :disabled="!canCancel(b) || actionLoadingId === b.id"
                            @click="cancelBooking(b)"
                            title="Foglalás lemondása"
                        >
                            Lemondás
                        </button>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>

        <!-- Lapozó -->
        <div v-if="meta && meta.last_page > 1" class="mt-4 flex items-center gap-2">
            <button class="px-3 py-1 border rounded" :disabled="page <= 1" @click="toPage(page - 1)">Előző</button>
            <span class="text-sm">
                {{ meta.from }}–{{ meta.to }} / {{ meta.total }} (oldal: {{ meta.current_page }}/{{ meta.last_page }})
            </span>
            <button class="px-3 py-1 border rounded" :disabled="page >= meta.last_page" @click="toPage(page + 1)">Következő</button>
        </div>
    </div>
</template>
