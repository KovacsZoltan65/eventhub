<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth.js';
import AdminBookingsService from '@/services/AdminBookingsService.js';

const auth = useAuthStore();

const filters = reactive({
    user_id: '',
    event_id: '',
    status: '',
    date_from: '',
    date_to: '',
    field: 'created_at',
    order: 'desc',
    perPage: 12,
    page: 1,
});

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref(null);

async function fetchRows(page = 1) {
    loading.value = true; error.value = null
    try {
        const res = await AdminBookingsService.list({ ...filters, page });
        rows.value = res.data;
        meta.value = {
            current: res.current_page,
            last: res.last_page,
            prev: res.prev_page_url,
            next: res.next_page_url,
            total: res.total,
        };
        filters.page = meta.value.current;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Betöltési hiba.';
    } finally {
        loading.value = false;
    }
}
onMounted(() => fetchRows());
watch(
    () => [filters.user_id, filters.event_id, filters.status, filters.date_from, filters.date_to, filters.field, filters.order, filters.perPage],
    () => fetchRows(1)
);

async function cancelRow(row) {
    if (!confirm(`Biztosan lemondod a foglalást #${row.id}?`)) return;
    try {
        await AdminBookingsService.cancel(row.id); 
        await fetchRows(filters.page);
    } catch (e) {
        alert(e?.response?.data?.message || 'Lemondási hiba.');
    }
}
</script>

<template>
    <div class="container mx-auto p-4 space-y-4">
        <h1 class="text-2xl font-bold">Foglalások (Admin)</h1>

        <div class="grid md:grid-cols-6 gap-2">
            <input v-model="filters.user_id"  type="number" min="1" placeholder="User ID"  class="border p-2 rounded">
            <input v-model="filters.event_id" type="number" min="1" placeholder="Event ID" class="border p-2 rounded">
            <select v-model="filters.status" class="border p-2 rounded">
                <option value="">— összes státusz —</option>
                <option value="pending">pending</option>
                <option value="confirmed">confirmed</option>
                <option value="cancelled">cancelled</option>
            </select>
            <input v-model="filters.date_from" type="date" class="border p-2 rounded">
            <input v-model="filters.date_to"   type="date" class="border p-2 rounded">
            <div class="flex gap-2">
                <select v-model="filters.field" class="border p-2 rounded">
                    <option value="created_at">létrehozva</option>
                    <option value="quantity">mennyiség</option>
                    <option value="total_price">összeg</option>
                </select>
                <select v-model="filters.order" class="border p-2 rounded">
                    <option value="desc">desc</option>
                    <option value="asc">asc</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="text-red-600">{{ error }}</div>
        <div v-if="loading">Betöltés…</div>

        <div class="overflow-auto border rounded">
        <table class="min-w-full border-collapse">
            <thead class="bg-gray-50">
            <tr>
                <th class="p-2 border-b text-left">#</th>
                <th class="p-2 border-b text-left">Esemény</th>
                <th class="p-2 border-b text-left">User</th>
                <th class="p-2 border-b text-left">Menny.</th>
                <th class="p-2 border-b text-left">Összeg</th>
                <th class="p-2 border-b text-left">Státusz</th>
                <th class="p-2 border-b text-right">Művelet</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="b in rows" :key="b.id" class="hover:bg-gray-50">
                <td class="p-2 border-b">{{ b.id }}</td>
                <td class="p-2 border-b">
                    <div class="font-medium">{{ b.event?.title || b.event_title }}</div>
                    <div class="text-xs opacity-70">{{ b.event?.location || b.event_location }}</div>
                    <div class="text-xs opacity-70">{{ new Date(b.created_at).toLocaleString() }}</div>
                </td>
                <td class="p-2 border-b">
                    <div>{{ b.user?.name || b.user_name }}</div>
                    <div class="text-xs opacity-70">{{ b.user?.email || b.user_email }}</div>
                </td>
                <td class="p-2 border-b">{{ b.quantity }}</td>
                <td class="p-2 border-b">{{ b.total_price ?? '—' }}</td>
                <td class="p-2 border-b">
                    <span class="px-2 py-1 text-xs rounded"
                        :class="{
                            'bg-green-100 text-green-700': b.status==='confirmed',
                            'bg-yellow-100 text-yellow-700': b.status==='pending',
                            'bg-red-100 text-red-700': b.status==='cancelled',
                        }">{{ b.status }}</span>
                </td>
                <td class="p-2 border-b text-right">
                    <button class="px-2 py-1 border rounded"
                            @click="cancelRow(b)"
                            :disabled="b.status==='cancelled'">
                        Lemond
                    </button>
                </td>
            </tr>
            <tr v-if="!loading && rows.length===0">
                <td colspan="7" class="p-3 text-center opacity-70">Nincs találat.</td>
            </tr>
            </tbody>
        </table>
        </div>

        <div v-if="meta" class="flex items-center gap-2">
            <button class="border px-3 py-1 rounded" :disabled="!meta.prev" @click="fetchRows(filters.page - 1)">Előző</button>
            <span>{{ meta.current }} / {{ meta.last }}</span>
            <button class="border px-3 py-1 rounded" :disabled="!meta.next" @click="fetchRows(filters.page + 1)">Következő</button>
            <span class="ml-auto text-sm opacity-70">Összesen: {{ meta.total }}</span>
            <select v-model="filters.perPage" class="border p-1 rounded">
                <option :value="12">12</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>
        
    </div>
</template>
