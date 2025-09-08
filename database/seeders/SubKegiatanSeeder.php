<?php

namespace Database\Seeders;

use App\Models\Kegiatan;
use App\Models\SubKegiatan;
use Illuminate\Database\Seeder;

class SubKegiatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $k1 = Kegiatan::where('kode', '1.02.01.10')->first();

        if ($k1) {
            SubKegiatan::create(['kegiatan_id' => $k1->id, 'kode' => '1.02.01.10.001', 'nama' => 'Pelayanan dan Penunjang Pelayanan BLUD']);
        }
    }
}
