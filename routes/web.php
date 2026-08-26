<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\ClassController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [AbsensiController::class, 'dashboard'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/feed', [AbsensiController::class, 'dashboardFeed'])->middleware(['auth', 'verified'])->name('dashboard.feed');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/siswa/import', [SiswaController::class, 'importForm'])->name('siswa.import');
    Route::post('/siswa/import/preview', [SiswaController::class, 'importPreview'])->name('siswa.import.preview');
    Route::post('/siswa/import/confirm', [SiswaController::class, 'importConfirm'])->name('siswa.import.confirm');
    Route::post('/siswa/import/process', [SiswaController::class, 'importProcessChunk'])->name('siswa.import.process');
    Route::get('/siswa/import/template', [SiswaController::class, 'downloadTemplate'])->name('siswa.import.template');
    Route::get('/siswa/export', [SiswaController::class, 'exportData'])->name('siswa.export');
    Route::get('/siswa/export-qr', [SiswaController::class, 'exportQr'])->name('siswa.export-qr');
    Route::delete('/siswa/hapus-per-kelas', [SiswaController::class, 'destroyByKelas'])->name('siswa.destroy.by-kelas');
    Route::get('/siswa/{siswa}/foto', [SiswaController::class, 'foto'])->name('siswa.foto');
    Route::get('/siswa/barcode/png-all', [SiswaController::class, 'barcodePngAll'])->name('siswa.barcode.png.all');
    Route::get('/siswa/barcode/pdf-all', [SiswaController::class, 'barcodePdfAll'])->name('siswa.barcode.pdf.all');
    Route::get('/siswa/{siswa}/barcode/png', [SiswaController::class, 'barcodePng'])->name('siswa.barcode.png');
    Route::get('/siswa/{siswa}/barcode/pdf', [SiswaController::class, 'barcodePdf'])->name('siswa.barcode.pdf');
    Route::resource('siswa', SiswaController::class);

    Route::get('/scan', [AbsensiController::class, 'scan'])->name('absensi.scan');
    Route::get('/absensi/live-feed', [AbsensiController::class, 'liveFeed'])->name('absensi.live-feed');
    Route::post('/absensi/proses', [AbsensiController::class, 'proses'])->name('absensi.proses');
    Route::get('/absensi/rekap', [AbsensiController::class, 'rekap'])->name('absensi.rekap');
    Route::get('/absensi/rekap-data', [AbsensiController::class, 'rekapData'])->name('absensi.rekap.data');
    Route::get('/absensi/rekap-export', [AbsensiController::class, 'rekapExport'])->name('absensi.rekap.export');
    Route::get('/absensi/laporan', [AbsensiController::class, 'laporanBulanan'])->name('absensi.laporan');
    Route::get('/absensi/laporan-data', [AbsensiController::class, 'laporanData'])->name('absensi.laporan.data');
    Route::get('/absensi/export', [AbsensiController::class, 'export'])->name('absensi.export');

    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::put('/classes/{class}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{class}', [ClassController::class, 'destroy'])->name('classes.destroy');
});

require __DIR__.'/auth.php';
