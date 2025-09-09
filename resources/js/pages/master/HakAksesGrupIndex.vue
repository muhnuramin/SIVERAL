<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ groups: Array<{ id: number; nama: string; hak_akses?: string[] }> }>();

const form = useForm({ nama: '', hak_akses: [] as string[] });
const hakAksesInput = ref('');
const editId = ref<number | null>(null);

function openEdit(g: any) {
    editId.value = g.id;
    form.nama = g.nama;
    form.hak_akses = g.hak_akses ?? [];
}

function resetForm() {
    editId.value = null;
    form.reset();
}

function submit() {
    // parse comma separated input into array
    form.hak_akses = hakAksesInput.value
        ? hakAksesInput.value
              .split(',')
              .map((s) => s.trim())
              .filter(Boolean)
        : form.hak_akses || [];

    if (editId.value) {
        form.put(route('hakaksesgrup.update', editId.value), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('hakaksesgrup.store'), { onSuccess: () => resetForm() });
    }
}

function remove(id: number) {
    if (!confirm('Hapus grup?')) return;
    form.delete(route('hakaksesgrup.destroy', id), { onSuccess: () => {} });
}
</script>

<template>
    <Head title="Grup Akses" />
    <AppLayout>
        <div class="m-4">
            <h1 class="mb-4 text-xl font-semibold">Grup Hak Akses</h1>
            <div class="mb-4 w-full max-w-lg">
                <label class="mb-2 block">Nama</label>
                <input v-model="form.nama" class="w-full rounded border px-3 py-2" />
                <label class="mt-2 mb-1 block">Hak Akses (pisahkan dengan koma di input lalu tekan +)</label>
                <input v-model="hakAksesInput" class="w-full rounded border px-3 py-2" placeholder="contoh: user.create,user.delete" />
                <div class="mt-2 flex gap-2">
                    <button @click="submit" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                    <button @click="resetForm" class="rounded border px-4 py-2">Batal</button>
                </div>
            </div>

            <div class="rounded bg-white p-4 shadow-sm">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Nama</th>
                            <th class="text-left">Hak Akses</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="g in props.groups" :key="g.id">
                            <td>{{ g.nama }}</td>
                            <td>{{ (g.hak_akses || []).join(', ') }}</td>
                            <td class="text-right">
                                <button @click="openEdit(g)" class="mr-2 rounded bg-slate-100 px-2 py-1">Edit</button>
                                <button @click="remove(g.id)" class="rounded bg-red-600 px-2 py-1 text-white">Hapus</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
