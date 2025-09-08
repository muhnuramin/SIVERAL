<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use App\Http\Requests\SatuanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SatuanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $satuans = Satuan::orderBy('nama')->paginate(15);
        
        return Inertia::render('Satuan/Index', [
            'satuans' => $satuans,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SatuanRequest $request)
    {
        $validated = $request->validated();

        Satuan::create([
            'nama' => $validated['nama'],
        ]);

        return back()->with('success', 'Satuan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SatuanRequest $request, Satuan $satuan)
    {
        $validated = $request->validated();

        $satuan->update([
            'nama' => $validated['nama'],
        ]);

        return back()->with('success', 'Satuan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Satuan $satuan)
    {
        // Check if satuan is being used in SSH items
        $isUsed = DB::table('rekening_ssh_items')
            ->where('satuan', $satuan->nama)
            ->exists();

        if ($isUsed) {
            return back()->withErrors(['delete' => 'Satuan tidak dapat dihapus karena sedang digunakan.']);
        }

        $satuan->delete();

        return back()->with('success', 'Satuan berhasil dihapus.');
    }

    /**
     * Get all satuans for API/dropdown usage
     */
    public function apiList()
    {
        $satuans = Satuan::orderBy('nama')->get(['id', 'nama']);
        
        return response()->json($satuans);
    }
}