<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatIDR, useThousandSeparated } from '@/utils/currency';
import { Head, router } from '@inertiajs/vue3';
import { CircleDollarSign, Receipt, Search, Wallet } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface SubSubKegiatan {
    id: number;
    kode: string;
    nama: string;
    pagus?: {
        tahun: number;
        pagu: number;
        sumber_anggaran_id?: number | null;
        verified_by?: number | null;
        verified_at?: string | null;
        verified_by_user?: { id: number; name: string } | null;
    }[];
    pics?: UserLite[];
}
interface SubKegiatan {
    id: number;
    kode: string;
    nama: string;
    sub_sub_kegiatans: SubSubKegiatan[];
}
interface Kegiatan {
    id: number;
    kode: string | null;
    nama: string;
    sub_kegiatans: SubKegiatan[];
}
interface Program {
    id: number;
    kode: string;
    nama: string;
    kegiatans: Kegiatan[];
}

interface UserLite {
    id: number;
    name: string;
    email?: string;
}

type SubSubRow = {
    subSubKode: string;
    subSubNama: string;
    pagu?: number | null;
    sumberDanaId?: number | null;
    picIds?: number[];
    isValidated?: boolean;
    verifiedBy?: string | null;
    verifiedAt?: string | null;
};
type SubGroup = { subKegiatanKode: string; subKegiatanNama: string; rows: SubSubRow[] };
type Activity = { kegiatanKode: string; kegiatanNama: string; subGroups: SubGroup[] };
type Group = { programKode: string; programNama: string; activities: Activity[] };

interface EditForm {
    programKode: string;
    programNama: string;
    kegiatanKode: string;
    kegiatanNama: string;
    subKegiatanKode: string;
    subKegiatanNama: string;
    subSubKode: string;
    subSubNama?: string;
    sumberDanaId: number | null;
    pagu: number | '';
    picIds: number[];
}

const props = defineProps<{
    programs: Program[];
    year?: number;
    users?: UserLite[];
    fundingSources?: { id: number; nama: string; kode?: string | null }[];
    totalAnggaranBlud?: number;
    totalUsedPagu?: number; // new prop from backend sum(sub_sub_kegiatan_pagus)
}>();
const notify = useNotify();
// selected year reactive from localStorage; falls back to server-provided year
const selectedYear = ref<number>(props.year ?? new Date().getFullYear());
onMounted(() => {
    const raw = window.localStorage.getItem('selected_year');
    const parsed = raw ? parseInt(raw, 10) : undefined;
    if (Number.isFinite(parsed) && parsed !== selectedYear.value) {
        selectedYear.value = parsed as number;
    }
    // removed localStorage preload for usedTotal; authoritative from backend now
    window.addEventListener('storage', (e) => {
        if (e.key === 'selected_year') {
            const v = e.newValue ? parseInt(e.newValue, 10) : undefined;
            if (Number.isFinite(v) && v !== selectedYear.value) {
                selectedYear.value = v as number;
            }
        }
    });
});

// When local selectedYear differs from server year, fetch data for that year
watch(
    () => selectedYear.value,
    (yr) => {
        // Update total anggaran form tahun when year changes
        totalAnggaranForm.value.tahun = yr;

        if (props.year !== yr) {
            router.get(route('anggaran.perencanaan', { year: yr } as any), {}, { preserveScroll: true, preserveState: true, replace: true });
        }
    },
    { immediate: false },
);

const q = ref('');

// zebra background per Program group
function groupBg(i: number) {
    return i % 2 === 0 ? 'bg-background/70' : 'bg-muted/40';
}

// Build groups per Program, each with Activity and SubKegiatan headers, then sub-sub detail rows
const groups = computed<Group[]>(() => {
    const out: Group[] = [];
    for (const p of props.programs) {
        const activitiesMap = new Map<string, Activity>();
        const kegiatans = p.kegiatans && p.kegiatans.length ? p.kegiatans : [null as unknown as Kegiatan];
        for (const k of kegiatans) {
            const key = `${k?.kode ?? ''}|${k?.nama ?? ''}`;
            if (!activitiesMap.has(key)) {
                activitiesMap.set(key, { kegiatanKode: k?.kode ? String(k.kode) : '', kegiatanNama: k?.nama ?? '', subGroups: [] });
            }
            const act = activitiesMap.get(key)!;

            const subs = k?.sub_kegiatans && k.sub_kegiatans.length ? k.sub_kegiatans : [null as unknown as SubKegiatan];
            for (const s of subs) {
                const sg: SubGroup = {
                    subKegiatanKode: s?.kode ?? '',
                    subKegiatanNama: s?.nama ?? '',
                    rows: [],
                };
                const subsubs = s?.sub_sub_kegiatans && s.sub_sub_kegiatans.length ? s.sub_sub_kegiatans : [null as unknown as SubSubKegiatan];
                for (const x of subsubs) {
                    let pagu: number | null | undefined = null;
                    let sumberDanaId: number | null = null;
                    let picIds: number[] = [];
                    let isValidated = false;
                    let verifiedBy: string | null = null;
                    let verifiedAt: string | null = null;

                    const xs: any = x as any;
                    const arr = Array.isArray(xs?.pagus) ? xs.pagus : [];
                    if (arr.length) {
                        const found = arr.find((p: any) => p && Number(p.tahun) === Number(selectedYear.value));
                        if (found) {
                            pagu = found.pagu ?? null;
                            sumberDanaId = found.sumber_anggaran_id ?? null;
                            picIds = Array.isArray(found.pics) ? found.pics.map((pic: any) => pic.id) : [];
                            isValidated = !!(found.verified_by && found.verified_at);
                            verifiedBy = found.verified_by_user?.name || null;
                            verifiedAt = found.verified_at || null;
                        } else {
                            pagu = arr[0]?.pagu ?? null;
                            sumberDanaId = arr[0]?.sumber_anggaran_id ?? null;
                            picIds = Array.isArray(arr[0]?.pics) ? arr[0].pics.map((pic: any) => pic.id) : [];
                        }
                    }

                    sg.rows.push({
                        subSubKode: x?.kode ?? '',
                        subSubNama: x?.nama ?? '',
                        pagu,
                        sumberDanaId,
                        picIds,
                        isValidated,
                        verifiedBy,
                        verifiedAt,
                    });
                }
                // sort sub-sub rows
                sg.rows.sort((r1, r2) => r1.subSubKode.localeCompare(r2.subSubKode));
                act.subGroups.push(sg);
            }
        }

        // Sort activities and their subgroups
        const activities = Array.from(activitiesMap.values())
            .map((a) => ({ ...a, subGroups: a.subGroups.sort((s1, s2) => s1.subKegiatanKode.localeCompare(s2.subKegiatanKode)) }))
            .sort((a, b) => a.kegiatanKode.localeCompare(b.kegiatanKode));

        out.push({ programKode: p.kode, programNama: p.nama, activities });
    }
    return out.sort((a, b) => a.programKode.localeCompare(b.programKode));
});

// Filter: if program matches, keep all activities; else filter by kegiatan or subkegiatan/sub-sub
const filteredGroups = computed<Group[]>(() => {
    const term = q.value.trim().toLowerCase();
    if (!term) return groups.value;
    const res: Group[] = [];
    for (const g of groups.value) {
        const progMatch = (g.programKode + ' ' + g.programNama).toLowerCase().includes(term);
        const activities = progMatch
            ? g.activities
            : (g.activities
                  .map((a) => {
                      const actMatch = (a.kegiatanKode + ' ' + a.kegiatanNama).toLowerCase().includes(term);
                      const subGroups = actMatch
                          ? a.subGroups
                          : (a.subGroups
                                .map((sg) => {
                                    const sgMatch = (sg.subKegiatanKode + ' ' + sg.subKegiatanNama).toLowerCase().includes(term);
                                    const rows = sgMatch
                                        ? sg.rows
                                        : sg.rows.filter((r) => (r.subSubKode + ' ' + r.subSubNama).toLowerCase().includes(term));
                                    return rows.length ? { ...sg, rows } : null;
                                })
                                .filter(Boolean) as SubGroup[]);
                      return subGroups.length ? { ...a, subGroups } : null;
                  })
                  .filter(Boolean) as Activity[]);
        if (activities.length) res.push({ ...g, activities });
    }
    return res;
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Anggaran', href: '#' },
    { title: 'Perencanaan', href: route('anggaran.perencanaan') },
];

// Totals summary (Anggaran BLUD) from backend aggregate
const totalAnggaran = computed<number>(() => props.totalAnggaranBlud ?? 0);
const usedTotal = computed<number>(() => props.totalUsedPagu ?? 0);
const sisaTotal = computed<number>(() => totalAnggaran.value - usedTotal.value);

// Modal state & handlers
const showConfirm = ref(false);
const pendingRow = ref<SubSubRow | null>(null);
function openConfirm(row: SubSubRow) {
    pendingRow.value = row;
    showConfirm.value = true;
}
function closeConfirm() {
    showConfirm.value = false;
    pendingRow.value = null;
}
function confirmDeactivate() {
    // TODO: send deactivate request
    closeConfirm();
}

// Edit modal state & handlers
const showEdit = ref(false);
const editTargetRow = ref<SubSubRow | null>(null);
const editForm = ref<EditForm>({
    programKode: '',
    programNama: '',
    kegiatanKode: '',
    kegiatanNama: '',
    subKegiatanKode: '',
    subKegiatanNama: '',
    subSubKode: '',
    sumberDanaId: null,
    pagu: '',
    picIds: [],
});

const editSubSubOptions = ref<SubSubRow[]>([]);

// Add Sub-Sub Kegiatan modal state & handlers
const showAdd = ref(false);
const addForm = ref<EditForm>({
    programKode: '',
    programNama: '',
    kegiatanKode: '',
    kegiatanNama: '',
    subKegiatanKode: '',
    subKegiatanNama: '',
    subSubKode: '',
    subSubNama: '',
    sumberDanaId: null,
    pagu: '',
    picIds: [],
});

// Total Anggaran BLUD modal state & handlers
const showTotalAnggaran = ref(false);
const totalAnggaranForm = ref({
    tahun: selectedYear.value,
    totalAnggaran: 0,
});

function openAdd(g: Group, a: Activity, sg: SubGroup) {
    addForm.value = {
        programKode: g.programKode,
        programNama: g.programNama,
        kegiatanKode: a.kegiatanKode,
        kegiatanNama: a.kegiatanNama,
        subKegiatanKode: sg.subKegiatanKode,
        subKegiatanNama: sg.subKegiatanNama,
        subSubKode: `${sg.subKegiatanKode}.`, // Langsung set dengan titik
        subSubNama: '',
        sumberDanaId: null,
        pagu: '',
        picIds: [],
    };
    showAdd.value = true;
}

function closeAdd() {
    showAdd.value = false;
}

function openTotalAnggaran() {
    totalAnggaranForm.value = {
        tahun: selectedYear.value,
        totalAnggaran: totalAnggaran.value,
    };
    showTotalAnggaran.value = true;
}

function closeTotalAnggaran() {
    showTotalAnggaran.value = false;
}

function saveTotalAnggaran() {
    const payload = {
        tahun: totalAnggaranForm.value.tahun,
        total_anggaran: totalAnggaranForm.value.totalAnggaran,
    };

    router.post(route('anggaran.perencanaan.update-total-anggaran'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            notify.success('Total Anggaran BLUD berhasil diupdate');
            closeTotalAnggaran();
        },
        onError: () => {
            notify.error('Gagal update Total Anggaran BLUD. Silakan coba lagi.');
        },
    });
}

function saveAdd() {
    const payload: any = {
        sub_kegiatan_kode: addForm.value.subKegiatanKode,
        kode: addForm.value.subSubKode,
        nama: addForm.value.subSubNama || 'Nama Sub-Sub Kegiatan',
    };

    router.post(route('anggaran.perencanaan.store-sub-sub-simple'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            notify.success('Sub-Sub Kegiatan berhasil ditambahkan');
            closeAdd();
        },
        onError: () => {
            notify.error('Gagal menambah Sub-Sub Kegiatan. Silakan coba lagi.');
        },
    });
}

function openEdit(g: Group, a: Activity, sg: SubGroup, r: SubSubRow) {
    editTargetRow.value = r;
    editSubSubOptions.value = [...sg.rows];
    editForm.value = {
        programKode: g.programKode,
        programNama: g.programNama,
        kegiatanKode: a.kegiatanKode,
        kegiatanNama: a.kegiatanNama,
        subKegiatanKode: sg.subKegiatanKode,
        subKegiatanNama: sg.subKegiatanNama,
        subSubKode: r.subSubKode,
        subSubNama: r.subSubNama,
        sumberDanaId: r.sumberDanaId || null,
        pagu: typeof r.pagu === 'number' ? r.pagu : '',
        picIds: r.picIds || [],
    };
    showEdit.value = true;
}

function closeEdit() {
    showEdit.value = false;
    editTargetRow.value = null;
}
function saveEdit() {
    if (!editTargetRow.value) {
        closeEdit();
        return;
    }

    const payload: any = {
        sub_sub_kode: editForm.value.subSubKode,
        tahun: selectedYear.value,
        pagu: typeof editForm.value.pagu === 'number' ? editForm.value.pagu : parseFloat(String(editForm.value.pagu).replace(/[^0-9]/g, '')),
        sumber_anggaran_id: Number.isFinite(editForm.value.sumberDanaId as any) ? Number(editForm.value.sumberDanaId) : null,
        pic_user_ids: (editForm.value.picIds || [])
            .map((id) => (typeof id === 'string' ? parseInt(id as any, 10) : id))
            .filter((id) => Number.isInteger(id as any)),
    };

    router.post(route('anggaran.perencanaan.update'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            // apply local optimistic update
            notify.success('Pagu berhasil disimpan');
            const n = typeof editForm.value.pagu === 'number' ? editForm.value.pagu : parseFloat(String(editForm.value.pagu).replace(/[^0-9]/g, ''));
            if (Number.isFinite(n)) {
                editTargetRow.value!.pagu = n;
            }
            closeEdit();
        },
        onError: () => {
            notify.error('Gagal menyimpan pagu. Silakan coba lagi.');
        },
    });
}

// Currency formatting for Pagu input (Rupiah)
const paguText = useThousandSeparated(
    computed({
        get: () => editForm.value.pagu,
        set: (v: any) => {
            editForm.value.pagu = v;
        },
    }),
);

// Currency formatting for Total Anggaran BLUD input (Rupiah)
const totalAnggaranText = useThousandSeparated(
    computed({
        get: () => totalAnggaranForm.value.totalAnggaran,
        set: (v: any) => {
            totalAnggaranForm.value.totalAnggaran = v;
        },
    }),
);

// Navigate to Rekening detail for a row
function goRekeningDetail(row: SubSubRow) {
    // Send SSK (Sub-Sub Kegiatan kode) so backend can resolve proper rekening (akun) heads
    // Ensure year from localStorage (selected_year) is passed
    let yr = selectedYear.value;
    try {
        const raw = window.localStorage.getItem('selected_year');
        const parsed = raw ? parseInt(raw, 10) : undefined;
        if (Number.isFinite(parsed)) yr = parsed as number;
    } catch {}
    router.get(route('anggaran.rekening.show', { ssk: row.subSubKode, year: yr } as any));
}
function onRowClick(g: Group, a: Activity, sg: SubGroup, row: SubSubRow) {
    console.log(row);
    if (row.pagu != null) {
        // persist minimal context to localStorage for RekeningDetail page to read
        const context = {
            program: { kode: g.programKode, nama: g.programNama },
            kegiatan: { kode: a.kegiatanKode, nama: a.kegiatanNama },
            sub_kegiatan: { kode: sg.subKegiatanKode, nama: sg.subKegiatanNama },
            sub_sub_kegiatan: {
                kode: row.subSubKode,
                nama: row.subSubNama,
                verified_at: row.verifiedAt || null,
            },
        };
        try {
            window.localStorage.setItem('rekening_context', JSON.stringify(context));
        } catch {}
        goRekeningDetail(row);
    }
}

// Validation functions
function validateSubSub(row: SubSubRow) {
    // Check if required fields are set first
    const validationErrors = [];

    // Check if pagu is set
    if (!row.pagu || row.pagu <= 0) {
        validationErrors.push('Pagu belum diset');
    }
    if (validationErrors.length > 0) {
        notify.warn(`Tidak dapat memvalidasi: ${validationErrors.join(', ')}. Silakan lengkapi data terlebih dahulu.`);
        return;
    }

    const payload = {
        sub_sub_kode: row.subSubKode,
        tahun: selectedYear.value,
        action: 'validate',
    };

    router.post(route('anggaran.perencanaan.validate'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            // Update local state optimistically
            notify.success('Sub-Sub Kegiatan berhasil divalidasi');
            row.isValidated = true;
            row.verifiedBy = 'Current User'; // You might want to get this from auth context
            row.verifiedAt = new Date().toISOString();
        },
        onError: (errors) => {
            // Handle specific validation errors from backend
            if (errors && typeof errors === 'object') {
                const errorMessages = [];
                if (errors.sumber_anggaran) {
                    errorMessages.push('Sumber Anggaran belum diset');
                }
                if (errors.pic) {
                    errorMessages.push('PIC belum diset');
                }
                if (errors.pagu) {
                    errorMessages.push('Pagu belum diset atau tidak valid');
                }

                if (errorMessages.length > 0) {
                    notify.warn(`Tidak dapat memvalidasi: ${errorMessages.join(', ')}. Silakan lengkapi data terlebih dahulu.`);
                } else {
                    notify.error('Gagal memvalidasi Sub-Sub Kegiatan. Silakan coba lagi.');
                }
            } else {
                notify.error('Gagal memvalidasi Sub-Sub Kegiatan. Silakan coba lagi.');
            }
        },
    });
}

function unvalidateSubSub(row: SubSubRow) {
    const payload = {
        sub_sub_kode: row.subSubKode,
        tahun: selectedYear.value,
        action: 'unvalidate',
    };

    router.post(route('anggaran.perencanaan.validate'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            notify.success('Validasi Sub-Sub Kegiatan dibatalkan');
            row.isValidated = false;
            row.verifiedBy = null;
            row.verifiedAt = null;
        },
        onError: () => {
            notify.error('Gagal membatalkan validasi Sub-Sub Kegiatan. Silakan coba lagi.');
        },
    });
}

function printAnggaran(g: Group, a: Activity, sg: SubGroup, r: SubSubRow) {
    // Simpan context ke localStorage agar halaman print bisa akses
    const context = {
        program: { kode: g.programKode, nama: g.programNama },
        kegiatan: { kode: a.kegiatanKode, nama: a.kegiatanNama },
        sub_kegiatan: { kode: sg.subKegiatanKode, nama: sg.subKegiatanNama },
        sub_sub_kegiatan: { kode: r.subSubKode, nama: r.subSubNama, verified_at: r.verifiedAt || null },
        tahun: selectedYear.value,
    };
    try {
        window.localStorage.setItem('print_context', JSON.stringify(context));
    } catch {}
    // Buka halaman print (buat route khusus, misal /anggaran/print?ssk=...&year=...)
    const url = `/anggaran/print?ssk=${encodeURIComponent(r.subSubKode)}&year=${encodeURIComponent(selectedYear.value)}`;
    window.open(url, '_blank');
}
</script>

<template>
    <Head title="Perencanaan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-4 p-4">
            <!-- header -->
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-green-400/30 to-green-600/20 shadow-lg"
                    >
                        <i class="fa fa-layer-group text-2xl text-green-700"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-green-700">Perencanaan Program & Kegiatan</h1>
                        <div class="mt-1 text-xs font-medium text-muted-foreground">Kelola pagu, validasi, dan rincian sub-sub kegiatan</div>
                    </div>
                </div>
                <div class="relative w-full sm:w-80">
                    <div class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2">
                        <Search class="size-5 text-primary" />
                    </div>
                    <input
                        v-model="q"
                        type="text"
                        placeholder="Cari program, kegiatan, atau sub (min 2 huruf)"
                        class="w-full rounded-lg border-2 border-primary/30 bg-gradient-to-r from-primary/5 to-background py-2 pr-3 pl-10 text-sm font-semibold text-primary shadow-sm transition-all focus:ring-2 focus:ring-primary/40 focus:outline-none"
                    />
                </div>
            </div>
            <!-- summary cards -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <!-- Total Anggaran BLUD -->
                <div class="flex items-center gap-3 rounded-lg border border-sky-500/30 bg-gradient-to-br from-sky-500/10 to-sky-500/5 p-3 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-500/20 text-sky-600">
                        <CircleDollarSign class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[11px] font-semibold tracking-wide text-sky-700 uppercase">Total Anggaran BLUD</div>
                        <div class="mt-0.5 truncate text-lg font-extrabold text-sky-700">{{ formatIDR(totalAnggaran) }}</div>
                    </div>
                    <button
                        type="button"
                        class="group relative inline-flex items-center rounded border border-sky-200 bg-sky-50 p-2 text-sky-700 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300"
                        @click="openTotalAnggaran"
                        title="Edit Total Anggaran"
                    >
                        <i class="fa fa-pencil-alt text-xs"></i>
                        <span
                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                        >
                            Edit Total Anggaran
                        </span>
                    </button>
                </div>
                <!-- Total Digunakan -->
                <div
                    class="flex items-center gap-3 rounded-lg border border-rose-500/30 bg-gradient-to-br from-rose-500/10 to-rose-500/5 p-3 shadow-sm"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-500/20 text-rose-600">
                        <Receipt class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-[11px] font-semibold tracking-wide text-rose-700 uppercase">Total Digunakan</div>
                        <div class="mt-0.5 truncate text-lg font-extrabold text-rose-700">{{ formatIDR(usedTotal) }}</div>
                    </div>
                </div>
                <!-- Sisa -->
                <div
                    class="flex items-center gap-3 rounded-lg border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 p-3 shadow-sm"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-600">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-[11px] font-semibold tracking-wide text-emerald-700 uppercase">Sisa</div>
                        <div class="mt-0.5 truncate text-lg font-extrabold text-emerald-700">{{ formatIDR(sisaTotal) }}</div>
                    </div>
                </div>
            </div>

            <!-- card (table) -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border">
                <div class="overflow-auto">
                    <table class="w-full min-w-[900px] overflow-hidden rounded-md border border-sidebar-border/70 text-sm">
                        <thead class="sticky top-0 z-10 border-b border-sidebar-border/80 bg-muted backdrop-blur dark:border-sidebar-border">
                            <tr class="text-[11px] tracking-wider text-foreground uppercase sm:text-xs">
                                <th class="border-x border-sidebar-border/70 px-3 py-3 text-left font-bold dark:border-sidebar-border">
                                    Sub-Sub Kegiatan
                                </th>
                                <th class="border-x border-sidebar-border/70 px-3 py-3 text-center font-bold dark:border-sidebar-border">
                                    Pagu PAPBD
                                </th>
                                <th class="border-x border-sidebar-border/70 px-3 py-3 text-center font-bold dark:border-sidebar-border">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(g, gi) in filteredGroups" :key="'g' + gi">
                                <tr :class="['group transition-colors hover:bg-muted/30', 'bg-gray-50 dark:bg-gray-800', groupBg(gi)]">
                                    <td class="border-x border-b border-sidebar-border/60 px-2 py-3 dark:border-sidebar-border" :colspan="3">
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono text-[13px] font-extrabold text-primary sm:text-base"
                                                >[ {{ g.programKode }} ]</span
                                            >
                                            <span class="text-lg font-extrabold tracking-tight text-foreground sm:text-lg">{{ g.programNama }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <template v-for="(a, ai) in g.activities" :key="'a' + gi + '-' + ai">
                                    <tr :class="[groupBg(gi), 'transition-colors hover:bg-muted/30', 'bg-gray-20 dark:bg-gray-700']">
                                        <td class="border-x border-b border-sidebar-border/60 px-2 py-2.5 dark:border-sidebar-border" :colspan="3">
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono text-[13px] font-bold text-primary sm:text-[14px]"
                                                    >[ {{ a.kegiatanKode }} ]</span
                                                >
                                                <span class="text-base font-bold text-foreground sm:text-lg">{{ a.kegiatanNama }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <template v-for="(sg, si) in a.subGroups" :key="'sg' + gi + '-' + ai + '-' + si">
                                        <tr :class="[groupBg(gi), 'transition-colors hover:bg-gray-200', 'bg-gray-50 dark:bg-gray-700']">
                                            <td
                                                class="border-x border-b border-sidebar-border/60 px-2 py-2.5 dark:border-sidebar-border"
                                                :colspan="3"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-3">
                                                        <span class="font-mono text-[12.5px] font-semibold text-primary sm:text-[13px]"
                                                            >[ {{ sg.subKegiatanKode }} ]</span
                                                        >
                                                        <span class="text-sm font-semibold text-foreground sm:text-[15px]">{{
                                                            sg.subKegiatanNama
                                                        }}</span>
                                                    </div>
                                                    <!-- Add Sub-Sub Kegiatan Button -->
                                                    <button
                                                        v-if="sg.subKegiatanKode != null && sg.subKegiatanKode !== ''"
                                                        type="button"
                                                        class="group relative inline-flex items-center gap-1 rounded border border-green-200 bg-green-50 px-2 py-1 text-xs text-green-700 hover:bg-green-100 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300"
                                                        @click.stop="openAdd(g, a, sg)"
                                                        title="Tambah Sub-Sub Kegiatan"
                                                    >
                                                        <i class="fa fa-plus text-[10px]"></i>
                                                        <span class="hidden sm:inline">Tambah Sub-Sub</span>
                                                        <span
                                                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                                                        >
                                                            Tambah Sub-Sub Kegiatan
                                                        </span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(r, ri) in sg.rows"
                                            :key="gi + '-' + ai + '-' + si + '-' + ri"
                                            :class="['bg-inherit transition-colors', r.pagu != null ? 'group cursor-pointer hover:bg-primary/5' : '']"
                                            :tabindex="r.pagu != null ? 0 : -1"
                                            :role="r.pagu != null ? 'button' : undefined"
                                            :title="r.pagu != null ? 'Klik untuk melihat rincian rekening' : undefined"
                                            @click="onRowClick(g, a, sg, r)"
                                            @keydown.enter.prevent="onRowClick(g, a, sg, r)"
                                            @keydown.space.prevent="onRowClick(g, a, sg, r)"
                                        >
                                            <td class="border-x border-b border-sidebar-border/60 px-3 py-2 dark:border-sidebar-border">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1">
                                                        <span class="font-mono text-[11.5px] font-semibold text-primary sm:text-[12px]"
                                                            >[ {{ r.subSubKode }} ]
                                                        </span>
                                                        <span class="text-[11px] font-medium text-foreground sm:text-sm">{{ r.subSubNama }}</span>
                                                    </div>
                                                    <!-- Validation Status Badge -->
                                                    <div v-if="r.isValidated" class="flex items-center gap-1">
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-[10px] font-semibold text-green-800 dark:bg-green-500/20 dark:text-green-300"
                                                        >
                                                            <i class="fa fa-check text-[8px]"></i>
                                                            Tervalidasi
                                                        </span>
                                                    </div>
                                                    <!-- Warning for incomplete data -->
                                                    <div v-else-if="!r.pagu || r.pagu <= 0" class="flex items-center gap-1">
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-1 text-[10px] font-semibold text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-300"
                                                        >
                                                            <i class="fa fa-exclamation-triangle text-[8px]"></i>
                                                            Data Belum Lengkap
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="border-x border-b border-sidebar-border/60 px-3 py-2 text-left dark:border-sidebar-border">
                                                <template v-if="r.pagu != null">
                                                    <span class="px-1 font-bold text-primary"> Rp {{ Number(r.pagu).toLocaleString('id-ID') }} </span>
                                                </template>
                                            </td>
                                            <td class="border-x border-b border-sidebar-border/60 px-3 py-2 dark:border-sidebar-border">
                                                <div class="flex items-center justify-center gap-2">
                                                    <!-- Edit Button - Hidden when validated -->
                                                    <button
                                                        v-if="!r.isValidated"
                                                        type="button"
                                                        class="group relative inline-flex items-center rounded border border-blue-200 bg-blue-50 p-2 text-blue-700 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300"
                                                        @click.stop="openEdit(g, a, sg, r)"
                                                        title="Edit"
                                                    >
                                                        <i class="fa fa-pencil-alt text-xs"></i>
                                                        <span
                                                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                                                        >
                                                            Edit
                                                        </span>
                                                    </button>
                                                    <!-- Delete Button - Hidden when validated -->
                                                    <button
                                                        v-if="!r.isValidated"
                                                        type="button"
                                                        class="group relative inline-flex items-center rounded border border-rose-200 bg-rose-50 p-2 text-rose-700 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300"
                                                        @click.stop="openConfirm(r)"
                                                        title="Hapus"
                                                    >
                                                        <i class="fa fa-trash-alt text-xs"></i>
                                                        <span
                                                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                                                        >
                                                            Hapus
                                                        </span>
                                                    </button>
                                                    <!-- Validate Button - Show when not validated -->
                                                    <button
                                                        v-if="!r.isValidated"
                                                        type="button"
                                                        class="group relative inline-flex items-center rounded border border-green-200 bg-green-50 p-2 text-green-700 hover:bg-green-100 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300"
                                                        @click.stop="validateSubSub(r)"
                                                        title="Validasi"
                                                    >
                                                        <i class="fa fa-check text-xs"></i>
                                                        <span
                                                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                                                        >
                                                            Validasi
                                                        </span>
                                                    </button>
                                                    <!-- Unvalidate Button - Show when validated -->
                                                    <button
                                                        v-if="r.isValidated"
                                                        type="button"
                                                        class="group relative inline-flex items-center rounded border border-orange-200 bg-orange-50 p-2 text-orange-700 hover:bg-orange-100 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300"
                                                        @click.stop="unvalidateSubSub(r)"
                                                        title="Batal Validasi"
                                                    >
                                                        <i class="fa fa-times text-xs"></i>
                                                        <span
                                                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                                                        >
                                                            Batal Validasi
                                                        </span>
                                                    </button>
                                                    <!-- Print Button - Always visible -->
                                                    <button
                                                        type="button"
                                                        class="group relative inline-flex items-center rounded border border-gray-200 bg-gray-50 p-2 text-gray-700 hover:bg-gray-100 dark:border-gray-500/30 dark:bg-gray-500/10 dark:text-gray-300"
                                                        @click.stop="printAnggaran(g, a, sg, r)"
                                                        title="Cetak"
                                                    >
                                                        <i class="fa fa-print text-xs"></i>
                                                        <span
                                                            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100"
                                                        >
                                                            Cetak
                                                        </span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>

    <ConfirmModal
        :open="showConfirm"
        title="Konfirmasi Nonaktifkan"
        message="Apakah anda yakin ingin menonaktifkan sub-sub kegiatan ini?"
        confirm-text="Ya, Nonaktifkan"
        cancel-text="Batal"
        variant="danger"
        @cancel="closeConfirm"
        @confirm="confirmDeactivate"
    >
        <template v-if="pendingRow">
            <span class="font-mono text-primary">[ {{ pendingRow.subSubKode }} ]</span>
            <span class="ml-1">{{ pendingRow.subSubNama }}</span>
        </template>
    </ConfirmModal>

    <!-- Edit Modal -->
    <ConfirmModal
        :open="showEdit"
        title="Edit Pagu Sub-Sub Kegiatan"
        :message="`Tahun: ${selectedYear}`"
        confirm-text="Simpan"
        cancel-text="Batal"
        variant="info"
        max-width="2xl"
        @cancel="closeEdit"
        @confirm="saveEdit"
    >
        <div class="space-y-3 text-foreground">
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Program</label>
                <input
                    type="text"
                    class="w-full rounded border border-border/60 bg-muted/40 px-3 py-2 text-sm text-foreground"
                    :value="`[ ${editForm.programKode} ] ${editForm.programNama}`"
                    disabled
                />
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Kegiatan</label>
                <input
                    type="text"
                    class="w-full rounded border border-border/60 bg-muted/40 px-3 py-2 text-sm text-foreground"
                    :value="`[ ${editForm.kegiatanKode} ] ${editForm.kegiatanNama}`"
                    disabled
                />
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Sub Kegiatan</label>
                <input
                    type="text"
                    class="w-full rounded border border-border/60 bg-muted/40 px-3 py-2 text-sm text-foreground"
                    :value="`[ ${editForm.subKegiatanKode} ] ${editForm.subKegiatanNama}`"
                    disabled
                />
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Sub-Sub Kegiatan</label>
                <select
                    v-model="editForm.subSubKode"
                    class="w-full rounded border border-border/60 bg-background px-3 py-2 text-sm text-foreground"
                    disabled
                >
                    <option v-for="opt in editSubSubOptions" :key="opt.subSubKode" :value="opt.subSubKode">
                        [ {{ opt.subSubKode }} ] {{ opt.subSubNama }}
                    </option>
                </select>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6">
                <div class="flex flex-col gap-3">
                    <label class="text-xs font-semibold">Sumber Dana</label>
                    <select
                        v-model.number="editForm.sumberDanaId"
                        class="w-full rounded border border-border/60 bg-background px-3 py-2 text-sm text-foreground"
                    >
                        <option :value="null">-- Pilih Sumber Dana --</option>
                        <option v-for="src in props.fundingSources || []" :key="src.id" :value="src.id">
                            {{ src.nama }}
                        </option>
                    </select>
                </div>
                <div class="flex flex-col gap-3">
                    <label class="text-xs font-semibold">Pagu</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-semibold text-muted-foreground"
                            >Rp</span
                        >
                        <input
                            v-model="paguText"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            class="w-full rounded border border-border/60 bg-background py-2 pr-3 pl-9 text-sm text-foreground"
                            placeholder="0"
                        />
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-2">
                <label class="text-xs font-semibold">PIC (bisa lebih dari satu)</label>
                <select
                    v-model="editForm.picIds"
                    multiple
                    class="w-full rounded border border-border/60 bg-background px-3 py-2 text-sm text-foreground"
                    size="5"
                >
                    <option v-for="u in props.users || []" :key="u.id" :value="u.id">
                        {{ u.name }}
                    </option>
                </select>
                <p class="text-[11px] text-muted-foreground">Tahan Ctrl (Windows) atau Cmd (Mac) untuk memilih lebih dari satu.</p>
            </div>
        </div>
    </ConfirmModal>

    <!-- Add Sub-Sub Kegiatan Modal -->
    <ConfirmModal
        :open="showAdd"
        title="Tambah Sub-Sub Kegiatan Baru"
        confirm-text="Simpan"
        cancel-text="Batal"
        variant="info"
        max-width="2xl"
        @cancel="closeAdd"
        @confirm="saveAdd"
    >
        <div class="space-y-3 text-foreground">
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Program</label>
                <input
                    type="text"
                    class="w-full rounded border border-border/60 bg-muted/40 px-3 py-2 text-sm text-foreground"
                    :value="`[ ${addForm.programKode} ] ${addForm.programNama}`"
                    disabled
                />
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Kegiatan</label>
                <input
                    type="text"
                    class="w-full rounded border border-border/60 bg-muted/40 px-3 py-2 text-sm text-foreground"
                    :value="`[ ${addForm.kegiatanKode} ] ${addForm.kegiatanNama}`"
                    disabled
                />
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-xs font-semibold">Sub Kegiatan</label>
                <input
                    type="text"
                    class="w-full rounded border border-border/60 bg-muted/40 px-3 py-2 text-sm text-foreground"
                    :value="`[ ${addForm.subKegiatanKode} ] ${addForm.subKegiatanNama}`"
                    disabled
                />
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6">
                <div class="flex flex-col gap-3">
                    <label class="text-xs font-semibold">Kode Sub-Sub Kegiatan <span class="text-red-500">*</span></label>
                    <input
                        v-model="addForm.subSubKode"
                        type="text"
                        class="w-full rounded border border-border/60 bg-background px-3 py-2 text-sm text-foreground"
                        placeholder="Contoh: 5.02.02.1.01.001"
                        required
                    />
                </div>
                <div class="flex flex-col gap-3">
                    <label class="text-xs font-semibold">Nama Sub-Sub Kegiatan <span class="text-red-500">*</span></label>
                    <input
                        v-model="addForm.subSubNama"
                        type="text"
                        class="w-full rounded border border-border/60 bg-background px-3 py-2 text-sm text-foreground"
                        placeholder="Nama Sub-Sub Kegiatan"
                        required
                    />
                </div>
            </div>
            <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-500/10">
                <div class="flex items-start gap-2">
                    <i class="fa fa-info-circle mt-0.5 text-blue-600 dark:text-blue-400"></i>
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        <p class="font-semibold">Catatan:</p>
                        <p>
                            Sub-Sub Kegiatan akan disimpan ke dalam master data. Untuk menambah pagu, sumber dana, dan PIC, gunakan fitur edit pada
                            tabel.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </ConfirmModal>

    <!-- Total Anggaran BLUD Modal -->
    <ConfirmModal
        :open="showTotalAnggaran"
        title="Edit Total Anggaran BLUD"
        confirm-text="Simpan"
        cancel-text="Batal"
        variant="info"
        max-width="lg"
        @cancel="closeTotalAnggaran"
        @confirm="saveTotalAnggaran"
    >
        <div class="space-y-4 text-foreground">
            <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-500/10">
                <div class="flex items-start gap-2">
                    <i class="fa fa-info-circle mt-0.5 text-blue-600 dark:text-blue-400"></i>
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        <p class="font-semibold">Informasi:</p>
                        <p>
                            Total Anggaran BLUD akan disimpan untuk tahun {{ totalAnggaranForm.tahun }}. Data ini akan digunakan sebagai batas
                            maksimal alokasi anggaran.
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-sm font-semibold">Tahun Anggaran</label>
                <input
                    v-model.number="totalAnggaranForm.tahun"
                    type="number"
                    min="2020"
                    max="2050"
                    class="w-full rounded border border-border/60 bg-background px-3 py-2 text-sm text-foreground"
                    readonly
                />
            </div>
            <div class="grid grid-cols-1 gap-3">
                <label class="text-sm font-semibold">Total Anggaran BLUD <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-muted-foreground"
                        >Rp</span
                    >
                    <input
                        v-model="totalAnggaranText"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="w-full rounded border border-border/60 bg-background py-3 pr-3 pl-10 text-sm text-foreground"
                        placeholder="0"
                        required
                    />
                </div>
                <p class="text-xs text-muted-foreground">Masukkan total anggaran BLUD untuk tahun {{ totalAnggaranForm.tahun }}</p>
            </div>
        </div>
    </ConfirmModal>
</template>

<style scoped>
.size-6 {
    width: 1.5rem;
    height: 1.5rem;
}
</style>
