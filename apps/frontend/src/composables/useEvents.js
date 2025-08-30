import { ref, reactive, watch, onMounted } from "vue";
import EventService from "../services/EventService.js";

export function useEvents(initial = {})
{
    const loading = ref(false);
    const error = ref(null);
    const items = ref([]);
    const meta = reactive({ currentPage: 1, perPage: 12, total: 0, lastPage: 1 });

    const filters = reactive({
        search: "",
        location: "",
        category: "",
        page: 1,
        perPage: 12,
        ...initial
    });

    /**
     * Lekéri az események listáját az aktuális szűrők alapján.
     * Kezeli a hibákat és a betöltési állapotot.
     * @returns {Promise<void>}
     */
    async function fetchEvents() {
        loading.value = true;
        error.value = null;
        try {
            const data = await EventsService.list(filters);
            items.value = data.data ?? data.items ?? [];
            meta.currentPage = data.meta?.currentPage ?? data.meta?.current_page ?? 1;
            meta.perPage = data.meta?.perPage ?? data.meta?.per_page ?? filters.perPage;
            meta.total = data.meta?.total ?? 0;
            meta.lastPage = data.meta?.lastPage ?? data.meta?.last_page ?? 1;
        } catch (err) {
            error.value = err;
        } finally {
            loading.value = false;
        }
    }

    watch(() => ({ ...filters }), fetchEvents, { deep: true });

    /**
     * Lekéri az aktuális oldalt az események listájában.
     * A filters.page értékét változtatja.
     * @param {number} p - Az oldal száma (1-től indul).
     */
    function goToPage(p) {
        if (p < 1 || p > meta.lastPage) return;
        filters.page = p;
    }

    return { loading, error, items, meta, filters, fetchEvents, goToPage };
}