<script setup>
import { reactive, watch, computed } from 'vue'

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    submitText: { type: String, default: 'Mentés' },
})

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const form = reactive({
    title: '',
    description: '',
    starts_at: '',
    location: '',
    capacity: 0,
    category: '',
    status: 'draft', // draft | published | cancelled
})

// init + v-model bridge
watch(() => props.modelValue, (v) => {
    Object.assign(form, {
        title: v?.title ?? '',
        description: v?.description ?? '',
        starts_at: v?.starts_at ? v.starts_at.slice(0,16) : '',
        location: v?.location ?? '',
        capacity: v?.capacity ?? 0,
        category: v?.category ?? '',
        status: v?.status ?? 'draft',
    })
}, { immediate: true })

watch(form, () => emit('update:modelValue', { ...form }), { deep: true })

const canSubmit = computed(() =>
    form.title?.trim().length &&
    form.starts_at?.length &&
    form.location?.trim().length &&
    Number(form.capacity) > 0
)

function onSubmit() { if (canSubmit.value) emit('submit') }
</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-3">
        <div class="grid md:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium">Cím</label>
            <input v-model="form.title" class="border rounded p-2 w-full" required>
        </div>
        <div>
            <label class="block text-sm font-medium">Helyszín</label>
            <input v-model="form.location" class="border rounded p-2 w-full" required>
        </div>
        <div>
            <label class="block text-sm font-medium">Kezdés</label>
            <!-- datetime-local, ISO „YYYY-MM-DDTHH:mm” -->
            <input v-model="form.starts_at" type="datetime-local" class="border rounded p-2 w-full" required>
        </div>
        <div>
            <label class="block text-sm font-medium">Kapacitás</label>
            <input v-model.number="form.capacity" type="number" min="1" class="border rounded p-2 w-full" required>
        </div>
        <div>
            <label class="block text-sm font-medium">Kategória (opcionális)</label>
            <input v-model="form.category" class="border rounded p-2 w-full">
        </div>
        <div>
            <label class="block text-sm font-medium">Státusz</label>
            <select v-model="form.status" class="border rounded p-2 w-full">
            <option value="draft">vázlat</option>
            <option value="published">közzétéve</option>
            <option value="cancelled">lemondva</option>
            </select>
        </div>
        </div>

        <div>
            <label class="block text-sm font-medium">Leírás</label>
            <textarea v-model="form.description" rows="4" class="border rounded p-2 w-full"></textarea>
        </div>

        <div class="flex gap-2 justify-end">
            <button type="button" class="px-3 py-2 border rounded" @click="emit('cancel')" :disabled="loading">Mégse</button>
            <button type="submit" class="px-3 py-2 border rounded bg-black text-white disabled:opacity-50"
                    :disabled="loading || !canSubmit">
                {{ loading ? 'Mentés…' : submitText }}
            </button>
        </div>
    </form>
</template>
