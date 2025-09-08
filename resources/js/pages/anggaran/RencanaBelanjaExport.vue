<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// pdfmake
// @ts-ignore
import pdfMake from 'pdfmake/build/pdfmake';
// @ts-ignore
import pdfFonts from 'pdfmake/build/vfs_fonts';
// some build setups export vfs under different shapes (pdfFonts.pdfMake.vfs or pdfFonts.vfs)
const _vfs = (pdfFonts && pdfFonts.pdfMake && pdfFonts.pdfMake.vfs) || (pdfFonts && pdfFonts.vfs) || pdfFonts;
pdfMake.vfs = _vfs || pdfMake.vfs || {};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Anggaran', href: '#' },
    { title: 'Rencana Belanja Export', href: route('rencana-belanja') },
];

function formatCellText(el: Element | null) {
    if (!el) return '';
    return String(el.textContent || '').trim();
}

function buildAndOpenPdf() {
    const docDefinition = {
        pageMargins: [24, 24, 24, 32],
        defaultStyle: { fontSize: 9 },
        styles: {
            th: { bold: true, fontSize: 9 },
        },
        content: [
            {
                table: {
                    headerRows: 2,
                    widths: [80, '*', 85, 55, 85, 40, 95],
                    body: [
                        // ---------- HEADER ROW 1 ----------
                        [
                            { text: 'Kode Rekening', style: 'th', alignment: 'center', rowSpan: 2 }, // c1
                            { text: 'Uraian', style: 'th', alignment: 'center', rowSpan: 2 }, // c2
                            { text: 'Rincian Perhitungan', style: 'th', alignment: 'center', colSpan: 4 }, // c3..c6
                            {},
                            {},
                            {},
                            { text: 'Jumlah (Rp)', style: 'th', alignment: 'center', rowSpan: 2 }, // c7
                        ],
                        // ---------- HEADER ROW 2 ----------
                        [
                            {}, // rowSpan Kode Rekening
                            {}, // rowSpan Uraian
                            { text: 'Koefisien / Volume', style: 'th', alignment: 'center' },
                            { text: 'Satuan', style: 'th', alignment: 'center' },
                            { text: 'Harga', style: 'th', alignment: 'center' },
                            { text: 'PPN', style: 'th', alignment: 'center' },
                            {}, // rowSpan Jumlah
                        ],

                        // ====== DATA ======
                        // Baris judul kelompok (PROGRAM/KEGIATAN/BELANJA ...)
                        [
                            { text: '5.1.01', bold: true },
                            { text: '[+] Belanja Pegawai BLUD RSUD Ibnu Sina Kabupaten Gresik', bold: true, colSpan: 5 },
                            {},
                            {},
                            {},
                            {},
                            { text: 'Rp4.252.509.970,67', bold: true, alignment: 'right' },
                        ],
                        // Sub-detail
                        [
                            '',
                            { text: 'Belanja Pegawai BLUD', margin: [10, 0, 0, 0] },
                            { text: '1 Tahun', alignment: 'center' },
                            { text: 'Tahun', alignment: 'center' },
                            { text: 'Rp4.252.509.970,67', alignment: 'right' },
                            { text: '0%', alignment: 'center' },
                            { text: 'Rp4.252.509.970,67', alignment: 'right' },
                        ],

                        // Contoh kelompok lain
                        [
                            { text: '5.1.02', bold: true },
                            { text: 'Belanja Barang dan Jasa BLUD', bold: true, colSpan: 5 },
                            {},
                            {},
                            {},
                            {},
                            { text: 'Rp129.896.779.185', bold: true, alignment: 'right' },
                        ],
                        [
                            '',
                            { text: 'Belanja Barang dan Jasa BLUD', margin: [10, 0, 0, 0] },
                            { text: '1 Tahun', alignment: 'center' },
                            { text: 'Tahun', alignment: 'center' },
                            { text: 'Rp129.896.779.185', alignment: 'right' },
                            { text: '0%', alignment: 'center' },
                            { text: 'Rp129.896.779.185', alignment: 'right' },
                        ],

                        // …lanjutkan baris sesuai datamu
                    ],
                },
                layout: {
                    // Header 2 baris diberi shading
                    fillColor: function (rowIndex: any) {
                        return rowIndex < 2 ? '#eaf4ea' : null;
                    },
                    hLineWidth: () => 1,
                    vLineWidth: () => 1,
                    hLineColor: () => '#000',
                    vLineColor: () => '#000',
                    paddingLeft: () => 4,
                    paddingRight: () => 4,
                    paddingTop: () => 3,
                    paddingBottom: () => 3,
                },
            },
        ],
    };

    pdfMake.createPdf(docDefinition).open();
}

onMounted(() => {
    // wait briefly to ensure data rendering from the main page if navigated with inertia replace
    setTimeout(buildAndOpenPdf, 300);
});
</script>

<template>
    <Head title="Export Rencana Belanja" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-4">
            <div class="overflow-hidden rounded-lg bg-white shadow-md dark:bg-slate-800">
                <div class="p-4">
                    <h2 class="text-lg font-semibold">Export Rencana Belanja</h2>
                    <p class="text-sm text-slate-500">PDF akan terbuka otomatis. Tutup halaman ini setelah download selesai.</p>
                </div>
                <div class="p-4">
                    <div class="text-sm text-slate-600">Jika PDF tidak terbuka otomatis, klik tombol di bawah.</div>
                    <div class="mt-3">
                        <button @click.prevent="buildAndOpenPdf" class="rounded border px-3 py-2">Buka PDF</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
