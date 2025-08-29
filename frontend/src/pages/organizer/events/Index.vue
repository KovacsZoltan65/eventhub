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

/**
 * Betölti az eseményeket a szerverről a megadott filterekkel.
 * A szerverről kapott adatokkal beállítja a `rows` és `meta` változókat.
 * Ha hiba történik, akkor a hibaüzenetet beállítja a `error`-re.
 * @param {number} [page=1] - a betöltendő oldal száma
 */
const fetchRows = async(page = 1) => {
    loading.value = true; error.value = null;
    try {
        // A szerverről kapott adatokkal beállítja a `rows` változót
        // A `meta` változóban a lapozásra vonatkozó információkat tároljuk
        const res = await OrganizerEventsService.list({ ...filters, page });

        /*
        const res = await OrganizerEventsService.list({
            search: filters.search,
            status: filters.status,
            field: filters.field,
            order: filters.order,
            perPage: filters.perPage,
            page
        });
        */

        rows.value = res.data;
        meta.value = {
            // A jelenlegi oldal száma
            current: res.current_page,
            // Az utolsó oldal száma
            last: res.last_page,
            // A korábbi oldal URL-je
            prev_url: res.prev_page_url,
            // A következő oldal URL-je
            next_url: res.next_page_url,
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
};

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

const onEditModelChange = (payload) => {
  // merge: a meglévő editModel + a form által küldött mezők
  editModel.value = { ...editModel.value, ...payload };
};

const onCreateModelChange = (payload) => {
  createModel.value = { ...createModel.value, ...payload };
};

// create / edit modál állapotok
const showCreate = ref(false);
const showEdit = ref(false);
const createModel = ref({});
const editModel = ref({});
const submitting = ref(false);

/**
 * Figyeli a szürési beállításokat (search, status, field, order, perPage) és
 * a változások esetén a listát újra betölti az első oldalon.
 */
watch(
    () => [filters.search, filters.status, filters.field, filters.order, filters.perPage], 
    () => fetchRows(1)
);

/**
 * Figyeli a szerkeszt  modal állapotát, és ha az bezárul, a szerkesztett
 * esemény adatait törli.
 */
watch(showEdit, (v) => { 
    if (!v) {
        editModel.value = {};
    }
});

/**
 * Figyeli az új esemény modal állapotát, és ha az bezárul, az új esemény
 * adatait törli.
 */
watch(showCreate, (v) => { 
    if (!v) {
        createModel.value = {};
    }
});

/**
 * Új esemény felvitelének megnyitása.
 * Beállítja a createModel-t üres értékekkel, és megnyitja a create modalt.
 */
const openCreate = () => {
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
    editModel.value = JSON.parse(JSON.stringify(row)); // deep clone
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
const submitEdit = async () => {
    if (!editModel.value?.id) {
        alert('Hiányzik az esemény azonosítója (id).');
        return;
    }
    submitting.value = true;
    try {
        await OrganizerEventsService.update(editModel.value.id, editModel.value);
        showEdit.value = false;
        await fetchRows(filters.page);
    } catch (e) {
        alert(e?.response?.data?.message || 'Mentési hiba.');
    } finally {
        submitting.value = false;
    }
};

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

const isCancelled = (s) => s === 'cancelled' || s === 'canceled';
const canPublish  = (row) => row.status === 'draft';          // csak draft-ból
const canCancel   = (row) => !isCancelled(row.status);         // már cancelled-et ne

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
                        <!-- SZERKESZTÉS -->
                        <button 
                            class="btn-eh is-secondary" 
                            @click="openEdit(row)"
                        >Szerk.</button>

                        <!-- PUBLIKÁLÁS -->
                        <button 
                            class="btn-eh is-primary" 
                            @click="publish(row)" 
                            :disabled="!canPublish(row)"
                        >Publikál</button>

                        <!-- LEMONDÁS -->
                        <button 
                            class="btn-eh is-danger" 
                            @click="cancelEvent(row)" 
                            :disabled="!canCancel(row)"
                        >Lemond</button>

                        <!-- TÖRLÉS -->
                        <button 
                            class="btn-eh is-danger" 
                            @click="remove(row)"
                        >Törlés</button>
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
                        :model-value="createModel"
                        :key="showCreate ? 'create' : 'create-hidden'"
                        :loading="submitting"
                        submit-text="Létrehozás"
                        @update:modelValue="onCreateModelChange"
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
                        :model-value="editModel"
                        :key="showEdit ? (editModel?.id ?? 'edit') : 'edit-hidden'"
                        :loading="submitting"
                        submit-text="Mentés"
                        @update:modelValue="onEditModelChange"
                        @submit="submitEdit"
                        @cancel="showEdit=false"
                    />
                </div>
            </div>
        </teleport>
    </main>
</template>

