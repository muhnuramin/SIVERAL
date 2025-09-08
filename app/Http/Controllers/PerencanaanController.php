<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Satuan;
use App\Models\SubKegiatan;
use App\Models\SubSubKegiatan;
use App\Models\Rekening;
use App\Models\User;
use App\Models\SumberAnggaran;
use App\Models\SubSubKegiatanPagu;
use App\Models\RekeningSshItem;
use App\Models\RekeningSshItemHarga;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PerencanaanController extends Controller
{
    public function index()
    {
        $year = request()->integer('year') ?? now()->year;
        // Eager-load the hierarchy including pagu per Sub-Sub Kegiatan (filtered by year)
        $programs = Program::with([
            'kegiatans' => function ($q) {
                $q->orderBy('kode');
            },
            'kegiatans.subKegiatans' => function ($q) {
                $q->orderBy('kode');
            },
            'kegiatans.subKegiatans.subSubKegiatans' => function ($q) {
                $q->orderBy('kode');
            },
            'kegiatans.subKegiatans.subSubKegiatans.pagus' => function ($q) use ($year) {
                $q->where('tahun', $year)->with('verifiedBy:id,name');
            },
        ])->orderBy('kode')->get();

        $users = User::query()->select('id', 'name')->orderBy('name')->get();

        $sources = SumberAnggaran::query()->select('id','nama','kode')->where('stAktif', true)->orderBy('nama')->get();

        // Total Anggaran BLUD (assume BLUD id=1) from sumber_anggaran_pagus (current year)
        $bludId = 1;
        $totalBlud = DB::table('sumber_anggaran_pagus')
            ->where('sumber_anggaran_id', $bludId)
            ->where('tahun', $year)
            ->sum('pagu');

        // Sum of all pagu set on sub_sub_kegiatan_pagus for the selected year (any sumber anggaran)
        $totalUsedPagu = DB::table('sub_sub_kegiatan_pagus')
            ->where('tahun', $year)
            ->sum('pagu');

        return Inertia::render('anggaran/Perencanaan', [
            'programs' => $programs,
            'year' => $year,
            'users' => $users,
            'fundingSources' => $sources,
            'totalAnggaranBlud' => (int) $totalBlud,
            'totalUsedPagu' => (int) $totalUsedPagu,
        ]);
    }

    public function rekening()
    {
        // Inputs (single kode/nama kept for backward compatibility)
        $kode = request()->string('kode')->toString();
        $nama = request()->string('nama')->toString();
        $sskCode = request()->string('ssk')->toString();
        $year = request()->integer('year') ?? now()->year;

        // 1) Optional headings provided via query (JSON string)
        $heads = $this->parseHeads(request()->string('heads')->toString());

        // 2) Load SSK (for context and DB-sourced heads)
        $ssk = $sskCode ? $this->loadSsk($sskCode) : null;
        $context = $this->makeContext($ssk);

        // 3) If no explicit heads, load from rekenings for the given SSK
        if (empty($heads) && $ssk) {
            $heads = $this->headsFromRekenings($ssk);
        }

        // 4) Shared dropdown options
        $satuans = Satuan::query()->select('id', 'nama')->orderBy('nama')->get();

        // 5) Load SSH items for the rekening codes (if any heads)
        $items = [];
        if (!empty($heads)) {
            $codes = collect($heads)->pluck('kode')->filter()->map(fn ($c) => rtrim((string) $c, '.'))->unique()->values();
            if ($codes->isNotEmpty()) {
                $tahunInt = (int)$year;
                $rows = RekeningSshItem::query()
                    ->whereIn('rekening_kode', $codes)
                    ->orderBy('rekening_kode')
                    ->orderBy('nama')
                    ->select([
                        'id','rekening_kode','nama','satuan',
                        DB::raw("COALESCE((SELECT rh.harga FROM rekening_ssh_item_hargas rh WHERE rh.rekening_ssh_item_id = rekening_ssh_items.id AND rh.tahun = {$tahunInt} ORDER BY rh.id DESC LIMIT 1),0) AS harga")
                    ])->get();

                $i = 1;
                $items = $rows->map(function($r) use (&$i, $year) {
                    return [
                        'no' => $i++,
                        'id' => (int)$r->id,
                        'rekening' => (string)$r->rekening_kode,
                        'nama' => (string)$r->nama,
                        'satuan' => (string)$r->satuan,
                        'harga' => (float)$r->harga,
                        'tahun' => $year,
                    ];
                })->values()->all();
            }
        }

        return Inertia::render('anggaran/RekeningDetail', [
            'kode' => $kode,
            'nama' => $nama,
            'heads' => $heads,
            'items' => $items,
            'year' => $year,
            'satuans' => $satuans,
            'context' => $context,
        ]);
    }

    /**
     * Update PIC on sub_sub_kegiatans and Pagu + Sumber Dana (sumber_anggaran_id) for a given year in sub_sub_kegiatan_pagus.
     */
    public function updatePagu(Request $request)
    {
        $data = $request->validate([
            'sub_sub_kode' => ['required', 'string'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'pagu' => ['nullable', 'numeric', 'min:0'],
            'sumber_anggaran_id' => ['nullable', 'integer', 'exists:sumber_anggarans,id'],
            'sumber_dana' => ['nullable', 'string'], // legacy support (nama)
            'pic_user_ids' => ['nullable', 'array'],
            'pic_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // Find SSK by kode
        $ssk = SubSubKegiatan::where('kode', $data['sub_sub_kode'])->firstOrFail();

        // Sync multiple PICs if provided (many-to-many)
        if (array_key_exists('pic_user_ids', $data)) {
            try {
                $ssk->pics()->sync($data['pic_user_ids'] ?? []);
            } catch (\Throwable $e) {
                // pivot table might not exist; swallow error
            }
        }

        // Resolve sumber_anggaran_id by nama (optional)
        $sumberId = $data['sumber_anggaran_id'] ?? null;
        if (!$sumberId && !empty($data['sumber_dana'])) {
            $sumber = SumberAnggaran::where('nama', $data['sumber_dana'])->first();
            $sumberId = $sumber?->id;
        }

        // Upsert pagu row (requires sumber_anggaran_id; if null, keep previous if exists else skip creating)
        if ($sumberId) {
            SubSubKegiatanPagu::updateOrCreate(
                [
                    'sub_sub_kegiatan_id' => $ssk->id,
                    'tahun' => (int) $data['tahun'],
                    'sumber_anggaran_id' => $sumberId,
                ],
                [
                    'pagu' => isset($data['pagu']) ? (int) $data['pagu'] : 0,
                ]
            );
        } elseif (isset($data['pagu'])) {
            // If sumber not provided but pagu is, try update any existing row for the year (first found)
            $row = SubSubKegiatanPagu::where('sub_sub_kegiatan_id', $ssk->id)
                ->where('tahun', (int) $data['tahun'])
                ->first();
            if ($row) {
                $row->pagu = (int) $data['pagu'];
                $row->save();
            }
        }

        return back()->with('status', 'updated');
    }

    /**
     * Validate or unvalidate a sub-sub kegiatan by updating verification fields in sub_sub_kegiatan_pagus
     */
    public function validateSubSub(Request $request)
    {
        $data = $request->validate([
            'sub_sub_kode' => ['required', 'string'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'action' => ['required', 'string', 'in:validate,unvalidate'],
        ]);

        // Find SSK by kode
        $ssk = SubSubKegiatan::where('kode', $data['sub_sub_kode'])->firstOrFail();

        $isValidated = $data['action'] === 'validate';
        
        if ($isValidated) {
            // Check if required fields are set before validation
            $pagu = SubSubKegiatanPagu::where('sub_sub_kegiatan_id', $ssk->id)
                ->where('tahun', $data['tahun'])
                ->first();
            
            $validationErrors = [];
            
            // Check if pagu exists and is set
            if (!$pagu || !$pagu->pagu || $pagu->pagu <= 0) {
                $validationErrors['pagu'] = 'Pagu belum diset atau tidak valid';
            }
            
            // Check if sumber anggaran is set
            if (!$pagu || !$pagu->sumber_anggaran_id) {
                $validationErrors['sumber_anggaran'] = 'Sumber Anggaran belum diset';
            }
            
            // Check if PIC is set (assuming PIC is stored in a pivot table or relationship)
            $picCount = $ssk->pics()->count();
            if ($picCount === 0) {
                $validationErrors['pic'] = 'PIC belum diset';
            }
            
            // If there are validation errors, return them
            if (!empty($validationErrors)) {
                return back()->withErrors($validationErrors)->with('error', 'Tidak dapat memvalidasi. Silakan lengkapi data terlebih dahulu.');
            }
            
            // Update verification fields in sub_sub_kegiatan_pagus table
            SubSubKegiatanPagu::where('sub_sub_kegiatan_id', $ssk->id)
                ->where('tahun', $data['tahun'])
                ->update([
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]);
            
            $message = 'Sub kegiatan berhasil divalidasi';
        } else {
            // Clear verification fields when unvalidating
            SubSubKegiatanPagu::where('sub_sub_kegiatan_id', $ssk->id)
                ->where('tahun', $data['tahun'])
                ->update([
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            
            $message = 'Validasi sub kegiatan berhasil dibatalkan';
        }
        
        return back()->with('status', $message);
    }

    /**
     * Parse `heads` query param (JSON) into a normalized array of [kode, nama].
     */
    private function parseHeads(?string $headsParam): array
    {
        if (!$headsParam) return [];
        try {
            $decoded = json_decode($headsParam, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
        if (!is_array($decoded)) return [];
        $out = [];
        foreach ($decoded as $h) {
            if (is_array($h)) {
                $out[] = [
                    'kode' => (string)($h['kode'] ?? ''),
                    'nama' => (string)($h['nama'] ?? ''),
                ];
            }
        }
        return $out;
    }

    /**
    * Load Sub-Sub Kegiatan with minimal relations needed for context.
     */
    private function loadSsk(string $sskCode): ?SubSubKegiatan
    {
        return SubSubKegiatan::query()
            ->where('kode', $sskCode)
            ->with([
                'subKegiatan:id,kegiatan_id,kode,nama',
                'subKegiatan.kegiatan:id,program_id,kode,nama',
                'subKegiatan.kegiatan.program:id,kode,nama',
            ])
            ->first();
    }

    /**
     * Build hierarchical context for the UI (program → kegiatan → sub → sub-sub).
     */
    private function makeContext(?SubSubKegiatan $ssk): array
    {
        if (!$ssk) {
            return [
                'program' => ['kode' => null, 'nama' => null],
                'kegiatan' => ['kode' => null, 'nama' => null],
                'sub_kegiatan' => ['kode' => null, 'nama' => null],
                'sub_sub_kegiatan' => ['kode' => null, 'nama' => null],
            ];
        }

        return [
            'program' => [
                'kode' => optional(optional(optional($ssk->subKegiatan)->kegiatan)->program)->kode,
                'nama' => optional(optional(optional($ssk->subKegiatan)->kegiatan)->program)->nama,
            ],
            'kegiatan' => [
                'kode' => optional(optional($ssk->subKegiatan)->kegiatan)->kode,
                'nama' => optional(optional($ssk->subKegiatan)->kegiatan)->nama,
            ],
            'sub_kegiatan' => [
                'kode' => optional($ssk->subKegiatan)->kode,
                'nama' => optional($ssk->subKegiatan)->nama,
            ],
            'sub_sub_kegiatan' => [
                'kode' => $ssk->kode,
                'nama' => $ssk->nama,
            ],
        ];
    }

    // (Akun table is not used in this flow; Rekening is the single source of heads.)

    /**
     * Fetch Rekening rows from DB for this SSK and shape them as heads.
     */
    private function headsFromRekenings(SubSubKegiatan $ssk): array
    {
        $rows = Rekening::query()
            ->where('id_sub_sub_kegiatan', $ssk->id)
            ->where('stAktif', true)
            ->orderBy('koderekening')
            ->get(['koderekening', 'namarekening']);
        if ($rows->isEmpty()) return [];
        return $rows->map(fn ($r) => [
            'kode' => (string)$r->koderekening,
            'nama' => (string)$r->namarekening,
        ])->values()->all();
    }

    /**
     * Store new Sub-Sub Kegiatan with pagu and related data
     */
    public function storeSubSub(Request $request)
    {
        $validated = $request->validate([
            'sub_kegiatan_kode' => 'required|string|exists:sub_kegiatans,kode',
            'kode' => 'required|string|max:50|unique:sub_sub_kegiatans,kode',
            'nama' => 'required|string|max:255',
            'tahun' => 'required|integer',
            'pagu' => 'nullable|numeric|min:0',
            'sumber_anggaran_id' => 'nullable|integer|exists:sumber_anggarans,id',
            'pic_user_ids' => 'nullable|array',
            'pic_user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            // Find SubKegiatan by kode
            $subKegiatan = SubKegiatan::where('kode', $validated['sub_kegiatan_kode'])->firstOrFail();

            // Create SubSubKegiatan
            $subSubKegiatan = SubSubKegiatan::create([
                'sub_kegiatan_id' => $subKegiatan->id,
                'kode' => $validated['kode'],
                'nama' => $validated['nama'],
            ]);

            // Create pagu if provided
            if (isset($validated['pagu']) && $validated['pagu'] > 0) {
                $pagu = SubSubKegiatanPagu::create([
                    'sub_sub_kegiatan_id' => $subSubKegiatan->id,
                    'tahun' => $validated['tahun'],
                    'pagu' => $validated['pagu'],
                    'sumber_anggaran_id' => $validated['sumber_anggaran_id'],
                ]);

                // Attach PIC users if provided
                if (!empty($validated['pic_user_ids'])) {
                    $pagu->pics()->sync($validated['pic_user_ids']);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Sub-Sub Kegiatan berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menambah Sub-Sub Kegiatan: ' . $e->getMessage()]);
        }
    }

    /**
     * Update Total Anggaran BLUD
     */
    public function updateTotalAnggaran(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|min:2020|max:2050',
            'total_anggaran' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Update or create entry in sumber_anggaran_pagus for sumber_anggaran_id = 1
            \App\Models\SumberAnggaranPagu::updateOrCreate(
                [
                    'sumber_anggaran_id' => 1,
                    'tahun' => $validated['tahun'],
                ],
                [
                    'pagu' => $validated['total_anggaran'],
                ]
            );

            DB::commit();

            return redirect()->back()->with('success', 'Total Anggaran BLUD berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal update Total Anggaran BLUD: ' . $e->getMessage()]);
        }
    }

    /**
     * Store simple sub-sub kegiatan (only to sub_sub_kegiatans table)
     */
    public function storeSubSubSimple(Request $request)
    {
        $validated = $request->validate([
            'sub_kegiatan_kode' => 'required|string|exists:sub_kegiatans,kode',
            'kode' => 'required|string|max:50|unique:sub_sub_kegiatans,kode',
            'nama' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Find SubKegiatan by kode
            $subKegiatan = SubKegiatan::where('kode', $validated['sub_kegiatan_kode'])->firstOrFail();

            // Create SubSubKegiatan only (no pagu record)
            SubSubKegiatan::create([
                'sub_kegiatan_id' => $subKegiatan->id,
                'kode' => $validated['kode'],
                'nama' => $validated['nama'],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Sub-Sub Kegiatan berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menambah Sub-Sub Kegiatan: ' . $e->getMessage()]);
        }
    }
}
