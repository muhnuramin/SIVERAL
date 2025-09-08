<?php

namespace App\Http\Controllers;

use App\Models\SubKegiatan;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubKegiatanController extends Controller
{
    public function index(Request $request)
    {
        $kegiatanId = $request->query('kegiatan_id');
        $kegiatan = null;

        if ($kegiatanId) {
            $kegiatan=Kegiatan::with(['subKegiatans'=>function($q){
                $q->orderBy('id', 'desc');
            }])->find($kegiatanId);

            $sub_kegiatan=$kegiatan?$kegiatan->sub_kegiatans:collect([]);
        }else{
            $sub_kegiatan=SubKegiatan::orderBy('id', 'desc')->get();
        }

        return Inertia::render('master/SubKegiatanIndex', [
            'sub_kegiatans' => $sub_kegiatan,
            'kegiatan' => $kegiatan,
        ]);
    }

    public function store(Request $request)
    {
        // support batch via sub_kegiatans[] or single payload
        if ($request->has('sub_kegiatans') && is_array($request->input('sub_kegiatans'))) {
            Log::info('SubKegiatan batch payload', $request->all());
            $data = $request->validate([
                'kegiatan_id' => 'required|exists:kegiatans,id',
                'sub_kegiatans' => 'required|array|min:1',
                'sub_kegiatans.*.kode' => 'required|string|max:50',
                'sub_kegiatans.*.nama' => 'required|string|max:255',
            ]);

            $created = 0;
            $details = [];
            DB::beginTransaction();
            try {
                foreach ($data['sub_kegiatans'] as $i => $s) {
                    $exists = SubKegiatan::where('kode', $s['kode'])->exists();
                    if ($exists) {
                        $details[] = ['index' => $i, 'kode' => $s['kode'], 'status' => 'skipped', 'reason' => 'duplicate_kode'];
                        continue;
                    }
                    $createdModel = SubKegiatan::create([
                        'kegiatan_id' => $data['kegiatan_id'],
                        'kode' => $s['kode'],
                        'nama' => $s['nama'],
                    ]);
                    $created++;
                    $details[] = ['index' => $i, 'id' => $createdModel->id, 'status' => 'created'];
                }
                DB::commit();
                if ($request->wantsJson()) {
                    return response()->json(['created' => $created, 'details' => $details]);
                }
                return redirect()->back()->with('success', "$created sub kegiatan(s) dibuat");
            } catch (\Exception $e) {
                DB::rollBack();
                if ($request->wantsJson()) return response()->json(['error' => $e->getMessage()], 500);
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        $data = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatans,id',
            'kode' => 'required|string|max:50|unique:sub_kegiatans,kode',
            'nama' => 'required|string|max:255',
        ]);

        $sub = SubKegiatan::create($data);
        return redirect()->back()->with('success', 'Sub kegiatan dibuat');
    }

    public function update(Request $request, $id)
    {
        $sub = SubKegiatan::findOrFail($id);
        $data = $request->validate([
            'kode' => 'required|string|max:50|unique:sub_kegiatans,kode,' . $sub->id,
            'nama' => 'required|string|max:255',
        ]);
        $sub->update($data);
        return redirect()->back()->with('success', 'Sub kegiatan diupdate');
    }

    public function getSubKegiatanByIdKegiatan($id)
    {
        $subs = SubKegiatan::where('kegiatan_id', $id)->orderBy('id', 'desc')->get();
        return response()->json(['sub_kegiatans' => $subs]);
    }

    public function destroy($id)
    {
        $sub = SubKegiatan::findOrFail($id);
        $sub->delete();
        return redirect()->back()->with('success', 'Sub kegiatan dihapus');
    }
}
