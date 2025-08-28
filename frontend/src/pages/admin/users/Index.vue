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

/**
 * Formáz egy dátumot ember által olvasható formára.
 * @param {string|number|Date} dt dátum (ISO 8601 string, timestamp, vagy Date-objektum)
 * @returns {string} Formázott dátum, vagy üres string, ha a dátum nincs megadva
 */
const fmt = (dt) => {
    if (!dt) return '';

    const d = new Date(dt);

    return d.toLocaleString('hu-HU', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

/**
 * Betölti a felhasználókat a szerverről a megadott filterekkel.
 * A szerverről kapott adatokkal beállítja a `rows` és `meta` változókat.
 * Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-re.
 * @param {number} [page=1] - a betöltendő oldal száma
 */
const fetchUsers = async(page = 1) => {
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
};

/**
 * Tiltja vagy engedélyezi a felhasználót a szerveren.
 * A felhasználónak meg kell erősítenie a tiltást/engedélyezést.
 * Sikeres tiltás/engedélyezés esetén a sikeresség üzenetét megjelenítjük.
 * Hiba esetén a hibaüzenetet megjelenítjük.
 * @param {Object} u - a felhasználó adatai
 */
const toggle = async(u) => {
    error.value = '';
    success.value = '';
    const next = !u.is_blocked;
    togglingId.value = u.id;
    const old = u.is_blocked;

    u.is_blocked = next;

    try {
        // A tilts/engedélyezés végrehajtása a szerveren
        await AdminUsersService.setBlocked(u.id, next);

        // Sikeres tiltás/engedélyezés esetén a sikeresség üzenetét megjelenítjük
        success.value = next ? `Felhasználó tiltva (#${u.id}).` : `Felhasználó engedélyezve (#${u.id}).`;
    } catch (e) {
        // Hiba esetén a hibaüzenetet megjelenítjük
        u.is_blocked = old; // visszaállít
        error.value = e?.response?.data?.message || 'A módosítás nem sikerült.';
    } finally {
        togglingId.value = null;
    }
};

onMounted(() => fetchUsers(1));
</script>

<template>
    <section class="container" style="padding: 1rem 0; max-width: 1100px;">
        <header style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem;">
            <h1 style="margin:0;">Felhasználók</h1>
        </header>

        <!-- Szűrő sáv -->
        <div class="card-eh toolbar-eh" style="padding: .75rem;">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Keresés név vagy e-mail szerint…"
                class="input-eh"
                style="width: 220px;"
            />
            <select v-model.number="filters.perPage" class="select-eh" style="width:80px;">
                <option :value="10">10</option>
                <option :value="12">12</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
            <select v-model="filters.field" class="select-eh" style="width:130px;">
                <option value="name">Név</option>
                <option value="email">Email</option>
                <option value="created_at">Regisztrált</option>
                <option value="is_blocked">Tiltva?</option>
            </select>
            <select v-model="filters.order" class="select-eh" style="width:110px;">
                <option value="asc">Növekvő</option>
                <option value="desc">Csökkenő</option>
            </select>
            <button @click="fetchUsers(1)" class="btn-eh is-primary">Szűrés</button>
        </div>

        <!-- Táblázat -->
        <article class="card-eh" style="padding:0; overflow:auto;">
            <table class="table-eh">
                <thead>
                <tr>
                    <th class="ta-right">ID</th>
                    <th>Név</th>
                    <th>Email</th>
                    <th>Regisztrált</th>
                    <th>Tiltva?</th>
                    <th style="width:1%"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="loading">
                    <td colspan="6" style="text-align:center; padding: 16px; opacity:.7;">Betöltés…</td>
                </tr>
                <tr v-else-if="rows.length === 0">
                    <td colspan="6" style="text-align:center; padding: 16px; opacity:.7;">Nincs találat.</td>
                </tr>
                <tr v-for="u in rows" :key="u.id">
                    <td class="ta-right">{{ u.id }}</td>
                    <td>{{ u.name }}</td>
                    <td>{{ u.email }}</td>
                    <td>{{ fmt(u.created_at) }}</td>
                    <td>
                        <span class="badge-eh" :class="u.is_blocked ? 'is-red' : 'is-green'">
                            {{ u.is_blocked ? 'Igen' : 'Nem' }}
                        </span>
                    </td>
                    <td>
                        <button
                            class="btn-eh"
                            :aria-busy="togglingId === u.id"
                            :disabled="togglingId === u.id"
                            @click="toggle(u)"
                        >
                            {{ u.is_blocked ? 'Engedélyezés' : 'Tiltás' }}
                        </button>
                    </td>
                </tr>
                </tbody>
        </table>
        </article>

        <!-- Lapozó -->
        <div v-if="meta" class="pager-eh" style="margin-top: .75rem;">
            <button
                class="btn-eh"
                :disabled="!meta?.links?.prev"
                @click="fetchUsers(meta.current_page - 1)"
            >◀</button>
            <span>Oldal {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button
                class="btn-eh"
                :disabled="!meta?.links?.next"
                @click="fetchUsers(meta.current_page + 1)"
            >▶</button>
        </div>

        <p v-if="error" style="color:#b00020;">{{ error }}</p>
        <p v-if="success" style="color:#0a7a2f;">{{ success }}</p>
    
    </section>
</template>
