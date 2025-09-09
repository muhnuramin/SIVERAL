<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const notify = useNotify();

const props = defineProps<{ rekenings?: any; subsubs?: Array<{ id: number; nama: string; code?: string }> }>();

const form = useForm({ id: null as number | null, koderekening: '', namarekening: '', id_sub_sub_kegiatan: null, stAktif: true });
const editMode = ref(false);
const showModal = ref(false);
const filterSub = ref<number | null>(null);
const showDeleteModal = ref(false);
const deleteTargetId = ref<number | null>(null);

import { computed } from 'vue';
const searchTerm = ref('');
const filteredList = computed(() => {
    const all = props.rekenings || [];
    let items = all.data ? all.data : all;
    if (filterSub.value) items = items.filter((r: any) => r.id_sub_sub_kegiatan === filterSub.value);
    if (searchTerm.value && searchTerm.value.trim()) {
        const q = searchTerm.value.trim().toLowerCase();
        items = items.filter(
            (r: any) => (r.koderekening || '').toString().toLowerCase().includes(q) || (r.namarekening || '').toLowerCase().includes(q),
        );
    }
    return items;
});

// group by sub_sub_kegiatan id to render rowspan
const groupedBySub = computed(() => {
    const list = filteredList.value || [];
    const groups: Record<number | string, any> = {};
    for (const r of list) {
        const key = r.id_sub_sub_kegiatan ?? 'null';
        if (!groups[key]) groups[key] = { sub: r.sub_sub_kegiatan || null, items: [] };
        groups[key].items.push(r);
    }
    return Object.values(groups);
});

function openCreateModal() {
    editMode.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function startEdit(r: any) {
    editMode.value = true;
    form.id = r.id;
    form.koderekening = r.koderekening;
    form.namarekening = r.namarekening;
    form.id_sub_sub_kegiatan = r.id_sub_sub_kegiatan;
    form.clearErrors();
    showModal.value = true;
}

function cancelEdit() {
    editMode.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = false;
}

function submit() {
    if (editMode.value && form.id) {
        form.put(route('rekening.update', form.id), {
            onSuccess: () => {
                notify.success('Rekening diperbarui');
                cancelEdit();
            },
            onError: () => {
                notify.error('Periksa input dan coba lagi');
                // keep modal open so user can fix validation errors
            },
        });
    } else {
        form.post(route('rekening.store'), {
            onSuccess: () => {
                notify.success('Rekening dibuat');
                form.reset();
                form.clearErrors();
                showModal.value = false;
            },
            onError: () => {
                notify.error('Periksa input dan coba lagi');
            },
        });
    }
}

function remove(id: number) {
    deleteTargetId.value = id;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!deleteTargetId.value) return;
    form.delete(route('rekening.destroy', deleteTargetId.value), {
        onSuccess: () => {
            notify.success('Rekening dihapus');
            showDeleteModal.value = false;
            deleteTargetId.value = null;
        },
        onError: () => {
            notify.error('Gagal menghapus rekening');
        },
    });
}
</script>

<template>
    <Head title="Rekening" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Master', href: '#' },
            { title: 'Sub Kegiatan', href: route('subkegiatan.index') },
        ]"
    >
        <div class="p-6">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-center">
                <div class="flex items-start gap-3 md:col-span-5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 shadow-sm">R</div>
                    <div>
                        <h1 class="text-2xl font-bold">Master Rekening</h1>
                        <div class="text-sm text-slate-500">Kelola daftar rekening yang terikat ke sub-sub kegiatan</div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 md:col-span-5 md:flex-row md:items-center">
                    <input v-model="searchTerm" placeholder="Cari kode atau nama..." class="w-full rounded border px-3 py-2 text-sm md:flex-1" />
                    <select v-model="filterSub" class="w-full rounded border px-3 py-2 text-sm md:w-auto">
                        <option :value="null">-- Semua Sub-Sub Kegiatan --</option>
                        <option v-for="s in props.subsubs || []" :key="s.id" :value="s.id">{{ s.nama }}</option>
                    </select>
                    <div class="mt-2 text-sm text-slate-600 md:hidden">
                        Terlihat: <strong>{{ filteredList.length }}</strong>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 md:col-span-2">
                    <div class="hidden items-center rounded bg-slate-100 px-2 py-1 text-sm text-slate-700 md:inline-flex">
                        Terlihat:
                        <span class="ml-2 inline-block rounded-full bg-emerald-600 px-2 py-0.5 text-xs text-white">{{ filteredList.length }}</span>
                    </div>
                    <button
                        @click.prevent="openCreateModal"
                        class="rounded bg-gradient-to-r from-emerald-500 to-emerald-700 px-3 py-1 text-white shadow md:px-4 md:py-2"
                    >
                        Buat Rekening
                    </button>
                </div>
            </div>

            <div class="">
                <div class="overflow-auto rounded bg-white shadow-sm lg:col-span-2">
                    <div class="border-b p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-slate-700">Daftar Rekening</div>
                            <div class="text-xs text-slate-500">
                                Total: <strong>{{ filteredList.length }}</strong>
                            </div>
                        </div>
                    </div>
                    <table class="min-w-full table-auto divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Kode</th>
                                <th class="px-4 py-2 text-left">Nama</th>
                                <th class="px-4 py-2 text-left">Sub-Sub Kegiatan</th>
                                <th class="px-4 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="groupedBySub.length">
                                <template v-for="(g, gi) in groupedBySub" :key="gi">
                                    <tr v-for="(r, ri) in g.items" :key="r.id" class="group transition-colors hover:bg-slate-50">
                                        <td class="px-4 py-3 align-top">
                                            <div>
                                                <span
                                                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700"
                                                    >{{ r.koderekening }}</span
                                                >
                                                <div class="mt-1 text-xs text-slate-400">#{{ r.id }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-semibold text-slate-800">{{ r.namarekening }}</div>
                                            <div class="mt-2 flex items-center gap-2">
                                                <span v-if="r.stAktif" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800"
                                                    >Aktif</span
                                                >
                                                <span v-else class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-800">Nonaktif</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top" v-if="ri === 0" :rowspan="g.items.length">
                                            <div class="rounded-lg bg-gradient-to-r from-emerald-50 to-emerald-100 p-3">
                                                <div class="text-xs font-medium text-emerald-800">Sub-Sub Kegiatan</div>
                                                <div class="font-semibold text-slate-800">{{ g.sub?.nama || '-' }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ g.sub?.code || '' }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center align-top">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    @click.prevent="startEdit(r)"
                                                    class="inline-flex items-center gap-2 rounded border border-sky-200 bg-white px-3 py-1 text-sm text-sky-700 shadow-sm transition-transform hover:scale-105"
                                                    title="Edit"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 20h9" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path
                                                            d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        ></path>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button
                                                    @click.prevent="remove(r.id)"
                                                    class="inline-flex items-center gap-2 rounded border border-red-200 bg-white px-3 py-1 text-sm text-red-700 shadow-sm transition-transform hover:scale-105"
                                                    title="Hapus"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                        <path d="M10 11v6"></path>
                                                        <path d="M14 11v6"></path>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                    <div class="py-8">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                                        >
                                            💼
                                        </div>
                                        <div class="text-lg font-semibold">Belum ada rekening</div>
                                        <div class="mt-2 text-sm text-slate-500">Tambahkan rekening pertama Anda dengan tombol "Buat Rekening".</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- no pagination: show optional filter note -->
                    <div class="border-t bg-white p-3 text-sm text-slate-500">
                        Menampilkan semua rekening (gunakan filter Sub-Sub Kegiatan untuk mempersempit).
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal for create/edit -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="relative w-full max-w-lg rounded bg-white p-6">
            <div class="-mt-6 mb-4 h-2 rounded-t bg-gradient-to-r from-emerald-500 to-emerald-700"></div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-semibold">{{ editMode ? 'Edit Rekening' : 'Buat Rekening' }}</h3>
                <button
                    @click.prevent="
                        showModal = false;
                        form.reset();
                        form.clearErrors();
                    "
                    class="rounded bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
                >
                    ✕
                </button>
            </div>
            <div class="space-y-3">
                <label class="block"
                    ><span class="text-sm">Kode</span>
                    <input v-model="form.koderekening" class="mt-1 w-full rounded border px-3 py-2" />
                    <div v-if="form.errors.koderekening" class="mt-1 text-sm text-rose-600">{{ form.errors.koderekening }}</div>
                </label>
                <label class="block"
                    ><span class="text-sm">Nama</span>
                    <input v-model="form.namarekening" class="mt-1 w-full rounded border px-3 py-2" />
                    <div v-if="form.errors.namarekening" class="mt-1 text-sm text-rose-600">{{ form.errors.namarekening }}</div>
                </label>
                <label class="block"
                    ><span class="text-sm">Sub-Sub Kegiatan</span>
                    <select v-model="form.id_sub_sub_kegiatan" class="mt-1 w-full rounded border px-3 py-2">
                        <option :value="null">-- Pilih Sub-Sub Kegiatan --</option>
                        <option v-for="s in props.subsubs || []" :key="s.id" :value="s.id">{{ s.nama }}</option>
                    </select>
                    <div v-if="form.errors.id_sub_sub_kegiatan" class="mt-1 text-sm text-rose-600">{{ form.errors.id_sub_sub_kegiatan }}</div>
                </label>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click.prevent="cancelEdit" class="rounded border px-4 py-2">Batal</button>
                <button @click.prevent="submit" class="rounded bg-emerald-600 px-4 py-2 text-white">{{ editMode ? 'Simpan' : 'Buat' }}</button>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold">Konfirmasi Hapus</h3>
            <p class="text-sm text-slate-600">Anda yakin ingin menghapus rekening ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button
                    @click.prevent="
                        showDeleteModal = false;
                        deleteTargetId = null;
                    "
                    class="rounded border px-4 py-2"
                >
                    Batal
                </button>
                <button @click.prevent="confirmDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
            </div>
        </div>
    </div>
</template>
