<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    // Hak akses grup CRUD
    Route::get('/hak-akses-grup', [\App\Http\Controllers\HakAksesGrupController::class, 'index'])->name('hakaksesgrup.index');
    Route::post('/hak-akses-grup', [\App\Http\Controllers\HakAksesGrupController::class, 'store'])->name('hakaksesgrup.store');
    Route::put('/hak-akses-grup/{id}', [\App\Http\Controllers\HakAksesGrupController::class, 'update'])->name('hakaksesgrup.update');
    Route::delete('/hak-akses-grup/{id}', [\App\Http\Controllers\HakAksesGrupController::class, 'destroy'])->name('hakaksesgrup.destroy');
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});
