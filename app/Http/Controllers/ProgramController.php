<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::orderBy('id', 'asc')->get();
        
        // If request wants JSON (AJAX), return JSON response
        if (request()->wantsJson()) {
            return response()->json([
                'programs' => $programs
            ]);
        }
        
        return Inertia::render('master/Program', [
            'programs' => $programs,
        ]);
    }

    public function store(Request $request)
    {
        // support batch creation via `programs` array or single payload
        if ($request->has('programs') && is_array($request->input('programs'))) {
            $data = $request->validate([
                'programs' => 'required|array|min:1',
                'programs.*.kode' => 'required|string|max:20|distinct',
                'programs.*.nama' => 'required|string|max:255',
            ]);

            $created = 0;
            DB::beginTransaction();
            try {
                foreach ($data['programs'] as $p) {
                    // enforce DB-unique constraint; skip duplicates gracefully
                    $exists = Program::where('kode', $p['kode'])->exists();
                    if ($exists) continue;
                    Program::create(['kode' => $p['kode'], 'nama' => $p['nama']]);
                    $created++;
                }
                DB::commit();
                return redirect()->back()->with('success', "$created program(s) berhasil dibuat");
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        $attrs = $request->validate([
            'kode' => 'required|string|max:20|unique:programs,kode',
            'nama' => 'required|string|max:255',
        ]);

        $program = Program::create($attrs);

        return redirect()->back()->with('success', 'Program berhasil dibuat');
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $attrs = $request->validate([
            'kode' => 'required|string|max:20|unique:programs,kode,' . $program->id,
            'nama' => 'required|string|max:255',
        ]);

        $program->update($attrs);

        return redirect()->back()->with('success', 'Program berhasil diupdate');
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return redirect()->back()->with('success', 'Program berhasil dihapus');
    }

    // return kegiatans for a program (JSON) - used for lazy-loading in frontend
    public function getKegiatanByIdProgram($id)
    {
        $program = Program::with('kegiatans')->findOrFail($id);
        
        // Add program info to each kegiatan
        $kegiatansWithProgram = $program->kegiatans->map(function($kegiatan) use ($program) {
            $kegiatan->program = [
                'id' => $program->id,
                'kode' => $program->kode,
                'nama' => $program->nama
            ];
            return $kegiatan;
        });
        
        return response()->json([
            'kegiatans' => $kegiatansWithProgram,
            'program' => $program
        ]);
    }

    // API endpoint to get all programs as JSON
    public function apiList()
    {
        $programs = Program::orderBy('id', 'asc')->get();
        return response()->json(['programs' => $programs]);
    }
}
