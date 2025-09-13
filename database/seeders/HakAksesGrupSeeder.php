<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HakAksesGrup;

class HakAksesGrupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['nama' => 'Admin', 'hak_akses' => ["Super Admin"]],
        ];

        foreach ($groups as $g) {
            HakAksesGrup::create($g);
        }
    }
}
