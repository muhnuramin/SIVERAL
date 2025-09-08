import { computed, type Ref } from 'vue';
// Utilitas pemformatan mata uang
// Helper yang dapat digunakan kembali untuk format Rupiah Indonesia di seluruh aplikasi.
// Penggunaan: import { formatIDR } from '@/utils/currency';

export function formatIDR(value: number | string | null | undefined): string {
    const n = typeof value === 'string' ? parseFloat(value.replace(/[^0-9.-]/g, '')) : Number(value || 0);
    if (!Number.isFinite(n)) return 'Rp 0';
    return `Rp ${n.toLocaleString('id-ID')}`;
}

// Menghapus format dan mengembalikan angka (misal dari input string "12.345")
export function parseIDR(input: string | number | null | undefined): number {
    if (input == null || input === '') return 0;
    const raw = typeof input === 'number' ? input.toString() : input;
    const digits = raw.replace(/[^0-9]/g, '');
    if (!digits) return 0;
    return parseInt(digits, 10) || 0;
}

// Helper generic untuk pemisah ribuan pada angka (tanpa awalan mata uang)
// Menyediakan computed ref sebagai facade di sekitar source ref<number | ''>.
// Contoh penggunaan:
//   const jumlah = ref<number | ''>('');
//   const jumlahTeks = useThousandSeparated(jumlah);
//   <input v-model="jumlahTeks" />

export function useThousandSeparated(source: Ref<number | ''>) {
    return computed<string>({
        get() {
            const v = source.value;
            if (v === '' || v == null) return '';
            const n = typeof v === 'number' ? v : parseInt(String(v), 10);
            if (!Number.isFinite(n)) return '';
            return n.toLocaleString('id-ID');
        },
        set(val: string) {
            const digits = (val || '').replace(/[^0-9]/g, '');
            if (!digits) {
                source.value = '' as any;
                return;
            }
            const n = parseInt(digits, 10);
            source.value = Number.isFinite(n) ? (n as any) : '';
        },
    });
}
