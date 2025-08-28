<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.js';
import OrganizerEventsService from '../../../services/OrganizerEventsService.js';
import EventForm from '@/components/events/EventForm.vue';

const auth = useAuthStore();
const router = useRouter();

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

onMounted(async () => {
    // opcionális: ha refresh után nincs betöltve a user, betöltjük
    if (!auth.user && auth.isAuthenticated) {
        try {
            await auth.fetchMe();
        } catch (e) {}
    }

    const roles = auth.user?.roles || [];
    const isOrgOrAdmin = roles.includes('organizer') || roles.includes('admin');
    if (!isOrgOrAdmin) {
        router.replace({ name: 'events.index' });
        return;
    }

    //ready.value = true;
    // ha van listatöltésed: await fetchRows(filters.page)
    await fetchRows(filters.page);   // első lista-betöltés
});

/**
 * Figyeli a szürési beállításokat (search, status, field, order, perPage) és
 * a változások esetén a listát újra betölti az első oldalon.
 */
watch(
    () => [filters.search, filters.status, filters.field, filters.order, filters.perPage], 
    () => fetchRows(1)
);

// create / edit modál állapotok
const showCreate = ref(false);
const showEdit = ref(false);
const createModel = ref({});
const editModel = ref({});
const submitting = ref(false);

/**
 * A create és edit modálok nyitott állapotát figyeli, és
 * a dokumentum és a body elemekre egy CSS osztályt ad hozzá,
 * amely tiltja a scroll-t, amíg a modál nyitva van.
 */
watch([showCreate, showEdit], ([c, e]) => {
    const anyOpen = c || e
    // dokumentum scroll tiltása, amíg a modál nyitva van
    document.documentElement.classList.toggle('html-no-scroll', anyOpen)
    document.body.classList.toggle('body-no-scroll', anyOpen)
})

/**
 * Új esemény felvitelének megnyitása.
 * Beállítja a createModel-t üres értékekkel, és megnyitja a create modalt.
 */
function openCreate() {
    createModel.value = {
        title: '', description: '', starts_at: '', location: '',
        capacity: 1, category: '', status: 'draft',
    };
    showCreate.value = true;
};

/**
 * Esemény szerkesztésének megnyitása.
 * A szerkesztend  esemény adatait a modellbe másolja, és a szerkeszt  modalt megnyitja.
 * @param {Object} row - az esemény adatai
 */
const openEdit = (row) => {
    editModel.value = { ...row };
    showEdit.value = true;
};

/**
 * Új esemény mentése a szerverre.
 * Sikeres mentés esetén a modál bezárul, és a listát újra betöltjük.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 */
const submitCreate = async () => {
    submitting.value = true;

    try {
        // A szerveren létrehozzuk az új eseményt a megadott adatokkal.
        // Sikeres mentés esetén a modál bezárul, és a listát újra betöltjük.
        await OrganizerEventsService.create(createModel.value);
        // Modál bezárása
        showCreate.value = false;
        // A lista újratöltése
        await fetchRows(filters.page);
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
        alert(e?.response?.data?.message || 'Mentési hiba.');
    } finally {
        // A mentés folyamatának lezárása
        submitting.value = false;
    }
};

/**
 * Módosított esemény mentése a szerverre.
 * Sikeres mentés esetén a modál bezárul, és a listát újra betöltjük.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 */
const submitEdit = async() => {

    submitting.value = true;

    try {
        // Módosított esemény mentése a szerverre.
        // Sikeres mentés esetén a modál bezárul, és a listát újra betöltjük.
        await OrganizerEventsService.update(editModel.value.id, editModel.value);
        showEdit.value = false;
        await fetchRows(filters.page);
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
        alert(e?.response?.data?.message || 'Mentési hiba.');
    } finally {
        // A mentési folyamat végén a submitting állapotot nullázni kell.
        submitting.value = false;
    }
}

/**
 * Esemény publikálása a szerveren.
 * Sikeres publikálás esetén a listát újra betöltjük.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 * @param {Object} row - az esemény adatai
 */
const publish = async (row) => {
    // A felhasználónak meg kell erősítenie a publikálást.
    if (!confirm(`Biztosan publikálod? "${row.title}"`)) return;

    try {
        // A publikálás végrehajtása a szerveren.
        // Sikeres publikálás esetén a listát újra betöltjük.
        await OrganizerEventsService.publish(row.id); 
        await fetchRows(filters.page);
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
        alert(e?.response?.data?.message || 'Publikálási hiba.');
    }
}

/**
 * Esemény lemondása a szerveren.
 * Sikeres lemondás esetén a listát újra betöltjük.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 * @param {Object} row - az esemény adatai
 */
const cancelEvent = async (row) => {
    // Lemondási folyamat indítása a szerveren.
    // Első lépésként a felhasználónak meg kell erősítenie a lemondást.
    // Ha nem erősíti meg, akkor a folyamat itt megáll.
    if (!confirm(`Biztosan lemondod? "${row.title}"`)) {
        return;
    }

    try {
        // A lemondás a szerveren elindul.
        // Sikeres lemondás esetén a listát újra betöltjük az aktuális oldalon.
        await OrganizerEventsService.cancel(row.id); 
        await fetchRows(filters.page);
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
        alert(e?.response?.data?.message || 'Lemondási hiba.');
    }
}

/**
 * Esemény törlése a szerveren.
 * Sikeres törlés esetén a listát újra betöltjük az aktuális oldalon, ha van még esemény,
 * ellenkező esetben az előző oldalra ugrik.
 * Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük.
 * @param {Object} row - az esemény adatai
 */
const remove = async (row) => {
    // A törlés megerősítése a felhasználóval
    // Ha nem erősíti meg a törlést, akkor a függvény nem csinál semmit
    if (!confirm(`Biztosan törlöd? "${row.title}"`)) {
        return;
    }

    try {
        // Törlés a szerveren
        await OrganizerEventsService.destroy(row.id);

        // Ha van még esemény a listában, akkor a listát újra betöltjük az aktuális oldalon
        // Ellenkező esetben az előző oldalra ugrik
        await fetchRows(rows.value.length > 1 ? filters.page : Math.max(1, filters.page - 1)); 
    } catch (e) {
        // Hiba esetén a hibaüzenetet a felhasználó számára megjelenítjük
        alert(e?.response?.data?.message || 'Törlési hiba.');
    }
}
</script>

<template>
    <main class="container" style="max-width:1100px; padding:1rem 0;">
        <header style="display:flex; align-items:center; gap:.75rem;">
            <h1 style="margin:0;">Saját események</h1>
            <button class="btn-eh is-primary" style="margin-left:auto;" @click="openCreate">+ Új esemény</button>
        </header>

        <!-- Szűrősáv -->
        <section class="card-eh" style="padding:.75rem; margin-top:.75rem;">
            <div class="toolbar-eh wrap">
                <input v-model="filters.search" placeholder="Keresés cím / leírás…" class="input-eh w-320" />
                <select v-model="filters.status" class="select-eh w-160">
                    <option value="">— összes státusz —</option>
                    <option value="draft">vázlat</option>
                    <option value="published">közzétéve</option>
                    <option value="cancelled">lemondva</option>
                </select>
                <select v-model="filters.field" class="select-eh w-160">
                    <option value="starts_at">kezdés</option>
                    <option value="title">cím</option>
                    <option value="location">helyszín</option>
                    <option value="category">kategória</option>
                    <option value="status">státusz</option>
                </select>
                <select v-model="filters.order" class="select-eh w-120">
                    <option value="desc">csökkenő</option>
                    <option value="asc">növekvő</option>
                </select>
                <select v-model="filters.perPage" class="select-eh w-120">
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
                <button class="btn-eh is-primary" @click="fetchRows(1)">Frissítés</button>
            </div>
        </section>

        <!-- Üzenetek -->
        <p v-if="error" class="alert-eh is-error">{{ error }}</p>
        <div v-if="loading" class="card-eh">Betöltés…</div>

        <!-- Táblázat -->
        <section v-else class="card-eh" style="padding:0; overflow:auto;">
            <table class="table-eh is-compact">
                <thead>
                <tr>
                    <th>Cím</th>
                    <th>Kezdés</th>
                    <th>Helyszín</th>
                    <th>Kategória</th>
                    <th class="ta-right">Kapacitás</th>
                    <th>Státusz</th>
                    <th style="text-align:right;">Műveletek</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.title }}</td>
                    <td>{{ new Date(row.starts_at).toLocaleString('hu-HU') }}</td>
                    <td>{{ row.location }}</td>
                    <td>{{ row.category || '—' }}</td>
                    <td class="ta-right">{{ row.capacity }}</td>
                    <td>
                        <span class="badge-eh"
                            :class="{
                            'is-yellow':  row.status==='draft',
                            'is-green':   row.status==='published',
                            'is-red':     row.status==='cancelled'
                            }"
                        >{{ row.status }}</span>
                    </td>
                    <td style="text-align:right;">
                        <button class="btn-eh is-secondary" @click="openEdit(row)">Szerk.</button>
                        <button class="btn-eh is-primary" @click="publish(row)" :disabled="row.status==='published'">Publikál</button>
                        <button class="btn-eh is-danger" @click="cancelEvent(row)" :disabled="row.status==='cancelled'">Lemond</button>
                        <button class="btn-eh is-danger" @click="remove(row)">Törlés</button>
                    </td>
                </tr>

                <tr v-if="!loading && rows.length === 0">
                    <td colspan="7" style="text-align:center; padding:16px; opacity:.7;">Nincs találat.</td>
                </tr>
                </tbody>
            </table>
        </section>

        <!-- Lapozó -->
        <div v-if="meta" class="pager-eh" style="margin-top:.75rem;">
            <button class="btn-eh" :disabled="!meta.prev_url" @click="fetchRows(filters.page - 1)">Előző</button>
            <span>{{ meta.current }} / {{ meta.last }}</span>
            <button class="btn-eh" :disabled="!meta.next_url" @click="fetchRows(filters.page + 1)">Következő</button>
            <span style="margin-left:auto; font-size:.9rem; opacity:.7;">Összesen: {{ meta.total }}</span>
        </div>

        <!-- Create modal -->
        <teleport to="body">
            <div v-if="showCreate" class="modal-overlay-eh" @click.self="showCreate = false">
                <div class="modal-card-eh" role="dialog" aria-modal="true" aria-labelledby="createTitle">
                    <h2 id="createTitle" style="margin:0 0 .5rem; font-size:1.15rem;">Új esemény</h2>
                    <EventForm
                        v-model="createModel"
                        :loading="submitting"
                        submit-text="Létrehozás"
                        @submit="submitCreate"
                        @cancel="showCreate=false"
                    />
                </div>
            </div>
        </teleport>

        <!-- Edit modal -->
        <teleport to="body">
            <div v-if="showEdit" class="modal-overlay-eh" @click.self="showEdit = false">
                <div class="modal-card-eh" role="dialog" aria-modal="true" aria-labelledby="editTitle">
                    <h2 id="editTitle" style="margin:0 0 .5rem; font-size:1.15rem;">Esemény szerkesztése</h2>
                    <EventForm
                        v-model="editModel"
                        :loading="submitting"
                        submit-text="Mentés"
                        @submit="submitEdit"
                        @cancel="showEdit=false"
                    />
                </div>
            </div>
        </teleport>
    </main>
</template>

