<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tahun = (int)($request->get('year') ?: now()->year);

        // Total Anggaran BLUD (sumber_anggaran_id = 1)
        $totalAnggaranBlud = (int) DB::table('sumber_anggaran_pagus')
            ->where('sumber_anggaran_id', 1)
            ->where('tahun', $tahun)
            ->sum('pagu');

        // Total Digunakan dari Pelaporan (scope to items with harga for selected tahun)
        $totalDigunakan = (int) DB::table('pelaporan as p')
            ->join('rekening_ssh_item_hargas as rsh', function ($j) use ($tahun) {
                $j->on('rsh.rekening_ssh_item_id', '=', 'p.item_id')
                  ->where('rsh.tahun', '=', $tahun);
            })
            ->sum('p.rupiah');

        $sisa = max(0, $totalAnggaranBlud - $totalDigunakan);

        // Monthly Pelaporan totals (jan..des)
        $pelaporanRaw = DB::table('pelaporan as p')
            ->join('rekening_ssh_item_hargas as rsh', function ($j) use ($tahun) {
                $j->on('rsh.rekening_ssh_item_id', '=', 'p.item_id')
                  ->where('rsh.tahun', '=', $tahun);
            })
            ->selectRaw('p.bulan, SUM(p.rupiah) as total')
            ->groupBy('p.bulan')
            ->pluck('total', 'bulan');

        $months = ['jan','feb','mar','apr','mei','jun','jul','agu','sep','okt','nov','des'];
        $monthlyPelaporan = [];
        foreach ($months as $m) {
            $monthlyPelaporan[] = (int) ($pelaporanRaw[$m] ?? 0);
        }

        // Monthly Evaluasi totals: SUM(vol_x * harga) per month
        $evalRow = DB::table('rka_item_plans as rip')
            ->join('rekening_ssh_item_hargas as rsh', function ($j) use ($tahun) {
                $j->on('rsh.rekening_ssh_item_id', '=', 'rip.rekening_ssh_item_id')
                  ->where('rsh.tahun', '=', $tahun);
            })
            ->where('rip.tahun', $tahun)
            ->selectRaw('
                SUM(COALESCE(rip.jan_vol,0) * rsh.harga) as jan,
                SUM(COALESCE(rip.feb_vol,0) * rsh.harga) as feb,
                SUM(COALESCE(rip.mar_vol,0) * rsh.harga) as mar,
                SUM(COALESCE(rip.apr_vol,0) * rsh.harga) as apr,
                SUM(COALESCE(rip.mei_vol,0) * rsh.harga) as mei,
                SUM(COALESCE(rip.jun_vol,0) * rsh.harga) as jun,
                SUM(COALESCE(rip.jul_vol,0) * rsh.harga) as jul,
                SUM(COALESCE(rip.agu_vol,0) * rsh.harga) as agu,
                SUM(COALESCE(rip.sep_vol,0) * rsh.harga) as sep,
                SUM(COALESCE(rip.okt_vol,0) * rsh.harga) as okt,
                SUM(COALESCE(rip.nov_vol,0) * rsh.harga) as nov,
                SUM(COALESCE(rip.des_vol,0) * rsh.harga) as des
            ')
            ->first();

        $monthlyEvaluasi = [];
        foreach ($months as $m) {
            $monthlyEvaluasi[] = (int) ($evalRow?->$m ?? 0);
        }

        // Pagu per Sub-Sub Kegiatan (allocation level) for selected year
        $subPaguRows = DB::table('sub_sub_kegiatan_pagus as sskp')
            ->join('sub_sub_kegiatans as ssk', 'ssk.id', '=', 'sskp.sub_sub_kegiatan_id')
            ->where('sskp.tahun', $tahun)
            ->where('sskp.sumber_anggaran_id', 1)
            ->selectRaw('ssk.nama as nama, SUM(sskp.pagu) as total')
            ->groupBy('ssk.nama')
            ->orderByDesc('total')
            ->get();

        $paguLabels = [];
        $paguValues = [];
        $totalPaguSub = 0;
        foreach ($subPaguRows as $r) {
            $paguLabels[] = (string) $r->nama;
            $val = (int) $r->total;
            $paguValues[] = $val;
            $totalPaguSub += $val;
        }

        $lebihan = max(0, $totalPaguSub - $totalAnggaranBlud);
        $sisaPagu = max(0, $totalAnggaranBlud - $totalPaguSub);

        return Inertia::render('Dashboard', [
            'year' => $tahun,
            'cards' => [
                'totalAnggaranBlud' => $totalAnggaranBlud,
                'totalDigunakan' => $totalDigunakan,
                'sisa' => $sisa,
            ],
            'series' => [
                'months' => $months,
                'evaluasi' => $monthlyEvaluasi,
                'pelaporan' => $monthlyPelaporan,
            ],
            'subKegiatanPagu' => [
                'labels' => $paguLabels,
                'values' => $paguValues,
                'totalPagu' => $totalPaguSub,
                'totalAnggaran' => $totalAnggaranBlud,
                'lebihan' => $lebihan,
                'sisa' => $sisaPagu,
            ],
        ]);
    }
}
