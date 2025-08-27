<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import OrganizerEventsService from '@/services/OrganizerEventsService.js';
import EventForm from '@/components/events/EventForm.vue';
import { useAuthStore } from '@/stores/auth.js';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

// egyszerű role guard komponens szinten is (router-ben már meta.roles is lehet)
if (!auth.user) await auth.fetchMe();
if (!auth.user?.roles?.includes('organizer') && !auth.user?.roles?.includes('admin')) {
    router.replace({ name: 'events.index' });
}

const filters = reactive({
    search: '',
    status: '',       // draft / published / cancelled / ''
    field: 'starts_at',
    order: 'desc',
    perPage: 10,
    page: 1,
});

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref(null);

async function fetchRows(page = 1) {
    loading.value = true; error.value = null;
    try {
        const res = await OrganizerEventsService.list({ ...filters, page });
        rows.value = res.data;
        meta.value = {
            current: res.current_page,
            last: res.last_page,
            prev_url: res.prev_page_url,
            next_url: res.next_page_url,
            total: res.total,
        };
        filters.page = meta.value.current;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Betöltési hiba.';
    } finally { loading.value = false; }
}
onMounted(() => fetchRows());
watch(() => [filters.search, filters.status, filters.field, filters.order, filters.perPage], () => fetchRows(1));

// create / edit modál állapotok
const showCreate = ref(false);
const showEdit = ref(false);
const createModel = ref({});
const editModel = ref({});
const submitting = ref(false);

function openCreate() {
    createModel.value = {
        title: '', description: '', starts_at: '', location: '',
        capacity: 1, category: '', status: 'draft',
    }
    showCreate.value = true
};

function openEdit(row) {
    editModel.value = { ...row }
    showEdit.value = true
};

async function submitCreate() {
    submitting.value = true;
    try {
        await OrganizerEventsService.create(createModel.value);
        showCreate.value = false;
        await fetchRows(filters.page);
    } catch (e) {
        alert(e?.response?.data?.message || 'Mentési hiba.');
    } finally { submitting.value = false; }
};

async function submitEdit() {
    submitting.value = true;
    try {
        await OrganizerEventsService.update(editModel.value.id, editModel.value);
        showEdit.value = false;
        await fetchRows(filters.page);
    } catch (e) {
        alert(e?.response?.data?.message || 'Mentési hiba.');
    } finally { submitting.value = false; }
}

async function publish(row) {
    if (!confirm(`Biztosan publikálod? "${row.title}"`)) return;
    try { await OrganizerEventsService.publish(row.id); await fetchRows(filters.page) }
    catch (e) { alert(e?.response?.data?.message || 'Publikálási hiba.'); }
}

async function cancelEvent(row) {
    if (!confirm(`Biztosan lemondod? "${row.title}"`)) return;
    try { await OrganizerEventsService.cancel(row.id); await fetchRows(filters.page); }
    catch (e) { alert(e?.response?.data?.message || 'Lemondási hiba.'); }
}

async function remove(row) {
    if (!confirm(`Biztosan törlöd? "${row.title}"`)) return;
    try { await OrganizerEventsService.destroy(row.id); await fetchRows(rows.value.length > 1 ? filters.page : Math.max(1, filters.page - 1)); }
    catch (e) { alert(e?.response?.data?.message || 'Törlési hiba.'); }
}
</script>

<template>
    <div class="container mx-auto p-4 space-y-4">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold">Saját események</h1>
            <span class="ml-auto">
                <button class="px-3 py-2 border rounded" @click="openCreate">+ Új esemény</button>
            </span>
        </div>

        <div class="grid md:grid-cols-5 gap-2">
            <input v-model="filters.search" placeholder="Keresés cím/ leírás…" class="border p-2 rounded md:col-span-2">
            <select v-model="filters.status" class="border p-2 rounded">
                <option value="">— összes státusz —</option>
                <option value="draft">vázlat</option>
                <option value="published">közzétéve</option>
                <option value="cancelled">lemondva</option>
            </select>
            <select v-model="filters.field" class="border p-2 rounded">
                <option value="starts_at">kezdés</option>
                <option value="title">cím</option>
                <option value="location">helyszín</option>
                <option value="category">kategória</option>
                <option value="status">státusz</option>
            </select>
            <select v-model="filters.order" class="border p-2 rounded">
                <option value="desc">csökkenő</option>
                <option value="asc">növekvő</option>
            </select>
        </div>

        <div v-if="error" class="text-red-600">{{ error }}</div>
        <div v-if="loading">Betöltés…</div>

        <div class="overflow-auto border rounded">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-2 border-b">Cím</th>
                    <th class="text-left p-2 border-b">Kezdés</th>
                    <th class="text-left p-2 border-b">Helyszín</th>
                    <th class="text-left p-2 border-b">Kategória</th>
                    <th class="text-left p-2 border-b">Kapacitás</th>
                    <th class="text-left p-2 border-b">Státusz</th>
                    <th class="text-right p-2 border-b">Műveletek</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50">
                    <td class="p-2 border-b">{{ row.title }}</td>
                    <td class="p-2 border-b">{{ new Date(row.starts_at).toLocaleString() }}</td>
                    <td class="p-2 border-b">{{ row.location }}</td>
                    <td class="p-2 border-b">{{ row.category || '—' }}</td>
                    <td class="p-2 border-b">{{ row.capacity }}</td>
                    <td class="p-2 border-b">
                    <span
                        class="px-2 py-1 rounded text-xs"
                        :class="{
                        'bg-yellow-100 text-yellow-700': row.status==='draft',
                        'bg-green-100 text-green-700': row.status==='published',
                        'bg-red-100 text-red-700': row.status==='cancelled',
                        }"
                    >{{ row.status }}</span>
                    </td>
                    <td class="p-2 border-b text-right">
                    <button class="px-2 py-1 border rounded mr-1" @click="openEdit(row)">Szerk.</button>
                    <button class="px-2 py-1 border rounded mr-1" @click="publish(row)" :disabled="row.status==='published'">Publikál</button>
                    <button class="px-2 py-1 border rounded mr-1" @click="cancelEvent(row)" :disabled="row.status==='cancelled'">Lemond</button>
                    <button class="px-2 py-1 border rounded" @click="remove(row)">Törlés</button>
                    </td>
                </tr>
                <tr v-if="!loading && rows.length === 0">
                    <td colspan="7" class="p-3 text-center opacity-70">Nincs találat.</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta" class="flex items-center gap-2">
            <button class="border px-3 py-1 rounded" :disabled="!meta.prev_url" @click="fetchRows(filters.page - 1)">Előző</button>
            <span>{{ meta.current }} / {{ meta.last }}</span>
            <button class="border px-3 py-1 rounded" :disabled="!meta.next_url" @click="fetchRows(filters.page + 1)">Következő</button>
            <span class="ml-auto text-sm opacity-70">Összesen: {{ meta.total }}</span>
            <select v-model="filters.perPage" class="border p-1 rounded">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>

        <!-- Create modal -->
        <div v-if="showCreate" class="fixed inset-0 bg-black/30 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl p-4 w-full max-w-2xl">
                <h2 class="text-xl font-semibold mb-3">Új esemény</h2>
                <EventForm v-model="createModel" :loading="submitting" submit-text="Létrehozás"
                        @submit="submitCreate" @cancel="showCreate=false" />
            </div>
        </div>

        <!-- Edit modal -->
        <div v-if="showEdit" class="fixed inset-0 bg-black/30 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl p-4 w-full max-w-2xl">
                <h2 class="text-xl font-semibold mb-3">Esemény szerkesztése</h2>
                <EventForm v-model="editModel" :loading="submitting" submit-text="Mentés"
                        @submit="submitEdit" @cancel="showEdit=false" />
            </div>
        </div>
        
    </div>
</template>
