<?php

namespace Database\Seeders;

use App\Models\SubSubKegiatan;
use App\Models\SubSubKegiatanPagu;
use Illuminate\Database\Seeder;

class SubSubKegiatanPaguSeeder extends Seeder
{
    /**
     * Seed pagu for each Sub-Sub Kegiatan for selected years.
     */
    public function run(): void
    {
        $years = [now()->year - 1, now()->year];

        $counter = 1;
        SubSubKegiatan::query()
            ->orderBy('kode')
            ->chunk(200, function ($rows) use (&$counter, $years) {
                foreach ($rows as $row) {
                    foreach ($years as $yr) {
                        // Deterministic sample pagu: starts at 5,000,000 and increases per item
                        $pagu = 5_000_000 + ($counter * 125_000);
                        SubSubKegiatanPagu::updateOrCreate(
                            [
                                'sumber_anggaran_id' => 1, // Default to the first funding source
                                'sub_sub_kegiatan_id' => $row->id,
                                'tahun' => $yr,
                            ],
                            [
                                'pagu' => $pagu,
                            ],
                        );
                    }
                    $counter++;
                }
            });
    }
}
