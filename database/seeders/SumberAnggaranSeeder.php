<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SumberAnggaranSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['nama' => 'BLUD', 'kode' => 'BLUD', 'stAktif' => true],
        ];
        foreach ($rows as $r) {
            DB::table('sumber_anggarans')->insertOrIgnore($r);
        }
    }
}
