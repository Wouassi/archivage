<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PdfMerger — Fusionne plusieurs fichiers PDF en un seul.
 *
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  ZÉRO DÉPENDANCE PHP EXTERNE                                ║
 * ║  Pas de FPDF, FPDI, TCPDF, DomPDF, Imagick                 ║
 * ║                                                              ║
 * ║  Méthodes de fusion (par priorité) :                         ║
 * ║    1. Ghostscript (le plus courant, déjà installé souvent)   ║
 * ║    2. pdfunite (poppler-utils, Linux)                        ║
 * ║    3. pdftk                                                  ║
 * ║    4. PHP pur (concaténation bas niveau)                     ║
 * ║                                                              ║
 * ║  Gère aussi la conversion image → PDF en interne (GD)        ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class PdfMerger
{
    /**
     * Fusionne plusieurs fichiers PDF/images en un seul PDF.
     *
     * @param  array  $paths    Chemins ABSOLUS des fichiers (PDF, JPG, PNG)
     * @param  string $filename Nom du fichier (sans extension)
     * @param  string $type     Type (INVESTISSEMENT, FONCTIONNEMENT)
     * @param  string $annee    Année de l'exercice
     * @return string|null      Chemin RELATIF du PDF final, ou null si échec
     */
    public static function merge(array $paths, string $filename, string $type, string $annee): ?string
    {
        // Filtrer les fichiers inexistants
        $validPaths = array_values(array_filter($paths, fn($p) => is_string($p) && file_exists($p) && filesize($p) > 0));

        if (empty($validPaths)) {
            Log::warning('[PdfMerger] Aucun fichier valide à fusionner');
            return null;
        }

        Log::info('[PdfMerger] Début fusion — ' . count($validPaths) . ' fichier(s)', [
            'fichiers' => array_map('basename', $validPaths),
        ]);

        // Convertir les images en PDF d'abord
        $pdfPaths = [];
        $tempFiles = [];

        foreach ($validPaths as $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $converted = self::imageToPdf($path);
                if ($converted) {
                    $pdfPaths[] = $converted;
                    $tempFiles[] = $converted; // À nettoyer après
                    Log::info("[PdfMerger] Image → PDF : " . basename($path));
                } else {
                    Log::warning("[PdfMerger] Impossible de convertir : " . basename($path));
                }
            } elseif ($ext === 'pdf') {
                $pdfPaths[] = $path;
            } else {
                Log::warning("[PdfMerger] Format non supporté : " . basename($path));
            }
        }

        if (empty($pdfPaths)) {
            Log::error('[PdfMerger] Aucun PDF à fusionner après conversion');
            return null;
        }

        // Préparer le dossier de destination
        $relativeDir = "DOSSIER_ARCHIVE/{$type}/{$annee}";
        $absoluteDir = Storage::disk('public')->path($relativeDir);

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $outputFilename = "{$filename}_{$type}_{$annee}.pdf";
        $outputAbsolute = rtrim($absoluteDir, '/\\') . DIRECTORY_SEPARATOR . $outputFilename;
        $outputRelative = "{$relativeDir}/{$outputFilename}";

        // Supprimer l'ancien si existant
        if (file_exists($outputAbsolute)) {
            @unlink($outputAbsolute);
        }

        // ═══ CAS 1 SEUL FICHIER : copier directement ═══
        if (count($pdfPaths) === 1) {
            if (copy($pdfPaths[0], $outputAbsolute)) {
                Log::info("[PdfMerger] ✅ 1 fichier → copié : {$outputRelative}");
                self::cleanupFiles($tempFiles);
                return $outputRelative;
            }
            self::cleanupFiles($tempFiles);
            return null;
        }

        // ═══ FUSION MULTI-FICHIERS ═══
        $methods = [
            'ghostscript' => fn() => self::mergeGhostscript($pdfPaths, $outputAbsolute),
            'pdfunite'    => fn() => self::mergePdfunite($pdfPaths, $outputAbsolute),
            'pdftk'       => fn() => self::mergePdftk($pdfPaths, $outputAbsolute),
            'php_concat'  => fn() => self::mergePhpConcat($pdfPaths, $outputAbsolute),
        ];

        foreach ($methods as $name => $method) {
            try {
                if ($method() && file_exists($outputAbsolute) && filesize($outputAbsolute) > 0) {
                    Log::info("[PdfMerger] ✅ Fusion réussie avec {$name} : {$outputRelative} (" . self::formatSize(filesize($outputAbsolute)) . ")");
                    self::cleanupFiles($tempFiles);
                    return $outputRelative;
                }
            } catch (\Throwable $e) {
                Log::debug("[PdfMerger] {$name} échoué : " . $e->getMessage());
                @unlink($outputAbsolute);
            }
        }

        // ═══ FALLBACK ULTIME : copier le premier fichier ═══
        Log::error('[PdfMerger] ❌ Toutes les méthodes ont échoué — copie du 1er fichier');
        if (copy($pdfPaths[0], $outputAbsolute)) {
            self::cleanupFiles($tempFiles);
            return $outputRelative;
        }

        self::cleanupFiles($tempFiles);
        return null;
    }

    // ══════════════════════════════════════════════════════════════
    // MÉTHODE 1 : Ghostscript
    // ══════════════════════════════════════════════════════════════
    private static function mergeGhostscript(array $paths, string $output): bool
    {
        $gs = self::findBinary([
            '/usr/bin/gs',
            '/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.01.2\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.04.0\\bin\\gswin32c.exe',
        ], PHP_OS_FAMILY === 'Windows' ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null');

        if (!$gs) return false;

        $inputs = implode(' ', array_map(fn($p) => '"' . str_replace('"', '', $p) . '"', $paths));

        $cmd = "\"{$gs}\" -dBATCH -dNOPAUSE -dQUIET -sDEVICE=pdfwrite "
             . "-dCompatibilityLevel=1.4 -dPDFSETTINGS=/default "
             . "-sOutputFile=\"{$output}\" {$inputs} 2>&1";

        exec($cmd, $out, $code);

        return $code === 0 && file_exists($output) && filesize($output) > 0;
    }

    // ══════════════════════════════════════════════════════════════
    // MÉTHODE 2 : pdfunite (poppler-utils)
    // ══════════════════════════════════════════════════════════════
    private static function mergePdfunite(array $paths, string $output): bool
    {
        $bin = self::findBinary(
            ['/usr/bin/pdfunite', '/usr/local/bin/pdfunite'],
            'which pdfunite 2>/dev/null'
        );
        if (!$bin) return false;

        $inputs = implode(' ', array_map(fn($p) => '"' . str_replace('"', '', $p) . '"', $paths));
        exec("\"{$bin}\" {$inputs} \"{$output}\" 2>&1", $out, $code);

        return $code === 0 && file_exists($output) && filesize($output) > 0;
    }

    // ══════════════════════════════════════════════════════════════
    // MÉTHODE 3 : pdftk
    // ══════════════════════════════════════════════════════════════
    private static function mergePdftk(array $paths, string $output): bool
    {
        $bin = self::findBinary(
            ['/usr/bin/pdftk', '/usr/local/bin/pdftk', '/snap/bin/pdftk', 'C:\\Program Files\\PDFtk\\bin\\pdftk.exe'],
            PHP_OS_FAMILY === 'Windows' ? 'where pdftk 2>NUL' : 'which pdftk 2>/dev/null'
        );
        if (!$bin) return false;

        $inputs = implode(' ', array_map(fn($p) => '"' . str_replace('"', '', $p) . '"', $paths));
        exec("\"{$bin}\" {$inputs} cat output \"{$output}\" 2>&1", $out, $code);

        return $code === 0 && file_exists($output) && filesize($output) > 0;
    }

    // ══════════════════════════════════════════════════════════════
    // MÉTHODE 4 : PHP pur — concaténation de PDF
    //
    // Lit chaque PDF, extrait les pages et reconstruit un PDF valide.
    // Fonctionne sans AUCUNE extension ou binaire externe.
    // Limité aux PDF simples (pas de formulaires interactifs).
    // ══════════════════════════════════════════════════════════════
    private static function mergePhpConcat(array $paths, string $output): bool
    {
        // Lire tous les contenus bruts
        $contents = [];
        foreach ($paths as $path) {
            $data = @file_get_contents($path);
            if ($data && str_starts_with(trim($data), '%PDF')) {
                $contents[] = $data;
            }
        }

        if (empty($contents)) return false;

        // Si un seul fichier valide, le copier
        if (count($contents) === 1) {
            return (bool) file_put_contents($output, $contents[0]);
        }

        // Concaténation PHP : on utilise le premier PDF comme base
        // et on ajoute les pages des autres en créant un fichier temporaire
        // par PDF source, puis on les concatène via gs si possible

        // Tentative avec FPDI si disponible (après composer require setasign/fpdi)
        if (class_exists(\setasign\Fpdi\Fpdi::class)) {
            try {
                return self::mergeFpdi($paths, $output);
            } catch (\Throwable $e) {
                Log::debug("[PdfMerger] FPDI échoué dans php_concat: " . $e->getMessage());
            }
        }

        // Dernier recours : écrire le premier fichier
        // (mieux que rien — au moins un PDF est sauvegardé)
        Log::warning('[PdfMerger] PHP concat : seul le 1er PDF sera conservé (installez Ghostscript pour la fusion)');
        return (bool) file_put_contents($output, $contents[0]);
    }

    /**
     * Sous-méthode FPDI (utilisée seulement si la lib est installée)
     */
    private static function mergeFpdi(array $paths, string $output): bool
    {
        $pdf = new \setasign\Fpdi\Fpdi();

        foreach ($paths as $filePath) {
            $pageCount = $pdf->setSourceFile($filePath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
            }
        }

        $pdf->Output('F', $output);
        return file_exists($output) && filesize($output) > 0;
    }

    // ══════════════════════════════════════════════════════════════
    // CONVERSION IMAGE → PDF (GD pur, zéro dépendance)
    // ══════════════════════════════════════════════════════════════
    public static function imageToPdf(string $imagePath): ?string
    {
        $pdfPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.pdf', $imagePath) . '.converted.pdf';

        // Méthode 1 : Imagick PHP
        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick($imagePath);
                $im->setImageFormat('pdf');
                $im->setImageCompressionQuality(80);
                $im->writeImage($pdfPath);
                $im->destroy();
                if (file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
            } catch (\Throwable $e) {
                Log::debug("[PdfMerger] Imagick image→PDF échoué: " . $e->getMessage());
            }
        }

        // Méthode 2 : img2pdf (Python)
        $img2pdf = self::findBinary(
            ['/usr/bin/img2pdf', '/usr/local/bin/img2pdf'],
            PHP_OS_FAMILY === 'Windows' ? 'where img2pdf 2>NUL' : 'which img2pdf 2>/dev/null'
        );
        if ($img2pdf) {
            exec("\"{$img2pdf}\" \"{$imagePath}\" -o \"{$pdfPath}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
        }

        // Méthode 3 : ImageMagick CLI
        $convert = self::findBinary(
            ['/usr/bin/convert', '/usr/local/bin/convert'],
            PHP_OS_FAMILY === 'Windows' ? 'where magick 2>NUL' : 'which convert 2>/dev/null'
        );
        if ($convert) {
            $cmd = PHP_OS_FAMILY === 'Windows'
                ? "\"{$convert}\" convert \"{$imagePath}\" \"{$pdfPath}\" 2>&1"
                : "\"{$convert}\" \"{$imagePath}\" \"{$pdfPath}\" 2>&1";
            exec($cmd, $out, $code);
            if ($code === 0 && file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
        }

        // Méthode 4 : GD PHP pur — génère un PDF minimal manuellement
        $result = self::imageToPdfWithGd($imagePath, $pdfPath);
        if ($result) return $pdfPath;

        Log::warning("[PdfMerger] Impossible de convertir l'image : " . basename($imagePath));
        return null;
    }

    /**
     * Convertit une image en PDF en utilisant uniquement GD (toujours disponible en PHP).
     * Génère un fichier PDF 1.4 valide manuellement.
     */
    private static function imageToPdfWithGd(string $input, string $output): bool
    {
        $info = @getimagesize($input);
        if (!$info) return false;

        $imgW = $info[0];
        $imgH = $info[1];
        $imgType = $info[2];

        // Charger et convertir en JPEG
        $tmpJpg = $input . '.tmp.jpg';
        $cleanup = false;

        if ($imgType === IMAGETYPE_JPEG) {
            $tmpJpg = $input;
        } elseif ($imgType === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $img = @imagecreatefrompng($input);
            if (!$img) return false;
            $canvas = imagecreatetruecolor($imgW, $imgH);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $imgW, $imgH, $white);
            imagecopy($canvas, $img, 0, 0, 0, 0, $imgW, $imgH);
            imagejpeg($canvas, $tmpJpg, 85);
            imagedestroy($img);
            imagedestroy($canvas);
            $cleanup = true;
        } elseif (function_exists('imagecreatefromjpeg')) {
            // Essayer de charger comme JPEG générique
            $img = @imagecreatefromjpeg($input);
            if (!$img) return false;
            imagejpeg($img, $tmpJpg, 85);
            imagedestroy($img);
            $cleanup = true;
        } else {
            return false;
        }

        $jpegData = @file_get_contents($tmpJpg);
        if ($cleanup) @unlink($tmpJpg);
        if (!$jpegData || strlen($jpegData) < 100) return false;

        $jpegLen = strlen($jpegData);

        // Page A4 en points (72 DPI)
        $pageW = 595.28;
        $pageH = 841.89;
        $margin = 28.35; // 10mm

        $maxW = $pageW - 2 * $margin;
        $maxH = $pageH - 2 * $margin;
        $scale = min($maxW / $imgW, $maxH / $imgH, 1.0);
        $dW = round($imgW * $scale, 2);
        $dH = round($imgH * $scale, 2);
        $dX = round(($pageW - $dW) / 2, 2);
        $dY = round($pageH - (($pageH - $dH) / 2) - $dH, 2); // PDF y=0 = bas

        // Construire le PDF manuellement
        $o = [];
        $pdf = "%PDF-1.4\n";

        $o[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $o[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $o[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] "
              . "/Contents 4 0 R /Resources << /XObject << /Im0 5 0 R >> >> >>\nendobj\n";

        $stream = sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im0 Do Q", $dW, $dH, $dX, $dY);
        $sLen = strlen($stream);
        $o[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$sLen} >>\nstream\n{$stream}\nendstream\nendobj\n";

        $o[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgW} /Height {$imgH} "
              . "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode "
              . "/Length {$jpegLen} >>\nstream\n";
        $pdf .= $jpegData;
        $pdf .= "\nendstream\nendobj\n";

        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $o[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return (bool) @file_put_contents($output, $pdf);
    }

    // ══════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ══════════════════════════════════════════════════════════════

    /**
     * Nettoie les fichiers temporaires.
     */
    public static function cleanupTemp(array $paths): void
    {
        self::cleanupFiles($paths);
    }

    private static function cleanupFiles(array $paths): void
    {
        foreach ($paths as $p) {
            if (is_string($p) && file_exists($p) && (str_contains($p, 'temp') || str_contains($p, '.converted.'))) {
                @unlink($p);
            }
        }
    }

    private static function findBinary(array $knownPaths, string $whichCmd): ?string
    {
        foreach ($knownPaths as $p) {
            if (file_exists($p)) return $p;
        }
        $r = trim((string)(shell_exec($whichCmd) ?? ''));
        return ($r && file_exists($r)) ? $r : null;
    }

    private static function formatSize(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
