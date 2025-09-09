<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HakAksesGrup;

class HakAksesGrupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['nama' => 'Admin', 'hak_akses' => ['user.create','user.update','user.delete','settings.manage']],
            ['nama' => 'Operator', 'hak_akses' => ['rka.view','rka.edit']],
            ['nama' => 'Viewer', 'hak_akses' => ['rka.view']],
        ];

        foreach ($groups as $g) {
            HakAksesGrup::create($g);
        }
    }
}
