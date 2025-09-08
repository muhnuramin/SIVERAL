<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{ programs?: Array<Record<string, any>> }>();
const notify = useNotify();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '#' },
    { title: 'Program', href: route('program') },
];

const programs = computed(() => props.programs ?? []);

const Modal = ref({
    ModalCreate: false,
    ModalUpdate: false,
    ModalDelete: false,
});
const selected = ref<Record<string, any> | null>(null);
// Single form state used for both create (multi-row) and update (single row)
const formState = reactive({
    // when creating: use programs array
    programs: [{ kode: '', nama: '' }],
    // when updating: use id/kode/nama
    id: null as number | null,
    kode: '',
    nama: '',
});
const rowErrors = ref<Array<Record<string, string> | null>>([]);
const deleteForm = useForm({ _method: 'DELETE' });

function openCreate() {
    // reset to one empty row
    formState.programs = [{ kode: '', nama: '' }];
    rowErrors.value = [];
    Modal.value.ModalCreate = true;
}

function addProgramRow() {
    formState.programs.push({ kode: '', nama: '' });
}

function removeProgramRow(idx: number) {
    if (formState.programs.length <= 1) return;
    formState.programs.splice(idx, 1);
    rowErrors.value.splice(idx, 1);
}

function submitCreate() {
    rowErrors.value = [];

    const f = useForm({ programs: formState.programs });
    f.post(route('program.store'), {
        preserveScroll: true,
        onError: (errs: Record<string, any>) => {
            // Laravel will return keys like 'programs.0.kode' -> map to rowErrors[0].kode
            const map: Array<Record<string, string>> = [];
            for (const k in errs) {
                const m = k.match(/^programs\.(\d+)\.(.+)$/);
                if (m) {
                    const idx = Number(m[1]);
                    const field = m[2];
                    map[idx] = map[idx] || {};
                    map[idx][field] = errs[k][0] ?? String(errs[k]);
                }
            }
            rowErrors.value = map;
            // notify error
            notify.error('Gagal membuat program');
        },
        onSuccess: () => {
            Modal.value.ModalCreate = false;
            formState.programs = [{ kode: '', nama: '' }];
            rowErrors.value = [];
            // notify success
            notify.success('Program berhasil dibuat');
        },
    });
}

function openUpdate(item: Record<string, any>) {
    selected.value = item;
    // populate shared formState for editing
    formState.id = item.id ?? null;
    formState.kode = item.kode ?? '';
    formState.nama = item.nama ?? '';
    Modal.value.ModalUpdate = true;
}

function submitUpdate() {
    if (!formState.id) return;
    const f = useForm({ id: formState.id, kode: formState.kode, nama: formState.nama });
    f.put(route('program.update', formState.id), {
        onSuccess: () => {
            Modal.value.ModalUpdate = false;
            notify.success('Program berhasil diperbarui');
        },
        onError: () => {
            notify.error('Gagal memperbarui program');
        },
    });
}

function openDelete(item: Record<string, any>) {
    selected.value = item;
    Modal.value.ModalDelete = true;
}

function confirmDelete() {
    if (!selected.value) return;
    deleteForm.post(route('program.destroy', selected.value.id), {
        onSuccess: () => {
            Modal.value.ModalDelete = false;
            selected.value = null;
            deleteForm.reset();
            notify.success('Program berhasil dihapus');
        },
        onError: () => {
            notify.error('Gagal menghapus program');
        },
    });
}
</script>

<template>
    <Head title="Program" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-4 space-y-6 rounded-lg bg-gray-50 p-6 shadow dark:bg-slate-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Master Program</h1>
                <div class="flex items-center gap-2">
                    <button
                        @click="openCreate"
                        class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1 text-sm text-white shadow transition hover:bg-emerald-700"
                    >
                        <i class="fa fa-plus"></i>
                        Tambah Program
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-slate-800">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Kode</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700 dark:text-slate-200">Nama</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700 dark:text-slate-200">Dibuat</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <template v-for="p in programs" :key="p.id">
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-700">
                                <td class="px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{{ p.kode }}</td>
                                <td class="px-4 py-3 text-center text-sm text-slate-700 dark:text-slate-200">{{ p.nama }}</td>
                                <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ p.created_at ? new Date(p.created_at).toLocaleString('id-ID') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button
                                            @click.prevent="openUpdate(p)"
                                            class="flex items-center gap-1 rounded bg-slate-100 px-3 py-1 text-slate-700 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                                        >
                                            <i class="fa fa-edit"></i>
                                            Edit
                                        </button>
                                        <button
                                            @click.prevent="openDelete(p)"
                                            class="flex items-center gap-1 rounded bg-red-600 px-3 py-1 text-white transition hover:bg-red-700"
                                        >
                                            <i class="fa fa-trash"></i>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="programs.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-base text-slate-500 dark:text-slate-400">Belum ada program.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Create (compact, scrollable) -->
        <div v-if="Modal.ModalCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-2xl rounded bg-white shadow-lg dark:bg-slate-800">
                <div class="border-b px-4 py-3 dark:border-slate-700">
                    <h3 class="text-base font-semibold">Tambah Program (multi-row)</h3>
                </div>

                <div class="max-h-[60vh] space-y-2 overflow-y-auto p-4">
                    <template v-for="(p, idx) in formState.programs" :key="idx">
                        <div class="grid grid-cols-12 items-center gap-2 py-1">
                            <div class="col-span-1 text-sm text-slate-600">#{{ idx + 1 }}</div>
                            <div class="col-span-3">
                                <input v-model="formState.programs[idx].kode" class="w-full rounded border px-2 py-1 text-sm" placeholder="Kode" />
                                <p v-if="rowErrors[idx]?.kode" class="mt-1 text-xs text-red-500">{{ rowErrors[idx].kode }}</p>
                            </div>
                            <div class="col-span-7">
                                <input
                                    v-model="formState.programs[idx].nama"
                                    class="w-full rounded border px-2 py-1 text-sm"
                                    placeholder="Nama program"
                                />
                                <p v-if="rowErrors[idx]?.nama" class="mt-1 text-xs text-red-500">{{ rowErrors[idx].nama }}</p>
                            </div>
                            <div class="col-span-1 flex items-center justify-end">
                                <button
                                    @click.prevent="removeProgramRow(idx)"
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200"
                                    v-if="formState.programs.length > 1"
                                    title="Hapus baris"
                                >
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="pt-2">
                        <button @click.prevent="addProgramRow" class="rounded bg-slate-100 px-3 py-1 text-sm">+ Tambah Baris</button>
                    </div>
                </div>

                <div class="sticky bottom-0 flex justify-end gap-2 border-t bg-white/90 px-4 py-3 backdrop-blur dark:bg-slate-800/90">
                    <button @click="Modal.ModalCreate = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitCreate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Update -->
        <div v-if="Modal.ModalUpdate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Update Program</h3>
                <div class="space-y-3">
                    <label class="block">
                        <span class="text-sm font-medium">Kode</span>
                        <input v-model="formState.kode" class="w-full rounded border px-3 py-2" placeholder="Kode" />
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Nama</span>
                        <input v-model="formState.nama" class="w-full rounded border px-3 py-2" placeholder="Nama program" />
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="Modal.ModalUpdate = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitUpdate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Delete -->
        <div v-if="Modal.ModalDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Hapus Program</h3>
                <p>
                    Apakah Anda yakin ingin menghapus program <strong>{{ selected?.nama }}</strong
                    >?
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="Modal.ModalDelete = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="confirmDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
