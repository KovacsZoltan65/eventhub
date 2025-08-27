<script setup>
import { ref, onMounted } from 'vue';
import AdminUsersService from '../../../services/AdminUsersService';

const loading = ref(false);
const togglingId = ref(null);
const error = ref('');
const success = ref('');

const rows = ref([]);
const meta = ref(null);

const filters = ref({
    search: '',
    perPage: 12,
    field: 'name',
    order: 'asc',
});

function fmt(s) {
    if (!s) return '';

    const d = new Date(s);

    return d.toLocaleString('hu-HU', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

async function fetchUsers(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const res = await AdminUsersService.list({
            search: filters.value.search,
            perPage: filters.value.perPage,
            field: filters.value.field,
            order: filters.value.order,
            page,
        });
        rows.value = res.data ?? res?.data ?? []; // Laravel paginator
        meta.value = res.meta ?? {
            current_page: res.current_page,
            last_page: res.last_page,
            links: {
                prev: res.prev_page_url,
                next: res.next_page_url,
            },
        };
    } catch (e) {
        error.value = e?.response?.data?.message || 'Hiba a felhasználók betöltése közben.';
    } finally {
        loading.value = false;
    }
}

async function toggle(u) {
    error.value = '';
    success.value = '';
    const next = !u.is_blocked;
    togglingId.value = u.id;
    const old = u.is_blocked;

    u.is_blocked = next;

    try {
        await AdminUsersService.setBlocked(u.id, next);
        success.value = next ? `Felhasználó tiltva (#${u.id}).` : `Felhasználó engedélyezve (#${u.id}).`;
    } catch (e) {
        u.is_blocked = old; // visszaállít
        error.value = e?.response?.data?.message || 'A módosítás nem sikerült.';
    } finally {
        togglingId.value = null;
    }
}

onMounted(() => fetchUsers(1));
</script>

<template>
    <div class="p-4 space-y-4">
        <h1 class="text-2xl font-semibold">Felhasználók</h1>

        <div class="flex flex-wrap items-center gap-2">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Keresés név vagy e-mail szerint…"
                class="border rounded px-3 py-1 w-64"
            />
            <select v-model.number="filters.perPage" class="border rounded px-2 py-1">
                <option :value="10">10</option>
                <option :value="12">12</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
            <select v-model="filters.field" class="border rounded px-2 py-1">
                <option value="name">Név</option>
                <option value="email">Email</option>
                <option value="created_at">Regisztrált</option>
                <option value="is_blocked">Tiltva?</option>
            </select>
            <select v-model="filters.order" class="border rounded px-2 py-1">
                <option value="asc">Növekvő</option>
                <option value="desc">Csökkenő</option>
            </select>
            <button @click="fetchUsers(1)" class="px-3 py-1 border rounded hover:bg-gray-50">
                Szűrés
            </button>
        </div>

        <div class="overflow-x-auto border rounded">
            <table class="min-w-full divide-y">
                <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2">ID</th>
                    <th class="text-left px-3 py-2">Név</th>
                    <th class="text-left px-3 py-2">Email</th>
                    <th class="text-left px-3 py-2">Regisztrált</th>
                    <th class="text-left px-3 py-2">Tiltva?</th>
                    <th class="px-3 py-2"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="loading">
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">Betöltés…</td>
                </tr>
                <tr v-else-if="rows.length === 0">
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">Nincs találat.</td>
                </tr>
                <tr v-for="u in rows" :key="u.id" class="odd:bg-white even:bg-gray-50">
                    <td class="px-3 py-2">{{ u.id }}</td>
                    <td class="px-3 py-2">{{ u.name }}</td>
                    <td class="px-3 py-2">{{ u.email }}</td>
                    <td class="px-3 py-2">{{ fmt(u.created_at) }}</td>
                    <td class="px-3 py-2">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs"
                            :class="u.is_blocked ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                        >
                            {{ u.is_blocked ? 'Igen' : 'Nem' }}
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <button
                            class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
                            :disabled="togglingId === u.id"
                            @click="toggle(u)"
                        >
                            {{ u.is_blocked ? 'Engedélyezés' : 'Tiltás' }}
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta" class="flex items-center gap-2">
            <button
                class="px-2 py-1 border rounded disabled:opacity-50"
                :disabled="!meta?.links?.prev"
                @click="fetchUsers(meta.current_page - 1)"
            >
                ◀
            </button>
            <span>Oldal {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button
                class="px-2 py-1 border rounded disabled:opacity-50"
                :disabled="!meta?.links?.next"
                @click="fetchUsers(meta.current_page + 1)"
            >
                ▶
            </button>
        </div>

        <p v-if="error" class="text-red-600">{{ error }}</p>
        <p v-if="success" class="text-green-700">{{ success }}</p>
    </div>

</template>