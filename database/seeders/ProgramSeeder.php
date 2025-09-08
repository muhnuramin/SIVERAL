<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            ['kode' => '1.02.01', 'nama' => 'Program Penunjang Urusan Pemerintahan Dfaerah Kabupaten / Kota'],
        ];

        foreach ($programs as $p) {
            Program::create($p);
        }
    }
}
