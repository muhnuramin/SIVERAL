<?php

namespace App\Http\Controllers;

use App\Models\RkaItemPlan;
use App\Models\RekeningSshItem;
use App\Models\RekeningSshItemHarga;
use App\Models\SubSubKegiatan;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EvaluasiController extends Controller
{
    public function index(Request $request)
    {
        $tahun = (int)($request->get('year') ?: now()->year);
        $user = Auth::user();

        // Base query menggunakan SQL yang diminta user
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
                'ssp.verified_at'
            ]);

        // Filter by PIC jika user bukan Super Admin (per hak akses grup)
        $isSuperAdmin = false;
        if (isset($user->hakaksesgrub) && is_array($user->hakaksesgrub->hak_akses ?? null)) {
            $isSuperAdmin = in_array('Super Admin', $user->hakaksesgrub->hak_akses ?? []);
        }

        if (! $isSuperAdmin) {
            $baseQuery->join('sub_sub_kegiatan_user as ssku', 'ssku.sub_sub_kegiatan_id', '=', 'ssk.id')
                ->where('ssku.user_id', $user->id);
        }

        // Ambil semua data
        $allItems = $baseQuery->get();

        if ($allItems->isEmpty()) {
            return Inertia::render('evaluasi/Index', [
                'tahun' => $tahun,
                'data' => ['ssk' => []],
            ]);
        }

        // Group by sub_sub_kegiatan
        $groupedBySsk = $allItems->groupBy('sub_sub_kegiatan_id');

        // Ambil existing volume plans
        $sskIds = $groupedBySsk->keys()->toArray();
        $plans = RkaItemPlan::whereIn('sub_sub_kegiatan_id', $sskIds)
            ->where('tahun', $tahun)
            ->get()
            ->groupBy('sub_sub_kegiatan_id')
            ->map(function($planGroup) {
                return $planGroup->keyBy('rekening_ssh_item_id');
            });

        // Build output
        $output = $groupedBySsk->map(function($items, $sskId) use ($plans) {
            $firstItem = $items->first();
            $existingPlans = $plans->get($sskId, collect());

            $planEntries = $items->map(function($item) use ($existingPlans) {
                $existingPlan = $existingPlans->get($item->item_id);
                
                return [
                    'plan_id' => $existingPlan?->id,
                    'item_id' => $item->item_id,
                    'nama' => $item->ssh_nama,
                    'satuan' => $item->satuan,
                    'harga' => (int)$item->harga,
                    'vol' => $existingPlan ? [
                        'jan' => $existingPlan->jan_vol,
                        'feb' => $existingPlan->feb_vol,
                        'mar' => $existingPlan->mar_vol,
                        'apr' => $existingPlan->apr_vol,
                        'mei' => $existingPlan->mei_vol,
                        'jun' => $existingPlan->jun_vol,
                        'jul' => $existingPlan->jul_vol,
                        'agu' => $existingPlan->agu_vol,
                        'sep' => $existingPlan->sep_vol,
                        'okt' => $existingPlan->okt_vol,
                        'nov' => $existingPlan->nov_vol,
                        'des' => $existingPlan->des_vol,
                    ] : [
                        'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
                        'mei' => 0, 'jun' => 0, 'jul' => 0, 'agu' => 0,
                        'sep' => 0, 'okt' => 0, 'nov' => 0, 'des' => 0,
                    ],
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

        return Inertia::render('evaluasi/Index', [
            'tahun' => $tahun,
            'data' => ['ssk' => $output],
        ]);
    }

    public function bulkSave(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer',
            'rows' => 'array',
            'rows.*.sub_sub_kegiatan_id' => 'required|integer|exists:sub_sub_kegiatans,id',
            'rows.*.item_id' => 'required|integer|exists:rekening_ssh_items,id',
            'rows.*.jan_vol' => 'nullable|integer|min:0',
            'rows.*.feb_vol' => 'nullable|integer|min:0',
            'rows.*.mar_vol' => 'nullable|integer|min:0',
            'rows.*.apr_vol' => 'nullable|integer|min:0',
            'rows.*.mei_vol' => 'nullable|integer|min:0',
            'rows.*.jun_vol' => 'nullable|integer|min:0',
            'rows.*.jul_vol' => 'nullable|integer|min:0',
            'rows.*.agu_vol' => 'nullable|integer|min:0',
            'rows.*.sep_vol' => 'nullable|integer|min:0',
            'rows.*.okt_vol' => 'nullable|integer|min:0',
            'rows.*.nov_vol' => 'nullable|integer|min:0',
            'rows.*.des_vol' => 'nullable|integer|min:0',
        ]);
        $tahun = (int)$validated['tahun'];
        foreach ($validated['rows'] as $row) {
            $plan = RkaItemPlan::firstOrNew([
                'sub_sub_kegiatan_id' => $row['sub_sub_kegiatan_id'],
                'rekening_ssh_item_id' => $row['item_id'],
                'tahun' => $tahun,
            ]);
            foreach (['jan','feb','mar','apr','mei','jun','jul','agu','sep','okt','nov','des'] as $m) {
                $plan->{$m.'_vol'} = (int)($row[$m.'_vol'] ?? 0);
            }
            $plan->save();
        }
        return back()->with('success','Rencana bulanan tersimpan');
    }
}
