<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

const props = defineProps<{ akuns?: Array<Record<string, any>>; sub_sub_kegiatan?: Record<string, any> }>();
const Modal = ref({
    ModalAkunCreate: false,
    ModalAkunEdit: false,
    ModalAkunDelete: false,
});

const form: any = useForm({ id: null as number | null, sub_sub_kegiatan_id: null as number | null, kode_rekening: '', nama: '' });
const deleteForm = useForm({ _method: 'DELETE' });
const items = ref<Array<Record<string, any>>>(props.akuns ?? []);
const loading = ref(false);
const subSubId = (props.sub_sub_kegiatan?.id ?? Number(new URLSearchParams(window.location.search).get('sub_sub_kegiatan_id'))) || null;
const subSub = ref<Record<string, any> | null>(props.sub_sub_kegiatan ?? null);
const selectedItem = ref<Record<string, any> | null>(null);
const notify = useNotify();

// unified form state for create (multi-row) and edit (single)
const formState = reactive({ akuns: [{ kode_rekening: '', nama: '' }], id: null as number | null, kode_rekening: '', nama: '' });
const createRowErrors = ref<Array<Record<string, string> | null>>([]);

function addAkunRow() {
    formState.akuns.push({ kode_rekening: '', nama: '' });
}

function removeAkunRow(idx: number) {
    if (formState.akuns.length <= 1) return;
    formState.akuns.splice(idx, 1);
    createRowErrors.value.splice(idx, 1);
}

function mapCreateErrors(errs: Record<string, any>) {
    // normalize possible shapes: { errors: { 'akuns.0.kode_rekening': ['...'] } } or direct { 'akuns.0.kode_rekening': [...] }
    const source = (errs && (errs.errors ?? errs)) as Record<string, any>;
    const map: Array<Record<string, string>> = [];
    if (!source) {
        createRowErrors.value = [];
        return;
    }
    for (const k in source) {
        const m = k.match(/^akuns\.(\d+)\.(.+)$/);
        if (m) {
            const idx = Number(m[1]);
            const field = m[2];
            map[idx] = map[idx] || {};
            const v = source[k];
            map[idx][field] = Array.isArray(v) ? (v[0] ?? String(v)) : (v ?? String(v));
        } else {
            // some backends/validators may return flat keys like 'kode_rekening' or 'nama'
            // map those to the first row (index 0) so the UI still shows the validation
            const simple = k.match(/^([^.]+)$/);
            if (simple) {
                const field = simple[1];
                map[0] = map[0] || {};
                const v = source[k];
                map[0][field] = Array.isArray(v) ? (v[0] ?? String(v)) : (v ?? String(v));
            }
        }
    }
    // log mapped result for easier debug in browser console
    console.log('Akun.create mappedRowErrors:', map);
    createRowErrors.value = map;
}

async function load() {
    if (!subSubId) return;
    loading.value = true;
    try {
        const res = await fetch(route('akun.get', subSubId));
        if (!res.ok) return;
        const data = await res.json();
        items.value = data.akuns || [];
        if (data.sub_sub_kegiatan && data.sub_sub_kegiatan.nama) subSub.value = data.sub_sub_kegiatan;
    } catch (e) {
        console.error('load akun error', e);
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    // open multi-row create modal
    form.reset();
    form.sub_sub_kegiatan_id = subSubId;
    formState.akuns = [{ kode_rekening: '', nama: '' }];
    formState.id = null;
    formState.kode_rekening = '';
    formState.nama = '';
    createRowErrors.value = [];
    Modal.value.ModalAkunCreate = true;
}

function openEdit(i: Record<string, any>) {
    // populate inertia form for editing so validation errors bind
    form.reset();
    form.id = i.id ?? null;
    form.kode_rekening = i.kode_rekening ?? '';
    form.nama = i.nama ?? '';
    form.sub_sub_kegiatan_id = i.sub_sub_kegiatan_id ?? subSubId;
    Modal.value.ModalAkunEdit = true;
}

function openSSH(i: Record<string, any>) {
    const id = i.id;
    try {
        if (typeof window !== 'undefined' && (window as any).Inertia) {
            (window as any).Inertia.get(route('ssh.index'), { akun_id: id });
            return;
        }
    } catch (e) {}
    window.location.href = route('ssh.index') + '?akun_id=' + id;
}

function openDelete(i: Record<string, any>) {
    selectedItem.value = i;
    Modal.value.ModalAkunDelete = true;
}

function submitCreate() {
    // normalize reactive objects into plain JS objects so Inertia sends correct payload
    const akunsPayload = formState.akuns.map((a: any) => ({
        kode_rekening: (a?.kode_rekening ?? '').toString(),
        nama: (a?.nama ?? '').toString(),
    }));
    const payload = { sub_sub_kegiatan_id: subSubId, akuns: akunsPayload };
    const f = useForm(payload);
    createRowErrors.value = [];
    f.post(route('akun.store'), {
        onError: (errs: Record<string, any>) => {
            // errs may have different shapes depending on how Inertia returns validation errors.
            // Normalize and log for easier debugging, then map to per-row errors.
            const normalized = errs?.errors ?? errs;
            console.log('Akun.create onError:', normalized);
            mapCreateErrors(normalized);
            // show generic toast only if there are any mapped errors or a general message
            const hasMapped =
                Array.isArray(createRowErrors.value) &&
                createRowErrors.value.length > 0 &&
                createRowErrors.value.some((e) => e && Object.keys(e).length > 0);
            if (hasMapped || normalized?.message) {
                notify.error('Periksa kembali input anda.');
            }
        },
        onSuccess: async () => {
            await load();
            Modal.value.ModalAkunCreate = false;
            formState.akuns = [{ kode_rekening: '', nama: '' }];
            createRowErrors.value = [];
            notify.success('Akun berhasil ditambahkan.');
        },
    });
}

function submitEdit() {
    if (!form.id) return;
    form.put(route('akun.update', form.id), {
        onSuccess: () => {
            form.reset();
            Modal.value.ModalAkunEdit = false;
            load();
            notify.success('Akun berhasil diperbarui');
        },
        onError: () => {
            notify.error('Gagal memperbarui akun');
        },
    });
}

function confirmDelete() {
    if (!selectedItem.value) return;
    deleteForm.post(route('akun.destroy', selectedItem.value.id), {
        onSuccess: () => {
            Modal.value.ModalAkunDelete = false;
            selectedItem.value = null;
            load();
            notify.success('Akun berhasil dihapus.');
        },
        onError: () => {
            notify.error('Gagal menghapus akun.');
        },
    });
}

onMounted(() => {
    if (!props.akuns) load();
});
</script>

<template>
    <Head title="Akun" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Master', href: '#' },
            { title: 'Akun', href: route('akun.index') },
        ]"
    >
        <div class="m-4 space-y-6 rounded-lg bg-gray-50 p-6 shadow dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Akun</h1>
                    <div class="text-sm text-slate-500">
                        Sub-Sub Kegiatan: <span class="font-semibold">{{ subSub?.nama ?? 'ID ' + subSubId }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click.prevent="openCreate" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">Tambah Akun</button>
                    <a :href="route('subsubkegiatan.index')" class="text-sm text-slate-500 hover:underline">&larr; Kembali ke Sub-Sub Kegiatan</a>
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-white p-4 shadow sm:p-6">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Kode Rekening</th>
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
                                <td class="px-4 py-3 text-sm text-slate-700">{{ i.kode_rekening }}</td>
                                <td class="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{{ i.nama }}</td>
                                <td class="px-4 py-3 text-center text-sm text-slate-500">
                                    {{ i.created_at ? new Date(i.created_at).toLocaleDateString('id-ID') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click.prevent="openSSH(i)"
                                            class="flex items-center gap-1 rounded bg-indigo-600 px-3 py-1 text-white"
                                        >
                                            <i class="fa fa-link"></i>
                                            SSH
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
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada akun.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Create (Program-style, compact, scrollable) -->
        <div v-if="Modal.ModalAkunCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-2xl rounded bg-white shadow-lg dark:bg-slate-800">
                <div class="border-b px-4 py-3 dark:border-slate-700">
                    <h3 class="text-base font-semibold">Tambah Akun (multi-row)</h3>
                </div>

                <div class="max-h-[60vh] space-y-2 overflow-y-auto p-4">
                    <template v-for="(a, idx) in formState.akuns" :key="idx">
                        <div class="grid grid-cols-12 items-center gap-2 py-1">
                            <div class="col-span-1 text-sm text-slate-600">#{{ idx + 1 }}</div>
                            <div class="col-span-3">
                                <input
                                    v-model="formState.akuns[idx].kode_rekening"
                                    class="w-full rounded border px-2 py-1 text-sm"
                                    placeholder="Kode Rekening"
                                />
                                <p v-if="createRowErrors[idx]?.kode_rekening" class="mt-1 text-xs text-red-500">
                                    {{ createRowErrors[idx].kode_rekening }}
                                </p>
                            </div>
                            <div class="col-span-7">
                                <input v-model="formState.akuns[idx].nama" class="w-full rounded border px-2 py-1 text-sm" placeholder="Nama akun" />
                                <p v-if="createRowErrors[idx]?.nama" class="mt-1 text-xs text-red-500">{{ createRowErrors[idx].nama }}</p>
                            </div>
                            <div class="col-span-1 flex items-center justify-end">
                                <button
                                    @click.prevent="removeAkunRow(idx)"
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200"
                                    v-if="formState.akuns.length > 1"
                                    title="Hapus baris"
                                >
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="pt-2">
                        <button @click.prevent="addAkunRow" class="rounded bg-slate-100 px-3 py-1 text-sm">+ Tambah Baris</button>
                    </div>
                </div>

                <div class="sticky bottom-0 flex justify-end gap-2 border-t bg-white/90 px-4 py-3 backdrop-blur dark:bg-slate-800/90">
                    <button @click="Modal.ModalAkunCreate = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitCreate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Edit -->
        <div v-if="Modal.ModalAkunEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">{{ formState.id ? 'Edit Akun' : 'Tambah Akun' }}</h3>
                <div class="space-y-3">
                    <label class="block">
                        <span class="text-sm font-medium">Kode Rekening</span>
                        <input v-model="form.kode_rekening" class="w-full rounded border px-3 py-2" placeholder="Kode Rekening (unik)" />
                        <p v-if="form.errors && form.errors.kode_rekening" class="mt-1 text-xs text-red-500">{{ form.errors.kode_rekening }}</p>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Nama</span>
                        <input v-model="form.nama" class="w-full rounded border px-3 py-2" placeholder="Nama" />
                        <p v-if="form.errors && form.errors.nama" class="mt-1 text-xs text-red-500">{{ form.errors.nama }}</p>
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click.prevent="Modal.ModalAkunEdit = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="submitEdit" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Delete -->
        <div v-if="Modal.ModalAkunDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Hapus Akun</h3>
                <p>
                    Apakah Anda yakin ingin menghapus akun <strong>{{ selectedItem?.nama }}</strong
                    >?
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click.prevent="Modal.ModalAkunDelete = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="confirmDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
