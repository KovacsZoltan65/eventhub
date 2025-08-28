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


/**
* Figyeljük a modelValue propot, és szinkronizáljuk az űrlap állapotát, amikor az megváltozik.
*
* Az Object.assign függvényt használjuk a propokból származó értékeknek az űrlap állapotába való egyesítésére.
* Így biztosítjuk, hogy az űrlap állapota mindig naprakész legyen a propokkal.
*/
watch(() => props.modelValue, (v) => {
    Object.assign(form, {
        title: v?.title ?? '',
        description: v?.description ?? '',
        starts_at: v?.starts_at ? v.starts_at.slice(0, 16) : '',
        location: v?.location ?? '',
        capacity: v?.capacity ?? 0,
        category: v?.category ?? '',
        status: v?.status ?? 'draft',
    });
}, { immediate: true });

watch(
    form, 
    () => emit('update:modelValue', { ...form }), 
    { deep: true }
);

const canSubmit = computed(() =>
    form.title?.trim().length &&
    form.starts_at?.length &&
    form.location?.trim().length &&
    Number(form.capacity) > 0
)

/**
 * A formot elküldi, ha a validáció sikeres.
 */
const onSubmit = () => {
    if (canSubmit.value) {
        emit('submit');
    }
};
</script>

<template>
    <form 
        @submit.prevent="onSubmit" 
        class="space-y-3"
    >
        <!-- felső rács: 2 oszlopon tördel, kis képernyőn egymás alatt -->
        <div class="form-grid-eh">

            <div class="field-eh">
                <label class="label-eh" for="ev-title">Cím</label>
                <input id="ev-title" v-model="form.title" class="input-eh" required :aria-invalid="!form.title?.trim().length" />
            </div>

            <div class="field-eh">
                <label class="label-eh" for="ev-location">Helyszín</label>
                <input id="ev-location" v-model="form.location" class="input-eh" required :aria-invalid="!form.location?.trim().length" />
            </div>

            <!-- KEZDÉS -->
            <div class="field-eh">
                <label class="label-eh" for="ev-starts">Kezdés</label>
                <!-- datetime-local, ISO „YYYY-MM-DDTHH:mm” -->
                <input id="ev-starts" v-model="form.starts_at" type="datetime-local" class="input-eh" required :aria-invalid="!form.starts_at?.length" />
                <small class="help-eh">Formátum: ÉÉÉÉ-HH-NN ÓÓ:PP</small>
            </div>

            <!-- KAPACITÁS -->
            <div class="field-eh">
                <label class="label-eh" for="ev-capacity">Kapacitás</label>
                <input 
                    id="ev-capacity" 
                    v-model.number="form.capacity" 
                    type="number" min="1" step="1" required 
                    class="input-eh" 
                    :aria-invalid="!(Number(form.capacity) > 0)" 
                />
            </div>

            <!-- KATEGÓRIA -->
            <div class="field-eh">
                <label class="label-eh" for="ev-category">Kategória (opcionális)</label>
                <input id="ev-category" v-model="form.category" class="input-eh" />
            </div>

            <!-- STATUSZ -->
            <div class="field-eh">
                <label class="label-eh" for="ev-status">Státusz</label>
                <select id="ev-status" v-model="form.status" class="select-eh">
                    <option value="draft">vázlat</option>
                    <option value="published">közzétéve</option>
                    <option value="cancelled">lemondva</option>
                </select>
            </div>

        </div>

        <div class="field-eh">
            <label class="label-eh" for="ev-desc">Leírás</label>
            <textarea id="ev-desc" v-model="form.description" class="textarea-eh" rows="4"></textarea>
        </div>

        <div class="form-actions-eh">
            <button type="button" class="btn-eh is-secondary" @click="emit('cancel')" :disabled="loading">Mégse</button>
            <button type="submit" class="btn-eh is-primary" :disabled="loading || !canSubmit">
                {{ loading ? 'Mentés…' : submitText }}
            </button>
        </div>
    </form>
</template>
