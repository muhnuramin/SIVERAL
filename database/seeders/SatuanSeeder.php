<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    public function run()
    {
        $list = [
            'bendel',
            'biji',
            'botol',
            'buah',
            'bulan',
            'dos',
            'kaleng',
            'kg',
            'kotak',
            'lembar',
            'liter',
            'lonjor',
            'ls',
            'meter',
            'orang',
            'pack',
            'paket',
            'pak',
            'pak kecil',
            'pcs',
            'pkt',
            'rim',
            'rol',
            'roll',
            'sak',
            'sampel',
            'set',
            'titik',
            'tahun',
            'uk',
            'unit',
            'bungkus'
        ];
        
        foreach ($list as $name) {
            DB::table('satuans')->insertOrIgnore([
                'nama' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
