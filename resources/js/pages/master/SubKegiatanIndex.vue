<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

const props = defineProps<{ sub_kegiatans?: Array<Record<string, any>>; kegiatan?: Record<string, any> }>();
const Modal = ref({
    ModalSubKegiatanCreate: false,
    ModalSubKegiatanEdit: false,
    ModalSubKegiatanDelete: false,
});
// sub-kegiatan form (single object for edit)
const subKForm: any = useForm({ id: null as number | null, kegiatan_id: null as number | null, kode: '', nama: '' });
const subKDeleteForm = useForm({ _method: 'DELETE' });
// create supports multiple rows
const createForm = reactive({ sub_kegiatans: [{ kode: '', nama: '' }] });
const createRowErrors = ref<Array<Record<string, string> | null>>([]);
const subs = ref<Array<Record<string, any>>>(props.sub_kegiatans ?? []);
const loading = ref(false);
const kegiatanId = (props.kegiatan?.id ?? Number(new URLSearchParams(window.location.search).get('kegiatan_id'))) || null;
const kegiatan = ref<Record<string, any> | null>(props.kegiatan ?? null);
const selectedKegiatan = ref<Record<string, any> | null>(null);
const notify = useNotify();

function openSubSubPage(s: Record<string, any>) {
    const id = s?.id;
    if (!id) return;
    const InertiaRuntime = (window as any).Inertia;
    if (InertiaRuntime && typeof InertiaRuntime.get === 'function') {
        InertiaRuntime.get(route('subsubkegiatan.index'), { sub_kegiatan_id: id });
    } else {
        window.location.href = route('subsubkegiatan.index') + '?sub_kegiatan_id=' + id;
    }
}

async function load() {
    if (!kegiatanId) return;
    loading.value = true;
    try {
        const res = await fetch(route('subkegiatan.get', kegiatanId));
        if (!res.ok) return;
        const data = await res.json();
        subs.value = data.sub_kegiatans || [];
        if (data.kegiatan && data.kegiatan.nama) kegiatan.value = data.kegiatan;
    } catch (e) {
        console.error('load subs error', e);
    } finally {
        loading.value = false;
    }
}

function openSubCreate() {
    subKForm.reset();
    subKForm.kegiatan_id = kegiatanId;
    createForm.sub_kegiatans = [{ kode: '', nama: '' }];
    createRowErrors.value = [];
    Modal.value.ModalSubKegiatanCreate = true;
}

function openSubEdit(s: Record<string, any>) {
    subKForm.reset();
    subKForm.id = s.id ?? null;
    subKForm.kegiatan_id = s.kegiatan_id ?? null;
    subKForm.kode = s.kode ?? '';
    subKForm.nama = s.nama ?? '';
    Modal.value.ModalSubKegiatanEdit = true;
}

function openSubDelete(s: Record<string, any>) {
    selectedKegiatan.value = null;
    selectedKegiatan.value = { id: s.kegiatan_id } as any;
    selectedKegiatan.value = null;
    selectedKegiatan.value = null;
    selectedKegiatan.value = { id: s.id, nama: s.nama } as any;
    Modal.value.ModalSubKegiatanDelete = true;
}

async function submitSubCreate() {
    const payload = { kegiatan_id: kegiatanId, sub_kegiatans: createForm.sub_kegiatans };
    const f = useForm(payload);
    createRowErrors.value = [];
    f.post(route('subkegiatan.store'), {
        onError: (errs: Record<string, any>) => {
            const map: Array<Record<string, string>> = [];
            for (const k in errs) {
                const m = k.match(/^sub_kegiatans\.(\d+)\.(.+)$/);
                if (m) {
                    const idx = Number(m[1]);
                    const field = m[2];
                    map[idx] = map[idx] || {};
                    map[idx][field] = errs[k][0] ?? String(errs[k]);
                }
            }
            createRowErrors.value = map;
            notify.error('Periksa kembali input anda.');
        },
        onSuccess: async () => {
            await load();
            Modal.value.ModalSubKegiatanCreate = false;
            createForm.sub_kegiatans = [{ kode: '', nama: '' }];
            createRowErrors.value = [];
            notify.success('Sub kegiatan berhasil ditambahkan.');
        },
    });
}

async function submitSubEdit() {
    if (!subKForm.id) return;
    subKForm.put(route('subkegiatan.update', subKForm.id), {
        onSuccess: async () => {
            await load();
            Modal.value.ModalSubKegiatanEdit = false;
            subKForm.reset();
            notify.success('Sub kegiatan berhasil diperbarui.');
        },
        onError: () => {
            notify.error('Gagal memperbarui sub kegiatan. Periksa input.');
        },
    });
}

async function confirmSubDelete() {
    if (!selectedKegiatan.value) return;
    subKDeleteForm.post(route('subkegiatan.destroy', selectedKegiatan.value.id), {
        onSuccess: async () => {
            await load();
            Modal.value.ModalSubKegiatanDelete = false;
            selectedKegiatan.value = null;
            notify.success('Sub kegiatan berhasil dihapus.');
        },
    });
}

onMounted(async () => {
    if (!props.sub_kegiatans) {
        await load();
    }
});

// goBack removed: using direct :href on the template for consistent behavior across pages
</script>
<template>
    <Head title="Sub Kegiatan" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Master', href: '#' },
            { title: 'Sub Kegiatan', href: route('subkegiatan.index') },
        ]"
    >
        <div class="m-4 space-y-6 rounded-lg bg-gray-50 p-6 shadow dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Sub Kegiatan</h1>
                    <div class="text-sm text-slate-500">
                        Kegiatan: <span class="font-semibold">{{ kegiatan?.nama ?? (kegiatanId ? 'ID ' + kegiatanId : '-') }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click.prevent="openSubCreate()" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">Tambah Sub Kegiatan</button>
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-white p-4 shadow sm:p-6">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Kode</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Nama</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700 dark:text-slate-200">Dibuat</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr v-if="loading">
                            <td colspan="5" class="px-4 py-8 text-center">Memuat...</td>
                        </tr>
                        <template v-for="s in subs" :key="s.id">
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ s.kode }}</td>
                                <td class="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{{ s.nama }}</td>
                                <td class="px-4 py-3 text-center text-sm text-slate-500">
                                    {{ s.created_at ? new Date(s.created_at).toLocaleDateString('id-ID') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click.prevent="openSubSubPage(s)"
                                            class="flex items-center gap-1 rounded bg-indigo-600 px-3 py-1 text-white"
                                        >
                                            <i class="fas fa-sitemap"></i>
                                            Sub-Sub
                                        </button>
                                        <button
                                            @click.prevent="openSubEdit(s)"
                                            class="flex items-center gap-1 rounded bg-slate-100 px-3 py-1 text-slate-700"
                                        >
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </button>
                                        <button
                                            @click.prevent="openSubDelete(s)"
                                            class="flex items-center gap-1 rounded bg-red-600 px-3 py-1 text-white"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!loading && subs.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada sub kegiatan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Modal Sub Kegiatan Create (multi-row compact) -->
        <div v-if="Modal.ModalSubKegiatanCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-2xl rounded bg-white shadow-lg dark:bg-slate-800">
                <div class="border-b px-4 py-3 dark:border-slate-700">
                    <h3 class="text-base font-semibold">Tambah Sub Kegiatan (multi-row)</h3>
                </div>

                <div class="max-h-[60vh] space-y-2 overflow-y-auto p-4">
                    <template v-for="(r, idx) in createForm.sub_kegiatans" :key="idx">
                        <div class="grid grid-cols-12 items-center gap-2 py-1">
                            <div class="col-span-1 text-sm text-slate-600">#{{ idx + 1 }}</div>
                            <div class="col-span-3">
                                <input
                                    v-model="createForm.sub_kegiatans[idx].kode"
                                    class="w-full rounded border px-2 py-1 text-sm"
                                    placeholder="Kode"
                                />
                                <p v-if="createRowErrors[idx]?.kode" class="mt-1 text-xs text-red-500">{{ createRowErrors[idx].kode }}</p>
                            </div>
                            <div class="col-span-7">
                                <input
                                    v-model="createForm.sub_kegiatans[idx].nama"
                                    class="w-full rounded border px-2 py-1 text-sm"
                                    placeholder="Nama sub kegiatan"
                                />
                                <p v-if="createRowErrors[idx]?.nama" class="mt-1 text-xs text-red-500">{{ createRowErrors[idx].nama }}</p>
                            </div>
                            <div class="col-span-1 flex items-center justify-end">
                                <button
                                    @click.prevent="(createForm.sub_kegiatans.splice(idx, 1), createRowErrors.splice(idx, 1))"
                                    class="text-sm text-red-600"
                                    v-if="createForm.sub_kegiatans.length > 1"
                                >
                                    ×
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="pt-2">
                        <button @click.prevent="createForm.sub_kegiatans.push({ kode: '', nama: '' })" class="rounded bg-slate-100 px-3 py-1 text-sm">
                            + Tambah Baris
                        </button>
                    </div>
                </div>

                <div class="sticky bottom-0 flex justify-end gap-2 border-t bg-white/90 px-4 py-3 backdrop-blur dark:bg-slate-800/90">
                    <button @click="Modal.ModalSubKegiatanCreate = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitSubCreate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Sub Kegiatan Edit -->
        <div v-if="Modal.ModalSubKegiatanEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">{{ subKForm.id ? 'Edit Sub Kegiatan' : 'Tambah Sub Kegiatan' }}</h3>
                <div class="space-y-3">
                    <input type="hidden" v-model="subKForm.kegiatan_id" />
                    <label class="block">
                        <span class="text-sm font-medium">Kode</span>
                        <input v-model="subKForm.kode" class="w-full rounded border px-3 py-2" placeholder="Kode sub kegiatan (unik)" />
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Nama</span>
                        <input v-model="subKForm.nama" class="w-full rounded border px-3 py-2" placeholder="Nama sub kegiatan" />
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="Modal.ModalSubKegiatanEdit = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitSubEdit" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Sub Kegiatan Delete -->
        <div v-if="Modal.ModalSubKegiatanDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Hapus Sub Kegiatan</h3>
                <p>
                    Apakah Anda yakin ingin menghapus sub kegiatan <strong>{{ selectedKegiatan?.nama }}</strong
                    >?
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="Modal.ModalSubKegiatanDelete = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="confirmSubDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
