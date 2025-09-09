<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use App\Models\SubSubKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $q = Rekening::with('subSubKegiatan')->orderBy('koderekening');
        if ($request->has('sub_sub_kegiatan_id')) {
            $q->where('id_sub_sub_kegiatan', $request->get('sub_sub_kegiatan_id'));
        }
        $rekenings = $q->get();

        $subsubs = SubSubKegiatan::orderBy('nama')->get(['id', 'kode', 'nama']);

        return Inertia::render('master/Rekening', [
            'rekenings' => $rekenings,
            'subsubs' => $subsubs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'koderekening' => 'required|string|unique:rekenings,koderekening',
            'namarekening' => 'required|string',
            'id_sub_sub_kegiatan' => 'required|integer|exists:sub_sub_kegiatans,id',
            'stAktif' => 'nullable|boolean',
        ]);

        Rekening::create([
            'koderekening' => $validated['koderekening'],
            'namarekening' => $validated['namarekening'],
            'id_sub_sub_kegiatan' => $validated['id_sub_sub_kegiatan'],
            'stAktif' => $validated['stAktif'] ?? true,
        ]);

        return back()->with('success', 'Rekening berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $rekening = Rekening::findOrFail($id);

        $validated = $request->validate([
            'koderekening' => 'required|string|unique:rekenings,koderekening,' . $rekening->id,
            'namarekening' => 'required|string',
            'id_sub_sub_kegiatan' => 'required|integer|exists:sub_sub_kegiatans,id',
            'stAktif' => 'nullable|boolean',
        ]);

        $rekening->update([
            'koderekening' => $validated['koderekening'],
            'namarekening' => $validated['namarekening'],
            'id_sub_sub_kegiatan' => $validated['id_sub_sub_kegiatan'],
            'stAktif' => $validated['stAktif'] ?? $rekening->stAktif,
        ]);

        return back()->with('success', 'Rekening berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $rekening = Rekening::findOrFail($id);

        // Check usage in SSH items
        $isUsed = DB::table('rekening_ssh_items')->where('rekening_kode', $rekening->koderekening)->exists();

        if ($isUsed) {
            return back()->withErrors(['delete' => 'Rekening tidak dapat dihapus karena sedang digunakan.']);
        }

        $rekening->delete();

        return back()->with('success', 'Rekening berhasil dihapus.');
    }

    public function apiList(Request $request)
    {
        $q = Rekening::orderBy('namarekening');
        if ($request->has('sub_sub_kegiatan_id')) {
            $q->where('id_sub_sub_kegiatan', $request->get('sub_sub_kegiatan_id'));
        }

        return response()->json($q->get(['id','koderekening','namarekening','id_sub_sub_kegiatan']));
    }
}
