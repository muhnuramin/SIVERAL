<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\SubKegiatanController;
use App\Http\Controllers\RencanaBelanjaController;
use App\Http\Controllers\SubSubKegiatanController;
use App\Http\Controllers\PerencanaanController;
use App\Http\Controllers\RekeningSshItemController;
use App\Http\Controllers\EvaluasiController;
use App\Http\Controllers\SatuanController;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PrintAnggaranController;

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
Route::get('/',[UserController::class,'index'])->name('home');
// program CRUD (handled below)


Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// user CRUD routes
Route::get('/user',[UserController::class,'viewuser'])->middleware(['auth', 'verified'])->name('user');
Route::post('/user', [UserController::class, 'store'])->middleware(['auth', 'verified'])->name('user.store');
Route::put('/user/{id}', [UserController::class, 'update'])->middleware(['auth', 'verified'])->name('user.update');
Route::delete('/user/{id}', [UserController::class, 'destroy'])->middleware(['auth', 'verified'])->name('user.destroy');
// Program routes
Route::get('/program', [ProgramController::class, 'index'])->middleware(['auth', 'verified'])->name('program');
Route::get('/api/programs', [ProgramController::class, 'apiList'])->middleware(['auth', 'verified'])->name('program.api');
Route::post('/program', [ProgramController::class, 'store'])->middleware(['auth', 'verified'])->name('program.store');
Route::put('/program/{id}', [ProgramController::class, 'update'])->middleware(['auth', 'verified'])->name('program.update');
Route::delete('/program/{id}', [ProgramController::class, 'destroy'])->middleware(['auth', 'verified'])->name('program.destroy');
Route::get('/program/{id}/kegiatans', [ProgramController::class, 'getKegiatanByIdProgram'])->middleware(['auth', 'verified'])->name('program.kegiatans');
// sub-kegiatan routes
Route::get('/sub-kegiatan', [SubKegiatanController::class, 'index'])->middleware(['auth', 'verified'])->name('subkegiatan.index');
Route::get('/kegiatan/{id}/sub-kegiatan', [SubKegiatanController::class, 'getSubKegiatanByIdKegiatan'])->middleware(['auth', 'verified'])->name('subkegiatan.get');
Route::post('/sub-kegiatan', [SubKegiatanController::class, 'store'])->middleware(['auth', 'verified'])->name('subkegiatan.store');
Route::put('/sub-kegiatan/{id}', [SubKegiatanController::class, 'update'])->middleware(['auth', 'verified'])->name('subkegiatan.update');
Route::delete('/sub-kegiatan/{id}', [SubKegiatanController::class, 'destroy'])->middleware(['auth', 'verified'])->name('subkegiatan.destroy');
// sub-sub-kegiatan routes
Route::get('/sub-sub-kegiatan', [SubSubKegiatanController::class, 'index'])->middleware(['auth', 'verified'])->name('subsubkegiatan.index');
Route::post('/sub-sub-kegiatan', [SubSubKegiatanController::class, 'store'])->middleware(['auth', 'verified'])->name('subsubkegiatan.store');
Route::put('/sub-sub-kegiatan/{id}', [SubSubKegiatanController::class, 'update'])->middleware(['auth', 'verified'])->name('subsubkegiatan.update');
Route::delete('/sub-sub-kegiatan/{id}', [SubSubKegiatanController::class, 'destroy'])->middleware(['auth', 'verified'])->name('subsubkegiatan.destroy');
Route::get('/sub-kegiatan/{id}/sub-sub-kegiatan', [SubSubKegiatanController::class, 'getSubSubKegiatanByIdSubKegiatan'])->middleware(['auth', 'verified'])->name('subsubkegiatan.get');
// kegiatan CRUD
Route::get('/kegiatan', [KegiatanController::class, 'index'])->middleware(['auth', 'verified'])->name('kegiatan.index');
Route::post('/kegiatan', [KegiatanController::class, 'store'])->middleware(['auth', 'verified'])->name('kegiatan.store');
Route::put('/kegiatan/{id}', [KegiatanController::class, 'update'])->middleware(['auth', 'verified'])->name('kegiatan.update');
Route::delete('/kegiatan/{id}', [KegiatanController::class, 'destroy'])->middleware(['auth', 'verified'])->name('kegiatan.destroy');
// rencana belanja
Route::get('/rencana-belanja', [RencanaBelanjaController::class, 'index'])->middleware(['auth', 'verified'])->name('rencana-belanja');
Route::get('/rencana-belanja/export', [RencanaBelanjaController::class, 'export'])->middleware(['auth', 'verified'])->name('rencana-belanja.export');
Route::get('/rencana-belanja/{id}', [RencanaBelanjaController::class, 'show'])->middleware(['auth', 'verified'])->name('rencana-belanja.show');
// Tambah route untuk halaman cetak anggaran
Route::get('/anggaran/print', [PrintAnggaranController::class, 'show'])->middleware(['auth', 'verified'])->name('anggaran.print');

// Anggaran > Perencanaan
Route::get('/anggaran/perencanaan', [PerencanaanController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.perencanaan');

// Anggaran > Rekening detail (from Perencanaan)
Route::get('/anggaran/rekening', [PerencanaanController::class, 'rekening'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.rekening.show');

// Anggaran > Perencanaan update pagu & sumber dana & PIC
Route::post('/anggaran/perencanaan/update', [PerencanaanController::class, 'updatePagu'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.perencanaan.update');

// Anggaran > Perencanaan store new sub-sub kegiatan
Route::post('/anggaran/perencanaan/store-sub-sub', [PerencanaanController::class, 'storeSubSub'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.perencanaan.store-sub-sub');

// Anggaran > Perencanaan validate/unvalidate sub-sub kegiatan
Route::post('/anggaran/perencanaan/validate', [PerencanaanController::class, 'validateSubSub'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.perencanaan.validate');

// Anggaran > Perencanaan update total anggaran BLUD
Route::post('/anggaran/perencanaan/update-total-anggaran', [PerencanaanController::class, 'updateTotalAnggaran'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.perencanaan.update-total-anggaran');

// Anggaran > Perencanaan store simple sub-sub kegiatan (only to sub_sub_kegiatans table)
Route::post('/anggaran/perencanaan/store-sub-sub-simple', [PerencanaanController::class, 'storeSubSubSimple'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.perencanaan.store-sub-sub-simple');

// Anggaran > Rekening SSH Items store
Route::post('/anggaran/rekening/ssh', [RekeningSshItemController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.rekening.ssh.store');
Route::put('/anggaran/rekening/ssh/{item}', [RekeningSshItemController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.rekening.ssh.update');
Route::delete('/anggaran/rekening/ssh/{item}', [RekeningSshItemController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.rekening.ssh.destroy');
Route::put('/anggaran/rekening/ssh/{item}/harga', [RekeningSshItemController::class, 'updateHarga'])
    ->middleware(['auth', 'verified'])
    ->name('anggaran.rekening.ssh.harga.update');

// Anggaran > Evaluasi
Route::get('/anggaran/evaluasi', [EvaluasiController::class, 'index'])
    ->middleware(['auth','verified'])
    ->name('evaluasi.index');
Route::post('/anggaran/evaluasi/bulk-save', [EvaluasiController::class, 'bulkSave'])
    ->middleware(['auth','verified'])
    ->name('evaluasi.bulk-save');

// Satuan CRUD routes
Route::get('/satuan', [SatuanController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('satuan.index');
Route::post('/satuan', [SatuanController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('satuan.store');
Route::put('/satuan/{satuan}', [SatuanController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('satuan.update');
Route::delete('/satuan/{satuan}', [SatuanController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('satuan.destroy');
Route::get('/api/satuans', [SatuanController::class, 'apiList'])
    ->middleware(['auth', 'verified'])
    ->name('satuan.api');

