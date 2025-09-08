<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $programId = $request->query('program_id');
        $program = null;

        if ($programId) {
            $program = Program::with(['kegiatans' => function ($q) {
                $q->orderBy('id', 'asc');
            }])->find($programId);

            $kegiatans = $program ? $program->kegiatans : collect([]);
            
            // Add program info to each kegiatan
            $kegiatans = $kegiatans->map(function($kegiatan) use ($program) {
                $kegiatan->program = [
                    'id' => $program->id,
                    'kode' => $program->kode,
                    'nama' => $program->nama
                ];
                return $kegiatan;
            });
        } else {
            // Load all kegiatans with their programs
            $kegiatans = Kegiatan::with('program')->orderBy('id', 'asc')->get();
        }

        return Inertia::render('master/KegiatanIndex', [
            'kegiatans' => $kegiatans,
            'program' => $program,
        ]);
    }

    public function store(Request $request)
    {
        // support batch creation via `kegiatans` array or single payload
        if ($request->has('kegiatans') && is_array($request->input('kegiatans'))) {
            // log incoming payload for debugging
            Log::info('Kegiatan batch payload', $request->all());

            $data = $request->validate([
                'program_id' => 'required|exists:programs,id',
                'kegiatans' => 'required|array|min:1',
                'kegiatans.*.kode' => 'nullable|string|max:30',
                'kegiatans.*.nama' => 'required|string|max:255',
            ]);

            $created = 0;
            $details = [];
            DB::beginTransaction();
            try {
                foreach ($data['kegiatans'] as $index => $p) {
                    $exists = isset($p['kode']) && $p['kode'] ? Kegiatan::where('kode', $p['kode'])->exists() : false;
                    if ($exists) {
                        $details[] = ['index' => $index, 'kode' => $p['kode'] ?? null, 'status' => 'skipped', 'reason' => 'duplicate_kode'];
                        continue;
                    }
                    $createdModel = Kegiatan::create([
                        'program_id' => $data['program_id'],
                        'kode' => $p['kode'] ?? null,
                        'nama' => $p['nama'],
                    ]);
                    $created++;
                    $details[] = ['index' => $index, 'id' => $createdModel->id, 'status' => 'created'];
                }
                DB::commit();

                // if client expects JSON (AJAX/Inertia), return detailed summary
                if ($request->wantsJson()) {
                    return response()->json(['created' => $created, 'details' => $details]);
                }

                return redirect()->back()->with('success', "$created kegiatan(s) berhasil dibuat");
            } catch (\Exception $e) {
                DB::rollBack();
                if ($request->wantsJson()) {
                    return response()->json(['error' => $e->getMessage()], 500);
                }
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        $attrs = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'kode' => 'nullable|string|max:30|unique:kegiatans,kode',
            'nama' => 'required|string|max:255',
        ]);

        $kegiatan = Kegiatan::create($attrs);
        return redirect()->back()->with('success', 'Kegiatan berhasil dibuat');
    }

    public function update(Request $request, $id)
    {
        $kegiatan = Kegiatan::findOrFail($id);

        $attrs = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'kode' => 'nullable|string|max:30|unique:kegiatans,kode,' . $kegiatan->id,
            'nama' => 'required|string|max:255',
        ]);

        $kegiatan->update($attrs);

        return redirect()->back()->with('success', 'Kegiatan berhasil diupdate');
    }

    public function destroy($id)
    {
        $kegiatan = Kegiatan::findOrFail($id);
        $kegiatan->delete();

        return redirect()->back()->with('success', 'Kegiatan berhasil dihapus');
    }
}
