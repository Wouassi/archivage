<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfMerger
{
    public static function merge(array $absPaths, string $filename, string $type, string $annee): ?string
    {
        $validPaths = array_filter($absPaths, fn($p) => file_exists($p));
        if (empty($validPaths)) { Log::warning('PdfMerger: Aucun fichier valide.'); return null; }

        try {
            $archiveDir = "DOSSIER_ARCHIVE/{$type}/{$annee}";
            $relativePath = "{$archiveDir}/{$filename}_{$type}_{$annee}.pdf";
            $absoluteDir = Storage::disk('public')->path($archiveDir);

            if (!is_dir($absoluteDir)) { mkdir($absoluteDir, 0755, true); }
            $outputPath = Storage::disk('public')->path($relativePath);

            if (count($validPaths) === 1) {
                copy($validPaths[0], $outputPath);
                return $relativePath;
            }

            // Fusion via pdfunite (Poppler)
            if (self::commandExists('pdfunite')) {
                $escaped = array_map('escapeshellarg', $validPaths);
                exec('pdfunite ' . implode(' ', $escaped) . ' ' . escapeshellarg($outputPath) . ' 2>&1', $out, $code);
                if ($code === 0 && file_exists($outputPath)) return $relativePath;
            }

            // Fusion via pdftk
            if (self::commandExists('pdftk')) {
                $escaped = array_map('escapeshellarg', $validPaths);
                exec('pdftk ' . implode(' ', $escaped) . ' cat output ' . escapeshellarg($outputPath) . ' 2>&1', $out, $code);
                if ($code === 0 && file_exists($outputPath)) return $relativePath;
            }

            // Fallback : copier le premier fichier
            copy(reset($validPaths), $outputPath);
            Log::warning('PdfMerger: Fallback copie (installer pdfunite ou pdftk pour fusion multi-PDF).');
            return $relativePath;

        } catch (\Exception $e) {
            Log::error("PdfMerger: {$e->getMessage()}");
            return null;
        }
    }

    private static function commandExists(string $cmd): bool
    {
        return !empty(trim(shell_exec((PHP_OS_FAMILY === 'Windows' ? 'where' : 'which') . " {$cmd} 2>/dev/null") ?? ''));
    }

    public static function cleanupTemp(array $paths): void
    {
        foreach ($paths as $p) {
            if (file_exists($p) && str_contains($p, 'uploads-tmp')) { unlink($p); }
        }
    }
}
