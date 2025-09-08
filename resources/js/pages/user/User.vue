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
    role: Array<string> | string;
    allPics?: { id: number; kode: string; nama: string }[];
}>();

const rolesList = computed(() => {
    const raw = props.role ?? [];
    if (Array.isArray(raw)) return raw;
    if (!raw) return [];
    // if comma-separated string, split, else wrap single string
    if (typeof raw === 'string' && raw.includes(',')) {
        return raw
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);
    }
    return [String(raw)];
});
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
    roles: [] as string[],
    nip: '',
    unit: '',
    password: '',
    pic_ids: [] as number[],
});

const updateForm = useForm({
    id: null as number | null,
    name: '',
    roles: [] as string[],
    nip: '',
    unit: '',
    password: '',
    pic_ids: [] as number[],
});

function openCreate() {
    createForm.reset();
    Modal.value.ModalCreate = true;
}

function submitCreate() {
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

    // Normalize roles to an array of strings that match `rolesList` entries.
    const rawRoles = user.roles ?? (user.role ? [user.role] : []);
    const mapped = (Array.isArray(rawRoles) ? rawRoles : [rawRoles])
        .map((r: any) => {
            if (!r) return '';
            if (typeof r === 'string') return r;
            if (typeof r === 'object') return r.name ?? r.role ?? r.slug ?? r.nama ?? String(r.id ?? '');
            return String(r);
        })
        .filter(Boolean);
    updateForm.roles = mapped;
    updateForm.nip = user.nip ?? '';
    updateForm.unit = user.unit ?? '';
    updateForm.password = '';
    updateForm.pic_ids = (user.pics || []).map((p: any) => p.id);
    Modal.value.ModalUpdate = true;
}

function submitUpdate() {
    if (!updateForm.id) return;
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
        <div class="m-4 space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Daftar User</h1>
                <button @click="openCreate" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
                    <i class="fa fa-user-plus mr-2" aria-hidden="true"></i>
                    Tambah User
                </button>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm dark:bg-slate-800">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
                            <th class="px-4 py-2 text-center text-sm font-medium">PIC (Sub-Sub Kegiatan)</th>
                            <th class="px-4 py-2 text-center text-sm font-medium">Role</th>
                            <th class="px-4 py-2 text-center text-sm font-medium">NIP</th>
                            <th class="px-4 py-2 text-center text-sm font-medium">Unit</th>
                            <th class="px-4 py-2 text-center text-sm font-medium">Dibuat</th>
                            <th class="px-4 py-2 text-center text-sm font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 font-semibold text-white"
                                    >
                                        {{ initials(user.name) }}
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ user.name ?? '-' }}</div>
                                    </div>
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
                                <div class="flex items-center justify-center gap-2">
                                    <div v-if="user.roles && user.roles.length">
                                        <div
                                            v-for="r in Array.isArray(user.roles) && user.roles.length ? user.roles : user.role ? [user.role] : []"
                                            :key="r"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800"
                                                >{{ r }}</span
                                            >
                                        </div>
                                    </div>
                                    <div v-else>-</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">{{ user.nip ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ user.unit ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ moment(user.created_at).format('DD/MM/YYYY HH:mm') }}</td>
                            <td class="px-4 py-3 text-center text-sm">
                                <div class="flex justify-center gap-2">
                                    <button
                                        @click.prevent="openUpdate(user)"
                                        class="flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-sm dark:bg-slate-700"
                                    >
                                        <i class="fa fa-edit text-slate-500"></i>
                                        Edit
                                    </button>
                                    <button
                                        @click.prevent="openDelete(user)"
                                        class="flex items-center gap-1 rounded bg-red-500 px-2 py-1 text-sm text-white"
                                    >
                                        <i class="fa fa-trash"></i>
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
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> PIC Sub-Sub Kegiatan </span>
                    <select v-model="createForm.pic_ids" multiple class="h-32 w-full rounded border px-2 py-1 text-xs">
                        <option v-for="p in props.allPics || []" :key="p.id" :value="p.id">{{ p.kode }} - {{ p.nama }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Role </span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template v-for="(r, idx) in rolesList" :key="`${r}-${idx}`">
                            <label :for="`role-create-${idx}`" class="inline-flex items-center gap-2 rounded border px-2 py-1">
                                <input :id="`role-create-${idx}`" type="checkbox" :value="r" v-model="createForm.roles" />
                                <span class="text-sm">{{ r }}</span>
                            </label>
                        </template>
                    </div>
                </label>
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
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> PIC Sub-Sub Kegiatan </span>
                    <select v-model="updateForm.pic_ids" multiple class="h-32 w-full rounded border px-2 py-1 text-xs">
                        <option v-for="p in props.allPics || []" :key="p.id" :value="p.id">{{ p.kode }} - {{ p.nama }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 flex items-center gap-2 text-sm font-medium"> Role </span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template v-for="(r, idx) in rolesList" :key="`${r}-${idx}`">
                            <label :for="`role-update-${idx}`" class="inline-flex items-center gap-2 rounded border px-2 py-1">
                                <input :id="`role-update-${idx}`" type="checkbox" :value="r" v-model="updateForm.roles" />
                                <span class="text-sm">{{ r }}</span>
                            </label>
                        </template>
                    </div>
                </label>
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
