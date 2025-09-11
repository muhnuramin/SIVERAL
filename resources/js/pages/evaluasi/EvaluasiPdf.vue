<script setup lang="ts">
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';
import { defineProps } from 'vue';
pdfMake.vfs = pdfFonts.vfs;

const props = defineProps<{
    tahun: number;
    rows: any[];
    months: string[];
    grand: { totalPagu: number; totalItems: number; totalSisa: number };
    grandTotalAmountPerMonth: (month: string) => number;
    formatIDR: (n: number) => string;
    monthlyAmount: (p: any, m: string) => number;
    totalPerItem: (p: any) => number;
}>();

// Hitung jumlah kolom tabel
function getItemColCount(months: string[]): number {
    // 2 kolom tetap + 2 kolom per bulan + 1 kolom total
    return 3 + months.length * 2;
}

// Buat header tabel evaluasi
function buildTableHeader(months: string[]): any[] {
    const itemColCount = getItemColCount(months);
    // Baris 1
    const headerRow1: any[] = [
        { text: 'SSH Item & Satuan', rowSpan: 2, style: 'th', alignment: 'center' },
        { text: 'Harga Satuan', rowSpan: 2, style: 'th', alignment: 'center' },
    ];
    months.forEach((m) => {
        // setiap bulan mengambil 2 kolom (Vol + Rupiah) sehingga kita tambahkan placeholder
        headerRow1.push({ text: m.toUpperCase(), colSpan: 2, style: 'th', alignment: 'center' });
        headerRow1.push({ text: '', style: 'th', alignment: 'center' }); // placeholder untuk kolSpan
    });
    headerRow1.push({ text: 'Total Item', rowSpan: 2, style: 'th', alignment: 'center' });

    // Baris 2
    const headerRow2: any[] = [
        { text: '', style: 'th', alignment: 'center' }, // SSH Item & Satuan (rowSpan)
        { text: '', style: 'th', alignment: 'center' }, // Harga Satuan (rowSpan)
    ];
    months.forEach(() => {
        headerRow2.push({ text: 'Vol', style: 'th', alignment: 'center' });
        headerRow2.push({ text: 'Rupiah', style: 'th', alignment: 'center' });
    });
    headerRow2.push({ text: '', style: 'th', alignment: 'center' }); // Total Item (rowSpan)

    // Pastikan jumlah sel di header sesuai itemColCount
    while (headerRow1.length < itemColCount) {
        headerRow1.push({ text: '', style: 'th', alignment: 'center' });
    }
    while (headerRow2.length < itemColCount) {
        headerRow2.push({ text: '', style: 'th', alignment: 'center' });
    }

    return [headerRow1, headerRow2];
}

// Buat body tabel evaluasi
function buildTableBody(
    rows: any[],
    months: string[],
    formatIDR: (n: number) => string,
    monthlyAmount: (p: any, m: string) => number,
    totalPerItem: (p: any) => number,
    grand: { totalPagu: number; totalItems: number; totalSisa: number },
): any[] {
    const itemColCount = getItemColCount(months);
    const body: any[] = [];

    rows.forEach((ssk: any) => {
        // Baris judul sub kegiatan & pagu (gunakan placeholder setelah colSpan)
        const headerCell = {
            text: `${ssk.nama}  |  Pagu: ${formatIDR(ssk.pagu)}`,
            colSpan: itemColCount,
            style: 'td',
            alignment: 'left',
            fontSize: 10,
            bold: true,
        };
        const headerRow: any[] = [headerCell];
        for (let i = 1; i < itemColCount; i++) {
            headerRow.push({ text: '' });
        }
        body.push(headerRow);

        if (ssk.plans && ssk.plans.length) {
            ssk.plans.forEach((p: any) => {
                const row: any[] = [];
                row.push({ text: p.nama + ' (' + p.satuan + ')', style: 'td', alignment: 'left' });
                row.push({ text: formatIDR(p.harga), style: 'td', alignment: 'right' });
                months.forEach((m: string) => {
                    row.push({ text: p.vol?.[m] ?? 0, style: 'td', alignment: 'center' });
                    row.push({ text: formatIDR(monthlyAmount(p, m)), style: 'td', alignment: 'right' });
                });
                row.push({ text: formatIDR(totalPerItem(p)), style: 'td', alignment: 'right' });
                // Pastikan jumlah cell sama dengan itemColCount
                while (row.length < itemColCount) {
                    row.push({ text: '', style: 'td', alignment: 'center' });
                }
                body.push(row);
            });
        } else {
            // Baris kosong: hanya satu cell dengan colSpan (tambahkan placeholder)
            const emptyRow: any[] = [{ text: 'Tidak ada item SSH', colSpan: itemColCount, style: 'td', alignment: 'center' }];
            for (let i = 1; i < itemColCount; i++) {
                emptyRow.push({ text: '' });
            }
            body.push(emptyRow);
        }
    });
    // Grand total row: hanya satu cell dengan colSpan (tambahkan placeholder)
    const totalRow: any[] = [
        {
            text: `TOTAL KESELURUHAN | Pagu: ${formatIDR(grand.totalPagu)}`,
            colSpan: itemColCount,
            style: 'th',
            alignment: 'left',
            fillColor: '#f0f0f0',
            fontSize: 10,
            bold: true,
        },
    ];
    for (let i = 1; i < itemColCount; i++) {
        totalRow.push({ text: '' });
    }
    body.push(totalRow);
    return body;
}

// Fungsi utama cetak PDF Evaluasi
function printPdfEvaluasi() {
    const tahun: number = props.tahun;
    const rows: any[] = props.rows;
    const months: string[] = props.months;
    const grand: { totalPagu: number; totalItems: number; totalSisa: number } = props.grand;
    const formatIDR: (n: number) => string = props.formatIDR;
    const monthlyAmount: (p: any, m: string) => number = props.monthlyAmount;
    const totalPerItem: (p: any) => number = props.totalPerItem;

    // Buat header dan body tabel
    const tableHeader = buildTableHeader(months);
    const tableBody = buildTableBody(rows, months, formatIDR, monthlyAmount, totalPerItem, grand);

    // Definisi dokumen PDF
    const docDef = {
        pageOrientation: 'landscape',
        pageSize: 'A3',
        pageMargins: [10, 10, 10, 10],
        content: [
            { text: `Evaluasi Anggaran Tahun ${tahun}`, style: 'header', alignment: 'center', margin: [0, 0, 0, 8] },
            { text: `Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}`, style: 'subheader', alignment: 'right', margin: [0, 0, 0, 8] },
            {
                table: {
                    headerRows: 2,
                    body: [...tableHeader, ...tableBody],
                },
                layout: {
                    hLineWidth: () => 0.5,
                    vLineWidth: () => 0.5,
                    hLineColor: () => '#aaa',
                    vLineColor: () => '#aaa',
                },
            },
        ],
        styles: {
            header: { fontSize: 16, bold: true },
            subheader: { fontSize: 10, italics: true },
            th: { fontSize: 9, bold: true, fillColor: '#f0f0f0', margin: [2, 2, 2, 2] },
            td: { fontSize: 8, margin: [2, 2, 2, 2] },
        },
        defaultStyle: { font: 'Roboto' },
        fonts: {
            Roboto: {
                normal: 'Roboto-Regular.ttf',
                bold: 'Roboto-Medium.ttf',
                italics: 'Roboto-Italic.ttf',
                bolditalics: 'Roboto-MediumItalic.ttf',
            },
        },
    };
    pdfMake.createPdf(docDef as any).open();
}
</script>
<template>
    <button
        @click="printPdfEvaluasi"
        class="inline-flex items-center gap-2 rounded bg-green-600 px-3 py-1 text-xs font-semibold text-white shadow transition hover:bg-green-700"
    >
        <i class="fa fa-print"></i>
        Cetak PDF
    </button>
</template>
