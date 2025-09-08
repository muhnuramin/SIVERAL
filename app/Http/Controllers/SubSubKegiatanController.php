<?php

namespace App\Http\Controllers;

use App\Models\SubSubKegiatan;
use App\Models\SubKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubSubKegiatanController extends Controller
{
    public function index(Request $request)
    {
        $subId = $request->query('sub_kegiatan_id');
        $sub = null;

        if ($subId) {
            $sub = SubKegiatan::with(['subSubKegiatans' => function ($q) {
                $q->orderBy('id', 'desc');
            }])->find($subId);

            $items = $sub ? $sub->sub_sub_kegiatans : collect([]);
        } else {
            $items = SubSubKegiatan::orderBy('id', 'desc')->get();
        }

        return Inertia::render('master/SubSubKegiatan', [
            'sub_sub_kegiatans' => $items,
            'sub_kegiatan' => $sub,
        ]);
    }

    public function store(Request $request)
    {
        // support batch creation via sub_sub_kegiatans[] or single payload
        if ($request->has('sub_sub_kegiatans') && is_array($request->input('sub_sub_kegiatans'))) {
            Log::info('SubSubKegiatan batch payload', $request->all());
            $data = $request->validate([
                'sub_kegiatan_id' => 'required|exists:sub_kegiatans,id',
                'sub_sub_kegiatans' => 'required|array|min:1',
                'sub_sub_kegiatans.*.kode' => 'required|string|max:60',
                'sub_sub_kegiatans.*.nama' => 'required|string|max:255',
            ]);

            $created = 0;
            $details = [];
            DB::beginTransaction();
            try {
                foreach ($data['sub_sub_kegiatans'] as $i => $s) {
                    $exists = SubSubKegiatan::where('kode', $s['kode'])->exists();
                    if ($exists) {
                        $details[] = ['index' => $i, 'kode' => $s['kode'], 'status' => 'skipped', 'reason' => 'duplicate_kode'];
                        continue;
                    }
                    $createdModel = SubSubKegiatan::create([
                        'sub_kegiatan_id' => $data['sub_kegiatan_id'],
                        'kode' => $s['kode'],
                        'nama' => $s['nama'],
                    ]);
                    $created++;
                    $details[] = ['index' => $i, 'id' => $createdModel->id, 'status' => 'created'];
                }
                DB::commit();
                if ($request->wantsJson()) return response()->json(['created' => $created, 'details' => $details]);
                return redirect()->back()->with('success', "$created sub-sub(s) dibuat");
            } catch (\Exception $e) {
                DB::rollBack();
                if ($request->wantsJson()) return response()->json(['error' => $e->getMessage()], 500);
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        $data = $request->validate([
            'sub_kegiatan_id' => 'required|exists:sub_kegiatans,id',
            'kode' => 'required|string|max:60|unique:sub_sub_kegiatans,kode',
            'nama' => 'required|string|max:255',
        ]);

        $item = SubSubKegiatan::create($data);

        return redirect()->back()->with('success', 'Sub-Sub Kegiatan dibuat');
    }

    public function update(Request $request, $id)
    {
        $item = SubSubKegiatan::findOrFail($id);
        $data = $request->validate([
            'kode' => 'required|string|max:60|unique:sub_sub_kegiatans,kode,' . $item->id,
            'nama' => 'required|string|max:255',
        ]);
        $item->update($data);

        return redirect()->back()->with('success', 'Sub-Sub Kegiatan diupdate');
    }

    public function getSubSubKegiatanByIdSubKegiatan($id)
    {
        $subKegiatan = SubSubKegiatan::where('sub_kegiatan_id',$id)->orderBy('id','desc')->get();
        return response()->json(['sub_sub_kegiatans' => $subKegiatan]);
    }

    public function destroy($id)
    {
        $item = SubSubKegiatan::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Sub-Sub Kegiatan dihapus');
    }
}
