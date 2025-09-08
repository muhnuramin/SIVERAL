<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import useNotify from '@/composables/useNotify';
import AppLayout from '@/layouts/AppLayout.vue';
import { AddForm } from '@/types/ssh';
import { formatIDR } from '@/utils/currency';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    kode?: string;
    nama?: string;
    heads?: { kode?: string; nama?: string }[];
    items?: { id?: number; no: number; rekening?: string; nama: string; satuan: string; spesifikasi?: string; harga: number; tahun?: number }[];
    satuans?: { id: number; nama: string }[];
    context?: {
        program?: { kode?: string | null; nama?: string | null };
        kegiatan?: { kode?: string | null; nama?: string | null };
        sub_kegiatan?: { kode?: string | null; nama?: string | null };
        sub_sub_kegiatan?: { kode?: string | null; nama?: string | null };
    } | null;
    year?: number;
}>();

const notify = useNotify();
const displayContext = computed(() => storedContext.value || props.context || null);
type SshItem = {
    id?: number;
    no?: number;
    rekening?: string;
    nama: string;
    satuan: string;
    spesifikasi?: string;
    harga: number;
    tahun?: number;
    _temp?: string;
};
const itemsList = ref<SshItem[]>(props.items ? [...props.items] : ([] as SshItem[]));
const storedContext = ref<any>(null);
const sshQuery = ref('');
const showDeleteModal = ref(false);
const showEditModal = ref(false);
const showAddModal = ref(false);
const activeItem = ref<any>(null);
const year = ref<number>(props.year || new Date().getFullYear());
const addForm = ref<AddForm>({ rekening: '', tahun: year.value, entries: [{ nama: '', satuan: '', harga: '', spesifikasi: '' }] });
const satuanList = ref<{ id: number; nama: string }[]>(props.satuans || []);
const satuanOptions = computed(() => satuanList.value.map((s) => ({ value: s.nama, label: s.nama })));
const satuanSearch = ref('');
const showSatuanDropdown = ref(false);
const highlightedSatuan = ref(-1);
const showCreateSatuanModal = ref(false);
const createSatuanForm = ref({ nama: '' });
const createSatuanErrors = ref<{ nama?: string }>({});
const addFormErrors = ref<{
    rekening?: string;
    entries?: { nama?: string; satuan?: string; harga?: string; spesifikasi?: string }[];
    tahun?: string;
}>({});
const editForm = ref<{ id?: number; nama: string; satuan: string; harga: number | ''; spesifikasi?: string }>({
    id: undefined,
    nama: '',
    satuan: '',
    harga: '',
    spesifikasi: '',
});
const localSelectedYear = ref<number | null>(null);
const breadcrumbs = computed(() => [
    { title: 'Anggaran', href: route('anggaran.perencanaan') },
    { title: 'Perencanaan', href: route('anggaran.perencanaan') },
    { title: 'Rekening', href: '#' },
]);

// Normalize heads into rows of kode segments and a name; compute max columns for kode segments
const headsData = computed(() => {
    const list = props.heads && props.heads.length ? props.heads : [{ kode: props.kode, nama: props.nama }];

    const rows = list.map((h) => {
        const segs = String(h?.kode || '')
            .split('.')
            .map((s) => s.trim())
            .filter((s) => s.length > 0);
        return { segs, nama: h?.nama || '-' };
    });

    const maxSegs = Math.max(
        1,
        rows.reduce((m, r) => Math.max(m, r.segs.length), 0),
    );
    const normalized = rows.map((r) => ({
        // Keep only actual segments to avoid trailing dots when joining
        segs: r.segs.length ? r.segs : ['-'],
        nama: r.nama,
    }));

    return { rows: normalized, maxSegs };
});

// Prefer context from localStorage when present
onMounted(() => {
    try {
        const raw = window.localStorage.getItem('rekening_context');
        storedContext.value = raw ? JSON.parse(raw) : null;
    } catch {
        storedContext.value = null;
    }
});

watch(
    () => props.items,
    (val: { no?: number; nama: string; satuan: string; harga: number }[] | undefined) => {
        itemsList.value = (val ? [...(val as any)] : []) as SshItem[];
    },
);

function openDelete(item: any) {
    activeItem.value = item;
    showDeleteModal.value = true;
}
function closeModals() {
    showDeleteModal.value = false;
    showEditModal.value = false;
    activeItem.value = null;
}

function openAdd() {
    showAddModal.value = true;
}
function closeAdd() {
    showAddModal.value = false;
    addForm.value = { rekening: '', tahun: year.value, entries: [{ nama: '', satuan: '', harga: '', spesifikasi: '' }] };
}

function openCreateSatuan() {
    showCreateSatuanModal.value = true;
    createSatuanForm.value = { nama: '' };
    createSatuanErrors.value = {};
}

function closeCreateSatuan() {
    showCreateSatuanModal.value = false;
    createSatuanForm.value = { nama: '' };
    createSatuanErrors.value = {};
}

function validateCreateSatuan(): boolean {
    const errors: { nama?: string } = {};

    if (!createSatuanForm.value.nama || !createSatuanForm.value.nama.trim()) {
        errors.nama = 'Nama satuan wajib diisi.';
    } else if (createSatuanForm.value.nama.trim().length > 255) {
        errors.nama = 'Nama satuan maksimal 255 karakter.';
    } else if (satuanList.value.some((s) => s.nama.toLowerCase() === createSatuanForm.value.nama.toLowerCase().trim())) {
        errors.nama = 'Nama satuan sudah ada.';
    }

    createSatuanErrors.value = errors;
    return Object.keys(errors).length === 0;
}

function saveCreateSatuan() {
    if (!validateCreateSatuan()) return;

    const payload = {
        nama: createSatuanForm.value.nama.trim(),
    };

    router.post(route('satuan.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            notify.success('Satuan berhasil ditambahkan');
            // Refresh satuan list
            refreshSatuanList();
            closeCreateSatuan();
        },
        onError: (errors) => {
            if (errors.nama) {
                createSatuanErrors.value.nama = errors.nama;
            } else {
                notify.error('Gagal menambah satuan');
            }
        },
    });
}

function refreshSatuanList() {
    // Call API to get updated satuan list
    fetch(route('satuan.api'))
        .then((response) => response.json())
        .then((data) => {
            satuanList.value = data;
        })
        .catch(() => {
            // Fallback: reload page data if API fails
            router.reload({ only: ['satuans'] });
        });
}

function saveAdd() {
    if (!validateAdd()) return;
    let ssk: string | null = null;
    try {
        const ctxRaw = window.localStorage.getItem('rekening_context');
        if (ctxRaw) {
            const c = JSON.parse(ctxRaw);
            ssk = c?.sub_sub_kegiatan?.kode || null;
        }
    } catch {}
    const headsJson = JSON.stringify(props.heads || []);
    const baseRekening = normalizeRek(addForm.value.rekening);
    // Post each entry separately (sequential) with optimistic UI
    addForm.value.entries.forEach((entry) => {
        const payload = {
            rekening: baseRekening,
            nama: entry.nama.trim(),
            satuan: entry.satuan.trim(),
            harga: entry.harga,
            spesifikasi: entry.spesifikasi?.trim() || '',
            tahun: addForm.value.tahun,
            ssk,
            heads: headsJson,
        } as any;
        const tempId = '__temp_' + Date.now() + Math.random().toString(16).slice(2);
        const parsedHarga = typeof payload.harga === 'number' ? payload.harga : parseFloat(String(payload.harga).replace(/[^0-9.]/g, ''));
        itemsList.value.push({
            rekening: baseRekening,
            nama: payload.nama,
            satuan: payload.satuan,
            harga: parsedHarga,
            tahun: payload.tahun,
            no: itemsList.value.length + 1,
            _temp: tempId,
        });
        router.post(route('anggaran.rekening.ssh.store'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                notify.success('Item berhasil ditambahkan');
            },
            onError: () => {
                notify.error('Gagal menambah item');
                itemsList.value = itemsList.value.filter((i) => i._temp !== tempId);
            },
        });
    });
    closeAdd();
}

// Rekening options (from heads)
const rekeningOptions = computed(() => {
    const list = (props.heads && props.heads.length ? props.heads : props.kode || props.nama ? [{ kode: props.kode, nama: props.nama }] : []) as {
        kode?: string;
        nama?: string;
    }[];
    return list
        .map((h) => ({
            value: String(h?.kode || ''),
            label: `${h?.kode || ''} - ${h?.nama || ''}`.trim(),
        }))
        .filter((o) => o.value.length > 0);
});

watch(
    () => showAddModal.value,
    (open) => {
        if (open) {
            // reset search helper for (legacy) single satuan picker, now per-row select used
            satuanSearch.value = '';
            showSatuanDropdown.value = false;
            highlightedSatuan.value = -1;
        }
    },
);

watch(
    () => satuanSearch.value,
    () => {
        highlightedSatuan.value = -1;
        showSatuanDropdown.value = true;
    },
);

function validateAdd(): boolean {
    const errs: { rekening?: string; entries?: { nama?: string; satuan?: string; harga?: string }[]; tahun?: string } = {};
    if (!addForm.value.rekening) errs.rekening = 'Rekening wajib dipilih.';
    if (!addForm.value.tahun || addForm.value.tahun < 2000) errs.tahun = 'Tahun tidak valid.';
    const entryErrors: { nama?: string; satuan?: string; harga?: string }[] = [];
    addForm.value.entries.forEach((e) => {
        const eErr: { nama?: string; satuan?: string; harga?: string } = {};
        if (!e.nama || !e.nama.trim()) eErr.nama = 'Nama wajib.';
        if (!e.satuan || !e.satuan.trim()) eErr.satuan = 'Satuan wajib.';
        const parsed = typeof e.harga === 'number' ? e.harga : parseFloat(String(e.harga).replace(/[^0-9.]/g, ''));
        if (!Number.isFinite(parsed)) eErr.harga = 'Harga tidak valid.';
        entryErrors.push(eErr);
    });
    if (entryErrors.some((x) => Object.keys(x).length)) errs.entries = entryErrors;
    addFormErrors.value = errs;
    return Object.keys(errs).length === 0;
}

watch(
    () => addForm.value.rekening,
    () => {
        if (addFormErrors.value.rekening) addFormErrors.value.rekening = '';
    },
);

function addEntryRow() {
    addForm.value.entries.push({ nama: '', satuan: '', harga: '', spesifikasi: '' });
    if (addFormErrors.value.entries) addFormErrors.value.entries.push({});
}
function removeEntryRow(idx: number) {
    if (addForm.value.entries.length === 1) return; // keep at least one row
    addForm.value.entries.splice(idx, 1);
    if (addFormErrors.value.entries) addFormErrors.value.entries.splice(idx, 1);
}

const itemsByRekening = computed(() => {
    const map: Record<string, SshItem[]> = {};
    const raw = sshQuery.value.trim().toLowerCase();
    const tokens = raw.length ? raw.split(/\s+/).filter(Boolean) : [];
    for (const it of itemsList.value) {
        const key = normalizeRek(it.rekening || '-');
        if (!map[key]) map[key] = [];
        if (!tokens.length) {
            map[key].push(it);
            continue;
        }
        const namaLc = (it.nama || '').toLowerCase();
        // require all tokens to appear in nama
        const match = tokens.every((t) => namaLc.includes(t));
        if (match) map[key].push(it);
    }
    return map;
});

const totalFilteredCount = computed(() => {
    return Object.values(itemsByRekening.value).reduce((sum, arr) => sum + arr.length, 0);
});

watch(
    () => addForm.value.tahun,
    () => {
        if (addFormErrors.value.tahun) addFormErrors.value.tahun = '';
    },
);

function openEdit(item: SshItem) {
    activeItem.value = item;
    editForm.value = { id: item.id, nama: item.nama, satuan: item.satuan, harga: item.harga, spesifikasi: item.spesifikasi || '' };
    showEditModal.value = true;
}

function submitEdit() {
    if (!editForm.value.id) {
        showEditModal.value = false;
        return;
    }
    // simple inline validation
    if (!editForm.value.nama.trim() || !editForm.value.satuan.trim()) {
        (window as any).toastr?.error?.('Nama dan Satuan wajib diisi');
        return;
    }
    const id = editForm.value.id;
    let ssk: string | null = null;
    try {
        const ctxRaw = window.localStorage.getItem('rekening_context');
        if (ctxRaw) {
            const c = JSON.parse(ctxRaw);
            ssk = c?.sub_sub_kegiatan?.kode || null;
        }
    } catch {}
    const payload = {
        nama: editForm.value.nama.trim(),
        satuan: editForm.value.satuan.trim(),
        harga: editForm.value.harga,
        spesifikasi: editForm.value.spesifikasi?.trim() || '',
        tahun: year.value,
        ssk,
        heads: JSON.stringify(props.heads || []),
    } as any;
    router.put(route('anggaran.rekening.ssh.update', id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            const target = itemsList.value.find((i) => i.id === id);
            if (target) {
                target.nama = payload.nama;
                target.satuan = payload.satuan;
                target.harga = Number(payload.harga) || 0;
                target.spesifikasi = payload.spesifikasi;
            }
            notify.success('Item berhasil diperbarui');
        },
        onError: () => notify.error('Gagal memperbarui item'),
    });
}

function submitDelete() {
    if (!activeItem.value || !activeItem.value.id) {
        showDeleteModal.value = false;
        return;
    }
    const id = activeItem.value.id;
    const currentYear = (localSelectedYear.value ?? year.value) as number;
    let ssk: string | null = null;
    try {
        const ctxRaw = window.localStorage.getItem('rekening_context');
        if (ctxRaw) {
            const c = JSON.parse(ctxRaw);
            ssk = c?.sub_sub_kegiatan?.kode || null;
        }
    } catch {}
    const headsPayload = JSON.stringify(props.heads || []);
    const prevItems = [...itemsList.value];
    itemsList.value = itemsList.value.filter((i) => i.id !== id);
    showDeleteModal.value = false;
    router.delete(route('anggaran.rekening.ssh.destroy', id), {
        data: { year: currentYear, ssk, heads: headsPayload },
        preserveScroll: true,
        onSuccess: () => {
            notify.success('Item berhasil dihapus');
        },
        onError: () => {
            notify.error('Gagal menghapus item');
            itemsList.value = prevItems;
        },
    });
}

function normalizeRek(k?: string) {
    return (k || '').trim().replace(/\.+$/, '');
}

onMounted(() => {
    try {
        const raw = window.localStorage.getItem('selected_year');
        const parsed = raw ? parseInt(raw, 10) : undefined;
        if (Number.isFinite(parsed)) localSelectedYear.value = parsed as number;
    } catch {}

    // If server provided year differs from local storage year, refetch with proper year (keeping ssk code via stored context)
    if (localSelectedYear.value && props.year !== localSelectedYear.value) {
        try {
            const ctxRaw = window.localStorage.getItem('rekening_context');
            if (ctxRaw) {
                const ctx = JSON.parse(ctxRaw);
                const ssk = ctx?.sub_sub_kegiatan?.kode;
                if (ssk) {
                    router.get(
                        route('anggaran.rekening.show', { ssk, year: localSelectedYear.value } as any),
                        {},
                        { preserveScroll: true, replace: true },
                    );
                }
            }
        } catch {}
    }
});
</script>

<template>
    <Head title="Detail Rekening" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-4 p-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <!-- Search + Single table with context, rekening heads, and items using colspan -->
                <div class="overflow-auto">
                    <table class="w-full min-w-[900px] overflow-hidden rounded-lg border border-sidebar-border/70 text-sm">
                        <tbody>
                            <!-- Context block: label : value (value spans remaining columns) -->
                            <template v-if="displayContext">
                                <tr class="bg-muted/30">
                                    <td class="w-[180px] px-3 py-2 text-[12px] font-semibold tracking-wide text-muted-foreground uppercase">
                                        Program
                                    </td>
                                    <td class="w-[12px] px-1 py-2 text-muted-foreground">:</td>
                                    <td class="px-3 py-2" colspan="2">
                                        <span class="font-mono text-[12.5px] font-semibold">{{ displayContext?.program?.kode || '-' }}</span>
                                        <span class="ml-2">{{ displayContext?.program?.nama || '-' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="w-[180px] px-3 py-2 text-[12px] font-semibold tracking-wide text-muted-foreground uppercase">
                                        Kegiatan
                                    </td>
                                    <td class="w-[12px] px-1 py-2 text-muted-foreground">:</td>
                                    <td class="px-3 py-2" colspan="2">
                                        <span class="font-mono text-[12.5px] font-semibold">{{ displayContext?.kegiatan?.kode || '-' }}</span>
                                        <span class="ml-2">{{ displayContext?.kegiatan?.nama || '-' }}</span>
                                    </td>
                                </tr>
                                <tr class="bg-muted/30">
                                    <td class="w-[180px] px-3 py-2 text-[12px] font-semibold tracking-wide text-muted-foreground uppercase">
                                        Sub Kegiatan
                                    </td>
                                    <td class="w-[12px] px-1 py-2 text-muted-foreground">:</td>
                                    <td class="px-3 py-2" colspan="2">
                                        <span class="font-mono text-[12.5px] font-semibold">{{ displayContext?.sub_kegiatan?.kode || '-' }}</span>
                                        <span class="ml-2">{{ displayContext?.sub_kegiatan?.nama || '-' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="w-[180px] px-3 py-2 text-[12px] font-semibold tracking-wide text-muted-foreground uppercase">
                                        Sub-Sub Kegiatan
                                    </td>
                                    <td class="w-[12px] px-1 py-2 text-muted-foreground">:</td>
                                    <td class="px-3 py-2" colspan="2">
                                        <span class="font-mono text-[12.5px] font-semibold">{{ displayContext?.sub_sub_kegiatan?.kode || '-' }}</span>
                                        <span class="ml-2">{{ displayContext?.sub_sub_kegiatan?.nama || '-' }}</span>
                                    </td>
                                </tr>
                                <tr class="bg-muted/30">
                                    <td class="w-[180px] px-3 py-2 text-[12px] font-semibold tracking-wide text-muted-foreground uppercase">
                                        Status
                                    </td>
                                    <td class="w-[12px] px-1 py-2 text-muted-foreground">:</td>
                                    <td class="px-3 py-2" colspan="2">
                                        <span
                                            class="inline-flex items-center gap-2 rounded px-2 py-1 font-semibold"
                                            :class="
                                                displayContext.sub_sub_kegiatan?.verified_at
                                                    ? 'border border-green-400 bg-green-100 text-green-700'
                                                    : 'border border-red-400 bg-red-100 text-red-700'
                                            "
                                        >
                                            <i
                                                :class="
                                                    displayContext.sub_sub_kegiatan?.verified_at
                                                        ? 'fa fa-check-circle text-green-500'
                                                        : 'fa fa-times-circle text-red-500'
                                                "
                                            ></i>
                                            {{ displayContext.sub_sub_kegiatan?.verified_at != null ? 'Tervalidasi' : 'Belum Tervalidasi' }}
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="mt-10 mb-4 rounded-xl border p-3 dark:border-green-700/40 dark:bg-green-950/30">
                        <table class="w-full min-w-[900px] table-fixed overflow-hidden rounded-lg border border-sidebar-border/70 text-sm">
                            <thead>
                                <tr class="bg-muted/40">
                                    <th colspan="5" class="px-3 py-2">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="text-sm font-semibold text-muted-foreground">Rekening & SSH</div>
                                            <div class="relative w-full sm:w-auto">
                                                <input
                                                    v-model="sshQuery"
                                                    type="text"
                                                    class="w-full rounded-md border border-green-600/70 bg-background py-1.5 pr-3 pl-3 text-sm focus:ring-2 focus:ring-green-400/40 focus:outline-none sm:w-64"
                                                    placeholder="Cari SSH (nama item)"
                                                />
                                                <button
                                                    v-if="displayContext.sub_sub_kegiatan?.verified_at == null"
                                                    class="ml-2 rounded bg-green-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-green-700"
                                                    type="button"
                                                    @click="openAdd"
                                                >
                                                    <i class="fa fa-plus mr-1"></i> Tambah SSH
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="transition-colors odd:bg-muted/20 hover:bg-muted/40">
                                    <td colspan="5" class="border-t p-0">
                                        <table class="w-full table-fixed">
                                            <thead>
                                                <tr>
                                                    <th
                                                        class="px-3 py-2 text-left text-[11px] font-extrabold tracking-wider text-foreground uppercase"
                                                        style="width: 40%"
                                                    >
                                                        KODE REKENING
                                                    </th>
                                                    <th class="px-1 py-2"></th>
                                                    <th
                                                        class="px-3 py-2 text-left text-[11px] font-extrabold tracking-wider text-foreground uppercase"
                                                        style="width: 60%"
                                                    >
                                                        NAMA REKENING
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template v-for="(row, i) in headsData.rows" :key="'rekblock-' + i">
                                                    <tr>
                                                        <td
                                                            class="truncate px-3 py-2 font-mono text-[12px] font-bold tracking-normal text-foreground/90"
                                                            :title="row.segs.join('.')"
                                                            style="width: 40%"
                                                        >
                                                            {{ row.segs.join('.') }}
                                                        </td>
                                                        <td class="w-[16px] px-1 py-2 text-center text-muted-foreground">:</td>
                                                        <td
                                                            class="truncate px-3 py-2 text-[12.5px] font-semibold text-foreground"
                                                            :title="row.nama"
                                                            style="width: 60%"
                                                        >
                                                            {{ row.nama }}
                                                        </td>
                                                    </tr>
                                                    <tr v-if="(itemsByRekening[normalizeRek(row.segs.join('.'))] || []).length">
                                                        <td colspan="3" class="p-0">
                                                            <table class="w-full border-t border-dashed text-[11px]">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="w-10 px-3 py-1 text-left font-semibold">No</th>
                                                                        <th class="px-3 py-1 text-left font-semibold">Nama Item</th>
                                                                        <th class="w-28 px-3 py-1 text-left font-semibold">Satuan</th>
                                                                        <th class="w-32 px-3 py-1 text-left font-semibold">Spesifikasi</th>
                                                                        <th class="w-40 px-3 py-1 text-right font-semibold">Harga</th>
                                                                        <th
                                                                            v-if="displayContext.sub_sub_kegiatan?.verified_at == null"
                                                                            class="w-32 px-3 py-1 text-center font-semibold"
                                                                        >
                                                                            Aksi
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr
                                                                        v-for="(it, idx) in itemsByRekening[normalizeRek(row.segs.join('.'))]"
                                                                        :key="row.segs.join('.') + '-' + idx"
                                                                    >
                                                                        <td class="px-3 py-1 align-top font-mono">{{ idx + 1 }}</td>
                                                                        <td class="px-3 py-1 align-top">{{ it.nama }}</td>
                                                                        <td class="px-3 py-1 align-top">{{ it.satuan }}</td>
                                                                        <td class="px-3 py-1 align-top">{{ it.spesifikasi || '-' }}</td>
                                                                        <td class="px-3 py-1 text-right align-top font-semibold text-green-700">
                                                                            {{ formatIDR(it.harga) }}
                                                                        </td>
                                                                        <td
                                                                            v-if="displayContext.sub_sub_kegiatan?.verified_at == null"
                                                                            class="space-x-1 px-3 py-1 text-center align-top"
                                                                        >
                                                                            <button
                                                                                class="rounded bg-amber-500 px-2 py-1 text-white hover:bg-amber-600"
                                                                                @click="openEdit(it)"
                                                                                title="Edit"
                                                                            >
                                                                                <i class="fa fa-edit"></i>
                                                                            </button>
                                                                            <button
                                                                                class="rounded bg-red-600 px-2 py-1 text-white hover:bg-red-700"
                                                                                @click="openDelete(it)"
                                                                                title="Hapus"
                                                                            >
                                                                                <i class="fa fa-trash"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Delete Modal using shared ConfirmModal -->
                    <ConfirmModal
                        :open="showDeleteModal"
                        title="Konfirmasi Hapus"
                        message="Apakah Anda yakin ingin menghapus item ini?"
                        confirm-text="Hapus"
                        cancel-text="Batal"
                        variant="danger"
                        @cancel="closeModals"
                        @confirm="submitDelete"
                    >
                        <template v-if="activeItem">
                            <div class="mt-2 rounded bg-muted/40 p-2 text-foreground">
                                <div class="font-semibold">{{ activeItem.nama }}</div>
                                <div class="text-xs text-muted-foreground">
                                    Satuan: {{ activeItem.satuan }} • Harga: Rp
                                    {{ Number(activeItem.harga).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </div>
                            </div>
                        </template>
                    </ConfirmModal>

                    <!-- Edit Modal (full) -->
                    <ConfirmModal
                        :open="showEditModal"
                        title="Edit Item SSH"
                        confirm-text="Simpan"
                        cancel-text="Tutup"
                        variant="info"
                        max-width="xl"
                        @cancel="closeModals"
                        @confirm="submitEdit"
                    >
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="col-span-2">
                                <label class="mb-1 block text-[11px] font-semibold text-muted-foreground uppercase">Nama Item</label>
                                <input v-model="editForm.nama" type="text" class="w-full rounded border px-3 py-2" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-muted-foreground uppercase">Satuan</label>
                                <input v-model="editForm.satuan" type="text" class="w-full rounded border px-3 py-2" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-muted-foreground uppercase">Harga ({{ year }})</label>
                                <input v-model.number="editForm.harga" type="number" min="0" class="w-full rounded border px-3 py-2" />
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1 block text-[11px] font-semibold text-muted-foreground uppercase">Spesifikasi</label>
                                <input v-model="editForm.spesifikasi" type="text" class="w-full rounded border px-3 py-2" placeholder="Opsional" />
                            </div>
                        </div>
                    </ConfirmModal>

                    <!-- Add SSH Modal using shared ConfirmModal (multi entries) -->
                    <ConfirmModal
                        :open="showAddModal"
                        title="Tambah Item SSH"
                        confirm-text="Tambah"
                        cancel-text="Batal"
                        variant="info"
                        max-width="xl"
                        @cancel="closeAdd"
                        @confirm="saveAdd"
                    >
                        <div class="space-y-5">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-muted-foreground uppercase">Rekening</label>
                                <select
                                    v-model="addForm.rekening"
                                    class="w-full rounded border px-3 py-2"
                                    :class="{ 'border-red-500 focus:ring-red-300': addFormErrors.rekening }"
                                >
                                    <option value="">-- Pilih Rekening --</option>
                                    <option v-for="opt in rekeningOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <p v-if="addFormErrors.rekening" class="mt-1 text-xs text-red-600">{{ addFormErrors.rekening }}</p>
                            </div>
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label class="block text-xs font-semibold text-muted-foreground uppercase">Items</label>
                                    <button
                                        type="button"
                                        class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white hover:bg-green-700"
                                        @click="addEntryRow"
                                    >
                                        + Baris
                                    </button>
                                </div>
                                <div class="overflow-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="bg-muted/40">
                                                <th class="px-2 py-1 text-left font-semibold">Nama Item</th>
                                                <th class="w-32 px-2 py-1 text-left font-semibold">Satuan</th>
                                                <th class="w-32 px-2 py-1 text-left font-semibold">Harga</th>
                                                <th class="w-32 px-2 py-1 text-left font-semibold">Spesifikasi</th>
                                                <th class="w-10 px-2 py-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, rIdx) in addForm.entries" :key="'entry-' + rIdx" class="border-t">
                                                <td class="px-2 py-1 align-top">
                                                    <input
                                                        v-model="row.nama"
                                                        type="text"
                                                        class="w-full rounded border px-2 py-1"
                                                        :class="{ 'border-red-500': addFormErrors.entries && addFormErrors.entries[rIdx]?.nama }"
                                                        placeholder="Nama item"
                                                    />
                                                    <p
                                                        v-if="addFormErrors.entries && addFormErrors.entries[rIdx]?.nama"
                                                        class="mt-0.5 text-[10px] text-red-600"
                                                    >
                                                        {{ addFormErrors.entries[rIdx]?.nama }}
                                                    </p>
                                                </td>
                                                <td class="px-2 py-1 align-top">
                                                    <div class="flex items-center gap-1">
                                                        <select
                                                            v-model="row.satuan"
                                                            class="w-full rounded border px-2 py-1"
                                                            :class="{
                                                                'border-red-500': addFormErrors.entries && addFormErrors.entries[rIdx]?.satuan,
                                                            }"
                                                        >
                                                            <option value="">-- Satuan --</option>
                                                            <option v-for="s in satuanOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                                                        </select>
                                                        <button
                                                            type="button"
                                                            class="flex-shrink-0 rounded bg-blue-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-blue-700"
                                                            @click="openCreateSatuan"
                                                            title="Tambah satuan baru"
                                                        >
                                                            +
                                                        </button>
                                                    </div>
                                                    <p
                                                        v-if="addFormErrors.entries && addFormErrors.entries[rIdx]?.satuan"
                                                        class="mt-0.5 text-[10px] text-red-600"
                                                    >
                                                        {{ addFormErrors.entries[rIdx]?.satuan }}
                                                    </p>
                                                </td>
                                                <td class="px-2 py-1 align-top">
                                                    <input
                                                        v-model="row.harga"
                                                        type="number"
                                                        min="0"
                                                        class="w-full rounded border px-2 py-1"
                                                        :class="{ 'border-red-500': addFormErrors.entries && addFormErrors.entries[rIdx]?.harga }"
                                                        placeholder="0"
                                                    />
                                                    <p
                                                        v-if="addFormErrors.entries && addFormErrors.entries[rIdx]?.harga"
                                                        class="mt-0.5 text-[10px] text-red-600"
                                                    >
                                                        {{ addFormErrors.entries[rIdx]?.harga }}
                                                    </p>
                                                </td>
                                                <td class="px-2 py-1 align-top">
                                                    <input
                                                        v-model="row.spesifikasi"
                                                        type="text"
                                                        class="w-full rounded border px-2 py-1"
                                                        :class="{
                                                            'border-red-500': addFormErrors.entries && addFormErrors.entries[rIdx]?.spesifikasi,
                                                        }"
                                                        placeholder="Spesifikasi (opsional)"
                                                    />
                                                    <p
                                                        v-if="addFormErrors.entries && addFormErrors.entries[rIdx]?.spesifikasi"
                                                        class="mt-0.5 text-[10px] text-red-600"
                                                    >
                                                        {{ addFormErrors.entries[rIdx]?.spesifikasi }}
                                                    </p>
                                                </td>
                                                <td class="px-2 py-1 text-center align-top">
                                                    <button
                                                        type="button"
                                                        class="rounded bg-red-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-red-700 disabled:opacity-40"
                                                        :disabled="addForm.entries.length === 1"
                                                        @click="removeEntryRow(rIdx)"
                                                        title="Hapus baris"
                                                    >
                                                        x
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-if="addFormErrors.tahun" class="mt-2 text-xs text-red-600">{{ addFormErrors.tahun }}</p>
                            </div>
                        </div>
                    </ConfirmModal>

                    <!-- Create Satuan Modal -->
                    <ConfirmModal
                        :open="showCreateSatuanModal"
                        title="Tambah Satuan Baru"
                        confirm-text="Simpan"
                        cancel-text="Batal"
                        variant="info"
                        max-width="md"
                        @cancel="closeCreateSatuan"
                        @confirm="saveCreateSatuan"
                    >
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-muted-foreground uppercase">Nama Satuan</label>
                                <input
                                    v-model="createSatuanForm.nama"
                                    type="text"
                                    class="w-full rounded border px-3 py-2"
                                    :class="{ 'border-red-500 focus:ring-red-300': createSatuanErrors.nama }"
                                    placeholder="Contoh: unit, buah, meter, kg"
                                    @keyup.enter="saveCreateSatuan"
                                />
                                <p v-if="createSatuanErrors.nama" class="mt-1 text-xs text-red-600">{{ createSatuanErrors.nama }}</p>
                            </div>
                            <div
                                class="rounded border border-blue-200 bg-blue-50 p-3 text-xs text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
                            >
                                <i class="fa fa-info-circle mr-1"></i>
                                Satuan akan otomatis tersedia di dropdown setelah berhasil ditambahkan.
                            </div>
                        </div>
                    </ConfirmModal>

                    <!-- After the main rekening & SSH table -->
                    <div
                        v-if="sshQuery && totalFilteredCount === 0"
                        class="mt-4 rounded border border-amber-400 bg-amber-50 p-3 text-[12px] font-medium text-amber-700 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-300"
                    >
                        Tidak ada item SSH dengan nama yang cocok untuk: "{{ sshQuery }}".
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
