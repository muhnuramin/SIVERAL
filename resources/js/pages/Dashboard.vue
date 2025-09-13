<script setup lang="ts">
import YearPickerModal from '@/components/YearPickerModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArcElement, BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, PieController, Tooltip } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
const showYearModal = ref(false);

const props = defineProps<{
    year?: number;
    cards?: { totalAnggaranBlud: number; totalDigunakan: number; sisa: number };
    series?: { months: string[]; evaluasi: number[]; pelaporan: number[] };
    subKegiatanPagu?: { labels: string[]; values: number[]; totalPagu: number; totalAnggaran: number; lebihan: number; sisa: number };
}>();

const monthLabels = computed(() =>
    (props.series?.months ?? ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des']).map((m) => m.toUpperCase()),
);

function formatIDR(n: number) {
    try {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
    } catch (_) {
        return `Rp ${Math.round(n || 0).toLocaleString('id-ID')}`;
    }
}

const evalSeries = computed(() => props.series?.evaluasi ?? Array(12).fill(0));
const reportSeries = computed(() => props.series?.pelaporan ?? Array(12).fill(0));
const pieLabels = computed(() => props.subKegiatanPagu?.labels ?? []);
const pieValues = computed(() => (props.subKegiatanPagu?.values ?? []).map((v) => Number(v || 0)));
const pieHasData = computed(() => pieLabels.value.length > 0 && pieValues.value.some((v) => v > 0));

// Chart.js setup
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, ArcElement, PieController);
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;
const pieCanvas = ref<HTMLCanvasElement | null>(null);
let pieInstance: Chart | null = null;

function renderChart() {
    if (!chartCanvas.value) return;
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: monthLabels.value,
            datasets: [
                {
                    label: 'Evaluasi',
                    data: evalSeries.value,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                    barPercentage: 0.9,
                    categoryPercentage: 0.6,
                },
                {
                    label: 'Pelaporan',
                    data: reportSeries.value,
                    backgroundColor: '#f59e0b',
                    borderRadius: 4,
                    barPercentage: 0.9,
                    categoryPercentage: 0.6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: '#64748b', boxWidth: 12, boxHeight: 12, usePointStyle: true },
                },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const val = ctx.parsed.y || 0;
                            return `${ctx.dataset.label}: ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 10 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        color: '#94a3b8',
                        callback(value) {
                            try {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(
                                    Number(value),
                                );
                            } catch {
                                return value as any;
                            }
                        },
                        font: { size: 10 },
                    },
                },
            },
        },
    });
}

onMounted(() => {
    try {
        const y = window.localStorage.getItem('selected_year');
        if (!y) showYearModal.value = true;
        // If a saved year exists and differs from server-provided year, navigate to it
        const saved = Number(y);
        if (saved && props.year && saved !== props.year) {
            router.get(route('dashboard'), { year: saved }, { preserveScroll: true, replace: true });
        }
    } catch (e) {
        showYearModal.value = true;
    }
    // Render chart after mount
    renderChart();
});

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
    if (pieInstance) {
        pieInstance.destroy();
        pieInstance = null;
    }
});

// Re-render chart when incoming data or labels change
watch(
    () => ({ eval: evalSeries.value, rep: reportSeries.value, labels: monthLabels.value }),
    () => {
        // Defer slightly to allow DOM/layout settle in SPA updates
        requestAnimationFrame(() => renderChart());
    },
    { deep: true },
);

function onYearSelect(y: number) {
    try {
        window.localStorage.setItem('selected_year', String(y));
    } catch (e) {}
    showYearModal.value = false;
    router.get(route('dashboard'), { year: y }, { preserveScroll: false });
}

// Pie chart for Pagu per Sub Kegiatan
function renderPie() {
    if (!pieCanvas.value) return;
    if (pieInstance) {
        pieInstance.destroy();
        pieInstance = null;
    }
    const labels = pieLabels.value;
    const values = pieValues.value;
    if (!labels.length || !values.length || !values.some((v) => v > 0)) return;
    const colors = [
        '#0ea5e9',
        '#22c55e',
        '#f59e0b',
        '#ef4444',
        '#a855f7',
        '#14b8a6',
        '#e11d48',
        '#84cc16',
        '#06b6d4',
        '#f97316',
        '#3b82f6',
        '#10b981',
        '#eab308',
        '#fb7185',
        '#8b5cf6',
        '#34d399',
        '#f43f5e',
        '#65a30d',
        '#0891b2',
        '#ea580c',
    ];
    pieInstance = new Chart(pieCanvas.value, {
        type: 'pie',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: labels.map((_, i) => colors[i % colors.length]),
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const val = ctx.parsed || 0;
                            const label = ctx.label || '';
                            return `${label}: ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)}`;
                        },
                    },
                },
            },
        },
    });
}

onMounted(() => {
    // also render pie once mounted
    renderPie();
});

watch([pieLabels, pieValues], () => requestAnimationFrame(() => renderPie()));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <YearPickerModal v-if="showYearModal" @select="onYearSelect" />
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- KPI Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-800">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                        <i class="fa-solid fa-sack-dollar text-emerald-600"></i>
                        Total Anggaran BLUD
                    </div>
                    <div class="mt-2 text-2xl font-extrabold text-emerald-700">{{ formatIDR(props.cards?.totalAnggaranBlud || 0) }}</div>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-800">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                        <i class="fa-solid fa-wallet text-amber-500"></i>
                        Total Digunakan
                    </div>
                    <div class="mt-2 text-2xl font-extrabold text-amber-600">{{ formatIDR(props.cards?.totalDigunakan || 0) }}</div>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-800">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                        <i class="fa-solid fa-piggy-bank text-sky-600"></i>
                        Sisa
                    </div>
                    <div class="mt-2 text-2xl font-extrabold text-sky-700">{{ formatIDR(props.cards?.sisa || 0) }}</div>
                </div>
            </div>

            <!-- Comparison Chart -->
            <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-800">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <i class="fa-solid fa-chart-column text-slate-500"></i>
                            Perbandingan Evaluasi vs Pelaporan
                        </div>
                        <div class="text-xs text-slate-500">Jumlah Pengeluaran Kas Per Bulan • {{ props.year ?? new Date().getFullYear() }}</div>
                    </div>
                    <div class="hidden items-center gap-4 md:flex">
                        <span class="flex items-center gap-2 text-xs text-slate-500">
                            <i class="fa-solid fa-square text-emerald-500"></i> Evaluasi
                        </span>
                        <span class="flex items-center gap-2 text-xs text-slate-500">
                            <i class="fa-solid fa-square text-amber-500"></i> Pelaporan
                        </span>
                    </div>
                </div>
                <div class="w-full overflow-x-auto">
                    <div class="h-[420px] w-full min-w-[960px]">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pie: Pagu per Sub Kegiatan -->
            <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-800">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <i class="fa-solid fa-chart-pie text-slate-500"></i>
                            Pagu per Sub Kegiatan
                        </div>
                        <div class="text-xs text-slate-500">
                            Total Pagu: {{ formatIDR(props.subKegiatanPagu?.totalPagu || 0) }} • Total Anggaran BLUD:
                            {{ formatIDR(props.subKegiatanPagu?.totalAnggaran || 0) }}
                        </div>
                    </div>
                    <div class="text-xs font-medium" :class="(props.subKegiatanPagu?.lebihan || 0) > 0 ? 'text-red-600' : 'text-emerald-600'">
                        <template v-if="(props.subKegiatanPagu?.lebihan || 0) > 0">
                            Lebihan: {{ formatIDR(props.subKegiatanPagu?.lebihan || 0) }}
                        </template>
                        <template v-else> Sisa: {{ formatIDR(props.subKegiatanPagu?.sisa || 0) }} </template>
                    </div>
                </div>
                <div class="w-full">
                    <div v-if="pieHasData" class="h-[420px] w-full">
                        <canvas ref="pieCanvas"></canvas>
                    </div>
                    <div v-else class="flex h-[260px] w-full items-center justify-center rounded-md border border-dashed text-sm text-slate-500">
                        Tidak ada data pagu sub-sub kegiatan untuk tahun ini.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
