<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Satuan {
    id: number;
    nama: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedSatuans {
    data: Satuan[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    satuans: PaginatedSatuans;
}

const props = defineProps<Props>();
const notify = useNotify();

// Form states
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// Form data
const addForm = ref({ nama: '' });
const editForm = ref({ id: 0, nama: '' });
const deleteItem = ref<Satuan | null>(null);

// Form errors
const addFormErrors = ref<{ nama?: string }>({});
const editFormErrors = ref<{ nama?: string }>({});

// Search
const searchQuery = ref('');

// Filtered data for search
const filteredSatuans = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.satuans.data;
    }
    return props.satuans.data.filter((satuan) => satuan.nama.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Satuan', href: '' },
];

// Modal functions
function openAddModal() {
    addForm.value = { nama: '' };
    addFormErrors.value = {};
    showAddModal.value = true;
}

function closeAddModal() {
    showAddModal.value = false;
    addForm.value = { nama: '' };
    addFormErrors.value = {};
}

function openEditModal(satuan: Satuan) {
    editForm.value = { id: satuan.id, nama: satuan.nama };
    editFormErrors.value = {};
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editForm.value = { id: 0, nama: '' };
    editFormErrors.value = {};
}

function openDeleteModal(satuan: Satuan) {
    deleteItem.value = satuan;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deleteItem.value = null;
}

// CRUD operations
function validateAddForm() {
    const errors: { nama?: string } = {};

    if (!addForm.value.nama.trim()) {
        errors.nama = 'Nama satuan wajib diisi.';
    } else if (addForm.value.nama.trim().length > 255) {
        errors.nama = 'Nama satuan maksimal 255 karakter.';
    }

    addFormErrors.value = errors;
    return Object.keys(errors).length === 0;
}

function validateEditForm() {
    const errors: { nama?: string } = {};

    if (!editForm.value.nama.trim()) {
        errors.nama = 'Nama satuan wajib diisi.';
    } else if (editForm.value.nama.trim().length > 255) {
        errors.nama = 'Nama satuan maksimal 255 karakter.';
    }

    editFormErrors.value = errors;
    return Object.keys(errors).length === 0;
}

function submitAdd() {
    if (!validateAddForm()) return;

    router.post(
        route('satuan.store'),
        {
            nama: addForm.value.nama.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeAddModal();
                notify.success('Satuan berhasil ditambahkan');
            },
            onError: (errors: any) => {
                if (errors.nama) {
                    addFormErrors.value.nama = errors.nama;
                } else {
                    notify.error('Gagal menambahkan satuan');
                }
            },
        },
    );
}

function submitEdit() {
    if (!validateEditForm()) return;

    router.put(
        route('satuan.update', editForm.value.id),
        {
            nama: editForm.value.nama.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
                notify.success('Satuan berhasil diperbarui');
            },
            onError: (errors: any) => {
                if (errors.nama) {
                    editFormErrors.value.nama = errors.nama;
                } else {
                    notify.error('Gagal memperbarui satuan');
                }
            },
        },
    );
}

function submitDelete() {
    if (!deleteItem.value) return;

    router.delete(route('satuan.destroy', deleteItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            notify.success('Satuan berhasil dihapus');
        },
        onError: (errors: any) => {
            closeDeleteModal();
            if (errors.delete) {
                notify.error(errors.delete);
            } else {
                notify.error('Gagal menghapus satuan');
            }
        },
    });
}

// Navigation
function goToPage(url: string) {
    if (url) {
        router.get(url, {}, { preserveScroll: true });
    }
}

// Format date
function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Satuan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Satuan</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Kelola data satuan yang digunakan dalam sistem</p>
                </div>
                <button
                    @click="openAddModal"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
                >
                    + Tambah Satuan
                </button>
            </div>

            <!-- Main Content Card -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <!-- Search -->
                <div class="mb-6">
                    <div class="relative max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari satuan..."
                            class="block w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Nama Satuan
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Dibuat
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Diperbarui
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="satuan in filteredSatuans" :key="satuan.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ satuan.nama }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ formatDate(satuan.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ formatDate(satuan.updated_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                        <div class="flex justify-end space-x-2">
                                            <button
                                                @click="openEditModal(satuan)"
                                                class="text-warning-600 hover:bg-warning-100 rounded p-2"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                @click="openDeleteModal(satuan)"
                                                class="text-danger-600 hover:bg-danger-100 rounded p-2"
                                                title="Hapus"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredSatuans.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="mb-2 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m0 0V9a2 2 0 012-2h2m0 0V6a2 2 0 012-2h2.09M12 13h0"
                                                />
                                            </svg>
                                            <span class="text-sm">
                                                {{ searchQuery ? 'Tidak ada satuan yang ditemukan' : 'Belum ada data satuan' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="satuans.last_page > 1" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Menampilkan {{ (satuans.current_page - 1) * satuans.per_page + 1 }} sampai
                        {{ Math.min(satuans.current_page * satuans.per_page, satuans.total) }}
                        dari {{ satuans.total }} data
                    </div>
                    <div class="flex space-x-1">
                        <button
                            v-for="link in satuans.links"
                            :key="link.label"
                            @click="goToPage(link.url || '')"
                            :disabled="!link.url"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : link.url
                                      ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                                      : 'cursor-not-allowed bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <ConfirmModal
            :open="showAddModal"
            title="Tambah Satuan"
            confirm-text="Simpan"
            cancel-text="Batal"
            variant="info"
            @cancel="closeAddModal"
            @confirm="submitAdd"
        >
            <div class="space-y-4">
                <div>
                    <label for="add-nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Nama Satuan </label>
                    <input
                        id="add-nama"
                        v-model="addForm.nama"
                        type="text"
                        :class="[
                            'mt-1 block w-full rounded-md border px-3 py-2 text-sm',
                            addFormErrors.nama
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                        ]"
                        placeholder="Masukkan nama satuan"
                        @keyup.enter="submitAdd"
                    />
                    <p v-if="addFormErrors.nama" class="mt-1 text-sm text-red-600">
                        {{ addFormErrors.nama }}
                    </p>
                </div>
            </div>
        </ConfirmModal>

        <!-- Edit Modal -->
        <ConfirmModal
            :open="showEditModal"
            title="Edit Satuan"
            confirm-text="Simpan"
            cancel-text="Batal"
            variant="info"
            @cancel="closeEditModal"
            @confirm="submitEdit"
        >
            <div class="space-y-4">
                <div>
                    <label for="edit-nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Nama Satuan </label>
                    <input
                        id="edit-nama"
                        v-model="editForm.nama"
                        type="text"
                        :class="[
                            'mt-1 block w-full rounded-md border px-3 py-2 text-sm',
                            editFormErrors.nama
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                        ]"
                        placeholder="Masukkan nama satuan"
                        @keyup.enter="submitEdit"
                    />
                    <p v-if="editFormErrors.nama" class="mt-1 text-sm text-red-600">
                        {{ editFormErrors.nama }}
                    </p>
                </div>
            </div>
        </ConfirmModal>

        <!-- Delete Modal -->
        <ConfirmModal
            :open="showDeleteModal"
            title="Hapus Satuan"
            confirm-text="Hapus"
            cancel-text="Batal"
            variant="danger"
            @cancel="closeDeleteModal"
            @confirm="submitDelete"
        >
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Apakah Anda yakin ingin menghapus satuan
                <span class="font-semibold">{{ deleteItem?.nama }}</span
                >? Tindakan ini tidak dapat dibatalkan.
            </p>
        </ConfirmModal>
    </AppLayout>
</template>
