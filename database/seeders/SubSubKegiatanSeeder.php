<?php

namespace Database\Seeders;

use App\Models\SubKegiatan;
use App\Models\SubSubKegiatan;
use Illuminate\Database\Seeder;

class SubSubKegiatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subKegiatan = SubKegiatan::where('kode', '1.02.01.10.001')->first();

        if (! $subKegiatan) {
            // Sub Kegiatan not found; nothing to seed.
            return;
        }

        $items = [
            ['kode' => '1.02.01.10.001.1.01', 'nama' => 'PENYEDIAAN PERLENGKAPAN PERALATAN KEDOKTERAN /ALAT KERJA MEDIS'],
            ['kode' => '1.02.01.10.001.1.02', 'nama' => 'PENYEDIAAN PERLENGKAPAN PERALATAN NON MEDIS/ALAT KERJA NON MEDIS'],
            ['kode' => '1.02.01.10.001.1.03', 'nama' => 'PEMELIHARAAN PERALATAN MEDIS (KEDOKTERAN) RUMAH SAKIT'],
            ['kode' => '1.02.01.10.001.1.04', 'nama' => 'PEMENUHAN OPERASIONAL PELAYANAN MEDIS'],
            ['kode' => '1.02.01.10.001.1.05', 'nama' => 'PENYEDIAAN OBAT DAN PERBEKALAN DI INSTALASI FARMASI'],
            ['kode' => '1.02.01.10.001.1.06', 'nama' => 'PENYEDIAAN BAHAN DAN PERBEKALAN DI INSTALASI RADIOLOGI'],
            ['kode' => '1.02.01.10.001.1.07', 'nama' => 'PENYEDIAAN DAN PERBEKALAN DI INSTALASI LABORATORIUM'],
            ['kode' => '1.02.01.10.001.1.08', 'nama' => 'PELAYANAN INSTALASI GIZI'],
            ['kode' => '1.02.01.10.001.2.01', 'nama' => 'PENYEDIAAN JASA KOMUNIKASI SUMBER DAYA AIR DAN LISTRIK'],
            ['kode' => '1.02.01.10.001.2.02', 'nama' => 'PENYEDIAAN ALAT TULIS KANTOR (ATK)'],
            ['kode' => '1.02.01.10.001.2.03', 'nama' => 'PEMELIHARAAN RUTIN/BERKALA RUMAH SAKIT'],
            ['kode' => '1.02.01.10.001.2.05', 'nama' => 'PENYEDIAAAN PERALATAN KEBERSIHAN DAN BAHAN PEMBERSIH'],
            ['kode' => '1.02.01.10.001.2.06', 'nama' => 'PENYEDIAAN MAKAN DAN MINUM'],
            ['kode' => '1.02.01.10.001.2.07', 'nama' => 'PENGADAAN PAKAIAN KERJA DAN DINAS BESERTA ATRIBUTNYA'],
            ['kode' => '1.02.01.10.001.2.09', 'nama' => 'PENDIDIKAN  DAN PELATIHAN FORMAL'],
            ['kode' => '1.02.01.10.001.2.10', 'nama' => 'PENYEDIAAN BARANG CETAKAN DAN PENGGANDAAN'],
            ['kode' => '1.02.01.10.001.2.11', 'nama' => 'PENYEDIAAN JASA OPERASIONAL'],
            ['kode' => '1.02.01.10.001.2.12', 'nama' => 'PEMELIHARAAN RUTIN / BERKALA MOBIL DINAS/OPERASIONAL'],
            ['kode' => '1.02.01.10.001.2.13', 'nama' => 'PENINGKATAN PEMANFAATAN SARANA KESEHATAN (PROMOSI KESEHATAN RUMAH SAKIT)'],
            ['kode' => '1.02.01.10.001.2.14', 'nama' => 'PENGADAAN PERALATAN/PERLENGKAPAN KANTOR/ RUMAH TANGGA RUMAH SAKIT'],
            ['kode' => '1.02.01.10.001.2.15', 'nama' => 'PENGADAAN ALAT-ALAT KEDOKTERAN RUMAH SAKIT'],
            ['kode' => '1.02.01.10.001.2.18', 'nama' => 'PEMBANGUNAN GEDUNG RAWAT JALAN TERPADU DAN DIAGNOSTIC CENTRE'],
            ['kode' => '1.02.01.10.001.2.21', 'nama' => 'PENYEDIAAN JASA TENAGA AHLI'],
            ['kode' => '1.02.01.10.001.2.22', 'nama' => 'PENYEDIAAN REMUNERASI'],
            ['kode' => '1.02.01.10.001.2.23', 'nama' => 'RAPAT-RAPAT KOORDINASI, KONSULTASI DAN PERJALANAN DINAS PELAYANAN DALAM KOTA DAN LUAR KOTA'],
            ['kode' => '1.02.01.10.001.2.24', 'nama' => 'PEMENUHAN OPERASIONAL UMUM DAN OPERASIONAL PELAYANAN MEDIS'],
        ];

        foreach ($items as $item) {
            SubSubKegiatan::updateOrCreate(
                ['kode' => $item['kode']],
                ['sub_kegiatan_id' => $subKegiatan->id, 'nama' => $item['nama']]
            );
        }
    }
}
