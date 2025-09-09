<script setup lang="ts">
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, ref } from 'vue';
const notify = useNotify();

const Modal = ref({
    ModalHapus: false,
    ModalCreate: false,
    ModalUpdate: false,
});

const props = defineProps<{
    dataUser: Array<Record<string, any>>;
    allPics?: { id: number; kode: string; nama: string }[];
    hakGroups?: { id: number; nama: string; hak_akses?: string[] }[];
}>();

// legacy `roles` removed — UI uses hakGroups instead
const deleteForm = useForm({ _method: 'DELETE' });
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '#',
    },
    {
        title: 'User',
        href: route('user'),
    },
];

const users = computed(() => props.dataUser ?? []);

function initials(name?: string) {
    if (!name) return '';
    const parts = name.split(' ').filter(Boolean);
    return parts
        .map((p: string) => p[0] ?? '')
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

const selectedUser = ref<Record<string, any> | null>(null);

const createForm = useForm({
    name: '',
    // new single group id field
    hakaksesgrub_id: null as number | null,
    roles: [] as string[], // keep for backward compatibility
    nip: '',
    unit: '',
    password: '',
    pic_ids: [] as number[],
});

const updateForm = useForm({
    id: null as number | null,
    name: '',
    hakaksesgrub_id: null as number | null,
    roles: [] as string[],
    nip: '',
    unit: '',
    password: '',
    pic_ids: [] as number[],
});

// --- embedded HakAksesGrup form state ---
// groups are passed from server via props.hakGroups
const groupsProp = computed(() => (props.hakGroups ?? []) as Array<any>);
const PERMISSIONS = ['Lihat Perencanaan', 'Lihat Evaluasi', 'Lihat Pelaporan', 'Super Admin'];

const groupForm = useForm({ nama: '', hak_akses: [] as string[] });
// checkbox model for permissions
const hakAksesChecked = ref<string[]>([]);
const editGroupId = ref<number | null>(null);

function openEditGroup(g: any) {
    editGroupId.value = g.id;
    groupForm.nama = g.nama;
    groupForm.hak_akses = g.hak_akses ?? [];
    hakAksesChecked.value = g.hak_akses ?? [];
}

function resetGroupForm() {
    editGroupId.value = null;
    groupForm.reset();
    hakAksesChecked.value = [];
}

function submitGroup() {
    groupForm.hak_akses = hakAksesChecked.value || [];
    if (editGroupId.value) {
        groupForm.put(route('hakaksesgrup.update', editGroupId.value), { onSuccess: () => resetGroupForm() });
    } else {
        groupForm.post(route('hakaksesgrup.store'), { onSuccess: () => resetGroupForm() });
    }
}

function removeGroup(id: number) {
    if (!confirm('Hapus grup?')) return;
    groupForm.delete(route('hakaksesgrup.destroy', id), { onSuccess: () => {} });
}

function refreshPage() {
    // use location to reload the page from the browser context
    location.reload();
}

function openCreate() {
    createForm.reset();
    Modal.value.ModalCreate = true;
}

function submitCreate() {
    // ensure payload includes hakaksesgrub_id
    createForm.post(route('user.store'), {
        onSuccess: () => {
            Modal.value.ModalCreate = false;
            createForm.reset();
            // notify
            notify.success('User berhasil dibuat');
        },
        onError: () => {
            notify.error('Gagal membuat user');
        },
    });
}

function openUpdate(user: Record<string, any>) {
    selectedUser.value = user;
    updateForm.reset();
    updateForm.id = user.id ?? null;
    updateForm.name = user.name ?? '';
    // Populate single hakaksesgrub if available, otherwise fallback to roles
    if (user.hakaksesgrub_id) {
        updateForm.hakaksesgrub_id = user.hakaksesgrub_id;
    } else if (user.hakaksesgrub && user.hakaksesgrub.id) {
        updateForm.hakaksesgrub_id = user.hakaksesgrub.id;
    } else {
        // Populate hakaksesgrub if available
        const rawHakGroups = user.hakaksesgrub ?? (user.hakaksesgrub_id ? [user.hakaksesgrub_id] : []);
        updateForm.hakaksesgrub_id = Array.isArray(rawHakGroups) ? rawHakGroups[0] : rawHakGroups;
    }
    updateForm.nip = user.nip ?? '';
    updateForm.unit = user.unit ?? '';
    updateForm.password = '';
    updateForm.pic_ids = (user.pics || []).map((p: any) => p.id);
    Modal.value.ModalUpdate = true;
}

function submitUpdate() {
    if (!updateForm.id) return;
    // payload will contain hakaksesgrub_id if set
    updateForm.put(route('user.update', updateForm.id), {
        onSuccess: () => {
            Modal.value.ModalUpdate = false;
            notify.success('User berhasil diperbarui');
        },
        onError: () => {
            notify.error('Gagal memperbarui user');
        },
    });
}

function openDelete(user: Record<string, any>) {
    selectedUser.value = user;
    Modal.value.ModalHapus = true;
}

function confirmDelete() {
    if (!selectedUser.value) return;
    deleteForm.post(route('user.destroy', selectedUser.value.id), {
        onSuccess: () => {
            Modal.value.ModalHapus = false;
            selectedUser.value = null;
            deleteForm.reset();
            notify.success('User berhasil dihapus');
        },
        onError: () => {
            notify.error('Gagal menghapus user');
        },
    });
}
</script>

<template>
    <Head title="User" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 dark:text-slate-100">Daftar User</h1>
                    <p class="mt-1 text-sm text-slate-500">Kelola akun pengguna dan pengaturan hak akses grup di sini.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="openCreate"
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Tambah User
                    </button>
                    <button @click="refreshPage" title="Refresh" class="rounded-full bg-slate-100 px-3 py-2 text-sm hover:bg-slate-200">
                        <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M21 12a9 9 0 11-3-6.72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl bg-white shadow-md dark:bg-slate-800">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-600">Nama</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600">PIC</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600">Group Akses</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600">NIP</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600">Unit</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600">Dibuat</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="odd:bg-white even:bg-slate-50 hover:even:bg-slate-100 dark:odd:bg-slate-800 dark:even:bg-slate-700"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 font-extrabold text-white shadow-md"
                                    >
                                        {{ initials(user.name) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ user.name ?? '-' }}</div>
                                        <div class="text-xs text-slate-400">{{ user.email ?? '' }}</div>
                                    </div>

                                    <!-- Hak Akses Grup section moved below the users table -->
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                <div class="flex flex-wrap justify-center gap-1">
                                    <span
                                        v-for="p in user.pics || []"
                                        :key="p.id"
                                        class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-800"
                                        >{{ p.nama }}</span
                                    >
                                    <template v-if="!(user.pics && user.pics.length)">-</template>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <div class="flex flex-col items-center">
                                    <div v-if="user.hakaksesgrub" class="text-center">
                                        <div class="font-semibold text-emerald-700">{{ user.hakaksesgrub.nama }}</div>
                                        <div class="text-xs text-slate-500">
                                            <span v-if="user.hakaksesgrub.hak_akses && user.hakaksesgrub.hak_akses.length">
                                                {{ (user.hakaksesgrub.hak_akses || []).slice(0, 3).join(', ') }}
                                                <span v-if="user.hakaksesgrub.hak_akses.length > 3"
                                                    >... ({{ user.hakaksesgrub.hak_akses.length }})</span
                                                >
                                            </span>
                                            <span v-else class="text-xs">-</span>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <div v-if="user.roles && user.roles.length" class="flex flex-wrap justify-center gap-1">
                                            <span
                                                v-for="r in user.roles"
                                                :key="r"
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800"
                                                >{{ r }}</span
                                            >
                                        </div>
                                        <div v-else class="text-slate-400">-</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">{{ user.nip ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ user.unit ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ moment(user.created_at).format('DD/MM/YYYY HH:mm') }}</td>
                            <td class="px-4 py-3 text-center text-sm">
                                <div class="flex justify-center gap-2">
                                    <button
                                        @click.prevent="openUpdate(user)"
                                        class="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-1 text-sm hover:bg-slate-200"
                                    >
                                        <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 5h6M6 12h12M6 19h12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        Edit
                                    </button>
                                    <button
                                        @click.prevent="openDelete(user)"
                                        class="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path
                                                d="M3 6h18M8 6v14a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada user.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Hak Akses Grup (embedded CRUD) -->
                <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="col-span-2 rounded-lg bg-gradient-to-r from-slate-50 to-white p-4 shadow-inner">
                        <h3 class="mb-3 text-lg font-bold text-slate-700">Daftar Grup Akses</h3>
                        <div class="overflow-auto rounded-md border border-slate-100">
                            <table class="min-w-full bg-white">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
                                        <th class="px-4 py-2 text-left text-sm font-medium">Hak Akses</th>
                                        <th class="px-4 py-2 text-center text-sm font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="g in groupsProp" :key="g.id" class="border-t hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-700">{{ g.nama }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-500">
                                            <div class="flex flex-wrap justify-start gap-2">
                                                <template v-if="g.hak_akses && g.hak_akses.length">
                                                    <span
                                                        v-for="a in g.hak_akses"
                                                        :key="a"
                                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700"
                                                        >{{ a }}</span
                                                    >
                                                </template>
                                                <span v-else class="text-slate-400">-</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button
                                                class="inline-flex items-center gap-2 rounded-md bg-amber-100 px-3 py-1 text-sm"
                                                @click="openEditGroup(g)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                class="ml-2 inline-flex items-center gap-2 rounded-md bg-red-100 px-3 py-1 text-sm text-red-700"
                                                @click="removeGroup(g.id)"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-span-1 rounded-lg bg-white p-4 shadow-md">
                        <h3 class="mb-3 text-lg font-bold text-slate-700">Buat / Edit Grup</h3>
                        <form @submit.prevent="submitGroup" class="space-y-3">
                            <label class="block">
                                <span class="text-sm font-medium text-slate-600">Nama Grup</span>
                                <input v-model="groupForm.nama" class="mt-1 w-full rounded border px-3 py-2" placeholder="Contoh: Admin" />
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-slate-600">Hak Akses</span>
                                <div class="mt-2 grid grid-cols-1 gap-2">
                                    <label v-for="perm in PERMISSIONS" :key="perm" class="inline-flex items-center gap-2">
                                        <input type="checkbox" :value="perm" v-model="hakAksesChecked" class="h-4 w-4" />
                                        <span class="text-sm text-slate-700">{{ perm }}</span>
                                    </label>
                                </div>
                            </label>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="resetGroupForm" class="rounded-md border px-3 py-1 text-sm">Batal</button>
                                <button type="submit" class="rounded-md bg-emerald-600 px-3 py-1 text-sm text-white">
                                    {{ editGroupId ? 'Simpan' : 'Buat' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal Create -->
    <div v-if="Modal.ModalCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
            <h3 class="mb-4 text-lg font-semibold">Tambah User</h3>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Nama </span>
                    <input v-model="createForm.name" class="w-full rounded border px-3 py-2" placeholder="Nama" />
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Group Akses </span>
                    <select v-model="createForm.hakaksesgrub_id" class="w-full rounded border px-3 py-2">
                        <option :value="null">-- Pilih Group --</option>
                        <option v-for="g in props.hakGroups || []" :key="g.id" :value="g.id">{{ g.nama }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> PIC Sub-Sub Kegiatan </span>
                    <select v-model="createForm.pic_ids" multiple class="h-32 w-full rounded border px-2 py-1 text-xs">
                        <option v-for="p in props.allPics || []" :key="p.id" :value="p.id">{{ p.kode }} - {{ p.nama }}</option>
                    </select>
                </label>
                <!-- roles removed: replaced by Group Akses -->
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> NIP </span>
                    <input v-model="createForm.nip" class="w-full rounded border px-3 py-2" placeholder="NIP" />
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Unit </span>
                    <input v-model="createForm.unit" class="w-full rounded border px-3 py-2" placeholder="Unit" />
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Password </span>
                    <input v-model="createForm.password" type="password" class="w-full rounded border px-3 py-2" placeholder="Password" />
                </label>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="Modal.ModalCreate = false" class="rounded border px-4 py-2">Batal</button>
                <button @click.prevent="submitCreate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Update -->
    <div v-if="Modal.ModalUpdate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-lg rounded bg-white p-6 dark:bg-slate-800">
            <h3 class="mb-4 text-lg font-semibold">Update User</h3>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Nama </span>
                    <input v-model="updateForm.name" class="w-full rounded border px-3 py-2" placeholder="Nama" />
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Group Akses </span>
                    <select v-model="updateForm.hakaksesgrub_id" class="w-full rounded border px-3 py-2">
                        <option :value="null">-- Pilih Group --</option>
                        <option v-for="g in props.hakGroups || []" :key="g.id" :value="g.id">{{ g.nama }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> PIC Sub-Sub Kegiatan </span>
                    <select v-model="updateForm.pic_ids" multiple class="h-32 w-full rounded border px-2 py-1 text-xs">
                        <option v-for="p in props.allPics || []" :key="p.id" :value="p.id">{{ p.kode }} - {{ p.nama }}</option>
                    </select>
                </label>
                <!-- roles removed: replaced by Group Akses -->
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> NIP </span>
                    <input v-model="updateForm.nip" class="w-full rounded border px-3 py-2" placeholder="NIP" />
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Unit </span>
                    <input v-model="updateForm.unit" class="w-full rounded border px-3 py-2" placeholder="Unit" />
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Password </span>
                    <input
                        v-model="updateForm.password"
                        type="password"
                        class="w-full rounded border px-3 py-2"
                        placeholder="Password (kosongkan jika tidak diubah)"
                    />
                </label>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="Modal.ModalUpdate = false" class="rounded border px-4 py-2">Batal</button>
                <button @click.prevent="submitUpdate" class="rounded bg-emerald-600 px-4 py-2 text-white">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Delete -->
    <div v-if="Modal.ModalHapus" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded bg-white p-6 dark:bg-slate-800">
            <h3 class="mb-4 text-lg font-semibold">Hapus User</h3>
            <p>
                Apakah Anda yakin ingin menghapus user <strong>{{ selectedUser?.name }}</strong
                >?
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="Modal.ModalHapus = false" class="rounded border px-4 py-2">Batal</button>
                <button @click.prevent="confirmDelete" class="rounded bg-red-600 px-4 py-2 text-white">Hapus</button>
            </div>
        </div>
    </div>
</template>
