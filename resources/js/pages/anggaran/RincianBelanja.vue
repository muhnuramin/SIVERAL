<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { defineProps, ref } from 'vue';

const props = defineProps<{ id: string }>();

// sample hierarchical data (kept for demo)
const data = ref([
    {
        id: 1,
        nama: 'Belanja Barang Pakai Habis',
        type: 'akun',
        children: [
            {
                id: 2,
                nama: 'Belanja Alat Tulis Kantor',
                type: 'sub_akun',
                children: [
                    {
                        id: 3,
                        kode: '5.1.02.01.01.0024',
                        nama: 'Belanja Alat/Bahan untuk Kegiatan Kantor - Alat Tulis Kantor',
                        type: 'detail',
                        children: [
                            {
                                id: 4,
                                uraian: 'HEKTER UKURAN 50',
                                spek: 'KANGAROO-MAX',
                                koefisien: '3 Buah',
                                harga_satuan: 150000,
                                total: 450000,
                                type: 'ssh',
                            },
                        ],
                    },
                ],
            },
        ],
    },
]);

// simplified view: no accordion/toggle — always show details

const currency = new Intl.NumberFormat('id-ID');
function formatRp(v: number | string) {
    const n = Number(v) || 0;
    return 'Rp ' + currency.format(n);
}
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Anggaran', href: '#' },
    { title: 'Sub Kegiatan Belanja', href: route('rencana-belanja') },
    { title: 'Rincian Belanja', href: '#' },
];

function goBack() {
    window.location.href = route('rencana-belanja');
}

const search = ref('');
</script>

<template>
    <Head title="Rincian Belanja" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-4">
            <div class="rounded bg-white p-4 shadow">
                <h2 class="text-lg font-semibold">Rincian Belanja</h2>
                <p class="text-sm text-slate-500">Halaman detail sederhana. Ganti dengan data nyata nanti.</p>
                <div class="mt-4 space-y-2">
                    <table>
                        <tr>
                            <td class="font-medium">Program</td>
                            <td class="px-3 font-medium">:</td>
                            <td class="text-slate-700">Nama Program Contoh</td>
                        </tr>
                        <tr>
                            <td class="font-medium">Kegiatan</td>
                            <td class="px-3 font-medium">:</td>
                            <td class="text-slate-700">Nama Kegiatan Contoh</td>
                        </tr>
                        <tr>
                            <td class="font-medium">Sub Kegiatan</td>
                            <td class="px-3 font-medium">:</td>
                            <td class="text-slate-700">{{ props.id }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="m-4">
            <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 items-center gap-2">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari rincian..."
                        class="rounded border border-gray-300 px-3 py-2 text-sm focus:border-green-400 focus:outline-none"
                    />
                    <button @click="search = ''" v-if="search" class="ml-1 rounded bg-gray-200 px-2 py-1 text-xs text-gray-600 hover:bg-gray-300">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="mt-2 flex gap-2 md:mt-0">
                    <button class="flex items-center gap-1 rounded bg-green-600 px-4 py-2 text-white shadow transition hover:bg-green-700">
                        <i class="fa fa-plus"></i>
                        <span>Tambah Rincian</span>
                    </button>
                    <button
                        @click="goBack"
                        class="flex items-center gap-1 rounded border border-gray-300 bg-white px-4 py-2 text-gray-700 shadow transition hover:bg-gray-100"
                    >
                        <i class="fa fa-arrow-left"></i>
                        <span>Kembali</span>
                    </button>
                </div>
            </div>
            <div class="rounded bg-white p-4 shadow">
                <div class="mt-3">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-left">
                                <th class="p-2" style="width: 5%">Aksi</th>
                                <th class="p-2">Uraian</th>
                                <th class="w-40 p-2">Koefisien</th>
                                <th class="w-40 p-2">Harga Satuan</th>
                                <th class="w-40 p-2">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detail in data[0].children[0].children" :key="detail.id" class="border-b">
                                <td class="bg-gray-50 p-2 text-center"></td>
                                <td class="bg-gray-50 p-2 font-semibold" colspan="4">{{ detail.kode }} {{ detail.nama }}</td>
                            </tr>
                            <tr v-for="ssh in data[0].children[0].children[0].children" :key="ssh.id" class="border-b">
                                <td class="flex justify-center gap-2 p-2">
                                    <button class="text-green-500">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="text-red-500">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                                <td class="p-2">
                                    <div class="font-semibold">{{ ssh.uraian }}</div>
                                    <div class="text-sm text-gray-500">{{ ssh.spek }}</div>
                                </td>
                                <td class="p-2">{{ ssh.koefisien }}</td>
                                <td class="p-2">{{ ssh.harga_satuan }}</td>
                                <td class="p-2">{{ ssh.total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
