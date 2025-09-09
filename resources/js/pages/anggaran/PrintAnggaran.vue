<script setup lang="ts">
import { formatIDR } from '@/utils/currency';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';
import { onMounted, ref } from 'vue';
pdfMake.vfs = pdfFonts.vfs;

// Terima props dari controller Inertia jika tersedia
const props = defineProps<{
    ssk?: string | null;
    year?: number | null;
    sub?: any | null;
    rekeningItems?: Array<any> | null;
}>();

const printContext = ref<any>(null);
const rekeningItems = ref<any[]>(props.rekeningItems ?? []);

onMounted(async () => {
    // Prefer server-provided props, tapi fallback ke localStorage
    if (!props.rekeningItems || !props.rekeningItems.length) {
        try {
            const raw = window.localStorage.getItem('print_context');
            printContext.value = raw ? JSON.parse(raw) : null;
        } catch {}

        // Jika server not provided, try fetch via API as fallback
        if ((!rekeningItems.value || !rekeningItems.value.length) && printContext.value?.sub_sub_kegiatan?.kode) {
            try {
                const resp = await fetch(
                    `/api/anggaran/rekening?ssk=${encodeURIComponent(printContext.value.sub_sub_kegiatan.kode)}&year=${encodeURIComponent(printContext.value.tahun)}`,
                );
                if (resp.ok) {
                    const data = await resp.json();
                    rekeningItems.value = data || [];
                }
            } catch (e) {
                // ignore
            }
        }
    } else {
        // use server-provided context
        printContext.value = {
            program: { kode: props.ssk ? props.ssk.split('.')[0] : '', nama: props.sub?.nama ?? '' },
            kegiatan: { kode: '', nama: '' },
            sub_kegiatan: { kode: '', nama: '' },
            sub_sub_kegiatan: { kode: props.ssk ?? '', nama: props.sub?.nama ?? '' },
            tahun: props.year ?? null,
        };
    }
});

function generatePdf() {
    const ctx = printContext.value;
    const items = rekeningItems.value;
    // Grouping: for demo we consider all items as one group; in real use, items should contain group info
    const headerTable = [
        ['Program', `: ${ctx?.program?.kode} - ${ctx?.program?.nama}`],
        ['Kegiatan', `: ${ctx?.kegiatan?.kode} - ${ctx?.kegiatan?.nama}`],
        ['Sub Kegiatan', `: ${ctx?.sub_kegiatan?.kode} - ${ctx?.sub_kegiatan?.nama}`],
        ['Sub-Sub Kegiatan', `: ${ctx?.sub_sub_kegiatan?.kode} - ${ctx?.sub_sub_kegiatan?.nama}`],
        ['Tahun', `: ${ctx?.tahun}`],
    ];

    // Build table body with complex header like example
    const tableBody = [];
    // top header row spanning columns
    tableBody.push([
        { text: 'Kode Rekening', style: 'tableHeader', rowSpan: 2 },
        { text: 'Uraian', style: 'tableHeader', rowSpan: 2 },
        { text: 'Rincian Perhitungan', style: 'tableHeader', colSpan: 4, alignment: 'center' },
        {},
        {},
        {},
        { text: 'Jumlah (Rp)', style: 'tableHeader', rowSpan: 2, alignment: 'right' },
    ]);
    // second header row for rincian
    tableBody.push([
        {},
        {},
        { text: 'Koefisien / Volume', style: 'tableHeader' },
        { text: 'Satuan', style: 'tableHeader' },
        { text: 'Harga', style: 'tableHeader' },
        { text: 'PPN', style: 'tableHeader' },
        {},
    ]);

    // Add items (for demo, add group captions and totals similar to sample)
    // Group caption
    tableBody.push([{ text: '', colSpan: 7, border: [false, false, false, false], margin: [0, 4, 0, 4] }, {}, {}, {}, {}, {}, {}]);
    tableBody.push([
        { text: '[ # ] Belanja Pegawai BLUD RSUD Ibnu Sina Kabupaten Gresik', colSpan: 6, bold: true },
        {},
        {},
        {},
        {},
        {},
        { text: formatIDR(42520979967), alignment: 'right', bold: true },
    ]);

    // Sub-row example
    tableBody.push([
        { text: '5.1.02', style: 'mono' },
        { text: 'Belanja Barang dan Jasa', bold: true },
        { text: '', colSpan: 4 },
        {},
        {},
        {},
        { text: formatIDR(129896779185), alignment: 'right', bold: true },
    ]);

    // List dummy items
    for (const item of items) {
        tableBody.push([
            { text: item.rekening, style: 'mono' },
            { text: item.nama },
            { text: '1', alignment: 'center' },
            { text: item.satuan, alignment: 'center' },
            { text: formatIDR(item.harga), alignment: 'right' },
            { text: '0%', alignment: 'center' },
            { text: formatIDR(item.harga), alignment: 'right' },
        ]);
    }

    // Group subtotal
    const subtotal = items.reduce((s, it) => s + Number(it.harga || 0), 0);
    tableBody.push([
        { text: '', colSpan: 6, border: [false, false, false, false] },
        {},
        {},
        {},
        {},
        {},
        { text: formatIDR(subtotal), alignment: 'right', bold: true },
    ]);

    const docDefinition: import('pdfmake/interfaces').TDocumentDefinitions = {
        pageSize: 'A4',
        pageOrientation: 'portrait',
        pageMargins: [24, 24, 24, 24],
        content: [
            { text: 'RINCIAN PERHITUNGAN ANGGARAN', style: 'title', alignment: 'center', margin: [0, 0, 0, 8] },
            {
                table: {
                    widths: ['auto', '*'],
                    body: headerTable,
                },
                layout: 'noBorders',
                margin: [0, 0, 0, 8],
            },
            {
                table: {
                    headerRows: 2,
                    widths: [60, '*', 70, 60, 70, 40, 90],
                    body: tableBody,
                },
                layout: {
                    fillColor: function (rowIndex: number, node: any, columnIndex: number) {
                        return rowIndex === 0 || rowIndex === 1 ? '#f3f4f6' : null;
                    },
                },
            },
        ],
        styles: {
            title: { fontSize: 12, bold: true },
            tableHeader: { bold: true, fontSize: 9, fillColor: '#f3f4f6', margin: [2, 6, 2, 6] },
            mono: { font: 'Courier', fontSize: 9 },
        },
        defaultStyle: { fontSize: 9 },
    };

    pdfMake.createPdf(docDefinition).download(`Anggaran_${ctx?.sub_sub_kegiatan?.kode || 'data'}.pdf`);
}
</script>

<template>
    <div class="print-container">
        <h2 class="mb-2 text-center text-xl font-bold">Cetak Anggaran</h2>
        <div class="mb-4">
            <table class="mb-2 w-full text-sm">
                <tr>
                    <td class="font-semibold">Program</td>
                    <td>: {{ printContext?.program?.kode }} - {{ printContext?.program?.nama }}</td>
                </tr>
                <tr>
                    <td class="font-semibold">Kegiatan</td>
                    <td>: {{ printContext?.kegiatan?.kode }} - {{ printContext?.kegiatan?.nama }}</td>
                </tr>
                <tr>
                    <td class="font-semibold">Sub Kegiatan</td>
                    <td>: {{ printContext?.sub_kegiatan?.kode }} - {{ printContext?.sub_kegiatan?.nama }}</td>
                </tr>
                <tr>
                    <td class="font-semibold">Sub-Sub Kegiatan</td>
                    <td>: {{ printContext?.sub_sub_kegiatan?.kode }} - {{ printContext?.sub_sub_kegiatan?.nama }}</td>
                </tr>
                <tr>
                    <td class="font-semibold">Tahun</td>
                    <td>: {{ printContext?.tahun }}</td>
                </tr>
            </table>
        </div>
        <table class="w-full border text-xs">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1">Kode Rekening</th>
                    <th class="border px-2 py-1">Nama Item</th>
                    <th class="border px-2 py-1">Satuan</th>
                    <th class="border px-2 py-1">Spesifikasi</th>
                    <th class="border px-2 py-1">Harga</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, idx) in rekeningItems" :key="idx">
                    <td class="border px-2 py-1 font-mono">{{ item.rekening }}</td>
                    <td class="border px-2 py-1">{{ item.nama }}</td>
                    <td class="border px-2 py-1">{{ item.satuan }}</td>
                    <td class="border px-2 py-1">{{ item.spesifikasi }}</td>
                    <td class="border px-2 py-1 text-right">{{ formatIDR(item.harga) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="mt-6 text-center">
            <button class="rounded bg-green-600 px-4 py-2 text-white" @click="generatePdf">Download PDF</button>
        </div>
    </div>
</template>

<style scoped>
.print-container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
@media print {
    .print-container {
        box-shadow: none;
        border-radius: 0;
        padding: 0;
        background: #fff;
    }
    button {
        display: none;
    }
}
</style>
