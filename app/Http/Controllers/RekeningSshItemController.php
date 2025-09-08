<?php

namespace App\Http\Controllers;

use App\Models\RekeningSshItem;
use App\Models\RekeningSshItemHarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RekeningSshItemController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'rekening' => ['required','string','max:100'],
            'nama' => ['required','string','max:255'],
            'satuan' => ['required','string','max:100'],
            'spesifikasi' => ['nullable','string','max:500'],
            'harga' => ['required','numeric','min:0'],
            'tahun' => ['nullable','integer','min:2000','max:2100'],
            'ssk' => ['nullable','string'],
            'heads' => ['nullable','string'], // JSON string
        ]);

        $item = RekeningSshItem::create([
            'rekening_kode' => rtrim($data['rekening'], '.'),
            'nama' => trim($data['nama']),
            'satuan' => trim($data['satuan']),
            'spesifikasi' => trim($data['spesifikasi'] ?? ''),
        ]);
        RekeningSshItemHarga::updateOrCreate([
            'rekening_ssh_item_id' => $item->id,
            'tahun' => $data['tahun'] ?? now()->year,
        ], [
            'harga' => (float) $data['harga'],
        ]);

        $params = [
            'ssk' => $data['ssk'] ?: ($item->rekening_kode),
            'year' => $data['tahun'] ?? now()->year,
        ];
        if (!empty($data['heads'])) {
            $params['heads'] = $data['heads'];
        }

        return redirect()->route('anggaran.rekening.show', $params)
            ->with('status', 'ssh-item-added');
    }

    public function update(Request $request, RekeningSshItem $item)
    {
        $data = $request->validate([
            'rekening' => ['sometimes','string','max:100'],
            'nama' => ['required','string','max:255'],
            'satuan' => ['required','string','max:100'],
            'spesifikasi' => ['nullable','string','max:500'],
            'harga' => ['required','numeric','min:0'],
            'tahun' => ['required','integer','min:2000','max:2100'],
            'ssk' => ['nullable','string'],
            'heads' => ['nullable','string'],
        ]);

        if (isset($data['rekening'])) {
            $item->rekening_kode = rtrim($data['rekening'], '.');
        }
        $item->nama = trim($data['nama']);
        $item->satuan = trim($data['satuan']);
        $item->spesifikasi = trim($data['spesifikasi'] ?? '');
        $item->save();

        RekeningSshItemHarga::updateOrCreate([
            'rekening_ssh_item_id' => $item->id,
            'tahun' => $data['tahun'],
        ], [
            'harga' => (float) $data['harga'],
        ]);

        $params = [
            'ssk' => $data['ssk'] ?: $item->rekening_kode,
            'year' => $data['tahun'],
        ];
        if (!empty($data['heads'])) {
            $params['heads'] = $data['heads'];
        }

        return redirect()->route('anggaran.rekening.show', $params)
            ->with('status', 'ssh-item-harga-updated');
    }

    public function updateHarga(Request $request, RekeningSshItem $item)
    {
        $data = $request->validate([
            'tahun' => ['required','integer','min:2000','max:2100'],
            'harga' => ['required','numeric','min:0'],
            'ssk' => ['nullable','string'],
            'heads' => ['nullable','string'],
        ]);
        RekeningSshItemHarga::updateOrCreate([
            'rekening_ssh_item_id' => $item->id,
            'tahun' => $data['tahun'],
        ], [
            'harga' => (float) $data['harga'],
        ]);
        $params = [
            'ssk' => $data['ssk'] ?: $item->rekening_kode,
            'year' => $data['tahun'],
        ];
        if (!empty($data['heads'])) {
            $params['heads'] = $data['heads'];
        }

        return redirect()->route('anggaran.rekening.show', $params)
            ->with('status', 'ssh-item-harga-updated');
    }

    public function destroy(RekeningSshItem $item, Request $request)
    {
        $year = $request->integer('year') ?: now()->year;
        $ssk = $request->string('ssk')->toString();
        $headsJson = $request->input('heads');
        // delete related harga rows first
        RekeningSshItemHarga::where('rekening_ssh_item_id', $item->id)->delete();
        $kodeForRedirect = $ssk ?: $item->rekening_kode; // prefer ssk context
        $item->delete();
        $params = [
            'ssk' => $kodeForRedirect,
            'year' => $year,
        ];
        if ($headsJson) {
            $params['heads'] = $headsJson; // pass through
        }
        return redirect()->route('anggaran.rekening.show', $params)->with('status', 'ssh-item-deleted');
    }
}
