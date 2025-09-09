<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HakAksesGrup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HakAksesGrupController extends Controller
{
    public function index()
    {
        $groups = HakAksesGrup::orderBy('id')->get()->map(fn($g)=>['id'=>$g->id,'nama'=>$g->nama,'hak_akses'=>$g->hak_akses]);
        return Inertia::render('master/HakAksesGrupIndex', ['groups'=>$groups]);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'nama' => 'required|string|max:255',
            'hak_akses' => 'nullable|array',
            'hak_akses.*' => 'string',
        ]);
        HakAksesGrup::create($attrs);
        return redirect()->back()->with('success','Group dibuat');
    }

    public function update(Request $request, $id)
    {
        $group = HakAksesGrup::findOrFail($id);
        $attrs = $request->validate([
            'nama' => 'required|string|max:255',
            'hak_akses' => 'nullable|array',
            'hak_akses.*' => 'string',
        ]);
        $group->update($attrs);
        return redirect()->back()->with('success','Group diperbarui');
    }

    public function destroy($id)
    {
        $g = HakAksesGrup::findOrFail($id);
        $g->delete();
        return redirect()->back()->with('success','Group dihapus');
    }
}
