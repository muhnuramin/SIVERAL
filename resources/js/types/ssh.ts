// Shared SSH-related form types
// Keeping these isolated allows reuse across multiple components (add/edit modals, composables, etc.)
export interface AddEntry {
    nama: string;
    satuan: string;
    harga: number | '';
    spesifikasi?: string;
}

export interface AddForm {
    rekening: string;
    tahun: number;
    entries: AddEntry[];
}
