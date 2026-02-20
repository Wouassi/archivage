<?php

use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\ScanUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// ═══ Scanner Larascan (Asprise Scanner.js) ═══
Route::middleware(['web', 'auth'])->prefix('scan')->group(function () {
    Route::post('/upload', [ScanUploadController::class, 'upload'])->name('scan.upload');
    Route::get('/session-files', [ScanUploadController::class, 'sessionFiles'])->name('scan.session-files');
    Route::delete('/clear', [ScanUploadController::class, 'clear'])->name('scan.clear');
});

// ═══ Export PDF de toutes les listes ═══
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/export/pdf/{type}', [ExportPdfController::class, 'export'])->name('export.pdf');
});

