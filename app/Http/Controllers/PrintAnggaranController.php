<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PrintAnggaranController extends Controller
{
    public function show(Request $request)
    {
        $ssk = $request->query('ssk');
        $year = $request->query('year');
        // Ambil data nyata: cari SubSubKegiatan dan rekenings terkait
        $sub = null;
        $rekeningItems = [];
        if ($ssk) {
            $sub = \App\Models\SubSubKegiatan::where('kode', $ssk)->first();
        }

        // Jika ditemukan, ambil Rekening (chart of accounts) yang terkait
        if ($sub) {
            $rekenings = $sub->rekenings()->get();
            foreach ($rekenings as $r) {
                $rekeningItems[] = [
                    'rekening' => $r->koderekening ?? null,
                    'nama' => $r->namarekening ?? null,
                    'satuan' => null,
                    'spesifikasi' => null,
                    'harga' => null,
                ];
            }
        }

        // Tambahan: ambil harga SSH items untuk tahun terkait (jika ada)
        if ($year) {
            $sshPrices = \App\Models\RekeningSshItemHarga::with('item')
                ->where('tahun', $year)
                ->get();
            foreach ($sshPrices as $p) {
                $rekeningItems[] = [
                    'rekening' => $p->item->rekening_kode ?? null,
                    'nama' => $p->item->nama ?? null,
                    'satuan' => $p->item->satuan ?? null,
                    'spesifikasi' => $p->item->spesifikasi ?? null,
                    'harga' => $p->harga ?? null,
                ];
            }
        }

        return Inertia::render('anggaran/PrintAnggaran', [
            'ssk' => $ssk,
            'year' => $year,
            'sub' => $sub,
            'rekeningItems' => $rekeningItems,
        ]);
    }
}
