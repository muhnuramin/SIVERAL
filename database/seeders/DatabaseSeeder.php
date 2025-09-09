<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    // Run the user seeder which creates some default users
    $this->call([
        // 1. Users (needed for any FK to users later)
        UserSeeder::class,
        // 2. Hierarchy top-down
        ProgramSeeder::class,
        KegiatanSeeder::class,
        SubKegiatanSeeder::class,
        SubSubKegiatanSeeder::class,
        // 3. Master funding sources before pagu rows
        SumberAnggaranSeeder::class,
        SumberAnggaranPaguSeeder::class,
        // 4. Pagu for each Sub-Sub (needs hierarchy + sumber anggaran)
        SubSubKegiatanPaguSeeder::class,
        // 5. Rekening items referencing sub_sub_kegiatans
        RekeningSeeder::class,
        // 6. SSH Items and prices
        RekeningSSHItemSeeder::class,
    // Hak akses groups
    \Database\Seeders\HakAksesGrupSeeder::class,
        // 7. Satuan master data
        SatuanSeeder::class,
    ]);
    }
}
