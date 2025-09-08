<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded bg-white p-6 shadow-lg">
            <h3 class="mb-2 text-lg font-semibold">Pilih Tahun Anggaran</h3>
            <p class="mb-4 text-sm text-slate-500">Pilih tahun anggaran yang akan digunakan. Ini wajib dipilih sebelum melanjutkan.</p>
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium">Tahun</label>
                <select v-model="selected" class="w-full rounded border px-3 py-2">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="onLogout" class="rounded border px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Logout</button>
                <button @click="onConfirm" class="rounded bg-emerald-600 px-4 py-2 text-white">Pilih dan Lanjutkan</button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const emit = defineEmits<{
    (e: 'select', year: number): void;
}>();

const now = new Date();
const currentYear = now.getFullYear();
const years = computed(() => {
    const arr: number[] = [];
    for (let i = currentYear; i >= currentYear - 10; i--) arr.push(i);
    return arr;
});

const selected = ref(currentYear);

function onConfirm() {
    emit('select', Number(selected.value));
}

async function onLogout() {
    localStorage.clear();
    await router.post(route('logout'));
}
</script>
