<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

const props = defineProps<{ sub_sub_kegiatans?: Array<Record<string, any>>; sub_kegiatan?: Record<string, any> }>();
const Modal = ref({
    ModalSubSubCreate: false,
    ModalSubSubEdit: false,
    ModalSubSubDelete: false,
});

const deleteForm = useForm({ _method: 'DELETE' });
// unified formState for create (multi-row) and edit — declared after subKegiatanId
let formState: any = reactive({
    id: null as number | null,
    sub_kegiatan_id: null as number | null,
    kode: '',
    nama: '',
    sub_sub_kegiatans: [{ kode: '', nama: '' }],
});
const items = ref<Array<Record<string, any>>>(props.sub_sub_kegiatans ?? []);
const loading = ref(false);
const subKegiatanId = (props.sub_kegiatan?.id ?? Number(new URLSearchParams(window.location.search).get('sub_kegiatan_id'))) || null;
const subKegiatan = ref<Record<string, any> | null>(props.sub_kegiatan ?? null);
// now initialize formState with actual subKegiatanId
formState.sub_kegiatan_id = subKegiatanId;
const selectedItem = ref<Record<string, any> | null>(null);

const notify = useNotify();

const createRowErrors = ref<Array<any>>([{}]);

async function load() {
    if (!subKegiatanId) return;
    loading.value = true;
    try {
        const res = await fetch(route('subsubkegiatan.get', subKegiatanId));
        if (!res.ok) return;
        const data = await res.json();
        items.value = data.sub_sub_kegiatans || [];
        if (data.sub_kegiatan && data.sub_kegiatan.nama) subKegiatan.value = data.sub_kegiatan;
    } catch (e) {
        console.error('load sub-sub error', e);
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    // reset create form
    formState.id = null;
    formState.sub_kegiatan_id = subKegiatanId;
    formState.sub_sub_kegiatans = [{ kode: '', nama: '' }];
    createRowErrors.value = [{}];
    Modal.value.ModalSubSubCreate = true;
}

function addRow() {
    formState.sub_sub_kegiatans.push({ kode: '', nama: '' });
    createRowErrors.value.push({});
}

function removeRow(index: number) {
    formState.sub_sub_kegiatans.splice(index, 1);
    createRowErrors.value.splice(index, 1);
}

function openEdit(i: Record<string, any>) {
    // populate unified formState for edit
    formState.id = i.id ?? null;
    formState.kode = i.kode ?? '';
    formState.nama = i.nama ?? '';
    formState.sub_kegiatan_id = i.sub_kegiatan_id ?? subKegiatanId;
    Modal.value.ModalSubSubEdit = true;
}

function openAkun(i: Record<string, any>) {
    const id = i.id ?? subKegiatanId;
    try {
        if (typeof window !== 'undefined' && (window as any).Inertia) {
            (window as any).Inertia.get(route('akun.index'), { sub_sub_kegiatan_id: id });
            return;
        }
    } catch (e) {
        // ignore and fallback
    }
    window.location.href = route('akun.index') + '?sub_sub_kegiatan_id=' + id;
}

function openDelete(i: Record<string, any>) {
    selectedItem.value = i;
    Modal.value.ModalSubSubDelete = true;
}

function mapErrors(errors: any) {
    // map flattened errors like sub_sub_kegiatans.0.nama -> createRowErrors[0].nama
    Object.keys(errors || {}).forEach((key) => {
        const m = key.match(/sub_sub_kegiatans\.(\d+)\.(.+)/);
        if (m) {
            const idx = parseInt(m[1], 10);
            const field = m[2];
            createRowErrors.value[idx] = createRowErrors.value[idx] || {};
            createRowErrors.value[idx][field] = errors[key];
        }
    });
}

function submitCreate() {
    // build a plain payload object for inertia
    const payload = {
        sub_kegiatan_id: formState.sub_kegiatan_id,
        sub_sub_kegiatans: formState.sub_sub_kegiatans.map((s: any) => ({ kode: s.kode, nama: s.nama })),
    };
    const f = useForm(payload);
    f.post(route('subsubkegiatan.store'), {
        preserveState: true,
        onError: (errors) => {
            createRowErrors.value = formState.sub_sub_kegiatans.map(() => ({}));
            mapErrors(errors);
            notify.error('Periksa kembali input anda.');
        },
        onSuccess: () => {
            Modal.value.ModalSubSubCreate = false;
            formState.sub_sub_kegiatans = [{ kode: '', nama: '' }];
            createRowErrors.value = [{}];
            load();
            notify.success('Sub-sub kegiatan berhasil ditambahkan.');
        },
    });
}

function submitEdit() {
    if (!formState.id) return;
    const payload = { sub_kegiatan_id: formState.sub_kegiatan_id, kode: formState.kode, nama: formState.nama };
    const f = useForm(payload);
    f.put(route('subsubkegiatan.update', formState.id), {
        onSuccess: () => {
            // reset edit fields
            formState.id = null;
            formState.kode = '';
            formState.nama = '';
            Modal.value.ModalSubSubEdit = false;
            load();
            notify.success('Sub-sub kegiatan berhasil diperbarui.');
        },
        onError: () => {
            notify.error('Gagal memperbarui sub-sub kegiatan. Periksa input.');
        },
    });
}

function confirmDelete() {
    if (!selectedItem.value) return;
    deleteForm.post(route('subsubkegiatan.destroy', selectedItem.value.id), {
        onSuccess: () => {
            Modal.value.ModalSubSubDelete = false;
            selectedItem.value = null;
            load();
            notify.success('Sub-sub kegiatan berhasil dihapus.');
        },
    });
}

onMounted(() => {
    if (!props.sub_sub_kegiatans) load();
});
</script>

<template>
    <Head title="Sub-Sub Kegiatan" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Master', href: '#' },
            { title: 'Sub-Sub Kegiatan', href: route('subsubkegiatan.index') },
        ]"
    >
        <div class="m-4 space-y-6 rounded-lg bg-gray-50 p-6 shadow dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Sub-Sub Kegiatan</h1>
                    <div class="text-sm text-slate-500">
                        Sub Kegiatan: <span class="font-semibold">{{ subKegiatan?.nama ?? (subKegiatanId ? 'ID ' + subKegiatanId : '-') }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click.prevent="openCreate" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">Tambah Sub-Sub</button>
                    <a
                        :href="
                            subKegiatan?.kegiatan_id
                                ? route('subkegiatan.index', { kegiatan_id: subKegiatan.kegiatan_id })
                                : route('subkegiatan.index')
                        "
                        class="text-sm text-slate-500 hover:underline"
                        >&larr; Kembali ke Sub Kegiatan</a
                    >
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
                            <td colspan="4" class="px-4 py-8 text-center">Memuat...</td>
                        </tr>
                        <template v-for="i in items" :key="i.id">
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ i.kode }}</td>
                                <td class="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{{ i.nama }}</td>
                                <td class="px-4 py-3 text-center text-sm text-slate-500">
                                    {{ i.created_at ? new Date(i.created_at).toLocaleDateString('id-ID') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click.prevent="openAkun(i)"
                                            class="flex items-center gap-1 rounded bg-indigo-600 px-3 py-1 text-white"
                                        >
                                            <i class="fa fa-book"></i>
                                            Akun
                                        </button>
                                        <button
                                            @click.prevent="openEdit(i)"
                                            class="flex items-center gap-1 rounded bg-slate-100 px-3 py-1 text-slate-700"
                                        >
                                            <i class="fa fa-edit"></i>
                                            Edit
                                        </button>
                                        <button
                                            @click.prevent="openDelete(i)"
                                            class="flex items-center gap-1 rounded bg-red-600 px-3 py-1 text-white"
                                        >
                                            <i class="fa fa-trash"></i>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!loading && items.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada sub-sub kegiatan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Create (multi-row) -->
        <div v-if="Modal.ModalSubSubCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-2xl rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Tambah Sub-Sub Kegiatan (multi-row)</h3>
                <div class="mb-3 text-sm text-slate-600">
                    Sub Kegiatan: <strong>{{ subKegiatan?.nama ?? subKegiatanId }}</strong>
                </div>
                <div class="max-h-64 space-y-3 overflow-auto pr-2">
                    <template v-for="(row, idx) in formState.sub_sub_kegiatans" :key="idx">
                        <div class="flex items-start gap-2">
                            <div class="w-1/4">
                                <label class="block text-sm font-medium">Kode</label>
                                <input v-model="row.kode" class="w-full rounded border px-3 py-2 text-sm" placeholder="Kode" />
                                <div v-if="createRowErrors[idx] && createRowErrors[idx].kode" class="mt-1 text-xs text-red-600">
                                    {{ createRowErrors[idx].kode }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium">Nama</label>
                                <input v-model="row.nama" class="w-full rounded border px-3 py-2 text-sm" placeholder="Nama" />
                                <div v-if="createRowErrors[idx] && createRowErrors[idx].nama" class="mt-1 text-xs text-red-600">
                                    {{ createRowErrors[idx].nama }}
                                </div>
                            </div>
                            <div class="w-20 pt-6 text-right">
                                <button
                                    v-if="formState.sub_sub_kegiatans.length > 1"
                                    @click.prevent="removeRow(idx)"
                                    class="rounded bg-red-600 px-3 py-1 text-sm text-white"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <button @click.prevent="addRow" class="rounded border px-3 py-1 text-sm">Tambah Baris</button>
                    <div class="flex gap-2">
                        <button @click.prevent="Modal.ModalSubSubCreate = false" class="rounded border px-4 py-2">Batal</button>
                        <button @click.prevent="submitCreate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan Semua</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit -->
        <div v-if="Modal.ModalSubSubEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">{{ formState.id ? 'Edit Sub-Sub Kegiatan' : 'Tambah Sub-Sub Kegiatan' }}</h3>
                <div class="space-y-3">
                    <input type="hidden" v-model="formState.sub_kegiatan_id" />
                    <label class="block">
                        <span class="text-sm font-medium">Kode</span>
                        <input v-model="formState.kode" class="w-full rounded border px-3 py-2" placeholder="Kode (unik)" />
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Nama</span>
                        <input v-model="formState.nama" class="w-full rounded border px-3 py-2" placeholder="Nama" />
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click.prevent="Modal.ModalSubSubEdit = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitEdit" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Delete -->
        <div v-if="Modal.ModalSubSubDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Hapus Sub-Sub Kegiatan</h3>
                <p>
                    Apakah Anda yakin ingin menghapus sub-sub kegiatan <strong>{{ selectedItem?.nama }}</strong
                    >?
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click.prevent="Modal.ModalSubSubDelete = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="confirmDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
