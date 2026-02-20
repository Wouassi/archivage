<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Réceptionne les fichiers scannés envoyés par Asprise Scanner.js
 * et les stocke temporairement pour fusion ultérieure lors de l'enregistrement du dossier.
 */
class ScanUploadController extends Controller
{
    /**
     * POST /scan/upload
     * Reçoit les images scannées (base64 ou fichiers) depuis Scanner.js
     */
    public function upload(Request $request): JsonResponse
    {
        $savedFiles = [];
        $sessionKey = 'larascan_files_' . ($request->input('session_id') ?: session()->getId());

        // ═══ SOURCE 1 : Fichiers multipart (Scanner.js form upload) ═══
        if ($request->hasFile('com_asprise_scannerjs_images')) {
            foreach ($request->file('com_asprise_scannerjs_images') as $file) {
                $saved = $this->storeFile($file);
                if ($saved) $savedFiles[] = $saved;
            }
        }

        // ═══ SOURCE 2 : Champ générique 'scanned_files' ═══
        if ($request->hasFile('scanned_files')) {
            $files = is_array($request->file('scanned_files'))
                ? $request->file('scanned_files')
                : [$request->file('scanned_files')];
            foreach ($files as $file) {
                $saved = $this->storeFile($file);
                if ($saved) $savedFiles[] = $saved;
            }
        }

        // ═══ SOURCE 3 : Base64 envoyé par JS ═══
        if ($request->has('images_base64')) {
            $images = is_array($request->input('images_base64'))
                ? $request->input('images_base64')
                : [$request->input('images_base64')];

            foreach ($images as $idx => $b64) {
                $saved = $this->storeBase64($b64, $idx);
                if ($saved) $savedFiles[] = $saved;
            }
        }

        // Accumuler en session
        $existing = session($sessionKey, []);
        $merged = array_merge($existing, $savedFiles);
        session([$sessionKey => $merged]);

        return response()->json([
            'success' => count($savedFiles) > 0,
            'count' => count($savedFiles),
            'files' => $savedFiles,
            'total_session' => count($merged),
            'message' => count($savedFiles) . ' page(s) numérisée(s)',
        ]);
    }

    /**
     * GET /scan/session-files
     * Retourne la liste des fichiers scannés dans la session courante
     */
    public function sessionFiles(Request $request): JsonResponse
    {
        $sessionKey = 'larascan_files_' . ($request->input('session_id') ?: session()->getId());
        $files = session($sessionKey, []);

        return response()->json([
            'files' => $files,
            'count' => count($files),
        ]);
    }

    /**
     * DELETE /scan/clear
     * Vide les fichiers scannés de la session
     */
    public function clear(Request $request): JsonResponse
    {
        $sessionKey = 'larascan_files_' . ($request->input('session_id') ?: session()->getId());
        $files = session($sessionKey, []);

        // Supprimer les fichiers temporaires
        foreach ($files as $f) {
            if (Storage::disk('public')->exists($f['path'])) {
                Storage::disk('public')->delete($f['path']);
            }
        }

        session()->forget($sessionKey);

        return response()->json(['success' => true, 'message' => 'Session scanner vidée']);
    }

    // ═══════════ HELPERS ═══════════

    private function storeFile($file): ?array
    {
        if (!$file || !$file->isValid()) return null;

        $ext = $file->getClientOriginalExtension() ?: 'pdf';
        $name = 'scan_' . Str::random(8) . '_' . now()->format('His') . '.' . $ext;
        $path = $file->storeAs('scans-tmp', $name, 'public');

        return [
            'name' => $name,
            'path' => $path,
            'size' => $file->getSize(),
            'size_human' => $this->humanFileSize($file->getSize()),
            'mime' => $file->getMimeType(),
            'url' => Storage::disk('public')->url($path),
        ];
    }

    private function storeBase64(string $b64, int $idx = 0): ?array
    {
        // Nettoyer le préfixe data URI si présent
        $b64Clean = preg_replace('/^data:[^;]+;base64,/', '', $b64);
        $binary = base64_decode($b64Clean);

        if (!$binary || strlen($binary) < 100) return null;

        // Détecter le format (PDF ou image)
        $isPdf = str_starts_with($binary, '%PDF');
        $ext = $isPdf ? 'pdf' : 'jpg';
        $name = 'scan_' . Str::random(8) . '_' . now()->format('His') . '_' . $idx . '.' . $ext;

        Storage::disk('public')->put("scans-tmp/{$name}", $binary);

        return [
            'name' => $name,
            'path' => "scans-tmp/{$name}",
            'size' => strlen($binary),
            'size_human' => $this->humanFileSize(strlen($binary)),
            'mime' => $isPdf ? 'application/pdf' : 'image/jpeg',
            'url' => Storage::disk('public')->url("scans-tmp/{$name}"),
        ];
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' Mo';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' Ko';
        return $bytes . ' o';
    }
}
