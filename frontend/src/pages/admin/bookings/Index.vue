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

const nfHUF = new Intl.NumberFormat('hu-HU', { style: 'currency', currency: 'HUF', maximumFractionDigits: 0 });
const nfNum = new Intl.NumberFormat('hu-HU');


/**
 * Formáz egy számot pénznemben. Ha a szám null, akkor '—' jelenik meg.
 * @param {number|null} v - a szám, amit formázni kell
 * @returns {string} a formázott szám
 */
function formatMoney(v) {
    if (v == null) return '—';

    // Ha a szerver HUF-ot közöl, akkor a HUF formázást használhatjuk:
    try {
        // A HUF formázás egy ilyen példát ad vissza: "1 200 000 Ft"
        return nfHUF.format(v); 
    } catch {
        // Ha a szerver nem HUF-ot közöl, akkor a szám formázását használjuk:
        // A szám formázás egy ilyen példát ad vissza: "1 200 000"
        return nfNum.format(v);
    }
}


/**
 * Alaphelyzetbe állítja a szürési beállításokat.
 * A lapozó beállításokat (page, perPage, field, order) is alaphelyzetbe állítja.
 * A lista újratöltését is elindítja.
 */
function resetFilters() {
    // Szürési beállítások alaphelyzete
    filters.user_id = ''
    filters.event_id = ''
    filters.status = ''
    filters.date_from = ''
    filters.date_to   = ''

    // Lapozó beállítások alaphelyzete
    filters.field = 'created_at'
    filters.order = 'desc'
    filters.perPage = 12

    // A lista újratöltése az első oldalon
    fetchRows(1)
}


const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref(null);

/**
 * Betölti a foglalásokat a szerverről a megadott filterekkel.
 * A szerverről kapott adatokkal beállítja a `rows` és `meta` változókat.
 * Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-re.
 * A függvény általánosan használható a lapozóhoz is.
 * @param {number} [page=1] - a betöltendő oldal száma
 */
const fetchRows = async (page = 1) => {
    loading.value = true; error.value = null;
    try {
        // A szerverről kapott adatokkal beállítja a `rows` változót
        // A `list` függvény a szerverről kapott adatokat adja vissza
        const res = await AdminBookingsService.list({ ...filters, page });

        // A szerverről kapott adatokkal beállítja a `meta` változót
        // A `meta` változóban a lapozásra vonatkozó információkat tároljuk
        rows.value = res.data;
        meta.value = {
            // A jelenlegi oldal száma
            current: res.current_page,
            // Az utolsó oldal száma
            last: res.last_page,
            // A korábbi oldal URL-je
            prev: res.prev_page_url,
            // A következő oldal URL-je
            next: res.next_page_url,
            // A foglalások száma
            total: res.total,
        };

        // A `filters.page` változónak a jelenlegi oldal számát állítjuk be
        filters.page = meta.value.current;
    } catch (e) {
        // Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-re
        error.value = e?.response?.data?.message || 'Betöltési hiba.';
    } finally {
        // A betöltési folyamat végén a `loading` változót nullázni kell
        loading.value = false;
    }
}
onMounted(() => fetchRows());

watch(
    () => [filters.user_id, filters.event_id, filters.status, filters.date_from, filters.date_to, filters.field, filters.order, filters.perPage],
    () => fetchRows(1)
);

/**
 * Foglalás lemondása a szerveren.
 * A felhasználónak meg kell erősítenie a lemondást.
 * Sikeres lemondás esetén a listát újra betöltjük az aktuális oldalon.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 * @param {Object} row - a foglalás adatai
 */
const cancelRow = async (row) => {
    // A lemondás megerősítése a felhasználóval
    if (!confirm(`Biztosan lemondod a foglalást #${row.id}?`)) return;

    try {
        // A lemondás végrehajtása a szerveren
        // Sikeres lemondás esetén a listát újra betöltjük az aktuális oldalon
        await AdminBookingsService.cancel(row.id); 
        await fetchRows(filters.page);
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük
        alert(e?.response?.data?.message || 'Lemondási hiba.');
    }
}
</script>

<template>
    <main class="container" style="max-width:1100px; padding:1rem 0;">
        <h1 style="margin:0 0 .75rem;">Foglalások (Admin)</h1>

        <!-- Szűrősáv -->
        <section class="card-eh" style="padding:.75rem; margin-bottom:.75rem;">
            <div class="toolbar-eh wrap" style="gap:.5rem;">
                
                <!-- User Id -->
                <input 
                    v-model="filters.user_id"
                    type="number" min="1" 
                    placeholder="User ID"
                    class="input-eh"
                    style="width:120px;"
                />

                <!-- Event Id -->
                <input 
                    v-model="filters.event_id" 
                    type="number" min="1" 
                    placeholder="Event ID" 
                    class="input-eh" 
                    style="width:120px;"
                />

                <!-- Státusz -->
                <select v-model="filters.status" class="select-eh" style="width:160px;">
                    <option value="">— összes státusz —</option>
                    <option value="pending">pending</option>
                    <option value="confirmed">confirmed</option>
                    <option value="cancelled">cancelled</option>
                </select>

                <!-- Dátumok -->
                <input 
                    v-model="filters.date_from" 
                    type="date" class="input-eh" 
                    style="width:160px;"
                />

                <input 
                    v-model="filters.date_to"
                    type="date" class="input-eh" 
                    style="width:160px;"
                />

                <!-- Szűrés -->
                <select v-model="filters.field" class="select-eh" style="width:140px;">
                    <option value="created_at">létrehozva</option>
                    <option value="quantity">mennyiség</option>
                    <option value="total_price">összeg</option>
                </select>

                <!-- Irány -->
                <select v-model="filters.order" class="select-eh" style="width:120px;">
                    <option value="desc">desc</option>
                    <option value="asc">asc</option>
                </select>

                <!-- Sor/oldal -->
                <select v-model="filters.perPage" class="select-eh" style="width:110px;">
                    <option :value="12">12</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>

                <!-- Frissítés -->
                <button
                    class="btn-eh is-primary" 
                    @click="fetchRows(1)"
                >Szűrés</button>
                
                <!-- Szürés törlése -->
                <button 
                    class="btn-eh is-sprimary" 
                    @click="resetFilters" 
                    title="Szűrők törlése"
                >Törlés</button>

            </div>
        </section>

        <!-- Üzenetek -->
        <p v-if="error"   class="alert-eh is-error">{{ error }}</p>
        <div v-if="loading" class="card-eh">Betöltés…</div>

        <!-- Táblázat -->
        <section v-else class="card-eh" style="padding:0; overflow:auto;">
            <table class="table-eh is-compact">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Esemény</th>
                    <th>User</th>
                    <th>Menny.</th>
                    <th>Összeg</th>
                    <th>Státusz</th>
                    <th style="text-align:right;">Művelet</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="b in rows" :key="b.id">
                    <td>#{{ b.id }}</td>
                    <td>
                        <div style="font-weight:600">{{ b.event?.title || b.event_title }}</div>
                        <div style="font-size:.85rem; opacity:.75">{{ b.event?.location || b.event_location }}</div>
                        <div style="font-size:.85rem; opacity:.75">{{ new Date(b.created_at).toLocaleString('hu-HU') }}</div>
                    </td>
                    <td>
                        <div>{{ b.user?.name || b.user_name }}</div>
                        <div style="font-size:.85rem; opacity:.75">{{ b.user?.email || b.user_email }}</div>
                    </td>
                    <td class="ta-right">{{ b.quantity }}</td>
                    <td class="ta-right">{{ formatMoney(b.total_price) }}</td>
                    <td>
                        <span class="badge-eh"
                            :class="{
                            'is-green':    b.status==='confirmed',
                            'is-yellow':   b.status==='pending',
                            'is-gray':     b.status==='cancelled'
                            }"
                        >{{ b.status }}</span>
                    </td>
                    <td style="text-align:right;">
                    <button
                        class="btn-eh is-danger"
                        :disabled="b.status==='cancelled'"
                        @click="cancelRow(b)"
                        title="Foglalás lemondása"
                    >
                        Lemond
                    </button>
                    </td>
                </tr>

                <tr v-if="!loading && rows.length===0">
                    <td 
                        colspan="7" 
                        style="text-align:center; padding:16px; opacity:.7;"
                    >Nincs találat.</td>
                </tr>
                </tbody>
            </table>
        </section>

        <!-- Lapozó -->
        <div v-if="meta" class="pager-eh" style="margin-top:.75rem;">
            <button 
                class="btn-eh" 
                :disabled="!meta.prev" 
                @click="fetchRows(filters.page - 1)"
            >Előző</button>

            <span>{{ meta.current }} / {{ meta.last }}</span>
            
            <button 
                class="btn-eh" 
                :disabled="!meta.next" 
                @click="fetchRows(filters.page + 1)"
            >Következő</button>

            <span style="margin-left:auto; font-size:.9rem; opacity:.7;">Összesen: {{ meta.total }}</span>
        </div>
    </main>
</template>

