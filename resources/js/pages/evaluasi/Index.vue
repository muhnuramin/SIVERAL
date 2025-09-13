<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, ref as vueRef } from 'vue';
import EvaluasiPdf from './EvaluasiPdf.vue';
// State untuk modal cetak PDF
const showPdfModal = vueRef(false);
const pdfType = vueRef<'bulan' | 'triwulan' | null>(null);
const selectedTriwulan = vueRef<number | null>(null);

function openPdfModal() {
    showPdfModal.value = true;
    pdfType.value = null;
    selectedTriwulan.value = null;
}

function closePdfModal() {
    showPdfModal.value = false;
}

function handlePdfPrint() {
    // Panggil EvaluasiPdf dengan props tambahan
    const type = pdfType.value;
    const triwulan = selectedTriwulan.value;
    // Trigger cetak PDF via ref
    if (type === 'bulan') {
        document.getElementById('evaluasi-pdf-btn-bulan')?.click();
    } else if (type === 'triwulan' && triwulan) {
        document.getElementById('evaluasi-pdf-btn-triwulan-' + triwulan)?.click();
    }
    closePdfModal();
}

const props = defineProps<{
    tahun: number;
    data: {
        ssk: {
            id: number;
            kode: string;
            nama: string;
            pagu: number;
            plans: { plan_id: number | null; item_id: number; nama: string; satuan: string; harga: number; vol: Record<string, number> }[];
        }[];
    };
}>();

const notify = useNotify();
const months: string[] = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];

// Search functionality
const q = ref('');

// Auto-save variables
const isSaving = ref(false);
let saveTimeout: ReturnType<typeof setTimeout> | null = null;

// Check and redirect with year from localStorage on mount
onMounted(() => {
    const raw = window.localStorage.getItem('selected_year');
    const yearFromStorage = raw ? parseInt(raw) : new Date().getFullYear();

    // If current page doesn't have the year param or it's different from localStorage, redirect
    const currentUrl = new URL(window.location.href);
    const currentYear = currentUrl.searchParams.get('year');

    if (!currentYear || parseInt(currentYear) !== yearFromStorage) {
        router.get(
            route('evaluasi.index', { year: yearFromStorage }),
            {},
            {
                preserveState: false,
                replace: true,
            },
        );
    }
});

// Reactive copy for editing volumes
const rows = ref(props.data.ssk.map((s) => ({ ...s, plans: s.plans.map((p) => ({ ...p, vol: { ...p.vol } })) })));

// Filtered rows based on search query
const filteredRows = computed(() => {
    if (!q.value.trim()) {
        return rows.value;
    }

    const query = q.value.toLowerCase().trim();

    return rows.value
        .map((ssk) => {
            // Filter plans based on search query
            const filteredPlans = ssk.plans.filter((plan) => {
                return plan.nama.toLowerCase().includes(query) || plan.satuan.toLowerCase().includes(query) || ssk.nama.toLowerCase().includes(query);
            });

            // Return the sub-kegiatan with filtered plans, or with empty plans if no match
            return {
                ...ssk,
                plans: filteredPlans,
            };
        })
        .filter((ssk) => {
            // Keep sub-kegiatan if it has matching plans OR if the sub-kegiatan name matches
            return ssk.plans.length > 0 || ssk.nama.toLowerCase().includes(query);
        });
});

function formatIDR(n: number) {
    return (n || 0).toLocaleString('id-ID');
}

function totalPerItem(p: any) {
    const sumVol = months.reduce((a, m) => a + (p.vol[m] || 0), 0);
    return sumVol * (p.harga || 0);
}
function monthlyAmount(p: any, m: string) {
    return (p.vol[m] || 0) * (p.harga || 0);
}
function totalPerSub(ssk: any) {
    return ssk.plans.reduce((a: number, p: any) => a + totalPerItem(p), 0);
}
function sisaSub(ssk: any) {
    return (ssk.pagu || 0) - totalPerSub(ssk);
}

// Calculate total amount for a specific month across all plans in a sub-kegiatan
function totalAmountPerMonth(ssk: any, month: string) {
    return ssk.plans.reduce((total: number, p: any) => total + monthlyAmount(p, month), 0);
}

// Calculate grand total per month
function grandTotalAmountPerMonth(month: string) {
    return filteredRows.value.reduce((total: number, ssk: any) => total + totalAmountPerMonth(ssk, month), 0);
}

// Calculate grand total per triwulan
function grandTotalAmountPerTriwulan(q: number) {
    // Q1: jan, feb, mar; Q2: apr, mei, jun; Q3: jul, agu, sep; Q4: okt, nov, des
    const triwulanMap = [
        ['jan', 'feb', 'mar'],
        ['apr', 'mei', 'jun'],
        ['jul', 'agu', 'sep'],
        ['okt', 'nov', 'des'],
    ];
    return triwulanMap[q - 1].reduce((sum, m) => sum + grandTotalAmountPerMonth(m), 0);
}

const grand = computed(() => {
    let totalPagu = 0;
    let totalItems = 0;
    let totalSisa = 0;
    filteredRows.value.forEach((s) => {
        const subTotal = totalPerSub(s);
        totalPagu += s.pagu || 0;
        totalItems += subTotal;
        totalSisa += (s.pagu || 0) - subTotal;
    });
    return { totalPagu, totalItems, totalSisa };
});

function save() {
    if (isSaving.value) return;

    isSaving.value = true;
    const payload: any[] = [];
    rows.value.forEach((s) => {
        s.plans.forEach((p) => {
            // Mapping vol ke format jan_vol, feb_vol, dst
            const volPayload: Record<string, number> = {};
            months.forEach((m) => {
                volPayload[`${m}_vol`] = p.vol[m] || 0;
            });
            payload.push({ sub_sub_kegiatan_id: s.id, item_id: p.item_id, ...volPayload });
        });
    });
    router.post(
        route('evaluasi.bulk-save'),
        { tahun: props.tahun, rows: payload },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
                notify.success('Tersimpan otomatis');
            },
            onError: () => {
                isSaving.value = false;
                notify.error('Gagal menyimpan');
            },
        },
    );
}

// Auto-save with debounce
function autoSave() {
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }
    saveTimeout = setTimeout(() => {
        save();
    }, 1000); // Wait 1 second after user stops typing
}

// Helper function to highlight search text
function highlightText(text: string, query: string): string {
    if (!query.trim()) return text;

    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
}
</script>

<template>
    <Head title="Evaluasi" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Anggaran', href: '#' },
            { title: 'Evaluasi', href: route('evaluasi.index') },
        ]"
    >
        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-green-400/30 to-green-600/20 shadow-lg"
                    >
                        <i class="fa fa-chart-line text-2xl text-green-700"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-green-700">Evaluasi Anggaran {{ tahun }}</h1>
                        <div class="mt-1 text-xs font-medium text-muted-foreground">Kelola evaluasi anggaran per tahun</div>
                    </div>
                </div>
                <div class="relative w-full sm:w-80">
                    <div class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2">
                        <i class="fa fa-search size-5"></i>
                    </div>
                    <input
                        v-model="q"
                        type="text"
                        placeholder="Cari SSH Item..."
                        class="w-full rounded-lg border-2 py-2 pr-3 pl-10 text-sm font-medium text-gray-700 shadow-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-200 focus:outline-none"
                    />
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700">
                <!-- Tombol Cetak PDF -->
                <button
                    @click="openPdfModal"
                    class="inline-flex items-center gap-2 rounded bg-green-600 px-3 py-1 text-xs font-semibold text-white shadow transition hover:bg-green-700"
                >
                    <i class="fa fa-print"></i>
                    Cetak PDF
                </button>
                <!-- EvaluasiPdf untuk per bulan (hidden trigger) -->
                <EvaluasiPdf
                    :tahun="tahun"
                    :rows="filteredRows"
                    :months="months"
                    :grand="grand"
                    :grandTotalAmountPerMonth="grandTotalAmountPerMonth"
                    :formatIDR="formatIDR"
                    :monthlyAmount="monthlyAmount"
                    :totalPerItem="totalPerItem"
                    :sisaSub="sisaSub"
                    :type="'bulan'"
                    style="display: none"
                    id="evaluasi-pdf-btn-bulan"
                />
                <!-- EvaluasiPdf untuk per triwulan (hidden triggers) -->
                <template v-for="q in 4" :key="'pdf-triwulan-' + q">
                    <EvaluasiPdf
                        :tahun="tahun"
                        :rows="filteredRows"
                        :months="months"
                        :grand="grand"
                        :grandTotalAmountPerMonth="grandTotalAmountPerMonth"
                        :formatIDR="formatIDR"
                        :monthlyAmount="monthlyAmount"
                        :totalPerItem="totalPerItem"
                        :sisaSub="sisaSub"
                        :type="'triwulan'"
                        :triwulan="q"
                        style="display: none"
                        :id="'evaluasi-pdf-btn-triwulan-' + q"
                    />
                </template>
                <i class="fa fa-hand-pointer text-lg text-gray-600"></i>

                <div>
                    <div class="font-medium text-gray-800">Tips</div>
                    <div class="text-xs text-gray-600">
                        Untuk scroll tabel ke samping, tahan
                        <span class="mr-1 ml-1 inline-block rounded border bg-gray-100 px-2 py-0.5 text-[11px] font-semibold">Shift</span>
                        lalu gulir roda mouse ke atas atau ke bawah.
                    </div>
                </div>
            </div>
            <!-- Search Results Info -->
            <div v-if="q.trim()" class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-600"></i>
                    <span class="text-sm font-medium text-blue-800">
                        Menampilkan hasil pencarian untuk: <span class="font-bold">"{{ q }}"</span>
                    </span>
                </div>
                <button
                    @click="q = ''"
                    class="flex items-center gap-1 rounded-md bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-200"
                >
                    <i class="fas fa-times"></i>
                    <span>Clear</span>
                </button>
            </div>

            <!-- No Results -->
            <div v-if="q.trim() && filteredRows.length === 0" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <i class="fas fa-search text-3xl text-yellow-600"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800">Tidak Ada Hasil</h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            Tidak ditemukan SSH item yang cocok dengan pencarian <span class="font-bold">"{{ q }}"</span>
                        </p>
                    </div>
                    <button
                        @click="q = ''"
                        class="mt-2 rounded-md bg-yellow-100 px-4 py-2 text-sm font-medium text-yellow-800 transition-colors hover:bg-yellow-200"
                    >
                        Reset Pencarian
                    </button>
                </div>
            </div>
            <div class="overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                <table class="w-full min-w-[1200px] border-collapse text-xs">
                    <thead>
                        <tr class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                            <th
                                rowspan="3"
                                class="sticky left-0 z-20 w-60 min-w-[220px] border border-green-500 bg-gradient-to-r from-green-600 to-green-700 p-2 text-center text-xs font-bold"
                            >
                                Sub Kegiatan & Pagu
                            </th>
                            <th
                                rowspan="3"
                                class="sticky left-[180px] z-20 w-44 min-w-[160px] border border-green-500 bg-gradient-to-r from-green-600 to-green-700 p-2 text-center text-xs font-bold"
                            >
                                SSH Item & Satuan
                            </th>
                            <th
                                rowspan="3"
                                class="sticky left-[340px] z-20 w-28 min-w-[110px] border border-green-500 bg-gradient-to-r from-green-600 to-green-700 p-2 text-center text-xs font-bold"
                            >
                                Harga Satuan
                            </th>
                            <th :colspan="months.length * 2" class="border border-green-500 bg-green-800 p-2 text-center text-xs font-bold">
                                Rencana Bulanan {{ tahun }}
                            </th>
                            <th rowspan="3" class="w-28 border border-green-500 bg-green-700 p-2 text-center text-xs font-bold">Total Item</th>
                            <th rowspan="3" class="w-28 border border-green-500 bg-green-700 p-2 text-center text-xs font-bold">Sisa Anggaran</th>
                        </tr>
                        <tr class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                            <template v-for="m in months" :key="'m1-' + m">
                                <th :colspan="2" class="border border-green-500 bg-green-700 p-1 text-center text-xs font-semibold uppercase">
                                    {{ m }}
                                </th>
                            </template>
                        </tr>
                        <tr class="bg-gradient-to-r from-green-500 to-green-600 text-white">
                            <template v-for="m in months" :key="'m2-' + m">
                                <th class="w-32 border border-green-400 bg-green-500 p-1 text-center text-xs font-medium">Vol</th>
                                <th class="w-32 border border-green-400 bg-green-500 p-1 text-center text-xs font-medium">
                                    Rupiah
                                    <div class="text-[10px] font-normal text-gray-100">(Harga x Vol)</div>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="ssk in filteredRows" :key="ssk.id">
                            <template v-if="ssk.plans.length">
                                <template v-for="(p, idx) in ssk.plans" :key="p.item_id">
                                    <tr class="bg-white transition-colors duration-150 even:bg-gray-50 hover:bg-blue-50">
                                        <td
                                            v-if="idx === 0"
                                            :rowspan="ssk.plans.length"
                                            class="sticky left-0 z-10 w-60 border border-gray-300 bg-gray-50 p-2 align-top text-xs font-semibold text-gray-900"
                                        >
                                            <div class="leading-tight" v-html="highlightText(ssk.nama, q)"></div>
                                            <div class="mt-1 font-mono text-[11px] font-bold text-green-700">Pagu: {{ formatIDR(ssk.pagu) }}</div>
                                        </td>
                                        <td
                                            class="sticky left-[180px] z-10 w-44 border border-gray-300 bg-white p-1.5 text-xs font-medium text-gray-900"
                                        >
                                            <div class="leading-tight" v-html="highlightText(p.nama, q)"></div>
                                            <div class="text-xs text-gray-500">(<span v-html="highlightText(p.satuan, q)"></span>)</div>
                                        </td>
                                        <td
                                            class="sticky left-[340px] z-10 w-28 border border-gray-300 bg-white p-1.5 text-right font-mono text-xs text-gray-900"
                                        >
                                            <div class="text-xs">{{ formatIDR(p.harga) }}</div>
                                        </td>

                                        <template v-for="m in months" :key="p.item_id + m">
                                            <td class="w-64 border border-gray-300 p-1.5 text-center">
                                                <input
                                                    v-model.number="p.vol[m]"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    placeholder="0"
                                                    @blur="autoSave"
                                                    class="w-full max-w-25 min-w-25 rounded border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-200 focus:outline-none"
                                                    style="appearance: none; -moz-appearance: textfield"
                                                />
                                            </td>
                                            <td class="max-w-25 min-w-25 border border-gray-300 p-1.5 text-right font-mono text-xs text-gray-900">
                                                {{ formatIDR(monthlyAmount(p, m)) }}
                                            </td>
                                        </template>
                                        <td class="w-28 border border-gray-300 p-1.5 text-right font-mono text-xs font-semibold text-gray-900">
                                            {{ formatIDR(totalPerItem(p)) }}
                                        </td>
                                        <td
                                            v-if="idx === 0"
                                            :rowspan="ssk.plans.length"
                                            class="w-28 border border-gray-300 p-1.5 text-right font-mono text-xs font-semibold"
                                            :class="sisaSub(ssk) < 0 ? 'bg-red-50 text-red-600' : 'text-gray-900'"
                                        >
                                            {{ formatIDR(sisaSub(ssk)) }}
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else class="bg-gray-100">
                                <td class="sticky left-0 z-10 border border-gray-300 bg-gray-100 p-2 text-xs font-semibold text-gray-600">
                                    <span v-html="highlightText(ssk.nama, q)"></span>
                                    <div class="mt-1 font-mono text-[11px] font-bold text-green-700">Pagu: {{ formatIDR(ssk.pagu) }}</div>
                                </td>
                                <td :colspan="months.length * 2 + 4" class="border border-gray-300 p-4 text-center text-xs text-gray-500 italic">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.966-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                                            ></path>
                                        </svg>
                                        Tidak ada item SSH
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <!-- Grand Total: Jumlah Pengeluaran kas Per Bulan dan Per Triwulan -->
                        <tr class="border-t-4 border-green-600 bg-gradient-to-r from-gray-100 to-gray-200 font-bold text-gray-800">
                            <td
                                class="sticky left-0 z-10 border border-gray-400 bg-gradient-to-r from-gray-100 to-gray-200 p-2 text-center text-xs font-bold tracking-wide"
                                :colspan="3"
                            >
                                Jumlah Pengeluaran kas Per Bulan
                            </td>
                            <template v-for="m in months" :key="'grand-total-bulan-' + m">
                                <td class="w-32 border border-gray-400 p-1.5 text-right font-mono text-xs font-bold" :colspan="2">
                                    {{ formatIDR(grandTotalAmountPerMonth(m)) }}
                                </td>
                            </template>
                            <td class="border border-gray-400 p-2 text-right font-mono text-xs font-bold" :colspan="2"></td>
                        </tr>
                        <tr class="border-t-2 border-green-400 bg-gradient-to-r from-gray-100 to-gray-200 font-bold text-gray-800">
                            <td
                                class="sticky left-0 z-10 border border-gray-400 bg-gradient-to-r from-gray-100 to-gray-200 p-2 text-center text-xs font-bold tracking-wide"
                                :colspan="3"
                            >
                                Jumlah Pengeluaran kas Per Triwulan
                            </td>
                            <td class="border border-gray-400 p-1.5 text-center font-mono text-xs font-bold text-gray-800" :colspan="6">
                                Triwulan 1: {{ formatIDR(grandTotalAmountPerTriwulan(1)) }}
                            </td>
                            <td class="border border-gray-400 p-1.5 text-center font-mono text-xs font-bold text-gray-800" :colspan="6">
                                Triwulan 2: {{ formatIDR(grandTotalAmountPerTriwulan(2)) }}
                            </td>
                            <td class="border border-gray-400 p-1.5 text-center font-mono text-xs font-bold text-gray-800" :colspan="6">
                                Triwulan 3: {{ formatIDR(grandTotalAmountPerTriwulan(3)) }}
                            </td>
                            <td class="border border-gray-400 p-1.5 text-center font-mono text-xs font-bold text-gray-800" :colspan="6">
                                Triwulan 4: {{ formatIDR(grandTotalAmountPerTriwulan(4)) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <ConfirmModal v-if="showPdfModal" :open="showPdfModal" title="Cetak PDF Evaluasi" :hideButton="true" @close="closePdfModal">
            <div class="mb-2 flex flex-col gap-3">
                <button
                    @click="pdfType = 'bulan'"
                    :class="pdfType === 'bulan' ? 'bg-green-600 text-white' : 'bg-gray-100'"
                    class="rounded px-3 py-2 font-semibold"
                >
                    Jumlah Pengeluaran kas Keseluruhan
                </button>
                <button
                    @click="pdfType = 'triwulan'"
                    :class="pdfType === 'triwulan' ? 'bg-green-600 text-white' : 'bg-gray-100'"
                    class="rounded px-3 py-2 font-semibold"
                >
                    Jumlah Pengeluaran kas Per Triwulan
                </button>
            </div>
            <div v-if="pdfType === 'triwulan'" class="mt-2 flex flex-col gap-2">
                <div class="mb-2 font-semibold">Pilih Triwulan:</div>
                <div class="flex gap-2">
                    <button
                        v-for="q in 4"
                        :key="'triwulan-btn-' + q"
                        @click="selectedTriwulan = q"
                        :class="selectedTriwulan === q ? 'bg-green-500 text-white' : 'bg-gray-200'"
                        class="rounded px-3 py-1 font-semibold"
                    >
                        Triwulan {{ q }}
                    </button>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button
                    @click="handlePdfPrint"
                    :disabled="!pdfType || (pdfType === 'triwulan' && !selectedTriwulan)"
                    class="rounded bg-green-600 px-3 py-1 font-semibold text-white disabled:opacity-50"
                >
                    Cetak
                </button>
            </div>
            <template #footer><div style="display: none"></div></template>
        </ConfirmModal>
    </AppLayout>
</template>
