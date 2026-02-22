<?php

use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Dossier;
use Illuminate\Support\Facades\Storage;

// ══════════════════════════════════════════════════════════════
// REDIRECTION VERS FILAMENT
// ══════════════════════════════════════════════════════════════
Route::get('/', fn () => redirect('/admin'));

// ══════════════════════════════════════════════════════════════
// BACKUP — Export BDD + DOSSIER_ARCHIVE en ZIP
// ══════════════════════════════════════════════════════════════
Route::get('/admin/backup/download', [BackupController::class, 'download'])
    ->middleware(['web', 'auth'])
    ->name('backup.download');

// ══════════════════════════════════════════════════════════════
// ROUTE POUR SERVIR LES FICHIERS DU STORAGE
// (nécessaire pour les aperçus PDF inline)
// ══════════════════════════════════════════════════════════════
Route::get('/storage-file/{path}', function (string $path) {
    $path = ltrim($path, '/');

    // Sécurité : interdire la traversée de répertoire
    if (str_contains($path, '..')) {
        abort(403);
    }

    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $abs = Storage::disk('public')->path($path);

    return response()->file($abs, [
        'Content-Type'        => mime_content_type($abs),
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
    ]);
})->where('path', '.*')->name('storage.file');

// ══════════════════════════════════════════════════════════════
// SCANNER LARASCAN — Upload de fichiers scannés
// ══════════════════════════════════════════════════════════════
Route::middleware(['web', 'auth'])->prefix('scan')->group(function () {

    // Upload d'un fichier scanné (PDF, JPG, PNG)
    Route::post('/upload', function (Request $request) {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file     = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path     = $file->storeAs('scanner/temp', $filename, 'public');

        return response()->json([
            'success'  => true,
            'path'     => $path,
            'filename' => $filename,
            'url'      => Storage::url($path),
        ]);
    })->name('scan.upload');

    // Récupérer les fichiers en session
    Route::get('/session-files', function () {
        $paths = session('larascan_pdf_paths', []);

        return response()->json([
            'success' => true,
            'paths'   => $paths,
            'count'   => count($paths),
        ]);
    })->name('scan.session-files');

    // Vider les fichiers temporaires du scanner
    Route::delete('/clear', function () {
        $paths = session('larascan_pdf_paths', []);

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        session()->forget('larascan_pdf_paths');

        return response()->json(['success' => true]);
    })->name('scan.clear');
});

// ══════════════════════════════════════════════════════════════
// TÉLÉCHARGEMENT PDF D'UN DOSSIER
// ══════════════════════════════════════════════════════════════
Route::get('/download-dossier-pdf/{dossier}', function (Dossier $dossier) {
    if ($dossier->fichier_path && Storage::disk('public')->exists($dossier->fichier_path)) {
        return Storage::disk('public')->download(
            $dossier->fichier_path,
            $dossier->ordre_paiement . '.pdf'
        );
    }
    abort(404, 'Fichier PDF introuvable');
})->middleware(['web', 'auth'])->name('download.dossier.pdf');

// ══════════════════════════════════════════════════════════════
// EXPORT PDF DE TOUTES LES LISTES
// ══════════════════════════════════════════════════════════════
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/export/pdf/{type}', [ExportPdfController::class, 'export'])
        ->name('export.pdf');
});
