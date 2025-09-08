<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps<{ kegiatans?: Array<Record<string, any>>; program?: Record<string, any> }>();
const Modal = ref({
    ModalKegiatan: false,
    ModalKegiatanEdit: false,
    ModalKegiatanDelete: false,
});

const programId = (props.program?.id ?? Number(new URLSearchParams(window.location.search).get('program_id'))) || null;
const kegiatans = ref<Array<Record<string, any>>>(props.kegiatans ?? []);
const programs = ref<Array<Record<string, any>>>([]);
const selectedProgramId = ref<number | null>(programId);
// single reactive form state used for both edit and multi-row create
const formState = reactive({ id: null as number | null, program_id: null as number | null, kode: '', nama: '', kegiatans: [{ kode: '', nama: '' }] });
const createRowErrors = ref<Array<Record<string, string> | null>>([]);
const kegiatanDeleteForm = useForm({ _method: 'DELETE' });
const selectedKegiatan = ref<Record<string, any> | null>(null);
const programName = ref<string | null>(props.program?.nama ?? null);
const loading = ref(false);
const notify = useNotify();

// Computed property untuk mengelompokkan kegiatan berdasarkan program
const groupedKegiatans = computed(() => {
    const groups: Record<string, { program: any; kegiatans: any[]; count: number }> = {};

    kegiatans.value.forEach((kegiatan) => {
        const programId = kegiatan.program?.id || 'unknown';
        if (!groups[programId]) {
            groups[programId] = {
                program: kegiatan.program || { nama: 'Program Tidak Diketahui', kode: '-' },
                kegiatans: [],
                count: 0,
            };
        }
        groups[programId].kegiatans.push(kegiatan);
        groups[programId].count++;
    });

    return Object.values(groups);
});

function addKegiatanRow() {
    formState.kegiatans.push({ kode: '', nama: '' });
}

function removeKegiatanRow(idx: number) {
    if (formState.kegiatans.length <= 1) return;
    formState.kegiatans.splice(idx, 1);
    createRowErrors.value.splice(idx, 1);
}

function submitKegiatanCreate() {
    // post batch of kegiatans
    const payload = {
        program_id: formState.program_id,
        kegiatans: formState.kegiatans,
    };
    const f = useForm(payload);
    createRowErrors.value = [];
    f.post(route('kegiatan.store'), {
        onError: (errs: Record<string, any>) => {
            const map: Array<Record<string, string>> = [];
            for (const k in errs) {
                const m = k.match(/^kegiatans\.(\d+)\.(.+)$/);
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
            // Reload data berdasarkan program yang dipilih
            await loadKegiatansByProgram(formState.program_id);
            Modal.value.ModalKegiatan = false;
            formState.kegiatans = [{ kode: '', nama: '' }];
            const selectedProgramId = formState.program_id;
            formState.program_id = null;
            createRowErrors.value = [];
            notify.success('Kegiatan berhasil ditambahkan.');
        },
    });
}
async function submitKegiatanEdit() {
    if (!formState.id) return;
    const payload = { program_id: formState.program_id, kode: formState.kode, nama: formState.nama };
    const f = useForm(payload);
    f.put(route('kegiatan.update', formState.id), {
        onSuccess: async () => {
            // Reload data berdasarkan program yang sedang diedit
            if (formState.program_id) {
                await loadKegiatansByProgram(formState.program_id);
            } else if (programId) {
                await load();
            }
            Modal.value.ModalKegiatanEdit = false;
            // reset formState edit fields
            formState.id = null;
            formState.kode = '';
            formState.nama = '';
            notify.success('Kegiatan berhasil diperbarui.');
        },
        onError: () => {
            notify.error('Gagal memperbarui kegiatan. Periksa input.');
        },
    });
}
async function confirmKegiatanDelete() {
    if (!selectedKegiatan.value) return;
    kegiatanDeleteForm.post(route('kegiatan.destroy', selectedKegiatan.value.id), {
        onSuccess: async () => {
            // Reload data berdasarkan program kegiatan yang dihapus
            if (selectedKegiatan.value?.program_id) {
                await loadKegiatansByProgram(selectedKegiatan.value.program_id);
            } else if (programId) {
                await load();
            }
            Modal.value.ModalKegiatanDelete = false;
            selectedKegiatan.value = null;
            kegiatanDeleteForm.reset();
            notify.success('Kegiatan berhasil dihapus.');
        },
        onError: () => {
            notify.error('Gagal menghapus kegiatan.');
        },
    });
}

function openKegiatanDelete(k: Record<string, any>) {
    selectedKegiatan.value = k;
    Modal.value.ModalKegiatanDelete = true;
}
function openKegiatanModal() {
    // reset formState for create
    formState.id = null;
    formState.program_id = programId ?? null;
    formState.kode = '';
    formState.nama = '';
    formState.kegiatans = [{ kode: '', nama: '' }];
    createRowErrors.value = [];
    Modal.value.ModalKegiatan = true;
}
function openKegiatanEdit(k: Record<string, any>) {
    // populate formState for edit
    formState.id = k.id ?? null;
    formState.program_id = k.program_id ?? null;
    formState.kode = k.kode ?? '';
    formState.nama = k.nama ?? '';
    Modal.value.ModalKegiatanEdit = true;
}

async function loadPrograms() {
    console.log('Loading programs from route:', route('program.api'));
    try {
        const res = await fetch(route('program.api'), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        console.log('Response status:', res.status, 'OK:', res.ok);
        if (!res.ok) {
            console.error('Failed to load programs:', res.status, res.statusText);
            const errorText = await res.text();
            console.error('Error response:', errorText);
            return;
        }
        const data = await res.json();
        console.log('Raw response data:', data);
        programs.value = data.programs || [];
        console.log('Programs loaded:', programs.value.length, 'items');
        console.log('Programs data:', programs.value);
    } catch (e) {
        console.error('load programs error', e);
    }
}

const editingId = ref<number | null>(null);

async function load() {
    if (!programId) return;
    loading.value = true;
    try {
        const res = await fetch(route('program.kegiatans', programId));
        if (!res.ok) return;
        const data = await res.json();
        kegiatans.value = data.kegiatans || [];
        if (data.program && data.program.nama) programName.value = data.program.nama;
        console.log('load kegiatans success', data);
    } catch (e) {
        console.error('load kegiatans error', e);
    } finally {
        loading.value = false;
    }
}

// Function to load kegiatans by specific program ID
async function loadKegiatansByProgram(programIdParam: number | null) {
    if (!programIdParam) return;
    loading.value = true;
    try {
        const res = await fetch(route('program.kegiatans', programIdParam));
        if (!res.ok) return;
        const data = await res.json();
        kegiatans.value = data.kegiatans || [];
        if (data.program && data.program.nama) programName.value = data.program.nama;
        console.log('load kegiatans by program success', data);
    } catch (e) {
        console.error('load kegiatans by program error', e);
    } finally {
        loading.value = false;
    }
}

function onProgramFilterChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    const value = target.value;
    selectedProgramId.value = value ? Number(value) : null;

    if (selectedProgramId.value) {
        loadKegiatansByProgram(selectedProgramId.value);
    } else {
        load();
    }
}

onMounted(async () => {
    await loadPrograms();
    if (!props.kegiatans) {
        await load();
    }
});
</script>

<template>
    <Head title="Kegiatan" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Master', href: '#' },
            { title: 'Program', href: '#' },
            { title: 'Kegiatan', href: route('kegiatan.index') },
        ]"
    >
        <div class="m-4 space-y-6 rounded-lg bg-gray-50 p-6 shadow dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Kegiatan</h1>
                    <p v-if="programName" class="text-sm text-slate-600 dark:text-slate-400">Program: {{ programName }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click.prevent="openKegiatanModal" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">Tambah Kegiatan</button>
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-white p-4 shadow sm:p-6">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Program</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Kode Kegiatan</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Kegiatan</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700 dark:text-slate-200">Dibuat</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr v-if="loading">
                            <td colspan="5" class="px-4 py-8 text-center">Memuat...</td>
                        </tr>

                        <template v-for="group in groupedKegiatans" :key="group.program.id">
                            <template v-for="(kegiatan, index) in group.kegiatans" :key="kegiatan.id">
                                <tr v-if="editingId !== kegiatan.id" class="hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <!-- Program column with rowspan -->
                                    <td
                                        v-if="index === 0"
                                        :rowspan="group.count"
                                        class="border-r border-slate-200 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-800 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-folder-tree text-blue-600"></i>
                                            <div>
                                                <div class="font-semibold">{{ group.program.nama }}</div>
                                                <div v-if="group.program.kode" class="text-xs text-slate-500">{{ group.program.kode }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kegiatan data -->
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                        {{ kegiatan.kode ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">
                                        {{ kegiatan.nama }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-slate-500">
                                        {{ kegiatan.created_at ? moment(kegiatan.created_at).format('DD/MM/YYYY') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                @click.prevent="openKegiatanEdit(kegiatan)"
                                                class="flex items-center gap-1 rounded bg-slate-100 px-3 py-1 text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                                <span class="hidden sm:inline">Edit</span>
                                            </button>
                                            <button
                                                @click.prevent="openKegiatanDelete(kegiatan)"
                                                class="flex items-center gap-1 rounded bg-red-600 px-3 py-1 text-white transition-colors hover:bg-red-700"
                                                title="Hapus"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                                <span class="hidden sm:inline">Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>

                        <tr v-if="!loading && kegiatans.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada kegiatan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Modal Kegiatan Create (multi-row compact) -->
        <div v-if="Modal.ModalKegiatan" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-3xl rounded bg-white shadow-lg dark:bg-slate-800">
                <div class="border-b px-4 py-3 dark:border-slate-700">
                    <h3 class="text-base font-semibold">Tambah Kegiatan (multi-row)</h3>
                </div>

                <div class="max-h-[70vh] space-y-4 overflow-y-auto p-4">
                    <!-- Program Selection -->
                    <div class="border-b pb-4 dark:border-slate-700">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                <i class="fas fa-folder-tree mr-1 text-blue-600"></i>
                                Pilih Program <span class="text-red-500">*</span>
                            </span>
                            <select
                                v-model="formState.program_id"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none dark:border-slate-600 dark:bg-slate-700"
                                required
                            >
                                <option value="" disabled>
                                    {{ programs.length === 0 ? '-- Loading programs... --' : '-- Pilih Program --' }}
                                </option>
                                <option v-for="program in programs" :key="program.id" :value="program.id">
                                    {{ program.kode ? `${program.kode} - ` : '' }}{{ program.nama }}
                                </option>
                            </select>
                            <p v-if="programs.length === 0" class="mt-1 text-xs text-blue-600">
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                Memuat daftar program...
                            </p>
                            <p v-else-if="!formState.program_id" class="mt-1 text-xs text-amber-600">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Program harus dipilih sebelum menambah kegiatan
                            </p>
                        </label>
                    </div>

                    <!-- Kegiatan Rows -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-list-ul text-green-600"></i>
                            <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300">Daftar Kegiatan</h4>
                        </div>

                        <template v-for="(r, idx) in formState.kegiatans" :key="idx">
                            <div class="grid grid-cols-12 items-center gap-2 rounded-lg bg-gray-50 p-3 dark:bg-slate-800">
                                <div class="col-span-1 text-sm font-medium text-slate-600 dark:text-slate-400">#{{ idx + 1 }}</div>
                                <div class="col-span-3">
                                    <input
                                        v-model="formState.kegiatans[idx].kode"
                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 focus:outline-none dark:border-slate-600 dark:bg-slate-700"
                                        placeholder="Kode (opsional)"
                                    />
                                    <p v-if="createRowErrors[idx]?.kode" class="mt-1 text-xs text-red-500">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ createRowErrors[idx].kode }}
                                    </p>
                                </div>
                                <div class="col-span-7">
                                    <input
                                        v-model="formState.kegiatans[idx].nama"
                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 focus:outline-none dark:border-slate-600 dark:bg-slate-700"
                                        placeholder="Nama kegiatan *"
                                        required
                                    />
                                    <p v-if="createRowErrors[idx]?.nama" class="mt-1 text-xs text-red-500">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ createRowErrors[idx].nama }}
                                    </p>
                                </div>
                                <div class="col-span-1 flex items-center justify-end">
                                    <button
                                        @click.prevent="removeKegiatanRow(idx)"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600 transition-all hover:bg-red-600 hover:text-white dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-600"
                                        v-if="formState.kegiatans.length > 1"
                                        title="Hapus baris"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-start pt-2">
                            <button
                                @click.prevent="addKegiatanRow"
                                class="flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                            >
                                <i class="fas fa-plus"></i>
                                <span>Tambah Baris</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="sticky bottom-0 flex justify-end gap-3 border-t bg-white/95 px-4 py-3 backdrop-blur dark:border-slate-700 dark:bg-slate-800/95"
                >
                    <button
                        @click="Modal.ModalKegiatan = false"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        <i class="fas fa-times"></i>
                        <span>Batal</span>
                    </button>
                    <button
                        @click.prevent="submitKegiatanCreate"
                        :disabled="!formState.program_id"
                        class="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i class="fas fa-save"></i>
                        <span>Simpan Kegiatan</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal Kegiatan Edit -->
        <div v-if="Modal.ModalKegiatanEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Edit Kegiatan</h3>
                <div class="space-y-4">
                    <!-- Program Selection -->
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            <i class="fas fa-folder-tree mr-1 text-blue-600"></i>
                            Program <span class="text-red-500">*</span>
                        </span>
                        <select
                            v-model="formState.program_id"
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none dark:border-slate-600 dark:bg-slate-700"
                            required
                        >
                            <option value="" disabled>
                                {{ programs.length === 0 ? '-- Loading programs... --' : '-- Pilih Program --' }}
                            </option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.kode ? `${program.kode} - ` : '' }}{{ program.nama }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Kode</span>
                        <input
                            v-model="formState.kode"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none dark:border-slate-600 dark:bg-slate-700"
                            placeholder="Kode kegiatan (opsional)"
                        />
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Nama <span class="text-red-500">*</span></span>
                        <input
                            v-model="formState.nama"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none dark:border-slate-600 dark:bg-slate-700"
                            placeholder="Nama kegiatan"
                            required
                        />
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="Modal.ModalKegiatanEdit = false"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        <i class="fas fa-times"></i>
                        <span>Batal</span>
                    </button>
                    <button
                        @click.prevent="submitKegiatanEdit"
                        :disabled="!formState.program_id || !formState.nama"
                        class="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i class="fas fa-save"></i>
                        <span>Simpan</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal Kegiatan Delete -->
        <div v-if="Modal.ModalKegiatanDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md rounded bg-white p-6 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold">Hapus Kegiatan</h3>
                <p>
                    Apakah Anda yakin ingin menghapus kegiatan <strong>{{ selectedKegiatan?.nama }}</strong
                    >?
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="Modal.ModalKegiatanDelete = false" class="rounded border px-4 py-2">Batal</button>
                    <button @click.prevent="confirmKegiatanDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
