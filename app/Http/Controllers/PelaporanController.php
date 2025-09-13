<?php
namespace App\Http\Controllers;

use App\Models\Pelaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PelaporanController extends Controller
{
    public function index(Request $request)
    {
        $tahun = (int)($request->get('year') ?: now()->year);
        $user = Auth::user();

        // Ambil daftar item per sub-sub kegiatan seperti Evaluasi (hanya yang terverifikasi)
        $baseQuery = DB::table('rekening_ssh_items as rsi')
            ->join('rekening_ssh_item_hargas as rsh', 'rsh.rekening_ssh_item_id', '=', 'rsi.id')
            ->join('rekenings as r', 'r.koderekening', '=', 'rsi.rekening_kode')
            ->join('sub_sub_kegiatans as ssk', 'ssk.id', '=', 'r.id_sub_sub_kegiatan')
            ->join('sub_sub_kegiatan_pagus as ssp', 'ssp.sub_sub_kegiatan_id', '=', 'ssk.id')
            ->where('rsh.tahun', $tahun)
            ->where('ssp.tahun', $tahun)
            ->whereNotNull('ssp.verified_at')
            ->select([
                'rsi.id as item_id',
                'rsi.nama as ssh_nama',
                'rsi.satuan',
                'rsh.harga',
                'ssk.id as sub_sub_kegiatan_id',
                'ssk.nama as sub_sub_kegiatan',
                'ssk.kode as sub_sub_kegiatan_kode',
                'ssp.pagu',
            ]);

        // Filter by PIC jika bukan super admin
        $isSuperAdmin = false;
        if (isset($user->hakaksesgrub) && is_array($user->hakaksesgrub->hak_akses ?? null)) {
            $isSuperAdmin = in_array('Super Admin', $user->hakaksesgrub->hak_akses ?? []);
        }
        if (! $isSuperAdmin) {
            $baseQuery->join('sub_sub_kegiatan_user as ssku', 'ssku.sub_sub_kegiatan_id', '=', 'ssk.id')
                ->where('ssku.user_id', $user->id);
        }

        $allItems = $baseQuery->get();

        if ($allItems->isEmpty()) {
            return Inertia::render('pelaporan/Index', [
                'tahun' => $tahun,
                'data' => ['ssk' => []],
            ]);
        }

        $groupedBySsk = $allItems->groupBy('sub_sub_kegiatan_id');
        $itemIds = $allItems->pluck('item_id')->all();

        // Ambil existing pelaporan untuk prefilling: [item_id][bulan] => ['vol'=>..,'rupiah'=>..]
        $existingPelaporan = Pelaporan::whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id')
            ->map(function ($rows) {
                return $rows->keyBy('bulan');
            });

        $months = ['jan','feb','mar','apr','mei','jun','jul','agu','sep','okt','nov','des'];

        $output = $groupedBySsk->map(function($items, $sskId) use ($existingPelaporan, $months) {
            $firstItem = $items->first();

            $planEntries = $items->map(function($item) use ($existingPelaporan, $months) {
                $pref = $existingPelaporan->get($item->item_id, collect());
                $vol = [];
                $rupiah = [];
                foreach ($months as $m) {
                    $row = $pref->get($m);
                    $vol[$m] = (int)($row->vol ?? 0);
                    $rupiah[$m] = (int)($row->rupiah ?? 0);
                }
                return [
                    'item_id' => (int)$item->item_id,
                    'nama' => $item->ssh_nama,
                    'satuan' => $item->satuan,
                    'harga' => (int)$item->harga,
                    'vol' => $vol,
                    'rupiah' => $rupiah,
                ];
            });

            return [
                'id' => (int)$sskId,
                'kode' => $firstItem->sub_sub_kegiatan_kode,
                'nama' => $firstItem->sub_sub_kegiatan,
                'pagu' => (int)$firstItem->pagu,
                'plans' => $planEntries->sortBy('nama')->values(),
            ];
        })->values();

        return Inertia::render('pelaporan/Index', [
            'tahun' => $tahun,
            'data' => ['ssk' => $output],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.item_id' => 'required|integer|exists:rekening_ssh_items,id',
            'data.*.bulan' => 'required|string|in:jan,feb,mar,apr,mei,jun,jul,agu,sep,okt,nov,des',
            'data.*.vol' => 'nullable|integer|min:0',
            'data.*.rupiah' => 'nullable|integer|min:0',
        ]);

        foreach ($validated['data'] as $row) {
            $rec = Pelaporan::firstOrNew([
                'item_id' => (int)$row['item_id'],
                'bulan' => $row['bulan'],
            ]);
            $rec->vol = (int)($row['vol'] ?? 0);
            $rec->rupiah = (int)($row['rupiah'] ?? 0);
            $rec->save();
        }

        return back()->with('success', 'Pelaporan tersimpan');
    }
}
