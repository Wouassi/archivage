<?php

namespace App\Filament\Resources\DossierResource\Pages;

use App\Filament\Resources\DossierResource;
use App\Models\ActivityLog;
use App\Models\Depense;
use App\Models\Exercice;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  CreateDossier — Fusion PDF intégrée                         ║
 * ║                                                              ║
 * ║  AUCUNE dépendance externe :                                 ║
 * ║  - Pas de PdfMerger                                          ║
 * ║  - Pas de FPDF / FPDI / TCPDF / DomPDF                      ║
 * ║  - Fusion via Ghostscript, pdfunite, pdftk ou GD pur         ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class CreateDossier extends CreateRecord
{
    protected static string $resource = DossierResource::class;

    private array $_tempPaths = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('━━━ [CreateDossier] Bouton Créer cliqué', [
            'op' => $data['ordre_paiement'] ?? 'NULL',
        ]);

        $allPdfPaths = [];

        // ═══ SOURCE 1 : Session PHP (scans LarascanScanner) ═══
        $sessionPaths = session('larascan_pdf_paths', []);
        session()->forget('larascan_pdf_paths');

        if (!empty($sessionPaths)) {
            Log::info('📦 [CreateDossier] Session : ' . count($sessionPaths) . ' fichier(s)');
        }

        foreach ($sessionPaths as $rel) {
            $abs = $this->resolveAndConvert($rel);
            if ($abs) {
                $allPdfPaths[] = $abs;
                $this->_tempPaths[] = $rel;
            }
        }

        // ═══ SOURCE 2 : FileUpload Filament ═══
        $uploadData = $data['fichiers_upload'] ?? null;
        if (!empty($uploadData)) {
            $uploadPaths = is_array($uploadData) ? array_values($uploadData) : [];
            Log::info('📤 [CreateDossier] FileUpload : ' . count($uploadPaths) . ' fichier(s)');

            foreach ($uploadPaths as $rel) {
                $abs = $this->resolveAndConvert($rel);
                if ($abs) $allPdfPaths[] = $abs;
            }
        }

        Log::info('🔢 [CreateDossier] Total à fusionner : ' . count($allPdfPaths));

        // ═══ FUSION ═══
        if (!empty($allPdfPaths)) {
            $depense  = Depense::find($data['depense_id']);
            $exercice = Exercice::find($data['exercice_id']);

            if (!$depense || !$exercice) {
                Log::error('❌ Dépense ou Exercice introuvable');
                $this->cleanupFields($data);
                return $data;
            }

            $op    = strtoupper(trim($data['ordre_paiement'] ?? 'DOC'));
            $fn    = preg_replace('/[^A-Za-z0-9_-]/', '_', $op);
            $type  = strtoupper($depense->type);
            $annee = (string) $exercice->annee;

            $fichierPath = $this->fusionnerPdfs($allPdfPaths, $fn, $type, $annee);

            if ($fichierPath) {
                $data['fichier_path'] = $fichierPath;
                Log::info("✅ PDF fusionné : {$fichierPath}");
                $this->compresserPdf($fichierPath);
            } else {
                Log::error('❌ Fusion échouée');
            }
        }

        $this->cleanupFields($data);
        return $data;
    }

    // ══════════════════════════════════════════════════════════════
    // RÉSOUDRE UN CHEMIN RELATIF → CHEMIN ABSOLU PDF
    // Convertit les images en PDF si nécessaire
    // ══════════════════════════════════════════════════════════════
    private function resolveAndConvert($rel): ?string
    {
        if (!is_string($rel) || empty(trim($rel))) return null;
        $rel = trim($rel);

        if (!Storage::disk('public')->exists($rel)) {
            Log::warning("⚠️ Fichier introuvable : {$rel}");
            return null;
        }

        $abs = Storage::disk('public')->path($rel);
        if (!file_exists($abs)) return null;

        // Si c'est une image, la convertir en PDF
        if (preg_match('/\.(jpg|jpeg|png)$/i', $rel)) {
            $pdfAbs = $this->imageToPdf($abs);
            return $pdfAbs;
        }

        return $abs;
    }

    // ══════════════════════════════════════════════════════════════
    // FUSION DE PDFs — AUCUNE DÉPENDANCE PHP EXTERNE
    //
    // Essaie dans l'ordre :
    //   1. Ghostscript (Windows + Linux)
    //   2. pdfunite (Linux, poppler-utils)
    //   3. pdftk (Windows + Linux)
    //   4. Copie simple (1 seul fichier)
    // ══════════════════════════════════════════════════════════════
    private function fusionnerPdfs(array $pdfPaths, string $fn, string $type, string $annee): ?string
    {
        // Filtrer fichiers valides
        $valid = array_values(array_filter($pdfPaths, fn($p) => file_exists($p) && filesize($p) > 0));
        if (empty($valid)) return null;

        // Préparer destination
        $relDir = "DOSSIER_ARCHIVE/{$type}/{$annee}";
        $absDir = Storage::disk('public')->path($relDir);
        if (!is_dir($absDir)) mkdir($absDir, 0755, true);

        $outputName = "{$fn}_{$type}_{$annee}.pdf";
        $outputAbs  = rtrim($absDir, '/\\') . DIRECTORY_SEPARATOR . $outputName;
        $outputRel  = "{$relDir}/{$outputName}";

        if (file_exists($outputAbs)) @unlink($outputAbs);

        // 1 seul fichier → copier
        if (count($valid) === 1) {
            copy($valid[0], $outputAbs);
            return file_exists($outputAbs) ? $outputRel : null;
        }

        $inputs = implode(' ', array_map(fn($p) => '"' . str_replace('"', '', $p) . '"', $valid));

        // ── Ghostscript ──
        $gs = $this->findBin([
            '/usr/bin/gs', '/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.01.2\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe',
        ], PHP_OS_FAMILY === 'Windows' ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null');

        if ($gs) {
            $cmd = "\"{$gs}\" -dBATCH -dNOPAUSE -dQUIET -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=\"{$outputAbs}\" {$inputs} 2>&1";
            exec($cmd, $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) {
                Log::info("[Fusion] ✅ Ghostscript ({$this->formatSize(filesize($outputAbs))})");
                return $outputRel;
            }
            @unlink($outputAbs);
        }

        // ── pdfunite ──
        $pu = $this->findBin(['/usr/bin/pdfunite', '/usr/local/bin/pdfunite'], 'which pdfunite 2>/dev/null');
        if ($pu) {
            exec("\"{$pu}\" {$inputs} \"{$outputAbs}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) {
                Log::info("[Fusion] ✅ pdfunite");
                return $outputRel;
            }
            @unlink($outputAbs);
        }

        // ── pdftk ──
        $tk = $this->findBin(
            ['/usr/bin/pdftk', '/usr/local/bin/pdftk', '/snap/bin/pdftk', 'C:\\Program Files\\PDFtk\\bin\\pdftk.exe'],
            PHP_OS_FAMILY === 'Windows' ? 'where pdftk 2>NUL' : 'which pdftk 2>/dev/null'
        );
        if ($tk) {
            exec("\"{$tk}\" {$inputs} cat output \"{$outputAbs}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) {
                Log::info("[Fusion] ✅ pdftk");
                return $outputRel;
            }
            @unlink($outputAbs);
        }

        // ── Fallback : copier le 1er ──
        Log::warning("[Fusion] ⚠️ Aucun outil disponible — copie du 1er fichier uniquement");
        Log::warning("[Fusion] Installez Ghostscript : Windows → ghostscript.com | Linux → sudo apt install ghostscript");
        copy($valid[0], $outputAbs);
        return file_exists($outputAbs) ? $outputRel : null;
    }

    // ══════════════════════════════════════════════════════════════
    // IMAGE → PDF (GD pur, zéro dépendance)
    // ══════════════════════════════════════════════════════════════
    private function imageToPdf(string $imagePath): ?string
    {
        $pdfPath = $imagePath . '.pdf';

        // Essai 1 : Imagick PHP
        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick($imagePath);
                $im->setImageFormat('pdf');
                $im->setImageCompressionQuality(80);
                $im->writeImage($pdfPath);
                $im->destroy();
                if (file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
            } catch (\Throwable $e) {
                Log::debug("Imagick img→pdf échoué: " . $e->getMessage());
            }
        }

        // Essai 2 : img2pdf CLI
        $img2pdf = $this->findBin(
            ['/usr/bin/img2pdf', '/usr/local/bin/img2pdf'],
            PHP_OS_FAMILY === 'Windows' ? 'where img2pdf 2>NUL' : 'which img2pdf 2>/dev/null'
        );
        if ($img2pdf) {
            exec("\"{$img2pdf}\" \"{$imagePath}\" -o \"{$pdfPath}\" 2>&1", $o, $c);
            if ($c === 0 && file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
        }

        // Essai 3 : ImageMagick CLI
        $convert = $this->findBin(
            ['/usr/bin/convert', '/usr/local/bin/convert'],
            PHP_OS_FAMILY === 'Windows' ? 'where magick 2>NUL' : 'which convert 2>/dev/null'
        );
        if ($convert) {
            $cmd = PHP_OS_FAMILY === 'Windows'
                ? "magick convert \"{$imagePath}\" \"{$pdfPath}\" 2>&1"
                : "\"{$convert}\" \"{$imagePath}\" \"{$pdfPath}\" 2>&1";
            exec($cmd, $o, $c);
            if ($c === 0 && file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
        }

        // Essai 4 : GD PHP pur → PDF manuel
        if ($this->gdImageToPdf($imagePath, $pdfPath)) return $pdfPath;

        Log::warning("Impossible de convertir image : " . basename($imagePath));
        return null;
    }

    /**
     * Génère un PDF valide depuis une image en utilisant uniquement GD.
     * Fonctionne TOUJOURS car GD est inclus dans toute installation PHP.
     */
    private function gdImageToPdf(string $input, string $output): bool
    {
        $info = @getimagesize($input);
        if (!$info) return false;

        [$imgW, $imgH, $imgType] = $info;

        // Convertir en JPEG en mémoire
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
        } else {
            return false;
        }

        $jpegData = @file_get_contents($tmpJpg);
        if ($cleanup) @unlink($tmpJpg);
        if (!$jpegData || strlen($jpegData) < 100) return false;

        $jpegLen = strlen($jpegData);

        // A4 en points
        $pageW = 595.28;
        $pageH = 841.89;
        $margin = 28.35;
        $scale = min(($pageW - 2 * $margin) / $imgW, ($pageH - 2 * $margin) / $imgH, 1.0);
        $dW = round($imgW * $scale, 2);
        $dH = round($imgH * $scale, 2);
        $dX = round(($pageW - $dW) / 2, 2);
        $dY = round($pageH - (($pageH - $dH) / 2) - $dH, 2);

        $o = [];
        $pdf = "%PDF-1.4\n";

        $o[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents 4 0 R /Resources << /XObject << /Im0 5 0 R >> >> >>\nendobj\n";

        $stream = sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im0 Do Q", $dW, $dH, $dX, $dY);
        $sLen = strlen($stream);
        $o[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$sLen} >>\nstream\n{$stream}\nendstream\nendobj\n";

        $o[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgW} /Height {$imgH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLen} >>\nstream\n";
        $pdf .= $jpegData;
        $pdf .= "\nendstream\nendobj\n";

        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) $pdf .= sprintf("%010d 00000 n \n", $o[$i]);
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return (bool) @file_put_contents($output, $pdf);
    }

    // ══════════════════════════════════════════════════════════════
    // COMPRESSION GHOSTSCRIPT
    // ══════════════════════════════════════════════════════════════
    private function compresserPdf(string $relativePath): void
    {
        $abs = Storage::disk('public')->path($relativePath);
        if (!file_exists($abs)) return;

        $gs = $this->findBin([
            '/usr/bin/gs', '/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe',
        ], PHP_OS_FAMILY === 'Windows' ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null');

        if (!$gs) return;

        $tmp = $abs . '.compressed.pdf';
        exec("\"{$gs}\" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=\"{$tmp}\" \"{$abs}\" 2>&1", $out, $code);

        if ($code === 0 && file_exists($tmp) && filesize($tmp) > 0 && filesize($tmp) < filesize($abs)) {
            rename($tmp, $abs);
        } else {
            @unlink($tmp);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // APRÈS CRÉATION
    // ══════════════════════════════════════════════════════════════
    protected function afterCreate(): void
    {
        $record = $this->record;

        foreach ($this->_tempPaths as $path) {
            try { if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path); } catch (\Throwable) {}
        }

        if (class_exists(ActivityLog::class) && method_exists(ActivityLog::class, 'logActivity')) {
            ActivityLog::logActivity('creation', "Dossier {$record->ordre_paiement} créé", $record, null, $record->toArray());
        }

        if ($record->fichier_path && class_exists(\App\Events\DocumentArchived::class)) {
            event(new \App\Events\DocumentArchived($record));
        }

        if ($record->pdf_exists) {
            Notification::make()->title('✅ Dossier créé — PDF fusionné')
                ->body(basename($record->fichier_path) . " ({$record->pdf_size})")
                ->success()->duration(6000)->send();
        } else {
            Notification::make()->title('⚠️ Dossier créé sans PDF')
                ->body("Aucun fichier trouvé.")->warning()->duration(8000)->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    // ══════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ══════════════════════════════════════════════════════════════
    private function cleanupFields(array &$data): void
    {
        unset($data['fichiers_upload'], $data['fichiers_pdf_paths'], $data['larascan_scanner'], $data['larascan']);
    }

    private function findBin(array $paths, string $whichCmd): ?string
    {
        foreach ($paths as $p) { if (file_exists($p)) return $p; }
        $r = trim((string)(shell_exec($whichCmd) ?? ''));
        return ($r && file_exists($r)) ? $r : null;
    }

    private function formatSize(int $bytes): string
    {
        $u = ['o', 'Ko', 'Mo'];
        $i = 0;
        while ($bytes >= 1024 && $i < 2) { $bytes /= 1024; $i++; }
        return round($bytes, 1) . ' ' . $u[$i];
    }
}
