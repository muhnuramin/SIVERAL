<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    tahun: number;
    data: {
        ssk: {
            id: number;
            kode: string;
            nama: string;
            pagu: number;
            plans: {
                item_id: number;
                nama: string;
                satuan: string;
                harga: number;
                vol: Record<string, number>;
                rupiah: Record<string, number>;
            }[];
        }[];
    };
}>();

const page = usePage();
const months: string[] = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];

// Reactive copy for editing
const rows = ref(props.data.ssk.map((s) => ({ ...s, plans: s.plans.map((p) => ({ ...p, vol: { ...p.vol }, rupiah: { ...p.rupiah } })) })));

function formatIDR(n: number) {
    return (n || 0).toLocaleString('id-ID');
}

function totalPerItem(p: any) {
    return months.reduce((a, m) => a + (p.rupiah[m] || 0), 0);
}

function totalPerSub(ssk: any) {
    return ssk.plans.reduce((a: number, p: any) => a + totalPerItem(p), 0);
}

function sisaSub(ssk: any) {
    return (ssk.pagu || 0) - totalPerSub(ssk);
}

function grandTotalAmountPerMonth(month: string) {
    let sum = 0;
    rows.value.forEach((ssk) => {
        ssk.plans.forEach((p: any) => {
            sum += p.rupiah[month] || 0;
        });
    });
    return sum;
}

function grandTotalAmountPerTriwulan(q: number) {
    const triwulanMap = [
        ['jan', 'feb', 'mar'],
        ['apr', 'mei', 'jun'],
        ['jul', 'agu', 'sep'],
        ['okt', 'nov', 'des'],
    ];
    return triwulanMap[q - 1].reduce((sum, m) => sum + grandTotalAmountPerMonth(m), 0);
}

const form = useForm<{ data: Array<{ item_id: number; bulan: string; vol: number; rupiah: number }> }>({ data: [] });

function submit() {
    const payload: Array<{ item_id: number; bulan: string; vol: number; rupiah: number }> = [];
    rows.value.forEach((ssk) => {
        ssk.plans.forEach((p: any) => {
            months.forEach((m) => {
                payload.push({ item_id: p.item_id, bulan: m, vol: Number(p.vol[m] || 0), rupiah: Number(p.rupiah[m] || 0) });
            });
        });
    });
    form.transform(() => ({ data: payload })).post(route('pelaporan.store'), { preserveScroll: true });
}

const flashSuccess = computed(() => (page.props as any)?.flash?.success as string | undefined);
</script>

<template>
    <Head title="Pelaporan" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Anggaran', href: '#' },
            { title: 'Pelaporan', href: route('pelaporan.index') },
        ]"
    >
        <div class="flex flex-col gap-4 p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-green-400/30 to-green-600/20 shadow-lg"
                    >
                        <i class="fa fa-file-invoice-dollar text-2xl text-green-700"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-green-700">Pelaporan Anggaran {{ props.tahun }}</h1>
                        <div class="mt-1 text-xs font-medium text-muted-foreground">Input volume dan rupiah per bulan</div>
                    </div>
                </div>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded bg-green-600 px-3 py-2 text-xs font-semibold text-white shadow transition hover:bg-green-700 disabled:opacity-50"
                >
                    <i class="fa fa-save"></i>
                    Simpan
                </button>
            </div>

            <div v-if="flashSuccess" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                {{ flashSuccess }}
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
                            <th :colspan="months.length * 2" class="border border-green-500 bg-green-800 p-2 text-center text-xs font-bold">
                                Realisasi Bulanan {{ props.tahun }}
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
                                <th class="w-40 border border-green-400 bg-green-500 p-1.5 text-center text-sm font-medium">Vol</th>
                                <th class="w-40 border border-green-400 bg-green-500 p-1.5 text-center text-sm font-medium">Rupiah</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="ssk in rows" :key="ssk.id">
                            <template v-if="ssk.plans.length">
                                <template v-for="(p, idx) in ssk.plans" :key="p.item_id">
                                    <tr class="bg-white transition-colors duration-150 even:bg-gray-50 hover:bg-green-50">
                                        <td
                                            v-if="idx === 0"
                                            :rowspan="ssk.plans.length"
                                            class="sticky left-0 z-10 w-60 border border-gray-300 bg-gray-50 p-2 align-top text-xs font-semibold text-gray-900"
                                        >
                                            <div class="leading-tight">{{ ssk.nama }}</div>
                                            <div class="mt-1 font-mono text-[11px] font-bold text-green-700">Pagu: {{ formatIDR(ssk.pagu) }}</div>
                                        </td>
                                        <td
                                            class="sticky left-[180px] z-10 w-44 border border-gray-300 bg-white p-1.5 text-xs font-medium text-gray-900"
                                        >
                                            <div class="leading-tight">{{ p.nama }}</div>
                                            <div class="text-xs text-gray-500">({{ p.satuan }})</div>
                                        </td>

                                        <template v-for="m in months" :key="p.item_id + '-' + m">
                                            <td class="w-40 border border-gray-300 p-1.5 text-center">
                                                <input
                                                    v-model.number="p.vol[m]"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    class="w-full max-w-30 min-w-30 rounded border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-200 focus:outline-none"
                                                />
                                            </td>
                                            <td class="w-40 border border-gray-300 p-1.5 text-right">
                                                <input
                                                    v-model.number="p.rupiah[m]"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    class="w-full max-w-30 min-w-30 rounded border border-gray-200 bg-white px-2 py-1.5 text-right font-mono text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-200 focus:outline-none"
                                                />
                                            </td>
                                        </template>
                                        <td class="w-28 border border-gray-300 p-1.5 text-right font-mono text-xs font-semibold text-gray-900">
                                            {{ formatIDR(totalPerItem(p)) }}
                                        </td>
                                        <td
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
                                    {{ ssk.nama }}
                                    <div class="mt-1 font-mono text-[11px] font-bold text-green-700">Pagu: {{ formatIDR(ssk.pagu) }}</div>
                                </td>
                                <td :colspan="months.length * 2 + 3" class="border border-gray-300 p-4 text-center text-xs text-gray-500 italic">
                                    Tidak ada item SSH
                                </td>
                            </tr>
                        </template>
                        <!-- Grand Total: Jumlah Pengeluaran kas Per Bulan dan Per Triwulan -->
                        <tr class="border-t-4 border-green-600 bg-gradient-to-r from-gray-100 to-gray-200 font-bold text-gray-800">
                            <td
                                class="sticky left-0 z-10 border border-gray-400 bg-gradient-to-r from-gray-100 to-gray-200 p-2 text-center text-xs font-bold tracking-wide"
                                :colspan="2"
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
                                :colspan="2"
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
    </AppLayout>
</template>
