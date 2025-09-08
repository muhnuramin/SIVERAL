<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SumberAnggaranPaguSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');
        $ids = DB::table('sumber_anggarans')->where('stAktif', true)->pluck('id');
        foreach ($ids as $id) {
            DB::table('sumber_anggaran_pagus')->insertOrIgnore([
                'sumber_anggaran_id' => $id,
                'tahun' => $year,
                'pagu' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
