<?php

namespace Database\Seeders;

use App\Models\Kegiatan;
use App\Models\Program;
use Illuminate\Database\Seeder;

class KegiatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $program1 = Program::where('kode', '1.02.01')->first();

        if ($program1) {
            Kegiatan::create(['program_id' => $program1->id, 'kode' => '1.02.01.10', 'nama' => 'Peningkatan Pelayanan BLUD']);
        }

    }
}
