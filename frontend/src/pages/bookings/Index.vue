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

const success = ref(null);
const actionLoadingId = ref(null);

/**
 * Megadja, hogy a foglalás lemondható-e.
 * Egy foglalás lemondható, ha nincs lemondva, és a hozz  tartozó esemény
 * id  beli id pontja nagyobb, mint a jelenlegi id pont.
 * @param {Object} b - a foglalás adatai
 * @returns {boolean} - `true`, ha a foglalás lemondható, egyébként `false`
 */
const canCancel = (b) => {
    if (!b || b.status === 'cancelled') return false;
    // jövőbeli esemény?
    const dt = b.event?.starts_at ? new Date(b.event.starts_at) : null;
    return dt ? dt.getTime() > Date.now() : true;
};

/**
 * Foglalás lemondása a szerveren.
 * Sikeres lemondás esetén a listát újra betöltjük az aktuális oldalon.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 * @param {Object} b - a foglalás adatai
 */
const cancelBooking = async(b) => {
    if (!b) return;
    if (!canCancel(b)) return;
    if (!confirm(`Biztosan lemondod a #${b.id} foglalást (${b.event?.title ?? ''})?`)) return;

    // A lemondás eredményéről a `success`-en keresztül kapunk visszajelzést.
    // A hibaüzenetet a `error` változóban tároljuk.
    success.value = null;
    error.value = null;
    actionLoadingId.value = b.id;

    try {
        // A lemondás végrehajtása a szerveren.
        const updated = await BookingsService.cancel(b.id);

        // A sor adatait a kapott válasszal frissítsük.
        const idx = rows.value.findIndex(r => r.id === b.id);

        if (idx !== -1) rows.value[idx] = { ...rows.value[idx], ...updated }

        // Sikeres lemondás esetén a sikeresség üzenetét megjelenítjük.
        success.value = `Foglalás lemondva (#${b.id}).`;
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
        error.value = e?.response?.data?.message || 'Lemondás sikertelen.';
    } finally {
        // A lemondás folyamatának a befejeztét jelentjük.
        actionLoadingId.value = null;
    }
}

const formatDate = (iso) => {
    if (!iso) return '-';
    try {
        return new Intl.DateTimeFormat('hu-HU', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        }).format(new Date(iso));
    } catch {
        return iso;
    }
};

/**
 * Betölti a saját foglalásaimat a szerverről a megadott filterekkel.
 * A szerverről kapott adatokkal beállítja a `rows` és `meta` változókat.
 * Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-re.
 */
const fetchData = async() => {
    loading.value = true;
    error.value = null;
    try {
        // A saját foglalásaim betöltése a szerverről a megadott filterekkel
        const res = await BookingsService.listMine({
            status: status.value || '', // a foglalások státusza
            page: page.value, // a betöltendő oldal száma
            perPage: perPage.value, // a listázandó elemek száma oldalanként
            field: 'created_at', // a rendezés mezője
            order: 'desc', // a rendezés iránya
        });

        // A szerverről kapott adatokkal beállítjuk a `rows` változót
        rows.value = res.data || [];
        // A szerverről kapott adatokkal beállítjuk a `meta` változót
        meta.value = res.meta || null;
    } catch (e) {
        // Ha hiba történik, akkor a hibaüzenetet beállítjuk a `error` változóban
        error.value = e?.response?.data?.message || 'Nem sikerült betölteni a foglalásokat.';
    } finally {
        // A betöltési folyamat végén a `loading` változót nullázni kell
        loading.value = false;
    }
};

/**
 * Beállítja a szervernek küldendő oldalszámot a megadott értékre.
 * Ha a megadott érték 1-nél kisebb, vagy a meta.lastPage-nél nagyobb, akkor nem csinál semmit.
 * @param {number} p Az oldalszám, amire át szeretnénk váltani.
 */
const toPage = (p) => {
    // Ha nincs meta, akkor nem csinálunk semmit.
    if (!meta.value) return;

    // Ha a p értéke 1-nél kisebb, vagy a meta.lastPage-nél nagyobb, akkor nem csinálunk semmit.
    if (p < 1 || p > meta.value.last_page) return;

    // A page változót a p értékére állítjuk be.
    page.value = p;
};

/**
 * Figyeli a szürési beállításokat (status, perPage) és
 * a változások esetén a listát újra betölti az első oldalon.
 */
watch([status, perPage], () => {
    // A lapozó beállításait nullázzuk,
    // hogy a listát újra az első oldalon töltsük be.
    page.value = 1;
    // A listát újra betöltjük a szerverről.
    fetchData();
});

onMounted(fetchData);
</script>

<template>
    <main class="container" style="max-width:1100px; padding:1rem 0;">
        <h1 style="margin:0 0 .75rem;">Saját foglalásaim</h1>

        <!-- Szűrősáv -->
        <section class="card-eh" style="padding:.75rem; margin-bottom:.75rem;">
            <div class="toolbar-eh">
                <span class="label-eh">Státusz</span>
                <select v-model="status" class="select-eh" style="width:160px;">
                    <option value="">(mind)</option>
                    <option value="pending">Függőben</option>
                    <option value="confirmed">Megerősített</option>
                    <option value="cancelled">Törölt</option>
                </select>

                <span class="label-eh" style="margin-left:.5rem;">Sor/oldal</span>
                <select v-model.number="perPage" class="select-eh" style="width:120px;">
                    <option :value="10">10</option>
                    <option :value="20">20</option>
                    <option :value="50">50</option>
                </select>

                <button class="btn-eh is-primary" @click="fetchData">Frissítés</button>
            </div>
        </section>

        <!-- Üzenetek -->
        <div v-if="loading" class="card-eh">Betöltés…</div>
        <p v-if="success" class="alert-eh is-success">{{ success }}</p>
        <p v-if="error"   class="alert-eh is-error">{{ error }}</p>

        <!-- Táblázat -->
        <section v-else class="card-eh" style="padding:0; overflow:auto;">
            <table class="table-eh is-compact">
                <thead>
                <tr>
                    <th>Azonosító</th>
                    <th>Esemény</th>
                    <th>Időpont</th>
                    <th>Helyszín</th>
                    <th>Db</th>
                    <th>Státusz</th>
                    <th>Foglalva</th>
                    <th style="width:1%"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="rows.length === 0">
                    <td colspan="8" style="text-align:center; padding:16px; opacity:.7;">Nincs foglalás.</td>
                </tr>

                <tr v-for="b in rows" :key="b.id">
                    <td>#{{ b.id }}</td>
                    <td>{{ b.event?.title ?? '—' }}</td>
                    <td>{{ formatDate(b.event?.starts_at) }}</td>
                    <td>{{ b.event?.location ?? '—' }}</td>
                    <td>{{ b.quantity }}</td>
                    <td>
                    <span class="badge-eh"
                        :class="{
                        'is-yellow':   b.status === 'pending',
                        'is-green':    b.status === 'confirmed',
                        'is-gray':     b.status === 'cancelled'
                        }"
                    >{{ b.status }}</span>
                    </td>
                    <td>{{ formatDate(b.created_at) }}</td>
                    <td>
                    <button
                        class="btn-eh is-danger"
                        :aria-busy="actionLoadingId === b.id"
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
        </section>

        <!-- Lapozó -->
        <div v-if="meta && meta.last_page > 1" class="pager-eh" style="margin-top:.75rem;">
            <button class="btn-eh" :disabled="page <= 1" @click="toPage(page - 1)">Előző</button>
            <span>{{ meta.from }}–{{ meta.to }} / {{ meta.total }} (oldal: {{ meta.current_page }}/{{ meta.last_page }})</span>
            <button class="btn-eh" :disabled="page >= meta.last_page" @click="toPage(page + 1)">Következő</button>
        </div>
    </main>
</template>