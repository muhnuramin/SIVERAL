<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Anggaran', href: '#' },
    { title: 'Sub Kegiatan Belanja', href: route('rencana-belanja') },
];

const dropdownOpen = ref(false);
const menuStyle = ref<Record<string, string>>({});

async function toggleDropdown(event: MouseEvent) {
    // compute and place the menu relative to the clicked button so it escapes parent clipping
    event.stopPropagation();
    const btn = event.currentTarget as HTMLElement | null;
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    // wait for next tick in case menu size matters
    dropdownOpen.value = !dropdownOpen.value;
    await nextTick();

    // default left align to button's left, place below the button
    const left = Math.round(rect.left + window.scrollX);
    const top = Math.round(rect.bottom + window.scrollY + 6); // small gap

    menuStyle.value = {
        position: 'fixed',
        left: `${left}px`,
        top: `${top}px`,
        zIndex: '9999',
        minWidth: '14rem',
    };
}

function closeDropdown() {
    dropdownOpen.value = false;
}

onMounted(() => {
    // close dropdown on any outside click
    window.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', closeDropdown);
});
</script>

<template>
    <Head title="Rencana Belanja" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-4">
            <div class="overflow-visible rounded-lg bg-white shadow-md dark:bg-slate-800">
                <div class="flex items-center justify-between border-b p-4 dark:border-slate-700">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Sub Kegiatan Belanja</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Daftar dan kelola sub kegiatan belanja beserta pagu dan realisasinya.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <input placeholder="Cari kode atau nama..." class="w-64 rounded border px-3 py-2 text-sm" />
                            <i class="fa fa-search absolute top-2 right-2 text-slate-400"></i>
                        </div>

                        <a
                            href="/rencana-belanja/export"
                            class="inline-flex items-center gap-2 rounded border px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                        >
                            <i class="fa-solid fa-file-export text-slate-700"></i>
                            Export
                        </a>
                        <button
                            class="inline-flex items-center gap-2 rounded bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-green-700"
                            title="Tambah Sub Kegiatan"
                        >
                            <i class="fa fa-plus"></i>
                            Tambah Sub Kegiatan
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium">Aksi</th>
                                    <th class="px-4 py-2 text-left font-medium">Sub Kegiatan</th>
                                    <th class="px-4 py-2 text-left font-medium">Pagu</th>
                                    <th class="px-4 py-2 text-left font-medium">Total Realisasi</th>
                                    <th class="px-4 py-2 text-left font-medium">Persentase</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white dark:bg-slate-800">
                                <!-- Program -->
                                <tr class="bg-slate-100 dark:bg-slate-900">
                                    <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200" colspan="5">
                                        [PROGRAM] PROG-01 PROGRAM PEMBANGUNAN A
                                    </td>
                                </tr>

                                <!-- Kegiatan -->
                                <tr class="bg-slate-100 dark:bg-slate-900">
                                    <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200" colspan="5">
                                        [KEGIATAN] PROG-01-KEG-01 KEGIATAN 1 DARI PROGRAM PEMBANGUNAN A
                                    </td>
                                </tr>
                                <!-- Sub Kegiatan 2 -->
                                <tr class="transition hover:bg-green-50">
                                    <td class="flex items-center justify-center gap-2 border-b px-2 py-2 text-center">
                                        <div class="relative">
                                            <div class="relative inline-block text-left">
                                                <!-- Tombol Trigger -->
                                                <button
                                                    id="menuButton"
                                                    @click.stop="(e) => toggleDropdown(e)"
                                                    class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-orange-400 px-2 py-1 text-white shadow-sm hover:bg-orange-500 focus:outline-none"
                                                >
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </button>

                                                <!-- Dropdown -->
                                                <div
                                                    id="dropdownMenu"
                                                    v-show="dropdownOpen"
                                                    :style="menuStyle"
                                                    class="ring-opacity-5 rounded-md bg-white shadow-lg ring-1 ring-black"
                                                    @click.stop
                                                >
                                                    <div class="py-1">
                                                        <!-- Update -->
                                                        <a
                                                            href="#"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                                        >
                                                            ✏️ <span class="ml-2">Update Sub Kegiatan</span>
                                                        </a>

                                                        <!-- Hapus -->
                                                        <a
                                                            href="#"
                                                            class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-800"
                                                        >
                                                            🗑️ <span class="ml-2">Hapus Sub Kegiatan</span>
                                                        </a>

                                                        <hr class="my-1" />

                                                        <!-- Lihat Sub Kegiatan -->
                                                        <!-- <a
                                                            href="#"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                                        >
                                                            📄 <span class="ml-2">Lihat Sub Kegiatan</span>
                                                        </a> -->

                                                        <!-- Lihat Rincian Belanja -->
                                                        <a
                                                            href="/rencana-belanja/1"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                                        >
                                                            📑 <span class="ml-2">Lihat Rincian Belanja</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b px-2 py-2">
                                        <span class="text-sm text-slate-700 dark:text-slate-200">[SUB KEGIATAN]</span>
                                        <span class="ml-1 text-gray-700">PROG-01-KEG-01-SUB-2 Sub Kegiatan 2 dari Kegiatan 1</span>
                                    </td>
                                    <td class="border-b px-2 py-2 text-right font-bold text-green-700">Rp. 63.857.667.678</td>
                                    <td class="border-b px-2 py-2 text-center text-gray-500">-</td>
                                    <td class="border-b px-2 py-2 text-center text-gray-500">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
