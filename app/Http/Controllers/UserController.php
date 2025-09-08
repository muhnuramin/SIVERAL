<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('auth/Login');
    }

    public function viewuser()
    {
        $role=['admin','Master','SSH','Batasan Pagu','Sub Kegiatan Belanja','verifikator'];
        $dataUser = User::with('subSubKegiatans:id,kode,nama')->get()->map(function($u){
            return [
                'id' => $u->id,
                'name' => $u->name,
                'roles' => $u->roles,
                'nip' => $u->NIP,
                'unit' => $u->unit,
                'pics' => $u->subSubKegiatans->map(fn($s) => ['id'=>$s->id,'kode'=>$s->kode,'nama'=>$s->nama])->values(),
                'created_at' => $u->created_at,
            ];
        });
        return Inertia::render('user/User', [
            'dataUser' => $dataUser,
            'role' => $role,
        ]);
    }
    
    public function store(Request $request)
    {
        $attrs = $request->validate([
            'name' => 'required|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'nip' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
            'pic_ids' => 'array',
            'pic_ids.*' => 'integer|exists:sub_sub_kegiatans,id'
        ]);

        // ensure roles exists even if null
        $attrs['roles'] = $attrs['roles'] ?? [];

        $user = User::create($attrs);
        if (!empty($attrs['pic_ids'])) {
            $user->subSubKegiatans()->sync($attrs['pic_ids']);
        }
        session()->flash('success', 'User berhasil dibuat');
        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $attrs = $request->validate([
            'name' => 'required|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'nip' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'pic_ids' => 'array',
            'pic_ids.*' => 'integer|exists:sub_sub_kegiatans,id'
        ]);

        if (empty($attrs['password'])) {
            unset($attrs['password']);
        }

        $attrs['roles'] = $attrs['roles'] ?? $user->roles ?? [];

        $user->update($attrs);
        if (isset($attrs['pic_ids'])) {
            $user->subSubKegiatans()->sync($attrs['pic_ids']);
        }

        return redirect()->back()->with('success', 'User berhasil diupdate');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->back();
    }
}
