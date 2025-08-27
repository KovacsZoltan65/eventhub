<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import EventsService from '../../services/EventService.js'

const router = useRouter()

const loading = ref(false)
const error = ref(null)
const rows = ref([])
const meta = reactive({ currentPage: 1, perPage: 12, lastPage: 1, total: 0 })
const filters = reactive({
    page: 1, perPage: 12, status: 'published',
    search: '', location: '', category: '',
    field: 'starts_at', order: 'asc', // <-- rendezés alap
})

function sortBy(field) {
    if (filters.field === field) {
        filters.order = filters.order === 'asc' ? 'desc' : 'asc'
    } else {
        filters.field = field
        filters.order = 'asc'
    }
}

function sortIndicator(field) {
    return filters.field !== field ? '' : (filters.order === 'asc' ? ' ▲' : ' ▼')
}

async function fetchEvents() {
    loading.value = true
    error.value = null
    try {
        const data = await EventsService.list(filters)
        rows.value = data?.data ?? data?.items ?? []
        meta.currentPage = Number(data?.meta?.current_page ?? data?.meta?.currentPage ?? filters.page)
        meta.perPage    = Number(data?.meta?.per_page ?? data?.meta?.perPage ?? filters.perPage)
        meta.total      = Number(data?.meta?.total ?? 0)
        meta.lastPage   = Number(data?.meta?.last_page ?? data?.meta?.lastPage ?? 1)
    } catch (e) {
        error.value = 'Nem sikerült betölteni az eseményeket.'
    } finally {
        loading.value = false
    }
}

onMounted(fetchEvents)
watch(() => ({ ...filters }), fetchEvents, { deep: true })

function toPage(p) { if (p<1 || p>meta.lastPage) return; filters.page = p }
function fmt(dt) { return dt ? new Date(dt).toLocaleString() : '—' }
</script>

<template>
    <section class="container mx-auto p-4">
        <h1 class="text-2xl font-semibold mb-4">Események</h1>

        <!-- (szűrőid maradhatnak a korábbról) -->

        <div v-if="loading">Betöltés…</div>
        <div v-else-if="error" class="text-red-600">{{ error }}</div>

        <div v-else class="mt-4 overflow-x-auto">
            <table v-if="rows.length" class="min-w-full border rounded">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-2 border-b cursor-pointer" @click="sortBy('starts_at')">
                        Kezdés{{ sortIndicator('starts_at') }}
                    </th>
                    <th class="text-left p-2 border-b cursor-pointer" @click="sortBy('title')">
                        Cím{{ sortIndicator('title') }}
                    </th>
                    <th class="text-left p-2 border-b cursor-pointer" @click="sortBy('location')">
                        Helyszín{{ sortIndicator('location') }}
                    </th>
                    <th class="text-left p-2 border-b">Kategória</th>
                    <th class="text-right p-2 border-b">Kapacitás</th>
                    <th class="text-right p-2 border-b">Szabad</th>
                    <th class="text-left p-2 border-b">Státusz</th>
                    <th class="text-left p-2 border-b">Művelet</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="ev in rows" :key="ev.id" class="hover:bg-gray-50">
                    <td class="p-2 border-b whitespace-nowrap">{{ fmt(ev.starts_at) }}</td>
                    <td class="p-2 border-b">
                        <a class="text-blue-700 hover:underline" @click.prevent="router.push(`/events/${ev.id}`)">
                            {{ ev.title }}
                        </a>
                    </td>
                    <td class="p-2 border-b">{{ ev.location }}</td>
                    <td class="p-2 border-b">{{ ev.category || '—' }}</td>
                    <td class="p-2 border-b text-right">{{ ev.capacity ?? '—' }}</td>
                    <td class="p-2 border-b text-right">{{ ev.remaining_seats ?? '—' }}</td>
                    <td class="p-2 border-b">
                        <span class="px-2 py-0.5 border rounded text-xs uppercase">{{ ev.status }}</span>
                    </td>
                    <td class="p-2 border-b">
                        <button @click.prevent="router.push(`/events/${ev.id}`)">részletek</button>
                    </td>
                </tr>
            </tbody>
            </table>

            <div v-else class="opacity-70">Nincs elérhető esemény.</div>
        </div>

        <div v-if="meta.lastPage > 1" class="flex items-center gap-2 justify-center mt-6">
            <button class="px-3 py-1 border rounded" :disabled="filters.page<=1" @click="toPage(filters.page - 1)">Előző</button>
            <span class="text-sm">Oldal {{ meta.currentPage }} / {{ meta.lastPage }}</span>
            <button class="px-3 py-1 border rounded" :disabled="filters.page>=meta.lastPage" @click="toPage(filters.page + 1)">Következő</button>
        </div>
        
    </section>
</template>
